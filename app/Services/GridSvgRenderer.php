<?php

namespace App\Services;

/**
 * GridSvgRenderer - Сервис для генерации SVG фигур на клетчатой бумаге (тема 18)
 *
 * Текущий контракт:
 * - Вход: номер блока, номер задания, индекс задачи в массиве tasks
 * - Выход: готовый SVG или пустая строка, если комбинация не поддержана
 *
 * Поддерживаются детерминированные SVG-варианты для блока 1 (задания 1..12),
 * синхронизированные с логикой из test/topic18.blade.php.
 */
class GridSvgRenderer
{
    private const TYPES = ['grid_image', 'grid_image_with_question'];

    private const GRID_SIZE = 18;
    private const PADDING = 0;

    private const COLORS = [
        'bg' => '#0a1628',
        'grid' => '#1e4a6e',
        'line' => '#c8dce8',
        'aux' => '#d4a855',
        'point' => '#7eb8da',
        'text' => '#c8dce8',
    ];

    public function supports(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    public function render(int $blockNumber, int $zadanieNumber, int $taskIndex, array $task = []): string
    {
        // На текущем этапе поддерживаем детерминированные SVG для блока 1, задания 1..12
        if ($blockNumber !== 1 || $zadanieNumber < 1 || $zadanieNumber > 12) {
            return '';
        }

        return match ($zadanieNumber) {
            1 => $this->renderRightTriangle($taskIndex),
            2 => $this->renderRhombus($taskIndex),
            3 => $this->renderTriangleWithM($taskIndex),
            4, 5, 6, 7 => $this->renderPolygonByZadanie($zadanieNumber, $taskIndex),
            8 => $this->renderTwoPoints($taskIndex),
            9 => $this->renderTriangleWithMidline($taskIndex),
            10 => $this->renderFigureWithAB($taskIndex),
            11 => $this->renderTrapezoidWithMidline($taskIndex),
            12 => $this->renderTwoCircles($taskIndex),
            default => '',
        };
    }

    private function renderRightTriangle(int $taskIndex): string
    {
        $variants = [
            ['A' => [0, 0], 'B' => [0, 6], 'C' => [8, 6], 'cols' => 9, 'rows' => 7],
            ['A' => [0, 0], 'B' => [0, 4], 'C' => [5, 4], 'cols' => 6, 'rows' => 5],
            ['A' => [0, 0], 'B' => [0, 3], 'C' => [7, 3], 'cols' => 8, 'rows' => 4],
            ['A' => [0, 0], 'B' => [0, 7], 'C' => [5, 7], 'cols' => 6, 'rows' => 8],
            ['A' => [0, 0], 'B' => [0, 5], 'C' => [4, 5], 'cols' => 5, 'rows' => 6],
            ['A' => [0, 0], 'B' => [0, 2], 'C' => [6, 2], 'cols' => 7, 'rows' => 3],
        ];

        $v = $variants[$taskIndex % count($variants)];
        $A = $this->toSvg($v['A'][0], $v['A'][1]);
        $B = $this->toSvg($v['B'][0], $v['B'][1]);
        $C = $this->toSvg($v['C'][0], $v['C'][1]);
        [$width, $height] = $this->canvasSize($v['cols'], $v['rows']);

        $size = 6;
        $rightAngle = sprintf('M %s %s L %s %s L %s %s', $B['x'] + $size, $B['y'], $B['x'] + $size, $B['y'] - $size, $B['x'], $B['y'] - $size);

        return $this->wrapSvg($width, $height,
            $this->grid($width, $height)
            . $this->polygon([$A, $B, $C])
            . $this->path($rightAngle)
        );
    }

    private function renderRhombus(int $taskIndex): string
    {
        $variants = [
            ['A' => [0, 3], 'B' => [3, 0], 'C' => [6, 3], 'D' => [3, 6], 'cols' => 7, 'rows' => 7],
            ['A' => [0, 2], 'B' => [4, 0], 'C' => [8, 2], 'D' => [4, 4], 'cols' => 9, 'rows' => 5],
            ['A' => [1, 3], 'B' => [4, 0], 'C' => [7, 3], 'D' => [4, 6], 'cols' => 9, 'rows' => 7],
            ['A' => [0, 2], 'B' => [3, 0], 'C' => [6, 2], 'D' => [3, 4], 'cols' => 7, 'rows' => 5],
            ['A' => [0, 3], 'B' => [2, 0], 'C' => [4, 3], 'D' => [2, 6], 'cols' => 5, 'rows' => 7],
            ['A' => [1, 2], 'B' => [5, 0], 'C' => [9, 2], 'D' => [5, 4], 'cols' => 11, 'rows' => 5],
        ];

        $v = $variants[$taskIndex % count($variants)];
        $A = $this->toSvg($v['A'][0], $v['A'][1]);
        $B = $this->toSvg($v['B'][0], $v['B'][1]);
        $C = $this->toSvg($v['C'][0], $v['C'][1]);
        $D = $this->toSvg($v['D'][0], $v['D'][1]);
        [$width, $height] = $this->canvasSize($v['cols'], $v['rows']);

        return $this->wrapSvg($width, $height,
            $this->grid($width, $height)
            . $this->polygon([$A, $B, $C, $D])
            . $this->line($A, $C, self::COLORS['line'], 1.5, '4,3')
            . $this->line($B, $D, self::COLORS['line'], 1.5, '4,3')
        );
    }

    private function renderTriangleWithM(int $taskIndex): string
    {
        $variants = [
            ['A' => [0, 6], 'B' => [4, 0], 'C' => [8, 6], 'M' => [2, 3], 'cols' => 9, 'rows' => 7],
            ['A' => [1, 4], 'B' => [4, 0], 'C' => [8, 4], 'M' => [6, 2], 'cols' => 9, 'rows' => 5],
            ['A' => [0, 6], 'B' => [2, 2], 'C' => [6, 6], 'M' => [1, 4], 'cols' => 7, 'rows' => 7],
            ['A' => [1, 6], 'B' => [5, 0], 'C' => [7, 6], 'M' => [3, 3], 'cols' => 8, 'rows' => 7],
            ['A' => [0, 4], 'B' => [4, 0], 'C' => [6, 4], 'M' => [5, 2], 'cols' => 7, 'rows' => 5],
            ['A' => [1, 6], 'B' => [4, 0], 'C' => [9, 6], 'M' => [2, 4], 'cols' => 10, 'rows' => 7],
        ];

        $v = $variants[$taskIndex % count($variants)];
        $A = $this->toSvg($v['A'][0], $v['A'][1]);
        $B = $this->toSvg($v['B'][0], $v['B'][1]);
        $C = $this->toSvg($v['C'][0], $v['C'][1]);
        $M = $this->toSvg($v['M'][0], $v['M'][1]);
        [$width, $height] = $this->canvasSize($v['cols'], $v['rows']);

        return $this->wrapSvg($width, $height,
            $this->grid($width, $height)
            . $this->polygon([$A, $B, $C])
            . $this->circle($M['x'], $M['y'], 4, self::COLORS['aux'])
            . $this->text('A', $A['x'] - 12, $A['y'] + 4)
            . $this->text('B', $B['x'] + 4, $B['y'] - 6)
            . $this->text('C', $C['x'] + 4, $C['y'] + 14)
            . $this->text('M', $M['x'] + 4, $M['y'] - 6, self::COLORS['aux'])
        );
    }

    private function renderPolygonByZadanie(int $zadanieNumber, int $taskIndex): string
    {
        $all = [
            4 => [
                [[1, 5], [1, 1], [6, 5]], [[0, 4], [3, 0], [6, 4]], [[1, 4], [1, 1], [5, 1], [5, 4]],
                [[0, 3], [2, 0], [5, 3]], [[1, 5], [3, 1], [7, 5]], [[0, 4], [0, 1], [4, 1], [4, 4]],
                [[1, 4], [2, 1], [6, 4]], [[0, 5], [2, 0], [6, 5]], [[1, 3], [1, 0], [5, 0], [5, 3]],
            ],
            5 => [
                [[1, 4], [1, 1], [4, 1], [6, 3], [4, 4]], [[0, 3], [2, 0], [5, 0], [5, 3]], [[1, 5], [1, 2], [3, 0], [6, 2], [6, 5]],
                [[0, 4], [2, 1], [5, 1], [7, 4]], [[1, 4], [1, 1], [3, 1], [5, 3], [3, 4]], [[0, 5], [0, 2], [3, 0], [6, 2], [6, 5]],
                [[1, 3], [3, 0], [6, 3], [4, 5]], [[0, 4], [1, 1], [4, 1], [5, 4]], [[1, 5], [2, 1], [5, 1], [6, 5]],
            ],
            6 => [
                [[2, 5], [0, 3], [2, 0], [5, 0], [7, 3], [5, 5]], [[1, 4], [0, 2], [2, 0], [5, 0], [6, 2], [4, 4]], [[2, 4], [0, 2], [2, 0], [4, 0], [6, 2], [4, 4]],
                [[1, 5], [0, 3], [1, 1], [4, 1], [5, 3], [4, 5]], [[2, 4], [1, 2], [2, 0], [5, 0], [6, 2], [5, 4]], [[1, 5], [0, 2], [2, 0], [5, 0], [7, 2], [6, 5]],
                [[2, 5], [0, 2], [2, 0], [6, 0], [8, 2], [6, 5]], [[1, 4], [0, 2], [1, 0], [5, 0], [6, 2], [5, 4]], [[2, 5], [1, 3], [2, 1], [5, 1], [6, 3], [5, 5]],
            ],
            7 => [
                [[0, 5], [0, 2], [2, 0], [5, 0], [7, 3], [5, 5]], [[1, 4], [1, 1], [3, 0], [6, 1], [6, 4]], [[0, 5], [0, 1], [3, 1], [5, 3], [5, 5]],
                [[1, 5], [0, 2], [3, 0], [6, 2], [5, 5]], [[0, 4], [2, 1], [5, 1], [7, 4], [4, 4]], [[1, 5], [1, 2], [4, 0], [7, 2], [7, 5]],
                [[0, 4], [0, 1], [2, 0], [5, 1], [5, 4]], [[1, 5], [0, 3], [2, 0], [6, 0], [7, 3], [5, 5]], [[2, 4], [0, 2], [2, 0], [5, 2], [4, 4]],
            ],
        ];

        $variants = $all[$zadanieNumber] ?? $all[4];
        $coords = $variants[$taskIndex % count($variants)];

        $maxX = 0;
        $maxY = 0;
        $points = [];
        foreach ($coords as [$gx, $gy]) {
            $maxX = max($maxX, $gx);
            $maxY = max($maxY, $gy);
            $points[] = $this->toSvg($gx, $gy);
        }

        $cols = $maxX + 2;
        $rows = $maxY + 1;
        [$width, $height] = $this->canvasSize($cols, $rows);

        return $this->wrapSvg($width, $height,
            $this->grid($width, $height)
            . $this->polygon($points)
        );
    }

    private function renderTwoPoints(int $taskIndex): string
    {
        $variants = [
            ['A' => [1, 4], 'B' => [4, 1], 'cols' => 6, 'rows' => 5],
            ['A' => [0, 3], 'B' => [5, 1], 'cols' => 7, 'rows' => 4],
            ['A' => [1, 5], 'B' => [6, 2], 'cols' => 8, 'rows' => 6],
            ['A' => [2, 4], 'B' => [5, 0], 'cols' => 7, 'rows' => 5],
            ['A' => [0, 4], 'B' => [3, 0], 'cols' => 5, 'rows' => 5],
            ['A' => [1, 5], 'B' => [5, 1], 'cols' => 7, 'rows' => 6],
        ];

        $v = $variants[$taskIndex % count($variants)];
        $A = $this->toSvg($v['A'][0], $v['A'][1]);
        $B = $this->toSvg($v['B'][0], $v['B'][1]);
        [$width, $height] = $this->canvasSize($v['cols'], $v['rows']);

        return $this->wrapSvg($width, $height,
            $this->grid($width, $height)
            . $this->line($A, $B)
            . $this->circle($A['x'], $A['y'], 4, self::COLORS['point'])
            . $this->circle($B['x'], $B['y'], 4, self::COLORS['point'])
        );
    }

    private function renderTriangleWithMidline(int $taskIndex): string
    {
        $variants = [
            ['A' => [0, 5], 'B' => [3, 0], 'C' => [7, 5], 'cols' => 8, 'rows' => 6],
            ['A' => [1, 4], 'B' => [4, 0], 'C' => [8, 4], 'cols' => 9, 'rows' => 5],
            ['A' => [0, 5], 'B' => [2, 1], 'C' => [6, 5], 'cols' => 7, 'rows' => 6],
            ['A' => [1, 5], 'B' => [5, 0], 'C' => [8, 5], 'cols' => 9, 'rows' => 6],
            ['A' => [0, 4], 'B' => [4, 0], 'C' => [6, 4], 'cols' => 7, 'rows' => 5],
            ['A' => [1, 5], 'B' => [4, 0], 'C' => [9, 5], 'cols' => 10, 'rows' => 6],
        ];

        $v = $variants[$taskIndex % count($variants)];
        $A = $this->toSvg($v['A'][0], $v['A'][1]);
        $B = $this->toSvg($v['B'][0], $v['B'][1]);
        $C = $this->toSvg($v['C'][0], $v['C'][1]);
        $M1 = ['x' => ($A['x'] + $B['x']) / 2, 'y' => ($A['y'] + $B['y']) / 2];
        $M2 = ['x' => ($B['x'] + $C['x']) / 2, 'y' => ($B['y'] + $C['y']) / 2];
        [$width, $height] = $this->canvasSize($v['cols'], $v['rows']);

        return $this->wrapSvg($width, $height,
            $this->grid($width, $height)
            . $this->polygon([$A, $B, $C])
            . $this->line($M1, $M2, self::COLORS['aux'], 2, '5,3')
            . $this->text('A', $A['x'] - 12, $A['y'] + 14)
            . $this->text('B', $B['x'] - 2, $B['y'] - 8)
            . $this->text('C', $C['x'] + 4, $C['y'] + 14)
        );
    }

    private function renderFigureWithAB(int $taskIndex): string
    {
        $variants = [
            ['shape' => [[0, 4], [2, 0], [6, 0], [8, 4]], 'A' => [1, 2], 'B' => [7, 2], 'cols' => 9, 'rows' => 5],
            ['shape' => [[1, 5], [1, 1], [5, 1], [7, 5]], 'A' => [1, 3], 'B' => [6, 3], 'cols' => 8, 'rows' => 6],
            ['shape' => [[0, 4], [3, 0], [6, 4]], 'A' => [1, 2], 'B' => [5, 2], 'cols' => 7, 'rows' => 5],
            ['shape' => [[1, 5], [0, 2], [4, 0], [7, 2], [6, 5]], 'A' => [2, 1], 'B' => [5, 1], 'cols' => 8, 'rows' => 6],
            ['shape' => [[0, 4], [2, 1], [5, 1], [7, 4]], 'A' => [1, 2], 'B' => [6, 2], 'cols' => 8, 'rows' => 5],
            ['shape' => [[1, 5], [1, 1], [6, 1], [6, 5]], 'A' => [1, 3], 'B' => [6, 3], 'cols' => 7, 'rows' => 6],
            ['shape' => [[0, 4], [3, 0], [7, 4]], 'A' => [2, 2], 'B' => [5, 2], 'cols' => 8, 'rows' => 5],
            ['shape' => [[1, 5], [2, 1], [6, 1], [7, 5]], 'A' => [2, 3], 'B' => [6, 3], 'cols' => 8, 'rows' => 6],
            ['shape' => [[0, 5], [0, 1], [5, 1], [5, 5]], 'A' => [0, 3], 'B' => [5, 3], 'cols' => 6, 'rows' => 6],
        ];

        $v = $variants[$taskIndex % count($variants)];
        $shape = array_map(fn ($p) => $this->toSvg($p[0], $p[1]), $v['shape']);
        $A = $this->toSvg($v['A'][0], $v['A'][1]);
        $B = $this->toSvg($v['B'][0], $v['B'][1]);
        [$width, $height] = $this->canvasSize($v['cols'], $v['rows']);

        return $this->wrapSvg($width, $height,
            $this->grid($width, $height)
            . $this->polygon($shape)
            . $this->line($A, $B, self::COLORS['aux'], 2.5)
            . $this->text('A', $A['x'] - 14, $A['y'] + 4)
            . $this->text('B', $B['x'] + 6, $B['y'] + 4)
        );
    }

    private function renderTrapezoidWithMidline(int $taskIndex): string
    {
        $variants = [
            ['A' => [0, 5], 'B' => [2, 1], 'C' => [6, 1], 'D' => [8, 5], 'cols' => 9, 'rows' => 6],
            ['A' => [1, 4], 'B' => [3, 0], 'C' => [5, 0], 'D' => [7, 4], 'cols' => 8, 'rows' => 5],
            ['A' => [0, 5], 'B' => [1, 2], 'C' => [5, 2], 'D' => [7, 5], 'cols' => 8, 'rows' => 6],
            ['A' => [1, 4], 'B' => [2, 1], 'C' => [6, 1], 'D' => [8, 4], 'cols' => 9, 'rows' => 5],
            ['A' => [0, 5], 'B' => [2, 0], 'C' => [4, 0], 'D' => [6, 5], 'cols' => 7, 'rows' => 6],
            ['A' => [1, 5], 'B' => [3, 1], 'C' => [7, 1], 'D' => [9, 5], 'cols' => 10, 'rows' => 6],
            ['A' => [0, 4], 'B' => [1, 1], 'C' => [5, 1], 'D' => [6, 4], 'cols' => 7, 'rows' => 5],
            ['A' => [1, 5], 'B' => [2, 2], 'C' => [6, 2], 'D' => [8, 5], 'cols' => 9, 'rows' => 6],
            ['A' => [0, 4], 'B' => [2, 0], 'C' => [6, 0], 'D' => [8, 4], 'cols' => 9, 'rows' => 5],
        ];

        $v = $variants[$taskIndex % count($variants)];
        $A = $this->toSvg($v['A'][0], $v['A'][1]);
        $B = $this->toSvg($v['B'][0], $v['B'][1]);
        $C = $this->toSvg($v['C'][0], $v['C'][1]);
        $D = $this->toSvg($v['D'][0], $v['D'][1]);
        $M1 = ['x' => ($A['x'] + $B['x']) / 2, 'y' => ($A['y'] + $B['y']) / 2];
        $M2 = ['x' => ($C['x'] + $D['x']) / 2, 'y' => ($C['y'] + $D['y']) / 2];
        [$width, $height] = $this->canvasSize($v['cols'], $v['rows']);

        return $this->wrapSvg($width, $height,
            $this->grid($width, $height)
            . $this->polygon([$A, $B, $C, $D])
            . $this->line($M1, $M2, self::COLORS['aux'], 2, '5,3')
        );
    }

    private function renderTwoCircles(int $taskIndex): string
    {
        $variants = [
            ['c1' => [2, 3], 'r1' => 2, 'c2' => [7, 3], 'r2' => 1, 'cols' => 9, 'rows' => 6],
            ['c1' => [2, 3], 'r1' => 2, 'c2' => [7, 3], 'r2' => 3, 'cols' => 11, 'rows' => 7],
            ['c1' => [3, 3], 'r1' => 3, 'c2' => [3, 3], 'r2' => 1, 'cols' => 7, 'rows' => 7],
            ['c1' => [2, 2], 'r1' => 1, 'c2' => [6, 2], 'r2' => 2, 'cols' => 9, 'rows' => 5],
            ['c1' => [3, 3], 'r1' => 2, 'c2' => [8, 3], 'r2' => 3, 'cols' => 12, 'rows' => 7],
            ['c1' => [3, 3], 'r1' => 3, 'c2' => [8, 3], 'r2' => 2, 'cols' => 11, 'rows' => 7],
            ['c1' => [2, 2], 'r1' => 2, 'c2' => [6, 2], 'r2' => 1, 'cols' => 8, 'rows' => 5],
            ['c1' => [3, 3], 'r1' => 1, 'c2' => [3, 3], 'r2' => 2, 'cols' => 6, 'rows' => 6],
            ['c1' => [2, 3], 'r1' => 2, 'c2' => [8, 3], 'r2' => 3, 'cols' => 12, 'rows' => 7],
        ];

        $v = $variants[$taskIndex % count($variants)];
        $c1 = $this->toSvg($v['c1'][0], $v['c1'][1]);
        $c2 = $this->toSvg($v['c2'][0], $v['c2'][1]);
        $r1 = $v['r1'] * self::GRID_SIZE;
        $r2 = $v['r2'] * self::GRID_SIZE;
        [$width, $height] = $this->canvasSize($v['cols'], $v['rows']);

        return $this->wrapSvg($width, $height,
            $this->grid($width, $height)
            . $this->circle($c1['x'], $c1['y'], $r1, 'none', self::COLORS['line'], 1.5)
            . $this->circle($c2['x'], $c2['y'], $r2, 'none', self::COLORS['line'], 1.5)
            . $this->circle($c1['x'], $c1['y'], 3, self::COLORS['point'])
            . $this->circle($c2['x'], $c2['y'], 3, self::COLORS['point'])
        );
    }

    private function wrapSvg(int $width, int $height, string $content): string
    {
        return "<svg viewBox=\"0 0 {$width} {$height}\" class=\"w-full max-w-[260px] h-auto mx-auto rounded\">\n{$content}</svg>";
    }

    private function grid(int $width, int $height): string
    {
        $patternId = 'grid-' . substr(md5((string) mt_rand()), 0, 8);

        return "  <defs>\n"
            . "    <pattern id=\"{$patternId}\" width=\"" . self::GRID_SIZE . "\" height=\"" . self::GRID_SIZE . "\" patternUnits=\"userSpaceOnUse\">\n"
            . "      <path d=\"M " . self::GRID_SIZE . " 0 L 0 0 0 " . self::GRID_SIZE . "\" fill=\"none\" stroke=\"" . self::COLORS['grid'] . "\" stroke-width=\"0.8\"/>\n"
            . "    </pattern>\n"
            . "  </defs>\n"
            . "  <rect x=\"0\" y=\"0\" width=\"{$width}\" height=\"{$height}\" fill=\"" . self::COLORS['bg'] . "\"/>\n"
            . "  <rect x=\"0\" y=\"0\" width=\"{$width}\" height=\"{$height}\" fill=\"url(#{$patternId})\"/>\n";
    }

    private function polygon(array $points): string
    {
        $s = implode(' ', array_map(fn ($p) => $p['x'] . ',' . $p['y'], $points));
        return "  <polygon points=\"{$s}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width=\"1.5\" stroke-linejoin=\"round\"/>\n";
    }

    private function line(array $a, array $b, ?string $color = null, float $width = 1.5, ?string $dash = null): string
    {
        $color = $color ?? self::COLORS['line'];
        $dashAttr = $dash ? " stroke-dasharray=\"{$dash}\"" : '';
        return "  <line x1=\"{$a['x']}\" y1=\"{$a['y']}\" x2=\"{$b['x']}\" y2=\"{$b['y']}\" stroke=\"{$color}\" stroke-width=\"{$width}\"{$dashAttr}/>\n";
    }

    private function path(string $d): string
    {
        return "  <path d=\"{$d}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width=\"1.5\"/>\n";
    }

    private function circle(float $cx, float $cy, float $r, ?string $fill = null, string $stroke = 'none', float $strokeWidth = 0): string
    {
        $fill = $fill ?? self::COLORS['point'];
        $strokeAttr = $stroke !== 'none' ? " stroke=\"{$stroke}\" stroke-width=\"{$strokeWidth}\"" : '';
        return "  <circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$r}\" fill=\"{$fill}\"{$strokeAttr}/>\n";
    }

    private function text(string $label, float $x, float $y, ?string $color = null): string
    {
        $color = $color ?? self::COLORS['text'];
        return "  <text x=\"{$x}\" y=\"{$y}\" fill=\"{$color}\" font-size=\"13\" font-weight=\"bold\" font-style=\"italic\">{$label}</text>\n";
    }

    private function toSvg(int $gx, int $gy): array
    {
        return [
            'x' => self::PADDING + $gx * self::GRID_SIZE,
            'y' => self::PADDING + $gy * self::GRID_SIZE,
        ];
    }

    private function canvasSize(int $cols, int $rows): array
    {
        return [
            (int) (self::PADDING * 2 + $cols * self::GRID_SIZE),
            (int) (self::PADDING * 2 + $rows * self::GRID_SIZE),
        ];
    }
}
