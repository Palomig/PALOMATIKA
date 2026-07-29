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

    private const CURATED_TOPIC_TASKS = [
        '06' => 81,
        '07' => 171,
        '08' => 321,
        '09' => 111,
        '10' => 211,
        '11' => 101,
        '12' => 175,
        '13' => 141,
        '14' => 110,
        '15' => 252,
        '16' => 322,
        '17' => 316,
        '18' => 154,
        '19' => 150,
        '20' => 200,
        '21' => 190,
        '22' => 157,
        '23' => 172,
        '24' => 60,
        '25' => 130,
    ];

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

    public function test_topic_16_uses_the_pedagogical_taxonomy(): void
    {
        Artisan::call('tasks:import-fipi', ['--and-retire' => true]);
        Cache::flush();

        $blocks = (new TaskDataService())->getBlocks('16');
        $groups = array_merge(...array_column($blocks, 'zadaniya'));

        $this->assertSame([
            'Углы в окружности',
            'Вписанные четырёхугольники',
            'Вписанная окружность',
            'Описанная окружность',
        ], array_column($blocks, 'title'));
        $this->assertCount(23, $groups);
        $this->assertSame(range(1, 23), array_column($groups, 'number'));
        $this->assertSame([
            10, 20, 10, 1, 10,
            20, 10, 20,
            10, 10, 30, 20, 10, 10, 30, 10,
            10, 20, 20, 10, 9, 10, 12,
        ], array_map(static fn (array $group): int => count($group['tasks']), $groups));
        $this->assertSame([
            'Центральный и вписанный углы: вписанный угол вдвое меньше',
            'Два диаметра: связь центрального и вписанного углов',
            'Угол, опирающийся на диаметр: 90°',
            'Две касательные: углы и радиусы',
            'Центр окружности лежит на стороне треугольника: найти угол',
            'Противоположные углы вписанного четырёхугольника',
            'Углы при параллельных основаниях трапеции',
            'Вписанный четырёхугольник: равные вписанные углы и сумма 180°',
            'Квадрат: найти радиус по стороне',
            'Квадрат: найти площадь по радиусу',
            'Трапеция: высота равна диаметру вписанной окружности',
            'Описанный четырёхугольник: суммы противоположных сторон',
            'Квадрат: найти диагональ по радиусу вписанной окружности',
            'Площадь треугольника по формуле S = pr',
            'Равносторонний треугольник и вписанная окружность: сторона ↔ радиус',
            'Ромб: радиус через диагональ и тангенс угла',
            'Прямоугольный треугольник: R = c / 2',
            'Квадрат и описанная окружность: сторона ↔ радиус',
            'Равносторонний треугольник и описанная окружность: сторона ↔ радиус',
            'Центр на гипотенузе: найти сторону по теореме Пифагора',
            'Расширенная теорема синусов',
            'Прямоугольник: площадь через диагональ и синус угла',
            'Квадрат и окружность с центром на середине стороны: теорема Пифагора',
        ], array_column($groups, 'instruction'));
    }

    public function test_all_curated_taxonomies_are_idempotent(): void
    {
        Artisan::call('tasks:import-fipi', ['--and-retire' => true]);
        Artisan::call('tasks:import-fipi', ['--and-retire' => true]);
        Cache::flush();

        $service = new TaskDataService();
        $topic16 = $service->getBlocks('16');
        $topic15 = $service->getBlocks('15');

        $this->assertCount(4, $topic16);
        $this->assertSame(322, array_sum(array_map(
            static fn (array $block): int => array_sum(array_map(
                static fn (array $group): int => count($group['tasks']),
                $block['zadaniya'],
            )),
            $topic16,
        )));
        $this->assertCount(5, $topic15);
        $this->assertSame('Углы в треугольнике', $topic15[0]['title']);
        $this->assertNotContains('ФИПИ', array_column($topic15, 'title'));
    }

    public function test_topics_06_through_25_have_complete_curated_groups(): void
    {
        Artisan::call('tasks:import-fipi', ['--and-retire' => true]);
        Cache::flush();

        $service = new TaskDataService();
        foreach (self::CURATED_TOPIC_TASKS as $topic => $expectedTasks) {
            $blocks = $service->getBlocks($topic);
            $groups = array_merge(...array_column($blocks, 'zadaniya'));
            $tasks = array_sum(array_map(
                static fn (array $group): int => count($group['tasks'] ?? []),
                $groups,
            ));

            $this->assertSame($expectedTasks, $tasks, "тема {$topic}");
            $this->assertSame(range(1, count($groups)), array_column($groups, 'number'), "тема {$topic}");
            $this->assertNotContains('', array_column($blocks, 'title'), "тема {$topic}");
            $this->assertNotContains(null, array_column($groups, 'taxonomy_key'), "тема {$topic}");
            foreach (array_column($groups, 'instruction') as $instruction) {
                $this->assertDoesNotMatchRegularExpression(
                    '/^Задание\s+\d+/u',
                    (string) $instruction,
                    "тема {$topic}",
                );
            }
        }
    }

    public function test_dry_run_reports_complete_curated_range(): void
    {
        $this->artisan('tasks:import-fipi', ['--dry-run' => true])
            ->expectsOutputToContain('КУРАТОРСКИЕ ТЕМЫ 06–25: тем 20, задач 3525')
            ->assertSuccessful();
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
