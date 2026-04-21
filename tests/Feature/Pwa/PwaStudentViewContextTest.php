<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaStudentViewContextTest extends TestCase
{
    use RefreshDatabase;

    private function makePreviewUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'name' => ucfirst($role) . ' Preview',
            'grade_num' => 9,
            'grade_letter' => 'А',
            'school_number' => '1',
            'city' => 'Чехов',
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_teacher_can_set_student_view_exam_to_vpr(): void
    {
        $teacher = $this->makePreviewUser('teacher');

        $response = $this->actingAs($teacher)
            ->post('http://student.palomatika.ru/view-as/student/exam/vpr');

        $response->assertRedirect(route('pwa.student.dashboard'));
        $response->assertSessionHas('view_as_exam', 'vpr');
    }

    public function test_teacher_can_set_student_view_vpr_grade(): void
    {
        $teacher = $this->makePreviewUser('teacher');

        $response = $this->actingAs($teacher)
            ->withSession(['view_as_exam' => 'vpr'])
            ->post('http://student.palomatika.ru/view-as/student/vpr-grade/7');

        $response->assertRedirect(route('pwa.student.dashboard'));
        $response->assertSessionHas('view_as_exam', 'vpr');
        $response->assertSessionHas('view_as_vpr_grade', 7);
    }

    public function test_teacher_student_view_dashboard_redirects_to_vpr_home_when_vpr_mode_selected(): void
    {
        $teacher = $this->makePreviewUser('teacher');

        $response = $this->actingAs($teacher)
            ->withSession([
                'view_as_exam' => 'vpr',
                'view_as_vpr_grade' => 7,
            ])
            ->get('http://student.palomatika.ru/');

        $response->assertRedirect(route('pwa.student.vpr.home', ['grade' => 7]));
    }

    public function test_teacher_student_view_dashboard_stays_on_oge_when_oge_mode_selected(): void
    {
        $teacher = $this->makePreviewUser('teacher');

        $response = $this->actingAs($teacher)
            ->withSession(['view_as_exam' => 'oge'])
            ->get('http://student.palomatika.ru/');

        $response->assertOk();
        $response->assertSee('Мини-ОГЭ');
        $response->assertDontSee('Мини-ВПР');
    }

    public function test_teacher_student_view_vpr_home_uses_session_grade_and_shows_exam_switcher(): void
    {
        $teacher = $this->makePreviewUser('teacher');

        $response = $this->actingAs($teacher)
            ->withSession([
                'view_as_exam' => 'vpr',
                'view_as_vpr_grade' => 6,
            ])
            ->get('http://student.palomatika.ru/vpr');

        $response->assertOk();
        $response->assertSee('ВПР · 6 класс');
        $response->assertSee('ОГЭ');
        $response->assertSee('ВПР');
        $response->assertSee('>5<', false);
        $response->assertSee('>6<', false);
        $response->assertSee('>7<', false);
        $response->assertSee('>8<', false);
    }
}
