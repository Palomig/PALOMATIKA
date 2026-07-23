# Домашка по итогам урока — план реализации

> **For Claude:** REQUIRED SUB-SKILL: Use executing-plans to implement this plan task-by-task.
> Дизайн: `docs/plans/2026-07-23-lesson-homework-design.md` (утверждён Стасом).

**Goal:** Кнопка «Домашка по уроку» на учительской странице урока: система предлагает аналоги разобранных задач, учитель отмечает нужные и отправляет участникам как photo-practice ДЗ.

**Architecture:** Новый сервис `LessonHomeworkSuggestionService` группирует задачи урока по `(bank, grade, topic_id, zadanie_number)` и достаёт соседние задачи из JSON-банков; `GET /lessons/{id}/homework-suggestions` отдаёт группы + участников + прошлые ДЗ урока; модал на lesson-prep шлёт выбор в **существующий** `POST /homework/assign` (`picker_tasks` + `student_ids` + новое опциональное `lesson_session_id` и `title`).

**Tech Stack:** Laravel 10, Alpine.js (CDN, без сборщиков), phpunit. Стиль — как соседний код (см. `TeacherController::assignFromPicker`, `resources/views/pwa/teacher/lesson-prep.blade.php`).

**Ветка:** `claude/lesson-v2` (auto-merge → прод!). Работать в `/home/dev/palomatika-lesson-v2`.

---

### Task 1: Миграция `homeworks.lesson_session_id`

**Files:**
- Create: `database/migrations/2026_07_23_000001_add_lesson_session_id_to_homeworks.php`
- Modify: `app/Models/Homework.php` (fillable + relation)

**Step 1: Миграция**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            // Связь «ДЗ создано по итогам урока» — для плашки «уже отправлялось»
            $table->foreignId('lesson_session_id')->nullable()->after('teacher_id')
                ->constrained('lesson_sessions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lesson_session_id');
        });
    }
};
```

**Step 2:** В `Homework::$fillable` добавить `'lesson_session_id'`.

**Step 3:** `php artisan migrate` локально; `php artisan migrate:status | tail -3` — миграция Ran.

**Step 4:** Commit `feat(homework): связь ДЗ с уроком (lesson_session_id)`.

---

### Task 2: `LessonHomeworkSuggestionService` (TDD)

**Files:**
- Create: `app/Services/LessonHomeworkSuggestionService.php`
- Test: `tests/Unit/LessonHomeworkSuggestionServiceTest.php`

Контракт: `suggestionsFor(LessonSession $session): array` → массив групп:

```php
[
  'key'          => 'oge:23:4',            // bank[:grade]:topic:zadanie
  'label'        => 'ОГЭ · Тема 23 · Задание 4',
  'lesson_stats' => ['task_count' => 2, 'solved' => 1], // solved = участников с верным ответом хотя бы на одну задачу группы... НЕТ:
                    // solved = число задач группы, решённых хотя бы одним учеником (проще и честнее для превью)
  'no_analogs'   => false,
  'suggestions'  => [
    ['bank' => 'oge', 'refs' => ['topic_id' => '23', 'zadanie_number' => 4, 'task_id' => 21],
     'preview_text' => '…expression…', 'preview_svg' => null],
  ],
]
```

**Step 1: Failing test.** Скопировать сетап из `tests/Unit/LessonSessionServiceTest.php` (создание live-сессии + `addTask` c bank `alg-skill` — он сидирован в тестовой среде; см. `test_submit_answer_allows_own_personal_task`). Тесты:

```php
public function test_groups_lesson_tasks_and_excludes_used_task_ids(): void
// добавить 2 задачи одного задания → одна группа; в suggestions нет task_id с урока
public function test_group_without_remaining_analogs_flagged(): void
// добавить ВСЕ задачи задания → группа с no_analogs=true и пустым suggestions
public function test_personal_task_participates(): void
// задача с assigned_student_id попадает в группировку наравне с общими
```

**Step 2:** `vendor/bin/phpunit tests/Unit/LessonHomeworkSuggestionServiceTest.php` → FAIL (class not found).

**Step 3: Реализация.** Данные задания доставать так же, как `TaskBankResolver` (те же `match ($bank)` + `TaskDataService`/`EgeTaskDataService`/`VprTaskDataService`/`AlgTaskDataService` + обход `blocks→zadaniya→tasks`; label скопировать оттуда же). Превью: `expression` задачи, `svg` если есть (как в `LessonTaskPickerService` карточки). `solved` — по `LessonSessionAttempt::where('is_correct', true)` задач группы. НЕ дублировать парсинг: приватный хелпер `zadanieTasks(string $bank, array $refs): array` в новом сервисе; если станет третьим дублем — вынести в `TaskBankResolver` публичным методом (сейчас YAGNI).

**Step 4:** Тесты зелёные. **Step 5:** Commit `feat(lesson): сервис предложений аналогов для домашки по уроку`.

---

### Task 3: Эндпоинт `GET /lessons/{id}/homework-suggestions` (TDD)

**Files:**
- Modify: `routes/pwa.php` (teacher-блок, рядом с `pwa.teacher.lessons.state`, ~строка 154)
- Modify: `app/Http/Controllers/Pwa/TeacherLessonController.php`
- Test: `tests/Feature/TeacherLessonControllerTest.php` (дописать)

**Step 1: Failing tests** (сетап скопировать из соседних тестов этого файла):

```php
public function test_homework_suggestions_returns_groups_participants_and_prior_homeworks(): void
// 200; json: groups[], participants[{id,name}], prior_homeworks[{id,title}]
public function test_homework_suggestions_forbidden_for_foreign_teacher(): void // 403
```

**Step 2:** FAIL (404). **Step 3: Реализация.**

Роут: `Route::get('/lessons/{id}/homework-suggestions', [TeacherLessonController::class, 'homeworkSuggestions'])->name('pwa.teacher.lessons.homework-suggestions')->whereNumber('id');`

Контроллер (владение уроком проверять как в соседних экшенах этого контроллера):

```php
public function homeworkSuggestions(Request $request, int $id): JsonResponse
{
    $session = $this->loadOwnSession($request, $id); // существующий приватный хелпер (найти точное имя рядом)
    $groups = app(LessonHomeworkSuggestionService::class)->suggestionsFor($session);
    $participants = /* как в state(): id+name участников */;
    $prior = Homework::where('lesson_session_id', $session->id)
        ->get(['id', 'title', 'assigned_at']);
    return response()->json(compact('groups', 'participants') + ['prior_homeworks' => $prior]);
}
```

**Step 4:** Тесты зелёные. **Step 5:** Commit `feat(lesson): эндпоинт предложений домашки по уроку`.

---

### Task 4: `assignHomework` — поддержка `lesson_session_id` + `title` (TDD, additive)

**Files:**
- Modify: `app/Http/Controllers/Pwa/TeacherController.php` (`assignHomework` ~496, `assignFromPicker` ~678)
- Test: `tests/Feature/Pwa/PwaHomeworkPhotoPracticeTest.php` (дописать)

**Step 1: Failing test:** POST `/homework/assign` c `picker_tasks` + `lesson_session_id` СВОЕГО урока + `title` → у созданного `Homework` заполнены оба поля; с чужим `lesson_session_id` → ДЗ создаётся, но `lesson_session_id = null` (мягко игнорируем, не 403 — additive-поведение).

**Step 2:** FAIL. **Step 3:** В валидацию добавить:

```php
'lesson_session_id' => 'nullable|integer|exists:lesson_sessions,id',
'title'             => 'nullable|string|max:160',
```

В `assignFromPicker` пробросить (сигнатуру не менять — читать из `$request`): перед `$homework->save()`:

```php
$lessonSessionId = (int) $request->input('lesson_session_id', 0);
if ($lessonSessionId && LessonSession::where('id', $lessonSessionId)->where('teacher_id', $user->id)->exists()) {
    $homework->lesson_session_id = $lessonSessionId;
}
if ($t = trim((string) $request->input('title', ''))) {
    $homework->title = mb_substr($t, 0, 160);
}
```

**Step 4:** Тесты зелёные (+ прогнать весь `PwaHomeworkPhotoPracticeTest` — регрессий нет). **Step 5:** Commit `feat(homework): назначение ДЗ с привязкой к уроку и своим названием`.

---

### Task 5: Модал «Домашка по уроку» на lesson-prep

**Files:**
- Modify: `resources/views/pwa/teacher/lesson-prep.blade.php`

Без юнит-тестов (Alpine/blade); проверка руками в Task 6. Правила стиля — как существующие модалы этого файла (найти модал кода урока / picker и повторить классы).

**Step 1: Кнопка** «📚 Домашка по уроку» рядом с кнопками управления уроком; `:disabled` если задач нет; по клику `openHomework()` → fetch suggestions → показать модал.

**Step 2: Модал** (Alpine-состояние в существующем компоненте страницы):

- плашка при `prior_homeworks.length`: «По этому уроку уже отправлялось ДЗ: {title}»;
- группы-спойлеры: label + «на уроке: N задач, решено M» + бейдж «аналогов нет» при `no_analogs`;
- карточки-аналоги с чекбоксом (превью: `preview_text` через KaTeX — на странице уже подключён; `preview_svg` инлайном, как карточки пикера);
- кнопки «По 2 в каждой группе» (отметить первые 2 незанятых в каждой) и «Снять всё»; счётчик «Выбрано: K»;
- получатели: чекбоксы, участники урока предотмечены; остальных привязанных учеников подтянуть как это делает страница ДЗ (`linkedStudentIds` — см. `TeacherController::homework`), в suggestions-ответ Task 3 добавить `other_students[]`, если участников мало;
- дедлайн: `<input type="date">` → `deadline` (как в обычном ДЗ);
- «Отправить»: обычный form-POST на `route('...assign...')` (роут существующего `/homework/assign`) со скрытыми полями `picker_tasks` (JSON `[{bank, refs}]`), `student_ids[]`, `lesson_session_id`, `title` = «ДЗ по уроку {d.m} — темы {уникальные topic_id}», `deadline`; disabled при 0 задач или 0 учеников. Редирект после — существующий (страница ДЗ).

**Step 3:** Commit `feat(lesson): модал домашки по уроку на lesson-prep`.

---

### Task 6: Полный прогон, ручная проверка, деплой

**Step 1:** `vendor/bin/phpunit tests/Unit/LessonSessionServiceTest.php tests/Unit/LessonHomeworkSuggestionServiceTest.php tests/Feature/TeacherLessonControllerTest.php tests/Feature/StudentLessonControllerTest.php tests/Feature/LessonSessionFlowTest.php tests/Feature/Pwa/PwaHomeworkPhotoPracticeTest.php` — всё зелёное.

**Step 2: Ручная проверка локально** (`php artisan serve` либо dev-чекаут): создать урок, добавить 2-3 задачи, открыть модал, отметить, отправить; убедиться, что у ученика появилось ДЗ и решается как photo-practice.

**Step 3: Деплой** (@deploy-ops): `git push -u origin claude/lesson-v2` → auto-merge+FTP; затем вебхуком `migrate` и `deploy:refresh`:

```bash
curl -s -X POST https://palomatika.ru/api/deploy/artisan -H "X-Deploy-Secret: $SECRET" \
  -H 'Content-Type: application/json' -d '{"command":"migrate","args":["--force"]}'
```

Проверить FTP-файлы (как обычно) и `migrate:status`.

**Step 4:** Обновить `.claude/product/modules/homework.md` (новый флоу + `lesson_session_id`) и память `palomatika-lesson-v2.md`. Commit `docs: модуль homework — ДЗ по итогам урока`.

---

## Заметки исполнителю

- **Ничего не менять в контракте `assignHomework`** для старых форм — только additive-поля.
- `TaskBankResolver` резолвит по одному — для списков задания в Task 2 писать свой обход, копируя `match ($bank)`; не менять резолвер без нужды.
- Тестовая среда сидирована банком `alg-skill` (см. существующие юнит-тесты урока) — в тестах использовать его, ОГЭ-JSON в тестовом окружении может отсутствовать.
- Ветка авто-деплоится: не пушить до Task 6.
