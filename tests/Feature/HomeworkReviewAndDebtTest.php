<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;
use App\Models\StudentNote;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Проверка домашки учителем: заметки о том, что ученик не понял (в ту же копилку,
 * что и заметки с урока), отметка «проверено» и долги — несданная работа не
 * теряется, когда сверху выдают новую.
 */
class HomeworkReviewAndDebtTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        // Выдача ДЗ дёргает телеграм-уведомление — в тестах наружу не ходим.
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => 245710727,
        ]);
        TeacherStudent::create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'source' => 'manual',
        ]);
    }

    private function teacherBase(): string
    {
        return 'https://teacher.' . config('app.base_domain');
    }

    private function makeAssignment(string $status = 'started', ?User $student = null): HomeworkAssignment
    {
        $homework = Homework::create([
            'teacher_id' => $this->teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Тема 23',
            'assigned_at' => now(),
        ]);
        HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 23,
            'task_order' => 1,
            'task_payload' => ['id' => 1, 'text' => 'Найдите высоту.'],
            'correct_answer' => '12',
        ]);

        return HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => ($student ?? $this->student)->id,
            'status' => $status,
            'tasks_total' => 1,
        ]);
    }

    public function test_note_lands_in_the_same_pile_as_lesson_notes(): void
    {
        $assignment = $this->makeAssignment();

        $this->actingAs($this->teacher)->post(
            $this->teacherBase() . "/homework/assignment/{$assignment->id}/note",
            ['body' => 'Не видит подобие треугольников', 'kind' => 'weakness', 'task_ref' => 'Задача 1']
        )->assertRedirect();

        $note = StudentNote::first();
        $this->assertNotNull($note);
        $this->assertSame('Не видит подобие треугольников', $note->body);
        $this->assertSame('weakness', $note->kind);
        $this->assertSame('homework', $note->source);
        $this->assertSame('Задача 1', $note->task_ref);
        $this->assertSame($assignment->id, $note->homework_assignment_id);
        $this->assertSame($this->student->id, $note->student_id);
    }

    public function test_note_shows_up_on_review_screen(): void
    {
        $assignment = $this->makeAssignment();

        $this->actingAs($this->teacher)->post(
            $this->teacherBase() . "/homework/assignment/{$assignment->id}/note",
            ['body' => 'Путает высоту и медиану']
        );

        $this->actingAs($this->teacher)
            ->get($this->teacherBase() . "/homework/assignment/{$assignment->id}")
            ->assertOk()
            ->assertSee('Путает высоту и медиану');
    }

    public function test_stranger_cannot_write_note(): void
    {
        $assignment = $this->makeAssignment();
        $stranger = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($stranger)->post(
            $this->teacherBase() . "/homework/assignment/{$assignment->id}/note",
            ['body' => 'чужая заметка']
        )->assertForbidden();

        $this->assertSame(0, StudentNote::count());
    }

    public function test_reviewed_toggles(): void
    {
        $assignment = $this->makeAssignment();
        $url = $this->teacherBase() . "/homework/assignment/{$assignment->id}/reviewed";

        $this->actingAs($this->teacher)->post($url)->assertRedirect();
        $assignment->refresh();
        $this->assertNotNull($assignment->reviewed_at);
        $this->assertSame($this->teacher->id, (int) $assignment->reviewed_by);

        $this->actingAs($this->teacher)->post($url)->assertRedirect();
        $this->assertNull($assignment->refresh()->reviewed_at);
    }

    public function test_new_homework_turns_unfinished_into_debt(): void
    {
        $old = $this->makeAssignment('started');
        $done = $this->makeAssignment('completed');

        // Учитель выдаёт новую домашку тому же ученику штатным путём.
        $this->actingAs($this->teacher)->post($this->teacherBase() . '/homework/assign', [
            'student_ids' => [$this->student->id],
            'type' => 'topic_photo_practice',
            'topic_number' => 23,
            'task_indices' => [0],
        ])->assertRedirect();

        $this->assertNotNull($old->refresh()->debt_since, 'Несданная работа должна стать долгом');
        $this->assertTrue($old->isDebt());
        $this->assertNull($done->refresh()->debt_since, 'Выполненная работа долгом не становится');

        // Свежая работа долгом не помечается.
        $fresh = HomeworkAssignment::where('student_id', $this->student->id)->orderByDesc('id')->first();
        $this->assertNull($fresh->debt_since);
    }

    public function test_other_students_are_not_touched(): void
    {
        $other = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => 111,
        ]);
        TeacherStudent::create(['teacher_id' => $this->teacher->id, 'student_id' => $other->id, 'source' => 'manual']);
        $otherUnfinished = $this->makeAssignment('started', $other);

        $this->actingAs($this->teacher)->post($this->teacherBase() . '/homework/assign', [
            'student_ids' => [$this->student->id],
            'type' => 'topic_photo_practice',
            'topic_number' => 23,
            'task_indices' => [0],
        ]);

        $this->assertNull($otherUnfinished->refresh()->debt_since);
    }

    public function test_debt_clears_when_work_is_finished(): void
    {
        $assignment = $this->makeAssignment('started');
        $assignment->update(['debt_since' => now()->subDay()]);
        $task = HomeworkTopicTask::where('homework_id', $assignment->homework_id)->first();

        $this->actingAs($this->student)->post(
            'https://student.' . config('app.base_domain') . "/homework/{$assignment->id}/tasks/{$task->id}",
            ['answer' => '12', 'solution_photos' => [\Illuminate\Http\UploadedFile::fake()->create('p.jpg', 100, 'image/jpeg')]]
        )->assertRedirect();

        $assignment->refresh();
        $this->assertSame('completed', $assignment->status);
        $this->assertNull($assignment->debt_since, 'Долг закрывается, как только работу довели до конца');
    }

    public function test_teacher_can_lift_debt_manually(): void
    {
        $assignment = $this->makeAssignment('started');
        $assignment->update(['debt_since' => now()]);

        $this->actingAs($this->teacher)
            ->post($this->teacherBase() . "/homework/assignment/{$assignment->id}/debt")
            ->assertRedirect();

        $this->assertNull($assignment->refresh()->debt_since);
    }

    public function test_student_sees_debt_first_with_badge(): void
    {
        $this->makeAssignment('assigned');
        $debt = $this->makeAssignment('started');
        $debt->update(['debt_since' => now()]);

        $response = $this->actingAs($this->student)
            ->get('https://student.' . config('app.base_domain') . '/homework')
            ->assertOk();

        $response->assertSee('долг');
        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, 'hw-item') === false ? PHP_INT_MAX : strrpos($html, 'hw-item'),
            strpos($html, 'hw-debt'),
            'Долг должен быть выше обычной домашки'
        );
    }
}
