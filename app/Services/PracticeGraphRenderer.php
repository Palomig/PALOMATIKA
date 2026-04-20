<?php

namespace App\Services;

class PracticeGraphRenderer
{
    private const VIEW_SIZE = 200;
    private const CENTER = 100;
    private const UNIT = 20;
    private const RANGE = 5;

    public function linear(float $k, float $b): string
    {
        $y1 = $k * -self::RANGE + $b;
        $y2 = $k * self::RANGE + $b;

        [$sx1, $sy1] = $this->toSvg(-self::RANGE, $y1);
        [$sx2, $sy2] = $this->toSvg(self::RANGE, $y2);

        $curve = sprintf(
            '<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f" stroke="#dc2626" stroke-width="2" stroke-linecap="round"/>',
            $sx1,
            $sy1,
            $sx2,
            $sy2
        );

        return $this->wrap($curve);
    }

    public function parabola(float $a, float $c): string
    {
        $path = '';
        $pen = false;

        for ($x = -self::RANGE; $x <= self::RANGE + 0.0001; $x += 0.1) {
            $y = $a * $x * $x + $c;

            if ($y < -self::RANGE || $y > self::RANGE) {
                $pen = false;
                continue;
            }

            [$sx, $sy] = $this->toSvg($x, $y);
            $cmd = $pen ? 'L' : 'M';
            $path .= sprintf('%s%.2f %.2f ', $cmd, $sx, $sy);
            $pen = true;
        }

        $curve = sprintf(
            '<path d="%s" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
            trim($path)
        );

        return $this->wrap($curve);
    }

    private function toSvg(float $x, float $y): array
    {
        return [
            self::CENTER + $x * self::UNIT,
            self::CENTER - $y * self::UNIT,
        ];
    }

    private function wrap(string $curve): string
    {
        $grid = $this->grid();
        $axes = $this->axes();
        $size = self::VIEW_SIZE;

        return <<<SVG
<svg viewBox="0 0 {$size} {$size}" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet" class="practice-graph-svg">{$grid}{$axes}{$curve}</svg>
SVG;
    }

    private function grid(): string
    {
        $lines = '';
        for ($i = -self::RANGE; $i <= self::RANGE; $i++) {
            if ($i === 0) {
                continue;
            }
            $offset = self::CENTER + $i * self::UNIT;
            $lines .= sprintf(
                '<line x1="%d" y1="0" x2="%d" y2="%d" stroke="#e5e7eb" stroke-width="0.5"/>',
                $offset,
                $offset,
                self::VIEW_SIZE
            );
            $lines .= sprintf(
                '<line x1="0" y1="%d" x2="%d" y2="%d" stroke="#e5e7eb" stroke-width="0.5"/>',
                $offset,
                self::VIEW_SIZE,
                $offset
            );
        }
        return $lines;
    }

    private function axes(): string
    {
        $size = self::VIEW_SIZE;
        $c = self::CENTER;

        return
            "<line x1=\"0\" y1=\"{$c}\" x2=\"{$size}\" y2=\"{$c}\" stroke=\"#666\" stroke-width=\"1\"/>" .
            "<line x1=\"{$c}\" y1=\"0\" x2=\"{$c}\" y2=\"{$size}\" stroke=\"#666\" stroke-width=\"1\"/>" .
            "<polygon points=\"198,{$c} 193,97 193,103\" fill=\"#666\"/>" .
            "<polygon points=\"{$c},2 97,7 103,7\" fill=\"#666\"/>" .
            "<text x=\"190\" y=\"" . ($c + 14) . "\" font-size=\"11\" fill=\"#666\" font-family=\"Arial, sans-serif\" font-weight=\"600\">x</text>" .
            "<text x=\"" . ($c - 14) . "\" y=\"12\" font-size=\"11\" fill=\"#666\" font-family=\"Arial, sans-serif\" font-weight=\"600\">y</text>" .
            "<text x=\"" . ($c + 3) . "\" y=\"" . ($c + 12) . "\" font-size=\"10\" fill=\"#666\" font-family=\"Arial, sans-serif\">0</text>";
    }
}
