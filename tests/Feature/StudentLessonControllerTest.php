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

    /** Хелпер: создать сессию с одной alg-skill задачей, стартовать и завести ученика по коду */
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
        $session->refresh();
        $svc->joinByCode($session->join_code, $student);
        return [$session, $task];
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

    // --- POST /lessons/join (вход по 4-значному коду) ---

    public function test_join_by_code_success_returns_lesson_id_and_creates_participant(): void
    {
        $teacher = $this->teacher();
        $session = app(LessonSessionService::class)->createAdhoc($teacher);
        $student = $this->student();

        $this->actingAs($student)
            ->postJson(self::STUDENT_BASE . '/lessons/join', ['code' => $session->join_code])
            ->assertOk()
            ->assertJson(['lesson_id' => $session->id]);

        $this->assertDatabaseHas('lesson_session_participants', [
            'lesson_session_id' => $session->id,
            'student_id'        => $student->id,
            'source'            => 'code',
        ]);
    }

    public function test_join_by_wrong_code_returns_422(): void
    {
        $this->actingAs($this->student())
            ->postJson(self::STUDENT_BASE . '/lessons/join', ['code' => '0000'])
            ->assertStatus(422)
            ->assertJsonStructure(['error']);
    }

    public function test_join_by_non_digit_code_fails_validation(): void
    {
        $this->actingAs($this->student())
            ->postJson(self::STUDENT_BASE . '/lessons/join', ['code' => 'abc1'])
            ->assertStatus(422);
    }

    public function test_join_by_code_then_lesson_page_opens(): void
    {
        $teacher = $this->teacher();
        $svc = app(LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        $svc->addTask($session, 'alg-skill', [
            'grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1,
        ]);
        $svc->start($session);
        $session->refresh();

        $student = $this->student();
        $this->actingAs($student)
            ->postJson(self::STUDENT_BASE . '/lessons/join', ['code' => $session->join_code])
            ->assertOk();

        $this->actingAs($student)
            ->get(self::STUDENT_BASE . "/lessons/{$session->id}")
            ->assertOk();
    }

    public function test_state_hides_other_students_personal_tasks(): void
    {
        $teacher = $this->teacher();
        $alice = $this->student();
        $bob = $this->student();
        $svc = app(LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]); // общая
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 2], $alice->id); // персональная Алисе
        $svc->start($session);
        $svc->joinByCode($session->fresh()->join_code, $alice);
        $svc->joinByCode($session->fresh()->join_code, $bob);

        // Алиса видит обе (общую + свою персональную).
        $aliceTasks = $this->actingAs($alice)->getJson(self::STUDENT_BASE . "/lessons/{$session->id}/state")->assertOk()->json('tasks');
        $this->assertCount(2, $aliceTasks);
        $this->assertTrue(collect($aliceTasks)->contains('personal', true));

        // Боб видит только общую.
        $bobTasks = $this->actingAs($bob)->getJson(self::STUDENT_BASE . "/lessons/{$session->id}/state")->assertOk()->json('tasks');
        $this->assertCount(1, $bobTasks);
        $this->assertFalse((bool) $bobTasks[0]['personal']);
    }

    public function test_activity_records_for_participant(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        [$session] = $this->makeLiveSessionWithTask($teacher, $student);

        $this->actingAs($student)
            ->postJson(self::STUDENT_BASE . "/lessons/{$session->id}/activity", ['visible' => true])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('lesson_activity_intervals', [
            'lesson_session_id' => $session->id,
            'student_id'        => $student->id,
            'kind'              => 'present',
            'ended_at'          => null,
        ]);
    }

    public function test_activity_rejects_non_participant(): void
    {
        $teacher = $this->teacher();
        [$session] = $this->makeLiveSessionWithTask($teacher, $this->student());

        $this->actingAs($this->student())
            ->postJson(self::STUDENT_BASE . "/lessons/{$session->id}/activity", ['visible' => true])
            ->assertForbidden();
    }

    public function test_activity_requires_visible_boolean(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        [$session] = $this->makeLiveSessionWithTask($teacher, $student);

        $this->actingAs($student)
            ->postJson(self::STUDENT_BASE . "/lessons/{$session->id}/activity", [])
            ->assertStatus(422);
    }

    public function test_event_records_paste_answer_with_task(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        [$session, $task] = $this->makeLiveSessionWithTask($teacher, $student);

        $this->actingAs($student)
            ->postJson(self::STUDENT_BASE . "/lessons/{$session->id}/event", [
                'kind' => 'paste_answer', 'task_id' => $task->id, 'meta' => ['length' => 7],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('lesson_behavior_events', [
            'lesson_session_id'      => $session->id,
            'student_id'             => $student->id,
            'lesson_session_task_id' => $task->id,
            'kind'                   => 'paste_answer',
        ]);
    }

    public function test_event_with_foreign_task_id_stores_without_task_link(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        [$session] = $this->makeLiveSessionWithTask($teacher, $student);
        [, $foreignTask] = $this->makeLiveSessionWithTask($this->teacher(), $this->student());

        $this->actingAs($student)
            ->postJson(self::STUDENT_BASE . "/lessons/{$session->id}/event", [
                'kind' => 'copy_task', 'task_id' => $foreignTask->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('lesson_behavior_events', [
            'lesson_session_id'      => $session->id,
            'student_id'             => $student->id,
            'lesson_session_task_id' => null,
            'kind'                   => 'copy_task',
        ]);
    }

    public function test_event_rejects_unknown_kind(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        [$session] = $this->makeLiveSessionWithTask($teacher, $student);

        $this->actingAs($student)
            ->postJson(self::STUDENT_BASE . "/lessons/{$session->id}/event", ['kind' => 'clipboard_dump'])
            ->assertStatus(422);
    }

    public function test_event_rejects_non_participant(): void
    {
        $teacher = $this->teacher();
        [$session] = $this->makeLiveSessionWithTask($teacher, $this->student());

        $this->actingAs($this->student())
            ->postJson(self::STUDENT_BASE . "/lessons/{$session->id}/event", ['kind' => 'resume'])
            ->assertForbidden();
    }
}
