<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Режим «смотрю глазами ученика»: учитель заходит на student-поддомен и
 * выбирает, какой экзамен видеть. Раньше выбор был из ОГЭ и ВПР — ЕГЭ в
 * переключателе не было вовсе, хотя экраны ЕГЭ учителя уже пускали.
 */
class TeacherStudentViewExamTest extends TestCase
{
    use RefreshDatabase;

    private const STUDENT_HOST = 'http://student.palomatika.ru';

    private function teacher(): User
    {
        return User::factory()->create([
            'role' => 'teacher', 'onboarding_completed_at' => now(),
        ]);
    }

    public function test_teacher_can_switch_the_view_to_ege(): void
    {
        $response = $this->actingAs($this->teacher())
            ->post(route('view-as.student.exam', ['exam' => 'ege']));

        $response->assertRedirect();
        $this->assertSame('ege', session('view_as_exam'));
    }

    public function test_dashboard_follows_the_chosen_exam(): void
    {
        $this->actingAs($this->teacher())
            ->withSession(['view_as_exam' => 'ege'])
            ->get(route('pwa.student.dashboard'))
            ->assertRedirect(route('pwa.student.ege.home'));
    }

    public function test_other_exams_still_work(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)->post(route('view-as.student.exam', ['exam' => 'vpr']));
        $this->assertSame('vpr', session('view_as_exam'));

        $this->actingAs($teacher)->post(route('view-as.student.exam', ['exam' => 'oge']));
        $this->assertSame('oge', session('view_as_exam'));
    }

    public function test_unknown_exam_is_rejected(): void
    {
        $this->actingAs($this->teacher())
            ->post(route('view-as.student.exam', ['exam' => 'gia']))
            ->assertNotFound();
    }

    public function test_student_cannot_switch_the_view(): void
    {
        $student = User::factory()->create([
            'role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($student)
            ->post(route('view-as.student.exam', ['exam' => 'ege']))
            ->assertForbidden();
    }

    public function test_switcher_offers_all_three_exams(): void
    {
        $page = $this->actingAs($this->teacher())
            ->get(route('pwa.student.ege.home'));

        $page->assertSee('>ОГЭ<', false)
            ->assertSee('>ЕГЭ<', false)
            ->assertSee('>ВПР<', false);
    }
}
