#!/usr/bin/env python3

import json
from pathlib import Path


ROOT = Path("/home/dev/palomatika")
TOPIC_PATH = ROOT / "storage/app/tasks/vpr/grade_5/topic_05.json"

COLORS = {
    "bg": "#0a1628",
    "grid": "#274764",
    "shape_fill": "#5a9fcf",
    "shape_edge": "#d4a855",
    "text": "#d5e5f0",
    "accent": "#7dd3fc",
}


SHAPES = {
    "notch_28": {
        "grid": (9, 8),
        "polygon": [(1, 1), (7, 1), (5, 3), (5, 5), (7, 7), (1, 7)],
    },
    "notch_27": {
        "grid": (9, 8),
        "polygon": [(1, 1), (7, 1), (4, 4), (7, 7), (1, 7)],
    },
    "notch_24": {
        "grid": (9, 8),
        "polygon": [(1, 1), (7, 1), (4, 3), (4, 5), (7, 7), (1, 7)],
    },
    "notch_8": {
        "grid": (6, 6),
        "polygon": [(1, 1), (4, 1), (2, 3), (4, 5), (1, 5)],
    },
    "bevel_31": {
        "grid": (11, 7),
        "polygon": [(1, 2), (2, 1), (8, 1), (9, 2), (9, 5), (1, 5)],
    },
    "house_48": {
        "grid": (11, 10),
        "polygon": [(1, 5), (5, 1), (9, 5), (9, 9), (1, 9)],
    },
    "bevel_35": {
        "grid": (9, 8),
        "polygon": [(1, 2), (2, 1), (6, 1), (7, 2), (7, 7), (1, 7)],
    },
    "notch_20": {
        "grid": (9, 6),
        "polygon": [(1, 1), (7, 1), (5, 3), (7, 5), (1, 5)],
    },
    "bevel_27": {
        "grid": (7, 10),
        "polygon": [(1, 2), (2, 1), (4, 1), (5, 2), (5, 8), (1, 8)],
    },
}


QUESTION = "Найдите площадь фигуры. Ответ дайте в квадратных сантиметрах."

TOPIC_STRUCTURE = [
    {
        "number": 1,
        "instruction": "Найдите площадь прямоугольника с вырезом",
        "type": "word_problem",
        "tasks": [
            {"id": 1, "shape": "notch_28", "answer": "28"},
            {"id": 2, "shape": "notch_27", "answer": "27"},
            {"id": 3, "shape": "notch_24", "answer": "24"},
            {"id": 4, "shape": "notch_27", "answer": "27"},
        ],
    },
    {
        "number": 2,
        "instruction": "Найдите площадь фигуры со срезанными углами",
        "type": "word_problem",
        "tasks": [
            {"id": 1, "shape": "bevel_31", "answer": "31"},
            {"id": 2, "shape": "bevel_35", "answer": "35"},
            {"id": 3, "shape": "bevel_27", "answer": "27"},
        ],
    },
    {
        "number": 3,
        "instruction": "Разбейте фигуру на прямоугольники и треугольники",
        "type": "word_problem",
        "tasks": [
            {"id": 1, "shape": "notch_8", "answer": "8"},
            {"id": 2, "shape": "house_48", "answer": "48"},
            {"id": 3, "shape": "notch_20", "answer": "20"},
        ],
    },
]


def make_shape_svg(shape: dict) -> str:
    cols, rows = shape["grid"]
    polygon = shape["polygon"]

    cell = 34
    left = 30
    top = 26
    right = 92
    bottom = 30

    width = left + cols * cell + right
    height = top + rows * cell + bottom

    def point(cell_point: tuple[int, int]) -> tuple[int, int]:
        x, y = cell_point
        return left + x * cell, top + y * cell

    def grid_x(value: int) -> int:
        return left + value * cell

    def grid_y(value: int) -> int:
        return top + value * cell

    polygon_points = " ".join(f"{x},{y}" for x, y in (point(p) for p in polygon))

    parts = [
        f'<svg viewBox="0 0 {width} {height}" class="w-full max-w-[520px] h-auto mx-auto" xmlns="http://www.w3.org/2000/svg">',
        f'<rect width="{width}" height="{height}" rx="20" fill="{COLORS["bg"]}"/>',
    ]

    for col in range(cols + 1):
        x = grid_x(col)
        parts.append(
            f'<line x1="{x}" y1="{grid_y(0)}" x2="{x}" y2="{grid_y(rows)}" stroke="{COLORS["grid"]}" stroke-width="1.2"/>'
        )

    for row in range(rows + 1):
        y = grid_y(row)
        parts.append(
            f'<line x1="{grid_x(0)}" y1="{y}" x2="{grid_x(cols)}" y2="{y}" stroke="{COLORS["grid"]}" stroke-width="1.2"/>'
        )

    parts.append(
        f'<polygon points="{polygon_points}" fill="{COLORS["shape_fill"]}" fill-opacity="0.22" '
        f'stroke="{COLORS["shape_edge"]}" stroke-width="4" stroke-linejoin="round"/>'
    )

    unit_x = width - 64
    unit_y = top + rows * cell / 2
    parts.append(
        f'<line x1="{unit_x - 18}" y1="{unit_y}" x2="{unit_x + 18}" y2="{unit_y}" stroke="{COLORS["text"]}" stroke-width="4"/>'
    )
    parts.append(
        f'<line x1="{unit_x - 18}" y1="{unit_y - 7}" x2="{unit_x - 18}" y2="{unit_y + 7}" stroke="{COLORS["text"]}" stroke-width="2"/>'
    )
    parts.append(
        f'<line x1="{unit_x + 18}" y1="{unit_y - 7}" x2="{unit_x + 18}" y2="{unit_y + 7}" stroke="{COLORS["text"]}" stroke-width="2"/>'
    )
    parts.append(
        f'<text x="{unit_x}" y="{unit_y - 12}" fill="{COLORS["text"]}" font-size="19" '
        f'font-family="Inter, Arial, sans-serif" text-anchor="middle">1 см</text>'
    )
    parts.append(
        f'<text x="{left}" y="18" fill="{COLORS["accent"]}" font-size="14" '
        f'font-family="Inter, Arial, sans-serif">Клетка 1 × 1 см</text>'
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
                    "text": QUESTION,
                    "answer": task["answer"],
                    "image": make_shape_svg(SHAPES[task["shape"]]),
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
        "topic_id": "05",
        "blocks": [
            {
                "number": 1,
                "title": "Тренажер",
                "zadaniya": zadaniya,
            }
        ],
    }


def main() -> None:
    topic = build_topic()
    TOPIC_PATH.write_text(json.dumps(topic, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


if __name__ == "__main__":
    main()
