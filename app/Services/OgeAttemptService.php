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
        return DB::transaction(function () use ($attempt, $clientTs) {
            $this->appendEvent($attempt, 'attempt_submitted', null, [], $clientTs);

            $this->finalizeOpenTimings($attempt);

            $attempt->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'last_seen_at' => now(),
            ]);

            OgeAttemptAnswer::where('attempt_id', $attempt->id)->update(['is_final' => true]);
            $this->scoreAttempt($attempt->fresh(['variant', 'answers']));

            return $attempt->fresh();
        });
    }

    public function rebuildScoring(OgeAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {
            $this->scoreAttempt($attempt->fresh(['variant', 'answers']));
        });
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

        $attemptId = $attempt->id;
        if (isset($cache[$attemptId])) {
            return $cache[$attemptId];
        }

        $hash = $attempt->variant?->hash;
        if (!$hash) {
            return $cache[$attemptId] = [];
        }

        $selected = $attempt->variant?->config_json['zadaniya'] ?? null;
        $variantPayload = $this->variantBuilder->build($hash, is_array($selected) ? $selected : null);

        $map = [];
        foreach ($variantPayload['tasks'] ?? [] as $index => $taskData) {
            $tn = (int) ($taskData['task_number'] ?? (6 + $index));
            $map[$tn] = $this->answerResolver->resolveFromVariantTask($taskData);
        }

        return $cache[$attemptId] = $map;
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
}
