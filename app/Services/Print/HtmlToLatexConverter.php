<?php

namespace App\Services\Print;

/**
 * Переводит HTML задания из банка в LaTeX.
 *
 * Формулы в банке уже хранятся в долларах ($\dfrac{8,2}{4,1}$) — это тот же
 * синтаксис, что понимает TeX, поэтому математика проходит насквозь и её
 * нельзя экранировать. Всё остальное экранируется.
 *
 * Чертежи вынимаются наружу: конвертер возвращает LaTeX с плейсхолдерами
 * \ogeFigure{N}, а список SVG отдаёт вызывающему, чтобы тот сам решил,
 * куда их положить и в каком размере вставить.
 */
class HtmlToLatexConverter
{
    /** @var list<string> Разметка вынутых чертежей в порядке появления. */
    private array $figures = [];

    /** @var list<string> Пути растровых иллюстраций в порядке появления. */
    private array $assets = [];

    /** @var list<string> Формулы, вынутые из разметки до разбора DOM. */
    private array $math = [];

    /** Глубина вложенности таблиц: внешняя — 0, вложенная в ячейку — 1 и глубже. */
    private int $tableDepth = 0;

    /** @var array<string, string> Незнакомые символы, встреченные при переводе. */
    private array $unknownChars = [];

    private const ENTITIES = [
        '&nbsp;' => "\u{00A0}",
        '&mdash;' => '---',
        '&ndash;' => '--',
        '&laquo;' => '«',
        '&raquo;' => '»',
        '&deg;' => '$^{\circ}$',
        '&times;' => '$\times$',
        '&minus;' => '$-$',
        '&hellip;' => '\ldots{}',
    ];

    /**
     * @return array{latex: string, figures: list<string>, assets: list<string>,
     *               unknown: array<string, string>}
     */
    public function convert(string $html): array
    {
        $this->figures = [];
        $this->assets = [];
        $this->math = [];
        $this->tableDepth = 0;
        $this->unknownChars = [];

        $html = strtr($html, self::ENTITIES);
        $html = $this->stashFigures($html);
        $html = $this->stashMath($html);

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="oge-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('oge-root');
        $latex = $root ? $this->renderChildren($root) : $this->escape(strip_tags($html));

        return [
            'latex' => $this->tidy($latex),
            'figures' => $this->figures,
            'assets' => $this->assets,
            'unknown' => $this->unknownChars,
        ];
    }

    /**
     * Вынимает <svg>…</svg> до разбора DOM.
     *
     * DOMDocument в режиме HTML не понимает вложенный SVG и рвёт его на части,
     * поэтому чертежи изымаются регулярным выражением заранее, а на их место
     * встаёт маркер.
     */
    private function stashFigures(string $html): string
    {
        return preg_replace_callback(
            '#<svg\b.*?</svg>#is',
            function (array $m): string {
                $this->figures[] = $m[0];

                return '<span data-oge-figure="' . (count($this->figures) - 1) . '"></span>';
            },
            $html
        ) ?? $html;
    }

    /**
     * Вынимает формулы до разбора DOM.
     *
     * В банке формулы хранятся в долларах и содержат сырые «<» и «>»:
     * условие «\$x<-2\$» для HTML-парсера выглядит как открывающийся тег, и
     * дальше он проглатывает всё до следующего «>» вместе с закрывающим
     * долларом. Формула печаталась исходником. Поэтому математика изымается
     * раньше, чем разметка попадает в DOMDocument.
     */
    private function stashMath(string $html): string
    {
        return preg_replace_callback(
            '/\$[^$]*\$|\\\([\s\S]*?\\\)/',
            function (array $m): string {
                $this->math[] = $this->normalizeMath($m[0]);

                return '<span data-oge-math="' . (count($this->math) - 1) . '"></span>';
            },
            $html
        ) ?? $html;
    }

    private function renderChildren(\DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= $this->renderNode($child);
        }

        return $out;
    }

    private function renderNode(\DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return $this->escapeKeepingMath($node->nodeValue ?? '');
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        /** @var \DOMElement $node */
        $tag = strtolower($node->tagName);

        return match ($tag) {
            'p', 'div' => $this->wrapParagraph($this->renderChildren($node)),
            'br' => "\\\\\n",
            'b', 'strong' => $this->wrap('textbf', $this->renderChildren($node)),
            'i', 'em' => $this->wrap('textit', $this->renderChildren($node)),
            'u' => $this->wrap('underline', $this->renderChildren($node)),
            'sub' => '$_{' . $this->stripMath($this->renderChildren($node)) . '}$',
            'sup' => '$^{' . $this->stripMath($this->renderChildren($node)) . '}$',
            'table' => $this->renderTable($node),
            'ul', 'ol' => $this->renderList($node),
            'span' => $this->renderSpan($node),
            'img' => $this->renderImage($node),
            'figure', 'aside' => $this->renderChildren($node),
            'figcaption' => '',
            'math', 'semantics', 'annotation' => $this->renderMathMl($node),
            default => $this->renderChildren($node),
        };
    }

    /**
     * Растровая иллюстрация вводного текста.
     *
     * Файл не разыскивается здесь: конвертер не знает ни про кэш, ни про сеть.
     * Наружу отдаётся только путь из разметки, а решение, откуда его взять,
     * принимает вызывающий.
     */
    private function renderImage(\DOMElement $node): string
    {
        $src = trim($node->getAttribute('src'));
        if ($src === '') {
            return '';
        }

        $this->assets[] = $src;

        return '\\ogeAsset{' . (count($this->assets) - 1) . '}';
    }

    /**
     * MathML из сырой разметки ФИПИ.
     *
     * Во вводных текстах формул почти нет — попадаются тире и знаки градуса,
     * завёрнутые в <math>. Разбирать MathML ради них незачем: берём текстовое
     * содержимое, оно и есть нужный символ.
     */
    private function renderMathMl(\DOMElement $node): string
    {
        return $this->escape(trim($node->textContent));
    }

    private function renderSpan(\DOMElement $node): string
    {
        $figure = $node->getAttribute('data-oge-figure');
        if ($figure !== '') {
            return '\\ogeFigure{' . (int) $figure . '}';
        }

        $math = $node->getAttribute('data-oge-math');
        if ($math !== '') {
            return $this->math[(int) $math] ?? '';
        }

        return $this->renderChildren($node);
    }

    private function wrapParagraph(string $inner): string
    {
        $inner = trim($inner);

        return $inner === '' ? '' : $inner . "\n\n";
    }

    /** Пустая обёртка выбрасывается: у ФИПИ полно <b> вокруг одного пробела. */
    private function wrap(string $macro, string $inner): string
    {
        $inner = trim($inner);

        if ($inner === '' || trim($inner, '~ ') === '') {
            return $inner;
        }

        return '\\' . $macro . '{' . $inner . '}';
    }

    /** Содержимое <sub>/<sup> попадает внутрь формулы — доллары там лишние. */
    private function stripMath(string $s): string
    {
        return trim(str_replace('$', '', $s));
    }

    private function renderList(\DOMElement $node): string
    {
        $items = [];
        foreach ($node->getElementsByTagName('li') as $li) {
            $items[] = trim($this->renderChildren($li));
        }

        if ($items === []) {
            return '';
        }

        return "\n" . implode("\\\\\n", $items) . "\n\n";
    }

    /**
     * Таблицы в банке бывают двух родов, и путать их нельзя.
     *
     * ФИПИ вёрстывал таблицей и раскладку: колонку блоков, полосу «А) чертёж
     * Б) чертёж», подпись под рисунком. Такие таблицы не должны печататься
     * линейками — линейки положены только таблицам данных из условия.
     */
    private function renderTable(\DOMElement $node): string
    {
        $nested = $this->tableDepth > 0;

        $this->tableDepth++;
        $rows = $this->tableRows($node);
        $this->tableDepth--;

        if ($rows === []) {
            return '';
        }

        $cols = max(array_map('count', $rows));

        // Колонка из одной ячейки — это стопка блоков, а не таблица.
        if ($cols === 1) {
            $blocks = [];
            foreach ($rows as $row) {
                $cell = trim($row[0]);
                if ($cell !== '') {
                    $blocks[] = $cell;
                }
            }

            return $blocks === [] ? '' : implode("\n\n", $blocks) . "\n\n";
        }

        // Классическая раскладка бланка: условие слева, чертёж справа.
        if ($side = $this->asSideBySide($rows, $cols)) {
            return $side;
        }

        // Вложенная таблица у ФИПИ — всегда раскладка: так свёрстаны полосы
        // «А) Б) В)» в задании на соответствие. Линейки там были бы клеткой
        // вокруг каждой формулы, чего в бланке нет.
        return ($nested || $this->hasFigures($rows))
            ? $this->renderPlainTable($rows, $cols)
            : $this->renderDataTable($rows, $cols);
    }

    /**
     * Строка «текст | чертёж» — это не таблица, а раскладка полосы.
     *
     * В колонках выравнивания (c) длинное условие не переносится и уезжает за
     * поле: та же строка, что на экране умещалась в ячейку, на A4 вылезает на
     * два сантиметра вправо. Такую пару верстаем двумя minipage.
     *
     * @param list<list<string>> $rows
     */
    private function asSideBySide(array $rows, int $cols): ?string
    {
        if (count($rows) !== 1 || $cols !== 2) {
            return null;
        }

        [$left, $right] = array_pad($rows[0], 2, '');

        $leftIsFigure = str_contains($left, '\\ogeFigure{') || str_contains($left, '\\ogeAsset');
        $rightIsFigure = str_contains($right, '\\ogeFigure{') || str_contains($right, '\\ogeAsset');

        if ($leftIsFigure === $rightIsFigure) {
            return null;
        }

        $text = $leftIsFigure ? $right : $left;
        $figure = $leftIsFigure ? $left : $right;

        if (trim($text) === '') {
            return null;
        }

        return '\\ogeSideBySide{' . trim($text) . '}{' . trim($figure) . "}\n\n";
    }

    /** @param list<list<string>> $rows */
    private function hasFigures(array $rows): bool
    {
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                if (str_contains($cell, '\\ogeFigure{') || str_contains($cell, '\\ogeAsset')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Прямые дочерние строки таблицы.
     *
     * Именно прямые: getElementsByTagName('tr') собирает и строки вложенных
     * таблиц, а у ФИПИ вложенность в задании 11 — четыре уровня. Внешняя
     * таблица получала бы строки всех внутренних и печаталась по разу на
     * каждый уровень вложенности.
     *
     * @return list<list<string>>
     */
    private function tableRows(\DOMElement $table): array
    {
        $rows = [];

        foreach ($this->directRows($table) as $tr) {
            $cells = [];
            foreach ($tr->childNodes as $cell) {
                if ($cell->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                $tag = strtolower($cell->nodeName);
                if ($tag !== 'td' && $tag !== 'th') {
                    continue;
                }
                $cells[] = trim($this->renderChildren($cell));
            }
            if ($cells !== []) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    /** @return list<\DOMElement> */
    private function directRows(\DOMElement $table): array
    {
        $rows = [];

        foreach ($table->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $tag = strtolower($child->nodeName);
            if ($tag === 'tr') {
                $rows[] = $child;
            } elseif (in_array($tag, ['tbody', 'thead', 'tfoot'], true)) {
                foreach ($child->childNodes as $inner) {
                    if ($inner->nodeType === XML_ELEMENT_NODE && strtolower($inner->nodeName) === 'tr') {
                        $rows[] = $inner;
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Раскладка без линеек: полоса «А) чертёж Б) чертёж», рисунок с подписью.
     *
     * @param list<list<string>> $rows
     */
    private function renderPlainTable(array $rows, int $cols): string
    {
        $body = '';
        $longest = 0;
        foreach ($rows as $row) {
            $row = array_pad($row, $cols, '');
            foreach ($row as $cell) {
                $longest = max($longest, mb_strlen($cell));
            }
            $body .= implode(' & ', array_map([$this, 'flattenCell'], $row)) . "\\\\\n";
        }

        // Короткие ячейки («А)», формула, номер) центруются; длинный текст
        // обязан переноситься, иначе строка вылезает за поле набора.
        $spec = $longest > 60
            ? str_repeat('p{' . sprintf('%.3f', 0.98 / $cols) . '\\linewidth}', $cols)
            : str_repeat('c', $cols);

        return "\n\\ogePlainTable{" . $spec . "}{\n" . $body . "}\n\n";
    }

    /**
     * Таблица данных из условия — с линейками.
     *
     * Данные ФИПИ приходят с рваными строками: в шапке две ячейки, ниже пять.
     * Ширина считается по самой длинной строке, короткие дополняются пустыми,
     * иначе LaTeX упадёт на несовпадении числа колонок.
     *
     * @param list<list<string>> $rows
     */
    private function renderDataTable(array $rows, int $cols): string
    {
        $spec = '|' . str_repeat('c|', $cols);

        $body = '';
        foreach ($rows as $row) {
            $row = array_pad($row, $cols, '');
            $body .= '\\cs ' . implode(' & ', array_map([$this, 'flattenCell'], $row)) . "\\\\\n\\hline\n";
        }

        return "\n\\ogeTable{" . $spec . "}{\n\\hline\n" . $body . "}\n\n";
    }

    /** Внутри ячейки не должно быть \par: перевод абзаца порвёт таблицу. */
    private function flattenCell(string $cell): string
    {
        return trim(str_replace(
            ["\n\n", "\n", '\\ogeAsset{', '\\ogeRaster{'],
            [' ', ' ', '\\ogeAssetCell{', '\\ogeRasterCell{'],
            $cell
        ));
    }

    /**
     * Экранирование с сохранением математики.
     *
     * Формула отдаётся TeX как есть, текст вокруг экранируется. Разделение
     * идёт по $…$ и \(…\); незакрытый доллар считается текстом, чтобы одна
     * битая задача не роняла сборку всего варианта.
     */
    private function escapeKeepingMath(string $text): string
    {
        $parts = preg_split('/(\$[^$]*\$|\\\\\([\s\S]*?\\\\\))/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $this->escape($text);
        }

        $out = '';
        foreach ($parts as $part) {
            $isMath = (str_starts_with($part, '$') && str_ends_with($part, '$') && strlen($part) > 1)
                || str_starts_with($part, '\\(');
            $out .= $isMath ? $this->normalizeMath($part) : $this->escape($part);
        }

        return $out;
    }

    /** \(…\) банка приводится к $…$: в тексте варианта они равноправны. */
    private function normalizeMath(string $math): string
    {
        if (str_starts_with($math, '\\(')) {
            $math = '$' . substr($math, 2, -2) . '$';
        }

        // В банке встречается «\text{при} x»: в формуле пробел между группами
        // не печатается, и на бумаге выходит слипшееся «приx». На экране KaTeX
        // это прощал, TeX — нет, поэтому вставляем тонкий пробел.
        return preg_replace('/(\\\\text\{[^}]*\})\s+(?=[^\s,.;:)\\}\$])/u', '$1\\, ', $math) ?? $math;
    }

    private function escape(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $text = strtr($text, [
            '\\' => '\\textbackslash{}',
            '{' => '\\{',
            '}' => '\\}',
            '$' => '\\$',
            '&' => '\\&',
            '#' => '\\#',
            '%' => '\\%',
            '_' => '\\_',
            '^' => '\\textasciicircum{}',
            '~' => '\\textasciitilde{}',
            '№' => '\\textnumero{}',
            '−' => '$-$',
            '×' => '$\\times$',
            '·' => '$\\cdot$',
            '≤' => '$\\leqslant$',
            '≥' => '$\\geqslant$',
            '∠' => '$\\angle$',
            '°' => '$^{\\circ}$',
            '…' => '\\ldots{}',
            '—' => '---',
            '–' => '--',
            "\u{00A0}" => '~',
            "\u{2022}" => '\\textbullet{}~',
            "\u{00AD}" => '\\-',      // мягкий перенос
            "\u{2009}" => '\\,',      // тонкий пробел
            "\u{22C5}" => '$\\cdot$',  // знак умножения точкой
            "\u{00B7}" => '$\\cdot$',
            "\u{00D7}" => '$\\times$',
            "\u{00BA}" => '$^{\\circ}$',
            "\u{2032}" => "\$'\$",
            "\u{221A}" => '$\\sqrt{\\phantom{x}}$',
            "\u{2260}" => '$\\ne$',
            "\u{2248}" => '$\\approx$',
            "\u{2192}" => '$\\to$',
            "\u{2212}" => '$-$',
            '«' => '\\guillemotleft{}',
            '»' => '\\guillemotright{}',
        ]);

        return $this->fallbackUnknown($text);
    }

    /**
     * Страховка от незнакомого символа.
     *
     * Один экзотический глиф в одной задаче не должен ронять сборку сотни
     * работ: pdfLaTeX на неизвестном юникоде падает намертво. Символ
     * заменяется вопросительным знаком и записывается в журнал вместе с
     * кодовой точкой — так пропажу видно и её можно внести в таблицу выше,
     * а не искать по логам pdflatex.
     */
    private function fallbackUnknown(string $text): string
    {
        return preg_replace_callback(
            '/[^\x{0000}-\x{007F}\x{0400}-\x{04FF}]/u',
            function (array $m): string {
                $code = mb_ord($m[0], 'UTF-8');
                $this->unknownChars[sprintf('U+%04X', $code)] = $m[0];

                return '?';
            },
            $text
        ) ?? $text;

        return $text;
    }

    private function tidy(string $latex): string
    {
        $latex = preg_replace('/[ \t]+/u', ' ', $latex) ?? $latex;
        $latex = preg_replace('/\n{3,}/', "\n\n", $latex) ?? $latex;

        return trim($latex);
    }
}
