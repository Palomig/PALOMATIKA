<?php

namespace App\Services;

/**
 * Разбор школьных числовых ответов, в том числе с подкоренными выражениями.
 *
 * Нужен там, где ответ ученика нельзя сверить строкой: «2√3», «2*sqrt(3)»,
 * «корень из 12» и «√12» — одно и то же число, а «(3+√5)/2» вообще не имеет
 * канонической записи. Поэтому и эталон, и ответ ученика приводятся к float
 * и сравниваются с допуском.
 *
 * Используется второй частью ОГЭ (TaskAnswerResolver), второй частью профиля
 * ЕГЭ и вступительной работой в 10 класс (Entrance10Service). Первой части не
 * касается: там ответ — целое число или последовательность цифр, и численная
 * сверка ничего не добавляет.
 *
 * Помимо радикалов понимает π, логарифмы и обратную тригонометрию — этим
 * записан почти весь ответ части 2 профиля: «13π/4; 23π/6», «arctg(5/3)»,
 * «(0; log_5(3)]». Банк один на оба экзамена не делится: правила сверки у
 * них одни, расходится только словарь, поэтому имена добавляются сюда, а не
 * во второй резолвер.
 */
class MathAnswerParser
{
    /** Допуск сравнения: √-ответы всё равно считаются в double. */
    public const EPSILON = 1e-6;

    private const ROOT_WORDS = ['\\sqrt', 'sqrt', 'корень из', 'корень', 'root'];

    /**
     * Что вычислитель понимает помимо чисел и скобок.
     *
     * Логарифмы сюда не входят: `log_a(b)` и `lg` переписываются в `ln` ещё
     * при разборе записи, поэтому до вычислителя доходит только `ln`.
     * Порядок важен — имена ищутся по этому списку, и «arctg» обязан
     * проверяться раньше «arc»-соседей с общим началом.
     */
    private const FUNCTIONS = ['arcsin', 'arccos', 'arctan', 'arctg', 'sqrt', 'ln'];

    /** Константы: имя → значение. */
    private const CONSTANTS = ['pi' => M_PI];

    /**
     * Признаки точного (не десятичного) ответа: по ним включается численная
     * сверка. Радикал проверяется отдельно — {@see hasRadical}.
     */
    private const EXACT_FORM_MARKERS = ['π', 'pi', 'log', 'lg', 'ln', 'arcsin', 'arccos', 'arctan', 'arctg'];

    /**
     * Есть ли в записи радикал (в любой из принимаемых нотаций).
     * По этому признаку решается, включать ли численную сверку вообще.
     */
    public function hasRadical(?string $value): bool
    {
        $s = mb_strtolower((string) $value);
        if (str_contains($s, '√')) {
            return true;
        }
        foreach (self::ROOT_WORDS as $word) {
            if (str_contains($s, $word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Значение одиночного выражения. null — если разобрать не удалось.
     */
    public function value(?string $raw): ?float
    {
        $expr = $this->toExpression((string) $raw);
        if ($expr === null) {
            return null;
        }

        return $this->evaluate($expr);
    }

    /**
     * Множество значений: «√6; -√6», «1; √12; -√12», «±√6».
     * Возвращает отсортированный список без дублей — сравнение идёт как
     * множество, порядок перечисления корней роли не играет.
     *
     * @return array<int, float>|null
     */
    public function valueSet(?string $raw): ?array
    {
        $candidates = $this->valueSetCandidates($raw);

        return $candidates[0] ?? null;
    }

    /**
     * Совпадают ли ответы как множества значений.
     * null — если хотя бы одну из сторон разобрать не удалось (решение о
     * правильности остаётся за вызывающим кодом).
     */
    public function setsMatch(?string $expected, ?string $actual): ?bool
    {
        $wanted = $this->valueSetCandidates($expected);
        $got = $this->valueSetCandidates($actual);
        if ($wanted === [] || $got === []) {
            return null;
        }

        foreach ($wanted as $want) {
            foreach ($got as $candidate) {
                if ($this->sameValues($want, $candidate)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Все осмысленные прочтения записи. Пробел между элементами неоднозначен:
     * «√6 -√6» — это и разность (0), и перечисление двух корней. Вместо того
     * чтобы угадывать, возвращаем оба варианта, а сверка принимает любой
     * совпавший. Первым идёт наиболее вероятное прочтение.
     *
     * @return array<int, array<int, float>>
     */
    public function valueSetCandidates(?string $raw): array
    {
        $s = trim((string) $raw);
        if ($s === '') {
            return [];
        }

        $candidates = [];
        foreach ($this->itemSplits($s) as $items) {
            $values = [];
            $ok = true;
            foreach ($items as $item) {
                foreach ($this->expandPlusMinus($item) as $variant) {
                    $value = $this->value($variant);
                    if ($value === null) {
                        $ok = false;
                        break 2;
                    }
                    $values[] = $value;
                }
            }
            if (!$ok || $values === []) {
                continue;
            }

            $normalized = $this->dedupe($values);
            foreach ($candidates as $existing) {
                if ($this->sameValues($existing, $normalized)) {
                    continue 2;
                }
            }
            $candidates[] = $normalized;
        }

        return $candidates;
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    private function sameValues(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }
        foreach ($a as $i => $value) {
            if (abs($value - $b[$i]) > self::EPSILON) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, float>  $values
     * @return array<int, float>
     */
    private function dedupe(array $values): array
    {
        sort($values);

        $unique = [];
        foreach ($values as $value) {
            $last = end($unique);
            if ($last === false || abs($last - $value) > self::EPSILON) {
                $unique[] = $value;
            }
        }

        return $unique;
    }

    /**
     * Единая точка входа: сверяет ответ ученика с эталоном, сам выбирая режим —
     * промежуток («(1; 1+√2)» из неравенства) или множество значений.
     * null — если эталон или ответ разобрать не удалось.
     */
    public function answersMatch(?string $expected, ?string $actual): ?bool
    {
        if ($this->looksLikeInterval($expected)) {
            return $this->intervalsMatch($expected, $actual);
        }

        return $this->setsMatch($expected, $actual);
    }

    /**
     * Нужна ли этому эталону численная сверка вместо сравнения строк.
     *
     * Раньше признаком был только радикал, и одна и та же по смыслу задача
     * вела себя по-разному: «4−√7; 4+√7» принимался в любом порядке, а
     * «−4; −3; 3» из соседнего варианта — только в том, что записан в банке.
     * Промежутки без корня вообще сверялись дословно, поэтому «[3;5]∪[−6;−4]»
     * и латинская «U» вместо «∪» считались ошибкой.
     */
    public function needsNumericComparison(?string $raw): bool
    {
        return $this->hasRadical($raw)
            || $this->hasExactForm($raw)
            || $this->looksLikeInterval($raw)
            || $this->looksLikeValueList($raw);
    }

    /**
     * Есть ли в ответе π, логарифм или обратная тригонометрия.
     *
     * Такой ответ точный: сверять его строкой нельзя, потому что «arctg(5/3)»
     * и «arctg 5/3» — одно и то же, а «13π/4; 23π/6» и та же пара в другом
     * порядке — один ответ. Часть 2 профиля почти вся такая: из 980 боевых
     * задач банка 67 записаны через π, log или arc, и до появления этих имён
     * в грамматике все они сверялись дословно. В ОГЭ таких ответов нет ни
     * одного, так что признак ничего там не меняет.
     */
    public function hasExactForm(?string $value): bool
    {
        $s = mb_strtolower((string) $value);
        foreach (self::EXACT_FORM_MARKERS as $marker) {
            if (str_contains($s, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Похоже ли на запись промежутка или объединения: «(a; b)», «[a; b)»,
     * «(-∞; 2)∪(3; +∞)», «(-∞;-5]∪{4}», «{-2}∪(-1;6)».
     */
    public function looksLikeInterval(?string $raw): bool
    {
        $s = trim((string) $raw);
        if ($s === '') {
            return false;
        }

        $opens = str_starts_with($s, '(') || str_starts_with($s, '[') || str_starts_with($s, '{');
        $closes = str_ends_with($s, ')') || str_ends_with($s, ']') || str_ends_with($s, '}');

        return $opens && $closes && (str_contains($s, ';') || str_contains($s, '∞'));
    }

    /**
     * Перечисление значений: «-4;-3;3», «60;120», «-1/6;1/2».
     * Скобки исключены — они означают промежуток, а не список.
     */
    public function looksLikeValueList(?string $raw): bool
    {
        $s = trim((string) $raw);
        if ($s === '' || !str_contains($s, ';')) {
            return false;
        }
        if (preg_match('/[()\[\]{}∪∞]/u', $s)) {
            return false;
        }

        $values = $this->valueSet($s);

        return $values !== null && count($values) > 1;
    }

    /**
     * Совпадают ли промежутки. Границы сверяются численно, тип скобки —
     * строго: строгое и нестрогое неравенство дают разные ответы.
     */
    public function intervalsMatch(?string $expected, ?string $actual): ?bool
    {
        $want = $this->parseIntervals($expected);
        if ($want === null) {
            return null;
        }
        $got = $this->parseIntervals($actual);
        if ($got === null) {
            return null;
        }

        if (count($want) !== count($got)) {
            return false;
        }
        foreach ($want as $i => $interval) {
            $other = $got[$i];
            if ($interval['left'] !== $other['left'] || $interval['right'] !== $other['right']) {
                return false;
            }
            foreach (['from', 'to'] as $edge) {
                if (is_infinite($interval[$edge]) || is_infinite($other[$edge])) {
                    if ($interval[$edge] !== $other[$edge]) {
                        return false;
                    }
                    continue;
                }
                if (abs($interval[$edge] - $other[$edge]) > self::EPSILON) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Разбирает объединение промежутков в отсортированный список.
     *
     * @return array<int, array{left:string, from:float, to:float, right:string}>|null
     */
    public function parseIntervals(?string $raw): ?array
    {
        $s = mb_strtolower(trim((string) $raw));
        if ($s === '') {
            return null;
        }
        $s = str_replace(['−', '–', '—'], '-', $s);
        $s = str_replace(['\\infty', 'infty', 'inf', 'бесконечности', 'бесконечность'], '∞', $s);
        $s = str_replace(['\\cup', 'u', 'ᴜ', 'υ'], '∪', $s);

        $parts = preg_split('/∪+/u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return null;
        }

        $intervals = [];
        foreach ($parts as $part) {
            $points = $this->parseFinitePart(trim($part));
            if ($points !== null) {
                foreach ($points as $point) {
                    $intervals[] = ['left' => '[', 'from' => $point, 'to' => $point, 'right' => ']'];
                }
                continue;
            }
            $interval = $this->parseInterval(trim($part));
            if ($interval === null) {
                return null;
            }
            $intervals[] = $interval;
        }

        usort($intervals, fn (array $a, array $b) => $a['from'] <=> $b['from']);

        return $intervals;
    }

    /**
     * Изолированные точки объединения: «{4}», «{-2; 3}», а также голое число —
     * ученики пишут «(-∞;-5]∪4» вместо «∪{4}». Внутри сравнения такая точка
     * живёт как вырожденный отрезок [a; a].
     *
     * @return array<int, float>|null  null, если это не перечисление точек
     */
    private function parseFinitePart(string $s): ?array
    {
        $braced = str_starts_with($s, '{') && str_ends_with($s, '}');
        $inner = $s;
        if ($braced) {
            $inner = mb_substr($s, 1, mb_strlen($s) - 2);
        } elseif (preg_match('/[()\[\]{}]/u', $s)) {
            return null;
        } elseif (str_contains($s, ';') || str_contains($s, ',')) {
            // Перечисление без фигурных скобок — это не член объединения, а
            // ответ-множество целиком: «1; 1+√2» вместо «(1; 1+√2)». Разбирать
            // его здесь нельзя, иначе промежуток молча превратится в две точки.
            return null;
        }

        $values = [];
        foreach (preg_split('/[;,]+/u', trim($inner), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $item) {
            $value = $this->value($item);
            if ($value === null) {
                return null;
            }
            $values[] = $value;
        }

        return $values === [] ? null : $values;
    }

    /** @return array{left:string, from:float, to:float, right:string}|null */
    private function parseInterval(string $s): ?array
    {
        if (mb_strlen($s) < 4) {
            return null;
        }
        $left = mb_substr($s, 0, 1);
        $right = mb_substr($s, -1);
        if (!in_array($left, ['(', '['], true) || !in_array($right, [')', ']'], true)) {
            return null;
        }

        $inner = mb_substr($s, 1, mb_strlen($s) - 2);
        $split = $this->splitTopLevel($inner);
        if ($split === null) {
            return null;
        }
        [$fromRaw, $toRaw] = $split;

        $from = $this->edgeValue($fromRaw);
        $to = $this->edgeValue($toRaw);
        if ($from === null || $to === null || $from > $to) {
            return null;
        }

        return ['left' => $left, 'from' => $from, 'to' => $to, 'right' => $right];
    }

    /** Граница промежутка: число, выражение с корнем или ±∞. */
    private function edgeValue(string $raw): ?float
    {
        $s = trim($raw);
        if (str_contains($s, '∞')) {
            return str_starts_with($s, '-') ? -INF : INF;
        }

        return $this->value($s);
    }

    /**
     * Делит содержимое промежутка по «;» верхнего уровня, чтобы не разрезать
     * границу вида «(3+√5)/2».
     *
     * @return array{0:string,1:string}|null
     */
    private function splitTopLevel(string $inner): ?array
    {
        $depth = 0;
        $len = strlen($inner);
        for ($i = 0; $i < $len; $i++) {
            $ch = $inner[$i];
            if ($ch === '(' || $ch === '[') {
                $depth++;
            } elseif ($ch === ')' || $ch === ']') {
                $depth--;
            } elseif (($ch === ';' || $ch === ',') && $depth === 0) {
                return [substr($inner, 0, $i), substr($inner, $i + 1)];
            }
        }

        return null;
    }

    /**
     * Ответ записан как десятичное приближение — только цифры, без радикала,
     * дроби и знака деления. Нужно, чтобы отличить «4.6457» от точного
     * «-4+√7»: численно они совпадут, но такой ответ ученику не засчитывают.
     */
    public function looksLikeDecimalApproximation(?string $raw): bool
    {
        $s = mb_strtolower(trim((string) $raw));
        if ($s === '') {
            return false;
        }
        $s = str_replace(['−', '–', '—'], '-', $s);
        $s = str_replace(',', '.', $s);
        $s = preg_replace('/\s+/u', '', $s) ?? $s;

        return (bool) preg_match('/^-?\d+\.\d+$/', $s);
    }

    // ------------------------------------------------------------------ разбор

    /**
     * Способы разбить запись на элементы перечисления, от более вероятного
     * к менее. При явной «;» вариант ровно один; иначе пробуем и «строка —
     * одно выражение», и «строка — перечисление через запятую/пробел».
     *
     * @return array<int, array<int, string>>
     */
    private function itemSplits(string $s): array
    {
        $s = preg_replace('/\s+и\s+/u', ';', $s) ?? $s;

        if (str_contains($s, ';')) {
            return [preg_split('/;+/u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: []];
        }

        $splits = [[$s]];

        // Запятая как разделитель — только если строка не читается как одно
        // число: иначе «1,5» развалилось бы на «1» и «5».
        $parts = preg_split('/[,\s]+/u', trim($s), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) > 1) {
            $splits[] = $parts;
        }

        return $splits;
    }

    /**
     * «±√6» → [«√6», «-√6»]. Если знака нет — элемент возвращается как есть.
     *
     * @return array<int, string>
     */
    private function expandPlusMinus(string $item): array
    {
        $normalized = str_replace(['+-', '-+', '\\pm'], '±', trim($item));
        if (!str_contains($normalized, '±')) {
            return [$item];
        }

        return [
            str_replace('±', '+', $normalized),
            str_replace('±', '-', $normalized),
        ];
    }

    /**
     * Приводит запись к ASCII-выражению с sqrt(): «2√3» → «2*sqrt(3)».
     * null — если встретилось что-то, чего вычислитель не примет.
     */
    private function toExpression(string $raw): ?string
    {
        $s = mb_strtolower(trim($raw));
        if ($s === '') {
            return null;
        }

        // Мусор из LaTeX-эталонов и обёртка «ответ: x = …» из ответов учеников.
        $s = str_replace(['$', '\\left', '\\right', '\\displaystyle', '\\cdot', '\\times'], ['', '', '', '', '*', '*'], $s);
        $s = preg_replace('/^ответ\s*:?\s*/u', '', $s) ?? $s;
        $s = preg_replace('/^[a-zа-яё]\s*=\s*/u', '', $s) ?? $s;
        // Единицы измерения: в №23 ответ — длина или площадь, и «12√6 см»
        // ученик пишет чаще, чем голое число.
        $s = preg_replace('/\s*(кв\.?\s*)?(см|мм|дм|км|м)\s*[23²³]?\.?$/u', '', $s) ?? $s;
        $s = preg_replace('/\s*(градусов|градуса|градус|°)\.?$/u', '', $s) ?? $s;
        $s = str_replace(['−', '–', '—'], '-', $s);
        $s = str_replace(['{,}', ','], '.', $s);
        $s = str_replace([':', '\\div'], '/', $s);

        // π — константа, а не буква: «13π/4» и «4π» это обычные числа.
        $s = str_replace(['\\pi', 'π'], 'pi', $s);
        // Обратная тригонометрия приходит и из LaTeX: «\arcsin», а «arctg»
        // в KaTeX набирается через \operatorname.
        $s = str_replace(
            ['\\operatorname{arctg}', '\\arcsin', '\\arccos', '\\arctan', '\\arctg', '\\log', '\\ln', '\\lg'],
            ['arctg', 'arcsin', 'arccos', 'arctan', 'arctg', 'log', 'ln', 'lg'],
            $s
        );

        // Все написания корня — к одному символу.
        foreach (self::ROOT_WORDS as $word) {
            $s = str_replace($word, '√', $s);
        }

        // \frac{a}{b} → ((a)/(b)).
        $s = $this->expandFractions($s);
        if ($s === null) {
            return null;
        }

        $s = str_replace(['{', '}'], ['(', ')'], $s);
        $s = preg_replace('/\s+/u', '', $s) ?? $s;

        $s = $this->expandRadicals($s);
        if ($s === null) {
            return null;
        }

        $s = $this->expandLogarithms($s);
        if ($s === null) {
            return null;
        }

        $s = $this->insertImplicitMultiplication($s);

        // Дальше допустимы только числа, операции, скобки и известные имена.
        // Проверка «что осталось после вычёркивания имён» надёжнее прежнего
        // набора символов: тот пропускал любую букву из слова sqrt, и «tar»
        // доходило до вычислителя.
        $probe = str_replace(
            array_merge(self::FUNCTIONS, array_keys(self::CONSTANTS)),
            '',
            $s
        );
        if (preg_match('/[^0-9.+\-*\/^()]/', $probe)) {
            return null;
        }

        return $s;
    }

    /**
     * Логарифмы — к натуральному: «log_3(84)» → «(ln(84)/ln(3))».
     *
     * Так вычислителю хватает одной функции вместо семейства с основанием.
     * Основание пишут и подстрочно («log_3(…)»), и в фигурных скобках
     * («log_{3}(…)» — к этому месту они уже стали круглыми). Без основания
     * логарифм считается десятичным; в единственном таком ответе банка —
     * «log(6^(2/log(3)))» — основание всё равно сокращается, и значение от
     * выбора не зависит.
     */
    private function expandLogarithms(string $s): ?string
    {
        $guard = 0;
        while (($pos = $this->findLogarithm($s)) !== null) {
            if (++$guard > 20) {
                return null;
            }

            [$offset, $name] = $pos;
            $before = substr($s, 0, $offset);
            $rest = substr($s, $offset + strlen($name));

            $base = $name === 'lg' ? '(10)' : null;

            if (str_starts_with($rest, '_')) {
                $rest = substr($rest, 1);
                if (str_starts_with($rest, '(')) {
                    $close = $this->matchingParen($rest, 0);
                    if ($close === null) {
                        return null;
                    }
                    $base = substr($rest, 0, $close + 1);
                    $rest = substr($rest, $close + 1);
                } elseif (preg_match('/^\d+(?:\.\d+)?/', $rest, $m)) {
                    $base = '(' . $m[0] . ')';
                    $rest = substr($rest, strlen($m[0]));
                } else {
                    return null;
                }
            }

            $base ??= '(10)';

            // Аргумент обязан быть в скобках: «log_3 84» без них читается
            // неоднозначно — «log_3(84)» это или «log_3(8)·4».
            if (!str_starts_with($rest, '(')) {
                return null;
            }
            $close = $this->matchingParen($rest, 0);
            if ($close === null) {
                return null;
            }
            $argument = substr($rest, 0, $close + 1);
            $tail = substr($rest, $close + 1);

            $s = $before . '(ln' . $argument . '/ln' . $base . ')' . $tail;
        }

        return $s;
    }

    /**
     * Ближайший логарифм и его написание. «lg» и «log» ищутся вместе, чтобы
     * левый по строке разбирался первым: у вложенных логарифмов внешний
     * обязан раскрыться раньше внутреннего.
     *
     * @return array{0:int,1:string}|null
     */
    private function findLogarithm(string $s): ?array
    {
        $best = null;
        foreach (['log', 'lg'] as $name) {
            $pos = strpos($s, $name);
            if ($pos === false) {
                continue;
            }
            if ($best === null || $pos < $best[0]) {
                $best = [$pos, $name];
            }
        }

        return $best;
    }

    /** \frac{a}{b} → ((a)/(b)), включая вложенные. */
    private function expandFractions(string $s): ?string
    {
        $guard = 0;
        while (($pos = strpos($s, '\\frac')) !== false) {
            if (++$guard > 20) {
                return null;
            }
            $num = $this->extractBraced($s, $pos + 5);
            if ($num === null) {
                return null;
            }
            [$numExpr, $afterNum] = $num;
            $den = $this->extractBraced($s, $afterNum);
            if ($den === null) {
                return null;
            }
            [$denExpr, $afterDen] = $den;

            $s = substr($s, 0, $pos) . '((' . $numExpr . ')/(' . $denExpr . '))' . substr($s, $afterDen);
        }

        return $s;
    }

    /**
     * «√12», «√(2/3)», «√ 3» → «sqrt(12)», «sqrt((2/3))», «sqrt(3)».
     * Операнд — либо сбалансированная скобка, либо число сразу после знака.
     */
    private function expandRadicals(string $s): ?string
    {
        $guard = 0;
        while (($pos = mb_strpos($s, '√')) !== false) {
            if (++$guard > 30) {
                return null;
            }
            $before = mb_substr($s, 0, $pos);
            $rest = mb_substr($s, $pos + 1);

            if ($rest === '') {
                return null;
            }

            if ($rest[0] === '(') {
                $close = $this->matchingParen($rest, 0);
                if ($close === null) {
                    return null;
                }
                $operand = substr($rest, 0, $close + 1);
                $tail = substr($rest, $close + 1);
            } elseif (preg_match('/^\d+(?:\.\d+)?/', $rest, $m)) {
                $operand = '(' . $m[0] . ')';
                $tail = substr($rest, strlen($m[0]));
            } else {
                return null;
            }

            $s = $before . 'sqrt' . $operand . $tail;
        }

        return $s;
    }

    /** «2sqrt(3)», «13pi», «sqrt(2)pi», «2(1+3)», «)(» → явное умножение. */
    private function insertImplicitMultiplication(string $s): string
    {
        // Длинные имена в чередовании идут первыми: иначе «arctg» совпало бы
        // по началу с более коротким соседом и распалось на два токена.
        $names = implode('|', array_merge(self::FUNCTIONS, array_keys(self::CONSTANTS)));

        $s = preg_replace('/(\d)(' . $names . '|\()/', '$1*$2', $s) ?? $s;
        $s = preg_replace('/\)(\d|' . $names . '|\()/', ')*$1', $s) ?? $s;
        // «pi» — не функция, после неё может стоять множитель: «pi√3».
        $s = preg_replace('/(pi)(\d|sqrt|\()/', '$1*$2', $s) ?? $s;

        return $s;
    }

    /** @return array{0:string,1:int}|null содержимое {…} и позиция за ним */
    private function extractBraced(string $s, int $start): ?array
    {
        while (isset($s[$start]) && ctype_space($s[$start])) {
            $start++;
        }
        if (!isset($s[$start]) || $s[$start] !== '{') {
            return null;
        }

        $depth = 0;
        $len = strlen($s);
        for ($i = $start; $i < $len; $i++) {
            if ($s[$i] === '{') {
                $depth++;
            } elseif ($s[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return [substr($s, $start + 1, $i - $start - 1), $i + 1];
                }
            }
        }

        return null;
    }

    /** Позиция парной закрывающей скобки к открывающей на $open. */
    private function matchingParen(string $s, int $open): ?int
    {
        $depth = 0;
        $len = strlen($s);
        for ($i = $open; $i < $len; $i++) {
            if ($s[$i] === '(') {
                $depth++;
            } elseif ($s[$i] === ')') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    // ------------------------------------------------------------- вычисление

    private function evaluate(string $expr): ?float
    {
        $tokens = $this->tokenize($expr);
        if ($tokens === null || $tokens === []) {
            return null;
        }

        $index = 0;
        $value = $this->parseSum($tokens, $index);
        if ($value === null || $index !== count($tokens)) {
            return null;
        }
        if (is_nan($value) || is_infinite($value)) {
            return null;
        }

        return $value;
    }

    /** @return array<int, string>|null */
    private function tokenize(string $expr): ?array
    {
        $tokens = [];
        $len = strlen($expr);
        for ($i = 0; $i < $len;) {
            $ch = $expr[$i];

            if ($ch === '*' && isset($expr[$i + 1]) && $expr[$i + 1] === '*') {
                $tokens[] = '^';
                $i += 2;
                continue;
            }
            if (str_contains('+-*/^()', $ch)) {
                $tokens[] = $ch;
                $i++;
                continue;
            }
            if (ctype_digit($ch) || $ch === '.') {
                $start = $i;
                $dots = 0;
                while ($i < $len && (ctype_digit($expr[$i]) || $expr[$i] === '.')) {
                    if ($expr[$i] === '.' && ++$dots > 1) {
                        return null;
                    }
                    $i++;
                }
                $tokens[] = substr($expr, $start, $i - $start);
                continue;
            }
            $name = $this->matchName($expr, $i);
            if ($name !== null) {
                $tokens[] = $name;
                $i += strlen($name);
                continue;
            }

            return null;
        }

        return $tokens;
    }

    /** Имя функции или константы, начинающееся на позиции $i. */
    private function matchName(string $expr, int $i): ?string
    {
        foreach (array_merge(self::FUNCTIONS, array_keys(self::CONSTANTS)) as $name) {
            if (substr($expr, $i, strlen($name)) === $name) {
                return $name;
            }
        }

        return null;
    }

    /** sum := product (('+' | '-') product)* */
    private function parseSum(array $tokens, int &$i): ?float
    {
        $value = $this->parseProduct($tokens, $i);
        if ($value === null) {
            return null;
        }

        while ($i < count($tokens) && ($tokens[$i] === '+' || $tokens[$i] === '-')) {
            $op = $tokens[$i++];
            $rhs = $this->parseProduct($tokens, $i);
            if ($rhs === null) {
                return null;
            }
            $value = $op === '+' ? $value + $rhs : $value - $rhs;
        }

        return $value;
    }

    /** product := power (('*' | '/') power)* */
    private function parseProduct(array $tokens, int &$i): ?float
    {
        $value = $this->parsePower($tokens, $i);
        if ($value === null) {
            return null;
        }

        while ($i < count($tokens) && ($tokens[$i] === '*' || $tokens[$i] === '/')) {
            $op = $tokens[$i++];
            $rhs = $this->parsePower($tokens, $i);
            if ($rhs === null) {
                return null;
            }
            if ($op === '/') {
                if (abs($rhs) < 1e-12) {
                    return null;
                }
                $value /= $rhs;
            } else {
                $value *= $rhs;
            }
        }

        return $value;
    }

    /** power := unary ('^' power)? — степень правоассоциативна. */
    private function parsePower(array $tokens, int &$i): ?float
    {
        $base = $this->parseUnary($tokens, $i);
        if ($base === null) {
            return null;
        }
        if ($i < count($tokens) && $tokens[$i] === '^') {
            $i++;
            $exp = $this->parsePower($tokens, $i);
            if ($exp === null) {
                return null;
            }

            return $base ** $exp;
        }

        return $base;
    }

    private function parseUnary(array $tokens, int &$i): ?float
    {
        if ($i < count($tokens) && ($tokens[$i] === '-' || $tokens[$i] === '+')) {
            $op = $tokens[$i++];
            $value = $this->parseUnary($tokens, $i);
            if ($value === null) {
                return null;
            }

            return $op === '-' ? -$value : $value;
        }

        return $this->parseAtom($tokens, $i);
    }

    private function parseAtom(array $tokens, int &$i): ?float
    {
        if ($i >= count($tokens)) {
            return null;
        }
        $token = $tokens[$i];

        if (isset(self::CONSTANTS[$token])) {
            $i++;

            return self::CONSTANTS[$token];
        }

        if (in_array($token, self::FUNCTIONS, true)) {
            $i++;
            if (($tokens[$i] ?? null) !== '(') {
                return null;
            }
            $i++;
            $inner = $this->parseSum($tokens, $i);
            if ($inner === null || ($tokens[$i] ?? null) !== ')') {
                return null;
            }
            $i++;

            return $this->applyFunction($token, $inner);
        }

        if ($token === '(') {
            $i++;
            $value = $this->parseSum($tokens, $i);
            if ($value === null || ($tokens[$i] ?? null) !== ')') {
                return null;
            }
            $i++;

            return $value;
        }

        if (preg_match('/^\d*\.?\d+$/', $token) || preg_match('/^\d+\.?\d*$/', $token)) {
            $i++;

            return (float) $token;
        }

        return null;
    }

    /**
     * Значение функции. Вне области определения — null: ответ не считается
     * ни верным, ни неверным по числу, и сверка уходит на сравнение строк.
     */
    private function applyFunction(string $name, float $argument): ?float
    {
        return match ($name) {
            'sqrt' => $argument < 0 ? null : sqrt($argument),
            'ln' => $argument <= 0 ? null : log($argument),
            'arcsin' => abs($argument) > 1 ? null : asin($argument),
            'arccos' => abs($argument) > 1 ? null : acos($argument),
            'arctan', 'arctg' => atan($argument),
            default => null,
        };
    }
}
