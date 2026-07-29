<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MiniAppTaskCanonicalizer
{
    /**
     * Отделить единственный чертёж от разметки условия.
     *
     * У банка ФИПИ условие и рисунок лежат в соседних ячейках таблицы. В
     * варианте чертёж показывается отдельным блоком, поэтому вынимаем его —
     * но только если он один: в соответствиях темы 11 график привязан к
     * своему номеру, и там место рисунка значащее.
     *
     * @return array{0: ?string, 1: string}
     */
    private function splitSingleDrawing(string $html): array
    {
        if (substr_count($html, '<svg') !== 1
            || !preg_match('/<svg\\b.*?<\\/svg>/is', $html, $m)) {
            return [null, $html];
        }

        $rest = str_replace($m[0], '', $html);
        $rest = preg_replace('/<p>\\s*<\\/p>|<td>\\s*<\\/td>/i', '', $rest) ?? $rest;

        return [$m[0], trim($rest)];
    }

    public function normalizeForUi(array $task): array
    {
        $inner = is_array($task['task'] ?? null) ? $task['task'] : [];

        $task = $this->ensureSelectedStatements($task, $inner);

        // Банк ФИПИ несёт условие готовой разметкой в `html`: ни `text`, ни
        // `expression`, ни `svg` у таких задач нет, и без этой ветки вариант
        // показывал пустое задание — только поле ответа или пустые варианты.
        $html = trim((string) ($inner['html'] ?? $task['html'] ?? ''));
        if ($html !== '') {
            [$drawing, $rest] = $this->splitSingleDrawing($html);
            $task['svg'] = $task['svg'] ?? $drawing;
            $task['text'] = $task['text'] ?? ($rest !== '' ? $rest : null);
            // Условие уже содержит формулировку целиком, поэтому заголовок
            // задания над ним не нужен — он её дословно повторял.
            $task['html_condition'] = true;
        }

        // «Какие из следующих утверждений являются истинными» — верных
        // вариантов несколько, и выбрать нужно все.
        $task['multi_select'] = (bool) ($task['multi_select'] ?? $inner['multi_select'] ?? false);

        $task['text'] = $task['text'] ?? ($inner['text'] ?? null);
        $task['expression'] = $task['expression'] ?? ($inner['expression'] ?? null);

        // point_value/target are internal values (e.g. coordinate on number line),
        // NOT display expressions. Only use them if there is no SVG/image.
        $hasSvgOrImage = !empty($inner['svg'] ?? ($task['svg'] ?? null))
            || !empty($inner['image'] ?? ($task['image'] ?? null));

        if (!$hasSvgOrImage && ($task['expression'] === null || $task['expression'] === '')) {
            $task['expression'] = (string) ($inner['point_value']
                ?? $inner['target']
                ?? $task['point_value']
                ?? $task['target']
                ?? '');
            if ($task['expression'] === '') {
                $task['expression'] = null;
            }
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
                // Вариант банка ФИПИ: номер в `n`, содержимое размеченным
                // `html`. Ни label, ни text, ни value у него нет — без этой
                // ветки в варианте показывались пустые кнопки «А Б В Г».
                // Идентификатором служит номер: ответы банка — числа, и в
                // PWA `optionAnswerValue()` вернёт именно его.
                if (isset($option['html']) && !isset($option['label'], $option['text'], $option['value'])) {
                    $number = (string) ($option['n'] ?? $index + 1);
                    $normalized[] = array_merge($option, [
                        'id' => $number,
                        'label' => $option['html'],
                        'text' => $option['html'],
                        'value' => $number,
                    ]);
                    continue;
                }

                $id = isset($option['id']) && $option['id'] !== '' ? (string) $option['id'] : $defaultId;
                $label = self::latexToUnicode((string) ($option['label'] ?? $option['text'] ?? $option['value'] ?? ''));

                $normalized[] = array_merge($option, [
                    'id' => $id,
                    'label' => $label,
                    'text' => self::latexToUnicode((string) ($option['text'] ?? $label)),
                    'value' => (string) ($option['value'] ?? $label),
                ]);
                continue;
            }

            $label = self::latexToUnicode((string) $option);
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
    public static function latexToUnicode(string $text): string
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
