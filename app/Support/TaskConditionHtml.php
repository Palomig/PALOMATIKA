<?php

namespace App\Support;

/**
 * Готовит условие задания к выводу на страницах разбора ошибок.
 *
 * Банк ФИПИ хранит условие готовой разметкой: абзацы, таблицы соответствий,
 * инлайновые SVG-чертежи. Экраны прохождения выводят её как есть (`x-html`),
 * а разбор ошибок экранировал — и ученик с учителем видели в карточке
 * «<p>Найдите значение выражения …</p>» и «<table><tr><td>» вместо задания.
 *
 * Разметка приходит из нашего же банка, но перед выводом всё равно снимаем
 * то, что странице выполнять нечего: скрипты, фреймы и обработчики событий.
 */
class TaskConditionHtml
{
    /** Теги, по которым условие считается размеченным, а не простым текстом. */
    private const MARKUP_TAGS = 'p|br|div|span|table|thead|tbody|tr|td|th|ul|ol|li|b|strong|i|em|u|sup|sub|img|svg|math';

    /** Выкидываются вместе с содержимым. */
    private const DANGEROUS_TAGS = 'script|iframe|object|embed|form|input|button';

    /** Условие свёрстано разметкой, а не набрано простым текстом. */
    public static function looksLikeMarkup(string $text): bool
    {
        return preg_match('#<(?:' . self::MARKUP_TAGS . ')\b[^>]*>#i', $text) === 1;
    }

    /**
     * HTML условия для вывода через `{!! !!}`.
     *
     * Простой текст экранируется и получает переносы строк, разметка банка
     * проходит насквозь — иначе ломаются таблицы соответствий и чертежи.
     */
    public static function render(string $text): string
    {
        if (!self::looksLikeMarkup($text)) {
            return nl2br(e($text));
        }

        return self::sanitize($text);
    }

    private static function sanitize(string $html): string
    {
        $html = preg_replace('#<(' . self::DANGEROUS_TAGS . ')\b[^>]*>.*?</\1\s*>#is', '', $html) ?? $html;
        $html = preg_replace('#</?(?:' . self::DANGEROUS_TAGS . ')\b[^>]*>#i', '', $html) ?? $html;

        // Обработчики событий и javascript: в адресах — единственное, чем
        // разметка условия может что-то выполнить.
        $html = preg_replace('#\son[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? $html;
        $html = preg_replace('#\s(href|src|xlink:href)\s*=\s*(["\']?)\s*javascript:[^"\'>]*\2#i', '', $html) ?? $html;

        return $html;
    }
}
