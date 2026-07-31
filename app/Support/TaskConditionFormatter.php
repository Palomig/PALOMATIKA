<?php

namespace App\Support;

/**
 * Готовит условие задания к показу в разборе ошибок: снимает лишние
 * математические разделители у формулы и убирает куски, которые уже
 * целиком повторяются в тексте условия.
 */
class TaskConditionFormatter
{
    /**
     * Возвращает голый LaTeX без внешней пары разделителей ($…$, $$…$$, \(…\), \[…\]).
     * Вьюхи оборачивают выражение в \(…\) сами, поэтому свои разделители внутри
     * ломают парсер KaTeX и он печатает исходник красным.
     */
    public static function bareExpression(string $expression): string
    {
        $expr = trim($expression);

        foreach ([['$$', '$$'], ['\\[', '\\]'], ['\\(', '\\)'], ['$', '$']] as [$left, $right]) {
            $lLen = strlen($left);
            $rLen = strlen($right);
            if (strlen($expr) > $lLen + $rLen - 1 && str_starts_with($expr, $left) && str_ends_with($expr, $right)) {
                $expr = trim(substr($expr, $lLen, strlen($expr) - $lLen - $rLen));
                break;
            }
        }

        return $expr;
    }

    /**
     * @return array{instruction: string, text: string, expression: string}
     */
    public static function compose(string $instruction, string $text, string $expression): array
    {
        $instruction = trim($instruction);
        $text = trim($text);
        $expression = self::bareExpression($expression);

        // Инструкция не нужна отдельной строкой, если текст условия начинается с неё.
        if ($instruction !== '' && $text !== '' && str_starts_with(self::normalize($text), self::normalize($instruction))) {
            $instruction = '';
        }

        // Формула не нужна отдельной строкой, если она уже есть в тексте условия.
        if ($expression !== '' && $text !== '' && str_contains(self::squash($text), self::squash($expression))) {
            $expression = '';
        }

        if ($text === '') {
            $text = $instruction;
            $instruction = '';
        }

        return [
            'instruction' => $instruction,
            'text' => $text,
            'expression' => $expression,
        ];
    }

    private static function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($value)));
    }

    /** Сравнение формул без учёта пробелов и разделителей. */
    private static function squash(string $value): string
    {
        return preg_replace('/\s+/u', '', str_replace(['$', '\\(', '\\)', '\\[', '\\]'], '', $value));
    }
}
