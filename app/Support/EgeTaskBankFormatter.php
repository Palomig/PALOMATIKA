<?php

namespace App\Support;

/** Presentation-only formatting for task conditions in the EGE bank. */
class EgeTaskBankFormatter
{
    /**
     * Put the first math fragment of the first paragraph on a new line.
     * Later fragments stay inline, so intervals in follow-up text are intact.
     */
    public static function separatePrimaryFormula(string $html): string
    {
        $formatted = preg_replace(
            '/\A(\s*<p\b[^>]*>(?:(?!<\/p>).)*?)\s*(\$)/us',
            '$1<br class="fipi-primary-formula-break">$2',
            $html,
            1
        );

        return is_string($formatted) ? $formatted : $html;
    }
}
