<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(int $grade): User
    {
        return User::factory()->create([
            'role'                    => 'student',
            'grade_num'               => $grade,
            'grade_letter'            => 'А',
            'school_number'           => '1',
            'city'                    => 'Чехов',
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_grade_5_redirects_to_vpr(): void
    {
        $user = $this->makeStudent(5);
        $this->actingAs($user)
             ->get('https://student.' . config('app.base_domain') . '/')
             ->assertRedirect(route('pwa.student.vpr.home'));
    }

    public function test_grade_9_stays_on_oge_dashboard(): void
    {
        $user     = $this->makeStudent(9);
        $response = $this->actingAs($user)
             ->get('https://student.' . config('app.base_domain') . '/');
        // Grade 9 — no redirect, renders OGE dashboard
        $response->assertStatus(200);
    }

    public function test_grade_10_redirects_to_ege(): void
    {
        $user = $this->makeStudent(10);
        $this->actingAs($user)
             ->get('https://student.' . config('app.base_domain') . '/')
             ->assertRedirect(route('pwa.student.ege.home'));
    }

    public function test_grade_12_redirects_to_history(): void
    {
        $user = $this->makeStudent(12);
        $this->actingAs($user)
             ->get('https://student.' . config('app.base_domain') . '/')
             ->assertRedirect(route('pwa.student.history'));
    }
}
