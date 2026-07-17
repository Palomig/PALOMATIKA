<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\LessonSchedule;
use App\Models\LessonSession;
use App\Models\LessonSessionTask;
use App\Services\LessonSessionService;
use App\Services\LessonTaskPickerService;
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

        return view('pwa.teacher.lesson-prep', [
            'session' => $session,
        ]);
    }

    /**
     * GET /lessons/picker-options?bank=oge&grade=7&topic_id=06&zadanie_number=1&skill_slug=...&level_id=...
     * Возвращает каскадные опции для выбранных refs.
     */
    public function pickerOptions(Request $request, LessonTaskPickerService $picker): JsonResponse
    {
        $bank = (string) $request->query('bank', '');
        if (!in_array($bank, ['oge', 'ege', 'vpr', 'alg-topic', 'alg-skill'], true)) {
            return response()->json(['error' => 'Unknown bank'], 422);
        }

        $section = $request->query('section') ?: null;
        if ($section !== null && !array_key_exists($section, LessonTaskPickerService::OGE_SECTIONS)) {
            return response()->json(['error' => 'Unknown section'], 422);
        }

        $refs = [
            'grade'           => $request->query('grade')           !== null ? (int) $request->query('grade') : null,
            'topic_id'        => $request->query('topic_id')        ?: null,
            'skill_slug'      => $request->query('skill_slug')      ?: null,
            'level_id'        => $request->query('level_id')        ?: null,
            'zadanie_number'  => $request->query('zadanie_number')  !== null ? (int) $request->query('zadanie_number') : null,
        ];
        $refs = array_filter($refs, fn ($v) => $v !== null && $v !== '');

        $response = [
            'grades'   => $picker->grades($bank),
            'sections' => $picker->sections($bank),
        ];

        if ($bank === 'alg-skill') {
            if (!empty($refs['grade'])) {
                $response['skills'] = $picker->skills((int) $refs['grade']);
            }
            if (!empty($refs['grade']) && !empty($refs['skill_slug'])) {
                $response['tasks'] = $picker->tasks($bank, $refs);
            }
        } else {
            $needsGrade = in_array($bank, ['vpr', 'alg-topic'], true);
            $gradeReady = !$needsGrade || !empty($refs['grade']);
            if ($gradeReady) {
                $response['topics'] = $picker->topics($bank, $refs['grade'] ?? null, $section);
            }
            if ($gradeReady && !empty($refs['topic_id'])) {
                $response['tasks'] = $picker->tasks($bank, $refs, $section);
            }
        }

        return response()->json($response);
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
     * POST /lessons/from-slot   body: { starts_at, ends_at?, student_ids?[] }
     *
     * Создаёт/открывает draft-сессию для слота расписания Evrium (по времени).
     * Используется кликом по карточке урока на экране /lessons, чтобы заранее
     * подготовить задания. Идемпотентно — повторный клик вернёт тот же черновик.
     */
    public function fromSlot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'starts_at'     => 'required|date',
            'ends_at'       => 'nullable|date',
            'student_ids'   => 'nullable|array',
            'student_ids.*' => 'integer',
        ]);

        try {
            $session = $this->sessions->createFromEvriumSlot(
                $request->user(),
                $data['starts_at'],
                $data['ends_at'] ?? null,
                $data['student_ids'] ?? [],
            );
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
     * POST /lessons/{id}/participants/{studentId}/release — вручную отпустить
     * ученика с урока (снять лок навигации).
     */
    public function release(Request $request, int $id, int $studentId): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);
        $this->sessions->release($session, $studentId, $request->user());

        return response()->json(['ok' => true]);
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
                'locked' => $p->hasActiveLock(),
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
            'join_code'    => $s->join_code,
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
