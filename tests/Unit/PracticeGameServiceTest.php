<?php

namespace Tests\Unit;

use App\Services\PracticeGameService;
use Tests\TestCase;

class PracticeGameServiceTest extends TestCase
{
    public function test_resolves_level_by_score(): void
    {
        $service = app(PracticeGameService::class);

        $this->assertSame(1, $service->resolveLevel('equations', 0)['level']);
        $this->assertSame(2, $service->resolveLevel('equations', 10)['level']);
        $this->assertSame(4, $service->resolveLevel('equations', 35)['level']);
    }

    public function test_functions_game_level_one_is_linear_level_two_is_parabola(): void
    {
        $service = app(PracticeGameService::class);

        $level1 = $service->resolveLevel('functions', 0);
        $level2 = $service->resolveLevel('functions', 15);

        $this->assertSame(['linear_sign_k', 'linear_sign_b'], $level1['task_types']);
        $this->assertSame(['parabola_sign_a', 'parabola_sign_c'], $level2['task_types']);
    }

    public function test_linear_sign_question_has_graph_and_sign_options(): void
    {
        $service = app(PracticeGameService::class);

        $question = $service->generateQuestionByTaskType('linear_sign_k');

        $this->assertSame('linear_sign_k', $question['task_type']);
        $this->assertNull($question['equation']);
        $this->assertIsString($question['graph']);
        $this->assertStringContainsString('<svg', $question['graph']);

        $labels = collect($question['options'])->pluck('label')->sort()->values()->all();
        $this->assertSame(['k < 0', 'k > 0'], $labels);
    }

    public function test_parabola_sign_c_question_returns_svg_and_sign_options(): void
    {
        $service = app(PracticeGameService::class);

        $question = $service->generateQuestionByTaskType('parabola_sign_c');

        $this->assertStringContainsString('<svg', $question['graph']);
        $labels = collect($question['options'])->pluck('label')->sort()->values()->all();
        $this->assertSame(['c < 0', 'c > 0'], $labels);
    }

    public function test_generates_negative_multiplier_transfer_question(): void
    {
        $service = app(PracticeGameService::class);

        $question = $service->generateQuestionByTaskType('move_negative_multiplier');

        $this->assertSame('move_negative_multiplier', $question['task_type']);
        $this->assertStringContainsString('x', $question['equation']);
        $this->assertCount(2, $question['options']);
        $this->assertSame(1, collect($question['options'])->where('is_correct', true)->count());

        $correct = collect($question['options'])->firstWhere('is_correct', true);
        $this->assertStringContainsString('/', $correct['label']);
        $this->assertStringContainsString('-', $correct['label']);
    }

    public function test_small_result_multiplier_trap_swaps_numerator_and_denominator(): void
    {
        $service = app(PracticeGameService::class);

        $question = $service->generateQuestionByTaskType('move_positive_multiplier_small_result');

        $correct = collect($question['options'])->firstWhere('is_correct', true);
        $wrong = collect($question['options'])->firstWhere('is_correct', false);

        $this->assertArrayHasKey('fraction', $correct);
        $this->assertArrayHasKey('fraction', $wrong);

        $this->assertLessThan(
            (int) $correct['fraction']['denominator'],
            (int) $correct['fraction']['numerator'],
            'правильный ответ имеет меньший числитель (произведение) и больший знаменатель (коэффициент)'
        );

        $this->assertSame($correct['fraction']['numerator'], $wrong['fraction']['denominator']);
        $this->assertSame($correct['fraction']['denominator'], $wrong['fraction']['numerator']);
    }

    public function test_generates_negative_term_transfer_question(): void
    {
        $service = app(PracticeGameService::class);

        $question = $service->generateQuestionByTaskType('move_negative_term_before_x');

        $this->assertSame('move_negative_term_before_x', $question['task_type']);
        $this->assertCount(2, $question['options']);

        $correct = collect($question['options'])->firstWhere('is_correct', true);
        $wrong = collect($question['options'])->firstWhere('is_correct', false);

        $this->assertStringContainsString('+', $correct['label']);
        $this->assertStringContainsString('-', $wrong['label']);
    }
}
