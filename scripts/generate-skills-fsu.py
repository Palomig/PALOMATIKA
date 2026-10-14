#!/usr/bin/env python3
"""
Генератор темы «Формулы сокращённого умножения» банка «Скиллы» — 7 класс.

Четыре уровня по 100 примеров (задача #7 блокнота Джарвиса, 14.10.2026):

  Уровень 1 — квадрат суммы и квадрат разности: раскрыть скобки.
      (x+3)², (5−a)², (3x−2)², (2a+5b)², (x²−4)², (−x−6)².
  Уровень 2 — разность квадратов, сумма и разность кубов: раскрыть скобки.
      (x−7)(x+7), (3a+4b)(3a−4b), (x²+5)(x²−5), (a+2)(a²−2a+4).
  Уровень 3 — разложение на множители.
      x²−49, 9a²−16b², x²+10x+25, 4a²−12ab+9b², a³−27, 3x²−12, 2a²+8a+8.
  Уровень 4 — упростить и вычислить.
      (x+5)²−(x−5)², (2a−1)²−(2a+1)(2a−1), 51², 98·102, 47²−37².

Действие записано в самом условии («Раскройте скобки: $…$»): уровни просят
разного, а инструкция у задания одна. Слова вне формулы — и TaskBankResolver
не выводит над условием инструкцию задания.

Форма ответа важна (см. AlgebraicAnswerComparator::formMatches): раскрытый
многочлен — без скобок и с приведёнными подобными, разложение — произведение.
Поэтому каждый пример проверяется sympy дважды: ответ равен условию, и ответ
записан в нужной форме. Ошибка роняет генерацию, а не уезжает в банк.

Запуск:  python3 scripts/generate-skills-fsu.py
Пишет:   database/data/skills/fsu.json (seed фиксирован)
"""

import json
import random
from pathlib import Path

import sympy
from sympy.parsing.sympy_parser import (convert_xor,
                                        implicit_multiplication_application,
                                        parse_expr, standard_transformations)

SEED = 20261014
PER_LEVEL = 100
GRADE = 7
OUT = Path(__file__).resolve().parent.parent / 'database' / 'data' / 'skills' / 'fsu.json'

TRANSFORMS = standard_transformations + (convert_xor, implicit_multiplication_application)
VARS = ['a', 'b', 'c', 'm', 'n', 'p', 'x', 'y', 'k', 't']
EXPAND, FACTOR, VALUE = 'Раскройте скобки', 'Разложите на множители', 'Упростите выражение'
COMPUTE = 'Вычислите удобным способом'


def parse(src: str):
    return parse_expr(src.replace('·', '*'), transformations=TRANSFORMS)


# ── запись одночленов и многочленов ────────────────────────────────────────

def mono(coef: int, var: str = '', deg: int = 1, tex: bool = False) -> str:
    """3x², x, −y, 7 — без «1x» и «x^1»."""
    if var == '' or deg == 0:
        return str(coef)
    pw = f'{var}^{{{deg}}}' if tex and deg > 1 else (f'{var}^{deg}' if deg > 1 else var)
    if coef == 1:
        return pw
    if coef == -1:
        return '-' + pw
    return f'{coef}{pw}'


def poly_str(expr, tex: bool = False, order=None) -> str:
    """Многочлен в школьной записи: старшие степени первыми, буквы — в порядке
    условия («25x^2-64t^2» для (5x+8t)(5x-8t)), первым — положительный член
    («9-n^2», а не «-n^2+9»)."""
    expr = sympy.expand(expr)
    names = list(order or [])
    gens = sorted(expr.free_symbols, key=lambda g: (names.index(str(g)) if str(g) in names else 99, str(g)))
    if not gens:
        return str(expr)
    terms = sympy.Poly(expr, *gens).terms()
    terms.sort(key=lambda t: (-sum(t[0]), [-d for d in t[0]]))
    if int(terms[0][1]) < 0:
        positive = [t for t in terms if int(t[1]) > 0]
        if positive:
            terms.remove(positive[0])
            terms.insert(0, positive[0])
    out = ''
    for powers, coef in terms:
        coef = int(coef)
        body = ''
        for g, d in zip(gens, powers):
            if d:
                body += f'{g}^{{{d}}}' if tex and d > 1 else (f'{g}^{d}' if d > 1 else str(g))
        piece = str(abs(coef)) if body == '' else (str(abs(coef)) if abs(coef) != 1 else '') + body
        out += (('-' if coef < 0 else '') + piece) if out == '' else ('-' if coef < 0 else '+') + piece
    return out


def letters(tex: str) -> list:
    """Буквы условия в порядке появления."""
    seen = []
    for ch in plain(tex):
        if ch.isalpha() and ch not in seen:
            seen.append(ch)
    return seen


def coprime(rng, ks, cs):
    """k и c без общего делителя: (8x−8)² — это уже вынос множителя, не формула."""
    while True:
        k, c = rng.choice(list(ks)), rng.choice(list(cs))
        if sympy.gcd(k, c) == 1:
            return k, c


def lin(k: int, v: str, c: int, w: str = '', tex: bool = False, vdeg: int = 1, wdeg: int = 1) -> str:
    """k·v^vdeg + c·w^wdeg одной строкой: «3x-2», «2a+5b», «x^2-4»."""
    first = mono(k, v, vdeg, tex)
    second = mono(abs(c), w, wdeg, tex) if w else str(abs(c))
    return f'{first}{"-" if c < 0 else "+"}{second}'


def sq(inner: str) -> str:
    return f'({inner})^{{2}}'


def cube(inner: str) -> str:
    return f'({inner})^{{3}}'


def plain(tex: str) -> str:
    return tex.replace('{', '').replace('}', '').replace('\\cdot', '*')


def pick(rng, items, n=1):
    return rng.sample(items, n) if n > 1 else rng.choice(items)


# ── шаблоны ────────────────────────────────────────────────────────────────
# Каждый шаблон возвращает (tex условия, sympy-условие, ответ, вид ответа).

def l1_x_plus_c(rng):
    v, c = pick(rng, VARS), rng.randint(1, 12) * rng.choice([1, -1])
    tex = sq(lin(1, v, c))
    return tex, parse(plain(tex)), EXPAND


def l1_c_minus_x(rng):
    v, c = pick(rng, VARS), rng.randint(2, 12)
    tex = sq(f'{c}{rng.choice("+-")}{v}')
    return tex, parse(plain(tex)), EXPAND


def l1_kx_c(rng):
    v = pick(rng, VARS)
    k, c = coprime(rng, range(2, 10), range(1, 10))
    c *= rng.choice([1, -1])
    tex = sq(lin(k, v, c))
    return tex, parse(plain(tex)), EXPAND


def l1_two_vars(rng):
    v, w = pick(rng, VARS, 2)
    k, c = coprime(rng, range(1, 8), range(1, 8))
    c *= rng.choice([1, -1])
    tex = sq(lin(k, v, c, w))
    return tex, parse(plain(tex)), EXPAND


def l1_square_of_power(rng):
    v, w = pick(rng, VARS, 2)
    k, c = coprime(rng, range(1, 6), range(1, 10))
    c *= rng.choice([1, -1])
    if rng.random() < 0.5:
        tex = sq(lin(k, v, c, tex=True, vdeg=2))
    else:
        tex = sq(lin(k, v, c, w, tex=True, vdeg=2))
    return tex, parse(plain(tex)), EXPAND


def l1_negative_first(rng):
    v, w = pick(rng, VARS, 2)
    k, c = coprime(rng, range(1, 7), range(1, 10))
    inner = f'-{mono(k, v)}{rng.choice("+-")}{mono(c, w) if rng.random() < 0.5 else c}'
    tex = sq(inner)
    return tex, parse(plain(tex)), EXPAND


def l2_diff_squares(rng):
    v, c = pick(rng, VARS), rng.randint(1, 12)
    a, b = f'{v}-{c}', f'{v}+{c}'
    if rng.random() < 0.5:
        a, b = b, a
    tex = f'({a})({b})'
    return tex, parse(plain(tex)), EXPAND


def l2_diff_squares_k(rng):
    v = pick(rng, VARS)
    k, c = coprime(rng, range(2, 10), range(1, 10))
    tex = f'({lin(k, v, c)})({lin(k, v, -c)})'
    return tex, parse(plain(tex)), EXPAND


def l2_diff_squares_swapped(rng):
    v, c = pick(rng, VARS), rng.randint(2, 12)
    tex = f'({c}-{v})({v}+{c})' if rng.random() < 0.5 else f'({v}+{c})({c}-{v})'
    return tex, parse(plain(tex)), EXPAND


def l2_diff_squares_two_vars(rng):
    v, w = pick(rng, VARS, 2)
    k, c = coprime(rng, range(1, 9), range(1, 9))
    tex = f'({lin(k, v, c, w)})({lin(k, v, -c, w)})'
    return tex, parse(plain(tex)), EXPAND


def l2_diff_squares_power(rng):
    v, w = pick(rng, VARS, 2)
    k, c = coprime(rng, range(1, 5), range(1, 10))
    if rng.random() < 0.5:
        tex = f'({lin(k, v, c, tex=True, vdeg=2)})({lin(k, v, -c, tex=True, vdeg=2)})'
    else:
        tex = f'({lin(k, v, -c, w, tex=True, vdeg=2)})({lin(k, v, c, w, tex=True, vdeg=2)})'
    return tex, parse(plain(tex)), EXPAND


def l2_cubes(rng):
    v, c = pick(rng, VARS), rng.randint(1, 6)
    s = rng.choice([1, -1])
    first = lin(1, v, s * c)
    second = f'{v}^{{2}}{"-" if s > 0 else "+"}{mono(c, v)}+{c * c}'
    tex = f'({first})({second})'
    return tex, parse(plain(tex)), EXPAND


def l2_cubes_k(rng):
    v = pick(rng, VARS)
    k, c = coprime(rng, range(2, 5), range(1, 6))
    s = rng.choice([1, -1])
    first = lin(k, v, s * c)
    second = f'{mono(k * k, v, 2, True)}{"-" if s > 0 else "+"}{mono(k * c, v)}+{c * c}'
    tex = f'({first})({second})'
    return tex, parse(plain(tex)), EXPAND


def l2_cube_of_sum(rng):
    v, c = pick(rng, VARS), rng.randint(1, 5) * rng.choice([1, -1])
    tex = cube(lin(1, v, c))
    return tex, parse(plain(tex)), EXPAND


def l3_diff_squares(rng):
    v, c = pick(rng, VARS), rng.randint(1, 15)
    tex = f'{v}^{{2}}-{c * c}' if rng.random() < 0.75 else f'{c * c}-{v}^{{2}}'
    return tex, parse(plain(tex)), FACTOR


def l3_diff_squares_k(rng):
    v, w = pick(rng, VARS, 2)
    k, c = coprime(rng, range(2, 10), range(1, 10))
    tex = f'{mono(k * k, v, 2, True)}-{c * c}' if rng.random() < 0.5 else f'{mono(k * k, v, 2, True)}-{mono(c * c, w, 2, True)}'
    return tex, parse(plain(tex)), FACTOR


def l3_square_trinomial(rng):
    v, c = pick(rng, VARS), rng.randint(1, 10) * rng.choice([1, -1])
    tex = poly_str(parse(f'({v}+({c}))**2'), tex=True)
    return tex, parse(plain(tex)), FACTOR


def l3_square_trinomial_k(rng):
    v, w = pick(rng, VARS, 2)
    k, c = coprime(rng, range(2, 7), range(1, 8))
    c *= rng.choice([1, -1])
    base = f'({k}*{v}+({c})*{w})**2' if rng.random() < 0.5 else f'({k}*{v}+({c}))**2'
    tex = poly_str(parse(base), tex=True)
    return tex, parse(plain(tex)), FACTOR


def l3_cubes(rng):
    v, c = pick(rng, VARS), rng.randint(1, 6)
    tex = f'{v}^{{3}}{rng.choice("+-")}{c ** 3}'
    return tex, parse(plain(tex)), FACTOR


def l3_cubes_k(rng):
    v = pick(rng, VARS)
    k, c = coprime(rng, range(2, 5), range(1, 6))
    tex = f'{mono(k ** 3, v, 3, True)}{rng.choice("+-")}{c ** 3}'
    return tex, parse(plain(tex)), FACTOR


def l3_common_then_diff(rng):
    v, n, c = pick(rng, VARS), rng.randint(2, 7), rng.randint(1, 8)
    tex = f'{mono(n, v, 2, True)}-{n * c * c}'
    return tex, parse(plain(tex)), FACTOR


def l3_common_then_square(rng):
    v, n, c = pick(rng, VARS), rng.randint(2, 5), rng.randint(1, 6) * rng.choice([1, -1])
    tex = poly_str(parse(f'{n}*({v}+({c}))**2'), tex=True)
    return tex, parse(plain(tex)), FACTOR


def l3_fourth_power(rng):
    v, c = pick(rng, VARS), rng.randint(1, 3)
    tex = f'{v}^{{4}}-{c ** 4}'
    return tex, parse(plain(tex)), FACTOR


def l4_squares_difference(rng):
    v, c = pick(rng, VARS), rng.randint(1, 9)
    s = rng.choice(['-', '+'])
    tex = f'({v}+{c})^{{2}}{s}({v}-{c})^{{2}}'
    return tex, parse(plain(tex)), VALUE


def l4_square_minus_product(rng):
    v, c = pick(rng, VARS), rng.randint(1, 9)
    tex = f'({v}+{c})^{{2}}-{v}({v}+{2 * c})' if rng.random() < 0.5 else f'({v}-{c})({v}+{c})-{v}^{{2}}'
    return tex, parse(plain(tex)), VALUE


def l4_k_mix(rng):
    v = pick(rng, VARS)
    k, c = coprime(rng, range(2, 6), range(1, 7))
    tex = f'({lin(k, v, -c)})^{{2}}-({lin(k, v, c)})({lin(k, v, -c)})'
    return tex, parse(plain(tex)), VALUE


def l4_two_vars(rng):
    v, w = pick(rng, VARS, 2)
    k, c = coprime(rng, range(1, 5), range(1, 5))
    s = rng.choice('+-')
    tex = f'({lin(k, v, c, w)})^{{2}}{s}({lin(k, v, -c, w)})^{{2}}'
    return tex, parse(plain(tex)), VALUE


def l4_compute_square(rng):
    base = rng.choice([10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 200])
    n = base + rng.choice([1, -1, 2, -2]) if base < 100 else base + rng.choice([1, -1, 2, -2, 3, -3])
    tex = f'{n}^{{2}}'
    return tex, sympy.Integer(n) ** 2, COMPUTE


def l4_compute_product(rng):
    base = rng.choice([20, 30, 40, 50, 60, 70, 80, 100, 200, 1000])
    d = rng.randint(1, 4)
    tex = f'{base - d}\\cdot {base + d}'
    return tex, sympy.Integer((base - d) * (base + d)), COMPUTE


def l4_compute_diff(rng):
    """a² − b², где a − b или a + b круглое: 47² − 37², 63² − 37²."""
    a = rng.randint(31, 99)
    options = [a - d for d in (10, 20, 30) if a - d > 10]
    if 10 < 100 - a < a:
        options.append(100 - a)
    b = rng.choice(options)
    tex = f'{a}^{{2}}-{b}^{{2}}'
    return tex, sympy.Integer(a * a - b * b), COMPUTE


LEVELS = [
    {'id': 1, 'title': 'Уровень 1 — квадрат суммы и разности',
     'templates': [(l1_x_plus_c, 20), (l1_c_minus_x, 10), (l1_kx_c, 30), (l1_two_vars, 20),
                   (l1_negative_first, 10), (l1_square_of_power, 10)]},
    {'id': 2, 'title': 'Уровень 2 — разность квадратов, сумма и разность кубов',
     'templates': [(l2_diff_squares, 20), (l2_diff_squares_swapped, 8), (l2_diff_squares_k, 17),
                   (l2_diff_squares_two_vars, 15), (l2_diff_squares_power, 10), (l2_cubes, 11),
                   (l2_cubes_k, 12), (l2_cube_of_sum, 7)]},
    {'id': 3, 'title': 'Уровень 3 — разложение на множители',
     'templates': [(l3_diff_squares, 20), (l3_square_trinomial, 18), (l3_diff_squares_k, 15),
                   (l3_square_trinomial_k, 12), (l3_cubes, 10), (l3_cubes_k, 7),
                   (l3_common_then_diff, 8), (l3_common_then_square, 7), (l3_fourth_power, 3)]},
    {'id': 4, 'title': 'Уровень 4 — упростить и вычислить',
     'templates': [(l4_compute_square, 15), (l4_compute_product, 12), (l4_compute_diff, 13),
                   (l4_squares_difference, 18), (l4_square_minus_product, 16), (l4_k_mix, 14),
                   (l4_two_vars, 12)]},
]


# ── ответы и их проверка ───────────────────────────────────────────────────

def factor_str(expr, order=None) -> str:
    """Разложение в школьной записи: «3(x-2)(x+2)», «(2a+5)^2», «(x-3)(x^2+3x+9)».
    Минус перед разложением прячем в множитель: 225−a² → «(15-a)(15+a)»."""
    names = list(order or [])
    def gens(f):
        return sorted(f.free_symbols, key=lambda g: (names.index(str(g)) if str(g) in names else 99, str(g)))

    content, prim = sympy.factor_list(sympy.expand(expr))
    parts = []
    for f, mult in prim:
        if sympy.Poly(f, *gens(f)).LC() < 0:
            f, content = -f, (-content if mult % 2 else content)
        parts.append([f, mult])
    flipped = False
    if content < 0:
        for part in parts:
            f, mult = part
            if mult % 2 and sympy.Poly(f).total_degree() == 1 and f.as_coeff_add()[0] < 0:
                part[0], content, flipped = -f, -content, True
                break
    rendered = []
    for f, mult in parts:
        const, rest = f.as_coeff_add()
        if flipped and sympy.Poly(f).total_degree() == 1 and const != 0:
            # Пара к «(15-a)»: «(15+a)», число первым в обоих множителях.
            inner = f'{const}' + ''.join(('-' if str(t).startswith('-') else '+') + str(t).lstrip('-')
                                         for t in [sympy.Add(*rest)])
        else:
            inner = poly_str(f, order=order)
        rendered.append((sympy.Poly(f).total_degree(), inner, mult))
    rendered.sort(key=lambda p: (p[0], p[1].replace('+', '~')))
    body = ''.join(f'({inner})^{mult}' if mult > 1 else f'({inner})' for _, inner, mult in rendered)
    content = int(content)
    if content == 1:
        return body
    if content == -1:
        return '-' + body
    return f'{content}{body}'


def answer_for(kind, value, order=None) -> str:
    if kind == COMPUTE:
        return str(int(value))
    if kind == FACTOR:
        return factor_str(value, order)
    return poly_str(value, order=order)


def check(kind, tex, value, answer):
    expanded = sympy.expand(value)
    got = sympy.expand(parse(answer))
    assert sympy.simplify(got - expanded) == 0, f'ответ не равен условию: {tex} → {answer}'
    if kind == FACTOR:
        assert '(' in answer and sympy.factor(expanded) == sympy.factor(parse(answer)), f'{tex} → {answer}'
        assert sympy.expand(parse(answer)) != parse(answer), f'не разложено: {answer}'
    elif kind != COMPUTE:
        assert '(' not in answer, f'скобки в раскрытом ответе: {tex} → {answer}'
        assert len(sympy.Add.make_args(sympy.expand(parse(answer)))) == len(answer.replace('^-', '^').lstrip('-').replace('-', '+').split('+')), f'не приведено: {answer}'


def generate(rng, templates, total, seen):
    items, per = [], {}
    for template, count in templates:
        made, guard = 0, 0
        while made < count:
            guard += 1
            assert guard < 5000, f'{template.__name__}: не набралось уникальных примеров'
            tex, value, kind = template(rng)
            # (n+4)² − n(n+8) и (p+4)² − p(p+8) — один пример, другая буква.
            shape = ''.join('?' if ch.isalpha() and ch in VARS else ch for ch in tex)
            if tex in seen or shape in seen:
                continue
            answer = answer_for(kind, value, letters(tex))
            if kind != COMPUTE and answer in ('0', ''):
                continue
            check(kind, tex, value, answer)
            seen.update((tex, shape))
            items.append({'tex': tex, 'answer': answer, 'kind': kind})
            made += 1
        per[template.__name__] = made
    assert len(items) == total, f'{len(items)} ≠ {total}'
    return items, per


def main() -> None:
    rng = random.Random(SEED)
    seen: set = set()
    subtypes, tasks, stats = [], [], {}
    for level in LEVELS:
        items, per = generate(rng, level['templates'], PER_LEVEL, seen)
        stats[level['title']] = per
        subtypes.append(level['title'])
        for item in items:
            tasks.append({
                'id': len(tasks) + 1,
                'expression': f'{item["kind"]}: ${item["tex"]}$',
                'answer': item['answer'],
                'status': 'production',
                'subtype': level['id'] - 1,
                'level': level['id'],
            })

    data = {
        'bank': 'skills',
        'topic': '04',
        'meta': {
            'title': 'Формулы сокращённого умножения',
            'description': 'Квадрат суммы и разности, разность квадратов, сумма и разность кубов: '
                           'раскрыть скобки, разложить на множители, упростить и вычислить. '
                           '7 класс, четыре уровня по 100 примеров.',
        },
        'generator': {'script': 'scripts/generate-skills-fsu.py', 'seed': SEED},
        'zadaniya': [{
            'number': GRADE,
            'title': f'{GRADE} класс',
            'grade': GRADE,
            'instruction': 'Формулы сокращённого умножения',
            'type': 'expression',
            'status': 'production',
            'subtypes': subtypes,
            'tasks': tasks,
        }],
    }
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')

    print(f'{OUT}: {len(tasks)} примеров, {len(subtypes)} уровня')
    for title, per in stats.items():
        print(f'  {title}: ' + ', '.join(f'{k}×{v}' for k, v in per.items()))


if __name__ == '__main__':
    main()
