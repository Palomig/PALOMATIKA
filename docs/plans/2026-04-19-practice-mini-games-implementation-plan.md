# Practice Mini-Games Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** добавить в student PWA раздел `Практика` с мини-игрой по уравнениям и редактируемым конфигом уровней, типов заданий и теории.

**Architecture:** новый `PracticeController` обслуживает страницы и JSON-эндпоинт для вопросов. Контент игр хранится в `config/practice_games.php`, а `PracticeGameService` инкапсулирует выбор уровня и генерацию конкретных заданий. UI игры реализуется в Blade + Alpine, без серверного хранения прогресса.

**Tech Stack:** Laravel, Blade, Alpine.js, PHPUnit

---

### Task 1: Practice Routes And Pages

**Files:**
- Modify: `routes/pwa.php`
- Create: `app/Http/Controllers/Pwa/PracticeController.php`
- Create: `resources/views/pwa/student/practice/index.blade.php`
- Create: `resources/views/pwa/student/practice/mini-games.blade.php`
- Create: `resources/views/pwa/student/practice/game-topic.blade.php`
- Test: `tests/Feature/Pwa/PwaPracticeRoutesTest.php`

**Step 1: Write the failing test**

- Проверить доступность `/practice`, `/practice/mini-games`, `/practice/mini-games/equations`.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Pwa/PwaPracticeRoutesTest.php`

**Step 3: Write minimal implementation**

- Добавить маршруты и контроллер.
- Вернуть базовые blade views с нужными текстами.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Pwa/PwaPracticeRoutesTest.php`

**Step 5: Commit**

```bash
git add routes/pwa.php app/Http/Controllers/Pwa/PracticeController.php resources/views/pwa/student/practice tests/Feature/Pwa/PwaPracticeRoutesTest.php
git commit -m "feat: add practice section routes"
```

### Task 2: Dashboard CTA

**Files:**
- Modify: `resources/views/pwa/student/dashboard.blade.php`
- Test: `tests/Feature/Pwa/PwaPracticeRoutesTest.php`

**Step 1: Write the failing test**

- Проверить, что на dashboard есть `Практика`, а текста `Разбор ошибок` нет.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Pwa/PwaPracticeRoutesTest.php --filter=dashboard`

**Step 3: Write minimal implementation**

- Заменить плитку на ссылку в `Практику`.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Pwa/PwaPracticeRoutesTest.php --filter=dashboard`

**Step 5: Commit**

```bash
git add resources/views/pwa/student/dashboard.blade.php tests/Feature/Pwa/PwaPracticeRoutesTest.php
git commit -m "feat: add practice dashboard entry"
```

### Task 3: Game Config And Generator

**Files:**
- Create: `config/practice_games.php`
- Create: `app/Services/PracticeGameService.php`
- Test: `tests/Unit/PracticeGameServiceTest.php`

**Step 1: Write the failing test**

- Проверить выбор уровня по счёту.
- Проверить генерацию для `move_negative_multiplier` и `move_negative_term_after_constant`.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/PracticeGameServiceTest.php`

**Step 3: Write minimal implementation**

- Описать тему `equations`.
- Реализовать методы чтения конфига, выбора уровня и генерации вопроса.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/PracticeGameServiceTest.php`

**Step 5: Commit**

```bash
git add config/practice_games.php app/Services/PracticeGameService.php tests/Unit/PracticeGameServiceTest.php
git commit -m "feat: add configurable practice game generator"
```

### Task 4: Question API And Game Screen

**Files:**
- Modify: `routes/pwa.php`
- Modify: `app/Http/Controllers/Pwa/PracticeController.php`
- Modify: `resources/views/pwa/student/practice/game-topic.blade.php`
- Test: `tests/Feature/Pwa/PwaPracticeRoutesTest.php`

**Step 1: Write the failing test**

- Проверить JSON-ответ `/practice/api/mini-games/equations/question`.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Pwa/PwaPracticeRoutesTest.php --filter=question`

**Step 3: Write minimal implementation**

- Добавить API-эндпоинт.
- Подключить Alpine state и игровой цикл.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Pwa/PwaPracticeRoutesTest.php --filter=question`

**Step 5: Commit**

```bash
git add routes/pwa.php app/Http/Controllers/Pwa/PracticeController.php resources/views/pwa/student/practice/game-topic.blade.php tests/Feature/Pwa/PwaPracticeRoutesTest.php
git commit -m "feat: add equations mini-game flow"
```

### Task 5: Polish And Verification

**Files:**
- Modify: `resources/views/pwa/student/practice/*.blade.php`
- Test: `tests/Feature/Pwa/PwaPracticeRoutesTest.php`
- Test: `tests/Unit/PracticeGameServiceTest.php`

**Step 1: Write the failing test**

- Добавить проверки на тексты вводного и итогового экранов, если нужно.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Pwa/PwaPracticeRoutesTest.php tests/Unit/PracticeGameServiceTest.php`

**Step 3: Write minimal implementation**

- Доработать стили, CTA и теоретический блок.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Pwa/PwaPracticeRoutesTest.php tests/Unit/PracticeGameServiceTest.php`

**Step 5: Commit**

```bash
git add resources/views/pwa/student/practice tests/Feature/Pwa/PwaPracticeRoutesTest.php tests/Unit/PracticeGameServiceTest.php
git commit -m "feat: polish practice mini-game experience"
```
