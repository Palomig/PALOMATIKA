<?php

namespace App\Services;

class TaskAnswerResolver
{
    public const UNKNOWN_ANSWER = 'нет в базе';

    public function resolveFromVariantTask(array $taskData): ?string
    {
        if (isset($taskData['correct_answer']) && $taskData['correct_answer'] !== null && $taskData['correct_answer'] !== '') {
            return (string) $taskData['correct_answer'];
        }

        if (!empty($taskData['is_matching_set'])) {
            return $this->resolveMatchingSetAnswer($taskData);
        }

        $zadanie = [
            'type' => $taskData['type'] ?? null,
            'statements' => $taskData['selected_statements'] ?? $taskData['statements'] ?? null,
            'options' => $taskData['options'] ?? null,
        ];

        return $this->resolveFromTaskAndZadanie($zadanie, $taskData['task'] ?? []);
    }

    public function resolveFromTaskAndZadanie(array $zadanie, array $task): ?string
    {
        foreach (['answer', 'correct_answer'] as $key) {
            if (isset($task[$key]) && $task[$key] !== null && $task[$key] !== '') {
                $value = trim((string) $task[$key]);
                if (mb_strtolower($value) === self::UNKNOWN_ANSWER) {
                    continue;
                }

                return $value;
            }
        }

        $type = (string) ($zadanie['type'] ?? '');

        if ($type === 'statements') {
            return $this->resolveStatementsAnswer($zadanie['statements'] ?? []);
        }

        if (in_array($type, ['matching', 'matching_signs', 'matching_4'], true)) {
            $opt = $task['options'][0] ?? null;
            return $opt !== null && $opt !== '' ? (string) $opt : null;
        }

        if ($type === 'graph_statements' && !empty($task['formula'])) {
            return (string) $task['formula'];
        }

        if ($type === 'count_integers' && !empty($task['left']) && !empty($task['right'])) {
            $betweenCount = $this->countIntegersBetween((string) $task['left'], (string) $task['right']);
            if ($betweenCount !== null) {
                return (string) $betweenCount;
            }
        }

        if ($this->isChoiceType($type)) {
            if (isset($task['correct']) && is_numeric($task['correct'])) {
                return (string) ((int) $task['correct'] + 1);
            }

            if (!empty($task['options']) || !empty($zadanie['options'])) {
                // В JSON выборных задач первый вариант хранится как корректный.
                return '1';
            }
        }

        if (isset($task['correct']) && is_numeric($task['correct'])) {
            $idx = (int) $task['correct'];
            if (isset($task['options'][$idx])) {
                return (string) $task['options'][$idx];
            }
            return (string) ($idx + 1);
        }

        if (!empty($task['options']) && is_array($task['options'])) {
            $first = $task['options'][0] ?? null;
            if ($first !== null && $first !== '') {
                return (string) $first;
            }
        }

        if (!empty($zadanie['options']) && is_array($zadanie['options'])) {
            $first = $zadanie['options'][0] ?? null;
            if ($first !== null && $first !== '') {
                return (string) $first;
            }
        }

        if (!empty($task['expression'])) {
            return $this->evaluateMathExpression((string) $task['expression']);
        }

        return null;
    }

    public function isCorrect(?string $userAnswer, ?string $correctAnswer): ?bool
    {
        $correct = $this->normalize((string) $correctAnswer);
        if ($correct === '') {
            return null;
        }

        $userRaw = (string) ($userAnswer ?? '');
        $user = $this->normalize($userRaw);

        if (preg_match('/^\d+$/', $correct)) {
            $trimmedRaw = preg_replace('/\s+/', '', $userRaw) ?? '';
            if (preg_match('/^\d+$/', $trimmedRaw)) {
                return ltrim($trimmedRaw, '0') === ltrim($correct, '0');
            }
            $digitsOnly = preg_replace('/\D+/', '', $userRaw);
            return $digitsOnly === $correct;
        }

        return $user !== '' && $user === $correct;
    }

    public function normalize(string $value): string
    {
        $value = trim(mb_strtolower($value));
        if ($value === '') {
            return '';
        }

        $value = str_replace(['−', '–'], '-', $value);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/\s+/u', '', $value) ?? '';

        if (preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            $num = (float) $value;
            if (abs($num - round($num)) < 1e-9) {
                return (string) (int) round($num);
            }

            return rtrim(rtrim(sprintf('%.10F', $num), '0'), '.');
        }

        return $value;
    }

    private function resolveStatementsAnswer(array $statements): ?string
    {
        if (empty($statements)) {
            return null;
        }

        $digits = [];
        $display = 1;
        foreach ($statements as $statement) {
            $isTrue = (bool) ($statement['is_true'] ?? false);
            $number = $statement['display_number'] ?? $display;
            if ($isTrue) {
                $digits[] = (string) $number;
            }
            $display++;
        }

        return empty($digits) ? null : implode('', $digits);
    }

    private function resolveMatchingSetAnswer(array $taskData): ?string
    {
        $tasks = $taskData['tasks'] ?? [];
        $formulas = $taskData['formulas'] ?? [];
        if (empty($tasks) || empty($formulas)) {
            return null;
        }

        $digits = [];
        foreach ($tasks as $task) {
            $correctFormula = $task['options'][0] ?? null;
            if (!$correctFormula) {
                return null;
            }
            $position = array_search($correctFormula, $formulas, true);
            if ($position === false) {
                return null;
            }
            $digits[] = (string) ($position + 1);
        }

        return implode('', $digits);
    }

    private function evaluateMathExpression(string $expr): ?string
    {
        $expr = trim($expr);
        if ($expr === '') {
            return null;
        }

        // Поддержка хвоста вида "\text{ при } a = 3, b = 5".
        [$mathPart, $variables] = $this->splitExpressionAndAssignments($expr);
        $converted = $this->latexToMathExpression($mathPart);
        if ($converted === null || $converted === '') {
            return null;
        }

        foreach ($variables as $name => $value) {
            $converted = preg_replace('/\b' . preg_quote($name, '/') . '\b/u', '(' . $value . ')', $converted) ?? $converted;
        }

        $converted = preg_replace('/\s+/', '', $converted) ?? '';
        if ($converted === '' || preg_match('/[^0-9\.\+\-\*\/\(\),a-zA-Z_]/', $converted)) {
            return null;
        }
        // После подстановки переменных должны остаться только sqrt/pow как функции.
        if (preg_match('/[a-zA-Z_]+/', $converted, $m) && !in_array($m[0], ['sqrt', 'pow'], true)) {
            return null;
        }

        try {
            /** @var mixed $value */
            $value = eval('return ' . $converted . ';');
        } catch (\Throwable) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $num = (float) $value;
        if (abs($num - round($num)) < 1e-9) {
            return (string) (int) round($num);
        }

        return rtrim(rtrim(sprintf('%.10F', $num), '0'), '.');
    }

    /**
     * @return array{0:string,1:array<string,float>}
     */
    private function splitExpressionAndAssignments(string $expr): array
    {
        $expr = str_replace(['\\left', '\\right', '\\displaystyle', '$', '−', '–'], ['', '', '', '', '-', '-'], $expr);
        $expr = str_replace('{,}', '.', $expr);

        $parts = preg_split('/\\\\text\s*\{\s*при\s*\}/u', $expr, 2);
        $math = trim((string) ($parts[0] ?? ''));
        $vars = [];

        if (!isset($parts[1])) {
            return [$math, $vars];
        }

        $tail = trim($parts[1]);
        foreach (preg_split('/\s*,\s*/', $tail) as $assignment) {
            if (!preg_match('/^([a-zA-Z]+)\s*=\s*(.+)$/u', trim($assignment), $m)) {
                continue;
            }
            $name = $m[1];
            $raw = trim($m[2]);
            $value = $this->parseAssignedValue($raw);
            if ($value === null) {
                continue;
            }
            $vars[$name] = $value;
        }

        return [$math, $vars];
    }

    private function parseAssignedValue(string $raw): ?float
    {
        $raw = str_replace(' ', '', $raw);
        $raw = str_replace(',', '.', $raw);

        if (preg_match('/^(-?\d+)\\\\frac\{(\d+)\}\{(\d+)\}$/', $raw, $m)) {
            $whole = (int) $m[1];
            $num = (int) $m[2];
            $den = (int) $m[3];
            if ($den === 0) {
                return null;
            }
            if ($whole < 0) {
                return $whole - ($num / $den);
            }
            return $whole + ($num / $den);
        }

        if (preg_match('/^(-?)\\\\frac\{(\d+)\}\{(\d+)\}$/', $raw, $m)) {
            $sign = $m[1] === '-' ? -1.0 : 1.0;
            $num = (int) $m[2];
            $den = (int) $m[3];
            if ($den === 0) {
                return null;
            }
            return $sign * ($num / $den);
        }

        if (is_numeric($raw)) {
            return (float) $raw;
        }

        return null;
    }

    private function latexToMathExpression(string $expr): ?string
    {
        $s = trim($expr);
        if ($s === '') {
            return null;
        }

        $s = str_replace(['\\cdot', '\\times', ':', '\\div'], ['*', '*', '/', '/'], $s);
        $s = str_replace(['{,}', ','], ['.', '.'], $s);

        // Коэффициент перед \sqrt: 5\sqrt{...} -> 5*\sqrt{...}
        $s = preg_replace('/(\d)\s*\\\\sqrt/u', '$1*\\\\sqrt', $s) ?? $s;

        // \frac{a}{b} -> ((a)/(b)), рекурсивно для вложенных фракций.
        while (($pos = strpos($s, '\\frac')) !== false) {
            $num = $this->extractBraced($s, $pos + 5);
            if ($num === null) {
                return null;
            }
            [$numExpr, $afterNum] = $num;
            $den = $this->extractBraced($s, $afterNum);
            if ($den === null) {
                return null;
            }
            [$denExpr, $afterDen] = $den;

            $replacement = '((' . $numExpr . ')/(' . $denExpr . '))';
            $s = substr($s, 0, $pos) . $replacement . substr($s, $afterDen);
        }

        // \sqrt{a} -> sqrt(a)
        while (($pos = strpos($s, '\\sqrt')) !== false) {
            $radicand = $this->extractBraced($s, $pos + 5);
            if ($radicand === null) {
                return null;
            }
            [$body, $after] = $radicand;
            $replacement = 'sqrt(' . $body . ')';
            $s = substr($s, 0, $pos) . $replacement . substr($s, $after);
        }

        // Приводим "16a" к "16*a" до разбора степеней, чтобы не получить "(16a)^n".
        $s = preg_replace('/(\d)([a-zA-Z])/u', '$1*$2', $s) ?? $s;

        // Степени вида a^{14}
        $s = preg_replace('/([a-zA-Z0-9\)\.]+)\^\{([^{}]+)\}/u', '($1)**($2)', $s) ?? $s;
        // Степени вида a^2
        $s = preg_replace('/([a-zA-Z0-9\)\.]+)\^(-?[0-9]+)/u', '($1)**($2)', $s) ?? $s;

        // Преобразуем оставшиеся фигурные скобки в круглые.
        $s = str_replace(['{', '}'], ['(', ')'], $s);

        // Неявные умножения.
        $s = preg_replace('/(\d)([a-zA-Z\(])/u', '$1*$2', $s) ?? $s;
        $s = preg_replace('/([a-zA-Z\)])(\d)/u', '$1*$2', $s) ?? $s;
        $s = preg_replace('/(?<!sqrt)(?<!pow)([a-zA-Z\)])\(/u', '$1*(', $s) ?? $s;
        $s = preg_replace('/\)([a-zA-Z\(])/u', ')*$1', $s) ?? $s;
        // Разделяем только пары одиночных переменных вида "ab" -> "a*b",
        // не трогая имена функций вроде sqrt/pow.
        $s = preg_replace('/\b([a-zA-Z])([a-zA-Z])\b/u', '$1*$2', $s) ?? $s;
        $s = str_replace(['sqrt*(', 'pow*('], ['sqrt(', 'pow('], $s);

        return $s;
    }

    /**
     * @return array{0:string,1:int}|null
     */
    private function extractBraced(string $s, int $start): ?array
    {
        while (isset($s[$start]) && ctype_space($s[$start])) {
            $start++;
        }
        if (!isset($s[$start]) || $s[$start] !== '{') {
            return null;
        }

        $depth = 0;
        $i = $start;
        $len = strlen($s);
        for (; $i < $len; $i++) {
            if ($s[$i] === '{') {
                $depth++;
            } elseif ($s[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    $content = substr($s, $start + 1, $i - $start - 1);
                    return [$content, $i + 1];
                }
            }
        }

        return null;
    }

    private function countIntegersBetween(string $leftExpr, string $rightExpr): ?int
    {
        $left = $this->evaluateMathExpression($leftExpr);
        $right = $this->evaluateMathExpression($rightExpr);

        if ($left === null || $right === null || !is_numeric($left) || !is_numeric($right)) {
            return null;
        }

        $min = min((float) $left, (float) $right);
        $max = max((float) $left, (float) $right);
        $start = (int) floor($min) + 1;
        $end = (int) ceil($max) - 1;

        return max(0, $end - $start + 1);
    }

    private function isChoiceType(string $type): bool
    {
        return in_array($type, [
            'choice',
            'simple_choice',
            'fraction_choice',
            'interval_choice',
            'between_fractions',
            'segment_choice',
            'fraction_options',
            'decimal_choice',
            'sqrt_choice',
            'sqrt_interval',
            'sqrt_segment',
            'sqrt_options',
            'comparison',
            'power_choice',
            'compare_fractions',
            'false_statements',
            'ordering',
            'point_value',
            'fraction_point',
            'count_integers',
            'negative_segment',
            'negative_interval',
        ], true);
    }
}
