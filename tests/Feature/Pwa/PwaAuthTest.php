<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PwaAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_login_page_shows_oauth_buttons(): void
    {
        $response = $this->get('http://student.palomatika.ru/login');
        $response->assertStatus(200);
        $response->assertSee('vkontakte');
    }

    public function test_logout_redirects_to_login(): void
    {
        $user = User::factory()->create(['oauth_provider' => 'vk', 'oauth_id' => '123']);
        $response = $this->actingAs($user)
            ->post('http://student.palomatika.ru/logout');
        $response->assertRedirect();
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $user = User::factory()->create([
            'oauth_provider' => 'vk',
            'oauth_id' => '456',
            'onboarding_completed_at' => now(),
        ]);
        $response = $this->actingAs($user)->get('http://student.palomatika.ru/login');
        $response->assertRedirect();
    }

    public function test_student_oauth_redirect_persists_migration_token_in_session(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirectUrl')->once()->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://oauth.example/authorize'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $response = $this->get('http://student.palomatika.ru/auth/google?migration_token=test-token');

        $response->assertRedirect('https://oauth.example/authorize');
        $response->assertSessionHas('pwa_migration_token', 'test-token');
    }

    public function test_student_oauth_callback_links_existing_telegram_user_when_migration_token_present(): void
    {
        $user = User::factory()->create([
            'oauth_provider' => 'telegram',
            'oauth_id' => 'tg-123',
            'onboarding_completed_at' => now(),
        ]);

        Cache::put('pwa_migration:test-token', ['user_id' => $user->id], now()->addMinutes(10));

        $socialUser = new class
        {
            public function getId(): string { return 'oauth-999'; }
            public function getName(): string { return 'Migrated User'; }
            public function getNickname(): string { return 'migrated'; }
            public function getEmail(): string { return 'migrated@example.test'; }
            public function getAvatar(): string { return 'https://example.test/avatar.png'; }
        };

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirectUrl')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $response = $this->withSession(['pwa_migration_token' => 'test-token'])
            ->get('http://student.palomatika.ru/auth/google/callback');

        $response->assertRedirect('http://student.palomatika.ru/');
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('google', $user->fresh()->oauth_provider);
        $this->assertSame('oauth-999', $user->fresh()->oauth_id);
    }

    public function test_teacher_login_logs_out_non_teacher_user_instead_of_redirecting_to_forbidden_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'oauth_provider' => 'vk',
            'oauth_id' => 'student-1',
        ]);

        $response = $this->actingAs($user)->get('http://teacher.palomatika.ru/login');

        $response->assertStatus(200);
        $response->assertSee('Кабинет репетитора');
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
