<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTeacherRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_dashboard_requires_auth(): void
    {
        $response = $this->get('http://teacher.palomatika.ru/dashboard');

        $response->assertRedirect('http://teacher.palomatika.ru/login');
    }

    public function test_teacher_dashboard_accessible_for_teacher_role(): void
    {
        $user = User::factory()->create([
            'oauth_provider' => 'vk',
            'oauth_id' => '789',
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('http://teacher.palomatika.ru/dashboard');

        $response->assertOk();
    }
}
