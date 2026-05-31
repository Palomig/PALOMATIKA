<?php

namespace Tests\Feature;

use App\Models\LessonSchedule;
use App\Models\LessonSession;
use App\Models\LessonSessionTask;
use App\Models\User;
use App\Services\LessonSessionService;
use App\Services\TaskAnswerResolver;
use App\Services\TaskBankResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLessonControllerTest extends TestCase
{
    use RefreshDatabase;

    private const STUDENT_BASE = 'http://student.palomatika.ru';
    private const MAIN_BASE    = 'http://palomatika.ru';

    private function teacher(): User
    {
        return User::create(['name' => 'T', 'email' => 't+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher']);
    }

    private function student(): User
    {
        // onboarding_completed_at нужно для middleware pwa.onboarding (если есть)
        return User::create([
            'name' => 'S', 'email' => 's+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'student',
            'onboarding_completed_at' => now(),
        ]);
    }

    /** Хелпер: создать сессию с одной alg-skill задачей и стартовать */
    private function makeLiveSessionWithTask(User $teacher, User $student): array
    {
        $svc = app(LessonSessionService::class);
        $slot = LessonSchedule::create([
            'teacher_id' => $teacher->id, 'student_id' => $student->id,
            'day_of_week' => (int) now()->format('N'), 'start_time' => '10:00', 'is_active' => true,
        ]);
        $session = $svc->createFromSchedule($slot);
        $task = $svc->addTask($session, 'alg-skill', [
            'grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1,
        ]);
        $svc->start($session);
        return [$session->fresh(), $task];
    }

    public function test_active_returns_null_when_no_live_session(): void
    {
        $this->actingAs($this->student())
            ->getJson(self::STUDENT_BASE . '/lessons/active')
            ->assertOk()
            ->assertJson(['session' => null]);
    }

    public function test_active_returns_session_when_student_is_participant(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        [$session] = $this->makeLiveSessionWithTask($teacher, $student);

        $this->actingAs($student)
            ->getJson(self::STUDENT_BASE . '/lessons/active')
            ->assertOk()
            ->assertJsonPath('session.id', $session->id);
    }

    public function test_state_returns_tasks_without_correct_answer(): void
    {
        [$session, $task] = $this->makeLiveSessionWithTask($this->teacher(), $student = $this->student());

        $resp = $this->actingAs($student)
            ->getJson(self::STUDENT_BASE . "/lessons/{$session->id}/state")
            ->assertOk();

        $taskJson = $resp->json('tasks.0');
        $this->assertSame('-8 + 6', $taskJson['payload']['expression']);
        $this->assertArrayNotHasKey('answer', $taskJson['payload'], 'answer не должен утечь');
        $this->assertArrayNotHasKey('raw', $taskJson['payload'], 'raw не должен утечь');
        $this->assertNull($taskJson['my_answer']);
    }

    public function test_non_participant_gets_403(): void
    {
        [$session] = $this->makeLiveSessionWithTask($this->teacher(), $this->student());
        $intruder = $this->student();

        $this->actingAs($intruder)
            ->getJson(self::STUDENT_BASE . "/lessons/{$session->id}/state")
            ->assertForbidden();
    }

    public function test_submit_answer_works_and_returns_no_is_correct(): void
    {
        [$session, $task] = $this->makeLiveSessionWithTask($this->teacher(), $student = $this->student());

        $resp = $this->actingAs($student)
            ->postJson(self::STUDENT_BASE . "/lessons/{$session->id}/answer", [
                'task_id' => $task->id, 'answer' => '-2',
            ])->assertOk();

        $this->assertSame(true, $resp->json('ok'));
        $this->assertNotNull($resp->json('answered_at'));
        $this->assertArrayNotHasKey('is_correct', $resp->json());
        $this->assertDatabaseHas('lesson_session_attempts', [
            'lesson_session_id'      => $session->id,
            'lesson_session_task_id' => $task->id,
            'student_id'             => $student->id,
            'answer_raw'             => '-2',
            'is_correct'             => true,
        ]);
    }

    public function test_submit_after_end_returns_422(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        [$session, $task] = $this->makeLiveSessionWithTask($teacher, $student);
        app(LessonSessionService::class)->end($session);

        $this->actingAs($student)
            ->postJson(self::STUDENT_BASE . "/lessons/{$session->id}/answer", [
                'task_id' => $task->id, 'answer' => '-2',
            ])->assertStatus(422);
    }

    public function test_other_student_cannot_submit(): void
    {
        [$session, $task] = $this->makeLiveSessionWithTask($this->teacher(), $this->student());
        $intruder = $this->student();

        $this->actingAs($intruder)
            ->postJson(self::STUDENT_BASE . "/lessons/{$session->id}/answer", [
                'task_id' => $task->id, 'answer' => '-2',
            ])->assertForbidden();
    }

    // --- LessonJoinController (main domain) ---

    public function test_join_guest_redirects_to_student_login(): void
    {
        $session = LessonSession::create(['teacher_id' => $this->teacher()->id, 'status' => 'live', 'invite_token' => str_repeat('a', 16)]);

        $resp = $this->get(self::MAIN_BASE . "/lesson/join/{$session->invite_token}")
            ->assertRedirect();

        $this->assertStringContainsString('student.palomatika.ru/login', $resp->headers->get('Location'));
    }

    public function test_join_teacher_gets_403(): void
    {
        $session = LessonSession::create(['teacher_id' => $this->teacher()->id, 'status' => 'live', 'invite_token' => str_repeat('b', 16)]);

        $this->actingAs($this->teacher())
            ->get(self::MAIN_BASE . "/lesson/join/{$session->invite_token}")
            ->assertForbidden();
    }

    public function test_join_unknown_token_404(): void
    {
        $this->actingAs($this->student())
            ->get(self::MAIN_BASE . '/lesson/join/' . str_repeat('z', 16))
            ->assertNotFound();
    }

    public function test_join_student_redirects_to_lesson_page(): void
    {
        $teacher = $this->teacher();
        $session = app(LessonSessionService::class)->createAdhoc($teacher);
        app(LessonSessionService::class)->addTask($session, 'alg-skill', [
            'grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1,
        ]);
        app(LessonSessionService::class)->start($session);
        $session->refresh();

        $student = $this->student();
        $resp = $this->actingAs($student)
            ->get(self::MAIN_BASE . "/lesson/join/{$session->invite_token}")
            ->assertRedirect();

        $this->assertStringContainsString("student.palomatika.ru/lessons/{$session->id}", $resp->headers->get('Location'));
        $this->assertDatabaseHas('lesson_session_participants', [
            'lesson_session_id' => $session->id,
            'student_id'        => $student->id,
            'source'            => 'invite',
        ]);
    }
}
