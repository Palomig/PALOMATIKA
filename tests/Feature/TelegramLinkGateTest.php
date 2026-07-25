<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramLinkGateTest extends TestCase
{
    use RefreshDatabase;

    private function studentHost(): string
    {
        return 'student.' . config('app.base_domain');
    }

    private function student(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'student',
            'onboarding_completed_at' => now(),
        ], $attributes));
    }

    /** Ученик, вошедший не через телеграм: chat_id ещё нет. */
    private function unlinkedStudent(array $attributes = []): User
    {
        return User::factory()->withoutTelegram()->create(array_merge([
            'role' => 'student',
            'onboarding_completed_at' => now(),
        ], $attributes));
    }

    public function test_student_without_chat_id_is_reminded(): void
    {
        $student = $this->unlinkedStudent(['oauth_provider' => 'yandex', 'oauth_id' => '977353831']);

        $this->actingAs($student)
            ->get('https://' . $this->studentHost() . '/homework')
            ->assertRedirect('https://' . $this->studentHost() . '/link-telegram');
    }

    public function test_student_with_chat_id_passes(): void
    {
        $student = $this->student(['telegram_chat_id' => 6490457130]);

        $this->actingAs($student)
            ->get('https://' . $this->studentHost() . '/homework')
            ->assertOk();
    }

    public function test_link_page_itself_is_not_gated(): void
    {
        $student = $this->unlinkedStudent(['oauth_provider' => 'google', 'oauth_id' => '103988573753214805316']);

        $this->actingAs($student)
            ->get('https://' . $this->studentHost() . '/link-telegram')
            ->assertOk();
    }

    public function test_link_page_redirects_home_when_already_linked(): void
    {
        $student = $this->student(['telegram_chat_id' => 6490457130]);

        $this->actingAs($student)
            ->get('https://' . $this->studentHost() . '/link-telegram')
            ->assertRedirect('https://' . $this->studentHost() . '/');
    }

    public function test_start_returns_deep_link(): void
    {
        config(['services.telegram.bot_username' => 'palomatika_auth_bot']);
        $student = $this->unlinkedStudent(['oauth_provider' => 'yandex', 'oauth_id' => '1']);

        $response = $this->actingAs($student)
            ->postJson('https://' . $this->studentHost() . '/link-telegram/start');

        $response->assertOk();
        $this->assertStringStartsWith(
            'https://t.me/palomatika_auth_bot?start=link_',
            $response->json('deep_link')
        );
    }

    public function test_status_does_not_leak_link_result_to_other_users(): void
    {
        $owner = $this->unlinkedStudent(['oauth_provider' => 'yandex', 'oauth_id' => '2']);
        $stranger = $this->student(['telegram_chat_id' => 111111]);

        $code = app(\App\Services\TelegramLinkService::class)->issueCode($owner)['code'];
        app(\App\Services\TelegramLinkService::class)->completeLink($code, ['id' => 222222]);

        $this->actingAs($stranger)
            ->getJson('https://' . $this->studentHost() . '/link-telegram/status?code=' . $code)
            ->assertOk()
            ->assertJson(['linked' => false]);
    }

    public function test_snooze_stops_reminder_for_a_day(): void
    {
        $student = $this->unlinkedStudent(['oauth_provider' => 'yandex', 'oauth_id' => '3']);

        $this->actingAs($student)
            ->post('https://' . $this->studentHost() . '/link-telegram/snooze')
            ->assertRedirect('https://' . $this->studentHost() . '/');

        $this->assertNotNull($student->fresh()->telegram_link_snoozed_until);

        // Сутки экран не всплывает — привязка остаётся просьбой, а не блоком.
        $this->actingAs($student)
            ->get('https://' . $this->studentHost() . '/homework')
            ->assertOk();
    }

    public function test_reminder_returns_after_snooze_expires(): void
    {
        $student = $this->unlinkedStudent([
            'oauth_provider' => 'yandex', 'oauth_id' => '4',
            'telegram_link_snoozed_until' => now()->subMinute(),
        ]);

        $this->actingAs($student)
            ->get('https://' . $this->studentHost() . '/homework')
            ->assertRedirect('https://' . $this->studentHost() . '/link-telegram');
    }

    public function test_json_requests_are_never_redirected(): void
    {
        $student = $this->unlinkedStudent(['oauth_provider' => 'google', 'oauth_id' => '5']);

        // Фоновые ручки не должны ломаться из-за непривязанного телеграма.
        $this->actingAs($student)
            ->getJson('https://' . $this->studentHost() . '/lessons/active')
            ->assertOk();
    }

    public function test_teacher_is_not_gated(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'onboarding_completed_at' => now()]);

        $this->actingAs($teacher)
            ->get('https://teacher.' . config('app.base_domain') . '/dashboard')
            ->assertOk();
    }
}
