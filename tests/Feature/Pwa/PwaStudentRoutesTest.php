<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PwaStudentRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_unauthenticated(): void
    {
        $response = $this->get('http://student.palomatika.ru/');
        $response->assertRedirect();
    }

    public function test_onboarding_page_accessible_when_authenticated(): void
    {
        $user = User::factory()->create(['oauth_provider' => 'vk', 'oauth_id' => '1']);
        $response = $this->actingAs($user)->get('http://student.palomatika.ru/onboarding');
        $response->assertStatus(200);
    }

    public function test_onboarding_page_contains_grade_letter_e(): void
    {
        $user = User::factory()->create(['oauth_provider' => 'vk', 'oauth_id' => '1']);

        $response = $this->actingAs($user)->get('http://student.palomatika.ru/onboarding');

        $response->assertOk();
        $response->assertSee("'А','Б','В','Г','Д','Е','И','К','М'", false);
    }
}
