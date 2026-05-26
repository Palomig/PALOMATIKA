<?php

namespace App\Services;

use App\Models\LessonSchedule;
use App\Models\LessonSession;
use App\Models\LessonSessionAttempt;
use App\Models\LessonSessionParticipant;
use App\Models\LessonSessionTask;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Бизнес-логика жизненного цикла lesson_session.
 * Контроллеры обращаются ТОЛЬКО через этот сервис, минуя прямые операции
 * с LessonSession* моделями.
 */
class LessonSessionService
{
    public function __construct(
        private readonly TaskBankResolver $bankResolver,
        private readonly TaskAnswerResolver $answerResolver,
    ) {
    }

    /**
     * Создаёт draft-сессию из слота расписания + переносит student в participants.
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

        return DB::transaction(function () use ($slot) {
            $session = LessonSession::create([
                'teacher_id'   => $slot->teacher_id,
                'schedule_id'  => $slot->id,
                'status'       => LessonSession::STATUS_DRAFT,
                'invite_token' => $this->generateInviteToken(),
            ]);
            LessonSessionParticipant::create([
                'lesson_session_id' => $session->id,
                'student_id'        => $slot->student_id,
                'source'            => LessonSessionParticipant::SOURCE_SCHEDULE,
            ]);
            return $session;
        });
    }

    /**
     * Создаёт ad-hoc сессию без расписания, с инвайт-токеном.
     */
    public function createAdhoc(User $teacher): LessonSession
    {
        return LessonSession::create([
            'teacher_id'   => $teacher->id,
            'status'       => LessonSession::STATUS_DRAFT,
            'invite_token' => $this->generateInviteToken(),
        ]);
    }

    /**
     * Добавляет задачу в сессию (резолвит из банка, кэширует payload).
     * Доступно только для draft и live (учитель может донабрать задач по ходу).
     */
    public function addTask(LessonSession $session, string $bank, array $refs): LessonSessionTask
    {
        if ($session->status === LessonSession::STATUS_ENDED) {
            throw new DomainException('Нельзя добавить задачу в завершённую сессию');
        }

        $resolved = $this->bankResolver->resolve($bank, $refs);

        $position = (int) ($session->tasks()->max('position') ?? 0) + 1;

        return LessonSessionTask::create([
            'lesson_session_id' => $session->id,
            'position'          => $position,
            'bank'              => $bank,
            'grade'             => $refs['grade']      ?? null,
            'topic_id'          => $refs['topic_id']   ?? null,
            'skill_slug'        => $refs['skill_slug'] ?? null,
            'task_ref'          => $this->serializeRefs($refs),
            'task_payload'      => $resolved,
            'correct_answer'    => $resolved['answer'],
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
        $session->update([
            'status'  => LessonSession::STATUS_ENDED,
            'ends_at' => now(),
        ]);
        return $session->fresh();
    }

    /**
     * Присоединяет ученика по инвайт-токену. Сессия должна быть live.
     */
    public function joinByToken(string $token, User $student): LessonSession
    {
        $session = LessonSession::where('invite_token', $token)->first();
        if (!$session) {
            throw new DomainException('Урок не найден');
        }
        if (!$session->isLive()) {
            throw new DomainException('Урок не активен');
        }

        LessonSessionParticipant::firstOrCreate(
            [
                'lesson_session_id' => $session->id,
                'student_id'        => $student->id,
            ],
            ['source' => LessonSessionParticipant::SOURCE_INVITE]
        );

        return $session;
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

    public function isParticipant(LessonSession $session, User $student): bool
    {
        return LessonSessionParticipant::where('lesson_session_id', $session->id)
            ->where('student_id', $student->id)
            ->exists();
    }

    private function generateInviteToken(): string
    {
        do {
            $token = Str::lower(Str::random(16));
        } while (LessonSession::where('invite_token', $token)->exists());
        return $token;
    }

    private function serializeRefs(array $refs): string
    {
        ksort($refs);
        return json_encode($refs, JSON_UNESCAPED_UNICODE);
    }
}
