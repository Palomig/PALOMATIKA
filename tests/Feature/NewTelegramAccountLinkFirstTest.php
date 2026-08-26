<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Свежий аккаунт без chat_id сначала идёт на привязку телеграма и только
 * потом на анкету. Иначе вернувшийся ученик, которого OIDC не узнал,
 * успевает «зарегистрироваться заново» до того, как привязка вернёт его
 * в старый аккаунт.
 */
class NewTelegramAccountLinkFirstTest extends TestCase
{
    use RefreshDatabase;

    private function studentHost(): string
    {
        return 'student.' . config('app.base_domain');
    }

    public function test_fresh_account_without_chat_id_goes_to_link_before_onboarding(): void
    {
        $fresh = User::factory()->withoutTelegram()->create([
            'role' => 'student',
            'onboarding_completed_at' => null,
            'oauth_provider' => 'telegram',
            'oauth_id' => '14889590223453232713',
            'telegram_oidc_sub' => '14889590223453232713',
        ]);

        $this->actingAs($fresh)
            ->get('https://' . $this->studentHost() . '/')
            ->assertRedirect('https://' . $this->studentHost() . '/link-telegram');
    }

    public function test_fresh_account_with_chat_id_still_goes_to_onboarding(): void
    {
        $fresh = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => null,
            'first_name' => null,
            'telegram_chat_id' => 6105200919,
        ]);

        $this->actingAs($fresh)
            ->get('https://' . $this->studentHost() . '/')
            ->assertRedirect('https://' . $this->studentHost() . '/onboarding');
    }

    public function test_snooze_still_lets_the_student_through_to_onboarding(): void
    {
        $fresh = User::factory()->withoutTelegram()->create([
            'role' => 'student',
            'onboarding_completed_at' => null,
            'first_name' => null,
            'telegram_link_snoozed_until' => now()->addDay(),
        ]);

        $this->actingAs($fresh)
            ->get('https://' . $this->studentHost() . '/')
            ->assertRedirect('https://' . $this->studentHost() . '/onboarding');
    }
}
