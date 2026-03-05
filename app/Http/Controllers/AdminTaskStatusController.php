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

        $this->taskDataService->upsertStatusByTaskKey($topicId, $taskKey, $status);

        return response()->json([
            'task_key' => $taskKey,
            'status' => $status,
            'storage' => 'db',
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

        $validTaskKeys = [];

        foreach ($taskKeys as $taskKey) {
            if (!$this->taskDataService->isValidTaskKey($taskKey, $topicId)) {
                continue;
            }
            if (!$this->taskDataService->taskExistsByKey($topicId, $taskKey)) {
                continue;
            }
            $validTaskKeys[] = $taskKey;
        }

        if (empty($validTaskKeys)) {
            return response()->json(['message' => 'Ни одна задача не обновлена.'], 422);
        }

        $updatedCount = $this->taskDataService->bulkUpsertStatusByTaskKeys($topicId, $validTaskKeys, $status);

        return response()->json([
            'updated' => $updatedCount,
            'status' => $status,
            'storage' => 'db',
        ]);
    }

}
