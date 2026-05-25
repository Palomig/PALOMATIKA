<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\LessonSession;
use App\Models\LessonSessionTask;
use App\Services\LessonSessionService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints ученика для урока. Все роуты в routes/pwa.php
 * под middleware ['auth', 'pwa.onboarding'] на student.palomatika.ru.
 *
 * Важная инварианата: ученик НЕ должен видеть correct_answer ни
 * своей задачи, ни чужих ответов. Сериализация фильтрует payload.
 */
class StudentLessonController extends Controller
{
    public function __construct(private readonly LessonSessionService $sessions)
    {
    }

    /**
     * GET /lessons/active — есть ли сейчас live-сессия, где этот student участник.
     * Возвращает первую активную или null.
     */
    public function active(Request $request): JsonResponse
    {
        $session = LessonSession::query()
            ->where('status', LessonSession::STATUS_LIVE)
            ->whereHas('participants', fn ($q) => $q->where('student_id', $request->user()->id))
            ->orderByDesc('starts_at')
            ->first();

        if (!$session) {
            return response()->json(['session' => null]);
        }

        return response()->json([
            'session' => [
                'id'         => $session->id,
                'starts_at'  => $session->starts_at?->toIso8601String(),
                'teacher_id' => $session->teacher_id,
            ],
        ]);
    }

    /**
     * GET /lessons/{id}/state — задачи + ТОЛЬКО свои ответы (без correct_answer).
     */
    public function state(Request $request, int $id): JsonResponse
    {
        $session = $this->loadSessionForStudent($request, $id);
        $session->load('tasks');
        $student = $request->user();

        $myAttempts = $session->attempts()
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('lesson_session_task_id');

        return response()->json([
            'session' => [
                'id'        => $session->id,
                'status'    => $session->status,
                'starts_at' => $session->starts_at?->toIso8601String(),
            ],
            'tasks' => $session->tasks->map(fn ($t) => $this->serializeTaskForStudent($t, $myAttempts->get($t->id)))->all(),
        ]);
    }

    /**
     * POST /lessons/{id}/answer   body: { task_id, answer }
     */
    public function answer(Request $request, int $id): JsonResponse
    {
        $session = $this->loadSessionForStudent($request, $id);

        $data = $request->validate([
            'task_id' => 'required|integer',
            'answer'  => 'required|string|max:500',
        ]);

        $task = LessonSessionTask::where('lesson_session_id', $session->id)
            ->findOrFail($data['task_id']);

        try {
            $attempt = $this->sessions->submitAnswer($session, $request->user(), $task, $data['answer']);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // НЕ возвращаем is_correct — ученик не должен знать
        return response()->json([
            'ok'          => true,
            'answered_at' => $attempt->answered_at?->toIso8601String(),
        ]);
    }

    private function loadSessionForStudent(Request $request, int $id): LessonSession
    {
        $session = LessonSession::findOrFail($id);
        if (!$this->sessions->isParticipant($session, $request->user())) {
            abort(403, 'Вы не участник этого урока');
        }
        return $session;
    }

    private function serializeTaskForStudent(LessonSessionTask $t, $myAttempt): array
    {
        $payload = $t->task_payload;
        // Скрываем answer из payload (он же в correct_answer, но в payload его тоже сохранили)
        unset($payload['answer'], $payload['raw']);

        return [
            'id'         => $t->id,
            'position'   => $t->position,
            'payload'    => $payload,
            'my_answer'  => $myAttempt?->answer_raw,
            'answered_at'=> $myAttempt?->answered_at?->toIso8601String(),
        ];
    }
}
