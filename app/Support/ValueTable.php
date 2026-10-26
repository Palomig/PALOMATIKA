<?php

namespace App\Support;

/**
 * Задача «заполните таблицу значений функции» (банк «Скиллы», графики).
 *
 * В условии таблица — KaTeX-формула `\begin{array}…\hline x&-2&-1&0\\…` с
 * пустой строкой y. Ученик заполняет её прямо в таблице: экраны урока и
 * домашки рисуют строку y полями ввода, а из условия убирают саму формулу
 * таблицы и подсказку «запишите значения через точку с запятой» — она нужна
 * только там, где полей нет. Клетки собираются в ответ «v1; v2; …», который
 * сверяет по порядку {@see \App\Services\TaskAnswerResolver::tableMatches()}.
 */
final class ValueTable
{
    private const TABLE = '/\s*\$\\\\begin\{array\}.*?\\\\end\{array\}\$/s';
    private const X_ROW = '/\\\\hline\s*x\s*&(.*?)\\\\\\\\/s';
    private const HINT = '/\s*В ответ запишите значения \$y\$ через точку с запятой\./u';

    /**
     * @return array{lead:string, xs:array<int,string>}|null null — задача не табличная
     */
    public static function parse(?string $expression): ?array
    {
        $expression = (string) $expression;
        if (!preg_match(self::TABLE, $expression, $table) || !preg_match(self::X_ROW, $table[0], $row)) {
            return null;
        }
        $xs = array_map('trim', explode('&', $row[1]));
        if (count($xs) < 2 || in_array('', $xs, true)) {
            return null;
        }

        $lead = preg_replace([self::TABLE, self::HINT], '', $expression) ?? $expression;

        return ['lead' => trim($lead), 'xs' => $xs];
    }
}
