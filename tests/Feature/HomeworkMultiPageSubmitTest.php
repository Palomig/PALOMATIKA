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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Решение задачи может занимать несколько страниц — ученик присылает до 10 фото
 * на попытку, и фото первой попытки не должны теряться при второй.
 */
class HomeworkMultiPageSubmitTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-secret-of-at-least-32-characters-long';

    private User $student;
    private User $teacher;
    private HomeworkAssignment $assignment;
    private HomeworkTopicTask $task;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        config([
            'services.hw_photos.url' => 'https://photos.test/hw-photos',
            'services.hw_photos.secret' => self::SECRET,
        ]);

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

        $homework = Homework::create([
            'teacher_id' => $this->teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Тема 23',
            'assigned_at' => now(),
        ]);
        $this->task = HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 23,
            'task_order' => 1,
            'task_payload' => ['id' => 1, 'text' => 'Найдите высоту.'],
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

    private function photoId(int $index = 0): string
    {
        $meta = [
            'a' => $this->assignment->id,
            'k' => $this->task->id,
            's' => $this->student->id,
            't' => time(),
            'r' => 'page' . $index,
            'x' => 'jpg',
        ];
        $body = rtrim(strtr(base64_encode(json_encode($meta)), '+/', '-_'), '=');
        $sig = rtrim(strtr(base64_encode(hash_hmac('sha256', $body, self::SECRET, true)), '+/', '-_'), '=');

        return "p.{$body}.{$sig}";
    }

    public function test_accepts_several_pages_in_order(): void
    {
        $ids = [$this->photoId(1), $this->photoId(2), $this->photoId(3)];

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12', 'photo_ids' => $ids])
            ->assertRedirect()
            ->assertSessionHas('success');

        $photos = HomeworkTopicTaskSubmission::first()->photos;
        $this->assertCount(3, $photos);
        $this->assertSame($ids, $photos->pluck('remote_id')->all());
        $this->assertSame([1, 2, 3], $photos->pluck('position')->all());
        $this->assertSame([1, 1, 1], $photos->pluck('attempt_no')->all());
    }

    public function test_rejects_more_than_ten_pages(): void
    {
        $ids = [];
        for ($i = 1; $i <= 11; $i++) {
            $ids[] = $this->photoId($i);
        }

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12', 'photo_ids' => $ids])
            ->assertRedirect();

        $this->assertNull(HomeworkTopicTaskSubmission::first());
        $this->assertStringContainsString('10 страниц', (string) session('error'));
    }

    public function test_second_attempt_keeps_first_attempt_pages(): void
    {
        // Первая попытка — неверный ответ, две страницы.
        $this->actingAs($this->student)->post($this->submitUrl(), [
            'answer' => '11',
            'photo_ids' => [$this->photoId(1), $this->photoId(2)],
        ]);

        // Вторая — верный ответ и три страницы.
        $this->actingAs($this->student)->post($this->submitUrl(), [
            'answer' => '12',
            'photo_ids' => [$this->photoId(3), $this->photoId(4), $this->photoId(5)],
        ]);

        $photos = HomeworkTopicTaskSubmission::first()->photos;
        $this->assertCount(5, $photos, 'Страницы первой попытки не должны пропадать');
        $this->assertSame(2, $photos->where('attempt_no', 1)->count());
        $this->assertSame(3, $photos->where('attempt_no', 2)->count());
    }

    public function test_fallback_accepts_several_files(): void
    {
        $files = [
            UploadedFile::fake()->create('page1.jpg', 4 * 1024, 'image/jpeg'),
            UploadedFile::fake()->create('page2.jpg', 4 * 1024, 'image/jpeg'),
        ];

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12', 'solution_photos' => $files])
            ->assertRedirect()
            ->assertSessionHas('success');

        $photos = HomeworkTopicTaskSubmission::first()->photos;
        $this->assertCount(2, $photos);
        foreach ($photos as $photo) {
            $this->assertNull($photo->remote_id);
            Storage::disk('public')->assertExists($photo->path);
        }
    }

    public function test_one_forged_page_rejects_whole_submission(): void
    {
        $this->actingAs($this->student)->post($this->submitUrl(), [
            'answer' => '12',
            'photo_ids' => [$this->photoId(1), 'p.forged.sig'],
        ])->assertRedirect();

        $this->assertNull(HomeworkTopicTaskSubmission::first());
        $this->assertSame(0, HomeworkSolutionPhoto::count());
    }

    public function test_teacher_opens_each_page(): void
    {
        $this->actingAs($this->student)->post($this->submitUrl(), [
            'answer' => '12',
            'photo_ids' => [$this->photoId(1), $this->photoId(2)],
        ]);

        $base = 'https://teacher.' . config('app.base_domain');

        $this->actingAs($this->teacher)
            ->get($base . "/homework/assignment/{$this->assignment->id}")
            ->assertOk()
            ->assertSee('Первая попытка')
            ->assertSee('2 страницы');

        foreach (HomeworkSolutionPhoto::all() as $photo) {
            $this->actingAs($this->teacher)
                ->get($base . "/homework/solution-photo/{$photo->id}?w=400")
                ->assertRedirect();
        }
    }

    public function test_stranger_cannot_open_page(): void
    {
        $this->actingAs($this->student)->post($this->submitUrl(), [
            'answer' => '12',
            'photo_ids' => [$this->photoId(1)],
        ]);

        $stranger = User::factory()->create(['role' => 'teacher']);
        $photo = HomeworkSolutionPhoto::first();

        $this->actingAs($stranger)
            ->get('https://teacher.' . config('app.base_domain') . "/homework/solution-photo/{$photo->id}")
            ->assertForbidden();
    }
}
