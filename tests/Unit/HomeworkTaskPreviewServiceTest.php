<?php

namespace Tests\Unit;

use App\Services\HomeworkTaskPreviewService;
use Tests\TestCase;

class HomeworkTaskPreviewServiceTest extends TestCase
{
    public function test_projects_nested_condition_without_answers_and_preserves_math_and_options(): void
    {
        $task = app(HomeworkTaskPreviewService::class)->condition([
            'task' => [
                'text' => 'Найдите значение выражения',
                'expression' => '\\frac{1}{2} + x^2',
                'answer' => 'ANSWER-SECRET',
                'options' => [['id' => 1, 'text' => '$x=2$', 'is_correct' => true, 'answer' => 'OPTION-SECRET']],
            ],
            'correct_answer' => 'ROOT-SECRET',
        ], 3);

        $this->assertSame(3, $task['number']);
        $this->assertStringContainsString('\\frac{1}{2}', $task['expression_html']);
        $this->assertSame('$x=2$', $task['options'][0]['html']);
        $this->assertStringNotContainsString('SECRET', json_encode($task));
        $this->assertStringNotContainsString('is_correct', json_encode($task));
    }

    public function test_preserves_fipi_html_multiple_drawings_and_ignores_empty_text(): void
    {
        $html = '<p>Сопоставьте графики</p><table><tr><td><svg viewBox="0 0 20 20"><circle r="2"/></svg></td><td><svg viewBox="0 0 30 30"><circle r="3"/></svg></td></tr></table>';
        $task = app(HomeworkTaskPreviewService::class)->condition([
            'text' => '', 'html' => $html, 'expression' => 'Flattened duplicate',
        ], 1);

        $this->assertSame($html, $task['text_html']);
        $this->assertSame('', $task['expression_html']);
        $this->assertSame('', $task['svg_html']);
    }

    public function test_keeps_all_issued_statements_and_value_table_without_truth_flags(): void
    {
        $task = app(HomeworkTaskPreviewService::class)->condition([
            'expression' => 'Заполните таблицу $\\begin{array}{c|cc}\\hline x&1&2\\\\y&&\\end{array}$',
            'statements' => array_fill(0, 4, ['text' => 'Утверждение', 'is_true' => true, 'answer' => 'SECRET']),
        ], 1);

        $this->assertCount(4, $task['statements']);
        $this->assertStringContainsString('\\begin{array}', $task['expression_html']);
        $this->assertStringNotContainsString('SECRET', json_encode($task));
    }

    public function test_resolves_image_paths_and_legacy_inline_svg(): void
    {
        $service = app(HomeworkTaskPreviewService::class);
        $this->assertSame('/images/tasks/06/test.png', $service->condition(['image' => 'test.png'], 1, '6')['image_url']);
        $this->assertSame('https://example.org/test.png', $service->condition(['image' => 'https://example.org/test.png'], 1)['image_url']);
        $this->assertNull($service->condition(['image' => 'javascript:alert(1)'], 1, '06')['image_url']);
        $this->assertStringContainsString('<svg', $service->condition(['image' => '<svg viewBox="0 0 1 1"></svg>'], 1)['svg_html']);
    }

    public function test_structured_numbers_and_bare_math_options_remain_renderable(): void
    {
        $service = app(HomeworkTaskPreviewService::class);
        $task = $service->condition([
            'type' => 'between_fractions',
            'instruction' => 'Какое число заключено между числами',
            'left' => '\\frac{8}{3}', 'right' => '\\frac{11}{4}',
            'options' => [['id' => 'a', 'text' => 'y = \\frac{2}{5}x + 2']],
        ], 1);
        $this->assertSame('\\(\\frac{8}{3} \\text{ и } \\frac{11}{4}\\)', $task['expression_html']);
        $this->assertSame('\\(y = \\frac{2}{5}x + 2\\)', $task['options'][0]['html']);
        $this->assertSame('\\([3; 4]\\)', $service->condition(['segment' => '[3; 4]'], 1)['expression_html']);
    }
}
