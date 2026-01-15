<?php

namespace App\Http\Controllers;

use App\Services\TaskDataService;
use Illuminate\Http\Request;

/**
 * Контроллер для страниц с заданиями по темам
 * Использует JSON-файлы через TaskDataService
 */
class TopicController extends Controller
{
    protected TaskDataService $taskService;

    public function __construct(TaskDataService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Список всех тем
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

        return view('topics.index', compact('topics'));
    }

    /**
     * Страница темы с заданиями
     */
    public function show(string $id)
    {
        // Нормализуем ID (6 -> 06)
        $topicId = str_pad($id, 2, '0', STR_PAD_LEFT);

        // Проверяем существование данных
        if (!$this->taskService->topicDataExists($topicId)) {
            // Fallback на старый контроллер если JSON не существует
            return $this->fallbackToLegacy($topicId);
        }

        // Получаем данные из JSON
        $blocks = $this->taskService->getBlocks($topicId);
        $topicMeta = $this->taskService->getTopicMeta($topicId);
        $stats = $this->taskService->getTopicStats($topicId);

        // Проверяем наличие кастомного шаблона для темы (с custom SVG)
        $customView = "topics.topic{$topicId}";
        if (view()->exists($customView)) {
            return view($customView, compact('blocks', 'topicId', 'topicMeta', 'stats'));
        }

        // Используем generic шаблон с JSON данными
        return view('topics.show', compact('blocks', 'topicId', 'topicMeta', 'stats'));
    }

    /**
     * Fallback на старый контроллер (временно, пока не мигрируем все данные)
     */
    protected function fallbackToLegacy(string $topicId): \Illuminate\Http\RedirectResponse
    {
        $legacyRoutes = [
            '06' => 'test.topic06',
            '07' => 'test.topic07',
            '08' => 'test.topic08',
            '09' => 'test.topic09',
            '10' => 'test.topic10',
            '11' => 'test.topic11',
            '12' => 'test.topic12',
            '13' => 'test.topic13',
            '14' => 'test.topic14',
            '15' => 'test.topic15',
            '16' => 'test.topic16',
            '17' => 'test.topic17',
            '18' => 'test.topic18',
            '19' => 'test.topic19',
        ];

        if (isset($legacyRoutes[$topicId])) {
            return redirect()->route($legacyRoutes[$topicId]);
        }

        abort(404, "Тема $topicId не найдена");
    }

    /**
     * Генератор ОГЭ вариантов
     */
    public function ogeGenerator()
    {
        $topics = [];
        foreach ($this->taskService->getAllTopicsMeta() as $topicId => $meta) {
            $topics[$topicId] = $meta;
        }

        return view('oge.generator', compact('topics'));
    }

    /**
     * Показать сгенерированный вариант ОГЭ
     */
    public function showOgeVariant(string $hash)
    {
        // Используем хэш как seed для детерминированной генерации
        $seed = crc32($hash);
        mt_srand($seed);

        $tasks = [];
        $topicIds = ['06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19'];

        foreach ($topicIds as $topicId) {
            $randomTasks = $this->taskService->getRandomTasks($topicId, 1);
            if (!empty($randomTasks)) {
                $tasks[] = $randomTasks[0];
            }
        }

        mt_srand(); // Сбрасываем seed

        return view('oge.variant', compact('tasks', 'hash'));
    }

    /**
     * API: Получить случайные задания из темы
     */
    public function apiGetRandomTasks(Request $request, string $topicId)
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
        $count = $request->input('count', 1);

        $tasks = $this->taskService->getRandomTasks($topicId, $count);

        return response()->json([
            'success' => true,
            'topic_id' => $topicId,
            'count' => count($tasks),
            'tasks' => $tasks,
        ]);
    }

    /**
     * API: Получить все данные темы
     */
    public function apiGetTopicData(string $topicId)
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);

        if (!$this->taskService->topicDataExists($topicId)) {
            return response()->json([
                'success' => false,
                'error' => 'Topic data not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'topic_id' => $topicId,
            'meta' => $this->taskService->getTopicMeta($topicId),
            'stats' => $this->taskService->getTopicStats($topicId),
            'blocks' => $this->taskService->getBlocks($topicId),
        ]);
    }
}
