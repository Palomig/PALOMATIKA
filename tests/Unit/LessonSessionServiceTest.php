<?php

namespace Tests\Unit;

use App\Models\LessonActivityInterval;
use App\Models\LessonSchedule;
use App\Models\LessonSession;
use App\Models\LessonSessionTask;
use App\Models\User;
use App\Services\LessonSessionService;
use App\Services\TaskAnswerResolver;
use App\Services\TaskBankResolver;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): LessonSessionService
    {
        return new LessonSessionService(
            new TaskBankResolver(),
            new TaskAnswerResolver(),
        );
    }

    private function makeTeacher(): User
    {
        return User::create([
            'name'     => 'T',
            'email'    => 't+' . uniqid() . '@t.t',
            'password' => 'x',
            'role'     => 'teacher',
        ]);
    }

    private function makeStudent(): User
    {
        return User::create([
            'name'     => 'S',
            'email'    => 's+' . uniqid() . '@t.t',
            'password' => 'x',
            'role'     => 'student',
        ]);
    }

    private function makeSlot(User $teacher, User $student): LessonSchedule
    {
        return LessonSchedule::create([
            'teacher_id'  => $teacher->id,
            'student_id'  => $student->id,
            'day_of_week' => (int) now()->format('N'),
            'start_time'  => '10:00',
            'end_time'    => '11:00',
            'is_active'   => true,
        ]);
    }

    /** Хелпер: live-сессия с одной задачей (createAdhoc + addTask + start). */
    private function makeLiveSession(LessonSessionService $svc, ?User $teacher = null): LessonSession
    {
        $session = $svc->createAdhoc($teacher ?? $this->makeTeacher());
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $svc->start($session);
        return $session->fresh();
    }

    // --- активность ученика на уроке ---

    public function test_record_activity_opens_present_then_toggles_away_and_back(): void
    {
        $svc = $this->service();
        $session = $this->makeLiveSession($svc);
        $student = $this->makeStudent();
        $svc->joinByCode($session->join_code, $student);

        // Вход на страницу → present.
        $svc->recordActivity($session, $student, true);
        $intervals = LessonActivityInterval::where('student_id', $student->id)->orderBy('id')->get();
        $this->assertCount(1, $intervals);
        $this->assertSame('present', $intervals[0]->kind);
        $this->assertNull($intervals[0]->ended_at);

        // Свернул → present закрыт, away открыт.
        $this->travel(30)->seconds();
        $svc->recordActivity($session, $student, false);
        $intervals = LessonActivityInterval::where('student_id', $student->id)->orderBy('id')->get();
        $this->assertCount(2, $intervals);
        $this->assertNotNull($intervals[0]->ended_at);
        $this->assertSame('away', $intervals[1]->kind);
        $this->assertNull($intervals[1]->ended_at);

        // Вернулся → away закрыт, present открыт.
        $this->travel(20)->seconds();
        $svc->recordActivity($session, $student, true);
        $intervals = LessonActivityInterval::where('student_id', $student->id)->orderBy('id')->get();
        $this->assertCount(3, $intervals);
        $this->assertNotNull($intervals[1]->ended_at);
        $this->assertSame('present', $intervals[2]->kind);
        $this->assertNull($intervals[2]->ended_at);
    }

    public function test_record_activity_heartbeat_does_not_create_new_interval(): void
    {
        $svc = $this->service();
        $session = $this->makeLiveSession($svc);
        $student = $this->makeStudent();
        $svc->joinByCode($session->join_code, $student);

        $svc->recordActivity($session, $student, true);
        $this->travel(10)->seconds();
        $svc->recordActivity($session, $student, true); // heartbeat
        $this->travel(10)->seconds();
        $svc->recordActivity($session, $student, true); // heartbeat

        $intervals = LessonActivityInterval::where('student_id', $student->id)->get();
        $this->assertCount(1, $intervals);
        // updated_at продлился до последнего heartbeat.
        $this->assertTrue($intervals[0]->updated_at->greaterThan($intervals[0]->started_at));
    }

    public function test_activity_summary_counts_present_away_and_state(): void
    {
        $svc = $this->service();
        $session = $this->makeLiveSession($svc);
        $student = $this->makeStudent();
        $svc->joinByCode($session->join_code, $student);

        $svc->recordActivity($session, $student, true);   // present 0s
        $this->travel(60)->seconds();
        $svc->recordActivity($session, $student, false);  // → away @60s
        $this->travel(30)->seconds();
        $svc->recordActivity($session, $student, true);   // away 30s → present @90s
        $this->travel(20)->seconds();                     // present idet, +20s

        $summary = $svc->activitySummary($session);
        $this->assertArrayHasKey($student->id, $summary);
        $a = $summary[$student->id];

        $this->assertSame('present', $a['state']);
        $this->assertSame(1, $a['away_count']);
        $this->assertEqualsWithDelta(30, $a['away_seconds'], 2);
        $this->assertEqualsWithDelta(80, $a['present_seconds'], 2); // 60 + 20
    }

    public function test_activity_summary_stale_present_counts_as_away_now(): void
    {
        $svc = $this->service();
        $session = $this->makeLiveSession($svc);
        $student = $this->makeStudent();
        $svc->joinByCode($session->join_code, $student);

        $svc->recordActivity($session, $student, true); // present, updated_at = now
        $this->travel(40)->seconds();                    // heartbeat молчит >25с

        $a = $svc->activitySummary($session)[$student->id];
        $this->assertSame('away', $a['state']); // молча закрыл вкладку
    }

    public function test_create_from_schedule_no_longer_autoadds_participants(): void
    {
        $svc = $this->service();
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();
        $slot = $this->makeSlot($teacher, $student);

        $session = $svc->createFromSchedule($slot);

        $this->assertSame(LessonSession::STATUS_DRAFT, $session->status);
        $this->assertSame($teacher->id, $session->teacher_id);
        $this->assertSame($slot->id, $session->schedule_id);
        // Урок v2: участники входят только по коду, автодобавления из слота нет.
        $this->assertSame(0, $session->participants()->count());
    }

    public function test_create_from_schedule_is_idempotent_within_same_day(): void
    {
        $svc = $this->service();
        $slot = $this->makeSlot($this->makeTeacher(), $this->makeStudent());

        $s1 = $svc->createFromSchedule($slot);
        $s2 = $svc->createFromSchedule($slot);

        $this->assertSame($s1->id, $s2->id);
    }

    public function test_create_adhoc_accepts_planned_starts_at(): void
    {
        $svc = $this->service();
        $at = now()->addDay()->setTime(15, 30);

        $session = $svc->createAdhoc($this->makeTeacher(), $at);

        $this->assertTrue($session->starts_at->equalTo($at));
        // Без указания времени starts_at остаётся пустым (проставится при старте).
        $this->assertNull($svc->createAdhoc($this->makeTeacher())->starts_at);
    }

    public function test_create_follow_up_next_week_same_time_idempotent(): void
    {
        $svc = $this->service();
        $teacher = $this->makeTeacher();
        $at = now()->setTime(15, 0)->setSeconds(0)->setMicroseconds(0);
        $session = $svc->createAdhoc($teacher, $at);

        $next = $svc->createFollowUp($session);

        $this->assertTrue($next->starts_at->equalTo($at->copy()->addWeek()));
        $this->assertSame(LessonSession::STATUS_DRAFT, $next->status);
        $this->assertNotSame($session->id, $next->id);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $next->join_code);

        // Повторный вызов возвращает тот же урок, а не плодит новые.
        $this->assertSame($next->id, $svc->createFollowUp($session)->id);
    }

    public function test_create_follow_up_without_starts_at_uses_now_plus_week(): void
    {
        $svc = $this->service();
        $session = $svc->createAdhoc($this->makeTeacher());

        $next = $svc->createFollowUp($session);

        $this->assertTrue($next->starts_at->between(
            now()->addWeek()->subMinute(),
            now()->addWeek()->addMinute(),
        ));
    }

    public function test_update_note(): void
    {
        $svc = $this->service();
        $session = $svc->createAdhoc($this->makeTeacher());

        $svc->updateNote($session, 'Повторить дроби, спросить про ДЗ');
        $this->assertSame('Повторить дроби, спросить про ДЗ', $session->fresh()->note);

        $svc->updateNote($session, null);
        $this->assertNull($session->fresh()->note);
    }

    public function test_create_generates_unique_4_digit_join_code(): void
    {
        $svc = $this->service();
        $s1 = $svc->createAdhoc($this->makeTeacher());
        $s2 = $svc->createAdhoc($this->makeTeacher());

        $this->assertNull($s1->schedule_id);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $s1->join_code);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $s2->join_code);
        $this->assertNotSame($s1->join_code, $s2->join_code);
        // invite_token упразднён — больше не заполняется.
        $this->assertNull($s1->invite_token);
    }

    public function test_create_from_schedule_generates_join_code(): void
    {
        $svc = $this->service();
        $slot = $this->makeSlot($this->makeTeacher(), $this->makeStudent());
        $session = $svc->createFromSchedule($slot);

        $this->assertMatchesRegularExpression('/^\d{4}$/', $session->join_code);
    }

    public function test_add_task_resolves_from_bank_and_caches_payload(): void
    {
        $svc = $this->service();
        $session = $svc->createAdhoc($this->makeTeacher());

        $task = $svc->addTask($session, 'alg-skill', [
            'grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1,
        ]);

        $this->assertSame(1, $task->position);
        $this->assertSame('-8 + 6', $task->task_payload['expression']);
        $this->assertSame('-2', $task->correct_answer);
        $this->assertSame('expression', $task->task_payload['type']);
    }

    public function test_add_task_after_end_throws(): void
    {
        $svc = $this->service();
        $session = $svc->createAdhoc($this->makeTeacher());
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $svc->start($session);
        $svc->end($session);

        $this->expectException(DomainException::class);
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 2]);
    }

    public function test_remove_task_only_in_draft(): void
    {
        $svc = $this->service();
        $session = $svc->createAdhoc($this->makeTeacher());
        $task = $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $svc->start($session);

        $this->expectException(DomainException::class);
        $svc->removeTask($task);
    }

    public function test_start_requires_at_least_one_task(): void
    {
        $svc = $this->service();
        $session = $svc->createAdhoc($this->makeTeacher());

        $this->expectException(DomainException::class);
        $svc->start($session);
    }

    public function test_join_by_code_adds_participant_in_draft_and_live(): void
    {
        $svc = $this->service();
        $session = $svc->createAdhoc($this->makeTeacher());
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $student = $this->makeStudent();

        // Вход разрешён уже в draft (ученик ждёт старта; submitAnswer всё равно требует live).
        $joined = $svc->joinByCode($session->join_code, $student);
        $this->assertSame($session->id, $joined->id);
        $this->assertTrue($svc->isParticipant($joined, $student));

        $p = $joined->participants()->where('student_id', $student->id)->first();
        $this->assertSame('code', $p->source);
        // Лок ставится на LOCK_MINUTES (60) от момента входа.
        $this->assertNotNull($p->locked_until);
        $this->assertTrue($p->locked_until->between(
            now()->addMinutes(LessonSessionService::LOCK_MINUTES - 1),
            now()->addMinutes(LessonSessionService::LOCK_MINUTES + 1),
        ));

        // Повторный вход идемпотентен и НЕ продлевает лок.
        $lockedUntilBefore = $p->locked_until;
        $svc->start($session);
        $this->travel(5)->minutes();
        $svc->joinByCode($session->join_code, $student);
        $this->assertSame(1, $joined->participants()->where('student_id', $student->id)->count());
        $this->assertTrue($p->fresh()->locked_until->equalTo($lockedUntilBefore));
    }

    // --- activeLockFor / release (Task C2) ---

    public function test_active_lock_found_after_join_by_code(): void
    {
        $svc = $this->service();
        $session = $this->makeLiveSession($svc);
        $student = $this->makeStudent();
        $svc->joinByCode($session->join_code, $student);

        $lock = $svc->activeLockFor($student);
        $this->assertNotNull($lock);
        $this->assertSame($session->id, $lock->lesson_session_id);
        $this->assertSame($student->id, $lock->student_id);
        $this->assertTrue($lock->hasActiveLock());
    }

    public function test_active_lock_gone_after_release(): void
    {
        $svc = $this->service();
        $teacher = $this->makeTeacher();
        $session = $this->makeLiveSession($svc, $teacher);
        $student = $this->makeStudent();
        $svc->joinByCode($session->join_code, $student);

        $svc->release($session, $student->id, $teacher);

        $this->assertNull($svc->activeLockFor($student));
        $p = $session->participants()->where('student_id', $student->id)->first();
        $this->assertNotNull($p->released_at);
        $this->assertSame($teacher->id, $p->released_by);
        $this->assertFalse($p->hasActiveLock());
    }

    public function test_active_lock_gone_after_session_end(): void
    {
        $svc = $this->service();
        $session = $this->makeLiveSession($svc);
        $student = $this->makeStudent();
        $svc->joinByCode($session->join_code, $student);
        $svc->end($session);

        $this->assertNull($svc->activeLockFor($student));
        $p = $session->participants()->where('student_id', $student->id)->first();
        $this->assertFalse($p->hasActiveLock());
    }

    public function test_active_lock_expires_after_lock_minutes(): void
    {
        $svc = $this->service();
        $session = $this->makeLiveSession($svc);
        $student = $this->makeStudent();
        $svc->joinByCode($session->join_code, $student);

        $this->travel(61)->minutes();

        $this->assertNull($svc->activeLockFor($student));
        $p = $session->participants()->where('student_id', $student->id)->first();
        $this->assertFalse($p->hasActiveLock());
    }

    public function test_active_lock_null_for_student_without_lesson(): void
    {
        $this->assertNull($this->service()->activeLockFor($this->makeStudent()));
    }

    public function test_release_unknown_participant_throws(): void
    {
        $svc = $this->service();
        $teacher = $this->makeTeacher();
        $session = $this->makeLiveSession($svc, $teacher);
        $outsider = $this->makeStudent();

        $this->expectException(ModelNotFoundException::class);
        $svc->release($session, $outsider->id, $teacher);
    }

    public function test_join_by_wrong_code_throws(): void
    {
        $this->expectException(DomainException::class);
        $this->service()->joinByCode('0000', $this->makeStudent());
    }

    public function test_join_by_code_of_ended_session_throws(): void
    {
        $svc = $this->service();
        $session = $svc->createAdhoc($this->makeTeacher());
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $code = $session->join_code;
        $svc->start($session);
        $svc->end($session);

        $this->expectException(DomainException::class);
        $svc->joinByCode($code, $this->makeStudent());
    }

    public function test_submit_answer_auto_checks_correctness(): void
    {
        $svc = $this->service();
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();
        $slot = $this->makeSlot($teacher, $student);

        $session = $svc->createFromSchedule($slot);
        $task = $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $svc->start($session);
        $svc->joinByCode($session->join_code, $student);

        $correct = $svc->submitAnswer($session, $student, $task, '-2');
        $this->assertTrue($correct->is_correct);
        $this->assertSame('-2', $correct->answer_raw);

        $wrong = $svc->submitAnswer($session, $student, $task, '99');
        $this->assertFalse($wrong->is_correct);
        $this->assertSame($correct->id, $wrong->id, 'should upsert, not create new');
    }

    public function test_submit_answer_rejects_non_participant(): void
    {
        $svc = $this->service();
        $session = $svc->createAdhoc($this->makeTeacher());
        $task = $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $svc->start($session);

        $this->expectException(DomainException::class);
        $svc->submitAnswer($session, $this->makeStudent(), $task, '-2');
    }

    public function test_submit_answer_rejects_after_end(): void
    {
        $svc = $this->service();
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();
        $slot = $this->makeSlot($teacher, $student);
        $session = $svc->createFromSchedule($slot);
        $task = $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $svc->start($session);
        $svc->joinByCode($session->join_code, $student);
        $svc->end($session);

        $this->expectException(DomainException::class);
        $svc->submitAnswer($session, $student, $task, '-2');
    }
}
