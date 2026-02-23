<?php

namespace App\Services;

class Topic13RuntimeSvgMigrationService
{
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
                if (!is_array($task) || empty($task['image']) || !is_string($task['image'])) {
                    continue;
                }

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

                $svg = $this->renderSolutionSvg($solution);
                if ($svg === null) {
                    continue;
                }

                $task['svg'] = $svg;
                $task['runtime_svg_migration'] = 'topic13_b1_z10_13_phase2';
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
    private function renderSolutionSvg(array $solution): ?string
    {
        $width = 360;
        $height = 78;
        $lineY = 34;
        $leftPad = 26;
        $rightPad = 334;
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

        $uid = substr(md5(json_encode($solution)), 0, 10);
        $svg = [];
        $svg[] = "<svg viewBox=\"0 0 {$width} {$height}\" class=\"w-full max-w-[360px] h-auto mx-auto number-line semantic-runtime-svg\" data-runtime-svg=\"topic13-b1-z10-13\">";
        $svg[] = '  <defs>';
        $svg[] = "    <marker id=\"arrow-r-{$uid}\" markerWidth=\"8\" markerHeight=\"8\" refX=\"0\" refY=\"3\" orient=\"auto\">";
        $svg[] = '      <path d="M0,0 L0,6 L7,3 z" fill="#c8dce8"/>';
        $svg[] = '    </marker>';
        $svg[] = "    <marker id=\"arrow-l-{$uid}\" markerWidth=\"8\" markerHeight=\"8\" refX=\"7\" refY=\"3\" orient=\"auto\">";
        $svg[] = '      <path d="M7,0 L7,6 L0,3 z" fill="#c8dce8"/>';
        $svg[] = '    </marker>';
        $svg[] = '  </defs>';
        $svg[] = '  <rect width="100%" height="100%" fill="#ffffff"/>';
        $svg[] = "  <line x1=\"{$leftPad}\" y1=\"{$lineY}\" x2=\"{$rightPad}\" y2=\"{$lineY}\" stroke=\"#334155\" stroke-width=\"2\" marker-start=\"url(#arrow-l-{$uid})\" marker-end=\"url(#arrow-r-{$uid})\"/>";

        foreach ($this->ticks($min, $max) as $tick) {
            $x = round($toX($tick), 2);
            $label = $this->formatTick($tick);
            $svg[] = "  <line x1=\"{$x}\" y1=\"" . ($lineY - 7) . "\" x2=\"{$x}\" y2=\"" . ($lineY + 7) . "\" stroke=\"#64748b\" stroke-width=\"1.2\"/>";
            $svg[] = "  <text x=\"{$x}\" y=\"58\" text-anchor=\"middle\" fill=\"#334155\" font-size=\"11\">{$label}</text>";
        }

        if ($solution['kind'] === 'all') {
            $svg[] = "  <line x1=\"{$leftPad}\" y1=\"{$lineY}\" x2=\"{$rightPad}\" y2=\"{$lineY}\" stroke=\"#0ea5e9\" stroke-width=\"5\" opacity=\"0.85\" stroke-linecap=\"round\"/>";
        } elseif ($solution['kind'] === 'intervals') {
            foreach ($solution['intervals'] as $interval) {
                $x1 = $interval['l'] === null ? $leftPad : round($toX((float) $interval['l']), 2);
                $x2 = $interval['r'] === null ? $rightPad : round($toX((float) $interval['r']), 2);
                $svg[] = "  <line x1=\"{$x1}\" y1=\"{$lineY}\" x2=\"{$x2}\" y2=\"{$lineY}\" stroke=\"#0ea5e9\" stroke-width=\"5\" opacity=\"0.85\" stroke-linecap=\"round\"/>";

                if ($interval['l'] !== null) {
                    $svg[] = $this->endpointCircle((float) $interval['l'], $interval['li'], $toX, $lineY);
                    $svg[] = $this->endpointLabel((float) $interval['l'], $toX);
                }
                if ($interval['r'] !== null && ($interval['l'] === null || abs((float) $interval['r'] - (float) ($interval['l'] ?? 0.0)) > 1e-9)) {
                    $svg[] = $this->endpointCircle((float) $interval['r'], $interval['ri'], $toX, $lineY);
                    $svg[] = $this->endpointLabel((float) $interval['r'], $toX);
                }
            }
        } else {
            $svg[] = '  <text x="180" y="28" text-anchor="middle" fill="#64748b" font-size="12">нет решений</text>';
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

    private function endpointCircle(float $value, bool $closed, callable $toX, int $lineY): string
    {
        $x = round($toX($value), 2);
        if ($closed) {
            return "  <circle cx=\"{$x}\" cy=\"{$lineY}\" r=\"5\" fill=\"#0284c7\" stroke=\"#ffffff\" stroke-width=\"1.5\"/>";
        }

        return "  <circle cx=\"{$x}\" cy=\"{$lineY}\" r=\"5\" fill=\"#ffffff\" stroke=\"#0284c7\" stroke-width=\"2\"/>";
    }

    private function endpointLabel(float $value, callable $toX): string
    {
        $x = round($toX($value), 2);
        $label = $this->formatTick($value);
        return "  <text x=\"{$x}\" y=\"18\" text-anchor=\"middle\" fill=\"#0f172a\" font-size=\"11\">{$label}</text>";
    }

    private function formatTick(float $value): string
    {
        if (abs($value - round($value)) < 1e-9) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
