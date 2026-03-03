# Topic 11 Matching Tasks — QA Report

**Date:** 2026-03-03
**Scope:** Answer storage, per-task answer resolution, and OGE variant scoring for `matching`/`matching_signs`/`matching_4` types.

---

## Problem Statement

On the `/test/11` topic page, `matching.blade.php` renders each graph as a separate task and calls `TaskAnswerResolver::resolveFromTaskAndZadanie()` per task. The resolver hits the matching branch (line 47-49) and returns `$task['options'][0]` — the **formula text** (e.g. `y = x + 3`). This is the correct formula for that one graph, but it is **not** the answer a student would enter. A student enters a digit (the position number of that formula in the shuffled formula list). Since no shuffled list is available to the resolver, every per-task answer badge shows a raw LaTeX formula string — never a positional digit.

---

## Two Distinct Pipelines

| Context | View | Answer Source | Answer Format | Correct? |
|---|---|---|---|---|
| Topic page `/test/11` | `matching.blade.php` | `resolveFromTaskAndZadanie()` per task | Formula text (`y = x + 3`) | Wrong display — shows formula, not digit |
| OGE variant `/oge/{hash}` | `matching-variant.blade.php` | `buildMatchingCorrectAnswer()` on whole set | Concatenated digits (`231`) | Correct |

### Path A — Topic page (per-task, broken)

1. `matching.blade.php:96` calls `$answerResolver->resolveFromTaskAndZadanie($zadanie, $task)`.
2. `TaskAnswerResolver:47-49`: type is `matching` → returns `$task['options'][0]` (formula string).
3. `task-answer.blade.php` renders that string as the "answer" badge under each graph.
4. The formulas list displayed by the view (`$displayFormulas`) is **not shuffled** (line 38: `$displayFormulas = $groupFormulas`), so `options[0]` always maps to position 1 in the display. The answer badge would always imply "1" if interpreted as a position.

### Path B — OGE variant (set-level, correct)

1. `OgeVariantBuilderService:73-80` calls `getRandomMatchingSet()` which picks 3 tasks and builds a **shuffled** `formulas` array.
2. `buildMatchingCorrectAnswer()` looks up each `task['options'][0]` in the shuffled `formulas` array and returns concatenated positions (e.g. `"231"`).
3. `resolveFromVariantTask()` sees `correct_answer` already set → returns it directly. Scoring works.

---

## Root Causes

### 1. No `answer` field in matching tasks

Matching tasks in `topic_11.json` have no `answer` key. Each task stores only:
```json
{ "id": 1, "image": "...", "options": ["y = x + 3", "y = 3", "y = 3x"], "svg": "..." }
```

The convention is `options[0]` = correct formula. But this is a formula string, not a positional digit.

### 2. `resolveFromTaskAndZadanie` cannot compute positional answers

The resolver receives a single `$task` and the parent `$zadanie`. It has no knowledge of:
- Which other tasks are in the same group of 3.
- The formula display order (which would require a shuffle seed or explicit ordering).

So it falls back to returning the raw formula string.

### 3. Topic page never shuffles formulas

`matching.blade.php:38`: `$displayFormulas = $groupFormulas;` — the comment says "перемешиваем" (shuffle) but the code assigns directly without shuffling. Because formulas are extracted in task order and task 0's correct formula is always position 1, every graph's answer effectively maps to `1` if you interpret it as a position.

---

## Recommendations

### R1: Add explicit `answer` field to each matching task in JSON

Store a stable positional answer per task, computed against a canonical formula list for that zadanie.

**Proposed JSON schema:**
```json
{
  "number": 1,
  "instruction": "...",
  "type": "matching",
  "formulas": ["y = 3", "y = x + 3", "y = 3x"],
  "tasks": [
    { "id": 1, "image": "...", "options": ["y = x + 3", ...], "answer": "2", "svg": "..." },
    { "id": 2, "image": "...", "options": ["y = -2x - 1", ...], "answer": "...", "svg": "..." },
    ...
  ]
}
```

- `formulas`: canonical display order for the entire zadanie (shared across all tasks in the group).
- `answer`: 1-based position of `options[0]` in `formulas`.
- `resolveFromTaskAndZadanie` will then find `$task['answer']` at line 30-31 and return the digit, not the formula.

### R2: Stop relying on `options[0]` as the answer derivation for matching types

Once `answer` fields exist, the matching branch in `TaskAnswerResolver` (lines 47-49) becomes dead code for topic-page rendering. It should remain as a fallback for backward compatibility but should be documented as deprecated.

### R3: Shuffle formulas on the topic page

`matching.blade.php:38` should actually shuffle `$displayFormulas`. Without shuffling, the positional mapping is trivially `123...` for any group, making the exercise meaningless. Use a seeded shuffle (e.g. task-id-based) so the order is deterministic for answer-checking.

### R4: Propagate zadanie-level `formulas` to the variant builder

`getRandomMatchingSet()` already builds a shuffled `formulas` array and `buildMatchingCorrectAnswer()` computes the correct answer from it. This is correct. But if R1 is adopted, the variant builder should use the zadanie's canonical `formulas` field as the base before shuffling, ensuring consistency.

### R5: Matching group answer should be a concatenated digit string

For OGE scoring, the answer for task 11 is always a single string like `"231"` (one digit per graph). The per-task answer badges on the topic page should display:
- Per graph: the digit position (e.g. `2`).
- Per group: the concatenated answer (e.g. `231`).

This matches the real OGE exam format.

---

## Impact Assessment

| Issue | Severity | Scope |
|---|---|---|
| Per-task answer badges show formula text instead of digit | Medium | `/test/11` topic page only |
| Formulas not shuffled on topic page | Medium | `/test/11` — exercises are trivial |
| OGE variant scoring | None (already correct) | `/oge/{hash}` |
| Data migration needed for `answer` + `formulas` fields | Low | `topic_11.json` only |

---

## Migration Checklist

- [ ] Add `formulas` array at zadanie level in `topic_11.json` for each matching zadanie
- [ ] Compute and add `answer` (digit) to each task in matching zadaniya
- [ ] Update `matching.blade.php` to shuffle `$displayFormulas` with a deterministic seed
- [ ] Verify `resolveFromTaskAndZadanie` returns digit from `$task['answer']` (no code change needed — existing line 30-31 handles it)
- [ ] Test on `/test/11` that answer badges show digits
- [ ] Test OGE variant scoring still passes (regression)
