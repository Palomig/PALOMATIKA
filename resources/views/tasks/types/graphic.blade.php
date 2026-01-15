{{--
    Тип: graphic (тема 13)
    Графическое решение неравенств - показывает выражение и варианты интервалов как SVG
--}}

@php
    $tasks = $zadanie['tasks'] ?? [];

    // Функция для решения линейного неравенства ax + b >= cx + d
    // и генерации вариантов ответов
    if (!function_exists('solveLinearInequality')) {
    function solveLinearInequality($expression) {
        // Парсим выражение вида "4x + 5 >= 6x - 2" или "3 - x >= 3x + 5"
        $expr = str_replace(['\\geq', '\\leq', '\\gt', '\\lt', '≥', '≤'], ['>=', '<=', '>', '<'], $expression);

        // Определяем знак неравенства
        $sign = '>=';
        if (str_contains($expr, '>=')) $sign = '>=';
        elseif (str_contains($expr, '<=')) $sign = '<=';
        elseif (str_contains($expr, '>')) $sign = '>';
        elseif (str_contains($expr, '<')) $sign = '<';

        $parts = preg_split('/>=|<=|>|</', $expr);
        if (count($parts) !== 2) {
            return ['solution' => null, 'options' => []];
        }

        $left = trim($parts[0]);
        $right = trim($parts[1]);

        // Парсим коэффициенты при x и свободные члены
        $parseCoeffs = function($side) {
            $coef = 0;
            $const = 0;

            // Нормализуем
            $side = str_replace(' ', '', $side);
            $side = str_replace('−', '-', $side);

            // Разбиваем на термы
            preg_match_all('/([+-]?\d*\.?\d*)x|([+-]?\d+\.?\d*)(?!x)/', $side, $matches, PREG_SET_ORDER);

            foreach ($matches as $m) {
                if (isset($m[1]) && $m[1] !== '') {
                    // Терм с x
                    $c = $m[1];
                    if ($c === '' || $c === '+') $c = '1';
                    elseif ($c === '-') $c = '-1';
                    $coef += floatval($c);
                } elseif (isset($m[2]) && $m[2] !== '') {
                    // Константа
                    $const += floatval($m[2]);
                }
            }

            // Особый случай: просто "x" или "-x"
            if (preg_match('/^-?x/', $side) && $coef == 0) {
                $coef = str_starts_with($side, '-') ? -1 : 1;
            }

            return ['coef' => $coef, 'const' => $const];
        };

        $leftParsed = $parseCoeffs($left);
        $rightParsed = $parseCoeffs($right);

        // ax + b >= cx + d => (a-c)x >= d-b
        $a = $leftParsed['coef'] - $rightParsed['coef'];
        $b = $rightParsed['const'] - $leftParsed['const'];

        if (abs($a) < 0.0001) {
            // Нет переменной x
            return ['solution' => null, 'options' => []];
        }

        $x = $b / $a;
        $x = round($x, 2);

        // Форматируем число
        $xStr = str_replace('.', ',', strval($x));

        // Определяем тип интервала в зависимости от знака и направления
        $isStrict = ($sign === '>' || $sign === '<');
        $leftBracket = $isStrict ? '(' : '[';
        $rightBracket = $isStrict ? ')' : ']';

        // Если коэффициент при x отрицательный, знак меняется
        if ($a < 0) {
            if ($sign === '>=' || $sign === '>') {
                // x <= или x <
                $solution = "(-∞; {$xStr}" . ($isStrict ? ')' : ']');
            } else {
                // x >= или x >
                $solution = ($isStrict ? '(' : '[') . "{$xStr}; +∞)";
            }
        } else {
            if ($sign === '>=' || $sign === '>') {
                // x >= или x >
                $solution = ($isStrict ? '(' : '[') . "{$xStr}; +∞)";
            } else {
                // x <= или x <
                $solution = "(-∞; {$xStr}" . ($isStrict ? ')' : ']');
            }
        }

        // Генерируем варианты (правильный + 3 неправильных)
        $wrongX = -$x; // Противоположное число
        $wrongXStr = str_replace('.', ',', strval(round($wrongX, 2)));

        $options = [
            "(-∞; {$xStr}]",
            "({$xStr}; +∞)",
            "[{$xStr}; +∞)",
            "(-∞; {$wrongXStr}]",
        ];

        // Перемешиваем и добавляем правильный ответ
        shuffle($options);

        return [
            'solution' => $solution,
            'solutionValue' => $x,
            'options' => $options,
        ];
    }
    } // end if (!function_exists)
@endphp

<div class="space-y-6">
    @foreach($tasks as $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $expression = $task['expression'] ?? '';
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}";

            // Решаем неравенство
            $solved = solveLinearInequality($expression);
            $options = [
                "(-∞; 3,5]",
                "(-∞; -8)",
                "[3,5; +∞)",
                "[-8; +∞)",
            ];

            // Если есть решение, используем его для генерации опций
            if ($solved['solution']) {
                $x = $solved['solutionValue'];
                $xStr = str_replace('.', ',', strval($x));
                $wrongX = -$x;
                $wrongXStr = str_replace('.', ',', strval(round($wrongX, 1)));

                $options = [
                    "(-∞; {$xStr}]",
                    "[{$xStr}; +∞)",
                    "({$wrongXStr}; +∞)",
                    "(-∞; {$wrongXStr})",
                ];
            }
        @endphp

        <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

            {{-- Выражение --}}
            <div class="flex items-start gap-3 mb-4">
                <span class="text-cyan-400 font-bold text-lg">{{ $task['id'] }})</span>
                <span class="text-slate-200 math-serif text-xl">${{ $expression }}$</span>
            </div>

            {{-- SVG варианты ответов --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($options as $i => $option)
                    <div class="bg-slate-700/50 rounded-lg p-3 hover:bg-slate-700 cursor-pointer transition border border-slate-600">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-cyan-400 font-bold">{{ $i + 1 }})</span>
                        </div>
                        @include('tasks.partials.interval-line', ['interval' => $option, 'index' => $i + 1])
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
