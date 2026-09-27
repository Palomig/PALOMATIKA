#!/usr/bin/env python3
"""
Генератор темы «Сокращение дробей» банка «Скиллы» — 8 класс.

Три уровня по 100 примеров. Уровни взяты не с потолка: это разбивка
заданий §1 «Сокращение дробей» (Макарычев, 8 класс) по тому, что ученик
обязан увидеть в дроби.

  Уровень 1 — одночлены и степени (задания 25–29, 44):
      числитель и знаменатель уже одночлены, сокращаются коэффициенты и
      степени одной буквы: 15x/(25y), 63x²y³/(42x⁶y⁴), 36m²n : (18mn), 8¹⁶/16¹².
  Уровень 2 — общий множитель и формулы (30–32, 46):
      сначала разложить на множители — вынести общий множитель или узнать
      разность квадратов и квадрат суммы: (3a+12b)/(6ab), (y²−16)/(3y+12),
      (a²+10a+25)/(a²−25).
  Уровень 3 — знаки, группировка, кубы (35–37, 42–45):
      множители отличаются знаком, раскладывать приходится группировкой или
      по формулам куба: (25−a²)/(3a−15), (ax+bx−ay−by)/(bx−by),
      (a²+a+1)/(a³−1), (b⁷−b¹⁰)/(b⁵−b²).

Внутри уровня примеры идут от простых к сложным (порядок шаблона, затем
величина коэффициентов).

Каждый пример проверяется независимо: строка условия и строка ответа
разбираются sympy, и разность приводится к нулю. Ошибка в ответе роняет
генерацию, а не уезжает в банк.

Запуск:  python3 scripts/generate-skills-fraction-reduction.py
Пишет:   database/data/skills/fraction-reduction.json (seed фиксирован)
"""

import json
import random
import re
from math import gcd
from pathlib import Path

import sympy
from sympy.parsing.sympy_parser import (convert_xor,
                                        implicit_multiplication_application,
                                        parse_expr, standard_transformations)

SEED = 20260927
PER_LEVEL = 100
GRADE = 8
OUT = Path(__file__).resolve().parent.parent / 'database' / 'data' / 'skills' / 'fraction-reduction.json'

TRANSFORMS = standard_transformations + (convert_xor, implicit_multiplication_application)

VARS = ['a', 'b', 'c', 'd', 'm', 'n', 'p', 'q', 'x', 'y', 'z']

# \dfrac, а не \frac: в карточке пикера и в домашке условие идёт строкой, и
# строчная дробь выходит вдвое мельче текста — читать её в телефоне нечем.
# Ловушка с «\dfrac в степени» сюда не относится: дробь у нас верхнего уровня.


def parse(src: str):
    # Между соседними буквами ставим знак умножения: иначе sympy читает «yn»
    # как функцию Бесселя, а «ab» — как одну переменную.
    return parse_expr(re.sub(r'(?<=[a-z])(?=[a-z])', '*', src), transformations=TRANSFORMS)


# ─── запись одночленов ──────────────────────────────────────────────────────

def power(var: str, deg: int, tex: bool) -> str:
    if deg == 1:
        return var
    return f'{var}^{{{deg}}}' if tex and deg >= 10 else f'{var}^{deg}'


def mono(coef: int, factors, tex: bool) -> str:
    """Одночлен: (3, [('x',2),('y',1)]) → «3x^2y».

    Буквы ставим по алфавиту, как в учебнике: «63c^5x^6», а не «63x^6c^5».
    """
    body = ''.join(power(v, d, tex) for v, d in sorted(factors) if d != 0)
    if body == '':
        return str(coef)
    if coef == 1:
        return body
    if coef == -1:
        return '-' + body
    return f'{coef}{body}'


def needs_parens(coef: int, factors) -> bool:
    """Знаменатель ответа берём в скобки, кроме числа и одной буквы."""
    real = [f for f in factors if f[1] != 0]
    if not real:
        return False
    return len(real) > 1 or real[0][1] != 1 or coef != 1


def ratio_answer(coef_num: int, num_factors, coef_den: int, den_factors) -> str:
    """Ответ-одночленная дробь в текстовом виде: «2x/(3y)», «3/(2x^4y)», «2m»."""
    sign = '-' if (coef_num < 0) != (coef_den < 0) else ''
    cn, cd = abs(coef_num), abs(coef_den)
    g = gcd(cn, cd)
    cn, cd = cn // g, cd // g
    num = mono(cn, num_factors, tex=False)
    if cd == 1 and not [f for f in den_factors if f[1] != 0]:
        return sign + num
    den = mono(cd, den_factors, tex=False)
    if needs_parens(cd, den_factors):
        den = f'({den})'
    return f'{sign}{num}/{den}'


def split_degrees(pairs):
    """[(var, deg_num, deg_den)] → множители числителя и знаменателя."""
    num, den = [], []
    for var, a, b in pairs:
        if a > b:
            num.append((var, a - b))
        elif b > a:
            den.append((var, b - a))
    return num, den


def pick_vars(rng, count):
    return rng.sample(VARS, count)


# ─── Уровень 1: одночлены и степени ─────────────────────────────────────────

def l1_same_var(rng):
    """2x/(3x) — сокращается буква, остаётся числовая дробь."""
    v = pick_vars(rng, 1)[0]
    k, m = rng.randint(2, 25), rng.randint(2, 25)
    if k == m:
        return None
    g = gcd(k, m)
    d = rng.randint(1, 2)
    num, den = mono(k, [(v, d)], True), mono(m, [(v, d)], True)
    answer = f'{k // g}/{m // g}' if m // g != 1 else str(k // g)
    return {'tex': rf'\dfrac{{{num}}}{{{den}}}',
            'check': f'({mono(k, [(v, d)], False)})/({mono(m, [(v, d)], False)})',
            'answer': answer, 'rank': (1, max(k, m))}


def l1_two_vars(rng):
    """15x/(25y) — сокращаются только коэффициенты."""
    u, v = pick_vars(rng, 2)
    g = rng.choice([2, 3, 4, 5, 6, 7])
    k, m = g * rng.randint(2, 9), g * rng.randint(2, 9)
    if k == m:
        return None
    return {'tex': rf'\dfrac{{{mono(k, [(u, 1)], True)}}}{{{mono(m, [(v, 1)], True)}}}',
            'check': f'({mono(k, [(u, 1)], False)})/({mono(m, [(v, 1)], False)})',
            'answer': ratio_answer(k, [(u, 1)], m, [(v, 1)]), 'rank': (2, max(k, m))}


def l1_common_var(rng):
    """10xz/(15yz) — общая буква и коэффициенты."""
    u, v, w = pick_vars(rng, 3)
    g = rng.choice([2, 3, 5])
    k, m = g * rng.randint(2, 8), g * rng.randint(2, 8)
    if k == m:
        return None
    return {'tex': rf'\dfrac{{{mono(k, [(u, 1), (w, 1)], True)}}}{{{mono(m, [(v, 1), (w, 1)], True)}}}',
            'check': f'({mono(k, [(u, 1), (w, 1)], False)})/({mono(m, [(v, 1), (w, 1)], False)})',
            'answer': ratio_answer(k, [(u, 1)], m, [(v, 1)]), 'rank': (3, max(k, m))}


def l1_powers(rng):
    """63x²y³/(42x⁶y⁴) — степени одной буквы в обе стороны."""
    u, v = pick_vars(rng, 2)
    g = rng.choice([2, 3, 6, 7, 9])
    k, m = g * rng.randint(2, 9), g * rng.randint(2, 9)
    au, bu = rng.randint(1, 6), rng.randint(1, 6)
    av, bv = rng.randint(1, 5), rng.randint(1, 5)
    if (au == bu and av == bv) or k == m:
        return None
    num_f, den_f = split_degrees([(u, au, bu), (v, av, bv)])
    return {'tex': rf'\dfrac{{{mono(k, [(u, au), (v, av)], True)}}}{{{mono(m, [(u, bu), (v, bv)], True)}}}',
            'check': f'({mono(k, [(u, au), (v, av)], False)})/({mono(m, [(u, bu), (v, bv)], False)})',
            'answer': ratio_answer(k, num_f, m, den_f), 'rank': (4, max(au, bu, av, bv) * 10)}


def l1_signs(rng):
    """−6p²q/(−2q³) — два минуса дают плюс, один оставляет минус."""
    u, v = pick_vars(rng, 2)
    k, m = rng.randint(2, 12), rng.randint(2, 12)
    sk, sm = rng.choice([1, -1]), rng.choice([1, -1])
    if sk == 1 and sm == 1:
        return None
    au, av, bv = rng.randint(1, 3), rng.randint(1, 3), rng.randint(2, 4)
    if av >= bv:
        return None
    num_f, den_f = split_degrees([(u, au, 0), (v, av, bv)])
    return {'tex': rf'\dfrac{{{mono(sk * k, [(u, au), (v, av)], True)}}}{{{mono(sm * m, [(v, bv)], True)}}}',
            'check': f'({mono(sk * k, [(u, au), (v, av)], False)})/({mono(sm * m, [(v, bv)], False)})',
            'answer': ratio_answer(sk * k, num_f, sm * m, den_f), 'rank': (5, k + m)}


def l1_quotient(rng):
    """36m²n : (18mn) — то же сокращение, записанное частным."""
    u, v = pick_vars(rng, 2)
    m = rng.randint(2, 12)
    k = m * rng.randint(2, 6)
    au, bu = rng.randint(2, 5), rng.randint(1, 3)
    if au <= bu:
        return None
    num = mono(k, [(u, au), (v, 1)], True)
    den = mono(m, [(u, bu), (v, 1)], True)
    return {'tex': f'{num} : ({den})',
            'check': f'({mono(k, [(u, au), (v, 1)], False)})/({mono(m, [(u, bu), (v, 1)], False)})',
            'answer': ratio_answer(k, [(u, au - bu)], m, []), 'rank': (6, k)}


def l1_numeric_powers(rng):
    """8¹⁶/16¹² — степени одного числа, ответ числовой."""
    base = rng.choice([2, 3, 5])
    s, t = rng.choice([(2, 3), (3, 2), (2, 4), (4, 2), (3, 4)])
    e1 = rng.randint(8, 30)
    total = s * e1
    if total % t != 0:
        return None
    e2 = total // t
    left = total - t * e2
    result = base ** (total - t * e2)
    e2 -= rng.choice([0, 0, 1])
    left = total - t * e2
    if not (0 <= left <= 4) or e2 < 5:
        return None
    result = base ** left
    return {'tex': rf'\dfrac{{{base ** s}^{{{e1}}}}}{{{base ** t}^{{{e2}}}}}',
            'check': f'({base ** s}^{e1})/({base ** t}^{e2})',
            'answer': str(result), 'rank': (7, e1)}


def l1_single_var(rng):
    """24a²c²/(36ac) — одна общая буква и коэффициенты."""
    u = pick_vars(rng, 1)[0]
    g = rng.choice([3, 4, 6, 8, 12])
    k, m = g * rng.randint(2, 7), g * rng.randint(2, 7)
    a, b = rng.randint(1, 7), rng.randint(1, 7)
    if a == b or k == m:
        return None
    num_f, den_f = split_degrees([(u, a, b)])
    return {'tex': rf'\dfrac{{{mono(k, [(u, a)], True)}}}{{{mono(m, [(u, b)], True)}}}',
            'check': f'({mono(k, [(u, a)], False)})/({mono(m, [(u, b)], False)})',
            'answer': ratio_answer(k, num_f, m, den_f), 'rank': (8, max(a, b) * 10)}


# ─── Уровень 2: общий множитель и формулы ───────────────────────────────────

def bracket(var: str, c: int, sign: int) -> str:
    return f'({var} {"+" if sign > 0 else "-"} {c})' if False else f'({var}{"+" if sign > 0 else "-"}{c})'


def l2_common_bracket(rng):
    """a(b−2)/(5(b−2)) — общий множитель уже в скобках."""
    u, v, w = pick_vars(rng, 3)
    c = rng.randint(2, 12)
    sign = rng.choice([1, -1])
    br = bracket(v, c, sign)
    k, m = rng.randint(1, 9), rng.randint(2, 20)
    if k == m or gcd(k, m) not in (1, k, m):
        return None
    num = mono(k, [(u, 1)], True) + br
    den = mono(m, [], True) + br if m != 1 else br
    ans = ratio_answer(k, [(u, 1)], m, [])
    return {'tex': rf'\dfrac{{{num}}}{{{den}}}',
            'check': f'({mono(k, [(u, 1)], False)}{br})/({m}{br})',
            'answer': ans, 'rank': (1, m)}


def l2_bracket_two_vars(rng):
    """15a(a−b)/(20b(a−b)) — общий множитель и коэффициенты."""
    u, v = pick_vars(rng, 2)
    br = f'({u}-{v})' if rng.random() < .5 else f'({u}+{v})'
    g = rng.choice([3, 4, 5])
    k, m = g * rng.randint(2, 6), g * rng.randint(2, 6)
    if k == m:
        return None
    num = mono(k, [(u, 1)], True) + br
    den = mono(m, [(v, 1)], True) + br
    return {'tex': rf'\dfrac{{{num}}}{{{den}}}',
            'check': f'({mono(k, [(u, 1)], False)}{br})/({mono(m, [(v, 1)], False)}{br})',
            'answer': ratio_answer(k, [(u, 1)], m, [(v, 1)]), 'rank': (2, max(k, m))}


def l2_factor_out_num(rng):
    """(3a+12b)/(6ab) — вынести общий множитель в числителе."""
    u, v = pick_vars(rng, 2)
    g = rng.choice([2, 3, 4, 5])
    a, b, c = g * rng.randint(1, 5), g * rng.randint(2, 8), g * rng.randint(1, 6)
    if b % a == 0 and a == c:
        return None
    sign = rng.choice(['+', '-'])
    num = f'{mono(a, [(u, 1)], True)}{sign}{mono(b, [(v, 1)], True)}'
    den = mono(c, [(u, 1), (v, 1)], True)
    g2 = gcd(gcd(a, b), c)
    ans_num = f'({mono(a // g2, [(u, 1)], False)}{sign}{mono(b // g2, [(v, 1)], False)})'
    ans_den = mono(c // g2, [(u, 1), (v, 1)], False)
    return {'tex': rf'\dfrac{{{num}}}{{{den}}}',
            'check': f'({mono(a, [(u, 1)], False)}{sign}{mono(b, [(v, 1)], False)})/({den})',
            'answer': f'{ans_num}/({ans_den})' if needs_parens(c // g2, [(u, 1), (v, 1)]) else f'{ans_num}/{ans_den}',
            'rank': (3, max(a, b, c))}


def l2_factor_out_den(rng):
    """(15b−20c)/(10b) — общий множитель только числовой."""
    u, v = pick_vars(rng, 2)
    g = rng.choice([2, 3, 5])
    a, b, c = g * rng.randint(2, 6), g * rng.randint(2, 8), g * rng.randint(1, 5)
    sign = rng.choice(['+', '-'])
    g2 = gcd(gcd(a, b), c)
    if g2 == 1:
        return None
    ans_num = f'({mono(a // g2, [(u, 1)], False)}{sign}{mono(b // g2, [(v, 1)], False)})'
    ans_den = mono(c // g2, [(u, 1)], False)
    return {'tex': rf'\dfrac{{{mono(a, [(u, 1)], True)}{sign}{mono(b, [(v, 1)], True)}}}{{{mono(c, [(u, 1)], True)}}}',
            'check': f'({mono(a, [(u, 1)], False)}{sign}{mono(b, [(v, 1)], False)})/({mono(c, [(u, 1)], False)})',
            'answer': f'{ans_num}/({ans_den})' if needs_parens(c // g2, [(u, 1)]) else f'{ans_num}/{ans_den}',
            'rank': (4, max(a, b, c))}


def l2_expanded_num(rng):
    """(2a−4)/(3(a−2)) — числитель придётся разложить самому."""
    u, v = pick_vars(rng, 2)
    c = rng.randint(2, 9)
    k, m = rng.randint(2, 9), rng.randint(2, 9)
    if k == m:
        return None
    return {'tex': rf'\dfrac{{{mono(k, [(u, 1)], True)}-{k * c}}}{{{m}({u}-{c})}}',
            'check': f'({mono(k, [(u, 1)], False)}-{k * c})/({m}({u}-{c}))',
            'answer': f'{k // gcd(k, m)}/{m // gcd(k, m)}' if m // gcd(k, m) != 1 else str(k // gcd(k, m)),
            'rank': (5, max(k, m))}


def l2_expanded_den(rng):
    """5x(y+2)/(6y+12) — знаменатель придётся разложить самому."""
    u, v = pick_vars(rng, 2)
    c = rng.randint(2, 9)
    k, m = rng.randint(2, 9), rng.randint(2, 9)
    return {'tex': rf'\dfrac{{{mono(k, [(u, 1)], True)}({v}+{c})}}{{{mono(m, [(v, 1)], True)}+{m * c}}}',
            'check': f'({mono(k, [(u, 1)], False)}({v}+{c}))/({mono(m, [(v, 1)], False)}+{m * c})',
            'answer': ratio_answer(k, [(u, 1)], m, []), 'rank': (6, max(k, m))}


def l2_var_factor(rng):
    """(a−3b)/(a²−3ab) — общий множитель в знаменателе — буква."""
    u, v = pick_vars(rng, 2)
    k = rng.randint(2, 9)
    sign = rng.choice(['+', '-'])
    return {'tex': rf'\dfrac{{{u}{sign}{mono(k, [(v, 1)], True)}}}{{{u}^2{sign}{mono(k, [(u, 1), (v, 1)], True)}}}',
            'check': f'({u}{sign}{mono(k, [(v, 1)], False)})/({u}^2{sign}{mono(k, [(u, 1), (v, 1)], False)})',
            'answer': f'1/{u}', 'rank': (7, k)}


def l2_factor_num_var(rng):
    """(3x²+15xy)/(x+5y) — сокращается целая скобка."""
    u, v = pick_vars(rng, 2)
    k, c = rng.randint(2, 9), rng.randint(2, 9)
    sign = rng.choice(['+', '-'])
    return {'tex': rf'\dfrac{{{mono(k, [(u, 2)], True)}{sign}{mono(k * c, [(u, 1), (v, 1)], True)}}}{{{u}{sign}{mono(c, [(v, 1)], True)}}}',
            'check': f'({mono(k, [(u, 2)], False)}{sign}{mono(k * c, [(u, 1), (v, 1)], False)})/({u}{sign}{mono(c, [(v, 1)], False)})',
            'answer': mono(k, [(u, 1)], False), 'rank': (8, k * c)}


def l2_diff_squares_lin(rng):
    """(y²−16)/(3y+12) — разность квадратов сверху."""
    u = pick_vars(rng, 1)[0]
    c, k = rng.randint(2, 12), rng.randint(2, 9)
    return {'tex': rf'\dfrac{{{u}^2-{c * c}}}{{{mono(k, [(u, 1)], True)}+{k * c}}}',
            'check': f'({u}^2-{c * c})/({mono(k, [(u, 1)], False)}+{k * c})',
            'answer': f'({u}-{c})/{k}' if k != 1 else f'{u}-{c}', 'rank': (9, c)}


def l2_diff_squares_two_vars(rng):
    """(5x−15y)/(x²−9y²) — разность квадратов снизу."""
    u, v = pick_vars(rng, 2)
    k, c = rng.randint(2, 9), rng.randint(2, 7)
    return {'tex': rf'\dfrac{{{mono(k, [(u, 1)], True)}-{mono(k * c, [(v, 1)], True)}}}{{{u}^2-{mono(c * c, [(v, 2)], True)}}}',
            'check': f'({mono(k, [(u, 1)], False)}-{mono(k * c, [(v, 1)], False)})/({u}^2-{mono(c * c, [(v, 2)], False)})',
            'answer': f'{k}/({u}+{mono(c, [(v, 1)], False)})', 'rank': (10, c)}


def l2_square_over_factored(rng):
    """(c+2)²/(7c²+14c) — квадрат суммы сверху, общий множитель снизу."""
    u = pick_vars(rng, 1)[0]
    c, k = rng.randint(2, 9), rng.randint(2, 9)
    return {'tex': rf'\dfrac{{({u}+{c})^2}}{{{mono(k, [(u, 2)], True)}+{mono(k * c, [(u, 1)], True)}}}',
            'check': f'(({u}+{c})^2)/({mono(k, [(u, 2)], False)}+{mono(k * c, [(u, 1)], False)})',
            'answer': f'({u}+{c})/({mono(k, [(u, 1)], False)})', 'rank': (11, c + k)}


def l2_factored_over_square(rng):
    """(6cd−18c)/(d−3)² — общий множитель сверху, квадрат разности снизу."""
    u, v = pick_vars(rng, 2)
    k, c = rng.randint(2, 9), rng.randint(2, 9)
    return {'tex': rf'\dfrac{{{mono(k, [(u, 1), (v, 1)], True)}-{mono(k * c, [(u, 1)], True)}}}{{({v}-{c})^2}}',
            'check': f'({mono(k, [(u, 1), (v, 1)], False)}-{mono(k * c, [(u, 1)], False)})/(({v}-{c})^2)',
            'answer': f'{mono(k, [(u, 1)], False)}/({v}-{c})', 'rank': (12, k * c)}


def l2_trinom_over_diff(rng):
    """(a²+10a+25)/(a²−25) — квадрат суммы и разность квадратов."""
    u = pick_vars(rng, 1)[0]
    c = rng.randint(2, 11)
    return {'tex': rf'\dfrac{{{u}^2+{mono(2 * c, [(u, 1)], True)}+{c * c}}}{{{u}^2-{c * c}}}',
            'check': f'({u}^2+{mono(2 * c, [(u, 1)], False)}+{c * c})/({u}^2-{c * c})',
            'answer': f'({u}+{c})/({u}-{c})', 'rank': (13, c)}


def l2_diff_over_trinom(rng):
    """(y²−9)/(y²−6y+9) — разность квадратов и квадрат разности."""
    u = pick_vars(rng, 1)[0]
    c = rng.randint(2, 11)
    return {'tex': rf'\dfrac{{{u}^2-{c * c}}}{{{u}^2-{mono(2 * c, [(u, 1)], True)}+{c * c}}}',
            'check': f'({u}^2-{c * c})/({u}^2-{mono(2 * c, [(u, 1)], False)}+{c * c})',
            'answer': f'({u}+{c})/({u}-{c})', 'rank': (14, c)}


# ─── Уровень 3: знаки, группировка, кубы ────────────────────────────────────

def l3_sign_bracket(rng):
    """a(x−2y)/(b(2y−x)) — скобки отличаются знаком."""
    u, v, w, t = pick_vars(rng, 4)
    k = rng.randint(2, 9)
    left = f'({w}-{mono(k, [(t, 1)], True)})'
    right = f'({mono(k, [(t, 1)], True)}-{w})'
    return {'tex': rf'\dfrac{{{u}{left}}}{{{v}{right}}}',
            'check': f'({u}({w}-{mono(k, [(t, 1)], False)}))/({v}({mono(k, [(t, 1)], False)}-{w}))',
            'answer': f'-{u}/{v}', 'rank': (1, k)}


def l3_sign_factor_out(rng):
    """(3a−36)/(12b−ab) — после вынесения множители противоположны."""
    u, v = pick_vars(rng, 2)
    k, c = rng.randint(2, 9), rng.randint(3, 15)
    return {'tex': rf'\dfrac{{{mono(k, [(u, 1)], True)}-{k * c}}}{{{mono(c, [(v, 1)], True)}-{mono(1, [(u, 1), (v, 1)], True)}}}',
            'check': f'({mono(k, [(u, 1)], False)}-{k * c})/({mono(c, [(v, 1)], False)}-{u}{v})',
            'answer': f'-{k}/{v}', 'rank': (2, c)}


def l3_sign_monomial(rng):
    """(7b−14b²)/(42b²−21b) — противоположные скобки и общая буква."""
    u = pick_vars(rng, 1)[0]
    a, b, m = rng.randint(2, 9), rng.randint(2, 9), rng.randint(2, 5)
    if a == b:
        return None
    num = f'{mono(a, [(u, 1)], True)}-{mono(a * m, [(u, 2)], True)}'
    den = f'{mono(b * m, [(u, 2)], True)}-{mono(b, [(u, 1)], True)}'
    g = gcd(a, b)
    ans = f'-{a // g}/{b // g}' if b // g != 1 else f'-{a // g}'
    return {'tex': rf'\dfrac{{{num}}}{{{den}}}',
            'check': f'({mono(a, [(u, 1)], False)}-{mono(a * m, [(u, 2)], False)})/({mono(b * m, [(u, 2)], False)}-{mono(b, [(u, 1)], False)})',
            'answer': ans, 'rank': (3, a + b)}


def l3_diff_squares_flipped(rng):
    """(25−a²)/(3a−15) — разность квадратов наоборот."""
    u = pick_vars(rng, 1)[0]
    c, k = rng.randint(2, 12), rng.randint(2, 9)
    return {'tex': rf'\dfrac{{{c * c}-{u}^2}}{{{mono(k, [(u, 1)], True)}-{k * c}}}',
            'check': f'({c * c}-{u}^2)/({mono(k, [(u, 1)], False)}-{k * c})',
            'answer': f'-({u}+{c})/{k}' if k != 1 else f'-({u}+{c})', 'rank': (4, c)}


def l3_cube_over_square(rng):
    """(b−2)³/(2−b)² — куб и квадрат противоположных скобок."""
    u = pick_vars(rng, 1)[0]
    c = rng.randint(2, 12)
    return {'tex': rf'\dfrac{{({u}-{c})^3}}{{({c}-{u})^2}}',
            'check': f'(({u}-{c})^3)/(({c}-{u})^2)',
            'answer': f'{u}-{c}', 'rank': (5, c)}


def l3_grouping_vars(rng):
    """(ax+bx−ay−by)/(bx−by) — группировка в четыре слагаемых."""
    a, b, x, y = pick_vars(rng, 4)
    return {'tex': rf'\dfrac{{{a}{x}+{b}{x}-{a}{y}-{b}{y}}}{{{b}{x}-{b}{y}}}',
            'check': f'({a}{x}+{b}{x}-{a}{y}-{b}{y})/({b}{x}-{b}{y})',
            'answer': f'({a}+{b})/{b}', 'rank': (6, 1)}


def l3_grouping_num(rng):
    """(2x+bx−2y−by)/(7x−7y) — группировка с числом."""
    b, x, y = pick_vars(rng, 3)
    k, m = rng.randint(2, 9), rng.randint(2, 9)
    return {'tex': rf'\dfrac{{{mono(k, [(x, 1)], True)}+{b}{x}-{mono(k, [(y, 1)], True)}-{b}{y}}}{{{mono(m, [(x, 1)], True)}-{mono(m, [(y, 1)], True)}}}',
            'check': f'({mono(k, [(x, 1)], False)}+{b}{x}-{mono(k, [(y, 1)], False)}-{b}{y})/({mono(m, [(x, 1)], False)}-{mono(m, [(y, 1)], False)})',
            'answer': f'({k}+{b})/{m}', 'rank': (7, k + m)}


def l3_cube_diff(rng):
    """(a²+a+1)/(a³−1) — неполный квадрат и разность кубов."""
    u = pick_vars(rng, 1)[0]
    c = rng.randint(1, 6)
    num = f'{u}^2+{mono(c, [(u, 1)], True)}+{c * c}' if c != 1 else f'{u}^2+{u}+1'
    check_num = f'{u}^2+{mono(c, [(u, 1)], False)}+{c * c}' if c != 1 else f'{u}^2+{u}+1'
    return {'tex': rf'\dfrac{{{num}}}{{{u}^3-{c ** 3}}}',
            'check': f'({check_num})/({u}^3-{c ** 3})',
            'answer': f'1/({u}-{c})', 'rank': (8, c)}


def l3_cube_sum_quotient(rng):
    """(1+a³) : (1+a) — сумма кубов, записанная частным."""
    u = pick_vars(rng, 1)[0]
    c = rng.randint(1, 6)
    ans_mid = f'-{mono(c, [(u, 1)], False)}' if c != 1 else f'-{u}'
    return {'tex': f'({c ** 3}+{u}^3) : ({c}+{u})',
            'check': f'({c ** 3}+{u}^3)/({c}+{u})',
            'answer': f'{u}^2{ans_mid}+{c * c}', 'rank': (9, c)}


def l3_powers_factor(rng):
    """(x⁶+x⁴)/(x⁴+x²) — общий множитель-степень."""
    u = pick_vars(rng, 1)[0]
    d = rng.randint(1, 3)
    low = rng.randint(2, 4)
    high = low + d
    step = rng.randint(1, 3)
    sign = rng.choice(['+', '-'])
    num = f'{power(u, high + step, True)}{sign}{power(u, high, True)}'
    den = f'{power(u, low + step, True)}{sign}{power(u, low, True)}'
    return {'tex': rf'\dfrac{{{num}}}{{{den}}}',
            'check': f'({power(u, high + step, False)}{sign}{power(u, high, False)})/({power(u, low + step, False)}{sign}{power(u, low, False)})',
            'answer': power(u, high - low, False), 'rank': (10, high + step)}


def l3_powers_sign(rng):
    """(b⁷−b¹⁰)/(b⁵−b²) — скобки после вынесения противоположны."""
    u = pick_vars(rng, 1)[0]
    low = rng.randint(2, 4)
    high = low + rng.randint(2, 5)
    step = rng.randint(2, 4)
    num = f'{power(u, high, True)}-{power(u, high + step, True)}'
    den = f'{power(u, low + step, True)}-{power(u, low, True)}'
    return {'tex': rf'\dfrac{{{num}}}{{{den}}}',
            'check': f'({power(u, high, False)}-{power(u, high + step, False)})/({power(u, low + step, False)}-{power(u, low, False)})',
            'answer': f'-{power(u, high - low, False)}', 'rank': (11, high + step)}


def l3_squares_over_square(rng):
    """(8b²−8a²)/(a²−2ab+b²) — разность квадратов и квадрат разности."""
    u, v = pick_vars(rng, 2)
    k = rng.randint(2, 12)
    return {'tex': rf'\dfrac{{{mono(k, [(v, 2)], True)}-{mono(k, [(u, 2)], True)}}}{{{u}^2-2{u}{v}+{v}^2}}',
            'check': f'({mono(k, [(v, 2)], False)}-{mono(k, [(u, 2)], False)})/({u}^2-2{u}{v}+{v}^2)',
            'answer': f'{k}({u}+{v})/({v}-{u})', 'rank': (12, k)}


def l3_trinom_over_grouping(rng):
    """(a²+2ac+c²)/(a²+ac−ax−cx) — квадрат суммы и группировка."""
    a, c, x = pick_vars(rng, 3)
    return {'tex': rf'\dfrac{{{a}^2+2{a}{c}+{c}^2}}{{{a}^2+{a}{c}-{a}{x}-{c}{x}}}',
            'check': f'({a}^2+2{a}{c}+{c}^2)/({a}^2+{a}{c}-{a}{x}-{c}{x})',
            'answer': f'({a}+{c})/({a}-{x})', 'rank': (13, 1)}


def l3_trinom_over_mono(rng):
    """(x²−4x+4)/(x²−2x) — квадрат разности и общий множитель."""
    u = pick_vars(rng, 1)[0]
    c = rng.randint(2, 9)
    return {'tex': rf'\dfrac{{{u}^2-{mono(2 * c, [(u, 1)], True)}+{c * c}}}{{{u}^2-{mono(c, [(u, 1)], True)}}}',
            'check': f'({u}^2-{mono(2 * c, [(u, 1)], False)}+{c * c})/({u}^2-{mono(c, [(u, 1)], False)})',
            'answer': f'({u}-{c})/{u}', 'rank': (14, c)}


LEVELS = [
    {
        'id': 1,
        'title': 'Уровень 1 — одночлены и степени',
        'hint': 'Числитель и знаменатель — одночлены: сокращаем коэффициенты и степени.',
        'templates': [l1_same_var, l1_two_vars, l1_common_var, l1_powers, l1_signs,
                      l1_quotient, l1_numeric_powers, l1_single_var],
    },
    {
        'id': 2,
        'title': 'Уровень 2 — общий множитель и формулы',
        'hint': 'Сначала разложить на множители: вынести общий множитель или применить формулы.',
        'templates': [l2_common_bracket, l2_bracket_two_vars, l2_factor_out_num, l2_factor_out_den,
                      l2_expanded_num, l2_expanded_den, l2_var_factor, l2_factor_num_var,
                      l2_diff_squares_lin, l2_diff_squares_two_vars, l2_square_over_factored,
                      l2_factored_over_square, l2_trinom_over_diff, l2_diff_over_trinom],
    },
    {
        'id': 3,
        'title': 'Уровень 3 — знаки, группировка, кубы',
        'hint': 'Множители отличаются знаком, раскладывать приходится группировкой или по формулам куба.',
        'templates': [l3_sign_bracket, l3_sign_factor_out, l3_sign_monomial, l3_diff_squares_flipped,
                      l3_cube_over_square, l3_grouping_vars, l3_grouping_num, l3_cube_diff,
                      l3_cube_sum_quotient, l3_powers_factor, l3_powers_sign,
                      l3_squares_over_square, l3_trinom_over_grouping, l3_trinom_over_mono],
    },
]


def verify(item: dict) -> None:
    """Условие и ответ разбираются независимо; разность обязана быть нулём."""
    left = parse(item['check'])
    right = parse(item['answer'])
    if sympy.simplify(sympy.cancel(left - right)) != 0:
        raise SystemExit(f'неверный ответ: {item["check"]} ≠ {item["answer"]}')
    # Дробь обязана сокращаться: иначе это не задание на сокращение.
    if sympy.simplify(left - parse(item['check'].replace('^', '**'))) != 0:
        raise SystemExit(f'условие разобрано неоднозначно: {item["check"]}')


def generate(rng, templates, count, seen):
    out = []
    per = {}
    attempts = 0
    while len(out) < count and attempts < 400000:
        attempts += 1
        tpl = templates[len(out) % len(templates)] if attempts < 1000 else rng.choice(templates)
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
    for level in LEVELS:
        items, per = generate(rng, level['templates'], PER_LEVEL, seen)
        stats[level['title']] = per
        subtypes.append(level['title'])
        for i, item in enumerate(items, start=1):
            tasks.append({
                'id': len(tasks) + 1,
                'expression': f'${item["tex"]}$',
                'answer': item['answer'],
                'status': 'production',
                'subtype': level['id'] - 1,
                'level': level['id'],
            })

    data = {
        'bank': 'skills',
        'topic': '02',
        'meta': {
            'title': 'Сокращение дробей',
            'description': 'Сокращение алгебраических дробей: одночлены и степени, '
                           'разложение на множители, знаки и группировка. 8 класс, три уровня по 100 примеров.',
        },
        'generator': {'script': 'scripts/generate-skills-fraction-reduction.py', 'seed': SEED},
        'zadaniya': [{
            'number': GRADE,
            'title': f'{GRADE} класс',
            'grade': GRADE,
            'instruction': 'Сократите дробь:',
            'type': 'expression',
            'status': 'production',
            'subtypes': subtypes,
            'tasks': tasks,
        }],
    }
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')

    print(f'{OUT}: {len(tasks)} примеров, {len(subtypes)} уровня')
    for title, per in stats.items():
        print(f'  {title}: ' + ', '.join(f'{k}×{v}' for k, v in sorted(per.items())))


if __name__ == '__main__':
    main()
