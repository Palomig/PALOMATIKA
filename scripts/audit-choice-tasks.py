#!/usr/bin/env python3
"""Audit choice-type inequality tasks in topic_13.json.

Parses each task's expression and options, solves the inequality with sympy,
matches the solution against options (by interval semantics), then compares
against the recorded `answer` field.
"""
import json
import re
import sys
from pathlib import Path

import sympy as sp
from sympy import Symbol, Rational, S, Interval, Union, EmptySet, oo, solve_univariate_inequality
from sympy.parsing.sympy_parser import parse_expr, standard_transformations, implicit_multiplication_application

PATH = Path(sys.argv[1]) if len(sys.argv) > 1 else Path("/home/dev/palomatika/storage/app/tasks/topic_13.json")

x = Symbol("x", real=True)
TRANSFORMATIONS = standard_transformations + (implicit_multiplication_application,)


def latex_to_sympy(expr_str: str) -> str:
    """Convert TeX-ish expressions to a sympy-parsable string."""
    s = expr_str
    s = s.replace("\\leq", "<=").replace("\\geq", ">=")
    s = s.replace("\\le", "<=").replace("\\ge", ">=")
    s = s.replace("^", "**")
    # decimal commas → dots
    s = re.sub(r"(\d),(\d)", r"\1.\2", s)
    return s


def parse_single_relation(s: str):
    for op in ("<=", ">=", "<", ">"):
        if op in s:
            lhs, rhs = s.split(op, 1)
            lhs_e = parse_expr(lhs.strip(), transformations=TRANSFORMATIONS, local_dict={"x": x})
            rhs_e = parse_expr(rhs.strip(), transformations=TRANSFORMATIONS, local_dict={"x": x})
            relations = {"<=": sp.Le, ">=": sp.Ge, "<": sp.Lt, ">": sp.Gt}
            return relations[op](lhs_e, rhs_e)
    raise ValueError(f"No comparison operator found in {s!r}")


def parse_inequality(expr_str: str):
    """Returns either a single Relational or a list of Relational (system)."""
    if "\\begin{cases}" in expr_str:
        # Extract body between cases tags
        body = expr_str
        body = body.replace("\\begin{cases}", "").replace("\\end{cases}", "")
        # Lines separated by \\
        # Replace \\ (LaTeX newline) with a sentinel
        body = body.replace("\\\\", "\n")
        # Remove TeX leftovers
        rels = []
        for line in body.split("\n"):
            line = latex_to_sympy(line).strip()
            if not line:
                continue
            rels.append(parse_single_relation(line))
        return rels
    s = latex_to_sympy(expr_str)
    return parse_single_relation(s)


def solve_ineq(rel_or_list):
    if isinstance(rel_or_list, list):
        sol = S.Reals
        for r in rel_or_list:
            sol = sol.intersect(solve_univariate_inequality(r, x, relational=False))
        return sol
    return solve_univariate_inequality(rel_or_list, x, relational=False)


def parse_option_label(label: str):
    """Parse option label (interval text) into a sympy set.

    Supports:
      - 'нет решений' → EmptySet
      - '(-∞; +∞)'    → Reals
      - '[a; b]', '(a; b)', '[a; b)', '(a; b]'
      - '(-∞; a]', '(-∞; a)'
      - '[a; +∞)', '(a; +∞)'
      - 'A ∪ B'
    """
    label = label.strip()
    if "нет решений" in label.lower():
        return EmptySet
    if label == "(-∞; +∞)":
        return S.Reals
    parts = [p.strip() for p in label.split("∪")]
    sets = []
    for part in parts:
        sets.append(parse_single_interval(part))
    if len(sets) == 1:
        return sets[0]
    return Union(*sets)


def parse_number(token: str):
    token = token.strip()
    if token in ("-∞", "−∞"):
        return -oo
    if token in ("+∞", "∞", "+∞"):
        return oo
    # commas as decimal separator
    token = token.replace(",", ".")
    return Rational(token)


def parse_single_interval(part: str):
    part = part.strip()
    m = re.match(r"^([\[\(])\s*([^;]+)\s*;\s*([^\)\]]+?)\s*([\)\]])$", part)
    if not m:
        raise ValueError(f"Cannot parse interval: {part!r}")
    left_b, left_v, right_v, right_b = m.groups()
    a = parse_number(left_v)
    b = parse_number(right_v)
    left_open = left_b == "("
    right_open = right_b == ")"
    return Interval(a, b, left_open=left_open, right_open=right_open)


def sets_equal(a, b) -> bool:
    try:
        diff1 = a - b
        diff2 = b - a
        return diff1 == EmptySet and diff2 == EmptySet
    except Exception:
        return a == b


def find_option_for_solution(options, solution):
    for opt in options:
        try:
            opt_set = parse_option_label(opt["label"])
        except Exception as e:
            return None, f"parse-error:{opt['id']}:{e}"
        if sets_equal(opt_set, solution):
            return opt["id"], None
    return None, "no-matching-option"


def strip_dollars(s: str) -> str:
    return s.strip().strip("$").strip()


def evaluate_option_with_point(opt_label: str, var_name: str, value):
    """Substitute value for var_name into the inequality in opt_label, return bool."""
    expr_str = strip_dollars(opt_label)
    # parse single relation
    rel = parse_single_relation(latex_to_sympy(expr_str.replace(var_name, "x")))
    return bool(rel.subs(x, Rational(str(value))))


def audit_point_task(task, options, recorded, var_label, point_value):
    """For tasks where each option is an inequality and a point value is given.
    Returns (correct_id, error)."""
    truthy = []
    for opt in options:
        try:
            if evaluate_option_with_point(opt["label"], var_label, point_value):
                truthy.append(opt["id"])
        except Exception as e:
            return None, f"option-eval-error:{opt['id']}:{e}"
    if len(truthy) == 1:
        return truthy[0], None
    return None, f"truthy-count={len(truthy)} ({truthy})"


def audit_block(block, block_path):
    issues = []
    instr = block.get("instruction", "")
    is_point_task = "координатной прямой отмечено" in instr or block.get("svg_type") == "single_point"
    for task in block.get("tasks", []):
        options = task.get("options")
        recorded = task.get("answer")
        if not (options and recorded is not None):
            continue
        # Branch: classic "expression → solve → match interval" form
        expr = task.get("expression")
        if expr:
            try:
                rel = parse_inequality(expr)
                solution = solve_ineq(rel)
                correct_id, err = find_option_for_solution(options, solution)
            except Exception as e:
                issues.append({"path": block_path, "task_id": task.get("id"),
                               "expression": expr, "error": f"solve-error: {e}",
                               "recorded": recorded})
                continue
            if correct_id is None:
                issues.append({"path": block_path, "task_id": task.get("id"),
                               "expression": expr, "error": err,
                               "solution": str(solution), "recorded": recorded})
                continue
            if correct_id != recorded:
                issues.append({"path": block_path, "task_id": task.get("id"),
                               "expression": expr, "solution": str(solution),
                               "expected": correct_id, "recorded": recorded})
            continue
        # Branch: point on number line — substitute and evaluate each option
        point_value = task.get("point_value")
        var_label = task.get("point_label", "a")
        if point_value is not None and is_point_task:
            correct_id, err = audit_point_task(task, options, recorded, var_label, point_value)
            if err:
                issues.append({"path": block_path, "task_id": task.get("id"),
                               "expression": f"point {var_label}={point_value}",
                               "error": err, "recorded": recorded})
                continue
            if correct_id != recorded:
                issues.append({"path": block_path, "task_id": task.get("id"),
                               "expression": f"point {var_label}={point_value}",
                               "expected": correct_id, "recorded": recorded})
    return issues


def main():
    data = json.loads(PATH.read_text())
    all_issues = []
    for block in data.get("blocks", []):
        for zad in block.get("zadaniya", []):
            if zad.get("type") != "choice":
                continue
            instr = zad.get("instruction", "")
            # Skip "укажите неравенство по картинке" type — needs reverse logic
            if "Укажите неравенство" in instr:
                continue
            path = f"block#{block.get('number')} zad#{zad.get('number')}"
            all_issues.extend(audit_block(zad, path))

    if not all_issues:
        print("No issues found.")
        return 0

    print(f"Found {len(all_issues)} issues:\n")
    for it in all_issues:
        print(f"  [{it['path']}] task id={it['task_id']}  expr: {it['expression']}")
        if "error" in it:
            print(f"    ⚠ {it['error']}  recorded={it['recorded']}")
        else:
            print(f"    sol={it['solution']}  expected={it['expected']}  recorded={it['recorded']}")
        print()
    return len(all_issues)


if __name__ == "__main__":
    sys.exit(main())
