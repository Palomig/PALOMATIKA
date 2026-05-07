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

    public function test_pwa_and_miniapp_onboarding_use_identical_grade_letters(): void
    {
        $user = User::factory()->create(['oauth_provider' => 'vk', 'oauth_id' => '1']);

        $pwaResponse = $this->actingAs($user)->get('http://student.palomatika.ru/onboarding');
        $miniappResponse = $this->actingAs($user)->get('/tg/onboarding');

        $pwaResponse->assertOk();
        $miniappResponse->assertOk();

        $letters = "'А','Б','В','Г','Д','Е','К','М'";
        $pwaResponse->assertSee($letters, false);
        $miniappResponse->assertSee($letters, false);
    }

    public function test_authenticated_student_request_updates_last_active_at(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'grade_num' => 9,
            'last_active_at' => null,
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('http://student.palomatika.ru/')
            ->assertOk();

        $this->assertNotNull($user->fresh()->last_active_at);
        $this->assertTrue($user->fresh()->last_active_at->greaterThanOrEqualTo(now()->subMinute()));
    }

}
