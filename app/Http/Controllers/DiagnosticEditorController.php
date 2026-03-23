<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Services\TaskDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DiagnosticEditorController extends Controller
{
    private const STORAGE_PATH = 'diagnostic_test.json';

    public function __construct(
        private readonly TaskDataService $taskDataService,
    ) {}

    /**
     * Главная страница редактора.
     */
    public function index()
    {
        try {
            $skills = Skill::active()
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('category');

            $saved = $this->loadSavedTest();

            $selectedItems = collect($saved)->flatMap(fn ($s) =>
                collect($s['tasks'] ?? [])->map(fn ($t) => [
                    'skill_id'       => $s['skill_id'],
                    'skill_name'     => $s['skill_name'],
                    'topic_id'       => $t['topic_id'] ?? '',
                    'block_number'   => $t['block_number'] ?? null,
                    'zadanie_number' => $t['zadanie_number'] ?? null,
                    'task_index'     => $t['task_index'] ?? 0,
                    'text'           => $t['question'] ?? $t['text'] ?? '',
                    'answer'         => $t['answer'] ?? '',
                ])
            )->values()->all();

            return view('miniapp.diagnostic-editor', [
                'skillsByCategory' => $skills,
                'savedTest'        => $saved,
                'selectedItems'    => $selectedItems,
                'totalQuestions'   => collect($saved)->sum(fn ($s) => count($s['tasks'] ?? [])),
            ]);
        } catch (\Throwable $e) {
            return response(
                get_class($e) . ': ' . $e->getMessage() . "\n\n" .
                $e->getFile() . ':' . $e->getLine() . "\n\n" .
                $e->getTraceAsString(),
                500,
                ['Content-Type' => 'text/plain']
            );
        }
    }

    /**
     * Сохранить тест.
     * Body: { items: [{ skill_id, topic_id, block_number, zadanie_number, task_index }] }
     */
    public function save(Request $request)
    {
        $items = $request->input('items', []);

        // Строим структуру: skill_id -> [tasks]
        $bySkill = [];
        foreach ($items as $item) {
            $skillId = (int) ($item['skill_id'] ?? 0);
            if (!$skillId) continue;

            $task = $this->resolveTask(
                $item['topic_id'] ?? '',
                $item['block_number'] ?? null,
                $item['zadanie_number'] ?? null,
                (int) ($item['task_index'] ?? 0),
            );

            if (!$task) continue;

            $bySkill[$skillId]['skill_id'] = $skillId;
            $bySkill[$skillId]['skill_name'] = $item['skill_name'] ?? '';
            $bySkill[$skillId]['tasks'][] = array_merge($task, [
                'topic_id' => $item['topic_id'],
                'block_number' => $item['block_number'],
                'zadanie_number' => $item['zadanie_number'],
                'task_index' => (int) $item['task_index'],
            ]);
        }

        Storage::put(self::STORAGE_PATH, json_encode(array_values($bySkill), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return response()->json([
            'ok' => true,
            'total_skills' => count($bySkill),
            'total_questions' => collect($bySkill)->sum(fn ($s) => count($s['tasks'])),
        ]);
    }

    /**
     * AJAX: вернуть задания для конкретной темы ОГЭ.
     * GET ?topic_id=06&env=production
     */
    public function browseTasks(Request $request)
    {
        $topicId = str_pad($request->input('topic_id', ''), 2, '0', STR_PAD_LEFT);
        $env = $request->input('env', 'production');

        $blocks = $this->taskDataService->getBlocks($topicId, $env);

        // Плоский список заданий для отображения в браузере
        $tasks = [];
        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $idx => $task) {
                    $tasks[] = [
                        'block_number'   => $block['number'] ?? null,
                        'zadanie_number' => $zadanie['number'] ?? null,
                        'task_index'     => $idx,
                        'text'           => $task['question'] ?? $task['text'] ?? '',
                        'answer'         => $task['answer'] ?? '',
                        'has_image'      => !empty($task['image']),
                    ];
                }
            }
        }

        return response()->json(['tasks' => $tasks, 'topic_id' => $topicId]);
    }

    // ─────────────────────────────────────────────────────────────

    private function loadSavedTest(): array
    {
        if (!Storage::exists(self::STORAGE_PATH)) {
            return [];
        }
        return json_decode(Storage::get(self::STORAGE_PATH), true) ?? [];
    }

    private function resolveTask(string $topicId, ?int $blockNumber, ?int $zadanieNumber, int $taskIndex): ?array
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
        $blocks = $this->taskDataService->getBlocks($topicId, 'production');

        foreach ($blocks as $block) {
            if ($blockNumber !== null && ($block['number'] ?? null) != $blockNumber) continue;
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                if ($zadanieNumber !== null && ($zadanie['number'] ?? null) != $zadanieNumber) continue;
                $tasks = $zadanie['tasks'] ?? [];
                if (isset($tasks[$taskIndex])) {
                    return $tasks[$taskIndex];
                }
            }
        }

        return null;
    }
}
