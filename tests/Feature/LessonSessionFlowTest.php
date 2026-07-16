<?php

namespace Tests\Feature;

use App\Models\LessonSchedule;
use App\Models\LessonSession;
use App\Models\User;
use App\Services\LessonSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * End-to-end сценарий: teacher собирает урок → 2 ученика входят по коду
 * и отвечают → teacher видит грид → завершение → submit после end → 422.
 *
 * Также проверяет команду lesson-sessions:auto-close.
 */
class LessonSessionFlowTest extends TestCase
{
    use RefreshDatabase;

    private const TEACHER_BASE = 'http://teacher.palomatika.ru';
    private const STUDENT_BASE = 'http://student.palomatika.ru';

    public function test_complete_lesson_flow_through_all_api_layers(): void
    {
        $teacher = User::create(['name' => 'Mrs. Ivanova', 'email' => 't' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher']);
        $alice   = User::create(['name' => 'Alice',       'email' => 'a' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'student', 'onboarding_completed_at' => now()]);
        $bob     = User::create(['name' => 'Bob',         'email' => 'b' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'student', 'onboarding_completed_at' => now()]);

        // 1. Teacher создаёт сессию из расписания (Alice в слоте)
        $slot = LessonSchedule::create([
            'teacher_id'  => $teacher->id,
            'student_id'  => $alice->id,
            'day_of_week' => (int) now()->format('N'),
            'start_time'  => '10:00',
            'end_time'    => '11:00',
            'is_active'   => true,
        ]);

        $sessionId = $this->actingAs($teacher)
            ->postJson(self::TEACHER_BASE . '/lessons', ['schedule_id' => $slot->id])
            ->assertCreated()
            ->json('session.id');

        // 2. Teacher добавляет 3 задачи: 2 expression (alg-skill) + 1 choice (oge)
        $t1 = $this->actingAs($teacher)->postJson(self::TEACHER_BASE . "/lessons/{$sessionId}/tasks", [
            'bank' => 'alg-skill',
            'refs' => ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1],
        ])->assertCreated()->json('task');

        $t2 = $this->actingAs($teacher)->postJson(self::TEACHER_BASE . "/lessons/{$sessionId}/tasks", [
            'bank' => 'alg-skill',
            'refs' => ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 2],
        ])->assertCreated()->json('task');

        $t3 = $this->actingAs($teacher)->postJson(self::TEACHER_BASE . "/lessons/{$sessionId}/tasks", [
            'bank' => 'oge',
            'refs' => ['topic_id' => '07', 'zadanie_number' => 1, 'task_id' => 1],
        ])->assertCreated()->json('task');

        $this->assertSame('expression', $t1['task_payload']['type']);
        $this->assertSame('choice',     $t3['task_payload']['type']);

        // 3. Teacher стартует урок
        $session = $this->actingAs($teacher)
            ->postJson(self::TEACHER_BASE . "/lessons/{$sessionId}/start")
            ->assertOk()
            ->assertJsonPath('session.status', 'live')
            ->json('session');

        // 4. Alice и Bob входят по 4-значному коду (автоучастников из расписания нет)
        $joinCode = $session['join_code'];
        $this->assertMatchesRegularExpression('/^\d{4}$/', $joinCode);

        $this->actingAs($alice)
            ->postJson(self::STUDENT_BASE . '/lessons/join', ['code' => $joinCode])
            ->assertOk()
            ->assertJson(['lesson_id' => $sessionId]);

        $this->actingAs($bob)
            ->postJson(self::STUDENT_BASE . '/lessons/join', ['code' => $joinCode])
            ->assertOk()
            ->assertJson(['lesson_id' => $sessionId]);

        // 5. Alice: active endpoint видит её участником
        $this->actingAs($alice)
            ->getJson(self::STUDENT_BASE . '/lessons/active')
            ->assertOk()
            ->assertJsonPath('session.id', $sessionId);

        // 6. Alice загружает state — задачи без correct_answer
        $aliceState = $this->actingAs($alice)
            ->getJson(self::STUDENT_BASE . "/lessons/{$sessionId}/state")
            ->assertOk()
            ->json();
        $this->assertCount(3, $aliceState['tasks']);
        $this->assertArrayNotHasKey('answer', $aliceState['tasks'][0]['payload']);

        // 7. Alice отвечает: t1 правильно, t2 неправильно, t3 правильно (choice 'c')
        $this->actingAs($alice)->postJson(self::STUDENT_BASE . "/lessons/{$sessionId}/answer",
            ['task_id' => $t1['id'], 'answer' => '-2'])->assertOk();
        $this->actingAs($alice)->postJson(self::STUDENT_BASE . "/lessons/{$sessionId}/answer",
            ['task_id' => $t2['id'], 'answer' => '999'])->assertOk();
        $this->actingAs($alice)->postJson(self::STUDENT_BASE . "/lessons/{$sessionId}/answer",
            ['task_id' => $t3['id'], 'answer' => 'c'])->assertOk();

        // 8. Bob отвечает только на t1 (правильно)
        $this->actingAs($bob)->postJson(self::STUDENT_BASE . "/lessons/{$sessionId}/answer",
            ['task_id' => $t1['id'], 'answer' => '-2'])->assertOk();

        // 9. Teacher смотрит state — видит грид с правильностью
        $state = $this->actingAs($teacher)
            ->getJson(self::TEACHER_BASE . "/lessons/{$sessionId}/state")
            ->assertOk()
            ->json();

        $this->assertCount(2, $state['participants']);
        $this->assertCount(3, $state['tasks']);

        $aliceGrid = $state['grid'][$alice->id];
        $this->assertTrue($aliceGrid[$t1['id']]['is_correct'], 'Alice t1 correct');
        $this->assertFalse($aliceGrid[$t2['id']]['is_correct'], 'Alice t2 wrong');
        $this->assertTrue($aliceGrid[$t3['id']]['is_correct'], 'Alice choice c correct');

        $bobGrid = $state['grid'][$bob->id];
        $this->assertTrue($bobGrid[$t1['id']]['is_correct'], 'Bob t1 correct');
        $this->assertArrayNotHasKey($t2['id'], $bobGrid, 'Bob did not answer t2');

        // 10. Teacher завершает урок
        $this->actingAs($teacher)
            ->postJson(self::TEACHER_BASE . "/lessons/{$sessionId}/end")
            ->assertOk()
            ->assertJsonPath('session.status', 'ended');

        // 11. Alice пытается отправить ответ после end → 422
        $this->actingAs($alice)
            ->postJson(self::STUDENT_BASE . "/lessons/{$sessionId}/answer",
                ['task_id' => $t1['id'], 'answer' => 'late'])
            ->assertStatus(422);

        // 12. Alice больше не видит активную сессию
        $this->actingAs($alice)
            ->getJson(self::STUDENT_BASE . '/lessons/active')
            ->assertOk()
            ->assertJson(['session' => null]);
    }

    public function test_auto_close_command_closes_stale_live_sessions(): void
    {
        $teacher = User::create(['name' => 'T', 'email' => 't' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher']);

        // Свежая live (1 час назад) — НЕ должна закрыться
        $fresh = LessonSession::create([
            'teacher_id' => $teacher->id, 'status' => LessonSession::STATUS_LIVE,
            'starts_at'  => now()->subHour(),
        ]);

        // Старая live (4 часа назад) — ДОЛЖНА закрыться
        $stale = LessonSession::create([
            'teacher_id' => $teacher->id, 'status' => LessonSession::STATUS_LIVE,
            'starts_at'  => now()->subHours(4),
        ]);

        // Draft сессия (вечно живая) — не трогаем
        $draft = LessonSession::create([
            'teacher_id' => $teacher->id, 'status' => LessonSession::STATUS_DRAFT,
            'starts_at'  => null,
        ]);

        $this->artisan('lesson-sessions:auto-close')
            ->expectsOutputToContain('Auto-closed 1/1')
            ->assertExitCode(0);

        $this->assertSame('live',  $fresh->fresh()->status);
        $this->assertSame('ended', $stale->fresh()->status);
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_auto_close_command_with_custom_hours_option(): void
    {
        $teacher = User::create(['name' => 'T', 'email' => 't' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher']);

        $s = LessonSession::create([
            'teacher_id' => $teacher->id, 'status' => LessonSession::STATUS_LIVE,
            'starts_at'  => now()->subMinutes(90),
        ]);

        $this->artisan('lesson-sessions:auto-close', ['--hours' => 1])
            ->assertExitCode(0);

        $this->assertSame('ended', $s->fresh()->status);
    }

    public function test_auto_close_no_op_when_no_stale_sessions(): void
    {
        $this->artisan('lesson-sessions:auto-close')
            ->expectsOutputToContain('No stale live sessions')
            ->assertExitCode(0);
    }
}
