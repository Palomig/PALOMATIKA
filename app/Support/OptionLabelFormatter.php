<?php

namespace App\Support;

class OptionLabelFormatter
{
    private const LETTER_LABELS = ['А', 'Б', 'В', 'Г', 'Д', 'Е'];

    public static function optionText(mixed $option): string
    {
        if (is_array($option)) {
            return (string) ($option['label'] ?? $option['text'] ?? $option['value'] ?? json_encode($option, JSON_UNESCAPED_UNICODE));
        }

        return (string) $option;
    }

    public static function optionLabel(mixed $option, ?int $fallbackIndex = null): string
    {
        // Варианты банка ФИПИ пронумерованы полем `n` и не имеют `id`. Без
        // этой ветки метка бралась по порядковому номеру из букв, и ответ «3»
        // показывался как «В» — а в ОГЭ буквенных ответов не бывает.
        if (is_array($option) && isset($option['n'])
            && preg_match('/^[1-9][0-9]*$/', (string) $option['n']) === 1) {
            return (string) $option['n'];
        }

        $id = is_array($option) ? (string) ($option['id'] ?? '') : '';

        return self::labelFromId($id, $fallbackIndex);
    }

    public static function labelFromId(?string $id, ?int $fallbackIndex = null): string
    {
        $id = mb_strtolower(trim((string) $id));

        if ($id !== '') {
            if (preg_match('/^[a-z]$/', $id) === 1) {
                return self::labelByIndex(ord($id) - ord('a'));
            }

            if (preg_match('/^o([1-9][0-9]*)$/', $id, $m) === 1) {
                return $m[1];
            }

            if (preg_match('/^[1-9][0-9]*$/', $id) === 1) {
                return $id;
            }
        }

        return self::labelByIndex($fallbackIndex);
    }

    public static function formatAnswer(mixed $answer, ?array $options = null): string
    {
        $raw = trim((string) $answer);
        if ($raw === '' || $raw === '—') {
            return $raw;
        }

        $options = is_array($options) ? array_values($options) : [];

        foreach ($options as $index => $option) {
            $optionId = is_array($option) ? trim((string) ($option['id'] ?? '')) : '';

            if ($optionId !== '' && mb_strtolower($raw) === mb_strtolower($optionId)) {
                return self::optionLabel($option, $index);
            }
        }

        if ($options !== [] && preg_match('/^[1-9][0-9]*$/', $raw) === 1) {
            $numericIndex = (int) $raw - 1;
            if (isset($options[$numericIndex])) {
                return self::optionLabel($options[$numericIndex], $numericIndex);
            }
        }

        return $raw;
    }

    private static function labelByIndex(?int $index): string
    {
        if (!is_int($index) || $index < 0) {
            return '';
        }

        return self::LETTER_LABELS[$index] ?? (string) ($index + 1);
    }
}
