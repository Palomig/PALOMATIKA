<?php

namespace App\Services\Print;

use App\Models\Task;
use App\Models\TaskIntro;

/**
 * Собирает исходник .tex печатного варианта из отобранных заданий.
 *
 * Разметка и метрики лежат в resources/latex/oge; сюда попадает только
 * содержимое — так вёрстку можно править, не трогая PHP.
 */
class PrintVariantComposer
{
    private const INSTR_PART1 = 'Ответами к заданиям 1--19 являются число или последовательность цифр,'
        . ' которые следует записать в {\upshape БЛАНК ОТВЕТОВ \textnumero{} 1} справа от номера'
        . ' соответствующего задания, начиная с первой клеточки. Если ответом является'
        . ' последовательность цифр, то запишите её без пробелов и других дополнительных'
        . ' символов. Каждый символ пишите в отдельной клеточке в соответствии с'
        . ' приведёнными в бланке образцами.';

    private const INSTR_PART2 = 'При выполнении заданий 20--25 используйте'
        . ' {\upshape БЛАНК ОТВЕТОВ \textnumero{} 2}. Сначала укажите номер задания,'
        . ' а затем запишите его решение и ответ. Пишите чётко и разборчиво.';

    /** @var array<string, string> Незнакомые символы, встреченные в варианте. */
    private array $unknown = [];

    public function __construct(
        private readonly HtmlToLatexConverter $converter,
        private readonly SvgPrintConverter $svg,
        private readonly PrintAssetStore $assets,
    ) {
    }

    /** @return array<string, string> */
    public function unknownChars(): array
    {
        return $this->unknown;
    }

    /**
     * @param list<array{number: int, topic: string, task: Task, part2: bool}> $items
     * @return array{tex: string, answers: array<int, string>}
     */
    public function compose(
        array $items,
        string $title,
        string $headLeft,
        string $headRight,
        ?TaskIntro $intro = null,
    ): array {
        $body = '';
        $answers = [];
        $partTwoOpened = false;
        $this->unknown = [];

        $introPrinted = $intro === null;

        foreach ($items as $item) {
            /** @var Task $task */
            $task = $item['task'];

            // Общее условие блока 1–5 печатается один раз, перед первым его
            // заданием: пять вопросов ссылаются на один и тот же план или график.
            if (!$introPrinted && $item['number'] <= 5) {
                $body .= $this->renderIntro($intro);
                $introPrinted = true;
            }

            if ($item['part2'] && !$partTwoOpened) {
                $body .= "\n\\newpage\n\\ogePartTwo\n\n"
                    . "\\begin{instrbox}\n\\itshape\n" . self::INSTR_PART2 . "\n\\end{instrbox}\n\n";
                $partTwoOpened = true;
            }

            $body .= $this->renderTask($item['number'], $task, $item['part2']);

            $answer = (string) ($task->answer ?? $task->payload['answer'] ?? '');
            if ($answer !== '') {
                $answers[$item['number']] = $answer;
            }
        }

        $tex = "\\documentclass[12pt,a4paper]{article}\n"
            . "\\usepackage{geometry}\n"
            . '\\newcommand{\\OGEheadleft}{' . $headLeft . "}\n"
            . '\\newcommand{\\OGEheadright}{' . $headRight . "}\n"
            . "\\input{params}\n\\input{preamble}\n\n"
            . "\\begin{document}\n\n"
            . '\\ogeTitle{' . $title . "}\n\n"
            . "\\begin{instrbox}\n\\itshape\n" . self::INSTR_PART1 . "\n\\end{instrbox}\n\n"
            . $body
            . "\n\\end{document}\n";

        return ['tex' => $tex, 'answers' => $answers];
    }

    /**
     * Вводный текст блока 1–5.
     *
     * Печатается без рамки задания и без номера — это условие, а не вопрос.
     * Иллюстрации ставятся с обтеканием: экспорт ФИПИ сваливает их все в
     * начало текста, и если печатать так, два рисунка съедают полосу целиком,
     * а условие уезжает на следующую страницу отдельно от своих картинок.
     */
    private function renderIntro(TaskIntro $intro): string
    {
        $converted = $this->converter->convert($intro->html);
        $this->unknown += $converted['unknown'];

        $latex = $this->resolveFigures($converted['latex'], $converted['figures'], 0, 'intro');
        $latex = $this->layoutIntroAssets($latex, $converted['assets'], $converted['captions']);

        return "\\ogeIntro{\n" . trim($latex) . "\n}\n\n";
    }

    /** Строк текста в абзаце на глаз: полоса набора вмещает около 85 знаков. */
    private const CHARS_PER_LINE = 85;

    /** Полная ширина полосы набора в пунктах — от неё считается узкая строка. */
    private const TEXT_WIDTH_PT = 481.9;

    /** Кегль 12pt даёт интерлиньяж 14,446pt — им и меряем высоту обтекания. */
    private const BASELINE_PT = 14.446;

    /**
     * Расставляет иллюстрации вводного текста по абзацам с обтеканием.
     *
     * @param list<string> $assets
     * @param array<int, string> $captions
     */
    private function layoutIntroAssets(string $latex, array $assets, array $captions): string
    {
        $wraps = [];

        foreach ($assets as $index => $relative) {
            $info = $this->assets->describe($relative);

            if ($info === null) {
                $latex = $this->replaceAsset($latex, $index, '\\textit{[иллюстрация недоступна]}');
                continue;
            }

            // Мелкий растр — это формула внутри предложения, её не двигаем.
            if ($info['height'] <= self::INLINE_HEIGHT_PT) {
                $latex = $this->replaceAsset(
                    $latex,
                    $index,
                    sprintf('\\ogeRasterInline{%s}{%.1f}', $info['path'], $info['height'])
                );
                continue;
            }

            $latex = $this->replaceAsset($latex, $index, '');
            $wraps[] = [
                'path' => $info['path'],
                'width' => min($info['width'], 200.0),
                'height' => $info['height'],
                'caption' => $captions[$index] ?? '',
            ];
        }

        return $wraps === [] ? $latex : $this->injectWraps($latex, $wraps);
    }

    private function replaceAsset(string $latex, int $index, string $with): string
    {
        foreach (['ogeAsset', 'ogeAssetCell'] as $macro) {
            $latex = str_replace('\\' . $macro . '{' . $index . '}', $with, $latex);
        }

        return $latex;
    }

    /**
     * Вставляет обтекаемые рисунки в начала абзацев.
     *
     * wrapfig не считает, сколько строк займёт рисунок, — число строк ему
     * задаётся вручную, и если поставить два рисунка подряд, второй наедет на
     * первый. Поэтому идём по абзацам, прикидывая их длину по числу знаков, и
     * ставим следующий рисунок только тогда, когда предыдущий уже обтёк.
     *
     * @param list<array{path: string, width: float, height: float, caption: string}> $wraps
     */
    private function injectWraps(string $latex, array $wraps): string
    {
        $paragraphs = preg_split('/\n{2,}/', trim($latex)) ?: [];
        $paragraphs = array_values(array_filter($paragraphs, static fn (string $s): bool => trim($s) !== ''));

        if ($paragraphs === []) {
            return $latex;
        }

        // Первый абзац оставляем во всю ширину: он вводит сюжет, и рисунок
        // рядом с одной-двумя строками смотрится оторванным.
        $at = min(1, count($paragraphs) - 1);

        foreach ($wraps as $wrap) {
            if ($at >= count($paragraphs)) {
                // Абзацы кончились — остаток печатаем обычными иллюстрациями.
                $paragraphs[] = sprintf('\\ogeRaster{%s}{%.1f}', $wrap['path'], $wrap['width']);
                continue;
            }

            $fit = $this->fitWrap($paragraphs, $at, $wrap);

            // Обтекать нечем даже уменьшенным рисунком: до конца условия
            // осталось меньше строк, чем он занимает. wrapfig в этом случае
            // пускает обтекание дальше — в первое задание, и оно печатается
            // узкой колонкой. Тогда печатаем рисунок обычным блоком.
            if ($fit === null) {
                $paragraphs[] = sprintf('\\ogeRaster{%s}{%.1f}', $wrap['path'], $wrap['width'])
                    . ($wrap['caption'] !== '' ? "\n\n" . $wrap['caption'] : '');
                continue;
            }

            [$width, $lines] = $fit;

            $paragraphs[$at] = sprintf(
                '\\ogeWrap{%d}{%.1f}{%s}{%s}',
                $lines,
                $width,
                $wrap['path'],
                $wrap['caption']
            ) . "\n" . $paragraphs[$at];

            $at = $this->nextFreeParagraph($paragraphs, $at, $lines, $width);
        }

        return implode("\n\n", $paragraphs);
    }

    /** Уже этого рисунок не сжимаем: подписи на чертеже станут нечитаемы. */
    private const MIN_WRAP_WIDTH_PT = 120.0;

    /**
     * Подбирает ширину рисунка, при которой его обтекает оставшийся текст.
     *
     * Уменьшение помогает дважды: рисунок становится ниже, и строка рядом с
     * ним длиннее — оставшегося условия хватает на большее число строк.
     *
     * @param list<string> $paragraphs
     * @param array{path: string, width: float, height: float, caption: string} $wrap
     * @return array{0: float, 1: int}|null Ширина и число обтекающих строк.
     */
    private function fitWrap(array $paragraphs, int $at, array $wrap): ?array
    {
        $caption = $wrap['caption'] !== '' ? 12.0 : 0.0;

        for ($width = $wrap['width']; $width >= self::MIN_WRAP_WIDTH_PT; $width -= 12.0) {
            $height = $wrap['height'] * ($width / $wrap['width']);
            $lines = (int) ceil(($height + $caption) / self::BASELINE_PT) + 1;

            if ($this->linesFrom($paragraphs, $at, $width) >= $lines) {
                return [$width, $lines];
            }
        }

        return null;
    }

    /**
     * Первый абзац, до которого предыдущий рисунок уже закончил обтекаться.
     *
     * @param list<string> $paragraphs
     */
    private function nextFreeParagraph(array $paragraphs, int $from, int $linesNeeded, float $width): int
    {
        $covered = 0;

        for ($i = $from; $i < count($paragraphs); $i++) {
            $covered += $this->paragraphLines($paragraphs[$i], $width);
            if ($covered >= $linesNeeded) {
                return $i + 1;
            }
        }

        return count($paragraphs);
    }

    /**
     * Сколько строк текста осталось до конца условия начиная с абзаца.
     *
     * @param list<string> $paragraphs
     */
    private function linesFrom(array $paragraphs, int $from, float $width): int
    {
        $lines = 0;
        for ($i = $from; $i < count($paragraphs); $i++) {
            $lines += $this->paragraphLines($paragraphs[$i], $width);
        }

        return $lines;
    }

    /**
     * Длина абзаца в строках.
     *
     * Рядом с рисунком строка короче на его ширину — считать её полной значит
     * недооценить абзац вдвое и пустить обтекание за пределы условия.
     */
    private function paragraphLines(string $paragraph, float $width): int
    {
        $share = max(0.25, (self::TEXT_WIDTH_PT - $width - 10.0) / self::TEXT_WIDTH_PT);
        $perLine = max(20.0, self::CHARS_PER_LINE * $share);

        return max(1, (int) ceil(mb_strlen($paragraph) / $perLine));
    }

    private function renderTask(int $number, Task $task, bool $part2): string
    {
        $payload = $task->payload ?? [];
        $html = (string) ($payload['html'] ?? '');

        $converted = $this->converter->convert($html);
        $this->unknown += $converted['unknown'];
        $latex = $this->resolveFigures($converted['latex'], $converted['figures'], $task->id);
        $latex = $this->resolveAssets($latex, $converted['assets']);

        $latex = $this->sideBySideIfCompact($latex);
        $latex .= $this->renderOptions($payload, $task->id);

        $out = "\\begin{zadanie}{{$number}}\n" . $this->bindParagraphs(trim($latex)) . "\n";
        $out .= $part2 ? "\\ogesolutiongap\n" : "\\otvet\n";
        $out .= "\\end{zadanie}\n\n";

        return $out;
    }

    /**
     * Запрещает разрыв страницы между абзацами одного задания.
     *
     * Высокого штрафа между строками мало: граница абзацев — отдельный,
     * ничем не защищённый разрыв, и условие из двух абзацев всё равно
     * расползается по двум листам.
     */
    private function bindParagraphs(string $latex): string
    {
        return str_replace("\n\n", "\n\n\\nopagebreak\n", $latex);
    }

    /**
     * Варианты ответа печатаются нумерованным списком.
     *
     * Ответом в банке служит номер варианта, а не буква, поэтому и на печати
     * нумерация только цифровая — иначе ключ разойдётся с бланком.
     */
    private function renderOptions(array $payload, int $taskId): string
    {
        $options = $payload['options'] ?? null;
        if (!is_array($options) || $options === []) {
            return '';
        }

        $rows = '';
        foreach ($options as $i => $option) {
            $optHtml = (string) ($option['html'] ?? $option['text'] ?? '');
            $converted = $this->converter->convert($optHtml);
            $this->unknown += $converted['unknown'];
            $text = $this->resolveFigures($converted['latex'], $converted['figures'], $taskId, 'opt' . $i);
            $n = (int) ($option['n'] ?? ($i + 1));
            $rows .= '\\ogeChoice{' . $n . '}{' . trim($text) . "}\n";
        }

        return "\n\\ogeChoices{\n" . $rows . "}\n";
    }

    /**
     * Компактный чертёж переносится вправо от текста.
     *
     * В банке ФИПИ рисунок к геометрической задаче лежит отдельным блоком —
     * то до условия, то после. В печатном бланке он всегда справа от текста,
     * иначе половина страницы уходит на воздух вокруг маленького треугольника.
     * Переносим только одиночный небольшой чертёж, стоящий отдельным абзацем:
     * широкий план участка и чертёж внутри предложения остаются на месте.
     */
    private function sideBySideIfCompact(string $latex): string
    {
        if (substr_count($latex, '\\ogeTaskFig{') !== 1) {
            return $latex;
        }

        $blocks = preg_split('/\n{2,}/', trim($latex)) ?: [];
        if (count($blocks) < 2) {
            return $latex;
        }

        $figureAt = null;
        foreach ($blocks as $i => $block) {
            if (!str_contains($block, '\\ogeTaskFig{')) {
                continue;
            }
            // Абзац, состоящий только из чертежа: ничего, кроме картинки.
            if (preg_match('/^\\\\ogeTaskFig\{([^}]+)\}\{([\d.]+)\}$/', trim($block), $m) !== 1) {
                return $latex;
            }
            $figureAt = $i;
            $path = $m[1];
            $width = (float) $m[2];
        }

        if ($figureAt === null || $width > 160.0) {
            return $latex;
        }

        unset($blocks[$figureAt]);
        $text = trim(implode("\n\n", $blocks));

        if ($text === '') {
            return $latex;
        }

        // Внутри minipage нужен голый \includegraphics: макрос блочного
        // чертежа начинается с \par и порвал бы колонку.
        $figure = sprintf('\\includegraphics[width=%.1fpt]{%s}', $width, $path);

        return '\\ogeSideBySide{' . $text . '}{' . $figure . "}\n\n";
    }

    /** Растр не выше этого — обозначение внутри строки, а не иллюстрация. */
    private const INLINE_HEIGHT_PT = 30.0;

    /**
     * Подстановка растровых иллюстраций.
     *
     * Различаем по натуральной высоте. У ФИПИ часть формул хранится картинками
     * («B=195» — это растр 60×20 px), и по разметке они неотличимы от плана
     * участка. Вынести такую формулу отдельной иллюстрацией — значит выбросить
     * кусок предложения: остаётся «имеет ширину  мм».
     *
     * Пропавший файл не роняет сборку, а оставляет заметку в тексте: тихо
     * выпущенная картинка сделала бы задание неразрешимым незаметно.
     */
    private function resolveAssets(string $latex, array $assets): string
    {
        foreach ($assets as $index => $relative) {
            $info = $this->assets->describe($relative);

            foreach (['ogeAsset' => false, 'ogeAssetCell' => true] as $macro => $inCell) {
                $latex = str_replace(
                    '\\' . $macro . '{' . $index . '}',
                    $this->rasterCall($info, $inCell),
                    $latex
                );
            }
        }

        return $latex;
    }

    /** @param array{path: string, width: float, height: float}|null $info */
    private function rasterCall(?array $info, bool $inCell): string
    {
        if ($info === null) {
            return '\\textit{[иллюстрация недоступна]}';
        }

        if ($info['height'] <= self::INLINE_HEIGHT_PT) {
            return sprintf('\\ogeRasterInline{%s}{%.1f}', $info['path'], $info['height']);
        }

        return sprintf(
            '\\%s{%s}{%.1f}',
            $inCell ? 'ogeRasterCell' : 'ogeRaster',
            $info['path'],
            $info['width']
        );
    }

    /**
     * Подстановка чертежей вместо плейсхолдеров.
     *
     * Ширина выбирается по натуральному размеру: широкие планы и графики
     * печатаются во всю колонку, компактные чертежи — в своём размере,
     * чтобы не раздувать треугольник на пол-листа.
     */
    private function resolveFigures(string $latex, array $figures, int $taskId, string $suffix = ''): string
    {
        foreach ($figures as $index => $markup) {
            $basename = 'fig-' . $taskId . ($suffix !== '' ? '-' . $suffix : '') . '-' . $index;
            $converted = $this->svg->convert($markup, $basename);

            if ($converted === null) {
                $latex = str_replace('\\ogeFigure{' . $index . '}', '', $latex);
                continue;
            }

            $widthPt = $converted['width'] * 0.75;
            $target = min($widthPt, 150.0);
            if ($widthPt > 260) {
                $target = 320.0;
            }

            // Блочный чертёж печатается макросом, а не голым \includegraphics:
            // макрос запрещает разрыв страницы между условием и рисунком.
            $include = sprintf(
                '\\ogeTaskFig{%s}{%.1f}',
                basename($converted['path'], '.pdf'),
                $target
            );

            $latex = str_replace('\\ogeFigure{' . $index . '}', $include, $latex);
        }

        return $latex;
    }
}
