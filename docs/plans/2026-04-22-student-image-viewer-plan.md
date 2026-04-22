# Student Image Viewer Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a shared fullscreen image viewer for student test pages, exclude OGE topic 11 graph tasks, and force landscape-style viewing for number-line images.

**Architecture:** Annotate serialized task payloads with viewer metadata in the PWA controllers, then render one shared viewer modal across the student test Blade templates. Keep the frontend logic data-driven so exclusions and orientation rules are testable without brittle DOM parsing.

**Tech Stack:** Laravel controllers, Blade templates, Alpine.js, PHPUnit feature tests

---

### Task 1: Add failing tests for viewer metadata and shared template wiring

**Files:**
- Modify: `tests/Feature/Pwa/PwaStudentExamAccessTest.php`
- Modify: `tests/Feature/Pwa/PwaVprDashboardTest.php`

**Step 1:** Add a failing OGE test asserting topic 11 tasks are serialized with `viewer_disabled=true`.

**Step 2:** Add a failing VPR test asserting number-line tasks are serialized with `viewer_orientation=landscape`.

**Step 3:** Add a failing template wiring test asserting `test.blade.php`, `vpr-test.blade.php`, and `ege-test.blade.php` include the shared image viewer partial.

**Step 4:** Run only the new tests and confirm they fail for the expected missing metadata / missing include reason.

### Task 2: Annotate task payloads in student controllers

**Files:**
- Modify: `app/Http/Controllers/Pwa/StudentController.php`
- Modify: `app/Http/Controllers/Pwa/VprController.php`
- Modify: `app/Http/Controllers/Pwa/EgeStudentController.php`

**Step 1:** Add a small normalization helper that writes viewer metadata onto tasks.

**Step 2:** Disable viewer only for `exam_type=oge` and `topic_id=11`.

**Step 3:** Mark number-line tasks as `landscape` for OGE topic `07` and VPR topic `06`.

**Step 4:** Re-run the targeted tests and confirm the payload assertions pass.

### Task 3: Build a shared student image viewer partial

**Files:**
- Create: `resources/views/pwa/student/partials/image-viewer.blade.php`
- Modify: `resources/views/pwa/student/test.blade.php`
- Modify: `resources/views/pwa/student/vpr-test.blade.php`
- Modify: `resources/views/pwa/student/ege-test.blade.php`

**Step 1:** Add a shared modal partial with overlay, close button, portrait/landscape classes, and rotate-device hint for landscape tasks.

**Step 2:** Add Alpine state and methods to open/close the viewer from the current task.

**Step 3:** Make image/SVG blocks clickable everywhere except when `viewer_disabled=true`.

**Step 4:** Keep existing wide graph layout intact while skipping fullscreen for OGE topic 11.

**Step 5:** Re-run the targeted tests and template wiring assertions.

### Task 4: Run focused regression checks and commit

**Files:**
- Modify: `tests/Feature/Pwa/PwaStudentExamAccessTest.php`
- Modify: `tests/Feature/Pwa/PwaVprDashboardTest.php`

**Step 1:** Run the new viewer tests plus nearby existing PWA regressions.

**Step 2:** Inspect the git diff for only the intended files.

**Step 3:** Commit with a focused message.

**Step 4:** Push `claude/pwa-migration` and verify whether automerge has reached `origin/main`.
