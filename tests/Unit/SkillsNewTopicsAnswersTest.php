<?php

namespace Tests\Unit;

use App\Services\TaskAnswerResolver;
use PHPUnit\Framework\TestCase;

/**
 * Уравнения, степени и графики (блокнот Джарвиса #61–63): каждый эталон
 * засчитывается собственной проверкой сайта, а то, что ученик мог бы сдать
 * вместо решения, — нет.
 */
class SkillsNewTopicsAnswersTest extends TestCase
{
    private function tasks(string $file): array
    {
        $data = json_decode(file_get_contents(__DIR__ . "/../../database/data/skills/{$file}.json"), true);

        return array_merge(...array_map(fn ($z) => $z['tasks'], $data['zadaniya']));
    }

    public function test_equation_roots_are_accepted_in_any_order(): void
    {
        $resolver = new TaskAnswerResolver();
        $tasks = $this->tasks('equations');
        $this->assertCount(1200, $tasks);
        foreach ($tasks as $task) {
            $answer = (string) $task['answer'];
            $this->assertTrue($resolver->isCorrect($answer, $answer), "эталон #{$task['id']}: {$answer}");
            $roots = explode(';', $answer);
            if (count($roots) > 1) {
                $this->assertTrue($resolver->isCorrect(implode('; ', array_reverse($roots)), $answer),
                    "корни в другом порядке #{$task['id']}: {$answer}");
                $this->assertNotTrue($resolver->isCorrect($roots[0], $answer), "один корень из нескольких #{$task['id']}");
            }
        }
    }

    public function test_powers_condition_is_not_an_answer(): void
    {
        $resolver = new TaskAnswerResolver();
        $tasks = $this->tasks('powers');
        $this->assertCount(700, $tasks);
        foreach ($tasks as $task) {
            $answer = (string) $task['answer'];
            $this->assertTrue($resolver->isCorrect($answer, $answer), "эталон #{$task['id']}: {$answer}");

            preg_match('/\$(.*)\$/u', $task['expression'], $m);
            $condition = strtr($m[1], ['\cdot' => '*', ':' => '/', '{,}' => '.', '{' => '(', '}' => ')']);
            if (str_contains($condition, '\\')) {
                continue;   // \dfrac и \left — ученик так не напишет
            }
            $this->assertNotTrue($resolver->isCorrect($condition, $answer),
                "условие засчиталось как ответ #{$task['id']}: {$condition} → {$answer}");
        }
    }

    public function test_graph_tables_are_checked_in_order(): void
    {
        $resolver = new TaskAnswerResolver();
        foreach ($this->tasks('graphs') as $task) {
            $answer = (string) $task['answer'];
            $cells = array_map('trim', explode('|', $answer));
            $this->assertTrue($resolver->isCorrect(implode('; ', $cells), $answer), "эталон #{$task['id']}: {$answer}");
            if (count(array_unique($cells)) > 1) {
                $reversed = implode('; ', array_reverse($cells));
                if ($reversed !== implode('; ', $cells)) {
                    $this->assertFalse($resolver->isCorrect($reversed, $answer), "таблица задом наперёд #{$task['id']}");
                }
            }
        }
    }
}
