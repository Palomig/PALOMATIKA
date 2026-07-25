<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\StudentNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentNotifierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.bot_token' => 'TESTTOKEN']);
    }

    public function test_sends_telegram_message_to_telegram_linked_student(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $student = User::create([
            'name' => 'TG', 'email' => 'tg@t.t', 'password' => 'x', 'role' => 'student',
            'oauth_provider' => 'telegram', 'oauth_id' => '555001', 'telegram_chat_id' => 555001,
        ]);

        $ok = app(StudentNotifier::class)->notify($student, 'Привет', 'https://student.palomatika.ru/homework');

        $this->assertTrue($ok);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org/botTESTTOKEN/sendMessage')
                && $request['chat_id'] === '555001'
                && str_contains($request['text'], 'Привет');
        });
    }

    public function test_does_not_send_to_non_telegram_student(): void
    {
        Http::fake();
        $student = User::create([
            'name' => 'G', 'email' => 'g@t.t', 'password' => 'x', 'role' => 'student',
            'oauth_provider' => 'google', 'oauth_id' => '999',
        ]);

        $ok = app(StudentNotifier::class)->notify($student, 'Привет', null);

        $this->assertFalse($ok);
        Http::assertNothingSent();
    }

    public function test_telegram_failure_does_not_throw(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 403], 403)]);
        $student = User::create([
            'name' => 'TG', 'email' => 'tg2@t.t', 'password' => 'x', 'role' => 'student',
            'oauth_provider' => 'telegram', 'oauth_id' => '555002', 'telegram_chat_id' => 555002,
        ]);

        $ok = app(StudentNotifier::class)->notify($student, 'Привет', null);

        $this->assertFalse($ok); // не бросает, помечает недоставленным
    }
}
