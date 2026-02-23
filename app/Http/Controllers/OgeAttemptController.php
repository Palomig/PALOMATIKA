<?php

namespace App\Http\Controllers;

use App\Models\OgeAttempt;
use App\Services\OgeAttemptService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OgeAttemptController extends Controller
{
    public function __construct(
        private readonly OgeAttemptService $attemptService,
        private readonly AuditLogger $auditLogger,
    )
    {
    }

    public function start(Request $request, string $hash): JsonResponse
    {
        $student = $request->user();

        [$variant, $attempt] = $this->attemptService->startAttempt($student, $hash, [
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ]);

        $this->logAudit($request, 'attempt_started', $student->id, $student->role, 'oge_attempt', $attempt->id, [
            'variant_id' => $variant->id,
            'variant_hash' => $variant->hash,
        ]);

        return response()->json([
            'success' => true,
            'variant_id' => $variant->id,
            'attempt_id' => $attempt->id,
            'status' => $attempt->status,
            'locked' => $attempt->status !== 'active',
        ]);
    }

    public function focus(Request $request, OgeAttempt $attempt, int $taskNumber): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->guardActiveAttempt($attempt);
        $this->validateTaskNumber($taskNumber);

        $timing = $this->attemptService->touchTiming($attempt, $taskNumber, 'task_focused', $request->input('client_ts'));

        $this->logAudit($request, 'task_focused', $request->user()->id, $request->user()->role, 'oge_attempt', $attempt->id, [
            'task_number' => $taskNumber,
            'focus_count' => $timing->focus_count,
        ]);

        return response()->json(['success' => true, 'focus_count' => $timing->focus_count]);
    }

    public function blur(Request $request, OgeAttempt $attempt, int $taskNumber): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->guardActiveAttempt($attempt);
        $this->validateTaskNumber($taskNumber);

        $timing = $this->attemptService->touchTiming($attempt, $taskNumber, 'task_blurred', $request->input('client_ts'));

        $this->logAudit($request, 'task_blurred', $request->user()->id, $request->user()->role, 'oge_attempt', $attempt->id, [
            'task_number' => $taskNumber,
            'active_ms' => $timing->active_ms,
        ]);

        return response()->json(['success' => true, 'task_number' => $timing->task_number]);
    }

    public function heartbeat(Request $request, OgeAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->guardActiveAttempt($attempt);

        $taskNumber = (int) $request->input('active_task', 0);
        $visible = (bool) $request->boolean('visible', true);
        if ($visible && $this->isValidTaskNumber($taskNumber)) {
            $this->attemptService->touchTiming($attempt, $taskNumber, 'heartbeat', $request->input('client_ts'));
        }

        $awayMs = (int) $request->input('away_ms', 0);
        if ($awayMs > 0) {
            $meta = $attempt->device_meta ?? [];
            $meta['away_ms_total'] = (int) ($meta['away_ms_total'] ?? 0) + $awayMs;
            $attempt->device_meta = $meta;

            $this->attemptService->appendEvent($attempt, 'tab_away', null, [
                'away_ms' => $awayMs,
            ], $request->input('client_ts'));
        }

        $this->logAudit($request, 'heartbeat', $request->user()->id, $request->user()->role, 'oge_attempt', $attempt->id, [
            'active_task' => $taskNumber,
            'visible' => $visible,
            'away_ms' => $awayMs,
        ]);

        $attempt->update(['last_seen_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function commit(Request $request, OgeAttempt $attempt, int $taskNumber): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->guardActiveAttempt($attempt);
        $this->validateTaskNumber($taskNumber);

        $validated = $request->validate([
            'answer' => 'required|string|max:255',
            'client_ts' => 'nullable|date',
        ]);

        $answer = $this->attemptService->commitAnswer(
            $attempt,
            $taskNumber,
            trim($validated['answer']),
            $validated['client_ts'] ?? null
        );

        $this->logAudit($request, 'answer_committed', $request->user()->id, $request->user()->role, 'oge_attempt', $attempt->id, [
            'task_number' => $taskNumber,
            'commits_count' => $answer->commits_count,
        ]);

        return response()->json([
            'success' => true,
            'task_number' => $taskNumber,
            'commits_count' => $answer->commits_count,
            'last_committed_at' => optional($answer->last_committed_at)->toIso8601String(),
        ]);
    }

    public function submit(Request $request, OgeAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->guardActiveAttempt($attempt);

        $attempt = $this->attemptService->submitAttempt($attempt, $request->input('client_ts'));

        $this->logAudit($request, 'attempt_submitted', $request->user()->id, $request->user()->role, 'oge_attempt', $attempt->id, [
            'status' => $attempt->status,
        ]);

        return response()->json([
            'success' => true,
            'status' => $attempt->status,
            'submitted_at' => optional($attempt->submitted_at)->toIso8601String(),
        ]);
    }

    public function status(Request $request, OgeAttempt $attempt): JsonResponse
    {
        $this->authorizeAttemptRead($request, $attempt);

        return response()->json([
            'success' => true,
            ...$this->attemptService->buildAttemptStatusPayload($attempt),
        ]);
    }

    public function result(Request $request, OgeAttempt $attempt): JsonResponse
    {
        $this->authorizeAttemptResult($request, $attempt);

        return response()->json([
            'success' => true,
            ...$this->attemptService->buildAttemptResultPayload($attempt),
        ]);
    }

    private function authorizeAttempt(Request $request, OgeAttempt $attempt): void
    {
        if ((int) $attempt->student_id !== (int) $request->user()->id) {
            abort(403);
        }
    }

    private function authorizeAttemptRead(Request $request, OgeAttempt $attempt): void
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            return;
        }

        if ((int) $attempt->student_id !== (int) $user->id) {
            abort(403);
        }
    }

    private function authorizeAttemptResult(Request $request, OgeAttempt $attempt): void
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            return;
        }

        $attempt->loadMissing('variant');
        if ((int) ($attempt->variant?->owner_teacher_id ?? 0) !== (int) $user->id) {
            abort(403);
        }
    }

    private function guardActiveAttempt(OgeAttempt $attempt): void
    {
        if ($attempt->status !== 'active') {
            abort(409, 'Attempt already submitted');
        }
    }

    private function validateTaskNumber(int $taskNumber): void
    {
        if (!$this->isValidTaskNumber($taskNumber)) {
            abort(422, 'Invalid task number');
        }
    }

    private function isValidTaskNumber(int $taskNumber): bool
    {
        return $taskNumber >= 1 && $taskNumber <= 255;
    }

    private function logAudit(
        Request $request,
        string $eventType,
        ?int $actorUserId,
        ?string $actorRole,
        ?string $subjectType,
        $subjectId,
        array $payload = []
    ): void {
        $this->auditLogger->log([
            'event_type' => $eventType,
            'category' => 'oge',
            'severity' => 'info',
            'actor_user_id' => $actorUserId,
            'actor_role' => $actorRole,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_json' => $payload,
        ]);
    }
}
