<?php

namespace App\Http\Controllers;

use App\Services\EgeTaskDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Контроллер для страниц с заданиями ЕГЭ
 * Использует JSON-файлы через EgeTaskDataService
 */
class EgeController extends Controller
{
    protected EgeTaskDataService $taskService;

    public function __construct(EgeTaskDataService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Список всех заданий ЕГЭ
     */
    public function index()
    {
        $topics = [];

        foreach ($this->taskService->getAllTopicsMeta() as $topicId => $meta) {
            $topics[$topicId] = array_merge($meta, [
                'exists' => $this->taskService->topicDataExists($topicId),
                'stats' => $this->taskService->topicDataExists($topicId)
                    ? $this->taskService->getTopicStats($topicId)
                    : null,
            ]);
        }

        return view('ege.index', compact('topics'));
    }

    /**
     * Страница задания ЕГЭ
     */
    public function show(string $id)
    {
        $topicId = str_pad($id, 2, '0', STR_PAD_LEFT);

        // Проверяем существование данных
        if (!$this->taskService->topicDataExists($topicId)) {
            abort(404, "Задание ЕГЭ №$topicId не найдено");
        }

        // Получаем данные из JSON
        $blocks = $this->taskService->getBlocks($topicId);
        $topicMeta = $this->taskService->getTopicMeta($topicId);
        $stats = $this->taskService->getTopicStats($topicId);

        return view('ege.show', compact('blocks', 'topicId', 'topicMeta', 'stats'));
    }

    /**
     * Генератор вариантов ЕГЭ
     */
    public function generator()
    {
        $topicIds = [];
        for ($i = 1; $i <= 19; $i++) {
            $topicIds[] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        $topicsWithZadaniya = [];

        foreach ($topicIds as $topicId) {
            try {
                if (!$this->taskService->topicDataExists($topicId)) {
                    continue;
                }

                $topicMeta = $this->taskService->getTopicMeta($topicId);
                $blocks = $this->taskService->getBlocks($topicId);

                if (empty($blocks)) {
                    continue;
                }

                $zadaniyaData = [];
                foreach ($blocks as $block) {
                    $blockTitle = $block['title'] ?? "Блок {$block['number']}";

                    foreach ($block['zadaniya'] ?? [] as $zadanie) {
                        $example = null;

                        if (isset($zadanie['tasks'][0])) {
                            $firstTask = $zadanie['tasks'][0];
                            $example = [
                                'type' => $zadanie['type'] ?? 'word_problem',
                                'instruction' => $zadanie['instruction'] ?? '',
                                'expression' => $firstTask['expression'] ?? '',
                                'text' => $firstTask['text'] ?? '',
                                'image' => $firstTask['image'] ?? null,
                            ];
                        }

                        $zadaniyaData[] = [
                            'zadanie_id' => "{$topicId}_{$block['number']}_{$zadanie['number']}",
                            'block_number' => $block['number'],
                            'block_title' => $blockTitle,
                            'zadanie_number' => $zadanie['number'],
                            'instruction' => $zadanie['instruction'] ?? '',
                            'example' => $example,
                        ];
                    }
                }

                if (!empty($zadaniyaData)) {
                    // Категории ЕГЭ: часть 1 (1-12) vs часть 2 (13-19)
                    $topicNum = (int) ltrim($topicId, '0');
                    $category = $topicNum <= 12 ? 'part1' : 'part2';

                    $topicsWithZadaniya[] = [
                        'topic_id' => $topicId,
                        'topic_number' => ltrim($topicId, '0'),
                        'title' => $topicMeta['title'],
                        'color' => $topicMeta['color'] ?? 'gray',
                        'category' => $category,
                        'zadaniya' => $zadaniyaData,
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to load EGE topic {$topicId}: " . $e->getMessage());
                continue;
            }
        }

        return view('ege.generator', [
            'topicsWithZadaniya' => $topicsWithZadaniya
        ]);
    }

    /**
     * Показать сгенерированный вариант ЕГЭ
     */
    public function showVariant(string $hash)
    {
        if (!preg_match('/^[a-zA-Z0-9]{5,8}$/', $hash)) {
            abort(404);
        }

        $seed = crc32($hash);
        mt_srand($seed);

        $variantNumber = (abs($seed) % 999) + 1;

        // Загружаем выбранные zadaniya из кэша
        $selectedZadaniya = Cache::get("ege_variant_{$hash}");

        if (!$selectedZadaniya) {
            // По умолчанию: все zadaniya из всех доступных тем
            $selectedZadaniya = [];
            for ($i = 1; $i <= 19; $i++) {
                $topicId = str_pad($i, 2, '0', STR_PAD_LEFT);
                if (!$this->taskService->topicDataExists($topicId)) continue;

                $blocks = $this->taskService->getBlocks($topicId);
                foreach ($blocks as $block) {
                    foreach ($block['zadaniya'] ?? [] as $zadanie) {
                        $selectedZadaniya[] = "{$topicId}_{$block['number']}_{$zadanie['number']}";
                    }
                }
            }
        }

        // Группируем по темам
        $zadaniyaByTopic = [];
        foreach ($selectedZadaniya as $zadanieId) {
            $parts = explode('_', $zadanieId);
            if (count($parts) !== 3) continue;

            [$topicId, $blockNumber, $zadanieNumber] = $parts;
            if (!isset($zadaniyaByTopic[$topicId])) {
                $zadaniyaByTopic[$topicId] = [];
            }
            $zadaniyaByTopic[$topicId][] = [
                'block' => (int) $blockNumber,
                'zadanie' => (int) $zadanieNumber,
            ];
        }

        // Генерируем по одному заданию на тему
        $tasks = [];
        foreach ($zadaniyaByTopic as $topicId => $zadaniyaList) {
            $randomZadanie = $zadaniyaList[array_rand($zadaniyaList)];

            $tasksFromZadanie = $this->taskService->getRandomTasksFromZadanie(
                $topicId,
                $randomZadanie['block'],
                $randomZadanie['zadanie'],
                1
            );

            if (!empty($tasksFromZadanie)) {
                $tasks[] = $tasksFromZadanie[0];
            }
        }

        mt_srand();

        return view('ege.variant', [
            'tasks' => $tasks,
            'variantNumber' => $variantNumber,
            'variantHash' => $hash,
            'selectedZadaniya' => $selectedZadaniya,
        ]);
    }

    /**
     * Сохранить конфигурацию варианта ЕГЭ
     */
    public function saveVariant(Request $request)
    {
        $hash = $request->input('hash');
        $zadaniya = $request->input('zadaniya');

        if (!preg_match('/^[a-zA-Z0-9]{5,8}$/', $hash)) {
            return response()->json(['error' => 'Invalid hash format'], 400);
        }

        if (!is_array($zadaniya) || empty($zadaniya)) {
            return response()->json(['error' => 'Invalid zadaniya'], 400);
        }

        foreach ($zadaniya as $zadanie) {
            if (!preg_match('/^\d{2}_\d+_\d+$/', $zadanie)) {
                return response()->json(['error' => 'Invalid zadanie format'], 400);
            }
        }

        Cache::put("ege_variant_{$hash}", $zadaniya, now()->addDays(30));

        return response()->json(['success' => true, 'hash' => $hash]);
    }

    /**
     * API: Получить случайные задания из темы ЕГЭ
     */
    public function apiGetRandomTasks(Request $request, string $topicId)
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
        $count = $request->input('count', 1);

        $tasks = $this->taskService->getRandomTasks($topicId, $count);

        return response()->json([
            'success' => true,
            'exam_type' => 'ege',
            'topic_id' => $topicId,
            'count' => count($tasks),
            'tasks' => $tasks,
        ]);
    }

    /**
     * API: Получить все данные задания ЕГЭ
     */
    public function apiGetTopicData(string $topicId)
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);

        if (!$this->taskService->topicDataExists($topicId)) {
            return response()->json([
                'success' => false,
                'error' => 'EGE topic data not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'exam_type' => 'ege',
            'topic_id' => $topicId,
            'meta' => $this->taskService->getTopicMeta($topicId),
            'stats' => $this->taskService->getTopicStats($topicId),
            'blocks' => $this->taskService->getBlocks($topicId),
        ]);
    }
}
