<?php

namespace Tests\Feature;

use App\Models\StudentNote;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Карточка ученика: тяжёлые блоки свёрнуты в <details>, чтобы «Наблюдения»
 * внизу были достижимы. Тест держит страницу от синтаксических поломок —
 * остальные её тесты живут на урезанной sqlite-схеме и здесь не помогают.
 */
class TeacherStudentProfileFoldTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://teacher.palomatika.ru';

    public function test_profile_renders_history_and_topics_as_collapsible_blocks(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'onboarding_completed_at' => now()]);
        $student = User::factory()->create(['role' => 'student', 'onboarding_completed_at' => now()]);
        TeacherStudent::create(['teacher_id' => $teacher->id, 'student_id' => $student->id, 'source' => 'manual']);

        StudentNote::create([
            'student_id' => $student->id, 'teacher_id' => $teacher->id,
            'kind' => 'weakness', 'source' => 'chat', 'body' => 'Путает подобие',
        ]);

        $html = $this->actingAs($teacher)
            ->get(self::BASE . "/students/{$student->id}")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('История вариантов', $html);
        $this->assertStringContainsString('Темы/задания: точность', $html);
        // Оба блока — раскрытые <details>, а не просто заголовки.
        $this->assertSame(2, substr_count($html, '<details class="card fold" open>'));
        $this->assertStringContainsString('Путает подобие', $html);
    }
}
