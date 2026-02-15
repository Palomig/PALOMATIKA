<?php

namespace Tests\Unit;

use App\Services\TaskAnswerResolver;
use PHPUnit\Framework\TestCase;

class TaskAnswerResolverTest extends TestCase
{
    public function test_resolves_matching_set_answer(): void
    {
        $resolver = new TaskAnswerResolver();

        $answer = $resolver->resolveFromVariantTask([
            'is_matching_set' => true,
            'tasks' => [
                ['options' => ['y=x']],
                ['options' => ['y=x^2']],
                ['options' => ['y=2x']],
            ],
            'formulas' => ['y=x^2', 'y=2x', 'y=x'],
        ]);

        $this->assertSame('312', $answer);
    }

    public function test_resolves_statements_answer_from_truthy_flags(): void
    {
        $resolver = new TaskAnswerResolver();

        $answer = $resolver->resolveFromTaskAndZadanie([
            'type' => 'statements',
            'statements' => [
                ['display_number' => 1, 'is_true' => true],
                ['display_number' => 2, 'is_true' => false],
                ['display_number' => 3, 'is_true' => true],
            ],
        ], []);

        $this->assertSame('13', $answer);
    }

    public function test_normalized_numeric_comparison_accepts_decimal_comma(): void
    {
        $resolver = new TaskAnswerResolver();

        $this->assertTrue($resolver->isCorrect('1,50', '1.5'));
        $this->assertTrue($resolver->isCorrect('  003  ', '3'));
        $this->assertFalse($resolver->isCorrect('2', '3'));
    }

    public function test_digit_sequence_comparison_strips_separators(): void
    {
        $resolver = new TaskAnswerResolver();

        $this->assertTrue($resolver->isCorrect('1, 3', '13'));
        $this->assertTrue($resolver->isCorrect('2 4 1', '241'));
        $this->assertFalse($resolver->isCorrect('241', '214'));
    }
}
