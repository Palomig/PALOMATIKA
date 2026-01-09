<?php

namespace App\Console\Commands;

use App\Http\Controllers\TestPdfController;
use App\Services\TaskDataService;
use Illuminate\Console\Command;

/**
 * Команда для экспорта данных заданий из TestPdfController в JSON-файлы
 */
class ExportTasksToJson extends Command
{
    protected $signature = 'tasks:export
                            {--topic= : Экспортировать только указанную тему (например: 06)}
                            {--force : Перезаписать существующие файлы}';

    protected $description = 'Экспортировать задания из TestPdfController в JSON-файлы';

    protected TaskDataService $taskService;
    protected TestPdfController $legacyController;

    public function __construct(TaskDataService $taskService)
    {
        parent::__construct();
        $this->taskService = $taskService;
    }

    public function handle(): int
    {
        $this->legacyController = app(TestPdfController::class);

        $topics = $this->option('topic')
            ? [$this->option('topic')]
            : ['06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19'];

        $force = $this->option('force');

        $this->info('Начинаю экспорт заданий в JSON...');
        $this->newLine();

        $exported = 0;
        $skipped = 0;

        foreach ($topics as $topicId) {
            $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);

            if (!$force && $this->taskService->topicDataExists($topicId)) {
                $this->warn("  ⏭  Тема {$topicId}: файл уже существует (используйте --force для перезаписи)");
                $skipped++;
                continue;
            }

            $blocks = $this->getBlocksFromLegacy($topicId);

            if (empty($blocks)) {
                $this->error("  ❌ Тема {$topicId}: не удалось получить данные");
                continue;
            }

            $data = [
                'topic_id' => $topicId,
                'meta' => $this->taskService->getTopicMeta($topicId),
                'exported_at' => now()->toIso8601String(),
                'blocks' => $blocks,
            ];

            if ($this->taskService->saveTopicData($topicId, $data)) {
                $stats = $this->taskService->getTopicStats($topicId);
                $this->info("  ✅ Тема {$topicId}: {$stats['blocks']} блоков, {$stats['tasks']} заданий");
                $exported++;
            } else {
                $this->error("  ❌ Тема {$topicId}: ошибка сохранения");
            }
        }

        $this->newLine();
        $this->info("Экспорт завершён: {$exported} тем экспортировано, {$skipped} пропущено");

        return Command::SUCCESS;
    }

    /**
     * Получить данные из старого контроллера через рефлексию
     */
    protected function getBlocksFromLegacy(string $topicId): array
    {
        $methodName = $topicId === '06' ? 'getAllBlocksData' : "getAllBlocksData{$topicId}";

        if (!method_exists($this->legacyController, $methodName)) {
            return [];
        }

        try {
            $reflection = new \ReflectionMethod($this->legacyController, $methodName);
            $reflection->setAccessible(true);
            return $reflection->invoke($this->legacyController);
        } catch (\Exception $e) {
            $this->error("Ошибка рефлексии для {$methodName}: " . $e->getMessage());
            return [];
        }
    }
}
