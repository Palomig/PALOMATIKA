<?php

namespace Tests\Unit;

use App\Models\OgeAttempt;
use App\Support\TaskConditionHtml;
use Carbon\Carbon;
use Tests\TestCase;

class TaskConditionHtmlTest extends TestCase
{
    public function test_plain_text_is_escaped_and_keeps_line_breaks(): void
    {
        $html = TaskConditionHtml::render("a < b\nв ответ запишите меньший");

        $this->assertStringContainsString('a &lt; b', $html);
        $this->assertStringContainsString('<br />', $html);
    }

    public function test_bank_markup_passes_through(): void
    {
        $html = TaskConditionHtml::render('<table><tr><td><p>Найдите $MN$.</p></td></tr></table>');

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<p>Найдите $MN$.</p>', $html);
    }

    public function test_scripts_and_handlers_are_stripped(): void
    {
        $html = TaskConditionHtml::render('<p onclick="steal()">Условие</p><script>alert(1)</script>');

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringContainsString('<p>Условие</p>', $html);
    }

    public function test_attempt_review_shows_condition_and_options_as_markup(): void
    {
        $attempt = new OgeAttempt();
        $attempt->submitted_at = Carbon::parse('2026-09-04 14:17:55');

        $html = view('pwa.teacher.student-attempt', [
            'attempt' => $attempt,
            'label' => 'Полный вариант',
            'correct' => 3,
            'total' => 14,
            'time' => 819,
            'backUrl' => '/students/25',
            'wrongTasks' => [[
                'task_number' => 7,
                'task_instruction' => 'Найти квадратный корень среди точек',
                'task_text' => '<p>На координатной прямой отмечены точки $A$, $B$.</p>',
                'task_expression' => '',
                'task_svg' => '',
                'task_image' => '',
                'task_options' => [
                    ['n' => 1, 'html' => '<p>точка $A$</p>', 'text' => '<p>точка $A$</p>'],
                    ['n' => 2, 'html' => '<p>точка $B$</p>', 'text' => '<p>точка $B$</p>'],
                ],
                'student_answer' => '2',
                'correct_answer' => '1',
            ]],
        ])->render();

        // Разметка условия и вариантов доезжает до страницы разбора живой,
        // а не текстом «<p>…</p>», как было до фикса.
        $this->assertStringContainsString('<p>На координатной прямой отмечены точки $A$, $B$.</p>', $html);
        $this->assertStringContainsString('<p>точка $A$</p>', $html);
        $this->assertStringNotContainsString('&lt;p&gt;', $html);
    }
}
