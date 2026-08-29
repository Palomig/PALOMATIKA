# EGE Level Switch Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the EGE level selector with a full-width horizontal two-button control labelled `Профиль` and `База`.

**Architecture:** Keep the existing level URLs, persistence, and accessibility attributes. Change only the Blade markup and component-local CSS, with a feature test that locks the horizontal layout and active-state contract.

**Tech Stack:** Laravel Blade, CSS, PHPUnit.

---

### Task 1: Lock the selector contract

**Files:**
- Modify: `tests/Feature/Pwa/EgeLevelsTest.php`

1. Extend the home-screen test to assert short labels, absence of the redundant marks, equal horizontal columns, and the active class for the selected level.
2. Run `php artisan test tests/Feature/Pwa/EgeLevelsTest.php --filter=home_has_one_level_scoped_full_variant_and_switcher` and confirm it fails on the old labels/markup.

### Task 2: Implement the horizontal control

**Files:**
- Modify: `resources/views/pwa/student/ege-home.blade.php`
- Test: `tests/Feature/Pwa/EgeLevelsTest.php`

1. Keep `.ege-level-switch` as a two-column grid with equal columns and no wrapping.
2. Restyle the active option with the existing accent background/border/text tokens and leave the inactive option muted.
3. Remove `.ege-level-mark` and render only `Профиль` and `База`.
4. Run the focused test and the complete EGE feature test.
5. Run `php scripts/blade-lint.php resources/views/pwa/student/ege-home.blade.php` and `git diff --check`.
6. Commit and push `claude/ege-level-switch`; do not promote to production without explicit approval.
