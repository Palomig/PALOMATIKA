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

    public function test_two_grades_with_blocks_of_one_hundred(): void
    {
        $groups = TaskGroup::where('bank', 'skills')->where('topic', '03')->orderBy('position')->get();

        $this->assertCount(2, $groups, 'скилл живёт двумя классами: 8 и 10–11');
        $this->assertSame(['8 класс', '10–11 класс'], $groups->pluck('payload.title')->all());

        $this->assertSame([
            'Уровень 1 — извлечение корня',
            'Уровень 2 — вынесение множителя, умножение и деление',
            'Уровень 3 — действия с корнями',
            'Тема — избавление от иррациональности',
        ], $groups[0]->payload['subtypes']);
        $this->assertSame([
            'Уровень 1 — корень n-й степени',
            'Уровень 2 — степень с дробным показателем',
            'Уровень 3 — свойства степеней и корней',
        ], $groups[1]->payload['subtypes']);

        foreach ($groups as $group) {
            $perBlock = Task::where('task_group_id', $group->id)->get()
                ->groupBy(fn (Task $t) => $t->payload['subtype'])->map->count();
            $this->assertSame(array_fill(0, count($group->payload['subtypes']), 100),
                $perBlock->values()->all(), "в блоках {$group->payload['title']} не по сотне");
        }
    }

    /**
     * Буква в задании обязана требовать работы до подстановки: вынести
     * степень, сократить, применить формулу. «√p при p = 169» — это просто
     * подстановка, такие задания Стас забраковал 29.09.
     */
    public function test_letter_tasks_demand_a_transformation(): void
    {
        $trivial = [];
        foreach ($this->tasks() as $task) {
            $expr = (string) $task->payload['expression'];
            if (!str_contains($expr, ' при ')) {
                continue;
            }
            [$formula] = explode(' при ', $expr, 2);
            // Голый корень из одной буквы — ровно тот случай, когда делать нечего.
            if (preg_match('/^\$\\sqrt\{[a-z]\}\$$/u', $formula)) {
                $trivial[] = $expr;
            }
        }

        $this->assertSame([], $trivial, 'буквенное задание свелось к подстановке');
    }

    public function test_every_block_has_tasks_with_letters(): void
    {
        $tasks = $this->tasks();

        foreach (TaskGroup::where('bank', 'skills')->where('topic', '03')->get() as $group) {
            foreach (array_keys($group->payload['subtypes']) as $block) {
                $withLetters = $tasks
                    ->where('task_group_id', $group->id)
                    ->filter(fn (Task $t) => $t->payload['subtype'] === $block)
                    ->filter(fn (Task $t) => str_contains($t->payload['expression'], ' при '));
                $this->assertGreaterThanOrEqual(20, $withLetters->count(),
                    "в блоке {$block} класса {$group->payload['title']} почти нет буквенных заданий");
            }
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

        $this->assertCount(700, $tasks);
        $this->assertSame(['8 класс', '10–11 класс'],
            array_values(array_unique(array_column($tasks, 'group_label'))));
        $this->assertSame(7, count(array_unique(array_column($tasks, 'subtype_key'))));
        $this->assertContains('Тема — избавление от иррациональности',
            array_column($tasks, 'subtype_label'));
    }

    private function tasks()
    {
        return Task::whereHas('group', fn ($q) => $q->where('bank', 'skills')->where('topic', '03'))->get();
    }
}
