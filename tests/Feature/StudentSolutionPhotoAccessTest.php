<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkSolutionPhoto;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Ученик смотрит фото собственного решения на разборе. Единственное место во
 * всей второй стадии домашки, где промах открывает чужую тетрадь: домашка
 * выдаётся на класс (один Homework — много HomeworkAssignment), поэтому
 * проверка идёт по assignment.student_id, а не по homework_id.
 */
class StudentSolutionPhotoAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Homework $homework;
    private HomeworkTopicTask $task;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->homework = Homework::create([
            'teacher_id' => $this->teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Тема 23',
            'assigned_at' => now(),
        ]);
        $this->task = HomeworkTopicTask::create([
            'homework_id' => $this->homework->id,
            'topic_number' => 23,
            'task_order' => 1,
            'task_payload' => ['text' => 'Задача'],
            'correct_answer' => '5',
        ]);
    }

    public function test_student_sees_own_photo(): void
    {
        [$student, $photo] = $this->photoFor('Свой');

        $this->actingAs($student)
            ->get($this->url($photo))
            ->assertOk();
    }

    public function test_student_cannot_see_photo_of_another_student(): void
    {
        [, $foreignPhoto] = $this->photoFor('Чужой');
        [$me] = $this->photoFor('Я');

        $this->actingAs($me)
            ->get($this->url($foreignPhoto))
            ->assertForbidden();
    }

    /**
     * Домашка на класс: у одноклассников общий homework_id и разные назначения.
     * Проверка «то же ДЗ» здесь открыла бы тетради всего класса.
     */
    public function test_classmate_with_the_same_homework_is_still_rejected(): void
    {
        [, $classmatePhoto] = $this->photoFor('Одноклассник');
        [$me] = $this->photoFor('Я');

        $this->assertSame(
            1,
            Homework::count(),
            'Обе домашки должны быть одним Homework — иначе тест проверяет не то'
        );

        $this->actingAs($me)
            ->get($this->url($classmatePhoto))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        [, $photo] = $this->photoFor('Свой');

        $this->get($this->url($photo))->assertRedirect();
    }

    private function url(HomeworkSolutionPhoto $photo): string
    {
        return 'https://student.' . config('app.base_domain') . '/homework/solution-photo/' . $photo->id;
    }

    /** @return array{0:User,1:HomeworkSolutionPhoto} */
    private function photoFor(string $name): array
    {
        static $chatId = 245710727;

        $student = User::factory()->create([
            'role' => 'student',
            'name' => $name,
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => $chatId++,
        ]);
        TeacherStudent::create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
        ]);

        $assignment = HomeworkAssignment::create([
            'homework_id' => $this->homework->id,
            'student_id' => $student->id,
            'status' => 'started',
            'tasks_total' => 1,
        ]);
        $submission = HomeworkTopicTaskSubmission::create([
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $this->task->id,
            'attempts_count' => 1,
            'first_answer' => '5',
            'is_correct' => true,
            'accepted_at' => now(),
        ]);

        $path = 'hw/' . $student->id . '/page.jpg';
        Storage::disk('public')->put($path, 'jpeg-bytes');

        $photo = HomeworkSolutionPhoto::create([
            'submission_id' => $submission->id,
            'attempt_no' => 1,
            'position' => 1,
            'path' => $path,
        ]);

        return [$student, $photo];
    }
}
