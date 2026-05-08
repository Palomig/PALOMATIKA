#!/usr/bin/env python3
"""Auto-fix incorrect 'answer' fields in topic_13.json based on the audit.

Reuses parsing/solving logic from audit-choice-tasks.py and rewrites the
file in place (preserving the exact structure / ordering / formatting).

Idempotent: only changes `answer` fields where they differ from the computed
correct option; everything else is untouched.
"""
import json
import sys
from pathlib import Path

# Reuse logic from the audit module.
sys.path.insert(0, str(Path(__file__).parent))
from importlib import import_module
audit = import_module("audit-choice-tasks")

PATH = Path(sys.argv[1]) if len(sys.argv) > 1 else Path("/home/dev/palomatika/storage/app/tasks/topic_13.json")


def main():
    raw = PATH.read_text()
    data = json.loads(raw)
    fixes = []

    for block in data.get("blocks", []):
        for zad in block.get("zadaniya", []):
            if zad.get("type") != "choice":
                continue
            instr = zad.get("instruction", "")
            if "Укажите неравенство" in instr:
                continue
            for task in zad.get("tasks", []):
                expr = task.get("expression")
                options = task.get("options")
                recorded = task.get("answer")
                if not (expr and options and recorded is not None):
                    continue
                try:
                    rel = audit.parse_inequality(expr)
                    sol = audit.solve_ineq(rel)
                    correct, err = audit.find_option_for_solution(options, sol)
                except Exception as e:
                    print(f"  skip block#{block.get('number')} zad#{zad.get('number')} t#{task.get('id')}: {e}")
                    continue
                if correct is None:
                    print(f"  skip block#{block.get('number')} zad#{zad.get('number')} t#{task.get('id')}: {err}")
                    continue
                if correct != recorded:
                    fixes.append({
                        "block": block.get("number"),
                        "zad": zad.get("number"),
                        "task_id": task.get("id"),
                        "expr": expr,
                        "old": recorded,
                        "new": correct,
                    })
                    task["answer"] = correct

    if not fixes:
        print("Nothing to fix.")
        return 0

    print(f"Applying {len(fixes)} fixes:")
    for f in fixes:
        print(f"  block#{f['block']} zad#{f['zad']} task#{f['task_id']}  {f['old']} → {f['new']}  ({f['expr']})")

    # Write back. Use ensure_ascii=False to preserve cyrillic / math chars.
    PATH.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n")
    print(f"\nWrote {PATH}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
