<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Основной клиент отправляет ответ обычным fetch и ждёт JSON.
 *
 * В Telegram WebView ученик не видит ни редиректа, ни страницы-заглушки: любой
 * не-JSON ответ для него неотличим от тишины, а ошибка на проде не оставляет
 * следа ни в базе, ни в логах. Поэтому у отправки есть явный контракт —
 * `ok`/`reload`/`message`/`code` — и отдельный канал для следа с телефона.
 */
class HomeworkPhotoJsonSubmitTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-secret-of-at-least-32-characters-long';

    private User $student;
    private HomeworkAssignment $assignment;
    private HomeworkTopicTask $task;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.hw_photos.url' => 'https://photos.test/hw-photos',
            'services.hw_photos.secret' => self::SECRET,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
        ]);
        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $this->student->id,
            'source' => 'manual',
        ]);

        $homework = Homework::create([
            'teacher_id' => $teacher->id,
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

    private function logUrl(): string
    {
        return $this->submitUrl() . '/photo-log';
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

    public function test_correct_answer_returns_ok_and_asks_page_to_reload(): void
    {
        $response = $this->actingAs($this->student)->postJson($this->submitUrl(), [
            'answer' => '12',
            'photo_ids' => [$this->photoId(1), $this->photoId(2)],
        ]);

        $response->assertOk()
            ->assertJson(['ok' => true, 'reload' => true])
            ->assertJsonPath('message', fn ($message) => str_contains((string) $message, 'ответ верный'));

        // Сообщение уезжает во флеш: после перезагрузки его показывает та же
        // плашка, что и при обычной отправке.
        $response->assertSessionHas('success');

        $submission = HomeworkTopicTaskSubmission::first();
        $this->assertNotNull($submission);
        $this->assertTrue((bool) $submission->is_correct);
        $this->assertCount(2, $submission->photos);
    }

    public function test_wrong_first_attempt_returns_not_ok_but_still_reloads(): void
    {
        $response = $this->actingAs($this->student)->postJson($this->submitUrl(), [
            'answer' => '11',
            'photo_ids' => [$this->photoId(1)],
        ]);

        // Попытка засчитана, карточка задачи на странице стала другой.
        $response->assertOk()->assertJson(['ok' => false, 'reload' => true]);
        $response->assertSessionHas('error');

        $this->assertSame(1, (int) HomeworkTopicTaskSubmission::first()->attempts_count);
    }

    public function test_validation_error_comes_back_without_reload(): void
    {
        $response = $this->actingAs($this->student)->postJson($this->submitUrl(), [
            'answer' => '12',
        ]);

        $response->assertOk()->assertJson([
            'ok' => false,
            'reload' => false,
            'code' => 'validation',
        ]);

        // Ученику ничего не перезагружаем: набранное на экране должно уцелеть.
        $response->assertSessionMissing('error');
        $this->assertNull(HomeworkTopicTaskSubmission::first());
    }

    public function test_forged_photo_id_asks_client_to_drop_its_photos(): void
    {
        $response = $this->actingAs($this->student)->postJson($this->submitUrl(), [
            'answer' => '12',
            'photo_ids' => [$this->photoId(1), 'p.forged.sig'],
        ]);

        $response->assertOk()->assertJson([
            'ok' => false,
            'reload' => false,
            'code' => 'photo_rejected',
        ]);

        $this->assertNull(HomeworkTopicTaskSubmission::first());
    }

    public function test_already_accepted_task_is_reported_not_swallowed(): void
    {
        $this->actingAs($this->student)->postJson($this->submitUrl(), [
            'answer' => '12',
            'photo_ids' => [$this->photoId(1)],
        ])->assertOk();

        $this->actingAs($this->student)->postJson($this->submitUrl(), [
            'answer' => '12',
            'photo_ids' => [$this->photoId(2)],
        ])->assertOk()->assertJson([
            'ok' => true,
            'reload' => true,
            'message' => 'Эта задача уже принята.',
        ]);

        $this->assertCount(1, HomeworkTopicTaskSubmission::first()->photos);
    }

    public function test_html_client_still_gets_a_redirect(): void
    {
        // No-JS и фолбэк с файлами не должны пострадать от нового контракта.
        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['answer' => '12', 'photo_ids' => [$this->photoId(1)]])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_client_trail_lands_in_its_own_log_channel(): void
    {
        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $context['outcome'] === 'network'
                    && $context['assignment'] === $this->assignment->id
                    && $context['trail'][0]['step'] === 'submit_start';
            });

        Log::shouldReceive('channel')->with('hw_photos')->once()->andReturn($logger);

        $this->actingAs($this->student)->postJson($this->logUrl(), [
            'outcome' => 'network',
            'ua' => 'Mozilla/5.0 (Linux; Android 13) TelegramWebView',
            'trail' => [
                ['at' => '10:10:01', 'step' => 'submit_start', 'detail' => '1 стр.'],
                ['at' => '10:10:44', 'step' => 'submit_error', 'detail' => 'Failed to fetch'],
            ],
        ])->assertOk()->assertJson(['ok' => true]);
    }

    public function test_trail_of_another_student_is_refused(): void
    {
        $stranger = User::factory()->create(['role' => 'student', 'onboarding_completed_at' => now()]);

        $this->actingAs($stranger)
            ->postJson($this->logUrl(), ['outcome' => 'ok', 'trail' => []])
            ->assertForbidden();
    }
}
