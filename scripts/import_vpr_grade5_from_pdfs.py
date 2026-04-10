#!/usr/bin/env python3

import base64
import io
import json
import re
import subprocess
import tempfile
from pathlib import Path

from PIL import Image


ROOT = Path("/home/dev/palomatika")
PDF_DIR = Path("/tmp/vpr5_extract")
OUT_DIR = ROOT / "storage/app/tasks/vpr/grade_5"

PAGE_CROPS = {
    "topic_04_chart": (220, 290, 1060, 900),
    "topic_05_shape": (430, 250, 820, 610),
    "topic_06_numberline": (270, 760, 1010, 945),
    "topic_14_table": (180, 285, 1105, 460),
}


def run(*args: str) -> str:
    return subprocess.check_output(args, text=True)


def normalize_spaces(text: str) -> str:
    text = text.replace("\x0c", "\n")
    text = text.replace("−", "-").replace("–", "-")
    text = re.sub(r"[ \t]+", " ", text)
    text = re.sub(r"\n{3,}", "\n\n", text)
    return text.strip()


def normalize_answer_value(value: str) -> str:
    value = normalize_spaces(value).rstrip(".")
    if re.fullmatch(r"[\d,\s]+", value):
        return value.replace(" ", "")
    return value


def page_text(pdf: Path, page: int) -> str:
    text = run("pdftotext", "-layout", "-f", str(page), "-l", str(page), str(pdf), "-")
    lines = []
    for line in text.splitlines():
        if "ВПР. Математика. 5 класс." in line or line.strip() == "КОД":
            continue
        if re.fullmatch(r"\s*\d+\s*", line):
            continue
        lines.append(line.rstrip())
    return "\n".join(lines).strip()


def extract_single(text: str, pattern: str) -> str:
    match = re.search(pattern, text, flags=re.S)
    if not match:
        raise RuntimeError(f"Pattern not found: {pattern}")
    return normalize_spaces(match.group(1))


def extract_task14_text(text: str) -> str:
    lines = [line.rstrip() for line in text.splitlines()]
    collected = []
    started = False
    for line in lines:
        if not started:
            if re.match(r"\s*14\s+", line):
                collected.append(re.sub(r"^\s*14\s+", "", line).strip())
                started = True
            continue
        if re.match(r"\s*Решение\.\s*$", line):
            break
        if re.search(r"\S\s{3,}\S\s{3,}\S", line):
            break
        if line.strip() == "":
            continue
        collected.append(line.strip())
    return normalize_spaces("\n".join(collected))


def render_page(pdf: Path, page: int) -> Image.Image:
    with tempfile.TemporaryDirectory() as tmp:
        prefix = Path(tmp) / "page"
        subprocess.check_call(
            ["pdftoppm", "-png", "-f", str(page), "-l", str(page), str(pdf), str(prefix)],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
        )
        png_path = Path(f"{prefix}-{page:02d}.png")
        return Image.open(png_path).convert("RGB")


def crop_to_svg(page_image: Image.Image, crop_name: str) -> str:
    cropped = page_image.crop(PAGE_CROPS[crop_name])
    buf = io.BytesIO()
    cropped.save(buf, format="PNG", optimize=True)
    encoded = base64.b64encode(buf.getvalue()).decode("ascii")
    width, height = cropped.size
    return (
        f'<svg viewBox="0 0 {width} {height}" class="w-full max-w-[420px] h-auto mx-auto" '
        f'xmlns="http://www.w3.org/2000/svg">'
        f'<image href="data:image/png;base64,{encoded}" width="{width}" height="{height}" '
        f'preserveAspectRatio="xMidYMid meet"/></svg>'
    )


def parse_part1_answers(page13_text: str, task1_text: str) -> dict[str, str]:
    answers: dict[str, str] = {}

    table_match = re.search(
        r"Номер задания\s+Правильный ответ(?P<body>.*?)(?:\n\s*\d+\s*\n1\s+Ответ:|\n\s*1\)\s*)",
        page13_text,
        flags=re.S,
    )
    if not table_match:
        raise RuntimeError("Part 1 answer table not found")

    for line in table_match.group("body").splitlines():
        match = re.match(r"\s*(2|3|5|6|7|8|9|10|11)\s+(.+?)\s*$", line)
        if match:
            answers[f"{int(match.group(1)):02d}"] = normalize_answer_value(match.group(2))

    task1_fraction_match = re.search(r"\n\s*(\d+)\s*\n1\s+Ответ:.*?\n\s*(\d+)\s*(?:\n|$)", page13_text, flags=re.S)
    if task1_fraction_match:
        answers["01"] = f"{task1_fraction_match.group(1)}/{task1_fraction_match.group(2)}"
    else:
        task1_simple_match = re.search(r"(?:^|\n)\s*1\s+Ответ:\s*([0-9,]+)", page13_text, flags=re.S)
        if not task1_simple_match:
            raise RuntimeError("Task 1 answer not found")
        answers["01"] = task1_simple_match.group(1)

    tail = page13_text[-500:]
    task4_match = re.search(
        r"1\)\s*(.+?)\s*;\s*(?:\n\s*4\s*)?\n\s*2\)\s*(.+?)(?:\.|\n)",
        tail,
        flags=re.S,
    )
    if not task4_match:
        task4_match = re.search(
            r"1\)\s*(.+?)(?:\n\s*4\s*)?\n\s*2\)\s*(.+?)(?:\.|\n)",
            tail,
            flags=re.S,
        )
    if not task4_match:
        raise RuntimeError("Task 4 answers not found")
    answers["04_1"] = normalize_answer_value(task4_match.group(1))
    answers["04_2"] = normalize_answer_value(task4_match.group(2))

    return answers


def parse_part2_answers(part2_text: str) -> dict[str, str]:
    answers: dict[str, str] = {}
    for task_num in range(12, 18):
        match = re.search(
            rf"\n{task_num}\s+Решение и указания к оцениванию.*?Ответ:\s*(.+?)(?:\n\n|\n\s*Обоснованно|\n\s*Возможна|\n\s*Получены)",
            part2_text,
            flags=re.S,
        )
        if not match:
            raise RuntimeError(f"Task {task_num} answer not found")
        answer = normalize_spaces(match.group(1)).rstrip(".")
        if task_num == 17:
            nums = re.findall(r"\d+", answer)
            answer = ", ".join(nums)
        else:
            digits = re.findall(r"[\d,]+", answer.replace(" ", ""))
            answer = digits[0] if digits else answer
        answers[f"{task_num:02d}"] = answer
    return answers


def build_topic_texts(pdf: Path, variant: int) -> dict[str, list[dict]]:
    p2 = page_text(pdf, 2)
    p3 = page_text(pdf, 3)
    p4 = page_text(pdf, 4)
    p5 = page_text(pdf, 5)
    p7 = page_text(pdf, 7)
    p8 = page_text(pdf, 8)
    p9 = page_text(pdf, 9)
    p10 = page_text(pdf, 10)
    p11 = page_text(pdf, 11)
    p12 = page_text(pdf, 12)

    texts: dict[str, list[dict]] = {
        "01": [{"text": extract_single(p2, r"(?:^|\n)\s*1\s+(.*?)\n\s*Ответ:"), "image": None}],
        "02": [{"text": extract_single(p2, r"(?:^|\n)\s*2\s+(.*?)\n\s*Ответ:"), "image": None}],
        "03": [{"text": extract_single(p2, r"(?:^|\n)\s*3\s+(.*?)\n\s*Ответ:"), "image": None}],
        "04": [],
        "05": [{"text": extract_single(p4, r"(?:^|\n)\s*5\s+(.*?)\n\s*Ответ:"), "image": None}],
        "06": [{"text": extract_single(p4, r"(?:^|\n)\s*6\s+(.*?)\n\s*Ответ:"), "image": None}],
        "07": [{"text": extract_single(p4, r"(?:^|\n)\s*7\s+(.*?)\n\s*Ответ:"), "image": None}],
        "08": [{"text": extract_single(p4, r"(?:^|\n)\s*8\s+(.*?)\n\s*Ответ:"), "image": None}],
        "09": [{"text": extract_single(p5, r"(?:^|\n)\s*9\s+(.*?)\n\s*Ответ:"), "image": None}],
        "10": [{"text": extract_single(p5, r"(?:^|\n)\s*10\s+(.*?)\n\s*Ответ:"), "image": None}],
        "11": [{"text": extract_single(p5, r"(?:^|\n)\s*11\s+(.*?)\n\s*Ответ:"), "image": None}],
        "12": [{"text": extract_single(p7, r"(?:^|\n)\s*12\s+(.*?)\n\s*Решение\."), "image": None}],
        "13": [{"text": extract_single(p8, r"(?:^|\n)\s*13\s+(.*?)\n\s*Решение\."), "image": None}],
        "14": [{"text": extract_task14_text(p9), "image": None}],
        "15": [{"text": extract_single(p10, r"(?:^|\n)\s*15\s+(.*?)\n\s*Решение\."), "image": None}],
        "16": [{"text": extract_single(p11, r"(?:^|\n)\s*16\s+(.*?)\n\s*Решение\."), "image": None}],
        "17": [{"text": extract_single(p12, r"(?:^|\n)\s*17\s+(.*?)\n\s*Решение\."), "image": None}],
    }

    base4 = extract_single(p3, r"(?:^|\n)\s*4\s+(.*?)\n\s*1\)")
    q41 = extract_single(p3, r"(?:^|\n)\s*1\)\s+(.*?)\n\s*Ответ:")
    q42 = extract_single(p3, r"(?:^|\n)\s*2\)\s+(.*?)\n\s*Ответ:")
    texts["04"].append({"text": f"{base4}\n1) {q41}", "image": None})
    texts["04"].append({"text": f"{base4}\n2) {q42}", "image": None})

    for topic_id, items in texts.items():
        for item in items:
            item["text"] = f"Вариант {variant}.\n{item['text']}"

    return texts


def build_dataset() -> dict[str, list[dict]]:
    dataset = {f"{i:02d}": [] for i in range(1, 18)}

    pdfs = sorted(
        PDF_DIR.glob("*_trenirovochny_variant_VPR_2025_po_matematike_5_klass.pdf"),
        key=lambda p: int(re.match(r"(\d+)_", p.name).group(1)),
    )

    next_ids = {key: 1 for key in dataset}

    for pdf in pdfs:
        variant = int(re.match(r"(\d+)_", pdf.name).group(1))
        page13_text = run("pdftotext", "-layout", "-f", "13", "-l", "13", str(pdf), "-")
        part2_text = run("pdftotext", "-layout", "-f", "14", "-l", "16", str(pdf), "-")
        texts = build_topic_texts(pdf, variant)
        answers = parse_part1_answers(page13_text, texts["01"][0]["text"]) | parse_part2_answers(part2_text)

        page3 = render_page(pdf, 3)
        page4 = render_page(pdf, 4)
        page9 = render_page(pdf, 9)

        chart_svg = crop_to_svg(page3, "topic_04_chart")
        shape_svg = crop_to_svg(page4, "topic_05_shape")
        line_svg = crop_to_svg(page4, "topic_06_numberline")
        table_svg = crop_to_svg(page9, "topic_14_table")

        for topic_id, items in texts.items():
            for idx, item in enumerate(items, start=1):
                answer_key = topic_id
                if topic_id == "04":
                    answer_key = f"04_{idx}"

                task = {
                    "id": next_ids[topic_id],
                    "text": item["text"],
                    "answer": answers[answer_key],
                    "status": "production",
                    "variant": variant,
                    "source_pdf": pdf.name,
                }

                if topic_id == "04":
                    task["image"] = chart_svg
                elif topic_id == "05":
                    task["image"] = shape_svg
                elif topic_id == "06":
                    task["image"] = line_svg
                elif topic_id == "14":
                    task["image"] = table_svg

                dataset[topic_id].append(task)
                next_ids[topic_id] += 1

    return dataset


def topic_json(topic_id: str, tasks: list[dict]) -> dict:
    return {
        "topic_id": topic_id,
        "exam_type": "vpr",
        "grade": 5,
        "blocks": [
            {
                "number": 1,
                "title": "Тренировочные варианты ВПР 2025",
                "zadaniya": [
                    {
                        "number": 1,
                        "instruction": "Задания из 10 тренировочных вариантов",
                        "type": "word_problem",
                        "tasks": tasks,
                    }
                ],
            }
        ],
    }


def main() -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    dataset = build_dataset()
    for topic_id, tasks in dataset.items():
        out_path = OUT_DIR / f"topic_{topic_id}.json"
        out_path.write_text(
            json.dumps(topic_json(topic_id, tasks), ensure_ascii=False, indent=2) + "\n",
            encoding="utf-8",
        )
    print(f"Generated {len(dataset)} topic files in {OUT_DIR}")


if __name__ == "__main__":
    main()
