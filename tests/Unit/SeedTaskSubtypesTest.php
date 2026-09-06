<?php

namespace Tests\Unit;

use App\Console\Commands\SeedTaskSubtypes;
use App\Models\Task;
use App\Models\TaskGroup;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

class SeedTaskSubtypesTest extends TestCase
{
    private function invokePrivate(string $method, array $args): mixed
    {
        $m = new ReflectionMethod(SeedTaskSubtypes::class, $method);
        $m->setAccessible(true);

        return $m->invokeArgs(new SeedTaskSubtypes(), $args);
    }

    private function task(int $id, string $html): Task
    {
        $task = new Task(['payload' => ['html' => $html]]);
        $task->id = $id;

        return $task;
    }

    private function group(array $tasks): TaskGroup
    {
        $group = new TaskGroup(['payload' => []]);
        $group->setRelation('tasks', collect($tasks));

        return $group;
    }

    public function test_numerals_in_words_are_data_not_a_new_series(): void
    {
        $a = $this->invokePrivate('signature', [$this->task(1, '<p>В амфитеатре 13 рядов. Сколько мест в одиннадцатом ряду?</p>')]);
        $b = $this->invokePrivate('signature', [$this->task(2, '<p>В амфитеатре 16 рядов. Сколько мест в десятом ряду?</p>')]);
        $c = $this->invokePrivate('signature', [$this->task(3, '<p>В амфитеатре 16 рядов. Сколько всего мест?</p>')]);

        $this->assertSame($a, $b);
        $this->assertNotSame($a, $c);
    }

    public function test_rules_split_group_by_condition_text(): void
    {
        $group = $this->group([
            $this->task(1, '<p>Какая из разностей положительна?</p>'),
            $this->task(2, '<p>Какая из разностей отрицательна?</p>'),
            $this->task(3, '<p>Какая из разностей отрицательна?</p>'),
        ]);
        $rules = $this->invokePrivate('rules', [[
            ['title' => 'Положительна', 'match' => 'положительна'],
            ['title' => 'Отрицательна', 'match' => '.'],
        ]]);

        [$series, $error] = $this->invokePrivate('byRules', [$group, $rules]);

        $this->assertNull($error);
        $this->assertSame([[1], [2, 3]], $series);
    }

    public function test_unmatched_task_stops_the_group(): void
    {
        $group = $this->group([$this->task(7, '<p>Найдите площадь трапеции.</p>')]);
        $rules = $this->invokePrivate('rules', [[['title' => 'Ромб', 'match' => 'ромб']]]);

        [$series, $error] = $this->invokePrivate('byRules', [$group, $rules]);

        $this->assertSame([], $series);
        $this->assertStringContainsString('не подошла', (string) $error);
    }

    public function test_empty_subtype_stops_the_group(): void
    {
        $group = $this->group([$this->task(7, '<p>Найдите площадь ромба.</p>')]);
        $rules = $this->invokePrivate('rules', [[
            ['title' => 'Ромб', 'match' => 'ромб'],
            ['title' => 'Трапеция', 'match' => 'трапеци'],
        ]]);

        [, $error] = $this->invokePrivate('byRules', [$group, $rules]);

        $this->assertStringContainsString('Трапеция', (string) $error);
    }

    public function test_titles_list_is_not_read_as_rules(): void
    {
        $this->assertNull($this->invokePrivate('rules', [['Даны катеты', 'Даны катет и гипотенуза']]));
    }

    public function test_subtype_plans_are_well_formed(): void
    {
        $files = File::files(storage_path('app/tasks/subtypes/oge'));
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $plan = json_decode(File::get($file->getPathname()), true);
            $this->assertIsArray($plan, $file->getFilename() . ' — не JSON');

            foreach ($plan as $number => $titles) {
                if (!is_array($titles)) {
                    continue;
                }
                $label = $file->getFilename() . ", задание {$number}";
                $this->assertGreaterThan(1, count($titles), "{$label}: подтип из одного названия не нужен");

                $rules = $this->invokePrivate('rules', [$titles]);
                if ($rules === null) {
                    foreach ($titles as $title) {
                        $this->assertIsString($title, $label);
                        $this->assertNotSame('', trim($title), $label);
                    }
                    continue;
                }
                foreach ($rules as $rule) {
                    $this->assertNotSame('', trim($rule['title']), $label);
                    $this->assertNotFalse(
                        @preg_match('/' . str_replace('/', '\/', $rule['match']) . '/iu', ''),
                        "{$label}: правило «{$rule['title']}» — сломанное регулярное выражение"
                    );
                }
            }
        }
    }
}
