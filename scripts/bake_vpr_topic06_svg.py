#!/usr/bin/env python3

import json
from pathlib import Path


ROOT = Path("/home/dev/palomatika")
TOPIC_PATH = ROOT / "storage/app/tasks/vpr/grade_5/topic_06.json"

COLORS = {
    "bg": "#0a1628",
    "line": "#c8dce8",
    "tick": "#7eb8da",
    "point": "#d4a855",
    "label": "#c8dce8",
    "zero": "#d4a855",
}


AXIS_MIN = 0
AXIS_MAX = 12


TOPIC_STRUCTURE = [
    {
        "number": 1,
        "instruction": "Найдите координату точки",
        "type": "word_problem",
        "tasks": [
            {"id": 1, "points": [{"value": 6, "label": "A"}], "text": "Найдите координату точки A.", "answer": "6"},
            {"id": 2, "points": [{"value": 4, "label": "A"}], "text": "Найдите координату точки A.", "answer": "4"},
            {"id": 3, "points": [{"value": 7, "label": "A"}], "text": "Найдите координату точки A.", "answer": "7"},
        ],
    },
    {
        "number": 2,
        "instruction": "Найдите, на сколько число B больше числа A",
        "type": "word_problem",
        "tasks": [
            {"id": 1, "points": [{"value": 3, "label": "A"}, {"value": 10, "label": "B"}], "text": "Найдите, на сколько число B больше числа A.", "answer": "7"},
            {"id": 2, "points": [{"value": 6, "label": "A"}, {"value": 11, "label": "B"}], "text": "Найдите, на сколько число B больше числа A.", "answer": "5"},
            {"id": 3, "points": [{"value": 3, "label": "A"}, {"value": 11, "label": "B"}], "text": "Найдите, на сколько число B больше числа A.", "answer": "8"},
            {"id": 4, "points": [{"value": 3, "label": "A"}, {"value": 10, "label": "B"}], "text": "Найдите, на сколько число B больше числа A.", "answer": "7"},
            {"id": 5, "points": [{"value": 6, "label": "A"}, {"value": 11, "label": "B"}], "text": "Найдите, на сколько число B больше числа A.", "answer": "5"},
            {"id": 6, "points": [{"value": 4, "label": "A"}, {"value": 10, "label": "B"}], "text": "Найдите, на сколько число B больше числа A.", "answer": "6"},
        ],
    },
    {
        "number": 3,
        "instruction": "Найдите, на сколько число A меньше числа B",
        "type": "word_problem",
        "tasks": [
            {"id": 1, "points": [{"value": 4, "label": "A"}, {"value": 10, "label": "B"}], "text": "Найдите, на сколько число A меньше числа B.", "answer": "6"},
        ],
    },
]


def render_number_line(points: list[dict]) -> str:
    width = 320
    height = 70
    line_y = 35
    margin_left = 20
    margin_right = 30
    line_width = width - margin_left - margin_right
    marker_id = "arrow-vpr-topic06"

    def get_x(value: float) -> float:
        return margin_left + ((value - AXIS_MIN) / (AXIS_MAX - AXIS_MIN)) * line_width

    parts = [
        f'<svg viewBox="0 0 {width} {height}" class="w-full max-w-[320px] h-auto mx-auto" xmlns="http://www.w3.org/2000/svg">',
        f'<rect width="{width}" height="{height}" fill="{COLORS["bg"]}"/>',
        "<defs>",
        f'<marker id="{marker_id}" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto">',
        f'<path d="M0,0 L0,6 L9,3 z" fill="{COLORS["line"]}"/>',
        "</marker>",
        "</defs>",
        f'<line x1="{margin_left}" y1="{line_y}" x2="{width - 15}" y2="{line_y}" stroke="{COLORS["line"]}" stroke-width="2" marker-end="url(#{marker_id})"/>',
    ]

    for tick in range(AXIS_MIN, AXIS_MAX + 1):
        x = get_x(tick)
        parts.append(
            f'<line x1="{x:.2f}" y1="{line_y - 7}" x2="{x:.2f}" y2="{line_y + 7}" stroke="{COLORS["tick"]}" stroke-width="1.5"/>'
        )
        if tick in (0, 1):
            color = COLORS["zero"] if tick == 0 else COLORS["label"]
            parts.append(
                f'<text x="{x:.2f}" y="57" text-anchor="middle" fill="{color}" font-size="11" font-weight="500">{tick}</text>'
            )

    for point in points:
        x = get_x(point["value"])
        parts.append(f'<circle cx="{x:.2f}" cy="{line_y}" r="5" fill="{COLORS["point"]}"/>')
        parts.append(
            f'<text x="{x:.2f}" y="23" text-anchor="middle" fill="{COLORS["label"]}" font-size="14" font-weight="600" font-style="italic">{point["label"]}</text>'
        )

    parts.append("</svg>")
    return "".join(parts)


def build_topic() -> dict:
    zadaniya = []

    for zadanie in TOPIC_STRUCTURE:
        tasks = []
        for task in zadanie["tasks"]:
            tasks.append(
                {
                    "id": task["id"],
                    "text": task["text"],
                    "answer": task["answer"],
                    "image": render_number_line(task["points"]),
                    "status": "production",
                }
            )

        zadaniya.append(
            {
                "number": zadanie["number"],
                "instruction": zadanie["instruction"],
                "type": zadanie["type"],
                "tasks": tasks,
            }
        )

    return {
        "exam_type": "vpr",
        "grade": 5,
        "topic_id": "06",
        "blocks": [
            {
                "number": 1,
                "title": "Тренажер",
                "zadaniya": zadaniya,
            }
        ],
    }


def main() -> None:
    TOPIC_PATH.write_text(json.dumps(build_topic(), ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


if __name__ == "__main__":
    main()
