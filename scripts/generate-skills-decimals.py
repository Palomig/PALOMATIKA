#!/usr/bin/env python3
"""
Генератор темы «Десятичные дроби» для банка «Скиллы».

100 примеров, в каждом ровно три действия из набора + − × ÷ (три РАЗНЫХ),
четыре операнда. Примеры разбиты на четыре задания по набору действий:
без деления, без умножения, без вычитания, без сложения — по 25 в каждом.

Тема сквозная для 5–11 классов, поэтому примеры не упрощены: внутри
каждого задания они отсортированы от простых к сложным (по числу знаков
после запятой, вложенности скобок и величине чисел).

Ограничения, чтобы пример решался столбиком без ловушек:
  • операнды — десятичные дроби с 1–2 знаками, не больше одного целого,
    все четыре числа разные;
  • каждый промежуточный результат положителен, не больше 1000 и имеет
    не больше трёх знаков после запятой;
  • деление — только нацело в десятичных: частное с ≤ 2 знаками;
  • ни одного умножения/деления на 1 и ни одного нуля по дороге.

Запуск:  python3 scripts/generate-skills-decimals.py
Пишет:   database/data/skills/decimals.json (детерминированно, seed фиксирован)
"""

import json
import random
from decimal import Decimal, ROUND_HALF_UP
from itertools import combinations
from pathlib import Path

SEED = 20260920
PER_GROUP = 25
OUT = Path(__file__).resolve().parent.parent / 'database' / 'data' / 'skills' / 'decimals.json'

PREC = {'+': 1, '-': 1, '*': 2, '/': 2}
TEX = {'+': '+', '-': '-', '*': r'\cdot', '/': ':'}

GROUPS = [
    (('+', '-', '*'), 'Сложение, вычитание и умножение'),
    (('+', '-', '/'), 'Сложение, вычитание и деление'),
    (('+', '*', '/'), 'Сложение, умножение и деление'),
    (('-', '*', '/'), 'Вычитание, умножение и деление'),
]

# Все формы дерева из трёх бинарных действий над четырьмя листьями.
# Лист — int (индекс операнда), узел — (op_index, left, right).
SHAPES = [
    ((0, (1, (2, 0, 1), 2), 3)),          # ((a∘b)∘c)∘d
    ((0, (1, 0, (2, 1, 2)), 3)),          # (a∘(b∘c))∘d
    ((0, 0, (1, (2, 1, 2), 3))),          # a∘((b∘c)∘d)
    ((0, 0, (1, 1, (2, 2, 3)))),          # a∘(b∘(c∘d))
    ((0, (1, 0, 1), (2, 2, 3))),          # (a∘b)∘(c∘d)
]


def dec(value: str) -> Decimal:
    return Decimal(value)


def places(x: Decimal) -> int:
    exp = x.normalize().as_tuple().exponent
    return -exp if exp < 0 else 0


def random_operand(rng: random.Random, allow_int: bool) -> Decimal:
    kind = rng.random()
    if allow_int and kind < 0.12:
        return Decimal(rng.choice([2, 3, 4, 5, 6, 8, 12, 15, 20, 25]))
    if kind < 0.65:
        # одна цифра после запятой: 0,1 … 48,9
        return Decimal(rng.randint(1, 489)) / Decimal(10)
    # две цифры после запятой: 0,05 … 24,99
    return Decimal(rng.randint(5, 2499)) / Decimal(100)


def random_divisor(rng: random.Random) -> Decimal:
    """Делители, на которые в 5–6 классе делят уголком без страха."""
    return Decimal(rng.choice([
        '0,2', '0,4', '0,5', '0,8', '1,2', '1,5', '1,6', '2,5', '0,25', '0,75',
        '1,25', '3,5', '4,5', '0,3', '0,6', '0,9', '1,8', '2,4', '0,05', '0,15',
        '2', '4', '5', '8', '2,2', '3,2', '6,4', '0,35', '0,45', '1,4', '0,7',
    ]).replace(',', '.'))


def apply(op: str, a: Decimal, b: Decimal):
    if op == '+':
        return a + b
    if op == '-':
        return a - b
    if op == '*':
        return a * b
    if b == 0:
        return None
    q = a / b
    if q != q.quantize(Decimal('0.01'), rounding=ROUND_HALF_UP):
        return None
    return q


def evaluate(node, ops, operands):
    """Возвращает значение или None, если пример нарушает ограничения."""
    if isinstance(node, int):
        return operands[node]
    op = ops[node[0]]
    a = evaluate(node[1], ops, operands)
    b = evaluate(node[2], ops, operands)
    if a is None or b is None:
        return None
    if op in '*/' and (a == 1 or b == 1):
        return None
    r = apply(op, a, b)
    if r is None or r <= 0 or places(r) > 3 or r > 1000:
        return None
    return r


def render(node, ops, operands, parent_prec=0, right=False, parent_op='') -> str:
    if isinstance(node, int):
        return fmt(operands[node])
    op = ops[node[0]]
    prec = PREC[op]
    body = f'{render(node[1], ops, operands, prec, False, op)} {TEX[op]} {render(node[2], ops, operands, prec, True, op)}'
    # Скобки: приоритет ниже родительского, либо правый операнд вычитания
    # и деления — a − (b − c), a − (b + c), a : (b · c) обязаны их сохранить;
    # a + (b − c) и a · (b : c) без скобок значат то же самое.
    if prec < parent_prec or (right and prec == parent_prec and parent_op in '-/'):
        return f'({body})'
    return body


def fmt(x: Decimal) -> str:
    s = format(x.normalize(), 'f')
    if 'E' in s or 'e' in s:
        s = format(x, 'f').rstrip('0').rstrip('.')
    return s.replace('.', '{,}')


def fmt_answer(x: Decimal) -> str:
    s = format(x.normalize(), 'f')
    if 'E' in s or 'e' in s:
        s = format(x, 'f').rstrip('0').rstrip('.')
    return s.replace('.', ',')


def pick_operands(rng: random.Random, node, ops):
    """Операнды по форме дерева: правый операнд деления берём из «удобных» делителей."""
    operands = [None] * 4
    ints_left = 1

    def walk(n, as_divisor):
        nonlocal ints_left
        if isinstance(n, int):
            if as_divisor:
                operands[n] = random_divisor(rng)
            else:
                v = random_operand(rng, ints_left > 0)
                if v == v.to_integral_value():
                    ints_left -= 1
                operands[n] = v
            return
        op = ops[n[0]]
        walk(n[1], False)
        walk(n[2], op == '/')

    walk(node, False)
    # Делитель тоже мог оказаться целым — не больше одного целого на пример,
    # и все четыре числа разные: 13,2 + 13,2 выглядит опечаткой.
    if sum(1 for v in operands if v == v.to_integral_value()) > 1:
        return None
    if len(set(operands)) < 4:
        return None
    return operands


def difficulty(node, ops, operands, value) -> tuple:
    """Грубый порядок «от простого к сложному» внутри задания."""
    depth = 0

    def d(n):
        nonlocal depth
        if isinstance(n, int):
            return 0
        return 1 + max(d(n[1]), d(n[2]))

    depth = d(node)
    total_places = sum(places(v) for v in operands)
    return (total_places, depth, max(operands), value)


def generate(rng: random.Random, opset, count: int, seen: set) -> list:
    out = []
    attempts = 0
    while len(out) < count and attempts < 200000:
        attempts += 1
        ops = list(opset)
        rng.shuffle(ops)
        shape = rng.choice(SHAPES)
        operands = pick_operands(rng, shape, ops)
        if operands is None:
            continue
        value = evaluate(shape, ops, operands)
        if value is None:
            continue
        tex = render(shape, ops, operands)
        if tex in seen:
            continue
        seen.add(tex)
        out.append({'tex': tex, 'value': value, 'rank': difficulty(shape, ops, operands, value)})
    if len(out) < count:
        raise SystemExit(f'не удалось набрать {count} примеров для {opset}: только {len(out)}')
    out.sort(key=lambda e: e['rank'])
    return out


def main() -> None:
    rng = random.Random(SEED)
    seen: set = set()
    zadaniya = []
    for number, (opset, title) in enumerate(GROUPS, start=1):
        examples = generate(rng, opset, PER_GROUP, seen)
        tasks = []
        for i, ex in enumerate(examples, start=1):
            tasks.append({
                'id': i,
                'expression': f'${ex["tex"]}$',
                'answer': fmt_answer(ex['value']),
                'status': 'production',
            })
        zadaniya.append({
            'number': number,
            'title': title,
            'instruction': 'Найдите значение выражения:',
            'type': 'expression',
            'status': 'production',
            'tasks': tasks,
        })

    data = {
        'bank': 'skills',
        'topic': '01',
        'meta': {
            'title': 'Десятичные дроби',
            'description': 'Сложение, вычитание, умножение и деление десятичных дробей: '
                           'сто примеров по три действия в каждом.',
        },
        'generator': {'script': 'scripts/generate-skills-decimals.py', 'seed': SEED},
        'zadaniya': zadaniya,
    }
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    total = sum(len(z['tasks']) for z in zadaniya)
    print(f'{OUT}: {total} примеров в {len(zadaniya)} заданиях')
    for z in zadaniya:
        print(f'  {z["number"]}. {z["title"]}: {z["tasks"][0]["expression"]} = {z["tasks"][0]["answer"]}'
              f'  …  {z["tasks"][-1]["expression"]} = {z["tasks"][-1]["answer"]}')


if __name__ == '__main__':
    main()
