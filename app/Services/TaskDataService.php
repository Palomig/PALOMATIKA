<?php

namespace App\Services;

use App\Models\TaskStatus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Сервис для работы с данными заданий
 *
 * Хранит задания в JSON-файлах в storage/app/tasks/
 * Обеспечивает единый источник данных для web и API
 */
class TaskDataService
{
    protected string $basePath;

    /**
     * Метаданные тем
     */
    protected array $topicsMeta = [
        '06' => [
            'title' => 'Дроби и степени',
            'description' => 'Вычисления с дробями и степенями',
            'color' => 'blue',
            'icon' => 'calculator',
        ],
        '07' => [
            'title' => 'Числа, координатная прямая',
            'description' => 'Сравнение чисел и работа с координатной прямой',
            'color' => 'cyan',
            'icon' => 'ruler',
        ],
        '08' => [
            'title' => 'Квадратные корни и степени',
            'description' => 'Вычисления с корнями и степенями',
            'color' => 'teal',
            'icon' => 'square-root',
        ],
        '09' => [
            'title' => 'Уравнения',
            'description' => 'Решение уравнений',
            'color' => 'emerald',
            'icon' => 'equals',
        ],
        '10' => [
            'title' => 'Теория вероятностей',
            'description' => 'Вычисление вероятностей событий',
            'color' => 'green',
            'icon' => 'dice',
        ],
        '11' => [
            'title' => 'Графики функций',
            'description' => 'Соответствие графиков и формул',
            'color' => 'lime',
            'icon' => 'chart-line',
        ],
        '12' => [
            'title' => 'Расчёты по формулам',
            'description' => 'Практические задачи с формулами',
            'color' => 'yellow',
            'icon' => 'function',
        ],
        '13' => [
            'title' => 'Неравенства',
            'description' => 'Решение неравенств',
            'color' => 'amber',
            'icon' => 'less-than',
        ],
        '14' => [
            'title' => 'Прогрессии',
            'description' => 'Арифметические и геометрические прогрессии',
            'color' => 'orange',
            'icon' => 'trending-up',
        ],
        '15' => [
            'title' => 'Треугольники',
            'description' => 'Геометрия треугольников',
            'color' => 'red',
            'icon' => 'triangle',
        ],
        '16' => [
            'title' => 'Окружность',
            'description' => 'Свойства окружности',
            'color' => 'rose',
            'icon' => 'circle',
        ],
        '17' => [
            'title' => 'Четырёхугольники',
            'description' => 'Свойства четырёхугольников',
            'color' => 'pink',
            'icon' => 'square',
        ],
        '18' => [
            'title' => 'Фигуры на клетчатой бумаге',
            'description' => 'Площади и длины на решётке',
            'color' => 'fuchsia',
            'icon' => 'grid',
        ],
        '19' => [
            'title' => 'Анализ геометрических высказываний',
            'description' => 'Верные и неверные утверждения',
            'color' => 'purple',
            'icon' => 'check-circle',
        ],
        '20' => [
            'title' => 'Графики и уравнения',
            'description' => 'Графики функций и уравнения',
            'color' => 'violet',
            'icon' => 'chart-line',
        ],
        '21' => [
            'title' => 'Текстовые задачи',
            'description' => 'Текстовые задачи на движение, работу и др.',
            'color' => 'indigo',
            'icon' => 'file-text',
        ],
        '23' => [
            'title' => 'Геометрическая задача на вычисление',
            'description' => 'Геометрические задачи на вычисление',
            'color' => 'sky',
            'icon' => 'compass',
        ],
    ];

    public function __construct()
    {
        $this->basePath = storage_path('app/tasks');

        // Автоматически создаём директорию если её нет
        if (!File::isDirectory($this->basePath)) {
            File::makeDirectory($this->basePath, 0755, true);
        }
    }

    /**
     * Получить метаданные темы
     */
    public function getTopicMeta(string $topicId): array
    {
        return $this->topicsMeta[$topicId] ?? [
            'title' => "Тема $topicId",
            'description' => '',
            'color' => 'gray',
            'icon' => 'book',
        ];
    }

    /**
     * Получить все метаданные тем
     */
    public function getAllTopicsMeta(): array
    {
        return $this->topicsMeta;
    }

    /**
     * Получить данные темы из JSON
     */
    public function getTopicData(string $topicId): array
    {
        // Auto-bake SVG in local environment if geometry file is newer
        if (app()->environment('local')) {
            $this->autoBakeIfNeeded($topicId);
        }

        $cacheKey = "topic_data_{$topicId}";

        return Cache::remember($cacheKey, 3600, function () use ($topicId) {
            $filePath = "{$this->basePath}/topic_{$topicId}.json";

            if (!File::exists($filePath)) {
                return [];
            }

            $content = File::get($filePath);
            $data = json_decode($content, true) ?? [];

            $data = app(OptionRenderModePolicy::class)->normalizeTopicData($topicId, $data);

            if ($topicId === '13') {
                $data = app(Topic13RuntimeSvgMigrationService::class)->migrate($data);
            }

            return $data;
        });
    }

    /**
     * Auto-bake SVG if geometry file is newer than output (local env only)
     */
    protected function autoBakeIfNeeded(string $topicId): void
    {
        $geometryPath = "{$this->basePath}/topic_{$topicId}_geometry.json";
        $outputPath = "{$this->basePath}/topic_{$topicId}.json";

        // Skip if no geometry file exists
        if (!File::exists($geometryPath)) {
            return;
        }

        $needsBake = false;

        if (!File::exists($outputPath)) {
            $needsBake = true;
        } else {
            $geometryTime = File::lastModified($geometryPath);
            $outputTime = File::lastModified($outputPath);
            $needsBake = $geometryTime > $outputTime;
        }

        if ($needsBake) {
            try {
                // Clear cache for this topic before baking
                Cache::forget("topic_data_{$topicId}");

                // Run svg:bake command
                Artisan::call('svg:bake', ['topic' => $topicId]);

                // Log for debugging
                if (config('app.debug')) {
                    \Log::info("Auto-baked SVG for topic {$topicId}");
                }
            } catch (\Exception $e) {
                \Log::warning("Auto-bake failed for topic {$topicId}: " . $e->getMessage());
            }
        }
    }

    /**
     * Получить блоки темы
     *
     * @param string|null $status Filter tasks by status ('production', 'draft', or null for all)
     */
    public function getBlocks(string $topicId, ?string $status = null): array
    {
        $data = $this->getTopicData($topicId);
        $blocks = $data['blocks'] ?? [];

        if ($status !== null) {
            $blocks = $this->filterBlocksByStatus($topicId, $blocks, $status);
        }

        return $blocks;
    }

    /**
     * Filter blocks to only include tasks with the given status.
     * Removes empty zadaniya and empty blocks after filtering.
     */
    protected function filterBlocksByStatus(string $topicId, array $blocks, string $status): array
    {
        $filtered = [];

        foreach ($blocks as $block) {
            $filteredZadaniya = [];
            $blockNumber = (int) ($block['number'] ?? 0);

            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $zadanieNumber = (int) ($zadanie['number'] ?? 0);

                if (($zadanie['type'] ?? '') === 'statements' && isset($zadanie['statements'])) {
                    // Filter individual statements by status
                    $filteredStatements = array_values(array_filter(
                        $zadanie['statements'],
                        fn ($s) => $this->resolveItemStatus(
                            $topicId,
                            $blockNumber,
                            $zadanieNumber,
                            'statement',
                            (int) ($s['id'] ?? 0),
                            (string) ($s['status'] ?? 'draft')
                        ) === $status
                    ));

                    if (!empty($filteredStatements)) {
                        $zadanie['statements'] = $filteredStatements;
                        $filteredZadaniya[] = $zadanie;
                    }
                } else {
                    // Filter tasks by status
                    $filteredTasks = array_values(array_filter(
                        $zadanie['tasks'] ?? [],
                        fn ($t) => $this->resolveItemStatus(
                            $topicId,
                            $blockNumber,
                            $zadanieNumber,
                            'task',
                            (int) ($t['id'] ?? 0),
                            (string) ($t['status'] ?? 'draft')
                        ) === $status
                    ));

                    if (!empty($filteredTasks)) {
                        $zadanie['tasks'] = $filteredTasks;
                        $filteredZadaniya[] = $zadanie;
                    }
                }
            }

            if (!empty($filteredZadaniya)) {
                $block['zadaniya'] = $filteredZadaniya;
                $filtered[] = $block;
            }
        }

        return $filtered;
    }

    protected function resolveItemStatus(
        string $topicId,
        int $blockNumber,
        int $zadanieNumber,
        string $itemType,
        int $itemId,
        string $fallback = 'draft'
    ): string {
        if ($itemId < 1 || $blockNumber < 1 || $zadanieNumber < 0) {
            return $fallback;
        }

        $taskKey = sprintf(
            'topic_%s_block_%d_zadanie_%d_%s_%d',
            str_pad($topicId, 2, '0', STR_PAD_LEFT),
            $blockNumber,
            $zadanieNumber,
            $itemType,
            $itemId
        );

        $map = $this->getTopicStatusMapFromDb($topicId);

        return $map[$taskKey] ?? $fallback;
    }

    protected function getTopicStatusMapFromDb(string $topicId): array
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
        $cacheKey = "task_status_map_{$topicId}";

        return Cache::remember($cacheKey, 300, function () use ($topicId) {
            return TaskStatus::query()
                ->where('topic_id', $topicId)
                ->pluck('status', 'task_key')
                ->toArray();
        });
    }

    public function upsertStatusByTaskKey(string $topicId, string $taskKey, string $status): void
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);

        TaskStatus::query()->updateOrCreate(
            ['topic_id' => $topicId, 'task_key' => $taskKey],
            ['status' => $status]
        );

        Cache::forget("task_status_map_{$topicId}");
    }

    public function bulkUpsertStatusByTaskKeys(string $topicId, array $taskKeys, string $status): int
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
        $now = now();
        $rows = [];

        foreach ($taskKeys as $taskKey) {
            $rows[] = [
                'topic_id' => $topicId,
                'task_key' => $taskKey,
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            TaskStatus::query()->upsert($rows, ['topic_id', 'task_key'], ['status', 'updated_at']);
            Cache::forget("task_status_map_{$topicId}");
        }

        return count($rows);
    }

    /**
     * Получить статистику темы
     */
    public function getTopicStats(string $topicId): array
    {
        $blocks = $this->getBlocks($topicId);

        $totalTasks = 0;
        $totalZadaniya = 0;

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $totalZadaniya++;

                // Для statements задачи в самом задании
                if (($zadanie['type'] ?? '') === 'statements' && isset($zadanie['statements'])) {
                    $totalTasks += count($zadanie['statements']);
                } else {
                    $totalTasks += count($zadanie['tasks'] ?? []);
                }
            }
        }

        return [
            'blocks' => count($blocks),
            'zadaniya' => $totalZadaniya,
            'tasks' => $totalTasks,
        ];
    }

    /**
     * Получить случайные задания из темы
     */
    public function getRandomTasks(string $topicId, int $count = 1, ?string $status = null): array
    {
        $blocks = $this->getBlocks($topicId, $status);
        $allTasks = [];
        $meta = $this->getTopicMeta($topicId);

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                // Для statements — сами statements являются "задачами"
                if (($zadanie['type'] ?? '') === 'statements' && isset($zadanie['statements'])) {
                    $allTasks[] = [
                        'topic_id' => $topicId,
                        'topic_title' => $meta['title'],
                        'block_number' => $block['number'],
                        'block_title' => $block['title'],
                        'zadanie_number' => $zadanie['number'],
                        'instruction' => $zadanie['instruction'],
                        'type' => 'statements',
                        'section' => $zadanie['section'] ?? null,
                        'statements' => $zadanie['statements'],
                    ];
                } else {
                    foreach ($zadanie['tasks'] ?? [] as $task) {
                        $allTasks[] = [
                            'topic_id' => $topicId,
                            'topic_title' => $meta['title'],
                            'block_number' => $block['number'],
                            'block_title' => $block['title'],
                            'zadanie_number' => $zadanie['number'],
                            'instruction' => $zadanie['instruction'],
                            'type' => $zadanie['type'] ?? 'expression',
                            'svg_type' => $zadanie['svg_type'] ?? null,
                            'options_render_mode' => $zadanie['options_render_mode'] ?? null,
                            'points' => $zadanie['points'] ?? null,
                            'options' => $zadanie['options'] ?? null,
                            'task' => $task,
                        ];
                    }
                }
            }
        }

        if (empty($allTasks)) {
            return [];
        }

        shuffle($allTasks);
        return array_slice($allTasks, 0, $count);
    }

    /**
     * Получить случайные задания из конкретного блока темы
     */
    public function getRandomTasksFromBlock(string $topicId, int $blockNumber, int $count = 1): array
    {
        $blocks = $this->getBlocks($topicId);
        $meta = $this->getTopicMeta($topicId);
        $allTasks = [];

        // Находим нужный блок
        foreach ($blocks as $block) {
            if ($block['number'] == $blockNumber) {
                foreach ($block['zadaniya'] ?? [] as $zadanie) {
                    // Для statements — сами statements являются "задачами"
                    if (($zadanie['type'] ?? '') === 'statements' && isset($zadanie['statements'])) {
                        $allTasks[] = [
                            'topic_id' => $topicId,
                            'topic_title' => $meta['title'],
                            'block_number' => $block['number'],
                            'block_title' => $block['title'],
                            'zadanie_number' => $zadanie['number'],
                            'instruction' => $zadanie['instruction'],
                            'type' => 'statements',
                            'section' => $zadanie['section'] ?? null,
                            'statements' => $zadanie['statements'],
                        ];
                    } else {
                        foreach ($zadanie['tasks'] ?? [] as $task) {
                            $allTasks[] = [
                                'topic_id' => $topicId,
                                'topic_title' => $meta['title'],
                                'block_number' => $block['number'],
                                'block_title' => $block['title'],
                                'zadanie_number' => $zadanie['number'],
                                'instruction' => $zadanie['instruction'],
                                'type' => $zadanie['type'] ?? 'expression',
                                'svg_type' => $zadanie['svg_type'] ?? null,
                                'options_render_mode' => $zadanie['options_render_mode'] ?? null,
                                'points' => $zadanie['points'] ?? null,
                                'options' => $zadanie['options'] ?? null,
                                'task' => $task,
                            ];
                        }
                    }
                }
                break;
            }
        }

        if (empty($allTasks)) {
            return [];
        }

        shuffle($allTasks);
        return array_slice($allTasks, 0, $count);
    }

    /**
     * Получить случайные задания из конкретного zadanie
     */
    public function getRandomTasksFromZadanie(string $topicId, int $blockNumber, int $zadanieNumber, int $count = 1): array
    {
        $blocks = $this->getBlocks($topicId);
        $meta = $this->getTopicMeta($topicId);
        $allTasks = [];

        // Находим нужный блок
        foreach ($blocks as $block) {
            if ($block['number'] == $blockNumber) {
                // Находим нужное zadanie
                foreach ($block['zadaniya'] ?? [] as $zadanie) {
                    if ($zadanie['number'] == $zadanieNumber) {
                        // Для statements — сами statements являются "задачами"
                        if (($zadanie['type'] ?? '') === 'statements' && isset($zadanie['statements'])) {
                            $allTasks[] = [
                                'topic_id' => $topicId,
                                'topic_title' => $meta['title'],
                                'block_number' => $block['number'],
                                'block_title' => $block['title'],
                                'zadanie_number' => $zadanie['number'],
                                'instruction' => $zadanie['instruction'],
                                'type' => 'statements',
                                'section' => $zadanie['section'] ?? null,
                                'statements' => $zadanie['statements'],
                            ];
                        } else {
                            foreach ($zadanie['tasks'] ?? [] as $task) {
                                $allTasks[] = [
                                    'topic_id' => $topicId,
                                    'topic_title' => $meta['title'],
                                    'block_number' => $block['number'],
                                    'block_title' => $block['title'],
                                    'zadanie_number' => $zadanie['number'],
                                    'instruction' => $zadanie['instruction'],
                                    'type' => $zadanie['type'] ?? 'expression',
                                    'svg_type' => $zadanie['svg_type'] ?? null,
                                    'options_render_mode' => $zadanie['options_render_mode'] ?? null,
                                    'points' => $zadanie['points'] ?? null,
                                    // Сначала проверяем options в задаче (для matching), затем в задании
                                    'options' => $task['options'] ?? $zadanie['options'] ?? null,
                                    'task' => $task,
                                    // SVG уже встроен в task['svg'] (если есть)
                                ];
                            }
                        }
                        break;
                    }
                }
                break;
            }
        }

        if (empty($allTasks)) {
            return [];
        }

        shuffle($allTasks);
        return array_slice($allTasks, 0, $count);
    }

    /**
     * Получить набор из 3 задач для matching типов (для варианта ОГЭ)
     * Формат: 3 графика (А, Б, В) и 3 формулы (1, 2, 3)
     */
    public function getRandomMatchingSet(string $topicId, ?string $status = null): ?array
    {
        $blocks = $this->getBlocks($topicId, $status);
        $meta = $this->getTopicMeta($topicId);

        // Собираем все zadaniya с типом matching
        $matchingZadaniya = [];

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $type = $zadanie['type'] ?? '';
                // Only use canonical matching pools for random matching sets.
                // Exclude block 2 legacy graph-derived pools to avoid unstable answer formats.
                if (in_array($type, ['matching', 'matching_signs', 'matching_4'], true) && (int) ($block['number'] ?? 0) === 1) {
                    $tasks = $zadanie['tasks'] ?? [];
                    // Нужно минимум 3 задачи
                    if (count($tasks) >= 3) {
                        $matchingZadaniya[] = [
                            'block' => $block,
                            'zadanie' => $zadanie,
                        ];
                    }
                }
            }
        }

        if (empty($matchingZadaniya)) {
            return null;
        }

        // Выбираем случайное zadanie
        $selected = $matchingZadaniya[array_rand($matchingZadaniya)];
        $block = $selected['block'];
        $zadanie = $selected['zadanie'];
        $tasks = $zadanie['tasks'];

        // Выбираем 3 случайных задачи
        $keys = array_rand($tasks, 3);
        shuffle($keys);

        $selectedTasks = [];
        $allFormulas = [];

        foreach ($keys as $key) {
            $task = $tasks[$key];
            $selectedTasks[] = $task;

            // Собираем первую формулу (правильный ответ) для каждого графика
            if (!empty($task['options'][0])) {
                $allFormulas[] = $task['options'][0];
            }
        }

        // Перемешиваем формулы для варианта
        shuffle($allFormulas);

        return [
            'topic_id' => $topicId,
            'topic_title' => $meta['title'],
            'block_number' => $block['number'],
            'block_title' => $block['title'],
            'zadanie_number' => $zadanie['number'],
            'instruction' => $zadanie['instruction'],
            'type' => $zadanie['type'],
            'tasks' => $selectedTasks,          // 3 задачи с графиками
            'formulas' => $allFormulas,         // 3 перемешанных формулы
            'is_matching_set' => true,          // Флаг для компонента
        ];
    }

    /**
     * Get all task references with production status for a topic.
     *
     * @return array<int, array{topic_id: string, block_number: int, zadanie_number: int, task_id: int}>
     */
    public function getProductionTaskRefs(string $topicId): array
    {
        $blocks = $this->getBlocks($topicId, 'production');
        $refs = [];

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    $refs[] = [
                        'topic_id' => $topicId,
                        'block_number' => (int) $block['number'],
                        'zadanie_number' => (int) $zadanie['number'],
                        'task_id' => (int) ($task['id'] ?? 0),
                    ];
                }
            }
        }

        return $refs;
    }

    /**
     * Check if a specific task has production status.
     */
    public function isTaskProduction(string $topicId, int $taskId): bool
    {
        $blocks = $this->getBlocks($topicId);

        foreach ($blocks as $block) {
            $blockNumber = (int) ($block['number'] ?? 0);
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $zadanieNumber = (int) ($zadanie['number'] ?? 0);
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    if ((int) ($task['id'] ?? 0) === $taskId) {
                        $resolved = $this->resolveItemStatus(
                            $topicId,
                            $blockNumber,
                            $zadanieNumber,
                            'task',
                            $taskId,
                            (string) ($task['status'] ?? 'draft')
                        );
                        return $resolved === 'production';
                    }
                }
            }
        }

        return false;
    }

    /**
     * Сохранить данные темы в JSON
     */
    public function saveTopicData(string $topicId, array $data): bool
    {
        // Capture old statuses before saving
        $oldStatuses = $this->getTaskStatusMap($topicId);

        $filePath = "{$this->basePath}/topic_{$topicId}.json";

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $result = File::put($filePath, $json);

        // Очистить кэш
        Cache::forget("topic_data_{$topicId}");

        // Auto-sync variant pool if statuses changed
        if ($result !== false) {
            $newStatuses = $this->extractTaskStatusMapFromData($data);
            $this->syncVariantPoolOnStatusChange($topicId, $oldStatuses, $newStatuses);
        }

        return $result !== false;
    }

    /**
     * Get a map of task_id => status for current topic data.
     */
    protected function getTaskStatusMap(string $topicId): array
    {
        $data = $this->getTopicData($topicId);
        return $this->extractTaskStatusMapFromData($data);
    }

    /**
     * Extract task_id => status map from topic data array.
     */
    protected function extractTaskStatusMapFromData(array $data): array
    {
        $map = [];

        foreach ($data['blocks'] ?? [] as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    $id = (int) ($task['id'] ?? 0);
                    if ($id > 0) {
                        $map[$id] = $task['status'] ?? 'draft';
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Sync variant pool when task statuses change.
     */
    protected function syncVariantPoolOnStatusChange(string $topicId, array $oldStatuses, array $newStatuses): void
    {
        $changedToProduction = [];
        $changedToDraft = [];

        foreach ($newStatuses as $taskId => $newStatus) {
            $oldStatus = $oldStatuses[$taskId] ?? 'draft';
            if ($oldStatus !== $newStatus) {
                if ($newStatus === 'draft') {
                    $changedToDraft[] = $taskId;
                } elseif ($newStatus === 'production') {
                    $changedToProduction[] = $taskId;
                }
            }
        }

        // Also check tasks that were removed entirely
        foreach ($oldStatuses as $taskId => $oldStatus) {
            if (!isset($newStatuses[$taskId]) && $oldStatus === 'production') {
                $changedToDraft[] = $taskId;
            }
        }

        if (empty($changedToDraft) && empty($changedToProduction)) {
            return;
        }

        try {
            $poolService = app(OgeVariantPoolService::class);

            foreach ($changedToDraft as $taskId) {
                $poolService->deactivateVariantsWithTask($topicId, $taskId);
            }

            if (!empty($changedToProduction)) {
                $poolService->reactivateEligibleVariants();
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to sync variant pool after status change', [
                'topic_id' => $topicId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Проверить существование файла данных темы
     */
    public function topicDataExists(string $topicId): bool
    {
        return File::exists("{$this->basePath}/topic_{$topicId}.json");
    }

    /**
     * Получить список всех тем с данными
     */
    public function getAvailableTopics(): array
    {
        $available = [];

        foreach (array_keys($this->topicsMeta) as $topicId) {
            if ($this->topicDataExists($topicId)) {
                $available[$topicId] = array_merge(
                    $this->getTopicMeta($topicId),
                    ['stats' => $this->getTopicStats($topicId)]
                );
            }
        }

        return $available;
    }

    /**
     * Очистить весь кэш данных
     */
    public function clearCache(): void
    {
        foreach (array_keys($this->topicsMeta) as $topicId) {
            Cache::forget("topic_data_{$topicId}");
        }
    }

    public function isValidTaskKey(string $taskKey, ?string $topicId = null): bool
    {
        $matches = [];
        if (!preg_match('/^topic_(\d{2})_block_(\d+)_zadanie_(\d+)_(task|statement)_(\d+)$/', $taskKey, $matches)) {
            return false;
        }

        if ($topicId === null) {
            return true;
        }

        return $matches[1] === str_pad($topicId, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Parse a task_key into its components.
     * Returns null if invalid.
     */
    public function parseTaskKey(string $taskKey): ?array
    {
        if (!preg_match('/^topic_(\d{2})_block_(\d+)_zadanie_(\d+)_(task|statement)_(\d+)$/', $taskKey, $m)) {
            return null;
        }

        return [
            'topic_id' => $m[1],
            'block_number' => (int) $m[2],
            'zadanie_number' => (int) $m[3],
            'item_type' => $m[4], // 'task' or 'statement'
            'item_id' => (int) $m[5],
        ];
    }

    public function taskExistsByKey(string $topicId, string $taskKey): bool
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
        $parsed = $this->parseTaskKey($taskKey);
        if (!$parsed || $parsed['topic_id'] !== $topicId) {
            return false;
        }

        $blockNumber = $parsed['block_number'];
        $zadanieNumber = $parsed['zadanie_number'];
        $itemId = $parsed['item_id'];
        $itemType = $parsed['item_type'];

        if ($blockNumber < 1 || $zadanieNumber < 0 || $itemId < 1) {
            return false;
        }

        $blocks = $this->getBlocks($topicId);
        foreach ($blocks as $block) {
            if ((int) ($block['number'] ?? 0) !== $blockNumber) {
                continue;
            }

            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                if ((int) ($zadanie['number'] ?? 0) !== $zadanieNumber) {
                    continue;
                }

                $items = $itemType === 'statement' ? ($zadanie['statements'] ?? []) : ($zadanie['tasks'] ?? []);
                foreach ($items as $item) {
                    if ((int) ($item['id'] ?? 0) === $itemId) {
                        return true;
                    }
                }

                return false;
            }

            return false;
        }

        return false;
    }

    public function updateTaskAnswerByKey(string $topicId, string $taskKey, string $answer): bool
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
        if (!$this->isValidTaskKey($taskKey, $topicId)) {
            return false;
        }

        preg_match('/^topic_\d{2}_block_(\d+)_zadanie_(\d+)_task_(\d+)$/', $taskKey, $matches);
        $blockNumber = (int) ($matches[1] ?? 0);
        $zadanieNumber = (int) ($matches[2] ?? 0);
        $taskId = (int) ($matches[3] ?? 0);

        if ($blockNumber < 1 || $zadanieNumber < 0 || $taskId < 1) {
            return false;
        }

        $topicData = $this->getTopicData($topicId);
        if (empty($topicData) || !isset($topicData['blocks']) || !is_array($topicData['blocks'])) {
            return false;
        }

        $updated = false;
        foreach ($topicData['blocks'] as $blockIndex => $block) {
            if ((int) ($block['number'] ?? 0) !== $blockNumber) {
                continue;
            }

            if (!isset($topicData['blocks'][$blockIndex]['zadaniya']) || !is_array($topicData['blocks'][$blockIndex]['zadaniya'])) {
                continue;
            }

            foreach ($topicData['blocks'][$blockIndex]['zadaniya'] as $zadanieIndex => $zadanie) {
                if ((int) ($zadanie['number'] ?? 0) !== $zadanieNumber) {
                    continue;
                }

                if (!isset($topicData['blocks'][$blockIndex]['zadaniya'][$zadanieIndex]['tasks']) || !is_array($topicData['blocks'][$blockIndex]['zadaniya'][$zadanieIndex]['tasks'])) {
                    continue;
                }

                foreach ($topicData['blocks'][$blockIndex]['zadaniya'][$zadanieIndex]['tasks'] as $taskIndex => $task) {
                    if ((int) ($task['id'] ?? 0) === $taskId) {
                        $topicData['blocks'][$blockIndex]['zadaniya'][$zadanieIndex]['tasks'][$taskIndex]['answer'] = $answer;
                        $updated = true;
                        break 3;
                    }
                }
            }
        }

        if (!$updated) {
            return false;
        }

        return $this->saveTopicData($topicId, $topicData);
    }
}
