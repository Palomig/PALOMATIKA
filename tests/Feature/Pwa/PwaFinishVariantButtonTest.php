<?php

namespace Tests\Feature\Pwa;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Регрессия на баг #22 agent-board: кнопка «Завершить вариант» в PWA-тесте
 * не срабатывала в Яндекс.Браузере из-за одной кнопки с ветвлением по стейту.
 *
 * Фикс: разделить на две отдельные кнопки через x-if. Этот тест проверяет,
 * что рендер содержит обе кнопки с ожидаемыми @click-handler-ами и маркером
 * data-testid для e2e-тестов.
 */
class PwaFinishVariantButtonTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(int $grade = 9): User
    {
        return User::factory()->create([
            'role' => 'student',
            'grade_num' => $grade,
            'grade_letter' => 'А',
            'school_number' => '1',
            'city' => 'Чехов',
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_oge_variant_renders_separate_next_and_finish_buttons(): void
    {
        $student = $this->makeStudent();
        $variant = OgeVariant::create([
            'hash' => 'finishbtnoge01',
            'exam_type' => OgeVariant::EXAM_OGE,
            'title' => 'ОГЭ кнопка завершить',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => [
                'tasks' => [
                    ['task_number' => 6, 'topic_id' => '06', 'type' => 'expression', 'text' => 'a'],
                    ['task_number' => 7, 'topic_id' => '07', 'type' => 'expression', 'text' => 'b'],
                ],
            ],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'started_at' => now()->subMinutes(2),
            'last_seen_at' => now()->subMinute(),
        ]);

        $html = $this->actingAs($student)
            ->get("http://student.palomatika.ru/test/{$attempt->id}")
            ->assertOk()
            ->getContent();

        // Две отдельные кнопки, каждая в своём x-if:
        $this->assertStringContainsString('x-if="current < total - 1"', $html);
        $this->assertStringContainsString('x-if="current >= total - 1"', $html);

        // next → goNext()
        $this->assertStringContainsString('@click="goNext()"', $html);

        // finish → openFinishModal() + testid для e2e
        $this->assertStringContainsString('@click="openFinishModal()"', $html);
        $this->assertStringContainsString('data-testid="finish-variant-btn"', $html);

        // Старый ветвящийся обработчик next() не должен рендериться как @click на
        // главной кнопке bottom-бара.
        $this->assertStringNotContainsString(
            ':class="{ \'finish\': current === total - 1 }"',
            $html,
            'Старая кнопка с одним @click="next()" осталась в разметке.'
        );
    }
}
