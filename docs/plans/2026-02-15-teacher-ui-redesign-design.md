# Teacher UI/UX Redesign (Light + Dark) Design

**Goal:** Redesign all `teacher/*` pages and internal OGE pages with modern UX/UI and add a stable light/dark mode switch.

## Scope

- Teacher shell layout in `resources/views/layouts/dashboard.blade.php`
- Teacher sidebar navigation in `resources/views/layouts/partials/nav-teacher.blade.php`
- All teacher pages:
  - `resources/views/teacher/dashboard.blade.php`
  - `resources/views/teacher/analytics.blade.php`
  - `resources/views/teacher/students.blade.php`
  - `resources/views/teacher/groups/index.blade.php`
  - `resources/views/teacher/homework.blade.php`
  - `resources/views/teacher/earnings.blade.php`
  - `resources/views/teacher/oge/teachers.blade.php`
  - `resources/views/teacher/oge/variants.blade.php`
  - `resources/views/teacher/oge/results.blade.php`

## UX/UI Decisions

- Add a global teacher-only light/dark mode toggle in top bar.
- Persist mode in `localStorage` using `palomatika_ui_mode`.
- Use a token-based surface system for both modes via CSS variables:
  - app background
  - card/surface levels
  - text/muted text
  - borders/hover states
- Keep existing information architecture and flows (no behavior breaks).
- Add compact “hero” section on every teacher page to improve orientation and actionability.
- Unify navigation with visual tags and stronger active-state clarity.

## Technical Strategy

- Keep existing Tailwind utility usage intact and layer mode-aware CSS overrides inside the teacher shell.
- Avoid backend/API changes.
- Keep Alpine logic untouched except new `uiModeSwitcher()` helper.
- Validate by compiling Blade templates and running teacher-related feature tests.
