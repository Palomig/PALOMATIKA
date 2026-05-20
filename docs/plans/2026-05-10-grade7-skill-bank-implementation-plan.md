# Grade 7 Skill Bank Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use executing-plans to implement this plan task-by-task.

**Goal:** Build a static and data-backed grade 7 algebra skill bank with one page per skill and three difficulty levels per skill.

**Architecture:** Generate `skills.json` from a curated skill catalog and deterministic task factories. Build static pages under `/var/www/html/alg-skills/7/` using the same visual language as `/alg-topics/7/0/`.

**Tech Stack:** Node.js scripts, JSON task data, static HTML, KaTeX, Laravel/PHP tests for existing algebra pages.

---

### Task 1: Skill Data Generator

**Files:**
- Create: `scripts/generate-grade7-skill-bank.mjs`
- Create: `storage/app/tasks/alg/grade_7/skills.json`

**Steps:**
1. Define a skill catalog grouped by arithmetic, expressions, equations, powers, polynomials, functions, and systems.
2. Add deterministic generators for simple, medium, and high tasks.
3. Normalize display math to `\cdot` and `:`.
4. Write `skills.json`.

### Task 2: Static Page Builder

**Files:**
- Create: `scripts/build-grade7-skill-pages.mjs`
- Write static output to `/var/www/html/alg-skills/7/`

**Steps:**
1. Read `skills.json`.
2. Build an index page with group filters and skill cards.
3. Build one page per skill.
4. Render math with KaTeX and wrap long text.

### Task 3: Regression Tests

**Files:**
- Create: `tests/js/grade7-skill-bank.test.mjs`

**Steps:**
1. Assert every skill has exactly three levels.
2. Assert every page skill has one `task_type`.
3. Assert expressions/prompts contain no raw `*` or `/`.
4. Assert each level has enough tasks for homework practice.

### Task 4: Verify and Commit

**Commands:**
- `node tests/js/grade7-skill-bank.test.mjs`
- `node scripts/generate-grade7-skill-bank.mjs`
- `node scripts/build-grade7-skill-pages.mjs`
- `php artisan test --filter=AlgTopicDataServiceTest`

**Commit:** `feat(algebra): build grade 7 skill bank pages`
