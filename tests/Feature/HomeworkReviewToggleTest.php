<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkReviewItem;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\StudentNote;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Переключатель «Разобрать» на странице проверки домашки.
 */
class HomeworkReviewToggleTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response([], 200)]);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
    }

    public function test_teacher_flags_task_for_review(): void
    {
        [$assignment, $task, $student] = $this->assign();

        $this->actingAs($this->teacher)
            ->post($this->toggleUrl($assignment, $task), ['on' => 1])
            ->assertRedirect();

        $item = HomeworkReviewItem::firstOrFail();
        $this->assertSame(HomeworkReviewItem::STATUS_PENDING, $item->status);
        $this->assertSame($student->id, (int) $item->student_id);
        $this->assertSame($this->teacher->id, (int) $item->teacher_id);
        $this->assertSame($task->id, (int) $item->homework_topic_task_id);
        $this->assertNull($item->student_note_id);
    }

    public function test_toggling_off_removes_the_flag(): void
    {
        [$assignment, $task] = $this->assign();

        $this->actingAs($this->teacher)->post($this->toggleUrl($assignment, $task), ['on' => 1]);
        $this->actingAs($this->teacher)->post($this->toggleUrl($assignment, $task), ['on' => 0]);

        $this->assertSame(0, HomeworkReviewItem::active()->count());
    }

    public function test_note_can_be_mirrored_into_student_card(): void
    {
        [$assignment, $task, $student] = $this->assign();

        $this->actingAs($this->teacher)->post($this->toggleUrl($assignment, $task), [
            'on' => 1,
            'note' => 'путает высоту и медиану',
            'to_student_card' => 1,
        ]);

        $item = HomeworkReviewItem::firstOrFail();
        $this->assertSame('путает высоту и медиану', $item->note);
        $this->assertNotNull($item->student_note_id);

        $note = StudentNote::findOrFail($item->student_note_id);
        $this->assertSame('todo', $note->kind);
        $this->assertSame('Задача 1', $note->task_ref);
        $this->assertSame($student->id, (int) $note->student_id);
    }

    public function test_note_without_the_checkbox_stays_out_of_student_card(): void
    {
        [$assignment, $task] = $this->assign();

        $this->actingAs($this->teacher)->post($this->toggleUrl($assignment, $task), [
            'on' => 1,
            'note' => 'только для разбора',
        ]);

        $this->assertSame('только для разбора', HomeworkReviewItem::firstOrFail()->note);
        $this->assertSame(0, StudentNote::count());
    }

    public function test_unrelated_teacher_is_rejected(): void
    {
        [$assignment, $task] = $this->assign();
        $stranger = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($stranger)
            ->post($this->toggleUrl($assignment, $task), ['on' => 1])
            ->assertForbidden();

        $this->assertSame(0, HomeworkReviewItem::count());
    }

    public function test_task_from_another_homework_is_rejected(): void
    {
        [$assignment] = $this->assign();
        [, $foreignTask] = $this->assign();

        $this->actingAs($this->teacher)
            ->post($this->toggleUrl($assignment, $foreignTask), ['on' => 1])
            ->assertNotFound();

        $this->assertSame(0, HomeworkReviewItem::count());
    }

    public function test_review_page_shows_counter_and_active_toggle(): void
    {
        [$assignment, $task] = $this->assign();
        $this->actingAs($this->teacher)->post($this->toggleUrl($assignment, $task), ['on' => 1]);

        // Счётчик и подпись кнопки рендерятся сервером и подхватываются Alpine:
        // без серверного значения при медленной загрузке моргала бы пустая кнопка.
        $this->actingAs($this->teacher)
            ->get('https://teacher.' . config('app.base_domain') . '/homework/assignment/' . $assignment->id)
            ->assertOk()
            ->assertSee('К разбору:')
            ->assertSee('>1</span>', false)
            ->assertSee('✓ Разобрать')
            ->assertSee('toggleReview(' . $task->id . ')', false);
    }

    /** Переключатель асинхронный: JSON-ответ вместо редиректа, скролл не слетает. */
    public function test_toggle_answers_json_without_redirect(): void
    {
        [$assignment, $task] = $this->assign();

        $this->actingAs($this->teacher)
            ->postJson($this->toggleUrl($assignment, $task), ['on' => true])
            ->assertOk()
            ->assertJsonPath('item.status', HomeworkReviewItem::STATUS_PENDING);

        $this->actingAs($this->teacher)
            ->postJson($this->toggleUrl($assignment, $task), ['on' => false])
            ->assertOk()
            ->assertJsonPath('item', null);
    }

    public function test_resolved_flag_is_shown_as_a_trace(): void
    {
        [$assignment, $task, $student] = $this->assign();
        HomeworkReviewItem::create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacher->id,
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $task->id,
            'status' => HomeworkReviewItem::STATUS_DONE,
            'resolved_at' => now(),
        ]);

        $this->actingAs($this->teacher)
            ->get('https://teacher.' . config('app.base_domain') . '/homework/assignment/' . $assignment->id)
            ->assertOk()
            ->assertSee('разобрано ' . now()->format('d.m'));
    }

    private function toggleUrl(HomeworkAssignment $assignment, HomeworkTopicTask $task): string
    {
        return 'https://teacher.' . config('app.base_domain')
            . '/homework/assignment/' . $assignment->id . '/tasks/' . $task->id . '/review';
    }

    /** @return array{0:HomeworkAssignment,1:HomeworkTopicTask,2:User} */
    private function assign(): array
    {
        $student = User::factory()->create(['role' => 'student', 'grade_num' => 9]);
        TeacherStudent::create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
        ]);

        $homework = Homework::create([
            'teacher_id' => $this->teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Тема 23',
            'assigned_at' => now(),
        ]);
        $task = HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 23,
            'task_order' => 1,
            'task_payload' => ['text' => 'В треугольнике $ABC$ угол $C$ равен $90^\circ$.'],
            'correct_answer' => '28',
        ]);
        $assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'status' => 'started',
            'tasks_total' => 1,
        ]);
        HomeworkTopicTaskSubmission::create([
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $task->id,
            'attempts_count' => 1,
            'first_answer' => '24',
            'is_correct' => false,
            'accepted_at' => now(),
        ]);

        return [$assignment, $task, $student];
    }
}
