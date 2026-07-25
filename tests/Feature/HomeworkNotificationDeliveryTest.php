<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\User;
use App\Services\StudentNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HomeworkNotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.bot_token' => 'TESTTOKEN']);
    }

    private function student(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'student'], $attributes));
    }

    public function test_sends_to_linked_chat_id(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        $student = $this->student(['telegram_chat_id' => 6490457130]);

        $this->assertTrue(app(StudentNotifier::class)->notify($student, 'Привет', 'https://student.test/homework'));

        Http::assertSent(fn ($request) => $request['chat_id'] === '6490457130');
    }

    public function test_does_not_send_to_oidc_pseudonym(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        // Такой аккаунт раньше «уведомлялся» в никуда: sub в oauth_id — не chat_id.
        $student = User::factory()->withoutTelegram()->create([
            'role' => 'student',
            'oauth_provider' => 'telegram',
            'oauth_id' => '16549735672622918414',
            'telegram_oidc_sub' => '16549735672622918414',
        ]);

        $this->assertFalse(app(StudentNotifier::class)->notify($student, 'Привет'));

        Http::assertNothingSent();
    }

    public function test_marks_student_blocked_on_403(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(
            ['ok' => false, 'description' => 'Forbidden: bot was blocked by the user'], 403
        )]);
        $student = $this->student(['telegram_chat_id' => 1133723423]);

        $this->assertFalse(app(StudentNotifier::class)->notify($student, 'Привет'));
        $this->assertNotNull($student->fresh()->telegram_blocked_at);
    }

    public function test_notified_at_stays_null_when_delivery_fails(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 403)]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = $this->student(['telegram_chat_id' => 1133723423]);

        $assignment = $this->assign($teacher, $student);

        $this->notifyVia($teacher, $assignment);

        // Раньше здесь проставлялся notified_at — недоставка выглядела отправкой.
        $this->assertNull($assignment->fresh()->notified_at);
    }

    public function test_notified_at_set_on_success(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = $this->student(['telegram_chat_id' => 6490457130]);

        $assignment = $this->assign($teacher, $student);

        $this->notifyVia($teacher, $assignment);

        $this->assertNotNull($assignment->fresh()->notified_at);
    }

    private function assign(User $teacher, User $student): HomeworkAssignment
    {
        $homework = Homework::create([
            'teacher_id' => $teacher->id, 'homework_type' => 'topic_photo_practice',
            'title' => 'ДЗ по уроку', 'tasks_count' => 3, 'topic_number' => 6, 'assigned_at' => now(),
        ]);

        return HomeworkAssignment::create([
            'homework_id' => $homework->id, 'student_id' => $student->id,
            'status' => 'assigned', 'tasks_total' => 3,
        ]);
    }

    /** Дёргает приватный notifyNewHomework так же, как это делает выдача ДЗ. */
    private function notifyVia(User $teacher, HomeworkAssignment $assignment): void
    {
        $controller = app(\App\Http\Controllers\Pwa\TeacherController::class);
        $method = new \ReflectionMethod($controller, 'notifyNewHomework');
        $method->setAccessible(true);
        $method->invoke($controller, $assignment->homework, [$assignment]);
    }
}
