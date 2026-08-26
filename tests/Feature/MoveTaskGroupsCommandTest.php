<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Services\EgeTaskDataService;
use App\Services\TaskBankRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Перенос заданий между темами банка ({@see \App\Console\Commands\MoveTaskGroups}).
 *
 * Классификатор ФИПИ разложил задачи по номерам заданий ЕГЭ по тексту, и
 * прикладные задачи с формулой осели в теме «Уравнение». Команда переносит
 * такую серию точечно: задачи едут за заданием, нумерация приёмника не
 * сдвигается, а тема-источник остаётся с плотными позициями.
 */
class MoveTaskGroupsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        foreach (['06' => 'Уравнение', '09' => 'Прикладная задача с формулой'] as $topic => $title) {
            TaskTopic::create([
                'bank' => 'ege', 'grade' => null, 'topic' => $topic,
                'payload' => ['topic_id' => $topic, 'meta' => ['title' => $title]],
            ]);
        }

        // Тема-источник: четыре «настоящих» уравнения и две прикладные серии.
        foreach ([1 => 'Иррациональные', 2 => 'Логарифмические', 3 => 'Распад изотопа', 4 => 'Закон Стефана'] as $n => $instruction) {
            $this->group('06', $n, $n - 1, $instruction);
        }
        // Тема-приёмник: одно задание, чтобы проверить, что серия встаёт следом.
        $this->group('09', 1, 0, 'Автомобиль разгоняется', block: 1, blockTitle: 'ФИПИ');
    }

    public function test_series_moves_to_target_topic_with_tasks(): void
    {
        $this->artisan('tasks:move-groups', [
            '--from' => '06', '--to' => '09', '--zadanie' => '3,4',
        ])->assertSuccessful();

        $moved = TaskGroup::where('bank', 'ege')->where('topic', '09')->orderBy('position')->get();

        $this->assertSame(
            ['Автомобиль разгоняется', 'Распад изотопа', 'Закон Стефана'],
            $moved->pluck('instruction')->all()
        );
        // Приёмник не перенумерован: №1 остался №1, серия встала следом.
        $this->assertSame([1, 2, 3], $moved->pluck('zadanie_number')->all());
        $this->assertSame([0, 1, 2], $moved->pluck('position')->all());
        $this->assertSame([1, 1, 1], $moved->pluck('block_number')->all());

        // Задачи переехали вместе с заданием.
        $this->assertSame(1, Task::where('task_group_id', $moved[1]->id)->count());

        // В теме-источнике остались только уравнения, позиции плотные.
        $rest = TaskGroup::where('bank', 'ege')->where('topic', '06')->orderBy('position')->get();
        $this->assertSame(['Иррациональные', 'Логарифмические'], $rest->pluck('instruction')->all());
        $this->assertSame([0, 1], $rest->pluck('position')->all());
    }

    public function test_moved_series_is_visible_in_target_topic_tree(): void
    {
        $this->artisan('tasks:move-groups', [
            '--from' => '06', '--to' => '09', '--zadanie' => '3',
        ])->assertSuccessful();

        $data = (new EgeTaskDataService())->getTopicData('09');

        // Блок один: у перенесённого задания тот же номер блока, иначе
        // репозиторий разорвал бы «ФИПИ» на два блока подряд.
        $this->assertCount(1, $data['blocks']);
        $this->assertSame(
            ['Автомобиль разгоняется', 'Распад изотопа'],
            array_column($data['blocks'][0]['zadaniya'], 'instruction')
        );
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->artisan('tasks:move-groups', [
            '--from' => '06', '--to' => '09', '--zadanie' => '3,4', '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame(4, TaskGroup::where('topic', '06')->count());
        $this->assertSame(1, TaskGroup::where('topic', '09')->count());
    }

    public function test_unknown_zadanie_aborts_whole_move(): void
    {
        $this->artisan('tasks:move-groups', [
            '--from' => '06', '--to' => '09', '--zadanie' => '3,42',
        ])->assertFailed();

        $this->assertSame(4, TaskGroup::where('topic', '06')->count());
        $this->assertSame(1, TaskGroup::where('topic', '09')->count());
    }

    private function group(string $topic, int $number, int $position, string $instruction,
                           int $block = 1, string $blockTitle = 'ФИПИ'): TaskGroup
    {
        $group = TaskGroup::create([
            'bank' => 'ege', 'grade' => null, 'topic' => $topic,
            'block_number' => $block, 'block_title' => $blockTitle,
            'zadanie_number' => $number, 'position' => $position,
            'instruction' => $instruction, 'type' => 'fipi',
            'payload' => ['instruction' => $instruction, 'type' => 'fipi', 'status' => 'production'],
            'status' => 'production', 'source' => 'fipi',
        ]);

        Task::create([
            'task_group_id' => $group->id, 'position' => 0, 'type' => 'fipi',
            'payload' => ['id' => 1, 'status' => 'production', 'answer' => '7',
                          'html' => "<p>{$instruction}</p>"],
            'answer' => '7', 'answer_src' => 'codex', 'status' => 'production',
            'source' => 'fipi', 'fipi_guid' => str_pad("{$topic}{$number}", 32, 'B'),
        ]);

        return $group;
    }
}
