<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Services\LessonTaskPickerService;
use App\Services\TaskAnswerResolver;
use App\Services\TaskBankRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Скилл «Арифметический квадратный корень»: 8 класс, четыре блока по 100.
 *
 * Три уровня сложности и отдельная тема «избавление от иррациональности»
 * внутри третьего уровня. Часть заданий буквенные — с подстановкой
 * «при a = 2», и в них проверять надо не только число, но и разметку:
 * условие смешивает текст и формулы, и знак «√» вместо \sqrt KaTeX не поймёт.
 */
class SkillsSquareRootsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();
        $this->artisan('tasks:import-skills')->assertSuccessful();
        Cache::flush();
    }

    public function test_four_blocks_of_one_hundred(): void
    {
        $group = TaskGroup::where('bank', 'skills')->where('topic', '03')->first();

        $this->assertNotNull($group);
        $this->assertSame('8 класс', $group->payload['title']);
        $this->assertSame([
            'Уровень 1 — извлечение корня',
            'Уровень 2 — вынесение множителя, умножение и деление',
            'Уровень 3 — действия с корнями',
            'Тема — избавление от иррациональности',
        ], $group->payload['subtypes']);

        $tasks = Task::where('task_group_id', $group->id)->get();
        $this->assertCount(400, $tasks);

        $perBlock = $tasks->groupBy(fn (Task $t) => $t->payload['subtype'])->map->count();
        $this->assertSame([100, 100, 100, 100], [$perBlock[0], $perBlock[1], $perBlock[2], $perBlock[3]]);
    }

    public function test_every_block_has_tasks_with_letters(): void
    {
        $tasks = $this->tasks();

        for ($block = 0; $block < 4; $block++) {
            $withLetters = $tasks
                ->filter(fn (Task $t) => $t->payload['subtype'] === $block)
                ->filter(fn (Task $t) => str_contains($t->payload['expression'], ' при '));
            $this->assertGreaterThanOrEqual(20, $withLetters->count(),
                "в блоке {$block} почти нет буквенных заданий");
        }
    }

    public function test_conditions_are_katex_and_never_use_a_bare_radical_sign(): void
    {
        foreach ($this->tasks() as $task) {
            $expr = (string) $task->payload['expression'];
            $this->assertStringStartsWith('$', $expr);
            $this->assertStringContainsString('\\', $expr, "условие без формулы: {$expr}");
            // «√» — не команда LaTeX: KaTeX покажет вместо формулы ошибку.
            $this->assertStringNotContainsString('√', $expr, "знак корня вместо \\sqrt: {$expr}");
        }
    }

    /**
     * Ответы с корнями сверяет прежний разборщик ОГЭ, и равные записи
     * обязаны приниматься: «6√2» и «√72» — один ответ, а десятичное
     * приближение — нет.
     */
    public function test_answers_accept_equal_forms_and_reject_approximations(): void
    {
        $resolver = new TaskAnswerResolver();

        foreach ($this->tasks() as $task) {
            $answer = (string) $task->answer;
            $this->assertNotSame('', $answer);
            $this->assertTrue($resolver->isCorrect($answer, $answer) === true,
                "эталон не сверяется сам с собой: {$answer}");
        }

        $this->assertTrue($resolver->isCorrect('√72', '6√2'));
        $this->assertTrue($resolver->isCorrect('6sqrt(2)', '6√2'));
        $this->assertFalse($resolver->isCorrect('8,485', '6√2'));
    }

    public function test_levels_reach_the_picker(): void
    {
        $tasks = app(LessonTaskPickerService::class)->tasks('skills', ['topic_id' => '03']);

        $this->assertCount(400, $tasks);
        $this->assertSame(['8 класс'], array_values(array_unique(array_column($tasks, 'group_label'))));
        $this->assertSame(4, count(array_unique(array_column($tasks, 'subtype_key'))));
        $this->assertContains('Тема — избавление от иррациональности',
            array_column($tasks, 'subtype_label'));
    }

    private function tasks()
    {
        return Task::whereHas('group', fn ($q) => $q->where('bank', 'skills')->where('topic', '03'))->get();
    }
}
