<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkSolutionPhoto;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\HomeworkPhotoStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Фото решений уехали во внешний сервис hw-photos (VPS): ученик грузит снимок
 * напрямую туда, Laravel только подписывает токены и проверяет photo_id.
 * Фолбэк на диск хостинга должен продолжать работать.
 */
class HomeworkPhotoStoreTest extends TestCase
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

    private function studentBase(): string
    {
        return 'https://student.' . config('app.base_domain');
    }

    private function submitUrl(): string
    {
        return $this->studentBase() . "/homework/{$this->assignment->id}/tasks/{$this->task->id}";
    }

    /** photo_id так, как его подписывает сервис: HMAC на том же общем секрете. */
    private function photoId(array $overrides = []): string
    {
        $meta = array_merge([
            'a' => $this->assignment->id,
            'k' => $this->task->id,
            's' => $this->student->id,
            't' => time(),
            'r' => 'abcd1234',
            'x' => 'jpg',
        ], $overrides);

        $body = rtrim(strtr(base64_encode(json_encode($meta)), '+/', '-_'), '=');
        $sig = rtrim(strtr(base64_encode(hash_hmac('sha256', $body, self::SECRET, true)), '+/', '-_'), '=');

        return "p.{$body}.{$sig}";
    }

    public function test_student_gets_upload_ticket(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson($this->submitUrl() . '/photo-ticket');

        $response->assertOk()->assertJson(['enabled' => true]);
        $this->assertSame('https://photos.test/hw-photos/v1/photos', $response->json('upload_url'));
        $this->assertGreaterThan(time(), $response->json('expires_at'));

        // Токен подписан и несёт ровно эту задачу и этого ученика.
        [$kind, $body, $sig] = explode('.', $response->json('token'));
        $this->assertSame('t', $kind);
        $this->assertSame(
            rtrim(strtr(base64_encode(hash_hmac('sha256', $body, self::SECRET, true)), '+/', '-_'), '='),
            $sig
        );
        $claim = json_decode(base64_decode(strtr($body, '-_', '+/')), true);
        $this->assertSame(
            [$this->assignment->id, $this->task->id, $this->student->id],
            [$claim['a'], $claim['k'], $claim['s']]
        );
    }

    public function test_other_student_cannot_get_ticket(): void
    {
        $stranger = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => 111,
        ]);

        $this->actingAs($stranger)
            ->postJson($this->submitUrl() . '/photo-ticket')
            ->assertForbidden();
    }

    public function test_ticket_disabled_without_secret(): void
    {
        config(['services.hw_photos.secret' => null]);

        $this->actingAs($this->student)
            ->postJson($this->submitUrl() . '/photo-ticket')
            ->assertOk()
            ->assertJson(['enabled' => false]);
    }

    public function test_submit_with_photo_id_stores_remote_reference(): void
    {
        $photoId = $this->photoId();

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12', 'photo_ids' => [$photoId]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $submission = HomeworkTopicTaskSubmission::first();
        $this->assertSame($photoId, $submission->photos->first()->remote_id);
        $this->assertNull($submission->photos->first()->path, 'На хостинге ничего лежать не должно');
        $this->assertTrue($submission->is_correct);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_forged_photo_id_is_rejected(): void
    {
        $forged = $this->photoId() . 'x';

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12', 'photo_ids' => [$forged]])
            ->assertRedirect();

        $this->assertNull(HomeworkTopicTaskSubmission::first(), 'Попытка не должна тратиться');
        $this->assertStringContainsString('не подтвердилось', session('error'));
    }

    public function test_photo_id_of_another_task_is_rejected(): void
    {
        // Подпись валидна, но фото загружено для другой задачи — не принимаем.
        $foreign = $this->photoId(['k' => $this->task->id + 999]);

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12', 'photo_ids' => [$foreign]])
            ->assertRedirect();

        $this->assertNull(HomeworkTopicTaskSubmission::first());
    }

    public function test_fallback_to_local_file_still_works(): void
    {
        // Сервис недоступен / нет JS — ученик отправляет файл как раньше.
        $photo = UploadedFile::fake()->create('IMG_1.jpg', 6 * 1024, 'image/jpeg');

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12', 'solution_photos' => [$photo]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $photo = HomeworkTopicTaskSubmission::first()->photos->first();
        $this->assertNull($photo->remote_id);
        Storage::disk('public')->assertExists($photo->path);
    }

    public function test_submit_without_any_photo_is_rejected(): void
    {
        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12'])
            ->assertRedirect();

        $this->assertNull(HomeworkTopicTaskSubmission::first());
        $this->assertStringContainsString('фото', mb_strtolower((string) session('error')));
    }

    public function test_teacher_photo_route_redirects_to_signed_url(): void
    {
        $photoId = $this->photoId();
        $this->actingAs($this->student)->post($this->submitUrl(), ['answer' => '12', 'photo_ids' => [$photoId]]);
        $photo = HomeworkSolutionPhoto::first();

        $response = $this->actingAs($this->teacher)->get(
            'https://teacher.' . config('app.base_domain') . "/homework/solution-photo/{$photo->id}?w=800"
        );

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertStringStartsWith('https://photos.test/hw-photos/v1/photo/', $target);
        $this->assertStringContainsString('w=800', $target);

        // Подпись на чтение проверяема тем же секретом и живёт ограниченное время.
        parse_str(parse_url($target, PHP_URL_QUERY), $query);
        $this->assertGreaterThan(time(), (int) $query['exp']);
        $this->assertSame(
            rtrim(strtr(base64_encode(hash_hmac('sha256', $photoId . '.' . $query['exp'], self::SECRET, true)), '+/', '-_'), '='),
            $query['sig']
        );
    }

    public function test_stranger_cannot_get_signed_url(): void
    {
        $this->actingAs($this->student)->post($this->submitUrl(), ['answer' => '12', 'photo_ids' => [$this->photoId()]]);
        $photo = HomeworkSolutionPhoto::first();
        $stranger = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($stranger)->get(
            'https://teacher.' . config('app.base_domain') . "/homework/solution-photo/{$photo->id}"
        )->assertForbidden();
    }

    public function test_read_url_is_null_when_disabled(): void
    {
        config(['services.hw_photos.secret' => null]);

        $this->assertNull(app(HomeworkPhotoStore::class)->readUrl($this->photoId()));
    }
}
