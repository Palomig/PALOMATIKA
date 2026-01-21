<?php

namespace App\Services;

/**
 * GeometrySvgRenderer - Сервис для генерации SVG из геометрических данных
 *
 * Рендерит SVG-изображения для тем 15, 16, 17 (треугольники, окружности, четырёхугольники)
 * на основе JSON-данных без зависимости от JavaScript.
 */
class GeometrySvgRenderer
{
    // Blueprint цветовая схема
    private const COLORS = [
        'bg' => '#0a1628',
        'grid_small' => '#1a3a5c',
        'grid_large' => '#1e4a6e',
        'line' => '#c8dce8',
        'circle' => '#5a9fcf',
        'aux' => '#5a9fcf',
        'accent' => '#d4a855',
        'axis' => '#3a5a7c',
        'service' => '#7eb8da',
        'text' => '#c8dce8',
        'text_aux' => '#5a9fcf',
    ];

    // Поддерживаемые типы SVG для треугольников (тема 15)
    private const TRIANGLE_TYPES = [
        'bisector',           // Биссектриса
        'median',             // Медиана
        'angles_sum',         // Сумма углов
        'external_angle',     // Внешний угол
        'isosceles',          // Равнобедренный
        'isosceles_external', // Внешний угол равнобедренного
        'right_triangle',     // Прямоугольный
        'altitude',           // Высота
        'area_right',         // Площадь прямоугольного
        'area_height',        // Площадь с высотой
        'midline',            // Средняя линия
        'pythagoras',         // Теорема Пифагора
        'equilateral',        // Равносторонний
        'circumcircle',       // Описанная окружность
        'trig',               // Тригонометрия
        'area_theorem',       // Теорема о площади
    ];

    /**
     * Рендерит SVG из геометрических данных
     *
     * @param string $svgType Тип геометрии
     * @param array $geometry Данные геометрии (points, derived, elements)
     * @param array $params Параметры задачи (длины, углы)
     * @return string Готовый SVG как HTML-строка
     */
    public function render(string $svgType, array $geometry, array $params = []): string
    {
        $viewBox = $geometry['viewBox'] ?? [0, 0, 200, 160];
        $points = $this->parsePoints($geometry['points'] ?? []);

        // Вычисляем производные точки
        $derived = $this->computeDerived($geometry['derived'] ?? [], $points);
        $allPoints = array_merge($points, $derived);

        // Вычисляем центр (центроид)
        $center = $this->computeCenter($allPoints, $geometry);

        // Генерируем SVG-элементы
        $svg = $this->generateSvg($svgType, $viewBox, $allPoints, $center, $geometry, $params);

        return $svg;
    }

    /**
     * Проверяет, поддерживается ли тип геометрии
     */
    public function supports(string $svgType): bool
    {
        return in_array($svgType, self::TRIANGLE_TYPES);
    }

    /**
     * Возвращает список поддерживаемых типов
     */
    public function getSupportedTypes(): array
    {
        return self::TRIANGLE_TYPES;
    }

    /**
     * Парсит точки из массива [x, y] в объекты
     */
    private function parsePoints(array $points): array
    {
        $result = [];
        foreach ($points as $name => $coords) {
            if (is_array($coords) && count($coords) >= 2) {
                $result[$name] = ['x' => $coords[0], 'y' => $coords[1]];
            }
        }
        return $result;
    }

    /**
     * Вычисляет производные точки (биссектриса, медиана и т.д.)
     */
    private function computeDerived(array $derived, array $points): array
    {
        $result = [];
        foreach ($derived as $name => $formula) {
            $result[$name] = $this->evaluateFormula($formula, $points, $result);
        }
        return $result;
    }

    /**
     * Вычисляет точку по формуле
     */
    private function evaluateFormula(string $formula, array $points, array $computed): array
    {
        $allPoints = array_merge($points, $computed);

        // midpoint(A,C) — середина отрезка
        if (preg_match('/^midpoint\((\w+),(\w+)\)$/', $formula, $m)) {
            $p1 = $allPoints[$m[1]] ?? ['x' => 0, 'y' => 0];
            $p2 = $allPoints[$m[2]] ?? ['x' => 0, 'y' => 0];
            return $this->midpoint($p1, $p2);
        }

        // bisector(A,B,C) — точка D на стороне BC для биссектрисы из A
        if (preg_match('/^bisector\((\w+),(\w+),(\w+)\)$/', $formula, $m)) {
            $A = $allPoints[$m[1]] ?? ['x' => 0, 'y' => 0];
            $B = $allPoints[$m[2]] ?? ['x' => 0, 'y' => 0];
            $C = $allPoints[$m[3]] ?? ['x' => 0, 'y' => 0];
            return $this->bisectorPoint($A, $B, $C);
        }

        // altitude_foot(A,B,C) — основание высоты из A на BC
        if (preg_match('/^altitude_foot\((\w+),(\w+),(\w+)\)$/', $formula, $m)) {
            $A = $allPoints[$m[1]] ?? ['x' => 0, 'y' => 0];
            $B = $allPoints[$m[2]] ?? ['x' => 0, 'y' => 0];
            $C = $allPoints[$m[3]] ?? ['x' => 0, 'y' => 0];
            return $this->altitudeFoot($A, $B, $C);
        }

        // circumcenter(A,B,C) — центр описанной окружности
        if (preg_match('/^circumcenter\((\w+),(\w+),(\w+)\)$/', $formula, $m)) {
            $A = $allPoints[$m[1]] ?? ['x' => 0, 'y' => 0];
            $B = $allPoints[$m[2]] ?? ['x' => 0, 'y' => 0];
            $C = $allPoints[$m[3]] ?? ['x' => 0, 'y' => 0];
            return $this->circumcenter($A, $B, $C);
        }

        // extend(C,A,50) — продолжение луча из C через A на 50px
        if (preg_match('/^extend\((\w+),(\w+),(\d+)\)$/', $formula, $m)) {
            $from = $allPoints[$m[1]] ?? ['x' => 0, 'y' => 0];
            $through = $allPoints[$m[2]] ?? ['x' => 0, 'y' => 0];
            $length = (int)$m[3];
            return $this->extendLine($from, $through, $length);
        }

        return ['x' => 0, 'y' => 0];
    }

    /**
     * Вычисляет центр фигуры (центроид для треугольника)
     */
    private function computeCenter(array $points, array $geometry): array
    {
        // Если указан явно
        if (isset($geometry['center'])) {
            return ['x' => $geometry['center'][0], 'y' => $geometry['center'][1]];
        }

        // Центроид по основным вершинам (A, B, C)
        $mainVertices = ['A', 'B', 'C'];
        $sumX = 0;
        $sumY = 0;
        $count = 0;

        foreach ($mainVertices as $v) {
            if (isset($points[$v])) {
                $sumX += $points[$v]['x'];
                $sumY += $points[$v]['y'];
                $count++;
            }
        }

        if ($count > 0) {
            return ['x' => $sumX / $count, 'y' => $sumY / $count];
        }

        // Fallback: центр всех точек
        $sumX = 0;
        $sumY = 0;
        foreach ($points as $p) {
            $sumX += $p['x'];
            $sumY += $p['y'];
        }
        $count = count($points);
        return $count > 0 ? ['x' => $sumX / $count, 'y' => $sumY / $count] : ['x' => 100, 'y' => 80];
    }

    /**
     * Генерирует SVG-строку
     */
    private function generateSvg(string $svgType, array $viewBox, array $points, array $center, array $geometry, array $params): string
    {
        $width = $viewBox[2] ?? 200;
        $height = $viewBox[3] ?? 160;

        // Стандартный размер SVG диаграммы: max-w-[250px], высота автоматическая
        $svg = "<svg viewBox=\"0 0 {$width} {$height}\" class=\"w-full max-w-[250px] h-auto mx-auto\">\n";

        // Фон с сеткой (blueprint style)
        $svg .= $this->renderBackground($width, $height);

        // Рендерим по типу SVG
        $svg .= match($svgType) {
            'bisector' => $this->renderBisector($points, $center, $geometry),
            'median' => $this->renderMedian($points, $center, $geometry),
            'angles_sum' => $this->renderAnglesSum($points, $center, $geometry),
            'external_angle' => $this->renderExternalAngle($points, $center, $geometry),
            'isosceles' => $this->renderIsosceles($points, $center, $geometry),
            'isosceles_external' => $this->renderIsoscelesExternal($points, $center, $geometry),
            'right_triangle' => $this->renderRightTriangle($points, $center, $geometry),
            'altitude' => $this->renderAltitude($points, $center, $geometry),
            'area_right' => $this->renderAreaRight($points, $center, $geometry),
            'area_height' => $this->renderAreaHeight($points, $center, $geometry),
            'midline' => $this->renderMidline($points, $center, $geometry),
            'pythagoras' => $this->renderPythagoras($points, $center, $geometry),
            'equilateral' => $this->renderEquilateral($points, $center, $geometry),
            'circumcircle' => $this->renderCircumcircle($points, $center, $geometry),
            'trig' => $this->renderTrig($points, $center, $geometry),
            'area_theorem' => $this->renderAreaTheorem($points, $center, $geometry),
            default => $this->renderBasicTriangle($points, $center),
        };

        $svg .= "</svg>";

        return $svg;
    }

    /**
     * Рендерит фон (blueprint style, без сетки)
     */
    private function renderBackground(int $width, int $height): string
    {
        return "  <rect width=\"100%\" height=\"100%\" fill=\"#0a1628\"/>\n";
    }

    // ========================================================================
    // РЕНДЕРЫ ДЛЯ КАЖДОГО ТИПА SVG
    // ========================================================================

    /**
     * 1. Биссектриса
     */
    private function renderBisector(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'] ?? $this->bisectorPoint($A, $B, $C);

        $svg = $this->renderTriangle($A, $B, $C);

        // Биссектриса (пунктир)
        $svg .= $this->line($A, $D, self::COLORS['aux'], 1, '8,4');

        // Дуги равных углов
        $arc1 = $this->makeAngleArc($A, $C, $D, 20);
        $arc2 = $this->makeAngleArc($A, $D, $B, 25);
        $svg .= "  <path d=\"{$arc1}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width=\"1.2\"/>\n";
        $svg .= "  <path d=\"{$arc2}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width=\"1.2\"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center));
        $svg .= $this->label('B', $this->labelPos($B, $center));
        $svg .= $this->label('C', $this->labelPos($C, $center));
        $svg .= $this->label('D', ['x' => $D['x'], 'y' => $D['y'] + 15], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * 2. Медиана
     */
    private function renderMedian(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $M = $points['M'] ?? $this->midpoint($A, $C);

        $svg = $this->renderTriangle($A, $B, $C);

        // Медиана (пунктир)
        $svg .= $this->line($B, $M, self::COLORS['aux'], 1, '8,4');

        // Маркеры равенства AM = MC
        $tickAM = $this->equalityTick($A, $M);
        $tickMC = $this->equalityTick($M, $C);
        $svg .= $this->renderTick($tickAM);
        $svg .= $this->renderTick($tickMC);

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $M]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center));
        $svg .= $this->label('B', $this->labelPos($B, $center));
        $svg .= $this->label('C', $this->labelPos($C, $center));
        $svg .= $this->label('M', ['x' => $M['x'], 'y' => $M['y'] + 15], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * 3. Сумма углов
     */
    private function renderAnglesSum(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];

        $svg = $this->renderTriangle($A, $B, $C);

        // Дуги всех трёх углов
        $arcA = $this->makeAngleArc($A, $C, $B, 22);
        $arcB = $this->makeAngleArc($B, $A, $C, 18);
        $arcC = $this->makeAngleArc($C, $B, $A, 22);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width=\"1.2\"/>\n";
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width=\"1.2\"/>\n";
        $svg .= "  <path d=\"{$arcC}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width=\"1.2\"/>\n";

        // Вершины и метки
        $svg .= $this->crosshairs([$A, $B, $C]);
        $svg .= $this->label('A', $this->labelPos($A, $center));
        $svg .= $this->label('B', $this->labelPos($B, $center));
        $svg .= $this->label('C', $this->labelPos($C, $center));

        return $svg;
    }

    /**
     * 4. Внешний угол
     */
    private function renderExternalAngle(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'] ?? $this->extendLine($A, $C, 50);

        $svg = $this->renderTriangle($A, $B, $C);

        // Продолжение стороны (пунктир)
        $svg .= $this->line($C, $D, self::COLORS['aux'], 1, '8,4');

        // Внутренний угол (тонкий)
        $arcInner = $this->makeAngleArc($C, $A, $B, 20);
        $svg .= "  <path d=\"{$arcInner}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width=\"1\"/>\n";

        // Внешний угол (акцент)
        $arcOuter = $this->makeAngleArc($C, $B, $D, 18);
        $svg .= "  <path d=\"{$arcOuter}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width=\"1.5\"/>\n";

        // Вершины и метки
        $svg .= $this->crosshairs([$A, $B, $C]);
        $svg .= $this->label('A', $this->labelPos($A, $center));
        $svg .= $this->label('B', $this->labelPos($B, $center));
        $svg .= $this->label('C', ['x' => $C['x'], 'y' => $C['y'] + 18]);

        return $svg;
    }

    /**
     * 5. Равнобедренный треугольник
     */
    private function renderIsosceles(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];

        $svg = $this->renderTriangle($A, $B, $C);

        // Маркеры равенства AB = BC (двойные)
        $markAB = $this->midpoint($A, $B);
        $markBC = $this->midpoint($B, $C);
        $svg .= $this->renderDoubleEqualityMark($A, $B);
        $svg .= $this->renderDoubleEqualityMark($B, $C);

        // Дуги равных углов при основании
        $arcA = $this->makeAngleArc($A, $C, $B, 25);
        $arcC = $this->makeAngleArc($C, $B, $A, 25);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width=\"1\"/>\n";
        $svg .= "  <path d=\"{$arcC}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width=\"1\"/>\n";

        // Угол при вершине (акцент)
        $arcB = $this->makeAngleArc($B, $A, $C, 20);
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width=\"1.2\"/>\n";

        // Вершины и метки
        $svg .= $this->crosshairs([$A, $B, $C]);
        $svg .= $this->label('A', $this->labelPos($A, $center));
        $svg .= $this->label('B', $this->labelPos($B, $center));
        $svg .= $this->label('C', $this->labelPos($C, $center));

        return $svg;
    }

    /**
     * 6. Внешний угол равнобедренного
     */
    private function renderIsoscelesExternal(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'] ?? $this->extendLine($A, $C, 50);

        $svg = $this->renderTriangle($A, $B, $C);

        // Продолжение стороны
        $svg .= $this->line($C, $D, self::COLORS['aux'], 1, '8,4');

        // Маркеры равенства
        $svg .= $this->renderSingleEqualityMark($A, $B);
        $svg .= $this->renderSingleEqualityMark($B, $C);

        // Внешний угол
        $arcOuter = $this->makeAngleArc($C, $B, $D, 18);
        $svg .= "  <path d=\"{$arcOuter}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width=\"1.5\"/>\n";

        // Вершины и метки
        $svg .= $this->crosshairs([$A, $B, $C]);
        $svg .= $this->label('A', $this->labelPos($A, $center));
        $svg .= $this->label('B', $this->labelPos($B, $center));
        $svg .= $this->label('C', ['x' => $C['x'], 'y' => $C['y'] + 18]);

        return $svg;
    }

    /**
     * 7. Прямоугольный треугольник
     */
    private function renderRightTriangle(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C']; // Прямой угол

        $svg = $this->renderTriangle($A, $B, $C);

        // Квадратик прямого угла
        $rightAngle = $this->rightAnglePath($C, $A, $B, 12);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width=\"1.2\"/>\n";

        // Дуги острых углов
        $arcA = $this->makeAngleArc($A, $C, $B, 22);
        $arcB = $this->makeAngleArc($B, $A, $C, 18);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width=\"1\"/>\n";
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width=\"1\"/>\n";

        // Вершины и метки
        $svg .= $this->crosshairs([$A, $B, $C]);
        $svg .= $this->label('A', $this->labelPos($A, $center));
        $svg .= $this->label('B', $this->labelPos($B, $center));
        $svg .= $this->label('C', $this->labelPos($C, $center));

        return $svg;
    }

    /**
     * 8. Высота
     */
    private function renderAltitude(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $H = $points['H'] ?? $this->altitudeFoot($B, $A, $C);

        $svg = $this->renderTriangle($A, $B, $C);

        // Высота (пунктир)
        $svg .= $this->line($B, $H, self::COLORS['aux'], 1, '8,4');

        // Прямой угол в H
        $rightAngle = $this->rightAnglePath($H, $A, $B, 10);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width=\"1\"/>\n";

        // Угол BAC (акцент)
        $arcA = $this->makeAngleArc($A, $C, $B, 22);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width=\"1.2\"/>\n";

        // Угол ABH
        $arcB = $this->makeAngleArc($B, $A, $H, 18);
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width=\"1\"/>\n";

        // Вершины и метки
        $svg .= $this->crosshairs([$A, $B, $C, $H]);
        $svg .= $this->label('A', $this->labelPos($A, $center));
        $svg .= $this->label('B', $this->labelPos($B, $center));
        $svg .= $this->label('C', $this->labelPos($C, $center));
        $svg .= $this->label('H', ['x' => $H['x'] + 12, 'y' => $H['y'] - 5], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * 9. Площадь прямоугольного треугольника
     */
    private function renderAreaRight(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C']; // Прямой угол

        // Треугольник с заливкой
        $svg = $this->renderTriangleFilled($A, $B, $C);

        // Квадратик прямого угла
        $rightAngle = $this->rightAnglePath($C, $A, $B, 12);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['axis'] . "\" stroke-width=\"1\"/>\n";

        // Выделенные катеты
        $svg .= $this->line($A, $C, self::COLORS['accent'], 2);
        $svg .= $this->line($C, $B, self::COLORS['aux'], 2);

        // Метки катетов
        $labelAC = $this->labelOnSegment($A, $C, -12);
        $labelCB = $this->labelOnSegment($C, $B, 8);
        $svg .= $this->label('a', $labelAC, self::COLORS['accent'], 12);
        $svg .= $this->label('b', ['x' => $labelCB['x'] + 8, 'y' => $labelCB['y']], self::COLORS['aux'], 12);

        // Вершины и метки
        $svg .= $this->crosshairs([$A, $B, $C]);
        $svg .= $this->label('A', $this->labelPos($A, $center));
        $svg .= $this->label('B', $this->labelPos($B, $center));
        $svg .= $this->label('C', $this->labelPos($C, $center));

        return $svg;
    }

    /**
     * 10. Площадь с высотой
     */
    private function renderAreaHeight(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $H = $points['H'] ?? ['x' => $B['x'], 'y' => $A['y']];

        $svg = $this->renderTriangleFilled($A, $B, $C);

        // Высота
        $svg .= $this->line($B, $H, self::COLORS['aux'], 1, '8,4');

        // Прямой угол
        $rightAngle = $this->rightAnglePath($H, $A, $B, 10);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width=\"1\"/>\n";

        // Метки
        $svg .= $this->label('a', ['x' => ($A['x'] + $C['x']) / 2, 'y' => $A['y'] + 18], self::COLORS['accent'], 12);
        $svg .= $this->label('h', ['x' => ($B['x'] + $H['x']) / 2 + 12, 'y' => ($B['y'] + $H['y']) / 2], self::COLORS['aux'], 12);

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $H]);
        $svg .= $this->label('A', ['x' => $A['x'] - 15, 'y' => $A['y'] + 5]);
        $svg .= $this->label('B', ['x' => $B['x'], 'y' => $B['y'] - 12]);
        $svg .= $this->label('C', ['x' => $C['x'] + 15, 'y' => $C['y'] + 5]);
        $svg .= $this->label('H', ['x' => $H['x'] + 5, 'y' => $H['y'] + 15], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * 11. Средняя линия
     */
    private function renderMidline(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $M = $points['M'] ?? $this->midpoint($A, $B);
        $N = $points['N'] ?? $this->midpoint($B, $C);

        $svg = $this->renderTriangle($A, $B, $C);

        // Средняя линия
        $svg .= $this->line($M, $N, self::COLORS['accent'], 1.5);

        // Маркеры середин
        $svg .= $this->renderSingleEqualityMark($A, $M);
        $svg .= $this->renderSingleEqualityMark($M, $B);
        $svg .= $this->renderSingleEqualityMark($B, $N);
        $svg .= $this->renderSingleEqualityMark($N, $C);

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $M, $N]);
        $svg .= $this->label('A', $this->labelPos($A, $center));
        $svg .= $this->label('B', $this->labelPos($B, $center));
        $svg .= $this->label('C', $this->labelPos($C, $center));
        $svg .= $this->label('M', ['x' => $M['x'] - 12, 'y' => $M['y']], self::COLORS['text_aux'], 12);
        $svg .= $this->label('N', ['x' => $N['x'] + 10, 'y' => $N['y']], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * 12-13. Теорема Пифагора
     */
    private function renderPythagoras(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C']; // Прямой угол

        $svg = $this->renderTriangle($A, $B, $C);

        // Квадратик прямого угла
        $rightAngle = $this->rightAnglePath($C, $A, $B, 12);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width=\"1\"/>\n";

        // Выделенные стороны
        $svg .= $this->line($A, $C, self::COLORS['accent'], 2);
        $svg .= $this->line($C, $B, self::COLORS['aux'], 2);
        $svg .= $this->line($A, $B, self::COLORS['service'], 2);

        // Метки сторон
        $svg .= $this->label('a', ['x' => ($A['x'] + $C['x']) / 2, 'y' => $A['y'] + 18], self::COLORS['accent'], 12);
        $svg .= $this->label('b', ['x' => $C['x'] + 15, 'y' => ($C['y'] + $B['y']) / 2], self::COLORS['aux'], 12);
        $svg .= $this->label('c', ['x' => ($A['x'] + $B['x']) / 2 - 12, 'y' => ($A['y'] + $B['y']) / 2 - 5], self::COLORS['service'], 12);

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C]);
        $svg .= $this->label('A', ['x' => $A['x'] - 15, 'y' => $A['y'] + 5]);
        $svg .= $this->label('B', ['x' => $B['x'] + 5, 'y' => $B['y'] - 12]);
        $svg .= $this->label('C', ['x' => $C['x'] + 15, 'y' => $C['y'] + 5]);

        return $svg;
    }

    /**
     * 14-18. Равносторонний треугольник
     */
    private function renderEquilateral(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $H = $points['H'] ?? ['x' => $B['x'], 'y' => $A['y']];

        $svg = $this->renderTriangle($A, $B, $C);

        // Высота BH
        $svg .= $this->line($B, $H, self::COLORS['aux'], 1, '8,4');

        // Прямой угол в H
        $rightAngle = $this->rightAnglePath($H, $A, $B, 10);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width=\"1\"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $H]);
        $svg .= $this->label('A', ['x' => $A['x'] - 15, 'y' => $A['y'] + 5]);
        $svg .= $this->label('B', ['x' => $B['x'], 'y' => $B['y'] - 12]);
        $svg .= $this->label('C', ['x' => $C['x'] + 15, 'y' => $C['y'] + 5]);
        $svg .= $this->label('H', ['x' => $H['x'] + 5, 'y' => $H['y'] + 15], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * 19. Описанная окружность
     */
    private function renderCircumcircle(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C']; // Прямой угол

        // Центр описанной окружности — середина гипотенузы
        $O = $points['O'] ?? $this->midpoint($A, $B);
        $R = $this->distance($O, $A);

        $svg = '';

        // Окружность
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$R}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width=\"1.2\"/>\n";

        // Треугольник
        $svg .= $this->renderTriangle($A, $B, $C);

        // Прямой угол в C
        $rightAngle = $this->rightAnglePath($C, $A, $B, 10);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width=\"1\"/>\n";

        // Радиус (пунктир)
        $svg .= $this->line($O, $A, self::COLORS['accent'], 1, '6,3');

        // Центр (крестик)
        $svg .= $this->centerMark($O);

        // Метка радиуса
        $svg .= $this->label('R', ['x' => ($O['x'] + $A['x']) / 2 - 8, 'y' => ($O['y'] + $A['y']) / 2 - 5], self::COLORS['accent'], 12);

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C]);
        $svg .= $this->label('A', $this->labelPos($A, $center));
        $svg .= $this->label('B', $this->labelPos($B, $center));
        $svg .= $this->label('C', $this->labelPos($C, $center));

        return $svg;
    }

    /**
     * 20-25. Тригонометрия
     */
    private function renderTrig(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C']; // Прямой угол

        $svg = $this->renderTriangle($A, $B, $C);

        // Прямой угол в C
        $rightAngle = $this->rightAnglePath($C, $A, $B, 12);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width=\"1\"/>\n";

        // Выделенные стороны
        $svg .= $this->line($A, $C, self::COLORS['aux'], 1.5);
        $svg .= $this->line($C, $B, self::COLORS['accent'], 1.5);

        // Метки сторон
        $svg .= $this->label('AC', ['x' => ($A['x'] + $C['x']) / 2, 'y' => $A['y'] + 18], self::COLORS['aux'], 12);
        $svg .= $this->label('BC', ['x' => $C['x'] + 18, 'y' => ($C['y'] + $B['y']) / 2 + 10], self::COLORS['accent'], 12);
        $svg .= $this->label('AB', ['x' => ($A['x'] + $B['x']) / 2 - 18, 'y' => ($A['y'] + $B['y']) / 2 - 5], self::COLORS['text'], 12);

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C]);
        $svg .= $this->label('A', ['x' => $A['x'] - 15, 'y' => $A['y'] + 5]);
        $svg .= $this->label('B', ['x' => $B['x'] + 5, 'y' => $B['y'] - 12]);
        $svg .= $this->label('C', ['x' => $C['x'] + 15, 'y' => $C['y'] + 5]);

        return $svg;
    }

    /**
     * 26. Теорема о площади (S = 1/2 * AB * BC * sin(B))
     */
    private function renderAreaTheorem(array $points, array $center, array $geometry): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];

        $svg = $this->renderTriangleFilled($A, $B, $C);

        // Угол B (акцент)
        $arcB = $this->makeAngleArc($B, $A, $C, 22);
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width=\"1.5\"/>\n";

        // Выделенные стороны AB и BC
        $svg .= $this->line($A, $B, self::COLORS['aux'], 2);
        $svg .= $this->line($B, $C, self::COLORS['accent'], 2);

        // Метки сторон
        $svg .= $this->label('AB', ['x' => ($A['x'] + $B['x']) / 2 - 15, 'y' => ($A['y'] + $B['y']) / 2 - 5], self::COLORS['aux'], 12);
        $svg .= $this->label('BC', ['x' => ($B['x'] + $C['x']) / 2 + 12, 'y' => ($B['y'] + $C['y']) / 2], self::COLORS['accent'], 12);

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C]);
        $svg .= $this->label('A', ['x' => $A['x'] - 15, 'y' => $A['y'] + 8]);
        $svg .= $this->label('B', ['x' => $B['x'], 'y' => $B['y'] - 15]);
        $svg .= $this->label('C', ['x' => $C['x'] + 15, 'y' => $C['y'] + 5]);

        return $svg;
    }

    /**
     * Базовый треугольник (fallback)
     */
    private function renderBasicTriangle(array $points, array $center): string
    {
        $A = $points['A'] ?? ['x' => 20, 'y' => 130];
        $B = $points['B'] ?? ['x' => 100, 'y' => 25];
        $C = $points['C'] ?? ['x' => 180, 'y' => 130];

        $svg = $this->renderTriangle($A, $B, $C);
        $svg .= $this->crosshairs([$A, $B, $C]);
        $svg .= $this->label('A', $this->labelPos($A, $center));
        $svg .= $this->label('B', $this->labelPos($B, $center));
        $svg .= $this->label('C', $this->labelPos($C, $center));

        return $svg;
    }

    // ========================================================================
    // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ РЕНДЕРИНГА
    // ========================================================================

    private function renderTriangle(array $A, array $B, array $C): string
    {
        return "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']}\" " .
               "fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width=\"1.5\" stroke-linejoin=\"round\"/>\n";
    }

    private function renderTriangleFilled(array $A, array $B, array $C): string
    {
        return "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']}\" " .
               "fill=\"rgba(90, 159, 207, 0.1)\" stroke=\"" . self::COLORS['line'] . "\" stroke-width=\"1.5\" stroke-linejoin=\"round\"/>\n";
    }

    private function line(array $p1, array $p2, string $color, float $width = 1, string $dasharray = ''): string
    {
        $dash = $dasharray ? " stroke-dasharray=\"{$dasharray}\"" : '';
        return "  <line x1=\"{$p1['x']}\" y1=\"{$p1['y']}\" x2=\"{$p2['x']}\" y2=\"{$p2['y']}\" " .
               "stroke=\"{$color}\" stroke-width=\"{$width}\"{$dash}/>\n";
    }

    private function crosshairs(array $points): string
    {
        $svg = '';
        foreach ($points as $p) {
            $x = $p['x'] - 6;
            $y = $p['y'] - 6;
            $svg .= <<<SVG
  <g transform="translate({$p['x']}, {$p['y']})">
    <line x1="-5" y1="0" x2="5" y2="0" stroke="#7eb8da" stroke-width="1"/>
    <line x1="0" y1="-5" x2="0" y2="5" stroke="#7eb8da" stroke-width="1"/>
    <circle cx="0" cy="0" r="2" fill="none" stroke="#7eb8da" stroke-width="0.8"/>
  </g>

SVG;
        }
        return $svg;
    }

    private function centerMark(array $p): string
    {
        return <<<SVG
  <g transform="translate({$p['x']}, {$p['y']})">
    <line x1="-6" y1="0" x2="-2" y2="0" stroke="#7eb8da" stroke-width="0.8"/>
    <line x1="2" y1="0" x2="6" y2="0" stroke="#7eb8da" stroke-width="0.8"/>
    <line x1="0" y1="-6" x2="0" y2="-2" stroke="#7eb8da" stroke-width="0.8"/>
    <line x1="0" y1="2" x2="0" y2="6" stroke="#7eb8da" stroke-width="0.8"/>
    <circle cx="0" cy="0" r="1.5" fill="#7eb8da"/>
  </g>

SVG;
    }

    private function label(string $text, array $pos, string $color = null, int $size = 14): string
    {
        $color = $color ?? self::COLORS['text'];
        return "  <text x=\"{$pos['x']}\" y=\"{$pos['y']}\" fill=\"{$color}\" font-size=\"{$size}\" " .
               "font-family=\"Courier New, monospace\" font-style=\"italic\" font-weight=\"500\" " .
               "text-anchor=\"middle\" dominant-baseline=\"middle\">{$text}</text>\n";
    }

    private function renderTick(array $tick): string
    {
        return "  <line x1=\"{$tick['x1']}\" y1=\"{$tick['y1']}\" x2=\"{$tick['x2']}\" y2=\"{$tick['y2']}\" " .
               "stroke=\"" . self::COLORS['service'] . "\" stroke-width=\"1.5\"/>\n";
    }

    private function renderSingleEqualityMark(array $p1, array $p2): string
    {
        $tick = $this->equalityTick($p1, $p2);
        return $this->renderTick($tick);
    }

    private function renderDoubleEqualityMark(array $p1, array $p2): string
    {
        $ticks = $this->doubleEqualityTick($p1, $p2);
        return $this->renderTick($ticks['tick1']) . $this->renderTick($ticks['tick2']);
    }

    // ========================================================================
    // МАТЕМАТИЧЕСКИЕ ФУНКЦИИ (порт из geometry-helpers.js)
    // ========================================================================

    /**
     * Позиция метки от центра
     */
    private function labelPos(array $point, array $center, float $distance = 15): array
    {
        $dx = $point['x'] - $center['x'];
        $dy = $point['y'] - $center['y'];
        $len = sqrt($dx * $dx + $dy * $dy);

        if ($len === 0.0) {
            return ['x' => $point['x'], 'y' => $point['y'] - $distance];
        }

        return [
            'x' => $point['x'] + ($dx / $len) * $distance,
            'y' => $point['y'] + ($dy / $len) * $distance
        ];
    }

    /**
     * SVG-путь дуги угла
     */
    private function makeAngleArc(array $vertex, array $point1, array $point2, float $radius): string
    {
        $angle1 = atan2($point1['y'] - $vertex['y'], $point1['x'] - $vertex['x']);
        $angle2 = atan2($point2['y'] - $vertex['y'], $point2['x'] - $vertex['x']);

        $x1 = $vertex['x'] + $radius * cos($angle1);
        $y1 = $vertex['y'] + $radius * sin($angle1);
        $x2 = $vertex['x'] + $radius * cos($angle2);
        $y2 = $vertex['y'] + $radius * sin($angle2);

        $angleDiff = $angle2 - $angle1;
        while ($angleDiff > M_PI) $angleDiff -= 2 * M_PI;
        while ($angleDiff < -M_PI) $angleDiff += 2 * M_PI;

        $sweep = $angleDiff > 0 ? 1 : 0;

        return "M {$x1} {$y1} A {$radius} {$radius} 0 0 {$sweep} {$x2} {$y2}";
    }

    /**
     * SVG-путь квадратика прямого угла
     */
    private function rightAnglePath(array $vertex, array $p1, array $p2, int $size = 12): string
    {
        $angle1 = atan2($p1['y'] - $vertex['y'], $p1['x'] - $vertex['x']);
        $angle2 = atan2($p2['y'] - $vertex['y'], $p2['x'] - $vertex['x']);

        $c1 = [
            'x' => $vertex['x'] + $size * cos($angle1),
            'y' => $vertex['y'] + $size * sin($angle1)
        ];
        $c2 = [
            'x' => $vertex['x'] + $size * cos($angle2),
            'y' => $vertex['y'] + $size * sin($angle2)
        ];
        $diag = [
            'x' => $c1['x'] + $size * cos($angle2),
            'y' => $c1['y'] + $size * sin($angle2)
        ];

        return "M {$c1['x']} {$c1['y']} L {$diag['x']} {$diag['y']} L {$c2['x']} {$c2['y']}";
    }

    /**
     * Середина отрезка
     */
    private function midpoint(array $p1, array $p2): array
    {
        return [
            'x' => ($p1['x'] + $p2['x']) / 2,
            'y' => ($p1['y'] + $p2['y']) / 2
        ];
    }

    /**
     * Точка на биссектрисе (делит противоположную сторону в отношении прилежащих)
     */
    private function bisectorPoint(array $A, array $B, array $C): array
    {
        $AB = $this->distance($A, $B);
        $AC = $this->distance($A, $C);
        $t = $AB / ($AB + $AC);

        return [
            'x' => $B['x'] + $t * ($C['x'] - $B['x']),
            'y' => $B['y'] + $t * ($C['y'] - $B['y'])
        ];
    }

    /**
     * Основание высоты из A на BC
     */
    private function altitudeFoot(array $A, array $B, array $C): array
    {
        $BCx = $C['x'] - $B['x'];
        $BCy = $C['y'] - $B['y'];
        $BAx = $A['x'] - $B['x'];
        $BAy = $A['y'] - $B['y'];

        $t = ($BAx * $BCx + $BAy * $BCy) / ($BCx * $BCx + $BCy * $BCy);

        return [
            'x' => $B['x'] + $t * $BCx,
            'y' => $B['y'] + $t * $BCy
        ];
    }

    /**
     * Центр описанной окружности
     */
    private function circumcenter(array $A, array $B, array $C): array
    {
        $D = 2 * ($A['x'] * ($B['y'] - $C['y']) + $B['x'] * ($C['y'] - $A['y']) + $C['x'] * ($A['y'] - $B['y']));

        if (abs($D) < 0.001) {
            return $this->midpoint($A, $B); // Вырожденный случай
        }

        $ux = (($A['x']**2 + $A['y']**2) * ($B['y'] - $C['y']) +
               ($B['x']**2 + $B['y']**2) * ($C['y'] - $A['y']) +
               ($C['x']**2 + $C['y']**2) * ($A['y'] - $B['y'])) / $D;

        $uy = (($A['x']**2 + $A['y']**2) * ($C['x'] - $B['x']) +
               ($B['x']**2 + $B['y']**2) * ($A['x'] - $C['x']) +
               ($C['x']**2 + $C['y']**2) * ($B['x'] - $A['x'])) / $D;

        return ['x' => $ux, 'y' => $uy];
    }

    /**
     * Продолжение луча
     */
    private function extendLine(array $from, array $through, float $length): array
    {
        $dx = $through['x'] - $from['x'];
        $dy = $through['y'] - $from['y'];
        $dist = sqrt($dx * $dx + $dy * $dy);

        if ($dist === 0.0) {
            return ['x' => $from['x'] + $length, 'y' => $from['y']];
        }

        return [
            'x' => $through['x'] + ($dx / $dist) * $length,
            'y' => $through['y'] + ($dy / $dist) * $length
        ];
    }

    /**
     * Расстояние между точками
     */
    private function distance(array $p1, array $p2): float
    {
        return sqrt(($p2['x'] - $p1['x']) ** 2 + ($p2['y'] - $p1['y']) ** 2);
    }

    /**
     * Маркер равенства (одна черточка)
     */
    private function equalityTick(array $p1, array $p2, float $t = 0.5, int $length = 8): array
    {
        $mid = [
            'x' => $p1['x'] + ($p2['x'] - $p1['x']) * $t,
            'y' => $p1['y'] + ($p2['y'] - $p1['y']) * $t
        ];

        $dx = $p2['x'] - $p1['x'];
        $dy = $p2['y'] - $p1['y'];
        $len = sqrt($dx * $dx + $dy * $dy);

        if ($len === 0.0) {
            return ['x1' => $mid['x'], 'y1' => $mid['y'], 'x2' => $mid['x'], 'y2' => $mid['y']];
        }

        $nx = -$dy / $len;
        $ny = $dx / $len;
        $half = $length / 2;

        return [
            'x1' => $mid['x'] - $nx * $half,
            'y1' => $mid['y'] - $ny * $half,
            'x2' => $mid['x'] + $nx * $half,
            'y2' => $mid['y'] + $ny * $half
        ];
    }

    /**
     * Двойной маркер равенства
     */
    private function doubleEqualityTick(array $p1, array $p2, float $t = 0.5, int $length = 8, int $gap = 4): array
    {
        $dx = $p2['x'] - $p1['x'];
        $dy = $p2['y'] - $p1['y'];
        $len = sqrt($dx * $dx + $dy * $dy);

        if ($len === 0.0) {
            $mid = ['x' => $p1['x'], 'y' => $p1['y']];
            $tick = ['x1' => $mid['x'], 'y1' => $mid['y'], 'x2' => $mid['x'], 'y2' => $mid['y']];
            return ['tick1' => $tick, 'tick2' => $tick];
        }

        $ux = $dx / $len;
        $uy = $dy / $len;
        $nx = -$dy / $len;
        $ny = $dx / $len;

        $mid = [
            'x' => $p1['x'] + $dx * $t,
            'y' => $p1['y'] + $dy * $t
        ];

        $half = $length / 2;
        $halfGap = $gap / 2;

        return [
            'tick1' => [
                'x1' => $mid['x'] - $ux * $halfGap - $nx * $half,
                'y1' => $mid['y'] - $uy * $halfGap - $ny * $half,
                'x2' => $mid['x'] - $ux * $halfGap + $nx * $half,
                'y2' => $mid['y'] - $uy * $halfGap + $ny * $half
            ],
            'tick2' => [
                'x1' => $mid['x'] + $ux * $halfGap - $nx * $half,
                'y1' => $mid['y'] + $uy * $halfGap - $ny * $half,
                'x2' => $mid['x'] + $ux * $halfGap + $nx * $half,
                'y2' => $mid['y'] + $uy * $halfGap + $ny * $half
            ]
        ];
    }

    /**
     * Позиция метки на середине отрезка
     */
    private function labelOnSegment(array $p1, array $p2, float $offset = 15, bool $flipSide = false): array
    {
        $mid = $this->midpoint($p1, $p2);

        $dx = $p2['x'] - $p1['x'];
        $dy = $p2['y'] - $p1['y'];
        $len = sqrt($dx * $dx + $dy * $dy);

        if ($len === 0.0) {
            return $mid;
        }

        $nx = -$dy / $len;
        $ny = $dx / $len;

        if ($flipSide) {
            $nx = -$nx;
            $ny = -$ny;
        }

        return [
            'x' => $mid['x'] + $nx * $offset,
            'y' => $mid['y'] + $ny * $offset
        ];
    }
}
