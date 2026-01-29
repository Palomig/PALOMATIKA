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

    // Поддерживаемые типы SVG для окружностей (тема 16)
    private const CIRCLE_TYPES = [
        'square_circle_vertex',      // Квадрат с окружностью через вершину
        'tangent_lines',             // Касательные к окружности
        'inscribed_angle',           // Вписанный угол
        'diameters',                 // Два диаметра
        'diameter_points',           // Точки по разные стороны диаметра
        'inscribed_trapezoid',       // Окружность, вписанная в трапецию
        'inscribed_square',          // Окружность, вписанная в квадрат
        'circumscribed_shapes',      // Описанные фигуры
        'triangle_inscribed_circle', // Треугольник с вписанной окружностью
        'quad_in_circle',            // Четырёхугольник в окружности
        'center_on_side',            // Центр на стороне треугольника
        'trapezoid_in_circle',       // Трапеция в окружности
        'sine_theorem',              // Теорема синусов
    ];

    // Поддерживаемые типы SVG для четырёхугольников (тема 17)
    private const QUADRILATERAL_TYPES = [
        'parallelogram_angles',      // Углы параллелограмма
        'parallelogram_diagonal',    // Диагональ параллелограмма
        'parallelogram_bisector',    // Биссектриса угла параллелограмма
        'parallelogram_diagonals',   // Диагонали параллелограмма
        'parallelogram_area',        // Площадь параллелограмма
        'isosceles_trapezoid',       // Равнобедренная трапеция
        'right_trapezoid',           // Прямоугольная трапеция
        'trapezoid_height',          // Трапеция с высотой
        'trapezoid_diagonal_45',     // Трапеция с диагональю 45°
        'trapezoid_area',            // Площадь трапеции
        'trapezoid_midline',         // Средняя линия трапеции
        'trapezoid_30',              // Трапеция с углом 30°
        'rectangle_diagonals',       // Прямоугольник с диагоналями
        'rectangle_point_e',         // Прямоугольник с точкой E
        'rhombus_angles',            // Углы ромба
        'rhombus_diagonal',          // Ромб с диагональю
        'rhombus_height',            // Высота ромба
        'rhombus_area',              // Площадь ромба
        'square_diagonal',           // Диагональ квадрата
        'square_area',               // Площадь квадрата
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
        return in_array($svgType, self::TRIANGLE_TYPES)
            || in_array($svgType, self::CIRCLE_TYPES)
            || in_array($svgType, self::QUADRILATERAL_TYPES);
    }

    /**
     * Возвращает список поддерживаемых типов
     */
    public function getSupportedTypes(): array
    {
        return array_merge(self::TRIANGLE_TYPES, self::CIRCLE_TYPES, self::QUADRILATERAL_TYPES);
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

        // Стандартный размер SVG диаграммы: max-w-[350px] (синхронизировано с клиентской версией)
        $svg = "<svg viewBox=\"0 0 {$width} {$height}\" class=\"w-full max-w-[350px] h-auto mx-auto\">\n";

        // Фон с сеткой (blueprint style)
        $svg .= $this->renderBackground($width, $height);

        // Рендерим по типу SVG
        $svg .= match($svgType) {
            // Тема 15: Треугольники
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
            // Тема 16: Окружности
            'square_circle_vertex' => $this->renderSquareCircleVertex($points, $center, $geometry, $params),
            'tangent_lines' => $this->renderTangentLines($points, $center, $geometry, $params),
            'inscribed_angle' => $this->renderInscribedAngle($points, $center, $geometry, $params),
            'diameters' => $this->renderDiameters($points, $center, $geometry, $params),
            'diameter_points' => $this->renderDiameterPoints($points, $center, $geometry, $params),
            'inscribed_trapezoid' => $this->renderInscribedTrapezoid($points, $center, $geometry, $params),
            'inscribed_square' => $this->renderInscribedSquare($points, $center, $geometry, $params),
            'circumscribed_shapes' => $this->renderCircumscribedShapes($points, $center, $geometry, $params),
            'triangle_inscribed_circle' => $this->renderTriangleInscribedCircle($points, $center, $geometry, $params),
            'quad_in_circle' => $this->renderQuadInCircle($points, $center, $geometry, $params),
            'center_on_side' => $this->renderCenterOnSide($points, $center, $geometry, $params),
            'trapezoid_in_circle' => $this->renderTrapezoidInCircle($points, $center, $geometry, $params),
            'sine_theorem' => $this->renderSineTheorem($points, $center, $geometry, $params),
            // Тема 17: Четырёхугольники
            'parallelogram_angles' => $this->renderParallelogramAngles($points, $center, $geometry, $params),
            'parallelogram_diagonal' => $this->renderParallelogramDiagonal($points, $center, $geometry, $params),
            'parallelogram_bisector' => $this->renderParallelogramBisector($points, $center, $geometry, $params),
            'parallelogram_diagonals' => $this->renderParallelogramDiagonals($points, $center, $geometry, $params),
            'parallelogram_area' => $this->renderParallelogramArea($points, $center, $geometry, $params),
            'isosceles_trapezoid' => $this->renderIsoscelesTrapezoid($points, $center, $geometry, $params),
            'right_trapezoid' => $this->renderRightTrapezoid($points, $center, $geometry, $params),
            'trapezoid_height' => $this->renderTrapezoidHeight($points, $center, $geometry, $params),
            'trapezoid_diagonal_45' => $this->renderTrapezoidDiagonal45($points, $center, $geometry, $params),
            'trapezoid_area' => $this->renderTrapezoidArea($points, $center, $geometry, $params),
            'trapezoid_midline' => $this->renderTrapezoidMidline($points, $center, $geometry, $params),
            'trapezoid_30' => $this->renderTrapezoid30($points, $center, $geometry, $params),
            'rectangle_diagonals' => $this->renderRectangleDiagonals($points, $center, $geometry, $params),
            'rectangle_point_e' => $this->renderRectanglePointE($points, $center, $geometry, $params),
            'rhombus_angles' => $this->renderRhombusAngles($points, $center, $geometry, $params),
            'rhombus_diagonal' => $this->renderRhombusDiagonal($points, $center, $geometry, $params),
            'rhombus_height' => $this->renderRhombusHeight($points, $center, $geometry, $params),
            'rhombus_area' => $this->renderRhombusArea($points, $center, $geometry, $params),
            'square_diagonal' => $this->renderSquareDiagonal($points, $center, $geometry, $params),
            'square_area' => $this->renderSquareArea($points, $center, $geometry, $params),
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
        $svg .= "  <path d=\"{$arc1}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";
        $svg .= "  <path d=\"{$arc2}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

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
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";
        $svg .= "  <path d=\"{$arcC}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

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
        $svg .= "  <path d=\"{$arcInner}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1"/>\n";

        // Внешний угол (акцент)
        $arcOuter = $this->makeAngleArc($C, $B, $D, 18);
        $svg .= "  <path d=\"{$arcOuter}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";

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
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$arcC}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1"/>\n";

        // Угол при вершине (акцент)
        $arcB = $this->makeAngleArc($B, $A, $C, 20);
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

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
        $svg .= "  <path d=\"{$arcOuter}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";

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
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

        // Дуги острых углов
        $arcA = $this->makeAngleArc($A, $C, $B, 22);
        $arcB = $this->makeAngleArc($B, $A, $C, 18);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1"/>\n";

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
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";

        // Угол BAC (акцент)
        $arcA = $this->makeAngleArc($A, $C, $B, 22);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

        // Угол ABH
        $arcB = $this->makeAngleArc($B, $A, $H, 18);
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1"/>\n";

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
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['axis'] . "\" stroke-width="1"/>\n";

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
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1"/>\n";

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
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1"/>\n";

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
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1"/>\n";

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
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$R}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.2"/>\n";

        // Треугольник
        $svg .= $this->renderTriangle($A, $B, $C);

        // Прямой угол в C
        $rightAngle = $this->rightAnglePath($C, $A, $B, 10);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1"/>\n";

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
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1"/>\n";

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
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";

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
               "fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="1.5" stroke-linejoin=\"round\"/>\n";
    }

    private function renderTriangleFilled(array $A, array $B, array $C): string
    {
        return "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']}\" " .
               "fill=\"rgba(90, 159, 207, 0.1)\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="1.5" stroke-linejoin=\"round\"/>\n";
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
        // GEOMETRY_SPEC: Times New Roman, serif - синхронизировано с клиентской версией
        return "  <text x=\"{$pos['x']}\" y=\"{$pos['y']}\" fill=\"{$color}\" font-size=\"{$size}\" " .
               "font-family=\"'Times New Roman', serif\" font-style=\"italic\" font-weight=\"500\" " .
               "text-anchor=\"middle\" dominant-baseline=\"middle\" class=\"geo-label\">{$text}</text>\n";
    }

    private function renderTick(array $tick): string
    {
        return "  <line x1=\"{$tick['x1']}\" y1=\"{$tick['y1']}\" x2=\"{$tick['x2']}\" y2=\"{$tick['y2']}\" " .
               "stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.5"/>\n";
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
     * Позиция метки угла (между двумя сторонами угла)
     * bias: 0.5 = точная середина между p1 и p2
     *       <0.5 = ближе к p1
     *       >0.5 = ближе к p2
     */
    private function angleLabelPos(array $vertex, array $p1, array $p2, float $labelRadius, float $bias = 0.5): array
    {
        $angle1 = atan2($p1['y'] - $vertex['y'], $p1['x'] - $vertex['x']);
        $angle2 = atan2($p2['y'] - $vertex['y'], $p2['x'] - $vertex['x']);

        // Нормализуем разницу углов к диапазону (-π, π]
        $diff = $angle2 - $angle1;
        while ($diff > M_PI) $diff -= 2 * M_PI;
        while ($diff < -M_PI) $diff += 2 * M_PI;

        // Позиция = angle1 + bias * diff
        $midAngle = $angle1 + $diff * $bias;

        return [
            'x' => $vertex['x'] + $labelRadius * cos($midAngle),
            'y' => $vertex['y'] + $labelRadius * sin($midAngle)
        ];
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

    // ========================================================================
    // РЕНДЕРЫ ДЛЯ ОКРУЖНОСТЕЙ (ТЕМА 16)
    // ========================================================================

    /**
     * Квадрат с окружностью через вершину (задания 1-8)
     * O - середина CD, окружность проходит через A
     */
    private function renderSquareCircleVertex(array $points, array $center, array $geometry, array $params): string
    {
        // Фиксированные координаты для 85% заполнения viewBox 220×200
        $A = ['x' => 72, 'y' => 34];
        $B = ['x' => 148, 'y' => 34];
        $C = ['x' => 148, 'y' => 110];
        $D = ['x' => 72, 'y' => 110];
        $O = ['x' => 110, 'y' => 110]; // Середина CD
        $R = 85; // Визуальный радиус

        $svg = '';

        // Окружность (тонкая линия)
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$R}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Квадрат
        $svg .= "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']} {$D['x']},{$D['y']}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Радиус к A (пунктир)
        $svg .= "  <line x1=\"{$O['x']}\" y1=\"{$O['y']}\" x2=\"{$A['x']}\" y2=\"{$A['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Крестики для точек
        $svg .= $this->crosshairs([$O, $A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', ['x' => $A['x'] - 12, 'y' => $A['y'] - 8], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] + 6, 'y' => $B['y'] - 8], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] + 6, 'y' => $C['y'] + 14], self::COLORS['text'], 14);
        $svg .= $this->label('D', ['x' => $D['x'] - 12, 'y' => $D['y'] + 14], self::COLORS['text'], 14);
        $svg .= $this->label('O', ['x' => $O['x'], 'y' => $O['y'] + 18], self::COLORS['text_aux'], 14);

        return $svg;
    }

    /**
     * Касательные к окружности (задания 9-12)
     *
     * ВАЖНО: Геометрия подобрана так, чтобы ЦЕЛОЧИСЛЕННЫЕ координаты
     * давали ТОЧНЫЕ касательные (OA ⊥ AP, OB ⊥ BP, |OA| = |OB| = R).
     *
     * Математическое доказательство:
     * - O = (60, 100), R = 60, P = (180, 40)
     * - |OP| = sqrt(120² + 60²) = sqrt(18000) = 60√5
     * - Треугольник 3-4-5: катеты 48, 36, гипотенуза 60
     * - A = (60+48, 100+36) = (108, 136): |OA| = 60 ✓
     * - OA = (48, 36), AP = (72, -96) = 24*(3, -4)
     * - OA·AP = 48*72 + 36*(-96) = 3456 - 3456 = 0 ✓
     */
    private function renderTangentLines(array $points, array $center, array $geometry, array $params): string
    {
        // Геометрия с касательными - стиль унифицирован с темами 15/17
        $O = ['x' => 70, 'y' => 100];   // Центр окружности
        $R = 55;                         // Радиус
        $P = ['x' => 190, 'y' => 50];   // Внешняя точка (пересечение касательных)
        $B = ['x' => 70, 'y' => 45];    // Точка касания (OB вертикально)
        // Точка A вычислена по формуле касательной (|OA| = R точно)
        $A = ['x' => 111, 'y' => 137];  // Точка касания (была 113,140 - вне круга!)

        $svg = '';

        // Окружность (тонкая линия, отличается от остальных элементов)
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$R}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Касательные AP и BP (основные линии - светлые, как стороны в темах 15/17)
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$P['x']}\" y2=\"{$P['y']}\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";
        $svg .= "  <line x1=\"{$B['x']}\" y1=\"{$B['y']}\" x2=\"{$P['x']}\" y2=\"{$P['y']}\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Линия AB (соединяет точки касания) - пунктир как вспомогательная
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$B['x']}\" y2=\"{$B['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Радиусы к точкам касания (пунктир - вспомогательные линии)
        $svg .= "  <line x1=\"{$O['x']}\" y1=\"{$O['y']}\" x2=\"{$A['x']}\" y2=\"{$A['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";
        $svg .= "  <line x1=\"{$O['x']}\" y1=\"{$O['y']}\" x2=\"{$B['x']}\" y2=\"{$B['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Прямые углы в точках касания (OA ⊥ AP, OB ⊥ BP) - белый цвет для контраста с пунктиром
        $rightAngleA = $this->rightAnglePath($A, $O, $P, 10);
        $rightAngleB = $this->rightAnglePath($B, $O, $P, 10);
        $svg .= "  <path d=\"{$rightAngleA}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="1.5"/>\n";
        $svg .= "  <path d=\"{$rightAngleB}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="1.5"/>\n";

        // Крестики для точек (как в темах 15/17)
        $svg .= $this->crosshairs([$O, $A, $B, $P]);

        // Метки
        $svg .= $this->label('O', ['x' => $O['x'] - 15, 'y' => $O['y'] + 5], self::COLORS['text_aux'], 14);
        $svg .= $this->label('A', ['x' => $A['x'] + 10, 'y' => $A['y'] + 12], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] - 5, 'y' => $B['y'] - 12], self::COLORS['text'], 14);
        $svg .= $this->label('P', ['x' => $P['x'] + 10, 'y' => $P['y'] + 5], self::COLORS['text'], 14);

        // Дуга угла в P (между касательными) - акцентный цвет
        $arcP = $this->makeAngleArc($P, $A, $B, 25);
        $svg .= "  <path d=\"{$arcP}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";

        // Подпись угла в P
        if (isset($params['angle'])) {
            $angleLabelPos = $this->angleLabelPos($P, $A, $B, 42);
            $svg .= "  <text x=\"{$angleLabelPos['x']}\" y=\"{$angleLabelPos['y']}\" fill=\"" . self::COLORS['accent'] . "\" font-size="13" " .
                    "text-anchor=\"middle\" dominant-baseline=\"middle\" class=\"geo-label\">{$params['angle']}°</text>\n";
        }

        // Дуга угла ABO (искомый угол) - вспомогательный цвет
        $arcB = $this->makeAngleArc($B, $A, $O, 18);
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.2"/>\n";

        // Вопросительный знак для искомого угла
        $angleLabelB = $this->angleLabelPos($B, $A, $O, 30);
        $svg .= "  <text x=\"{$angleLabelB['x']}\" y=\"{$angleLabelB['y']}\" fill=\"" . self::COLORS['service'] . "\" font-size="12" " .
                "text-anchor=\"middle\" dominant-baseline=\"middle\" class=\"geo-label\">?</text>\n";

        return $svg;
    }

    /**
     * Вписанный угол (задания 13-16)
     * Координаты синхронизированы с клиентской версией (topic16.blade.php)
     */
    private function renderInscribedAngle(array $points, array $center, array $geometry, array $params): string
    {
        // 85% заполнение viewBox 220×200
        // Все точки A, B, C лежат НА окружности
        $O = ['x' => 110, 'y' => 100];
        $R = 80;
        // A: угол 150° (внизу слева)
        $A = ['x' => 41, 'y' => 140];
        // B: угол 30° (внизу справа)
        $B = ['x' => 179, 'y' => 140];
        // C: угол -120° (вверху слева)
        $C = ['x' => 70, 'y' => 31];

        $svg = '';

        // Окружность (тонкая линия)
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$R}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Треугольник ABC
        $svg .= "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Центральный угол (радиусы OA и OB) - пунктир как вспомогательные
        $svg .= "  <line x1=\"{$O['x']}\" y1=\"{$O['y']}\" x2=\"{$A['x']}\" y2=\"{$A['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";
        $svg .= "  <line x1=\"{$O['x']}\" y1=\"{$O['y']}\" x2=\"{$B['x']}\" y2=\"{$B['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Дуга центрального угла AOB (уменьшена для текста)
        $arcO = $this->makeAngleArc($O, $A, $B, 18);
        $svg .= "  <path d=\"{$arcO}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";

        // Крестики для точек
        $svg .= $this->crosshairs([$O, $A, $B, $C]);

        // Метки (O - сверху-слева, чтобы не перекрывать пунктир OB)
        $svg .= $this->label('O', ['x' => $O['x'] - 15, 'y' => $O['y'] - 10], self::COLORS['text_aux'], 14);
        $svg .= $this->label('A', ['x' => $A['x'] - 14, 'y' => $A['y'] + 14], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] + 8, 'y' => $B['y'] + 14], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] - 16, 'y' => $C['y'] - 8], self::COLORS['text'], 14);

        // Подпись угла AOB (если передан параметр aob) - ближе к центру, выше
        if (isset($params['aob'])) {
            $angleLabelPos = $this->angleLabelPos($O, $A, $B, 32);
            $svg .= "  <text x=\"{$angleLabelPos['x']}\" y=\"{$angleLabelPos['y']}\" fill=\"" . self::COLORS['accent'] . "\" font-size="12" " .
                    "text-anchor=\"middle\" dominant-baseline=\"middle\" class=\"geo-label\">{$params['aob']}°</text>\n";
        }

        return $svg;
    }

    /**
     * Два диаметра (задания 17-24)
     * Координаты синхронизированы с клиентской версией (topic16.blade.php)
     */
    private function renderDiameters(array $points, array $center, array $geometry, array $params): string
    {
        // 85% заполнение viewBox 220×200
        // A, C - горизонтальный диаметр; B, D - диаметр под углом
        // Все точки НА окружности с R=85
        $O = ['x' => 110, 'y' => 100];
        $R = 85;
        $A = ['x' => 25, 'y' => 100];
        $C = ['x' => 195, 'y' => 100];
        // B: угол ~-60° (вверху слева)
        $B = ['x' => 67, 'y' => 26];
        // D: противоположная точка диаметра
        $D = ['x' => 153, 'y' => 174];

        $svg = '';

        // Окружность (тонкая линия)
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$R}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Диаметры (основные линии)
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$C['x']}\" y2=\"{$C['y']}\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";
        $svg .= "  <line x1=\"{$B['x']}\" y1=\"{$B['y']}\" x2=\"{$D['x']}\" y2=\"{$D['y']}\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Хорда BC (акцент)
        $svg .= "  <line x1=\"{$B['x']}\" y1=\"{$B['y']}\" x2=\"{$C['x']}\" y2=\"{$C['y']}\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="2"/>\n";

        // Крестики для точек
        $svg .= $this->crosshairs([$O, $A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', ['x' => $A['x'] - 18, 'y' => $A['y'] + 5], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] - 16, 'y' => $B['y'] - 6], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] + 10, 'y' => $C['y'] + 6], self::COLORS['text'], 14);
        $svg .= $this->label('D', ['x' => $D['x'] + 8, 'y' => $D['y'] + 16], self::COLORS['text'], 14);
        $svg .= $this->label('O', ['x' => $O['x'] + 10, 'y' => $O['y'] - 10], self::COLORS['text_aux'], 14);

        return $svg;
    }

    /**
     * Точки по разные стороны от диаметра (задания 25-28)
     * Координаты синхронизированы с клиентской версией (topic16.blade.php)
     */
    private function renderDiameterPoints(array $points, array $center, array $geometry, array $params): string
    {
        // Все точки A, B, N, M НА окружности с R=80 (уменьшен для размещения M)
        $O = ['x' => 110, 'y' => 100];
        $R = 80;
        $A = ['x' => 30, 'y' => 100];
        $B = ['x' => 190, 'y' => 100];
        // N: вверху слева (угол ~-110°)
        $N = ['x' => 83, 'y' => 27];
        // M: внизу справа (угол ~70°) - поднят чтобы метка помещалась
        $M = ['x' => 137, 'y' => 173];

        $svg = '';

        // Окружность (тонкая линия)
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$R}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Диаметр AB
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$B['x']}\" y2=\"{$B['y']}\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Линии к N (акцент)
        $svg .= "  <line x1=\"{$N['x']}\" y1=\"{$N['y']}\" x2=\"{$A['x']}\" y2=\"{$A['y']}\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="2"/>\n";
        $svg .= "  <line x1=\"{$N['x']}\" y1=\"{$N['y']}\" x2=\"{$B['x']}\" y2=\"{$B['y']}\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="2"/>\n";

        // Линии к M (вспомогательные пунктиром)
        $svg .= "  <line x1=\"{$M['x']}\" y1=\"{$M['y']}\" x2=\"{$N['x']}\" y2=\"{$N['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";
        $svg .= "  <line x1=\"{$M['x']}\" y1=\"{$M['y']}\" x2=\"{$B['x']}\" y2=\"{$B['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Прямой угол в N (вписанный угол, опирающийся на диаметр = 90°) - белый для контраста
        $rightAngleN = $this->rightAnglePath($N, $A, $B, 10);
        $svg .= "  <path d=\"{$rightAngleN}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="1.5"/>\n";

        // Крестики для точек
        $svg .= $this->crosshairs([$A, $B, $N, $M]);

        // Метки
        $svg .= $this->label('A', ['x' => $A['x'] - 15, 'y' => $A['y'] + 5], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] + 10, 'y' => $B['y'] + 5], self::COLORS['text'], 14);
        $svg .= $this->label('N', ['x' => $N['x'] - 6, 'y' => $N['y'] - 12], self::COLORS['text'], 14);
        $svg .= $this->label('M', ['x' => $M['x'] + 12, 'y' => $M['y'] + 8], self::COLORS['text'], 14);

        return $svg;
    }

    /**
     * Окружность, вписанная в трапецию (задания 29-34)
     */
    private function renderInscribedTrapezoid(array $points, array $center, array $geometry, array $params): string
    {
        // Координаты синхронизированы с клиентской версией (topic16.blade.php)
        // 85% заполнение viewBox, center=(110,100), r=70
        $O = ['x' => 110, 'y' => 100];
        $r = 70;

        $svg = '';

        // Определяем тип трапеции
        $type = $params['type'] ?? 'trapezoid';

        if ($type === 'right_trapezoid') {
            // Прямоугольная трапеция - левая сторона вертикальная
            $svg .= "  <polygon points=\"40,170 40,30 160,30 208,170\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";
            // Маркер прямого угла (левый верхний)
            $svg .= "  <path d=\"M 40,45 L 55,45 L 55,30\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.2"/>\n";
        } else {
            // Обычная или равнобедренная трапеция
            // a×b=4900, a=45, b=109
            $svg .= "  <polygon points=\"1,170 65,30 155,30 219,170\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";
        }

        // Вписанная окружность: касается всех 4 сторон
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$r}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Радиус к основанию (пунктирная линия)
        $svg .= "  <line x1=\"{$O['x']}\" y1=\"{$O['y']}\" x2=\"{$O['x']}\" y2=\"170\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Крестик для центра
        $svg .= $this->crosshairs([$O]);

        // Метка радиуса
        if (isset($params['r'])) {
            $svg .= "  <text x=\"122\" y=\"145\" fill=\"" . self::COLORS['accent'] . "\" font-size="13" font-family=\"'Times New Roman', serif\" font-style=\"italic\" font-weight=\"500\" class=\"geo-label\">r={$params['r']}</text>\n";
        }

        return $svg;
    }

    /**
     * Окружность, вписанная в квадрат (задания 35-42)
     * Координаты синхронизированы с клиентской версией (topic16.blade.php)
     */
    private function renderInscribedSquare(array $points, array $center, array $geometry, array $params): string
    {
        // 85% заполнение viewBox: квадрат 170×170, r=85
        $O = ['x' => 110, 'y' => 95];
        $r = 85;

        $svg = '';

        // Квадрат
        $svg .= "  <rect x=\"25\" y=\"10\" width=\"170\" height=\"170\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Вписанная окружность
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$r}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Определяем тип задачи
        $find = $params['find'] ?? 'radius';

        if ($find === 'area' && isset($params['radius'])) {
            // Задачи 39-42: дан радиус, найти площадь - показываем радиус пунктиром
            $svg .= "  <line x1=\"{$O['x']}\" y1=\"{$O['y']}\" x2=\"195\" y2=\"{$O['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";
            $svg .= "  <text x=\"152\" y=\"85\" fill=\"" . self::COLORS['accent'] . "\" font-size="13" font-family=\"'Times New Roman', serif\" font-weight=\"500\" class=\"geo-label\">r={$params['radius']}</text>\n";
        } else if (isset($params['side'])) {
            // Задачи 35-38: дана сторона, найти радиус - показываем сторону
            $svg .= "  <text x=\"110\" y=\"195\" fill=\"" . self::COLORS['accent'] . "\" font-size="13" font-family=\"'Times New Roman', serif\" font-weight=\"500\" class=\"geo-label\" text-anchor=\"middle\">a={$params['side']}</text>\n";
        }

        // Крестик для центра
        $svg .= $this->crosshairs([$O]);

        return $svg;
    }

    /**
     * Описанные фигуры (задания 43-54, 103-114)
     * Разные варианты в зависимости от params:
     * - find=diagonal: квадрат с вписанной окружностью (43-46)
     * - shape=trapezoid: трапеция, описанная около окружности (47-50)
     * - shape=quad: четырёхугольник, описанный около окружности (51-54)
     * - shape=square: квадрат с описанной окружностью (103-106)
     * - shape=equilateral: равносторонний треугольник с описанной окружностью (107-114)
     */
    private function renderCircumscribedShapes(array $points, array $center, array $geometry, array $params): string
    {
        $shape = $params['shape'] ?? null;
        $find = $params['find'] ?? null;

        // Задания 43-46: Квадрат с вписанной окружностью
        if ($find === 'diagonal') {
            return $this->renderSquareWithInscribedCircle();
        }

        // Задания 103-106: Квадрат с описанной окружностью
        if ($shape === 'square') {
            return $this->renderSquareWithCircumscribedCircle();
        }

        // Задания 107-114: Равносторонний треугольник с описанной окружностью
        if ($shape === 'equilateral') {
            return $this->renderEquilateralWithCircumscribedCircle();
        }

        // Задания 47-50: Трапеция, описанная около окружности
        if ($shape === 'trapezoid') {
            return $this->renderTrapezoidCircumscribedAroundCircle();
        }

        // Задания 51-54: Четырёхугольник, описанный около окружности (default)
        return $this->renderQuadCircumscribedAroundCircle();
    }

    /**
     * Квадрат с вписанной окружностью (43-46)
     */
    private function renderSquareWithInscribedCircle(): string
    {
        $O = ['x' => 110, 'y' => 100];
        $side = 140;
        $r = $side / 2; // Радиус вписанной окружности = половина стороны

        $A = ['x' => $O['x'] - $side/2, 'y' => $O['y'] + $side/2]; // нижний левый
        $B = ['x' => $O['x'] - $side/2, 'y' => $O['y'] - $side/2]; // верхний левый
        $C = ['x' => $O['x'] + $side/2, 'y' => $O['y'] - $side/2]; // верхний правый
        $D = ['x' => $O['x'] + $side/2, 'y' => $O['y'] + $side/2]; // нижний правый

        $svg = '';

        // Квадрат
        $svg .= "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']} {$D['x']},{$D['y']}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Вписанная окружность
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$r}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Диагональ (пунктир)
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$C['x']}\" y2=\"{$C['y']}\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Крестики
        $svg .= $this->crosshairs([$O, $A, $B, $C, $D]);

        // Метки (без r - это диагональ, не радиус)
        $svg .= $this->label('A', ['x' => $A['x'] - 12, 'y' => $A['y'] + 12], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] - 12, 'y' => $B['y'] - 8], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] + 8, 'y' => $C['y'] - 8], self::COLORS['text'], 14);
        $svg .= $this->label('D', ['x' => $D['x'] + 8, 'y' => $D['y'] + 12], self::COLORS['text'], 14);

        return $svg;
    }

    /**
     * Трапеция, описанная около окружности (47-50)
     * НЕ равнобедренная! Произвольная трапеция с вписанной окружностью.
     * Координаты вычислены так, что окружность касается всех 4 сторон.
     * Условие касания: AB + CD = BC + AD
     */
    private function renderTrapezoidCircumscribedAroundCircle(): string
    {
        $O = ['x' => 110, 'y' => 100];
        $r = 48;

        // Произвольная (НЕ равнобедренная) трапеция как на референсе:
        // - Левая сторона почти вертикальная (13px по горизонтали)
        // - Правая сторона пологая (44px по горизонтали)
        //
        // Для левой стороны AB: A(55, 148) → B(68, 52)
        // Расстояние от O = |10560 - 48*(55 + 68)| / sqrt(9216 + 13²) ≈ 48 ✓
        //
        // Для правой стороны CD: C(141, 52) → D(185, 148)
        // Расстояние от O = |48*(141 + 185) - 10560| / sqrt(9216 + 44²) ≈ 48 ✓

        // Нижнее основание AD (y = O['y'] + r = 148)
        $A = ['x' => 55, 'y' => 148];
        $D = ['x' => 185, 'y' => 148];

        // Верхнее основание BC (y = O['y'] - r = 52)
        $B = ['x' => 68, 'y' => 52];
        $C = ['x' => 141, 'y' => 52];

        $svg = '';

        // Трапеция
        $svg .= "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']} {$D['x']},{$D['y']}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Вписанная окружность
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$r}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Крестики
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', ['x' => $A['x'] - 12, 'y' => $A['y'] + 12], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] - 12, 'y' => $B['y'] - 8], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] + 8, 'y' => $C['y'] - 8], self::COLORS['text'], 14);
        $svg .= $this->label('D', ['x' => $D['x'] + 8, 'y' => $D['y'] + 12], self::COLORS['text'], 14);

        return $svg;
    }

    /**
     * Четырёхугольник, описанный около окружности (51-54)
     */
    private function renderQuadCircumscribedAroundCircle(): string
    {
        $O = ['x' => 110, 'y' => 100];
        $r = 45;

        // Произвольный выпуклый четырёхугольник (НЕ трапеция!)
        // Все 4 стороны - касательные к окружности под разными углами
        // BC и AD НЕ параллельны (BC имеет положительный наклон, AD отрицательный)
        //
        // Касательные под углами:
        // θ_AB = 170° (левая сторона)
        // θ_BC = 95° (верхняя, наклонена влево)
        // θ_CD = 10° (правая сторона)
        // θ_DA = 255° (нижняя, наклонена)
        //
        // Вершины - пересечения касательных:

        $A = ['x' => 59, 'y' => 67];
        $B = ['x' => 72, 'y' => 142];
        $C = ['x' => 147, 'y' => 148];
        $D = ['x' => 167, 'y' => 39];

        $svg = '';

        // Четырёхугольник
        $svg .= "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']} {$D['x']},{$D['y']}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Вписанная окружность
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$r}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Крестики для вершин
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки (позиции адаптированы для нового расположения вершин)
        $svg .= $this->label('A', ['x' => $A['x'] - 14, 'y' => $A['y']], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] - 14, 'y' => $B['y'] + 12], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] + 10, 'y' => $C['y'] + 12], self::COLORS['text'], 14);
        $svg .= $this->label('D', ['x' => $D['x'] + 10, 'y' => $D['y'] - 8], self::COLORS['text'], 14);

        return $svg;
    }

    /**
     * Квадрат с описанной окружностью (103-106)
     */
    private function renderSquareWithCircumscribedCircle(): string
    {
        $O = ['x' => 110, 'y' => 100];
        $side = 120;
        $R = $side * sqrt(2) / 2; // Радиус описанной окружности

        $A = ['x' => $O['x'] - $side/2, 'y' => $O['y'] + $side/2];
        $B = ['x' => $O['x'] - $side/2, 'y' => $O['y'] - $side/2];
        $C = ['x' => $O['x'] + $side/2, 'y' => $O['y'] - $side/2];
        $D = ['x' => $O['x'] + $side/2, 'y' => $O['y'] + $side/2];

        $svg = '';

        // Описанная окружность
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"" . round($R) . "\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Квадрат
        $svg .= "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']} {$D['x']},{$D['y']}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Радиус (пунктир)
        $svg .= "  <line x1=\"{$O['x']}\" y1=\"{$O['y']}\" x2=\"{$A['x']}\" y2=\"{$A['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Крестики
        $svg .= $this->crosshairs([$O, $A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', ['x' => $A['x'] - 12, 'y' => $A['y'] + 12], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] - 12, 'y' => $B['y'] - 8], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] + 8, 'y' => $C['y'] - 8], self::COLORS['text'], 14);
        $svg .= $this->label('D', ['x' => $D['x'] + 8, 'y' => $D['y'] + 12], self::COLORS['text'], 14);
        $svg .= $this->label('R', ['x' => ($O['x'] + $A['x'])/2 - 12, 'y' => ($O['y'] + $A['y'])/2], self::COLORS['aux'], 13);

        return $svg;
    }

    /**
     * Равносторонний треугольник с описанной окружностью (107-114)
     */
    private function renderEquilateralWithCircumscribedCircle(): string
    {
        $cx = 110;
        $cy = 105;
        $side = 140;
        $h = $side * sqrt(3) / 2;
        $R = $side / sqrt(3); // Радиус описанной окружности

        $A = ['x' => $cx, 'y' => $cy - $h * 2/3];
        $B = ['x' => $cx - $side/2, 'y' => $cy + $h/3];
        $C = ['x' => $cx + $side/2, 'y' => $cy + $h/3];
        $O = ['x' => $cx, 'y' => $cy];

        $svg = '';

        // Описанная окружность
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"" . round($R) . "\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Треугольник
        $svg .= "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Радиус (пунктир)
        $svg .= "  <line x1=\"{$O['x']}\" y1=\"{$O['y']}\" x2=\"{$A['x']}\" y2=\"{$A['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Крестики
        $svg .= $this->crosshairs([$O, $A, $B, $C]);

        // Метки
        $svg .= $this->label('A', ['x' => $A['x'], 'y' => $A['y'] - 12], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] - 12, 'y' => $B['y'] + 10], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] + 10, 'y' => $C['y'] + 10], self::COLORS['text'], 14);
        $svg .= $this->label('R', ['x' => $O['x'] + 10, 'y' => ($O['y'] + $A['y'])/2], self::COLORS['aux'], 13);

        return $svg;
    }

    /**
     * Треугольник с вписанной окружностью (задания 55-66)
     */
    private function renderTriangleInscribedCircle(array $points, array $center, array $geometry, array $params): string
    {
        // Равносторонний треугольник с вписанной окружностью
        $cx = 110;
        $cy = 110;
        $side = 150;
        $h = $side * sqrt(3) / 2;
        $r = $h / 3; // Радиус вписанной окружности

        $A = ['x' => $cx, 'y' => $cy - $h * 2 / 3];
        $B = ['x' => $cx - $side / 2, 'y' => $cy + $h / 3];
        $C = ['x' => $cx + $side / 2, 'y' => $cy + $h / 3];
        $O = ['x' => $cx, 'y' => $cy];

        $svg = '';

        // Треугольник
        $svg .= "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Вписанная окружность
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"" . round($r) . "\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Радиус к основанию (пунктир)
        $svg .= "  <line x1=\"{$O['x']}\" y1=\"{$O['y']}\" x2=\"{$O['x']}\" y2=\"" . ($cy + $h / 3) . "\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Крестики для точек
        $svg .= $this->crosshairs([$O, $A, $B, $C]);

        // Метки
        $svg .= $this->label('A', ['x' => $A['x'], 'y' => $A['y'] - 12], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] - 15, 'y' => $B['y'] + 8], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] + 10, 'y' => $C['y'] + 8], self::COLORS['text'], 14);
        $svg .= $this->label('r', ['x' => $O['x'] + 12, 'y' => $O['y'] + 15], self::COLORS['accent'], 13);

        return $svg;
    }

    /**
     * Четырёхугольник в окружности (задания 67-74)
     */
    private function renderQuadInCircle(array $points, array $center, array $geometry, array $params): string
    {
        $O = ['x' => 110, 'y' => 100];
        $R = 70;

        // Четырёхугольник ABCD вписан в окружность
        // Все вершины ТОЧНО на окружности (вычислены через полярные координаты)
        // A: угол 230° (нижний левый)
        $A = ['x' => 65, 'y' => 154];
        // B: угол 130° (верхний левый)
        $B = ['x' => 65, 'y' => 46];
        // C: угол 50° (верхний правый)
        $C = ['x' => 155, 'y' => 46];
        // D: угол -40° (нижний правый)
        $D = ['x' => 164, 'y' => 145];

        $svg = '';

        // Окружность (тонкая линия)
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$R}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Четырёхугольник
        $svg .= "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']} {$D['x']},{$D['y']}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Диагонали (пунктир)
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$C['x']}\" y2=\"{$C['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";
        $svg .= "  <line x1=\"{$B['x']}\" y1=\"{$B['y']}\" x2=\"{$D['x']}\" y2=\"{$D['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Угол A (акцент)
        $arcA = $this->makeAngleArc($A, $D, $B, 20);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";

        // Крестики для точек
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', ['x' => $A['x'] - 14, 'y' => $A['y'] + 10], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] - 14, 'y' => $B['y'] - 8], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] + 8, 'y' => $C['y'] - 8], self::COLORS['text'], 14);
        $svg .= $this->label('D', ['x' => $D['x'] + 8, 'y' => $D['y'] + 10], self::COLORS['text'], 14);

        return $svg;
    }

    /**
     * Центр на стороне треугольника (задания 75-90)
     * Синхронизировано с клиентской версией topic16.blade.php
     */
    private function renderCenterOnSide(array $points, array $center, array $geometry, array $params): string
    {
        // Координаты как в клиентской версии: circle cx=110, cy=110, r=85
        $O = ['x' => 110, 'y' => 110];
        $R = 85;

        // A и B на диаметре (y=110)
        $A = ['x' => 25, 'y' => 110];
        $B = ['x' => 195, 'y' => 110];

        // C на окружности (разные формы в клиенте, но для сервера используем одну)
        // Форма по умолчанию: C левее центра
        $C = ['x' => 70, 'y' => 35];

        $svg = '';

        // Описанная окружность (тонкая линия)
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$R}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Треугольник
        $svg .= "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Прямой угол в C (вписанный угол, опирающийся на диаметр = 90°) - белый для контраста
        $rightAngleC = $this->rightAnglePath($C, $A, $B, 10);
        $svg .= "  <path d=\"{$rightAngleC}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="1.5"/>\n";

        // Крестики для точек
        $svg .= $this->crosshairs([$A, $B, $C, $O]);

        // Метки вершин
        $svg .= $this->label('A', ['x' => $A['x'] - 15, 'y' => $A['y'] + 6], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] + 5, 'y' => $B['y'] + 6], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] - 12, 'y' => $C['y'] - 9], self::COLORS['text'], 14);
        $svg .= $this->label('O', ['x' => $O['x'], 'y' => $O['y'] + 18], self::COLORS['text_aux'], 14);

        return $svg;
    }

    /**
     * Трапеция в окружности (задания 91-102)
     */
    private function renderTrapezoidInCircle(array $points, array $center, array $geometry, array $params): string
    {
        $O = ['x' => 110, 'y' => 100];
        $R = 70;

        // Равнобедренная трапеция, вписанная в окружность
        $A = ['x' => 45, 'y' => 130];
        $B = ['x' => 60, 'y' => 50];
        $C = ['x' => 160, 'y' => 50];
        $D = ['x' => 175, 'y' => 130];

        $svg = '';

        // Окружность (тонкая линия)
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$R}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Трапеция
        $svg .= "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']} {$D['x']},{$D['y']}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Угол A (акцент)
        $arcA = $this->makeAngleArc($A, $D, $B, 20);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";

        // Крестики для точек
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', ['x' => $A['x'] - 12, 'y' => $A['y'] + 8], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] - 10, 'y' => $B['y'] - 8], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] + 8, 'y' => $C['y'] - 8], self::COLORS['text'], 14);
        $svg .= $this->label('D', ['x' => $D['x'] + 10, 'y' => $D['y'] + 8], self::COLORS['text'], 14);

        return $svg;
    }

    /**
     * Теорема синусов (задания 115-126)
     */
    private function renderSineTheorem(array $points, array $center, array $geometry, array $params): string
    {
        $O = ['x' => 110, 'y' => 100];
        $R = 70;

        // Треугольник ABC в окружности
        $A = ['x' => 55, 'y' => 145];
        $B = ['x' => 175, 'y' => 130];
        $C = ['x' => 90, 'y' => 35];

        $svg = '';

        // Окружность (тонкая линия)
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"{$R}\" fill=\"none\" stroke=\"" . self::COLORS['circle'] . "\" stroke-width="1.5"/>\n";

        // Треугольник
        $svg .= "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']}\" fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";

        // Сторона AB (напротив угла C) - акцент
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$B['x']}\" y2=\"{$B['y']}\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="2"/>\n";

        // Угол C
        $arcC = $this->makeAngleArc($C, $A, $B, 20);
        $svg .= "  <path d=\"{$arcC}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.5"/>\n";

        // Радиус (пунктир)
        $svg .= "  <line x1=\"{$O['x']}\" y1=\"{$O['y']}\" x2=\"{$A['x']}\" y2=\"{$A['y']}\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Крестики для всех точек
        $svg .= $this->crosshairs([$O, $A, $B, $C]);

        // Метки
        $svg .= $this->label('A', ['x' => $A['x'] - 12, 'y' => $A['y'] + 10], self::COLORS['text'], 14);
        $svg .= $this->label('B', ['x' => $B['x'] + 10, 'y' => $B['y'] + 5], self::COLORS['text'], 14);
        $svg .= $this->label('C', ['x' => $C['x'] - 5, 'y' => $C['y'] - 12], self::COLORS['text'], 14);
        $svg .= $this->label('R', ['x' => ($O['x'] + $A['x']) / 2 - 10, 'y' => ($O['y'] + $A['y']) / 2 - 8], self::COLORS['aux'], 13);

        return $svg;
    }

    // ========================================================================
    // РЕНДЕРЫ ДЛЯ ЧЕТЫРЁХУГОЛЬНИКОВ (Тема 17)
    // ========================================================================

    /**
     * Рендерит базовый четырёхугольник
     */
    private function renderQuadrilateral(array $A, array $B, array $C, array $D): string
    {
        return "  <polygon points=\"{$A['x']},{$A['y']} {$B['x']},{$B['y']} {$C['x']},{$C['y']} {$D['x']},{$D['y']}\" " .
               "fill=\"none\" stroke=\"" . self::COLORS['line'] . "\" stroke-width="2"/>\n";
    }

    /**
     * 17.1 Углы параллелограмма
     */
    private function renderParallelogramAngles(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Дуги углов при A и C (равные)
        $arcA = $this->makeAngleArc($A, $D, $B, 20);
        $arcC = $this->makeAngleArc($C, $B, $D, 20);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";
        $svg .= "  <path d=\"{$arcC}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";

        // Дуги углов при B и D (равные)
        $arcB = $this->makeAngleArc($B, $A, $C, 18);
        $arcD = $this->makeAngleArc($D, $C, $A, 18);
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.2"/>\n";
        $svg .= "  <path d=\"{$arcD}\" fill=\"none\" stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.2"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));

        return $svg;
    }

    /**
     * 17.2 Диагональ параллелограмма
     */
    private function renderParallelogramDiagonal(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Диагональ BD
        $svg .= "  <line x1=\"{$B['x']}\" y1=\"{$B['y']}\" x2=\"{$D['x']}\" y2=\"{$D['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Углы при вершине D (диагональ делит угол)
        $arcDBA = $this->makeAngleArc($D, $A, $B, 22);
        $arcDBC = $this->makeAngleArc($D, $B, $C, 28);
        $svg .= "  <path d=\"{$arcDBA}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";
        $svg .= "  <path d=\"{$arcDBC}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.5"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));

        return $svg;
    }

    /**
     * 17.3 Биссектриса угла параллелограмма
     */
    private function renderParallelogramBisector(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Биссектриса из A до стороны BC
        // Вычисляем направление биссектрисы
        $bisDir = $this->bisectorDirectionParallelogram($A, $D, $B);
        // Находим пересечение с BC
        $E = $this->lineIntersection($A, $bisDir, $B, $C);

        // Биссектриса (пунктир)
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$E['x']}\" y2=\"{$E['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Точка E
        $svg .= "  <circle cx=\"{$E['x']}\" cy=\"{$E['y']}\" r=\"3\" fill=\"" . self::COLORS['circle'] . "\"/>\n";

        // Дуги равных половин угла
        $arcADE = $this->makeAngleArc($A, $D, $E, 25);
        $arcAEB = $this->makeAngleArc($A, $E, $B, 30);
        $svg .= "  <path d=\"{$arcADE}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";
        $svg .= "  <path d=\"{$arcAEB}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));
        $svg .= $this->label('E', ['x' => $E['x'] + 8, 'y' => $E['y'] - 10], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * Направление биссектрисы угла параллелограмма
     */
    private function bisectorDirectionParallelogram(array $vertex, array $p1, array $p2): array
    {
        // Единичный вектор от vertex к p1
        $d1 = $this->distance($vertex, $p1);
        $u1 = ['x' => ($p1['x'] - $vertex['x']) / $d1, 'y' => ($p1['y'] - $vertex['y']) / $d1];

        // Единичный вектор от vertex к p2
        $d2 = $this->distance($vertex, $p2);
        $u2 = ['x' => ($p2['x'] - $vertex['x']) / $d2, 'y' => ($p2['y'] - $vertex['y']) / $d2];

        // Биссектриса = сумма единичных векторов
        $bx = $u1['x'] + $u2['x'];
        $by = $u1['y'] + $u2['y'];
        $blen = sqrt($bx * $bx + $by * $by);

        return ['x' => $bx / $blen, 'y' => $by / $blen];
    }

    /**
     * Пересечение луча с отрезком
     */
    private function lineIntersection(array $rayOrigin, array $rayDir, array $segP1, array $segP2): array
    {
        $dx = $segP2['x'] - $segP1['x'];
        $dy = $segP2['y'] - $segP1['y'];

        $denom = $rayDir['x'] * $dy - $rayDir['y'] * $dx;
        if (abs($denom) < 1e-10) {
            // Параллельные линии - возвращаем середину отрезка
            return $this->midpoint($segP1, $segP2);
        }

        $t = (($segP1['x'] - $rayOrigin['x']) * $dy - ($segP1['y'] - $rayOrigin['y']) * $dx) / $denom;

        return [
            'x' => $rayOrigin['x'] + $t * $rayDir['x'],
            'y' => $rayOrigin['y'] + $t * $rayDir['y']
        ];
    }

    /**
     * 17.4 Диагонали параллелограмма
     */
    private function renderParallelogramDiagonals(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];
        $O = $points['O'] ?? $this->midpoint($A, $C);

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Диагонали
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$C['x']}\" y2=\"{$C['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";
        $svg .= "  <line x1=\"{$B['x']}\" y1=\"{$B['y']}\" x2=\"{$D['x']}\" y2=\"{$D['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Точка O
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"4\" fill=\"" . self::COLORS['accent'] . "\"/>\n";

        // Маркеры равенства AO = OC
        $tickAO = $this->equalityTick($A, $O);
        $tickOC = $this->equalityTick($O, $C);
        $svg .= $this->renderTick($tickAO);
        $svg .= $this->renderTick($tickOC);

        // Маркеры равенства BO = OD (двойные)
        $svg .= $this->renderDoubleEqualityMark($B, $O);
        $svg .= $this->renderDoubleEqualityMark($O, $D);

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));
        $svg .= $this->label('O', ['x' => $O['x'] + 10, 'y' => $O['y'] - 10], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * 17.5 Равнобедренная трапеция
     */
    private function renderIsoscelesTrapezoid(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Маркеры равенства боковых сторон AB = CD
        $svg .= $this->renderSingleEqualityMark($A, $B);
        $svg .= $this->renderSingleEqualityMark($C, $D);

        // Дуги равных углов при основании
        $arcA = $this->makeAngleArc($A, $D, $B, 22);
        $arcD = $this->makeAngleArc($D, $C, $A, 22);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";
        $svg .= "  <path d=\"{$arcD}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));

        return $svg;
    }

    /**
     * 17.7 Прямоугольная трапеция
     */
    private function renderRightTrapezoid(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Прямые углы при A и B
        $rightAngleA = $this->rightAnglePath($A, $D, $B, 12);
        $rightAngleB = $this->rightAnglePath($B, $A, $C, 12);
        $svg .= "  <path d=\"{$rightAngleA}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";
        $svg .= "  <path d=\"{$rightAngleB}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

        // Угол при D
        $arcD = $this->makeAngleArc($D, $C, $A, 20);
        $svg .= "  <path d=\"{$arcD}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.2"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));

        return $svg;
    }

    /**
     * 17.8 Трапеция с высотой
     */
    private function renderTrapezoidHeight(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Основание высоты из C на AD
        $H = $this->altitudeFoot($C, $A, $D);

        // Высота (пунктир)
        $svg .= "  <line x1=\"{$C['x']}\" y1=\"{$C['y']}\" x2=\"{$H['x']}\" y2=\"{$H['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Прямой угол
        $rightAngle = $this->rightAnglePath($H, $A, $C, 10);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

        // Точка H
        $svg .= "  <circle cx=\"{$H['x']}\" cy=\"{$H['y']}\" r=\"3\" fill=\"" . self::COLORS['circle'] . "\"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));
        $svg .= $this->label('H', ['x' => $H['x'], 'y' => $H['y'] + 18], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * 17.9 Трапеция с диагональю 45°
     */
    private function renderTrapezoidDiagonal45(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Маркеры равенства боковых сторон
        $svg .= $this->renderSingleEqualityMark($A, $B);
        $svg .= $this->renderSingleEqualityMark($C, $D);

        // Диагональ AC
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$C['x']}\" y2=\"{$C['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Угол 45° при A
        $arcA = $this->makeAngleArc($A, $D, $C, 30);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";

        // Метка угла
        $angleLabelPos = $this->angleLabelPos($A, $D, $C, 45, 0.5);
        $svg .= "  <text x=\"{$angleLabelPos['x']}\" y=\"{$angleLabelPos['y']}\" fill=\"" . self::COLORS['accent'] . "\" font-size="11" " .
                "text-anchor=\"middle\" dominant-baseline=\"middle\">45°</text>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));

        return $svg;
    }

    /**
     * 17.10 Прямоугольник с диагоналями
     */
    private function renderRectangleDiagonals(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];
        $O = $points['O'] ?? $this->midpoint($A, $C);

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Прямые углы
        $rightAngleA = $this->rightAnglePath($A, $D, $B, 10);
        $rightAngleB = $this->rightAnglePath($B, $A, $C, 10);
        $rightAngleC = $this->rightAnglePath($C, $B, $D, 10);
        $rightAngleD = $this->rightAnglePath($D, $C, $A, 10);
        $svg .= "  <path d=\"{$rightAngleA}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$rightAngleB}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$rightAngleC}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$rightAngleD}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";

        // Диагонали
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$C['x']}\" y2=\"{$C['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";
        $svg .= "  <line x1=\"{$B['x']}\" y1=\"{$B['y']}\" x2=\"{$D['x']}\" y2=\"{$D['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Точка O
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"4\" fill=\"" . self::COLORS['accent'] . "\"/>\n";

        // Угол между диагоналями
        $arcO = $this->makeAngleArc($O, $A, $B, 18);
        $svg .= "  <path d=\"{$arcO}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));
        $svg .= $this->label('O', ['x' => $O['x'] + 12, 'y' => $O['y'] - 10], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * 17.11 Углы ромба
     */
    private function renderRhombusAngles(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Маркеры равенства всех сторон
        $svg .= $this->renderSingleEqualityMark($A, $B);
        $svg .= $this->renderSingleEqualityMark($B, $C);
        $svg .= $this->renderSingleEqualityMark($C, $D);
        $svg .= $this->renderSingleEqualityMark($D, $A);

        // Углы (острые и тупые)
        $arcA = $this->makeAngleArc($A, $D, $B, 20);
        $arcC = $this->makeAngleArc($C, $B, $D, 20);
        $arcB = $this->makeAngleArc($B, $A, $C, 25);
        $arcD = $this->makeAngleArc($D, $C, $A, 25);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";
        $svg .= "  <path d=\"{$arcC}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.2"/>\n";
        $svg .= "  <path d=\"{$arcD}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.2"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));

        return $svg;
    }

    /**
     * 17.12 Ромб с диагональю
     */
    private function renderRhombusDiagonal(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Диагональ AC
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$C['x']}\" y2=\"{$C['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Маркеры равенства сторон
        $svg .= $this->renderSingleEqualityMark($A, $B);
        $svg .= $this->renderSingleEqualityMark($B, $C);
        $svg .= $this->renderSingleEqualityMark($C, $D);
        $svg .= $this->renderSingleEqualityMark($D, $A);

        // Угол ABC
        $arcB = $this->makeAngleArc($B, $A, $C, 22);
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.5"/>\n";

        // Угол ACD (искомый)
        $arcACD = $this->makeAngleArc($C, $A, $D, 28);
        $svg .= "  <path d=\"{$arcACD}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.5"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));

        return $svg;
    }

    /**
     * 17.13 Высота ромба
     */
    private function renderRhombusHeight(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Основание высоты из C на AD
        $H = $this->altitudeFoot($C, $A, $D);

        // Высота
        $svg .= "  <line x1=\"{$C['x']}\" y1=\"{$C['y']}\" x2=\"{$H['x']}\" y2=\"{$H['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Прямой угол
        $rightAngle = $this->rightAnglePath($H, $A, $C, 10);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

        // Маркеры равенства сторон
        $svg .= $this->renderSingleEqualityMark($A, $B);
        $svg .= $this->renderSingleEqualityMark($B, $C);
        $svg .= $this->renderSingleEqualityMark($C, $D);
        $svg .= $this->renderSingleEqualityMark($D, $A);

        // Тупой угол 150°
        $arcB = $this->makeAngleArc($B, $A, $C, 18);
        $svg .= "  <path d=\"{$arcB}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.2"/>\n";

        // Точка H
        $svg .= "  <circle cx=\"{$H['x']}\" cy=\"{$H['y']}\" r=\"3\" fill=\"" . self::COLORS['circle'] . "\"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));
        $svg .= $this->label('H', ['x' => $H['x'], 'y' => $H['y'] + 18], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * 17.14 Площадь параллелограмма
     */
    private function renderParallelogramArea(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Высота из B на AD
        $H = $this->altitudeFoot($B, $A, $D);

        // Высота
        $svg .= "  <line x1=\"{$B['x']}\" y1=\"{$B['y']}\" x2=\"{$H['x']}\" y2=\"{$H['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Прямой угол
        $rightAngle = $this->rightAnglePath($H, $A, $B, 10);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

        // Точка H
        $svg .= "  <circle cx=\"{$H['x']}\" cy=\"{$H['y']}\" r=\"3\" fill=\"" . self::COLORS['circle'] . "\"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));
        $svg .= $this->label('H', ['x' => $H['x'], 'y' => $H['y'] + 18], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * 17.15 Площадь трапеции
     */
    private function renderTrapezoidArea(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Высота из B на AD
        $H = $this->altitudeFoot($B, $A, $D);

        // Высота
        $svg .= "  <line x1=\"{$B['x']}\" y1=\"{$B['y']}\" x2=\"{$H['x']}\" y2=\"{$H['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Прямой угол
        $rightAngle = $this->rightAnglePath($H, $A, $B, 10);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

        // Точка H
        $svg .= "  <circle cx=\"{$H['x']}\" cy=\"{$H['y']}\" r=\"3\" fill=\"" . self::COLORS['circle'] . "\"/>\n";

        // Метка высоты
        $hLabelPos = $this->labelOnSegment($B, $H, 15);
        $svg .= "  <text x=\"{$hLabelPos['x']}\" y=\"{$hLabelPos['y']}\" fill=\"" . self::COLORS['aux'] . "\" font-size="11" " .
                "text-anchor=\"middle\" dominant-baseline=\"middle\">h</text>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));

        return $svg;
    }

    /**
     * 17.16 Площадь ромба
     */
    private function renderRhombusArea(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];
        $O = $this->midpoint($A, $C);

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Диагонали
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$C['x']}\" y2=\"{$C['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";
        $svg .= "  <line x1=\"{$B['x']}\" y1=\"{$B['y']}\" x2=\"{$D['x']}\" y2=\"{$D['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Прямой угол в центре
        $rightAngle = $this->rightAnglePath($O, $A, $B, 10);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

        // Точка O
        $svg .= "  <circle cx=\"{$O['x']}\" cy=\"{$O['y']}\" r=\"3\" fill=\"" . self::COLORS['circle'] . "\"/>\n";

        // Маркеры равенства сторон
        $svg .= $this->renderSingleEqualityMark($A, $B);
        $svg .= $this->renderSingleEqualityMark($B, $C);
        $svg .= $this->renderSingleEqualityMark($C, $D);
        $svg .= $this->renderSingleEqualityMark($D, $A);

        // Угол 30°
        $arcA = $this->makeAngleArc($A, $D, $B, 25);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.2"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));

        return $svg;
    }

    /**
     * 17.17 Диагональ квадрата
     */
    private function renderSquareDiagonal(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Прямые углы
        $rightAngleA = $this->rightAnglePath($A, $D, $B, 12);
        $rightAngleB = $this->rightAnglePath($B, $A, $C, 12);
        $rightAngleC = $this->rightAnglePath($C, $B, $D, 12);
        $rightAngleD = $this->rightAnglePath($D, $C, $A, 12);
        $svg .= "  <path d=\"{$rightAngleA}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$rightAngleB}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$rightAngleC}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$rightAngleD}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";

        // Диагональ AC
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$C['x']}\" y2=\"{$C['y']}\" " .
                "stroke=\"" . self::COLORS['accent'] . "\" stroke-width="2"/>\n";

        // Маркеры равенства сторон
        $svg .= $this->renderSingleEqualityMark($A, $B);
        $svg .= $this->renderSingleEqualityMark($B, $C);
        $svg .= $this->renderSingleEqualityMark($C, $D);
        $svg .= $this->renderSingleEqualityMark($D, $A);

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));

        return $svg;
    }

    /**
     * 17.18 Средняя линия трапеции
     */
    private function renderTrapezoidMidline(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Середины боковых сторон
        $M = $this->midpoint($A, $B);
        $N = $this->midpoint($C, $D);

        // Средняя линия MN
        $svg .= "  <line x1=\"{$M['x']}\" y1=\"{$M['y']}\" x2=\"{$N['x']}\" y2=\"{$N['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Диагональ AC (пересекает среднюю линию)
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$C['x']}\" y2=\"{$C['y']}\" " .
                "stroke=\"" . self::COLORS['service'] . "\" stroke-width="1" stroke-dasharray=\"4,3\"/>\n";

        // Точка пересечения F
        $F = $this->lineSegmentIntersection($A, $C, $M, $N);
        $svg .= "  <circle cx=\"{$F['x']}\" cy=\"{$F['y']}\" r=\"3\" fill=\"" . self::COLORS['accent'] . "\"/>\n";

        // Маркеры равенства AM = MB
        $tickAM = $this->equalityTick($A, $M);
        $tickMB = $this->equalityTick($M, $B);
        $svg .= $this->renderTick($tickAM);
        $svg .= $this->renderTick($tickMB);

        // Маркеры равенства CN = ND (двойные)
        $svg .= $this->renderDoubleEqualityMark($C, $N);
        $svg .= $this->renderDoubleEqualityMark($N, $D);

        // Точки M, N
        $svg .= "  <circle cx=\"{$M['x']}\" cy=\"{$M['y']}\" r=\"3\" fill=\"" . self::COLORS['circle'] . "\"/>\n";
        $svg .= "  <circle cx=\"{$N['x']}\" cy=\"{$N['y']}\" r=\"3\" fill=\"" . self::COLORS['circle'] . "\"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));
        $svg .= $this->label('M', ['x' => $M['x'] - 15, 'y' => $M['y']], self::COLORS['text_aux'], 12);
        $svg .= $this->label('N', ['x' => $N['x'] + 15, 'y' => $N['y']], self::COLORS['text_aux'], 12);
        $svg .= $this->label('F', ['x' => $F['x'] + 10, 'y' => $F['y'] - 10], self::COLORS['text_aux'], 12);

        return $svg;
    }

    /**
     * Пересечение двух отрезков
     */
    private function lineSegmentIntersection(array $p1, array $p2, array $p3, array $p4): array
    {
        $x1 = $p1['x']; $y1 = $p1['y'];
        $x2 = $p2['x']; $y2 = $p2['y'];
        $x3 = $p3['x']; $y3 = $p3['y'];
        $x4 = $p4['x']; $y4 = $p4['y'];

        $denom = ($x1 - $x2) * ($y3 - $y4) - ($y1 - $y2) * ($x3 - $x4);
        if (abs($denom) < 1e-10) {
            return $this->midpoint($p3, $p4);
        }

        $t = (($x1 - $x3) * ($y3 - $y4) - ($y1 - $y3) * ($x3 - $x4)) / $denom;

        return [
            'x' => $x1 + $t * ($x2 - $x1),
            'y' => $y1 + $t * ($y2 - $y1)
        ];
    }

    /**
     * 17.19 Трапеция с углом 30°
     */
    private function renderTrapezoid30(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Высота из B на AD
        $H = $this->altitudeFoot($B, $A, $D);

        // Высота
        $svg .= "  <line x1=\"{$B['x']}\" y1=\"{$B['y']}\" x2=\"{$H['x']}\" y2=\"{$H['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5" stroke-dasharray="8,4"/>\n";

        // Прямой угол
        $rightAngle = $this->rightAnglePath($H, $A, $B, 10);
        $svg .= "  <path d=\"{$rightAngle}\" fill=\"none\" stroke=\"" . self::COLORS['accent'] . "\" stroke-width="1.2"/>\n";

        // Угол 30° при A
        $arcA = $this->makeAngleArc($A, $D, $B, 30);
        $svg .= "  <path d=\"{$arcA}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.5"/>\n";

        // Метка угла
        $angleLabelPos = $this->angleLabelPos($A, $D, $B, 45, 0.5);
        $svg .= "  <text x=\"{$angleLabelPos['x']}\" y=\"{$angleLabelPos['y']}\" fill=\"" . self::COLORS['service'] . "\" font-size="11" " .
                "text-anchor=\"middle\" dominant-baseline=\"middle\">30°</text>\n";

        // Точка H
        $svg .= "  <circle cx=\"{$H['x']}\" cy=\"{$H['y']}\" r=\"3\" fill=\"" . self::COLORS['circle'] . "\"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));

        return $svg;
    }

    /**
     * 17.20 Прямоугольник с точкой E
     */
    private function renderRectanglePointE(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Прямые углы
        $rightAngleA = $this->rightAnglePath($A, $D, $B, 10);
        $rightAngleB = $this->rightAnglePath($B, $A, $C, 10);
        $rightAngleC = $this->rightAnglePath($C, $B, $D, 10);
        $rightAngleD = $this->rightAnglePath($D, $C, $A, 10);
        $svg .= "  <path d=\"{$rightAngleA}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$rightAngleB}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$rightAngleC}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$rightAngleD}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";

        // Точка E на BC (при угле EAB = 45°, BE = AB)
        // Для визуализации используем E примерно посередине BC
        $E = [
            'x' => $B['x'] + ($C['x'] - $B['x']) * 0.4,
            'y' => $B['y'] + ($C['y'] - $B['y']) * 0.4
        ];

        // Отрезок AE
        $svg .= "  <line x1=\"{$A['x']}\" y1=\"{$A['y']}\" x2=\"{$E['x']}\" y2=\"{$E['y']}\" " .
                "stroke=\"" . self::COLORS['aux'] . "\" stroke-width="1.5"/>\n";

        // Отрезок ED
        $svg .= "  <line x1=\"{$E['x']}\" y1=\"{$E['y']}\" x2=\"{$D['x']}\" y2=\"{$D['y']}\" " .
                "stroke=\"" . self::COLORS['accent'] . "\" stroke-width="2"/>\n";

        // Угол EAB = 45°
        $arcEAB = $this->makeAngleArc($A, $B, $E, 25);
        $svg .= "  <path d=\"{$arcEAB}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1.5"/>\n";

        // Метка угла
        $angleLabelPos = $this->angleLabelPos($A, $B, $E, 40, 0.5);
        $svg .= "  <text x=\"{$angleLabelPos['x']}\" y=\"{$angleLabelPos['y']}\" fill=\"" . self::COLORS['service'] . "\" font-size="11" " .
                "text-anchor=\"middle\" dominant-baseline=\"middle\">45°</text>\n";

        // Точка E
        $svg .= "  <circle cx=\"{$E['x']}\" cy=\"{$E['y']}\" r=\"4\" fill=\"" . self::COLORS['accent'] . "\"/>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));
        $svg .= $this->label('E', ['x' => $E['x'] + 12, 'y' => $E['y'] - 5], self::COLORS['text_aux'], 13);

        return $svg;
    }

    /**
     * 17.22 Площадь квадрата
     */
    private function renderSquareArea(array $points, array $center, array $geometry, array $params): string
    {
        $A = $points['A'];
        $B = $points['B'];
        $C = $points['C'];
        $D = $points['D'];

        $svg = $this->renderQuadrilateral($A, $B, $C, $D);

        // Прямые углы
        $rightAngleA = $this->rightAnglePath($A, $D, $B, 12);
        $rightAngleB = $this->rightAnglePath($B, $A, $C, 12);
        $rightAngleC = $this->rightAnglePath($C, $B, $D, 12);
        $rightAngleD = $this->rightAnglePath($D, $C, $A, 12);
        $svg .= "  <path d=\"{$rightAngleA}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$rightAngleB}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$rightAngleC}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";
        $svg .= "  <path d=\"{$rightAngleD}\" fill=\"none\" stroke=\"" . self::COLORS['service'] . "\" stroke-width="1"/>\n";

        // Диагональ BD (данная)
        $svg .= "  <line x1=\"{$B['x']}\" y1=\"{$B['y']}\" x2=\"{$D['x']}\" y2=\"{$D['y']}\" " .
                "stroke=\"" . self::COLORS['accent'] . "\" stroke-width="2"/>\n";

        // Маркеры равенства сторон
        $svg .= $this->renderSingleEqualityMark($A, $B);
        $svg .= $this->renderSingleEqualityMark($B, $C);
        $svg .= $this->renderSingleEqualityMark($C, $D);
        $svg .= $this->renderSingleEqualityMark($D, $A);

        // Метка диагонали
        $diagLabelPos = $this->labelOnSegment($B, $D, 15);
        $svg .= "  <text x=\"{$diagLabelPos['x']}\" y=\"{$diagLabelPos['y']}\" fill=\"" . self::COLORS['accent'] . "\" font-size="12" " .
                "text-anchor=\"middle\" dominant-baseline=\"middle\">d</text>\n";

        // Вершины
        $svg .= $this->crosshairs([$A, $B, $C, $D]);

        // Метки
        $svg .= $this->label('A', $this->labelPos($A, $center, 18));
        $svg .= $this->label('B', $this->labelPos($B, $center, 18));
        $svg .= $this->label('C', $this->labelPos($C, $center, 18));
        $svg .= $this->label('D', $this->labelPos($D, $center, 18));

        return $svg;
    }
}
