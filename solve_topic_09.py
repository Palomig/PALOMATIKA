#!/usr/bin/env python3
"""
Solve all equations in topic_09.json and add "answer" fields.
"""

import json
import re
from sympy import symbols, solve, Rational, sqrt, Eq, sympify
from sympy.parsing.sympy_parser import parse_expr, standard_transformations, implicit_multiplication_application, convert_xor
from fractions import Fraction

x = symbols('x')

def latex_to_sympy(expr_str: str) -> str:
    """Convert LaTeX math expression to sympy-parseable string."""
    s = expr_str.strip()

    # Remove \displaystyle
    s = s.replace('\\displaystyle', '')

    # Handle \frac{a}{b} -> (a)/(b)
    while '\\frac' in s:
        # Find \frac{...}{...}
        idx = s.index('\\frac')
        # Find first {
        i = idx + len('\\frac')
        while i < len(s) and s[i] == ' ':
            i += 1
        if i < len(s) and s[i] == '{':
            # Find matching }
            depth = 1
            j = i + 1
            while j < len(s) and depth > 0:
                if s[j] == '{': depth += 1
                elif s[j] == '}': depth -= 1
                j += 1
            numerator = s[i+1:j-1]
            # Find second {
            k = j
            while k < len(s) and s[k] == ' ':
                k += 1
            if k < len(s) and s[k] == '{':
                depth = 1
                m = k + 1
                while m < len(s) and depth > 0:
                    if s[m] == '{': depth += 1
                    elif s[m] == '}': depth -= 1
                    m += 1
                denominator = s[k+1:m-1]
                s = s[:idx] + '((' + numerator + ')/(' + denominator + '))' + s[m:]
            else:
                break
        else:
            break

    # Handle ^2, ^{...}
    s = re.sub(r'\^{([^}]+)}', r'**(\1)', s)
    s = re.sub(r'\^(\d+)', r'**\1', s)

    # Remove \left, \right
    s = s.replace('\\left', '').replace('\\right', '')

    # Remove \cdot -> *
    s = s.replace('\\cdot', '*')

    # Remove remaining backslashes for common functions
    s = s.replace('\\', '')

    return s


def parse_equation(expr_str: str):
    """Parse a LaTeX equation string into a sympy equation (lhs - rhs = 0)."""
    converted = latex_to_sympy(expr_str)

    # Split on '='
    parts = converted.split('=')
    if len(parts) != 2:
        raise ValueError(f"Expected exactly one '=' in: {expr_str} -> {converted}")

    lhs_str = parts[0].strip()
    rhs_str = parts[1].strip()

    transformations = standard_transformations + (implicit_multiplication_application, convert_xor)

    lhs = parse_expr(lhs_str, local_dict={'x': x}, transformations=transformations)
    rhs = parse_expr(rhs_str, local_dict={'x': x}, transformations=transformations)

    return lhs - rhs


def format_answer(val) -> str:
    """Format a sympy value as a clean answer string."""
    r = Rational(val)

    # Check if integer
    if r.q == 1:
        return str(int(r.p))

    # Check if terminating decimal
    # A fraction p/q in lowest terms has terminating decimal iff q has only factors 2 and 5
    q = abs(r.q)
    temp = q
    while temp % 2 == 0:
        temp //= 2
    while temp % 5 == 0:
        temp //= 5

    if temp == 1:
        # Terminating decimal
        f = float(r)
        # Format without trailing zeros
        s = f"{f:.10f}".rstrip('0').rstrip('.')
        return s
    else:
        # Non-terminating: use fraction form
        return f"{r.p}/{r.q}"


def determine_answer_mode(instruction: str) -> str:
    """Determine what to return: 'min', 'max', or 'single'."""
    inst_lower = instruction.lower()
    if 'меньший из корней' in inst_lower:
        return 'min'
    elif 'больший из корней' in inst_lower:
        return 'max'
    else:
        return 'single'


def solve_task(expression: str, mode: str) -> str:
    """Solve an equation and return the answer string."""
    equation = parse_equation(expression)
    roots = solve(equation, x)

    if not roots:
        raise ValueError(f"No roots found for: {expression}")

    # Filter out complex roots
    real_roots = [r for r in roots if r.is_real]

    if not real_roots:
        raise ValueError(f"No real roots for: {expression}")

    if mode == 'single':
        if len(real_roots) == 1:
            return format_answer(real_roots[0])
        else:
            # For "find the root" with multiple roots, this shouldn't happen for linear
            # but if it does, return the single root or raise
            raise ValueError(f"Multiple roots {real_roots} but mode is 'single' for: {expression}")
    elif mode == 'min':
        return format_answer(min(real_roots))
    elif mode == 'max':
        return format_answer(max(real_roots))

    raise ValueError(f"Unknown mode: {mode}")


def main():
    filepath = '/home/dev/palomatika/storage/app/tasks/topic_09.json'

    with open(filepath, 'r', encoding='utf-8') as f:
        data = json.load(f)

    total = 0
    solved = 0
    errors = []

    for block in data['blocks']:
        for zadanie in block['zadaniya']:
            mode = determine_answer_mode(zadanie['instruction'])
            for task in zadanie['tasks']:
                total += 1
                expr = task['expression']
                try:
                    answer = solve_task(expr, mode)
                    task['answer'] = answer
                    solved += 1
                    print(f"  Block {block['number']}, Zadanie {zadanie['number']}, Task {task['id']}: {expr} -> {answer}")
                except Exception as e:
                    errors.append((block['number'], zadanie['number'], task['id'], expr, str(e)))
                    print(f"  ERROR Block {block['number']}, Zadanie {zadanie['number']}, Task {task['id']}: {expr} -> {e}")

    print(f"\nSolved: {solved}/{total}")
    if errors:
        print(f"Errors: {len(errors)}")
        for err in errors:
            print(f"  Block {err[0]}, Z{err[1]}, T{err[2]}: {err[3]} -> {err[4]}")

    with open(filepath, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=4)

    print(f"\nFile saved: {filepath}")


if __name__ == '__main__':
    main()
