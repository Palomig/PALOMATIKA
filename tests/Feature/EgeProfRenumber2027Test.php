<?php

namespace Tests\Feature;

use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Services\EgeTaskDataService;
use App\Services\TaskBankRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Переезд профиля ЕГЭ на нумерацию КИМ 2027.
 *
 * Проверяется не «команда отработала», а то, ради чего она нужна: ни одна
 * группа не потерялась, слитые номера встали друг за другом, а не вперемешку,
 * и подписи заданий переехали вместе с номерами — иначе под тринадцатым
 * значилось бы «Уравнение (часть 2)».
 */
class EgeProfRenumber2027Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();
        $this->seedBank2026();
    }

    /** Банк по плану 2026: девятнадцать номеров, по две группы в каждом. */
    private function seedBank2026(): void
    {
        for ($number = 1; $number <= 19; $number++) {
            $topic = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            TaskTopic::create([
                'bank' => 'ege',
                'grade' => null,
                'topic' => $topic,
                'payload' => ['topic_id' => $topic, 'level' => 'prof',
                              'meta' => ['title' => "Старое название {$topic}"]],
            ]);
            foreach ([0, 1] as $position) {
                TaskGroup::create([
                    'bank' => 'ege',
                    'grade' => null,
                    'topic' => $topic,
                    'block_number' => 1,
                    'zadanie_number' => $position + 1,
                    'position' => $position,
                    'type' => 'fipi',
                    'source' => 'fipi',
                    'payload' => ['marker' => "{$topic}-{$position}"],
                ]);
            }
        }
    }

    private function markersOf(string $topic): array
    {
        return TaskGroup::where('bank', 'ege')->where('topic', $topic)
            ->orderBy('position')->get()
            ->map(fn (TaskGroup $g) => $g->payload['marker'])->all();
    }

    public function test_numbers_shift_by_the_official_map(): void
    {
        Artisan::call('ege:renumber-2027');

        // Первые пять не двигались, дальше сдвиг на единицу.
        $this->assertSame(['01-0', '01-1'], $this->markersOf('01'));
        $this->assertSame(['06-0', '06-1'], $this->markersOf('07'));
        $this->assertSame(['11-0', '11-1'], $this->markersOf('12'));
        // Экономическая переехала из части 2 в часть 1.
        $this->assertSame(['16-0', '16-1'], $this->markersOf('13'));
        $this->assertSame(['13-0', '13-1'], $this->markersOf('14'));
        // Хвост части 2.
        $this->assertSame(['17-0', '17-1'], $this->markersOf('18'));
        $this->assertSame(['19-0', '19-1'], $this->markersOf('20'));
    }

    public function test_extremum_task_joins_the_derivative_one_at_the_end(): void
    {
        Artisan::call('ege:renumber-2027');

        // Прежние восьмой и двенадцатый — это теперь один девятый, и
        // двенадцатый идёт ПОСЛЕ восьмого, а не вперемешку с ним.
        $this->assertSame(['08-0', '08-1', '12-0', '12-1'], $this->markersOf('09'));
        $this->assertSame(1, TaskTopic::where('bank', 'ege')->where('topic', '09')->count());
    }

    public function test_no_group_is_lost_and_new_numbers_stay_empty(): void
    {
        $before = TaskGroup::where('bank', 'ege')->count();

        Artisan::call('ege:renumber-2027');

        $this->assertSame($before, TaskGroup::where('bank', 'ege')->count());
        // Заданий 6 и 17 в банке ФИПИ пока нет — они обязаны остаться пустыми,
        // а не подобрать чужие задачи.
        $this->assertSame(0, TaskGroup::where('bank', 'ege')->where('topic', '06')->count());
        $this->assertSame(0, TaskGroup::where('bank', 'ege')->where('topic', '17')->count());
    }

    public function test_titles_follow_the_new_numbers(): void
    {
        Artisan::call('ege:renumber-2027');

        $titleOf = fn (string $topic) => TaskTopic::where('bank', 'ege')
            ->where('topic', $topic)->value('payload')['meta']['title'];

        $this->assertSame('Экономическая задача', $titleOf('13'));
        $this->assertSame('Уравнение (часть 2)', $titleOf('14'));
        $this->assertSame('Числа и их свойства', $titleOf('20'));
    }

    public function test_second_run_changes_nothing(): void
    {
        Artisan::call('ege:renumber-2027');
        $snapshot = TaskGroup::where('bank', 'ege')->orderBy('id')
            ->pluck('topic', 'id')->all();

        Artisan::call('ege:renumber-2027');

        $this->assertSame($snapshot, TaskGroup::where('bank', 'ege')->orderBy('id')
            ->pluck('topic', 'id')->all());
    }

    public function test_base_level_bank_is_left_alone(): void
    {
        TaskGroup::create([
            'bank' => EgeTaskDataService::BANK_BASE, 'grade' => null, 'topic' => '16',
            'block_number' => 1, 'zadanie_number' => 1, 'position' => 0,
            'type' => 'fipi', 'source' => 'fipi', 'payload' => ['marker' => 'base-16'],
        ]);

        Artisan::call('ege:renumber-2027');

        $this->assertSame(1, TaskGroup::where('bank', EgeTaskDataService::BANK_BASE)
            ->where('topic', '16')->count());
    }
}
