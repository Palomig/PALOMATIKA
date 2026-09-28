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
    v = pick_vars(rng, 1)[0]
    k = rng.randint(11, 40)
    return {'tex': f'${root(v)}${subs_clause([(v, k * k)], tex=True)}',
            'check': f'sqrt({k * k})', 'answer': str(k), 'rank': (6, k)}


def l1_var_square(rng):
    v = pick_vars(rng, 1)[0]
    k = rng.randint(2, 40)
    value = Fraction(k, 10) if rng.random() < .5 else Fraction(k)
    return {'tex': f'${root(f"{v}^2")}${subs_clause([(v, value)], tex=True)}',
            'check': f'sqrt((Rational({value.numerator},{value.denominator}))**2)',
            'answer': num(value, tex=False), 'rank': (7, k)}


def l1_var_product(rng):
    u, v = pick_vars(rng, 2)
    k = rng.randint(2, 15)
    a = rng.randint(2, 9)
    b = k * k * a   # произведение — полный квадрат: a·b = (ka)²
    a, b = a * a, k * k
    return {'tex': f'${root(f"{u}{v}")}${subs_clause([(u, a), (v, b)], tex=True)}',
            'check': f'sqrt({a}*{b})', 'answer': str(isqrt(a * b)), 'rank': (8, isqrt(a * b))}


def l1_var_quotient(rng):
    u, v = pick_vars(rng, 2)
    p, q = rng.randint(2, 14), rng.randint(2, 14)
    if p == q or gcd(p, q) != 1:
        return None
    return {'tex': rf'$\sqrt{{\dfrac{{{u}}}{{{v}}}}}${subs_clause([(u, p * p), (v, q * q)], tex=True)}',
            'check': f'sqrt(Rational({p * p},{q * q}))',
            'answer': f'{p}/{q}', 'rank': (9, q)}


def l1_var_coefficient(rng):
    u, v = pick_vars(rng, 2)
    k, m = rng.randint(2, 12), rng.randint(3, 20)
    return {'tex': f'${u}{root(v)}${subs_clause([(u, k), (v, m * m)], tex=True)}',
            'check': f'{k}*sqrt({m * m})', 'answer': str(k * m), 'rank': (10, k * m)}


# ─── Уровень 2: вынесение множителя, умножение и деление ────────────────────

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
    u, v = pick_vars(rng, 2)
    a = rng.randint(2, 12)
    k = rng.randint(2, 9)
    b = a * k * k
    if b > 300:
        return None
    return {'tex': f'${root(f"{u}{v}")}${subs_clause([(u, a), (v, b)], tex=True)}',
            'check': f'sqrt({a}*{b})', 'answer': str(a * k), 'rank': (8, a * b)}


def l2_var_coefficient(rng):
    u, v = pick_vars(rng, 2)
    k = rng.randint(2, 8)
    p, m = rng.randint(2, 5), rng.choice(NON_SQUARES[:8])
    return {'tex': f'${u}{root(v)}${subs_clause([(u, k), (v, p * p * m)], tex=True)}',
            'check': f'{k}*sqrt({p * p * m})',
            'answer': radical(k * p, m, tex=False), 'rank': (9, k * p * m)}


def l2_var_quotient(rng):
    u, v = pick_vars(rng, 2)
    b = rng.randint(2, 9)
    k = rng.randint(2, 12)
    a = b * k * k
    if a > 500:
        return None
    return {'tex': rf'$\dfrac{{{root(u)}}}{{{root(v)}}}${subs_clause([(u, a), (v, b)], tex=True)}',
            'check': f'sqrt({a})/sqrt({b})', 'answer': str(k), 'rank': (10, a)}


def l2_var_square(rng):
    u, v = pick_vars(rng, 2)
    k, m = rng.randint(2, 7), rng.choice(NON_SQUARES[:10])
    return {'tex': f'$({u}{root(v)})^2${subs_clause([(u, k), (v, m)], tex=True)}',
            'check': f'({k}*sqrt({m}))**2', 'answer': str(k * k * m), 'rank': (11, k * k * m)}


# ─── Уровень 3: действия с корнями ──────────────────────────────────────────

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
    u, v = pick_vars(rng, 2)
    k, p = rng.randint(2, 9), rng.randint(2, 5)
    m = rng.choice(NON_SQUARES[:5])
    return {'tex': f'$({u} + {v}{root(str(m))})^2${subs_clause([(u, k), (v, p)], tex=True)}',
            'check': f'({k}+{p}*sqrt({m}))**2',
            'answer': f'{k * k + p * p * m}+{radical(2 * k * p, m, tex=False)}', 'rank': (7, k * p * m)}


def l3_var_like_terms(rng):
    u, v = pick_vars(rng, 2)
    m = rng.choice(NON_SQUARES[:8])
    a, b = rng.randint(3, 12), rng.randint(2, 9)
    if a == b:
        return None
    return {'tex': f'${u}{root(str(m))} - {v}{root(str(m))}${subs_clause([(u, a), (v, b)], tex=True)}',
            'check': f'{a}*sqrt({m})-{b}*sqrt({m})',
            'answer': radical(a - b, m, tex=False), 'rank': (8, abs(a - b) * m)}


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
    u, v = pick_vars(rng, 2)
    m = rng.choice(NON_SQUARES[:8])
    k = m * rng.randint(2, 5)
    return {'tex': rf'$\dfrac{{{u}}}{{{root(v)}}}${subs_clause([(u, k), (v, m)], tex=True)}',
            'check': f'{k}/sqrt({m})', 'answer': radical(k // m, m, tex=False), 'rank': (6, k)}


def ir_var_conjugate(rng):
    u, v = pick_vars(rng, 2)
    a, b = rng.randint(5, 30), rng.randint(2, 29)
    if a <= b or not square_free(a) or not square_free(b):
        return None
    d = a - b
    k = d * rng.randint(1, 4)
    coef = k // d
    body = f'√{a}+√{b}'
    return {'tex': rf'$\dfrac{{{k}}}{{{root(u)} - {root(v)}}}${subs_clause([(u, a), (v, b)], tex=True)}',
            'check': f'{k}/(sqrt({a})-sqrt({b}))',
            'answer': body if coef == 1 else f'{coef}({body})', 'rank': (7, k)}


BLOCKS = [
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
    subtypes, tasks, stats = [], [], {}

    for index, block in enumerate(BLOCKS):
        items, per = generate(rng, block['templates'], PER_BLOCK, seen)
        stats[block['title']] = per
        subtypes.append(block['title'])
        for item in items:
            tasks.append({
                'id': len(tasks) + 1,
                'expression': item['tex'],
                'answer': item['answer'],
                'status': 'production',
                'subtype': index,
                'level': min(index + 1, 3),
            })

    data = {
        'bank': 'skills',
        'topic': '03',
        'meta': {
            'title': 'Арифметический квадратный корень',
            'description': 'Извлечение корня, вынесение множителя, действия с корнями и '
                           'избавление от иррациональности. 8 класс, четыре блока по 100 примеров.',
        },
        'generator': {'script': 'scripts/generate-skills-square-roots.py', 'seed': SEED},
        'zadaniya': [{
            'number': GRADE,
            'title': f'{GRADE} класс',
            'grade': GRADE,
            'instruction': 'Найдите значение выражения:',
            'type': 'expression',
            'status': 'production',
            'subtypes': subtypes,
            'tasks': tasks,
        }],
    }
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')

    letters = sum(1 for t in tasks if ' при ' in t['expression'])
    print(f'{OUT}: {len(tasks)} примеров, {len(subtypes)} блока, из них буквенных {letters}')
    for title, per in stats.items():
        print(f'  {title}: ' + ', '.join(f'{k}×{v}' for k, v in sorted(per.items())))


if __name__ == '__main__':
    main()
