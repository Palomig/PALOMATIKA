<?php

namespace App\Http\Controllers;

use App\Models\OgeVariant;
use App\Services\MiniVariantService;
use App\Services\TaskDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MiniAppAdminController extends Controller
{
    public function __construct(
        private readonly TaskDataService $taskData,
        private readonly MiniVariantService $miniVariant,
    ) {
    }

    /**
     * Admin: list curated variants.
     */
    public function adminVariants()
    {
        $user = Auth::user();

        $variants = OgeVariant::where('owner_teacher_id', $user->id)
            ->where('is_curated', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($v) => [
                'id' => $v->id,
                'hash' => $v->hash,
                'title' => $v->title,
                'task_count' => $v->curatedTasks()->count(),
                'created' => $v->created_at?->format('d.m.Y'),
            ]);

        // Load topics with zadaniya for the create form (same as OGE generator)
        $topicIds = ['06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19'];
        $topicsWithZadaniya = [];

        foreach ($topicIds as $topicId) {
            try {
                $topicMeta = $this->taskData->getTopicMeta($topicId);
                $blocks = $this->taskData->getBlocks($topicId);
                if (empty($blocks)) continue;

                $zadaniyaData = [];
                foreach ($blocks as $block) {
                    foreach ($block['zadaniya'] ?? [] as $zadanie) {
                        $example = null;
                        if (($zadanie['type'] ?? '') === 'statements' && isset($zadanie['statements'][0])) {
                            $example = ['type' => 'statements', 'text' => $zadanie['statements'][0]['text'] ?? ''];
                        } elseif (isset($zadanie['tasks'][0])) {
                            $firstTask = $zadanie['tasks'][0];
                            $example = [
                                'type' => $zadanie['type'] ?? 'expression',
                                'expression' => $firstTask['expression'] ?? '',
                                'text' => $firstTask['text'] ?? '',
                            ];
                        }

                        $taskCount = ($zadanie['type'] ?? '') === 'statements'
                            ? count($zadanie['statements'] ?? [])
                            : count($zadanie['tasks'] ?? []);

                        $zadaniyaData[] = [
                            'zadanie_id' => "{$topicId}_{$block['number']}_{$zadanie['number']}",
                            'block_number' => $block['number'],
                            'zadanie_number' => $zadanie['number'],
                            'instruction' => $zadanie['instruction'] ?? '',
                            'task_count' => $taskCount,
                            'example' => $example,
                        ];
                    }
                }

                if (!empty($zadaniyaData)) {
                    $topicsWithZadaniya[] = [
                        'topic_id' => $topicId,
                        'topic_number' => ltrim($topicId, '0'),
                        'title' => $topicMeta['title'],
                        'zadaniya' => $zadaniyaData,
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return view('miniapp.admin-variants', [
            'variants' => $variants,
            'topicsWithZadaniya' => $topicsWithZadaniya,
        ]);
    }

    /**
     * Admin: create curated variant (POST).
     */
    public function createCuratedVariant(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|min:2|max:200',
            'zadaniya' => 'required|array|min:1|max:100',
            'zadaniya.*' => 'required|string|regex:/^\d{2}_\d+_\d+$/',
        ]);

        $user = $request->user();
        $hash = $this->miniVariant->generateHash();

        // Build tasks: for each zadanie_id, pick one random task
        $selectedTasks = [];
        $sortOrder = 1;

        foreach ($data['zadaniya'] as $zadanieId) {
            [$topicId, $blockNumber, $zadanieNumber] = explode('_', $zadanieId);
            $taskNumber = (int) ltrim($topicId, '0');

            $task = $this->taskData->getRandomTasksFromZadanie(
                $topicId,
                (int) $blockNumber,
                (int) $zadanieNumber,
                1
            );

            if (!empty($task)) {
                $t = $task[0];
                $t['task_number'] = $taskNumber;
                $selectedTasks[] = $t;
            }
            $sortOrder++;
        }

        if (empty($selectedTasks)) {
            return response()->json(['success' => false, 'message' => 'Не удалось найти задания'], 422);
        }

        $variant = OgeVariant::create([
            'hash' => $hash,
            'owner_teacher_id' => $user->id,
            'title' => $data['title'],
            'mode' => OgeVariant::MODE_FULL,
            'is_curated' => true,
            'source' => OgeVariant::SOURCE_CURATED,
            'config_json' => ['tasks' => $selectedTasks],
        ]);

        // Save curated task references
        $sortOrder = 1;
        foreach ($data['zadaniya'] as $zadanieId) {
            [$topicId, $blockNumber, $zadanieNumber] = explode('_', $zadanieId);
            $variant->curatedTasks()->create([
                'task_number' => (int) ltrim($topicId, '0'),
                'topic_id' => $topicId,
                'block_number' => (int) $blockNumber,
                'zadanie_number' => (int) $zadanieNumber,
                'task_index' => 0,
                'sort_order' => $sortOrder++,
            ]);
        }

        return response()->json([
            'success' => true,
            'variant' => [
                'id' => $variant->id,
                'hash' => $variant->hash,
                'title' => $variant->title,
                'task_count' => count($selectedTasks),
            ],
        ]);
    }
}
