#!/usr/bin/env python3
"""Deterministic inventory for Topic 07 tasks."""

from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any


DEFAULT_TOPIC_PATH = Path("storage/app/tasks/topic_07.json")


def load_topic(path: Path) -> dict[str, Any]:
    with path.open("r", encoding="utf-8") as fh:
        return json.load(fh)


def format_points(points: list[dict[str, Any]]) -> str:
    parts = []
    for point in points:
        label = str(point.get("label", "")).strip() or "?"
        value = point.get("value")
        parts.append(f"{label}={value}")
    return ", ".join(parts)


def task_rows(topic: dict[str, Any]) -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []

    for block in topic.get("blocks", []):
        block_num = int(block.get("number", 0))
        for zadanie in block.get("zadaniya", []):
            zadanie_num = int(zadanie.get("number", 0))
            zadanie_tasks = zadanie.get("tasks") or []

            if zadanie_tasks:
                for task in zadanie_tasks:
                    task_id = task.get("id")
                    task_options = task.get("options", zadanie.get("options", []))
                    task_answer = task.get("answer", zadanie.get("answer"))
                    task_svg = task.get("svg")
                    zadanie_svg = zadanie.get("svg")
                    if "point_value" in task:
                        point_repr = task.get("point_value")
                    elif isinstance(task.get("points"), list) and task["points"]:
                        point_repr = format_points(task["points"])
                    elif isinstance(zadanie.get("points"), list) and zadanie["points"]:
                        point_repr = format_points(zadanie["points"])
                    elif "point" in task:
                        point_repr = task.get("point")
                    else:
                        point_repr = None

                    rows.append(
                        {
                            "block": block_num,
                            "zadanie": zadanie_num,
                            "task_id": task_id,
                            "type": zadanie.get("type"),
                            "current_point": point_repr,
                            "current_options": task_options if isinstance(task_options, list) else [],
                            "current_answer": task_answer,
                            "has_svg": bool(task_svg or zadanie_svg),
                            "svg_source": "task" if task_svg else ("zadanie" if zadanie_svg else "none"),
                        }
                    )
            else:
                zadanie_options = zadanie.get("options", [])
                zadanie_answer = zadanie.get("answer")
                if isinstance(zadanie.get("points"), list) and zadanie["points"]:
                    point_repr = format_points(zadanie["points"])
                else:
                    point_repr = None

                rows.append(
                    {
                        "block": block_num,
                        "zadanie": zadanie_num,
                        "task_id": "zadanie_level",
                        "type": zadanie.get("type"),
                        "current_point": point_repr,
                        "current_options": zadanie_options if isinstance(zadanie_options, list) else [],
                        "current_answer": zadanie_answer,
                        "has_svg": bool(zadanie.get("svg")),
                        "svg_source": "zadanie" if zadanie.get("svg") else "none",
                    }
                )

    rows.sort(key=lambda r: (r["block"], r["zadanie"], str(r["task_id"])))
    return rows


def to_markdown(rows: list[dict[str, Any]]) -> str:
    out = [
        "| block | zadanie | task_id | type | point | options_count | answer | has_svg | svg_source |",
        "|---:|---:|---|---|---|---:|---|---|---|",
    ]
    for row in rows:
        point = "" if row["current_point"] is None else str(row["current_point"])
        answer = "" if row["current_answer"] is None else str(row["current_answer"])
        out.append(
            "| {block} | {zadanie} | {task_id} | {type} | {point} | {options_count} | {answer} | {has_svg} | {svg_source} |".format(
                block=row["block"],
                zadanie=row["zadanie"],
                task_id=row["task_id"],
                type=row["type"],
                point=point.replace("|", "\\|"),
                options_count=len(row["current_options"]),
                answer=answer.replace("|", "\\|"),
                has_svg="yes" if row["has_svg"] else "no",
                svg_source=row["svg_source"],
            )
        )
    return "\n".join(out) + "\n"


def main() -> int:
    parser = argparse.ArgumentParser(description="Topic 07 inventory report")
    parser.add_argument("--topic-path", type=Path, default=DEFAULT_TOPIC_PATH)
    parser.add_argument("--format", choices=["json", "md"], default="json")
    parser.add_argument("--output", type=Path, default=None)
    args = parser.parse_args()

    topic = load_topic(args.topic_path)
    rows = task_rows(topic)
    payload = {"topic_id": topic.get("topic_id"), "rows": rows}

    if args.format == "json":
        content = json.dumps(payload, ensure_ascii=False, indent=2) + "\n"
    else:
        content = to_markdown(rows)

    if args.output:
        args.output.parent.mkdir(parents=True, exist_ok=True)
        args.output.write_text(content, encoding="utf-8")
    else:
        print(content, end="")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
