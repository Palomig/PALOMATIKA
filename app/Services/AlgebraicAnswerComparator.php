<?php

namespace App\Services;

/**
 * Сверка буквенных ответов — «2x/(3y)», «(a+5)/(a-5)», «-8(a+b)/(a-b)».
 *
 * Дословно их не сверить: одна и та же сокращённая дробь записывается
 * десятком способов, и ученик почти никогда не попадёт в строку эталона:
 *
 *     2x/(3y)   2x/3y   (2x)/(3y)   2*x/(3*y)   \frac{2x}{3y}
 *     -(a+5)/3  (-a-5)/3   -(5+a)/3
 *     4(a-b)    4a-4b
 *
 * Поэтому сравниваем не запись, а значение: подставляем в обе записи
 * одни и те же числа и смотрим, совпали ли результаты. Точки берём
 * иррациональные и заведомо не круглые — на них случайно совпасть нельзя,
 * а набор фиксирован, так что проверка воспроизводима.
 *
 * Юкстапозиция («3y») связывает крепче деления: «2x/3y» — это 2x/(3y), как
 * и пишут в тетради, а не (2x/3)·y.
 *
 * Включается только там, где эталон буквенный (см. {@see looksAlgebraic()}):
 * числа, множества и π-формы ОГЭ и ЕГЭ идут прежними ветками.
 */
class AlgebraicAnswerComparator
{
    /** Сколько точек обязано совпасть, чтобы признать ответы равными. */
    private const REQUIRED_POINTS = 3;

    /** Ни одного целого и ни одного круглого: случайное совпадение исключено. */
    private const BASE_POINTS = [2.3, 3.7, 5.11, 7.13, 11.17, 13.19, 17.23, 19.29, 23.31, 29.37, 31.41];

    private const ATTEMPTS = 6;

    /** Кириллица, неотличимая на вид от латиницы: ученик набрал не в той раскладке. */
    private const HOMOGLYPHS = [
        'а' => 'a', 'в' => 'b', 'с' => 'c', 'е' => 'e', 'ё' => 'e', 'к' => 'k',
        'м' => 'm', 'о' => 'o', 'р' => 'p', 'т' => 't', 'у' => 'y', 'х' => 'x',
    ];

    private const SUPERSCRIPTS = ['²' => '^2', '³' => '^3', '⁴' => '^4', '⁵' => '^5', '⁶' => '^6'];

    /** Имена функций и констант: буква внутри них — не переменная. */
    private const RESERVED = ['arcsin', 'arccos', 'arctg', 'arcctg', 'sqrt', 'log', 'lg', 'ln',
                              'sin', 'cos', 'tg', 'ctg', 'pi'];

    private string $src = '';
    private int $pos = 0;
    /** @var array<string, float> */
    private array $values = [];

    /**
     * Буквенный ли эталон. Числа, наборы корней и ответы через π, log и
     * радикалы сюда не попадают — у них свои, более строгие правила.
     */
    public function looksAlgebraic(?string $answer): bool
    {
        $s = mb_strtolower(trim((string) $answer));
        if ($s === '' || str_contains($s, '√') || str_contains($s, 'π') || str_contains($s, ';')) {
            return false;
        }
        $s = str_replace(self::RESERVED, ' ', $s);

        // Смотрим ровно на латиницу: «нет в базе» и прочая проза эталоном
        // буквенного ответа не являются, хотя на вид в них есть «e» и «a».
        return (bool) preg_match('/[a-z]/', $s);
    }

    /**
     * Равны ли ответы как выражения.
     *
     * @return bool|null null — эталон или ответ не разобрать, решает вызывающий
     */
    public function equals(?string $reference, ?string $user): ?bool
    {
        $ref = $this->normalize((string) $reference);
        $usr = $this->normalize((string) $user);
        if ($ref === '' || $usr === '') {
            return null;
        }

        $refVars = $this->variables($ref);
        if ($refVars === [] || !$this->parses($ref)) {
            return null;    // эталон числовой или не разбирается — не наша ветка
        }
        // Дальше эталон заведомо выражение, значит всё, что с ним не сходится,
        // — ошибка ученика, а не повод отдавать сверку строке.
        if (!$this->parses($usr)) {
            return false;   // ученик написал то, что выражением не является
        }
        // Ответ про другие буквы — это другой ответ, считать значения незачем.
        if ($refVars !== $this->variables($usr)) {
            return false;
        }
        // Значение то же, а записано не в том виде, о котором спрашивали:
        // переписанное условие вместо раскрытых скобок и наоборот.
        if (!$this->formMatches($ref, $usr)) {
            return false;
        }

        $matched = 0;
        for ($attempt = 0; $attempt < self::ATTEMPTS; $attempt++) {
            $point = $this->point($refVars, $attempt);

            $a = $this->evaluate($ref, $point);
            $b = $this->evaluate($usr, $point);
            if ($a === null || $b === null) {
                continue;   // точка не подошла (деление на ноль) — берём следующую
            }

            if (abs($a - $b) > 1e-7 * max(1.0, abs($a), abs($b))) {
                return false;
            }
            $matched++;
        }

        return $matched >= self::REQUIRED_POINTS ? true : null;
    }

    /**
     * Форма ответа берётся из записи эталона — больше до проверки ничего не доходит.
     *
     *  - эталон без скобок («x^2+6x+9», «12x», «x-3»): скобок нет и у ученика,
     *    а слагаемых столько же — иначе засчитался бы ответ без раскрытых скобок
     *    или без приведения подобных («(x+3)^2», «x^2+3x+3x+9»);
     *  - эталон-произведение («(x-3)(x+3)», «3(a+1)^2»): у ученика тоже
     *    произведение, и скобочных множителей не меньше — «x^2-9» или
     *    «3(x^2-4)» за разложение на множители не считаются.
     *
     * Дроби («2x/3y», «(a+5)/(a-5)») не трогаем: там равны любые записи.
     */
    private function formMatches(string $ref, string $usr): bool
    {
        $ref = $this->collapseExponents($ref);
        $usr = $this->collapseExponents($usr);
        if (str_contains($ref, '/')) {
            return true;
        }

        if (!str_contains($ref, '(')) {
            return !str_contains($usr, '(') && $this->termCount($usr) === $this->termCount($ref);
        }

        if ($this->termCount($ref) === 1) {
            $usr = $this->stripOuterParens($usr);
            return $this->termCount($usr) === 1 && $this->bracketFactors($usr) >= $this->bracketFactors($ref);
        }

        return true;
    }

    /** «x^(2)» из «x^{2}» — показатель, а не скобочный множитель. */
    private function collapseExponents(string $s): string
    {
        return preg_replace('/\^\((\d+)\)/', '^$1', $s) ?? $s;
    }

    /** Слагаемых на верхнем уровне: знак в начале, в показателе или после «*» не считаем. */
    private function termCount(string $s): int
    {
        $terms = 1;
        $depth = 0;
        for ($i = 0, $n = strlen($s); $i < $n; $i++) {
            $ch = $s[$i];
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
            } elseif (($ch === '+' || $ch === '-') && $depth === 0 && $i > 0
                && !in_array($s[$i - 1], ['^', '*', '/', '(', '+', '-'], true)) {
                $terms++;
            }
        }

        return $terms;
    }

    /** Скобочных множителей верхнего уровня; «(a+1)^2» — это два. */
    private function bracketFactors(string $s): int
    {
        $count = 0;
        $depth = 0;
        for ($i = 0, $n = strlen($s); $i < $n; $i++) {
            if ($s[$i] === '(') {
                $depth++;
            } elseif ($s[$i] === ')') {
                $depth--;
                if ($depth === 0) {
                    $power = preg_match('/^\^(\d+)/', substr($s, $i + 1), $m) ? (int) $m[1] : 1;
                    $count += max(1, $power);
                }
            }
        }

        return $count;
    }

    /** «((x-3)(x+3))» → «(x-3)(x+3)»: лишняя внешняя пара скобок формы не меняет. */
    private function stripOuterParens(string $s): string
    {
        while (strlen($s) > 1 && $s[0] === '(' && $this->closingParen($s, 0) === strlen($s) - 1) {
            $s = substr($s, 1, -1);
        }

        return $s;
    }

    private function closingParen(string $s, int $open): ?int
    {
        $depth = 0;
        for ($i = $open, $n = strlen($s); $i < $n; $i++) {
            if ($s[$i] === '(') {
                $depth++;
            } elseif ($s[$i] === ')' && --$depth === 0) {
                return $i;
            }
        }

        return null;
    }

    private function applyHomoglyphs(string $s): string
    {
        return strtr($s, self::HOMOGLYPHS);
    }

    /** Приводит запись к виду, который понимает разборщик ниже. */
    private function normalize(string $value): string
    {
        $s = mb_strtolower(trim($value));
        $s = $this->applyHomoglyphs($s);
        $s = strtr($s, self::SUPERSCRIPTS);
        $s = str_replace(['−', '–', '—'], '-', $s);
        $s = str_replace(['·', '×', '\\cdot', '\\times'], '*', $s);
        $s = str_replace(['\\left', '\\right', '\\,', '\\;', '\\ ', '$', ' ', "\t"], '', $s);
        $s = $this->expandFractions($s);
        $s = str_replace([':', '÷'], '/', $s);
        $s = str_replace(',', '.', $s);
        $s = str_replace(['{', '}'], ['(', ')'], $s);

        return $s;
    }

    /** \frac{A}{B} и \dfrac{A}{B} → ((A)/(B)). */
    private function expandFractions(string $s): string
    {
        foreach (['\\dfrac', '\\tfrac', '\\frac'] as $macro) {
            while (($at = strpos($s, $macro)) !== false) {
                $num = $this->group($s, $at + strlen($macro), $end);
                if ($num === null) {
                    return str_replace($macro, '', $s);
                }
                $den = $this->group($s, $end, $end2);
                if ($den === null) {
                    return str_replace($macro, '', $s);
                }
                $s = substr($s, 0, $at) . '((' . $num . ')/(' . $den . '))' . substr($s, $end2);
            }
        }

        return $s;
    }

    /** Содержимое { … }, начиная с открывающей скобки на позиции $from. */
    private function group(string $s, int $from, ?int &$end = null): ?string
    {
        if (($s[$from] ?? '') !== '{') {
            return null;
        }
        $depth = 0;
        for ($i = $from; $i < strlen($s); $i++) {
            if ($s[$i] === '{') {
                $depth++;
            } elseif ($s[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    $end = $i + 1;
                    return substr($s, $from + 1, $i - $from - 1);
                }
            }
        }

        return null;
    }

    /** @return array<int, string> буквы выражения, по алфавиту и без повторов */
    private function variables(string $normalized): array
    {
        preg_match_all('/[a-z]/', $normalized, $m);
        $vars = array_values(array_unique($m[0]));
        sort($vars);

        return $vars;
    }

    /**
     * @param array<int, string> $vars
     * @return array<string, float>
     */
    private function point(array $vars, int $attempt): array
    {
        $point = [];
        foreach ($vars as $i => $var) {
            $base = self::BASE_POINTS[($i + $attempt) % count(self::BASE_POINTS)];
            $point[$var] = $base + $attempt * 0.617;
        }

        return $point;
    }

    private function parses(string $normalized): bool
    {
        // Точка, на которой ничего не обнуляется: годится, чтобы отличить
        // «выражение с запретной точкой» от «не выражение вовсе».
        return $this->evaluate($normalized, $this->point($this->variables($normalized), 0)) !== null
            || $this->evaluate($normalized, $this->point($this->variables($normalized), 3)) !== null;
    }

    /**
     * @param array<string, float> $point
     * @return float|null null — не разобралось или деление на ноль
     */
    private function evaluate(string $expression, array $point): ?float
    {
        $this->src = $expression;
        $this->pos = 0;
        $this->values = $point;

        try {
            $value = $this->expr();
        } catch (\Throwable) {
            return null;
        }

        if ($this->pos !== strlen($this->src) || !is_finite($value)) {
            return null;
        }

        return $value;
    }

    // ─── разборщик: expr → term → juxt → power → atom ────────────────────

    private function expr(): float
    {
        $value = $this->term();
        while ($this->pos < strlen($this->src) && ($this->src[$this->pos] === '+' || $this->src[$this->pos] === '-')) {
            $op = $this->src[$this->pos++];
            $rhs = $this->term();
            $value = $op === '+' ? $value + $rhs : $value - $rhs;
        }

        return $value;
    }

    private function term(): float
    {
        $sign = 1.0;
        while ($this->pos < strlen($this->src) && ($this->src[$this->pos] === '-' || $this->src[$this->pos] === '+')) {
            if ($this->src[$this->pos++] === '-') {
                $sign = -$sign;
            }
        }

        $value = $sign * $this->juxt();
        while ($this->pos < strlen($this->src) && ($this->src[$this->pos] === '*' || $this->src[$this->pos] === '/')) {
            $op = $this->src[$this->pos++];
            $rhs = $this->juxt();
            if ($op === '/') {
                if (abs($rhs) < 1e-9) {
                    throw new \DomainException('division by zero');
                }
                $value /= $rhs;
            } else {
                $value *= $rhs;
            }
        }

        return $value;
    }

    /** Множители, записанные подряд: «3y», «2x(a+1)» — связывают крепче деления. */
    private function juxt(): float
    {
        $value = $this->power();
        while ($this->pos < strlen($this->src) && $this->startsAtom($this->src[$this->pos])) {
            $value *= $this->power();
        }

        return $value;
    }

    private function startsAtom(string $ch): bool
    {
        return $ch === '(' || ctype_alpha($ch) || ctype_digit($ch) || $ch === '.';
    }

    private function power(): float
    {
        $base = $this->atom();
        if ($this->pos < strlen($this->src) && $this->src[$this->pos] === '^') {
            $this->pos++;
            $sign = 1.0;
            while ($this->pos < strlen($this->src) && ($this->src[$this->pos] === '-' || $this->src[$this->pos] === '+')) {
                if ($this->src[$this->pos++] === '-') {
                    $sign = -$sign;
                }
            }
            $exp = $sign * $this->atom();
            if (abs($base) < 1e-12 && $exp < 0) {
                throw new \DomainException('zero to negative power');
            }
            $base = $base ** $exp;
        }

        return $base;
    }

    private function atom(): float
    {
        if ($this->pos >= strlen($this->src)) {
            throw new \DomainException('unexpected end');
        }

        $ch = $this->src[$this->pos];

        if ($ch === '(') {
            $this->pos++;
            $value = $this->expr();
            if (($this->src[$this->pos] ?? '') !== ')') {
                throw new \DomainException('missing )');
            }
            $this->pos++;

            return $value;
        }

        if (ctype_digit($ch) || $ch === '.') {
            $start = $this->pos;
            while ($this->pos < strlen($this->src)
                && (ctype_digit($this->src[$this->pos]) || $this->src[$this->pos] === '.')) {
                $this->pos++;
            }

            return (float) substr($this->src, $start, $this->pos - $start);
        }

        if (ctype_alpha($ch)) {
            $this->pos++;
            if (!array_key_exists($ch, $this->values)) {
                throw new \DomainException("unknown variable {$ch}");
            }

            return $this->values[$ch];
        }

        throw new \DomainException("unexpected {$ch}");
    }
}
