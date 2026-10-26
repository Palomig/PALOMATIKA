#!/usr/bin/env python3
"""
Генератор темы «Графики функций» банка «Скиллы» (задача #63 блокнота Джарвиса, 25.10.2026).

Стас: «учим только делать таблицу и строить графики: даём функцию и таблицу,
ученик заполняет таблицу и рисует по функции график». Классы — 7 и 8
(9-й Стас убрал 25.10).
Автоматически проверяется таблица; график ученик строит в тетради (в домашке
его видно на фото решения).

Два класса по три уровня. Где функций мало (y = kx, y = ax², y = k/x), уровень
короче — 50, 25 и 50 таблиц: сотня давала бы повторы. Остальные по 100.

  7 класс
    Уровень 1 — прямая пропорциональность y = kx (k целое и дробное).
    Уровень 2 — линейная функция y = kx + b.
    Уровень 3 — линейная функция с дробным коэффициентом: y = ½x − 3, y = −0,5x + 2.
  8 класс
    Уровень 1 — парабола y = ax².
    Уровень 2 — гипербола y = k/x.
    Уровень 3 — y = k√x + b.

Ответ — строка y по столбцам через «|»: «-7 | -5 | -3 | -1 | 1». На сайте
сверяется по порядку (TaskAnswerResolver::tableMatches), ученик пишет через
«;» или пробел. Значения — целые или конечные десятичные («0,5»), точки
таблицы подобраны так, чтобы их было удобно отметить на клетчатой бумаге.

Каждое значение пересчитывается sympy по записи функции из условия.

Запуск:  python3 scripts/generate-skills-graphs.py
Пишет:   database/data/skills/graphs.json (seed фиксирован)
"""

import json
import random
from fractions import Fraction
from pathlib import Path

import sympy
from sympy.parsing.sympy_parser import (convert_xor, implicit_multiplication_application,
                                        parse_expr, rationalize, standard_transformations)

SEED = 20261027
OUT = Path(__file__).resolve().parent.parent / 'database' / 'data' / 'skills' / 'graphs.json'
TRANSFORMS = standard_transformations + (convert_xor, implicit_multiplication_application, rationalize)
X = sympy.Symbol('x')
PROMPT = 'Заполните таблицу значений функции ${f}$ и постройте её график. В ответ запишите значения $y$ через точку с запятой.'


def nz(rng, lo, hi):
    return rng.randint(lo, hi) * rng.choice([1, -1])


def coef_tex(k, decimal=False) -> str:
    """Коэффициент перед x: «», «-», «3», «-\\dfrac{1}{2}», «0{,}5»."""
    k = Fraction(k)
    if k == 1:
        return ''
    if k == -1:
        return '-'
    if k.denominator == 1:
        return str(k.numerator)
    if decimal:
        return f'{float(k):g}'.replace('.', '{,}')
    s = '-' if k < 0 else ''
    return f'{s}\\dfrac{{{abs(k.numerator)}}}{{{k.denominator}}}'


def add_const(b) -> str:
    b = Fraction(b)
    if b == 0:
        return ''
    return f'{"+" if b > 0 else "-"}{abs(b.numerator) if b.denominator == 1 else float(abs(b))}'.replace('.', '{,}')


def cell(v: Fraction) -> str:
    if v.denominator == 1:
        return str(v.numerator)
    return f'{float(v):g}'.replace('.', ',')


def table_tex(xs) -> str:
    cols = 'c' * (len(xs) + 1)
    top = '&'.join(str(v) for v in xs)
    blank = '&'.join('\\phantom{-00}' for _ in xs)
    return f'\\begin{{array}}{{|{"|".join(cols)}|}}\\hline x&{top}\\\\\\hline y&{blank}\\\\\\hline\\end{{array}}'


def finite_decimal(v: Fraction) -> bool:
    d = v.denominator
    for p in (2, 5):
        while d % p == 0:
            d //= p
    return d == 1


def window(c, half):
    return list(range(c - half, c + half + 1))


# ── 7 класс ─────────────────────────────────────────────────────────────────

def g7_kx(rng):
    k = Fraction(rng.choice([nz(rng, 1, 6), Fraction(nz(rng, 1, 2), 3), Fraction(nz(rng, 1, 3), 2)]))
    step = k.denominator
    c = rng.randint(-1, 1)
    xs = [step * i for i in range(c - 2, c + 3)]
    return f'y={coef_tex(k)}x', lambda v: k * v, xs


def g7_kx_b(rng):
    k, b = nz(rng, 1, 4), nz(rng, 1, 6)
    xs = window(rng.randint(-1, 1), 2)
    return f'y={coef_tex(k)}x{add_const(b)}', lambda v: k * v + b, xs


def g7_frac_k(rng):
    den = rng.choice([2, 2, 3, 4])
    k = Fraction(nz(rng, 1, den - 1 if den > 2 else 1), den)
    decimal = den in (2, 4) and rng.random() < 0.5
    b = nz(rng, 1, 5)
    xs = [den * i for i in range(-2, 3)]
    return f'y={coef_tex(k, decimal)}x{add_const(b)}', lambda v: k * v + b, xs


# ── 8 класс ─────────────────────────────────────────────────────────────────

def g8_ax2(rng):
    a = Fraction(rng.choice([1, 2, 3, -1, -2, -3, Fraction(1, 2), Fraction(-1, 2), Fraction(1, 4), Fraction(-1, 4)]))
    if a.denominator == 4:
        xs = [-4, -2, 0, 2, 4]
    elif a.denominator == 2:
        xs = rng.choice([[-4, -2, 0, 2, 4], window(0, 2), window(0, 3)])
    else:
        xs = rng.choice([window(0, 2), window(0, 3)])
    return f'y={coef_tex(a, rng.random() < 0.4)}x^{{2}}', lambda v: a * v * v, xs


def g8_hyperbola(rng):
    k = nz(rng, 1, 12)
    divs = sorted(d for d in range(1, abs(k) + 1) if abs(k) % d == 0)
    pos = divs if len(divs) <= 3 else rng.sample(divs, 3)
    pos = sorted(pos)
    if len(pos) < 2:
        pos = [1, 2] if k % 2 == 0 else [1, abs(k)]
    xs = sorted([-p for p in pos] + pos)
    return f'y={"-" if k < 0 else ""}\\dfrac{{{abs(k)}}}{{x}}', lambda v: Fraction(k, v), xs


def g8_sqrt(rng):
    k = Fraction(rng.choice([1, 1, 2, 3, -1, -2, Fraction(1, 2)]))
    b = rng.randint(-5, 5)
    xs = [0, 1, 4, 9, 16] if rng.random() < 0.7 else [0, 1, 4, 9]
    tex = f'y={coef_tex(k)}\\sqrt{{x}}{add_const(b)}'
    return tex, lambda v: k * Fraction(int(sympy.sqrt(v))) + b, xs


GRADES = [
    (7, '7 класс', [
        ('Уровень 1 — прямая пропорциональность y = kx', [(g7_kx, 50)]),
        ('Уровень 2 — линейная функция y = kx + b', [(g7_kx_b, 100)]),
        ('Уровень 3 — линейная функция с дробным k', [(g7_frac_k, 100)]),
    ]),
    (8, '8 класс', [
        ('Уровень 1 — парабола y = ax²', [(g8_ax2, 25)]),
        ('Уровень 2 — гипербола y = k/x', [(g8_hyperbola, 50)]),
        ('Уровень 3 — y = k√x + b', [(g8_sqrt, 100)]),
    ]),
]


def to_sympy(f_tex: str):
    s = f_tex.split('=', 1)[1]
    s = s.replace('{,}', '.').replace('\\sqrt{x}', 'sqrt(x)')
    while '\\dfrac{' in s:
        i = s.index('\\dfrac{')
        j = s.index('}', i)
        k = s.index('}', j + 1)
        s = s[:i] + f'(({s[i + 7:j]})/({s[j + 2:k]}))' + s[k + 1:]
    s = s.replace('^{', '**(').replace('}', ')')
    return parse_expr(s, transformations=TRANSFORMS)


def build(rng, template):
    got = template(rng)
    if got is None:
        return None
    f_tex, f, xs = got
    if len(xs) < 4 or len(set(xs)) != len(xs):
        return None
    ys = [Fraction(f(Fraction(v))) for v in xs]
    if any(not finite_decimal(y) or abs(y) > 40 for y in ys):
        return None
    if len({tuple(ys)}) == 1 and len(set(ys)) == 1:
        return None
    expr = to_sympy(f_tex)
    for v, y in zip(xs, ys):
        exact = sympy.nsimplify(expr.subs(X, v))
        assert exact == sympy.Rational(y.numerator, y.denominator), f'{f_tex} при x={v}: {exact} ≠ {y}'
    text = PROMPT.format(f=f_tex) + f' ${table_tex(xs)}$'
    return text, ' | '.join(cell(y) for y in ys), f_tex + str(xs)


def main() -> None:
    rng = random.Random(SEED)
    seen = set()
    zadaniya = []
    for grade, title, levels in GRADES:
        tasks, subtypes = [], []
        for li, (ltitle, templates) in enumerate(levels):
            subtypes.append(ltitle)
            for template, count in templates:
                made, guard = 0, 0
                while made < count:
                    guard += 1
                    assert guard < 50000, f'{template.__name__}: не набралось уникальных ({made})'
                    got = build(rng, template)
                    if got is None or got[2] in seen:
                        continue
                    seen.add(got[2])
                    tasks.append({'id': len(tasks) + 1, 'expression': got[0], 'answer': got[1],
                                  'status': 'production', 'subtype': li, 'level': li + 1})
                    made += 1
        zadaniya.append({'number': grade, 'title': title, 'grade': grade, 'instruction': 'Графики функций',
                         'type': 'expression', 'status': 'production', 'subtypes': subtypes, 'tasks': tasks})

    data = {
        'bank': 'skills',
        'topic': '07',
        'meta': {
            'title': 'Графики функций',
            'description': 'Таблица значений и построение графика: прямая, парабола, гипербола, y = √x. '
                           '7 и 8 класс, по три уровня (25–100 таблиц).',
        },
        'generator': {'script': 'scripts/generate-skills-graphs.py', 'seed': SEED},
        'zadaniya': zadaniya,
    }
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(f'{OUT}: {sum(len(z["tasks"]) for z in zadaniya)} примеров')


if __name__ == '__main__':
    main()
