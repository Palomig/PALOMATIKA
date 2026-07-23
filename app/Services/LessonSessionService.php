<?php

namespace App\Services;

use App\Models\LessonActivityInterval;
use App\Models\LessonBehaviorEvent;
use App\Models\LessonSchedule;
use App\Models\LessonSession;
use App\Models\LessonSessionAttempt;
use App\Models\LessonSessionParticipant;
use App\Models\LessonSessionTask;
use App\Models\StudentNote;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Бизнес-логика жизненного цикла lesson_session.
 * Контроллеры обращаются ТОЛЬКО через этот сервис, минуя прямые операции
 * с LessonSession* моделями.
 */
class LessonSessionService
{
    /** Статусы, в которых сессия занимает join_code и доступна для входа по коду. */
    public const JOIN_CODE_STATUSES = [LessonSession::STATUS_DRAFT, LessonSession::STATUS_LIVE];

    /** На сколько минут ученик локается на странице урока после входа по коду. */
    public const LOCK_MINUTES = 60;

    /** Present-интервал без heartbeat дольше этого — считаем, что ученик молча ушёл. */
    public const ACTIVITY_STALE_SECONDS = 25;

    /** Ответ в течение этого окна после возврата из away — подозрение на списывание. */
    public const QUICK_AFTER_AWAY_SECONDS = 20;

    public function __construct(
        private readonly TaskBankResolver $bankResolver,
        private readonly TaskAnswerResolver $answerResolver,
    ) {
    }

    /**
     * Создаёт draft-сессию из слота расписания (ученики входят сами по коду).
     * Идемпотентно: если для этого слота сегодня уже есть live/draft — возвращает её.
     */
    public function createFromSchedule(LessonSchedule $slot): LessonSession
    {
        $existing = LessonSession::where('schedule_id', $slot->id)
            ->whereIn('status', [LessonSession::STATUS_DRAFT, LessonSession::STATUS_LIVE])
            ->whereDate('created_at', today())
            ->first();
        if ($existing) {
            return $existing;
        }

        // Урок v2: участники входят только по коду — автодобавления из слота нет.
        return LessonSession::create([
            'teacher_id'  => $slot->teacher_id,
            'schedule_id' => $slot->id,
            'status'      => LessonSession::STATUS_DRAFT,
            'join_code'   => $this->generateJoinCode(),
        ]);
    }

    /**
     * Создаёт/возвращает draft-сессию для слота расписания Evrium.
     *
     * Слоты Evrium приходят из внешней CRM и не имеют локального id, поэтому
     * идемпотентность строится на (teacher_id + starts_at): если для этого
     * времени уже есть draft/live сессия — возвращаем её, иначе создаём черновик.
     * Это позволяет учителю заранее (в режиме draft) набрать задания для урока.
     *
     * @param  array<int>  $studentIds  сохранён для совместимости вызова; участники входят по коду
     */
    public function createFromEvriumSlot(User $teacher, string $startsAt, ?string $endsAt, array $studentIds): LessonSession
    {
        $startsAt = Carbon::parse($startsAt);
        $endsAt   = $endsAt ? Carbon::parse($endsAt) : null;

        $existing = LessonSession::where('teacher_id', $teacher->id)
            ->whereIn('status', self::JOIN_CODE_STATUSES)
            ->where('starts_at', $startsAt)
            ->first();
        if ($existing) {
            return $existing;
        }

        // Урок v2: участники входят только по коду — $studentIds сохранён в сигнатуре
        // для совместимости вызова из fromSlot, но автодобавления больше нет.
        return LessonSession::create([
            'teacher_id' => $teacher->id,
            'status'     => LessonSession::STATUS_DRAFT,
            'starts_at'  => $startsAt,
            'ends_at'    => $endsAt,
            'join_code'  => $this->generateJoinCode(),
        ]);
    }

    /**
     * Создаёт ad-hoc сессию без расписания, с кодом входа.
     * $startsAt — планируемое время урока (для отображения в списке уроков);
     * без него время проставится фактическое при старте.
     */
    public function createAdhoc(User $teacher, ?Carbon $startsAt = null): LessonSession
    {
        return LessonSession::create([
            'teacher_id' => $teacher->id,
            'status'     => LessonSession::STATUS_DRAFT,
            'starts_at'  => $startsAt,
            'join_code'  => $this->generateJoinCode(),
        ]);
    }

    /**
     * «Следующий урок»: черновик на то же время через неделю.
     * Идемпотентно по (teacher, starts_at) — повторный клик вернёт тот же черновик.
     * Заметку и задания учитель добавляет в новый урок сам.
     */
    public function createFollowUp(LessonSession $session): LessonSession
    {
        $startsAt = ($session->starts_at ?? now())->copy()->addWeek();
        $endsAt   = $session->ends_at?->copy()->addWeek();

        return $this->createFromEvriumSlot(
            $session->teacher,
            $startsAt->toDateTimeString(),
            $endsAt?->toDateTimeString(),
            []
        );
    }

    /**
     * Заметка учителя к уроку (ученикам не отдаётся).
     */
    public function updateNote(LessonSession $session, ?string $note): void
    {
        $session->update(['note' => ($note !== null && trim($note) !== '') ? $note : null]);
    }

    /**
     * Добавляет задачу в сессию (резолвит из банка, кэширует payload).
     * Доступно только для draft и live (учитель может донабрать задач по ходу).
     */
    public function addTask(
        LessonSession $session,
        string $bank,
        array $refs,
        ?int $assignedStudentId = null
    ): LessonSessionTask {
        if ($session->status === LessonSession::STATUS_ENDED) {
            throw new DomainException('Нельзя добавить задачу в завершённую сессию');
        }

        $resolved = $this->bankResolver->resolve($bank, $refs);

        $position = (int) ($session->tasks()->max('position') ?? 0) + 1;

        return LessonSessionTask::create([
            'lesson_session_id'   => $session->id,
            'assigned_student_id' => $assignedStudentId,
            'position'            => $position,
            'bank'                => $bank,
            'grade'               => $refs['grade']      ?? null,
            'topic_id'            => $refs['topic_id']   ?? null,
            'skill_slug'          => $refs['skill_slug'] ?? null,
            'task_ref'            => $this->serializeRefs($refs),
            'task_payload'        => $resolved,
            'correct_answer'      => $resolved['answer'],
        ]);
    }

    public function removeTask(LessonSessionTask $task): void
    {
        if ($task->session->status !== LessonSession::STATUS_DRAFT) {
            throw new DomainException('Удалять задачи можно только в draft-сессии');
        }
        $task->delete();
    }

    public function start(LessonSession $session): LessonSession
    {
        if ($session->status !== LessonSession::STATUS_DRAFT) {
            throw new DomainException("Сессия в статусе {$session->status}, старт невозможен");
        }
        if ($session->tasks()->count() === 0) {
            throw new DomainException('Нельзя запустить урок без задач');
        }
        $session->update([
            'status'    => LessonSession::STATUS_LIVE,
            'starts_at' => now(),
        ]);
        return $session->fresh();
    }

    public function end(LessonSession $session): LessonSession
    {
        if ($session->status !== LessonSession::STATUS_LIVE) {
            throw new DomainException("Сессия в статусе {$session->status}, завершить нельзя");
        }
        $endsAt = now();
        $session->update([
            'status'  => LessonSession::STATUS_ENDED,
            'ends_at' => $endsAt,
        ]);
        // Открытые интервалы активности не должны переживать урок.
        LessonActivityInterval::where('lesson_session_id', $session->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => $endsAt]);
        return $session->fresh();
    }

    /**
     * Присоединяет ученика по 4-значному коду урока.
     * Вход разрешён в draft и live: ученик может зайти до старта и ждать;
     * submitAnswer всё равно требует live-статус.
     */
    public function joinByCode(string $code, User $student): LessonSession
    {
        $session = LessonSession::whereIn('status', self::JOIN_CODE_STATUSES)
            ->where('join_code', $code)
            ->first();
        if (!$session) {
            throw new DomainException('Урок с таким кодом не найден');
        }

        LessonSessionParticipant::firstOrCreate(
            [
                'lesson_session_id' => $session->id,
                'student_id'        => $student->id,
            ],
            [
                'source'       => LessonSessionParticipant::SOURCE_CODE,
                // Лок ставится только при создании участия: повторный вход НЕ продлевает.
                'locked_until' => now()->addMinutes(self::LOCK_MINUTES),
            ]
        );

        return $session;
    }

    /**
     * Участие ученика с активным локом (урок не завершён, не отпущен, время не вышло).
     */
    public function activeLockFor(User $student): ?LessonSessionParticipant
    {
        return LessonSessionParticipant::where('student_id', $student->id)
            ->whereNull('released_at')
            ->where('locked_until', '>', now())
            ->whereHas('session', fn ($q) => $q->whereIn('status', self::JOIN_CODE_STATUSES))
            ->latest('joined_at')
            ->first();
    }

    /**
     * Учитель вручную отпускает ученика с урока (снимает лок).
     */
    public function release(LessonSession $session, int $studentId, User $teacher): void
    {
        $p = LessonSessionParticipant::where('lesson_session_id', $session->id)
            ->where('student_id', $studentId)
            ->firstOrFail();
        $p->update(['released_at' => now(), 'released_by' => $teacher->id]);
    }

    /**
     * Пишет активность ученика на странице урока непрерывным таймлайном
     * present/away. Сервер ставит время сам (клиенту не доверяем).
     * $visible=true — на странице, false — свернул/ушёл.
     */
    public function recordActivity(LessonSession $session, User $student, bool $visible): void
    {
        $desired = $visible ? LessonActivityInterval::KIND_PRESENT : LessonActivityInterval::KIND_AWAY;

        // Heartbeat и visibilitychange часто приходят одновременно; без мьютекса
        // конкурирующие запросы плодили дубли открытых интервалов, которые никогда
        // не закрывались и раздували away на часы.
        DB::transaction(function () use ($session, $student, $desired) {
            $now = now();

            LessonSessionParticipant::where('lesson_session_id', $session->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            $open = LessonActivityInterval::where('lesson_session_id', $session->id)
                ->where('student_id', $student->id)
                ->whereNull('ended_at')
                ->orderByDesc('started_at')
                ->get();

            $keep = null;
            foreach ($open as $iv) {
                if ($keep === null && $iv->kind === $desired) {
                    $keep = $iv;
                    continue;
                }
                // Закрывает и смену состояния, и осиротевшие дубли прошлых гонок.
                $iv->update(['ended_at' => $now]);
            }

            // Состояние не изменилось → heartbeat (только бампаем updated_at).
            if ($keep) {
                $keep->update(['updated_at' => $now]);
                return;
            }

            LessonActivityInterval::create([
                'lesson_session_id' => $session->id,
                'student_id'        => $student->id,
                'kind'              => $desired,
                'started_at'        => $now,
                'updated_at'        => $now,
            ]);
        });
    }

    /**
     * Агрегаты активности по каждому участнику: текущее состояние, число отлучек,
     * время вне страницы и на странице (в секундах).
     *
     * @return array<int, array{state:string, away_count:int, away_seconds:int, present_seconds:int}>
     */
    public function activitySummary(LessonSession $session): array
    {
        $nowRef = $session->status === LessonSession::STATUS_ENDED && $session->ends_at
            ? $session->ends_at
            : now();
        $staleBefore = now()->copy()->subSeconds(self::ACTIVITY_STALE_SECONDS);

        $byStudent = LessonActivityInterval::where('lesson_session_id', $session->id)
            ->orderBy('started_at')
            ->get()
            ->groupBy('student_id');

        $out = [];
        foreach ($byStudent as $studentId => $intervals) {
            $list = $intervals->values();
            $n = $list->count();
            $presentSec = 0;
            $awaySec = 0;
            $awayCount = 0;

            foreach ($list as $i => $iv) {
                $open = $iv->ended_at === null;

                if ($iv->kind === LessonActivityInterval::KIND_AWAY) {
                    $end = $iv->ended_at ?? $nowRef;
                } else {
                    // Молчащий present (нет heartbeat) — ученик ушёл, обрезаем по updated_at.
                    $stale = $open && $iv->updated_at && $iv->updated_at->lessThan($staleBefore);
                    $end = $iv->ended_at ?? ($stale ? $iv->updated_at : $nowRef);
                }

                // Интервал не может тянуться дальше начала следующего: осиротевшие
                // открытые дубли (гонка до фикса 07.2026) иначе раздувают сумму на часы.
                if ($i + 1 < $n && $end->greaterThan($list[$i + 1]->started_at)) {
                    $end = $list[$i + 1]->started_at;
                }

                $sec = max(0, $iv->started_at->diffInSeconds($end, false));
                if ($iv->kind === LessonActivityInterval::KIND_AWAY) {
                    $awaySec += $sec;
                    // Схлопнутые в ноль дубли — не отлучки.
                    if ($sec >= 1) {
                        $awayCount++;
                    }
                } else {
                    $presentSec += $sec;
                }
            }

            // Текущее состояние — по последнему интервалу.
            $lastIv = $list->last();
            $state = 'gone';
            if ($lastIv && $lastIv->ended_at === null) {
                if ($lastIv->kind === LessonActivityInterval::KIND_AWAY) {
                    $state = 'away';
                } else {
                    $stale = $lastIv->updated_at && $lastIv->updated_at->lessThan($staleBefore);
                    $state = $stale ? 'away' : 'present';
                }
            }

            $out[(int) $studentId] = [
                'state'           => $state,
                'away_count'      => $awayCount,
                'away_seconds'    => (int) $awaySec,
                'present_seconds' => (int) $presentSec,
            ];
        }

        return $out;
    }

    /**
     * Пишет поведенческое событие ученика (сигнал списывания): copy_task /
     * paste_answer / resume. Время ставит сервер, meta — только whitelisted-ключи.
     */
    public function recordBehaviorEvent(
        LessonSession $session,
        User $student,
        string $kind,
        ?LessonSessionTask $task = null,
        array $meta = []
    ): LessonBehaviorEvent {
        if (!in_array($kind, LessonBehaviorEvent::KINDS, true)) {
            throw new DomainException('Неизвестный тип события');
        }
        if ($task && $task->lesson_session_id !== $session->id) {
            throw new DomainException('Задача не относится к этой сессии');
        }

        $clean = array_intersect_key($meta, array_flip(['length', 'away_seconds']));
        $clean = array_map(fn ($v) => max(0, (int) $v), $clean);

        return LessonBehaviorEvent::create([
            'lesson_session_id'      => $session->id,
            'student_id'             => $student->id,
            'lesson_session_task_id' => $task?->id,
            'kind'                   => $kind,
            'meta'                   => $clean ?: null,
            'occurred_at'            => now(),
        ]);
    }

    /**
     * Сводка поведенческих сигналов по каждому участнику:
     * счётчики copy/paste, задачи со вставленным ответом и задачи,
     * отвеченные в течение QUICK_AFTER_AWAY_SECONDS после возврата из away.
     *
     * @return array<int, array{copy_count:int, paste_count:int, pasted_tasks:int[], quick_after_away_tasks:int[]}>
     */
    public function behaviorSummary(LessonSession $session): array
    {
        $out = [];
        $entry = fn () => [
            'copy_count'             => 0,
            'paste_count'            => 0,
            'pasted_tasks'           => [],
            'quick_after_away_tasks' => [],
        ];

        $events = LessonBehaviorEvent::where('lesson_session_id', $session->id)->get();
        foreach ($events as $e) {
            $sid = (int) $e->student_id;
            $out[$sid] ??= $entry();
            if ($e->kind === LessonBehaviorEvent::KIND_COPY_TASK) {
                $out[$sid]['copy_count']++;
            } elseif ($e->kind === LessonBehaviorEvent::KIND_PASTE_ANSWER) {
                $out[$sid]['paste_count']++;
                if ($e->lesson_session_task_id) {
                    $out[$sid]['pasted_tasks'][] = (int) $e->lesson_session_task_id;
                }
            }
        }

        // «Ответил сразу после возврата»: answered_at попадает в окно после конца away.
        $awayEnds = LessonActivityInterval::where('lesson_session_id', $session->id)
            ->where('kind', LessonActivityInterval::KIND_AWAY)
            ->whereNotNull('ended_at')
            ->get()
            ->groupBy('student_id');

        $attempts = LessonSessionAttempt::where('lesson_session_id', $session->id)
            ->whereNotNull('answered_at')
            ->get();

        foreach ($attempts as $a) {
            foreach ($awayEnds->get($a->student_id, collect()) as $iv) {
                $delta = $iv->ended_at->diffInSeconds($a->answered_at, false);
                if ($delta >= 0 && $delta <= self::QUICK_AFTER_AWAY_SECONDS) {
                    $sid = (int) $a->student_id;
                    $out[$sid] ??= $entry();
                    $out[$sid]['quick_after_away_tasks'][] = (int) $a->lesson_session_task_id;
                    break;
                }
            }
        }

        foreach ($out as &$row) {
            $row['pasted_tasks'] = array_values(array_unique($row['pasted_tasks']));
            $row['quick_after_away_tasks'] = array_values(array_unique($row['quick_after_away_tasks']));
        }

        return $out;
    }

    /**
     * Принимает ответ ученика. Если у задачи есть correct_answer — авточек.
     * UPSERT по (session, student, task).
     */
    public function submitAnswer(
        LessonSession $session,
        User $student,
        LessonSessionTask $task,
        string $answer
    ): LessonSessionAttempt {
        if (!$session->isLive()) {
            throw new DomainException('Урок не активен — ответ не принят');
        }
        if ($task->lesson_session_id !== $session->id) {
            throw new DomainException('Задача не относится к этой сессии');
        }
        if (!$this->isParticipant($session, $student)) {
            throw new DomainException('Ученик не участник этой сессии');
        }
        if (!$task->visibleTo($student->id)) {
            throw new DomainException('Эта задача назначена другому ученику');
        }

        $isCorrect = $task->correct_answer !== ''
            ? $this->answerResolver->isCorrect($answer, $task->correct_answer)
            : null;

        return LessonSessionAttempt::updateOrCreate(
            [
                'lesson_session_id'      => $session->id,
                'lesson_session_task_id' => $task->id,
                'student_id'             => $student->id,
            ],
            [
                'answer_raw'  => $answer,
                'is_correct'  => $isCorrect,
                'answered_at' => now(),
            ]
        );
    }

    /**
     * Кнопка «не понимает» на уроке: фиксирует слабое место ученика по задаче
     * (без LLM). Пишет student_notes(kind=weakness, source=lesson_button).
     *
     * alg-skill задачи имеют topic_id=null, но заданный skill_slug — берём его.
     */
    public function recordDontUnderstand(
        LessonSession $s,
        User $teacher,
        User $student,
        LessonSessionTask $task
    ): StudentNote {
        $topic = $task->topic_id ?: ($task->skill_slug ?: null);
        $expr = (string) ($task->task_payload['expression'] ?? '');

        return StudentNote::create([
            'student_id'        => $student->id,
            'teacher_id'        => $teacher->id,
            'lesson_session_id' => $s->id,
            'task_ref'          => $task->task_ref,
            'topic_tag'         => $topic,
            'kind'              => 'weakness',
            'source'            => 'lesson_button',
            'body'              => 'Не понимает: ' . mb_substr($expr !== '' ? $expr : ('задача ' . $task->position), 0, 200),
        ]);
    }

    public function isParticipant(LessonSession $session, User $student): bool
    {
        return $this->isParticipantId($session, $student->id);
    }

    public function isParticipantId(LessonSession $session, int $studentId): bool
    {
        return LessonSessionParticipant::where('lesson_session_id', $session->id)
            ->where('student_id', $studentId)
            ->exists();
    }

    /** 4-значный код, уникальный среди draft/live сессий (после ended код освобождается). */
    private function generateJoinCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (LessonSession::whereIn('status', self::JOIN_CODE_STATUSES)
            ->where('join_code', $code)->exists());
        return $code;
    }

    private function serializeRefs(array $refs): string
    {
        ksort($refs);
        return json_encode($refs, JSON_UNESCAPED_UNICODE);
    }
}
