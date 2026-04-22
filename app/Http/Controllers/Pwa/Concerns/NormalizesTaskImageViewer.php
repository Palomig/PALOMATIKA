<?php

namespace App\Http\Controllers\Pwa\Concerns;

use App\Models\OgeVariant;

trait NormalizesTaskImageViewer
{
    /**
     * @param  array<int, mixed>  $tasks
     * @return array<int, mixed>
     */
    private function normalizeTaskImageViewerMeta(OgeVariant $variant, array $tasks): array
    {
        $examType = (string) ($variant->exam_type ?? '');

        foreach ($tasks as $index => $task) {
            if (!is_array($task)) {
                continue;
            }

            $topicId = str_pad((string) ($task['topic_id'] ?? ''), 2, '0', STR_PAD_LEFT);
            $hasVisual = trim((string) ($task['svg'] ?? '')) !== ''
                || trim((string) ($task['image'] ?? '')) !== '';

            $task['viewer_disabled'] = !$hasVisual || ($examType === OgeVariant::EXAM_OGE && $topicId === '11');
            $task['viewer_orientation'] = $this->taskViewerOrientation($examType, $topicId);

            $tasks[$index] = $task;
        }

        return $tasks;
    }

    private function taskViewerOrientation(string $examType, string $topicId): string
    {
        if ($examType === OgeVariant::EXAM_OGE && $topicId === '07') {
            return 'landscape';
        }

        if (str_starts_with($examType, 'vpr_') && $topicId === '06') {
            return 'landscape';
        }

        return 'default';
    }
}
