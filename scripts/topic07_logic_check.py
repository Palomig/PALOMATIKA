#!/usr/bin/env python3
"""Consistency checks for Topic 07 inequality tasks."""

from __future__ import annotations

import argparse
import ast
import json
import operator
import re
from pathlib import Path
from typing import Any


DEFAULT_TOPIC_PATH = Path("storage/app/tasks/topic_07.json")
INEQ_PATTERN = re.compile(r"(<=|>=|<|>|≤|≥|\\le|\\ge)")
SAFE_BIN_OPS = {
    ast.Add: operator.add,
    ast.Sub: operator.sub,
    ast.Mult: operator.mul,
    ast.Div: operator.truediv,
    ast.Pow: operator.pow,
}
SAFE_UNARY_OPS = {ast.UAdd: operator.pos, ast.USub: operator.neg}
SAFE_CMP_OPS = {
    ast.Lt: operator.lt,
    ast.Gt: operator.gt,
    ast.LtE: operator.le,
    ast.GtE: operator.ge,
    ast.Eq: operator.eq,
    ast.NotEq: operator.ne,
}


def load_topic(path: Path) -> dict[str, Any]:
    with path.open("r", encoding="utf-8") as fh:
        return json.load(fh)


def normalize_math(option_text: str) -> str:
    expr = option_text.strip().replace("$", "")
    expr = expr.replace("\\|", "|")
    expr = expr.replace("\\cdot", "*").replace("\\times", "*")
    expr = expr.replace("≤", "<=").replace("≥", ">=")
    expr = expr.replace("\\le", "<=").replace("\\ge", ">=")
    expr = expr.replace("^", "**")
    expr = expr.replace("−", "-").replace("–", "-")

    # Convert \frac{a}{b} to (a)/(b), repeatedly for nested fractions.
    frac_pattern = re.compile(r"\\frac\s*\{([^{}]+)\}\s*\{([^{}]+)\}")
    while True:
        match = frac_pattern.search(expr)
        if not match:
            break
        replacement = f"(({match.group(1)})/({match.group(2)}))"
        expr = expr[: match.start()] + replacement + expr[match.end() :]

    # Convert |x| style absolute value to abs(x)
    while "|" in expr:
        start = expr.find("|")
        end = expr.find("|", start + 1)
        if end == -1:
            break
        inner = expr[start + 1 : end]
        expr = expr[:start] + f"abs({inner})" + expr[end + 1 :]

    expr = re.sub(r"\s+", " ", expr).strip()
    # Insert implicit multiplication: 2x, 2(x+1), (x+1)y, x^2 y, xy.
    expr = re.sub(r"(?<=[0-9a-zA-Z_)])\s+(?=[a-zA-Z_(])", "*", expr)
    expr = re.sub(r"(?<=[0-9)])(?=[a-zA-Z(])", "*", expr)
    expr = re.sub(r"\b([a-zA-Z])([a-zA-Z])\b", r"\1*\2", expr)
    return expr


def safe_eval_expression(expr: str, variables: dict[str, float]) -> bool:
    node = ast.parse(expr, mode="eval").body

    def _eval(n: ast.AST) -> float | bool:
        if isinstance(n, ast.Constant) and isinstance(n.value, (int, float, bool)):
            return n.value
        if isinstance(n, ast.Name):
            if n.id not in variables:
                raise ValueError(f"Unknown variable: {n.id}")
            return variables[n.id]
        if isinstance(n, ast.BinOp) and type(n.op) in SAFE_BIN_OPS:
            return SAFE_BIN_OPS[type(n.op)](_eval(n.left), _eval(n.right))
        if isinstance(n, ast.UnaryOp) and type(n.op) in SAFE_UNARY_OPS:
            return SAFE_UNARY_OPS[type(n.op)](_eval(n.operand))
        if isinstance(n, ast.Call) and isinstance(n.func, ast.Name) and n.func.id == "abs" and len(n.args) == 1:
            return abs(_eval(n.args[0]))
        if isinstance(n, ast.Compare):
            left = _eval(n.left)
            for op_node, comparator in zip(n.ops, n.comparators):
                if type(op_node) not in SAFE_CMP_OPS:
                    raise ValueError(f"Unsupported comparator: {type(op_node).__name__}")
                right = _eval(comparator)
                if not SAFE_CMP_OPS[type(op_node)](left, right):
                    return False
                left = right
            return True
        raise ValueError(f"Unsupported syntax: {type(n).__name__}")

    result = _eval(node)
    if not isinstance(result, bool):
        raise ValueError("Expression did not evaluate to boolean")
    return result


def build_value_map(task: dict[str, Any]) -> dict[str, float]:
    values: dict[str, float] = {}

    if "point_value" in task and "point_label" in task:
        label = str(task["point_label"]).strip()
        if label:
            values[label] = float(task["point_value"])

    for point in task.get("points", []):
        label = str(point.get("label", "")).strip()
        if not label or "value" not in point:
            continue
        values[label] = float(point["value"])

    return values


def parse_answer_index(raw_answer: Any) -> int | None:
    if isinstance(raw_answer, int):
        return raw_answer
    raw_text = str(raw_answer).strip()
    if raw_text.isdigit():
        return int(raw_text)
    return None


def collect_inequality_mismatches(topic: dict[str, Any]) -> list[dict[str, Any]]:
    mismatches: list[dict[str, Any]] = []

    for block in topic.get("blocks", []):
        block_num = int(block.get("number", 0))
        for zadanie in block.get("zadaniya", []):
            zadanie_num = int(zadanie.get("number", 0))
            for task in zadanie.get("tasks", []):
                options = task.get("options", [])
                if not isinstance(options, list) or not options:
                    continue
                if not any(isinstance(opt, str) and INEQ_PATTERN.search(opt) for opt in options):
                    continue

                task_id = task.get("id")
                answer_raw = task.get("answer")
                answer_index = parse_answer_index(answer_raw)
                value_map = build_value_map(task)

                if not value_map:
                    # This audit is scoped to inequality tasks that have explicit value sources.
                    continue

                if answer_index is None:
                    mismatches.append(
                        {
                            "severity": "high",
                            "code": "invalid_answer_index",
                            "block": block_num,
                            "zadanie": zadanie_num,
                            "task_id": task_id,
                            "details": f"Answer is not a 1-based numeric index: {answer_raw!r}",
                        }
                    )
                    continue

                if answer_index < 1 or answer_index > len(options):
                    mismatches.append(
                        {
                            "severity": "high",
                            "code": "answer_out_of_range",
                            "block": block_num,
                            "zadanie": zadanie_num,
                            "task_id": task_id,
                            "details": f"Answer {answer_index} outside options range 1..{len(options)}",
                        }
                    )
                    continue

                true_indices: list[int] = []
                parse_errors: list[str] = []

                for idx, option in enumerate(options, start=1):
                    if not isinstance(option, str) or not INEQ_PATTERN.search(option):
                        continue
                    parts = re.split(r"\s+(?:и|and)\s+", option)
                    part_results: list[bool] = []
                    for part in parts:
                        normalized = normalize_math(part)
                        try:
                            part_results.append(safe_eval_expression(normalized, value_map))
                        except Exception as exc:  # noqa: BLE001 - explicit audit output
                            parse_errors.append(f"opt#{idx}: {part!r} -> {exc}")
                            part_results = []
                            break
                    if part_results and all(part_results):
                        true_indices.append(idx)

                if parse_errors:
                    mismatches.append(
                        {
                            "severity": "medium",
                            "code": "parse_error",
                            "block": block_num,
                            "zadanie": zadanie_num,
                            "task_id": task_id,
                            "details": "; ".join(parse_errors),
                        }
                    )
                    continue

                if len(true_indices) != 1:
                    mismatches.append(
                        {
                            "severity": "high" if len(true_indices) == 0 else "medium",
                            "code": "non_unique_true_options",
                            "block": block_num,
                            "zadanie": zadanie_num,
                            "task_id": task_id,
                            "details": f"Expected exactly one true option, got {true_indices or 'none'}",
                        }
                    )

                if answer_index not in true_indices:
                    mismatches.append(
                        {
                            "severity": "high",
                            "code": "answer_does_not_match_truth",
                            "block": block_num,
                            "zadanie": zadanie_num,
                            "task_id": task_id,
                            "details": f"Answer={answer_index}, true_options={true_indices or 'none'}",
                        }
                    )

    mismatches.sort(key=lambda m: (m["block"], m["zadanie"], m["task_id"], m["severity"], m["code"]))
    return mismatches


def as_markdown(mismatches: list[dict[str, Any]]) -> str:
    lines = [
        "| severity | code | block | zadanie | task_id | details |",
        "|---|---|---:|---:|---:|---|",
    ]
    for item in mismatches:
        details = str(item["details"]).replace("|", "\\|")
        lines.append(
            f"| {item['severity']} | {item['code']} | {item['block']} | {item['zadanie']} | {item['task_id']} | {details} |"
        )
    return "\n".join(lines) + "\n"


def main() -> int:
    parser = argparse.ArgumentParser(description="Topic 07 inequality consistency checker")
    parser.add_argument("--topic-path", type=Path, default=DEFAULT_TOPIC_PATH)
    parser.add_argument("--format", choices=["json", "md"], default="json")
    parser.add_argument("--output", type=Path, default=None)
    args = parser.parse_args()

    topic = load_topic(args.topic_path)
    mismatches = collect_inequality_mismatches(topic)

    severity_counts = {"high": 0, "medium": 0, "low": 0}
    for item in mismatches:
        severity_counts[item["severity"]] = severity_counts.get(item["severity"], 0) + 1

    payload = {
        "topic_id": topic.get("topic_id"),
        "summary": {
            "mismatch_count": len(mismatches),
            "severity_counts": severity_counts,
        },
        "mismatches": mismatches,
    }

    if args.format == "json":
        content = json.dumps(payload, ensure_ascii=False, indent=2) + "\n"
    else:
        content = as_markdown(mismatches)

    if args.output:
        args.output.parent.mkdir(parents=True, exist_ok=True)
        args.output.write_text(content, encoding="utf-8")
    else:
        print(content, end="")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
