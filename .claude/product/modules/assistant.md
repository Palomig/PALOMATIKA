# AI-ассистент учителя

> Дата: 2026-07-17. Активный модуль (v1). Дизайн: `docs/plans/2026-07-17-ai-assistant-design.md`, план: `-implementation.md`.

## Статус

**Реализован v1, на проде** (2026-07-17). Роль: **запись + память**. UX v2 (переделан по просьбе Стаса): на уроке — кнопка «📝 Заметки» → шторка с явным мультивыбором учеников + текст → DeepSeek вытаскивает только теги {kind, topic_tag}, на каждого выбранного создаётся `student_note`. Плюс кнопка «не понимает» на задаче и просмотр/правка записей в карточке ученика. Чат-диалог убран; recall — в карточке ученика.

## Данные

- **`student_notes`** (миграция `2026_07_18_000001`): `student_id`, `teacher_id`, `lesson_session_id?`, `task_ref?`, `topic_tag?`, `kind` enum(weakness/strength/todo/general), `body`, `source` enum(chat/lesson_button/manual), `created_at`. «База по ученикам» поверх авто-аналитики `student_topic_mastery`. Изоляция по `teacher_id`.
- **`lesson_assistant_messages`** (миграция `2026_07_18_000002`): лог чата на урок (`role` teacher/assistant, `content`).
- Общие заметки урока — в существующее `lesson_sessions.note` (бот пишет через `add_lesson_note`).

## Провайдер

DeepSeek (`config/services.php` → `deepseek`: `api_key`=`DEEPSEEK_API_KEY`, `base_url`, `model`=`deepseek-chat`). OpenAI-совместимый Chat Completions + tool calling. Swappable на Kimi (Moonshot) сменой env (`DEEPSEEK_BASE_URL`/`DEEPSEEK_MODEL`), без правок кода. Ключ есть в dev `.env`; **на прод прописать отдельно**.

## Ключевые файлы

| Файл | Назначение |
|---|---|
| `app/Services/AssistantService.php` | Клиент DeepSeek (`chat`), анонимизация (`anonymize`/`deanonymize`/`nameMap`), оркестрация (`handleMessage`) с исполнением tool-calls |
| `app/Models/StudentNote.php`, `LessonAssistantMessage.php` | Модели |
| `app/Http/Controllers/Pwa/TeacherLessonController.php` | `assistant`, `assistantHistory`, `dontUnderstand` |
| `app/Http/Controllers/Pwa/StudentNoteController.php` | `update`/`destroy` записей (guard по teacher_id) |
| `app/Services/LessonSessionService.php` | `recordDontUnderstand` (без LLM) |
| `resources/views/pwa/teacher/lesson-prep.blade.php` | Чат-блок (заменил «Заметку») + кнопка «не понимает» в live-гриде |
| `resources/views/pwa/teacher/student-profile.blade.php` | Секция «Наблюдения» (фильтр/правка/удаление, +`<noscript>`-фолбэк) |

## Механизм

`AssistantService::recordNote(session, teacher, studentIds[], text)` — один вызов DeepSeek с tool `tag_note(kind, topic_tag)` только ради тегов (ученики выбраны явно, резолвить некого); на каждого studentId создаётся `StudentNote` (одинаковые kind/topic/body). Текст перед отправкой анонимизируется, в БД пишется оригинал. Fallback: API упал → записи всё равно создаются (kind=general) — ученики известны явно, ничего не теряется. Чат-оркестрация (handleMessage/record_observation/answer_about_student) удалена.

## Приватность

Аноним-прослойка перед КАЖДЫМ вызовом API: имена участников → плейсхолдеры `P1/P2…` (регистронезависимо, кириллица через `preg_replace /iu`, длинные имена первыми). Наружу — только текст + справочник тем. Имя/ФИО/школа/контакты/id не уходят. `participant_ref` из ответа модели разворачивается обратно в `student_id`.

## Эндпоинты (teacher.palomatika.ru, auth+role:teacher,admin)

- `POST /lessons/{id}/notes {student_ids[], text}` → `{kind, topic_tag, notes[]}` (не-участник → 422)
- `POST /lessons/{id}/dont-understand {student_id, task_id}` → запись без LLM (201)
- `PATCH/DELETE /student-notes/{id}` → правка/удаление (guard teacher_id)

## Устойчивость

- `chat()` бросает RuntimeException при HTTP-fail (timeout 15с, retry 1). `handleMessage` ловит → сохраняет реплики, `reply="Не смог разобрать (ассистент недоступен)…"`, записей не создаёт (student_id NOT NULL — бесхозный нот невозможен).
- Ключ не задан → чат ответит ошибкой, кнопка «не понимает» работает (без LLM).

## Тесты

`AssistantServiceTest` (unit: аноним + клиент, Http::fake), `AssistantChatTest` (оркестрация/recall/fallback), `StudentNoteControllerTest`, `StudentNotesProfileTest`, + кейсы в `TeacherLessonControllerTest`/`LessonSessionServiceTest`. Реальный API в тестах не дёргается.

## Не в v1

Полноценный собеседник-аналитик, кросс-ученические сводки, автосоветы по ДЗ, редактирование записей через чат, суммаризация урока, голос.
