<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Services\AlgebraicAnswerComparator;
use App\Services\LessonTaskPickerService;
use App\Services\TaskAnswerResolver;
use App\Services\TaskBankRepository;
use App\Services\TaskBankResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Скилл «Сокращение дробей»: класс → уровень → примеры.
 *
 * Внутри скилла деление по классам (пока 8-й), внутри класса — три уровня
 * сложности по сотне примеров. В пикере это спойлер класса с подспойлерами
 * уровней, поэтому проверяем не только числа в базе, но и разметку подтипов:
 * без неё триста карточек свалятся в одну кучу.
 */
class SkillsFractionReductionTest extends TestCase
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

    public function test_topic_holds_three_levels_of_one_hundred(): void
    {
        $group = TaskGroup::where('bank', 'skills')->where('topic', '02')->first();

        $this->assertNotNull($group);
        $this->assertSame('8 класс', $group->payload['title']);
        $this->assertSame(8, $group->payload['grade'], 'класс лежит в payload — по нему строится разбивка');
        $this->assertCount(3, $group->payload['subtypes']);

        $tasks = Task::where('task_group_id', $group->id)->get();
        $this->assertCount(300, $tasks);

        $perLevel = $tasks->groupBy(fn (Task $t) => $t->payload['subtype'])->map->count();
        $this->assertSame([100, 100, 100], [$perLevel[0], $perLevel[1], $perLevel[2]]);
    }

    public function test_every_answer_survives_its_own_check(): void
    {
        $comparator = new AlgebraicAnswerComparator();
        $resolver = new TaskAnswerResolver();

        $tasks = Task::whereHas('group', fn ($q) => $q->where('bank', 'skills')->where('topic', '02'))->get();
        foreach ($tasks as $task) {
            $answer = (string) $task->answer;
            $this->assertNotSame('', $answer, 'задача без ответа в пикер не попадёт');
            $this->assertTrue($resolver->isCorrect($answer, $answer) === true,
                "эталон не сверяется сам с собой: {$answer}");

            if ($comparator->looksAlgebraic($answer)) {
                // Пробелы и LaTeX-дробь ученик пишет как придётся — это тот же ответ.
                $spaced = preg_replace('/([+\-])/', ' $1 ', $answer);
                $this->assertTrue($resolver->isCorrect($spaced, $answer),
                    "ответ с пробелами не принят: {$spaced} против {$answer}");
            }
        }
    }

    public function test_levels_are_visible_as_subtypes_in_the_picker(): void
    {
        $tasks = app(LessonTaskPickerService::class)->tasks('skills', ['topic_id' => '02']);

        $this->assertCount(300, $tasks);
        $this->assertSame(['8 класс'], array_values(array_unique(array_column($tasks, 'group_label'))));

        $levels = array_values(array_unique(array_column($tasks, 'subtype_label')));
        $this->assertSame([
            'Уровень 1 — одночлены и степени',
            'Уровень 2 — общий множитель и формулы',
            'Уровень 3 — знаки, группировка, кубы',
        ], $levels, 'уровни обязаны быть подтипами, иначе 300 карточек лягут одной кучей');

        // Ключ подтипа уникален внутри задания — по нему картотека и группирует.
        $this->assertSame(3, count(array_unique(array_column($tasks, 'subtype_key'))));
    }

    public function test_examples_look_like_fractions_and_reduce(): void
    {
        $tasks = app(LessonTaskPickerService::class)->tasks('skills', ['topic_id' => '02']);

        foreach ($tasks as $task) {
            $expr = (string) $task['expression'];
            $this->assertStringStartsWith('$', $expr, 'условие — формула KaTeX');
            $this->assertTrue(str_contains($expr, '\dfrac') || str_contains($expr, ' : '),
                "условие не полноразмерная дробь и не частное: {$expr}");
            // Сокращённая запись короче исходной — иначе сокращать было нечего.
            $this->assertLessThan(mb_strlen($expr), mb_strlen((string) $task['answer']) + 2, $expr);
        }
    }

    public function test_task_resolves_into_homework_with_its_answer(): void
    {
        $picker = app(LessonTaskPickerService::class);
        $tasks = $picker->tasks('skills', ['topic_id' => '02']);
        $third = $tasks[250];

        $resolved = app(TaskBankResolver::class)->resolve('skills', [
            'topic_id' => '02',
            'zadanie_number' => $third['zadanie_number'],
            'task_id' => $third['id'],
        ]);

        $this->assertSame('expression', $resolved['type']);
        $this->assertSame($third['expression'], $resolved['expression']);
        $this->assertSame($third['answer'], $resolved['answer']);
        $this->assertSame('Скиллы · Сокращение дробей · Задание 8.' . $third['id'], $resolved['source_label']);
    }
}
