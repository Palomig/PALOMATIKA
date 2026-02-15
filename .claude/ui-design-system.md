# PALOMATIKA UI Design System

## Source of Truth

- Global design tokens: `resources/views/partials/head-config.blade.php`
- KaTeX math rendering: `resources/views/partials/head-katex.blade.php`
- Reusable UI components: `resources/views/components/ui/`
- Unified dashboard layout: `resources/views/layouts/dashboard.blade.php`
- Navigation partials: `resources/views/layouts/partials/nav-student.blade.php`, `nav-teacher.blade.php`

## Token Rules

- Use Tailwind tokens from `head-config` only (`dark`, `coral`, `success`, `warning`, `danger`, `info`)
- Avoid page-local Tailwind config overrides unless absolutely required
- Keep spacing/radius/shadow consistent with `head-config` values
- Exception: EGE pages use `body.ege-theme` CSS override for darker palette

## Layout Architecture

```
layouts/dashboard.blade.php    ← Unified layout (role: student|teacher)
├── layouts/app.blade.php      ← Thin wrapper: @extends('layouts.dashboard', ['role' => 'student'])
├── layouts/teacher.blade.php  ← Thin wrapper: @extends('layouts.dashboard', ['role' => 'teacher'])
├── layouts/auth.blade.php     ← Standalone auth layout
├── layouts/topic.blade.php    ← Topic pages (OGE)
└── layouts/ege.blade.php      ← EGE pages (darker palette)
```

## Component Rules

- UI components live under `resources/views/components/ui/`
- Every component must define `@props` at top with short self-documenting comments
- Favor variants (`variant`, `size`, `color`) over copy-paste markup
- Use `{{ $attributes->merge(['class' => '...']) }}` for extensibility
- All components use design tokens from `head-config`

## Current UI Components (10 total)

| Component | Usage | Props |
|-----------|-------|-------|
| `x-ui.button` | Links/buttons | `href, variant(primary/ghost/outline), size(sm/md/lg), type` |
| `x-ui.card` | Content containers | `title` |
| `x-ui.stat` | Metric display | `value, label` |
| `x-ui.input` | Form fields | `type, label, placeholder, error, xModel` |
| `x-ui.avatar` | User avatars | `name, src, size(sm/md/lg/xl), bg, color` |
| `x-ui.progress-bar` | Progress indicators | `value, max, color(coral/green/blue/orange), showLabel, height(sm/md/lg)` |
| `x-ui.badge` | Tags/labels | `label, color(coral/green/blue/yellow/purple/gray), size(sm/md)` |
| `x-ui.modal` | Dialog overlays | `show, title, maxWidth(sm/md/lg/xl)` |
| `x-ui.empty-state` | No-data placeholders | `title, description, icon, action, actionHref` |
| `x-ui.loading-spinner` | Loading indicators | `size(sm/md/lg), text` |

## Color Palette

| Token | Default | Usage |
|-------|---------|-------|
| `dark` | `#1a1a2e` | Main background |
| `dark-light` | `#252542` | Card/sidebar background |
| `dark-lighter` | `#2d2d4a` | Hover states |
| `coral` | `#ff6b6b` | Primary accent (buttons, highlights) |
| `coral-dark` | `#e85555` | Hover for coral |
| `accent` | `#8b5cf6` | Secondary accent (purple) |
| `success` | `#10b981` | Success states |
| `warning` | `#f59e0b` | Warning states |
| `danger` | `#ef4444` | Error states |
| `info` | `#3b82f6` | Info states |

## Border Radii

| Token | Value | Usage |
|-------|-------|-------|
| `rounded-card` | `1rem` | Cards, panels |
| `rounded-button` | `0.75rem` | Buttons |
| `rounded-input` | `0.75rem` | Inputs |
| `rounded-badge` | `9999px` | Badges, pills |

## Animations

- `animate-fade-in` — opacity 0→1 (0.5s)
- `animate-slide-up` — translate-y 20px→0 (0.5s)
- `animate-slide-down` — translate-y -20px→0 (0.3s)
- `animate-pulse-soft` — scale 1→1.05→1 (2s loop)
- `animate-shake` — horizontal shake (0.5s)
- `transition-card` — utility class: hover:shadow-card-hover hover:-translate-y-0.5

## Commit Discipline

- One completed task = one commit
- Commit message format: `feat(ui): <task summary>` or `refactor(ui): <task summary>`
- After each commit, update `.claude/ui-improvement-progress.md` with date and hash
