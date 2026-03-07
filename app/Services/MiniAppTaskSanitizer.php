<?php

namespace App\Services;

class MiniAppTaskSanitizer
{
    private const FIELDS_TO_STRIP = [
        'correct_answer',
        'canonical_answer',
        'answer',
        'is_true',
    ];

    public function sanitize(array $task): array
    {
        foreach (self::FIELDS_TO_STRIP as $field) {
            unset($task[$field]);
        }

        if (isset($task['task']) && is_array($task['task'])) {
            unset($task['task']['answer'], $task['task']['correct_answer']);
        }

        if (isset($task['selected_statements']) && is_array($task['selected_statements'])) {
            foreach ($task['selected_statements'] as &$stmt) {
                if (is_array($stmt)) {
                    unset($stmt['is_true']);
                }
            }
            unset($stmt);
        }

        if (isset($task['statements']) && is_array($task['statements'])) {
            foreach ($task['statements'] as &$stmt) {
                if (is_array($stmt)) {
                    unset($stmt['is_true']);
                }
            }
            unset($stmt);
        }

        return $task;
    }
}
