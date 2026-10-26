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


def bare_argument(src: str, i: int) -> str:
    """«\\lg x», «\\cos x»: аргумент без скобок — одна буква — берём в скобки."""
    j = i
    while j < len(src) and src[j] == ' ':
        j += 1
    return f'({src[j]})' if j < len(src) and src[j].isalpha() else ''


def skip_bare(src: str, i: int) -> int:
    j = i
    while j < len(src) and src[j] == ' ':
        j += 1
    return j + 1 if j < len(src) and src[j].isalpha() else i


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
        if src.startswith(r'\sqrt[', i):
            # Корень n-й степени: \sqrt[3]{-125} → real_root(-125, 3).
            close = src.index(']', i)
            index = latex_to_plain(src[i + len(r'\sqrt['):close])
            body, i = take_group(src, close + 1)
            glue = '*' if out and (out[-1].isalnum() or out[-1] == ')') else ''
            out += f'{glue}real_root({latex_to_plain(body)},{index})'
            continue
        if src.startswith(r'\log_', i):        # \log_{2}(…) → log((…), 2)
            base, i = take_group(src, i + len(r'\log_'))
            out += f'logb{len(LOG_BASES)}' + bare_argument(src, i)
            LOG_BASES.append(latex_to_plain(base))
            i = skip_bare(src, i)
            continue
        for macro, name in ((r'\lg', 'logb_ten'), (r'\sin', 'sin'), (r'\cos', 'cos'),
                            (r'\operatorname{tg}', 'tan'), (r'\pi', 'pi')):
            if src.startswith(macro, i):
                glue = '*' if name == 'pi' and out and (out[-1].isalnum() or out[-1] == ')') else ''
                out, i = out + glue + name, i + len(macro)
                if name != 'pi':
                    out += bare_argument(src, i)
                    i = skip_bare(src, i)
                break
        else:
            macro = None
        if macro:
            continue
        if src.startswith(r'\left', i) or src.startswith(r'\right', i):
            i += len(r'\left') if src.startswith(r'\left', i) else len(r'\right')
            continue
        if src.startswith(r'\sqrt', i):
            body, i = take_group(src, i + len(r'\sqrt'))
            # «x\sqrt{p}» — умножение: без явного знака sympy склеит «xsqrt»
            # в одну переменную и развалит её на буквы.
            glue = '*' if out and (out[-1].isalnum() or out[-1] == ')') else ''
            out += f'{glue}sqrt({latex_to_plain(body)})'
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


# Имена, которые нельзя рвать на отдельные переменные.
KNOWN_NAMES = ('real_root', 'Rational', 'sqrt', 'sin', 'cos', 'tan', 'pi', 'logb_ten')
LOG_BASES: list = []   # основания \log_{b} по порядку: «logbN» → log(…, b)


def parse(src: str):
    # Между соседними буквами ставим знак умножения: «yn» иначе уедет в
    # функцию Бесселя, а «ab» станет одной переменной. Имена функций от этого
    # правила защищены — «sqrt» распался бы на произведение четырёх букв.
    def split_word(m):
        word = m.group(0)
        if word in KNOWN_NAMES or re.fullmatch(r'logb\d+', word):
            return word
        return '*'.join(word)

    guarded = re.sub(r'[A-Za-z_]+\d*', lambda m: split_word(m) if not re.fullmatch(r'logb\d+', m.group(0)) else m.group(0), src)
    local = {f'logb{k}': (lambda b: (lambda arg: sympy.log(arg, b)))(parse_expr(base, transformations=TRANSFORMS))
             for k, base in enumerate(LOG_BASES)}
    local['logb_ten'] = lambda arg: sympy.log(arg, 10)

    return parse_expr(guarded, local_dict=local, transformations=TRANSFORMS)


def split_substitution(expression: str) -> tuple[str, dict]:
    """«$√(ab)$ при $a=2$, $b=18$» → формула и значения букв."""
    if ' при ' not in expression:
        return expression, {}

    head, tail = expression.split(' при ', 1)
    # Десятичную запятую убираем до разбиения списка: «$a=0{,}5$, $b=2$»
    # иначе рвётся ровно посередине числа.
    tail = tail.replace('{,}', '.')
    values = {}
    for part in tail.split(','):
        chunk = part.replace('$', '').strip()
        if '=' not in chunk:
            continue
        name, raw = chunk.split('=', 1)
        values[name.strip()] = parse(latex_to_plain(raw.strip()))

    return head, values


def parse_answer(answer: str):
    """Ответ банка: «6√2», «37+20√3», «3(√7+√3)», «5√2/2», «1,5»."""
    s = str(answer).replace(',', '.')
    s = re.sub(r'√\((.*?)\)', r'sqrt(\1)', s)
    s = re.sub(r'√(\d+)', r'sqrt(\1)', s)
    s = re.sub(r'(\d|\))(?=(sqrt|\())', r'\1*', s)

    return parse(s)


def check_equation(task) -> bool:
    """«Решите уравнение: $…=…$» — каждый корень обращает уравнение в верное
    равенство. «Наименьший положительный корень … в градусах» — подставляем
    градусы в радианах."""
    formula = re.search(r'\$(.*?)\$', task['expression']).group(1)
    lhs, rhs = formula.split('=', 1)
    diff = parse(latex_to_plain(lhs)) - parse(latex_to_plain(rhs))
    x = sympy.Symbol('x')
    degrees = 'в градусах' in task['expression']
    for root in str(task['answer']).split(';'):
        value = parse_answer(root)
        point = value * sympy.pi / 180 if degrees else value
        if sympy.simplify(diff.subs(x, point)) != 0:
            return False
    return True


def check_table(task) -> bool:
    """«Заполните таблицу значений функции $y=…$» — y считаем по функции."""
    func = re.search(r'функции \$y=(.*?)\$', task['expression']).group(1)
    xs = re.search(r'\\hline x&(.*?)\\\\', task['expression']).group(1).split('&')
    f = parse(latex_to_plain(func))
    x = sympy.Symbol('x')
    ys = [parse_answer(c.strip()) for c in str(task['answer']).split('|')]
    return len(xs) == len(ys) and all(sympy.simplify(f.subs(x, int(v)) - y) == 0 for v, y in zip(xs, ys))


def check_file(path: Path) -> tuple[int, list[str]]:
    data = json.loads(path.read_text(encoding='utf-8'))
    errors, total = [], 0
    for zadanie in data.get('zadaniya', []):
        for task in zadanie.get('tasks', []):
            total += 1
            LOG_BASES.clear()
            special = None
            if 'Заполните таблицу' in task['expression']:
                special = check_table
            elif re.search(r'\$[^$]*=[^$]*\$', task['expression']) and ' при ' not in task['expression']:
                special = check_equation
            if special:
                try:
                    ok = special(task)
                except Exception as e:  # noqa: BLE001
                    errors.append(f'{path.name} №{task["id"]}: не разобрано ({e}) — {task["expression"]}')
                    continue
                if not ok:
                    errors.append(f'{path.name} №{task["id"]}: {task["expression"]} ≠ {task["answer"]}')
                continue
            head, values = split_substitution(task['expression'])
            # «Раскройте скобки: $(x+3)^{2}$» — действие словами перед формулой.
            if not values and ':' in head.split('$', 1)[0]:
                head = '$' + head.split('$', 1)[1]
            expr = latex_to_plain(head)
            try:
                left = parse(expr)
                if values:
                    left = left.subs({sympy.Symbol(k): v for k, v in values.items()})
                diff = sympy.simplify(sympy.cancel(left - parse_answer(task['answer'])))
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
