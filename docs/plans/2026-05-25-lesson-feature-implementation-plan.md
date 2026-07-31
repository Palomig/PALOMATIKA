# Lesson Feature Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use executing-plans to implement this plan task-by-task.

**Goal:** Live-урок: учитель собирает пул задач из любого банка → ученики решают в реальном времени → учитель видит ✓/✗ по каждому ответу.

**Architecture:** Новая сущность `lesson_session` поверх существующего `lesson_schedule`. Снимки задач (`task_payload`) денормализуются на момент сборки сессии — по аналогии с `homework_topic_tasks`. Лайв через polling (3–5 сек). Off-schedule инвайт — ссылка с токеном.

**Tech Stack:** Laravel 10 + MySQL 8 + Alpine.js (CDN) + Tailwind (CDN). Без сборщиков. Polling — обычный `fetch()` с `setInterval`.

## Зафиксированные решения (см. обсуждение 2026-05-25)

| # | Аспект | Решение |
|---|---|---|
| 1 | Состав | Один общий список задач на весь слот |
| 2 | Источник | Все банки: ОГЭ + ЕГЭ + ВПР + alg-topics + alg-skills |
| 3 | Лайв | Polling каждые 3-5 сек |
| 4 | Старт | Автоматически по `lesson_schedule` + invite по ссылке с токеном |
| 5 | Типы | `expression` + `choice` в v1 |
| 6 | Pacing | Все задачи сразу, ученик в своём темпе |
| 7 | Проверка | Авточек через `TaskAnswerResolver`, ✓/✗ только у учителя |

---

## Схема данных

```
lesson_sessions
  id, teacher_id (FK users), schedule_id (FK lesson_schedule, nullable)
  status ENUM('draft','live','ended')
  starts_at, ends_at (nullable), invite_token (nullable, unique)
  created_at, updated_at

lesson_session_tasks                  ← задачи в сессии (снимок)
  id, lesson_session_id, position
  bank ENUM('oge','ege','vpr','alg-topic','alg-skill')
  grade (nullable), topic_id (nullable), skill_slug (nullable)
  task_ref TEXT                       ← внутренний id внутри банка
  task_payload JSON                   ← снимок (expression, type, options, answer)
  correct_answer TEXT
  created_at, updated_at

lesson_session_participants           ← кто участвует (из schedule + по инвайту)
  id, lesson_session_id, student_id, source ENUM('schedule','invite')
  joined_at
  UNIQUE (lesson_session_id, student_id)

lesson_session_attempts               ← ответы
  id, lesson_session_id, lesson_session_task_id, student_id
  answer_raw TEXT, is_correct BOOLEAN, answered_at, updated_at
  UNIQUE (lesson_session_id, student_id, lesson_session_task_id)
```

---

## Задачи

### Task 1: Миграции + Eloquent-модели

**Files:**
- Create: `database/migrations/2026_05_25_120000_create_lesson_sessions_table.php`
- Create: `database/migrations/2026_05_25_120001_create_lesson_session_tasks_table.php`
- Create: `database/migrations/2026_05_25_120002_create_lesson_session_participants_table.php`
- Create: `database/migrations/2026_05_25_120003_create_lesson_session_attempts_table.php`
- Create: `app/Models/LessonSession.php`
- Create: `app/Models/LessonSessionTask.php`
- Create: `app/Models/LessonSessionParticipant.php`
- Create: `app/Models/LessonSessionAttempt.php`

**Steps:**
1. Миграции по схеме выше. FK на `users`, `lesson_schedule` с `nullOnDelete()` для schedule_id.
2. Индексы: `(status, starts_at)` на sessions; `(student_id, lesson_session_id)` на attempts.
3. Eloquent-модели с relations: `LessonSession::tasks() / participants() / attempts() / teacher() / schedule()`.
4. `LessonSession::status` enum-кастом, payload JSON-кастом.
5. Запустить `php artisan migrate` локально, проверить структуру.

**Definition of done:** `php artisan migrate` проходит чисто, `php artisan tinker` — `LessonSession::create([...])` работает.

---

### Task 2: TaskBankResolver — унифицированный доступ к 5 банкам

**Files:**
- Create: `app/Services/TaskBankResolver.php`
- Create: `tests/Unit/TaskBankResolverTest.php`

**Steps:**
1. Один публичный метод: `resolve(string $bank, array $refs): array` — возвращает `{expression, type, answer, options?, source_label}`.
2. Внутри роутинг по `$bank`:
   - `oge` → `TaskDataService::getTaskById($topicId, $taskId)`
   - `ege` → `EgeTaskDataService::...`
   - `vpr` → `VprTaskDataService::...` (с grade)
   - `alg-topic` → `AlgTaskDataService::getTaskById(...)`
   - `alg-skill` → `AlgTaskDataService::getSkillBySlug(...)` + поиск задачи по id/level
3. Фильтр по типу: в v1 принимаем только `expression` и `choice` (для choice — extract `options`/`variants` из payload). Throw `\DomainException` если тип не поддержан.
4. Тесты на каждый банк (1 happy path + 1 unsupported type).

**Definition of done:** Resolver достаёт задачу из любого из 5 банков, тесты зелёные.

---

### Task 3: LessonSessionService — бизнес-логика сессии

**Files:**
- Create: `app/Services/LessonSessionService.php`
- Create: `tests/Unit/LessonSessionServiceTest.php`

**Steps:**
1. `createFromSchedule(LessonSchedule $slot): LessonSession` — создаёт draft + переносит student_id из `lesson_schedule` в participants (source=schedule).
2. `createAdhoc(User $teacher): LessonSession` — без schedule, генерит `invite_token` (16-char random).
3. `addTask(LessonSession $s, string $bank, array $refs): LessonSessionTask` — через TaskBankResolver, кладёт денормализованный snapshot.
4. `removeTask(LessonSessionTask $task)` — только если status=draft.
5. `start(LessonSession $s)` — переход draft→live, прокидывает `starts_at = now()`.
6. `end(LessonSession $s)` — live→ended.
7. `joinByToken(string $token, User $student): LessonSession` — добавляет participant с source=invite, если status=live.
8. `submitAnswer(LessonSession $s, User $student, LessonSessionTask $task, string $answer)` — upsert attempt, прогоняет через `TaskAnswerResolver`.

**Definition of done:** Сервис покрыт unit-тестами на каждый метод; нет прямых обращений к моделям из других сервисов мимо него.

---

### Task 4: Teacher API endpoints

**Files:**
- Create: `app/Http/Controllers/Api/Pwa/LessonSessionTeacherController.php`
- Edit: `routes/pwa.php`

**Endpoints (все под middleware `auth` + `role:teacher,admin`):**
- `POST   /api/pwa/teacher/lessons` — создать сессию (body: `{schedule_id?}`)
- `POST   /api/pwa/teacher/lessons/{id}/tasks` — добавить задачу (body: `{bank, refs}`)
- `DELETE /api/pwa/teacher/lessons/{id}/tasks/{taskId}`
- `POST   /api/pwa/teacher/lessons/{id}/start`
- `POST   /api/pwa/teacher/lessons/{id}/end`
- `GET    /api/pwa/teacher/lessons/{id}/state` — полный state для polling: participants + tasks + attempts grid

**Steps:**
1. Контроллер тонкий — делегирует в `LessonSessionService`.
2. `state` отдаёт компактный JSON: `{session, tasks:[{id,position,payload,answer}], grid: {studentId: {taskId: {answer, is_correct, answered_at}}}}`.
3. Авторизация: учитель видит только свои сессии (`teacher_id === auth()->id()`).
4. Feature-тесты на 200/403/404 для каждого endpoint.

**Definition of done:** `php artisan route:list | grep lessons` показывает все 6 роутов; feature-тесты зелёные.

---

### Task 5: Student API endpoints + invite join

**Files:**
- Create: `app/Http/Controllers/Api/Pwa/LessonSessionStudentController.php`
- Create: `app/Http/Controllers/LessonJoinController.php` (web, не API — для редиректа после клика по ссылке)
- Edit: `routes/pwa.php`, `routes/web.php`

**Endpoints:**
- `GET  /api/pwa/student/lessons/active` — есть ли сейчас live-сессия у текущего student (по participants)
- `GET  /api/pwa/student/lessons/{id}/state` — задачи + свои ответы (без `correct_answer`, без чужих ответов)
- `POST /api/pwa/student/lessons/{id}/answer` — `{task_id, answer}`
- `GET  /lesson/join/{token}` — `LessonJoinController@join`: вызывает `joinByToken`, редиректит на student PWA lesson page

**Steps:**
1. Авторизация: student должен быть в `participants` (или попасть через `joinByToken`).
2. `state` для student обязан скрывать `correct_answer` (фильтр в сериализаторе).
3. Throttling: `submitAnswer` под `throttle:60,1` на student_id+task_id.
4. Feature-тесты: попытка ответить чужому student → 403; submit после `end` → 422.

**Definition of done:** Student может присоединиться по ссылке, отправить ответ, увидеть только свои данные.

---

### Task 6: Teacher PWA — prep screen (task picker)

**Files:**
- Create: `app/Http/Controllers/Pwa/TeacherLessonController.php`
- Create: `resources/views/pwa/teacher/lesson-prep.blade.php`
- Edit: `resources/views/pwa/teacher/lessons.blade.php` (кнопка «Открыть урок» на слоте)
- Edit: `routes/pwa.php`

**Steps:**
1. Роут `GET /pwa/teacher/lessons/{id}` → `TeacherLessonController@show` (когда session.status в draft|live).
2. Экран prep:
   - Список выбранных задач (drag-n-drop сортировка, удаление).
   - Кнопка «Добавить задачу» открывает picker.
   - Кнопка «Запустить урок» (draft→live), кнопка «Завершить».
3. Task picker (модалка):
   - Селектор банка (5 кнопок).
   - Зависимые селекты: класс / тема / навык / уровень.
   - Список задач банка (используем reuse существующих DataService) с чекбоксами.
4. Alpine.js для интерактивности; AJAX в Teacher API.

**Definition of done:** Учитель может зайти на свой слот в `/pwa/teacher/lessons` → жмёт «Открыть урок» → собирает 5 задач из alg-skills + 2 из ОГЭ → стартует урок.

---

### Task 7: Teacher PWA — live view (грид ответов)

**Files:**
- Edit: `resources/views/pwa/teacher/lesson-prep.blade.php` (или вынести в `lesson-live.blade.php`)

**Steps:**
1. Когда session.status === live, экран показывает таблицу:
   - Строки — ученики из `participants`.
   - Столбцы — задачи по `position`.
   - В клетке: ответ ученика + бадж ✓/✗ (или «—» если пусто).
2. Под таблицей — мини-сводка по задачам (% правильных, кто не ответил).
3. JS: `setInterval(fetchState, 4000)`. На фокусе/visibility — приостановка polling.
4. Корректный ответ показан в заголовке столбца (только для учителя).

**Definition of done:** Учитель видит таблицу N учеников × M задач, она обновляется ≤4 сек после ответа ученика.

---

### Task 8: Student PWA — «Урок сейчас» tile + lesson page

**Files:**
- Edit: `resources/views/pwa/student/dashboard.blade.php` (большая кнопка «Урок» условная)
- Create: `app/Http/Controllers/Pwa/StudentLessonController.php`
- Create: `resources/views/pwa/student/lesson.blade.php`
- Edit: `routes/pwa.php`

**Steps:**
1. На dashboard student PWA добавить компонент `<x-lesson-tile>`:
   - Вызов `GET /api/pwa/student/lessons/active` при загрузке + каждые 30 сек.
   - Если есть live-сессия — показать большую кнопку «УРОК» (accent, full-width).
2. Lesson page (`/pwa/student/lessons/{id}`):
   - Список задач по `position`.
   - Каждая задача — карточка: expression + `<input>` (для expression) или radio (для choice).
   - На blur/submit — POST в `/api/pwa/student/lessons/{id}/answer`.
   - Индикатор «отправлено» (бледный), **без** ✓/✗ — ученик не должен знать правильность.
   - Polling каждые 5 сек — на случай если учитель добавил задачу в live (если разрешим).
3. Стиль — как существующий student PWA (тёмная тема, Tailwind/Alpine).

**Definition of done:** Когда учитель стартовал урок, ученик заходит в PWA → видит кнопку → попадает на страницу → вводит ответы → учитель видит их.

---

### Task 9: Invite link UI + share

**Files:**
- Edit: `resources/views/pwa/teacher/lesson-prep.blade.php`
- Edit: `app/Http/Controllers/LessonJoinController.php`

**Steps:**
1. На prep-экране при `status=live` показывать блок «Ссылка для приглашения»:
   - `https://palomatika.ru/lesson/join/{invite_token}`
   - Кнопка «Скопировать», кнопка «Открыть в WhatsApp» (`wa.me/?text=...`).
2. `LessonJoinController@join`:
   - Если guest → редирект на login с `?intended=/lesson/join/{token}`.
   - Если student → `LessonSessionService::joinByToken` → редирект в student PWA `/pwa/student/lessons/{id}`.
   - Если teacher/parent → 403 с понятным экраном.
3. Edge case: токен не найден → 404 «Урок не найден или завершён».

**Definition of done:** Учитель копирует ссылку, отправляет в WhatsApp; новый ученик переходит, логинится, попадает в урок.

---

### Task 10: Auto-close + cron + integration test

**Files:**
- Create: `app/Console/Commands/LessonSessionsAutoCloseCommand.php`
- Edit: `app/Console/Kernel.php`
- Create: `tests/Feature/LessonSessionFlowTest.php`

**Steps:**
1. Команда `lesson-sessions:auto-close`: закрывает live-сессии где `starts_at < now() - 3 hours` (защита от забытых).
2. Schedule в `Kernel.php`: `->everyFifteenMinutes()->withoutOverlapping()`.
3. Integration test полного флоу:
   - Учитель создаёт сессию из расписания.
   - Добавляет 1 alg-skills + 1 ОГЭ + 1 choice задачу.
   - Стартует.
   - 2 ученика логинятся, отвечают (1 правильно, 1 неправильно).
   - Учитель видит грид с корректным is_correct.
   - Учитель завершает.
   - Повторный submit → 422.

**Definition of done:** Cron установлен; integration test зелёный.

---

## После v1 (вне этого плана)

- Типы: matching, statements, geometry
- Live-режим с «текущей задачей» (синхронный класс)
- Индивидуальные задания на ученика внутри одного слота
- Push-уведомления ученикам при старте урока
- История уроков ученика + отчёт учителю
- WebSocket вместо polling (если polling упрётся в нагрузку)

---

## Координация

- Каждая Task = одна карточка на Agent Board (project=palomatika, column=unassigned).
- Зависимости: Task 1 блокирует все остальные; Task 2 нужен для Task 3,6,7; Task 3 нужен для Task 4,5; Task 4,5 нужны для Task 6,7,8.
- Можно брать параллельно: после Task 1 — Task 2 и Task 3 в параллели; после Task 4 — Task 5 + Task 6 в параллели.
- Перед стартом каждой Task — обновить статус карточки на Agent Board (взять из unassigned в claude/codex).
- После завершения — перенести в completed с кратким summary.
