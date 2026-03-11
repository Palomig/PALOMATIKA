#!/usr/bin/env python3
"""
Compute answers for all tasks in topic_08.json (square roots and exponents).
Uses sympy for symbolic math and LaTeX parsing.
"""

import json
import re
import sys
from fractions import Fraction
from sympy import (
    sqrt, Rational, symbols, simplify, Abs, sympify, Integer,
    parse_expr, S
)
from sympy.parsing.latex import parse_latex


def parse_mixed_fraction(s):
    """Parse mixed fraction like '3\\frac{3}{7}' -> Rational(24, 7)"""
    # Match: integer\frac{num}{den}
    m = re.match(r'^(-?\d+)\\frac\{(\d+)\}\{(\d+)\}$', s.strip())
    if m:
        whole = int(m.group(1))
        num = int(m.group(2))
        den = int(m.group(3))
        if whole < 0:
            return Rational(whole * den - num, den)
        return Rational(whole * den + num, den)
    # Match: \frac{num}{den}
    m = re.match(r'^\\frac\{(\d+)\}\{(\d+)\}$', s.strip())
    if m:
        return Rational(int(m.group(1)), int(m.group(2)))
    # Match: -\frac{num}{den}
    m = re.match(r'^-\\frac\{(\d+)\}\{(\d+)\}$', s.strip())
    if m:
        return Rational(-int(m.group(1)), int(m.group(2)))
    # Plain integer
    m = re.match(r'^(-?\d+)$', s.strip())
    if m:
        return Rational(int(m.group(1)))
    raise ValueError(f"Cannot parse value: {s}")


def extract_vars(latex_str):
    """Split expression and variable assignments from LaTeX string.
    Returns (expr_latex, var_dict) where var_dict maps symbol names to values.
    """
    # Split on \text{ при }
    if '\\text{ при }' in latex_str:
        parts = latex_str.split('\\text{ при }')
        expr_part = parts[0].strip()
        vars_part = parts[1].strip()

        var_dict = {}
        # Parse variable assignments: "a = 3\frac{3}{7}, b = \frac{1}{7}"
        # or "x = 3, y = 4"
        assignments = re.split(r',\s*', vars_part)
        for assign in assignments:
            assign = assign.strip()
            m = re.match(r'([a-zA-Z]+)\s*=\s*(.+)$', assign)
            if m:
                var_name = m.group(1)
                val_str = m.group(2).strip()
                var_dict[var_name] = parse_mixed_fraction(val_str)
        return expr_part, var_dict
    return latex_str, {}


def latex_to_sympy(latex_str):
    """Convert LaTeX expression to a sympy expression, handling special cases."""

    # Replace \cdot with *
    s = latex_str.replace('\\cdot', '*')

    # Replace colon division (a : b) with /
    s = re.sub(r'\s*:\s*', ' / ', s)

    # Handle {,} as decimal point (Russian notation): {,} -> .
    s = s.replace('{,}', '.')

    # Handle coefficient before \sqrt: e.g. 5\sqrt -> 5*\sqrt
    s = re.sub(r'(\d)\\sqrt', r'\1*\\sqrt', s)

    # Handle coefficient before ( : e.g. 5( -> 5*(
    s = re.sub(r'(\d)\(', r'\1*(', s)

    # Handle )\sqrt -> )*\sqrt
    s = re.sub(r'\)\\sqrt', r')*\\sqrt', s)

    # Handle )( -> )*(
    s = re.sub(r'\)\(', r')*(', s)

    # Handle (-a) pattern - parse_latex sometimes struggles
    # We'll handle (-a)^n by converting to ((-1)*a)^n
    # Actually let's try parse_latex first and fall back

    try:
        expr = parse_latex(s)
        return expr
    except Exception as e:
        raise ValueError(f"Failed to parse LaTeX: {s}\nError: {e}")


def compute_answer(expression_latex):
    """Compute the numeric answer for a given LaTeX expression."""
    expr_str, var_dict = extract_vars(expression_latex)

    expr = latex_to_sympy(expr_str)

    if var_dict:
        # Create sympy symbols and substitute
        sub_dict = {}
        for var_name, val in var_dict.items():
            sym = symbols(var_name)
            sub_dict[sym] = val
        result = expr.subs(sub_dict)
    else:
        result = expr

    # Simplify and evaluate
    result = simplify(result)

    # If result has sqrt or other irrational parts, try to evaluate numerically
    # But for these problems, results should always be rational/integer
    if result.is_number:
        # Try to get exact rational
        r = result.evalf()
        # Check if it's essentially an integer
        if abs(r - round(float(r))) < 1e-9:
            return str(int(round(float(r))))
        else:
            # Return as decimal
            f = float(r)
            # Check if it's a simple fraction
            if f == int(f):
                return str(int(f))
            else:
                # Format nicely
                formatted = f"{f:.10f}".rstrip('0').rstrip('.')
                return formatted
    else:
        raise ValueError(f"Result is not numeric: {result}")


def format_answer(val):
    """Format answer as string: integer if whole, otherwise decimal."""
    f = float(val)
    if f == int(f):
        return str(int(f))
    formatted = f"{f:.10f}".rstrip('0').rstrip('.')
    return formatted


def main():
    json_path = '/home/dev/palomatika/storage/app/tasks/topic_08.json'

    with open(json_path, 'r', encoding='utf-8') as f:
        data = json.load(f)

    total = 0
    errors = 0
    computed = 0

    for block in data['blocks']:
        for zadanie in block['zadaniya']:
            for task in zadanie['tasks']:
                total += 1
                expr = task['expression']
                try:
                    answer = compute_answer(expr)
                    task['answer'] = answer
                    computed += 1
                    print(f"  [OK] Block {block['number']}, Z{zadanie['number']}, Task {task['id']}: {answer}")
                except Exception as e:
                    errors += 1
                    print(f"  [ERR] Block {block['number']}, Z{zadanie['number']}, Task {task['id']}: {e}")
                    print(f"         Expression: {expr}")

    print(f"\nTotal: {total}, Computed: {computed}, Errors: {errors}")

    if errors > 0:
        print("\nThere were errors! Not saving file.")
        sys.exit(1)

    # Save back
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=4)

    print(f"\nSaved {json_path} with {computed} answers.")


if __name__ == '__main__':
    main()
