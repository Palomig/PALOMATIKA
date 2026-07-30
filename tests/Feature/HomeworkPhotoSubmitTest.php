<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkSolutionPhoto;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Сдача фото-домашки учеником: раньше фото тетради с телефона (3–8 МБ)
 * отбивалось лимитом 5 МБ, ответ терялся, а сдать ДЗ было невозможно.
 */
class HomeworkPhotoSubmitTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;
    private HomeworkAssignment $assignment;
    private HomeworkTopicTask $task;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        // Онбординг пройден и телеграм привязан — иначе middleware уводит со страницы ДЗ.
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

        $homework = Homework::create([
            'teacher_id' => $this->teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Тема 23: 1 задача с фото решения',
            'assigned_at' => now(),
        ]);

        $this->task = HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 23,
            'task_order' => 1,
            // payload из банка ФИПИ: условие лежит в `html`, а не в `text`
            'task_payload' => [
                'id' => 1,
                'html' => '<p>Катеты прямоугольного треугольника равны 15 и 20. Найдите высоту.</p>',
                'instruction' => 'Высота к гипотенузе и подобие треугольников',
                'topic_id' => '23',
            ],
            'correct_answer' => '12',
        ]);

        $this->assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $this->student->id,
            'status' => 'assigned',
            'tasks_total' => 1,
        ]);
    }

    private function submitUrl(): string
    {
        return 'https://student.' . config('app.base_domain')
            . "/homework/{$this->assignment->id}/tasks/{$this->task->id}";
    }

    private function homeworkUrl(): string
    {
        return 'https://student.' . config('app.base_domain') . "/homework/{$this->assignment->id}";
    }

    public function test_student_sees_fipi_task_text(): void
    {
        $this->actingAs($this->student)
            ->get($this->homeworkUrl())
            ->assertOk()
            ->assertSee('Катеты прямоугольного треугольника', false);
    }

    public function test_photo_from_phone_is_accepted(): void
    {
        // Типичный снимок тетради: заметно больше прежнего лимита 5 МБ.
        $photo = UploadedFile::fake()->create('IMG_0042.jpg', 8 * 1024, 'image/jpeg');

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12', 'solution_photos' => [$photo]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $submission = HomeworkTopicTaskSubmission::first();
        $this->assertNotNull($submission, 'Сабмишн не сохранился');
        $this->assertSame('12', $submission->first_answer);
        $this->assertTrue($submission->is_correct);
        $this->assertNotNull($submission->accepted_at);
        $this->assertCount(1, $submission->photos);
        Storage::disk('public')->assertExists($submission->photos->first()->path);
    }

    public function test_heic_photo_from_iphone_is_accepted(): void
    {
        $photo = UploadedFile::fake()->create('IMG_0043.heic', 3 * 1024, 'image/heic');

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12', 'solution_photos' => [$photo]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull(HomeworkTopicTaskSubmission::first());
    }

    public function test_oversized_photo_reports_in_russian_and_keeps_answer(): void
    {
        $photo = UploadedFile::fake()->create('huge.jpg', 25 * 1024, 'image/jpeg');

        $response = $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12', 'solution_photos' => [$photo]]);

        $response->assertRedirect()->assertSessionHas('answer_task_id', $this->task->id);
        $this->assertStringContainsString('тяжёлое', session('error'));
        $this->assertSame('12', session('_old_input')['answer'] ?? null);
        $this->assertNull(HomeworkTopicTaskSubmission::first(), 'Попытка не должна тратиться');
    }

    public function test_non_image_file_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('solution.pdf', 200, 'application/pdf');

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12', 'solution_photos' => [$file]])
            ->assertRedirect();

        $this->assertNull(HomeworkTopicTaskSubmission::first());
    }

    public function test_teacher_sees_answers_and_photo(): void
    {
        $photo = UploadedFile::fake()->create('IMG_0042.jpg', 6 * 1024, 'image/jpeg');
        $this->actingAs($this->student)->post($this->submitUrl(), ['answer' => '11', 'solution_photos' => [$photo]]);

        $submission = HomeworkTopicTaskSubmission::first();
        $this->assertSame('11', $submission->first_answer);

        $base = 'https://teacher.' . config('app.base_domain');

        $this->actingAs($this->teacher)
            ->get($base . "/homework/assignment/{$this->assignment->id}")
            ->assertOk()
            ->assertSee('11')
            ->assertSee('Катеты прямоугольного треугольника', false);

        $this->actingAs($this->teacher)
            ->get($base . '/homework/solution-photo/' . $submission->photos->first()->id)
            ->assertOk();
    }

    public function test_other_teacher_cannot_see_photo(): void
    {
        $photo = UploadedFile::fake()->create('IMG_0042.jpg', 1024, 'image/jpeg');
        $this->actingAs($this->student)->post($this->submitUrl(), ['answer' => '12', 'solution_photos' => [$photo]]);

        $photo = HomeworkSolutionPhoto::first();
        $stranger = User::factory()->create(['role' => 'teacher']);

        $base = 'https://teacher.' . config('app.base_domain');

        $this->actingAs($stranger)
            ->get($base . "/homework/assignment/{$this->assignment->id}")
            ->assertForbidden();

        $this->actingAs($stranger)
            ->get($base . '/homework/solution-photo/' . $photo->id)
            ->assertForbidden();
    }
}
