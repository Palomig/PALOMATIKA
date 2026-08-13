<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkReviewItem;
use App\Models\HomeworkTopicTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Пункт разбора — задача домашки, отмеченная учителем как «разобрать на уроке».
 */
class HomeworkReviewItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_item_stores_link_to_task_and_defaults_to_pending(): void
    {
        [$teacher, $student, $assignment, $task] = $this->fixture();

        $item = HomeworkReviewItem::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $task->id,
            'note' => 'не видит подобия',
        ]);

        $fresh = $item->fresh();
        $this->assertSame(HomeworkReviewItem::STATUS_PENDING, $fresh->status);
        $this->assertSame($task->id, $fresh->topicTask->id);
        $this->assertSame($student->id, $fresh->student->id);
        $this->assertSame('не видит подобия', $fresh->note);
        $this->assertNotNull($fresh->created_at);
        $this->assertNull($fresh->resolved_at);
        $this->assertNull($fresh->lesson_session_id);
    }

    public function test_same_task_can_be_flagged_again_after_it_was_resolved(): void
    {
        [$teacher, $student, $assignment, $task] = $this->fixture();

        HomeworkReviewItem::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $task->id,
            'status' => HomeworkReviewItem::STATUS_DONE,
            'resolved_at' => now(),
        ]);

        // Повторная отметка той же задачи не должна упереться в уникальный индекс:
        // разобрали на прошлом уроке — не значит, что нельзя отметить снова.
        $second = HomeworkReviewItem::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $task->id,
        ]);

        $this->assertSame(HomeworkReviewItem::STATUS_PENDING, $second->fresh()->status);
        $this->assertSame(2, HomeworkReviewItem::where('homework_topic_task_id', $task->id)->count());
    }

    public function test_active_scope_returns_only_pending_and_planned(): void
    {
        [$teacher, $student, $assignment, $task] = $this->fixture();

        foreach ([
            HomeworkReviewItem::STATUS_PENDING,
            HomeworkReviewItem::STATUS_PLANNED,
            HomeworkReviewItem::STATUS_DONE,
        ] as $status) {
            HomeworkReviewItem::create([
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'homework_assignment_id' => $assignment->id,
                'homework_topic_task_id' => $task->id,
                'status' => $status,
            ]);
        }

        $this->assertSame(2, HomeworkReviewItem::active()->count());
    }

    /** @return array{0:User,1:User,2:HomeworkAssignment,3:HomeworkTopicTask} */
    private function fixture(): array
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $homework = Homework::create([
            'teacher_id' => $teacher->id,
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
            'task_payload' => ['text' => 'Найдите $x$'],
            'correct_answer' => '5',
        ]);
        $assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'status' => 'assigned',
            'tasks_total' => 1,
        ]);

        return [$teacher, $student, $assignment, $task];
    }
}
