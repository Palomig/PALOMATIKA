# PALOMATIKA UI Design System

## Source of Truth

- Global design tokens: `resources/views/partials/head-config.blade.php`
- Reusable UI components: `resources/views/components/ui/`
- Landing implementation using tokens/components: `resources/views/welcome.blade.php`

## Token Rules

- Use Tailwind tokens from `head-config` only (`dark`, `coral`, `success`, `warning`, `danger`, `info`)
- Avoid page-local Tailwind config overrides unless absolutely required
- Keep spacing/radius/shadow consistent with `head-config` values

## Component Rules

- UI components live under `resources/views/components/ui/`
- Every component must define `@props` at top with short self-documenting comments
- Favor variants (`variant`, `size`, etc.) over copy-paste markup

## Current UI Components

- `x-ui.button` - button/link with `variant` and `size`
- `x-ui.card` - content container card with optional `title`
- `x-ui.stat` - metric value + label block

## Commit Discipline

- One completed task = one commit
- Commit message format: `feat(ui): <task summary>` or `refactor(ui): <task summary>`
- After each commit, update `.claude/ui-improvement-progress.md` with date and hash
