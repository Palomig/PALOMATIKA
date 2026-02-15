<?php

namespace App\Http\Controllers;

use App\Models\OgeAttempt;
use App\Services\OgeAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OgeAttemptController extends Controller
{
    public function __construct(private readonly OgeAttemptService $attemptService)
    {
    }

    public function start(Request $request, string $hash): JsonResponse
    {
        $student = $request->user();

        [$variant, $attempt] = $this->attemptService->startAttempt($student, $hash, [
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'variant_id' => $variant->id,
            'attempt_id' => $attempt->id,
            'status' => $attempt->status,
            'locked' => $attempt->status === 'submitted',
        ]);
    }

    public function focus(Request $request, OgeAttempt $attempt, int $taskNumber): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->guardActiveAttempt($attempt);
        $this->validateTaskNumber($taskNumber);

        $timing = $this->attemptService->touchTiming($attempt, $taskNumber, 'task_focused', $request->input('client_ts'));

        return response()->json(['success' => true, 'focus_count' => $timing->focus_count]);
    }

    public function blur(Request $request, OgeAttempt $attempt, int $taskNumber): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->guardActiveAttempt($attempt);
        $this->validateTaskNumber($taskNumber);

        $timing = $this->attemptService->touchTiming($attempt, $taskNumber, 'task_blurred', $request->input('client_ts'));

        return response()->json(['success' => true, 'task_number' => $timing->task_number]);
    }

    public function heartbeat(Request $request, OgeAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->guardActiveAttempt($attempt);

        $taskNumber = (int) $request->input('active_task', 0);
        if ($taskNumber >= 6 && $taskNumber <= 19) {
            $this->attemptService->touchTiming($attempt, $taskNumber, 'heartbeat', $request->input('client_ts'));
        }

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

        return response()->json([
            'success' => true,
            'status' => $attempt->status,
            'submitted_at' => optional($attempt->submitted_at)->toIso8601String(),
        ]);
    }

    private function authorizeAttempt(Request $request, OgeAttempt $attempt): void
    {
        if ((int) $attempt->student_id !== (int) $request->user()->id) {
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
        if ($taskNumber < 6 || $taskNumber > 19) {
            abort(422, 'Invalid task number');
        }
    }
}

