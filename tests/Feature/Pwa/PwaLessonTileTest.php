<?php

namespace Tests\Feature\Pwa;

use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Плитка «УРОК» на дашборде видна только ученикам, прикреплённым к учителю.
 */
class PwaLessonTileTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://student.palomatika.ru';

    private function student(): User
    {
        return User::create([
            'name' => 'S', 'email' => 's+' . uniqid() . '@t.t', 'password' => 'x',
            'role' => 'student', 'grade_num' => 9, 'onboarding_completed_at' => now(),
            'telegram_chat_id' => random_int(100000000, 999999999),
        ]);
    }

    public function test_linked_student_sees_lesson_tile(): void
    {
        $student = $this->student();
        $teacher = User::create([
            'name' => 'T', 'email' => 't+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher',
        ]);
        TeacherStudent::create(['teacher_id' => $teacher->id, 'student_id' => $student->id]);

        $this->actingAs($student)
            ->get(self::BASE . '/')
            ->assertOk()
            ->assertSee('lessonTile(', false);
    }

    public function test_unlinked_student_does_not_see_lesson_tile(): void
    {
        $this->actingAs($this->student())
            ->get(self::BASE . '/')
            ->assertOk()
            ->assertDontSee('lessonTile(', false);
    }

    public function test_admin_sees_lesson_tile_in_student_view(): void
    {
        // Супер-админ смотрит интерфейс ученика — плитка нужна для превью урока.
        $admin = User::create([
            'name' => 'A', 'email' => 'a+' . uniqid() . '@t.t', 'password' => 'x',
            'role' => 'admin', 'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(self::BASE . '/')
            ->assertOk()
            ->assertSee('lessonTile(', false);
    }

    public function test_admin_can_join_lesson_by_code(): void
    {
        $teacher = User::create([
            'name' => 'T', 'email' => 't+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher',
        ]);
        $admin = User::create([
            'name' => 'A', 'email' => 'a+' . uniqid() . '@t.t', 'password' => 'x',
            'role' => 'admin', 'onboarding_completed_at' => now(),
        ]);
        $svc = app(\App\Services\LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $svc->start($session);

        $this->actingAs($admin)
            ->postJson(self::BASE . '/lessons/join', ['code' => $session->fresh()->join_code])
            ->assertOk()
            ->assertJson(['lesson_id' => $session->id]);

        $this->actingAs($admin)
            ->get(self::BASE . "/lessons/{$session->id}")
            ->assertOk();
    }
}
