<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\LessonSchedule;
use App\Models\LessonSession;
use App\Models\LessonSessionTask;
use App\Services\LessonSessionService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints учителя для управления lesson_session.
 * Все роуты в routes/pwa.php под middleware ['auth','role:teacher,admin']
 * на домене teacher.palomatika.ru.
 *
 * Авторизация дополнительная: учитель видит/меняет только свои сессии
 * (teacher_id === auth()->id() — для admin делаем то же, чтобы случайно
 * не дотянуться до чужого слота).
 */
class TeacherLessonController extends Controller
{
    public function __construct(private readonly LessonSessionService $sessions)
    {
    }

    /**
     * GET /lessons/{id} — экран prep/live для одной сессии.
     * При status=draft показывает picker + список задач + кнопку «Запустить».
     * При status=live добавляет блок инвайт-ссылки и live-грид (Task 7).
     */
    public function show(Request $request, int $id)
    {
        $session = $this->loadOwnSession($request, $id);
        $session->load(['tasks', 'participants.student']);

        // Preload alg-skill 7 bundle для picker'а (v1 — только grade 7)
        $algSkillsBundle = (new \App\Services\AlgTaskDataService(7))->getSkillsBundle();

        return view('pwa.teacher.lesson-prep', [
            'session'           => $session,
            'algSkillsBundle7'  => $algSkillsBundle,
        ]);
    }

    /**
     * POST /lessons   body: { schedule_id?: int }
     */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'schedule_id' => 'nullable|integer|exists:lesson_schedule,id',
        ]);

        try {
            if (!empty($data['schedule_id'])) {
                $slot = LessonSchedule::findOrFail($data['schedule_id']);
                if ($slot->teacher_id !== $request->user()->id) {
                    abort(403, 'Не ваш слот');
                }
                $session = $this->sessions->createFromSchedule($slot);
            } else {
                $session = $this->sessions->createAdhoc($request->user());
            }
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['session' => $this->serializeSession($session)], 201);
    }

    /**
     * POST /lessons/{id}/tasks   body: { bank: string, refs: object }
     */
    public function addTask(Request $request, int $id): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);

        $data = $request->validate([
            'bank' => 'required|string|in:oge,ege,vpr,alg-topic,alg-skill',
            'refs' => 'required|array',
        ]);

        try {
            $task = $this->sessions->addTask($session, $data['bank'], $data['refs']);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['task' => $this->serializeTask($task)], 201);
    }

    /**
     * DELETE /lessons/{id}/tasks/{taskId}
     */
    public function removeTask(Request $request, int $id, int $taskId): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);
        $task = LessonSessionTask::where('lesson_session_id', $session->id)->findOrFail($taskId);

        try {
            $this->sessions->removeTask($task);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * POST /lessons/{id}/start
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);

        try {
            $session = $this->sessions->start($session);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['session' => $this->serializeSession($session)]);
    }

    /**
     * POST /lessons/{id}/end
     */
    public function end(Request $request, int $id): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);

        try {
            $session = $this->sessions->end($session);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['session' => $this->serializeSession($session)]);
    }

    /**
     * GET /lessons/{id}/state — полный снапшот для polling.
     * Возвращает session + tasks (с correct_answer) + participants + grid ответов.
     */
    public function state(Request $request, int $id): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);
        $session->load(['tasks', 'participants.student', 'attempts']);

        $grid = [];
        foreach ($session->attempts as $a) {
            $grid[$a->student_id][$a->lesson_session_task_id] = [
                'answer'      => $a->answer_raw,
                'is_correct'  => $a->is_correct,
                'answered_at' => $a->answered_at?->toIso8601String(),
            ];
        }

        return response()->json([
            'session'      => $this->serializeSession($session),
            'tasks'        => $session->tasks->map(fn ($t) => $this->serializeTask($t))->all(),
            'participants' => $session->participants->map(fn ($p) => [
                'id'     => $p->student_id,
                'name'   => $p->student?->name,
                'source' => $p->source,
            ])->all(),
            'grid'         => $grid,
        ]);
    }

    private function loadOwnSession(Request $request, int $id): LessonSession
    {
        $session = LessonSession::findOrFail($id);
        if ($session->teacher_id !== $request->user()->id) {
            abort(403, 'Не ваш урок');
        }
        return $session;
    }

    private function serializeSession(LessonSession $s): array
    {
        return [
            'id'           => $s->id,
            'status'       => $s->status,
            'schedule_id'  => $s->schedule_id,
            'starts_at'    => $s->starts_at?->toIso8601String(),
            'ends_at'      => $s->ends_at?->toIso8601String(),
            'invite_token' => $s->invite_token,
        ];
    }

    private function serializeTask(LessonSessionTask $t): array
    {
        return [
            'id'             => $t->id,
            'position'       => $t->position,
            'bank'           => $t->bank,
            'grade'          => $t->grade,
            'topic_id'       => $t->topic_id,
            'skill_slug'     => $t->skill_slug,
            'task_payload'   => $t->task_payload,
            'correct_answer' => $t->correct_answer,
        ];
    }
}
