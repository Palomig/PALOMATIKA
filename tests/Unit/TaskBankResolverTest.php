<?php

namespace Tests\Unit;

use App\Services\LessonTaskPickerService;
use App\Services\TaskBankResolver;
use DomainException;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Тесты используют реальные JSON-файлы банков (storage/app/tasks/...).
 * Считаем существование topic_06.json (ОГЭ) и алгебру 7 класса базовой инвариантой.
 */
class TaskBankResolverTest extends TestCase
{
    private function resolver(): TaskBankResolver
    {
        return new TaskBankResolver();
    }

    public function test_oge_resolves_expression_task(): void
    {
        $r = $this->resolver()->resolve('oge', [
            'topic_id'        => '06',
            'zadanie_number'  => 1,
            'task_id'         => 1,
        ]);

        $this->assertSame('expression', $r['type']);
        $this->assertNotSame('', $r['expression']);
        $this->assertNotSame('', $r['answer']);
        $this->assertStringContainsString('ОГЭ', $r['source_label']);
        $this->assertArrayHasKey('raw', $r);
    }

    public function test_oge_unknown_zadanie_throws(): void
    {
        $this->expectException(DomainException::class);
        $this->resolver()->resolve('oge', [
            'topic_id'        => '06',
            'zadanie_number'  => 9999,
            'task_id'         => 1,
        ]);
    }

    public function test_oge_choice_returns_options(): void
    {
        // topic_07 zadanie 1 — choice type (from inspection)
        $r = $this->resolver()->resolve('oge', [
            'topic_id'        => '07',
            'zadanie_number'  => 1,
            'task_id'         => 1,
        ]);

        $this->assertSame('choice', $r['type']);
        $this->assertArrayHasKey('options', $r);
        $this->assertNotEmpty($r['options']);
        $this->assertArrayHasKey('id', $r['options'][0]);
        $this->assertArrayHasKey('label', $r['options'][0]);
    }

    public function test_alg_skill_resolves_simple_level_task(): void
    {
        $r = $this->resolver()->resolve('alg-skill', [
            'grade'      => 7,
            'skill_slug' => 'signed-add',
            'level_id'   => 'simple',
            'task_id'    => 1,
        ]);

        $this->assertSame('expression', $r['type']);
        $this->assertSame('-8 + 6', $r['expression']);
        $this->assertSame('-2', $r['answer']);
        $this->assertStringContainsString('Сложение', $r['source_label']);
    }

    public function test_alg_skill_unknown_slug_throws(): void
    {
        $this->expectException(DomainException::class);
        $this->resolver()->resolve('alg-skill', [
            'grade'      => 7,
            'skill_slug' => 'nonexistent-slug',
            'level_id'   => 'simple',
            'task_id'    => 1,
        ]);
    }

    /**
     * Задачи 2-й части (тема 20, word_problem) резолвятся по refs,
     * которые отдаёт picker для раздела part2 — без правок резолвера.
     */
    public function test_resolves_part2_task_topic_20(): void
    {
        $picker = new LessonTaskPickerService();
        $tasks = $picker->tasks('oge', ['topic_id' => '20'], 'part2');
        $this->assertNotEmpty($tasks, 'Picker должен отдавать задачи темы 20 в разделе part2');

        $t = $tasks[0];
        $resolved = $this->resolver()->resolve('oge', [
            'topic_id'       => '20',
            'zadanie_number' => $t['zadanie_number'],
            'task_id'        => $t['id'],
        ]);

        // word_problem нормализуется к expression (текст условия + auto-check ответа)
        $this->assertSame('expression', $resolved['type']);
        $this->assertNotSame('', $resolved['expression']);
        $this->assertNotSame('', $resolved['answer']);
        $this->assertStringContainsString('Тема 20', $resolved['source_label']);
    }

    /**
     * «Новые задания» (тема 09) живут в zadanie с number = 0 — refs picker'а
     * содержат zadanie_number: 0. Фиксируем, что findTaskInBlocks находит его
     * (сравнение (string) 0 === '0').
     */
    public function test_resolves_new_zadanie_task_with_zadanie_number_zero(): void
    {
        $picker = new LessonTaskPickerService();
        $tasks = $picker->tasks('oge', ['topic_id' => '09'], 'new');
        $this->assertNotEmpty($tasks, 'Picker должен отдавать «Новые задания» темы 09 в разделе new');

        $t = $tasks[0];
        $this->assertSame(0, $t['zadanie_number'], 'Новые задания лежат в zadanie с number = 0');

        $resolved = $this->resolver()->resolve('oge', [
            'topic_id'       => '09',
            'zadanie_number' => $t['zadanie_number'],
            'task_id'        => $t['id'],
        ]);

        $this->assertSame('expression', $resolved['type']);
        $this->assertNotSame('', $resolved['expression']);
        $this->assertNotSame('', $resolved['answer']);
        $this->assertStringContainsString('Задание 0.', $resolved['source_label']);
    }

    /**
     * Тема 24 (геометрические доказательства) — задачи без эталонного ответа.
     * По дизайну (v1, «2-я часть на уроке») они ДОЛЖНЫ попадать в picker
     * в разделе part2: ученик вписывает ответ, учитель видит его без ✓/✗
     * (submitAnswer отдаёт is_correct = null при correct_answer === '').
     */
    public function test_picker_returns_topic_24_proof_tasks_without_answer_in_part2(): void
    {
        $picker = new LessonTaskPickerService();
        $tasks = $picker->tasks('oge', ['topic_id' => '24'], 'part2');

        $this->assertNotEmpty($tasks, 'Picker должен отдавать задачи темы 24 в разделе part2');
        foreach ($tasks as $t) {
            $this->assertSame('', $t['answer'], 'Задачи темы 24 — без эталонного ответа');
            $this->assertNotSame('', $t['expression'], 'Условие (текст) обязано присутствовать');
        }

        // Resolve по refs picker'а работает без правок резолвера:
        // geometry нормализуется к expression, пустой answer допустим.
        $t = $tasks[0];
        $resolved = $this->resolver()->resolve('oge', [
            'topic_id'       => '24',
            'zadanie_number' => $t['zadanie_number'],
            'task_id'        => $t['id'],
        ]);

        $this->assertSame('expression', $resolved['type']);
        $this->assertNotSame('', $resolved['expression']);
        $this->assertSame('', $resolved['answer']);
        $this->assertStringContainsString('Тема 24', $resolved['source_label']);
    }

    public function test_unknown_bank_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->resolver()->resolve('unknown-bank', []);
    }

    public function test_missing_refs_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->resolver()->resolve('oge', ['topic_id' => '06']); // missing zadanie_number, task_id
    }
}
