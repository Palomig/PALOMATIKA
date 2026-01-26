<?php

namespace App\Services\Geometry;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * MetadataService - Сервис для работы с метаданными геометрических изображений
 *
 * Хранит метаданные (координаты вершин, настройки линий, углов и т.д.)
 * для редактируемых изображений в JSON файле.
 */
class MetadataService
{
    private string $path = 'geometry/geometry-metadata.json';

    /**
     * Получить метаданные для задания
     */
    public function get(string $taskId): ?array
    {
        $data = $this->loadAll();
        return $data['tasks'][$taskId] ?? null;
    }

    /**
     * Сохранить метаданные для задания
     */
    public function save(string $taskId, array $metadata): void
    {
        $data = $this->loadAll();

        $metadata['updated_at'] = now()->toISOString();
        if (!isset($metadata['created_at'])) {
            $metadata['created_at'] = now()->toISOString();
        }
        $metadata['created_via'] = 'editor';

        $data['tasks'][$taskId] = $metadata;
        $this->saveAll($data);

        Log::info("Geometry metadata saved for task: {$taskId}");
    }

    /**
     * Удалить метаданные для задания
     */
    public function delete(string $taskId): void
    {
        $data = $this->loadAll();
        unset($data['tasks'][$taskId]);
        $this->saveAll($data);

        Log::info("Geometry metadata deleted for task: {$taskId}");
    }

    /**
     * Проверить существование метаданных
     */
    public function exists(string $taskId): bool
    {
        return $this->get($taskId) !== null;
    }

    /**
     * Проверить, редактируемо ли изображение (создано через редактор)
     */
    public function isEditable(string $taskId): bool
    {
        $meta = $this->get($taskId);
        return $meta && ($meta['created_via'] ?? '') === 'editor';
    }

    /**
     * Получить все задания с метаданными
     */
    public function getAll(): array
    {
        $data = $this->loadAll();
        return $data['tasks'] ?? [];
    }

    /**
     * Получить статистику миграции
     */
    public function getMigrationStats(): array
    {
        $all = $this->getAll();
        $editable = array_filter($all, fn($m) => ($m['created_via'] ?? '') === 'editor');

        return [
            'total_with_metadata' => count($all),
            'editable' => count($editable),
            'legacy' => count($all) - count($editable)
        ];
    }

    /**
     * Загрузить все данные из файла
     */
    private function loadAll(): array
    {
        if (!Storage::exists($this->path)) {
            return [
                'version' => '1.0',
                'tasks' => []
            ];
        }

        $content = Storage::get($this->path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse geometry metadata JSON: ' . json_last_error_msg());
            return [
                'version' => '1.0',
                'tasks' => []
            ];
        }

        return $data;
    }

    /**
     * Сохранить все данные в файл
     */
    private function saveAll(array $data): void
    {
        $data['version'] = '1.0';
        $data['updated_at'] = now()->toISOString();

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        Storage::put($this->path, $json);
    }

    /**
     * Парсинг taskId для получения информации
     * Формат: {номер_задания}{OGE|EGE}{номер_задачи}
     * Пример: 15OGE123, 3EGE456
     */
    public function parseTaskId(string $taskId): array
    {
        // Паттерн: число + OGE/EGE + число
        if (preg_match('/^(\d+)(OGE|EGE)(\d+)$/', $taskId, $matches)) {
            return [
                'topic_number' => (int) $matches[1],
                'exam_type' => strtolower($matches[2]),
                'task_number' => (int) $matches[3],
                'valid' => true
            ];
        }

        return [
            'topic_number' => null,
            'exam_type' => null,
            'task_number' => null,
            'valid' => false
        ];
    }

    /**
     * Создать taskId из компонентов
     */
    public function buildTaskId(int $topicNumber, string $examType, int $taskNumber): string
    {
        $examType = strtoupper($examType);
        if (!in_array($examType, ['OGE', 'EGE'])) {
            throw new \InvalidArgumentException("Invalid exam type: {$examType}");
        }

        return "{$topicNumber}{$examType}{$taskNumber}";
    }
}
