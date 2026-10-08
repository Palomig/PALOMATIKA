<?php

namespace Tests\Unit;

use App\Services\TaskBankResolver;
use PHPUnit\Framework\TestCase;

/**
 * Поле ответа ученика на уроке выбирается по серии задач.
 */
class TaskAnswerFieldTest extends TestCase
{
    public function test_numeric_fractions_with_integers_get_fraction_field(): void
    {
        $this->assertSame(['fraction', false, false], TaskBankResolver::answerField(['2/3', '3/5', '1', '5/7']));
    }

    public function test_mixed_numbers_get_mixed_field(): void
    {
        $this->assertSame(['mixed', false, false], TaskBankResolver::answerField(['2 7/11', '3/4', '5']));
    }

    public function test_reducing_algebraic_fractions_gets_letters(): void
    {
        $this->assertSame(['fraction', true, true],
            TaskBankResolver::answerField(['5/11', '2', '(a+5)/(a-5)', '-8(a+b)/(a-b)']));
    }

    public function test_decimals_sets_roots_and_functions_stay_plain(): void
    {
        foreach ([['0,5', '1/2'], ['-3;-2;1'], ['2√3', '1/2'], ['cos(x)-sin(x)', '1/x'], ['12', '15'], []] as $answers) {
            $this->assertSame('text', TaskBankResolver::answerField($answers)[0], implode(' | ', $answers));
        }
    }
}
