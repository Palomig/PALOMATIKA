<?php

namespace App\Services;

/**
 * NumberLineSvgRenderer - Сервис для генерации SVG координатных прямых
 *
 * Рендерит SVG-изображения для темы 07 (Числа, координатная прямая)
 * на основе JSON-данных.
 */
class NumberLineSvgRenderer
{
    // Blueprint цветовая схема (синхронизировано с GeometrySvgRenderer)
    private const COLORS = [
        'bg' => '#0a1628',
        'line' => '#c8dce8',          // Основная линия
        'tick' => '#7eb8da',          // Риски
        'point' => '#22c55e',         // Точки (зелёные)
        'label' => '#60a5fa',         // Подписи точек (голубые)
        'number' => '#94a3b8',        // Цифры на оси (серые)
        'zero' => '#f59e0b',          // Ноль (янтарный)
        'arrow' => '#c8dce8',         // Стрелка
    ];

    // Поддерживаемые типы SVG
    private const TYPES = [
        'single_point',       // Одна точка
        'two_points',         // Две точки
        'three_points',       // Три точки
        'four_points_abcd',   // Четыре точки A, B, C, D
        'point_a_on_range',   // Точка A на отрезке [min, max]
    ];

    /**
     * Рендерит SVG из данных задания
     */
    public function render(string $svgType, array $task, array $zadanie = []): string
    {
        return match($svgType) {
            'single_point' => $this->renderSinglePoint($task),
            'two_points' => $this->renderTwoPoints($task),
            'three_points' => $this->renderThreePoints($task, $zadanie),
            'four_points_abcd' => $this->renderFourPointsABCD($task),
            'point_a_on_range' => $this->renderPointAOnRange($task),
            default => '',
        };
    }

    /**
     * Проверяет, поддерживается ли тип
     */
    public function supports(string $svgType): bool
    {
        return in_array($svgType, self::TYPES);
    }

    /**
     * Одна точка на координатной прямой
     */
    private function renderSinglePoint(array $task): string
    {
        $pointValue = $task['point_value'] ?? 5;
        $pointLabel = $task['point_label'] ?? 'a';

        // Определяем диапазон
        $minVal = floor(min($pointValue, 0)) - 1;
        $maxVal = ceil(max($pointValue, 1)) + 1;

        // Для дробных значений от 0 до 1
        if ($pointValue > 0 && $pointValue < 1) {
            $minVal = 0;
            $maxVal = 1;
        } elseif ($pointValue >= 1 && $pointValue < 2) {
            $minVal = 0;
            $maxVal = 2;
        }

        return $this->generateNumberLine([
            ['value' => $pointValue, 'label' => $pointLabel]
        ], $minVal, $maxVal);
    }

    /**
     * Две точки на координатной прямой
     */
    private function renderTwoPoints(array $task): string
    {
        $points = $task['points'] ?? [];
        if (empty($points)) {
            return '';
        }

        $values = array_column($points, 'value');
        $minVal = floor(min(min($values), 0)) - 1;
        $maxVal = ceil(max($values)) + 1;

        return $this->generateNumberLine($points, $minVal, $maxVal);
    }

    /**
     * Три точки на координатной прямой
     * (используется для заданий типа simple_choice)
     */
    private function renderThreePoints(array $task, array $zadanie): string
    {
        // Для simple_choice точки хранятся в zadanie
        $points = $zadanie['points'] ?? $task['points'] ?? [];
        if (empty($points)) {
            return '';
        }

        $values = array_column($points, 'value');
        $minVal = floor(min(min($values), 0)) - 1;
        $maxVal = ceil(max($values)) + 1;

        return $this->generateNumberLine($points, $minVal, $maxVal);
    }

    /**
     * Четыре точки A, B, C, D на координатной прямой
     */
    private function renderFourPointsABCD(array $task): string
    {
        $fourPoints = $task['four_points'] ?? [];
        $range = $task['range'] ?? null;

        if (empty($fourPoints)) {
            return '';
        }

        // Создаём массив точек с метками A, B, C, D
        $labels = ['A', 'B', 'C', 'D'];
        $points = [];
        foreach ($fourPoints as $i => $value) {
            $points[] = [
                'value' => $value,
                'label' => $labels[$i] ?? chr(65 + $i)
            ];
        }

        // Определяем диапазон
        if ($range) {
            $minVal = $range[0];
            $maxVal = $range[1];
        } else {
            $minVal = floor(min($fourPoints)) - 1;
            $maxVal = ceil(max($fourPoints)) + 1;
        }

        return $this->generateNumberLine($points, $minVal, $maxVal, true);
    }

    /**
     * Точка A на отрезке [min, max]
     */
    private function renderPointAOnRange(array $task): string
    {
        $pointA = $task['point_a'] ?? null;
        $range = $task['range'] ?? null;

        if ($pointA === null || $range === null) {
            return '';
        }

        $points = [
            ['value' => $pointA, 'label' => 'A']
        ];

        return $this->generateNumberLine($points, $range[0], $range[1], true);
    }

    /**
     * Генерирует SVG координатной прямой
     */
    private function generateNumberLine(array $points, float $minVal, float $maxVal, bool $showRangeTicks = false): string
    {
        $width = 320;
        $height = 70; // Увеличиваем высоту для размещения подписей сверху и снизу
        $lineY = 35;  // Линия по центру
        $marginLeft = 20;
        $marginRight = 30;
        $lineWidth = $width - $marginLeft - $marginRight;

        $range = $maxVal - $minVal;
        if ($range <= 0) $range = 1;

        // Функция для вычисления X-координаты
        $getX = function($value) use ($minVal, $range, $marginLeft, $lineWidth) {
            return $marginLeft + (($value - $minVal) / $range) * $lineWidth;
        };

        // Определяем, нужно ли чередовать позиции подписей
        $needAlternate = $this->needAlternateLabels($points, $getX);

        // Начало SVG
        $svg = "<svg viewBox=\"0 0 {$width} {$height}\" class=\"w-full max-w-[320px] h-auto mx-auto\">\n";

        // Фон
        $svg .= "  <rect width=\"100%\" height=\"100%\" fill=\"" . self::COLORS['bg'] . "\"/>\n";

        // Маркер стрелки
        $arrowId = 'arrow-' . md5(json_encode($points) . $minVal . $maxVal);
        $svg .= "  <defs>\n";
        $svg .= "    <marker id=\"{$arrowId}\" markerWidth=\"10\" markerHeight=\"10\" refX=\"0\" refY=\"3\" orient=\"auto\">\n";
        $svg .= "      <path d=\"M0,0 L0,6 L9,3 z\" fill=\"" . self::COLORS['arrow'] . "\"/>\n";
        $svg .= "    </marker>\n";
        $svg .= "  </defs>\n";

        // Основная линия
        $svg .= "  <line x1=\"{$marginLeft}\" y1=\"{$lineY}\" x2=\"" . ($width - 15) . "\" y2=\"{$lineY}\" ";
        $svg .= "stroke=\"" . self::COLORS['line'] . "\" stroke-width=\"2\" marker-end=\"url(#{$arrowId})\"/>\n";

        // Риски и подписи чисел
        $step = $this->calculateStep($range);
        $firstTick = ceil($minVal / $step) * $step;

        for ($v = $firstTick; $v <= $maxVal; $v += $step) {
            $x = $getX($v);

            // Риска
            $svg .= "  <line x1=\"{$x}\" y1=\"" . ($lineY - 7) . "\" x2=\"{$x}\" y2=\"" . ($lineY + 7) . "\" ";
            $svg .= "stroke=\"" . self::COLORS['tick'] . "\" stroke-width=\"1.5\"/>\n";

            // Подпись числа (всегда снизу)
            $color = ($v == 0) ? self::COLORS['zero'] : self::COLORS['number'];
            $label = $this->formatNumber($v);
            $svg .= "  <text x=\"{$x}\" y=\"" . ($lineY + 22) . "\" text-anchor=\"middle\" ";
            $svg .= "fill=\"{$color}\" font-size=\"11\" font-weight=\"500\">{$label}</text>\n";
        }

        // Ноль отдельно, если его нет на шкале
        if ($minVal <= 0 && $maxVal >= 0 && ($firstTick > 0 || $firstTick + $step <= 0)) {
            $zeroInTicks = false;
            for ($v = $firstTick; $v <= $maxVal; $v += $step) {
                if (abs($v) < 0.0001) {
                    $zeroInTicks = true;
                    break;
                }
            }
            if (!$zeroInTicks) {
                $x = $getX(0);
                $svg .= "  <line x1=\"{$x}\" y1=\"" . ($lineY - 7) . "\" x2=\"{$x}\" y2=\"" . ($lineY + 7) . "\" ";
                $svg .= "stroke=\"" . self::COLORS['zero'] . "\" stroke-width=\"1.5\"/>\n";
                $svg .= "  <text x=\"{$x}\" y=\"" . ($lineY + 22) . "\" text-anchor=\"middle\" ";
                $svg .= "fill=\"" . self::COLORS['zero'] . "\" font-size=\"11\" font-weight=\"600\">0</text>\n";
            }
        }

        // Точки с умным позиционированием подписей
        $labelPositions = $this->calculateLabelPositions($points, $getX, $lineY, $needAlternate);

        foreach ($points as $i => $pt) {
            $x = $getX($pt['value']);
            $label = $pt['label'] ?? '';

            // Точка (круг)
            $svg .= "  <circle cx=\"{$x}\" cy=\"{$lineY}\" r=\"5\" fill=\"" . self::COLORS['point'] . "\"/>\n";

            // Подпись точки
            if ($label && isset($labelPositions[$i])) {
                $labelY = $labelPositions[$i]['y'];
                $svg .= "  <text x=\"{$x}\" y=\"{$labelY}\" text-anchor=\"middle\" ";
                $svg .= "fill=\"" . self::COLORS['label'] . "\" font-size=\"14\" font-weight=\"600\" ";
                $svg .= "font-style=\"italic\">{$label}</text>\n";
            }
        }

        $svg .= "</svg>";

        return $svg;
    }

    /**
     * Определяет, нужно ли чередовать позиции подписей
     */
    private function needAlternateLabels(array $points, callable $getX): bool
    {
        if (count($points) < 2) {
            return false;
        }

        // Минимальное расстояние между подписями (в пикселях)
        $minDistance = 18;

        for ($i = 0; $i < count($points) - 1; $i++) {
            $x1 = $getX($points[$i]['value']);
            $x2 = $getX($points[$i + 1]['value']);

            if (abs($x2 - $x1) < $minDistance) {
                return true;
            }
        }

        return false;
    }

    /**
     * Вычисляет позиции подписей с учётом коллизий
     */
    private function calculateLabelPositions(array $points, callable $getX, float $lineY, bool $needAlternate): array
    {
        $positions = [];
        $labelAbove = $lineY - 12;  // Позиция выше линии
        $labelBelow = $lineY + 28;  // Позиция ниже линии (под числами)

        if (!$needAlternate) {
            // Все подписи сверху
            foreach ($points as $i => $pt) {
                $positions[$i] = ['y' => $labelAbove];
            }
            return $positions;
        }

        // Строгое чередование: нечётные сверху, чётные снизу
        // Это гарантирует, что соседние подписи никогда не будут на одной высоте
        foreach ($points as $i => $pt) {
            $positions[$i] = ['y' => ($i % 2 === 0) ? $labelAbove : $labelBelow];
        }

        return $positions;
    }

    /**
     * Вычисляет шаг для рисок
     */
    private function calculateStep(float $range): float
    {
        if ($range <= 1) return 0.1;
        if ($range <= 2) return 0.5;
        if ($range <= 5) return 1;
        if ($range <= 10) return 1;
        if ($range <= 20) return 2;
        return 5;
    }

    /**
     * Форматирует число для отображения
     */
    private function formatNumber(float $value): string
    {
        // Убираем лишние нули после запятой
        $formatted = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        // Если число целое, возвращаем без дробной части
        if ($value == (int)$value) {
            return (string)(int)$value;
        }

        return $formatted;
    }
}
