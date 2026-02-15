<?php

namespace App\Services;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptAnswer;
use App\Models\OgeAttemptEvent;
use App\Models\OgeAttemptTaskTiming;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OgeAttemptService
{
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

            if ($eventType === 'task_focused') {
                $timing->focus_count += 1;
                $timing->last_focus_at = now();
            }

            if ($eventType === 'heartbeat') {
                $timing->last_heartbeat_at = now();
            }

            $timing->save();

            return $timing;
        });
    }

    public function submitAttempt(OgeAttempt $attempt, $clientTs = null): OgeAttempt
    {
        return DB::transaction(function () use ($attempt, $clientTs) {
            $this->appendEvent($attempt, 'attempt_submitted', null, [], $clientTs);

            $attempt->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'last_seen_at' => now(),
            ]);

            OgeAttemptAnswer::where('attempt_id', $attempt->id)->update(['is_final' => true]);

            return $attempt->fresh();
        });
    }
}

