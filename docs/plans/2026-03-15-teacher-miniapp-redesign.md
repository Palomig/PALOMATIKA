# Teacher Mini App Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Rebuild the teacher Telegram Mini App into a mobile-first flow centered on student profiles, fast homework assignment, and a useful "Today" hub.

**Architecture:** Keep the existing Laravel controller and route structure, but redesign the teacher mini app Blade views around a shared mobile shell with bottom navigation, reusable card patterns, and bottom-sheet flows. Reuse existing data from `MiniAppController` wherever possible, adding only small controller/view-model enrichments needed to support the new UI states.

**Tech Stack:** Laravel, Blade, Alpine.js, inline mini app CSS in `resources/views/layouts/miniapp.blade.php`, existing Telegram WebApp integration

---

### Task 1: Audit teacher mini app entry points and shared shell

**Files:**
- Modify: `resources/views/layouts/miniapp.blade.php`
- Inspect: `resources/views/miniapp/teacher-dashboard.blade.php`
- Inspect: `resources/views/miniapp/teacher-students.blade.php`
- Inspect: `resources/views/miniapp/teacher-student-profile.blade.php`
- Inspect: `resources/views/miniapp/teacher-homework.blade.php`

**Step 1: Capture the current shared layout constraints**

Review the layout for:

- available CSS variables;
- current page width and spacing;
- how styles and scripts are injected;
- which global classes can be safely replaced versus extended.

**Step 2: Define the shared shell responsibilities**

Document which shell elements should live in the layout:

- app background;
- page container;
- sticky or fixed bottom navigation;
- shared card, chip, stat and sheet primitives;
- shared top header behavior.

**Step 3: Implement the shell updates**

Add the shared visual system and reusable mobile primitives to `resources/views/layouts/miniapp.blade.php` without breaking non-teacher mini app screens.

**Step 4: Verify visual regressions**

Open the teacher screens locally and confirm the shell still renders all pages without clipped content or unusable spacing.

**Step 5: Commit**

```bash
git add resources/views/layouts/miniapp.blade.php
git commit -m "feat: add shared mobile shell for teacher mini app"
```

### Task 2: Rebuild the teacher dashboard into a "Today" hub

**Files:**
- Modify: `app/Http/Controllers/MiniAppController.php`
- Modify: `resources/views/miniapp/teacher-dashboard.blade.php`
- Inspect: `resources/views/miniapp/teacher-homework.blade.php`

**Step 1: Write the failing behavior check**

Define the required UI states:

- current lesson list with quick actions;
- attention block;
- quick actions block;
- useful empty state when no lesson exists.

If there are feature or browser tests for mini app pages, add one for the teacher dashboard content. If there are no existing UI tests for this area, note the gap and use manual verification for this task.

**Step 2: Enrich the controller data minimally**

Reuse schedule and student linkage concepts already used by `teacherHomework()`. Add only the data needed to render:

- today/current lesson preview;
- students needing attention;
- shortcut counts or summaries.

Prefer extracting a small shared helper instead of duplicating schedule assembly logic.

**Step 3: Implement the new dashboard UI**

Convert `teacher-dashboard.blade.php` into a `Today` screen with:

- hero/header;
- current lesson cards;
- attention cards;
- quick actions;
- condensed recent activity where useful.

**Step 4: Verify on mobile width**

Check that the most important actions are visible above the fold on a narrow viewport and that each primary action is reachable in one tap.

**Step 5: Commit**

```bash
git add app/Http/Controllers/MiniAppController.php resources/views/miniapp/teacher-dashboard.blade.php
git commit -m "feat: redesign teacher dashboard as today hub"
```

### Task 3: Redesign the student list as a mobile CRM

**Files:**
- Modify: `resources/views/miniapp/teacher-students.blade.php`
- Modify: `app/Http/Controllers/MiniAppController.php`

**Step 1: Write the failing behavior check**

Define the required list behaviors:

- search remains available;
- quick filters render;
- cards prioritize profile access over inline editing;
- alias/link settings remain accessible through a secondary flow.

Add or update tests if coverage exists; otherwise plan manual verification with concrete scenarios.

**Step 2: Prepare student card view data**

Add compact derived fields as needed:

- risk label;
- linkage state;
- current lesson state;
- brief progress signal if already derivable from available data.

Do not introduce expensive analytics queries unless clearly necessary.

**Step 3: Implement the redesigned list**

Replace the current form-heavy list with:

- search;
- filter chips;
- cleaner student cards;
- a bottom sheet or expandable secondary panel for alias/Evrium settings.

**Step 4: Verify common flows**

Manually confirm:

- searching still works;
- opening profile still works;
- toggling ownership still works;
- saving alias still works.

**Step 5: Commit**

```bash
git add app/Http/Controllers/MiniAppController.php resources/views/miniapp/teacher-students.blade.php
git commit -m "feat: redesign teacher student list for mobile"
```

### Task 4: Turn the student profile into the primary work surface

**Files:**
- Modify: `resources/views/miniapp/teacher-student-profile.blade.php`
- Modify: `app/Http/Controllers/MiniAppController.php`

**Step 1: Write the failing behavior check**

Define the target sections:

- student header with actions;
- KPI strip;
- weak topics;
- recent attempts;
- homework history.

If there is no existing test coverage for this Blade page, document manual checks and expected rendered content.

**Step 2: Add missing profile data**

Augment the controller/view data only where necessary to support:

- homework status summary;
- clear last activity signal;
- actionable weak-topic cards.

Keep the query set tight and avoid broad eager-loading that is not required by the new UI.

**Step 3: Implement the new profile layout**

Make the page answer "what should the teacher do next?" rather than merely listing stats.

**Step 4: Verify edge cases**

Check:

- student with no attempts;
- student with no scored data;
- student with no homework history;
- long names and missing email.

**Step 5: Commit**

```bash
git add app/Http/Controllers/MiniAppController.php resources/views/miniapp/teacher-student-profile.blade.php
git commit -m "feat: redesign teacher student profile"
```

### Task 5: Rebuild homework around assignment and control modes

**Files:**
- Modify: `resources/views/miniapp/teacher-homework.blade.php`
- Modify: `app/Http/Controllers/MiniAppController.php`

**Step 1: Write the failing behavior check**

Define the target states:

- `Assign` mode;
- `Control` mode;
- schedule-aware assignment shortcuts;
- recent assignment status cards.

Add tests if existing infrastructure makes it cheap; otherwise use explicit manual verification.

**Step 2: Restructure the page model**

Keep current data sources, but group them for the new UI:

- current lesson assignment targets;
- student picker data;
- recent homework grouped by status;
- assignment flow defaults.

**Step 3: Implement the UI and sheets**

Redesign the page so:

- the page opens in a clear primary mode;
- bottom sheets handle assignment flows;
- control cards are compact and legible on mobile;
- inline form clutter is removed.

**Step 4: Verify end-to-end behavior**

Manually test:

- assign full variant;
- assign topic practice;
- assign from current lesson;
- assignment success toast;
- visibility of recent homework statuses.

**Step 5: Commit**

```bash
git add app/Http/Controllers/MiniAppController.php resources/views/miniapp/teacher-homework.blade.php
git commit -m "feat: redesign teacher homework flow"
```

### Task 6: Add bottom navigation and secondary "More" entry points

**Files:**
- Modify: `resources/views/layouts/miniapp.blade.php`
- Modify: teacher mini app views that must mark active nav state

**Step 1: Define navigation mapping**

Map the teacher routes to bottom-nav items:

- `/tg/teacher/dashboard` -> `Сегодня`
- `/tg/teacher/students` -> `Ученики`
- `/tg/teacher/homework` -> `Домашка`
- secondary teacher/admin routes -> `Ещё`

**Step 2: Implement the navigation**

Add a fixed mobile bottom nav that respects safe area insets and does not cover page content.

**Step 3: Expose secondary actions**

Provide an entry point for:

- variants;
- referrals;
- role switch;
- any remaining teacher tools.

This can be a dedicated `More` sheet or a compact page if that is simpler and more robust.

**Step 4: Verify route coverage**

Open each teacher route and confirm the correct nav item is active or that the page still has a coherent escape hatch back to the main sections.

**Step 5: Commit**

```bash
git add resources/views/layouts/miniapp.blade.php resources/views/miniapp/teacher-*.blade.php
git commit -m "feat: add teacher mini app navigation"
```

### Task 7: QA, regression checks and polish

**Files:**
- Inspect: all modified teacher mini app views
- Inspect: `routes/web.php`
- Inspect: any tests touched during implementation

**Step 1: Run targeted tests**

Run the smallest relevant suite available for any tests added or changed.

Possible commands:

```bash
php artisan test --filter=MiniApp
```

If the project has no useful coverage for these views, state that clearly and rely on manual verification.

**Step 2: Run manual UX checks**

Verify:

- Telegram-safe viewport behavior;
- no bottom nav overlap;
- bottom sheets scroll correctly;
- long content remains readable;
- light theme remains legible in Telegram WebApp.

**Step 3: Clean up**

Remove dead styles and duplicated rules introduced during the transition.

**Step 4: Final regression pass**

Open:

- dashboard/today;
- students list;
- student profile;
- homework;
- one non-teacher mini app screen to ensure shared layout changes did not break it.

**Step 5: Commit**

```bash
git add resources/views/layouts/miniapp.blade.php resources/views/miniapp app/Http/Controllers/MiniAppController.php
git commit -m "feat: polish teacher mini app redesign"
```
