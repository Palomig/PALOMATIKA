<?php

namespace App\Services;

use App\Models\OgeVariant;

class VariantTaskNumberResolver
{
    /**
     * Resolve the two canonical numbering fields for a single task.
     *
     * - slot: 1-based position used for answer storage, scoring, and attempt APIs
     * - exam_number: OGE topic number for display (e.g. 8, 14, 17)
     *
     * @param array       $task    Task data from config_json['tasks']
     * @param int         $index   0-based position in the tasks array
     * @param OgeVariant  $variant The variant this task belongs to
     * @return array{slot: int, exam_number: int}
     */
    public static function resolve(array $task, int $index, OgeVariant $variant): array
    {
        // Canonical fields — if present, trust them unconditionally.
        if (isset($task['slot']) && isset($task['exam_number'])) {
            return [
                'slot' => (int) $task['slot'],
                'exam_number' => (int) $task['exam_number'],
            ];
        }

        // --- Legacy resolution (single place for all fallback logic) ---

        $examNumber = self::resolveExamNumber($task);
        $mode = (string) ($variant->mode ?? '');
        $isMini = str_starts_with($mode, 'mini_');
        $isCustomRandom = $variant->source() === OgeVariant::SOURCE_CUSTOM_RANDOM;

        if ($isMini) {
            $slot = $index + 1;
        } elseif ($isCustomRandom) {
            $slot = (int) ($task['attempt_task_number'] ?? $task['test_number'] ?? ($index + 1));
        } else {
            // Legacy hash-based full variants: slot = exam number
            $slot = $examNumber > 0 ? $examNumber : (6 + $index);
        }

        if ($examNumber <= 0) {
            $examNumber = $slot;
        }

        return [
            'slot' => $slot,
            'exam_number' => $examNumber,
        ];
    }

    /**
     * Resolve all tasks in a variant, returning an array indexed by slot.
     *
     * @param array<int, mixed>  $tasks   Tasks from config_json['tasks']
     * @param OgeVariant         $variant
     * @return array<int, array{slot: int, exam_number: int, task: array}>
     */
    public static function resolveAll(array $tasks, OgeVariant $variant): array
    {
        $result = [];

        foreach (array_values($tasks) as $index => $task) {
            if (!is_array($task)) {
                continue;
            }

            $numbers = self::resolve($task, $index, $variant);

            if ($numbers['slot'] < 1 || $numbers['slot'] > 255) {
                continue;
            }

            $result[$numbers['slot']] = [
                'slot' => $numbers['slot'],
                'exam_number' => $numbers['exam_number'],
                'task' => $task,
            ];
        }

        return $result;
    }

    /**
     * Extract exam number from any known field.
     */
    private static function resolveExamNumber(array $task): int
    {
        $raw = $task['task_number'] ?? $task['zadanie_number'] ?? null;
        if ($raw !== null) {
            return (int) $raw;
        }

        $topicId = $task['topic_id'] ?? null;
        if ($topicId !== null) {
            return (int) ltrim((string) $topicId, '0');
        }

        return 0;
    }
}
