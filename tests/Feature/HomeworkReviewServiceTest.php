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
use App\Services\LessonSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Сборка карточек разбора и их жизненный цикл: pending → planned → done.
 */
class HomeworkReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    private HomeworkReviewService $service;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response([], 200)]);
        $this->service = app(HomeworkReviewService::class);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
    }

    public function test_card_carries_task_answers_photos_and_note(): void
    {
        [$student, $item] = $this->flagged('не видит подобия');

        $cards = $this->service->pendingFor([$student->id], $this->teacher->id);

        $this->assertCount(1, $cards);
        $card = $cards[0];
        $this->assertSame($item->id, $card['id']);
        $this->assertSame($student->name, $card['student_name']);
        $this->assertSame(1, $card['task_order']);
        $this->assertStringContainsString('треугольнике', $card['text']);
        $this->assertSame('28', $card['correct']);
        $this->assertSame('24', $card['first_answer']);
        $this->assertSame('не видит подобия', $card['teacher_note']);
        $this->assertCount(2, $card['photos']);
        $this->assertSame('первая попытка · стр. 1', $card['photos'][0]['label']);
        $this->assertStringContainsString('teacher.', $card['photos'][0]['url']);
    }

    public function test_plan_into_moves_pending_to_planned(): void
    {
        [$student, $item] = $this->flagged();
        $session = $this->lessonWith([$student]);

        $moved = $this->service->planInto($session, [$item->id]);

        $this->assertSame(1, $moved);
        $fresh = $item->fresh();
        $this->assertSame(HomeworkReviewItem::STATUS_PLANNED, $fresh->status);
        $this->assertSame($session->id, (int) $fresh->lesson_session_id);
        $this->assertSame([], $this->service->pendingFor([$student->id], $this->teacher->id));
    }

    public function test_plan_into_ignores_items_of_non_participants(): void
    {
        [$outsider, $item] = $this->flagged();
        [$participant] = $this->flagged();
        $session = $this->lessonWith([$participant]);

        $moved = $this->service->planInto($session, [$item->id]);

        $this->assertSame(0, $moved);
        $this->assertSame(HomeworkReviewItem::STATUS_PENDING, $item->fresh()->status);
        $this->assertNotNull($outsider);
    }

    public function test_unplan_returns_item_to_the_queue(): void
    {
        [$student, $item] = $this->flagged();
        $session = $this->lessonWith([$student]);
        $this->service->planInto($session, [$item->id]);

        $this->service->unplan($session, $item->id);

        $fresh = $item->fresh();
        $this->assertSame(HomeworkReviewItem::STATUS_PENDING, $fresh->status);
        $this->assertNull($fresh->lesson_session_id);
    }

    public function test_student_cards_hide_the_teacher_note_and_use_student_photo_route(): void
    {
        [$student, $item] = $this->flagged('только для учителя');
        $session = $this->lessonWith([$student]);
        $this->service->planInto($session, [$item->id]);

        $cards = $this->service->cardsForStudent($session, $student->id);

        $this->assertCount(1, $cards);
        $this->assertArrayNotHasKey('teacher_note', $cards[0]);
        $this->assertStringContainsString('student.', $cards[0]['photos'][0]['url']);
    }

    public function test_student_does_not_see_cards_of_another_participant(): void
    {
        [$me, $myItem] = $this->flagged();
        [$other, $otherItem] = $this->flagged();
        $session = $this->lessonWith([$me, $other]);
        $this->service->planInto($session, [$myItem->id, $otherItem->id]);

        $cards = $this->service->cardsForStudent($session, $me->id);

        $this->assertCount(1, $cards);
        $this->assertSame($myItem->id, $cards[0]['id']);
    }

    public function test_pending_items_never_reach_the_student(): void
    {
        [$student, $item] = $this->flagged();
        $session = $this->lessonWith([$student]);

        // Пункт отмечен, но в урок не добавлен — ученику его быть не должно.
        $this->assertSame(HomeworkReviewItem::STATUS_PENDING, $item->fresh()->status);
        $this->assertSame([], $this->service->cardsForStudent($session, $student->id));
    }

    public function test_ending_a_lesson_resolves_only_its_own_items(): void
    {
        [$student, $item] = $this->flagged();
        [$otherStudent, $otherItem] = $this->flagged();

        $session = $this->lessonWith([$student]);
        $otherSession = $this->lessonWith([$otherStudent]);
        $this->service->planInto($session, [$item->id]);
        $this->service->planInto($otherSession, [$otherItem->id]);

        app(LessonSessionService::class)->end($session);

        $resolved = $item->fresh();
        $this->assertSame(HomeworkReviewItem::STATUS_DONE, $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertSame($session->id, (int) $resolved->lesson_session_id);

        $this->assertSame(HomeworkReviewItem::STATUS_PLANNED, $otherItem->fresh()->status);
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
    private function flagged(?string $note = null): array
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
        foreach ([1, 2] as $position) {
            HomeworkSolutionPhoto::create([
                'submission_id' => $submission->id,
                'attempt_no' => 1,
                'position' => $position,
                'path' => 'hw/' . $student->id . '/' . $position . '.jpg',
            ]);
        }

        $item = HomeworkReviewItem::create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacher->id,
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $task->id,
            'note' => $note,
        ]);

        return [$student, $item];
    }
}
