# Домашка (Homework)

> Дата скана: 2026-05-07 (после редизайна #44). Активный модуль в разработке. Источник истины — код, эта карта — навигация.

## Уведомления ученику о ДЗ — Фаза 1 (2026-07-23)

- **`StudentNotifier`** (`app/Services/StudentNotifier.php`) — `notify(User, text, url)`; телеграм-канал (нужен `users.telegram_chat_id`), `Http` фасад (fakeable). Web push — Фаза 2 (заглушка).
- **Новое ДЗ** → `TeacherController::notifyNewHomework()` во ВСЕХ трёх ветках `assignHomework` (picker, тема без picker, мини-вариант); `notified_at` ставится только при успешной доставке, 403 помечает `users.telegram_blocked_at`.
- **Напоминание о сроке** → команда `homework:remind-deadlines` (планировщик `dailyAt 08:00`), ДЗ со сроком сегодня/завтра, `status != completed`, дедуп `reminded_at`.
- **In-app поп-ап** (раз в день) → `HomeworkPopupComposer` на `layouts.pwa`, партиал `pwa/shared/homework-popup.blade.php`; частота через `users.homework_popup_shown_on`; скип на homework/lesson-страницах.
- Новые поля: `homework_assignments.notified_at`/`reminded_at`, `users.homework_popup_shown_on` (миграция `2026_07_23_000002`).
- Предусловие: `assignFromPicker` сохраняет `deadline` → `homeworks.deadline_at`.
- Тесты: `StudentNotifierTest`, `HomeworkPopupTest`, `RemindHomeworkDeadlinesTest`, `PwaHomeworkPhotoPracticeTest::test_assign_notifies_*`, `HomeworkNotificationDeliveryTest`.

## Привязка телеграма и склейка аккаунтов (2026-07-25)

Уведомления упирались в идентичность: OIDC-вход («Войти через Telegram») кладёт в
`oauth_id` псевдоним `sub` (19–20 цифр), а не chat_id, — бот такому аккаунту писать не может.

- **Разделение ключей:** `users.telegram_chat_id` (настоящий id, только из initData мини-аппа
  и `/start` в боте) и `users.telegram_oidc_sub` (псевдоним, только для узнавания при входе).
  Миграция `2026_07_25_000001` + бэкфилл по длине старого `oauth_id`.
- **`TelegramIdentityResolver`**: `resolveByChatId()` для мини-аппа/бота, `resolveBySub()` для OIDC.
- **Привязка** (`TelegramLinkService` + `Pwa\TelegramLinkController`, экран `/link-telegram`):
  веб-сессия выдаёт одноразовый код → `t.me/<bot>?start=link_<code>` → вебхук бота знает
  и код, и настоящий id. Ученикам без chat_id экран показывается middleware `pwa.telegram-link`,
  но это не блок: кнопка «Напомнить позже» ставит `users.telegram_link_snoozed_until` на сутки,
  а JSON-ручки и не-GET запросы не редиректятся вообще (миграция `2026_07_25_000002`).
- **Склейка:** если chat_id уже принадлежит другой записи — это один человек, `AccountMergeService`
  переносит данные по 29 таблицам, донор получает `merged_into_id`. Канонический — по роли,
  при равных ролях по возрасту записи. Вручную: `php artisan users:merge {from} {into} [--dry-run]`.
- Сводка охвата: `php artisan users:telegram-status [--unlinked]`.
- Со страницы входа PWA убрана старая кнопка «Telegram» (бот-Start) — её роуты
  (`/api/telegram/generate-token`) удалены ещё раньше, кнопка вела в 404.
- Тесты: `TelegramIdentityResolverTest`, `TelegramUnifiedIdentityTest`, `TelegramLinkGateTest`.

## Домашка по итогам урока (2026-07-23, ветка `claude/lesson-v2`)

Кнопка «📚 Домашка по уроку» на учительской странице урока (`lesson-prep.blade.php`)
предлагает аналоги разобранных задач; учитель отмечает нужные и отправляет участникам.

- **`homeworks.lesson_session_id`** (nullable FK на `lesson_sessions`, миграция `2026_07_23_000001`) — связь ДЗ с уроком, для плашки «уже отправлялось».
- **`LessonHomeworkSuggestionService::suggestionsFor()`** — группирует задачи урока по заданию (`oge/ege/vpr/alg-topic`: тема+номер; `alg-skill`: навык+уровень), подбирает неиспользованные аналоги из JSON-банка, превью через `TaskBankResolver`. Флаг `no_analogs`, счётчик `solved`.
- **`GET /lessons/{id}/homework-suggestions`** (`TeacherLessonController::homeworkSuggestions`) → `{groups, participants, other_students, prior_homeworks}`. Владелец урока только (403 чужому).
- **Отправка** — через существующий `POST /homework/assign` (`assignFromPicker`): `picker_tasks` + `student_ids` + новые `lesson_session_id` (только свой урок) и `title`. Участники своего урока авторизованы как получатели даже без `TeacherStudent` (вошли по коду).
- Тип ДЗ — `topic_photo_practice`, ученический флоу без изменений.
- Тесты: `LessonHomeworkSuggestionServiceTest`, `TeacherLessonControllerTest::test_homework_suggestions_*`, `PwaHomeworkPhotoPracticeTest::test_assign_from_*`.

## Статус

**На проде, в самом начале использования.** Текущие данные:

| Таблица | Rows | Что |
|---|---|---|
| `homeworks` | 2 | 1 photo-practice (тема 16, 10 задач), 1 mini-variant (`yyzaw2q0um`) |
| `homework_assignments` | 2 | назначения ученикам |
| `homework_topic_tasks` | 10 | задачи внутри photo-practice ДЗ |
| `homework_topic_task_submissions` | 0 | никто ещё не сдавал |

> Старая схема `homework_tasks` + колонка `homeworks.topic_id` дропнуты в #44/#46 вместе с пазлами. Все активные ДЗ работают через новую схему (`homework_topic_tasks` + `homework_assignments`).

Активная разработка идёт на ветке `claude/pwa-migration`, последние коммиты по homework:
- `31e97a1` — рендер SVG/image в topic photo-practice
- `9cdd979` — Mini-VPR для 5–8 классов на mini variant homework
- `684c14e` — переработка assign flow: mini variant + task picker + photo modal
- `e725f27` — добавлен photo-based topic homework
- `1a27912` — интеграция Evrium schedule + UI привязки учеников

## Бизнес-смысл

Учитель назначает ДЗ ученику или группе учеников. Ученик в PWA видит назначенные задачи и решает. Фото-практика: ученик прикрепляет **фото решения** + вписывает **ответ** — система автоматически проверяет ответ, фото остаётся для учителя.

## Типы ДЗ (поле `homeworks.homework_type`)

ENUM из `2026_03_12_100100_add_homework_variant_and_topic_types`. Активные типы:

| Тип | Когда добавлен | Активность сейчас |
|---|---|---|
| `full_variant` | mar 2026 | ✅ активен (mini-variant ДЗ) |
| `topic_photo_practice` | apr 2026 | ✅ активен (главный фокус разработки) |

> ENUM также содержит legacy-значения `specific_tasks`/`topic_random`/`weak_skills`/`topic_practice` от старой пазловой схемы. Сами таблицы и код этих типов удалены в #44/#46; ENUM-значения остались в схеме как мёртвый набор и могут быть почищены отдельной миграцией. **В `assignHomework()` (`Pwa/TeacherController:491`) валидация принимает только `mini_variant` и `topic_photo_practice`.**

## Архитектура

### Текущая схема (apr 2026, для `topic_photo_practice`)

```
homeworks
  └── homework_topic_tasks (FK homework_id)
        ├── topic_number (1-25)
        ├── task_order
        ├── task_payload JSON      ← снимок задачи на момент назначения
        └── correct_answer         ← денормализованный эталон
  └── homework_assignments (FK homework_id)
        ├── reviewed_at / reviewed_by   ← «проверено» учителем
        ├── debt_since                  ← работа стала долгом
        └── homework_topic_task_submissions
              ├── attempts_count (0..2)
              ├── first_answer / second_answer
              ├── is_correct
              ├── accepted_at      ← null = не принято
              └── homework_solution_photos   ← страницы решения, до 10 на попытку
                    ├── attempt_no (1..2) / position
                    └── remote_id (hw-photos) ИЛИ path (фолбэк на хостинге)

student_notes.homework_assignment_id   ← заметки учителя по этой домашке
```

**Почему `task_payload` денормализованный** — чтобы ДЗ не сломалось если задача в JSON-банке поменяется/удалится.

### Mini-variant

`homework_type = 'full_variant'` + `variant_hash` (например `yyzaw2q0um`). Использует обычный механизм OGE-вариантов через `OgeVariantBuilderService` (или `VprVariantBuilderService` для 5–8 классов — коммит `9cdd979`).

## Models

| Класс | Файл | Что |
|---|---|---|
| `Homework` | `app/Models/Homework.php` | главное ДЗ (тип, тема, тайтл, deadline) |
| `HomeworkAssignment` | `app/Models/HomeworkAssignment.php` | привязка к ученику + статус (`assigned`/`started`/`completed`) + прогресс |
| `HomeworkTopicTask` | `app/Models/HomeworkTopicTask.php` | задача photo-practice (payload + correct_answer) |
| `HomeworkTopicTaskSubmission` | `app/Models/HomeworkTopicTaskSubmission.php` | сабмишн ученика (ответ + фото) |

## Routes

### Teacher PWA (`teacher.palomatika.ru`)
| Method | Path | Controller |
|---|---|---|
| GET | `/homework` | `TeacherController::homework` — список ДЗ + назначения |
| GET | `/homework/topic-tasks/{topicNumber}` | `TeacherController::topicTasks` — JSON: задачи топика для picker'а |
| POST | `/homework/assign` | `TeacherController::assignHomework` — создание ДЗ + назначения |
| GET | `/homework/assignment/{assignment}` | `TeacherController::homeworkSubmissions` — ответы ученика + фото решений |
| GET | `/homework/submission/{submission}/photo` | `TeacherController::homeworkSolutionPhoto` — отдаёт фото решения (с проверкой доступа) |

### Student PWA (`student.palomatika.ru`)
| Method | Path | Controller |
|---|---|---|
| GET | `/homework` | `StudentController::studentHomework` — список assignments ученика |
| GET | `/homework/{assignment}` | `StudentController::showTopicHomework` — открыть photo-practice ДЗ |
| POST | `/homework/{assignment}/tasks/{homeworkTask}` | `StudentController::submitTopicHomeworkTask` — отправить ответ + `photo_id` (или файл, фолбэк) |
| POST | `/homework/{assignment}/tasks/{homeworkTask}/photo-ticket` | `StudentController::homeworkPhotoTicket` — тикет на прямую загрузку фото в hw-photos |
| POST | `/homework/{assignment}/tasks/{homeworkTask}/photo-log` | `StudentController::homeworkPhotoLog` — след отправки с телефона ученика в канал `hw_photos` |

### Parent
- `parent/child-homework.blade.php` — родитель видит ДЗ ребёнка (детали — в `ParentAppController`)

## Views

- `resources/views/pwa/teacher/homework.blade.php` — учительский экран ДЗ
- `resources/views/pwa/teacher/homework-submissions.blade.php` — проверка: ответы ученика по попыткам + фото решения
- `resources/views/pwa/student/student-homework.blade.php` — список ДЗ ученика
- `resources/views/pwa/student/homework-topic-practice.blade.php` — экран решения photo-practice

## Жизненный цикл photo-practice

1. **Учитель выбирает топик** (UI картирует через `topicOptions` из `TaskDataService::getAllTopicsMeta()`)
2. **Picker задач** — `topicTasks(topicNumber)` возвращает все задачи топика с эталонными ответами
3. **assign** — учитель выбирает учеников (один или массив `student_ids`) и индексы задач (max 60)
4. **Создаётся `homeworks` + `homework_topic_tasks` (snapshot задач) + `homework_assignments` для каждого ученика**
5. **Ученик открывает ДЗ** (`showTopicHomework`) — assignment переходит в `started`
6. **Ученик отправляет ответ + фото** (`submitTopicHomeworkTask`):
   - Ответ нормализуется (lowercase, whitespace removed) и сравнивается с `correct_answer`
   - Решение может занимать несколько страниц: принимаем **до 10 фото на попытку** (`HomeworkSolutionPhoto::MAX_PER_ATTEMPT`)
   - Фото: браузер ужимает каждый снимок до ~1600px/JPEG и **грузит напрямую в сервис hw-photos на VPS** (см. `taskPhotos()` во вью)
   - **Отправка идёт одним `fetch` с JSON `{answer, photo_ids}`** (с 2026-08-07): нажали кнопку → дождались/повторили загрузку страниц → отправили → показали ответ сервера. Нативной отправки формы в основном пути нет
   - Фолбэк (сервис недоступен / нет JS): файлы приходят в Laravel как раньше и сохраняются в `storage/app/public/homework_solutions/{assignment_id}/`. Принимаем до 20 МБ каждый, форматы jpg/png/webp/heic/heif/gif/bmp — **HEIC обязателен, это формат камеры iPhone**
   - Смешанного режима нет: если хоть одна страница не загрузилась в сервис, вся задача уходит файлами — иначе учитель увидит решение кусками
   - При ошибке валидации ответ и `answer_task_id` возвращаются на страницу (плашка по-русски, попытка не тратится)
   - Если ответ верный → `is_correct=true`, `accepted_at=now()`
   - Если неверный, попытка #1 → можно попробовать ещё раз (фото нужно прикреплять снова)
   - На попытке #2 (даже если неверно) → `accepted_at=now()` (учитель проверяет по фото)
7. **`refreshTopicHomeworkProgress`** обновляет статус assignment (`completed` если все принято) и снимает долг, когда работу довели до конца

### Контракт отправки (с 2026-08-07)

Отправка из браузера идёт JSON'ом и получает JSON — раньше это был обычный
POST формы с редиректом, и в Telegram WebView ученик не видел ни редиректа, ни
419/413-заглушки: любой сбой выглядел как тишина, а в базе и `laravel.log` не
оставалось следов. Ответ всегда один и тот же объект:

| Поле | Смысл |
|---|---|
| `ok` | приняли ли отправку |
| `reload` | состояние задачи изменилось, странице надо перечитать себя; сообщение сервер кладёт во флеш, и после перезагрузки его показывает обычная плашка |
| `message` | готовый текст для ученика |
| `code` | `validation` \| `photo_rejected` \| `store_failed` — по `photo_rejected` клиент выбрасывает свои `photo_id` и просит фото заново |

No-JS и фолбэк с файлами по-прежнему получают redirect с флешками — контракт
проверяется тестом `HomeworkPhotoJsonSubmitTest`.

Загруженные `photo_id` клиент держит в `localStorage` (`hw-pages:{assignment}:{task}`,
сутки): после протухшей сессии, обрыва или случайной перезагрузки ученику не надо
переснимать тетрадь. Черновик стирается после успешной отправки.

### Телеметрия отправки

`POST /homework/{assignment}/tasks/{task}/photo-log` — клиент присылает след
(`picked`, `upload_http`, `submit_start`, `submit_http`, `submit_error`, …) и
свой User-Agent; всё уходит в отдельный канал `hw_photos`
(`storage/logs/hw-photos.log`, 14 дней). Половина сценария выполняется на
телефоне, и без этого лога сбой у ученика не оставляет следов вообще нигде —
именно поэтому предыдущие попытки чинили вслепую. **Первое место, куда смотреть
по жалобе «не отправляется».**

## Проверка учителем (с 2026-07-30)

**Страница `/homework` учителя — это доска проверки, а не расписание** (переделана 31.07 по просьбе Стаса). Расписание Evrium и список учеников оттуда убраны; выдача ДЗ живёт на уроке, здесь осталась резервная кнопка «Выдать ДЗ» (та же шторка с picker'ом). Привязки профилей (alias + evrium_name) уехали в свёрнутый блок внизу — **другого места для них в интерфейсе нет**, `/students` их не умеет.

Три вкладки:

| Вкладка | Что показывает |
|---|---|
| **Новые** | плашки «как новое сообщение»: имя (алиас), класс, название ДЗ, «сдано N из M», когда сдано. Условие — есть хотя бы один сабмишн и `reviewed_at IS NULL` |
| **Проверенные** | то же приглушённо, с датой проверки (последние 50) |
| **Статистика** | сдали / не сдали / ждут проверки; по каждому ДЗ полоса «сдали N из M»; «кто не делает» — ученики с ≥2 незакрытыми работами |

Нажатие «Отметить проверенным» на экране проверки **редиректит обратно на доску** — работа исчезает из «Новых». Мини-варианты (`full_variant`) в «Новых»/«Проверенных» не участвуют: их проверять нечего, но в статистику по ДЗ они попадают.

Экран `/homework/assignment/{assignment}` — ответы по попыткам, страницы решения плитками (превью `?w=400`, клик — оригинал), заметки.

- **Заметки** пишутся в ту же таблицу `student_notes`, что и заметки с урока (`source='homework'`, `homework_assignment_id`, `task_ref` = «Задача N»), поэтому сразу видны в карточке ученика. Вид заметки выбирается руками (`weakness`/`todo`/`strength`/`general`) — без LLM-классификации, в отличие от чата на уроке.
- **«Проверено»** — `reviewed_at` + `reviewed_by`, переключается кнопкой.
- **Долг.** Когда учитель выдаёт ученику новое ДЗ, все его незавершённые работы получают `debt_since` (`TeacherController::carryOverUnfinished`, вызывается из `afterHomeworkAssigned` — единая точка для всех трёх путей выдачи). Долг висит у ученика первым в списке с плашкой, снимается автоматически при выполнении или вручную кнопкой у учителя.

## Единый инструмент для всех классов (с 2026-07-31)

Урок и домашка больше не про один только 9 класс:

- **плитка «УРОК»** есть на всех трёх дашбордах ученика — ОГЭ (`dashboard`), ВПР (`vpr-home`, 5–8) и ЕГЭ (`ege-home`, 10–11, живёт на `/ege-app`);
- **вход в домашку** добавлен на ЕГЭ-дашборд (на ВПР и ОГЭ был);
- **мини-вариант** для 10–11 создаётся из банка ЕГЭ (`buildEgePool`), раньше им уходил ОГЭ;
- **классы в picker'е строятся из данных** — `LessonTaskPickerService::availableClasses()` (кэш 12 ч), пустых вкладок не бывает.

Что реально есть в банках на 31.07.2026: ВПР 5 (280 задач) и 6 (198), навыки алгебры 7 класса, ОГЭ 9, ЕГЭ (18 тем). **ВПР 7–8 — пустые заготовки** (`blocks: []`), навыков для 5, 6, 8 нет, поэтому «8 класс» в picker'е не показывается: восьмиклассники работают через 9 ОГЭ (у них для этого есть тумблер на дашборде). Как только задачи появятся — вкладка возникнет сама.

Ловушка данных: у части задач ВПР ответ многочастный (`["в среду", "6"]`). Одной строкой такой ответ не проверить, поэтому `supportedTasks` считает их «без ответа» — в выбор они попадают только там, где автопроверка не нужна. До 31.07 это молча роняло `Array to string conversion`.

## Выбор задач — общий drill-down picker

С 2026-06-21 задачи в ДЗ (тип `topic_photo_practice`) можно набирать через **общий drill-down picker** — тот же компонент, что и на уроке.

- **Партиал:** `resources/views/pwa/_shared/task-picker.blade.php` (Alpine-фабрика `taskPicker({ onAdd, existingUids })`). Шаги: класс → полоски (навыки/темы, с «1 примером») → уровень сложности / блок → карточки задач. Банк скрыт за таблицей `PICKER_CLASSES` (7/8 → `alg-skill`, 9 ОГЭ → `oge`).
- **Бэкенд опций:** `GET /lessons/picker-options` (`LessonTaskPickerService`); полоски несут поле `preview` (+ `preview_svg`).
- **Сохранение ДЗ:** вью шлёт скрытое поле `picker_tasks` = JSON-массив `[{bank, refs}]`; `TeacherController::assignFromPicker()` резолвит каждый через `TaskBankResolver::resolve()` в `homework_topic_tasks` (`task_payload` + `correct_answer`), недоступные пропускает. Легаси-путь `task_indices`/`topic_number` сохранён (additive).
- **`topic_number` для alg-skill:** колонка NOT NULL, темы у навыка нет → пишется `0` (нейтрально: используется только для пути к картинкам ОГЭ/ВПР, а alg-skill задачи формульные/SVG-inline).
- **Follow-up:** дедуп `existingUids` пока no-op (picker `uid` ≠ сохранённый идентификатор). KaTeX на учительской странице ДЗ подключён (@push('katex')).
- **Picker v2 (2026-07-16, ветка `claude/lesson-v2`):** общий партиал переписан под интерфейс «как база заданий» — класс (7/8/9 ОГЭ, 9 по умолчанию) → разделы (1я/2я часть/Новые задания) → пилюли тем → спойлеры-задания с кнопкой «Выбрать блок» → карточки; глобальная корзина переживает навигацию. Контракт `taskPicker({onAdd, existingUids})` не менялся — ДЗ работает без правок своей стороны. Детали — в модуле [lessons](lessons.md).

## Выбор задач — общий drill-down picker

С 2026-06-21 задачи в ДЗ (тип `topic_photo_practice`) можно набирать через **общий drill-down picker** — тот же компонент, что и на уроке.

- **Партиал:** `resources/views/pwa/_shared/task-picker.blade.php` (Alpine-фабрика `taskPicker({ onAdd, existingUids })`). Шаги: класс → полоски (навыки/темы, с «1 примером») → уровень сложности / блок → карточки задач. Банк скрыт за таблицей `PICKER_CLASSES` (7/8 → `alg-skill`, 9 ОГЭ → `oge`).
- **Бэкенд опций:** `GET /lessons/picker-options` (`LessonTaskPickerService`); полоски несут поле `preview` (+ `preview_svg`).
- **Сохранение ДЗ:** вью шлёт скрытое поле `picker_tasks` = JSON-массив `[{bank, refs}]`; `TeacherController::assignFromPicker()` резолвит каждый через `TaskBankResolver::resolve()` в `homework_topic_tasks` (`task_payload` + `correct_answer`), недоступные пропускает. Легаси-путь `task_indices`/`topic_number` сохранён (additive).
- **`topic_number` для alg-skill:** колонка NOT NULL, темы у навыка нет → пишется `0` (нейтрально: используется только для пути к картинкам ОГЭ/ВПР, а alg-skill задачи формульные/SVG-inline).
- **Follow-up:** дедуп `existingUids` пока no-op (picker `uid` ≠ сохранённый идентификатор); KaTeX на странице ДЗ не подключён — формулы рендерятся как текст.

## Связки и зависимости

- **TaskBankResolver** — резолвит выбранные picker'ом `{bank, refs}` в снапшот задачи при сохранении ДЗ
- **LessonTaskPickerService** — каскадные опции picker'а (классы/полоски с preview/задачи)
- **TaskDataService** — источник данных топика для `topicTasks` picker'а и для рендера задач в `homework-topic-practice.blade.php`
- **OgeVariantBuilderService / VprVariantBuilderService** — для `full_variant` mini-variant ДЗ
- **TeacherStudent** — проверка что ученик привязан к учителю (`linkedStudentIds`)
- **Evrium schedule API** — на teacher-странице ДЗ показывается расписание уроков с разбивкой по «текущим/прошлым» ученикам (см. `collectTeacherScheduleData`)
- **`StudentExamAccessService`** — определяет какой экзамен видит ученик (oge/vpr/ege) — влияет на mini-variant homework
- **hw-photos (сервис на dev-VPS)** — основное хранилище фото решений: `services/hw-photos/` в этом репозитории, рантайм `/home/dev/hw-photos`, публично `https://palomig.ru/hw-photos/`. Мост — `App\Services\HomeworkPhotoStore` на общем секрете `HW_PHOTOS_SECRET` (три подписи: upload-токен наш → проверяет сервис; `photo_id` его → проверяем мы; read-ссылка наша → проверяет сервис). Сетевых вызовов Laravel↔сервис нет. Подробности и эксплуатация — в `services/hw-photos/README.md`.
- **storage public disk** — фолбэк-хранилище: фото ложится в `homework_solutions/{assignment_id}/`, если сервис недоступен. ⚠️ На проде `public/storage` — **не симлинк**, а обычная папка-копия, поэтому по `/storage/...` фото отдаётся 404. Оба варианта учителю отдаёт `homeworkSolutionPhoto` (для внешних — редирект на подписанную ссылку, для локальных — `response()->file()`), всегда с проверкой прав: публичных ссылок на тетради учеников нет и быть не должно.

## Тесты

- `tests/Feature/Pwa/PwaHomeworkPhotoPracticeTest.php` — feature-тесты photo-practice flow
- `tests/Feature/HomeworkPhotoSubmitTest.php` — сдача фото (тяжёлый снимок с телефона, HEIC, отказ не-картинки, экран учителя и доступ к фото)
- `tests/Feature/HomeworkPhotoStoreTest.php` — внешнее хранилище: тикет, приём `photo_id`, отказ подделки и чужой задачи, фолбэк, подписанные ссылки
- `tests/Feature/HomeworkMultiPageSubmitTest.php` — многостраничные решения: порядок страниц, лимит 10, сохранность страниц первой попытки, доступ учителя
- `tests/Feature/HomeworkReviewAndDebtTest.php` — заметки, «проверено», долги (появление, снятие, чужих учеников не задевает)
- `tests/Feature/TeacherHomeworkReviewBoardTest.php` — доска проверки: что попадает в «Новые», уход работы после «проверено», статистика и «кто не делает», чужие ДЗ не видны
- `tests/browser/homework-photo-smoke.mjs` — **браузерный** смоук сдачи (Alpine: сжатие, загрузка страниц, photo_ids vs фолбэк). Обязателен после правок `homework-topic-practice.blade.php`: phpunit и curl этот слой не видят, из-за чего 31.07 на прод уехала поломка со «загружаем…» навсегда
- `services/hw-photos/test/smoke.mjs` — сам сервис (15 проверок: загрузка, подписи, миниатюры, отказы). Гоняется по живому сервису: `node test/smoke.mjs [base_url]`

## Ловушки клиентской части (31.07.2026)

Сдача фото держится на Alpine-компоненте `taskPhotos()` во вью ученика. Две грабли, на которых уже поскользнулись:

- **`$el` внутри метода — это не форма.** В обработчике `@change` Alpine подставляет в `$el` сам инпут, поэтому `this.$el.querySelector('input[type=file]')` возвращает `null`. Элементы формы брать только через `$refs` (`photoInput`, `photoIds`).
- **`app.locale` в проекте `en`** — `diffForHumans()` даёт «9 seconds ago». Относительное время собираем руками (`humanSubmittedAt`), `trans_choice` с русскими формами тоже не работает.
- **Мутировать надо реактивную ссылку.** `pages.push(obj)` кладёт сырой объект; правки по старой ссылке (`obj.uploading = false`) Alpine не видит, и строка залипает на «загружаем…». Работать с `this.pages[this.pages.length - 1]`.

## Известные неровности

- **Дублирование типов:** ENUM содержит и legacy-значения (`specific_tasks`, `topic_random`, `weak_skills`, `topic_practice`) от старой пазловой схемы. Сами таблицы дропнуты в #44/#46, но ENUM остался — стоит почистить отдельной миграцией.
- **`homework.homework_type` vs `assignHomework` request type** — UI шлёт `mini_variant`, а в DB пишется `full_variant`. Это маппинг внутри контроллера — стоит проверять при изменениях.
- **`tasks_count`** в `homeworks` денормализован: для photo-practice = количество задач, для mini-variant = nullable (берётся из варианта).
- **`accepted_at`** ставится после 2-й попытки даже если ответ неверный — учитель проверяет по фото на экране `/homework/assignment/{assignment}` (с 2026-07-30). Отдельной «модерации» (пересдача/комментарий учителя) по-прежнему нет.
- **Два хранилища одновременно:** у сабмишна заполнено либо `solution_photo_remote_id` (hw-photos), либо `solution_photo_path` (фолбэк на хостинге). Обратной синхронизации нет — фолбэк-фото так и остаётся на хостинге.
- **Колонки `solution_photo_path` / `solution_photo_remote_id` в сабмишне мертвы** с 2026-07-30 — страницы решения переехали в `homework_solution_photos`. На проде живых сабмишнов не было, бэкфила не делали; колонки стоит снести отдельной миграцией.
- **`task_payload` из банка ФИПИ хранит условие в `html`**, из curated-банка — в `text`. Вью читают цепочку `text_html ?? text ?? html ?? question ?? expression` — при добавлении новых банков сверяться с ней, иначе ученик увидит только слово «Задача» (так и было до 2026-07-30).

## При работе

- **Меняешь миграции homework** — учитывай что есть 3 миграции в порядке: `2026_01_02_000006`, `2026_03_12_100100`, `2026_04_23_000001`. Не ломай порядок.
- **Меняешь типы (ENUM)** — нужна новая миграция с `DB::statement("ALTER TABLE homeworks MODIFY homework_type ENUM(...)")` (см. `2026_04_23_000001`). SQLite-ветка пропускается.
- **Тестируешь UI photo-practice** — нужен учитель + ученик + связь `teacher_students`. Фото любое (validation: `file|max:20480|mimes:jpg,jpeg,png,webp,heic,heif,gif,bmp`). Ученику нужны `onboarding_completed_at` и `telegram_chat_id`, иначе middleware уводит с страницы ДЗ.
- **На dev-среде (этот сервер)** работаем без push в прод — пользователь явно сказал что фича разрабатывается локально перед деплоем.
