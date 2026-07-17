# Домашка (Homework)

> Дата скана: 2026-05-07 (после редизайна #44). Активный модуль в разработке. Источник истины — код, эта карта — навигация.

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
        └── homework_topic_task_submissions
              ├── attempts_count (0..2)
              ├── first_answer / second_answer
              ├── solution_photo_path
              ├── is_correct
              └── accepted_at      ← null = не принято
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

### Student PWA (`student.palomatika.ru`)
| Method | Path | Controller |
|---|---|---|
| GET | `/homework` | `StudentController::studentHomework` — список assignments ученика |
| GET | `/homework/{assignment}` | `StudentController::showTopicHomework` — открыть photo-practice ДЗ |
| POST | `/homework/{assignment}/tasks/{homeworkTask}` | `StudentController::submitTopicHomeworkTask` — отправить ответ + фото |

### Parent
- `parent/child-homework.blade.php` — родитель видит ДЗ ребёнка (детали — в `ParentAppController`)

## Views

- `resources/views/pwa/teacher/homework.blade.php` — учительский экран ДЗ
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
   - Фото сохраняется в `storage/app/public/homework_solutions/{assignment_id}/`
   - Если ответ верный → `is_correct=true`, `accepted_at=now()`
   - Если неверный, попытка #1 → можно попробовать ещё раз (фото нужно прикреплять снова)
   - На попытке #2 (даже если неверно) → `accepted_at=now()` (учитель проверяет по фото)
7. **`refreshTopicHomeworkProgress`** обновляет статус assignment (`completed` если все принято)

## Выбор задач — общий drill-down picker

С 2026-06-21 задачи в ДЗ (тип `topic_photo_practice`) можно набирать через **общий drill-down picker** — тот же компонент, что и на уроке.

- **Партиал:** `resources/views/pwa/_shared/task-picker.blade.php` (Alpine-фабрика `taskPicker({ onAdd, existingUids })`). Шаги: класс → полоски (навыки/темы, с «1 примером») → уровень сложности / блок → карточки задач. Банк скрыт за таблицей `PICKER_CLASSES` (7/8 → `alg-skill`, 9 ОГЭ → `oge`).
- **Бэкенд опций:** `GET /lessons/picker-options` (`LessonTaskPickerService`); полоски несут поле `preview` (+ `preview_svg`).
- **Сохранение ДЗ:** вью шлёт скрытое поле `picker_tasks` = JSON-массив `[{bank, refs}]`; `TeacherController::assignFromPicker()` резолвит каждый через `TaskBankResolver::resolve()` в `homework_topic_tasks` (`task_payload` + `correct_answer`), недоступные пропускает. Легаси-путь `task_indices`/`topic_number` сохранён (additive).
- **`topic_number` для alg-skill:** колонка NOT NULL, темы у навыка нет → пишется `0` (нейтрально: используется только для пути к картинкам ОГЭ/ВПР, а alg-skill задачи формульные/SVG-inline).
- **Follow-up:** дедуп `existingUids` пока no-op (picker `uid` ≠ сохранённый идентификатор). KaTeX на учительской странице ДЗ подключён (@push('katex')).
- **Picker v2 (2026-07-16, ветка `claude/lesson-v2`):** общий партиал переписан под интерфейс «как база заданий» — класс (7/8/9 ОГЭ, 9 по умолчанию) → разделы (1я/2я часть/Новые задания) → пилюли тем → спойлеры-задания с кнопкой «Выбрать блок» → карточки; глобальная корзина переживает навигацию. Контракт `taskPicker({onAdd, existingUids})` не менялся — ДЗ работает без правок своей стороны. Детали — в модуле [lessons](lessons.md).

## Связки и зависимости

- **TaskBankResolver** — резолвит выбранные picker'ом `{bank, refs}` в снапшот задачи при сохранении ДЗ
- **LessonTaskPickerService** — каскадные опции picker'а (классы/полоски с preview/задачи)
- **TaskDataService** — источник данных топика для `topicTasks` picker'а и для рендера задач в `homework-topic-practice.blade.php`
- **OgeVariantBuilderService / VprVariantBuilderService** — для `full_variant` mini-variant ДЗ
- **TeacherStudent** — проверка что ученик привязан к учителю (`linkedStudentIds`)
- **Evrium schedule API** — на teacher-странице ДЗ показывается расписание уроков с разбивкой по «текущим/прошлым» ученикам (см. `collectTeacherScheduleData`)
- **`StudentExamAccessService`** — определяет какой экзамен видит ученик (oge/vpr/ege) — влияет на mini-variant homework
- **storage public disk** — фото решений хранятся в `homework_solutions/{assignment_id}/`

## Тесты

- `tests/Feature/Pwa/PwaHomeworkPhotoPracticeTest.php` — feature-тесты photo-practice flow

## Известные неровности

- **Дублирование типов:** ENUM содержит и legacy-значения (`specific_tasks`, `topic_random`, `weak_skills`, `topic_practice`) от старой пазловой схемы. Сами таблицы дропнуты в #44/#46, но ENUM остался — стоит почистить отдельной миграцией.
- **`homework.homework_type` vs `assignHomework` request type** — UI шлёт `mini_variant`, а в DB пишется `full_variant`. Это маппинг внутри контроллера — стоит проверять при изменениях.
- **`tasks_count`** в `homeworks` денормализован: для photo-practice = количество задач, для mini-variant = nullable (берётся из варианта).
- **`accepted_at`** ставится после 2-й попытки даже если ответ неверный — учитель должен проверить по фото вручную, но **UI учителя для модерации сабмишнов пока не реализован** (или не нашёл — стоит свериться при работе).

## При работе

- **Меняешь миграции homework** — учитывай что есть 3 миграции в порядке: `2026_01_02_000006`, `2026_03_12_100100`, `2026_04_23_000001`. Не ломай порядок.
- **Меняешь типы (ENUM)** — нужна новая миграция с `DB::statement("ALTER TABLE homeworks MODIFY homework_type ENUM(...)")` (см. `2026_04_23_000001`). SQLite-ветка пропускается.
- **Тестируешь UI photo-practice** — нужен учитель + ученик + связь `teacher_students`. Фото можно подставлять любое (validation: image, max 5MB).
- **На dev-среде (этот сервер)** работаем без push в прод — пользователь явно сказал что фича разрабатывается локально перед деплоем.
