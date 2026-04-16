# Android Keyboard Layout Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Remove Android keyboard layout forcing from student test answer inputs across OGE, EGE, and VPR pages.

**Architecture:** Replace `inputmode="numeric"` with neutral text input behavior in student Blade templates and add a regression test that scans the templates for forbidden numeric inputmode usage.

**Tech Stack:** Laravel Blade, PHPUnit feature tests.

---

### Task 1: Add regression test

**Files:**
- Modify: `tests/Feature/Pwa/PwaVprDashboardTest.php`

**Step 1: Write the failing test**

Add a test that loads the three student test Blade files and asserts they do not contain `inputmode="numeric"`.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php --filter='student_test_templates_do_not_force_numeric_inputmode'`
Expected: FAIL

### Task 2: Remove numeric inputmode from student test templates

**Files:**
- Modify: `resources/views/pwa/student/vpr-test.blade.php`
- Modify: `resources/views/pwa/student/test.blade.php`
- Modify: `resources/views/pwa/student/ege-test.blade.php`

**Step 1: Update templates**

Replace any `inputmode="numeric"` answer fields with neutral text input mode.

**Step 2: Run targeted test**

Run: `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php --filter='student_test_templates_do_not_force_numeric_inputmode'`
Expected: PASS

### Task 3: Run regression suite and ship

**Files:**
- Modify: tracked files above

**Step 1: Run full suite**

Run: `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php`
Expected: PASS

**Step 2: Commit and push**

Run:
```bash
git add docs/plans/2026-04-16-android-keyboard-layout-design.md docs/plans/2026-04-16-android-keyboard-layout-plan.md resources/views/pwa/student/vpr-test.blade.php resources/views/pwa/student/test.blade.php resources/views/pwa/student/ege-test.blade.php tests/Feature/Pwa/PwaVprDashboardTest.php
git commit -m "fix: stop forcing android keyboard layout in tests"
git push -u origin claude/pwa-migration
```
