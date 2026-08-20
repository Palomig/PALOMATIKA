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
     * Печатается без рамки задания и без номера — это условие, а не вопрос,
     * ровно как в бланке ФИПИ.
     */
    private function renderIntro(TaskIntro $intro): string
    {
        $converted = $this->converter->convert($intro->html);
        $this->unknown += $converted['unknown'];
        $latex = $this->resolveFigures($converted['latex'], $converted['figures'], 0, 'intro');
        $latex = $this->resolveAssets($latex, $converted['assets']);

        return "\\ogeIntro{\n" . trim($latex) . "\n}\n\n";
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

        $out = "\\begin{zadanie}{{$number}}\n" . trim($latex) . "\n";
        $out .= $part2 ? "\\ogesolutiongap\n" : "\\otvet\n";
        $out .= "\\end{zadanie}\n\n";

        return $out;
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
     * широкий план участка и чертёж внутри предложения должны остаться на месте.
     */
    private function sideBySideIfCompact(string $latex): string
    {
        if (substr_count($latex, '\\includegraphics') !== 1) {
            return $latex;
        }

        $blocks = preg_split('/\n{2,}/', trim($latex)) ?: [];
        if (count($blocks) < 2) {
            return $latex;
        }

        $figureAt = null;
        foreach ($blocks as $i => $block) {
            if (str_contains($block, '\\includegraphics')) {
                // Абзац, состоящий только из чертежа: ничего, кроме картинки.
                if (preg_match('/^\\\\includegraphics\[[^\]]*\]\{[^}]+\}$/', trim($block)) !== 1) {
                    return $latex;
                }
                $figureAt = $i;
            }
        }

        if ($figureAt === null) {
            return $latex;
        }

        if (!preg_match('/width=([\d.]+)pt/', $blocks[$figureAt], $m) || (float) $m[1] > 160.0) {
            return $latex;
        }

        $figure = trim($blocks[$figureAt]);
        unset($blocks[$figureAt]);
        $text = trim(implode("\n\n", $blocks));

        if ($text === '') {
            return $latex;
        }

        return '\\ogeSideBySide{' . $text . '}{' . $figure . "}\n\n";
    }

    /**
     * Подстановка растровых иллюстраций.
     *
     * Ширина берётся щедрая: планы участков и графики тарифов — главный
     * носитель условия в заданиях 1–5, мелкими их печатать нельзя.
     * Пропавший файл не роняет сборку, а оставляет заметку в тексте:
     * тихо выпущенная картинка сделала бы задание неразрешимым незаметно.
     */
    private function resolveAssets(string $latex, array $assets): string
    {
        foreach ($assets as $index => $relative) {
            $local = $this->assets->localPath($relative);

            foreach (['ogeAsset' => 'ogeRaster', 'ogeAssetCell' => 'ogeRasterCell'] as $from => $to) {
                $replacement = $local === null
                    ? '\\textit{[иллюстрация недоступна]}'
                    : sprintf('\\%s{%s}', $to, $local);

                $latex = str_replace('\\' . $from . '{' . $index . '}', $replacement, $latex);
            }
        }

        return $latex;
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

            $include = sprintf(
                '\\includegraphics[width=%.1fpt]{%s}',
                $target,
                basename($converted['path'], '.pdf')
            );

            $latex = str_replace('\\ogeFigure{' . $index . '}', $include, $latex);
        }

        return $latex;
    }
}
