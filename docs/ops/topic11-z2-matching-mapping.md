# Topic 11 B1/Z2 Matching Mapping

**Date:** 2026-03-03
**Scope:** `storage/app/tasks/topic_11.json`, Block 1, Zadanie 2 (`type=matching`).

## Applied Rules
- SVG for each task is generated from one equation inside that task's `options[]`.
- Explicit `task.answer` stores the 1-based index of the equation used for the graph.
- Mapping is intentionally non-identity across the task set (answers are not all `1`).

## Task Mapping

| Task ID | `answer` | Equation used for SVG (`options[answer-1]`) |
|---|---:|---|
| 1 | 2 | `y = -2x + 6` |
| 2 | 3 | `y = \frac{1}{3}x` |
| 3 | 2 | `y = 2x - 4` |
| 4 | 2 | `y = -3x` |
| 5 | 3 | `y = -\frac{1}{2}x - 2` |
| 6 | 1 | `y = -\frac{1}{2}x + 3` |

## Validation
- `php artisan test tests/Feature/Topic11Zadanie2MatchingIntegrityTest.php`
- Assertions covered:
  - each task has explicit answer index in `[1..3]`;
  - SVG slope/intercept match the chosen answer formula;
  - answer distribution is non-trivial;
  - `tasks.types.matching` view renders for B1/Z2.
