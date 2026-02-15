# OGE Assessment Workflow Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver teacher-owned OGE assessment flow with student answer versioning, final submission, and teacher-only correctness/time analytics.

**Architecture:** Hybrid event-sourcing. Write all student interactions to immutable events; maintain projection tables for fast student/teacher pages. Enforce role-based access with strict visibility boundaries.

**Tech Stack:** Laravel (routes, middleware, policies, migrations, Eloquent), Blade + Alpine, existing Telegram integration.

---

### Task 1: Access Control Baseline

**Files:**
- Create: `app/Http/Middleware/EnsureUserRole.php`
- Modify: `app/Http/Kernel.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/OgeAccessControlTest.php`

**Step 1: Write failing tests for teacher/student access rules**

**Step 2: Run tests to verify failure**

**Step 3: Add role middleware and route guards**

**Step 4: Re-run tests**

**Step 5: Commit**

### Task 2: Core OGE Assessment Schema

**Files:**
- Create: `database/migrations/2026_02_15_120000_create_oge_assessment_tables.php`
- Create: `app/Models/OgeVariant.php`
- Create: `app/Models/OgeAttempt.php`
- Create: `app/Models/OgeAttemptEvent.php`
- Create: `app/Models/OgeAttemptAnswer.php`
- Create: `app/Models/OgeAttemptTaskTiming.php`
- Create: `app/Models/OgeAttemptScoring.php`
- Test: `tests/Feature/OgeAssessmentSchemaTest.php`

**Step 1: Write schema/relationship tests**

**Step 2: Verify fail**

**Step 3: Implement migration + models**

**Step 4: Verify pass**

**Step 5: Commit**

### Task 3: Student Attempt API (start/focus/blur/commit/heartbeat/submit)

**Files:**
- Create: `app/Http/Controllers/OgeAttemptController.php`
- Create: `app/Services/OgeAttemptService.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/OgeAttemptApiTest.php`

**Step 1: Write failing API tests**

**Step 2: Verify fail**

**Step 3: Implement endpoints + event writes + projection updates**

**Step 4: Verify pass**

**Step 5: Commit**

### Task 4: Student Variant Page UX (OK/Edit/Finish)

**Files:**
- Modify: `resources/views/test/oge-variant.blade.php`
- Modify: `resources/views/tasks/variant-task.blade.php`
- Create: `resources/views/components/oge/student-controls.blade.php`
- Test: `tests/Feature/OgeStudentVariantPageTest.php`

**Step 1: Write page behavior tests where feasible**

**Step 2: Verify fail**

**Step 3: Add per-task `OK` and `Edit`, add `Finish` flow wiring**

**Step 4: Verify pass**

**Step 5: Commit**

### Task 5: Teacher Review Pages (hierarchy and results table)

**Files:**
- Create: `app/Http/Controllers/Teacher/OgeReviewController.php`
- Create: `resources/views/teacher/oge/teachers.blade.php`
- Create: `resources/views/teacher/oge/variants.blade.php`
- Create: `resources/views/teacher/oge/results.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/TeacherOgeReviewTest.php`

**Step 1: Write failing tests for read-only cross-teacher visibility**

**Step 2: Verify fail**

**Step 3: Implement pages and queries using projections**

**Step 4: Verify pass**

**Step 5: Commit**

### Task 6: Student Groups (owner teacher + shared viewing)

**Files:**
- Create: `database/migrations/2026_02_15_130000_create_student_groups_tables.php`
- Create: `app/Models/StudentGroup.php`
- Create: `app/Http/Controllers/Teacher/StudentGroupController.php`
- Create: `resources/views/teacher/groups/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/StudentGroupsTest.php`

**Step 1: Write failing tests for M:N group assignment**

**Step 2: Verify fail**

**Step 3: Implement DB + CRUD minimal UI**

**Step 4: Verify pass**

**Step 5: Commit**

### Task 7: Telegram Send Actions

**Files:**
- Create: `app/Http/Controllers/Teacher/OgeShareController.php`
- Modify: Telegram bot service files used in project
- Modify: teacher variant/results views
- Test: `tests/Feature/OgeTelegramShareTest.php`

**Step 1: Write failing tests for authorization + payload creation**

**Step 2: Verify fail**

**Step 3: Implement group/personal send actions**

**Step 4: Verify pass**

**Step 5: Commit**

### Task 8: Analytics Feature Materialization (Phase 1)

**Files:**
- Create: `app/Services/OgeAnalyticsService.php`
- Create: `app/Console/Commands/OgeMaterializeAnalytics.php`
- Modify: teacher results view to show core metrics
- Test: `tests/Feature/OgeAnalyticsMaterializationTest.php`

**Step 1: Write failing tests for computed metrics**

**Step 2: Verify fail**

**Step 3: Implement metric materialization from events**

**Step 4: Verify pass**

**Step 5: Commit**
