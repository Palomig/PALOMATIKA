<?php

namespace Tests\Unit;

use App\Services\Print\HtmlToLatexConverter;
use PHPUnit\Framework\TestCase;

class HtmlToLatexConverterTest extends TestCase
{
    private function convert(string $html): array
    {
        return (new HtmlToLatexConverter())->convert($html);
    }

    public function test_math_passes_through_untouched(): void
    {
        $out = $this->convert('<p>Найдите значение $\dfrac{8,2}{4,1}$.</p>');

        $this->assertStringContainsString('$\dfrac{8,2}{4,1}$', $out['latex']);
    }

    public function test_specials_outside_math_are_escaped(): void
    {
        $out = $this->convert('<p>Скидка 10% и знак &amp; в тексте_с_подчёркиванием</p>');

        $this->assertStringContainsString('10\\%', $out['latex']);
        $this->assertStringContainsString('\\&', $out['latex']);
        $this->assertStringContainsString('\\_', $out['latex']);
    }

    public function test_svg_is_extracted_and_replaced_by_placeholder(): void
    {
        $out = $this->convert('<p>До<svg viewBox="0 0 10 10"><line x1="0"/></svg>после</p>');

        $this->assertCount(1, $out['figures']);
        $this->assertStringContainsString('<svg', $out['figures'][0]);
        $this->assertStringContainsString('\\ogeFigure{0}', $out['latex']);
    }

    /**
     * Вложенность у ФИПИ доходит до четырёх уровней, и обход через
     * getElementsByTagName собирал строки внутренних таблиц во внешнюю —
     * задание печаталось по разу на каждый уровень.
     */
    public function test_nested_tables_do_not_duplicate_rows(): void
    {
        $html = '<table><tr><td><table><tr><td>А</td><td>Б</td></tr></table></td></tr></table>';

        $out = $this->convert($html);

        $this->assertSame(1, substr_count($out['latex'], 'А'));
        $this->assertSame(1, substr_count($out['latex'], 'Б'));
    }

    public function test_data_table_gets_rules_and_layout_table_does_not(): void
    {
        $data = $this->convert('<table><tr><td>Тип</td><td>Цена</td></tr><tr><td>А</td><td>1</td></tr></table>');
        $this->assertStringContainsString('\\ogeTable', $data['latex']);

        $layout = $this->convert('<table><tr><td><table><tr><td>А)</td><td>Б)</td></tr></table></td></tr></table>');
        $this->assertStringContainsString('\\ogePlainTable', $layout['latex']);
        $this->assertStringNotContainsString('\\ogeTable{', $layout['latex']);
    }

    public function test_text_and_figure_row_becomes_side_by_side(): void
    {
        $html = '<table><tr><td><p>Условие задачи</p></td>'
            . '<td><svg viewBox="0 0 10 10"></svg></td></tr></table>';

        $out = $this->convert($html);

        $this->assertStringContainsString('\\ogeSideBySide{', $out['latex']);
        $this->assertStringContainsString('Условие задачи', $out['latex']);
    }

    public function test_ragged_rows_are_padded_to_equal_width(): void
    {
        $html = '<table><tr><td>Шапка</td></tr><tr><td>1</td><td>2</td><td>3</td></tr></table>';

        $out = $this->convert($html);

        // Три колонки в спецификации и ровно два амперсанда в короткой строке.
        $this->assertStringContainsString('{|c|c|c|}', $out['latex']);
        $this->assertStringContainsString('Шапка & & ', $out['latex']);
    }

    public function test_raster_image_is_collected_as_asset(): void
    {
        $out = $this->convert('<figure><img src="img/intro_X/plan.png"><figcaption>Рис. 1</figcaption></figure>');

        $this->assertSame(['img/intro_X/plan.png'], $out['assets']);
        $this->assertStringContainsString('\\ogeAsset{0}', $out['latex']);
    }

    /**
     * Один экзотический глиф не должен ронять сборку всей пачки работ:
     * pdfLaTeX на неизвестном юникоде падает намертво.
     */
    public function test_unknown_character_is_replaced_and_reported(): void
    {
        $out = $this->convert("<p>масса 5\u{2295}кг</p>");

        $this->assertStringContainsString('?', $out['latex']);
        $this->assertArrayHasKey('U+2295', $out['unknown']);
    }

    /**
     * Условие «$x<-2$» для HTML-парсера выглядит открывающимся тегом, и он
     * проглатывает всё до следующего «>» вместе с закрывающим долларом —
     * формула печаталась исходником.
     */
    public function test_math_with_raw_angle_brackets_survives(): void
    {
        $html = '<p>$y=\\begin{cases}x+1 & \\text{при } x\\geqslant-2,\\\\ x+6 & \\text{при } x<-2.\\end{cases}$</p>'
            . '<p>Определите $m$.</p>';

        $out = $this->convert($html);

        $this->assertStringContainsString('\\begin{cases}', $out['latex']);
        $this->assertStringContainsString('x<-2', $out['latex']);
        $this->assertStringContainsString('\\end{cases}$', $out['latex']);
        $this->assertStringContainsString('Определите $m$.', $out['latex']);
    }

    /**
     * В формуле пробел между группами не печатается: «\text{при} x» на бумаге
     * слипается в «приx». KaTeX это прощал, TeX — нет.
     */
    public function test_space_after_text_group_in_math_is_preserved(): void
    {
        $out = $this->convert('<p>$x+1 & \\text{при} x\\geqslant-2$</p>');

        $this->assertStringContainsString('\\text{при}\\, x', $out['latex']);
    }

    public function test_known_typography_is_mapped(): void
    {
        $out = $this->convert('<p>&laquo;Стандартный&raquo; 18&nbsp;000 &mdash; 5&#x22C5;3</p>');

        $this->assertStringContainsString('\\guillemotleft{}', $out['latex']);
        $this->assertStringContainsString('18~000', $out['latex']);
        $this->assertStringContainsString('---', $out['latex']);
        $this->assertStringContainsString('$\\cdot$', $out['latex']);
        $this->assertSame([], $out['unknown']);
    }
}
