# EGE Task Formula Layout Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Put the primary formulas of EGE task-bank topics 13 and 15 on a separate line while keeping intervals and later formulas inline, and preserve real data tables in topic 16 on mobile.

**Architecture:** Add a presentation-only formatter that inserts one line break before the first math fragment in the first paragraph. Invoke it in the EGE task-bank Blade view only for topics 13 and 15, leaving stored task HTML untouched.

**Tech Stack:** Laravel, PHP regular expressions, Blade, PHPUnit.

---

### Task 1: Add the presentation formatter

**Files:**
- Create: `app/Support/EgeTaskBankFormatter.php`
- Create: `tests/Unit/EgeTaskBankFormatterTest.php`

1. Write failing tests for topic-13-style HTML, topic-15-style HTML, the inline interval, and HTML without a leading formula.
2. Run `php artisan test tests/Unit/EgeTaskBankFormatterTest.php` and confirm the class is absent.
3. Implement `separatePrimaryFormula(string $html): string`, inserting one marked `<br>` before the first `$…$` fragment of the first paragraph.
4. Re-run the unit test and commit.

### Task 2: Apply the formatter to topics 13 and 15

**Files:**
- Modify: `resources/views/pwa/student/ege-tasks.blade.php`
- Modify: `tests/Feature/Pwa/EgeFipiVariantTest.php`

1. Add a failing feature assertion that topic 13 and topic 15 render the marked break, while another topic does not.
2. Format `$task['html']` only when `(int) $selected` is 13 or 15.
3. Run the focused feature test, then `EgeFipiVariantTest` and `EgeLevelsTest` completely.
4. Run `php scripts/blade-lint.php resources/views/pwa/student/ege-tasks.blade.php` and `git diff --check`.
5. Commit and push `claude/ege-task-format`; production promotion requires explicit approval.

### Task 3: Preserve topic 16 financial tables

**Files:**
- Modify: `resources/views/pwa/student/ege-tasks.blade.php`
- Modify: `tests/Feature/Pwa/EgeFipiVariantTest.php`

1. Add a failing feature test with the real table structure used by the financial task.
2. Remove the nested `<style>` element from the layout stack.
3. Keep data tables as tables with horizontal scrolling; scope the old mobile stacking rule to figure-layout tables only.
4. Run the focused tests, the complete EGE feature suites, Blade lint, and `git diff --check`.
