<?php

namespace Tests\Unit;

use App\Services\TaskAnswerResolver;
use PHPUnit\Framework\TestCase;

/**
 * Таблица значений функции (скилл «Графики»): ответ — строка y по столбцам.
 * Сверяется по порядку: у параболы значения повторяются, и множеством
 * «4 | 1 | 0 | 1 | 4» превратилось бы в {0, 1, 4}.
 */
class TableAnswerTest extends TestCase
{
    private function check(string $user, string $correct): ?bool
    {
        return (new TaskAnswerResolver())->isCorrect($user, $correct);
    }

    public function test_separators_semicolon_bar_and_spaces(): void
    {
        $this->assertTrue($this->check('-7; -5; -3; -1; 1', '-7 | -5 | -3 | -1 | 1'));
        $this->assertTrue($this->check('-7|-5|-3|-1|1', '-7 | -5 | -3 | -1 | 1'));
        $this->assertTrue($this->check('-7 -5 -3 -1 1', '-7 | -5 | -3 | -1 | 1'));
        $this->assertTrue($this->check('−7; −5; −3; −1; 1', '-7 | -5 | -3 | -1 | 1'));
    }

    public function test_order_and_repeats_matter(): void
    {
        $this->assertTrue($this->check('4;1;0;1;4', '4 | 1 | 0 | 1 | 4'));
        $this->assertFalse($this->check('0;1;4', '4 | 1 | 0 | 1 | 4'));
        $this->assertFalse($this->check('1;4;0;1;4', '4 | 1 | 0 | 1 | 4'));
    }

    public function test_fractions_and_decimals(): void
    {
        $this->assertTrue($this->check('1/2; 2; 0,5', '1/2 | 2 | 1/2'));
        $this->assertFalse($this->check('2/4; 2; 1/2', '1/2 | 2 | 1/2'), 'дробь в ответе сокращают');
        $this->assertTrue($this->check('-2,5; 0; 2,5', '-5/2 | 0 | 5/2'));
    }

    public function test_wrong_count_is_wrong(): void
    {
        $this->assertFalse($this->check('-7; -5; -3', '-7 | -5 | -3 | -1 | 1'));
        $this->assertFalse($this->check('', '-7 | -5 | -3 | -1 | 1'));
    }
}
