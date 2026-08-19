<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Services\LessonTaskPickerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Подтипы задания видны и при наборе заданий на урок, а не только в
 * разделе «2я часть».
 */
class LessonPickerShowsSubtypesTest extends TestCase
{
    use RefreshDatabase;

    private function group(array $subtypes, array $tasks): void
    {
        $group = TaskGroup::create([
            'bank' => 'oge', 'topic' => '23', 'block_number' => 1, 'block_title' => 'Треугольники',
            'zadanie_number' => 1, 'position' => 1, 'type' => 'word_problem', 'source' => 'fipi',
            'payload' => array_filter([
                'number' => 1,
                'instruction' => 'Высота к гипотенузе',
                'type' => 'word_problem',
                'subtypes' => $subtypes ?: null,
            ]),
        ]);

        foreach ($tasks as $i => [$subtype, $text]) {
            Task::create([
                'task_group_id' => $group->id, 'position' => $i, 'type' => 'word_problem', 'source' => 'fipi',
                'payload' => array_filter([
                    'id' => $i + 1,
                    'text' => $text,
                    'task_type' => 'word_problem',
                    'answer' => '12',
                    'subtype' => $subtype,
                ], static fn ($v) => $v !== null),
                'answer' => '12',
            ]);
        }
    }

    public function test_tasks_carry_their_subtype_label(): void
    {
        $this->group(['Даны катеты', 'Дана проекция'], [
            [0, 'Катеты равны 15 и 20.'],
            [0, 'Катеты равны 18 и 24.'],
            [1, 'Найдите AB, если AH=9.'],
        ]);

        $tasks = app(LessonTaskPickerService::class)->tasks('oge', ['topic_id' => '23'], 'part2');

        $this->assertCount(3, $tasks);
        $this->assertSame(['Даны катеты', 'Даны катеты', 'Дана проекция'], array_column($tasks, 'subtype_label'));
        // Ключ подтипа уникален внутри задания и не смешивает разные серии.
        $this->assertSame(['1.0', '1.0', '1.1'], array_column($tasks, 'subtype_key'));
        // Задание осталось одним блоком — подтип это уровень внутри него.
        $this->assertSame([1, 1, 1], array_column($tasks, 'group_key'));
    }

    public function test_group_without_subtypes_stays_flat(): void
    {
        $this->group([], [[null, 'Катеты равны 15 и 20.'], [null, 'Катеты равны 18 и 24.']]);

        $tasks = app(LessonTaskPickerService::class)->tasks('oge', ['topic_id' => '23'], 'part2');

        $this->assertCount(2, $tasks);
        $this->assertSame([null, null], array_column($tasks, 'subtype_key'));
    }
}
