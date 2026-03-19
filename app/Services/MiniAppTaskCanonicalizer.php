<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MiniAppTaskCanonicalizer
{
    public function normalizeForUi(array $task): array
    {
        $inner = is_array($task['task'] ?? null) ? $task['task'] : [];

        $task = $this->ensureSelectedStatements($task, $inner);

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

        // Topic 7 structured types: build expression from left/right or segment fields.
        if (($task['expression'] === null || $task['expression'] === '')) {
            $left = $inner['left'] ?? ($task['left'] ?? null);
            $right = $inner['right'] ?? ($task['right'] ?? null);
            $segment = $inner['segment'] ?? ($task['segment'] ?? null);

            if ($left !== null && $right !== null) {
                $task['expression'] = $left . ' \\text{ и } ' . $right;
            } elseif ($segment !== null) {
                $task['expression'] = (string) $segment;
            }
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

    private function ensureSelectedStatements(array $task, array $inner): array
    {
        $type = (string) ($task['type'] ?? $inner['type'] ?? '');
        if ($type !== 'statements') {
            return $task;
        }

        $selected = $task['selected_statements'] ?? null;
        if (is_array($selected) && !empty($selected)) {
            $task['selected_statements'] = $this->assignDisplayNumbers($selected);
            $task['statements'] = $task['selected_statements'];
            return $task;
        }

        $raw = $task['statements'] ?? $inner['statements'] ?? null;
        if (!is_array($raw) || empty($raw)) {
            return $task;
        }

        $instruction = (string) ($task['instruction'] ?? $inner['instruction'] ?? '');
        $task['selected_statements'] = $this->selectStatementsForVariant($raw, $instruction);
        $task['statements'] = $task['selected_statements'];

        return $task;
    }

    /**
     * Deterministically reduce topic-19 style statements to the OGE display set.
     * Default rule: 2 true + 1 false. "Какое ..." means 1 true + 2 false.
     *
     * @param array<int, mixed> $statements
     * @return array<int, array<string, mixed>>
     */
    private function selectStatementsForVariant(array $statements, string $instruction): array
    {
        if (count($statements) <= 3) {
            return $this->assignDisplayNumbers($statements);
        }

        $normalized = [];
        foreach (array_values($statements) as $index => $statement) {
            if (is_array($statement)) {
                $normalized[] = $statement + ['_original_index' => $index];
            } else {
                $normalized[] = [
                    'text' => (string) $statement,
                    'is_true' => false,
                    '_original_index' => $index,
                ];
            }
        }

        $lower = mb_strtolower(trim($instruction));
        $desiredTrueCount = str_starts_with($lower, 'какое') ? 1 : 2;
        $desiredFalseCount = 3 - $desiredTrueCount;

        $trueStatements = array_values(array_filter($normalized, fn ($s) => !empty($s['is_true'])));
        $falseStatements = array_values(array_filter($normalized, fn ($s) => empty($s['is_true'])));

        if (count($trueStatements) < $desiredTrueCount || count($falseStatements) < $desiredFalseCount) {
            if ($desiredTrueCount === 2 && count($trueStatements) >= 1 && count($falseStatements) >= 2) {
                $desiredTrueCount = 1;
                $desiredFalseCount = 2;
            } elseif ($desiredTrueCount === 1 && count($trueStatements) >= 2 && count($falseStatements) >= 1) {
                $desiredTrueCount = 2;
                $desiredFalseCount = 1;
            } else {
                return $this->assignDisplayNumbers(array_slice($normalized, 0, 3));
            }
        }

        $selected = array_merge(
            array_slice($trueStatements, 0, $desiredTrueCount),
            array_slice($falseStatements, 0, $desiredFalseCount)
        );

        usort($selected, fn ($a, $b) => (int) ($a['_original_index'] ?? 0) <=> (int) ($b['_original_index'] ?? 0));

        return $this->assignDisplayNumbers($selected);
    }

    /**
     * @param array<int, mixed> $statements
     * @return array<int, array<string, mixed>>
     */
    private function assignDisplayNumbers(array $statements): array
    {
        $selected = [];

        foreach (array_values($statements) as $index => $statement) {
            if (!is_array($statement)) {
                $statement = [
                    'text' => (string) $statement,
                    'is_true' => false,
                ];
            }

            $statement['display_number'] = $index + 1;
            unset($statement['_original_index']);
            $selected[] = $statement;
        }

        return $selected;
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
                $label = $this->latexToUnicode((string) ($option['label'] ?? $option['text'] ?? $option['value'] ?? ''));

                $normalized[] = array_merge($option, [
                    'id' => $id,
                    'label' => $label,
                    'text' => $this->latexToUnicode((string) ($option['text'] ?? $label)),
                    'value' => (string) ($option['value'] ?? $label),
                ]);
                continue;
            }

            $label = $this->latexToUnicode((string) $option);
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

    /**
     * Convert common LaTeX constructs to Unicode for plain-text display.
     */
    private function latexToUnicode(string $text): string
    {
        // \sqrt{X} → √X
        $text = preg_replace('/\\\\sqrt\{([^}]+)\}/', '√$1', $text);
        // \frac{A}{B} → A/B
        $text = preg_replace('/\\\\frac\{([^}]+)\}\{([^}]+)\}/', '$1/$2', $text);
        // \pi → π, \infty → ∞
        $text = str_replace(['\\pi', '\\infty', '\\cdot', '\\times', '\\leq', '\\geq', '\\neq'], ['π', '∞', '·', '×', '≤', '≥', '≠'], $text);
        // Clean remaining braces from simple expressions: {,} → ,
        $text = str_replace(['{', '}'], '', $text);

        return $text;
    }

    private function optionIdByIndex(int $index): string
    {
        if ($index < 26) {
            return chr(ord('a') + $index);
        }

        return 'o' . ($index + 1);
    }
}
