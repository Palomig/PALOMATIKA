# Topic 07 Render Runbook

## Purpose
Ensure Topic 07 (`Числа, координатная прямая`) uses pre-baked `task.svg` as the single rendering source when it exists.

## Required commands
1. Bake SVG into topic JSON:
   ```bash
   php artisan svg:bake 7
   ```
2. Clear application caches after bake/deploy:
   ```bash
   php artisan optimize:clear
   ```

## Non-negotiable data requirement
- On server, baked SVG must stay persisted in:
  - `storage/app/tasks/topic_07.json`
- `task["svg"]` in this file is the canonical render source for Topic 07 when present.
- Do not rely on runtime/manual fallback rendering if baked SVG is already available.

## Validation checklist
- Open a Topic 07 render path that uses `tasks.types.choice`.
- Confirm each task with `task["svg"]` renders exactly one SVG container.
- Confirm no duplicate fallback number-line SVG appears for the same task.
