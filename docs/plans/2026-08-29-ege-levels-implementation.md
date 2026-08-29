# EGE Levels Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Turn profile and base EGE into two complete, persistent modes of the same `/ege-app` interface, including mini exams, full variants, task banks, active attempts, and history labels.

**Architecture:** Resolve `prof|base` once per request and pass it through the existing parameterized EGE data, builder, and pool services. Persist the student's choice in `users.ege_level`, index every variant by `oge_variants.level`, and keep `config_json.level` for compatibility. Reuse the existing test/results screens and the OGE/VPR mini-variant interaction patterns.

**Tech Stack:** Laravel, Eloquent/MySQL, Blade, Alpine.js, PHPUnit.

---

### Task 1: Persist user and variant levels

**Files:**
- Create: `database/migrations/2026_08_29_000001_add_ege_level_to_users_and_oge_variants.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/OgeVariant.php`
- Test: `tests/Feature/Pwa/EgeLevelsTest.php`

1. Write a failing migration/model test asserting `users.ege_level` and `oge_variants.level` exist and are mass assignable.
2. Run `php artisan test tests/Feature/Pwa/EgeLevelsTest.php --filter=level_columns` and confirm it fails for missing columns.
3. Add nullable `users.ege_level`, indexed nullable `oge_variants.level`, and a migration backfill: EGE variants use `config_json.level` when valid, otherwise `prof`; non-EGE variants remain null.
4. Add the two fields to model fillable lists.
5. Re-run the focused test and commit.

### Task 2: Resolve and persist the selected level

**Files:**
- Modify: `app/Http/Controllers/Pwa/EgeStudentController.php`
- Test: `tests/Feature/Pwa/EgeLevelsTest.php`

1. Write failing tests for default profile, stored base, query override persistence, invalid query fallback, and no profile write in teacher/admin student-view context.
2. Run the focused tests and confirm the resolver behavior is absent.
3. Replace `levelFrom()` with one `resolveLevel(Request)` and a small valid-level guard. Use it in `home`, `startFull`, and `taskDatabase`.
4. Re-run tests and commit.

### Task 3: Make the home screen level-scoped

**Files:**
- Modify: `app/Http/Controllers/Pwa/EgeStudentController.php`
- Modify: `resources/views/pwa/student/ege-home.blade.php`
- Test: `tests/Feature/Pwa/EgeLevelsTest.php`

1. Write failing tests asserting level-specific badge/count, one full-variant tile, bank URLs, and active-attempt filtering.
2. Verify the tests fail against the two-tile/profile-only screen.
3. Build home data from the selected `EgeTaskDataService`, filter active EGE variants by indexed `level`, and treat legacy null as profile only.
4. Add the accessible segmented level switch, preserve the existing PWA aesthetic, and thread `level` through all EGE links/actions.
5. Re-run tests and commit.

### Task 4: Build mini-EGE variants

**Files:**
- Modify: `app/Models/OgeVariant.php`
- Modify: `app/Services/EgeVariantBuilderService.php`
- Modify: `app/Services/EgeVariantPoolService.php`
- Test: `tests/Unit/EgeVariantBuilderServiceTest.php`
- Test: `tests/Feature/Pwa/EgeLevelsTest.php`

1. Write failing unit tests for each level/mode topic range, unique topic numbers, requested counts, production-only selection, and anti-repeat separation.
2. Confirm failures because `buildMini()` and EGE mini modes do not exist.
3. Centralize the mode map, extract `buildFromTopics()`, and implement deterministic `buildMini()` with random distinct topics.
4. Extend the pool to create mini variants with `level`, mode, title, and `config_json.level`.
5. Re-run unit/feature tests and commit.

### Task 5: Add mini-EGE routes and UI

**Files:**
- Modify: `routes/pwa.php`
- Modify: `app/Http/Controllers/Pwa/EgeStudentController.php`
- Modify: `resources/views/pwa/student/ege-home.blade.php`
- Test: `tests/Feature/Pwa/EgeLevelsTest.php`

1. Write failing tests for every accepted mode, invalid mode, grade access, JSON redirects, and error `422`.
2. Confirm routes/controller actions are absent.
3. Add `POST /ege-app/mini/start`, validate mode against the selected level, create/start the attempt, and return JSON while logging pool and attempt failures separately.
4. Add a «Мини-ЕГЭ» tile and level-specific bottom sheet whose labels/counts come from the centralized mode map.
5. Re-run tests and commit.

### Task 6: Finish the task-bank navigation

**Files:**
- Modify: `app/Http/Controllers/Pwa/EgeStudentController.php`
- Modify: `resources/views/pwa/student/ege-tasks.blade.php`
- Modify: `resources/views/pwa/student/ege-home.blade.php`
- Test: `tests/Feature/Pwa/EgeLevelsTest.php`

1. Write failing tests that profile retains two parts, base shows all 21 numbers without part selection, and back/topic links preserve level.
2. Confirm current links lose level or expose the old base entry.
3. Remove the nested level choice, pass the selected level directly, and preserve it on every navigation link.
4. Re-run tests plus `TaskBankPagesLookAlikeTest` and commit.

### Task 7: Label history and retain backward compatibility

**Files:**
- Modify: `app/Http/Controllers/Traits/MiniAppHelpers.php`
- Test: `tests/Feature/Pwa/EgeLevelsTest.php`

1. Write failing tests for full and every mini label at both levels plus a legacy EGE variant without `level`.
2. Verify current labels fall through to OGE/default wording.
3. Make `variantModeLabel()` EGE-aware, prefer the indexed column, and fall back to `config_json.level` then `prof`.
4. Re-run tests and commit.

### Task 8: Regression verification

**Files:**
- Modify only if a regression test reveals an in-scope defect.

1. Run `php artisan test tests/Feature/Pwa/EgeLevelsTest.php tests/Feature/Pwa/EgeFipiVariantTest.php tests/Unit/EgeVariantBuilderServiceTest.php`.
2. Run the task-bank visual-contract test named in the specification.
3. Run relevant OGE/VPR dashboard and mini-variant tests to prove shared constants/helpers remain compatible.
4. Inspect the diff, confirm migrations are reversible, and confirm no unrelated worktree files changed.
5. Commit any test-driven corrections and report branch, commits, and verification results. Do not promote or deploy without an explicit follow-up instruction.
