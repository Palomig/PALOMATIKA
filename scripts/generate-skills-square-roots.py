#!/usr/bin/env python3
"""
Генератор темы «Арифметический квадратный корень» банка «Скиллы» — 8 класс.

Четыре блока по 100 примеров: три уровня сложности и отдельная тема внутри
третьего уровня.

  Уровень 1 — извлечение корня: корень из числа, дроби, десятичной дроби и
      степени: √196, √(81/121), √2,25, √(9·16).
  Уровень 2 — вынесение множителя, умножение и деление: √72 = 6√2,
      √18·√8, √150 : √6, (3√5)².
  Уровень 3 — действия с корнями: подобные слагаемые и формулы сокращённого
      умножения: √50−√18+√8, (√11−√6)(√11+√6), (5+2√3)².
  Тема — избавление от иррациональности в знаменателе: 6/√3, 12/(√7−√3).

В каждом блоке примерно треть примеров буквенные: условие с буквами и
подстановкой — «√(ab) при a = 2, b = 18». На третьем уровне букв несколько,
и они стоят там, где работает формула сокращённого умножения.

Равные по значению записи ответа считаются верными везде: и «6√2», и «√72» —
один ответ (так же, как в ОГЭ), поэтому ответы пишем в приведённом виде, но
ученика за другую форму не наказываем.

Каждый пример проверяется sympy: условие и ответ разбираются независимо, их
разность обязана быть нулём. Ошибка роняет генерацию, а не уезжает в банк.

Запуск:  python3 scripts/generate-skills-square-roots.py
Пишет:   database/data/skills/square-roots.json (seed фиксирован)
"""

import json
import random
import re
from fractions import Fraction
from math import gcd, isqrt
from pathlib import Path

import sympy as sp

SEED = 20260928
PER_BLOCK = 100
GRADE = 8
OUT = Path(__file__).resolve().parent.parent / 'database' / 'data' / 'skills' / 'square-roots.json'

VARS = ['a', 'b', 'c', 'm', 'n', 'p', 'q', 'x', 'y']

# Числа, из которых корень не извлекается нацело, — «хвост» ответа.
NON_SQUARES = [2, 3, 5, 6, 7, 10, 11, 13, 14, 15, 17, 19, 21, 22, 23, 26, 29, 30, 31, 33, 34, 35]


# ─── запись чисел ───────────────────────────────────────────────────────────

def num(value, tex: bool) -> str:
    """Число в запись банка: 1,5 в LaTeX это «1{,}5», в ответе — «1,5»."""
    if isinstance(value, Fraction):
        if value.denominator == 1:
            return str(value.numerator)
        dec = Fraction(value)
        # Десятичной записью пользуемся, только если она конечна и коротка.
        if 10 ** 4 % dec.denominator == 0:
            s = f'{float(dec):.4f}'.rstrip('0').rstrip('.')
            return s.replace('.', '{,}') if tex else s.replace('.', ',')
        return (rf'\dfrac{{{dec.numerator}}}{{{dec.denominator}}}' if tex
                else f'{dec.numerator}/{dec.denominator}')
    return str(value)


def root(inner: str) -> str:
    return rf'\sqrt{{{inner}}}'


def radical(coef, inner: int, tex: bool) -> str:
    """k√m в нужной записи; при m = 1 корень исчезает."""
    if inner == 1:
        return num(coef, tex)
    sign = '-' if coef < 0 else ''
    c = abs(coef)
    head = '' if c == 1 else num(c, tex)
    return f'{sign}{head}{root(str(inner)) if tex else f"√{inner}"}'


def simplify_radical(n: int) -> tuple[int, int]:
    """√n = k√m: вынесенный множитель и остаток под корнем."""
    k, m = 1, n
    d = 2
    while d * d <= m:
        while m % (d * d) == 0:
            m //= d * d
            k *= d
        d += 1
    return k, m


def is_square(n: int) -> bool:
    return n >= 0 and isqrt(n) ** 2 == n


def square_free(n: int) -> bool:
    """Из n не вынести множитель: иначе «√28» в ответе — недоделанная работа."""
    return simplify_radical(n)[0] == 1


def subs_clause(pairs, tex: bool) -> str:
    """«при a = 2, b = 18» — подстановка в условии."""
    parts = [f'${v}={num(val, tex=True)}$' for v, val in pairs]
    return ' при ' + ', '.join(parts)


def pick_vars(rng, count):
    return rng.sample(VARS, count)


# ─── Уровень 1: извлечение корня ────────────────────────────────────────────

def l1_integer(rng):
    k = rng.randint(2, 30)
    return {'tex': f'${root(str(k * k))}$', 'check': f'sqrt({k * k})',
            'answer': str(k), 'rank': (1, k)}


def l1_fraction(rng):
    p, q = rng.randint(2, 15), rng.randint(2, 15)
    if p == q or gcd(p, q) != 1:
        return None
    return {'tex': rf'$\sqrt{{\dfrac{{{p * p}}}{{{q * q}}}}}$',
            'check': f'sqrt(Rational({p * p},{q * q}))',
            'answer': f'{p}/{q}', 'rank': (2, q)}


def l1_decimal(rng):
    k = rng.choice([2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 15, 16, 18, 21, 25, 35])
    scale = rng.choice([Fraction(1, 10), Fraction(1, 100)])
    value = k * scale
    under = value * value
    return {'tex': f'${root(num(under, tex=True))}$',
            'check': f'sqrt(Rational({under.numerator},{under.denominator}))',
            'answer': num(value, tex=False), 'rank': (3, k)}


def l1_product(rng):
    a, b = rng.randint(2, 12), rng.randint(2, 15)
    if a == b:
        return None
    inner = str(a * a) + r' \cdot ' + str(b * b)
    return {'tex': f'${root(inner)}$',
            'check': f'sqrt({a * a}*{b * b})',
            'answer': str(a * b), 'rank': (4, a * b)}


def l1_power(rng):
    base = rng.randint(2, 9)
    power = rng.choice([4, 6])
    return {'tex': f'${root(f"{base}^{power}")}$', 'check': f'sqrt({base}**{power})',
            'answer': str(base ** (power // 2)), 'rank': (5, base ** (power // 2))}


def l1_var_simple(rng):
    """√(a²b²) при a=3, b=7 — сначала вынести обе буквы, потом считать."""
    u, v = pick_vars(rng, 2)
    a, b = rng.randint(2, 15), rng.randint(2, 15)
    if a == b:
        return None
    inner = f'{u}^2{v}^2'
    return {'tex': f'${root(inner)}${subs_clause([(u, a), (v, b)], tex=True)}',
            'check': f'sqrt({a}**2*{b}**2)', 'answer': str(a * b), 'rank': (6, a * b)}


def l1_var_square(rng):
    """√(a⁴) и √(a⁶) при a = 5 — степень под корнем делится пополам."""
    v = pick_vars(rng, 1)[0]
    power = rng.choice([4, 6, 8])
    a = rng.randint(2, 12)
    if a ** (power // 2) > 5000:
        return None
    return {'tex': f'${root(f"{v}^{power}")}${subs_clause([(v, a)], tex=True)}',
            'check': f'sqrt({a}**{power})', 'answer': str(a ** (power // 2)), 'rank': (7, power * 10)}


def l1_var_product(rng):
    """√(36a²) при a = 0,5 — коэффициент и буква выносятся вместе."""
    v = pick_vars(rng, 1)[0]
    k = rng.choice([4, 9, 16, 25, 36, 49, 64, 81, 100, 144])
    a = rng.choice([Fraction(1, 2), Fraction(3, 10), Fraction(rng.randint(2, 9)),
                    Fraction(rng.randint(11, 30), 10)])
    value = isqrt(k) * a
    return {'tex': f'${root(f"{k}{v}^2")}${subs_clause([(v, a)], tex=True)}',
            'check': f'sqrt({k}*(Rational({a.numerator},{a.denominator}))**2)',
            'answer': num(value, tex=False), 'rank': (8, k)}


def l1_var_quotient(rng):
    """√(a²)·√(b⁴) при a=6, b=3 — два корня, обе буквы в степенях."""
    u, v = pick_vars(rng, 2)
    a, b = rng.randint(2, 14), rng.randint(2, 6)
    body = root(f'{u}^2') + r' \cdot ' + root(f'{v}^4')
    return {'tex': f'${body}$' + subs_clause([(u, a), (v, b)], tex=True),
            'check': f'sqrt({a}**2)*sqrt({b}**4)', 'answer': str(a * b * b), 'rank': (9, a * b * b)}


def l1_var_coefficient(rng):
    """√(a²b⁴) при a=9, b=2 — под одним корнем две буквы в степенях."""
    u, v = pick_vars(rng, 2)
    a, b = rng.randint(2, 20), rng.randint(2, 6)
    return {'tex': f'${root(f"{u}^2{v}^4")}${subs_clause([(u, a), (v, b)], tex=True)}',
            'check': f'sqrt({a}**2*{b}**4)', 'answer': str(a * b * b), 'rank': (10, a * b * b)}


def l2_extract(rng):
    k, m = rng.randint(2, 9), rng.choice(NON_SQUARES[:12])
    return {'tex': f'${root(str(k * k * m))}$', 'check': f'sqrt({k * k * m})',
            'answer': radical(k, m, tex=False), 'rank': (1, k * k * m)}


def l2_product_integer(rng):
    k = rng.randint(3, 20)
    a = rng.randint(2, 12)
    if k * k % a != 0:
        return None
    b = k * k // a
    if a == b or b > 200:
        return None
    return {'tex': f'${root(str(a))} \\cdot {root(str(b))}$', 'check': f'sqrt({a})*sqrt({b})',
            'answer': str(k), 'rank': (2, k)}


def l2_product_radical(rng):
    a, b = rng.randint(2, 20), rng.randint(2, 20)
    k, m = simplify_radical(a * b)
    if m == 1 or k == 1:
        return None
    return {'tex': f'${root(str(a))} \\cdot {root(str(b))}$', 'check': f'sqrt({a})*sqrt({b})',
            'answer': radical(k, m, tex=False), 'rank': (3, a * b)}


def l2_quotient(rng):
    b = rng.randint(2, 12)
    k = rng.randint(2, 15)
    a = b * k * k
    if a > 400:
        return None
    return {'tex': f'${root(str(a))} : {root(str(b))}$', 'check': f'sqrt({a})/sqrt({b})',
            'answer': str(k), 'rank': (4, a)}


def l2_quotient_radical(rng):
    b = rng.randint(2, 9)
    m = rng.choice(NON_SQUARES[:8])
    k = rng.randint(2, 6)
    a = b * k * k * m
    if a > 600:
        return None
    return {'tex': rf'$\dfrac{{{root(str(a))}}}{{{root(str(b))}}}$', 'check': f'sqrt({a})/sqrt({b})',
            'answer': radical(k, m, tex=False), 'rank': (5, a)}


def l2_coefficients(rng):
    k, p = rng.randint(2, 6), rng.randint(2, 6)
    m, n = rng.choice(NON_SQUARES[:8]), rng.choice(NON_SQUARES[:8])
    coef, inner = simplify_radical(m * n)
    return {'tex': f'${k}{root(str(m))} \\cdot {p}{root(str(n))}$',
            'check': f'{k}*sqrt({m})*{p}*sqrt({n})',
            'answer': radical(k * p * coef, inner, tex=False), 'rank': (6, k * p * m * n)}


def l2_square(rng):
    k, m = rng.randint(2, 9), rng.choice(NON_SQUARES[:10])
    return {'tex': f'$({k}{root(str(m))})^2$', 'check': f'({k}*sqrt({m}))**2',
            'answer': str(k * k * m), 'rank': (7, k * k * m)}


def l2_var_product(rng):
    """√(a²b) при a=3, b=5 — из-под корня выходит буква, а не число."""
    u, v = pick_vars(rng, 2)
    a = rng.randint(2, 12)
    b = rng.choice(NON_SQUARES[:10])
    return {'tex': f'${root(f"{u}^2{v}")}${subs_clause([(u, a), (v, b)], tex=True)}',
            'check': f'sqrt({a}**2*{b})', 'answer': radical(a, b, tex=False), 'rank': (8, a * b)}


def l2_var_coefficient(rng):
    """√(a³) при a=7 — половина степени выходит, половина остаётся."""
    v = pick_vars(rng, 1)[0]
    power = rng.choice([3, 5])
    a = rng.choice([x for x in range(2, 15) if square_free(x)])
    out = a ** (power // 2)
    return {'tex': f'${root(f"{v}^{power}")}${subs_clause([(v, a)], tex=True)}',
            'check': f'sqrt({a}**{power})', 'answer': radical(out, a, tex=False), 'rank': (9, a * power)}


def l2_var_quotient(rng):
    """√(a⁷) : √(a³) при a=6 — степени вычитаются до подстановки."""
    v = pick_vars(rng, 1)[0]
    high = rng.choice([7, 9, 11])
    low = high - rng.choice([2, 4])
    a = rng.randint(2, 12)
    value = a ** ((high - low) // 2)
    if value > 5000:
        return None
    return {'tex': rf'$\dfrac{{{root(f"{v}^{high}")}}}{{{root(f"{v}^{low}")}}}${subs_clause([(v, a)], tex=True)}',
            'check': f'sqrt({a}**{high})/sqrt({a}**{low})', 'answer': str(value), 'rank': (10, high * 10)}


def l2_var_square(rng):
    """√(ab)·√(ab³) при a=2, b=3 — перемножить под одним корнем, потом вынести."""
    u, v = pick_vars(rng, 2)
    a, b = rng.randint(2, 9), rng.randint(2, 7)
    body = root(f'{u}{v}') + r' \cdot ' + root(f'{u}{v}^3')
    return {'tex': f'${body}$' + subs_clause([(u, a), (v, b)], tex=True),
            'check': f'sqrt({a}*{b})*sqrt({a}*{b}**3)', 'answer': str(a * b * b), 'rank': (11, a * b * b)}


def l3_like_terms(rng):
    m = rng.choice(NON_SQUARES[:8])
    a, b, c = rng.randint(2, 9), rng.randint(2, 9), rng.randint(2, 9)
    total = a - b + c
    # Одинаковые слагаемые превращают пример в обман: «√45 − √80 + √80».
    if total <= 0 or len({a, b, c}) < 3:
        return None
    parts = [root(str(a * a * m)), root(str(b * b * m)), root(str(c * c * m))]
    return {'tex': f'${parts[0]} - {parts[1]} + {parts[2]}$',
            'check': f'sqrt({a * a * m})-sqrt({b * b * m})+sqrt({c * c * m})',
            'answer': radical(total, m, tex=False), 'rank': (1, a * a * m)}


def l3_conjugate(rng):
    a, b = rng.randint(3, 30), rng.randint(2, 29)
    if a <= b or not square_free(a) or not square_free(b):
        return None
    return {'tex': f'$({root(str(a))} - {root(str(b))})({root(str(a))} + {root(str(b))})$',
            'check': f'(sqrt({a})-sqrt({b}))*(sqrt({a})+sqrt({b}))',
            'answer': str(a - b), 'rank': (2, a)}


def l3_square_sum(rng):
    k, p = rng.randint(2, 9), rng.randint(2, 5)
    m = rng.choice(NON_SQUARES[:6])
    tail = 2 * k * p
    return {'tex': f'$({k} + {p}{root(str(m))})^2$', 'check': f'({k}+{p}*sqrt({m}))**2',
            'answer': f'{k * k + p * p * m}+{radical(tail, m, tex=False)}', 'rank': (3, k * p * m)}


def l3_square_roots(rng):
    a, b = rng.randint(2, 15), rng.randint(2, 15)
    if a == b or not square_free(a) or not square_free(b):
        return None
    coef, inner = simplify_radical(a * b)
    tail = radical(2 * coef, inner, tex=False)
    sign = rng.choice(['+', '-'])
    return {'tex': f'$({root(str(a))} {sign} {root(str(b))})^2$',
            'check': f'(sqrt({a}){sign}sqrt({b}))**2',
            'answer': f'{a + b}{"+" if sign == "+" else "-"}{tail}', 'rank': (4, a * b)}


def l3_distribute(rng):
    m = rng.choice(NON_SQUARES[:6])
    k = rng.randint(2, 7)
    a = rng.randint(2, 7)
    # k = a обнуляет выражение: «√5(√45 − 3√5)» это не задание.
    if k == a:
        return None
    inner_a = k * k * m
    return {'tex': f'${root(str(m))}({root(str(inner_a))} - {a}{root(str(m))})$',
            'check': f'sqrt({m})*(sqrt({inner_a})-{a}*sqrt({m}))',
            'answer': str(k * m - a * m), 'rank': (5, inner_a)}


def l3_var_conjugate(rng):
    u, v = pick_vars(rng, 2)
    a, b = rng.randint(5, 30), rng.randint(2, 29)
    if a <= b or not square_free(a) or not square_free(b):
        return None
    return {'tex': f'$({root(u)} - {root(v)})({root(u)} + {root(v)})${subs_clause([(u, a), (v, b)], tex=True)}',
            'check': f'(sqrt({a})-sqrt({b}))*(sqrt({a})+sqrt({b}))',
            'answer': str(a - b), 'rank': (6, a)}


def l3_var_square_sum(rng):
    """√(a²+2ab+b²) при a=8, b=5 — под корнем полный квадрат."""
    u, v = pick_vars(rng, 2)
    a, b = rng.randint(2, 20), rng.randint(2, 20)
    sign = rng.choice(['+', '-'])
    if sign == '-' and a == b:
        return None
    inner = f'{u}^2 {sign} 2{u}{v} + {v}^2'
    value = a + b if sign == '+' else abs(a - b)
    return {'tex': f'${root(inner)}${subs_clause([(u, a), (v, b)], tex=True)}',
            'check': f'sqrt({a}**2 {sign} 2*{a}*{b} + {b}**2)',
            'answer': str(value), 'rank': (7, a + b)}


def l3_var_like_terms(rng):
    """√(a²m) − √(b²m) при a=7, b=3 — вынести и привести подобные."""
    u, v = pick_vars(rng, 2)
    m = rng.choice(NON_SQUARES[:8])
    a, b = rng.randint(3, 12), rng.randint(2, 9)
    if a <= b:
        return None
    first = root(f'{u}^2' + r' \cdot ' + str(m))
    second = root(f'{v}^2' + r' \cdot ' + str(m))
    return {'tex': f'${first} - {second}$' + subs_clause([(u, a), (v, b)], tex=True),
            'check': f'sqrt({a}**2*{m})-sqrt({b}**2*{m})',
            'answer': radical(a - b, m, tex=False), 'rank': (8, (a - b) * m)}


def l3_var_difference(rng):
    u, v = pick_vars(rng, 2)
    a, b = rng.randint(4, 20), rng.randint(2, 19)
    if a <= b or not square_free(a) or not square_free(b):
        return None
    # Подстановка сама с корнями — пишем её командой LaTeX, а не знаком «√»:
    # KaTeX такой символ не понимает и покажет вместо формулы ошибку.
    return {'tex': f'$({u} + {v})({u} - {v})${subs_clause([(u, root(str(a))), (v, root(str(b)))], tex=True)}',
            'check': f'(sqrt({a})+sqrt({b}))*(sqrt({a})-sqrt({b}))',
            'answer': str(a - b), 'rank': (9, a)}


# ─── Тема: избавление от иррациональности ───────────────────────────────────

def ir_simple(rng):
    m = rng.choice(NON_SQUARES[:10])
    k = m * rng.randint(1, 6)
    return {'tex': rf'$\dfrac{{{k}}}{{{root(str(m))}}}$', 'check': f'{k}/sqrt({m})',
            'answer': radical(k // m, m, tex=False), 'rank': (1, k)}


def ir_fraction_tail(rng):
    m = rng.choice(NON_SQUARES[:8])
    k = rng.randint(2, 9)
    if k % m == 0:
        return None
    g = gcd(k, m)
    return {'tex': rf'$\dfrac{{{k}}}{{{root(str(m))}}}$', 'check': f'{k}/sqrt({m})',
            'answer': f'{radical(k // g, m, tex=False)}/{m // g}', 'rank': (2, k * m)}


def ir_root_over_root(rng):
    a, b = rng.randint(2, 15), rng.randint(2, 9)
    if is_square(a * b) or a % b == 0:
        return None
    coef, inner = simplify_radical(a * b)
    g = gcd(coef, b)
    num_part = radical(coef // g, inner, tex=False)
    den = b // g
    return {'tex': rf'$\dfrac{{{root(str(a))}}}{{{root(str(b))}}}$', 'check': f'sqrt({a})/sqrt({b})',
            'answer': num_part if den == 1 else f'{num_part}/{den}', 'rank': (3, a * b)}


def ir_conjugate(rng):
    a, b = rng.randint(3, 30), rng.randint(2, 29)
    if a <= b or not square_free(a) or not square_free(b):
        return None
    d = a - b
    k = d * rng.randint(1, 5)
    sign = rng.choice(['-', '+'])
    other = '+' if sign == '-' else '-'
    coef = k // d
    head = '' if coef == 1 else str(coef)
    return {'tex': rf'$\dfrac{{{k}}}{{{root(str(a))} {sign} {root(str(b))}}}$',
            'check': f'{k}/(sqrt({a}){sign}sqrt({b}))',
            'answer': f'{head}(√{a} {other} √{b})'.replace(' ', '') if coef != 1
                      else f'√{a}{other}√{b}',
            'rank': (4, k)}


def ir_integer_conjugate(rng):
    n = rng.randint(2, 9)
    b = rng.choice([x for x in NON_SQUARES[:12] if x != n * n and square_free(x)])
    d = n * n - b
    if d == 0 or abs(d) > 20:
        return None
    k = abs(d) * rng.randint(1, 4)
    sign = rng.choice(['-', '+'])
    other = '+' if sign == '-' else '-'
    coef = Fraction(k, d)
    # Сопряжённое к «n + √b» — это «n − √b», и наоборот.
    body = f'{n}{other}√{b}'
    t = abs(coef)
    if coef < 0 and other == '-':
        # −t(n − √b) = t(√b − n): минус перед скобкой уходит внутрь.
        body, coef = f'√{b}-{n}', t
    head = '' if t == 1 else num(t, tex=False)
    minus = '-' if coef < 0 else ''
    answer = f'{minus}{head}({body})' if head or minus else body
    return {'tex': rf'$\dfrac{{{k}}}{{{n} {sign} {root(str(b))}}}$',
            'check': f'{k}/({n}{sign}sqrt({b}))',
            'answer': answer, 'rank': (5, k)}


def ir_var_simple(rng):
    """a : √a при a=12 — деление уводит букву под корень."""
    v = pick_vars(rng, 1)[0]
    a = rng.choice([x for x in range(2, 60) if not is_square(x)])
    k, m = simplify_radical(a)
    return {'tex': rf'$\dfrac{{{v}}}{{{root(v)}}}${subs_clause([(v, a)], tex=True)}',
            'check': f'{a}/sqrt({a})', 'answer': radical(k, m, tex=False), 'rank': (6, a)}


def ir_var_conjugate(rng):
    """(a − b) : (√a − √b) при a=7, b=3 — сопряжённое сокращает дробь."""
    u, v = pick_vars(rng, 2)
    a, b = rng.randint(5, 40), rng.randint(2, 39)
    if a <= b or not square_free(a) or not square_free(b):
        return None
    sign = rng.choice(['-', '+'])
    other = '+' if sign == '-' else '-'
    if sign == '+' and b > a:
        return None
    return {'tex': rf'$\dfrac{{{u} - {v}}}{{{root(u)} {sign} {root(v)}}}$'
                   + subs_clause([(u, a), (v, b)], tex=True),
            'check': f'({a}-{b})/(sqrt({a}){sign}sqrt({b}))',
            'answer': f'√{a}{other}√{b}', 'rank': (7, a)}


# ─── 10–11 класс: корень n-й степени и дробный показатель ───────────────────
#
# Типы взяты из школьной темы «Степень с рациональным показателем»:
# вычислить корень n-й степени, перевести корень в степень и обратно,
# применить свойства степеней к буквенному выражению.

NTH_BASES = [2, 3, 5, 6, 7, 10]


def nth_root(index: int, inner: str) -> str:
    return rf'\sqrt[{index}]{{{inner}}}'


def proper_fraction(k: int, n: int) -> bool:
    """Показатель обязан быть несократимой дробью: «c^{2/2}» — это c, а не задание."""
    return n > 1 and gcd(abs(k), n) == 1


def frac_power(base: str, num_: int, den: int) -> str:
    """Степень с дробным показателем в записи учебника: 8^{2/3}."""
    sign = '-' if num_ < 0 else ''
    return rf'{base}^{{{sign}\frac{{{abs(num_)}}}{{{den}}}}}'


def p1_root_of_number(rng):
    """∛64, ⁴√625, ⁶√729 — корень n-й степени из числа."""
    n = rng.choice([3, 3, 4, 4, 5, 6, 6])
    base = rng.choice(NTH_BASES)
    value = base ** n
    if value > 20000:
        return None
    return {'tex': f'${nth_root(n, str(value))}$', 'check': f'real_root({value},{n})',
            'answer': str(base), 'rank': (1, n * 10 + base)}


def p1_root_negative(rng):
    """∛(−125) — у корня нечётной степени отрицательное число законно."""
    n = rng.choice([3, 5])
    base = rng.choice([2, 3, 4, 5, 6, 10]) if n == 3 else rng.choice([2, 3])
    value = base ** n
    return {'tex': f'${nth_root(n, str(-value))}$', 'check': f'real_root({-value},{n})',
            'answer': str(-base), 'rank': (2, n * 10 + base)}


def p1_root_of_product(rng):
    """∛(8·27) — корень из произведения берётся по множителям."""
    n = rng.choice([3, 4])
    a, b = rng.choice([2, 3, 5]), rng.choice([2, 3, 5])
    if a == b:
        return None
    value_a, value_b = a ** n, b ** n
    inner = f'{value_a} \cdot {value_b}'
    return {'tex': f'${nth_root(n, inner)}$', 'check': f'real_root({value_a}*{value_b},{n})',
            'answer': str(a * b), 'rank': (3, n * 10 + a * b)}


def p1_root_of_fraction(rng):
    """⁴√(16/81) — корень из дроби: отдельно числитель, отдельно знаменатель."""
    n = rng.choice([3, 4, 5])
    a, b = rng.choice([2, 3, 4]), rng.choice([2, 3, 5])
    if a == b:
        return None
    inner = rf'\dfrac{{{a ** n}}}{{{b ** n}}}'
    return {'tex': f'${nth_root(n, inner)}$',
            'check': f'real_root(Rational({a ** n},{b ** n}),{n})',
            'answer': f'{a}/{b}', 'rank': (4, n * 10 + b)}


def p1_root_of_power(rng):
    """⁶√(2¹²) — показатель делится на степень корня."""
    n = rng.choice([3, 4, 5, 6])
    base = rng.choice([2, 3, 5])
    k = rng.randint(2, 4)
    power = n * k
    if base ** k > 3000 or base ** power > 10 ** 12:
        return None
    return {'tex': f'${nth_root(n, f"{base}^{{{power}}}")}$', 'check': f'real_root({base}**{power},{n})',
            'answer': str(base ** k), 'rank': (5, power)}


def p1_root_decimal(rng):
    """∛0,008 — та же степень, записанная десятичной дробью."""
    n = rng.choice([3, 4])
    base = rng.choice([2, 3, 5])
    scale = Fraction(1, 10)
    value = (base * scale) ** n
    return {'tex': f'${nth_root(n, num(value, tex=True))}$',
            'check': f'real_root(Rational({value.numerator},{value.denominator}),{n})',
            'answer': num(base * scale, tex=False), 'rank': (6, n * 10)}


def p1_var_root_of_power(rng):
    """⁴√(a⁸) при a=3 — показатель делится на степень корня до подстановки."""
    v = pick_vars(rng, 1)[0]
    n = rng.choice([3, 4, 5, 6])
    k = rng.randint(2, 3)
    a = rng.randint(2, 9)
    if a ** k > 3000:
        return None
    return {'tex': f'${nth_root(n, f"{v}^{{{n * k}}}")}$' + subs_clause([(v, a)], tex=True),
            'check': f'real_root({a}**{n * k},{n})', 'answer': str(a ** k), 'rank': (7, n * 10 + k)}


def p1_var_root_of_product(rng):
    """∛(ab) при a=8, b=27 — корень из произведения по множителям."""
    u, v = pick_vars(rng, 2)
    n = rng.choice([3, 4, 5])
    a, b = rng.choice([2, 3, 5]), rng.choice([2, 3, 5])
    if a == b:
        return None
    return {'tex': f'${nth_root(n, f"{u}{v}")}$' + subs_clause([(u, a ** n), (v, b ** n)], tex=True),
            'check': f'real_root({a ** n}*{b ** n},{n})', 'answer': str(a * b), 'rank': (8, n * 10)}


def p2_var_power(rng):
    """a^{3/4} при a=16 — знаменатель показателя это степень корня."""
    v = pick_vars(rng, 1)[0]
    n = rng.choice([2, 3, 4, 5, 6])
    k = rng.randint(2, max(2, n))
    base = rng.choice([2, 3, 5])
    a = base ** n
    if a > 5000 or base ** k > 5000 or not proper_fraction(k, n):
        return None
    return {'tex': f'${frac_power(v, k, n)}$' + subs_clause([(v, a)], tex=True),
            'check': f'Rational({a})**Rational({k},{n})', 'answer': str(base ** k),
            'rank': (13, n * 10 + k)}


def p2_var_power_negative(rng):
    """a^{-1/2} при a=36 — отрицательный показатель переворачивает дробь."""
    v = pick_vars(rng, 1)[0]
    n = rng.choice([2, 3, 4])
    k = rng.randint(1, n)
    base = rng.choice([2, 3, 5, 6])
    a = base ** n
    if a > 5000 or not proper_fraction(k, n):
        return None
    result = Fraction(1, base ** k)
    return {'tex': f'${frac_power(v, -k, n)}$' + subs_clause([(v, a)], tex=True),
            'check': f'Rational({a})**Rational({-k},{n})', 'answer': num(result, tex=False),
            'rank': (14, n * 10 + k)}


def p2_power_simple(rng):
    """8^{2/3} — дробный показатель: знаменатель это степень корня."""
    n = rng.choice([2, 3, 4, 5, 6])
    base = rng.choice([2, 3, 5, 10])
    k = rng.randint(2, n - 1) if n > 2 else 1
    if not proper_fraction(k, n):
        return None
    value = base ** n
    if value > 20000 or base ** k > 5000:
        return None
    return {'tex': f'${frac_power(str(value), k, n)}$', 'check': f'Rational({value})**Rational({k},{n})',
            'answer': str(base ** k), 'rank': (7, n * 10 + k)}


def p2_power_negative(rng):
    """81^{-1/4} — отрицательный показатель переворачивает дробь."""
    n = rng.choice([2, 3, 4, 5, 6])
    base = rng.choice([2, 3, 5])
    k = rng.randint(1, max(1, n - 1))
    value = base ** n
    if value > 20000 or not proper_fraction(k, n):
        return None
    result = Fraction(1, base ** k)
    return {'tex': f'${frac_power(str(value), -k, n)}$',
            'check': f'Rational({value})**Rational({-k},{n})',
            'answer': num(result, tex=False), 'rank': (8, n * 10 + k)}


def p2_power_decimal_base(rng):
    """(0,125)^{-1/3} — та же степень с десятичным основанием."""
    n = rng.choice([3, 4])
    base = rng.choice([2, 5])
    value = Fraction(1, base ** n)
    k = 1
    return {'tex': f'${frac_power("(" + num(value, tex=True) + ")", -k, n)}$',
            'check': f'Rational({value.numerator},{value.denominator})**Rational({-k},{n})',
            'answer': str(base ** k), 'rank': (9, n * 10)}


def p2_power_fraction_base(rng):
    """(16/81)^{3/4} — дробное основание и дробный показатель."""
    n = rng.choice([2, 3, 4])
    a, b = rng.choice([2, 3]), rng.choice([2, 3, 5])
    if a == b:
        return None
    k = rng.randint(1, n)
    if not proper_fraction(k, n):
        return None
    base = rf'\left(\dfrac{{{a ** n}}}{{{b ** n}}}\right)'
    result = Fraction(a ** k, b ** k)
    return {'tex': f'${frac_power(base, k, n)}$',
            'check': f'Rational({a ** n},{b ** n})**Rational({k},{n})',
            'answer': num(result, tex=False), 'rank': (10, n * 10 + k)}


def p2_power_sum(rng):
    """27^{2/3} + 16^{3/4} — два дробных показателя в одном примере."""
    n1, n2 = rng.choice([3, 4]), rng.choice([3, 4, 6])
    b1, b2 = rng.choice([2, 3, 5]), rng.choice([2, 3])
    k1 = rng.randint(1, n1 - 1)
    k2 = rng.randint(1, n2 - 1)
    v1, v2 = b1 ** n1, b2 ** n2
    if v1 > 5000 or v2 > 5000 or not proper_fraction(k1, n1) or not proper_fraction(k2, n2):
        return None
    sign = rng.choice(['+', '-'])
    result = b1 ** k1 + b2 ** k2 if sign == '+' else b1 ** k1 - b2 ** k2
    if result <= 0:
        return None
    return {'tex': f'${frac_power(str(v1), k1, n1)} {sign} {frac_power(str(v2), k2, n2)}$',
            'check': f'Rational({v1})**Rational({k1},{n1}) {sign} Rational({v2})**Rational({k2},{n2})',
            'answer': str(result), 'rank': (11, v1)}


def p2_root_to_power(rng):
    """⁴√16 · ⁶√64 — корни разных степеней в произведении."""
    n1, n2 = rng.choice([3, 4]), rng.choice([4, 5, 6])
    b1, b2 = rng.choice([2, 3]), rng.choice([2, 3])
    v1, v2 = b1 ** n1, b2 ** n2
    if v1 > 5000 or v2 > 5000:
        return None
    body = nth_root(n1, str(v1)) + r' \cdot ' + nth_root(n2, str(v2))
    return {'tex': f'${body}$', 'check': f'real_root({v1},{n1})*real_root({v2},{n2})',
            'answer': str(b1 * b2), 'rank': (12, v1 * v2)}


def p3_var_power_product(rng):
    """a^{1/2}·a^{1/3} при a=64 — показатели складываются до подстановки."""
    v = pick_vars(rng, 1)[0]
    n1, n2 = rng.choice([2, 3, 4]), rng.choice([2, 3, 6])
    if n1 == n2:
        return None
    total = Fraction(1, n1) + Fraction(1, n2)
    base = rng.choice([2, 3, 5])
    lcm = n1 * n2 // gcd(n1, n2)
    a = base ** lcm
    if a > 10 ** 6:
        return None
    # Fraction ** Fraction даёт float, поэтому считаем корень целыми числами.
    root_value = round(a ** (1 / total.denominator))
    if root_value ** total.denominator != a:
        return None
    result = root_value ** total.numerator
    if result > 10 ** 6:
        return None
    body = frac_power(v, 1, n1) + r' \cdot ' + frac_power(v, 1, n2)
    return {'tex': f'${body}$' + subs_clause([(v, a)], tex=True),
            'check': f'Rational({a})**Rational({total.numerator},{total.denominator})',
            'answer': str(result), 'rank': (13, n1 * n2)}


def p3_var_power_of_power(rng):
    """(a^{3/4})^{8/3} при a=3 — показатели перемножаются."""
    v = pick_vars(rng, 1)[0]
    n, k = rng.choice([2, 3, 4]), rng.choice([2, 3, 4])
    total = rng.randint(2, 3)      # итоговая целая степень
    a = rng.randint(2, 9)
    if a ** total > 10 ** 4:
        return None
    outer_num, outer_den = total * n, k
    if not proper_fraction(k, n) or not proper_fraction(outer_num, outer_den):
        return None
    base = f'({frac_power(v, k, n)})'
    return {'tex': f'${frac_power(base, outer_num, outer_den)}$' + subs_clause([(v, a)], tex=True),
            'check': f'Rational({a})**(Rational({k},{n})*Rational({outer_num},{outer_den}))',
            'answer': str(a ** total), 'rank': (14, total * 10)}


def p3_var_root_of_powers(rng):
    """⁶√(a¹²b⁶) при a=2, b=5 — степени делятся на степень корня."""
    u, v = pick_vars(rng, 2)
    n = rng.choice([3, 4, 5, 6])
    ka, kb = rng.randint(2, 3), rng.randint(1, 2)
    a, b = rng.randint(2, 6), rng.randint(2, 6)
    if a ** ka * b ** kb > 5000:
        return None
    inner = f'{u}^{{{n * ka}}}{v}^{{{n * kb}}}'
    return {'tex': f'${nth_root(n, inner)}$' + subs_clause([(u, a), (v, b)], tex=True),
            'check': f'real_root({a}**{n * ka}*{b}**{n * kb},{n})',
            'answer': str(a ** ka * b ** kb), 'rank': (15, n * 10)}


def p3_var_quotient(rng):
    """a^{5/6} : a^{1/6} при a=16 — показатели вычитаются."""
    v = pick_vars(rng, 1)[0]
    n = rng.choice([3, 4, 6])
    k1 = rng.randint(2, n)
    k2 = rng.randint(1, k1 - 1) if k1 > 1 else None
    if k2 is None:
        return None
    if not proper_fraction(k1, n) or not proper_fraction(k2, n):
        return None
    diff = Fraction(k1 - k2, n)
    base = rng.choice([2, 3, 5])
    a = base ** n
    if a > 5000:
        return None
    root_value = round(a ** (1 / diff.denominator))
    if root_value ** diff.denominator != a:
        return None
    result = root_value ** diff.numerator
    return {'tex': rf'$\dfrac{{{frac_power(v, k1, n)}}}{{{frac_power(v, k2, n)}}}$'
                   + subs_clause([(v, a)], tex=True),
            'check': f'Rational({a})**Rational({diff.numerator},{diff.denominator})',
            'answer': str(result), 'rank': (16, n * 10)}


def p3_var_bracket(rng):
    """(1 + c^{1/2})² − 2c^{1/2} при c=49 — раскрыть скобку, привести подобные."""
    v = pick_vars(rng, 1)[0]
    c = rng.choice([4, 9, 16, 25, 36, 49, 64, 81, 100, 121, 144])
    return {'tex': f'$(1 + {frac_power(v, 1, 2)})^2 - 2{frac_power(v, 1, 2)}$'
                   + subs_clause([(v, c)], tex=True),
            'check': f'(1 + Rational({c})**Rational(1,2))**2 - 2*Rational({c})**Rational(1,2)',
            'answer': str(1 + c), 'rank': (17, c)}


def p3_var_root_product(rng):
    """∛(a²)·∛a при a=7 — под одним корнем степени складываются."""
    v = pick_vars(rng, 1)[0]
    n = rng.choice([3, 4, 5])
    k1, k2 = rng.randint(1, n - 1), None
    k2 = n - k1
    a = rng.randint(2, 20)
    body = nth_root(n, f'{v}^{k1}' if k1 > 1 else v) + r' \cdot ' + nth_root(n, f'{v}^{k2}' if k2 > 1 else v)
    return {'tex': f'${body}$' + subs_clause([(v, a)], tex=True),
            'check': f'real_root({a}**{k1},{n})*real_root({a}**{k2},{n})',
            'answer': str(a), 'rank': (18, n * 10 + a)}


GRADE_BLOCKS = [
    {
        'grade': 8,
        'title': '8 класс',
        'instruction': 'Найдите значение выражения:',
        'blocks': [
            {
                'title': 'Уровень 1 — извлечение корня',
                'templates': [l1_integer, l1_fraction, l1_decimal, l1_product, l1_power,
                              l1_var_simple, l1_var_square, l1_var_product, l1_var_quotient,
                              l1_var_coefficient],
            },
            {
                'title': 'Уровень 2 — вынесение множителя, умножение и деление',
                'templates': [l2_extract, l2_product_integer, l2_product_radical, l2_quotient,
                              l2_quotient_radical, l2_coefficients, l2_square,
                              l2_var_product, l2_var_coefficient, l2_var_quotient, l2_var_square],
            },
            {
                'title': 'Уровень 3 — действия с корнями',
                'templates': [l3_like_terms, l3_conjugate, l3_square_sum, l3_square_roots, l3_distribute,
                              l3_var_conjugate, l3_var_square_sum, l3_var_like_terms, l3_var_difference],
            },
            {
                'title': 'Тема — избавление от иррациональности',
                'templates': [ir_simple, ir_fraction_tail, ir_root_over_root, ir_conjugate,
                              ir_integer_conjugate, ir_var_simple, ir_var_conjugate],
            },
        ],
    },
    {
        'grade': 10,
        'title': '10–11 класс',
        'instruction': 'Найдите значение выражения:',
        'blocks': [
            {
                'title': 'Уровень 1 — корень n-й степени',
                'templates': [p1_root_of_number, p1_root_negative, p1_root_of_product,
                              p1_root_of_fraction, p1_root_of_power, p1_root_decimal,
                              p1_var_root_of_power, p1_var_root_of_product],
            },
            {
                'title': 'Уровень 2 — степень с дробным показателем',
                'templates': [p2_power_simple, p2_power_negative, p2_power_decimal_base,
                              p2_power_fraction_base, p2_power_sum, p2_root_to_power,
                              p2_var_power, p2_var_power_negative],
            },
            {
                'title': 'Уровень 3 — свойства степеней и корней',
                'templates': [p3_var_power_product, p3_var_power_of_power, p3_var_root_of_powers,
                              p3_var_quotient, p3_var_bracket, p3_var_root_product],
            },
        ],
    },
]


def parse_answer(answer: str):
    """Ответ банка («6√2», «37+20√3», «3(√7+√3)», «5√2/2») — в выражение sympy."""
    s = answer.replace(',', '.')
    s = re.sub(r'√\((.*?)\)', r'sqrt(\1)', s)       # √(…)
    s = re.sub(r'√(\d+)', r'sqrt(\1)', s)            # √14
    s = re.sub(r'(\d|\))(?=(sqrt|\())', r'\1*', s)  # 20sqrt(3), 3(…)

    return sp.sympify(s, rational=True)


def verify(item: dict) -> None:
    left = sp.sympify(item['check'], rational=True)
    right = parse_answer(item['answer'])
    if sp.simplify(sp.nsimplify(left - right)) != 0:
        raise SystemExit(f'неверный ответ: {item["check"]} ≠ {item["answer"]} '
                         f'({sp.N(left)} против {sp.N(right)})')


def generate(rng, templates, count, seen):
    out, per, attempts = [], {}, 0
    while len(out) < count and attempts < 400000:
        attempts += 1
        tpl = templates[len(out) % len(templates)] if attempts < 2000 else rng.choice(templates)
        item = tpl(rng)
        if item is None or item['tex'] in seen:
            continue
        verify(item)
        seen.add(item['tex'])
        per[tpl.__name__] = per.get(tpl.__name__, 0) + 1
        out.append(item)
    if len(out) < count:
        raise SystemExit(f'набрано только {len(out)} из {count}')
    out.sort(key=lambda e: e['rank'])
    return out, per


def main() -> None:
    rng = random.Random(SEED)
    seen: set = set()
    zadaniya, stats = [], {}

    for grade_block in GRADE_BLOCKS:
        subtypes, tasks = [], []
        for index, block in enumerate(grade_block['blocks']):
            items, per = generate(rng, block['templates'], PER_BLOCK, seen)
            stats[f"{grade_block['title']} · {block['title']}"] = per
            subtypes.append(block['title'])
            for item in items:
                tasks.append({
                    'id': len(tasks) + 1,
                    'expression': item['tex'],
                    'answer': item['answer'],
                    'status': 'production',
                    'subtype': index,
                    'level': index + 1,
                })

        zadaniya.append({
            'number': grade_block['grade'],
            'title': grade_block['title'],
            'grade': grade_block['grade'],
            'instruction': grade_block['instruction'],
            'type': 'expression',
            'status': 'production',
            'subtypes': subtypes,
            'tasks': tasks,
        })

    data = {
        'bank': 'skills',
        'topic': '03',
        'meta': {
            'title': 'Арифметический корень',
            'description': '8 класс: извлечение корня, вынесение множителя, действия с корнями и '
                           'избавление от иррациональности. 10–11 класс: корень n-й степени и '
                           'степень с дробным показателем. По 100 примеров в блоке.',
        },
        'generator': {'script': 'scripts/generate-skills-square-roots.py', 'seed': SEED},
        'zadaniya': zadaniya,
    }
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')

    total = sum(len(z['tasks']) for z in zadaniya)
    letters = sum(1 for z in zadaniya for t in z['tasks'] if ' при ' in t['expression'])
    print(f'{OUT}: {total} примеров в {len(zadaniya)} классах, из них буквенных {letters}')
    for title, per in stats.items():
        print(f'  {title}: ' + ', '.join(f'{k}×{v}' for k, v in sorted(per.items())))


if __name__ == '__main__':
    main()
