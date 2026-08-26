<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\LessonSchedule;
use App\Models\LessonSession;
use App\Models\LessonSessionTask;
use App\Models\StudentNote;
use App\Models\Homework;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\AssistantService;
use App\Services\HomeworkReviewService;
use App\Services\LessonHomeworkSuggestionService;
use App\Services\LessonSessionService;
use App\Services\LessonTaskPickerService;
use App\Services\TaskBankResolver;
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
        $session->load(['tasks.assignedStudent', 'participants.student']);

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
        if (!in_array($bank, TaskBankResolver::BANKS, true)) {
            return response()->json(['error' => 'Unknown bank'], 422);
        }

        // Разделы есть у ОГЭ (части и «новые») и у ЕГЭ (части экзамена);
        // проверять только по ОГЭ нельзя — у банков они разные.
        $section = $request->query('section') ?: null;
        $knownSections = $bank === 'ege'
            ? LessonTaskPickerService::EGE_SECTIONS
            : LessonTaskPickerService::OGE_SECTIONS;
        if ($section !== null && !array_key_exists($section, $knownSections)) {
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
     * POST /lessons   body: { schedule_id?: int, starts_at?: 'Y-m-d H:i' }
     * starts_at — планируемые день и время урока (для списка уроков).
     */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'schedule_id' => 'nullable|integer|exists:lesson_schedule,id',
            'starts_at'   => 'nullable|date',
        ]);

        try {
            if (!empty($data['schedule_id'])) {
                $slot = LessonSchedule::findOrFail($data['schedule_id']);
                if ($slot->teacher_id !== $request->user()->id) {
                    abort(403, 'Не ваш слот');
                }
                $session = $this->sessions->createFromSchedule($slot);
            } else {
                $startsAt = !empty($data['starts_at'])
                    ? \Illuminate\Support\Carbon::parse($data['starts_at'])->setSeconds(0)
                    : null;
                $session = $this->sessions->createAdhoc($request->user(), $startsAt);
            }
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['session' => $this->serializeSession($session)], 201);
    }

    /**
     * POST /lessons/{id}/next — «следующий урок»: черновик на то же время
     * через неделю (идемпотентно). Заметку/задания учитель добавляет в нём сам.
     */
    public function next(Request $request, int $id): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);
        $next = $this->sessions->createFollowUp($session);

        return response()->json(['session' => $this->serializeSession($next)], 201);
    }

    /**
     * POST /lessons/{id}/note   body: { note?: string }
     */
    public function note(Request $request, int $id): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);
        $data = $request->validate(['note' => 'nullable|string|max:2000']);
        $this->sessions->updateNote($session, $data['note'] ?? null);

        return response()->json(['ok' => true]);
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
     * POST /lessons/{id}/tasks   body: { bank, refs, assigned_student_id? }
     * assigned_student_id — персональная задача конкретному участнику (null = всем).
     */
    public function addTask(Request $request, int $id): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);

        $data = $request->validate([
            'bank'                => 'required|string|in:oge,ege,vpr,alg-topic,alg-skill',
            'refs'                => 'required|array',
            'assigned_student_id' => 'nullable|integer',
        ]);

        $assigned = $data['assigned_student_id'] ?? null;
        if ($assigned !== null && !$this->sessions->isParticipantId($session, (int) $assigned)) {
            return response()->json(['error' => 'Ученик не участник урока'], 422);
        }

        try {
            $task = $this->sessions->addTask($session, $data['bank'], $data['refs'], $assigned ? (int) $assigned : null);
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
        $session->load(['tasks.assignedStudent', 'participants.student', 'attempts']);

        $activity = $this->sessions->activitySummary($session);
        $behavior = $this->sessions->behaviorSummary($session);
        $noteCounts = $this->noteCounts($request->user()->id, $session->participants->pluck('student_id')->all());

        $grid = [];
        foreach ($session->attempts as $a) {
            $b = $behavior[$a->student_id] ?? null;
            $grid[$a->student_id][$a->lesson_session_task_id] = [
                'answer'      => $a->answer_raw,
                'is_correct'  => $a->is_correct,
                'answered_at' => $a->answered_at?->toIso8601String(),
                // Сигналы списывания: ответ вставлен из буфера / дан сразу после отлучки
                'pasted'      => $b && in_array($a->lesson_session_task_id, $b['pasted_tasks'], true),
                'quick_after_away' => $b && in_array($a->lesson_session_task_id, $b['quick_after_away_tasks'], true),
            ];
        }

        return response()->json([
            'session'      => $this->serializeSession($session),
            'tasks'        => $session->tasks->map(fn ($t) => $this->serializeTask($t))->all(),
            'participants' => $session->participants->map(fn ($p) => [
                'id'       => $p->student_id,
                'name'     => $p->student?->name,
                'source'   => $p->source,
                'locked'   => $p->hasActiveLock(),
                'activity' => $activity[$p->student_id] ?? [
                    'state' => 'gone', 'away_count' => 0, 'away_seconds' => 0, 'present_seconds' => 0,
                ],
                'behavior' => [
                    'copy_count'  => $behavior[$p->student_id]['copy_count'] ?? 0,
                    'paste_count' => $behavior[$p->student_id]['paste_count'] ?? 0,
                ],
                // Счётчик для чипа: сколько моих заметок об этом ученике всего.
                'notes_count' => $noteCounts[$p->student_id] ?? 0,
            ])->all(),
            'grid'         => $grid,
        ]);
    }

    /**
     * GET /lessons/{id}/homework-suggestions
     * Аналоги разобранных на уроке задач для «домашки по уроку» + участники + ранее
     * отправленные по этому уроку ДЗ.
     */
    public function homeworkSuggestions(Request $request, int $id): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);
        $session->load('participants.student');

        $groups = app(LessonHomeworkSuggestionService::class)->suggestionsFor($session);

        $participantIds = $session->participants->pluck('student_id')->all();
        $participants = $session->participants->map(fn ($p) => [
            'id'   => $p->student_id,
            'name' => $p->student?->name,
        ])->values()->all();

        // Остальные привязанные ученики учителя (можно доотметить в модале).
        $others = TeacherStudent::where('teacher_id', $session->teacher_id)
            ->whereNotIn('student_id', $participantIds)
            ->with('student:id,name')
            ->get()
            ->map(fn ($r) => [
                'id'   => (int) $r->student_id,
                'name' => $r->student_alias ?: $r->student?->name,
            ])
            ->filter(fn ($s) => $s['name'] !== null)
            ->values()->all();

        $prior = Homework::where('lesson_session_id', $session->id)
            ->orderByDesc('assigned_at')
            ->get(['id', 'title', 'assigned_at'])
            ->map(fn ($h) => [
                'id'    => $h->id,
                'title' => $h->title,
                'date'  => $h->assigned_at?->format('d.m.Y H:i'),
            ])->all();

        return response()->json([
            'groups'          => $groups,
            'participants'    => $participants,
            'other_students'  => $others,
            'prior_homeworks' => $prior,
        ]);
    }

    /**
     * GET /lessons/{id}/review-items
     *
     * Вторая стадия домашки на экране урока: что учитель отметил «разобрать»
     * у участников (pending) и что уже поставил в повестку (planned).
     */
    public function reviewItems(Request $request, int $id): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);
        $studentIds = $session->participants()->pluck('student_id')->all();

        $service = app(HomeworkReviewService::class);

        return response()->json([
            'pending' => $service->pendingFor($studentIds, (int) $session->teacher_id),
            'planned' => $service->plannedFor($session),
        ]);
    }

    /**
     * POST /lessons/{id}/review-items   body: { item_ids: [] }
     */
    public function planReviewItems(Request $request, int $id): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);

        $data = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer',
        ]);

        $service = app(HomeworkReviewService::class);
        $planned = $service->planInto($session, $data['item_ids']);

        return response()->json([
            'planned_count' => $planned,
            'planned' => $service->plannedFor($session),
        ]);
    }

    /**
     * DELETE /lessons/{id}/review-items/{itemId} — учитель передумал.
     */
    public function unplanReviewItem(Request $request, int $id, int $itemId): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);
        app(HomeworkReviewService::class)->unplan($session, $itemId);

        return response()->json(['ok' => true]);
    }

    /**
     * POST /lessons/{id}/dont-understand   body: { student_id, task_id }
     * Кнопка «не понимает» — фиксирует слабое место ученика по задаче (без LLM).
     */
    public function dontUnderstand(Request $request, int $id): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);

        $data = $request->validate([
            'student_id' => 'required|integer',
            'task_id'    => 'required|integer',
        ]);

        if (!$this->sessions->isParticipantId($session, (int) $data['student_id'])) {
            return response()->json(['error' => 'Ученик не участник урока'], 422);
        }

        $task = LessonSessionTask::where('lesson_session_id', $session->id)
            ->findOrFail($data['task_id']);
        $student = User::findOrFail($data['student_id']);

        $note = $this->sessions->recordDontUnderstand($session, $request->user(), $student, $task);

        return response()->json([
            'note' => [
                'id'        => $note->id,
                'kind'      => $note->kind,
                'source'    => $note->source,
                'topic_tag' => $note->topic_tag,
                'task_ref'  => $note->task_ref,
                'body'      => $note->body,
            ],
        ], 201);
    }

    /**
     * POST /lessons/{id}/notes   body: { student_ids: int[], text: string }
     * Форма быстрой записи: учитель ЯВНО выбрал учеников и написал текст,
     * DeepSeek только вытаскивает теги {kind, topic_tag}. Одна запись на ученика.
     */
    public function notes(Request $request, int $id): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);

        $data = $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'integer',
            'text'          => 'required|string|max:2000',
        ]);

        foreach ($data['student_ids'] as $sid) {
            if (!$this->sessions->isParticipantId($session, (int) $sid)) {
                return response()->json(['error' => 'Ученик не участник урока'], 422);
            }
        }

        $result = app(AssistantService::class)
            ->recordNote($session, $request->user(), $data['student_ids'], $data['text']);

        return response()->json([
            'kind'      => $result['kind'],
            'topic_tag' => $result['topic_tag'],
            'notes'     => $result['notes']->map(fn ($n) => [
                'id'         => $n->id,
                'body'       => $n->body,
                'kind'       => $n->kind,
                'topic_tag'  => $n->topic_tag,
                'student_id' => $n->student_id,
            ])->all(),
        ]);
    }

    /**
     * GET /lessons/{id}/students/{studentId}/notes
     * Вся история моих заметок об ученике — чтобы смотреть их прямо на уроке,
     * не уходя в карточку ученика. Чужие заметки об этом же ученике не отдаём.
     */
    public function studentNotes(Request $request, int $id, int $studentId): JsonResponse
    {
        $session = $this->loadOwnSession($request, $id);

        if (!$this->sessions->isParticipantId($session, $studentId)) {
            return response()->json(['error' => 'Ученик не участник урока'], 422);
        }

        $student = User::findOrFail($studentId);

        $notes = StudentNote::where('student_id', $studentId)
            ->where('teacher_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return response()->json([
            'student' => ['id' => $student->id, 'name' => $student->name],
            'notes'   => $notes->map(fn (StudentNote $n) => [
                'id'                => $n->id,
                'body'              => $n->body,
                'kind'              => $n->kind,
                'topic_tag'         => $n->topic_tag,
                'task_ref'          => $n->task_ref,
                'source'            => $n->source,
                'created_at'        => $n->created_at?->format('d.m.Y'),
                'is_current_lesson' => (int) $n->lesson_session_id === $session->id,
            ])->all(),
        ]);
    }

    /**
     * Сколько моих заметок по каждому из учеников: одним запросом на весь урок.
     *
     * @param  array<int,int>  $studentIds
     * @return array<int,int>  student_id => count
     */
    private function noteCounts(int $teacherId, array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        return StudentNote::where('teacher_id', $teacherId)
            ->whereIn('student_id', $studentIds)
            ->selectRaw('student_id, COUNT(*) as c')
            ->groupBy('student_id')
            ->pluck('c', 'student_id')
            ->map(fn ($c) => (int) $c)
            ->all();
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
            'note'         => $s->note,
        ];
    }

    private function serializeTask(LessonSessionTask $t): array
    {
        return [
            'id'                  => $t->id,
            'position'            => $t->position,
            'bank'                => $t->bank,
            'grade'               => $t->grade,
            'topic_id'            => $t->topic_id,
            'skill_slug'          => $t->skill_slug,
            'task_payload'        => $t->task_payload,
            'correct_answer'      => $t->correct_answer,
            'assigned_student_id' => $t->assigned_student_id,
            'assigned_name'       => $t->relationLoaded('assignedStudent') ? $t->assignedStudent?->name : null,
        ];
    }
}
