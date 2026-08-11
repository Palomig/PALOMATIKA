<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Services\EgeTaskDataService;
use App\Services\TaskBankRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Импорт профиля ЕГЭ из банка ФИПИ.
 *
 * Выгрузка в тесте маленькая и собрана вручную: настоящая весит больше
 * мегабайта, и гонять её в каждом прогоне незачем — проверяется раскладка,
 * а не содержимое банка.
 */
class FipiEgeBankImportTest extends TestCase
{
    use RefreshDatabase;

    private string $bankPath;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        $this->bankPath = storage_path('app/imports/ege_prof_katex_test.json');
        File::ensureDirectoryExists(dirname($this->bankPath));
        File::put($this->bankPath, json_encode($this->bank(), JSON_UNESCAPED_UNICODE));
    }

    protected function tearDown(): void
    {
        File::delete($this->bankPath);
        parent::tearDown();
    }

    /** @return array<string, mixed> */
    private function bank(): array
    {
        return [
            'source' => 'fipi-ege',
            'level' => 'prof',
            'count' => 5,
            'tasks' => [
                [
                    'guid' => 'AAAA0000000000000000000000000001',
                    'task_no' => 1, 'subtype_id' => 'p1', 'subtype_title' => 'Треугольник',
                    'html' => '<p>В треугольнике $ABC$ угол $C$ равен $90^\circ$.</p>',
                    'answer' => '6', 'answer_src' => 'codex',
                ],
                [
                    'guid' => 'AAAA0000000000000000000000000002',
                    'task_no' => 1, 'subtype_id' => 'p2', 'subtype_title' => 'Окружность',
                    'html' => '<p>Рисунок: <img src="img/AAAA02/pic.png" alt="рисунок"></p>',
                    'images' => ['img/AAAA02/pic.png'],
                    'answer' => '12', 'answer_src' => 'codex',
                ],
                [
                    'guid' => 'AAAA0000000000000000000000000003',
                    'task_no' => 15, 'subtype_id' => 's1', 'subtype_title' => 'Неравенство',
                    'part2' => true,
                    'html' => '<p>Решите неравенство.</p>',
                    'answer' => '(0;1/64]∪[1/9;1/5)', 'answer_src' => 'calc',
                ],
                [
                    'guid' => 'AAAA0000000000000000000000000004',
                    'task_no' => 19, 'subtype_id' => 'n1', 'subtype_title' => 'Числа',
                    'html' => '<p>Может ли …</p>',
                    'answer' => 'Да', 'answer_kind' => 'yes_no', 'answer_src' => 'codex',
                ],
                [
                    'guid' => 'AAAA0000000000000000000000000005',
                    'task_no' => 0, 'subtype_id' => 'x1', 'subtype_title' => 'Без номера',
                    'html' => '<p>Задача без номера задания.</p>',
                    'answer' => '5', 'answer_src' => 'codex',
                ],
            ],
        ];
    }

    private function import(array $options = []): int
    {
        return Artisan::call('tasks:import-fipi-ege',
            array_merge(['--file' => $this->bankPath], $options));
    }

    public function test_tasks_are_laid_out_by_exam_task_number(): void
    {
        $this->assertSame(0, $this->import());

        $this->assertSame(['01', '15', '19'],
            TaskTopic::query()->where('bank', 'ege')->orderBy('topic')->pluck('topic')->all());

        $first = TaskGroup::query()->where('bank', 'ege')->where('topic', '01')
            ->orderBy('position')->get();
        $this->assertCount(2, $first, 'подтипы банка становятся отдельными заданиями');
        $this->assertSame(['Треугольник', 'Окружность'], $first->pluck('instruction')->all());
        $this->assertSame('ФИПИ', $first->first()->block_title);
    }

    public function test_task_without_number_is_not_imported(): void
    {
        $this->import();

        $this->assertNull(Task::query()
            ->where('fipi_guid', 'AAAA0000000000000000000000000005')->first(),
            'без номера задания теме неоткуда взяться — такие задачи пропускаются');
        $this->assertSame(4, Task::query()->where('source', 'fipi')->count());
    }

    public function test_answer_that_cannot_be_checked_goes_to_draft(): void
    {
        $this->import();

        $yesNo = Task::query()->where('fipi_guid', 'AAAA0000000000000000000000000004')->first();
        $this->assertSame('draft', $yesNo->status,
            '«Да» автопроверка принять не может — ученику такую задачу не показываем');

        $usual = Task::query()->where('fipi_guid', 'AAAA0000000000000000000000000001')->first();
        $this->assertSame('production', $usual->status);
    }

    public function test_image_links_are_rewritten_to_public_path(): void
    {
        $this->import();

        $withPicture = Task::query()->where('fipi_guid', 'AAAA0000000000000000000000000002')->first();
        $this->assertStringContainsString('src="/ege-bank/img/AAAA02/pic.png"',
            $withPicture->payload['html'],
            'относительный путь в PWA разрешался бы от адреса страницы и давал 404');
    }

    public function test_topic_is_served_from_database_after_import(): void
    {
        $this->import();
        Cache::flush();

        $topic = (new EgeTaskDataService())->getTopicData('15');
        $this->assertNotEmpty($topic['blocks'] ?? [], 'тема должна собираться из базы');
        $task = $topic['blocks'][0]['zadaniya'][0]['tasks'][0];
        $this->assertSame('(0;1/64]∪[1/9;1/5)', $task['answer']);
    }

    public function test_reimport_replaces_only_fipi_tasks(): void
    {
        $this->import();
        $before = Task::query()->where('source', 'fipi')->count();

        $own = TaskGroup::create([
            'bank' => 'ege', 'topic' => '01', 'block_number' => 1, 'block_title' => 'Своё',
            'zadanie_number' => 9, 'position' => 99, 'type' => 'expression',
            'status' => 'production', 'source' => 'palomatika',
        ]);

        $this->import();

        $this->assertSame($before, Task::query()->where('source', 'fipi')->count());
        $this->assertDatabaseHas('task_groups', ['id' => $own->id, 'source' => 'palomatika']);
    }

    public function test_and_retire_switches_the_previous_bank_off(): void
    {
        $own = TaskGroup::create([
            'bank' => 'ege', 'topic' => '01', 'block_number' => 1, 'block_title' => 'Своё',
            'zadanie_number' => 9, 'position' => 99, 'type' => 'expression',
            'status' => 'production', 'source' => 'palomatika',
        ]);
        Task::create([
            'task_group_id' => $own->id, 'position' => 0, 'type' => 'expression',
            'payload' => ['id' => 1, 'text' => 'старая задача'], 'answer' => '1',
            'status' => 'production', 'source' => 'palomatika',
        ]);

        $this->import(['--and-retire' => true]);

        $this->assertDatabaseHas('task_groups',
            ['id' => $own->id, 'source' => TaskBankRepository::RETIRED]);
        $this->assertSame(0, Task::query()->where('source', 'palomatika')->count(),
            'старый банк не удаляется, а помечается: история ДЗ должна остаться читаемой');
    }
}
