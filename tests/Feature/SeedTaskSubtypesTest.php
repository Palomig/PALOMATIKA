<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Разбиение кураторской группы на подтипы: серии команда находит сама по
 * тексту условия, названия берёт из файла.
 */
class SeedTaskSubtypesTest extends TestCase
{
    use RefreshDatabase;

    private string $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures = storage_path('app/tasks/subtypes/testbank');
        File::ensureDirectoryExists($this->fixtures);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixtures);
        parent::tearDown();
    }

    private function plan(array $plan): void
    {
        File::put($this->fixtures . '/topic_23.json', json_encode($plan, JSON_UNESCAPED_UNICODE));
    }

    private function group(int $number, array $texts): TaskGroup
    {
        $group = TaskGroup::create([
            'bank' => 'testbank',
            'topic' => '23',
            'block_number' => 1,
            'zadanie_number' => $number,
            'position' => $number,
            'type' => 'word_problem',
            'payload' => ['number' => $number, 'instruction' => 'Группа'],
            'source' => 'fipi',
        ]);

        foreach (array_values($texts) as $i => $text) {
            Task::create([
                'task_group_id' => $group->id,
                'position' => $i,
                'type' => 'word_problem',
                'payload' => ['id' => $i + 1, 'html' => '<p>' . $text . '</p>'],
                'source' => 'fipi',
            ]);
        }

        return $group;
    }

    public function test_splits_group_into_series_by_wording(): void
    {
        // Три серии: внутри серии текст совпадает дословно, меняются числа.
        $group = $this->group(1, [
            'Катеты равны 15 и 20. Найдите высоту.',
            'Катеты равны 18 и 24. Найдите высоту.',
            'Катет и гипотенуза равны 21 и 75. Найдите высоту.',
            'Найдите AB, если AH=9, AC=36.',
            'Найдите AB, если AH=3, AC=27.',
        ]);
        $this->plan(['1' => ['Даны катеты', 'Даны катет и гипотенуза', 'Дана проекция']]);

        $this->artisan('tasks:seed-subtypes', ['--bank' => 'testbank', '--topic' => '23'])->assertSuccessful();

        $payload = TaskGroup::query()->whereKey($group->id)->first()->payload;
        $this->assertSame(['Даны катеты', 'Даны катет и гипотенуза', 'Дана проекция'], $payload['subtypes']);

        $byPosition = Task::query()->where('task_group_id', $group->id)->orderBy('position')->get()
            ->map(fn (Task $t) => $t->payload['subtype'])->all();
        $this->assertSame([0, 0, 1, 2, 2], $byPosition);
    }

    public function test_refuses_to_write_when_series_count_differs(): void
    {
        $group = $this->group(1, [
            'Катеты равны 15 и 20. Найдите высоту.',
            'Найдите AB, если AH=9, AC=36.',
        ]);
        $this->plan(['1' => ['Первый', 'Второй', 'Третий']]);

        $this->artisan('tasks:seed-subtypes', ['--bank' => 'testbank', '--topic' => '23'])->assertFailed();

        $this->assertArrayNotHasKey('subtypes', TaskGroup::query()->whereKey($group->id)->first()->payload);
    }

    /** Формулы с разным числом чисел — та же серия: важен текст, а не данные. */
    public function test_series_survives_different_formula_shape(): void
    {
        $group = $this->group(11, [
            'Найдите радиус, если $\cos\angle BAC=\dfrac{2\sqrt{2}}{3}$.',
            'Найдите радиус, если $\cos\angle BAC=\dfrac{\sqrt{11}}{6}$.',
        ]);
        $this->plan(['11' => ['Окружность через две точки стороны']]);

        $this->artisan('tasks:seed-subtypes', ['--bank' => 'testbank', '--topic' => '23'])->assertSuccessful();

        $this->assertCount(1, TaskGroup::query()->whereKey($group->id)->first()->payload['subtypes']);
    }

    /** Раскрытая группа показывает подтипы, а не плоский список задач. */
    public function test_page_renders_subtypes_inside_the_group(): void
    {
        $group = TaskGroup::create([
            'bank' => 'oge', 'topic' => '23', 'block_number' => 1, 'block_title' => 'Треугольники',
            'zadanie_number' => 1, 'position' => 1, 'type' => 'word_problem', 'source' => 'fipi',
            'payload' => [
                'number' => 1,
                'instruction' => 'Высота к гипотенузе',
                'taxonomy_key' => 'altitude',
                'subtypes' => ['Даны катеты', 'Дана проекция'],
            ],
        ]);
        foreach ([[0, 'Катеты равны 15 и 20.'], [0, 'Катеты равны 18 и 24.'], [1, 'Найдите AB, если AH=9.']] as $i => [$sub, $text]) {
            Task::create([
                'task_group_id' => $group->id, 'position' => $i, 'type' => 'word_problem', 'source' => 'fipi',
                'payload' => ['id' => $i + 1, 'text' => $text, 'subtype' => $sub], 'answer' => '12',
            ]);
        }

        $teacher = \App\Models\User::create([
            'name' => 'T', 'email' => 't+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher',
            'onboarding_completed_at' => now(), 'telegram_chat_id' => random_int(100000000, 999999999),
        ]);

        $response = $this->actingAs($teacher)->get('http://student.palomatika.ru/part2?topic=23');

        $response->assertOk();
        $response->assertSee('Даны катеты');
        $response->assertSee('Дана проекция');
        $response->assertSee('class="subtype"', false);
        // Задачи разошлись по подтипам: 2 и 1.
        $this->assertSame(2, substr_count($response->getContent(), '<details class="subtype">'));
    }

    /** Реальная разметка темы 23 — чтобы файл не разъехался незаметно. */
    public function test_topic_23_plan_is_well_formed(): void
    {
        $plan = json_decode(File::get(storage_path('app/tasks/subtypes/oge/topic_23.json')), true);
        $groups = array_filter($plan, static fn ($v) => is_array($v));

        $this->assertCount(8, $groups);
        $this->assertSame(18, array_sum(array_map('count', $groups)));

        foreach ($groups as $number => $titles) {
            $this->assertGreaterThan(1, count($titles), "группа {$number}: подтип должен быть не один");
            foreach ($titles as $title) {
                $this->assertNotSame('', trim($title));
            }
        }
    }
}
