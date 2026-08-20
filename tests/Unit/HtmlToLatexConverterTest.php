<?php

namespace Tests\Unit;

use App\Services\Print\HtmlToLatexConverter;
use PHPUnit\Framework\TestCase;

class HtmlToLatexConverterTest extends TestCase
{
    private function convert(string $html, ?\Closure $measure = null): array
    {
        return (new HtmlToLatexConverter($measure))->convert($html);
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

    /** Одинокая ячейка в широкой таблице — подзаголовок во всю ширину. */
    public function test_single_cell_row_spans_the_table(): void
    {
        $html = '<table><tr><td>Шапка</td></tr><tr><td>1</td><td>2</td><td>3</td></tr></table>';

        $out = $this->convert($html);

        $this->assertStringContainsString('{|c|c|c|}', $out['latex']);
        // Распорка обязана быть внутри \multicolumn: он должен открывать
        // ячейку, иначе TeX падает на «Misplaced \omit».
        $this->assertStringContainsString('\\multicolumn{3}{|c|}{\\cs Шапка}', $out['latex']);
        $this->assertStringNotContainsString('\\cs \\multicolumn{3}', $out['latex']);
    }

    /**
     * Экспорт ФИПИ теряет colspan и rowspan: «Диаметр диска (дюймы)» приезжает
     * одной ячейкой в строке из двух, а под ней четыре номера. Без сшивки
     * заголовок встаёт над одной колонкой вместо четырёх, и таблица врёт.
     */
    public function test_lost_header_merges_are_reconstructed(): void
    {
        $html = '<table>'
            . '<tr><td>Ширина шины</td><td>Диаметр диска</td></tr>'
            . '<tr><td>15</td><td>16</td><td>17</td><td>18</td></tr>'
            . '<tr><td>195</td><td>a</td><td>b</td><td>c</td><td>d</td></tr>'
            . '<tr><td>205</td><td>e</td><td>f</td><td>g</td><td>h</td></tr>'
            . '</table>';

        $out = $this->convert($html);

        $this->assertStringContainsString('\\multirow{2}{*}{Ширина шины}', $out['latex']);
        $this->assertStringContainsString('\\multicolumn{4}{c|}{Диаметр диска}', $out['latex']);
        // Под сшитой ячейкой линейка идёт только над колонками.
        $this->assertStringContainsString('\\cline{2-5}', $out['latex']);
        // Вторая строка сдвинута вправо: первую колонку держит объединение.
        $this->assertStringContainsString('\\cs & 15 & 16 & 17 & 18', $out['latex']);
    }

    /** Ровная таблица сшивок не получает: там нечего восстанавливать. */
    public function test_regular_table_is_not_merged(): void
    {
        $html = '<table><tr><td>Тип</td><td>Цена</td><td>Срок</td></tr>'
            . '<tr><td>А</td><td>1</td><td>2</td></tr>'
            . '<tr><td>Б</td><td>3</td><td>4</td></tr></table>';

        $out = $this->convert($html);

        $this->assertStringNotContainsString('\\multirow', $out['latex']);
        $this->assertStringNotContainsString('\\multicolumn', $out['latex']);
    }

    public function test_raster_image_is_collected_with_its_caption(): void
    {
        $out = $this->convert('<figure><img src="img/intro_X/plan.png"><figcaption>Рис. 1</figcaption></figure>');

        $this->assertSame(['img/intro_X/plan.png'], $out['assets']);
        $this->assertStringContainsString('\\ogeAsset{0}', $out['latex']);
        $this->assertSame('Рис. 1', $out['captions'][0]);
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

    /**
     * Экспорт ФИПИ складывает формулы-картинки в общий блок рисунков, а на их
     * месте в предложении оставляет пустой span: выходит «имеет ширину  мм».
     */
    public function test_inline_formula_images_are_returned_into_the_sentence(): void
    {
        $html = '<aside class="ifigs">'
            . '<figure><img src="big.jpg"><figcaption>Рис. 1</figcaption></figure>'
            . '<figure><img src="f1.png"><figcaption>Рис. 2</figcaption></figure>'
            . '</aside>'
            . '<div class="itxt"><p>шина имеет ширину <span></span> мм.</p></div>';

        $measure = static fn (string $rel): ?float => $rel === 'f1.png' ? 15.0 : 200.0;

        $out = $this->convert($html, $measure);

        // Формула вернулась в предложение, крупный чертёж остался иллюстрацией.
        $this->assertSame(['big.jpg', 'f1.png'], $out['assets']);
        $this->assertStringContainsString('ширину \\ogeAsset{1} мм', $out['latex']);
    }

    /** Пустой span без парного растра — обычный мусор разметки, не трогаем. */
    public function test_stray_empty_span_is_not_filled_with_a_figure(): void
    {
        $html = '<aside class="ifigs"><figure><img src="big.jpg"></figure></aside>'
            . '<div class="itxt"><p>текст <span></span></p></div>';

        $out = $this->convert($html, static fn (): ?float => 200.0);

        $this->assertSame(['big.jpg'], $out['assets']);
        $this->assertStringNotContainsString('текст \\ogeAsset', $out['latex']);
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
