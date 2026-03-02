<?php

namespace App\Http\Controllers;

use App\Services\TaskDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTaskStatusController extends Controller
{
    public function __construct(
        private readonly TaskDataService $taskDataService,
    ) {
    }

    public function update(Request $request, string $topicId): JsonResponse
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);

        $validated = $request->validate([
            'task_key' => ['required', 'string', 'max:120'],
            'status' => ['required', 'string', 'in:production,draft'],
        ]);

        $taskKey = trim($validated['task_key']);
        $status = $validated['status'];

        if (!$this->taskDataService->isValidTaskKey($taskKey, $topicId)) {
            return response()->json(['message' => 'Некорректный task_key.'], 422);
        }

        if (!$this->taskDataService->taskExistsByKey($topicId, $taskKey)) {
            return response()->json(['message' => 'Задача не найдена.'], 404);
        }

        $data = $this->taskDataService->getTopicData($topicId);
        if (empty($data['blocks'])) {
            return response()->json(['message' => 'Тема пуста.'], 404);
        }

        $parsed = $this->taskDataService->parseTaskKey($taskKey);
        $updated = $this->applyStatusToItem($data, $parsed, $status);

        if (!$updated) {
            return response()->json(['message' => 'Задача не найдена.'], 404);
        }

        if (!$this->taskDataService->saveTopicData($topicId, $data)) {
            return response()->json(['message' => 'Не удалось сохранить.'], 500);
        }

        return response()->json([
            'task_key' => $taskKey,
            'status' => $status,
        ]);
    }

    /**
     * Bulk update status for multiple tasks/statements.
     */
    public function bulkUpdate(Request $request, string $topicId): JsonResponse
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);

        $validated = $request->validate([
            'task_keys' => ['required', 'array', 'min:1', 'max:500'],
            'task_keys.*' => ['required', 'string', 'max:120'],
            'status' => ['required', 'string', 'in:production,draft'],
        ]);

        $status = $validated['status'];
        $taskKeys = $validated['task_keys'];

        $data = $this->taskDataService->getTopicData($topicId);
        if (empty($data['blocks'])) {
            return response()->json(['message' => 'Тема пуста.'], 404);
        }

        $updatedCount = 0;

        foreach ($taskKeys as $taskKey) {
            $parsed = $this->taskDataService->parseTaskKey($taskKey);
            if (!$parsed || $parsed['topic_id'] !== $topicId) {
                continue;
            }

            if ($this->applyStatusToItem($data, $parsed, $status)) {
                $updatedCount++;
            }
        }

        if ($updatedCount === 0) {
            return response()->json(['message' => 'Ни одна задача не обновлена.'], 422);
        }

        if (!$this->taskDataService->saveTopicData($topicId, $data)) {
            return response()->json(['message' => 'Не удалось сохранить.'], 500);
        }

        return response()->json([
            'updated' => $updatedCount,
            'status' => $status,
        ]);
    }

    /**
     * Apply status to a task or statement in the data array.
     */
    private function applyStatusToItem(array &$data, ?array $parsed, string $status): bool
    {
        if (!$parsed) {
            return false;
        }

        $arrayKey = $parsed['item_type'] === 'statement' ? 'statements' : 'tasks';

        foreach ($data['blocks'] as $bi => $block) {
            if ((int) ($block['number'] ?? 0) !== $parsed['block_number']) {
                continue;
            }
            foreach ($block['zadaniya'] ?? [] as $zi => $zadanie) {
                if ((int) ($zadanie['number'] ?? 0) !== $parsed['zadanie_number']) {
                    continue;
                }
                foreach ($zadanie[$arrayKey] ?? [] as $ti => $item) {
                    if ((int) ($item['id'] ?? 0) === $parsed['item_id']) {
                        $data['blocks'][$bi]['zadaniya'][$zi][$arrayKey][$ti]['status'] = $status;
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
