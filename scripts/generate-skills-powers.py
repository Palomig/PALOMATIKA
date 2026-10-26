#!/usr/bin/env python3
"""
Генератор темы «Степени» банка «Скиллы» (задача #62 блокнота Джарвиса, 25.10.2026).

Два класса по три уровня из 100 примеров:

  7 класс — натуральный показатель
    Уровень 1 — свойства степеней: a⁵·a³, a⁹:a⁴, (a³)⁴, (a²)³·a⁵:a⁷.
    Уровень 2 — вычислить удобным способом: 2¹⁰:2⁷, 5³·2³, (3⁴)²:3⁷, 4⁵:2⁸.
    Уровень 3 — одночлены: 3a²b·(−2ab³), (−2x³y)²·5xy, (2a²b)³.
    Уровень 4 — разложить основания на простые: 36³/(2⁵·3⁶), 8⁵/16³, 75²·2³/(15·10²)
               (добавлен 26.10, свой seed — прежние примеры не сдвигаются).
  8 класс — целый показатель
    Уровень 1 — вычислить: 2⁻³, (2/3)⁻², 5⁻²·5⁴, 3⁻⁴:3⁻⁶, 0,5⁻².
    Уровень 2 — упростить: a⁻³·a⁵, (x⁻²)³·x⁸, (a⁻²b³)⁻²·a⁵b⁷.
    Уровень 3 — числа в стандартном виде: (2,4·10⁻³)·(5·10⁶), (9·10⁵):(3·10²).

10–11 класс не делаем: дробный показатель уже есть в «Арифметическом корне».

Буквенный ответ — одночлен в стандартном виде («-6a^3b^4»): проверка на сайте
не засчитает ни переписанное условие, ни «a^5*a^3» (см. isStandardMonomial).
Показатели в ответе всегда натуральные — отрицательные только в условии.
Числовой ответ — целое, несократимая дробь «9/4» или десятичная «0,036».

Каждый пример пересчитывается sympy по самому условию: ответ обязан совпасть
со значением, буквенный — тождественно. Ошибка роняет генерацию.

Запуск:  python3 scripts/generate-skills-powers.py
Пишет:   database/data/skills/powers.json (seed фиксирован)
"""

import json
import random
from fractions import Fraction
from pathlib import Path

import sympy
from sympy.parsing.sympy_parser import (convert_xor, implicit_multiplication_application,
                                        parse_expr, rationalize, standard_transformations)

SEED = 20261026
PER_LEVEL = 100
OUT = Path(__file__).resolve().parent.parent / 'database' / 'data' / 'skills' / 'powers.json'
TRANSFORMS = standard_transformations + (convert_xor, implicit_multiplication_application, rationalize)
VARS = ['a', 'b', 'c', 'x', 'y', 'm', 'n', 'p', 'k']
SIMPLIFY, COMPUTE = 'Упростите', 'Вычислите'
STANDARD = 'Найдите значение выражения'


def nz(rng, lo, hi):
    return rng.randint(lo, hi) * rng.choice([1, -1])


def pw(base: str, e: int) -> str:
    return base if e == 1 else f'{base}^{{{e}}}'


def num(q) -> str:
    q = Fraction(q)
    if q.denominator == 1:
        return str(q.numerator)
    s = '-' if q < 0 else ''
    return f'{s}\\dfrac{{{abs(q.numerator)}}}{{{q.denominator}}}'


def dec(q) -> str:
    s = f'{float(q):.12f}'.rstrip('0').rstrip('.')
    return s.replace('.', '{,}')


def ans_num(q) -> str:
    q = Fraction(q)
    if q.denominator == 1:
        return str(q.numerator)
    # конечная десятичная — запятой, иначе дробью
    d = q.denominator
    while d % 2 == 0:
        d //= 2
    while d % 5 == 0:
        d //= 5
    if d == 1 and STANDARD_MODE[0]:
        return f'{float(q):.12f}'.rstrip('0').rstrip('.').replace('.', ',')
    return f'{q.numerator}/{q.denominator}'


STANDARD_MODE = [False]


def mono_answer(coef, powers: dict) -> str:
    """-6a^3b^4: коэффициент и буквы в порядке появления."""
    coef = Fraction(coef)
    assert coef.denominator == 1, coef
    body = ''.join(v if e == 1 else f'{v}^{e}' for v, e in powers.items() if e != 0)
    assert all(e >= 0 for e in powers.values()), powers
    c = int(coef)
    if body == '':
        return str(c)
    if c == 1:
        return body
    if c == -1:
        return '-' + body
    return f'{c}{body}'


def mono_tex(coef, powers: dict) -> str:
    c = Fraction(coef)
    body = ''.join(pw(v, e) for v, e in powers.items() if e != 0)
    if body == '':
        return num(c)
    if c == 1:
        return body
    if c == -1:
        return '-' + body
    if c.denominator != 1:
        return dec(c) + body if abs(c) < 1 and c.denominator in (2, 4, 5, 10) else num(c) + body
    return f'{c.numerator}{body}'


def to_sympy(tex: str):
    s = (tex.replace('\\cdot', '*').replace(':', '/').replace('{,}', '.')
         .replace('\\left', '').replace('\\right', ''))
    s = s.replace('{', '(').replace('}', ')')
    s = s.replace('\\dfrac(', 'frac(')
    # \dfrac(a)(b) → ((a)/(b))
    while 'frac(' in s:
        i = s.index('frac(')
        depth, j = 0, i + 4
        while True:
            depth += s[j] == '('
            depth -= s[j] == ')'
            if depth == 0:
                break
            j += 1
        k, depth = j + 1, 0
        while True:
            depth += s[k] == '('
            depth -= s[k] == ')'
            if depth == 0:
                break
            k += 1
        s = s[:i] + f'(({s[i + 4:j + 1]})/({s[j + 1:k + 1]}))' + s[k + 1:]
    return parse_expr(s, transformations=TRANSFORMS, evaluate=True)


# ── 7 класс ─────────────────────────────────────────────────────────────────

def p7_mul(rng):
    v = rng.choice(VARS)
    m, n = rng.randint(2, 12), rng.randint(2, 12)
    return f'{pw(v, m)}\\cdot {pw(v, n)}', {v: m + n}, 1


def p7_div(rng):
    v = rng.choice(VARS)
    n = rng.randint(2, 10)
    m = n + rng.randint(1, 10)
    return f'{pw(v, m)}:{pw(v, n)}', {v: m - n}, 1


def p7_pow(rng):
    v = rng.choice(VARS)
    m, n = rng.randint(2, 9), rng.randint(2, 6)
    return f'({pw(v, m)})^{{{n}}}', {v: m * n}, 1


def p7_three(rng):
    v = rng.choice(VARS)
    m, n, k = rng.randint(1, 9), rng.randint(2, 9), rng.randint(2, 9)
    return f'{pw(v, m)}\\cdot {pw(v, n)}\\cdot {pw(v, k)}', {v: m + n + k}, 1


def p7_mixed(rng):
    v = rng.choice(VARS)
    m, n, k = rng.randint(2, 5), rng.randint(2, 4), rng.randint(1, 8)
    total = m * n + k
    l = rng.randint(1, total - 1)
    return f'({pw(v, m)})^{{{n}}}\\cdot {pw(v, k)}:{pw(v, l)}', {v: total - l}, 1


def p7_frac(rng):
    v = rng.choice(VARS)
    m, n = rng.randint(2, 8), rng.randint(2, 8)
    top = m + n
    d = rng.randint(2, top - 1)
    return f'\\dfrac{{{pw(v, m)}\\cdot {pw(v, n)}}}{{{pw(v, d)}}}', {v: top - d}, 1


def c7_same_base(rng):
    b = rng.choice([2, 3, 5, 7, 10, 2, 3])
    n = rng.randint(3, 15)
    m = n + rng.randint(1, 4 if b <= 3 else 2)
    return f'{b}^{{{m}}}:{b}^{{{n}}}', None, None


def c7_same_exp(rng):
    """aⁿ·bⁿ = (ab)ⁿ и aⁿ:bⁿ = (a/b)ⁿ — показатели одинаковые, основания нет."""
    if rng.random() < 0.4:
        a, b = rng.choice([(2, 5), (5, 2), (4, 25), (25, 4), (20, 5), (2, 50), (4, 5), (5, 4), (2, 3), (3, 3)])
        n = rng.randint(2, 5)
        return f'{a}^{{{n}}}\\cdot {b}^{{{n}}}', None, None
    a, b = rng.choice([(12, 4), (15, 5), (18, 6), (21, 7), (24, 8), (36, 9), (48, 12), (30, 6), (35, 7), (60, 12), (45, 9), (28, 7)])
    n = rng.randint(2, 5)
    return f'{a}^{{{n}}}:{b}^{{{n}}}', None, None


def c7_pow_div(rng):
    b = rng.choice([2, 3, 5])
    m, n = rng.randint(2, 5), rng.randint(2, 4)
    k = m * n - rng.randint(1, 3 if b == 2 else 2)
    return f'({b}^{{{m}}})^{{{n}}}:{b}^{{{k}}}', None, None


def c7_change_base(rng):
    small, big = rng.choice([(2, 4), (2, 8), (3, 9), (3, 27), (5, 25), (2, 16)])
    e = {4: 2, 8: 3, 9: 2, 27: 3, 25: 2, 16: 4}[big]
    n = rng.randint(2, 5)
    k = e * n - rng.randint(1, 3 if small == 2 else 2)
    return f'{big}^{{{n}}}:{small}^{{{k}}}', None, None


def c7_frac_powers(rng):
    b = rng.choice([2, 3, 2])
    m, n = rng.randint(3, 10), rng.randint(3, 10)
    d = m + n - rng.randint(1, 4 if b == 2 else 3)
    return f'\\dfrac{{{b}^{{{m}}}\\cdot {b}^{{{n}}}}}{{{b}^{{{d}}}}}', None, None


def c7_decimal_pair(rng):
    """0,25⁶·4⁸: множители с произведением 1, остаётся степень второго."""
    a, b = rng.choice([(Fraction(1, 4), 4), (Fraction(1, 2), 2), (Fraction(1, 5), 5), (Fraction(1, 10), 10)])
    n = rng.randint(3, 12)
    extra = rng.randint(1, 3 if b <= 4 else 2)
    return f'{dec(a)}^{{{n}}}\\cdot {b}^{{{n + extra}}}', None, None


def m7_product(rng):
    v, w = rng.sample(VARS[:6], 2)
    c1, c2 = nz(rng, 1, 9), nz(rng, 1, 9)
    if abs(c1) == 1:
        c1 = 2 * (1 if c1 > 0 else -1)
    e = {v: rng.randint(1, 5), w: rng.randint(1, 5)}
    f = {v: rng.randint(1, 4), w: rng.randint(0, 4)}
    left = mono_tex(c1, e)
    right = mono_tex(c2, f)
    right = f'({right})' if c2 < 0 else right
    return f'{left}\\cdot {right}', {v: e[v] + f[v], w: e[w] + f[w]}, c1 * c2


def m7_power(rng):
    v, w = rng.sample(VARS[:6], 2)
    c = nz(rng, 1, 4)
    e = {v: rng.randint(1, 4), w: rng.randint(0, 3)}
    n = rng.randint(2, 4 if abs(c) <= 2 else 3)
    return f'({mono_tex(c, e)})^{{{n}}}', {k: val * n for k, val in e.items()}, c ** n


def m7_power_times(rng):
    v, w = rng.sample(VARS[:6], 2)
    c = nz(rng, 1, 3)
    e = {v: rng.randint(1, 3), w: rng.randint(1, 2)}
    n = rng.randint(2, 3)
    d = rng.randint(2, 6) * rng.choice([1, -1])
    f = {v: rng.randint(1, 3), w: rng.randint(0, 2)}
    right = mono_tex(d, f)
    right = f'({right})' if d < 0 else right
    return (f'({mono_tex(c, e)})^{{{n}}}\\cdot {right}',
            {v: e[v] * n + f[v], w: e[w] * n + f[w]}, c ** n * d)


def m7_decimal_coef(rng):
    v, w = rng.sample(VARS[:6], 2)
    a = rng.choice([Fraction(1, 2), Fraction(1, 4), Fraction(1, 5), Fraction(3, 2), Fraction(5, 2)])
    k = rng.randint(1, 8) * a.denominator * rng.choice([1, -1])
    e = {v: rng.randint(1, 4), w: rng.randint(1, 3)}
    f = {v: rng.randint(1, 3), w: rng.randint(0, 3)}
    left = mono_tex(k, e)
    right = mono_tex(a if rng.random() < 0.5 else -a, f)
    sign = 1 if not right.startswith('-') else -1
    right = f'({right})' if right.startswith('-') else right
    return f'{left}\\cdot {right}', {v: e[v] + f[v], w: e[w] + f[w]}, k * a * sign


# ── 8 класс ─────────────────────────────────────────────────────────────────

def n8_single(rng):
    b = rng.choice([2, 3, 4, 5, 10, 2, 3])
    n = rng.randint(1, 4 if b <= 3 else 3)
    return f'{b}^{{-{n}}}', None, None


def n8_neg_base(rng):
    b = rng.choice([2, 3, 5])
    n = rng.randint(1, 3)
    return f'(-{b})^{{-{n}}}', None, None


def n8_fraction_base(rng):
    p, q = rng.choice([(2, 3), (3, 4), (1, 2), (1, 3), (2, 5), (3, 2), (5, 2), (4, 3), (1, 5)])
    n = rng.randint(1, 3)
    return f'\\left(\\dfrac{{{p}}}{{{q}}}\\right)^{{-{n}}}', None, None


def n8_decimal_base(rng):
    a = rng.choice([Fraction(1, 2), Fraction(1, 5), Fraction(1, 10), Fraction(1, 4), Fraction(2, 10)])
    n = rng.randint(1, 3)
    return f'{dec(a)}^{{-{n}}}', None, None


def n8_mul(rng):
    b = rng.choice([2, 3, 5, 10])
    m = rng.randint(-8, -1)
    n = rng.randint(1, 9)
    if m + n == 0 or abs(m + n) > (4 if b <= 3 else 3):
        n = -m + rng.choice([1, 2, -1])
    return f'{b}^{{{m}}}\\cdot {b}^{{{n}}}', None, None


def n8_div(rng):
    b = rng.choice([2, 3, 5, 10])
    m, n = -rng.randint(1, 8), -rng.randint(1, 8)
    if m == n:
        n -= 1
    if abs(m - n) > (4 if b <= 3 else 3):
        n = m - rng.choice([1, 2, -1, -2])
    return f'{b}^{{{m}}}:{b}^{{{n}}}', None, None


def n8_pow(rng):
    b = rng.choice([2, 3, 5])
    m, n = rng.randint(1, 3), rng.randint(1, 2)
    k = rng.randint(-3, 3)
    return f'({b}^{{-{m}}})^{{-{n}}}\\cdot {b}^{{{k}}}', None, None


def n8_sum(rng):
    b, c = rng.sample([2, 3, 4, 5], 2)
    return f'{b}^{{-1}}+{c}^{{-1}}', None, None


def s8_mul(rng):
    v = rng.choice(VARS)
    m = -rng.randint(1, 9)
    n = -m + rng.randint(1, 8)
    return f'{pw(v, m)}\\cdot {pw(v, n)}', {v: m + n}, 1


def s8_div(rng):
    v = rng.choice(VARS)
    m = -rng.randint(1, 6)
    n = m - rng.randint(1, 6)
    return f'{pw(v, m)}:{pw(v, n)}', {v: m - n}, 1


def s8_pow_mul(rng):
    v = rng.choice(VARS)
    m, n = rng.randint(1, 4), rng.randint(2, 4)
    k = m * n + rng.randint(1, 6)
    return f'({pw(v, -m)})^{{{n}}}\\cdot {pw(v, k)}', {v: k - m * n}, 1


def s8_two_vars(rng):
    v, w = rng.sample(VARS[:6], 2)
    a, b = rng.randint(1, 4), rng.randint(1, 4)
    n = rng.randint(2, 3)
    # (v^{-a} w^{b})^{-n} = v^{an} w^{-bn}; домножаем, чтобы показатели стали натуральными
    cv, cw = -rng.randint(0, a * n - 1), b * n + rng.randint(1, 4)
    ev, ew = a * n + cv, -b * n + cw
    return (f'({pw(v, -a)}{pw(w, b)})^{{-{n}}}\\cdot {pw(v, cv) if cv else ""}{pw(w, cw)}',
            {v: ev, w: ew}, 1)


def s8_coef(rng):
    v, w = rng.sample(VARS[:6], 2)
    c1 = rng.choice([2, 3, 4, 5, 6, 8])
    c2 = rng.choice([2, 3, 4, 5, 6])
    m1, m2 = -rng.randint(1, 5), rng.randint(1, 5)
    n1, n2 = rng.randint(2, 8), -rng.randint(0, 4)
    if m1 + n1 <= 0:
        n1 = -m1 + rng.randint(1, 3)
    if m2 + n2 <= 0:
        n2 = -m2 + rng.randint(1, 2)
    return (f'{c1}{pw(v, m1)}{pw(w, m2)}\\cdot {c2}{pw(v, n1)}{pw(w, n2) if n2 else ""}',
            {v: m1 + n1, w: m2 + n2}, c1 * c2)


def sci(rng):
    """(a·10^m)·(b·10^n) или (a·10^m):(b·10^n) с конечным ответом."""
    ops = rng.choice(['mul', 'mul', 'div'])
    while True:
        a = Fraction(rng.randint(11, 99), 10) if rng.random() < 0.6 else Fraction(rng.randint(1, 9))
        b = Fraction(rng.randint(11, 99), 10) if rng.random() < 0.4 else Fraction(rng.randint(1, 9))
        m, n = rng.choice([k for k in range(-6, 8) if k != 0]), rng.choice([k for k in range(-6, 8) if k != 0])
        if a == 1 or b == 1 or (a == b and m == n):
            continue
        val = a * b * Fraction(10) ** (m + n) if ops == 'mul' else a / b * Fraction(10) ** (m - n)
        d = val.denominator
        while d % 2 == 0:
            d //= 2
        while d % 5 == 0:
            d //= 5
        digits = len(f'{float(val):.12f}'.rstrip('0').rstrip('.').replace('.', '').lstrip('0'))
        if d == 1 and Fraction(1, 10000) <= val <= 10 ** 7 and digits <= 6:
            break
    left = f'{dec(a)}\\cdot 10^{{{m}}}'
    right = f'{dec(b)}\\cdot 10^{{{n}}}'
    op = '\\cdot ' if ops == 'mul' else ':'
    return f'({left}){op}({right})', None, None


# Составные основания и их разложение на простые: 36 = 2²·3², 75 = 3·5².
FACTORS = {
    4: {2: 2}, 8: {2: 3}, 16: {2: 4}, 32: {2: 5}, 64: {2: 6}, 9: {3: 2}, 27: {3: 3}, 81: {3: 4},
    25: {5: 2}, 125: {5: 3}, 6: {2: 1, 3: 1}, 10: {2: 1, 5: 1}, 12: {2: 2, 3: 1}, 15: {3: 1, 5: 1},
    18: {2: 1, 3: 2}, 20: {2: 2, 5: 1}, 24: {2: 3, 3: 1}, 36: {2: 2, 3: 2}, 45: {3: 2, 5: 1},
    48: {2: 4, 3: 1}, 50: {2: 1, 5: 2}, 54: {2: 1, 3: 3}, 72: {2: 3, 3: 2}, 75: {3: 1, 5: 2},
    100: {2: 2, 5: 2},
}
PRIMES = {2: {2: 1}, 3: {3: 1}, 5: {5: 1}}


def b7_prime_bases(rng):
    """\\dfrac{36^{3}}{2^{5}\\cdot 3^{6}}: основания раскладываются на степени
    2, 3, 5, после чего почти всё сокращается. Ответ — небольшое число или дробь."""
    composite = list(FACTORS)
    top = rng.sample(composite, rng.choice([1, 1, 2]))
    bottom_pool = composite + list(PRIMES) * 3
    bottom = rng.sample([b for b in bottom_pool if b not in top], rng.choice([1, 2, 2]))
    bottom = list(dict.fromkeys(bottom))
    vec, parts = {}, {}
    for side, bases in ((1, top), (-1, bottom)):
        for b in bases:
            e = rng.randint(2, 9)
            parts[(side, b)] = e
            for p, k in {**FACTORS, **PRIMES}[b].items():
                vec[p] = vec.get(p, 0) + side * k * e
    # Сокращается почти всё: остаток — степени 2, 3, 5 не выше куба.
    if not vec or all(v == 0 for v in vec.values()):
        return None
    if any(abs(v) > 3 for v in vec.values()) or sum(abs(v) for v in vec.values()) > 5:
        return None
    if sum(e for (side, _), e in parts.items() if side > 0) < 3:
        return None
    # Целый ответ — основной случай, дробный — примерно каждый третий.
    if any(v < 0 for v in vec.values()) and rng.random() < 0.7:
        return None
    num = '\\cdot '.join(f'{b}^{{{parts[(1, b)]}}}' for b in top)
    den = '\\cdot '.join(f'{b}^{{{parts[(-1, b)]}}}' for b in bottom)
    return f'\\dfrac{{{num}}}{{{den}}}', None, None


LEVELS = [
    (7, '7 класс', [
        ('Уровень 1 — свойства степеней', SIMPLIFY, [(p7_mul, 20), (p7_div, 20), (p7_pow, 20), (p7_three, 10), (p7_mixed, 20), (p7_frac, 10)]),
        ('Уровень 2 — вычислить удобным способом', COMPUTE, [(c7_same_base, 20), (c7_same_exp, 15), (c7_pow_div, 20), (c7_change_base, 20), (c7_frac_powers, 15), (c7_decimal_pair, 10)]),
        ('Уровень 3 — одночлены', SIMPLIFY, [(m7_product, 35), (m7_power, 25), (m7_power_times, 25), (m7_decimal_coef, 15)]),
        # Добавлен 26.10 (Стас): свой seed — прежние уровни и 8 класс не сдвигаются,
        # номера задач, на которые уже ссылаются уроки и домашки, те же.
        ('Уровень 4 — разложить основания на простые', COMPUTE, [(b7_prime_bases, 100)], SEED + 7004),
    ]),
    (8, '8 класс', [
        ('Уровень 1 — отрицательный показатель: вычислить', COMPUTE, [(n8_single, 12), (n8_neg_base, 8), (n8_fraction_base, 15), (n8_decimal_base, 10), (n8_mul, 15), (n8_div, 15), (n8_pow, 15), (n8_sum, 10)]),
        ('Уровень 2 — отрицательный показатель: упростить', SIMPLIFY, [(s8_mul, 25), (s8_div, 20), (s8_pow_mul, 20), (s8_two_vars, 15), (s8_coef, 20)]),
        ('Уровень 3 — числа в стандартном виде', STANDARD, [(sci, 100)]),
    ]),
]


def build(rng, template, kind):
    got = template(rng)
    if got is None:
        return None
    tex, powers, coef = got
    value = sympy.simplify(to_sympy(tex))
    if powers is None:
        assert not value.free_symbols, tex
        assert value.is_rational, f'{tex} = {value}'
        q = Fraction(int(value.p), int(value.q))
        if kind == COMPUTE and (abs(q) > 100000 or (q.denominator != 1 and q.denominator > 1000)):
            return None
        STANDARD_MODE[0] = kind == STANDARD
        answer = ans_num(q)
        STANDARD_MODE[0] = False
        return tex, answer
    if any(e < 0 for e in powers.values()):
        return None
    answer = mono_answer(coef, powers)
    expect = parse_expr(answer, transformations=TRANSFORMS)
    assert sympy.simplify(value - expect) == 0, f'{tex}: {value} ≠ {answer}'
    return tex, answer


def main() -> None:
    rng = random.Random(SEED)
    seen = set()
    zadaniya = []
    for grade, title, levels in LEVELS:
        tasks, subtypes = [], []
        for li, (ltitle, kind, templates, *own_seed) in enumerate(levels):
            subtypes.append(ltitle)
            lrng = random.Random(own_seed[0]) if own_seed else rng
            for template, count in templates:
                made, guard = 0, 0
                while made < count:
                    guard += 1
                    assert guard < 20000, f'{template.__name__}: не набралось уникальных'
                    got = build(lrng, template, kind)
                    if got is None:
                        continue
                    tex, answer = got
                    shape = ''.join('?' if ch in VARS else ch for ch in tex)
                    if tex in seen or shape in seen:
                        continue
                    seen.update((tex, shape))
                    tasks.append({'id': len(tasks) + 1, 'expression': f'{kind}: ${tex}$', 'answer': answer,
                                  'status': 'production', 'subtype': li, 'level': li + 1})
                    made += 1
        assert len(tasks) == PER_LEVEL * len(levels)
        zadaniya.append({'number': grade, 'title': title, 'grade': grade, 'instruction': 'Степени',
                         'type': 'expression', 'status': 'production', 'subtypes': subtypes, 'tasks': tasks})

    data = {
        'bank': 'skills',
        'topic': '06',
        'meta': {
            'title': 'Степени',
            'description': 'Свойства степеней с натуральным и целым показателем, вычисления удобным способом, разложение оснований на простые, '
                           'одночлены, числа в стандартном виде. 7 класс — четыре уровня, 8 класс — три, по 100 примеров.',
        },
        'generator': {'script': 'scripts/generate-skills-powers.py', 'seed': SEED},
        'zadaniya': zadaniya,
    }
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(f'{OUT}: {sum(len(z["tasks"]) for z in zadaniya)} примеров')


if __name__ == '__main__':
    main()
