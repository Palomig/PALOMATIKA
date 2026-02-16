# Teacher SugarCRM Light Redesign Design

## Goal
Привести все внутренние страницы `/teacher` к единому light-only визуальному языку в стиле SugarCRM: мягкие светлые поверхности, тонкие бордеры, акцентные тёмные кнопки, чистая иерархия карточек и таблиц.

## Scope
- `resources/views/layouts/dashboard.blade.php` (teacher shell)
- `resources/views/layouts/partials/nav-teacher.blade.php`
- Все страницы `resources/views/teacher/*.blade.php` и `resources/views/teacher/oge/*.blade.php`

## Design Decisions
1. Teacher UI mode = только light (dark-mode отключён для teacher-ветки).
2. Общие токены `--tsh-*` и утилиты (`tsh-card`, `tsh-card-soft`, `tsh-progress`, `tsh-btn*`) используются как единственный источник визуального стиля.
3. App-level actions унифицированы через `tsh-action-primary` и `tsh-action-secondary` (без инлайн-стилей в каждом шаблоне).
4. Сайдбар — компактный icon rail с чёрным active state.
5. Таблицы и карточки используют светлую иерархию: `surface` + `surface-soft` + `border-soft`.
6. Бизнес-логика, Alpine state, API-вызовы и роутинг не меняются.

## UX Rules
- Основной CTA: чёрный (`var(--tsh-accent)`) с белым текстом.
- Вторичные кнопки: ghost/outline с тонкой границей.
- Бейджи и статусы — мягкие пастельные оттенки (blue/emerald/amber/rose).
- Глубина интерфейса достигается тенями и градиентами, без тёмных подложек.

## Validation
- Добавлен feature-test `tests/Feature/TeacherUiLightThemeTest.php`:
  - teacher layout не инициализирует dark-mode script;
  - earnings page не содержит прежнюю тёмную gradient-карточку.
