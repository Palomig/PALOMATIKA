# Grade 7 Algebra Curriculum And Task Bank Design

## Goal

Build a full-year algebra 7 curriculum and task bank for tutors who teach 2 hours per week. The bank should not mirror every textbook exercise. It should train the core skills a student needs, with enough real-world and conceptual tasks to make algebraic transformations meaningful.

## Context

The current prototype is published as a static viewer:

- `/var/www/html/grade7-topics.html`
- `/var/www/html/grade7-topics.json`

The source data lives in the repository:

- `storage/app/tasks/grade_7/topic_01.json` through `topic_11.json`
- `storage/app/tasks/grade_7/_ref/_index.json`
- `storage/app/tasks/grade_7/_ref/_curated.json`
- `scripts/convert-to-topics.mjs`
- `scripts/build-topics-overview.mjs`

The current grade 7 bank has 11 algebra topics and 2959 tasks curated from Makarychev 2023. This is useful raw material, but it is still mostly a filtered textbook. The next layer should be methodological: topic sequence, micro-skills, conceptual tasks, generated practice, homework sets, answers, hints, and common mistakes.

Laravel already has a product skeleton for algebra task pages:

- `app/Services/AlgTaskDataService.php`
- `app/Http/Controllers/AlgTopicController.php`
- `resources/views/alg-topics/index.blade.php`
- `resources/views/alg-topics/show.blade.php`
- routes under `/alg-topics`

That skeleton expects data in `storage/app/tasks/alg/grade_7/topic_NN.json`, while the current prototype data is in `storage/app/tasks/grade_7/topic_NN.json`.

## Teaching Model

Use this structure for each topic:

```text
Skill -> meaning -> guided examples -> practice -> mixed review -> homework
```

The goal is to make every symbolic rule answer a student question:

- What does this expression describe?
- Why is this transformation valid?
- What stays the same before and after the transformation?
- What mistake would change the meaning?

For example, in "Раскрытие скобок", the rule is not "change signs after a minus". The meaning is:

```text
Parentheses are a group.
Opening parentheses means applying the outside operation to every element of the group.
```

## Full-Year Shape

Assume 30-34 teaching weeks, one 2-hour lesson per week, with homework of 15-20 tasks.

Recommended yearly rhythm:

```text
Weeks 1-2: diagnostics and arithmetic/algebra readiness
Weeks 3-5: expressions, parentheses, like terms
Weeks 6-9: linear equations and word problems
Weeks 10-12: functions, coordinates, linear function basics
Weeks 13-15: powers and monomials
Weeks 16-19: polynomials and factorization basics
Weeks 20-24: formulas of shortened multiplication
Weeks 25-27: applying formulas and mixed transformations
Weeks 28-31: systems of linear equations
Weeks 32-34: final mixed review and individual gaps
```

The exact number of weeks can shift by student level, but each topic should expose enough ready-made homework sets for slow, normal, and strong pacing.

## Topic Structure

Each topic should contain:

- `meta`: title, description, grade, subject, source, skills.
- `curriculum`: lesson goals, prerequisites, common misconceptions, real-world models.
- `micro_skills`: atomic skills that can be trained and assessed.
- `blocks`: task blocks rendered by the existing task UI.
- `homework_sets`: 15-20 task bundles ready for tutors.
- `checks`: short 5-task mini-checks for the end of a lesson.

Recommended task roles:

- `concept`: explain meaning, write a situation, compare two expressions.
- `guided`: first formal tasks after explanation.
- `drill`: symbolic fluency.
- `real_context`: word problems that model the skill.
- `mixed_review`: older skills mixed with the current one.
- `error_analysis`: find and explain a common mistake.
- `mini_check`: quick assessment.

Recommended difficulty labels:

- `intro`: one-step, meaning-first.
- `base`: required school level.
- `standard`: typical homework level.
- `stretch`: for stronger students.
- `challenge`: optional, not required for normal pacing.

## Task Schema Extension

Keep the existing task shape compatible with the OGE/VPR bank, but add optional methodological fields:

```json
{
  "id": 1,
  "expression": "1000 - (x - 100)",
  "answer": "1000 - x + 100",
  "status": "ready",
  "skill": "minus_before_parentheses",
  "type": "real_context",
  "difficulty": "base",
  "lesson_role": "homework",
  "hint": "The purchase costs x - 100 because of the discount. Subtract the whole group.",
  "common_mistake": "1000 - x - 100",
  "explanation": "Subtracting a discounted price gives back the discount in the remaining balance.",
  "src": {
    "kind": "generated",
    "based_on": "parentheses_discount_model"
  }
}
```

These fields should be optional so existing tasks continue to render.

## Example Topic: Раскрытие Скобок

### Main Idea

```text
Скобки - это группа.
Раскрыть скобки - значит выполнить действие с каждым элементом группы.
```

### Micro-Skills

1. Interpret parentheses as a group or set.
2. Open parentheses after a plus sign.
3. Open parentheses after a minus sign.
4. Multiply a group by a positive coefficient.
5. Multiply a group by a negative coefficient.
6. Open parentheses and combine like terms.
7. Check equivalence by substitution.
8. Find and correct sign mistakes.

### Lesson Plan, 2 Hours

```text
0-10 min: parentheses as a set, receipt, package, or area model.
10-25 min: plus before parentheses.
25-45 min: minus before parentheses.
45-65 min: coefficient before parentheses.
65-85 min: symbolic practice.
85-105 min: real-context tasks.
105-115 min: 5-task mini-check.
115-120 min: homework setup and error patterns.
```

### Task Mix For One Homework

Use 15-20 tasks:

- 3 concept tasks.
- 4 plus/minus parentheses tasks.
- 5 coefficient tasks.
- 4 simplify-after-opening tasks.
- 2 real-context tasks.
- 1 error-analysis task.

### Sample Homework

1. Придумай ситуацию к выражению `2(x + 300)`. Раскрой скобки и объясни, что означает каждый член.
2. Придумай ситуацию к выражению `1000 - (x + 150)`. Раскрой скобки и объясни знаки.
3. Придумай ситуацию к выражению `500 - (x - 80)`. Почему после раскрытия появляется `+80`?
4. Раскрой скобки: `8 + (x + 5)`.
5. Раскрой скобки: `12 + (a - 7)`.
6. Раскрой скобки: `20 - (x + 6)`.
7. Раскрой скобки: `15 - (y - 9)`.
8. Раскрой скобки: `3(x + 4)`.
9. Раскрой скобки: `5(a - 2)`.
10. Раскрой скобки: `-2(x + 7)`.
11. Раскрой скобки: `-4(y - 3)`.
12. Упрости: `2(x + 5) + 3x`.
13. Упрости: `4(a - 2) + 7`.
14. Упрости: `10 - 2(x + 3)`.
15. Упрости: `3(y - 4) + 2(y + 1)`.
16. Упрости: `5(a + 2) - 3(a - 1)`.
17. Один набор состоит из тетради за `x` рублей и ручки за 35 рублей. Купили 6 наборов. Запиши стоимость покупки двумя способами.
18. У прямоугольника одна сторона равна `x + 4`, другая равна 3. Запиши площадь и раскрой скобки.
19. У Пети было 1200 рублей. Он купил товар за `x` рублей со скидкой 100 рублей. Запиши, сколько денег осталось, и раскрой скобки.
20. Ученик написал: `6 - (x + 2) = 6 - x + 2`. Найди ошибку, запиши правильно и объясни словами.

## Generation Targets

For "Раскрытие скобок", target enough tasks for several weeks and multiple student levels:

- 30 concept tasks.
- 60 plus/minus parentheses tasks.
- 60 coefficient-before-parentheses tasks.
- 60 opening-and-like-terms tasks.
- 30 real-context tasks.
- 30 error-analysis tasks.
- 10 mini-checks of 5 tasks each.
- 8 homework sets of 15-20 tasks.

For other topics, use the same proportions but adapt task roles to the topic.

## Product Direction

The bank should serve tutors first:

- Tutors need ready lesson and homework sets, not only a giant list.
- Every homework should be printable or assignable as a bundle.
- Tasks should have answers and short hints.
- Tasks should be tagged enough to filter by skill, type, and difficulty.
- Generated tasks should be separate from textbook-sourced tasks.

The existing static viewer can remain a research artifact. Product data should move to:

```text
storage/app/tasks/alg/grade_7/
```

The old files can either be copied as baseline source blocks or converted with the new methodological fields.

## Open Decisions

- Whether homework sets should be stored inside each `topic_NN.json` or in separate `homework/topic_NN_set_NN.json` files.
- Whether generated tasks should be committed directly or produced by deterministic generation scripts from templates.
- Whether answers must be complete for the first release or can be added topic by topic.
- Whether tutors should see explanations/hints in the UI immediately or only after expanding a task.

