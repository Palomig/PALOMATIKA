<?php

namespace App\Http\Controllers;

use App\Models\TaskAnswerOverride;
use App\Models\TaskAnswerOverrideLog;
use App\Services\TaskAnswerProvenanceService;
use App\Services\TaskDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminTaskAnswerController extends Controller
{
    public function __construct(
        private readonly TaskDataService $taskDataService,
        private readonly TaskAnswerProvenanceService $provenanceService,
    ) {
    }

    public function update(Request $request, string $topicId): JsonResponse
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);

        $validated = $request->validate([
            'task_key' => ['required', 'string', 'max:120'],
            'answer' => ['required', 'string', 'max:255'],
        ]);

        $taskKey = trim($validated['task_key']);
        $answer = trim($validated['answer']);

        if ($answer === '') {
            return response()->json([
                'message' => 'Поле answer не должно быть пустым.',
            ], 422);
        }

        if (!$this->taskDataService->isValidTaskKey($taskKey, $topicId)) {
            return response()->json([
                'message' => 'Некорректный task_key.',
            ], 422);
        }

        if (!$this->taskDataService->taskExistsByKey($topicId, $taskKey)) {
            return response()->json([
                'message' => 'Задача не найдена.',
            ], 404);
        }

        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($this->canWriteOverrides()) {
            try {
                $override = TaskAnswerOverride::query()->firstOrNew(['task_key' => $taskKey]);
                $oldAnswer = $override->exists ? $override->answer : null;

                $override->answer = $answer;
                $override->source = 'manual';
                $override->updated_by_user_id = $user->id;
                $override->save();

                TaskAnswerOverrideLog::query()->create([
                    'override_id' => $override->id,
                    'old_answer' => $oldAnswer,
                    'new_answer' => $answer,
                    'changed_by_user_id' => $user->id,
                ]);

                $sourceLabel = $this->provenanceService->sourceLabel('manual', $user->name);

                return response()->json([
                    'task_key' => $taskKey,
                    'answer' => $answer,
                    'source' => 'manual',
                    'source_label' => $sourceLabel,
                    'updated_by_name' => $user->name,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to write task answer override, falling back to JSON file.', [
                    'task_key' => $taskKey,
                    'topic_id' => $topicId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!$this->taskDataService->updateTaskAnswerByKey($topicId, $taskKey, $answer)) {
            return response()->json([
                'message' => 'Не удалось сохранить ответ.',
            ], 500);
        }

        return response()->json([
            'task_key' => $taskKey,
            'answer' => $answer,
            'source' => 'file',
            'source_label' => 'JSON',
            'updated_by_name' => $user->name,
        ]);
    }

    private function canWriteOverrides(): bool
    {
        try {
            return Schema::hasTable('task_answer_overrides') && Schema::hasTable('task_answer_override_logs');
        } catch (\Throwable) {
            return false;
        }
    }
}
