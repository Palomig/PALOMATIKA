#!/usr/bin/env python3
"""
Независимая проверка ответов банка «Скиллы».

Читает готовые JSON из `database/data/skills/` — то есть ровно то, что уедет
в базу и что увидит ученик, — переводит LaTeX условия в обычную запись и
сверяет с ответом через sympy. Генераторы проверяют себя сами по своим
внутренним строкам; здесь проверяется опубликованная разметка.

Запуск:  python3 scripts/verify-skills-answers.py [файл.json …]
"""

import json
import re
import sys
from pathlib import Path

import sympy
from sympy.parsing.sympy_parser import (convert_xor,
                                        implicit_multiplication_application,
                                        parse_expr, rationalize,
                                        standard_transformations)

# rationalize: 748,88 читается точной дробью, а не Float — иначе сверка
# «сложили и умножили» спотыкается о двоичное округление.
TRANSFORMS = standard_transformations + (convert_xor, implicit_multiplication_application, rationalize)
DATA_DIR = Path(__file__).resolve().parent.parent / 'database' / 'data' / 'skills'


def take_group(src: str, i: int) -> tuple[str, int]:
    """Содержимое { … } начиная с открывающей скобки на позиции i."""
    depth, start = 0, i
    while i < len(src):
        if src[i] == '{':
            depth += 1
        elif src[i] == '}':
            depth -= 1
            if depth == 0:
                return src[start + 1:i], i + 1
        i += 1
    raise ValueError(f'незакрытая скобка в {src!r}')


def latex_to_plain(tex: str) -> str:
    """Подмножество LaTeX наших банков → запись, понятная sympy."""
    src = tex.strip().strip('$')
    out, i = '', 0
    while i < len(src):
        macro = r'\dfrac' if src.startswith(r'\dfrac', i) else (r'\frac' if src.startswith(r'\frac', i) else None)
        if macro:
            num, i = take_group(src, i + len(macro))
            den, i = take_group(src, i)
            out += f'(({latex_to_plain(num)})/({latex_to_plain(den)}))'
            continue
        if src.startswith(r'\cdot', i):
            out, i = out + '*', i + len(r'\cdot')
            continue
        if src.startswith('{,}', i):          # десятичная запятая банка дробей
            out, i = out + '.', i + 3
            continue
        if src[i] == '{':                     # показатель степени: ^{12}
            body, i = take_group(src, i)
            out += f'({latex_to_plain(body)})'
            continue
        if src[i] == ':':                     # частное: «36m^2n : (18mn)», «27,2 : 4»
            out, i = out + '/', i + 1
            continue
        out, i = out + src[i], i + 1
    return out


def parse(src: str):
    # Между соседними буквами — знак умножения: «yn» иначе уедет в функцию Бесселя.
    return parse_expr(re.sub(r'(?<=[a-z])(?=[a-z])', '*', src), transformations=TRANSFORMS)


def check_file(path: Path) -> tuple[int, list[str]]:
    data = json.loads(path.read_text(encoding='utf-8'))
    errors, total = [], 0
    for zadanie in data.get('zadaniya', []):
        for task in zadanie.get('tasks', []):
            total += 1
            expr = latex_to_plain(task['expression'])
            answer = str(task['answer']).replace(',', '.')
            try:
                diff = sympy.simplify(sympy.cancel(parse(expr) - parse(answer)))
            except Exception as e:  # noqa: BLE001 — любая поломка разбора это ошибка данных
                errors.append(f'{path.name} №{task["id"]}: не разобрано ({e}) — {task["expression"]}')
                continue
            if diff != 0:
                errors.append(f'{path.name} №{task["id"]}: {task["expression"]} ≠ {task["answer"]}')
    return total, errors


def main() -> int:
    paths = [Path(a) for a in sys.argv[1:]] or sorted(DATA_DIR.glob('*.json'))
    failed = 0
    for path in paths:
        total, errors = check_file(path)
        print(f'{path.name}: проверено {total}, ошибок {len(errors)}')
        for e in errors[:20]:
            print('  ' + e)
        failed += len(errors)
    return 1 if failed else 0


if __name__ == '__main__':
    raise SystemExit(main())
