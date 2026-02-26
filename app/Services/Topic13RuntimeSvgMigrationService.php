<?php

namespace App\Services;

class Topic13RuntimeSvgMigrationService
{
    private const COMPACT_BG = '#0d1b2a';
    private const COMPACT_AXIS = '#c8dce8';
    private const COMPACT_LABEL = '#d4e8f7';
    private const COMPACT_HATCH = '#e8a838';
    private const TOPIC13_LABEL_FONT_SIZE = 17;

    public function __construct(
        private readonly InequalityNumberRaySvgRenderer $numberRayRenderer
    ) {
    }

    /**
     * Phase 2 runtime migration: Block 1, Zadaniya 10-13.
     */
    public function migrate(array $data): array
    {
        if (!isset($data['blocks'][0]['zadaniya']) || !is_array($data['blocks'][0]['zadaniya'])) {
            return $data;
        }

        foreach ($data['blocks'][0]['zadaniya'] as $zadanieIndex => $zadanie) {
            if (!is_array($zadanie)) {
                continue;
            }

            $number = (int) ($zadanie['number'] ?? 0);
            if (!in_array($number, [10, 11, 12, 13], true)) {
                continue;
            }

            if (!isset($zadanie['tasks']) || !is_array($zadanie['tasks'])) {
                continue;
            }

            foreach ($zadanie['tasks'] as $taskIndex => $task) {
                if (!is_array($task)) {
                    continue;
                }

                if ($number === 10) {
                    $sourceExpr = $this->sourceExpressionForTask($number, $task);
                    if ($sourceExpr === null) {
                        continue;
                    }

                    $solution = $this->solveInequality($sourceExpr);
                    if ($solution === null) {
                        continue;
                    }

                    $graphOptions = $this->topic13Z10GraphOptionsForSolution($solution);
                    if ($graphOptions !== null) {
                        [$graphOptions, $newAnswer] = $this->reorderGraphOptionsAndAnswer(
                            $graphOptions,
                            (int) ($task['answer'] ?? 1),
                            (int) ($task['id'] ?? ($taskIndex + 1))
                        );

                        $task['graph_options'] = $graphOptions;
                        $task['answer'] = (string) $newAnswer;
                        $task['graph_options_mode'] = 'compact_number_line';
                        unset($task['image']);
                        $task['runtime_svg_migration'] = 'topic13_b1_z10_13_phase2';
                        $zadanie['tasks'][$taskIndex] = $task;
                    }
                    continue;
                }

                if ($number === 13) {
                    $taskId = (int) ($task['id'] ?? ($taskIndex + 1));
                    if ($taskId === 5) {
                        $graphOptions = $this->topic13Z13Task5GraphOptions();
                    } elseif ($taskId === 7) {
                        $graphOptions = $this->topic13Z13Task7GraphOptions();
                    } else {
                        $sourceExpr = $this->sourceExpressionForTask($number, $task);
                        if ($sourceExpr === null) {
                            continue;
                        }

                        $solution = $this->solveInequality($sourceExpr);
                        if ($solution === null) {
                            continue;
                        }

                        $graphOptions = $this->topic13Z10GraphOptionsForSolution($solution);
                    }

                    if ($graphOptions === null) {
                        continue;
                    }

                    [$graphOptions, $newAnswer] = $this->reorderGraphOptionsAndAnswer(
                        $graphOptions,
                        (int) ($task['answer'] ?? 1),
                        $taskId
                    );

                    $task['graph_options'] = $graphOptions;
                    $task['answer'] = (string) $newAnswer;
                    $task['graph_options_mode'] = 'compact_number_line';
                    unset($task['image'], $task['svg']);
                    $task['runtime_svg_migration'] = 'topic13_b1_z13_semantic_options';
                    $zadanie['tasks'][$taskIndex] = $task;
                    continue;
                }

                if (empty($task['image']) || !is_string($task['image'])) {
                    continue;
                }

                if ($number === 11) {
                    if (!empty($task['svg']) && is_string($task['svg'])) {
                        continue;
                    }

                    $sourceExpr = $this->sourceExpressionForTask($number, $task);
                    if ($sourceExpr === null) {
                        continue;
                    }

                    $solution = $this->solveInequality($sourceExpr);
                    if ($solution === null) {
                        continue;
                    }

                    $runtimeSvgId = 'topic13-b1-z11-prompt-' . ($task['id'] ?? ($taskIndex + 1));
                    $svg = $this->renderSolutionSvg($solution, [
                        'mode' => 'compact_option',
                        'runtime_svg_id' => $runtimeSvgId,
                    ]);
                    if ($svg === null) {
                        continue;
                    }

                    $task['svg'] = $svg;
                    unset($task['image']);
                    $task['runtime_svg_migration'] = 'topic13_b1_z11_semantic_prompt';
                    $zadanie['tasks'][$taskIndex] = $task;
                    continue;
                }

                if (!empty($task['svg']) && is_string($task['svg'])) {
                    if ($number === 12) {
                        unset($task['image']);
                        $task['runtime_svg_migration'] = 'topic13_b1_z12_semantic_prompt';
                        $zadanie['tasks'][$taskIndex] = $task;
                    }
                    continue;
                }

                $sourceExpr = $this->sourceExpressionForTask($number, $task);
                if ($sourceExpr === null) {
                    continue;
                }

                $solution = $this->solveInequality($sourceExpr);
                if ($solution === null) {
                    continue;
                }

                $svgConfig = [];
                if ($number === 12) {
                    $svgConfig = [
                        'mode' => 'compact_option',
                        'runtime_svg_id' => 'topic13-b1-z12-prompt-' . ($task['id'] ?? ($taskIndex + 1)),
                    ];
                }

                $svg = $this->renderSolutionSvg($solution, $svgConfig);
                if ($svg === null) {
                    continue;
                }

                $task['svg'] = $svg;
                if ($number === 12) {
                    unset($task['image']);
                    $task['runtime_svg_migration'] = 'topic13_b1_z12_semantic_prompt';
                } else {
                    $task['runtime_svg_migration'] = 'topic13_b1_z10_13_phase2';
                }
                $zadanie['tasks'][$taskIndex] = $task;
            }

            $data['blocks'][0]['zadaniya'][$zadanieIndex] = $zadanie;
        }

        return $data;
    }

    private function sourceExpressionForTask(int $zadanieNumber, array $task): ?string
    {
        if (in_array($zadanieNumber, [10, 13], true)) {
            $expr = $task['expression'] ?? null;
            return is_string($expr) && trim($expr) !== '' ? $expr : null;
        }

        if ($zadanieNumber === 11) {
            $options = $task['options'] ?? null;
            if (!is_array($options) || $options === []) {
                return null;
            }

            $taskId = (int) ($task['id'] ?? 0);
            $promptOptionByTaskId = [
                5 => 1, // x² - 16 ≤ 0 => [-4; 4]
                7 => 2, // x² - 81 ≤ 0 => [-9; 9]
            ];
            $promptIndex = $promptOptionByTaskId[$taskId] ?? 0;

            $expr = $options[$promptIndex] ?? $options[0] ?? null;
            return is_string($expr) && trim($expr) !== '' ? $expr : null;
        }

        $firstOption = $task['options'][0] ?? null;
        return is_string($firstOption) && trim($firstOption) !== '' ? $firstOption : null;
    }

    /**
     * @return array{kind:string,intervals?:array<int,array{l:?float,r:?float,li:bool,ri:bool}>}|null
     */
    private function solveInequality(string $expr): ?array
    {
        $normalized = $this->normalizeExpression($expr);

        if (!preg_match('/^(.*?)(<=|>=|<|>)(.*)$/', $normalized, $m)) {
            return null;
        }

        $lhsRaw = trim($m[1]);
        $op = $m[2];
        $rhsRaw = trim($m[3]);

        $lhs = $this->parsePolynomial($lhsRaw);
        $rhs = $this->parsePolynomial($rhsRaw);
        if ($lhs === null || $rhs === null) {
            return null;
        }

        $poly = [
            'a' => $lhs['a'] - $rhs['a'],
            'b' => $lhs['b'] - $rhs['b'],
            'c' => $lhs['c'] - $rhs['c'],
        ];

        return $this->solvePolynomialAgainstZero($poly, $op);
    }

    private function normalizeExpression(string $expr): string
    {
        $s = trim($expr);
        $s = str_replace(['\\left', '\\right', ' ', '−', '–', '²'], ['', '', '', '-', '-', '^2'], $s);
        $s = str_replace(['\\leq', '\\geq'], ['<=', '>='], $s);
        $s = str_replace(['≤', '≥'], ['<=', '>='], $s);
        $s = str_replace(',', '.', $s);

        return $s;
    }

    /**
     * @return array{a:float,b:float,c:float}|null
     */
    private function parsePolynomial(string $expr): ?array
    {
        $expr = trim($expr);
        if ($expr === '') {
            return ['a' => 0.0, 'b' => 0.0, 'c' => 0.0];
        }

        if ($expr[0] !== '-') {
            $expr = '+' . $expr;
        }
        $expr = str_replace('-', '+-', $expr);
        $parts = array_values(array_filter(explode('+', $expr), static fn ($p) => $p !== ''));

        $a = 0.0;
        $b = 0.0;
        $c = 0.0;

        foreach ($parts as $part) {
            if (str_contains($part, 'x^2')) {
                $coef = str_replace('x^2', '', $part);
                $a += $this->parseCoefficient($coef);
                continue;
            }

            if (str_contains($part, 'x')) {
                $coef = str_replace('x', '', $part);
                $b += $this->parseCoefficient($coef);
                continue;
            }

            if (!is_numeric($part)) {
                return null;
            }
            $c += (float) $part;
        }

        return ['a' => $a, 'b' => $b, 'c' => $c];
    }

    private function parseCoefficient(string $coef): float
    {
        if ($coef === '' || $coef === '+') {
            return 1.0;
        }

        if ($coef === '-') {
            return -1.0;
        }

        return (float) $coef;
    }

    /**
     * @param array{a:float,b:float,c:float} $poly
     * @return array{kind:string,intervals?:array<int,array{l:?float,r:?float,li:bool,ri:bool}>}|null
     */
    private function solvePolynomialAgainstZero(array $poly, string $op): ?array
    {
        $eps = 1e-9;
        $a = $poly['a'];
        $b = $poly['b'];
        $c = $poly['c'];

        if (abs($a) < $eps && abs($b) < $eps) {
            return $this->truthSet($this->compareValue($c, 0.0, $op, $eps));
        }

        if (abs($a) < $eps) {
            $x0 = -$c / $b;
            return match ($op) {
                '<' => $b > 0
                    ? $this->intervals([['l' => null, 'r' => $x0, 'li' => false, 'ri' => false]])
                    : $this->intervals([['l' => $x0, 'r' => null, 'li' => false, 'ri' => false]]),
                '<=' => $b > 0
                    ? $this->intervals([['l' => null, 'r' => $x0, 'li' => false, 'ri' => true]])
                    : $this->intervals([['l' => $x0, 'r' => null, 'li' => true, 'ri' => false]]),
                '>' => $b > 0
                    ? $this->intervals([['l' => $x0, 'r' => null, 'li' => false, 'ri' => false]])
                    : $this->intervals([['l' => null, 'r' => $x0, 'li' => false, 'ri' => false]]),
                '>=' => $b > 0
                    ? $this->intervals([['l' => $x0, 'r' => null, 'li' => true, 'ri' => false]])
                    : $this->intervals([['l' => null, 'r' => $x0, 'li' => false, 'ri' => true]]),
                default => null,
            };
        }

        $d = $b * $b - 4 * $a * $c;
        if ($d < -$eps) {
            return $this->truthSet($this->compareValue($a, 0.0, $op, $eps));
        }

        if (abs($d) <= $eps) {
            $root = -$b / (2 * $a);
            $outsidePositive = $a > 0;

            return match ($op) {
                '<' => $this->truthSet(!$outsidePositive),
                '>' => $this->truthSet($outsidePositive),
                '<=' => $outsidePositive
                    ? $this->intervals([['l' => $root, 'r' => $root, 'li' => true, 'ri' => true]])
                    : $this->truthSet(true),
                '>=' => $outsidePositive
                    ? $this->truthSet(true)
                    : $this->intervals([['l' => $root, 'r' => $root, 'li' => true, 'ri' => true]]),
                default => null,
            };
        }

        $sqrtD = sqrt(max(0.0, $d));
        $r1 = (-$b - $sqrtD) / (2 * $a);
        $r2 = (-$b + $sqrtD) / (2 * $a);
        if ($r1 > $r2) {
            [$r1, $r2] = [$r2, $r1];
        }

        $outsidePositive = $a > 0;
        $strict = in_array($op, ['<', '>'], true);
        $wantPositive = in_array($op, ['>', '>='], true);
        $wantOutside = ($wantPositive && $outsidePositive) || (!$wantPositive && !$outsidePositive);

        if ($wantOutside) {
            return $this->intervals([
                ['l' => null, 'r' => $r1, 'li' => false, 'ri' => !$strict],
                ['l' => $r2, 'r' => null, 'li' => !$strict, 'ri' => false],
            ]);
        }

        return $this->intervals([
            ['l' => $r1, 'r' => $r2, 'li' => !$strict, 'ri' => !$strict],
        ]);
    }

    private function compareValue(float $left, float $right, string $op, float $eps): bool
    {
        return match ($op) {
            '<' => $left < $right - $eps,
            '<=' => $left <= $right + $eps,
            '>' => $left > $right + $eps,
            '>=' => $left >= $right - $eps,
            default => false,
        };
    }

    /**
     * @return array{kind:string,intervals?:array<int,array{l:?float,r:?float,li:bool,ri:bool}>}
     */
    private function truthSet(bool $truth): array
    {
        return $truth ? ['kind' => 'all'] : ['kind' => 'none'];
    }

    /**
     * @param array<int,array{l:?float,r:?float,li:bool,ri:bool}> $intervals
     * @return array{kind:string,intervals:array<int,array{l:?float,r:?float,li:bool,ri:bool}>}
     */
    private function intervals(array $intervals): array
    {
        $filtered = [];
        foreach ($intervals as $interval) {
            if ($interval['l'] !== null && $interval['r'] !== null) {
                if ($interval['l'] > $interval['r']) {
                    continue;
                }
                if (abs($interval['l'] - $interval['r']) < 1e-9 && (!$interval['li'] || !$interval['ri'])) {
                    continue;
                }
            }
            $filtered[] = $interval;
        }

        if ($filtered === []) {
            return ['kind' => 'none'];
        }

        return ['kind' => 'intervals', 'intervals' => $filtered];
    }

    /**
     * @param array{kind:string,intervals?:array<int,array{l:?float,r:?float,li:bool,ri:bool}>} $solution
     */
    private function renderSolutionSvg(array $solution, array $config = []): ?string
    {
        $mode = (string) ($config['mode'] ?? 'solution');
        $isCompactOption = $mode === 'compact_option';
        $textOnlyNone = (bool) ($config['text_only_none'] ?? false);
        $runtimeSvgId = (string) ($config['runtime_svg_id'] ?? 'topic13-b1-z10-option');
        $fractionLabels = is_array($config['fraction_labels'] ?? null) ? $config['fraction_labels'] : [];
        $fractionLabelYOffset = (int) ($config['fraction_label_y_offset'] ?? 0);

        $rayConfig = $this->solutionToRayConfig($solution);
        if ($rayConfig !== null) {
            $class = $isCompactOption
                ? 'w-full h-auto number-line semantic-runtime-svg'
                : 'w-full max-w-[360px] h-auto mx-auto number-line semantic-runtime-svg';

            return $this->numberRayRenderer->render([
                ...$rayConfig,
                'class' => $class,
                'runtimeSvgId' => $runtimeSvgId,
                'fractionLabels' => $fractionLabels,
                'labelFontSize' => self::TOPIC13_LABEL_FONT_SIZE,
                'fractionLabelYOffset' => $fractionLabelYOffset,
            ]);
        }

        $width = $isCompactOption ? 300 : 360;
        $height = $isCompactOption ? 42 : 78;
        $lineY = $isCompactOption ? 16 : 34;
        $leftPad = $isCompactOption ? 14 : 26;
        $rightPad = $isCompactOption ? 286 : 334;
        $usable = $rightPad - $leftPad;

        $finite = [];
        foreach (($solution['intervals'] ?? []) as $interval) {
            foreach (['l', 'r'] as $key) {
                if ($interval[$key] !== null) {
                    $finite[] = (float) $interval[$key];
                }
            }
        }

        [$min, $max] = $this->plotRange($solution['kind'], $finite);
        if ($max - $min < 1e-6) {
            $min -= 1;
            $max += 1;
        }

        $toX = static function (float $value) use ($min, $max, $leftPad, $usable): float {
            return $leftPad + (($value - $min) / ($max - $min)) * $usable;
        };

        $uidSeed = json_encode([$solution, $config], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $uid = substr(md5((string) $uidSeed), 0, 10);

        if ($isCompactOption && $textOnlyNone && $solution['kind'] === 'none') {
            return implode("\n", [
                "<svg viewBox=\"0 0 {$width} {$height}\" class=\"w-full h-auto semantic-runtime-svg\" data-runtime-svg=\"{$runtimeSvgId}\" data-label-mode=\"boundary-only\">",
                "  <rect width=\"100%\" height=\"100%\" fill=\"" . self::COMPACT_BG . "\"/>",
                "  <text x=\"150\" y=\"22\" text-anchor=\"middle\" fill=\"" . self::COMPACT_LABEL . "\" font-size=\"12\">нет решений</text>",
                '</svg>',
            ]);
        }

        $svg = [];
        if ($isCompactOption) {
            $svg[] = "<svg viewBox=\"0 0 {$width} {$height}\" class=\"w-full h-auto number-line semantic-runtime-svg\" data-runtime-svg=\"{$runtimeSvgId}\" data-label-mode=\"boundary-only\">";
        } else {
            $svg[] = "<svg viewBox=\"0 0 {$width} {$height}\" class=\"w-full max-w-[360px] h-auto mx-auto number-line semantic-runtime-svg\" data-runtime-svg=\"topic13-b1-z10-13\">";
        }
        $svg[] = '  <defs>';
        $svg[] = "    <marker id=\"arrow-r-{$uid}\" markerWidth=\"8\" markerHeight=\"8\" refX=\"0\" refY=\"3\" orient=\"auto\">";
        $svg[] = '      <path d="M0,0 L0,6 L7,3 z" fill="#c8dce8"/>';
        $svg[] = '    </marker>';
        $svg[] = "    <marker id=\"arrow-l-{$uid}\" markerWidth=\"8\" markerHeight=\"8\" refX=\"7\" refY=\"3\" orient=\"auto\">";
        $svg[] = '      <path d="M7,0 L7,6 L0,3 z" fill="#c8dce8"/>';
        $svg[] = '    </marker>';
        if ($isCompactOption) {
            $svg[] = "    <pattern id=\"hatch-{$uid}\" patternUnits=\"userSpaceOnUse\" width=\"6\" height=\"6\" patternTransform=\"rotate(28)\">";
            $svg[] = '      <line x1="0" y1="0" x2="0" y2="6" stroke="' . self::COMPACT_HATCH . '" stroke-width="1.4"/>';
            $svg[] = '    </pattern>';
        }
        $svg[] = '  </defs>';
        $svg[] = $isCompactOption
            ? '  <rect width="100%" height="100%" fill="' . self::COMPACT_BG . '"/>'
            : '  <rect width="100%" height="100%" fill="#ffffff"/>';
        $axisStroke = $isCompactOption ? self::COMPACT_AXIS : '#334155';
        $svg[] = "  <line x1=\"{$leftPad}\" y1=\"{$lineY}\" x2=\"{$rightPad}\" y2=\"{$lineY}\" stroke=\"{$axisStroke}\" stroke-width=\"2\" marker-start=\"url(#arrow-l-{$uid})\" marker-end=\"url(#arrow-r-{$uid})\"/>";

        if (!$isCompactOption) {
            foreach ($this->ticks($min, $max) as $tick) {
                $x = round($toX($tick), 2);
                $label = $this->formatTick($tick);
                $svg[] = "  <line x1=\"{$x}\" y1=\"" . ($lineY - 7) . "\" x2=\"{$x}\" y2=\"" . ($lineY + 7) . "\" stroke=\"#64748b\" stroke-width=\"1.2\"/>";
                $svg[] = "  <text x=\"{$x}\" y=\"58\" text-anchor=\"middle\" fill=\"#334155\" font-size=\"11\" data-label-role=\"tick\">{$label}</text>";
            }
        }

        if ($solution['kind'] === 'all') {
            if ($isCompactOption) {
                $svg[] = "  <rect x=\"{$leftPad}\" y=\"" . ($lineY - 4) . "\" width=\"{$usable}\" height=\"8\" fill=\"url(#hatch-{$uid})\" opacity=\"0.95\"/>";
            } else {
                $svg[] = "  <line x1=\"{$leftPad}\" y1=\"{$lineY}\" x2=\"{$rightPad}\" y2=\"{$lineY}\" stroke=\"#0ea5e9\" stroke-width=\"5\" opacity=\"0.85\" stroke-linecap=\"round\"/>";
            }
        } elseif ($solution['kind'] === 'intervals') {
            foreach ($solution['intervals'] as $interval) {
                $x1 = $interval['l'] === null ? $leftPad : round($toX((float) $interval['l']), 2);
                $x2 = $interval['r'] === null ? $rightPad : round($toX((float) $interval['r']), 2);
                if ($isCompactOption) {
                    $rectX = min($x1, $x2);
                    $rectW = max(0.0, abs($x2 - $x1));
                    $svg[] = "  <rect x=\"{$rectX}\" y=\"" . ($lineY - 4) . "\" width=\"{$rectW}\" height=\"8\" fill=\"url(#hatch-{$uid})\" opacity=\"0.95\"/>";
                } else {
                    $svg[] = "  <line x1=\"{$x1}\" y1=\"{$lineY}\" x2=\"{$x2}\" y2=\"{$lineY}\" stroke=\"#0ea5e9\" stroke-width=\"5\" opacity=\"0.85\" stroke-linecap=\"round\"/>";
                }

                if ($interval['l'] !== null) {
                    $svg[] = $this->endpointMarker((float) $interval['l'], $interval['li'], $toX, $lineY, $isCompactOption);
                    $svg[] = $this->endpointLabel((float) $interval['l'], $toX, $isCompactOption);
                }
                if ($interval['r'] !== null && ($interval['l'] === null || abs((float) $interval['r'] - (float) ($interval['l'] ?? 0.0)) > 1e-9)) {
                    $svg[] = $this->endpointMarker((float) $interval['r'], $interval['ri'], $toX, $lineY, $isCompactOption);
                    $svg[] = $this->endpointLabel((float) $interval['r'], $toX, $isCompactOption);
                }
            }
        } else {
            $noX = $isCompactOption ? ($width / 2) : 180;
            $noY = $isCompactOption ? 18 : 28;
            $fill = $isCompactOption ? self::COMPACT_LABEL : '#64748b';
            $svg[] = "  <text x=\"{$noX}\" y=\"{$noY}\" text-anchor=\"middle\" fill=\"{$fill}\" font-size=\"12\">нет решений</text>";
        }

        $svg[] = '</svg>';

        return implode("\n", $svg);
    }

    /**
     * @param list<float> $finite
     * @return array{0:float,1:float}
     */
    private function plotRange(string $kind, array $finite): array
    {
        if ($kind === 'all' || $finite === []) {
            return [-6.0, 6.0];
        }

        $min = min($finite);
        $max = max($finite);

        $min = min($min, 0.0);
        $max = max($max, 0.0);

        $span = max(1.0, $max - $min);
        $pad = max(1.5, $span * 0.35);

        return [$min - $pad, $max + $pad];
    }

    /**
     * @return list<float>
     */
    private function ticks(float $min, float $max): array
    {
        $range = max(1e-6, $max - $min);
        $step = $this->niceStep($range / 6.0);
        $start = floor($min / $step) * $step;
        $ticks = [];

        for ($v = $start; $v <= $max + 1e-9; $v += $step) {
            $ticks[] = round($v, 6);
        }

        if ($ticks === []) {
            $ticks[] = 0.0;
        }

        return $ticks;
    }

    private function niceStep(float $raw): float
    {
        if ($raw <= 1) {
            return 1.0;
        }

        $pow = pow(10, floor(log10($raw)));
        $norm = $raw / $pow;

        if ($norm <= 1) {
            return 1 * $pow;
        }
        if ($norm <= 2) {
            return 2 * $pow;
        }
        if ($norm <= 5) {
            return 5 * $pow;
        }

        return 10 * $pow;
    }

    private function endpointMarker(float $value, bool $closed, callable $toX, int $lineY, bool $compactOption): string
    {
        $x = round($toX($value), 2);
        if ($compactOption) {
            if ($closed) {
                return "  <circle cx=\"{$x}\" cy=\"{$lineY}\" r=\"3.5\" fill=\"" . self::COMPACT_AXIS . "\" stroke=\"" . self::COMPACT_AXIS . "\" stroke-width=\"1\"/>";
            }

            return "  <circle cx=\"{$x}\" cy=\"{$lineY}\" r=\"3.5\" fill=\"" . self::COMPACT_BG . "\" stroke=\"" . self::COMPACT_AXIS . "\" stroke-width=\"1.2\"/>";
        }

        if ($closed) {
            return "  <circle cx=\"{$x}\" cy=\"{$lineY}\" r=\"5\" fill=\"#0284c7\" stroke=\"#ffffff\" stroke-width=\"1.5\"/>";
        }

        return "  <circle cx=\"{$x}\" cy=\"{$lineY}\" r=\"5\" fill=\"#ffffff\" stroke=\"#0284c7\" stroke-width=\"2\"/>";
    }

    private function endpointLabel(float $value, callable $toX, bool $compactOption): string
    {
        $x = round($toX($value), 2);
        $label = $this->formatTick($value);
        if ($compactOption) {
            return "  <text x=\"{$x}\" y=\"34\" text-anchor=\"middle\" fill=\"" . self::COMPACT_LABEL . "\" font-size=\"9\" data-label-role=\"boundary\" data-label-pos=\"below\">{$label}</text>";
        }

        return "  <text x=\"{$x}\" y=\"18\" text-anchor=\"middle\" fill=\"#0f172a\" font-size=\"11\" data-label-role=\"boundary\" data-label-pos=\"above\">{$label}</text>";
    }

    private function formatTick(float $value): string
    {
        if (abs($value - round($value)) < 1e-9) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    /**
     * @param array{kind:string,intervals?:array<int,array{l:?float,r:?float,li:bool,ri:bool}>} $solution
     * @return list<array{index:int,svg:string,text:string}>|null
     */
    private function topic13Z10GraphOptionsForSolution(array $solution): ?array
    {
        if (($solution['kind'] ?? null) !== 'intervals') {
            return null;
        }

        $bounds = [];
        foreach (($solution['intervals'] ?? []) as $interval) {
            foreach (['l', 'r'] as $key) {
                if ($interval[$key] !== null) {
                    $bounds[] = (float) $interval[$key];
                }
            }
        }

        $bounds = array_values(array_unique(array_map(static fn (float $v) => round($v, 8), $bounds)));
        sort($bounds);
        if (count($bounds) < 2) {
            return null;
        }

        $a = (float) $bounds[0];
        $b = (float) $bounds[1];

        $isInner = count($solution['intervals'] ?? []) === 1
            && ($solution['intervals'][0]['l'] ?? null) !== null
            && ($solution['intervals'][0]['r'] ?? null) !== null;

        $closed = false;
        foreach (($solution['intervals'] ?? []) as $interval) {
            $closed = $closed || (bool) ($interval['li'] ?? false) || (bool) ($interval['ri'] ?? false);
        }

        $correct = $solution;
        $singleLeft = $this->intervals([['l' => $a, 'r' => null, 'li' => $closed, 'ri' => false]]);
        $singleRight = $this->intervals([['l' => $b, 'r' => null, 'li' => $closed, 'ri' => false]]);
        $bounded = $this->intervals([['l' => $a, 'r' => $b, 'li' => $closed, 'ri' => $closed]]);
        $outer = $this->intervals([
            ['l' => null, 'r' => $a, 'li' => false, 'ri' => $closed],
            ['l' => $b, 'r' => null, 'li' => $closed, 'ri' => false],
        ]);

        $candidates = $isInner
            ? [$correct, $outer, $singleRight, $singleLeft]
            : [$correct, $singleLeft, $bounded, $singleRight];

        $options = [];
        foreach ($candidates as $index => $candidate) {
            $svg = $this->renderSolutionSvg($candidate, [
                'mode' => 'compact_option',
                'runtime_svg_id' => 'topic13-b1-z10-option-' . ($index + 1),
            ]);

            if (!is_string($svg) || $svg === '') {
                return null;
            }

            $options[] = [
                'index' => $index + 1,
                'svg' => $svg,
                'text' => $this->solutionToText($candidate),
            ];
        }

        return count($options) === 4 ? $options : null;
    }

    /**
     * @return list<array{index:int,svg:string,text:string}>|null
     */
    private function topic13Z13Task5GraphOptions(): ?array
    {
        $boundary = 6.0 / 7.0;
        $minusBoundary = -$boundary;

        // Keep option #1 as the correct candidate before deterministic rotation.
        $candidates = [
            $this->intervals([['l' => null, 'r' => $minusBoundary, 'li' => false, 'ri' => true]]),
            $this->intervals([['l' => $boundary, 'r' => null, 'li' => true, 'ri' => false]]),
            $this->intervals([['l' => $minusBoundary, 'r' => $boundary, 'li' => true, 'ri' => true]]),
            $this->intervals([['l' => $minusBoundary, 'r' => null, 'li' => true, 'ri' => false]]),
        ];

        $customTexts = [
            1 => '(-∞; -6/7]',
            2 => '[6/7; +∞)',
            3 => '[-6/7; 6/7]',
            4 => '[-6/7; +∞)',
        ];

        $fractionLabels = [
            '-6/7' => ['numerator' => 6, 'denominator' => 7, 'negative' => true],
            '6/7' => ['numerator' => 6, 'denominator' => 7, 'negative' => false],
        ];

        $options = [];
        foreach ($candidates as $index => $candidate) {
            $svg = $this->renderSolutionSvg($candidate, [
                'mode' => 'compact_option',
                'runtime_svg_id' => 'topic13-b1-z13-task5-option-' . ($index + 1),
                'fraction_labels' => $fractionLabels,
                'fraction_label_y_offset' => 3,
            ]);

            if (!is_string($svg) || $svg === '') {
                return null;
            }

            $options[] = [
                'index' => $index + 1,
                'svg' => $svg,
                'text' => $customTexts[$index + 1],
            ];
        }

        return count($options) === 4 ? $options : null;
    }

    /**
     * @return list<array{index:int,svg:string,text:string}>|null
     */
    private function topic13Z13Task7GraphOptions(): ?array
    {
        $boundary = 4.0 / 9.0;
        $minusBoundary = -$boundary;

        // Keep option #1 as the correct candidate before deterministic rotation.
        $candidates = [
            $this->intervals([['l' => $minusBoundary, 'r' => $boundary, 'li' => true, 'ri' => true]]),
            $this->intervals([
                ['l' => null, 'r' => $minusBoundary, 'li' => false, 'ri' => true],
                ['l' => $boundary, 'r' => null, 'li' => true, 'ri' => false],
            ]),
            $this->intervals([['l' => $boundary, 'r' => null, 'li' => true, 'ri' => false]]),
            $this->intervals([['l' => $minusBoundary, 'r' => null, 'li' => true, 'ri' => false]]),
        ];

        $customTexts = [
            1 => '[-4/9; 4/9]',
            2 => '(-∞; -4/9] ∪ [4/9; +∞)',
            3 => '[4/9; +∞)',
            4 => '[-4/9; +∞)',
        ];

        $fractionLabels = [
            '-4/9' => ['numerator' => 4, 'denominator' => 9, 'negative' => true],
            '4/9' => ['numerator' => 4, 'denominator' => 9, 'negative' => false],
        ];

        $options = [];
        foreach ($candidates as $index => $candidate) {
            $svg = $this->renderSolutionSvg($candidate, [
                'mode' => 'compact_option',
                'runtime_svg_id' => 'topic13-b1-z13-task7-option-' . ($index + 1),
                'fraction_labels' => $fractionLabels,
                'fraction_label_y_offset' => 3,
            ]);

            if (!is_string($svg) || $svg === '') {
                return null;
            }

            $options[] = [
                'index' => $index + 1,
                'svg' => $svg,
                'text' => $customTexts[$index + 1],
            ];
        }

        return count($options) === 4 ? $options : null;
    }

    /**
     * @param array{kind:string,intervals?:array<int,array{l:?float,r:?float,li:bool,ri:bool}>} $solution
     * @return array{type:string,a?:float,b?:float,axisMin:float,axisMax:float}|null
     */
    private function solutionToRayConfig(array $solution): ?array
    {
        if (($solution['kind'] ?? null) !== 'intervals') {
            return null;
        }

        $intervals = $solution['intervals'] ?? [];
        if (!is_array($intervals) || $intervals === []) {
            return null;
        }

        $finite = [];
        foreach ($intervals as $interval) {
            foreach (['l', 'r'] as $key) {
                if (($interval[$key] ?? null) !== null) {
                    $finite[] = (float) $interval[$key];
                }
            }
        }

        if ($finite === []) {
            return null;
        }

        $min = min(array_merge($finite, [0.0]));
        $max = max(array_merge($finite, [0.0]));
        $span = max(1.0, $max - $min);
        $pad = max(1.0, $span * 0.35);
        $axisMin = $min - $pad;
        $axisMax = $max + $pad;

        if (count($intervals) === 1) {
            $i = $intervals[0];
            if (($i['l'] ?? null) === null && ($i['r'] ?? null) !== null) {
                return [
                    'type' => ($i['ri'] ?? false) ? 'lte' : 'lt',
                    'b' => (float) $i['r'],
                    'axisMin' => $axisMin,
                    'axisMax' => $axisMax,
                ];
            }

            if (($i['l'] ?? null) !== null && ($i['r'] ?? null) === null) {
                return [
                    'type' => ($i['li'] ?? false) ? 'gte' : 'gt',
                    'a' => (float) $i['l'],
                    'axisMin' => $axisMin,
                    'axisMax' => $axisMax,
                ];
            }

            if (($i['l'] ?? null) !== null && ($i['r'] ?? null) !== null) {
                $li = (bool) ($i['li'] ?? false);
                $ri = (bool) ($i['ri'] ?? false);
                $type = match ([$li, $ri]) {
                    [true, true] => 'closed',
                    [false, false] => 'open',
                    [false, true] => 'lopen',
                    [true, false] => 'ropen',
                };

                return [
                    'type' => $type,
                    'a' => (float) $i['l'],
                    'b' => (float) $i['r'],
                    'axisMin' => $axisMin,
                    'axisMax' => $axisMax,
                ];
            }

            return null;
        }

        if (count($intervals) === 2) {
            $first = $intervals[0];
            $second = $intervals[1];
            if (($first['l'] ?? null) !== null || ($first['r'] ?? null) === null) {
                return null;
            }
            if (($second['l'] ?? null) === null || ($second['r'] ?? null) !== null) {
                return null;
            }

            $closed = (bool) ($first['ri'] ?? false) && (bool) ($second['li'] ?? false);

            return [
                'type' => $closed ? 'outerClosed' : 'outer',
                'a' => (float) $first['r'],
                'b' => (float) $second['l'],
                'axisMin' => $axisMin,
                'axisMax' => $axisMax,
            ];
        }

        return null;
    }

    /**
     * @return list<array{index:int,svg:string,text:string}>
     */
    private function topic13Z10GraphOptionsForImage(string $image): ?array
    {
        $imageMap = $this->topic13Z10ImageOptionMap();
        $items = $imageMap[$image] ?? null;
        if (!is_array($items)) {
            return null;
        }

        $options = [];
        foreach ($items as $index => $solution) {
            if (!is_array($solution)) {
                continue;
            }

            $svg = $this->renderSolutionSvg($solution, ['mode' => 'compact_option']);
            if (!is_string($svg) || $svg === '') {
                continue;
            }

            $options[] = [
                'index' => $index + 1,
                'svg' => $svg,
                'text' => $this->solutionToText($solution),
            ];
        }

        return count($options) === 4 ? $options : null;
    }

    /**
     * @return array<string,list<array{kind:string,intervals?:array<int,array{l:?float,r:?float,li:bool,ri:bool}>}>>
     */
    private function topic13Z10ImageOptionMap(): array
    {
        return [
            'img-026.png' => [
                $this->intervals([['l' => -1.0, 'r' => 9.0, 'li' => false, 'ri' => false]]),
                $this->intervals([
                    ['l' => null, 'r' => -1.0, 'li' => false, 'ri' => false],
                    ['l' => 9.0, 'r' => null, 'li' => false, 'ri' => false],
                ]),
                $this->intervals([['l' => 9.0, 'r' => null, 'li' => false, 'ri' => false]]),
                $this->intervals([['l' => -1.0, 'r' => null, 'li' => false, 'ri' => false]]),
            ],
            'img-027.png' => [
                $this->intervals([
                    ['l' => null, 'r' => 0.0, 'li' => false, 'ri' => true],
                    ['l' => 7.0, 'r' => null, 'li' => true, 'ri' => false],
                ]),
                $this->intervals([['l' => 0.0, 'r' => null, 'li' => true, 'ri' => false]]),
                $this->intervals([['l' => 0.0, 'r' => 7.0, 'li' => true, 'ri' => true]]),
                $this->intervals([['l' => 7.0, 'r' => null, 'li' => true, 'ri' => false]]),
            ],
            'img-028.png' => [
                $this->intervals([['l' => 0.0, 'r' => 6.0, 'li' => false, 'ri' => false]]),
                $this->intervals([
                    ['l' => null, 'r' => 0.0, 'li' => false, 'ri' => false],
                    ['l' => 6.0, 'r' => null, 'li' => false, 'ri' => false],
                ]),
                $this->intervals([['l' => 6.0, 'r' => null, 'li' => false, 'ri' => false]]),
                $this->intervals([['l' => 0.0, 'r' => null, 'li' => false, 'ri' => false]]),
            ],
            'img-029.png' => [
                $this->intervals([['l' => 8.0, 'r' => null, 'li' => false, 'ri' => false]]),
                $this->intervals([
                    ['l' => null, 'r' => 0.0, 'li' => false, 'ri' => false],
                    ['l' => 8.0, 'r' => null, 'li' => false, 'ri' => false],
                ]),
                $this->intervals([['l' => 0.0, 'r' => 8.0, 'li' => false, 'ri' => false]]),
                $this->intervals([['l' => 0.0, 'r' => null, 'li' => false, 'ri' => false]]),
            ],
            'img-030.png' => [
                $this->intervals([
                    ['l' => null, 'r' => 0.0, 'li' => false, 'ri' => true],
                    ['l' => 4.0, 'r' => null, 'li' => true, 'ri' => false],
                ]),
                $this->intervals([['l' => 0.0, 'r' => null, 'li' => true, 'ri' => false]]),
                $this->intervals([['l' => 0.0, 'r' => 4.0, 'li' => true, 'ri' => true]]),
                $this->intervals([['l' => 4.0, 'r' => null, 'li' => true, 'ri' => false]]),
            ],
            'img-031.png' => [
                $this->intervals([['l' => 0.0, 'r' => null, 'li' => true, 'ri' => false]]),
                $this->intervals([['l' => 0.0, 'r' => 7.0, 'li' => true, 'ri' => true]]),
                $this->intervals([
                    ['l' => null, 'r' => 0.0, 'li' => false, 'ri' => true],
                    ['l' => 7.0, 'r' => null, 'li' => true, 'ri' => false],
                ]),
                $this->intervals([['l' => 7.0, 'r' => null, 'li' => true, 'ri' => false]]),
            ],
            'img-032.png' => [
                $this->intervals([
                    ['l' => null, 'r' => 0.0, 'li' => false, 'ri' => true],
                    ['l' => 5.0, 'r' => null, 'li' => true, 'ri' => false],
                ]),
                $this->intervals([['l' => 0.0, 'r' => 5.0, 'li' => true, 'ri' => true]]),
                $this->intervals([['l' => 5.0, 'r' => null, 'li' => true, 'ri' => false]]),
                $this->intervals([['l' => 0.0, 'r' => null, 'li' => true, 'ri' => false]]),
            ],
            'img-033.png' => [
                $this->intervals([['l' => 1.0, 'r' => null, 'li' => true, 'ri' => false]]),
                $this->intervals([
                    ['l' => null, 'r' => 0.0, 'li' => false, 'ri' => true],
                    ['l' => 1.0, 'r' => null, 'li' => true, 'ri' => false],
                ]),
                $this->intervals([['l' => 0.0, 'r' => null, 'li' => true, 'ri' => false]]),
                $this->intervals([['l' => 0.0, 'r' => 1.0, 'li' => true, 'ri' => true]]),
            ],
            'img-034.png' => [
                $this->intervals([
                    ['l' => null, 'r' => 0.0, 'li' => false, 'ri' => false],
                    ['l' => 2.0, 'r' => null, 'li' => false, 'ri' => false],
                ]),
                $this->intervals([['l' => 2.0, 'r' => null, 'li' => false, 'ri' => false]]),
                $this->intervals([['l' => 0.0, 'r' => 2.0, 'li' => false, 'ri' => false]]),
                $this->intervals([['l' => 0.0, 'r' => null, 'li' => false, 'ri' => false]]),
            ],
        ];
    }

    /**
     * @param list<array{index:int,svg:string,text:string}> $options
     * @return array{0:list<array{index:int,svg:string,text:string}>,1:int}
     */
    private function reorderGraphOptionsAndAnswer(array $options, int $currentAnswer, int $taskId): array
    {
        if (count($options) !== 4) {
            return [$options, max(1, min(4, $currentAnswer))];
        }

        $currentAnswer = max(1, min(4, $currentAnswer));

        // Deterministic non-zero rotation so correct option is not constantly #1.
        $rotation = ($taskId % 3) + 1; // 1..3
        $rotated = [];
        for ($i = 0; $i < 4; $i++) {
            $rotated[] = $options[($i + $rotation) % 4];
        }

        $newAnswer = 1;
        $reindexed = [];
        foreach ($rotated as $i => $option) {
            $newIndex = $i + 1;
            if (($option['index'] ?? 0) === $currentAnswer) {
                $newAnswer = $newIndex;
            }
            $option['index'] = $newIndex;
            $reindexed[] = $option;
        }

        return [$reindexed, $newAnswer];
    }

    /**
     * @param array{kind:string,intervals?:array<int,array{l:?float,r:?float,li:bool,ri:bool}>} $solution
     */
    private function solutionToText(array $solution): string
    {
        if (($solution['kind'] ?? null) === 'none') {
            return 'нет решений';
        }

        if (($solution['kind'] ?? null) === 'all') {
            return '(-∞; +∞)';
        }

        $parts = [];
        foreach (($solution['intervals'] ?? []) as $interval) {
            $left = ($interval['li'] ?? false) ? '[' : '(';
            $right = ($interval['ri'] ?? false) ? ']' : ')';
            $l = ($interval['l'] ?? null) === null ? '-∞' : $this->formatTick((float) $interval['l']);
            $r = ($interval['r'] ?? null) === null ? '+∞' : $this->formatTick((float) $interval['r']);
            $parts[] = $left . $l . '; ' . $r . $right;
        }

        return implode(' ∪ ', $parts);
    }
}
