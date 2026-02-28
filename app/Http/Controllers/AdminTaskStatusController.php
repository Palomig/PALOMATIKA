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

        // Extract block/zadanie/task from task_key
        preg_match('/^topic_\d{2}_block_(\d+)_zadanie_(\d+)_task_(\d+)$/', $taskKey, $matches);
        $blockNumber = (int) $matches[1];
        $zadanieNumber = (int) $matches[2];
        $taskId = (int) $matches[3];

        $updated = false;
        foreach ($data['blocks'] as $bi => $block) {
            if ((int) ($block['number'] ?? 0) !== $blockNumber) {
                continue;
            }
            foreach ($block['zadaniya'] ?? [] as $zi => $zadanie) {
                if ((int) ($zadanie['number'] ?? 0) !== $zadanieNumber) {
                    continue;
                }
                foreach ($zadanie['tasks'] ?? [] as $ti => $task) {
                    if ((int) ($task['id'] ?? 0) === $taskId) {
                        $data['blocks'][$bi]['zadaniya'][$zi]['tasks'][$ti]['status'] = $status;
                        $updated = true;
                        break 3;
                    }
                }
            }
        }

        if (!$updated) {
            return response()->json(['message' => 'Задача не найдена.'], 404);
        }

        // saveTopicData will trigger auto-sync of variant pool
        if (!$this->taskDataService->saveTopicData($topicId, $data)) {
            return response()->json(['message' => 'Не удалось сохранить.'], 500);
        }

        return response()->json([
            'task_key' => $taskKey,
            'status' => $status,
        ]);
    }

    /**
     * Bulk update status for multiple tasks.
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
            if (!$this->taskDataService->isValidTaskKey($taskKey, $topicId)) {
                continue;
            }

            preg_match('/^topic_\d{2}_block_(\d+)_zadanie_(\d+)_task_(\d+)$/', $taskKey, $matches);
            if (empty($matches)) {
                continue;
            }

            $blockNumber = (int) $matches[1];
            $zadanieNumber = (int) $matches[2];
            $taskId = (int) $matches[3];

            foreach ($data['blocks'] as $bi => $block) {
                if ((int) ($block['number'] ?? 0) !== $blockNumber) {
                    continue;
                }
                foreach ($block['zadaniya'] ?? [] as $zi => $zadanie) {
                    if ((int) ($zadanie['number'] ?? 0) !== $zadanieNumber) {
                        continue;
                    }
                    foreach ($zadanie['tasks'] ?? [] as $ti => $task) {
                        if ((int) ($task['id'] ?? 0) === $taskId) {
                            $data['blocks'][$bi]['zadaniya'][$zi]['tasks'][$ti]['status'] = $status;
                            $updatedCount++;
                            break 3;
                        }
                    }
                }
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
}
