<?php

namespace App\Services;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptAnswer;
use App\Models\OgeAttemptEvent;
use App\Models\OgeAttemptScoring;
use App\Models\OgeAttemptTaskDetail;
use App\Models\OgeAttemptTaskTiming;
use App\Models\OgeVariant;
use App\Models\StudentTopicMastery;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OgeAttemptService
{
    /** @var array<string, array<int, string|null>> Per-request answer map cache */
    private array $answerMapCache = [];

    /** @var array<string, array<int, array>> Per-request task detail map cache */
    private array $detailMapCache = [];

    public function __construct(
        private readonly OgeVariantBuilderService $variantBuilder,
        private readonly TaskAnswerResolver $answerResolver,
        private readonly ?MiniAppTaskCanonicalizer $taskCanonicalizer = null,
    ) {
    }

    public function resolveVariant(string $hash, ?int $ownerTeacherId = null): OgeVariant
    {
        $variant = OgeVariant::where('hash', $hash)->first();

        if ($variant) {
            return $variant;
        }

        return OgeVariant::create([
            'hash' => $hash,
            'owner_teacher_id' => $ownerTeacherId,
            'title' => "Вариант {$hash}",
            'config_json' => null,
        ]);
    }

    public function startAttempt(User $student, string $hash, array $deviceMeta = []): array
    {
        return DB::transaction(function () use ($student, $hash, $deviceMeta) {
            $variant = $this->resolveVariant($hash);

            $attempt = OgeAttempt::query()
                ->where('variant_id', $variant->id)
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if ($attempt) {
                $attempt->update(['last_seen_at' => now()]);
            } else {
                $attempt = OgeAttempt::create([
                    'variant_id' => $variant->id,
                    'student_id' => $student->id,
                    'status' => 'active',
                    'device_meta' => $deviceMeta,
                    'started_at' => now(),
                    'last_seen_at' => now(),
                ]);

                $this->appendEvent($attempt, 'attempt_started', null, [
                    'variant_hash' => $hash,
                ]);
            }

            $this->ensureVariantTaskSnapshot($variant);
            $this->ensureFrozenAnswerSnapshot($attempt);

            return [$variant, $attempt];
        });
    }

    public function appendEvent(OgeAttempt $attempt, string $eventType, ?int $taskNumber = null, array $payload = [], $clientTs = null): OgeAttemptEvent
    {
        // Protect against concurrent requests producing the same seq
        // (unique key: attempt_id + seq). Retry a few times on duplicate-key race.
        for ($i = 0; $i < 5; $i++) {
            $nextSeq = (int) OgeAttemptEvent::where('attempt_id', $attempt->id)->max('seq') + 1;

            try {
                return OgeAttemptEvent::create([
                    'attempt_id' => $attempt->id,
                    'seq' => $nextSeq,
                    'event_type' => $eventType,
                    'task_number' => $taskNumber,
                    'payload_json' => $payload ?: null,
                    'client_ts' => $clientTs,
                    'server_ts' => now(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $message = (string) $e->getMessage();
                if (str_contains($message, 'oge_attempt_events_attempt_id_seq_unique') || str_contains($message, '1062 Duplicate entry')) {
                    usleep(15000); // 15ms backoff and retry
                    continue;
                }
                throw $e;
            }
        }

        // Final fallback: force-gap seq and try once more
        $nextSeq = (int) OgeAttemptEvent::where('attempt_id', $attempt->id)->max('seq') + 2;
        return OgeAttemptEvent::create([
            'attempt_id' => $attempt->id,
            'seq' => $nextSeq,
            'event_type' => $eventType,
            'task_number' => $taskNumber,
            'payload_json' => $payload ?: null,
            'client_ts' => $clientTs,
            'server_ts' => now(),
        ]);
    }

    public function commitAnswer(OgeAttempt $attempt, int $taskNumber, string $answer, $clientTs = null): OgeAttemptAnswer
    {
        return DB::transaction(function () use ($attempt, $taskNumber, $answer, $clientTs) {
            $this->appendEvent($attempt, 'answer_committed', $taskNumber, [
                'answer' => $answer,
            ], $clientTs);

            $answerProjection = OgeAttemptAnswer::firstOrCreate(
                ['attempt_id' => $attempt->id, 'task_number' => $taskNumber],
                [
                    'current_answer' => null,
                    'commits_count' => 0,
                    'first_committed_at' => null,
                    'last_committed_at' => null,
                    'is_final' => false,
                ]
            );

            if ($answerProjection->commits_count === 0) {
                $answerProjection->first_committed_at = now();
            }

            $answerProjection->current_answer = $answer;
            $answerProjection->commits_count += 1;
            $answerProjection->last_committed_at = now();
            $answerProjection->save();

            $this->upsertScoringForTask($attempt, $taskNumber, $answerProjection->current_answer);

            return $answerProjection;
        });
    }

    /**
     * Save all answers in a single transaction. Called once at submit time.
     * Replaces the old per-keystroke commit approach to eliminate race conditions.
     */
    public function commitAnswersBatch(OgeAttempt $attempt, array $answers): void
    {
        DB::transaction(function () use ($attempt, $answers) {
            $now = now();

            foreach ($answers as $taskNumber => $answer) {
                $taskNumber = (int) $taskNumber;
                if ($taskNumber < 1 || $taskNumber > 255) {
                    continue;
                }

                $answer = trim($answer);
                if ($answer === '') {
                    continue;
                }

                $this->appendEvent($attempt, 'answer_committed', $taskNumber, [
                    'answer' => $answer,
                ]);

                $answerProjection = OgeAttemptAnswer::firstOrCreate(
                    ['attempt_id' => $attempt->id, 'task_number' => $taskNumber],
                    [
                        'current_answer' => null,
                        'commits_count' => 0,
                        'first_committed_at' => null,
                        'last_committed_at' => null,
                        'is_final' => false,
                    ]
                );

                if ($answerProjection->commits_count === 0) {
                    $answerProjection->first_committed_at = $now;
                }

                $answerProjection->current_answer = $answer;
                $answerProjection->commits_count += 1;
                $answerProjection->last_committed_at = $now;
                $answerProjection->save();

                $this->upsertScoringForTask($attempt, $taskNumber, $answer);
            }
        });
    }

    public function touchTiming(OgeAttempt $attempt, int $taskNumber, string $eventType, $clientTs = null): OgeAttemptTaskTiming
    {
        return DB::transaction(function () use ($attempt, $taskNumber, $eventType, $clientTs) {
            $this->appendEvent($attempt, $eventType, $taskNumber, [], $clientTs);

            $timing = OgeAttemptTaskTiming::firstOrCreate(
                ['attempt_id' => $attempt->id, 'task_number' => $taskNumber],
                [
                    'active_ms' => 0,
                    'focus_count' => 0,
                    'heartbeat_count' => 0,
                    'blur_count' => 0,
                    'first_focused_at' => null,
                    'last_focus_at' => null,
                    'last_heartbeat_at' => null,
                ]
            );

            $now = now();

            if ($eventType === 'task_focused') {
                $timing->focus_count += 1;
                $timing->first_focused_at = $timing->first_focused_at ?? $now;
                $timing->last_focus_at = $now;
                $timing->last_heartbeat_at = $now;
            }

            if ($eventType === 'heartbeat') {
                $timing->heartbeat_count += 1;
                if ($timing->last_focus_at) {
                    $anchor = $timing->last_heartbeat_at ?? $timing->last_focus_at;
                    $delta = $anchor ? $anchor->diffInMilliseconds($now) : 0;
                    $timing->active_ms += min(max($delta, 0), 30000);
                }

                $timing->last_heartbeat_at = $now;
            }

            if ($eventType === 'task_blurred') {
                $timing->blur_count += 1;
                if ($timing->last_focus_at) {
                    $anchor = $timing->last_heartbeat_at ?? $timing->last_focus_at;
                    $delta = $anchor ? $anchor->diffInMilliseconds($now) : 0;
                    $timing->active_ms += min(max($delta, 0), 30000);
                }

                $timing->last_focus_at = null;
                $timing->last_heartbeat_at = null;
            }

            $timing->save();

            return $timing;
        });
    }

    public function submitAttempt(OgeAttempt $attempt, $clientTs = null): OgeAttempt
    {
        DB::transaction(function () use ($attempt, $clientTs) {
            $this->appendEvent($attempt, 'attempt_submitted', null, [], $clientTs);

            $this->finalizeOpenTimings($attempt);

            $attempt->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'last_seen_at' => now(),
            ]);

            OgeAttemptAnswer::where('attempt_id', $attempt->id)->update(['is_final' => true]);
        });

        try {
            $freshAttempt = $attempt->fresh(['variant', 'answers']);
            $this->scoreAttempt($freshAttempt);
            $this->persistTaskDetails($freshAttempt);
            $attempt->update(['status' => 'scored']);
            $this->appendEvent($attempt, 'attempt_scored');

            // Update mastery aggregates (non-critical — failures are logged, not re-thrown)
            try {
                $this->updateStudentMastery($freshAttempt);
            } catch (\Throwable $e) {
                Log::warning('Failed to update student mastery after scoring', [
                    'attempt_id' => $attempt->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            $attempt->update(['status' => 'error']);
            $this->appendEvent($attempt, 'attempt_scoring_failed', null, [
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            Log::error('OGE attempt scoring failed after submit', [
                'attempt_id' => $attempt->id,
                'variant_id' => $attempt->variant_id,
                'student_id' => $attempt->student_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $attempt->fresh();
    }

    public function rebuildScoring(OgeAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {
            $freshAttempt = $attempt->fresh(['variant', 'answers']);
            $this->scoreAttempt($freshAttempt);
            $this->persistTaskDetails($freshAttempt);
        });
    }

    public function buildAttemptStatusPayload(OgeAttempt $attempt): array
    {
        return $this->buildAttemptReadPayload($attempt, false);
    }

    public function buildAttemptResultPayload(OgeAttempt $attempt): array
    {
        return $this->buildAttemptReadPayload($attempt, true);
    }

    // -----------------------------------------------------------------------
    // Task detail persistence
    // -----------------------------------------------------------------------

    /**
     * Persist task fingerprints (topic_id, type, svg_type, section, etc.)
     * for each task in the attempt. Called once at scoring time.
     */
    private function persistTaskDetails(OgeAttempt $attempt): void
    {
        $detailMap = $this->getTaskDetailMap($attempt);
        if (empty($detailMap)) {
            return;
        }

        foreach ($detailMap as $taskNumber => $meta) {
            $topicId = (string) ($meta['topic_id'] ?? str_pad((string) $taskNumber, 2, '0', STR_PAD_LEFT));
            $blockNumber = (int) ($meta['block_number'] ?? 0);
            $zadanieNumber = (int) ($meta['zadanie_number'] ?? 0);
            $taskIndex = isset($meta['task_index']) ? (int) $meta['task_index'] : null;
            $taskType = (string) ($meta['task_type'] ?? 'unknown');
            $svgType = $meta['svg_type'] ?? null;
            $subtype = $meta['subtype'] ?? null;
            $section = $meta['section'] ?? null;
            $source = $meta['source'] ?? null;

            $taskKey = OgeAttemptTaskDetail::buildTaskKey($topicId, $blockNumber, $zadanieNumber, $taskIndex);
            $taskFingerprint = self::buildTaskFingerprint([
                'topic_id' => $topicId,
                'block_number' => $blockNumber,
                'zadanie_number' => $zadanieNumber,
                'task_index' => $taskIndex,
                'task_type' => $taskType,
                'svg_type' => $svgType,
                'subtype' => $subtype,
                'section' => $section,
                'source' => $source,
            ]);

            OgeAttemptTaskDetail::updateOrCreate(
                ['attempt_id' => $attempt->id, 'task_number' => $taskNumber],
                [
                    'topic_id' => $topicId,
                    'block_number' => $blockNumber,
                    'zadanie_number' => $zadanieNumber,
                    'task_index' => $taskIndex,
                    'task_type' => $taskType,
                    'svg_type' => $svgType,
                    'subtype' => $subtype,
                    'section' => $section,
                    'source' => $source,
                    'task_key' => $taskKey,
                    'task_fingerprint' => $taskFingerprint,
                ],
            );
        }
    }

    // -----------------------------------------------------------------------
    // Student mastery aggregation
    // -----------------------------------------------------------------------

    /**
     * After scoring, update per-student per-topic-type mastery stats.
     * Uses exponentially weighted average (EWA) for mastery_score.
     */
    private function updateStudentMastery(OgeAttempt $attempt): void
    {
        $studentId = (int) $attempt->student_id;
        if ($studentId <= 0) {
            return;
        }

        $attempt->loadMissing(['scorings', 'taskTimings', 'taskDetails']);

        $scoringsByTask = $attempt->scorings->keyBy(fn ($s) => (int) $s->task_number);
        $timingsByTask = $attempt->taskTimings->keyBy(fn ($t) => (int) $t->task_number);
        $detailsByTask = $attempt->taskDetails->keyBy(fn ($d) => (int) $d->task_number);

        foreach ($detailsByTask as $taskNumber => $detail) {
            $scoring = $scoringsByTask->get($taskNumber);
            if ($scoring === null || $scoring->is_correct === null) {
                continue; // skip unchecked tasks
            }

            $timing = $timingsByTask->get($taskNumber);
            $activeMs = (int) ($timing?->active_ms ?? 0);
            $isCorrect = (bool) $scoring->is_correct;

            // Composite key for mastery: topic_id + task_type + svg_type + section
            $compositeKey = [
                'student_id' => $studentId,
                'topic_id' => $detail->topic_id,
                'task_type' => $detail->task_type,
                'svg_type' => $detail->svg_type,
                'subtype' => $detail->subtype,
                'section' => $detail->section,
            ];

            $mastery = StudentTopicMastery::firstOrCreate($compositeKey, [
                'attempts_count' => 0,
                'correct_count' => 0,
                'total_active_ms' => 0,
                'avg_active_ms' => 0,
                'accuracy' => 0,
                'mastery_score' => 0.5, // neutral starting point
                'recent_outcomes' => [],
                'current_correct_streak' => 0,
                'current_incorrect_streak' => 0,
                'last_outcome' => null,
            ]);

            $mastery->attempts_count += 1;
            if ($isCorrect) {
                $mastery->correct_count += 1;
            }
            $mastery->total_active_ms += $activeMs;
            $mastery->avg_active_ms = $mastery->attempts_count > 0
                ? (int) ($mastery->total_active_ms / $mastery->attempts_count)
                : 0;
            $mastery->accuracy = $mastery->attempts_count > 0
                ? round($mastery->correct_count / $mastery->attempts_count, 4)
                : 0;

            // EWA mastery: new = alpha * observation + (1-alpha) * old
            // alpha = 0.3 gives reasonable weight to recent performance
            $alpha = 0.3;
            $observation = $isCorrect ? 1.0 : 0.0;
            $mastery->mastery_score = round($alpha * $observation + (1 - $alpha) * $mastery->mastery_score, 4);
            $mastery->recent_outcomes = self::appendRecentOutcome($mastery->recent_outcomes, $isCorrect);
            if ($isCorrect) {
                $mastery->current_correct_streak += 1;
                $mastery->current_incorrect_streak = 0;
            } else {
                $mastery->current_incorrect_streak += 1;
                $mastery->current_correct_streak = 0;
            }
            $mastery->last_outcome = $isCorrect;
            $mastery->last_attempted_at = now();
            $mastery->save();
        }
    }

    // -----------------------------------------------------------------------
    // Internal scoring
    // -----------------------------------------------------------------------

    private function finalizeOpenTimings(OgeAttempt $attempt): void
    {
        $attempt->loadMissing('taskTimings');

        $now = now();
        foreach ($attempt->taskTimings as $timing) {
            if (!$timing->last_focus_at) {
                continue;
            }

            $anchor = $timing->last_heartbeat_at ?? $timing->last_focus_at;
            $delta = $anchor ? $anchor->diffInMilliseconds($now) : 0;
            $timing->active_ms += min(max($delta, 0), 30000);
            $timing->last_focus_at = null;
            $timing->last_heartbeat_at = null;
            $timing->save();
        }
    }

    private function scoreAttempt(OgeAttempt $attempt): void
    {
        $correctByTaskNumber = is_array($attempt->frozen_answers_json)
            ? $attempt->frozen_answers_json
            : [];

        if (empty($correctByTaskNumber)) {
            Log::warning('Scoring from live data (no frozen answers)', [
                'attempt_id' => $attempt->id,
                'variant_id' => $attempt->variant_id,
            ]);

            $correctByTaskNumber = $this->getCorrectAnswerMap($attempt);
        }

        if (empty($correctByTaskNumber)) {
            return;
        }

        $attempt->loadMissing('answers');
        foreach ($attempt->answers as $answer) {
            $taskNumber = (int) $answer->task_number;
            $this->persistScoringRow($attempt->id, $taskNumber, $answer->current_answer, $correctByTaskNumber[$taskNumber] ?? null);
        }
    }

    private function upsertScoringForTask(OgeAttempt $attempt, int $taskNumber, ?string $userAnswer): void
    {
        $attempt->loadMissing('variant');
        $hash = $attempt->variant?->hash;
        if (!$hash) {
            return;
        }

        $correctMap = is_array($attempt->frozen_answers_json)
            ? $attempt->frozen_answers_json
            : [];

        if (empty($correctMap)) {
            $correctMap = $this->getCorrectAnswerMap($attempt);
        }

        $correct = $correctMap[$taskNumber] ?? null;

        $this->persistScoringRow($attempt->id, $taskNumber, $userAnswer, $correct);
    }

    /**
     * Build a task_number => correct_answer map for the variant, cached per request.
     *
     * @return array<int, string|null>
     */
    private function getCorrectAnswerMap(OgeAttempt $attempt): array
    {
        $cacheKey = implode(':', [
            (string) $attempt->id,
            (string) ($attempt->variant_id ?? 0),
            (string) ($attempt->variant?->hash ?? ''),
        ]);

        if (isset($this->answerMapCache[$cacheKey])) {
            return $this->answerMapCache[$cacheKey];
        }

        // Populate both answer map and detail map together to avoid double work
        [$answerMap, $detailMap] = $this->buildVariantMaps($attempt);

        $this->answerMapCache[$cacheKey] = $answerMap;
        $this->detailMapCache[$cacheKey] = $detailMap;

        return $answerMap;
    }

    private function ensureVariantTaskSnapshot(OgeVariant $variant): void
    {
        if ($variant->isCustomRandom()) {
            return;
        }

        $config = is_array($variant->config_json ?? null) ? $variant->config_json : [];
        if (is_array($config['tasks'] ?? null) && !empty($config['tasks'])) {
            return;
        }

        $selected = is_array($config['zadaniya'] ?? null) ? $config['zadaniya'] : null;
        $variantPayload = $this->variantBuilder->build($variant->hash, $selected);
        $tasks = is_array($variantPayload['tasks'] ?? null) ? $variantPayload['tasks'] : [];
        if (empty($tasks)) {
            return;
        }

        $config['tasks'] = $tasks;

        $variant->forceFill([
            'config_json' => $config,
        ])->save();

        $variant->refresh();
    }

    private function ensureFrozenAnswerSnapshot(OgeAttempt $attempt): void
    {
        if (is_array($attempt->frozen_answers_json) && !empty($attempt->frozen_answers_json)) {
            return;
        }

        $correctMap = $this->getCorrectAnswerMap($attempt);
        if (empty($correctMap)) {
            return;
        }

        $canonicalizer = $this->taskCanonicalizer ?? app(MiniAppTaskCanonicalizer::class);
        $variantTasksByNumber = $this->resolveVariantTasksByTaskNumber($attempt);

        foreach ($correctMap as $taskNumber => $answer) {
            if ($answer === null || $answer === '') {
                continue;
            }

            $task = $variantTasksByNumber[(int) $taskNumber] ?? null;
            if (!is_array($task)) {
                continue;
            }

            $normalizedTask = $canonicalizer->normalizeForUi($task);
            $kind = (string) ($normalizedTask['answer_kind'] ?? '');
            if ($kind !== 'choice_index') {
                continue;
            }

            $canonicalOptionId = $normalizedTask['canonical_option_id'] ?? null;
            if (is_string($canonicalOptionId) && $canonicalOptionId !== '') {
                $correctMap[(int) $taskNumber] = $canonicalOptionId;
            }
        }

        $attempt->forceFill([
            'frozen_answers_json' => $correctMap,
        ])->save();

        $attempt->refresh();
    }

    /**
     * Build a task_number => metadata map for task fingerprinting.
     *
     * @return array<int, array{topic_id: string, block_number: int, zadanie_number: int, task_index: int|null, task_type: string, svg_type: string|null, section: string|null}>
     */
    private function getTaskDetailMap(OgeAttempt $attempt): array
    {
        // Ensure the answer map (and thus the detail map) is built
        $this->getCorrectAnswerMap($attempt);

        $cacheKey = implode(':', [
            (string) $attempt->id,
            (string) ($attempt->variant_id ?? 0),
            (string) ($attempt->variant?->hash ?? ''),
        ]);

        return $this->detailMapCache[$cacheKey] ?? [];
    }

    /**
     * @return array<int, array>
     */
    private function resolveVariantTasksByTaskNumber(OgeAttempt $attempt): array
    {
        $hash = $attempt->variant?->hash;
        if (!$hash) {
            return [];
        }

        $tasks = [];

        if ($attempt->variant?->isCustomRandom()) {
            $customTasks = $attempt->variant?->config_json['custom_tasks'] ?? [];
            if ((!is_array($customTasks) || empty($customTasks)) && is_string($hash) && $hash !== '') {
                $customTasks = $this->loadCustomRandomTasksByHash($hash);
            }
            if (is_array($customTasks)) {
                $tasks = $customTasks;
            }
        } else {
            $configuredTasks = $attempt->variant?->config_json['tasks'] ?? null;
            if (is_array($configuredTasks) && !empty($configuredTasks)) {
                $tasks = $configuredTasks;
            } else {
                $selected = $attempt->variant?->config_json['zadaniya'] ?? null;
                $variantPayload = $this->variantBuilder->build($hash, is_array($selected) ? $selected : null);
                $tasks = is_array($variantPayload['tasks'] ?? null) ? $variantPayload['tasks'] : [];
            }
        }

        $indexed = [];
        foreach ($tasks as $index => $taskData) {
            if (!is_array($taskData)) {
                continue;
            }

            $tn = (int) ($taskData['attempt_task_number'] ?? $taskData['task_number'] ?? $taskData['test_number'] ?? ($attempt->variant?->isCustomRandom() ? ($index + 1) : (6 + $index)));
            if ($tn < 1 || $tn > 255) {
                continue;
            }

            $indexed[$tn] = $taskData;
        }

        return $indexed;
    }

    /**
     * Build both the correct-answer map and the task-detail map from the variant
     * in a single pass, avoiding double variant reconstruction.
     *
     * @return array{0: array<int, string|null>, 1: array<int, array>}
     */
    private function buildVariantMaps(OgeAttempt $attempt): array
    {
        $hash = $attempt->variant?->hash;
        if (!$hash) {
            return [[], []];
        }

        if ($attempt->variant?->isCustomRandom()) {
            return $this->buildMapsFromCustomRandom($attempt, $hash);
        }

        $configuredTasks = $attempt->variant?->config_json['tasks'] ?? null;
        if (is_array($configuredTasks) && !empty($configuredTasks)) {
            return $this->buildMapsFromTaskArray($configuredTasks, 6);
        }

        $selected = $attempt->variant?->config_json['zadaniya'] ?? null;
        $variantPayload = $this->variantBuilder->build($hash, is_array($selected) ? $selected : null);

        return $this->buildMapsFromTaskArray($variantPayload['tasks'] ?? [], 6);
    }

    /**
     * @return array{0: array<int, string|null>, 1: array<int, array>}
     */
    private function buildMapsFromCustomRandom(OgeAttempt $attempt, string $hash): array
    {
        $customTasks = $attempt->variant?->config_json['custom_tasks'] ?? [];
        if ((!is_array($customTasks) || empty($customTasks)) && is_string($hash) && $hash !== '') {
            $customTasks = $this->loadCustomRandomTasksByHash($hash);
        }

        if (!is_array($customTasks) || empty($customTasks)) {
            return [[], []];
        }

        $answerMap = [];
        $detailMap = [];

        foreach ($customTasks as $index => $taskData) {
            if (!is_array($taskData)) {
                continue;
            }

            $tn = (int) ($taskData['attempt_task_number'] ?? $taskData['task_number'] ?? $taskData['test_number'] ?? ($index + 1));
            if ($tn < 1 || $tn > 255) {
                continue;
            }

            $answerMap[$tn] = $this->answerResolver->resolveFromVariantTask($taskData);
            $detailMap[$tn] = self::extractTaskMeta($taskData, $tn);
        }

        return [$answerMap, $detailMap];
    }

    /**
     * @return array{0: array<int, string|null>, 1: array<int, array>}
     */
    private function buildMapsFromTaskArray(array $tasks, int $defaultStartTn): array
    {
        $answerMap = [];
        $detailMap = [];

        foreach ($tasks as $index => $taskData) {
            if (!is_array($taskData)) {
                continue;
            }

            $tn = (int) ($taskData['attempt_task_number'] ?? $taskData['task_number'] ?? $taskData['test_number'] ?? ($defaultStartTn + $index));
            if ($tn < 1 || $tn > 255) {
                continue;
            }

            $answerMap[$tn] = $this->answerResolver->resolveFromVariantTask($taskData);
            $detailMap[$tn] = self::extractTaskMeta($taskData, $tn);
        }

        return [$answerMap, $detailMap];
    }

    /**
     * Extract task fingerprint metadata from a variant task payload.
     */
    private static function extractTaskMeta(array $taskData, int $taskNumber): array
    {
        $topicId = $taskData['topic_id']
            ?? str_pad((string) $taskNumber, 2, '0', STR_PAD_LEFT);

        return [
            'topic_id' => (string) $topicId,
            'block_number' => (int) ($taskData['block_number'] ?? 0),
            'zadanie_number' => (int) ($taskData['zadanie_number'] ?? 0),
            'task_index' => isset($taskData['task']['id']) ? (int) $taskData['task']['id'] : null,
            'task_type' => (string) ($taskData['type'] ?? 'unknown'),
            'svg_type' => $taskData['svg_type'] ?? null,
            'subtype' => $taskData['subtype'] ?? $taskData['task']['subtype'] ?? $taskData['svg_type'] ?? null,
            'section' => $taskData['section'] ?? null,
            'source' => $taskData['source'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $meta
     */
    private static function buildTaskFingerprint(array $meta): string
    {
        $parts = [
            (string) ($meta['topic_id'] ?? ''),
            (string) ($meta['block_number'] ?? 0),
            (string) ($meta['zadanie_number'] ?? 0),
            (string) ($meta['task_index'] ?? ''),
            (string) ($meta['task_type'] ?? ''),
            (string) ($meta['svg_type'] ?? ''),
            (string) ($meta['subtype'] ?? ''),
            (string) ($meta['section'] ?? ''),
            (string) ($meta['source'] ?? ''),
        ];

        return hash('sha256', implode('|', $parts));
    }

    /**
     * @param mixed $existing
     * @return array<int, bool>
     */
    private static function appendRecentOutcome($existing, bool $isCorrect): array
    {
        $history = is_array($existing) ? $existing : [];
        $history[] = $isCorrect;

        return array_slice(array_values($history), -10);
    }

    /**
     * Legacy compatibility: some custom variants keep task payload outside config_json.
     *
     * @return array<int, mixed>
     */
    private function loadCustomRandomTasksByHash(string $hash): array
    {
        $cacheKey = "custom_random_test_{$hash}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        $path = "custom_random_tests/{$hash}.json";
        if (!Storage::disk('local')->exists($path)) {
            return [];
        }

        try {
            $decoded = json_decode((string) Storage::disk('local')->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::warning('Failed to read custom random task payload for scoring', [
                'variant_hash' => $hash,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        if (!is_array($decoded) || empty($decoded)) {
            return [];
        }

        Cache::put($cacheKey, $decoded, now()->addDays(7));

        return $decoded;
    }

    private function persistScoringRow(int $attemptId, int $taskNumber, ?string $userAnswer, ?string $correctAnswer): void
    {
        $isCorrect = $this->answerResolver->isCorrect($userAnswer, $correctAnswer);

        OgeAttemptScoring::updateOrCreate(
            ['attempt_id' => $attemptId, 'task_number' => $taskNumber],
            [
                'is_correct' => $isCorrect,
                'correct_answer' => $correctAnswer,
                'checked_at' => now(),
            ],
        );
    }

    private function buildAttemptReadPayload(OgeAttempt $attempt, bool $includeScoring): array
    {
        $attempt->loadMissing([
            'variant',
            'student:id,name,email',
            'answers',
            'taskTimings',
            'scorings',
        ]);

        $taskNumbers = $this->resolveAttemptTaskNumbers($attempt);

        $answersByTask = $attempt->answers
            ->keyBy(fn (OgeAttemptAnswer $answer) => (int) $answer->task_number);
        $timingsByTask = $attempt->taskTimings
            ->keyBy(fn (OgeAttemptTaskTiming $timing) => (int) $timing->task_number);
        $scoringsByTask = $attempt->scorings
            ->keyBy(fn (OgeAttemptScoring $scoring) => (int) $scoring->task_number);

        $tasks = [];
        $answeredCount = 0;
        $correctCount = 0;
        $incorrectCount = 0;
        $uncheckedCount = 0;
        $unansweredCount = 0;
        $totalActiveMs = 0;

        foreach ($taskNumbers as $taskNumber) {
            $answer = $answersByTask->get($taskNumber);
            $timing = $timingsByTask->get($taskNumber);
            $scoring = $scoringsByTask->get($taskNumber);

            $answerValue = $answer?->current_answer;
            $hasAnswer = is_string($answerValue) && trim($answerValue) !== '';
            $hasTiming = $timing && (((int) $timing->focus_count) > 0 || ((int) $timing->active_ms) > 0);

            $activeMs = (int) ($timing?->active_ms ?? 0);
            $totalActiveMs += $activeMs;

            if ($hasAnswer) {
                $answeredCount++;
            } else {
                $unansweredCount++;
            }

            if ($includeScoring) {
                if (!$hasAnswer) {
                    $taskStatus = 'unanswered';
                } elseif ($scoring?->is_correct === true) {
                    $taskStatus = 'correct';
                    $correctCount++;
                } elseif ($scoring?->is_correct === false) {
                    $taskStatus = 'incorrect';
                    $incorrectCount++;
                } else {
                    $taskStatus = 'unchecked';
                    $uncheckedCount++;
                }
            } else {
                $taskStatus = $hasAnswer ? 'answered' : ($hasTiming ? 'seen' : 'unanswered');
            }

            $taskRow = [
                'task_number' => $taskNumber,
                'status' => $taskStatus,
                'answer' => $answerValue,
                'commits_count' => (int) ($answer?->commits_count ?? 0),
                'is_final' => (bool) ($answer?->is_final ?? false),
                'first_committed_at' => optional($answer?->first_committed_at)->toIso8601String(),
                'last_committed_at' => optional($answer?->last_committed_at)->toIso8601String(),
                'active_ms' => $activeMs,
                'focus_count' => (int) ($timing?->focus_count ?? 0),
                'heartbeat_count' => (int) ($timing?->heartbeat_count ?? 0),
                'blur_count' => (int) ($timing?->blur_count ?? 0),
                'first_focused_at' => optional($timing?->first_focused_at)->toIso8601String(),
                'last_focus_at' => optional($timing?->last_focus_at)->toIso8601String(),
                'last_heartbeat_at' => optional($timing?->last_heartbeat_at)->toIso8601String(),
            ];

            if ($includeScoring) {
                $taskRow['is_correct'] = $scoring?->is_correct;
                $taskRow['correct_answer'] = $scoring?->correct_answer;
                $taskRow['checked_at'] = optional($scoring?->checked_at)->toIso8601String();
            }

            $tasks[] = $taskRow;
        }

        $awayMsTotal = (int) (($attempt->device_meta ?? [])['away_ms_total'] ?? 0);
        $durationMs = null;
        if ($attempt->started_at && $attempt->submitted_at) {
            $durationMs = max(0, $attempt->started_at->diffInMilliseconds($attempt->submitted_at, false));
        }

        $baseAttempt = [
            'id' => (int) $attempt->id,
            'status' => (string) $attempt->status,
            'locked' => $attempt->status !== 'active',
            'variant_id' => (int) $attempt->variant_id,
            'variant_hash' => (string) ($attempt->variant?->hash ?? ''),
            'is_custom' => (bool) $attempt->variant?->isCustomRandom(),
            'student_id' => (int) $attempt->student_id,
            'started_at' => optional($attempt->started_at)->toIso8601String(),
            'submitted_at' => optional($attempt->submitted_at)->toIso8601String(),
            'last_seen_at' => optional($attempt->last_seen_at)->toIso8601String(),
        ];

        if ($includeScoring) {
            $baseAttempt['student'] = $attempt->student ? [
                'id' => (int) $attempt->student->id,
                'name' => $attempt->student->name,
                'email' => $attempt->student->email,
            ] : null;
            $baseAttempt['variant'] = $attempt->variant ? [
                'id' => (int) $attempt->variant->id,
                'hash' => (string) $attempt->variant->hash,
                'title' => $attempt->variant->title,
                'owner_teacher_id' => $attempt->variant->owner_teacher_id !== null ? (int) $attempt->variant->owner_teacher_id : null,
            ] : null;
        }

        $summary = [
            'tasks_total' => count($taskNumbers),
            'answered_count' => $answeredCount,
            'unanswered_count' => $unansweredCount,
            'total_active_ms' => $totalActiveMs,
            'away_ms_total' => $awayMsTotal,
            'duration_ms' => $durationMs,
        ];

        if ($includeScoring) {
            $summary['correct_count'] = $correctCount;
            $summary['incorrect_count'] = $incorrectCount;
            $summary['unchecked_count'] = $uncheckedCount;
        }

        return [
            'attempt' => $baseAttempt,
            'summary' => $summary,
            'tasks' => $tasks,
        ];
    }

    /**
     * @return array<int>
     */
    private function resolveAttemptTaskNumbers(OgeAttempt $attempt): array
    {
        $attempt->loadMissing(['variant', 'answers', 'taskTimings', 'scorings']);

        $numbers = [];

        if ($attempt->variant?->isCustomRandom()) {
            $config = $attempt->variant->config_json ?? [];
            $fromConfigNumbers = $config['custom_task_numbers'] ?? [];
            if (is_array($fromConfigNumbers)) {
                foreach ($fromConfigNumbers as $number) {
                    $n = (int) $number;
                    if ($n >= 1 && $n <= 255) {
                        $numbers[] = $n;
                    }
                }
            }

            $customTasks = $config['custom_tasks'] ?? [];
            if (is_array($customTasks)) {
                foreach ($customTasks as $index => $taskData) {
                    if (!is_array($taskData)) {
                        continue;
                    }

                    $n = (int) ($taskData['attempt_task_number'] ?? $taskData['task_number'] ?? $taskData['test_number'] ?? ($index + 1));
                    if ($n >= 1 && $n <= 255) {
                        $numbers[] = $n;
                    }
                }
            }
        } else {
            $numbers = range(6, 19);
        }

        foreach ([$attempt->answers, $attempt->taskTimings, $attempt->scorings] as $collection) {
            foreach ($collection as $row) {
                $n = (int) ($row->task_number ?? 0);
                if ($n >= 1 && $n <= 255) {
                    $numbers[] = $n;
                }
            }
        }

        $numbers = array_values(array_unique($numbers));
        sort($numbers);

        if (!empty($numbers)) {
            return $numbers;
        }

        return $attempt->variant?->isCustomRandom() ? range(1, 19) : range(6, 19);
    }
}
