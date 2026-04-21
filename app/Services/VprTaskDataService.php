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

    // Количество тем ВПР по классам (5-7: 17 заданий, 8: 18 заданий).
    public const GRADE_TOPIC_COUNT = [
        5 => 17,
        6 => 17,
        7 => 17,
        8 => 18,
    ];

    // Generic-метаданные (цвет/иконка) — одинаковы для всех классов.
    // Заголовки переопределяются per-grade через $gradeTopicTitles.
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

    // Grade-specific названия и описания тем. Нет записи ⇒ fallback «Задание N».
    protected array $gradeTopicMeta = [
        5 => [
            '01' => ['title' => 'Обыкновенные дроби',              'description' => 'Вычисления и преобразования обыкновенных дробей'],
            '02' => ['title' => 'Часть и целое',                   'description' => 'Текстовые задачи на нахождение части, остатка и целого'],
            '03' => ['title' => 'Неизвестный компонент',           'description' => 'Равенства на нахождение неизвестного числа'],
            '04' => ['title' => 'Диаграммы',                        'description' => 'Чтение данных, сравнение и подсчёт по диаграммам'],
            '05' => ['title' => 'Площадь фигур на клетчатой бумаге','description' => 'Нахождение площади фигур по клеткам и разбиение на простые части'],
            '06' => ['title' => 'Числовой луч',                    'description' => 'Координаты точек и сравнение чисел на числовом луче'],
        ],
        6 => [],
        7 => [],
        8 => [],
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
        $base = $this->topicsMeta[$topicId] ?? [
            'title' => "Задание $topicId", 'description' => '',
            'color' => 'gray', 'icon' => 'book',
        ];

        $override = $this->gradeTopicMeta[$this->grade][$topicId] ?? [];

        return array_merge($base, $override);
    }

    public function getAllTopicsMeta(): array { return $this->topicsMeta; }

    /**
     * Максимальный номер темы для этого класса (5-7 → 17, 8 → 18).
     */
    public function getMaxTopic(): int
    {
        return self::GRADE_TOPIC_COUNT[$this->grade] ?? 18;
    }

    /**
     * Карта `[topicId => title]` только для тем, существующих у этого класса.
     * Использует grade-specific названия с фолбэком на «Задание N».
     *
     * @return array<string, string>
     */
    public function getTopicNamesMap(): array
    {
        $map = [];
        for ($n = 1; $n <= $this->getMaxTopic(); $n++) {
            $topicId = str_pad((string) $n, 2, '0', STR_PAD_LEFT);
            $map[$topicId] = $this->getTopicMeta($topicId)['title'];
        }
        return $map;
    }

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
            return $this->normalizeTopicData($topicId, $data);
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
                    $task = $this->normalizeTask($topicId, $task, $zadanie);
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

    private function normalizeTask(string $topicId, array $task, array $zadanie): array
    {
        $task = $this->canonicalizeLegacyTask($topicId, $task);

        if (
            $this->grade === 5
            && $topicId === '01'
            && empty($task['text'])
            && isset($task['expression'], $task['denominator'])
        ) {
            $task['text'] = sprintf(
                'Представьте число %s в виде дроби со знаменателем %s. Какой числитель получится?',
                $task['expression'],
                $task['denominator']
            );
        }

        return $task;
    }

    private function normalizeTopicData(string $topicId, array $data): array
    {
        if (empty($data['blocks']) || !is_array($data['blocks'])) {
            return $data;
        }

        foreach ($data['blocks'] as $blockIndex => $block) {
            if (!is_array($block) || empty($block['zadaniya']) || !is_array($block['zadaniya'])) {
                continue;
            }

            foreach ($block['zadaniya'] as $zadanieIndex => $zadanie) {
                if (!is_array($zadanie) || empty($zadanie['tasks']) || !is_array($zadanie['tasks'])) {
                    continue;
                }

                foreach ($zadanie['tasks'] as $taskIndex => $task) {
                    if (!is_array($task)) {
                        continue;
                    }

                    $data['blocks'][$blockIndex]['zadaniya'][$zadanieIndex]['tasks'][$taskIndex] =
                        $this->normalizeTask($topicId, $task, $zadanie);
                }
            }
        }

        return $data;
    }

    public function canonicalizeLegacyTask(string $topicId, array $task): array
    {
        $topicId = str_pad((string) $topicId, 2, '0', STR_PAD_LEFT);

        if (!$this->containsEmbeddedBase64Image($task)) {
            return $task;
        }

        $canonicalTask = $this->findCanonicalTask($topicId, $task);
        if ($canonicalTask === null) {
            return $task;
        }

        foreach (['text', 'expression', 'svg', 'image', 'table', 'options', 'answer', 'correct_answer', 'answer_1', 'answer_2'] as $field) {
            if (array_key_exists($field, $canonicalTask)) {
                $task[$field] = $canonicalTask[$field];
                continue;
            }

            unset($task[$field]);
        }

        return $task;
    }

    private function containsEmbeddedBase64Image(array $task): bool
    {
        $image = (string) ($task['image'] ?? '');

        return str_contains($image, 'data:image/png;base64')
            || str_contains($image, 'data:image/jpeg;base64')
            || str_contains($image, 'data:image/jpg;base64');
    }

    private function findCanonicalTask(string $topicId, array $legacyTask): ?array
    {
        $data = $this->getTopicData($topicId);
        $legacyId = (int) ($legacyTask['id'] ?? 0);
        $legacyVariant = (int) ($legacyTask['variant'] ?? 0);
        $legacySourcePdf = trim((string) ($legacyTask['source_pdf'] ?? ''));

        foreach ($data['blocks'] ?? [] as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    $taskId = (int) ($task['id'] ?? 0);
                    $taskVariant = (int) ($task['variant'] ?? 0);
                    $taskSourcePdf = trim((string) ($task['source_pdf'] ?? ''));

                    if ($legacyId > 0 && $taskId === $legacyId) {
                        return $task;
                    }

                    if ($legacyVariant > 0 && $taskVariant === $legacyVariant) {
                        return $task;
                    }

                    if ($legacySourcePdf !== '' && $taskSourcePdf !== '' && $taskSourcePdf === $legacySourcePdf) {
                        return $task;
                    }
                }
            }
        }

        return null;
    }
}
