# FIPI Lesson Resolver Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Восстановить добавление заданий нового банка ФИПИ в урок.

**Architecture:** Нормализовать специфичные поля `fipi` внутри
`TaskBankResolver`, сохранив старый контракт уроков и домашек. Импорт, схема
БД и фронтовой picker остаются без изменений.

**Tech Stack:** Laravel 10, PHP 8.2, PHPUnit, Eloquent/SQLite tests.

---

### Task 1: Зафиксировать регрессию

**Files:**
- Create: `tests/Feature/FipiLessonTaskResolveTest.php`

**Step 1: Write the failing test**

Импортировать `bank_katex.json`, получить реальные задачи через
`LessonTaskPickerService`, передать их refs в `TaskBankResolver` и проверить
нормализованные условие, SVG и варианты. Через teacher endpoint проверить,
что задача сохраняется в `lesson_session_tasks`.

**Step 2: Run test to verify it fails**

Run:
`DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/FipiLessonTaskResolveTest.php --do-not-cache-result`

Expected: FAIL с `Task type 'fipi' not supported in v1`.

### Task 2: Нормализовать формат ФИПИ

**Files:**
- Modify: `app/Services/TaskBankResolver.php`
- Test: `tests/Feature/FipiLessonTaskResolveTest.php`

**Step 1: Write minimal implementation**

Добавить разбор HTML условия, извлечение единственного SVG, преобразование
`{n, html}` в `{id, label}` и выбор `choice` только для одиночного ответа.

**Step 2: Run test to verify it passes**

Run:
`DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/FipiLessonTaskResolveTest.php --do-not-cache-result`

Expected: PASS.

**Step 3: Run focused regressions**

Run:
`DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Unit/TaskBankResolverTest.php tests/Feature/TeacherLessonControllerTest.php tests/Feature/LessonPickerShowsFipiTasksTest.php --do-not-cache-result`

Expected: PASS.

**Step 4: Commit**

Commit test and implementation as one focused bugfix after the red/green
cycle, then push `claude/fix-fipi-lesson-add`.

