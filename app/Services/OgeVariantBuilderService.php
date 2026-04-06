<?php

namespace App\Services;

use App\Models\OgeVariant;

class OgeVariantBuilderService
{
    public function __construct(private readonly TaskDataService $taskDataService)
    {
    }

    /**
     * Build deterministic OGE variant payload from hash and selected zadaniya list.
     *
     * @return array{tasks: array<int, array>, variantNumber: int, selectedZadaniya: array<int, string>}
     */
    public function build(string $hash, ?array $selectedZadaniya = null): array
    {
        $seed = crc32($hash);
        mt_srand($seed);

        $variantNumber = (abs($seed) % 999) + 1;
        $selectedZadaniya = $this->resolveSelectedZadaniya($hash, $selectedZadaniya);

        $tasks = [];
        $topicTitles = [
            '06' => 'Дроби и степени',
            '07' => 'Числа, координатная прямая',
            '08' => 'Квадратные корни и степени',
            '09' => 'Уравнения',
            '10' => 'Теория вероятностей',
            '11' => 'Графики функций',
            '12' => 'Расчёты по формулам',
            '13' => 'Неравенства',
            '14' => 'Прогрессии',
            '15' => 'Треугольники',
            '16' => 'Окружность',
            '17' => 'Четырёхугольники',
            '18' => 'Фигуры на клетчатой бумаге',
            '19' => 'Анализ геометрических высказываний',
        ];

        $zadaniyaByTopic = [];
        foreach ($selectedZadaniya as $zadanieId) {
            $parts = explode('_', $zadanieId);
            if (count($parts) !== 3) {
                continue;
            }

            [$topicIdRaw, $blockNumber, $zadanieNumber] = $parts;
            $topicId = str_pad((string) $topicIdRaw, 2, '0', STR_PAD_LEFT);
            $topicKey = 't' . $topicId; // Префикс не даёт PHP приводить ключ к int.

            if (!isset($zadaniyaByTopic[$topicKey])) {
                $zadaniyaByTopic[$topicKey] = [
                    'topic_id' => $topicId,
                    'zadaniya' => [],
                ];
            }

            $zadaniyaByTopic[$topicKey]['zadaniya'][] = [
                'block' => (int) $blockNumber,
                'zadanie' => (int) $zadanieNumber,
            ];
        }

        foreach ($zadaniyaByTopic as $topicGroup) {
            $topicId = $topicGroup['topic_id'];
            $zadaniyaList = $topicGroup['zadaniya'];

            if ($topicId === '11') {
                $matchingSet = $this->taskDataService->getRandomMatchingSet($topicId);
                if ($matchingSet) {
                    $matchingSet['topic_id'] = $topicId;
                    $matchingSet['topic_title'] = $topicTitles[$topicId] ?? '';
                    $matchingSet['task_number'] = (int) ltrim($topicId, '0');
                    $matchingSet['correct_answer'] = $this->buildMatchingCorrectAnswer($matchingSet);
                    $tasks[] = $matchingSet;
                }
                continue;
            }

            if ($topicId === '19') {
                $task = $this->buildStatementsTaskFromGlobalPool($topicId, $topicTitles[$topicId] ?? '');
                if ($task) {
                    $tasks[] = $task;
                }
                continue;
            }

            $randomIndex = $this->pickRandomIndex(count($zadaniyaList));
            if ($randomIndex === null) {
                continue;
            }

            $randomZadanie = $zadaniyaList[$randomIndex];

            $tasksFromZadanie = $this->taskDataService->getRandomTasksFromZadanie(
                $topicId,
                $randomZadanie['block'],
                $randomZadanie['zadanie'],
                1,
                'production'
            );

            if (empty($tasksFromZadanie)) {
                continue;
            }

            $task = $tasksFromZadanie[0];
            $task['topic_id'] = $topicId;
            $task['topic_title'] = $topicTitles[$topicId] ?? '';
            $task['task_number'] = (int) ltrim($topicId, '0');
            $task = $this->enrichTaskWithCorrectAnswer($task);
            $tasks[] = $task;
        }

        mt_srand();

        return [
            'tasks' => $tasks,
            'variantNumber' => $variantNumber,
            'selectedZadaniya' => $selectedZadaniya,
        ];
    }

    private function resolveSelectedZadaniya(string $hash, ?array $selectedZadaniya): array
    {
        if (is_array($selectedZadaniya) && !empty($selectedZadaniya)) {
            return $selectedZadaniya;
        }

        $fromCache = \Cache::get("oge_variant_{$hash}");
        if (is_array($fromCache) && !empty($fromCache)) {
            return $fromCache;
        }

        try {
            $variant = OgeVariant::where('hash', $hash)->first();
            $fromDb = $variant?->config_json['zadaniya'] ?? null;
            if (is_array($fromDb) && !empty($fromDb)) {
                return $fromDb;
            }
        } catch (\Throwable $e) {
            \Log::warning('Unable to resolve OGE variant from DB, fallback to defaults', [
                'hash' => $hash,
                'error' => $e->getMessage(),
            ]);
        }

        $defaultZadaniya = [];
        $defaultTopics = ['06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17'];
        foreach ($defaultTopics as $topicId) {
            $blocks = $this->taskDataService->getBlocks($topicId, 'production');
            foreach ($blocks as $block) {
                foreach ($block['zadaniya'] ?? [] as $zadanie) {
                    $defaultZadaniya[] = "{$topicId}_{$block['number']}_{$zadanie['number']}";
                }
            }
        }

        return $defaultZadaniya;
    }

    /**
     * Build a single statements task for topic 19 by pooling ALL statements
     * from every block and zadanie, then picking 2 true + 1 false.
     */
    private function buildStatementsTaskFromGlobalPool(string $topicId, string $topicTitle): ?array
    {
        $blocks = $this->taskDataService->getBlocks($topicId, 'production');

        $truePool  = [];
        $falsePool = [];

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] as $zadanie) {
                if (($zadanie['type'] ?? '') !== 'statements') {
                    continue;
                }
                foreach ($zadanie['statements'] ?? [] as $statement) {
                    if (!empty($statement['is_true'])) {
                        $truePool[]  = $statement;
                    } else {
                        $falsePool[] = $statement;
                    }
                }
            }
        }

        if (empty($truePool) || empty($falsePool)) {
            return null;
        }

        $pickedTrue  = $this->pickDistinctRandomIndexes(count($truePool),  min(2, count($truePool)));
        $pickedFalse = $this->pickDistinctRandomIndexes(count($falsePool), min(1, count($falsePool)));

        $selected = [];
        foreach ($pickedTrue  as $i) { $selected[] = $truePool[$i]; }
        foreach ($pickedFalse as $i) { $selected[] = $falsePool[$i]; }

        shuffle($selected); // mt_rand is seeded, so this is deterministic per hash

        $display = 1;
        $truthyIndexes = [];
        foreach ($selected as &$statement) {
            $statement['display_number'] = $display;
            if (!empty($statement['is_true'])) {
                $truthyIndexes[] = (string) $display;
            }
            $display++;
        }
        unset($statement);

        return [
            'type'                => 'statements',
            'instruction'         => 'Укажите номера верных утверждений.',
            'topic_id'            => $topicId,
            'topic_title'         => $topicTitle,
            'task_number'         => (int) ltrim($topicId, '0'),
            'selected_statements' => $selected,
            'correct_answer'      => implode('', $truthyIndexes),
        ];
    }

    private function enrichTaskWithCorrectAnswer(array $task): array
    {
        $type = $task['type'] ?? '';

        if ($type === 'statements' && isset($task['statements']) && is_array($task['statements'])) {
            $allStatements = $task['statements'];

            $truePool  = array_values(array_filter($allStatements, fn($s) => !empty($s['is_true'])));
            $falsePool = array_values(array_filter($allStatements, fn($s) =>  empty($s['is_true'])));

            $pickedTrue  = $this->pickDistinctRandomIndexes(count($truePool),  min(2, count($truePool)));
            $pickedFalse = $this->pickDistinctRandomIndexes(count($falsePool), min(1, count($falsePool)));

            $selected = [];
            foreach ($pickedTrue  as $i) { $selected[] = ['_orig_idx' => array_search($truePool[$i],  $allStatements), 'stmt' => $truePool[$i]]; }
            foreach ($pickedFalse as $i) { $selected[] = ['_orig_idx' => array_search($falsePool[$i], $allStatements), 'stmt' => $falsePool[$i]]; }

            usort($selected, fn($a, $b) => $a['_orig_idx'] <=> $b['_orig_idx']);
            $selected = array_column($selected, 'stmt');

            $display = 1;
            $truthyIndexes = [];
            foreach ($selected as &$statement) {
                $statement['display_number'] = $display;
                if (!empty($statement['is_true'])) {
                    $truthyIndexes[] = (string) $display;
                }
                $display++;
            }
            unset($statement);

            $task['selected_statements'] = $selected;
            $task['correct_answer'] = implode('', $truthyIndexes);
            return $task;
        }

        $task['correct_answer'] = $task['task']['answer'] ?? null;
        return $task;
    }

    /**
     * Deterministic random index based on mt_rand() seed configured in build().
     */
    private function pickRandomIndex(int $count): ?int
    {
        if ($count <= 0) {
            return null;
        }

        return mt_rand(0, $count - 1);
    }

    /**
     * Deterministically pick distinct indexes without relying on array_rand().
     *
     * @return array<int, int>
     */
    private function pickDistinctRandomIndexes(int $poolSize, int $take): array
    {
        if ($poolSize <= 0 || $take <= 0) {
            return [];
        }

        $available = range(0, $poolSize - 1);
        $picked = [];

        $limit = min($take, $poolSize);
        for ($i = 0; $i < $limit; $i++) {
            $idx = mt_rand(0, count($available) - 1);
            $picked[] = $available[$idx];
            array_splice($available, $idx, 1);
        }

        return $picked;
    }

    private function buildMatchingCorrectAnswer(array $matchingSet): ?string
    {
        $tasks = $matchingSet['tasks'] ?? [];
        $formulas = $matchingSet['formulas'] ?? [];

        if (empty($tasks) || empty($formulas)) {
            return null;
        }

        $indexes = [];
        foreach ($tasks as $task) {
            $correctFormula = $task['options'][0] ?? null;
            if (!$correctFormula) {
                return null;
            }
            $position = array_search($correctFormula, $formulas, true);
            if ($position === false) {
                return null;
            }
            $indexes[] = (string) ($position + 1);
        }

        return implode('', $indexes);
    }

    /**
     * Build a static placement test for new students.
     *
     * Returns 2 tasks per topic (6-18, excluding 19) that test different skills.
     * Uses a fixed seed for deterministic task selection.
     *
     * @return array{tasks: array<int, array>, variantNumber: int, selectedZadaniya: array}
     */
    public function buildPlacementTest(): array
    {
        // Fixed seed for deterministic task selection
        mt_srand(crc32('placement_test_v1'));

        $topicTitles = [
            '06' => 'Дроби и степени',
            '07' => 'Числа, координатная прямая',
            '08' => 'Квадратные корни и степени',
            '09' => 'Уравнения',
            '11' => 'Графики функций',
            '13' => 'Неравенства',
            '15' => 'Треугольники',
            '16' => 'Окружность',
            '17' => 'Четырёхугольники',
            '18' => 'Фигуры на клетчатой бумаге',
        ];

        // zadaniya per topic testing different skills.
        // Format: [topicId, block, zadanie, skill description]
        $selections = [
            // Topic 06: Дроби и степени
            ['06', 3, 5, 'Сложение обыкновенных дробей'],
            ['06', 1, 2, 'Сложение и вычитание десятичных дробей'],
            ['06', 1, 1, 'Умножение и деление обыкновенных дробей'],
            // Topic 07: Числа, координатная прямая
            ['07', 1, 10, 'Между какими целыми числами заключено число'],
            ['07', 2, 8, 'Какому промежутку принадлежит корень'],
            // Topic 08: Квадратные корни и степени
            ['08', 1, 3, 'Простые степени и корни'],
            ['08', 1, 8, 'Вычисление корня из степени'],
            ['08', 3, 5, 'Свойства степеней'],
            // Topic 09: Уравнения
            ['09', 1, 1, 'Линейное уравнение'],
            ['09', 1, 4, 'Квадратное уравнение'],
            // Topic 11: Графики функций (matching — 1 задание, т.к. оно уже комплексное)
            ['11', 1, 0, 'Соответствие графиков и формул'],
            // Topic 13: Неравенства
            ['13', 1, 1, 'Линейное неравенство'],
            ['13', 1, 6, 'Квадратное неравенство'],
            // Topic 15: Треугольники
            ['15', 1, 3, 'Углы треугольника'],
            ['15', 1, 9, 'Площадь прямоугольного треугольника'],
            // Topic 16: Окружность
            ['16', 1, 1, 'Базовые свойства окружности'],
            ['16', 1, 4, 'Вписанные и центральные углы'],
            // Topic 17: Четырёхугольники
            ['17', 1, 1, 'Свойства четырёхугольников'],
            ['17', 1, 2, 'Площади четырёхугольников'],
            // Topic 18: Фигуры на клетчатой бумаге
            ['18', 1, 1, 'Длина отрезка на клетчатой бумаге'],
            ['18', 1, 4, 'Площадь фигуры на клетчатой бумаге'],
        ];

        $tasks = [];

        foreach ($selections as $sel) {
            [$topicId, $blockNumber, $zadanieNumber] = $sel;

            // Topic 11 matching: use special method
            if ($topicId === '11') {
                $matchingSet = $this->taskDataService->getRandomMatchingSet($topicId);
                if ($matchingSet) {
                    $matchingSet['topic_id'] = $topicId;
                    $matchingSet['topic_title'] = $topicTitles[$topicId] ?? '';
                    $matchingSet['task_number'] = 11;
                    $matchingSet['correct_answer'] = $this->buildMatchingCorrectAnswer($matchingSet);
                    $tasks[] = $matchingSet;
                }
                continue;
            }

            $tasksFromZadanie = $this->taskDataService->getRandomTasksFromZadanie(
                $topicId,
                $blockNumber,
                $zadanieNumber,
                1
            );

            if (empty($tasksFromZadanie)) {
                continue;
            }

            $task = $tasksFromZadanie[0];
            $task['topic_id'] = $topicId;
            $task['topic_title'] = $topicTitles[$topicId] ?? '';
            $task['task_number'] = (int) ltrim($topicId, '0');
            $task = $this->enrichTaskWithCorrectAnswer($task);
            $tasks[] = $task;
        }

        mt_srand();

        return [
            'tasks' => $tasks,
            'variantNumber' => 0,
            'selectedZadaniya' => [],
        ];
    }
}
