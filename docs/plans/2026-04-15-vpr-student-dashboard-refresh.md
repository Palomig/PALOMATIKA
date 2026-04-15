# VPR Student Dashboard Refresh Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Bring the student VPR dashboard in line with the existing student OGE dashboard UI while making every exam-specific action operate on VPR flows.

**Architecture:** Reuse the current PWA student dashboard interaction model instead of maintaining a separate VPR landing page. Extend the VPR controller and variant pool so the dashboard can render the same cards, unfinished-attempt banners, and modal flows as OGE, but backed by VPR routes and VPR variants.

**Tech Stack:** Laravel, Blade, Alpine.js, PHPUnit feature tests

---

## Design Summary

- Replace the current minimal `pwa.student.vpr-home` screen with a dashboard styled after `pwa.student.dashboard`.
- Show the real student grade everywhere the dashboard references the class.
- Keep the same card grid and modal patterns as OGE, but retarget them to VPR:
  - `Мини-ВПР`
  - `Полный вариант`
  - `База заданий`
  - `История`
  - `Профиль`
  - `Репетитор`
  - `Пригласить друга`
- Add a real VPR mini flow so the mini card starts a smaller VPR attempt instead of acting as a dead-end.
- Preserve existing VPR full test, results, and task database pages.

### Task 1: Lock expected VPR dashboard behavior with feature tests

**Files:**
- Modify: `tests/Feature/Pwa/PwaStudentRoutesTest.php`
- Create: `tests/Feature/Pwa/PwaVprDashboardTest.php`

**Step 1: Write the failing test**

Add feature coverage for:
- authenticated grade-5/6/7/8 student can open `http://student.palomatika.ru/vpr`
- response includes `ВПР · {grade} класс`
- response includes `Мини-ВПР`, `Полный вариант`, and `База заданий`
- `POST http://student.palomatika.ru/vpr/mini/start` creates a VPR mini attempt and returns JSON redirect

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php`

Expected: FAIL because the VPR dashboard still renders the old simplified screen and there is no VPR mini route.

**Step 3: Write minimal implementation**

Implement controller, route, pool, and Blade changes needed to satisfy the tests.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php`

Expected: PASS

### Task 2: Add VPR mini-variant generation support

**Files:**
- Modify: `app/Services/VprVariantBuilderService.php`
- Modify: `app/Services/VprVariantPoolService.php`
- Modify: `app/Http/Controllers/Pwa/VprController.php`

**Step 1: Write the failing test**

Cover that mini VPR creates a smaller variant with a VPR exam type and a `mini_*` mode.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php --filter=mini`

Expected: FAIL because the VPR pool only creates full variants.

**Step 3: Write minimal implementation**

- Allow the VPR builder to build a mini variant from a bounded subset of topics
- Persist VPR mini variants with `mini_mixed` mode
- Return JSON redirects for VPR mini/full starts when the dashboard uses fetch

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php --filter=mini`

Expected: PASS

### Task 3: Replace the VPR landing page with a dashboard matching student OGE

**Files:**
- Modify: `resources/views/pwa/student/vpr-home.blade.php`
- Modify: `app/Http/Controllers/Pwa/VprController.php`

**Step 1: Write the failing test**

Assert that the VPR dashboard includes the OGE-style action grid and grade-specific label.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php --filter=dashboard`

Expected: FAIL because the old VPR page does not match the new structure.

**Step 3: Write minimal implementation**

- Feed the VPR view with OGE-style dashboard data
- Reuse the same card layout, unfinished attempts section, and modals
- Change only the exam wording and route targets to VPR equivalents

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php --filter=dashboard`

Expected: PASS

### Task 4: Run focused regression checks

**Files:**
- Modify: none

**Step 1: Run focused tests**

Run:
- `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php`
- `php artisan test tests/Feature/GradeRoutingTest.php`

Expected: PASS

**Step 2: Sanity-check existing VPR pages**

Run a quick manual code review of:
- `resources/views/pwa/student/vpr-test.blade.php`
- `resources/views/pwa/student/vpr-results.blade.php`
- `resources/views/pwa/student/vpr-tasks.blade.php`

Expected: links still point back to `pwa.student.vpr.home`
