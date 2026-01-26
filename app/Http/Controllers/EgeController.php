<?php

namespace App\Http\Controllers;

use App\Services\EgeTaskDataService;
use Illuminate\Http\Request;

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
        $topics = $this->taskService->getAvailableTopics();
        return view('ege.generator', compact('topics'));
    }

    /**
     * Показать сгенерированный вариант ЕГЭ
     */
    public function showVariant(string $hash)
    {
        // Используем хэш как seed для детерминированной генерации
        $seed = crc32($hash);
        mt_srand($seed);

        $tasks = [];
        $availableTopics = array_keys($this->taskService->getAvailableTopics());

        foreach ($availableTopics as $topicId) {
            $randomTasks = $this->taskService->getRandomTasks($topicId, 1);
            if (!empty($randomTasks)) {
                $tasks[] = $randomTasks[0];
            }
        }

        mt_srand(); // Сбрасываем seed

        return view('ege.variant', compact('tasks', 'hash'));
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
