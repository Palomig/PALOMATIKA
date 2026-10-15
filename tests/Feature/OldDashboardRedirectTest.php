<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Старая главная первой версии (/dashboard на основном домене) не показывается —
 * после входа (Telegram, Яндекс, Google) человек попадает в свой кабинет.
 */
class OldDashboardRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_goes_to_student_pwa(): void
    {
        $student = User::factory()->create(['role' => 'student', 'onboarding_completed_at' => now()]);

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertRedirect('https://student.' . config('app.base_domain') . '/');
    }

    public function test_teacher_and_admin_go_to_teacher_pwa(): void
    {
        foreach (['teacher', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role, 'onboarding_completed_at' => now()]);

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertRedirect('https://teacher.' . config('app.base_domain') . '/dashboard');
        }
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect();
    }
}
