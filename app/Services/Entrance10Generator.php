<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Генератор задач «на подобии» вступительной работы в 10 класс.
 *
 * Для каждого поддерживаемого номера возвращает задачу той же структуры, что и
 * статические задачи из вариантов: number/title/parts[], где каждая часть несёт
 * text (LaTeX), check, answer (каноничный, проверяемый) и answer_display/solution.
 *
 * Все ответы вычисляются точно (никаких «примерно») — генераторы построены так,
 * чтобы итог был целым/дробью/радикалом с известным замкнутым видом.
 *
 * Поддерживаются номера 1, 2, 3, 4, 6, 9. Номера 5 (график), 7 и 8 (геометрия)
 * не генерируются — используются статические задачи из вариантов.
 */
class Entrance10Generator
{
    public const GENERATABLE = [1, 2, 3, 4, 6, 9];

    public function isGeneratable(int $number): bool
    {
        return in_array($number, self::GENERATABLE, true);
    }

    public function generate(int $number): array
    {
        return match ($number) {
            1 => $this->gen1(),
            2 => $this->gen2(),
            3 => $this->gen3(),
            4 => $this->gen4(),
            6 => $this->gen6(),
            9 => $this->gen9(),
            default => throw new InvalidArgumentException("Number {$number} is not generatable"),
        };
    }

    // ---------------------------------------------------------------- helpers

    private function task(int $number, string $title, array $parts): array
    {
        return ['number' => $number, 'title' => $title, 'parts' => $parts, 'generated' => true];
    }

    private function part(?string $label, int $points, string $text, string $check, string $answer, string $answerDisplay, string $solution): array
    {
        return [
            'label' => $label,
            'points' => $points,
            'text' => $text,
            'check' => $check,
            'answer' => $answer,
            'answer_display' => $answerDisplay,
            'solution' => $solution,
        ];
    }

    /** Слагаемое многочлена со знаком: term(2,'x^2') => " + 2x^2", term(-1,'x') => " - x". */
    private function term(int $coef, string $mono): string
    {
        if ($coef === 0) {
            return '';
        }
        $sign = $coef > 0 ? ' + ' : ' - ';
        $a = abs($coef);
        if ($mono === '') {
            $body = (string) $a;
        } else {
            $body = $a === 1 ? $mono : $a . $mono;
        }
        return $sign . $body;
    }

    private function polyStart(string $mono): string
    {
        return $mono; // ведущий член с коэффициентом 1, без знака
    }

    private function isPerfectSquare(int $n): array
    {
        $r = (int) round(sqrt($n));
        return [$r * $r === $n, $r];
    }

    // ---------------------------------------------------------------- №1

    private function gen1(): array
    {
        $family = ['conj', 'square', 'zero'][random_int(0, 2)];
        $r = random_int(2, 6);
        $q = $r + random_int(1, 6);

        if ($family === 'conj') {
            $ans = $q - $r;
            return $this->task(1, 'Упростите выражение', [
                $this->part(null, 2,
                    "Упростите выражение \$(\\sqrt{{$q}}+\\sqrt{{$r}})(\\sqrt{{$q}}-\\sqrt{{$r}})\$.",
                    'number', (string) $ans, "\${$ans}\$",
                    "Произведение сопряжённых: \$(\\sqrt{{$q}})^2-(\\sqrt{{$r}})^2={$q}-{$r}={$ans}\$."),
            ]);
        }

        if ($family === 'square') {
            $ans = $q + $r;
            $qr = $q * $r;
            return $this->task(1, 'Упростите выражение', [
                $this->part(null, 2,
                    "Упростите выражение \$(\\sqrt{{$q}}+\\sqrt{{$r}})^2-2\\sqrt{{$qr}}\$.",
                    'number', (string) $ans, "\${$ans}\$",
                    "\$(\\sqrt{{$q}}+\\sqrt{{$r}})^2={$q}+{$r}+2\\sqrt{{$qr}}\$, вычитаем \$2\\sqrt{{$qr}}\$, остаётся \${$ans}\$."),
            ]);
        }

        // zero: (√(pq)+√(pr))√p/p − p/(√q−√r), где p=q−r ⇒ выражение = 0
        $p = $q - $r;
        $pq = $p * $q;
        $pr = $p * $r;
        return $this->task(1, 'Упростите выражение', [
            $this->part(null, 2,
                "Упростите выражение \$\\dfrac{(\\sqrt{{$pq}}+\\sqrt{{$pr}})\\sqrt{{$p}}}{{$p}}-\\dfrac{{$p}}{\\sqrt{{$q}}-\\sqrt{{$r}}}\$.",
                'number', '0', '$0$',
                "Первая дробь \$=\\sqrt{{$q}}+\\sqrt{{$r}}\$, вторая после умножения на сопряжённое тоже \$\\sqrt{{$q}}+\\sqrt{{$r}}\$. Разность равна \$0\$."),
        ]);
    }

    // ---------------------------------------------------------------- №2

    private function gen2(): array
    {
        $nonSquares = [2, 3, 5, 6, 7, 8, 10, 11, 12, 13];
        $k = $nonSquares[random_int(0, count($nonSquares) - 1)];
        $sk = sqrt($k);
        $minus = random_int(0, 1) === 1; // (1 - √k)^2 + √(N + M√k), inner = a+√k

        if ($minus) {
            $a = random_int(2, 7);
            $value = 1 + $k + $a - $sk;   // = (1-√k)^2 + (a+√k)
            $N = $a * $a + $k;
            $M = 2 * $a;
            $inner = "{$N}+{$M}\\sqrt{{$k}}";
            $head = "(1-\\sqrt{{$k}})^2";
            $sol = "\$(1-\\sqrt{{$k}})^2={$k}+1-2\\sqrt{{$k}}\$; \$\\sqrt{{$inner}}=\\sqrt{({$a}+\\sqrt{{$k}})^2}={$a}+\\sqrt{{$k}}\$. Сумма \$=" . ($k + 1 + $a) . "-\\sqrt{{$k}}\$.";
        } else {
            $a = max(random_int(2, 7), (int) floor($sk) + 1); // нужно a > √k, чтобы |a-√k|=a-√k
            $value = 1 + $k + $a + $sk;   // = (1+√k)^2 + (a-√k)
            $N = $a * $a + $k;
            $M = 2 * $a;
            $inner = "{$N}-{$M}\\sqrt{{$k}}";
            $head = "(1+\\sqrt{{$k}})^2";
            $sol = "\$(1+\\sqrt{{$k}})^2={$k}+1+2\\sqrt{{$k}}\$; \$\\sqrt{{$inner}}=\\sqrt{({$a}-\\sqrt{{$k}})^2}={$a}-\\sqrt{{$k}}\$. Сумма \$=" . ($k + 1 + $a) . "+\\sqrt{{$k}}\$.";
        }

        $ans = (int) floor($value - 1e-9);
        return $this->task(2, 'Наибольшее натуральное число', [
            $this->part(null, 2,
                "Найдите наибольшее натуральное число, не превосходящее числа \${$head}+\\sqrt{{$inner}}\$.",
                'number', (string) $ans, "\${$ans}\$", $sol),
        ]);
    }

    // ---------------------------------------------------------------- №3

    private function gen3(): array
    {
        // а) сумма кратных d между L и R
        $d = [3, 6, 7, 9, 11, 13][random_int(0, 5)];
        $L = random_int(100, 350);
        if ($L % $d === 0) {
            $L++;
        }
        $R = $L + random_int(200, 600);
        if ($R % $d === 0) {
            $R--;
        }
        $first = (intdiv($L, $d) + 1) * $d;      // наименьшее кратное строго > L
        $last = intdiv($R, $d) * $d;             // наибольшее кратное <= R (R не кратно)
        $count = intdiv($last - $first, $d) + 1;
        $sumA = intdiv(($first + $last) * $count, 2);

        // б) сколько nd-значных чисел с хотя бы одной цифрой со свойством
        $nd = [3, 4][random_int(0, 1)];
        $ndWord = $nd === 3 ? 'трёхзначных' : 'четырёхзначных';
        $props = [
            'even' => ['text' => 'чётная цифра',           'first' => 5, 'other' => 5],
            'div3' => ['text' => 'цифра, кратная 3',        'first' => 6, 'other' => 6],
            'odd'  => ['text' => 'нечётная цифра',          'first' => 4, 'other' => 5],
            'div5' => ['text' => 'цифра, кратная 5',        'first' => 8, 'other' => 8],
        ];
        $pk = array_keys($props)[random_int(0, 3)];
        $p = $props[$pk];
        $total = 9 * (10 ** ($nd - 1));
        $complement = $p['first'] * ($p['other'] ** ($nd - 1));
        $countB = $total - $complement;

        return $this->task(3, 'Суммы и комбинаторика', [
            $this->part('а', 2,
                "Найдите сумму всех натуральных чисел, кратных {$d}, расположенных между {$L} и {$R}.",
                'number', (string) $sumA, "\$" . number_format($sumA, 0, '.', '\\,') . "\$",
                "Это арифметическая прогрессия \${$first},\\dots,{$last}\$ из {$count} членов. Сумма \$=\\dfrac{{$first}+{$last}}{2}\\cdot{$count}={$sumA}\$."),
            $this->part('б', 2,
                "Сколько существует {$ndWord} чисел, у которых в десятичной записи присутствует хотя бы одна {$p['text']}?",
                'number', (string) $countB, "\${$countB}\$",
                "Всего {$ndWord} чисел \${$total}\$. Без нужного свойства \$" . $p['first'] . "\\cdot" . $p['other'] . "^{" . ($nd - 1) . "}={$complement}\$. Ответ \${$total}-{$complement}={$countB}\$."),
        ]);
    }

    // ---------------------------------------------------------------- №4

    private function gen4(): array
    {
        // а) кубическое группировкой: (x+p)(x^2 − m) = 0
        do {
            $p = random_int(-5, 5);
        } while ($p === 0);
        $m = random_int(2, 20);
        // многочлен: x^3 + p x^2 − m x − p m
        $poly = $this->polyStart('x^3') . $this->term($p, 'x^2') . $this->term(-$m, 'x') . $this->term(-$p * $m, '');

        [$isSq, $s] = $this->isPerfectSquare($m);
        $rootNeg = -$p;
        if ($isSq) {
            $tokens = [(string) $rootNeg, (string) $s, (string) (-$s)];
            $disp = "\$x={$rootNeg};\\ x=" . $s . ";\\ x=" . (-$s) . "\$";
        } else {
            $tokens = [(string) $rootNeg, "√{$m}", "-√{$m}"];
            $disp = "\$x={$rootNeg};\\ x=\\pm\\sqrt{{$m}}\$";
        }
        $partA = $this->part('а', 2,
            "\${$poly}=0\$",
            'number_set', implode(';', $tokens), $disp,
            "Группировка: \$x^2(x" . ($p >= 0 ? '+' . $p : (string) $p) . ")-{$m}(x" . ($p >= 0 ? '+' . $p : (string) $p) . ")=(x" . ($p >= 0 ? '+' . $p : (string) $p) . ")(x^2-{$m})=0\$.");

        // б) замена t=(x−h)^2: t^2 − S t + P = 0 с корнями g1^2, g2^2 (целые корни x)
        $h = random_int(1, 3);
        $g = [1, 2, 3];
        shuffle($g);
        [$g1, $g2] = [$g[0], $g[1]];
        $t1 = $g1 * $g1;
        $t2 = $g2 * $g2;
        $S = $t1 + $t2;
        $P = $t1 * $t2;
        $h2 = $h * $h;
        $twoH = 2 * $h;
        // (x^2 − 2h x + h^2)^2 − S(x − h)^2 + P = 0
        $trinom = "x^2-{$twoH}x+{$h2}";
        $roots = [];
        foreach ([$g1, $g2] as $gg) {
            $roots[] = $h - $gg;
            $roots[] = $h + $gg;
        }
        sort($roots);
        $tokensB = array_map('strval', $roots);
        $dispB = '$x=' . implode(';\\ ', $tokensB) . '$';
        $partB = $this->part('б', 2,
            "\$({$trinom})^2-{$S}(x-{$h})^2+{$P}=0\$",
            'number_set', implode(';', $tokensB), $dispB,
            "Замена \$t=(x-{$h})^2\$: \$t^2-{$S}t+{$P}=0\$, \$t={$t1}\$ или \$t={$t2}\$. Тогда \$x-{$h}=\\pm{$g1}\$ и \$x-{$h}=\\pm{$g2}\$.");

        return $this->task(4, 'Решите уравнения', [$partA, $partB]);
    }

    // ---------------------------------------------------------------- №6

    private function gen6(): array
    {
        // Генерация переформулирована под однозначно проверяемый числовой ответ
        // (без интервалов): уравнение (x−k)(x−param)=0, корни k и param.
        $k = random_int(1, 5);
        $param = ['a', 'b'][random_int(0, 1)];
        $lin = "({$param} + {$k})";
        $prod = $k === 1 ? $param : "{$k}{$param}";
        $eq = "x^2-{$lin}x+{$prod}=0";

        return $this->task(6, 'Уравнение с параметром', [
            $this->part('а', 2,
                "При каком значении параметра \${$param}\$ уравнение \${$eq}\$ имеет единственный корень?",
                'number', (string) $k, "\${$param}={$k}\$",
                "\${$eq}\\Leftrightarrow(x-{$k})(x-{$param})=0\$, корни \${$k}\$ и \${$param}\$. Они совпадают (единственный корень) при \${$param}={$k}\$."),
            $this->part('б', 1,
                "При каком значении параметра \${$param}\$ один из корней уравнения \${$eq}\$ равен нулю?",
                'number', '0', "\${$param}=0\$",
                "Произведение корней равно свободному члену \${$k}\\cdot{$param}\$. Корень \$0\$ возможен, когда \${$k}{$param}=0\$, то есть при \${$param}=0\$."),
        ]);
    }

    // ---------------------------------------------------------------- №9

    private function gen9(): array
    {
        $A = random_int(12, 22) * 2;          // чётное 24..44
        $B = random_int(8, ($A - 4) >> 1) * 2; // чётное, < A
        $diff = $A - $B;
        $sumAB = $A + $B;

        $valid = function (int $x) use ($diff, $sumAB): bool {
            return $x >= $diff && $x <= $sumAB && (($x - $diff) % 2 === 0);
        };

        $c = random_int(0, $B);
        $xValid = $diff + 2 * $c;               // достижимо
        $xParity = $xValid + 1;                 // неверная чётность
        $xRange = $sumAB + 2;                   // больше максимума

        $candidates = [$xValid, $xParity, $xRange];
        shuffle($candidates);

        $labels = ['а', 'б', 'в'];
        $parts = [];
        foreach ($candidates as $i => $x) {
            $ok = $valid($x);
            $preamble = $i === 0
                ? "Трое друзей играли в шашки. Один сыграл {$A} партий, другой — {$B}. Мог ли третий сыграть {$x} партий?"
                : "…Мог ли третий сыграть {$x} партий?";
            $sol = $ok
                ? "Да: сумма партий чётна, и \${$diff}\\le{$x}\\le{$sumAB}\$ той же чётности — значение достижимо."
                : (($x - $diff) % 2 !== 0
                    ? "Нет: сумма \${$A}+{$B}+{$x}\$ должна быть чётной, а {$x} нарушает чётность."
                    : "Нет: третий не мог сыграть больше \${$A}+{$B}={$sumAB}\$ партий.");
            $parts[] = $this->part($labels[$i], 1, $preamble, 'yesno', $ok ? 'да' : 'нет', $ok ? 'Да' : 'Нет', $sol);
        }

        return $this->task(9, 'Шашки', $parts);
    }
}
