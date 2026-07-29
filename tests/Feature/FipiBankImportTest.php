<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Services\TaskBankRepository;
use App\Services\TaskDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Импорт банка ФИПИ и отключение прежнего банка ОГЭ.
 */
class FipiBankImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        if (!file_exists(storage_path('app/imports/bank_katex.json'))) {
            $this->markTestSkipped('нет выгрузки банка ФИПИ');
        }
        Artisan::call('tasks:import-json', ['--bank' => 'oge']);
        Artisan::call('tasks:import-fipi');
    }

    public function test_both_banks_live_side_by_side_until_the_old_one_is_retired(): void
    {
        $this->assertSame(2624, Task::query()->where('source', 'palomatika')->count());
        $this->assertSame(3884, Task::query()->where('source', 'fipi')->count());
    }

    public function test_options_carry_no_letter_ids(): void
    {
        // В PWA optionAnswerValue() отдаёт opt.id, а без него — порядковый
        // номер. Ответы ФИПИ — номера, поэтому буквенных id быть не должно.
        $task = Task::query()
            ->where('source', 'fipi')
            ->whereNotNull('answer')
            ->get()
            ->first(fn (Task $t) => !empty($t->payload['options']));

        $this->assertNotNull($task, 'не нашлось задания с вариантами');
        foreach ($task->payload['options'] as $option) {
            $this->assertArrayNotHasKey('id', $option);
            $this->assertArrayHasKey('n', $option);
        }
        $this->assertSame((string) $task->answer, (string) (int) $task->answer,
            'ответ на задание с вариантами должен быть номером');
    }

    public function test_zero_is_a_real_answer_not_a_missing_one(): void
    {
        // Десять уравнений темы 9 имеют корень «0»; empty('0') в PHP — true,
        // и они уехали бы в draft.
        $zeros = Task::query()->where('source', 'fipi')->where('answer', '0')->get();

        $this->assertCount(10, $zeros);
        foreach ($zeros as $task) {
            $this->assertSame('production', $task->status);
        }
    }

    public function test_retiring_requires_a_replacement_and_hides_the_old_bank(): void
    {
        $service = new TaskDataService();
        $before = $service->getTopicData('16');
        $this->assertNotEmpty($before['blocks']);

        Artisan::call('tasks:retire-legacy');
        Cache::flush();

        $groups = TaskGroup::query()->where('bank', 'oge')->where('topic', '16')->get();
        $this->assertTrue($groups->where('source', TaskBankRepository::RETIRED)->isNotEmpty());

        $after = (new TaskDataService())->getTopicData('16');
        $sources = TaskGroup::query()
            ->where('bank', 'oge')->where('topic', '16')
            ->where('source', '!=', TaskBankRepository::RETIRED)
            ->pluck('source')->unique();

        $this->assertSame(['fipi'], $sources->values()->all());
        $this->assertNotEmpty($after['blocks'], 'после отключения тема осталась пустой');
    }

    public function test_status_reaches_the_interface_through_payload(): void
    {
        // Структуру для интерфейса репозиторий собирает из payload, а не из
        // колонок. Пока `status` лежал только в колонке, фильтр «production»
        // отсекал почти весь банк: тема 16 показывала 8 задач из 322.
        $service = new TaskDataService();
        Artisan::call('tasks:import-fipi', ['--and-retire' => true]);
        Cache::flush();

        $all = $service->getBlocks('16');
        $production = $service->getBlocks('16', 'production');

        $count = static fn (array $blocks) => array_sum(array_map(
            static fn (array $b) => array_sum(array_map(
                static fn (array $z) => count($z['tasks'] ?? []),
                $b['zadaniya'] ?? []
            )),
            $blocks
        ));

        $this->assertSame(322, $count($all));
        $this->assertSame(322, $count($production), 'фильтр production потерял задания');
    }

    public function test_proofs_without_answers_stay_out_of_production(): void
    {
        Artisan::call('tasks:import-fipi', ['--and-retire' => true]);
        Cache::flush();

        $production = (new TaskDataService())->getBlocks('24', 'production');
        $tasks = array_sum(array_map(
            static fn (array $b) => array_sum(array_map(
                static fn (array $z) => count($z['tasks'] ?? []),
                $b['zadaniya'] ?? []
            )),
            $production
        ));

        $this->assertSame(0, $tasks, 'доказательства темы 24 без ответов не должны идти в выдачу');
    }

    public function test_and_retire_switches_banks_without_a_window(): void
    {
        // Импорт и отключение в одной транзакции: иначе между ними тема
        // показывала бы оба банка сразу.
        Artisan::call('tasks:import-fipi', ['--and-retire' => true]);

        $live = TaskGroup::query()
            ->where('bank', 'oge')
            ->where('source', '!=', TaskBankRepository::RETIRED)
            ->pluck('source')->unique()->values()->all();

        $this->assertSame(['fipi'], $live);
        $this->assertSame(2624, Task::query()->where('source', TaskBankRepository::RETIRED)->count());
    }

    public function test_retiring_refuses_without_a_replacement(): void
    {
        Artisan::call('tasks:retire-legacy');           // ФИПИ на месте — сработает
        Artisan::call('tasks:retire-legacy', ['--restore' => true]);

        TaskGroup::query()->where('source', 'fipi')->delete();

        $code = Artisan::call('tasks:retire-legacy');

        $this->assertSame(1, $code, 'команда обязана отказаться без банка-замены');
        $this->assertSame(0, TaskGroup::query()->where('source', TaskBankRepository::RETIRED)->count());
    }
}
