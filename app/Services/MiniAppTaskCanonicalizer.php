<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

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
        $task['graph_options'] = $task['graph_options'] ?? ($inner['graph_options'] ?? null);
        $task['graph_options_mode'] = $task['graph_options_mode'] ?? ($inner['graph_options_mode'] ?? null);
        $task = $this->normalizeStableOptions($task);

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
            // Default alias; may be overridden to stable option id for choice tasks.
            $task['correct_answer'] = (string) $canonical;
        }

        $task = $this->attachCanonicalOptionId($task);

        if (($task['answer_kind'] ?? null) === 'choice_index' && !empty($task['canonical_option_id'])) {
            $task['correct_answer'] = (string) $task['canonical_option_id'];
        }

        return $task;
    }

    private function resolveCanonicalAnswer(array $task, array $inner): array
    {
        $type = (string) ($task['type'] ?? $inner['type'] ?? '');

        if ($type === 'statements') {
            $stmts = $task['selected_statements'] ?? null;
            if ($stmts === null) {
                $raw = $task['statements'] ?? [];
                if (is_array($raw) && count($raw) <= 3) {
                    $stmts = $raw;
                } elseif (is_array($raw) && count($raw) > 3) {
                    Log::warning('Statements task without selected_statements', [
                        'task_number' => $task['task_number'] ?? null,
                    ]);
                    return ['statements_mask', null];
                }
            }

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

    private function normalizeStableOptions(array $task): array
    {
        $options = $task['options'] ?? null;
        if (!is_array($options) || empty($options)) {
            return $task;
        }

        $normalized = [];
        foreach (array_values($options) as $index => $option) {
            $defaultId = $this->optionIdByIndex($index);

            if (is_array($option)) {
                $id = isset($option['id']) && $option['id'] !== '' ? (string) $option['id'] : $defaultId;
                $label = (string) ($option['label'] ?? $option['text'] ?? $option['value'] ?? '');

                $normalized[] = array_merge($option, [
                    'id' => $id,
                    'label' => $label,
                    'text' => (string) ($option['text'] ?? $label),
                    'value' => (string) ($option['value'] ?? $label),
                ]);
                continue;
            }

            $label = (string) $option;
            $normalized[] = [
                'id' => $defaultId,
                'label' => $label,
                'text' => $label,
                'value' => $label,
            ];
        }

        $task['options'] = $normalized;

        return $task;
    }

    private function attachCanonicalOptionId(array $task): array
    {
        $kind = (string) ($task['answer_kind'] ?? '');
        $canonical = (string) ($task['canonical_answer'] ?? '');
        $options = $task['options'] ?? null;

        if ($kind !== 'choice_index' || !is_array($options) || !preg_match('/^[1-9][0-9]*$/', $canonical)) {
            return $task;
        }

        $index = (int) $canonical - 1;
        if (isset($options[$index]['id'])) {
            $task['canonical_option_id'] = (string) $options[$index]['id'];
        }

        return $task;
    }

    private function optionIdByIndex(int $index): string
    {
        if ($index < 26) {
            return chr(ord('a') + $index);
        }

        return 'o' . ($index + 1);
    }
}
