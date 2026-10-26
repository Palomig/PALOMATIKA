<?php

namespace Tests\Unit;

use App\Support\ValueTable;
use PHPUnit\Framework\TestCase;

class ValueTableTest extends TestCase
{
    private const TASK = 'Заполните таблицу значений функции $y=-4x+3$ и постройте её график. '
        . 'В ответ запишите значения $y$ через точку с запятой. '
        . '$\begin{array}{|c|c|c|c|c|c|}\hline x&-2&-1&0&1&2\\\\\hline y&\phantom{-00}&\phantom{-00}&\phantom{-00}&\phantom{-00}&\phantom{-00}\\\\\hline\end{array}$';

    public function test_table_task_is_split_into_lead_and_x_values(): void
    {
        $t = ValueTable::parse(self::TASK);

        $this->assertSame(['-2', '-1', '0', '1', '2'], $t['xs']);
        $this->assertSame('Заполните таблицу значений функции $y=-4x+3$ и постройте её график.', $t['lead'],
            'без формулы таблицы и без подсказки про точку с запятой — поля ввода прямо в таблице');
    }

    public function test_every_graph_task_of_the_bank_parses(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../../database/data/skills/graphs.json'), true);
        foreach ($data['zadaniya'] as $z) {
            foreach ($z['tasks'] as $task) {
                $t = ValueTable::parse($task['expression']);
                $this->assertNotNull($t, "#{$task['id']}");
                $this->assertCount(count(explode('|', $task['answer'])), $t['xs'], "#{$task['id']}: клеток столько же, сколько значений");
                $this->assertStringNotContainsString('array', $t['lead']);
            }
        }
    }

    public function test_ordinary_task_is_not_a_table(): void
    {
        $this->assertNull(ValueTable::parse('Решите уравнение: $x^{2}-5x+6=0$'));
        $this->assertNull(ValueTable::parse(null));
    }
}
