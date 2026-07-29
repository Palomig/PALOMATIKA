# Lessons — живой урок

> Дата создания модуля: 2026-05-26, обновлён 2026-07-16 (урок v2: код входа, лок, picker как база заданий). Активный модуль. Источник истины — код, эта карта — навигация.

## Статус

**На проде** с 2026-05-25 (v1). **v2** (ветка `claude/lesson-v2`, 2026-07-16): вход по 4-значному коду вместо инвайт-ссылок, лок ученика на 60 минут, выбор заданий «как база заданий». Дизайн/план: `docs/plans/2026-07-16-lesson-v2-*.md`.

## Бизнес-смысл

Учитель ведёт живой урок: собирает пул задач (ОГЭ part1/part2/новые, alg-skill 7/8), 1–N учеников решают одновременно, учитель видит ответы и автоматическую проверку (✓/✗) в реальном времени. Ученики НЕ видят правильность своих ответов — это инструмент диагностики, не тренажёра.

**Вход в урок — только по коду (v2):** при создании сессии генерируется уникальный (среди draft/live) 4-значный `join_code`, учитель видит его крупно на prep-странице и диктует ученикам. Ученик вводит код в модале плитки «УРОК» на дашборде → `POST /lessons/join` → страница урока. Инвайт-ссылки `/lesson/join/{token}` и автодобавление участников из слота расписания **упразднены** (`LessonJoinController` удалён; `invite_token` колонка осталась в БД, но не заполняется). Вход разрешён в draft и live (в draft ученик ждёт старта); после `ended` код освобождается.

**Лок 60 минут (v2):** при входе по коду участнику ставится `locked_until = now()+60min`. Пока лок активен (урок не `ended`, не отпущен, время не вышло) ученик не может пользоваться другими страницами student PWA — middleware редиректит на страницу урока (JSON-запросы → 423 `{error:'lesson_lock'}`). Лок снимается: по истечении 60 минут, при завершении урока, или вручную учителем (кнопка «✕ отпустить» у имени ученика). Повторный вход НЕ продлевает лок. На странице ученика — таймер `mm:ss` и бейдж «Учитель отпустил».

## Архитектура

```
lesson_sessions                    ← состояние урока + join_code (char 4, nullable)
  ├── lesson_session_tasks         ← снимки задач из банков (denormalized payload)
  ├── lesson_session_participants  ← кто участвует (source=code) + locked_until/released_at/released_by
  └── lesson_session_attempts      ← ответы (UPSERT по session+student+task)
```

Lifecycle: `draft` → `live` → `ended`. Миграции v2: `2026_07_16_000001` (join_code, source enum +'code'), `2026_07_16_000002` (поля лока).

## Ключевые файлы

| Файл | Назначение |
|---|---|
| `app/Services/LessonSessionService.php` | Лайфсайкл: createFromSchedule/createFromEvriumSlot/createAdhoc/addTask/start/end/**joinByCode**/**activeLockFor**/**release**/submitAnswer. `LOCK_MINUTES=60`, `JOIN_CODE_STATUSES=[draft,live]`. **Контроллеры обязаны ходить только через этот сервис.** |
| `app/Services/TaskBankResolver.php` | Унифицированный доступ к 5 банкам: `resolve(bank, refs)`. Резолвит и задачи без эталонного ответа (answer='') — на уроке они без авточека (`is_correct=null`). |
| `app/Services/LessonTaskPickerService.php` | Данные для picker'а: `sections('oge')` → part1/part2/new, `topics(bank, grade, section)`, `tasks(bank, refs, section)`. «Новые задания» = zadanie с `number 0` и `label 'Новые задания'`; для part2 разрешены задачи без answer (тема 24 — доказательства). |
| `app/Http/Middleware/EnforceLessonLock.php` | Лок навигации ученика (alias `pwa.lesson-lock` на student-группе; разрешены роуты урока + logout + bug-report). |
| `app/Http/Controllers/Pwa/TeacherLessonController.php` | Teacher API + prep view; `pickerOptions` (c `section`), `release` |
| `app/Http/Controllers/Pwa/StudentLessonController.php` | Student API: `join` (по коду), `state` (с блоком `lock`), `answer`, страница урока |
| `app/Console/Commands/LessonSessionsAutoCloseCommand.php` | Cron: каждые 15 мин закрывает live сессии где `starts_at < now()-3h` |
| `resources/views/pwa/_shared/task-picker.blade.php` | Общий picker v2 «как база заданий» (см. ниже) |
| `resources/views/pwa/teacher/lesson-prep.blade.php` | Prep + live grid (Alpine, polling 4с и в draft, и в live); код урока 48px; чипы участников с «отпустить» |
| `resources/views/pwa/student/lesson.blade.php` | Страница урока (KaTeX, polling 5с, таймер лока) |
| `resources/views/pwa/student/partials/lesson-tile.blade.php` | Плитка «УРОК» на dashboard: всегда видна; live-сессия → открыть, иначе модал ввода кода |

## Планирование уроков (2026-07-17)

- **«Начать новый урок»** на `/lessons` спрашивает день и время (модал, по умолчанию сегодня/сейчас) → `POST /lessons {starts_at}`; урок появляется в списке уроков в свой день (внеплановые сессии мержатся в дни рядом с Evrium-слотами, дни без слотов тоже показываются).
- **«Следующий урок»** на prep-странице → `POST /lessons/{id}/next` → идемпотентный черновик на то же время через неделю (через `createFollowUp`/`createFromEvriumSlot`); туда заранее пишется заметка и добавляются задания.
- **Заметка учителя** — `lesson_sessions.note` (миграция `2026_07_17_000001`), `POST /lessons/{id}/note`, автосейв textarea на prep; ученикам не отдаётся; сниппет виден в списке уроков.

## AI-ассистент (2026-07-17)

Чат-ассистент на странице урока (заменил блок «Заметка») фиксирует наблюдения об учениках в `student_notes` через DeepSeek function-calling; кнопка «не понимает» в live-гриде пишет запись без LLM. Детали — модуль [assistant](assistant.md).

## Активность ученика (2026-07-17)

Таблица `lesson_activity_intervals` (миграция `2026_07_17_000002`): непрерывный таймлайн `present`/`away` на пару (session, student). Клиент на странице урока шлёт `POST /lessons/{id}/activity {visible}` на `visibilitychange`, heartbeat 10с (пока видима вкладка) и `navigator.sendBeacon` на `pagehide`. Сервер (`LessonSessionService::recordActivity`) ставит время сам и держит максимум один открытый интервал; `activitySummary()` даёт по ученику `{state, away_count, away_seconds, present_seconds}` (present без heartbeat >25с = ученик молча ушёл → state `away`). Учитель видит в live-гриде и чипах 🟢/🔴 + «отходил N× · вне X · на странице Y» (данные в teacher `state().participants[].activity`). Роут разрешён под lesson-lock. TODO: визуальный таймлайн-бар после урока (интервалы уже пишутся).

## Персональные задания (2026-07-17)

`lesson_session_tasks.assigned_student_id` nullable (миграция `2026_07_17_000004`): null = задача всем, id = персональная (видит/решает только этот ученик). `addTask(..., ?assignedStudentId)`, `LessonSessionTask::visibleTo(studentId)`; `submitAnswer` отклоняет чужую персональную; student `state()` фильтрует по видимости и нумерует последовательно (+`personal` флаг). Teacher `POST /lessons/{id}/tasks` принимает `assigned_student_id` (валидируется как участник), `state()` отдаёт `assigned_student_id`/`assigned_name`. UI: в picker-overlay селектор «Кому: Всем классу / <ученик>»; бейдж «для Имя» в списке и заголовке грида; ячейки не-назначенных учеников — серый «·» (`live-cell-na`); у ученика бейдж «персональная». Добавлять задачи (общие и персональные) можно и в live.

## Picker v2 — «как база заданий» (общий с ДЗ)

Флоу: **класс** (7/8/9 ОГЭ; «9 ОГЭ» выбран по умолчанию) → для 9: **разделы** (1я часть / 2я часть / Новые задания) → **пилюли тем** (номера) → **спойлеры-задания** (`group_label` + счётчик) → карточки задач (SVG/картинка/текст/формула + ответ; «без автопроверки» при пустом answer). Кнопка «Выбрать блок» в спойлере — toggle всех задач блока. Для 7/8 — прежний drill-down по навыкам. **Корзина глобальная**: переживает смену класса/раздела/темы; sticky-панель «Выбрано N · Добавить». Контракт `taskPicker({onAdd, existingUids})` не менялся — используется на уроке и в ДЗ.

Для ОГЭ темы 6–25 импортируются в курируемом учебном порядке по точным
GUID-картам `resources/task-taxonomies/oge-topic-NN.php`. Поэтому
`group_label` и порядок спойлеров в picker совпадают с банком ученика:
раздел знаний → метод решения → задачи. Повторный импорт безопасен только
при полном совпадении карты с выгрузкой; расхождение останавливает команду
до изменения банка. Задачи второй части без ответа (доказательства темы 24)
по-прежнему разрешены в уроке без автоматической проверки.

Бэкенд: `GET /lessons/picker-options?bank=&section=&topic_id=` → `{grades, sections, topics, tasks}`; невалидный section → 422.

## Маршруты

**teacher.palomatika.ru** (auth + role:teacher,admin):
- `POST   /lessons` — создать сессию (body: `{schedule_id?}`)
- `POST   /lessons/from-slot` — из Evrium-слота
- `GET    /lessons/picker-options` — опции picker'а (bank, section, topic_id, …)
- `GET    /lessons/{id}` — prep+live screen
- `GET    /lessons/{id}/state` — JSON snapshot (participants с `locked`)
- `POST   /lessons/{id}/tasks` / `DELETE /lessons/{id}/tasks/{taskId}`
- `POST   /lessons/{id}/start` / `POST /lessons/{id}/end`
- `POST   /lessons/{id}/participants/{studentId}/release` — снять лок вручную

**student.palomatika.ru** (auth + pwa.onboarding + pwa.lesson-lock):
- `POST /lessons/join` — вход по коду (body: `{code}`; 422 при неверном)
- `GET  /lessons/active` — есть ли live-сессия у текущего student
- `GET  /lessons/{id}` — страница урока (HTML)
- `GET  /lessons/{id}/state` — JSON (БЕЗ correct_answer; + блок `lock`)
- `POST /lessons/{id}/answer` — submit

## Инварианты безопасности

1. Teacher endpoints проверяют `teacher_id === auth()->id()` (даже admin не лезет в чужую сессию); release чужой сессии → 403.
2. Student endpoints проверяют участие через `LessonSessionService::isParticipant`.
3. `task_payload` хранит `answer`, но student `state` явно `unset payload[answer]`/`payload[raw]`.
4. Чужие ответы student'у не возвращаются (только `my_answer`); `is_correct` ученику не отдаётся.
5. Лок enforce'ится сервером (middleware), не только UI.

## Поддерживаемые типы задач

- `expression` (включая нормализованные word_problem/geometry) — input text → `TaskAnswerResolver::isCorrect`; при `correct_answer=''` (доказательства темы 24) — `is_correct=null`, учитель оценивает по ответу сам.
- `choice` — radio с `options[{id,label}]`.

## Тесты

`LessonTaskPickerServiceTest` (unit), `TaskBankResolverTest` (unit), `LessonSessionServiceTest` (unit, join/lock/release), `TeacherLessonControllerTest`, `StudentLessonControllerTest`, `LessonLockTest`, `LessonSessionFlowTest` (e2e код-флоу). Все на `palomatika_test` MySQL.

## Не сделано / открытое

- **Ввод ответа для 2-й части** — отдельный способ (не текстовое поле) на проработке: заметка в Obsidian `PALOMATIKA/Задачи/Ввод ответа для 2 части на уроке.md`.
- `invite_token` — мёртвая колонка в `lesson_sessions` (не заполняется), можно дропнуть отдельной миграцией.
- ЕГЭ/ВПР-банки в picker'е урока не выведены (bank поддержан бэкендом, UI — только 7/8/9 ОГЭ).
- Push-уведомления при старте; история уроков ученика; WebSocket вместо polling.
- Превью-пример у темы 24 в списке тем пустой (firstTopicExample не знает section).
