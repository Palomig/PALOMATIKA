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
