<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Кнопка «Скачать приложение» (установка PWA) в профиле ученика.
 */
class InstallAppButtonTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://student.palomatika.ru';

    private function student(): User
    {
        return User::factory()->create([
            'role' => 'student', 'grade_num' => 9, 'onboarding_completed_at' => now(),
        ]);
    }

    public function test_profile_shows_install_button(): void
    {
        $resp = $this->actingAs($this->student())->get(self::BASE . '/profile')->assertOk();

        $resp->assertSee('Скачать приложение');
        $resp->assertSee('installApp()', false);
        // Стили партиала уезжают в @stack('styles') лейаута — проверяем, что доехали.
        $resp->assertSee('.install-app__btn', false);
    }

    /** Кнопка бесполезна без манифеста и service worker — они должны отдаваться. */
    public function test_manifest_and_service_worker_are_served(): void
    {
        $manifest = $this->get(self::BASE . '/manifest.json')->assertOk();

        $manifest->assertHeader('Content-Type', 'application/manifest+json');
        $manifest->assertJsonPath('display', 'standalone');
        $manifest->assertJsonPath('icons.0.src', '/icons/student-192.png');

        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('icons/student-192.png'));
        $this->assertFileExists(public_path('icons/student-512.png'));
    }
}
