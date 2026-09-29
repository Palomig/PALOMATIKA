<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NameDictionaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaOnboardingNameTest extends TestCase
{
    use RefreshDatabase;

    private function submit(User $user, string $firstName, bool $unverified = false)
    {
        $host = 'https://student.' . config('app.base_domain');

        return $this->actingAs($user)
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson($host . '/onboarding', [
                'first_name' => $firstName,
                'last_name' => 'Жупиков',
                'name_unverified' => $unverified ? 1 : 0,
                'grade_num' => 9,
                'grade_letter' => 'А',
                'school_number' => 3,
            ]);
    }

    public function test_yo_and_ye_spellings_are_both_known(): void
    {
        $names = app(NameDictionaryService::class);

        $this->assertTrue($names->isKnownName('Артём'));
        $this->assertTrue($names->isKnownName('Артем'));
        $this->assertTrue($names->isKnownName('семен'));
        $this->assertTrue($names->isKnownName('Федор'));
        $this->assertFalse($names->isKnownName('Qwerty'));
    }

    public function test_artem_without_yo_completes_onboarding(): void
    {
        $user = User::factory()->create(['role' => 'student', 'onboarding_completed_at' => null]);

        $this->submit($user, 'Артем')->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->onboarding_completed_at);
        $this->assertSame('Артем', $user->first_name);
    }

    public function test_unknown_name_returns_json_error_instead_of_silent_redirect(): void
    {
        $user = User::factory()->create(['role' => 'student', 'onboarding_completed_at' => null]);

        $this->submit($user, 'Qwerty')
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'не найдено в списке'));

        $this->assertNull($user->fresh()->onboarding_completed_at);
    }

    public function test_unknown_name_with_checkbox_is_accepted(): void
    {
        $user = User::factory()->create(['role' => 'student', 'onboarding_completed_at' => null]);

        $this->submit($user, 'Qwerty', true)->assertRedirect();

        $this->assertNotNull($user->fresh()->onboarding_completed_at);
    }
}
