<?php

namespace App\Services;

/**
 * GraphSvgRenderer - Сервис для генерации SVG графиков функций
 *
 * Рендерит SVG-изображения для темы 11 (Графики функций)
 * на основе формул без зависимости от JavaScript.
 *
 * Поддерживаемые типы функций:
 * - Линейные: y = kx + b
 * - Квадратичные: y = ax² + bx + c
 * - Гиперболические: y = k/x
 */
class GraphSvgRenderer
{
    // Размеры SVG (увеличены на 30%)
    private const WIDTH = 234;
    private const HEIGHT = 234;
    private const PADDING = 32;
    private const SCALE = 23;

    // Blueprint цветовая схема
    private const COLORS = [
        'bg' => '#0f172a',
        'grid' => '#334155',
        'axis' => '#64748b',
        'graph' => '#10b981',
        'text' => '#94a3b8',
    ];

    /**
     * Рендерит SVG для графика функции
     *
     * @param string $formula Формула функции (например, "y = 2x + 3")
     * @return string SVG как HTML-строка
     */
    public function render(string $formula): string
    {
        $centerX = self::WIDTH / 2;
        $centerY = self::HEIGHT / 2;

        $svg = $this->startSvg();
        $svg .= $this->renderBackground();
        $svg .= $this->renderGrid($centerX, $centerY);
        $svg .= $this->renderAxes($centerX, $centerY);
        $svg .= $this->renderArrows($centerX, $centerY);
        $svg .= $this->renderLabels($centerX, $centerY);
        $svg .= $this->renderTickMarks($centerX, $centerY);
        $svg .= $this->renderFunction($formula, $centerX, $centerY);
        $svg .= $this->endSvg();

        return $svg;
    }

    /**
     * Начало SVG
     */
    private function startSvg(): string
    {
        $width = self::WIDTH;
        $height = self::HEIGHT;
        return "<svg viewBox=\"0 0 {$width} {$height}\" class=\"w-full max-w-[234px] h-auto mx-auto\">\n";
    }

    /**
     * Конец SVG
     */
    private function endSvg(): string
    {
        return "</svg>";
    }

    /**
     * Фон
     */
    private function renderBackground(): string
    {
        $bg = self::COLORS['bg'];
        return "  <rect width=\"100%\" height=\"100%\" fill=\"{$bg}\"/>\n";
    }

    /**
     * Сетка
     */
    private function renderGrid(float $centerX, float $centerY): string
    {
        $svg = "  <g class=\"grid\">\n";
        $color = self::COLORS['grid'];
        $padding = self::PADDING;
        $scale = self::SCALE;
        $width = self::WIDTH;
        $height = self::HEIGHT;

        for ($i = -5; $i <= 5; $i++) {
            $x = $centerX + $i * $scale;
            $y1 = $padding - 5;
            $y2 = $height - $padding + 5;
            $svg .= "    <line x1=\"{$x}\" y1=\"{$y1}\" x2=\"{$x}\" y2=\"{$y2}\" stroke=\"{$color}\" stroke-width=\"0.5\"/>\n";

            $y = $centerY + $i * $scale;
            $x1 = $padding - 5;
            $x2 = $width - $padding + 5;
            $svg .= "    <line x1=\"{$x1}\" y1=\"{$y}\" x2=\"{$x2}\" y2=\"{$y}\" stroke=\"{$color}\" stroke-width=\"0.5\"/>\n";
        }

        $svg .= "  </g>\n";
        return $svg;
    }

    /**
     * Оси координат
     */
    private function renderAxes(float $centerX, float $centerY): string
    {
        $svg = "";
        $color = self::COLORS['axis'];
        $padding = self::PADDING;
        $width = self::WIDTH;
        $height = self::HEIGHT;

        // Ось X
        $x1 = $padding - 5;
        $x2 = $width - $padding + 5;
        $svg .= "  <line x1=\"{$x1}\" y1=\"{$centerY}\" x2=\"{$x2}\" y2=\"{$centerY}\" stroke=\"{$color}\" stroke-width=\"1.5\"/>\n";

        // Ось Y
        $y1 = $padding - 5;
        $y2 = $height - $padding + 5;
        $svg .= "  <line x1=\"{$centerX}\" y1=\"{$y1}\" x2=\"{$centerX}\" y2=\"{$y2}\" stroke=\"{$color}\" stroke-width=\"1.5\"/>\n";

        return $svg;
    }

    /**
     * Стрелки осей
     */
    private function renderArrows(float $centerX, float $centerY): string
    {
        $svg = "";
        $color = self::COLORS['axis'];
        $padding = self::PADDING;
        $width = self::WIDTH;

        // Стрелка X
        $xArrowX = $width - $padding + 2;
        $svg .= "  <polygon points=\"{$xArrowX}," . ($centerY - 3) . " {$xArrowX}," . ($centerY + 3) . " " . ($xArrowX + 6) . ",{$centerY}\" fill=\"{$color}\"/>\n";

        // Стрелка Y
        $yArrowY = $padding - 2;
        $svg .= "  <polygon points=\"" . ($centerX - 3) . ",{$yArrowY} " . ($centerX + 3) . ",{$yArrowY} {$centerX}," . ($yArrowY - 6) . "\" fill=\"{$color}\"/>\n";

        return $svg;
    }

    /**
     * Подписи осей
     */
    private function renderLabels(float $centerX, float $centerY): string
    {
        $svg = "";
        $color = self::COLORS['text'];
        $padding = self::PADDING;
        $width = self::WIDTH;
        $scale = self::SCALE;

        // Подпись x
        $xLabelX = $width - $padding + 10;
        $xLabelY = $centerY + 4;
        $svg .= "  <text x=\"{$xLabelX}\" y=\"{$xLabelY}\" fill=\"{$color}\" font-size=\"11\" font-style=\"italic\">x</text>\n";

        // Подпись y
        $yLabelX = $centerX + 5;
        $yLabelY = $padding - 10;
        $svg .= "  <text x=\"{$yLabelX}\" y=\"{$yLabelY}\" fill=\"{$color}\" font-size=\"11\" font-style=\"italic\">y</text>\n";

        // Подпись 0
        $svg .= "  <text x=\"" . ($centerX - 10) . "\" y=\"" . ($centerY + 12) . "\" fill=\"{$color}\" font-size=\"10\">0</text>\n";

        // Подпись 1 на оси X
        $svg .= "  <text x=\"" . ($centerX + $scale - 2) . "\" y=\"" . ($centerY + 12) . "\" fill=\"{$color}\" font-size=\"10\">1</text>\n";

        // Подпись 1 на оси Y
        $svg .= "  <text x=\"" . ($centerX + 5) . "\" y=\"" . ($centerY - $scale + 4) . "\" fill=\"{$color}\" font-size=\"10\">1</text>\n";

        return $svg;
    }

    /**
     * Засечки на осях
     */
    private function renderTickMarks(float $centerX, float $centerY): string
    {
        $svg = "";
        $color = self::COLORS['axis'];
        $scale = self::SCALE;

        for ($i = -4; $i <= 4; $i++) {
            if ($i === 0) continue;

            // Засечки на X
            $x = $centerX + $i * $scale;
            $svg .= "  <line x1=\"{$x}\" y1=\"" . ($centerY - 2) . "\" x2=\"{$x}\" y2=\"" . ($centerY + 2) . "\" stroke=\"{$color}\" stroke-width=\"1\"/>\n";

            // Засечки на Y
            $y = $centerY - $i * $scale;
            $svg .= "  <line x1=\"" . ($centerX - 2) . "\" y1=\"{$y}\" x2=\"" . ($centerX + 2) . "\" y2=\"{$y}\" stroke=\"{$color}\" stroke-width=\"1\"/>\n";
        }

        return $svg;
    }

    /**
     * Рендерит график функции
     */
    private function renderFunction(string $formula, float $centerX, float $centerY): string
    {
        // Очищаем формулу
        $f = preg_replace('/\s+/', '', $formula);
        $f = str_replace(['y=', '−'], ['', '-'], $f);

        // Определяем тип функции
        if (strpos($f, '/x') !== false || preg_match('/\\\\frac\{[^}]+\}\{x\}/', $f) || preg_match('/\\\\frac\{[^}]+\}\{\d*x\}/', $f)) {
            return $this->renderHyperbola($f, $centerX, $centerY);
        } elseif (strpos($f, 'x²') !== false || strpos($f, 'x^2') !== false || preg_match('/\d*x\^?2/', $f)) {
            return $this->renderQuadratic($f, $centerX, $centerY);
        } else {
            return $this->renderLinear($f, $centerX, $centerY);
        }
    }

    /**
     * Линейная функция y = kx + b
     */
    private function renderLinear(string $f, float $centerX, float $centerY): string
    {
        $k = 0;
        $b = 0;

        // Обработка дробей вида \frac{2}{5}
        $f = preg_replace_callback('/\\\\frac\{(-?\d+)\}\{(\d+)\}/', function ($m) {
            return (float)$m[1] / (float)$m[2];
        }, $f);

        // Константа: y = 3
        if (preg_match('/^-?\d+\.?\d*$/', $f) && strpos($f, 'x') === false) {
            $k = 0;
            $b = (float)$f;
        }
        // y = kx + b или y = kx - b
        elseif (preg_match('/^(-?\d*\.?\d*)x([+-]\d+\.?\d*)?$/', $f, $match)) {
            $kStr = $match[1];
            if ($kStr === '' || $kStr === '+') $k = 1;
            elseif ($kStr === '-') $k = -1;
            else $k = (float)$kStr;
            $b = isset($match[2]) ? (float)$match[2] : 0;
        }
        // y = b + kx
        elseif (preg_match('/^(-?\d+\.?\d*)([+-]\d*\.?\d*)x$/', $f, $match)) {
            $b = (float)$match[1];
            $kStr = $match[2];
            if ($kStr === '+' || $kStr === '') $k = 1;
            elseif ($kStr === '-') $k = -1;
            else $k = (float)$kStr;
        }
        // y = x или y = -x
        elseif ($f === 'x') {
            $k = 1;
            $b = 0;
        } elseif ($f === '-x') {
            $k = -1;
            $b = 0;
        }

        return $this->renderPath($this->generateLinearPoints($k, $b, $centerX, $centerY));
    }

    /**
     * Генерация точек для линейной функции
     */
    private function generateLinearPoints(float $k, float $b, float $centerX, float $centerY): array
    {
        $points = [];
        $scale = self::SCALE;
        $width = self::WIDTH;
        $height = self::HEIGHT;

        for ($x = -6; $x <= 6; $x += 0.25) {
            $y = $k * $x + $b;
            $px = $centerX + $x * $scale;
            $py = $centerY - $y * $scale;

            if ($px >= 0 && $px <= $width && $py >= 0 && $py <= $height) {
                $points[] = [round($px, 1), round($py, 1)];
            }
        }

        return $points;
    }

    /**
     * Квадратичная функция y = ax² + bx + c
     */
    private function renderQuadratic(string $f, float $centerX, float $centerY): string
    {
        $a = 1;
        $b = 0;
        $c = 0;

        // Обработка дробей
        $f = preg_replace_callback('/\\\\frac\{(-?\d+)\}\{(\d+)\}/', function ($m) {
            return (float)$m[1] / (float)$m[2];
        }, $f);

        $f = str_replace('x²', 'x^2', $f);

        // Коэффициент при x²
        if (preg_match('/^(-?\d*\.?\d*)x\^?2/', $f, $match)) {
            $aStr = $match[1];
            if ($aStr === '' || $aStr === '+') $a = 1;
            elseif ($aStr === '-') $a = -1;
            else $a = (float)$aStr;
        }

        // Коэффициент при x (не x²)
        if (preg_match('/([+-]\d*\.?\d*)x(?!\^)/', $f, $match)) {
            $bStr = $match[1];
            if ($bStr === '+' || $bStr === '') $b = 1;
            elseif ($bStr === '-') $b = -1;
            else $b = (float)$bStr;
        }

        // Свободный член
        if (preg_match('/([+-]\d+\.?\d*)$/', $f, $match) && strpos($match[0], 'x') === false) {
            $c = (float)$match[1];
        }

        return $this->renderPath($this->generateQuadraticPoints($a, $b, $c, $centerX, $centerY));
    }

    /**
     * Генерация точек для квадратичной функции
     */
    private function generateQuadraticPoints(float $a, float $b, float $c, float $centerX, float $centerY): array
    {
        $points = [];
        $scale = self::SCALE;
        $width = self::WIDTH;
        $height = self::HEIGHT;

        for ($x = -6; $x <= 6; $x += 0.1) {
            $y = $a * $x * $x + $b * $x + $c;
            $px = $centerX + $x * $scale;
            $py = $centerY - $y * $scale;

            if ($px >= -10 && $px <= $width + 10 && $py >= -10 && $py <= $height + 10) {
                $points[] = [round($px, 1), round($py, 1)];
            }
        }

        return $points;
    }

    /**
     * Гипербола y = k/x
     */
    private function renderHyperbola(string $f, float $centerX, float $centerY): string
    {
        $k = 1;

        // Обработка \frac{k}{x}
        $f = preg_replace_callback('/\\\\frac\{(-?\d+)\}\{x\}/', function ($m) {
            return $m[1] . '/x';
        }, $f);

        $f = preg_replace_callback('/\\\\frac\{(-?\d+)\}\{(\d+)x\}/', function ($m) {
            return ((float)$m[1] / (float)$m[2]) . '/x';
        }, $f);

        if (preg_match('/(-?\d*\.?\d*)\/x/', $f, $match)) {
            $kStr = $match[1];
            if ($kStr === '' || $kStr === '+') $k = 1;
            elseif ($kStr === '-') $k = -1;
            else $k = (float)$kStr;
        }

        $svg = "";
        $svg .= $this->renderPath($this->generateHyperbolaPoints($k, $centerX, $centerY, true));
        $svg .= $this->renderPath($this->generateHyperbolaPoints($k, $centerX, $centerY, false));

        return $svg;
    }

    /**
     * Генерация точек для гиперболы (одна ветвь)
     */
    private function generateHyperbolaPoints(float $k, float $centerX, float $centerY, bool $positive): array
    {
        $points = [];
        $scale = self::SCALE;
        $width = self::WIDTH;
        $height = self::HEIGHT;

        if ($positive) {
            for ($x = 0.2; $x <= 6; $x += 0.05) {
                $y = $k / $x;
                $px = $centerX + $x * $scale;
                $py = $centerY - $y * $scale;

                if ($px >= 0 && $px <= $width && $py >= 0 && $py <= $height) {
                    $points[] = [round($px, 1), round($py, 1)];
                }
            }
        } else {
            for ($x = -6; $x <= -0.2; $x += 0.05) {
                $y = $k / $x;
                $px = $centerX + $x * $scale;
                $py = $centerY - $y * $scale;

                if ($px >= 0 && $px <= $width && $py >= 0 && $py <= $height) {
                    $points[] = [round($px, 1), round($py, 1)];
                }
            }
        }

        return $points;
    }

    /**
     * Рендерит path из массива точек
     */
    private function renderPath(array $points): string
    {
        if (count($points) < 2) {
            return "";
        }

        $color = self::COLORS['graph'];
        $pathData = "M " . implode(' L ', array_map(function ($p) {
            return "{$p[0]},{$p[1]}";
        }, $points));

        return "  <path d=\"{$pathData}\" stroke=\"{$color}\" stroke-width=\"2\" fill=\"none\" stroke-linecap=\"round\"/>\n";
    }

    /**
     * Проверяет, поддерживается ли формула
     */
    public function supports(string $formula): bool
    {
        // Очищаем формулу
        $f = preg_replace('/\s+/', '', $formula);
        $f = str_replace(['y=', '−'], ['', '-'], $f);

        // Проверяем базовые паттерны
        if (preg_match('/^-?\d+\.?\d*$/', $f)) return true; // константа
        if (preg_match('/x/', $f)) return true; // содержит x

        return false;
    }
}
