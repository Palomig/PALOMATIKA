<?php

namespace Tests\Feature;

use App\Services\TaskBankRepository;
use App\Services\TaskDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * `TaskDataService` меняет источник, но не интерфейс: то же дерево тем,
 * блоков и задач, что и раньше из JSON.
 *
 * Отдельно проверяется откат на файл: миграции на проде запускаются не
 * деплоем, а отдельной командой, поэтому между выкладкой кода и переездом
 * данных сервис обязан работать по-старому.
 */
class TaskDataServiceReadsDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();
    }

    public function test_falls_back_to_json_while_database_is_empty(): void
    {
        $service = new TaskDataService();

        $fromFile = $service->getTopicData('16');

        $this->assertNotEmpty($fromFile['blocks'] ?? [], 'тема 16 не прочиталась из файла');
        $this->assertSame(
            $this->canonical(json_decode(File::get(storage_path('app/tasks/topic_16.json')), true)['blocks']),
            $this->canonical($this->stripNormalization($fromFile['blocks'])),
        );
    }

    public function test_database_becomes_the_source_once_the_topic_is_imported(): void
    {
        $service = new TaskDataService();
        $fromFile = $service->getTopicData('16');

        Artisan::call('tasks:import-json', ['--bank' => 'oge']);
        Cache::flush();

        $fromDb = (new TaskDataService())->getTopicData('16');

        $this->assertSame($this->canonical($fromFile), $this->canonical($fromDb));
    }

    public function test_block_order_and_titles_survive(): void
    {
        Artisan::call('tasks:import-json', ['--bank' => 'oge']);
        Cache::flush();

        $file = json_decode(File::get(storage_path('app/tasks/topic_07.json')), true);
        $db = (new TaskDataService())->getTopicData('07');

        $this->assertSame(
            array_map(static fn (array $b) => [$b['number'] ?? null, $b['title'] ?? null], $file['blocks']),
            array_map(static fn (array $b) => [$b['number'] ?? null, $b['title'] ?? null], $db['blocks']),
        );
    }

    public function test_topic_level_fields_are_not_lost(): void
    {
        Artisan::call('tasks:import-json', ['--bank' => 'oge']);
        Cache::flush();

        $db = (new TaskDataService())->getTopicData('07');

        // `topic_id` читает OgeAttemptService, `meta` — соседние сервисы банков.
        $this->assertSame('07', $db['topic_id'] ?? null);
        $this->assertSame('Числа, координатная прямая', $db['meta']['title'] ?? null);
    }

    /**
     * `getTopicData()` пропускает данные через OptionRenderModePolicy, и он
     * дописывает `options_render_mode`. Для сверки с сырым файлом этот ключ
     * убираем — сравниваем источник, а не нормализацию.
     */
    private function stripNormalization(array $blocks): array
    {
        foreach ($blocks as &$block) {
            foreach ($block['zadaniya'] ?? [] as &$zadanie) {
                unset($zadanie['options_render_mode']);
                foreach ($zadanie['tasks'] ?? [] as &$task) {
                    unset($task['options_render_mode']);
                }
            }
        }
        return $blocks;
    }

    private function canonical(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        $isList = array_is_list($value);
        $value = array_map(fn ($v) => $this->canonical($v), $value);
        if (!$isList) {
            ksort($value);
        }
        return $value;
    }
}
