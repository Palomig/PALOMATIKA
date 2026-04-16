# Admin History Routes Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add an admin-friendly history flow on the student PWA domain using `/history/{studentId}` and `/history/{studentId}/{attemptId}` so admins can browse a student's attempts without typing raw attempt URLs.

**Architecture:** Keep the existing `/history` and `/history/{attemptId}` behavior for students. Add two admin-only routes in the same controller, reuse the existing history aggregation and attempt-detail logic, and render dedicated views that include quick switching between a student's attempts.

**Tech Stack:** Laravel routes/controllers, Blade views, PHPUnit feature tests.

---

### Task 1: Add failing tests for admin history routes

**Files:**
- Modify: `tests/Feature/Pwa/PwaVprDashboardTest.php`

**Step 1: Write the failing tests**

Add feature tests that assert:
- admin can open `/history/{studentId}` and see the student's attempts
- admin can open `/history/{studentId}/{attemptId}`
- mismatched `{studentId}` and `{attemptId}` returns 404

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php --filter='admin_can_open_student_history'`
Expected: FAIL with missing routes or 404s.

### Task 2: Implement admin history routes and controller actions

**Files:**
- Modify: `routes/pwa.php`
- Modify: `app/Http/Controllers/Pwa/StudentController.php`

**Step 1: Add routes**

Add:
- `GET /history/{studentId}` for admin student history list
- `GET /history/{studentId}/{attemptId}` for admin student history detail

**Step 2: Write minimal implementation**

In `StudentController`:
- add admin-only actions for student history list/detail
- factor reusable helpers for building attempt summaries and detail payloads
- ensure detail action verifies that the attempt belongs to the requested student

**Step 3: Run targeted tests**

Run: `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php --filter='admin_can_open_student_history'`
Expected: PASS

### Task 3: Build admin history UI

**Files:**
- Create: `resources/views/pwa/student/admin-history.blade.php`
- Modify: `resources/views/pwa/student/history-detail.blade.php`

**Step 1: Add the list page**

Render:
- student name and ID
- compact list of attempts for that student
- links to `/history/{studentId}/{attemptId}`

**Step 2: Update detail page navigation**

When opened through admin history, back link should return to `/history/{studentId}` and the header should show the student context.

**Step 3: Run full regression suite**

Run: `php artisan test tests/Feature/Pwa/PwaVprDashboardTest.php`
Expected: PASS

### Task 4: Commit and push

**Files:**
- Modify: tracked files from Tasks 1-3

**Step 1: Review git diff**

Run: `git diff -- app/Http/Controllers/Pwa/StudentController.php routes/pwa.php resources/views/pwa/student/history-detail.blade.php resources/views/pwa/student/admin-history.blade.php tests/Feature/Pwa/PwaVprDashboardTest.php`

**Step 2: Commit**

Run:
```bash
git add docs/plans/2026-04-16-admin-history-routes.md routes/pwa.php app/Http/Controllers/Pwa/StudentController.php resources/views/pwa/student/history-detail.blade.php resources/views/pwa/student/admin-history.blade.php tests/Feature/Pwa/PwaVprDashboardTest.php
git commit -m "feat: add admin student history browser"
```

**Step 3: Push**

Run: `git push -u origin claude/pwa-migration`
