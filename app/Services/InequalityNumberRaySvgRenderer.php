<?php

namespace App\Services;

class InequalityNumberRaySvgRenderer
{
    private const VIEWBOX_WIDTH = 420;
    private const VIEWBOX_HEIGHT = 60;
    private const AXIS_Y = 30;
    private const LABEL_Y = 50;
    private const AXIS_LEFT = 16;
    private const AXIS_RIGHT = 404;
    private const RAY_RIGHT = 396;
    private const TICK_Y1 = 16;
    private const TICK_Y2 = 30;
    private const HATCH_Y1 = 18;
    private const HATCH_Y2 = 30;
    private const HATCH_STEP = 10;
    private const HATCH_SHIFT = 6;
    private const FONT = 'JetBrains Mono, monospace';
    private const DEFAULT_LABEL_FONT_SIZE = 17;
    private const DEFAULT_FRACTION_FONT_SIZE = 14;

    /**
     * @param array{
     *   type:string,
     *   a?:float|int,
     *   b?:float|int,
     *   axisMin?:float|int,
     *   axisMax?:float|int,
     *   class?:string,
     *   runtimeSvgId?:string,
     *   fractionLabels?:array<string,array{numerator:int,denominator:int,negative?:bool}>,
     *   labelFontSize?:int,
     *   fractionLabelYOffset?:int
     * } $config
     */
    public function render(array $config): string
    {
        $type = (string) ($config['type'] ?? 'open');
        $axisMin = (float) ($config['axisMin'] ?? -10);
        $axisMax = (float) ($config['axisMax'] ?? 10);

        if ($axisMax <= $axisMin) {
            $axisMax = $axisMin + 1.0;
        }

        $a = array_key_exists('a', $config) ? (float) $config['a'] : null;
        $b = array_key_exists('b', $config) ? (float) $config['b'] : null;
        $class = trim((string) ($config['class'] ?? 'w-full h-auto'));
        $runtimeSvgId = trim((string) ($config['runtimeSvgId'] ?? ''));
        $fractionLabels = is_array($config['fractionLabels'] ?? null) ? $config['fractionLabels'] : [];
        $labelFontSize = max(10, (int) ($config['labelFontSize'] ?? self::DEFAULT_LABEL_FONT_SIZE));
        $fractionFontSize = max(10, (int) max(self::DEFAULT_FRACTION_FONT_SIZE, $labelFontSize - 3));
        $fractionLabelYOffset = (int) ($config['fractionLabelYOffset'] ?? 0);

        $ray = $this->buildRay($type, $a, $b, $axisMin, $axisMax);
        $uid = substr(md5(json_encode([$type, $a, $b, $axisMin, $axisMax], JSON_UNESCAPED_UNICODE)), 0, 10);

        $attrs = [
            'xmlns="http://www.w3.org/2000/svg"',
            'viewBox="0 0 420 60"',
            'class="' . htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"',
        ];

        if ($runtimeSvgId !== '') {
            $attrs[] = 'data-runtime-svg="' . htmlspecialchars($runtimeSvgId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        $svg = [];
        $svg[] = '<svg ' . implode(' ', $attrs) . '>';
        $svg[] = '  <defs>';
        $svg[] = "    <marker id=\"nr-arrow-{$uid}\" markerWidth=\"10\" markerHeight=\"10\" refX=\"9\" refY=\"3\" orient=\"auto\">";
        $svg[] = '      <path d="M0,0 L0,6 L9,3 z" fill="#4d9fdc"/>';
        $svg[] = '    </marker>';

        foreach ($ray['segments'] as $index => $segment) {
            $sx = $this->toX((float) $segment['start'], $axisMin, $axisMax);
            $ex = $this->toX((float) $segment['end'], $axisMin, $axisMax);
            $clipX = round(min($sx, $ex), 2);
            $clipW = round(abs($ex - $sx), 2);
            $svg[] = "    <clipPath id=\"nr-clip-{$uid}-{$index}\">";
            $svg[] = "      <rect x=\"{$clipX}\" y=\"" . self::HATCH_Y1 . "\" width=\"{$clipW}\" height=\"" . (self::HATCH_Y2 - self::HATCH_Y1) . "\"/>";
            $svg[] = '    </clipPath>';
        }
        $svg[] = '  </defs>';

        $svg[] = "  <line x1=\"" . self::AXIS_LEFT . "\" y1=\"" . self::AXIS_Y . "\" x2=\"" . self::RAY_RIGHT . "\" y2=\"" . self::AXIS_Y . "\" stroke=\"#4d9fdc\" stroke-width=\"1.8\" marker-end=\"url(#nr-arrow-{$uid})\"/>";

        foreach ($ray['segments'] as $index => $segment) {
            $sx = min($this->toX((float) $segment['start'], $axisMin, $axisMax), $this->toX((float) $segment['end'], $axisMin, $axisMax));
            $ex = max($this->toX((float) $segment['start'], $axisMin, $axisMax), $this->toX((float) $segment['end'], $axisMin, $axisMax));
            $svg[] = "  <g clip-path=\"url(#nr-clip-{$uid}-{$index})\">";

            $from = floor(($sx - 20) / self::HATCH_STEP) * self::HATCH_STEP;
            $to = ceil(($ex + 20) / self::HATCH_STEP) * self::HATCH_STEP;
            for ($x = $from; $x <= $to; $x += self::HATCH_STEP) {
                if ($segment['dir'] === 'left') {
                    $x1 = $x + self::HATCH_SHIFT;
                    $x2 = $x;
                } else {
                    $x1 = $x - self::HATCH_SHIFT;
                    $x2 = $x;
                }

                $svg[] = '    <line x1="' . round($x1, 2) . '" y1="18" x2="' . round($x2, 2) . '" y2="30" stroke="#e8a838" stroke-width="3.6" opacity="0.9"/>';
            }
            $svg[] = '  </g>';
        }

        foreach ($ray['boundaries'] as $boundary) {
            $value = (float) $boundary['value'];
            $x = round($this->toX($value, $axisMin, $axisMax), 2);

            $svg[] = "  <line x1=\"{$x}\" y1=\"" . self::TICK_Y1 . "\" x2=\"{$x}\" y2=\"" . self::TICK_Y2 . "\" stroke=\"#4d9fdc\" stroke-width=\"2.8\"/>";
            if ($boundary['closed']) {
                $svg[] = "  <circle cx=\"{$x}\" cy=\"" . self::AXIS_Y . "\" r=\"5\" fill=\"#4d9fdc\"/>";
            } else {
                $svg[] = "  <circle cx=\"{$x}\" cy=\"" . self::AXIS_Y . "\" r=\"5\" fill=\"#0d1b2a\" stroke=\"#4d9fdc\" stroke-width=\"2\"/>";
            }
            $svg[] = $this->renderBoundaryLabel(
                $x,
                $value,
                $fractionLabels,
                $labelFontSize,
                $fractionFontSize,
                $fractionLabelYOffset
            );
        }

        $svg[] = '</svg>';

        return implode("\n", $svg);
    }

    /**
     * @return array{
     *   segments:list<array{start:float,end:float,dir:string}>,
     *   boundaries:list<array{value:float,closed:bool}>
     * }
     */
    private function buildRay(string $type, ?float $a, ?float $b, float $axisMin, float $axisMax): array
    {
        $segments = [];
        $boundaries = [];

        $pushBoundary = static function (array &$items, float $value, bool $closed): void {
            foreach ($items as $index => $item) {
                if (abs((float) $item['value'] - $value) < 1e-9) {
                    $items[$index]['closed'] = $item['closed'] || $closed;
                    return;
                }
            }

            $items[] = ['value' => $value, 'closed' => $closed];
        };

        switch ($type) {
            case 'lt':
                $boundary = $b ?? 0.0;
                $segments[] = ['start' => $axisMin, 'end' => $boundary, 'dir' => 'left'];
                $pushBoundary($boundaries, $boundary, false);
                break;
            case 'lte':
                $boundary = $b ?? 0.0;
                $segments[] = ['start' => $axisMin, 'end' => $boundary, 'dir' => 'left'];
                $pushBoundary($boundaries, $boundary, true);
                break;
            case 'gt':
                $boundary = $a ?? 0.0;
                $segments[] = ['start' => $boundary, 'end' => $axisMax, 'dir' => 'right'];
                $pushBoundary($boundaries, $boundary, false);
                break;
            case 'gte':
                $boundary = $a ?? 0.0;
                $segments[] = ['start' => $boundary, 'end' => $axisMax, 'dir' => 'right'];
                $pushBoundary($boundaries, $boundary, true);
                break;
            case 'closed':
            case 'open':
            case 'lopen':
            case 'ropen':
                $left = min($a ?? 0.0, $b ?? 0.0);
                $right = max($a ?? 0.0, $b ?? 0.0);
                $segments[] = ['start' => $left, 'end' => $right, 'dir' => 'right'];
                $pushBoundary($boundaries, $left, in_array($type, ['closed', 'ropen'], true));
                $pushBoundary($boundaries, $right, in_array($type, ['closed', 'lopen'], true));
                break;
            case 'outerClosed':
            case 'outer':
            default:
                $left = min($a ?? 0.0, $b ?? 0.0);
                $right = max($a ?? 0.0, $b ?? 0.0);
                $segments[] = ['start' => $axisMin, 'end' => $left, 'dir' => 'left'];
                $segments[] = ['start' => $right, 'end' => $axisMax, 'dir' => 'right'];
                $closed = $type === 'outerClosed';
                $pushBoundary($boundaries, $left, $closed);
                $pushBoundary($boundaries, $right, $closed);
                break;
        }

        usort($boundaries, static fn (array $l, array $r) => $l['value'] <=> $r['value']);

        return [
            'segments' => $segments,
            'boundaries' => $boundaries,
        ];
    }

    private function toX(float $value, float $axisMin, float $axisMax): float
    {
        $ratio = ($value - $axisMin) / ($axisMax - $axisMin);
        $x = self::AXIS_LEFT + $ratio * (self::AXIS_RIGHT - self::AXIS_LEFT);
        if ($x < self::AXIS_LEFT) {
            return self::AXIS_LEFT;
        }
        if ($x > self::AXIS_RIGHT) {
            return self::AXIS_RIGHT;
        }

        return $x;
    }

    private function formatLabel(float $value): string
    {
        if (abs($value - round($value)) < 1e-9) {
            $label = (string) (int) round($value);
        } else {
            $label = rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
        }

        $label = str_replace('-', '−', $label);
        return str_replace('.', ',', $label);
    }

    /**
     * @param array<string,array{numerator:int,denominator:int,negative?:bool}> $fractionLabels
     */
    private function renderBoundaryLabel(
        float $x,
        float $value,
        array $fractionLabels,
        int $labelFontSize,
        int $fractionFontSize,
        int $fractionLabelYOffset
    ): string
    {
        $fraction = $this->resolveFractionLabel($value, $fractionLabels);
        if ($fraction === null) {
            return "  <text x=\"{$x}\" y=\"" . self::LABEL_Y . "\" text-anchor=\"middle\" fill=\"#d4e8f7\" font-family=\"" . self::FONT . "\" font-size=\"{$labelFontSize}\">" . $this->formatLabel($value) . '</text>';
        }

        $numerator = (int) $fraction['numerator'];
        $denominator = (int) $fraction['denominator'];
        $negative = (bool) ($fraction['negative'] ?? false);
        $minus = $negative ? '−' : '';
        $minusX = round($x - 12, 2);
        $minusY = 49 + $fractionLabelYOffset;
        $numeratorY = 42 + $fractionLabelYOffset;
        $fractionLineY = 44 + $fractionLabelYOffset;
        $denominatorY = 56 + $fractionLabelYOffset;

        return implode("\n", [
            "  <g data-label-format=\"stacked-fraction\" data-fraction=\"{$numerator}/{$denominator}\">",
            $negative
                ? "    <text x=\"{$minusX}\" y=\"{$minusY}\" text-anchor=\"middle\" fill=\"#d4e8f7\" font-family=\"" . self::FONT . "\" font-size=\"{$labelFontSize}\">{$minus}</text>"
                : '',
            "    <text x=\"{$x}\" y=\"{$numeratorY}\" text-anchor=\"middle\" fill=\"#d4e8f7\" font-family=\"" . self::FONT . "\" font-size=\"{$fractionFontSize}\">{$numerator}</text>",
            '    <line x1="' . round($x - 6, 2) . '" y1="' . $fractionLineY . '" x2="' . round($x + 6, 2) . '" y2="' . $fractionLineY . '" stroke="#d4e8f7" stroke-width="1.4"/>',
            "    <text x=\"{$x}\" y=\"{$denominatorY}\" text-anchor=\"middle\" fill=\"#d4e8f7\" font-family=\"" . self::FONT . "\" font-size=\"{$fractionFontSize}\">{$denominator}</text>",
            '  </g>',
        ]);
    }

    /**
     * @param array<string,array{numerator:int,denominator:int,negative?:bool}> $fractionLabels
     * @return array{numerator:int,denominator:int,negative?:bool}|null
     */
    private function resolveFractionLabel(float $value, array $fractionLabels): ?array
    {
        if ($fractionLabels === []) {
            return null;
        }

        foreach ($fractionLabels as $key => $spec) {
            if (!is_array($spec)) {
                continue;
            }

            if (str_contains($key, '/')) {
                [$rawNum, $rawDen] = explode('/', $key, 2);
                $num = (float) $rawNum;
                $den = (float) $rawDen;
                if (abs($den) < 1e-9) {
                    continue;
                }
                if (abs($value - ($num / $den)) < 1e-6) {
                    return $spec;
                }
                continue;
            }

            if (is_numeric($key) && abs($value - (float) $key) < 1e-6) {
                return $spec;
            }
        }

        return null;
    }
}
