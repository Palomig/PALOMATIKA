#!/usr/bin/env python3

import json
from copy import deepcopy
from pathlib import Path


ROOT = Path("/home/dev/palomatika")
TOPIC_PATH = ROOT / "storage/app/tasks/vpr/grade_5/topic_04.json"

COLORS = {
    "bg": "#0a1628",
    "grid": "#1e4a6e",
    "axis": "#3a5a7c",
    "bar": "#5a9fcf",
    "bar_edge": "#c8dce8",
    "text": "#c8dce8",
    "label": "#d4a855",
}


CHARTS = {
    "mountains": {
        "kind": "bar",
        "title": "Высоты гор России",
        "y_max": 5700,
        "y_min": 4900,
        "y_tick": 100,
        "minor_tick": 20,
        "labels": [
            "Джангитау",
            "Дыхтау",
            "Казбек",
            "Катын-Тау",
            "Коштантау",
            "Мижирги",
            "Пик Пушкина",
            "Шхара",
            "Эльбрус",
        ],
        "values": [5085, 5205, 5033, 4979, 5152, 5025, 5100, 5068, 5642],
        "value_suffix": "м",
    },
    "hottabych_a": {
        "kind": "bar",
        "title": "Желания Хоттабыча по дням недели",
        "y_max": 6,
        "y_min": 0,
        "y_tick": 1,
        "minor_tick": 1,
        "labels": ["пн", "вт", "ср", "чт", "пт", "сб", "вс"],
        "values": [2, 3, 1, 5, 3, 4, 5],
    },
    "hottabych_b": {
        "kind": "bar",
        "title": "Желания Хоттабыча по дням недели",
        "y_max": 9,
        "y_min": 0,
        "y_tick": 1,
        "minor_tick": 1,
        "labels": ["пн", "вт", "ср", "чт", "пт", "сб", "вс"],
        "values": [2, 4, 8, 3, 3, 5, 8],
    },
    "football": {
        "kind": "bar",
        "title": "Чемпионы мира по футболу",
        "y_max": 6,
        "y_min": 0,
        "y_tick": 1,
        "minor_tick": 1,
        "labels": [
            "Англия",
            "Аргентина",
            "Бразилия",
            "Германия",
            "Испания",
            "Италия",
            "Уругвай",
            "Франция",
        ],
        "values": [1, 2, 5, 4, 1, 4, 2, 2],
    },
    "clear_days": {
        "kind": "bar",
        "title": "Ясные дни в Москве за 2018 год",
        "y_max": 18,
        "y_min": 0,
        "y_tick": 2,
        "minor_tick": 1,
        "labels": [
            "январь",
            "февраль",
            "март",
            "апрель",
            "май",
            "июнь",
            "июль",
            "август",
            "сентябрь",
            "октябрь",
            "ноябрь",
            "декабрь",
        ],
        "values": [4, 5, 10, 14, 17, 13, 14, 17, 14, 9, 4, 2],
    },
    "lakes": {
        "kind": "bar",
        "title": "Площади крупнейших озёр России",
        "y_max": 35000,
        "y_min": 0,
        "y_tick": 5000,
        "minor_tick": 2500,
        "labels": [
            "Чудско-Псковское",
            "Таймыр",
            "Ханка",
            "Байкал",
            "Онежское",
            "Убсу-Нур",
            "Ладожское",
        ],
        "values": [3550, 4560, 4070, 31500, 9700, 3350, 17700],
        "value_suffix": "км²",
    },
    "handball": {
        "kind": "bar",
        "title": "Чемпионы мира по гандболу",
        "y_max": 7,
        "y_min": 0,
        "y_tick": 1,
        "minor_tick": 1,
        "labels": [
            "Германия",
            "Дания",
            "Испания",
            "Россия",
            "Франция",
            "Хорватия",
            "Швеция",
        ],
        "values": [1, 1, 2, 2, 6, 1, 2],
    },
}


TASK_TO_CHART = {
    1: "mountains",
    2: "mountains",
    3: "hottabych_a",
    4: "hottabych_a",
    5: "hottabych_a",
    6: "hottabych_a",
    7: "football",
    8: "football",
    9: "clear_days",
    10: "clear_days",
    11: "hottabych_b",
    12: "hottabych_b",
    13: "lakes",
    14: "lakes",
    15: "football",
    16: "football",
    17: "handball",
    18: "handball",
    19: "mountains",
    20: "mountains",
}


def esc(text: str) -> str:
    return (
        text.replace("&", "&amp;")
        .replace("<", "&lt;")
        .replace(">", "&gt;")
        .replace('"', "&quot;")
    )


def nice_number(value: int) -> str:
    return f"{value:,}".replace(",", " ")


def make_bar_chart_svg(chart: dict) -> str:
    width = 360
    height = 250
    left = 54
    right = 16
    top = 18
    bottom = 74
    plot_w = width - left - right
    plot_h = height - top - bottom

    labels = chart["labels"]
    values = chart["values"]
    count = len(labels)
    step = plot_w / count
    bar_w = min(18, step * 0.46)
    x_offset = (step - bar_w) / 2

    y_min = chart["y_min"]
    y_max = chart["y_max"]
    y_tick = chart["y_tick"]
    minor_tick = chart["minor_tick"]

    def y_pos(value: float) -> float:
        ratio = (value - y_min) / (y_max - y_min)
        return top + plot_h - ratio * plot_h

    parts = [
        f'<svg viewBox="0 0 {width} {height}" class="w-full max-w-[350px] h-auto mx-auto" xmlns="http://www.w3.org/2000/svg">',
        f'<rect width="100%" height="100%" fill="{COLORS["bg"]}"/>',
    ]

    minor = y_min
    while minor <= y_max:
        y = y_pos(minor)
        parts.append(
            f'<line x1="{left}" y1="{y:.2f}" x2="{width - right}" y2="{y:.2f}" '
            f'stroke="{COLORS["grid"]}" stroke-width="0.6" opacity="0.55"/>'
        )
        minor += minor_tick

    major = y_min
    while major <= y_max:
        y = y_pos(major)
        parts.append(
            f'<line x1="{left}" y1="{y:.2f}" x2="{width - right}" y2="{y:.2f}" '
            f'stroke="{COLORS["axis"]}" stroke-width="0.9" opacity="0.95"/>'
        )
        parts.append(
            f'<text x="{left - 8}" y="{y + 4:.2f}" fill="{COLORS["text"]}" font-size="10" '
            f'font-family="Inter, Arial, sans-serif" text-anchor="end">{esc(nice_number(major))}</text>'
        )
        major += y_tick

    parts.append(
        f'<line x1="{left}" y1="{top}" x2="{left}" y2="{top + plot_h}" stroke="{COLORS["bar_edge"]}" stroke-width="1.2"/>'
    )
    parts.append(
        f'<line x1="{left}" y1="{top + plot_h}" x2="{width - right}" y2="{top + plot_h}" stroke="{COLORS["bar_edge"]}" stroke-width="1.2"/>'
    )

    for idx, (label, value) in enumerate(zip(labels, values)):
        x = left + idx * step + x_offset
        y = y_pos(value)
        h = top + plot_h - y
        parts.append(
            f'<rect x="{x:.2f}" y="{y:.2f}" width="{bar_w:.2f}" height="{h:.2f}" '
            f'rx="2" fill="{COLORS["bar"]}" fill-opacity="0.9" stroke="{COLORS["bar_edge"]}" stroke-width="0.9"/>'
        )
        parts.append(
            f'<text x="{x + bar_w / 2:.2f}" y="{top + plot_h + 27:.2f}" fill="{COLORS["text"]}" font-size="10.5" '
            f'font-family="Inter, Arial, sans-serif" text-anchor="end" transform="rotate(-42 {x + bar_w / 2:.2f} {top + plot_h + 27:.2f})">{esc(label)}</text>'
        )

    parts.append(
        f'<text x="{width / 2:.2f}" y="12" fill="{COLORS["label"]}" font-size="11" '
        f'font-family="Inter, Arial, sans-serif" text-anchor="middle">{esc(chart["title"])}</text>'
    )
    parts.append("</svg>")
    return "".join(parts)


def main() -> None:
    data = json.loads(TOPIC_PATH.read_text(encoding="utf-8"))
    tasks = data["blocks"][0]["zadaniya"][0]["tasks"]

    baked_svgs = {name: make_bar_chart_svg(cfg) for name, cfg in CHARTS.items()}

    for task in tasks:
        chart_name = TASK_TO_CHART.get(task["id"])
        if chart_name is None:
            raise RuntimeError(f"No chart mapping for task {task['id']}")
        task["image"] = baked_svgs[chart_name]

    TOPIC_PATH.write_text(
        json.dumps(data, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )

    print(f"Baked semantic SVG for {len(tasks)} tasks into {TOPIC_PATH}")


if __name__ == "__main__":
    main()
