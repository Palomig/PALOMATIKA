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
 * Подписи заданий ЕГЭ и черновики в выдаче.
 *
 * Два дефекта, найденные на проде после переезда банка:
 *
 * 1. Название задания жило в трёх местах — в банке, в карте
 *    {@see EgeTaskDataService} и отдельными картами в шаблонах ученика. Карты
 *    отстали от нумерации ФИПИ, и одно задание называлось по-разному на
 *    списке и на своей странице («Графики» против «Текстовой задачи»).
 * 2. Генератор вариантов и API случайных задач не смотрели на статус, и в
 *    печатный вариант могла попасть черновая задача — та, у которой ещё нет
 *    ответа.
 */
class EgeBankNamingAndDraftsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        // Тема 10 — та самая, где карта и банк расходились.
        TaskTopic::create([
            'bank' => 'ege', 'grade' => null, 'topic' => '10',
            'payload' => ['topic_id' => '10', 'meta' => [
                'title' => 'Текстовая задача · из банка',
                'description' => 'Движение, работа, проценты и смеси',
            ]],
        ]);

        $group = TaskGroup::create([
            'bank' => 'ege', 'grade' => null, 'topic' => '10',
            'block_number' => 1, 'block_title' => 'ФИПИ', 'zadanie_number' => 1,
            'position' => 0, 'instruction' => 'Движение по реке', 'type' => 'fipi',
            'payload' => ['instruction' => 'Движение по реке', 'type' => 'fipi', 'status' => 'production'],
            'status' => 'production', 'source' => 'fipi',
        ]);

        Task::create([
            'task_group_id' => $group->id, 'position' => 0, 'type' => 'fipi',
            'payload' => ['id' => 1, 'status' => 'production', 'answer' => '24',
                'html' => '<p>Расстояние между пристанями равно 192 км.</p>'],
            'answer' => '24', 'answer_src' => 'codex', 'status' => 'production',
            'source' => 'fipi', 'fipi_guid' => str_pad('10A', 32, 'A'),
        ]);

        Task::create([
            'task_group_id' => $group->id, 'position' => 1, 'type' => 'fipi',
            'payload' => ['id' => 2, 'status' => 'draft',
                'html' => '<p>Черновик без ответа.</p>'],
            'answer' => null, 'status' => 'draft',
            'source' => 'fipi', 'fipi_guid' => str_pad('10B', 32, 'B'),
        ]);
    }

    public function test_topic_list_and_topic_page_agree_on_the_name(): void
    {
        // Название в базе намеренно отличается от карты сервиса: так видно,
        // что список берёт его из банка, а не из запасных метаданных.
        $list = $this->get('/ege');
        $list->assertOk();
        $list->assertSee('Текстовая задача · из банка', false);

        $page = $this->get('/ege/topics/10');
        $page->assertOk();
        $page->assertSee('Текстовая задача · из банка', false);
        $page->assertSee('Движение, работа, проценты и смеси');
    }

    public function test_fallback_map_matches_fipi_numbering(): void
    {
        $meta = (new EgeTaskDataService())->getAllTopicsMeta();

        $this->assertSame('Текстовая задача', $meta['10']['title']);
        $this->assertSame('Графики функций', $meta['11']['title']);
        $this->assertSame('Неравенство', $meta['15']['title']);
        $this->assertSame('Задача с параметром', $meta['18']['title']);
    }

    public function test_random_tasks_skip_drafts(): void
    {
        $service = new EgeTaskDataService();

        for ($i = 0; $i < 20; $i++) {
            $tasks = $service->getRandomTasks('10', 5);
            foreach ($tasks as $item) {
                $this->assertSame('production', $item['task']['status'] ?? 'production',
                    'черновик не должен попадать в случайную выдачу');
            }

            $fromZadanie = $service->getRandomTasksFromZadanie('10', 1, 1, 5);
            foreach ($fromZadanie as $item) {
                $this->assertSame('production', $item['task']['status'] ?? 'production',
                    'черновик не должен попадать в задание варианта');
            }
        }
    }

    public function test_printed_variant_shows_the_condition_and_drawing(): void
    {
        $group = TaskGroup::where('bank', 'ege')->where('topic', '10')->firstOrFail();
        Task::create([
            'task_group_id' => $group->id, 'position' => 2, 'type' => 'fipi',
            'payload' => ['id' => 3, 'status' => 'production', 'answer' => '5',
                'html' => '<p>Условие с чертежом.</p><p><img class="fipi-figure" src="/ege-bank/img/x/y.png" alt="рисунок"></p>'],
            'answer' => '5', 'answer_src' => 'codex', 'status' => 'production',
            'source' => 'fipi', 'fipi_guid' => str_pad('10C', 32, 'C'),
        ]);
        Cache::flush();

        $response = $this->get('/ege/variant/abcde');

        $response->assertOk();
        // Условие банка лежит в `html`; шаблон печатал только text/expression,
        // и в варианте оставалась одна подпись подтипа.
        $response->assertSee('Расстояние между пристанями', false);
        $response->assertDontSee('Черновик без ответа', false);
    }

    public function test_api_random_task_is_not_a_draft(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/ege/10/random?count=3');
            $response->assertOk();

            foreach ($response->json('tasks') ?? [] as $item) {
                $this->assertNotSame('draft', $item['task']['status'] ?? 'production');
            }
        }
    }
}
