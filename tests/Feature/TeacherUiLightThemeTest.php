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
        $response->assertSee('window.__teacherUiMode = "light"', false);
        $response->assertSee('--tsh-bg: var(--site-bg, #EEF2FF)', false);
        $response->assertDontSee("window.__uiMode = localStorage.getItem('palomatika_ui_mode') || 'dark'", false);
    }

    public function test_teacher_earnings_page_avoids_dark_gradient_cards(): void
    {
        $response = $this->actingAs($this->teacher())->get('/teacher/earnings');

        $response->assertOk();
        $response->assertDontSee('bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 text-white', false);
        $response->assertSee('tsh-card', false);
    }

    public function test_teacher_dashboard_applies_dark_mode_from_user_preference(): void
    {
        $teacher = User::factory()->make([
            'role' => 'teacher',
            'teacher_ui_mode' => 'dark',
        ]);

        $response = $this->actingAs($teacher)->get('/teacher');

        $response->assertOk();
        $response->assertSee('window.__teacherUiMode = "dark"', false);
        $response->assertSee('--tsh-bg: var(--site-dark-bg, #111433)', false);
    }

    public function test_teacher_layout_uses_wide_sidebar_navigation_shell(): void
    {
        $response = $this->actingAs($this->teacher())->get('/teacher/students');

        $response->assertOk();
        $response->assertSee('tsh-fluid-shell', false);
        $response->assertSee('--tsh-primary: var(--site-primary, #4F46E5)', false);
        $response->assertDontSee('tsh-tabs', false);
    }

    public function test_theme_engine_exposes_site_palette_variables_for_teacher_shell(): void
    {
        $response = $this->actingAs($this->teacher())->get('/teacher');

        $response->assertOk();
        $response->assertSee("document.documentElement.style.setProperty('--site-primary'", false);
        $response->assertSee("document.documentElement.style.setProperty('--site-bg'", false);
    }
}
