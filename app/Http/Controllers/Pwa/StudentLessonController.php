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
     * GET /lessons/{id} — страница урока (рендерится в student PWA).
     * Возвращает HTML с задачами и формами ввода ответа.
     */
    public function show(Request $request, int $id)
    {
        $session = $this->loadSessionForStudent($request, $id);
        $session->load('tasks');

        return view('pwa.student.lesson', [
            'session' => $session,
        ]);
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
     * POST /lessons/join   body: { code } — вход по 4-значному коду урока.
     */
    public function join(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'required|digits:4']);

        try {
            $session = $this->sessions->joinByCode($data['code'], $request->user());
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['lesson_id' => $session->id]);
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

        // Свой participant — для таймера лока на странице урока.
        $me = $session->participants()->where('student_id', $student->id)->first();

        return response()->json([
            'session' => [
                'id'        => $session->id,
                'status'    => $session->status,
                'starts_at' => $session->starts_at?->toIso8601String(),
            ],
            'lock' => [
                'locked_until' => $me?->locked_until?->toIso8601String(),
                'released_at'  => $me?->released_at?->toIso8601String(),
                'active'       => (bool) $me?->hasActiveLock(),
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

    /**
     * POST /lessons/{id}/activity   body: { visible: bool }
     * Ученик сообщает, на странице ли он (present) или свернул (away).
     * Также принимается через navigator.sendBeacon при закрытии вкладки.
     */
    public function activity(Request $request, int $id): JsonResponse
    {
        $session = $this->loadSessionForStudent($request, $id);
        $data = $request->validate(['visible' => 'required|boolean']);

        $this->sessions->recordActivity($session, $request->user(), (bool) $data['visible']);

        return response()->json(['ok' => true]);
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
