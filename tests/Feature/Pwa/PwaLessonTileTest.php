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
}
