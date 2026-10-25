#!/usr/bin/env python3
"""
Генератор темы «Уравнения» банка «Скиллы» (задача #61 блокнота Джарвиса, 25.10.2026).

Три класса, в каждом четыре уровня по 100 примеров:

  7 класс
    Уровень 1 — линейные: 5x=−35, x−7=12, 3x+4=19, 8−3x=20.
    Уровень 2 — скобки и перенос: 4x−7=2x+5, 3(x−2)=12, 2(x+3)−5(x−1)=17.
    Уровень 3 — дробные коэффициенты: x/3+x/4=7, (2x−1)/3=(x+4)/5, 0,3x+1,2=0,5x−0,4.
    Уровень 4 — произведение равно нулю: (x−3)(x+5)=0, x(2x−7)=0, (x−1)(x+2)(3x−4)=0.
  8 класс
    Уровень 1 — неполные квадратные: 3x²−12x=0, 4x²−25=0, (x−3)²=16.
    Уровень 2 — полные квадратные через дискриминант: x²−5x+6=0, 3x²+x−2=0, x²=4x+12.
    Уровень 3 — дробно-рациональные: (x²−9)/(x−3)=0, (x+5)/(x−2)=3, 4/(x−1)=3/(x+2).
    Уровень 4 — биквадратные и замена: x⁴−13x²+36=0, (x²+x)²−8(x²+x)+12=0.
  10–11 класс
    Уровень 1 — показательные: 2^{x−3}=16, 4^x=8^{x−1}, 4^x−6·2^x+8=0.
    Уровень 2 — логарифмические: log₂(3x−1)=3, log₃(x+1)+log₃(x+3)=1.
    Уровень 3 — иррациональные: √(2x+7)=5, √(x+7)=x+1, ∛(3x−1)=−2.
    Уровень 4 — простейшие тригонометрические: наименьший положительный корень
               в градусах, cos(2x−π/3)=1/2.

Ответ — корни через «;» по возрастанию (на сайте сверяются как множество,
порядок не важен), дроби несократимые «7/3». Корни всегда рациональные:
это тренажёр приёма, а не упражнение в записи иррациональностей.

Каждое уравнение решается заново sympy — по самому условию, не по тому, как
его собирали. Посторонние корни (знаменатель, ОДЗ логарифма, проверка корня)
отсеиваются явной проверкой области, а не доверием к solve: у логарифмов
log(−2)−log(−2)=0 sympy засчитал бы. Расхождение роняет генерацию.

Запуск:  python3 scripts/generate-skills-equations.py
Пишет:   database/data/skills/equations.json (seed фиксирован)
"""

import json
import math
import random
from fractions import Fraction
from pathlib import Path

import sympy
from sympy import Rational, sqrt, log, Symbol, pi

SEED = 20261025
PER_LEVEL = 100
OUT = Path(__file__).resolve().parent.parent / 'database' / 'data' / 'skills' / 'equations.json'
x = Symbol('x', real=True)
SOLVE = 'Решите уравнение'


# ── запись чисел и многочленов ──────────────────────────────────────────────

def num(q) -> str:
    """Рациональное в TeX: 7, −3, \\dfrac{2}{3}, −\\dfrac{5}{4}."""
    q = Fraction(q)
    if q.denominator == 1:
        return str(q.numerator)
    s = '-' if q < 0 else ''
    return f'{s}\\dfrac{{{abs(q.numerator)}}}{{{q.denominator}}}'


def dec(q) -> str:
    """Десятичная дробь в TeX с запятой: 0{,}3."""
    s = f'{float(q):.10f}'.rstrip('0').rstrip('.')
    return s.replace('.', '{,}')


def ans(q) -> str:
    q = Fraction(q)
    return str(q.numerator) if q.denominator == 1 else f'{q.numerator}/{q.denominator}'


def term(c, body: str, first: bool) -> str:
    """Слагаемое c·body со знаком: «3x», «−x», «+5», «−\\dfrac{1}{2}x»."""
    c = Fraction(c)
    if c == 0:
        return ''
    sign = '-' if c < 0 else ('' if first else '+')
    a = abs(c)
    if body == '':
        return sign + num(a)
    coef = '' if a == 1 else num(a)
    return sign + coef + body


def poly(coefs, var='x') -> str:
    """Многочлен по коэффициентам от старшей степени: [3, 0, -12] → 3x^{2}-12."""
    deg = len(coefs) - 1
    out = ''
    for i, c in enumerate(coefs):
        p = deg - i
        body = '' if p == 0 else (var if p == 1 else f'{var}^{{{p}}}')
        out += term(c, body, out == '')
    return out or '0'


def lin(a, b, var='x') -> str:
    return poly([a, b], var)


def nz(rng, lo, hi):
    """Ненулевое целое из ±[lo, hi]."""
    return rng.randint(lo, hi) * rng.choice([1, -1])


# ── проверка ────────────────────────────────────────────────────────────────

def real_roots(lhs, rhs, domain=()):
    """Действительные корни lhs=rhs, прошедшие проверку области.
    domain — список (выражение, вид): 'ne0', 'gt0', 'ge0'."""
    sols = sympy.solve(sympy.Eq(lhs, rhs), x)
    out = set()
    for s in sols:
        s = sympy.nsimplify(s)
        if not s.is_real:
            continue
        ok = True
        for expr, kind in domain:
            v = sympy.simplify(expr.subs(x, s))
            if (kind == 'ne0' and v == 0) or (kind == 'gt0' and not v > 0) or (kind == 'ge0' and not v >= 0):
                ok = False
        if ok and sympy.simplify(lhs.subs(x, s) - rhs.subs(x, s)) == 0:
            out.add(s)
    return sorted(out)


def roots_answer(roots) -> str:
    for r in roots:
        assert r.is_rational, f'нерациональный корень {r}'
    return ';'.join(ans(Fraction(int(r.p), int(r.q))) for r in roots)


# ── 7 класс ─────────────────────────────────────────────────────────────────

def e7_ax_b(rng):
    a, r = nz(rng, 2, 12), nz(rng, 1, 15)
    return f'{lin(a, 0)}={a * r}', a * x, sympy.Integer(a * r), ()


def e7_x_plus(rng):
    b, r = nz(rng, 2, 30), nz(rng, 1, 30)
    return f'{lin(1, b)}={r + b}', x + b, sympy.Integer(r + b), ()


def e7_ax_plus_b(rng):
    a, b, r = nz(rng, 2, 9), nz(rng, 1, 20), nz(rng, 1, 12)
    return f'{lin(a, b)}={a * r + b}', a * x + b, sympy.Integer(a * r + b), ()


def e7_b_minus_ax(rng):
    a, b, r = rng.randint(2, 9), rng.randint(1, 25), nz(rng, 1, 10)
    return f'{b}-{lin(a, 0)}={b - a * r}', b - a * x, sympy.Integer(b - a * r), ()


def e7_frac_root(rng):
    a = rng.randint(2, 9)
    p = nz(rng, 1, 20)
    if p % a == 0:
        p += 1
    b = nz(rng, 1, 15)
    return f'{lin(a, b)}={p + b}', a * x + b, sympy.Integer(p + b), ()


def e7_both_sides(rng):
    a, c = rng.sample([k for k in range(-9, 10) if k != 0], 2)
    r, b = nz(rng, 1, 10), nz(rng, 1, 15)
    d = a * r + b - c * r
    return f'{lin(a, b)}={lin(c, d)}', a * x + b, c * x + d, ()


def e7_bracket(rng):
    a, b, r = nz(rng, 2, 8), nz(rng, 1, 9), nz(rng, 1, 10)
    return f'{a}({lin(1, b)})={a * (r + b)}', a * (x + b), sympy.Integer(a * (r + b)), ()


def e7_bracket_both(rng):
    a, b, c = nz(rng, 2, 7), nz(rng, 1, 9), nz(rng, 1, 9)
    if a == c:
        c += 1
    r = nz(rng, 1, 9)
    d = a * (r + b) - c * r
    return f'{a}({lin(1, b)})={lin(c, d)}', a * (x + b), c * x + d, ()


def e7_two_brackets(rng):
    a, b, c, d = rng.randint(2, 7), nz(rng, 1, 9), rng.randint(2, 7), nz(rng, 1, 9)
    if a == c:
        c += 1
    r = nz(rng, 1, 8)
    e = a * (r + b) - c * (r + d)
    return f'{a}({lin(1, b)})-{c}({lin(1, d)})={e}', a * (x + b) - c * (x + d), sympy.Integer(e), ()


def e7_bracket_k(rng):
    a, k, b = nz(rng, 2, 6), rng.randint(2, 5), nz(rng, 1, 9)
    r = nz(rng, 1, 8)
    c = nz(rng, 1, 6)
    if c == a * k:
        c += 1
    d = a * (k * r + b) - c * r
    return f'{a}({lin(k, b)})={lin(c, d)}', a * (k * x + b), c * x + d, ()


def e7_x_over(rng):
    a, b = rng.sample(range(2, 9), 2)
    m = sympy.ilcm(a, b) * nz(rng, 1, 4)
    c = Fraction(m, a) + Fraction(m, b)
    return f'\\dfrac{{x}}{{{a}}}+\\dfrac{{x}}{{{b}}}={num(c)}', x / a + x / b, Rational(c.numerator, c.denominator), ()


def e7_x_plus_over(rng):
    a, b, c = nz(rng, 1, 12), rng.randint(2, 9), nz(rng, 1, 8)
    return f'\\dfrac{{{lin(1, a)}}}{{{b}}}={c}', (x + a) / b, sympy.Integer(c), ()


def e7_two_fracs(rng):
    while True:
        a, b, c, d, e, f = rng.randint(1, 5), nz(rng, 1, 9), rng.randint(2, 7), rng.randint(1, 5), nz(rng, 1, 9), rng.randint(2, 7)
        if c != f and Fraction(a, c) != Fraction(d, f):
            break
    lhs, rhs = (a * x + b) / c, (d * x + e) / f
    return f'\\dfrac{{{lin(a, b)}}}{{{c}}}=\\dfrac{{{lin(d, e)}}}{{{f}}}', lhs, rhs, ()


def e7_frac_minus(rng):
    b, d = rng.sample(range(2, 7), 2)
    a, c = nz(rng, 1, 9), nz(rng, 1, 9)
    r = nz(rng, 1, 12)
    e = Fraction(r + a, b) - Fraction(r + c, d)
    return (f'\\dfrac{{{lin(1, a)}}}{{{b}}}-\\dfrac{{{lin(1, c)}}}{{{d}}}={num(e)}',
            (x + a) / b - (x + c) / d, Rational(e.numerator, e.denominator), ())


def e7_decimals(rng):
    while True:
        a, c = Fraction(rng.randint(1, 19), 10), Fraction(rng.randint(1, 19), 10)
        if a != c:
            break
    b = Fraction(nz(rng, 1, 40), 10)
    r = nz(rng, 1, 15)
    d = a * r + b - c * r
    dx = lambda q: 'x' if q == 1 else f'{dec(q)}x'
    tex = f'{dx(a)}{"+" if b > 0 else "-"}{dec(abs(b))}={dx(c)}{"+" if d >= 0 else "-"}{dec(abs(d))}'
    R = lambda q: Rational(q.numerator, q.denominator)
    return tex, R(a) * x + R(b), R(c) * x + R(d), ()


def e7_product2(rng):
    a, b = rng.sample([k for k in range(-12, 13) if k != 0], 2)
    return f'({lin(1, -a)})({lin(1, -b)})=0', (x - a) * (x - b), sympy.Integer(0), ()


def e7_x_times(rng):
    a, b = rng.randint(2, 9), nz(rng, 1, 15)
    return f'x({lin(a, b)})=0', x * (a * x + b), sympy.Integer(0), ()


def e7_product_k(rng):
    a, b, c, d = rng.randint(2, 7), nz(rng, 1, 9), rng.randint(1, 5), nz(rng, 1, 9)
    return f'({lin(a, b)})({lin(c, d)})=0', (a * x + b) * (c * x + d), sympy.Integer(0), ()


def e7_product3(rng):
    a, b = rng.sample([k for k in range(-9, 10) if k != 0], 2)
    k, c = rng.randint(2, 5), nz(rng, 1, 9)
    return f'({lin(1, -a)})({lin(1, -b)})({lin(k, c)})=0', (x - a) * (x - b) * (k * x + c), sympy.Integer(0), ()


# ── 8 класс ─────────────────────────────────────────────────────────────────

def e8_ax2_bx(rng):
    a, b = nz(rng, 1, 9), nz(rng, 1, 30)
    return f'{poly([a, b, 0])}=0', a * x ** 2 + b * x, sympy.Integer(0), ()


def e8_ax2_c(rng):
    p, q = rng.randint(1, 12), rng.choice([1, 1, 1, 2, 3, 5])
    a = q * q * rng.choice([1, 1, 2, 3])
    c = Fraction(p * p * a, q * q)
    assert c.denominator == 1
    c = int(c)
    if rng.random() < 0.5:
        return f'{poly([a, 0, -c])}=0', a * x ** 2 - c, sympy.Integer(0), ()
    return f'{poly([a, 0, 0])}={c}', a * x ** 2, sympy.Integer(c), ()


def e8_shift_sq(rng):
    a, b = nz(rng, 1, 9), rng.randint(1, 10)
    return f'({lin(1, a)})^{{2}}={b * b}', (x + a) ** 2, sympy.Integer(b * b), ()


def e8_move(rng):
    a, b = rng.randint(2, 7), nz(rng, 1, 20)
    return f'{poly([a, 0, 0])}={lin(b, 0)}', a * x ** 2, b * x, ()


def e8_monic(rng):
    r1, r2 = rng.sample([k for k in range(-12, 13) if k != 0], 2)
    return f'{poly([1, -(r1 + r2), r1 * r2])}=0', x ** 2 - (r1 + r2) * x + r1 * r2, sympy.Integer(0), ()


def e8_rational_roots(rng):
    while True:
        p, q = rng.randint(2, 5), nz(rng, 1, 7)
        r = nz(rng, 1, 8)
        if sympy.gcd(p, q) == 1:
            break
    # (p x − q)(x − r) = p x² − (pr + q) x + q r
    k = rng.choice([1, 1, -1])
    coefs = [k * p, -k * (p * r + q), k * q * r]
    return f'{poly(coefs)}=0', sum(c * x ** (2 - i) for i, c in enumerate(coefs)), sympy.Integer(0), ()


def e8_rearrange(rng):
    r1, r2 = rng.sample([k for k in range(-10, 11) if k != 0], 2)
    b, c = r1 + r2, -r1 * r2
    return f'x^{{2}}={lin(b, c)}', x ** 2, b * x + c, ()


def e8_brackets(rng):
    r1, r2 = rng.sample([k for k in range(-9, 10) if k != 0], 2)
    a, b = rng.sample([k for k in range(-8, 9) if k != 0], 2)
    # (x+a)(x+b) = c  ⇔  x² + (a+b)x + ab − c = 0 с корнями r1, r2
    if a + b != -(r1 + r2):
        b = -(r1 + r2) - a
    if b == 0:
        b = 1
        a = -(r1 + r2) - 1
    c = a * b - r1 * r2
    return f'({lin(1, a)})({lin(1, b)})={c}', (x + a) * (x + b), sympy.Integer(c), ()


def e8_double(rng):
    r = nz(rng, 1, 9)
    k = rng.choice([1, 1, 2, 3])
    coefs = [k, -2 * k * r, k * r * r]
    return f'{poly(coefs)}=0', k * (x - r) ** 2, sympy.Integer(0), ()


def e8_cancel(rng):
    a = rng.randint(1, 12) * rng.choice([1, -1])
    return f'\\dfrac{{{poly([1, 0, -a * a])}}}{{{lin(1, -a)}}}=0', (x ** 2 - a * a) / (x - a), sympy.Integer(0), [(x - a, 'ne0')]


def e8_cancel_trinomial(rng):
    r1, r2 = rng.sample([k for k in range(-9, 10) if k != 0], 2)
    m = rng.choice([r1, r2, nz(rng, 1, 9)])
    num_ = x ** 2 - (r1 + r2) * x + r1 * r2
    return (f'\\dfrac{{{poly([1, -(r1 + r2), r1 * r2])}}}{{{lin(1, -m)}}}=0',
            num_ / (x - m), sympy.Integer(0), [(x - m, 'ne0')])


def e8_linear_frac(rng):
    a, b, c = nz(rng, 1, 9), nz(rng, 1, 9), rng.choice([2, 3, 4, 5, -2, -3])
    return f'\\dfrac{{{lin(1, a)}}}{{{lin(1, b)}}}={c}', (x + a) / (x + b), sympy.Integer(c), [(x + b, 'ne0')]


def e8_proportion(rng):
    a, b = rng.sample(range(1, 10), 2)
    c, d = nz(rng, 1, 7), nz(rng, 1, 7)
    return (f'\\dfrac{{{a}}}{{{lin(1, c)}}}=\\dfrac{{{b}}}{{{lin(1, d)}}}', a / (x + c), b / (x + d),
            [(x + c, 'ne0'), (x + d, 'ne0')])


def e8_x_over(rng):
    # x²/(x−a) = cx/(x−a): корни 0 и c, но c = a — посторонний
    a, c = rng.randint(1, 9), rng.randint(1, 9)
    return (f'\\dfrac{{x^{{2}}}}{{{lin(1, -a)}}}=\\dfrac{{{lin(c, 0)}}}{{{lin(1, -a)}}}',
            x ** 2 / (x - a), c * x / (x - a), [(x - a, 'ne0')])


def e8_sum_fracs(rng):
    a, b = rng.sample(range(1, 8), 2)
    k = rng.choice([1, 2, 3])
    return (f'\\dfrac{{{k}}}{{{lin(1, -a)}}}+\\dfrac{{{k}}}{{{lin(1, a)}}}=\\dfrac{{{b}}}{{{poly([1, 0, -a * a])}}}',
            k / (x - a) + k / (x + a), b / (x ** 2 - a * a), [(x - a, 'ne0'), (x + a, 'ne0')])


def e8_biquad(rng):
    a, b = rng.sample(range(1, 10), 2)
    p, q = a * a + b * b, a * a * b * b
    return f'x^{{4}}-{p}x^{{2}}+{q}=0', x ** 4 - p * x ** 2 + q, sympy.Integer(0), ()


def e8_biquad_neg(rng):
    a, n = rng.randint(1, 6), rng.randint(1, 20)
    # (x² − a²)(x² + n) = x⁴ + (n − a²)x² − a²n
    return f'{poly([1, 0, n - a * a, 0, -a * a * n])}=0', (x ** 2 - a * a) * (x ** 2 + n), sympy.Integer(0), ()


def e8_subst(rng):
    shape = rng.choice([(1, 0), (1, 1), (1, -1), (1, 2), (1, -2), (1, 3), (1, -3)])
    inner_tex = poly([shape[0], shape[1], 0])
    inner = x ** 2 + shape[1] * x
    # t = inner; берём t с рациональными корнями inner = t
    cands = sorted({int(sympy.expand(inner.subs(x, k))) for k in range(-8, 9)})
    t1, t2 = rng.sample(cands, 2)
    return (f'({inner_tex})^{{2}}{term(-(t1 + t2), "(" + inner_tex + ")", False)}{term(t1 * t2, "", False)}=0',
            (inner - t1) * (inner - t2), sympy.Integer(0), ())


def e8_subst_lin(rng):
    a = nz(rng, 1, 6)
    t1, t2 = rng.sample([k for k in range(-9, 10) if k != 0], 2)
    inner_tex = lin(1, a)
    return (f'({inner_tex})^{{2}}{term(-(t1 + t2), "(" + inner_tex + ")", False)}{term(t1 * t2, "", False)}=0',
            (x + a - t1) * (x + a - t2), sympy.Integer(0), ())


# ── 10–11 класс ─────────────────────────────────────────────────────────────

def power_of(base, k) -> str:
    v = Fraction(base) ** k
    return num(v)


def e10_exp_simple(rng):
    b = rng.choice([2, 3, 5, 2, 3])
    k = rng.randint(-3, 5 if b == 2 else 4)
    a, c = rng.choice([1, 1, 2, 3]), nz(rng, 1, 6)
    return f'{b}^{{{lin(a, c)}}}={power_of(b, k)}', sympy.Integer(b) ** (a * x + c), Rational(Fraction(b) ** k), ()


def e10_exp_recip(rng):
    b = rng.choice([2, 3, 5])
    k = rng.randint(-3, 3)
    c = nz(rng, 1, 5)
    return (f'\\left(\\dfrac{{1}}{{{b}}}\\right)^{{{lin(1, c)}}}={power_of(b, k)}',
            Rational(1, b) ** (x + c), Rational(Fraction(b) ** k), ())


def e10_exp_same_family(rng):
    base = rng.choice([2, 3])
    p, q = rng.sample([1, 2, 3] if base == 3 else [1, 2, 3, 4], 2)
    a, c = nz(rng, 1, 4), nz(rng, 1, 4)
    left, right = base ** p, base ** q
    return (f'{left}^{{{lin(1, a)}}}={right}^{{{lin(1, c)}}}',
            sympy.Integer(left) ** (x + a), sympy.Integer(right) ** (x + c), ())


def e10_exp_quad(rng):
    base = rng.choice([2, 3])
    # t = base^x, (t − base^m)(t − s) = 0, s — степень или отрицательное (посторонний)
    m = rng.randint(0, 3)
    s = base ** rng.randint(0, 3) if rng.random() < 0.7 else -rng.randint(1, 8)
    if s == base ** m:
        s = -1
    p, q = base ** m + s, base ** m * s
    mid = term(-p, f'{base}^{{x}}', False) if abs(p) == 1 else term(-p, '\\cdot ' + f'{base}^{{x}}', False)
    tex = f'{base * base}^{{x}}{mid}{term(q, "", False)}=0'
    t = sympy.Integer(base) ** x
    return tex, t ** 2 - p * t + q, sympy.Integer(0), ()


def e10_exp_quad_exponent(rng):
    b = rng.choice([2, 3, 5])
    r1, r2 = rng.sample(range(-4, 6), 2)
    k = -r1 * r2  # x² − (r1+r2)x = k
    if abs(k) > {2: 6, 3: 4, 5: 3}[b]:
        return None   # правая часть вроде 65536 — это уже не тренажёр приёма
    return (f'{b}^{{{poly([1, -(r1 + r2), 0])}}}={power_of(b, k)}',
            sympy.Integer(b) ** (x ** 2 - (r1 + r2) * x), Rational(Fraction(b) ** k), ())


def logtex(b) -> str:
    return '\\lg' if b == 10 else f'\\log_{{{b}}}'


def logsym(arg, b):
    return log(arg, b)


def e10_log_simple(rng):
    b = rng.choice([2, 3, 5, 10, 2, 3])
    k = rng.randint(-2, 4 if b <= 3 else 2)
    a, c = rng.randint(1, 5), nz(rng, 1, 9)
    return (f'{logtex(b)}({lin(a, c)})={k}', logsym(a * x + c, b), sympy.Integer(k), [(a * x + c, 'gt0')])


def e10_log_equal_args(rng):
    b = rng.choice([2, 3, 5, 7])
    while True:
        a, c, d, e = rng.randint(1, 5), nz(rng, 1, 9), rng.randint(1, 5), nz(rng, 1, 9)
        if a != d:
            break
    return (f'{logtex(b)}({lin(a, c)})={logtex(b)}({lin(d, e)})', logsym(a * x + c, b), logsym(d * x + e, b),
            [(a * x + c, 'gt0'), (d * x + e, 'gt0')])


def e10_log_quad(rng):
    b = rng.choice([2, 3, 5])
    k = rng.randint(1, 3)
    r1, r2 = rng.sample(range(-6, 7), 2)
    c = r1 * r2 + b ** k  # x² − (r1+r2)x + c = b^k
    arg = x ** 2 - (r1 + r2) * x + c
    return (f'{logtex(b)}({poly([1, -(r1 + r2), c])})={k}', logsym(arg, b), sympy.Integer(k), [(arg, 'gt0')])


def e10_log_sum(rng):
    b = rng.choice([2, 3, 5, 6])
    a, c = rng.sample(range(-5, 8), 2)
    k = rng.randint(1, 3)
    return (f'{logtex(b)}({lin(1, a)})+{logtex(b)}({lin(1, c)})={k}', logsym((x + a) * (x + c), b), sympy.Integer(k),
            [(x + a, 'gt0'), (x + c, 'gt0')])


def e10_log_power(rng):
    b = rng.choice([2, 3, 4, 5, 10])
    k = nz(rng, 1, 3)
    return (f'{logtex(b)} x={k}', logsym(x, b), sympy.Integer(k), [(x, 'gt0')])


def e10_sqrt_simple(rng):
    a, c = rng.randint(1, 6), rng.randint(1, 9)
    r = nz(rng, 1, 15)
    b = c * c - a * r
    return f'\\sqrt{{{lin(a, b)}}}={c}', sqrt(a * x + b), sympy.Integer(c), [(a * x + b, 'ge0')]


def e10_sqrt_equal(rng):
    while True:
        a, b, c, d = rng.randint(1, 6), nz(rng, 1, 15), rng.randint(1, 6), nz(rng, 1, 15)
        if a != c:
            break
    return (f'\\sqrt{{{lin(a, b)}}}=\\sqrt{{{lin(c, d)}}}', sqrt(a * x + b), sqrt(c * x + d),
            [(a * x + b, 'ge0'), (c * x + d, 'ge0')])


def e10_sqrt_linear(rng):
    # √(x + a) = x + b: корни квадрата (x+b)² = x + a, лишний отсеет проверка
    r1, r2 = rng.sample(range(-6, 10), 2)
    # (x + b)² − (x + a) = (x − r1)(x − r2) ⇒ 2b − 1 = −(r1+r2), b² − a = r1 r2
    if (1 - (r1 + r2)) % 2:
        r2 += 1
        if r2 == r1:
            r2 += 2
    b = (1 - (r1 + r2)) // 2
    a = b * b - r1 * r2
    return f'\\sqrt{{{lin(1, a)}}}={lin(1, b)}', sqrt(x + a), x + b, [(x + a, 'ge0'), (x + b, 'ge0')]


def e10_cbrt(rng):
    a, c = rng.randint(1, 6), nz(rng, 1, 5)
    r = nz(rng, 1, 12)
    b = c ** 3 - a * r
    return f'\\sqrt[3]{{{lin(a, b)}}}={c}', a * x + b, sympy.Integer(c ** 3), ()


TRIG = {
    'sin': {Rational(1, 2): [30, 150], Rational(-1, 2): [210, 330], sqrt(2) / 2: [45, 135], -sqrt(2) / 2: [225, 315],
            sqrt(3) / 2: [60, 120], -sqrt(3) / 2: [240, 300], sympy.Integer(1): [90], sympy.Integer(-1): [270], sympy.Integer(0): [0, 180]},
    'cos': {Rational(1, 2): [60, 300], Rational(-1, 2): [120, 240], sqrt(2) / 2: [45, 315], -sqrt(2) / 2: [135, 225],
            sqrt(3) / 2: [30, 330], -sqrt(3) / 2: [150, 210], sympy.Integer(1): [0], sympy.Integer(-1): [180], sympy.Integer(0): [90, 270]},
    'tg': {sympy.Integer(1): [45], sympy.Integer(-1): [135], sqrt(3): [60], -sqrt(3): [120], sqrt(3) / 3: [30], -sqrt(3) / 3: [150], sympy.Integer(0): [0]},
}
VAL_TEX = {Rational(1, 2): '\\dfrac{1}{2}', Rational(-1, 2): '-\\dfrac{1}{2}', sqrt(2) / 2: '\\dfrac{\\sqrt{2}}{2}',
           -sqrt(2) / 2: '-\\dfrac{\\sqrt{2}}{2}', sqrt(3) / 2: '\\dfrac{\\sqrt{3}}{2}', -sqrt(3) / 2: '-\\dfrac{\\sqrt{3}}{2}',
           sympy.Integer(1): '1', sympy.Integer(-1): '-1', sympy.Integer(0): '0', sqrt(3): '\\sqrt{3}', -sqrt(3): '-\\sqrt{3}',
           sqrt(3) / 3: '\\dfrac{\\sqrt{3}}{3}', -sqrt(3) / 3: '-\\dfrac{\\sqrt{3}}{3}'}
SHIFTS = [0, 0, 30, -30, 45, -45, 60, -60, 90, -90]


def pi_tex(deg) -> str:
    q = Fraction(deg, 180)
    if q == 0:
        return '0'
    s = '-' if q < 0 else ''
    q = abs(q)
    if q.denominator == 1:
        return f'{s}{"" if q.numerator == 1 else q.numerator}\\pi'
    top = '\\pi' if q.numerator == 1 else f'{q.numerator}\\pi'
    return f'{s}\\dfrac{{{top}}}{{{q.denominator}}}'


def e10_trig(rng):
    """Наименьший положительный корень в градусах; аргумент a·x + b (b в радианах)."""
    fn = rng.choice(['sin', 'cos', 'tg', 'sin', 'cos'])
    v = rng.choice(list(TRIG[fn]))
    a = rng.choice([1, 1, 2, 3, Fraction(1, 2)])
    b = rng.choice(SHIFTS)
    period = 180 if fn == 'tg' else 360
    # аргумент u = a·x + b ∈ {base + period·n}; x = (u − b)/a; ищем наименьший положительный
    cands = []
    for base in TRIG[fn][v]:
        for n in range(-4, 8):
            xv = (Fraction(base + period * n) - b) / Fraction(a)
            if xv > 0:
                cands.append(xv)
    best = min(cands)
    if best.denominator != 1:
        return None
    a_tex = '' if a == 1 else (f'\\dfrac{{x}}{{{Fraction(a).denominator}}}' if a == Fraction(1, 2) else str(a))
    xarg = a_tex if a == Fraction(1, 2) else f'{a_tex}x'
    arg = xarg if b == 0 else f'{xarg}{"+" if b > 0 else "-"}{pi_tex(abs(b))}'
    fn_tex = {'sin': '\\sin', 'cos': '\\cos', 'tg': '\\operatorname{tg}'}[fn]
    tex = f'{fn_tex}\\left({arg}\\right)={VAL_TEX[v]}' if b != 0 or a != 1 else f'{fn_tex} x={VAL_TEX[v]}'
    # Независимая проверка. Точно: best — корень. Численно: других корней на
    # (0; best) нет — все корни таких уравнений кратны 1/6°, перебираем этот шаг.
    f = {'sin': sympy.sin, 'cos': sympy.cos, 'tg': sympy.tan}[fn]
    a_q = Rational(Fraction(a).numerator, Fraction(a).denominator)
    u = lambda deg: a_q * pi * deg / 180 + pi * Rational(b, 180)
    assert sympy.simplify(f(u(Rational(int(best)))) - v) == 0, f'{tex}: {best} не корень'
    fl = {'sin': math.sin, 'cos': math.cos, 'tg': math.tan}[fn]
    vf = float(v)
    af = float(Fraction(a))
    for i in range(1, int(best) * 6):
        uu = af * math.pi * (i / 6) / 180 + math.pi * b / 180
        if fn == 'tg' and abs(math.cos(uu)) < 1e-12:
            continue
        assert abs(fl(uu) - vf) > 1e-9, f'{tex}: корень {i / 6}° меньше {best}'
    return tex, int(best)


# ── сборка ──────────────────────────────────────────────────────────────────

GRADES = [
    (7, '7 класс', [
        ('Уровень 1 — линейные', [(e7_ax_b, 20), (e7_x_plus, 15), (e7_ax_plus_b, 30), (e7_b_minus_ax, 20), (e7_frac_root, 15)]),
        ('Уровень 2 — скобки и перенос', [(e7_both_sides, 30), (e7_bracket, 15), (e7_bracket_both, 20), (e7_two_brackets, 20), (e7_bracket_k, 15)]),
        ('Уровень 3 — дробные коэффициенты', [(e7_x_over, 20), (e7_x_plus_over, 15), (e7_two_fracs, 25), (e7_frac_minus, 20), (e7_decimals, 20)]),
        ('Уровень 4 — произведение равно нулю', [(e7_product2, 30), (e7_x_times, 25), (e7_product_k, 25), (e7_product3, 20)]),
    ]),
    (8, '8 класс', [
        ('Уровень 1 — неполные квадратные', [(e8_ax2_bx, 35), (e8_ax2_c, 30), (e8_shift_sq, 20), (e8_move, 15)]),
        ('Уровень 2 — полные квадратные через дискриминант', [(e8_monic, 35), (e8_rational_roots, 30), (e8_rearrange, 15), (e8_brackets, 12), (e8_double, 8)]),
        ('Уровень 3 — дробно-рациональные', [(e8_cancel, 15), (e8_cancel_trinomial, 25), (e8_linear_frac, 20), (e8_proportion, 20), (e8_x_over, 10), (e8_sum_fracs, 10)]),
        ('Уровень 4 — биквадратные и замена', [(e8_biquad, 30), (e8_biquad_neg, 25), (e8_subst, 25), (e8_subst_lin, 20)]),
    ]),
    (10, '10–11 класс', [
        ('Уровень 1 — показательные', [(e10_exp_simple, 30), (e10_exp_recip, 15), (e10_exp_same_family, 20), (e10_exp_quad, 20), (e10_exp_quad_exponent, 15)]),
        ('Уровень 2 — логарифмические', [(e10_log_simple, 30), (e10_log_equal_args, 20), (e10_log_quad, 20), (e10_log_sum, 20), (e10_log_power, 10)]),
        ('Уровень 3 — иррациональные', [(e10_sqrt_simple, 30), (e10_sqrt_equal, 20), (e10_sqrt_linear, 30), (e10_cbrt, 20)]),
        ('Уровень 4 — простейшие тригонометрические', [(e10_trig, 100)]),
    ]),
]

TRIG_PREFIX = 'Найдите наименьший положительный корень уравнения'


def make(rng, template):
    if template is e10_trig:
        out = template(rng)
        if out is None:
            return None
        tex, deg = out
        return f'{TRIG_PREFIX} ${tex}$. Ответ дайте в градусах.', str(deg)
    got = template(rng)
    if got is None:
        return None
    tex, lhs, rhs, domain = got
    roots = real_roots(lhs, rhs, list(domain))
    if not roots or len(roots) > 4:
        return None
    if any(not r.is_rational for r in roots):
        return None
    if any(abs(r) > 60 for r in roots):
        return None
    return f'{SOLVE}: ${tex}$', roots_answer(roots)


def main() -> None:
    rng = random.Random(SEED)
    seen = set()
    zadaniya, stats = [], {}
    for grade, title, levels in GRADES:
        tasks, subtypes = [], []
        for li, (ltitle, templates) in enumerate(levels):
            subtypes.append(ltitle)
            for template, count in templates:
                made, guard = 0, 0
                while made < count:
                    guard += 1
                    assert guard < 20000, f'{template.__name__}: не набралось уникальных'
                    got = make(rng, template)
                    if got is None or got[0] in seen:
                        continue
                    seen.add(got[0])
                    tasks.append({'id': len(tasks) + 1, 'expression': got[0], 'answer': got[1],
                                  'status': 'production', 'subtype': li, 'level': li + 1})
                    made += 1
                stats[f'{title} · {ltitle} · {template.__name__}'] = made
                print(f'  {template.__name__}: {made} (попыток {guard})', flush=True)
        assert len(tasks) == PER_LEVEL * len(levels)
        zadaniya.append({'number': grade, 'title': title, 'grade': grade, 'instruction': 'Уравнения',
                         'type': 'expression', 'status': 'production', 'subtypes': subtypes, 'tasks': tasks})

    data = {
        'bank': 'skills',
        'topic': '05',
        'meta': {
            'title': 'Уравнения',
            'description': 'Линейные, квадратные, дробно-рациональные, биквадратные; '
                           'показательные, логарифмические, иррациональные и простейшие тригонометрические. '
                           '7, 8 и 10–11 класс, по четыре уровня из 100 примеров.',
        },
        'generator': {'script': 'scripts/generate-skills-equations.py', 'seed': SEED},
        'zadaniya': zadaniya,
    }
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(f'{OUT}: {sum(len(z["tasks"]) for z in zadaniya)} примеров')
    for k, v in stats.items():
        print(f'  {k}: {v}')


if __name__ == '__main__':
    main()
