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
                return mb_strtolower($value) === self::UNKNOWN_ANSWER ? null : $value;
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

        if (preg_match('/[a-zA-Zа-яА-Я=]/u', $expr)) {
            return null;
        }

        $expr = str_replace(['$', '−', '–', '\\cdot', '\\times', '{', '}', ','], ['', '-', '-', '*', '*', '(', ')', '.'], $expr);

        while (preg_match('/\\\\frac\(([^()]+)\)\(([^()]+)\)/', $expr, $m)) {
            $expr = str_replace($m[0], '((' . $m[1] . ')/(' . $m[2] . '))', $expr);
        }

        while (preg_match('/\\\\sqrt\(([^()]+)\)/', $expr, $m)) {
            if (!is_numeric($m[1])) {
                return null;
            }
            $expr = str_replace($m[0], (string) sqrt((float) $m[1]), $expr);
        }

        $expr = str_replace('^', '**', $expr);
        $expr = preg_replace('/\s+/', '', $expr) ?? '';

        if ($expr === '' || !preg_match('/^[0-9\.\+\-\*\/\(\)]+$/', $expr)) {
            return null;
        }

        try {
            /** @var mixed $value */
            $value = eval('return ' . $expr . ';');
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
}
