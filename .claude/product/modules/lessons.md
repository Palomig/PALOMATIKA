# Lessons — живой урок

> Дата создания модуля: 2026-05-26. Активный модуль. Источник истины — код, эта карта — навигация.

## Статус

**На проде** с 2026-05-25. Минимальный v1 без UI-обвязки на основе расписания (Evrium-слотов).

## Бизнес-смысл

Учитель ведёт живой урок: собирает пул задач из любого банка (ОГЭ/ЕГЭ/ВПР/alg-topic/alg-skill), 1–N учеников решают одновременно, учитель видит ответы и автоматическую проверку (✓/✗) в реальном времени. Ученики НЕ видят правильность своих ответов — это инструмент диагностики, не тренажёра.

**Два сценария старта:**
1. **Schedule-based** — учитель создаёт сессию из своего `lesson_schedule` слота (ученик из слота автоматически в `participants`).
2. **Ad-hoc** — учитель создаёт сессию «с нуля», получает `invite_token`, рассылает ссылку `https://palomatika.ru/lesson/join/{token}` (WhatsApp/Telegram).

В обоих сценариях у сессии есть `invite_token` — на регулярный урок можно дозвать гостя/замену.

## Архитектура

```
lesson_sessions                    ← состояние урока
  ├── lesson_session_tasks         ← снимки задач из банков (denormalized payload)
  ├── lesson_session_participants  ← кто участвует (schedule|invite)
  └── lesson_session_attempts      ← ответы (UPSERT по session+student+task)
```

Lifecycle: `draft` → `live` → `ended`.

## Ключевые файлы

| Файл | Назначение |
|---|---|
| `app/Services/LessonSessionService.php` | Бизнес-логика lifecycle (createFromSchedule/createAdhoc/addTask/start/end/joinByToken/submitAnswer). **Контроллеры обязаны ходить только через этот сервис.** |
| `app/Services/TaskBankResolver.php` | Унифицированный доступ к 5 банкам: `resolve(bank, refs)` → `{expression, type, answer, options?, source_label, raw}`. V1 поддерживает только `expression` и `choice`. |
| `app/Models/LessonSession.php` + 3 связанных | Eloquent-модели |
| `app/Http/Controllers/Pwa/TeacherLessonController.php` | Teacher API + prep view (`GET /lessons/{id}`) |
| `app/Http/Controllers/Pwa/StudentLessonController.php` | Student API + lesson view |
| `app/Http/Controllers/LessonJoinController.php` | `/lesson/join/{token}` (guest→login, student→PWA, teacher→403) |
| `app/Console/Commands/LessonSessionsAutoCloseCommand.php` | Cron: каждые 15 мин закрывает live сессии где `starts_at < now()-3h` |
| `resources/views/pwa/teacher/lesson-prep.blade.php` | Prep + live grid (Alpine.js, polling 4с) |
| `resources/views/pwa/student/lesson.blade.php` | Страница урока (KaTeX, polling 5с) |
| `resources/views/pwa/student/partials/lesson-tile.blade.php` | Большая зелёная кнопка «УРОК» на dashboard (polling 30с) |

## Маршруты

**teacher.palomatika.ru** (auth + role:teacher,admin):
- `POST   /lessons` — создать сессию (body: `{schedule_id?}`)
- `GET    /lessons/{id}` — prep+live screen
- `GET    /lessons/{id}/state` — JSON snapshot для polling
- `POST   /lessons/{id}/tasks` — добавить задачу (body: `{bank, refs}`)
- `DELETE /lessons/{id}/tasks/{taskId}`
- `POST   /lessons/{id}/start`
- `POST   /lessons/{id}/end`

**student.palomatika.ru** (auth + pwa.onboarding):
- `GET  /lessons/active` — есть ли live-сессия у текущего student
- `GET  /lessons/{id}` — страница урока (HTML)
- `GET  /lessons/{id}/state` — JSON (БЕЗ correct_answer, только свои ответы)
- `POST /lessons/{id}/answer` — submit (body: `{task_id, answer}`)

**palomatika.ru** (public):
- `GET /lesson/join/{token}` — обработка инвайт-ссылки

## Инварианты безопасности

1. Teacher endpoints проверяют `teacher_id === auth()->id()` (даже admin не лезет в чужую сессию).
2. Student endpoints проверяют участие через `LessonSessionService::isParticipant`.
3. `LessonSessionTask::task_payload` хранит `answer`, но Student `state` явно `unset payload[answer]` и `payload[raw]` — ученик ответа НЕ видит.
4. Чужие ответы student'у не возвращаются (только `my_answer`).
5. `is_correct` НЕ возвращается ученику ни в state, ни в ответе на submit.

## Поддерживаемые типы задач (v1)

- `expression` — input text → `TaskAnswerResolver::isCorrect`
- `choice` — radio с `options[{id, label}]`, ответ = id опции

Прочие типы (geometry, matching, statements, …) бросают `DomainException` в `TaskBankResolver::normalize`.

## Тесты

- `tests/Unit/TaskBankResolverTest.php` — 7
- `tests/Unit/LessonSessionServiceTest.php` — 11
- `tests/Feature/TeacherLessonControllerTest.php` — 10
- `tests/Feature/StudentLessonControllerTest.php` — 11
- `tests/Feature/LessonSessionFlowTest.php` — 4 (полный e2e + 3 на auto-close)

**Всего: 43 теста** (145 assertions). Все на `palomatika_test` MySQL.

## План реализации

Полный план в `docs/plans/2026-05-25-lesson-feature-implementation-plan.md`. Все 10 задач (#55–#64 на Agent Board) выполнены и в проде.

## Что не сделано в v1 (next iterations)

- **Полноценный picker для oge/ege/vpr/alg-topic** — сейчас raw refs форма (учитель вводит руками). Каскадный picker — только для alg-skill 7.
- **UI-привязка к Evrium слотам** — у слотов нет `lesson_schedule.id`, не подцепить «открыть урок по слоту». Сейчас одна общая кнопка «🎯 Начать новый урок».
- **Расширение типов** — geometry, matching, statements, word_problem.
- **Push-уведомления** ученикам при старте.
- **Bundle alg-skills (1.9MB JSON)** инлайнится в prep page (~3.5MB HTML). Перенести в отдельный endpoint.
- **WebSocket вместо polling** — если нагрузка polling упрётся.
- **История уроков** ученика + отчёт учителю после `end`.
