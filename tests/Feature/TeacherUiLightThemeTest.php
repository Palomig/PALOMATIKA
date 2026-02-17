<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class TeacherUiLightThemeTest extends TestCase
{
    private function teacher(): User
    {
        return User::factory()->make(['role' => 'teacher']);
    }

    public function test_teacher_dashboard_uses_light_shell_and_does_not_boot_dark_mode_script(): void
    {
        $response = $this->actingAs($this->teacher())->get('/teacher');

        $response->assertOk();
        $response->assertSee('teacher-shell', false);
        $response->assertSee('--tsh-bg: #eef1f8', false);
        $response->assertDontSee("window.__uiMode = localStorage.getItem('palomatika_ui_mode') || 'dark'", false);
        $response->assertDontSee(":root[data-ui-mode=\"dark\"]", false);
    }

    public function test_teacher_earnings_page_avoids_dark_gradient_cards(): void
    {
        $response = $this->actingAs($this->teacher())->get('/teacher/earnings');

        $response->assertOk();
        $response->assertDontSee('bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 text-white', false);
        $response->assertSee('tsh-card', false);
    }
}
