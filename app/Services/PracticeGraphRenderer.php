<?php

namespace App\Services;

class PracticeGraphRenderer
{
    private const VIEW_SIZE = 250;
    private const CENTER = 125;
    private const UNIT = 25;
    private const RANGE = 5;

    private const CARD_BG = '#0d1b30';
    private const CARD_BORDER = '#284b6a';
    private const GRID = '#1a3a5c';
    private const AXIS = '#3a5a7c';
    private const CURVE = '#5a9fcf';
    private const LABEL = '#c8dce8';

    public function linear(float $k, float $b): string
    {
        $y1 = $k * -self::RANGE + $b;
        $y2 = $k * self::RANGE + $b;

        [$sx1, $sy1] = $this->toSvg(-self::RANGE, $y1);
        [$sx2, $sy2] = $this->toSvg(self::RANGE, $y2);

        $curve = sprintf(
            '<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f" stroke="%s" stroke-width="2.8" stroke-linecap="round"/>',
            $sx1,
            $sy1,
            $sx2,
            $sy2,
            self::CURVE
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
            '<path d="%s" fill="none" stroke="%s" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>',
            trim($path),
            self::CURVE
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
        $size = self::VIEW_SIZE;
        $card = sprintf(
            '<rect x="0.5" y="0.5" width="%d" height="%d" rx="10" fill="%s" stroke="%s" stroke-width="1.2"/>',
            $size - 1,
            $size - 1,
            self::CARD_BG,
            self::CARD_BORDER
        );

        $grid = $this->grid();
        $axes = $this->axes();

        return <<<SVG
<svg viewBox="0 0 {$size} {$size}" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet" class="practice-graph-svg">{$card}{$grid}{$axes}{$curve}</svg>
SVG;
    }

    private function grid(): string
    {
        $lines = '';
        $size = self::VIEW_SIZE;
        for ($i = -self::RANGE; $i <= self::RANGE; $i++) {
            if ($i === 0) {
                continue;
            }
            $offset = self::CENTER + $i * self::UNIT;
            $lines .= sprintf(
                '<line x1="%d" y1="0" x2="%d" y2="%d" stroke="%s" stroke-width="0.6"/>',
                $offset,
                $offset,
                $size,
                self::GRID
            );
            $lines .= sprintf(
                '<line x1="0" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="0.6"/>',
                $offset,
                $size,
                $offset,
                self::GRID
            );
        }
        return $lines;
    }

    private function axes(): string
    {
        $size = self::VIEW_SIZE;
        $c = self::CENTER;
        $u = self::UNIT;

        $mainAxes = sprintf(
            '<line x1="0" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="1.6"/>' .
            '<line x1="%d" y1="0" x2="%d" y2="%d" stroke="%s" stroke-width="1.6"/>',
            $c,
            $size,
            $c,
            self::AXIS,
            $c,
            $c,
            $size,
            self::AXIS
        );

        $arrows = sprintf(
            '<polygon points="%d,%d %d,%d %d,%d" fill="%s"/>' .
            '<polygon points="%d,%d %d,%d %d,%d" fill="%s"/>',
            $size - 2,
            $c,
            $size - 8,
            $c - 3,
            $size - 8,
            $c + 3,
            self::AXIS,
            $c,
            2,
            $c - 3,
            8,
            $c + 3,
            8,
            self::AXIS
        );

        $ticks = '';
        for ($i = -self::RANGE; $i <= self::RANGE; $i++) {
            if ($i === 0) {
                continue;
            }
            $px = $c + $i * $u;
            $ticks .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="1"/>',
                $px,
                $c - 3,
                $px,
                $c + 3,
                self::AXIS
            );
            $py = $c - $i * $u;
            $ticks .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="1"/>',
                $c - 3,
                $py,
                $c + 3,
                $py,
                self::AXIS
            );
        }

        $labels = sprintf(
            '<text x="%d" y="%d" font-size="12" fill="%s" font-family="Arial, sans-serif" font-style="italic">x</text>' .
            '<text x="%d" y="%d" font-size="12" fill="%s" font-family="Arial, sans-serif" font-style="italic">y</text>' .
            '<text x="%d" y="%d" font-size="11" fill="%s" font-family="Arial, sans-serif">0</text>' .
            '<text x="%d" y="%d" font-size="11" fill="%s" font-family="Arial, sans-serif">1</text>' .
            '<text x="%d" y="%d" font-size="11" fill="%s" font-family="Arial, sans-serif">1</text>',
            $size - 14,
            $c + 16,
            self::LABEL,
            $c - 14,
            14,
            self::LABEL,
            $c - 12,
            $c + 14,
            self::LABEL,
            $c + $u - 4,
            $c + 14,
            self::LABEL,
            $c - 12,
            $c - $u + 4,
            self::LABEL
        );

        return $mainAxes . $arrows . $ticks . $labels;
    }
}
