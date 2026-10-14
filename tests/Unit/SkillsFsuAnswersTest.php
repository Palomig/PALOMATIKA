<?php

namespace Tests\Unit;

use App\Services\TaskAnswerResolver;
use PHPUnit\Framework\TestCase;

/**
 * Тема «Формулы сокращённого умножения»: эталон засчитывается, а переписанное
 * условие — нет. Иначе в «Раскройте скобки» ученик сдал бы само условие, а в
 * «Разложите на множители» — исходный многочлен: значение-то то же.
 */
class SkillsFsuAnswersTest extends TestCase
{
    public function test_reference_is_accepted_and_condition_is_not(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../../database/data/skills/fsu.json'), true);
        $resolver = new TaskAnswerResolver();
        $tasks = $data['zadaniya'][0]['tasks'];

        $this->assertCount(400, $tasks);
        foreach ($tasks as $task) {
            $answer = (string) $task['answer'];
            $this->assertTrue($resolver->isCorrect($answer, $answer), "эталон #{$task['id']}: {$answer}");

            preg_match('/\$(.*)\$/u', $task['expression'], $m);
            $condition = str_replace('\cdot', '*', $m[1]);
            $this->assertNotTrue(
                $resolver->isCorrect($condition, $answer),
                "условие засчиталось как ответ #{$task['id']}: {$condition} → {$answer}"
            );
        }
    }
}
