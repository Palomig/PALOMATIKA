<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkReviewItem;
use App\Models\HomeworkSolutionPhoto;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\LessonSession;
use App\Models\LessonSessionParticipant;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\HomeworkReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Разбор на экране ученика: видит только свои карточки и только те, что
 * учитель поставил в повестку урока.
 */
class StudentLessonReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response([], 200)]);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
    }

    public function test_state_contains_own_review_card(): void
    {
        [$student, $item] = $this->flagged();
        $session = $this->lessonWith([$student]);
        app(HomeworkReviewService::class)->planInto($session, [$item->id]);

        $this->actingAs($student)
            ->getJson($this->stateUrl($session))
            ->assertOk()
            ->assertJsonCount(1, 'review')
            ->assertJsonPath('review.0.id', $item->id)
            ->assertJsonPath('review.0.task_order', 1);
    }

    public function test_student_does_not_see_review_of_another_participant(): void
    {
        [$me, $myItem] = $this->flagged();
        [$other, $otherItem] = $this->flagged();
        $session = $this->lessonWith([$me, $other]);
        app(HomeworkReviewService::class)->planInto($session, [$myItem->id, $otherItem->id]);

        $response = $this->actingAs($me)->getJson($this->stateUrl($session))->assertOk();

        $this->assertCount(1, $response->json('review'));
        $this->assertSame($myItem->id, $response->json('review.0.id'));
    }

    public function test_pending_items_are_not_exposed_to_the_student(): void
    {
        [$student] = $this->flagged();
        $session = $this->lessonWith([$student]);

        // Пункт отмечен на проверке, но в урок не добавлен.
        $this->actingAs($student)
            ->getJson($this->stateUrl($session))
            ->assertOk()
            ->assertJsonCount(0, 'review');
    }

    public function test_teacher_note_is_not_leaked_to_the_student(): void
    {
        [$student, $item] = $this->flagged();
        $session = $this->lessonWith([$student]);
        app(HomeworkReviewService::class)->planInto($session, [$item->id]);

        $response = $this->actingAs($student)->getJson($this->stateUrl($session))->assertOk();

        $this->assertArrayNotHasKey('teacher_note', $response->json('review.0'));
        $response->assertDontSee('путает высоту и медиану');
    }

    private function stateUrl(LessonSession $session): string
    {
        return 'https://student.' . config('app.base_domain') . '/lessons/' . $session->id . '/state';
    }

    /** @param array<int, User> $students */
    private function lessonWith(array $students): LessonSession
    {
        $session = LessonSession::create([
            'teacher_id' => $this->teacher->id,
            'status' => LessonSession::STATUS_LIVE,
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
        $submission = HomeworkTopicTaskSubmission::create([
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $task->id,
            'attempts_count' => 1,
            'first_answer' => '24',
            'is_correct' => false,
            'accepted_at' => now(),
        ]);
        HomeworkSolutionPhoto::create([
            'submission_id' => $submission->id,
            'attempt_no' => 1,
            'position' => 1,
            'path' => 'hw/' . $student->id . '/1.jpg',
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
