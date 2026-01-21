<?php

namespace App\Services;

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
        $cacheKey = "topic_data_{$topicId}";

        return Cache::remember($cacheKey, 3600, function () use ($topicId) {
            $filePath = "{$this->basePath}/topic_{$topicId}.json";

            if (!File::exists($filePath)) {
                return [];
            }

            $content = File::get($filePath);
            return json_decode($content, true) ?? [];
        });
    }

    /**
     * Получить блоки темы
     */
    public function getBlocks(string $topicId): array
    {
        $data = $this->getTopicData($topicId);
        return $data['blocks'] ?? [];
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
    public function getRandomTasks(string $topicId, int $count = 1): array
    {
        $blocks = $this->getBlocks($topicId);
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
                                    'points' => $zadanie['points'] ?? null,
                                    // FIXED: Сначала проверяем options в задаче (для matching), затем в задании
                                    'options' => $task['options'] ?? $zadanie['options'] ?? null,
                                    'task' => $task,
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
     * Сохранить данные темы в JSON
     */
    public function saveTopicData(string $topicId, array $data): bool
    {
        $filePath = "{$this->basePath}/topic_{$topicId}.json";

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $result = File::put($filePath, $json);

        // Очистить кэш
        Cache::forget("topic_data_{$topicId}");

        return $result !== false;
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
            Cache::forget("topic_data_geometry_{$topicId}");
        }
    }

    // ========================================================================
    // МЕТОДЫ ДЛЯ ГЕОМЕТРИИ С SVG-РЕНДЕРИНГОМ
    // ========================================================================

    /**
     * Получить данные темы с геометрией (из topic_XX_geometry.json)
     */
    public function getGeometryTopicData(string $topicId): array
    {
        $cacheKey = "topic_data_geometry_{$topicId}";

        return Cache::remember($cacheKey, 3600, function () use ($topicId) {
            $filePath = "{$this->basePath}/topic_{$topicId}_geometry.json";

            if (!File::exists($filePath)) {
                return [];
            }

            $content = File::get($filePath);
            return json_decode($content, true) ?? [];
        });
    }

    /**
     * Получить блоки темы с геометрией
     */
    public function getGeometryBlocks(string $topicId): array
    {
        $data = $this->getGeometryTopicData($topicId);
        return $data['blocks'] ?? [];
    }

    /**
     * Проверить существование файла геометрических данных
     */
    public function geometryDataExists(string $topicId): bool
    {
        return File::exists("{$this->basePath}/topic_{$topicId}_geometry.json");
    }

    /**
     * Добавить отрендеренный SVG к задаче
     *
     * @param array $taskData Данные задачи (с svg_type и geometry из zadanie)
     * @return array Задача с добавленным rendered_svg
     */
    public function getTaskWithRenderedSvg(array $taskData): array
    {
        // Если нет данных для рендеринга - возвращаем как есть
        if (!isset($taskData['svg_type']) || !isset($taskData['geometry'])) {
            return $taskData;
        }

        $renderer = app(GeometrySvgRenderer::class);

        if (!$renderer->supports($taskData['svg_type'])) {
            return $taskData;
        }

        $taskData['rendered_svg'] = $renderer->render(
            $taskData['svg_type'],
            $taskData['geometry'],
            $taskData['task']['params'] ?? []
        );

        return $taskData;
    }

    /**
     * Получить случайные задания с отрендеренным SVG
     */
    public function getRandomTasksWithSvg(string $topicId, int $count = 1): array
    {
        // Используем геометрические данные если есть
        if ($this->geometryDataExists($topicId)) {
            $blocks = $this->getGeometryBlocks($topicId);
        } else {
            $blocks = $this->getBlocks($topicId);
        }

        $meta = $this->getTopicMeta($topicId);
        $allTasks = [];

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    $taskData = [
                        'topic_id' => $topicId,
                        'topic_title' => $meta['title'],
                        'block_number' => $block['number'],
                        'block_title' => $block['title'],
                        'zadanie_number' => $zadanie['number'],
                        'instruction' => $zadanie['instruction'],
                        'type' => $zadanie['type'] ?? 'expression',
                        'svg_type' => $zadanie['svg_type'] ?? null,
                        'geometry' => $zadanie['geometry'] ?? null,
                        'task' => $task,
                    ];

                    // Рендерим SVG если есть данные
                    $allTasks[] = $this->getTaskWithRenderedSvg($taskData);
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
     * Получить блоки с отрендеренным SVG для всех задач
     */
    public function getBlocksWithRenderedSvg(string $topicId): array
    {
        // Используем геометрические данные если есть
        if ($this->geometryDataExists($topicId)) {
            $blocks = $this->getGeometryBlocks($topicId);
        } else {
            $blocks = $this->getBlocks($topicId);
        }

        $renderer = app(GeometrySvgRenderer::class);

        foreach ($blocks as &$block) {
            foreach ($block['zadaniya'] as &$zadanie) {
                // Пропускаем если нет данных для рендеринга
                if (!isset($zadanie['svg_type']) || !isset($zadanie['geometry'])) {
                    continue;
                }

                // Пропускаем если тип не поддерживается
                if (!$renderer->supports($zadanie['svg_type'])) {
                    continue;
                }

                // Рендерим SVG для каждой задачи
                foreach ($zadanie['tasks'] as &$task) {
                    $task['rendered_svg'] = $renderer->render(
                        $zadanie['svg_type'],
                        $zadanie['geometry'],
                        $task['params'] ?? []
                    );
                }
            }
        }

        return $blocks;
    }
}
