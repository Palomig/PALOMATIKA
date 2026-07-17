<?php

namespace Tests\Feature\Pwa;

use App\Models\OgeVariant;
use App\Models\User;
use App\Services\StudentExamAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ученики 10–11 классов (ЕГЭ по умолчанию) могут переключаться в ОГЭ-базу
 * для повторения — зеркало тумблера ВПР↔ОГЭ у 8 класса.
 */
class PwaEgeOgeToggleTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://student.palomatika.ru';

    private function student(int $grade): User
    {
        return User::factory()->create([
            'role' => 'student', 'grade_num' => $grade, 'grade_letter' => 'А',
            'school_number' => '1', 'city' => 'Чехов', 'onboarding_completed_at' => now(),
        ]);
    }

    public function test_grade_11_allowed_exam_types_include_oge(): void
    {
        $svc = app(StudentExamAccessService::class);
        $this->assertEqualsCanonicalizing(
            [OgeVariant::EXAM_EGE, OgeVariant::EXAM_OGE],
            $svc->allowedExamTypesFor($this->student(11)),
        );
        $this->assertEqualsCanonicalizing(
            [OgeVariant::EXAM_EGE, OgeVariant::EXAM_OGE],
            $svc->allowedExamTypesFor($this->student(10)),
        );
    }

    public function test_grade_11_default_dashboard_redirects_to_ege(): void
    {
        $this->actingAs($this->student(11))
            ->get(self::BASE . '/')
            ->assertRedirect();
    }

    public function test_grade_11_can_open_oge_dashboard(): void
    {
        $this->actingAs($this->student(11))
            ->get(self::BASE . '/oge')
            ->assertOk()
            ->assertSee('Переключиться на ЕГЭ');
    }

    public function test_grade_11_ege_home_shows_oge_toggle(): void
    {
        $this->actingAs($this->student(11))
            ->get(self::BASE . '/ege-app')
            ->assertOk()
            ->assertSee('Переключиться на ОГЭ');
    }

    public function test_grade_9_oge_dashboard_has_no_ege_toggle(): void
    {
        $this->actingAs($this->student(9))
            ->get(self::BASE . '/')
            ->assertOk()
            ->assertDontSee('Переключиться на ЕГЭ');
    }
}
