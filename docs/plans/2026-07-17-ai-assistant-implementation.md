# AI-ассистент учителя v1 — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: используй executing-plans для пошаговой реализации.

**Goal:** Чат-ассистент на странице урока: учитель фиксирует наблюдения об учениках, DeepSeek через function-calling превращает их в теговые записи (`student_notes`); плюс кнопка «не понимает» и просмотр записей в карточке ученика.

**Architecture:** Laravel-сервис `AssistantService` дёргает DeepSeek Chat Completions (OpenAI-совместимый, tool calling) через `Http`. Перед отправкой имена участников заменяются на placeholder'ы (аноним). Модель возвращает tool_calls → сервер создаёт `student_notes` / дописывает `lesson_sessions.note`. «Не понимает» и CRUD записей — без LLM. UI — Alpine на существующих страницах урока и профиля ученика.

**Tech Stack:** PHP 8.2 / Laravel 10, MySQL, Alpine.js, DeepSeek API (`deepseek-chat`), Laravel `Http` + `Http::fake` в тестах.

**Дисциплина:** DRY, YAGNI, TDD (red→green→commit), контроллеры ходят только через сервисы. Тесты на `palomatika_test` (MySQL). Реальный API НЕ дёргаем — только `Http::fake`. НЕ пушить/деплоить до явного OK (push = прод).

**Провайдер/ключ:** `DEEPSEEK_API_KEY` уже в dev `.env`. На прод — добавить в env отдельно перед деплоем.

---

## Часть A — Хранилище и запись без LLM

### Task A1: Миграции и модели

**Files:**
- Create: `database/migrations/2026_07_18_000001_create_student_notes_table.php`
- Create: `database/migrations/2026_07_18_000002_create_lesson_assistant_messages_table.php`
- Create: `app/Models/StudentNote.php`
- Create: `app/Models/LessonAssistantMessage.php`

**Step 1 — миграция student_notes:**
```php
Schema::create('student_notes', function (Blueprint $t) {
    $t->id();
    $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
    $t->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
    $t->foreignId('lesson_session_id')->nullable()->constrained('lesson_sessions')->nullOnDelete();
    $t->string('task_ref')->nullable();
    $t->string('topic_tag')->nullable();
    $t->enum('kind', ['weakness', 'strength', 'todo', 'general'])->default('general');
    $t->text('body');
    $t->enum('source', ['chat', 'lesson_button', 'manual'])->default('chat');
    $t->timestamp('created_at')->useCurrent();
    $t->index(['student_id', 'kind']);
    $t->index('teacher_id');
});
```
DATETIME/timestamp: `created_at` единственный timestamp — implicit ON UPDATE не страшен (не апдейтим). Ок оставить timestamp.

**Step 2 — миграция lesson_assistant_messages:**
```php
Schema::create('lesson_assistant_messages', function (Blueprint $t) {
    $t->id();
    $t->foreignId('lesson_session_id')->constrained('lesson_sessions')->cascadeOnDelete();
    $t->enum('role', ['teacher', 'assistant']);
    $t->text('content');
    $t->timestamp('created_at')->useCurrent();
    $t->index('lesson_session_id');
});
```

**Step 3 — модели.** `StudentNote`: `$fillable` все поля, `$timestamps=false` (только created_at, cast datetime), relations `student()`, `teacher()`, `session()`. `LessonAssistantMessage`: `$fillable`, `$timestamps=false`, cast created_at.

**Step 4 — прогнать:** `php artisan migrate` (dev). Ожидать DONE обеих.

**Step 5 — commit:** `feat(assistant): миграции student_notes и lesson_assistant_messages`.

---

### Task A2: Конфиг DeepSeek

**Files:** Modify `config/services.php`

**Step 1** — добавить блок:
```php
'deepseek' => [
    'api_key'  => env('DEEPSEEK_API_KEY'),
    'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
    'model'    => env('DEEPSEEK_MODEL', 'deepseek-chat'),
],
```
**Step 2 — commit:** `feat(assistant): конфиг DeepSeek (swappable на Kimi через env)`.

---

### Task A3: «Не понимает» — сервис + эндпоинт (без LLM)

**Files:**
- Modify: `app/Services/LessonSessionService.php` (метод `recordDontUnderstand`)
- Modify: `app/Http/Controllers/Pwa/TeacherLessonController.php` (метод `dontUnderstand`)
- Modify: `routes/pwa.php`
- Test: `tests/Feature/TeacherLessonControllerTest.php`, `tests/Unit/LessonSessionServiceTest.php`

**Step 1 — тест сервиса (red):** `recordDontUnderstand(session, teacher, student, task)` создаёт `student_notes` с `source=lesson_button`, `kind=weakness`, `task_ref` из `task->task_ref`, `topic_tag` из `task->topic_id`/payload, `body` вида "Не понимает: <expression|topic>". Assert строка в БД.

**Step 2 — реализация сервиса:**
```php
public function recordDontUnderstand(LessonSession $s, User $teacher, User $student, LessonSessionTask $task): StudentNote {
    $topic = $task->topic_id ?: ($task->skill_slug ?: null);
    $expr = (string)($task->task_payload['expression'] ?? '');
    return StudentNote::create([
        'student_id' => $student->id, 'teacher_id' => $teacher->id,
        'lesson_session_id' => $s->id, 'task_ref' => $task->task_ref,
        'topic_tag' => $topic, 'kind' => 'weakness', 'source' => 'lesson_button',
        'body' => 'Не понимает: ' . mb_substr($expr !== '' ? $expr : ('задача ' . $task->position), 0, 200),
    ]);
}
```

**Step 3 — тест эндпоинта (red):** `POST /lessons/{id}/dont-understand {student_id, task_id}` (teacher, своя сессия) → 201; участник обязателен → 422 для не-участника; чужая сессия → 403.

**Step 4 — контроллер:** валидация `student_id`/`task_id` integer; проверить `isParticipantId` и что task принадлежит сессии; вызвать сервис.

**Step 5 — роут** в teacher-группе: `POST /lessons/{id}/dont-understand` → `pwa.teacher.lessons.dont-understand`.

**Step 6 — прогон** `--filter='TeacherLessonControllerTest|LessonSessionServiceTest'` → зелёное.

**Step 7 — commit:** `feat(assistant): кнопка «не понимает» — запись без LLM`.

---

### Task A4: CRUD записей (PATCH/DELETE)

**Files:**
- Create: `app/Http/Controllers/Pwa/StudentNoteController.php`
- Modify: `routes/pwa.php` (teacher-группа)
- Test: `tests/Feature/StudentNoteControllerTest.php`

**Step 1 — тест (red):** `PATCH /student-notes/{id} {body?, kind?, topic_tag?}` меняет запись своего ученика (teacher_id == auth); `DELETE /student-notes/{id}` удаляет; чужая (другой teacher_id) → 403/404.

**Step 2 — контроллер:** `update` (validate body/kind/topic_tag nullable; kind in enum), `destroy`; guard `note->teacher_id === auth id` иначе abort 404.

**Step 3 — роуты** `PATCH/DELETE /student-notes/{id}`.

**Step 4 — прогон + commit:** `feat(assistant): правка/удаление записей ученика`.

---

### Task A5: Секция «Наблюдения» в карточке ученика

**Files:**
- Modify: `app/Http/Controllers/Pwa/TeacherController.php:studentProfile` (грузить `StudentNote::where('student_id',..)->where('teacher_id',..)->latest('created_at')->get()` → в view как `$notes`)
- Modify: `resources/views/pwa/teacher/student-profile.blade.php` (секция со списком, фильтр по kind, кнопки удалить/править через PATCH/DELETE, Alpine)
- Test: `tests/Feature/Pwa/...` — профиль рендерит записи ученика; чужие записи (другого teacher) не показаны.

**Step 1 — тест (red) → Step 2 контроллер → Step 3 blade → Step 4 прогон.**
**Step 5 — commit:** `feat(assistant): наблюдения в карточке ученика`.

---

## Часть B — LLM-ассистент

### Task B1: AssistantService — клиент + анонимизация

**Files:**
- Create: `app/Services/AssistantService.php`
- Test: `tests/Unit/AssistantServiceTest.php`

**Step 1 — тест анонимизации (red):** `anonymize(text, participants)` заменяет имена участников на `P1/P2…`, возвращает `[cleanText, map]`; `deanonymize` разворачивает обратно. Assert: имя ученика отсутствует в cleanText.

**Step 2 — тест вызова (red, Http::fake):**
```php
Http::fake(['*/chat/completions' => Http::response([
  'choices' => [['message' => ['tool_calls' => [[
    'id'=>'c1','type'=>'function','function'=>[
      'name'=>'record_observation',
      'arguments'=>json_encode(['participant_ref'=>'P1','kind'=>'weakness','topic_tag'=>'геометрия: подобие','body'=>'путает признаки подобия'])
    ]]]]]]],
]) ]);
// assert: в payload запроса НЕТ имени участника (аноним), tools переданы
Http::assertSent(fn($r) => !str_contains($r->body(), 'Петя') && str_contains($r->body(), 'record_observation'));
```

**Step 3 — реализация:** метод `chat(array $messages, array $tools): array` → `Http::withToken(config deepseek.api_key)->baseUrl(...)->timeout(15)->retry(1,200)->post('/chat/completions', [...])`; вернуть распарсенные tool_calls/контент. `anonymize/deanonymize` — по списку участников (имя→Pn, регистронезависимо, самые длинные имена первыми).

**Step 4 — прогон + commit:** `feat(assistant): DeepSeek-клиент + анонимизация (Http::fake)`.

---

### Task B2: Оркестрация чата + исполнение tools

**Files:**
- Modify: `app/Services/AssistantService.php` (метод `handleMessage`)
- Test: `tests/Feature/AssistantChatTest.php`

**Step 1 — тест (red, Http::fake на record_observation):** `handleMessage(session, teacher, "Петя путает подобие")` создаёт `student_notes` для участника Петя (kind=weakness, topic из аргументов), сохраняет обе реплики в `lesson_assistant_messages`, возвращает текст ответа бота.

**Step 2 — тест recall (Http::fake на answer_about_student):** запрос про ученика → сервер подкладывает его записи в follow-up вызов → бот отвечает; assert ответ содержит суть записей.

**Step 3 — тест fallback (Http::fake ошибка 500):** API упал → создаётся `student_notes(kind=general, source=chat)` с сырым текстом, ответ «не разобрал, сохранил как есть».

**Step 4 — реализация `handleMessage`:**
- собрать участников сессии, `anonymize` сообщение;
- system-prompt (по-русски: «ты помощник учителя, фиксируешь наблюдения; вызывай инструменты») + справочник тем ОГЭ (короткий список) + tools (`record_observation`, `add_lesson_note`, `answer_about_student`);
- вызвать `chat`; на tool_calls — исполнить: `record_observation`→`StudentNote::create` (deanonymize participant_ref→student_id), `add_lesson_note`→append к `lesson_sessions.note`, `answer_about_student`→подгрузить записи, второй вызов chat для финального ответа;
- сохранить реплики в `lesson_assistant_messages`; вернуть ответ.
- try/catch вокруг `chat`: на исключение — fallback-нот.

**Step 5 — прогон + commit:** `feat(assistant): оркестрация чата и исполнение tool-calls`.

---

### Task B3: Эндпоинты ассистента

**Files:**
- Modify: `app/Http/Controllers/Pwa/TeacherLessonController.php` (`assistant`, `assistantHistory`)
- Modify: `routes/pwa.php`
- Test: `tests/Feature/TeacherLessonControllerTest.php`

**Step 1 — тест (red):** `POST /lessons/{id}/assistant {message}` (Http::fake) → 200 с `{reply, notes[]}`; `GET /lessons/{id}/assistant` → история сообщений; чужая сессия → 403; пустой message → 422.

**Step 2 — контроллер:** `assistant` — validate message required string; `loadOwnSession`; `AssistantService::handleMessage`; вернуть reply + свежие записи сессии. `assistantHistory` — вернуть `lesson_assistant_messages` сессии.

**Step 3 — роуты** в teacher-группе: `POST/GET /lessons/{id}/assistant`.

**Step 4 — прогон + commit:** `feat(assistant): эндпоинты чата ассистента`.

---

## Часть C — UI

### Task C1: Чат-блок на странице урока (замена «Заметки»)

**Files:** Modify `resources/views/pwa/teacher/lesson-prep.blade.php`

- Блок «📝 Заметка» заменить на «🤖 Ассистент»: лента сообщений (из `GET /assistant`, рендер teacher/assistant), поле ввода + отправка (`POST /assistant`), после ответа — дозагрузить историю и обновить список задач/записей. Существующее `saveNote` можно оставить скрытым fallback'ом или убрать (бот пишет в note сам).
- JS: `assistantMessages`, `assistantInput`, `sendToAssistant()`, `loadAssistantHistory()`; проверить синтаксис `node --check` (вырезать `<script>`).

**Commit:** `feat(assistant): чат-ассистент на странице урока`.

### Task C2: Кнопка «не понимает» в live-гриде

**Files:** Modify `resources/views/pwa/teacher/lesson-prep.blade.php`

- В заголовке задачи (live-грид) — кнопка «не понимает»; клик → мини-выбор ученика (из participants) → `POST /dont-understand {student_id, task_id}` → тост «Записал». Alpine-стейт `duFor` (какая задача), `duPick(taskId, studentId)`.

**Commit:** `feat(assistant): кнопка «не понимает» на задаче в уроке`.

### Task C3: UI записей в карточке ученика

**Files:** Modify `resources/views/pwa/teacher/student-profile.blade.php`

- Секция «Наблюдения»: список с бейджем kind (🔴 западает / 🟢 сильная / 📌 todo / 💬 общее) + topic_tag + дата; фильтр по kind; удалить (DELETE) и inline-правка body (PATCH). Alpine.

**Commit:** `feat(assistant): UI наблюдений в карточке ученика`.

---

## Часть D — Финал

### Task D1: Полный прогон + доки

- `php artisan test` — сверить с бейзлайном (pre-existing падения не считать).
- Обновить `.claude/product/modules/` — новый модуль `assistant.md` (данные, tools, приватность, провайдер, эндпоинты) + ссылка из lessons.md.
- Commit: `docs(product): модуль AI-ассистента`.

### Task D2: Деплой (после OK Стаса)

- Прописать `DEEPSEEK_API_KEY` в прод `.env` (FTP или через хостинг-панель — env недоступен deploy-API).
- Залить файлы (FTP по [[palomatika-ftp-manual-deploy]]), `migrate` + `view:clear`/`deploy:refresh` через deploy-API.
- E2E-смоук на проде (Playwright, qa-teacher): создать урок, написать ассистенту фразу (реальный DeepSeek), проверить что запись создалась и видна в карточке; «не понимает» с задачи.

---

## Порядок

A1→A2→A3→A4→A5 (полезно и без LLM), затем B1→B2→B3 (LLM), затем C1→C2→C3 (UI), D в конце. A и C-часть по «не понимает»/записям можно показать Стасу раньше LLM.
