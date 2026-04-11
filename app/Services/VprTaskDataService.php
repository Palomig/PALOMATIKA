<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Сервис данных ВПР — аналог TaskDataService, но per-grade.
 * Данные: storage/app/tasks/vpr/grade_{N}/topic_NN.json
 */
class VprTaskDataService
{
    protected string $basePath;

    // Метаданные тем одинаковы для всех классов — 18 заданий.
    // Заголовки будут уточнены когда придут PDF.
    protected array $topicsMeta = [
        '01' => ['title' => 'Задание 1',  'description' => '', 'color' => 'blue',    'icon' => 'calculator'],
        '02' => ['title' => 'Задание 2',  'description' => '', 'color' => 'cyan',    'icon' => 'calculator'],
        '03' => ['title' => 'Задание 3',  'description' => '', 'color' => 'teal',    'icon' => 'calculator'],
        '04' => ['title' => 'Задание 4',  'description' => '', 'color' => 'emerald', 'icon' => 'calculator'],
        '05' => ['title' => 'Задание 5',  'description' => '', 'color' => 'green',   'icon' => 'calculator'],
        '06' => ['title' => 'Задание 6',  'description' => '', 'color' => 'lime',    'icon' => 'calculator'],
        '07' => ['title' => 'Задание 7',  'description' => '', 'color' => 'yellow',  'icon' => 'calculator'],
        '08' => ['title' => 'Задание 8',  'description' => '', 'color' => 'amber',   'icon' => 'calculator'],
        '09' => ['title' => 'Задание 9',  'description' => '', 'color' => 'orange',  'icon' => 'calculator'],
        '10' => ['title' => 'Задание 10', 'description' => '', 'color' => 'red',     'icon' => 'calculator'],
        '11' => ['title' => 'Задание 11', 'description' => '', 'color' => 'rose',    'icon' => 'calculator'],
        '12' => ['title' => 'Задание 12', 'description' => '', 'color' => 'pink',    'icon' => 'calculator'],
        '13' => ['title' => 'Задание 13', 'description' => '', 'color' => 'fuchsia', 'icon' => 'calculator'],
        '14' => ['title' => 'Задание 14', 'description' => '', 'color' => 'purple',  'icon' => 'calculator'],
        '15' => ['title' => 'Задание 15', 'description' => '', 'color' => 'violet',  'icon' => 'calculator'],
        '16' => ['title' => 'Задание 16', 'description' => '', 'color' => 'indigo',  'icon' => 'calculator'],
        '17' => ['title' => 'Задание 17', 'description' => '', 'color' => 'sky',     'icon' => 'calculator'],
        '18' => ['title' => 'Задание 18', 'description' => '', 'color' => 'slate',   'icon' => 'calculator'],
    ];

    public function __construct(protected int $grade)
    {
        $this->basePath = storage_path("app/tasks/vpr/grade_{$grade}");

        if (!File::isDirectory($this->basePath)) {
            File::makeDirectory($this->basePath, 0755, true);
        }
    }

    public function getGrade(): int { return $this->grade; }

    public function getTopicMeta(string $topicId): array
    {
        if ($this->grade === 5 && $topicId === '01') {
            return [
                'title' => 'Обыкновенные дроби',
                'description' => 'Вычисления и преобразования обыкновенных дробей',
                'color' => 'blue',
                'icon' => 'calculator',
            ];
        }

        if ($this->grade === 5 && $topicId === '02') {
            return [
                'title' => 'Часть и целое',
                'description' => 'Текстовые задачи на нахождение части, остатка и целого',
                'color' => 'cyan',
                'icon' => 'calculator',
            ];
        }

        return $this->topicsMeta[$topicId] ?? [
            'title' => "Задание $topicId", 'description' => '',
            'color' => 'gray', 'icon' => 'book',
        ];
    }

    public function getAllTopicsMeta(): array { return $this->topicsMeta; }

    public function topicDataExists(string $topicId): bool
    {
        return File::exists("{$this->basePath}/topic_{$topicId}.json");
    }

    public function getTopicData(string $topicId): array
    {
        $cacheKey = "vpr_g{$this->grade}_topic_{$topicId}";

        return Cache::remember($cacheKey, 3600, function () use ($topicId) {
            $path = "{$this->basePath}/topic_{$topicId}.json";
            if (!File::exists($path)) return [];
            $data = json_decode(File::get($path), true) ?? [];
            return $data;
        });
    }

    public function getBlocks(string $topicId): array
    {
        return $this->getTopicData($topicId)['blocks'] ?? [];
    }

    public function getTopicStats(string $topicId): array
    {
        $data  = $this->getTopicData($topicId);
        $total = 0;
        foreach ($data['blocks'] ?? [] as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $total += count($zadanie['tasks'] ?? []);
            }
        }
        return ['total_tasks' => $total];
    }

    /**
     * Выбрать случайную задачу из топика (статус production).
     */
    public function getRandomTaskFromTopic(string $topicId, ?string $status = 'production'): ?array
    {
        $data = $this->getTopicData($topicId);
        $candidates = [];

        foreach ($data['blocks'] ?? [] as $blockIdx => $block) {
            foreach ($block['zadaniya'] ?? [] as $zadIdx => $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    if ($status && ($task['status'] ?? 'production') !== $status) continue;
                    $candidates[] = [
                        'task'           => $task,
                        'topic_id'       => $topicId,
                        'block_number'   => $block['number'] ?? ($blockIdx + 1),
                        'zadanie_number' => $zadanie['number'] ?? ($zadIdx + 1),
                        'task_number'    => (int) ltrim($topicId, '0'),
                        'type'           => $zadanie['type'] ?? 'expression',
                        'instruction'    => $zadanie['instruction'] ?? '',
                    ];
                }
            }
        }

        if (empty($candidates)) return null;
        return $candidates[array_rand($candidates)];
    }
}
