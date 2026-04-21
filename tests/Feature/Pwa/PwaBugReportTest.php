<?php

namespace Tests\Feature\Pwa;

use App\Models\BugReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaBugReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(): User
    {
        return User::factory()->create([
            'role' => 'student',
            'grade_num' => 6,
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_bug_report_endpoint_stores_report_for_authenticated_student(): void
    {
        $user = $this->makeStudent();

        $response = $this->actingAs($user)
            ->postJson('http://student.palomatika.ru/bug-report', [
                'url' => 'http://student.palomatika.ru/vpr',
                'route_name' => 'pwa.student.vpr.home',
                'description' => 'Тестовый репорт',
                'user_agent' => 'Mozilla/5.0 (Test)',
                'screen_info' => ['screen_w' => 390, 'screen_h' => 844],
                'js_errors' => null,
                'page_context' => null,
            ]);

        $response->assertOk();
        $response->assertExactJson(['ok' => true]);

        $this->assertDatabaseHas('bug_reports', [
            'user_id' => $user->id,
            'url' => 'http://student.palomatika.ru/vpr',
            'description' => 'Тестовый репорт',
        ]);
    }

    public function test_bug_report_endpoint_requires_url(): void
    {
        $response = $this->postJson('http://student.palomatika.ru/bug-report', [
            'description' => 'no url',
        ]);

        $response->assertStatus(422);
    }
}
