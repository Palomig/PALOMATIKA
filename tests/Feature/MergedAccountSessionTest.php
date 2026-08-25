<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Сценарий Вадима (25.08.2026): ученик со старым аккаунтом вошёл через
 * «Войти через Telegram» в другом браузере, OIDC не узнал его по псевдониму
 * и завёл дубль. Привязка телеграма слила дубль в старый аккаунт, но браузер
 * остался залогинен под донором — ученик видел пустой кабинет и говорил,
 * что не может попасть в свой аккаунт.
 */
class MergedAccountSessionTest extends TestCase
{
    use RefreshDatabase;

    private function studentHost(): string
    {
        return 'student.' . config('app.base_domain');
    }

    public function test_session_on_merged_donor_is_moved_to_canonical(): void
    {
        $canonical = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => 6105200919,
        ]);

        $donor = User::factory()->withoutTelegram()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
            'merged_into_id' => $canonical->id,
            'merged_at' => now(),
        ]);

        $this->actingAs($donor)
            ->get('https://' . $this->studentHost() . '/')
            ->assertOk();

        $this->assertTrue(Auth::check());
        $this->assertSame($canonical->id, Auth::id(), 'Сессия должна переехать на канонический аккаунт');
    }

    public function test_donor_pointing_at_deleted_account_is_logged_out(): void
    {
        $donor = User::factory()->withoutTelegram()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
            'merged_into_id' => 999999,
            'merged_at' => now(),
        ]);

        $this->actingAs($donor)
            ->get('https://' . $this->studentHost() . '/')
            ->assertRedirect('https://' . $this->studentHost() . '/login');

        $this->assertFalse(Auth::check());
    }

    public function test_merge_chain_is_followed_to_the_end(): void
    {
        $canonical = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => 6105200919,
        ]);

        $middle = User::factory()->withoutTelegram()->create([
            'role' => 'student',
            'merged_into_id' => $canonical->id,
            'merged_at' => now(),
        ]);

        $donor = User::factory()->withoutTelegram()->create([
            'role' => 'student',
            'merged_into_id' => $middle->id,
            'merged_at' => now(),
        ]);

        $this->actingAs($donor)->get('https://' . $this->studentHost() . '/');

        $this->assertSame($canonical->id, Auth::id());
    }

    public function test_merge_loop_does_not_hang_and_logs_out(): void
    {
        $a = User::factory()->withoutTelegram()->create(['role' => 'student']);
        $b = User::factory()->withoutTelegram()->create([
            'role' => 'student',
            'merged_into_id' => $a->id,
        ]);
        $a->update(['merged_into_id' => $b->id]);

        $this->actingAs($a)
            ->get('https://' . $this->studentHost() . '/')
            ->assertRedirect('https://' . $this->studentHost() . '/login');

        $this->assertFalse(Auth::check());
    }

    public function test_normal_user_is_untouched(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => 6490457130,
        ]);

        $this->actingAs($student)
            ->get('https://' . $this->studentHost() . '/')
            ->assertOk();

        $this->assertSame($student->id, Auth::id());
    }

    /** Полный путь: реальное слияние сервисом, а потом запрос из сессии донора. */
    public function test_after_real_merge_donor_session_lands_on_canonical(): void
    {
        $canonical = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => 6105200919,
        ]);

        $donor = User::factory()->withoutTelegram()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
            'telegram_oidc_sub' => '14889590223453232713',
        ]);

        app(AccountMergeService::class)->merge($donor, $canonical);

        $this->actingAs($donor->fresh())
            ->get('https://' . $this->studentHost() . '/')
            ->assertOk();

        $this->assertSame($canonical->id, Auth::id());
    }
}
