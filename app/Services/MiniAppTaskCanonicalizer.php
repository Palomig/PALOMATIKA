<?php

namespace App\Services;

class MiniAppTaskCanonicalizer
{
    public function normalizeForUi(array $task): array
    {
        $inner = is_array($task['task'] ?? null) ? $task['task'] : [];

        $task['text'] = $task['text'] ?? ($inner['text'] ?? null);
        $task['expression'] = $task['expression'] ?? ($inner['expression'] ?? null);

        if (($task['expression'] === null || $task['expression'] === '') && isset($inner['point_value'])) {
            $task['expression'] = (string) $inner['point_value'];
        }
        if (($task['expression'] === null || $task['expression'] === '') && isset($inner['target'])) {
            $task['expression'] = (string) $inner['target'];
        }
        if (($task['expression'] === null || $task['expression'] === '') && isset($task['point_value'])) {
            $task['expression'] = (string) $task['point_value'];
        }
        if (($task['expression'] === null || $task['expression'] === '') && isset($task['target'])) {
            $task['expression'] = (string) $task['target'];
        }

        $task['svg'] = $task['svg'] ?? ($inner['svg'] ?? null);
        $task['image'] = $task['image'] ?? ($inner['image'] ?? null);
        $task['options'] = $task['options'] ?? ($inner['options'] ?? null);

        // statements-mode fallback for old payloads
        if (($task['type'] ?? '') === 'statements') {
            $statements = $task['selected_statements'] ?? $task['statements'] ?? [];
            if (is_array($statements) && !empty($statements)) {
                $lines = [];
                foreach ($statements as $idx => $s) {
                    if (is_array($s)) {
                        $text = (string) ($s['text'] ?? '');
                        $num = (int) ($s['display_number'] ?? ($idx + 1));
                    } else {
                        $text = (string) $s;
                        $num = $idx + 1;
                    }
                    if ($text !== '') {
                        $lines[] = $num . ') ' . e($text);
                    }
                }
                if (!empty($lines) && empty($task['text'])) {
                    $task['text'] = implode('<br>', $lines);
                }
            }
        }

        [$kind, $canonical] = $this->resolveCanonicalAnswer($task, $inner);
        if ($kind !== null) {
            $task['answer_kind'] = $kind;
        }
        if ($canonical !== null && $canonical !== '') {
            $task['canonical_answer'] = (string) $canonical;
            // Backward compatible alias for existing scorer.
            $task['correct_answer'] = (string) $canonical;
        }

        return $task;
    }

    private function resolveCanonicalAnswer(array $task, array $inner): array
    {
        $type = (string) ($task['type'] ?? $inner['type'] ?? '');

        if ($type === 'statements') {
            $stmts = $task['selected_statements'] ?? $task['statements'] ?? [];
            if (is_array($stmts) && !empty($stmts)) {
                $digits = [];
                foreach ($stmts as $idx => $s) {
                    if (!is_array($s)) {
                        continue;
                    }
                    if (!array_key_exists('is_true', $s)) {
                        continue;
                    }
                    if ((bool) $s['is_true'] === true) {
                        $digits[] = (string) ($idx + 1);
                    }
                }
                return ['statements_mask', implode('', $digits)];
            }
        }

        $raw = $task['correct_answer'] ?? $task['answer'] ?? $inner['answer'] ?? null;
        if ($raw === null || $raw === '') {
            return [null, null];
        }

        $raw = (string) $raw;
        if ($type === 'matching') {
            if (preg_match('/^[1-9]+$/', $raw)) {
                return ['matching_order', $raw];
            }
            // keep backward-compatible text answer if source is irregular
            return ['matching_order', $raw];
        }

        if (preg_match('/^[1-9][0-9]*$/', $raw)) {
            return ['choice_index', $raw];
        }

        return ['numeric_or_text', $raw];
    }
}
