<?php

namespace App\Http\Controllers;

use App\Services\TaskAnswerProvenanceService;
use App\Services\TaskAnswerResolver;
use App\Services\TaskDataService;
use Illuminate\Http\Request;

/**
 * Контроллер для страниц с заданиями по темам
 * Использует JSON-файлы через TaskDataService
 */
class TopicController extends Controller
{
    protected TaskDataService $taskService;
    protected TaskAnswerProvenanceService $answerProvenanceService;
    protected TaskAnswerResolver $taskAnswerResolver;

    public function __construct(
        TaskDataService $taskService,
        TaskAnswerProvenanceService $answerProvenanceService,
        TaskAnswerResolver $taskAnswerResolver
    )
    {
        $this->taskService = $taskService;
        $this->answerProvenanceService = $answerProvenanceService;
        $this->taskAnswerResolver = $taskAnswerResolver;
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
     *
     * Все темы используют единый JSON-источник данных.
     * Для геометрии (15, 16) SVG предзаготовлен в task['svg'].
     */
    public function show(string $id)
    {
        $topicId = str_pad($id, 2, '0', STR_PAD_LEFT);

        // Проверяем существование данных
        if (!$this->taskService->topicDataExists($topicId)) {
            // Fallback на старый контроллер если JSON не существует
            return $this->fallbackToLegacy($topicId);
        }

        // Получаем данные из JSON (SVG уже встроен в task['svg'])
        $blocks = $this->taskService->getBlocks($topicId);
        $topicMeta = $this->taskService->getTopicMeta($topicId);
        $stats = $this->taskService->getTopicStats($topicId);
        $answerOverrides = $this->answerProvenanceService->getOverridesForTopic($topicId);

        return view('topics.show', compact('blocks', 'topicId', 'topicMeta', 'stats', 'answerOverrides'));
    }

    /**
     * Fallback на старый контроллер (для тем без JSON)
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

    /**
     * Экспорт заданий с ответами в JSON (удобно для LLM).
     * Поддержка двух режимов:
     * - весь topic: /topics/{id}/export
     * - конкретное задание: /topics/{id}/export?block=1&zadanie=1
     */
    public function export(Request $request, string $id)
    {
        $topicId = str_pad($id, 2, '0', STR_PAD_LEFT);

        if (!$this->taskService->topicDataExists($topicId)) {
            abort(404, "Тема {$topicId} не найдена");
        }

        $blockFilter = $request->integer('block');
        $zadanieFilter = $request->integer('zadanie');

        $blocks = $this->taskService->getBlocks($topicId);
        $topicMeta = $this->taskService->getTopicMeta($topicId);
        $overrides = $this->answerProvenanceService->getOverridesForTopic($topicId);

        $exportBlocks = [];

        foreach ($blocks as $block) {
            $blockNumber = (int) ($block['number'] ?? 0);
            if ($blockFilter && $blockFilter !== $blockNumber) {
                continue;
            }

            $exportZadaniya = [];
            foreach (($block['zadaniya'] ?? []) as $zadanie) {
                $zadanieNumber = (int) ($zadanie['number'] ?? 0);
                if ($zadanieFilter && $zadanieFilter !== $zadanieNumber) {
                    continue;
                }

                $tasks = [];
                foreach (($zadanie['tasks'] ?? []) as $task) {
                    $taskId = (int) ($task['id'] ?? 0);
                    $taskKey = "topic_{$topicId}_block_{$blockNumber}_zadanie_{$zadanieNumber}_task_{$taskId}";
                    $override = $overrides[$taskKey] ?? null;

                    $answer = $this->taskAnswerResolver->resolveFromTaskAndZadanie($zadanie, $task);
                    $source = 'ai';
                    $sourceLabel = 'AI';

                    if ($override) {
                        $answer = (string) ($override['answer'] ?? '');
                        $source = (string) ($override['source'] ?? 'manual');
                        $sourceLabel = (string) ($override['source_label'] ?? 'Ручной');
                    }

                    if (is_string($answer) && mb_strtolower(trim($answer)) === TaskAnswerResolver::UNKNOWN_ANSWER) {
                        $answer = null;
                    }

                    $tasks[] = [
                        'task_id' => $taskId,
                        'task_key' => $taskKey,
                        'answer' => $answer !== null && $answer !== '' ? (string) $answer : null,
                        'answer_status' => ($answer !== null && $answer !== '') ? 'known' : 'missing',
                        'answer_source' => $source,
                        'answer_source_label' => $sourceLabel,
                        'task' => $task,
                    ];
                }

                $exportZadaniya[] = [
                    'number' => $zadanieNumber,
                    'instruction' => (string) ($zadanie['instruction'] ?? ''),
                    'type' => (string) ($zadanie['type'] ?? ''),
                    'section' => $zadanie['section'] ?? null,
                    'tasks' => $tasks,
                ];
            }

            if (!empty($exportZadaniya)) {
                $exportBlocks[] = [
                    'number' => $blockNumber,
                    'title' => (string) ($block['title'] ?? ''),
                    'zadaniya' => $exportZadaniya,
                ];
            }
        }

        if (empty($exportBlocks)) {
            abort(404, 'Запрошенные блок/задание не найдены');
        }

        $payload = [
            'exam' => 'OGE',
            'topic_id' => $topicId,
            'topic_title' => (string) ($topicMeta['title'] ?? ''),
            'exported_at' => now()->toIso8601String(),
            'filters' => [
                'block' => $blockFilter ?: null,
                'zadanie' => $zadanieFilter ?: null,
            ],
            'blocks' => $exportBlocks,
        ];

        $fileName = "oge_topic_{$topicId}";
        if ($blockFilter) {
            $fileName .= "_block_{$blockFilter}";
        }
        if ($zadanieFilter) {
            $fileName .= "_zadanie_{$zadanieFilter}";
        }
        $fileName .= '.json';

        return response(
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            200,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );
    }

    // ========================================================================
    // SVG-СТРАНИЦЫ (SVG предзаготовлен в JSON через svg:bake)
    // ========================================================================

    /**
     * Страница темы с предзаготовленными SVG
     *
     * Роут: /topics/{id}/svg
     * SVG хранятся в task['svg'] в JSON-файлах (предгенерированы через php artisan svg:bake)
     */
    public function showWithServerSvg(string $id)
    {
        $topicId = str_pad($id, 2, '0', STR_PAD_LEFT);

        // Проверяем существование темы
        if (!$this->taskService->topicDataExists($topicId)) {
            return $this->show($id);
        }

        // SVG уже встроены в task['svg'] — просто получаем блоки
        $blocks = $this->taskService->getBlocks($topicId);
        $topicMeta = $this->taskService->getTopicMeta($topicId);
        $stats = $this->taskService->getTopicStats($topicId);
        $answerOverrides = $this->answerProvenanceService->getOverridesForTopic($topicId);

        return view('topics.show-svg', compact('blocks', 'topicId', 'topicMeta', 'stats', 'answerOverrides'));
    }

    /**
     * API: Получить случайные задания (SVG встроен в task['svg'])
     */
    public function apiGetRandomTasksWithSvg(Request $request, string $topicId)
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
        $count = $request->input('count', 1);

        // SVG уже встроен в task['svg'] — просто получаем задания
        $tasks = $this->taskService->getRandomTasks($topicId, $count);

        return response()->json([
            'success' => true,
            'topic_id' => $topicId,
            'count' => count($tasks),
            'tasks' => $tasks,
        ]);
    }
}
