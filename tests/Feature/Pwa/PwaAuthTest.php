<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
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
}
