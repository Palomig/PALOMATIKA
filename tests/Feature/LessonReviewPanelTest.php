<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkReviewItem;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\LessonSession;
use App\Models\LessonSessionParticipant;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Эндпоинты разбора на экране урока: что предложить учителю и что он поставил
 * в повестку.
 */
class LessonReviewPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response([], 200)]);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
    }

    public function test_teacher_sees_pending_items_of_participants(): void
    {
        [$student, $item] = $this->flagged();
        $session = $this->lessonWith([$student]);

        $this->actingAs($this->teacher)
            ->getJson($this->url($session))
            ->assertOk()
            ->assertJsonPath('pending.0.id', $item->id)
            ->assertJsonPath('pending.0.student_id', $student->id)
            ->assertJsonCount(0, 'planned');
    }

    public function test_items_of_a_student_outside_the_lesson_are_not_offered(): void
    {
        [$outsider] = $this->flagged();
        [$participant] = $this->flagged();
        $session = $this->lessonWith([$participant]);

        $response = $this->actingAs($this->teacher)->getJson($this->url($session))->assertOk();

        $this->assertCount(1, $response->json('pending'));
        $this->assertNotSame($outsider->id, $response->json('pending.0.student_id'));
    }

    public function test_planning_moves_items_into_the_lesson(): void
    {
        [$student, $item] = $this->flagged();
        $session = $this->lessonWith([$student]);

        $this->actingAs($this->teacher)
            ->postJson($this->url($session), ['item_ids' => [$item->id]])
            ->assertOk()
            ->assertJsonPath('planned_count', 1)
            ->assertJsonCount(1, 'planned');

        $this->assertSame(HomeworkReviewItem::STATUS_PLANNED, $item->fresh()->status);
    }

    public function test_planning_the_same_item_twice_changes_nothing(): void
    {
        [$student, $item] = $this->flagged();
        $session = $this->lessonWith([$student]);

        $this->actingAs($this->teacher)->postJson($this->url($session), ['item_ids' => [$item->id]]);
        $this->actingAs($this->teacher)
            ->postJson($this->url($session), ['item_ids' => [$item->id]])
            ->assertOk()
            ->assertJsonPath('planned_count', 0)
            ->assertJsonCount(1, 'planned');
    }

    public function test_unplanning_returns_the_item_to_the_queue(): void
    {
        [$student, $item] = $this->flagged();
        $session = $this->lessonWith([$student]);
        $this->actingAs($this->teacher)->postJson($this->url($session), ['item_ids' => [$item->id]]);

        $this->actingAs($this->teacher)
            ->deleteJson($this->url($session) . '/' . $item->id)
            ->assertOk();

        $this->assertSame(HomeworkReviewItem::STATUS_PENDING, $item->fresh()->status);
    }

    public function test_another_teacher_cannot_reach_the_lesson(): void
    {
        [$student] = $this->flagged();
        $session = $this->lessonWith([$student]);
        $stranger = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($stranger)->getJson($this->url($session))->assertForbidden();
    }

    private function url(LessonSession $session): string
    {
        return 'https://teacher.' . config('app.base_domain') . '/lessons/' . $session->id . '/review-items';
    }

    /** @param array<int, User> $students */
    private function lessonWith(array $students): LessonSession
    {
        $session = LessonSession::create([
            'teacher_id' => $this->teacher->id,
            'status' => LessonSession::STATUS_DRAFT,
            'starts_at' => now(),
        ]);
        foreach ($students as $student) {
            LessonSessionParticipant::create([
                'lesson_session_id' => $session->id,
                'student_id' => $student->id,
                'source' => LessonSessionParticipant::SOURCE_CODE,
                'joined_at' => now(),
            ]);
        }

        return $session->fresh();
    }

    /** @return array{0:User,1:HomeworkReviewItem} */
    private function flagged(): array
    {
        static $chatId = 245710727;

        $student = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => $chatId++,
        ]);
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
            'task_payload' => ['text' => 'В треугольнике $ABC$ угол $C$ прямой.'],
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

        $item = HomeworkReviewItem::create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacher->id,
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $task->id,
            'note' => 'путает высоту и медиану',
        ]);

        return [$student, $item];
    }
}
