<?php

namespace Tests\Unit;

use App\Support\TaskConditionFormatter;
use PHPUnit\Framework\TestCase;

class TaskConditionFormatterTest extends TestCase
{
    public function test_strips_outer_math_delimiters(): void
    {
        $this->assertSame('x+1', TaskConditionFormatter::bareExpression('$x+1$'));
        $this->assertSame('x+1', TaskConditionFormatter::bareExpression('$$x+1$$'));
        $this->assertSame('x+1', TaskConditionFormatter::bareExpression('\\(x+1\\)'));
        $this->assertSame('x+1', TaskConditionFormatter::bareExpression('\\[x+1\\]'));
        $this->assertSame('x+1', TaskConditionFormatter::bareExpression('x+1'));
        $this->assertSame('', TaskConditionFormatter::bareExpression(''));
        $this->assertSame('$', TaskConditionFormatter::bareExpression('$'));
    }

    public function test_drops_expression_already_present_in_text(): void
    {
        $expression = '$\\dfrac{(\\sqrt{8}-\\sqrt{4})\\sqrt{2}}{2}$';
        $text = 'Упростите выражение ' . $expression . '.';

        $result = TaskConditionFormatter::compose('Упростите выражение', $text, $expression);

        $this->assertSame('', $result['instruction']);
        $this->assertSame($text, $result['text']);
        $this->assertSame('', $result['expression']);
    }

    public function test_keeps_expression_when_text_does_not_contain_it(): void
    {
        $result = TaskConditionFormatter::compose('', 'Решите уравнение', 'x^2-4=0');

        $this->assertSame('', $result['instruction']);
        $this->assertSame('Решите уравнение', $result['text']);
        $this->assertSame('x^2-4=0', $result['expression']);
    }

    public function test_instruction_without_text_becomes_text(): void
    {
        $result = TaskConditionFormatter::compose('Найдите значение выражения', '', '\\dfrac{a}{b}');

        $this->assertSame('', $result['instruction']);
        $this->assertSame('Найдите значение выражения', $result['text']);
        $this->assertSame('\\dfrac{a}{b}', $result['expression']);
    }

    public function test_keeps_instruction_when_text_is_a_different_sentence(): void
    {
        $result = TaskConditionFormatter::compose('Упростите выражение', 'В ответе укажите целое число', '');

        $this->assertSame('Упростите выражение', $result['instruction']);
        $this->assertSame('В ответе укажите целое число', $result['text']);
    }
}
