<?php

namespace Tests\Feature;

use App\Models\LessonSchedule;
use App\Models\LessonSession;
use App\Models\LessonSessionTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherLessonControllerTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://teacher.palomatika.ru';

    private function teacher(): User
    {
        return User::create([
            'name' => 'T', 'email' => 't+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher',
        ]);
    }

    private function student(): User
    {
        return User::create([
            'name' => 'S', 'email' => 's+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'student',
        ]);
    }

    public function test_create_adhoc_session_returns_invite_token(): void
    {
        $resp = $this->actingAs($this->teacher())
            ->postJson(self::BASE . '/lessons');

        $resp->assertCreated()
            ->assertJsonStructure(['session' => ['id', 'status', 'invite_token']]);

        $this->assertNotNull($resp->json('session.invite_token'));
        $this->assertSame('draft', $resp->json('session.status'));
    }

    public function test_create_session_from_schedule_includes_student(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $slot = LessonSchedule::create([
            'teacher_id' => $teacher->id, 'student_id' => $student->id,
            'day_of_week' => (int) now()->format('N'),
            'start_time' => '10:00', 'end_time' => '11:00', 'is_active' => true,
        ]);

        $resp = $this->actingAs($teacher)
            ->postJson(self::BASE . '/lessons', ['schedule_id' => $slot->id])
            ->assertCreated();

        $sessionId = $resp->json('session.id');
        $this->assertDatabaseHas('lesson_session_participants', [
            'lesson_session_id' => $sessionId, 'student_id' => $student->id,
        ]);
    }

    public function test_other_teacher_cannot_use_others_slot(): void
    {
        $owner = $this->teacher();
        $intruder = $this->teacher();
        $slot = LessonSchedule::create([
            'teacher_id' => $owner->id, 'student_id' => $this->student()->id,
            'day_of_week' => 1, 'start_time' => '10:00', 'is_active' => true,
        ]);

        $this->actingAs($intruder)
            ->postJson(self::BASE . '/lessons', ['schedule_id' => $slot->id])
            ->assertForbidden();
    }

    public function test_full_flow_create_add_start_state_end(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $slot = LessonSchedule::create([
            'teacher_id' => $teacher->id, 'student_id' => $student->id,
            'day_of_week' => (int) now()->format('N'),
            'start_time' => '10:00', 'is_active' => true,
        ]);

        $sessionId = $this->actingAs($teacher)
            ->postJson(self::BASE . '/lessons', ['schedule_id' => $slot->id])
            ->assertCreated()->json('session.id');

        $taskResp = $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$sessionId}/tasks", [
                'bank' => 'alg-skill',
                'refs' => ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1],
            ])->assertCreated();
        $this->assertSame('-2', $taskResp->json('task.correct_answer'));
        $this->assertSame('-8 + 6', $taskResp->json('task.task_payload.expression'));

        $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$sessionId}/start")
            ->assertOk()
            ->assertJsonPath('session.status', 'live');

        $state = $this->actingAs($teacher)
            ->getJson(self::BASE . "/lessons/{$sessionId}/state")
            ->assertOk();
        $this->assertSame('live', $state->json('session.status'));
        $this->assertCount(1, $state->json('tasks'));
        $this->assertCount(1, $state->json('participants'));
        $this->assertSame([], $state->json('grid'));

        $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$sessionId}/end")
            ->assertOk()
            ->assertJsonPath('session.status', 'ended');
    }

    public function test_state_returns_404_for_unknown_session(): void
    {
        $this->actingAs($this->teacher())
            ->getJson(self::BASE . '/lessons/9999/state')
            ->assertNotFound();
    }

    public function test_other_teacher_cannot_read_state(): void
    {
        $owner = $this->teacher();
        $intruder = $this->teacher();
        $session = LessonSession::create(['teacher_id' => $owner->id, 'status' => 'draft']);

        $this->actingAs($intruder)
            ->getJson(self::BASE . "/lessons/{$session->id}/state")
            ->assertForbidden();
    }

    public function test_start_fails_with_no_tasks(): void
    {
        $teacher = $this->teacher();
        $session = LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'draft']);

        $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$session->id}/start")
            ->assertStatus(422);
    }

    public function test_prep_view_renders_for_owner(): void
    {
        $teacher = $this->teacher();
        $session = LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'draft', 'invite_token' => str_repeat('a', 16)]);

        $resp = $this->actingAs($teacher)
            ->get(self::BASE . "/lessons/{$session->id}")
            ->assertOk();

        $html = $resp->getContent();
        $this->assertStringContainsString('Урок #' . $session->id, $html);
        $this->assertStringContainsString('lessonPrep', $html, 'Alpine wrapper present');
        $this->assertStringContainsString('signed-add', $html, 'alg-skills bundle inlined for picker');
    }

    public function test_prep_view_forbidden_for_other_teacher(): void
    {
        $owner = $this->teacher();
        $intruder = $this->teacher();
        $session = LessonSession::create(['teacher_id' => $owner->id, 'status' => 'draft']);

        $this->actingAs($intruder)
            ->get(self::BASE . "/lessons/{$session->id}")
            ->assertForbidden();
    }

    public function test_remove_task_from_another_session_404(): void
    {
        $teacher = $this->teacher();
        $sessionA = LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'draft']);
        $sessionB = LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'draft']);
        $taskInB = LessonSessionTask::create([
            'lesson_session_id' => $sessionB->id, 'position' => 1, 'bank' => 'alg-skill',
            'task_ref' => '{}', 'task_payload' => [], 'correct_answer' => '0',
        ]);

        $this->actingAs($teacher)
            ->deleteJson(self::BASE . "/lessons/{$sessionA->id}/tasks/{$taskInB->id}")
            ->assertNotFound();
    }
}
