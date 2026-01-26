<?php

namespace App\Http\Controllers;

use App\Services\Geometry\MetadataService;
use App\Services\TaskDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * GeometryEditorController - Контроллер для SVG редактора геометрии
 *
 * Обрабатывает загрузку и сохранение метаданных геометрических изображений.
 */
class GeometryEditorController extends Controller
{
    public function __construct(
        private MetadataService $metadataService,
        private TaskDataService $taskDataService
    ) {}

    /**
     * Загрузить данные для редактора
     *
     * GET /api/geometry/{taskId}/load
     */
    public function load(string $taskId): JsonResponse
    {
        try {
            $metadata = $this->metadataService->get($taskId);
            $currentSvg = $this->getCurrentSvg($taskId);

            if ($metadata && $this->metadataService->isEditable($taskId)) {
                // Полный режим редактирования
                return response()->json([
                    'mode' => 'full_edit',
                    'metadata' => $metadata,
                    'svg' => $currentSvg,
                    'task_id' => $taskId
                ]);
            }

            // Legacy-режим: только просмотр + кнопка пересоздать
            return response()->json([
                'mode' => 'legacy_view',
                'metadata' => null,
                'svg' => $currentSvg,
                'task_id' => $taskId,
                'message' => 'Изображение создано в старой системе. Для редактирования нужно пересоздать.'
            ]);

        } catch (\Exception $e) {
            Log::error("Error loading geometry for task {$taskId}: " . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Ошибка загрузки: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Сохранить изображение из редактора
     *
     * POST /api/geometry/{taskId}/save
     */
    public function save(Request $request, string $taskId): JsonResponse
    {
        $validated = $request->validate([
            'metadata' => 'required|array',
            'svg' => 'required|string'
        ]);

        try {
            // 1. Сохраняем метаданные
            $this->metadataService->save($taskId, $validated['metadata']);

            // 2. Обновляем SVG в соответствующем topic JSON
            $this->updateTopicJson($taskId, $validated['svg']);

            // 3. Очищаем кэш
            Artisan::call('cache:clear');

            Log::info("Geometry saved for task: {$taskId}");

            return response()->json([
                'success' => true,
                'message' => 'Изображение сохранено',
                'task_id' => $taskId,
                'updated_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error("Error saving geometry for task {$taskId}: " . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Ошибка сохранения: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить метаданные
     *
     * DELETE /api/geometry/{taskId}/metadata
     */
    public function delete(string $taskId): JsonResponse
    {
        try {
            $this->metadataService->delete($taskId);

            return response()->json([
                'success' => true,
                'message' => 'Метаданные удалены'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Ошибка удаления: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить статистику миграции
     *
     * GET /api/geometry/stats
     */
    public function stats(): JsonResponse
    {
        $stats = $this->metadataService->getMigrationStats();

        return response()->json($stats);
    }

    /**
     * Получить текущий SVG для задания
     */
    private function getCurrentSvg(string $taskId): ?string
    {
        $parsed = $this->metadataService->parseTaskId($taskId);

        if (!$parsed['valid']) {
            return null;
        }

        try {
            $topicId = str_pad($parsed['topic_number'], 2, '0', STR_PAD_LEFT);

            if ($parsed['exam_type'] === 'ege') {
                // ЕГЭ: storage/app/tasks/ege/topic_XX.json
                $path = storage_path("app/tasks/ege/topic_{$topicId}.json");
            } else {
                // ОГЭ: storage/app/tasks/topic_XX.json
                $path = storage_path("app/tasks/topic_{$topicId}.json");
            }

            if (!file_exists($path)) {
                return null;
            }

            $data = json_decode(file_get_contents($path), true);

            // Ищем задачу по номеру
            foreach ($data['blocks'] ?? [] as $block) {
                foreach ($block['zadaniya'] ?? [] as $zadanie) {
                    foreach ($zadanie['tasks'] ?? [] as $task) {
                        if (($task['id'] ?? null) == $parsed['task_number']) {
                            return $task['svg'] ?? $task['image'] ?? null;
                        }
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::warning("Could not get SVG for task {$taskId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Обновить SVG в topic JSON файле
     */
    private function updateTopicJson(string $taskId, string $svg): void
    {
        $parsed = $this->metadataService->parseTaskId($taskId);

        if (!$parsed['valid']) {
            throw new \InvalidArgumentException("Invalid task ID format: {$taskId}");
        }

        $topicId = str_pad($parsed['topic_number'], 2, '0', STR_PAD_LEFT);

        if ($parsed['exam_type'] === 'ege') {
            $path = storage_path("app/tasks/ege/topic_{$topicId}.json");
        } else {
            $path = storage_path("app/tasks/topic_{$topicId}.json");
        }

        if (!file_exists($path)) {
            throw new \RuntimeException("Topic file not found: {$path}");
        }

        $data = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in topic file: " . json_last_error_msg());
        }

        // Ищем и обновляем задачу
        $found = false;
        foreach ($data['blocks'] as &$block) {
            foreach ($block['zadaniya'] as &$zadanie) {
                foreach ($zadanie['tasks'] as &$task) {
                    if (($task['id'] ?? null) == $parsed['task_number']) {
                        $task['svg'] = $svg;
                        $found = true;
                        break 3;
                    }
                }
            }
        }

        if (!$found) {
            throw new \RuntimeException("Task {$parsed['task_number']} not found in topic {$topicId}");
        }

        // Сохраняем обновлённый файл
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($path, $json);

        Log::info("Updated SVG in topic file for task: {$taskId}");
    }
}
