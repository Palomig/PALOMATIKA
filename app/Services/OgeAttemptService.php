<?php

namespace App\Services;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptAnswer;
use App\Models\OgeAttemptEvent;
use App\Models\OgeAttemptScoring;
use App\Models\OgeAttemptTaskTiming;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OgeAttemptService
{
    public function __construct(
        private readonly OgeVariantBuilderService $variantBuilder,
        private readonly TaskAnswerResolver $answerResolver,
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

            $attempt = OgeAttempt::firstOrCreate(
                [
                    'variant_id' => $variant->id,
                    'student_id' => $student->id,
                ],
                [
                    'status' => 'active',
                    'device_meta' => $deviceMeta,
                    'started_at' => now(),
                    'last_seen_at' => now(),
                ]
            );

            if (!$attempt->wasRecentlyCreated) {
                $attempt->update(['last_seen_at' => now()]);
            } else {
                $this->appendEvent($attempt, 'attempt_started', null, [
                    'variant_hash' => $hash,
                ]);
            }

            return [$variant, $attempt];
        });
    }

    public function appendEvent(OgeAttempt $attempt, string $eventType, ?int $taskNumber = null, array $payload = [], $clientTs = null): OgeAttemptEvent
    {
        $nextSeq = (int) OgeAttemptEvent::where('attempt_id', $attempt->id)->max('seq') + 1;

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

    public function touchTiming(OgeAttempt $attempt, int $taskNumber, string $eventType, $clientTs = null): OgeAttemptTaskTiming
    {
        return DB::transaction(function () use ($attempt, $taskNumber, $eventType, $clientTs) {
            $this->appendEvent($attempt, $eventType, $taskNumber, [], $clientTs);

            $timing = OgeAttemptTaskTiming::firstOrCreate(
                ['attempt_id' => $attempt->id, 'task_number' => $taskNumber],
                [
                    'active_ms' => 0,
                    'focus_count' => 0,
                    'last_focus_at' => null,
                    'last_heartbeat_at' => null,
                ]
            );

            $now = now();

            if ($eventType === 'task_focused') {
                $timing->focus_count += 1;
                $timing->last_focus_at = $now;
                $timing->last_heartbeat_at = $now;
            }

            if ($eventType === 'heartbeat') {
                if ($timing->last_focus_at) {
                    $anchor = $timing->last_heartbeat_at ?? $timing->last_focus_at;
                    $delta = $anchor ? $anchor->diffInMilliseconds($now) : 0;
                    $timing->active_ms += min(max($delta, 0), 30000);
                }

                $timing->last_heartbeat_at = $now;
            }

            if ($eventType === 'task_blurred') {
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
            $this->scoreAttempt($attempt->fresh(['variant', 'answers']));
            $attempt->update(['status' => 'scored']);
            $this->appendEvent($attempt, 'attempt_scored');
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
            $this->scoreAttempt($attempt->fresh(['variant', 'answers']));
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
        $correctByTaskNumber = $this->getCorrectAnswerMap($attempt);
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

        $correctMap = $this->getCorrectAnswerMap($attempt);
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
        static $cache = [];

        $cacheKey = implode(':', [
            (string) $attempt->id,
            (string) ($attempt->variant_id ?? 0),
            (string) ($attempt->variant?->hash ?? ''),
        ]);

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $hash = $attempt->variant?->hash;
        if (!$hash) {
            return $cache[$cacheKey] = [];
        }

        if ($attempt->variant?->isCustomRandom()) {
            $customTasks = $attempt->variant?->config_json['custom_tasks'] ?? [];
            if ((!is_array($customTasks) || empty($customTasks)) && is_string($hash) && $hash !== '') {
                $customTasks = $this->loadCustomRandomTasksByHash($hash);
            }

            if (!is_array($customTasks) || empty($customTasks)) {
                return $cache[$cacheKey] = [];
            }

            $map = [];
            foreach ($customTasks as $index => $taskData) {
                if (!is_array($taskData)) {
                    continue;
                }

                $tn = (int) ($taskData['attempt_task_number'] ?? $taskData['task_number'] ?? $taskData['test_number'] ?? ($index + 1));
                if ($tn < 1 || $tn > 255) {
                    continue;
                }

                $map[$tn] = $this->answerResolver->resolveFromVariantTask($taskData);
            }

            return $cache[$cacheKey] = $map;
        }

        $selected = $attempt->variant?->config_json['zadaniya'] ?? null;
        $variantPayload = $this->variantBuilder->build($hash, is_array($selected) ? $selected : null);

        $map = [];
        foreach ($variantPayload['tasks'] ?? [] as $index => $taskData) {
            $tn = (int) ($taskData['task_number'] ?? (6 + $index));
            $map[$tn] = $this->answerResolver->resolveFromVariantTask($taskData);
        }

        return $cache[$cacheKey] = $map;
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
