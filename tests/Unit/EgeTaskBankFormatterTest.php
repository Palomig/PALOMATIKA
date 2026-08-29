<?php

namespace Tests\Unit;

use App\Support\EgeTaskBankFormatter;
use PHPUnit\Framework\TestCase;

class EgeTaskBankFormatterTest extends TestCase
{
    public function test_separates_the_equation_but_keeps_the_interval_inline(): void
    {
        $html = '<p>а) Решите уравнение $x^2-1=0$.</p>'
            . '<p>б) Найдите корни, принадлежащие отрезку $[0;5]$.</p>';

        $formatted = EgeTaskBankFormatter::separatePrimaryFormula($html);

        $this->assertStringContainsString(
            'а) Решите уравнение<br class="fipi-primary-formula-break">$x^2-1=0$.',
            $formatted
        );
        $this->assertStringContainsString('отрезку $[0;5]$.', $formatted);
        $this->assertSame(1, substr_count($formatted, 'fipi-primary-formula-break'));
    }

    public function test_separates_the_primary_inequality(): void
    {
        $html = '<p>Решите неравенство $\log_3(x)\leq 2$.</p>';

        $this->assertSame(
            '<p>Решите неравенство<br class="fipi-primary-formula-break">$\log_3(x)\leq 2$.</p>',
            EgeTaskBankFormatter::separatePrimaryFormula($html)
        );
    }

    public function test_leaves_html_without_a_formula_unchanged(): void
    {
        $html = '<p>Докажите утверждение.</p>';

        $this->assertSame($html, EgeTaskBankFormatter::separatePrimaryFormula($html));
    }
}
