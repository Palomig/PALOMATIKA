<?php

namespace Tests\Feature\Pwa;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeworkPopupTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://student.palomatika.ru';

    private function student(): User
    {
        return User::factory()->create([
            'role' => 'student', 'grade_num' => 9, 'onboarding_completed_at' => now(),
        ]);
    }

    private function giveIncompleteHomework(User $student): void
    {
        $hw = Homework::create([
            'teacher_id' => User::factory()->create(['role' => 'teacher'])->id,
            'homework_type' => 'topic_photo_practice',
            'title' => 'Домашка №1',
            'tasks_count' => 3,
            'topic_number' => 6,
            'assigned_at' => now(),
        ]);
        HomeworkAssignment::create([
            'homework_id' => $hw->id, 'student_id' => $student->id,
            'status' => 'assigned', 'tasks_total' => 3, 'tasks_completed' => 1,
        ]);
    }

    public function test_popup_shows_once_then_marks_shown(): void
    {
        $student = $this->student();
        $this->giveIncompleteHomework($student);
        $this->assertNull($student->homework_popup_shown_on);

        $resp = $this->actingAs($student)->get(self::BASE . '/profile')->assertOk();
        $resp->assertSee('hwpop-overlay', false);
        $resp->assertSee('Домашка №1');

        $this->assertTrue($student->fresh()->homework_popup_shown_on->isToday());

        // Второй заход в тот же день — без поп-апа.
        $this->actingAs($student)->get(self::BASE . '/profile')->assertOk()
            ->assertDontSee('hwpop-overlay', false);
    }

    public function test_no_popup_without_incomplete_homework(): void
    {
        $student = $this->student();
        $this->actingAs($student)->get(self::BASE . '/profile')->assertOk()
            ->assertDontSee('hwpop-overlay', false);
        $this->assertNull($student->fresh()->homework_popup_shown_on);
    }

    public function test_completed_homework_does_not_trigger_popup(): void
    {
        $student = $this->student();
        $hw = Homework::create([
            'teacher_id' => User::factory()->create(['role' => 'teacher'])->id,
            'homework_type' => 'topic_photo_practice', 'title' => 'Готово',
            'tasks_count' => 2, 'topic_number' => 6, 'assigned_at' => now(),
        ]);
        HomeworkAssignment::create([
            'homework_id' => $hw->id, 'student_id' => $student->id,
            'status' => 'completed', 'tasks_total' => 2, 'tasks_completed' => 2,
        ]);

        $this->actingAs($student)->get(self::BASE . '/profile')->assertOk()
            ->assertDontSee('hwpop-overlay', false);
    }
}
