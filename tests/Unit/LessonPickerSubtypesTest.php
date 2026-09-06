<?php

namespace Tests\Unit;

use App\Services\LessonTaskPickerService;
use Tests\TestCase;

class LessonPickerSubtypesTest extends TestCase
{
    /** Банк темы 15: одна группа, задачи размечены двумя подтипами. */
    private function service(): LessonTaskPickerService
    {
        return new class extends LessonTaskPickerService {
            protected function resolveBlocks(string $bank, array $refs): array
            {
                return [[
                    'number' => 1,
                    'zadaniya' => [[
                        'number' => 7,
                        'instruction' => 'Теорема Пифагора: найти гипотенузу или катет',
                        'type' => 'fipi',
                        'subtypes' => ['Найти гипотенузу по катетам', 'Найти катет по катету и гипотенузе'],
                        'tasks' => [
                            ['id' => 1, 'html' => '<p>Катеты равны 7 и 24. Найдите гипотенузу.</p>', 'answer' => '25', 'subtype' => 0],
                            ['id' => 2, 'html' => '<p>Катет и гипотенуза равны 7 и 25. Найдите другой катет.</p>', 'answer' => '24', 'subtype' => 1],
                            ['id' => 3, 'html' => '<p>Катеты равны 6 и 8. Найдите гипотенузу.</p>', 'answer' => '10', 'subtype' => 0],
                        ],
                    ]],
                ]];
            }
        };
    }

    public function test_picker_labels_tasks_of_part1_topic_with_subtypes(): void
    {
        $tasks = $this->service()->tasks('oge', ['topic_id' => '15'], 'part1');

        $this->assertCount(3, $tasks);
        $this->assertSame(['7.0', '7.1', '7.0'], array_column($tasks, 'subtype_key'));
        $this->assertSame('Найти гипотенузу по катетам', $tasks[0]['subtype_label']);
        $this->assertSame('Найти катет по катету и гипотенузе', $tasks[1]['subtype_label']);
        // Подтип — второй уровень внутри группы, сама группа не меняется.
        $this->assertSame([7, 7, 7], array_column($tasks, 'group_key'));
    }

    public function test_group_without_markup_stays_flat(): void
    {
        $service = new class extends LessonTaskPickerService {
            protected function resolveBlocks(string $bank, array $refs): array
            {
                return [[
                    'number' => 1,
                    'zadaniya' => [[
                        'number' => 4,
                        'instruction' => 'Оценить дробь между соседними целыми числами',
                        'type' => 'fipi',
                        'tasks' => [['id' => 1, 'html' => '<p>Между какими числами заключено 130/11?</p>', 'answer' => '11']],
                    ]],
                ]];
            }
        };

        $tasks = $service->tasks('oge', ['topic_id' => '07'], 'part1');

        $this->assertCount(1, $tasks);
        $this->assertNull($tasks[0]['subtype_key']);
    }
}
