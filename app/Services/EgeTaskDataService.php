<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Сервис для работы с данными заданий ЕГЭ
 *
 * Хранит задания в JSON-файлах в storage/app/tasks/ege/
 * Обособленный от ОГЭ источник данных
 */
class EgeTaskDataService
{
    protected string $basePath;

    /**
     * Метаданные заданий ЕГЭ (1-19)
     */
    protected array $topicsMeta = [
        '01' => [
            'title' => 'Планиметрия',
            'description' => 'Геометрия на плоскости',
            'color' => 'purple',
            'icon' => 'shapes',
        ],
        '02' => [
            'title' => 'Векторы',
            'description' => 'Координаты и векторы',
            'color' => 'indigo',
            'icon' => 'arrow-right',
        ],
        '03' => [
            'title' => 'Стереометрия',
            'description' => 'Геометрия в пространстве',
            'color' => 'blue',
            'icon' => 'cube',
        ],
        '04' => [
            'title' => 'Вероятности',
            'description' => 'Теория вероятностей',
            'color' => 'cyan',
            'icon' => 'dice',
        ],
        '05' => [
            'title' => 'Вероятности (сложные)',
            'description' => 'Сложные задачи на вероятность',
            'color' => 'teal',
            'icon' => 'percent',
        ],
        '06' => [
            'title' => 'Уравнения',
            'description' => 'Простейшие уравнения',
            'color' => 'emerald',
            'icon' => 'equals',
        ],
        '07' => [
            'title' => 'Значения функций',
            'description' => 'Вычисление значений функций',
            'color' => 'green',
            'icon' => 'function',
        ],
        '08' => [
            'title' => 'Производные',
            'description' => 'Производные и первообразные',
            'color' => 'lime',
            'icon' => 'trending-up',
        ],
        '09' => [
            'title' => 'Текстовые задачи',
            'description' => 'Физические и практические задачи',
            'color' => 'yellow',
            'icon' => 'calculator',
        ],
        '10' => [
            'title' => 'Графики',
            'description' => 'Чтение графиков и диаграмм',
            'color' => 'amber',
            'icon' => 'chart-line',
        ],
        '11' => [
            'title' => 'Прикладные задачи',
            'description' => 'Задачи с прикладным содержанием',
            'color' => 'orange',
            'icon' => 'wrench',
        ],
        '12' => [
            'title' => 'Наибольшее/наименьшее',
            'description' => 'Экстремумы функций',
            'color' => 'red',
            'icon' => 'maximize',
        ],
        '13' => [
            'title' => 'Уравнения (сложные)',
            'description' => 'Тригонометрические, показательные, логарифмические',
            'color' => 'rose',
            'icon' => 'sigma',
        ],
        '14' => [
            'title' => 'Стереометрия (сложная)',
            'description' => 'Построения и вычисления в пространстве',
            'color' => 'pink',
            'icon' => 'box',
        ],
        '15' => [
            'title' => 'Неравенства',
            'description' => 'Логарифмические и показательные неравенства',
            'color' => 'fuchsia',
            'icon' => 'less-than',
        ],
        '16' => [
            'title' => 'Экономические задачи',
            'description' => 'Финансовая математика',
            'color' => 'violet',
            'icon' => 'dollar',
        ],
        '17' => [
            'title' => 'Планиметрия (сложная)',
            'description' => 'Сложные планиметрические задачи',
            'color' => 'purple',
            'icon' => 'triangle',
        ],
        '18' => [
            'title' => 'Параметры',
            'description' => 'Задачи с параметрами',
            'color' => 'indigo',
            'icon' => 'variable',
        ],
        '19' => [
            'title' => 'Числа и свойства',
            'description' => 'Теория чисел',
            'color' => 'blue',
            'icon' => 'hash',
        ],
    ];

    public function __construct()
    {
        $this->basePath = storage_path('app/tasks/ege');

        // Автоматически создаём директорию если её нет
        if (!File::isDirectory($this->basePath)) {
            File::makeDirectory($this->basePath, 0755, true);
        }
    }

    /**
     * Получить метаданные задания ЕГЭ
     */
    public function getTopicMeta(string $topicId): array
    {
        // Сначала проверяем данные из JSON файла
        $data = $this->getTopicData($topicId);
        if (!empty($data['meta'])) {
            return $data['meta'];
        }

        // Fallback на захардкоженные метаданные
        return $this->topicsMeta[$topicId] ?? [
            'title' => "Задание $topicId",
            'description' => '',
            'color' => 'gray',
            'icon' => 'book',
        ];
    }

    /**
     * Получить все метаданные заданий
     */
    public function getAllTopicsMeta(): array
    {
        return $this->topicsMeta;
    }

    /**
     * Получить данные задания из JSON
     */
    public function getTopicData(string $topicId): array
    {
        $cacheKey = "ege_topic_data_{$topicId}";

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
     * Получить блоки задания
     */
    public function getBlocks(string $topicId): array
    {
        $data = $this->getTopicData($topicId);
        return $data['blocks'] ?? [];
    }

    /**
     * Получить статистику задания
     */
    public function getTopicStats(string $topicId): array
    {
        $blocks = $this->getBlocks($topicId);

        $totalTasks = 0;
        $totalZadaniya = 0;

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $totalZadaniya++;
                $totalTasks += count($zadanie['tasks'] ?? []);
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
        $meta = $this->getTopicMeta($topicId);
        $allTasks = [];

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    $allTasks[] = [
                        'topic_id' => $topicId,
                        'exam_type' => 'ege',
                        'topic_title' => $meta['title'],
                        'block_number' => $block['number'],
                        'block_title' => $block['title'],
                        'zadanie_number' => $zadanie['number'],
                        'instruction' => $zadanie['instruction'],
                        'type' => $zadanie['type'] ?? 'geometry',
                        'svg_type' => $zadanie['svg_type'] ?? null,
                        'task' => $task,
                    ];
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
     * Проверить существование файла данных задания
     */
    public function topicDataExists(string $topicId): bool
    {
        return File::exists("{$this->basePath}/topic_{$topicId}.json");
    }

    /**
     * Получить список всех заданий с данными
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
     * Получить случайные задания из конкретного zadanie
     */
    public function getRandomTasksFromZadanie(string $topicId, int $blockNumber, int $zadanieNumber, int $count = 1): array
    {
        $blocks = $this->getBlocks($topicId);
        $meta = $this->getTopicMeta($topicId);

        foreach ($blocks as $block) {
            if (($block['number'] ?? 0) != $blockNumber) continue;

            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                if (($zadanie['number'] ?? 0) != $zadanieNumber) continue;

                $tasks = $zadanie['tasks'] ?? [];
                if (empty($tasks)) return [];

                $selectedKeys = array_rand($tasks, min($count, count($tasks)));
                if (!is_array($selectedKeys)) $selectedKeys = [$selectedKeys];

                $result = [];
                foreach ($selectedKeys as $key) {
                    $result[] = [
                        'topic_id' => $topicId,
                        'exam_type' => 'ege',
                        'topic_title' => $meta['title'],
                        'block_number' => $block['number'],
                        'block_title' => $block['title'],
                        'zadanie_number' => $zadanie['number'],
                        'instruction' => $zadanie['instruction'],
                        'type' => $zadanie['type'] ?? 'word_problem',
                        'svg_type' => $zadanie['svg_type'] ?? null,
                        'task' => $tasks[$key],
                    ];
                }

                return $result;
            }
        }

        return [];
    }

    /**
     * Очистить весь кэш данных
     */
    public function clearCache(): void
    {
        foreach (array_keys($this->topicsMeta) as $topicId) {
            Cache::forget("ege_topic_data_{$topicId}");
        }
    }
}
