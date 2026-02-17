<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CustomGeneratorResultPageTest extends TestCase
{
    private function userWithRole(string $role): User
    {
        return User::factory()->make(['role' => $role]);
    }

    public function test_cached_custom_test_result_page_is_accessible_by_hash(): void
    {
        $hash = 'abcd1234';

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '15',
            'topic_title' => 'Треугольники',
            'block_number' => 1,
            'zadanie_number' => 1,
            'instruction' => 'Проверка',
            'task' => [
                'svg' => '<svg viewBox="0 0 10 10"><circle cx="5" cy="5" r="3"/></svg>',
                'options' => ['1', '2'],
            ],
        ]], now()->addMinutes(5));

        $response = $this->get("/test/generator/result/{$hash}");

        $response->assertOk();
        $response->assertSee('Кастомный тест ОГЭ');
        $response->assertSee('<svg', false);
    }

    public function test_result_page_renders_inline_svg_from_image_field(): void
    {
        $hash = 'efgh5678';

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '15',
            'topic_title' => 'Треугольники',
            'block_number' => 1,
            'zadanie_number' => 1,
            'instruction' => 'Проверка inline SVG',
            'task' => [
                'image' => '<svg viewBox="0 0 12 12"><rect x="1" y="1" width="10" height="10"/></svg>',
                'options' => ['1', '2'],
            ],
        ]], now()->addMinutes(5));

        $response = $this->get("/test/generator/result/{$hash}");

        $response->assertOk();
        $response->assertSee('<svg viewBox="0 0 12 12">', false);
    }

    public function test_result_page_renders_svg_from_svg_type_payload(): void
    {
        $hash = 'ijkl9012';

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '07',
            'topic_title' => 'Числа, координатная прямая',
            'block_number' => 1,
            'zadanie_number' => 1,
            'instruction' => 'Проверка svg_type',
            'svg_type' => 'single_point',
            'task' => [
                'point_value' => 2.5,
                'point_label' => 'a',
                'options' => ['1', '2'],
            ],
        ]], now()->addMinutes(5));

        $response = $this->get("/test/generator/result/{$hash}");

        $response->assertOk();
        $response->assertSee('number-line', false);
    }

    public function test_result_page_sorts_tasks_by_exam_number_and_shows_exam_number_in_badge(): void
    {
        $hash = 'mnop3456';

        Cache::put("custom_random_test_{$hash}", [
            [
                'test_number' => 1,
                'topic_id' => '09',
                'topic_title' => 'Уравнения',
                'block_number' => 1,
                'zadanie_number' => 2,
                'instruction' => 'task-9',
                'task' => ['text' => 'A'],
            ],
            [
                'test_number' => 2,
                'topic_id' => '06',
                'topic_title' => 'Вычисления',
                'block_number' => 1,
                'zadanie_number' => 4,
                'instruction' => 'task-6',
                'task' => ['text' => 'B'],
            ],
            [
                'test_number' => 3,
                'topic_id' => '07',
                'topic_title' => 'Числа',
                'block_number' => 1,
                'zadanie_number' => 1,
                'instruction' => 'task-7',
                'task' => ['text' => 'C'],
            ],
        ], now()->addMinutes(5));

        $response = $this->get("/test/generator/result/{$hash}");

        $response->assertOk();
        $response->assertSeeInOrder(['task-6', 'task-7', 'task-9']);
        $response->assertSee('data-exam-number="6"', false);
    }

    public function test_result_page_renders_choice_answers_as_numbered_list_with_number_input(): void
    {
        $hash = 'qrst7890';

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '07',
            'topic_title' => 'Числа',
            'block_number' => 2,
            'zadanie_number' => 1,
            'instruction' => 'Выберите верный вариант',
            'task' => [
                'options' => ['opt A', 'opt B', 'opt C', 'opt D'],
            ],
        ]], now()->addMinutes(5));

        $response = $this->get("/test/generator/result/{$hash}");

        $response->assertOk();
        $response->assertSee('1. opt A');
        $response->assertSee('2. opt B');
        $response->assertSee('3. opt C');
        $response->assertSee('4. opt D');
        $response->assertSee('Введите номер ответа');
        $response->assertDontSee('type="radio"', false);
    }

    public function test_result_page_renders_statements_for_topic_19_tasks(): void
    {
        $hash = 'uvwx1234';

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '19',
            'topic_title' => 'Анализ геометрических высказываний',
            'block_number' => 2,
            'zadanie_number' => 5,
            'instruction' => 'Укажите номера верных утверждений.',
            'type' => 'statements',
            'task' => [
                'statements' => [
                    ['id' => 29, 'text' => 'Утверждение A'],
                    ['id' => 30, 'text' => 'Утверждение B'],
                ],
            ],
        ]], now()->addMinutes(5));

        $response = $this->get("/test/generator/result/{$hash}");

        $response->assertOk();
        $response->assertSee('1. Утверждение A');
        $response->assertSee('2. Утверждение B');
        $response->assertSee('Введите номера верных утверждений');
    }

    public function test_result_page_renders_only_three_statements_for_topic_19(): void
    {
        $hash = 'yzab5678';

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '19',
            'topic_title' => 'Анализ геометрических высказываний',
            'block_number' => 1,
            'zadanie_number' => 3,
            'instruction' => 'Укажите номера верных утверждений.',
            'type' => 'statements',
            'task' => [
                'statements' => [
                    ['id' => 53, 'text' => 'A'],
                    ['id' => 54, 'text' => 'B'],
                    ['id' => 55, 'text' => 'C'],
                    ['id' => 56, 'text' => 'D'],
                ],
            ],
        ]], now()->addMinutes(5));

        $response = $this->get("/test/generator/result/{$hash}");

        $response->assertOk();
        $response->assertSee('1. A');
        $response->assertSee('2. B');
        $response->assertSee('3. C');
        $response->assertDontSee('4. D');
        $response->assertDontSee('53. A');
    }

    public function test_result_page_wraps_latex_like_options_with_math_delimiters(): void
    {
        $hash = 'cdef9012';

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '07',
            'topic_title' => 'Числа, координатная прямая',
            'block_number' => 1,
            'zadanie_number' => 15,
            'instruction' => 'Одно из чисел отмечено на прямой точкой A. Какое это число?',
            'task' => [
                'options' => ['\\frac{75}{23}', '\\frac{85}{23}', '\\frac{97}{23}', '\\frac{110}{23}'],
            ],
        ]], now()->addMinutes(5));

        $response = $this->get("/test/generator/result/{$hash}");

        $response->assertOk();
        $response->assertSee('1. $\\frac{75}{23}$');
        $response->assertSee('2. $\\frac{85}{23}$');
    }

    public function test_student_sees_selectable_options_instead_of_teacher_number_input(): void
    {
        $hash = 'ghij3456';
        $student = $this->userWithRole('student');

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '07',
            'topic_title' => 'Числа, координатная прямая',
            'block_number' => 1,
            'zadanie_number' => 15,
            'instruction' => 'Выберите верный вариант.',
            'task' => [
                'options' => ['\\frac{75}{23}', '\\frac{85}{23}', '\\frac{97}{23}', '\\frac{110}{23}'],
            ],
        ]], now()->addMinutes(5));

        $response = $this->actingAs($student)->get("/test/generator/result/{$hash}");

        $response->assertOk();
        $response->assertSee('type="radio"', false);
        $response->assertDontSee('Введите номер ответа');
    }

    public function test_custom_hash_is_rendered_via_oge_route_with_same_template(): void
    {
        $hash = 'klmn7890';
        $student = $this->userWithRole('student');

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '15',
            'topic_title' => 'Треугольники',
            'block_number' => 2,
            'zadanie_number' => 3,
            'instruction' => 'УНИКАЛЬНЫЙ МАРКЕР КАСТОМ',
            'type' => 'word_problem',
            'task' => [
                'text' => 'Текст кастомной задачи',
            ],
        ]], now()->addMinutes(5));

        $response = $this->actingAs($student)->get("/oge/{$hash}");

        $response->assertOk();
        $response->assertSee('Вариант №');
        $response->assertSee('УНИКАЛЬНЫЙ МАРКЕР КАСТОМ');
    }

    public function test_custom_hash_uses_exam_task_numbers_not_sequential_indices(): void
    {
        $hash = 'opqr1234';
        $student = $this->userWithRole('student');

        Cache::put("custom_random_test_{$hash}", [
            [
                'test_number' => 1,
                'topic_id' => '13',
                'topic_title' => 'Неравенства',
                'block_number' => 1,
                'zadanie_number' => 2,
                'instruction' => 'Неравенства',
                'type' => 'graphic',
                'task' => [
                    'expression' => 'x > 1',
                    'options' => ['(1; +\\infty)', '(-\\infty; 1]'],
                ],
            ],
            [
                'test_number' => 2,
                'topic_id' => '15',
                'topic_title' => 'Треугольники',
                'block_number' => 1,
                'zadanie_number' => 1,
                'instruction' => 'Треугольники',
                'type' => 'word_problem',
                'task' => [
                    'text' => 'Найдите сторону',
                ],
            ],
        ], now()->addMinutes(5));

        $response = $this->actingAs($student)->get("/oge/{$hash}");

        $response->assertOk();
        $response->assertSee('data-task-number="13"', false);
        $response->assertSee('data-task-number="15"', false);
        $response->assertDontSee('data-task-number="6"', false);
        $response->assertDontSee('data-task-number="7"', false);
    }
}
