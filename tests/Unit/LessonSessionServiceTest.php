<?php

namespace Tests\Unit;

use App\Models\LessonSchedule;
use App\Models\LessonSession;
use App\Models\LessonSessionTask;
use App\Models\User;
use App\Services\LessonSessionService;
use App\Services\TaskAnswerResolver;
use App\Services\TaskBankResolver;
use DomainException;
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

    public function test_create_from_schedule_adds_student_as_participant(): void
    {
        $svc = $this->service();
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();
        $slot = $this->makeSlot($teacher, $student);

        $session = $svc->createFromSchedule($slot);

        $this->assertSame(LessonSession::STATUS_DRAFT, $session->status);
        $this->assertSame($teacher->id, $session->teacher_id);
        $this->assertSame($slot->id, $session->schedule_id);
        $this->assertTrue($svc->isParticipant($session, $student));
    }

    public function test_create_from_schedule_is_idempotent_within_same_day(): void
    {
        $svc = $this->service();
        $slot = $this->makeSlot($this->makeTeacher(), $this->makeStudent());

        $s1 = $svc->createFromSchedule($slot);
        $s2 = $svc->createFromSchedule($slot);

        $this->assertSame($s1->id, $s2->id);
    }

    public function test_create_adhoc_generates_invite_token(): void
    {
        $session = $this->service()->createAdhoc($this->makeTeacher());

        $this->assertNull($session->schedule_id);
        $this->assertNotNull($session->invite_token);
        $this->assertSame(16, strlen($session->invite_token));
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

    public function test_join_by_token_works_only_when_live(): void
    {
        $svc = $this->service();
        $session = $svc->createAdhoc($this->makeTeacher());
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $student = $this->makeStudent();

        try {
            $svc->joinByToken($session->invite_token, $student);
            $this->fail('expected DomainException for draft session');
        } catch (DomainException) {
            // expected
        }

        $svc->start($session);
        $joined = $svc->joinByToken($session->invite_token, $student);

        $this->assertSame($session->id, $joined->id);
        $this->assertTrue($svc->isParticipant($joined, $student));
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
        $svc->end($session);

        $this->expectException(DomainException::class);
        $svc->submitAnswer($session, $student, $task, '-2');
    }
}
