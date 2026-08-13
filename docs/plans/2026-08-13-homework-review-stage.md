# Вторая стадия домашки «разобрать на уроке» — план реализации

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Учитель на странице проверки домашки отмечает задания, которые надо разобрать, и они становятся повесткой ближайшего урока — с фото решения ученика и заметкой учителя.

**Architecture:** Новая таблица `homework_review_items` со статусами `pending → planned → done`. Таблица задач урока `lesson_session_tasks` не трогается вообще: разбор живёт отдельной панелью на экране урока и отдельным блоком у ученика. Гашение навешено на завершение сессии.

**Tech Stack:** Laravel 10, MySQL 8, Blade, Alpine.js 3 (CDN), KaTeX 0.16.21 (CDN), PHPUnit.

**Дизайн:** [2026-08-13-homework-review-stage-design.md](2026-08-13-homework-review-stage-design.md) — читать перед началом.

---

## Контекст исполнителю

Рабочая директория: `/home/dev/palomatika-hw-review` (worktree от `origin/main`, ветка `claude/hw-review-stage`).

**Как гонять тесты.** Тестовая БД поднята: MySQL на `127.0.0.1`, база `palomatika_test`, пользователь `palomatika_test` / `palomatika_test_pw` (прописано в `phpunit.xml`). Запуск одного файла:

```bash
cd /home/dev/palomatika-hw-review && php artisan test --filter=HomeworkReviewItemsTest
```

**Не запускай весь `php artisan test`.** Полный прогон красный и на `main` — это давно известно и к твоим изменениям отношения не имеет. Смотри только на свои файлы.

**У worktree нет `vendor/`.** Первым делом:

```bash
cd /home/dev/palomatika-hw-review && composer install --no-interaction
```

**Ловушки прода, которые надо соблюдать (проверены болью):**
- В миграциях использовать `$table->dateTime(...)`, **не** `timestamp(...)` — MySQL на проде вешает `ON UPDATE CURRENT_TIMESTAMP` на первую TIMESTAMP-колонку и портит данные.
- В Alpine для `:disabled` писать `!!(...)` — иначе на проде прилетает `undefined` и атрибут ведёт себя наоборот.
- Условия задач содержат LaTeX внутри `$…$`. Везде, где показывается текст задачи, нужен KaTeX. На странице проверки уже есть готовый механизм: класс `math-scope` + инициализация в `@push('scripts')` — переиспользуй, не изобретай.

**Деплой.** Пуш ветки `claude/*` авто-мержится в `main`, но на прод **сам по себе ничего не выкладывает**. Доставка — `scripts/deploy-prod.sh` после мержа. В рамках этого плана деплой не делаем: он в самом конце, отдельным решением.

---

## Task 1: Таблица и модель `HomeworkReviewItem`

**Files:**
- Create: `database/migrations/2026_08_13_000001_create_homework_review_items_table.php`
- Create: `app/Models/HomeworkReviewItem.php`
- Test: `tests/Feature/HomeworkReviewItemsTest.php`

**Про уникальность:** уникального индекса на пару (assignment, task) **не делаем**. Одну и ту же задачу можно отметить повторно после того, как её уже разобрали на прошлом уроке (status = done), и unique это заблокировал бы. Вместо этого поиск всегда идёт по «активной» строке: `whereIn('status', ['pending','planned'])`.

**Step 1: Написать падающий тест**

Создай `tests/Feature/HomeworkReviewItemsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkReviewItem;
use App\Models\HomeworkTopicTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeworkReviewItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_item_stores_link_to_task_and_defaults_to_pending(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $homework = Homework::create([
            'teacher_id' => $teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Тема 23',
            'assigned_at' => now(),
        ]);
        $task = HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 23,
            'task_order' => 1,
            'task_payload' => ['text' => 'Найдите $x$'],
            'correct_answer' => '5',
        ]);
        $assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'status' => 'assigned',
            'tasks_total' => 1,
        ]);

        $item = HomeworkReviewItem::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $task->id,
            'note' => 'не видит подобия',
        ]);

        $this->assertSame('pending', $item->fresh()->status);
        $this->assertSame($task->id, $item->topicTask->id);
        $this->assertSame($student->id, $item->student->id);
        $this->assertNotNull($item->created_at);
    }

    public function test_same_task_can_be_flagged_again_after_it_was_resolved(): void
    {
        [$teacher, $student, $assignment, $task] = $this->fixture();

        HomeworkReviewItem::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $task->id,
            'status' => 'done',
            'resolved_at' => now(),
        ]);

        // Повторная отметка той же задачи не должна упереться в уникальный индекс.
        $second = HomeworkReviewItem::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $task->id,
        ]);

        $this->assertSame('pending', $second->fresh()->status);
        $this->assertSame(2, HomeworkReviewItem::where('homework_topic_task_id', $task->id)->count());
    }

    /** @return array{0:User,1:User,2:HomeworkAssignment,3:HomeworkTopicTask} */
    private function fixture(): array
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $homework = Homework::create([
            'teacher_id' => $teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Тема 23',
            'assigned_at' => now(),
        ]);
        $task = HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 23,
            'task_order' => 1,
            'task_payload' => ['text' => 'Найдите $x$'],
            'correct_answer' => '5',
        ]);
        $assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'status' => 'assigned',
            'tasks_total' => 1,
        ]);

        return [$teacher, $student, $assignment, $task];
    }
}
```

**Step 2: Убедиться, что тест падает**

```bash
php artisan test --filter=HomeworkReviewItemsTest
```

Ожидается: `Class "App\Models\HomeworkReviewItem" not found`.

**Step 3: Миграция**

`database/migrations/2026_08_13_000001_create_homework_review_items_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_review_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('homework_assignment_id')->constrained('homework_assignments')->cascadeOnDelete();
            $t->foreignId('homework_topic_task_id')->constrained('homework_topic_tasks')->cascadeOnDelete();
            $t->text('note')->nullable();
            $t->foreignId('student_note_id')->nullable()->constrained('student_notes')->nullOnDelete();
            $t->enum('status', ['pending', 'planned', 'done'])->default('pending');
            $t->foreignId('lesson_session_id')->nullable()->constrained('lesson_sessions')->nullOnDelete();
            // dateTime, а не timestamp: MySQL на проде вешает ON UPDATE CURRENT_TIMESTAMP
            // на первую TIMESTAMP-колонку и молча перетирает дату при любом update.
            $t->dateTime('created_at')->useCurrent();
            $t->dateTime('resolved_at')->nullable();

            $t->index(['student_id', 'status']);
            $t->index(['homework_assignment_id', 'homework_topic_task_id'], 'hri_assignment_task_idx');
            $t->index('lesson_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_review_items');
    }
};
```

**Step 4: Модель**

`app/Models/HomeworkReviewItem.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Пункт разбора — задача домашки, которую учитель отметил «разобрать на уроке».
 *
 * Живёт отдельно от student_notes сознательно: заметка про ученика («не видит
 * подобия») остаётся в его карточке навсегда, а пункт разбора гаснет после
 * ближайшего урока. Смешать — значит либо засорить карточку, либо чистить руками.
 */
class HomeworkReviewItem extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PLANNED = 'planned';
    public const STATUS_DONE    = 'done';

    /** Статусы, в которых пункт считается «живым»: повторно его не заводим. */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_PLANNED];

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'homework_assignment_id',
        'homework_topic_task_id',
        'note',
        'student_note_id',
        'status',
        'lesson_session_id',
        'resolved_at',
    ];

    protected $casts = [
        'created_at'  => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(HomeworkAssignment::class, 'homework_assignment_id');
    }

    public function topicTask(): BelongsTo
    {
        return $this->belongsTo(HomeworkTopicTask::class, 'homework_topic_task_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereIn('status', self::ACTIVE_STATUSES);
    }
}
```

**Step 5: Прогнать тест**

```bash
php artisan test --filter=HomeworkReviewItemsTest
```

Ожидается: 2 passed.

**Step 6: Коммит**

```bash
git add database/migrations/2026_08_13_000001_create_homework_review_items_table.php \
        app/Models/HomeworkReviewItem.php tests/Feature/HomeworkReviewItemsTest.php
git commit -m "feat(homework): таблица и модель пунктов разбора"
```

---

## Task 2: Переключатель «Разобрать» — эндпоинт

**Files:**
- Modify: `routes/pwa.php:183` (после `homework.debt`)
- Modify: `app/Http/Controllers/Pwa/TeacherController.php` (рядом с `homeworkNote`, ~строка 862)
- Test: `tests/Feature/HomeworkReviewToggleTest.php`

Эндпоинт идемпотентный: `on: true` создаёт активный пункт или обновляет заметку у существующего, `on: false` удаляет активный пункт (не помечает done — «done» значит «разобрали», а не «передумал отмечать»).

**Step 1: Написать падающий тест**

`tests/Feature/HomeworkReviewToggleTest.php` — четыре проверки:

1. Учитель отмечает задачу → создан пункт `pending` с его `teacher_id` и `student_id` ученика.
2. Повторный вызов с `on: false` → активного пункта нет.
3. С `note` и `to_student_card: true` → рядом создана `StudentNote` с `kind = todo`, её id лежит в `student_note_id`.
4. Чужой учитель (не привязан к ученику) получает 403.

Структуру фикстур бери из `tests/Feature/TeacherHomeworkReviewBoardTest.php:47` (метод `assign`) — там уже есть готовая обвязка `Homework` + `HomeworkTopicTask` + `HomeworkAssignment` + `TeacherStudent`. URL строится как `'https://teacher.' . config('app.base_domain') . '/homework/assignment/' . $assignment->id . '/tasks/' . $task->id . '/review'`.

**Step 2: Убедиться, что падает**

```bash
php artisan test --filter=HomeworkReviewToggleTest
```

Ожидается: 404 вместо 200 — маршрута ещё нет.

**Step 3: Маршрут**

В `routes/pwa.php` сразу после строки с `pwa.teacher.homework.debt`:

```php
Route::post('/homework/assignment/{assignment}/tasks/{homeworkTask}/review', [TeacherController::class, 'homeworkReviewToggle'])->name('pwa.teacher.homework.review-toggle');
```

**Step 4: Метод контроллера**

В `app/Http/Controllers/Pwa/TeacherController.php` после `homeworkNote()`:

```php
/**
 * Отметка «разобрать на уроке» на конкретной задаче домашки.
 *
 * Отдельная сущность, а не заметка: у неё свой срок жизни — гаснет, когда
 * урок с этой задачей завершён. Заметка при этом опциональна: проверяя
 * дюжину задач, учитель ставит галочку, а комментарий пишет не всегда.
 */
public function homeworkReviewToggle(Request $request, HomeworkAssignment $assignment, HomeworkTopicTask $homeworkTask)
{
    $user = $request->user();
    $assignment->load('homework');
    abort_unless($assignment->homework !== null, 404);
    abort_unless($this->canReviewHomework($user, $assignment), 403);
    abort_unless((int) $homeworkTask->homework_id === (int) $assignment->homework_id, 404);

    $data = $request->validate([
        'on'              => 'required|boolean',
        'note'            => 'nullable|string|max:2000',
        'to_student_card' => 'nullable|boolean',
    ]);

    $item = HomeworkReviewItem::where('homework_assignment_id', $assignment->id)
        ->where('homework_topic_task_id', $homeworkTask->id)
        ->active()
        ->first();

    if (!$data['on']) {
        $item?->delete();

        return $request->expectsJson()
            ? response()->json(['item' => null])
            : back()->with('success', 'Отметка снята.');
    }

    $note = trim((string) ($data['note'] ?? '')) ?: null;

    if ($item === null) {
        $item = new HomeworkReviewItem([
            'student_id'             => $assignment->student_id,
            'teacher_id'             => $user->id,
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $homeworkTask->id,
        ]);
    }
    $item->note = $note ?? $item->note;

    // Заметка дублируется в карточку ученика только по явной галочке: копилка
    // заметок — про ученика вообще, а не про одну домашку.
    if ($note !== null && !empty($data['to_student_card'])) {
        $studentNote = StudentNote::create([
            'student_id'             => $assignment->student_id,
            'teacher_id'             => $user->id,
            'homework_assignment_id' => $assignment->id,
            'task_ref'               => 'Задача ' . $homeworkTask->task_order,
            'kind'                   => 'todo',
            'source'                 => 'homework',
            'body'                   => $note,
        ]);
        $item->student_note_id = $studentNote->id;
    }

    $item->save();

    return $request->expectsJson()
        ? response()->json(['item' => $item->fresh()])
        : back()->with('success', 'Задача отмечена к разбору.');
}
```

Не забудь импорты `HomeworkReviewItem` и `HomeworkTopicTask` в шапке контроллера.

**Step 5: Прогнать тест**

```bash
php artisan test --filter=HomeworkReviewToggleTest
```

Ожидается: 4 passed.

**Step 6: Коммит**

```bash
git add routes/pwa.php app/Http/Controllers/Pwa/TeacherController.php tests/Feature/HomeworkReviewToggleTest.php
git commit -m "feat(homework): эндпоинт отметки «разобрать на уроке»"
```

---

## Task 3: Переключатель «Разобрать» — интерфейс страницы проверки

**Files:**
- Modify: `app/Http/Controllers/Pwa/TeacherController.php:803` (`homeworkSubmissions` — отдать пункты во view)
- Modify: `resources/views/pwa/teacher/homework-submissions.blade.php`
- Test: `tests/Feature/HomeworkReviewToggleTest.php` (добавить проверку рендера)

**Step 1: Тест на рендер**

Добавь в `HomeworkReviewToggleTest` метод: отмеченная задача → страница проверки содержит `К разбору: 1`, а сама карточка — активный переключатель. Проверяй по `assertSee`, не по вёрстке.

**Step 2: Убедиться, что падает**

```bash
php artisan test --filter=HomeworkReviewToggleTest
```

**Step 3: Контроллер отдаёт пункты**

В `homeworkSubmissions()` рядом с `$notes`:

```php
$reviewItems = HomeworkReviewItem::where('homework_assignment_id', $assignment->id)
    ->get()
    ->keyBy('homework_topic_task_id');
```

И передай `'reviewItems' => $reviewItems` во `view(...)`.

**Step 4: Вёрстка**

В `resources/views/pwa/teacher/homework-submissions.blade.php`:

1. В `.sub-summary` после `.summary-badges` — счётчик:

```blade
@php $pendingCount = $reviewItems->whereIn('status', ['pending', 'planned'])->count(); @endphp
@if($pendingCount > 0)
  <div class="review-counter">К разбору: {{ $pendingCount }}</div>
@endif
```

2. В `.sub-head` каждой карточки, рядом с `.sub-state` — переключатель формой (страница целиком на обычных POST-формах, Alpine здесь только для заметок — не тащи fetch ради одной кнопки):

```blade
@php
  $item = $reviewItems->get($task->id);
  $isFlagged = $item !== null && in_array($item->status, ['pending', 'planned'], true);
  $isResolved = $item !== null && $item->status === 'done';
@endphp
<form method="POST" action="{{ route('pwa.teacher.homework.review-toggle', [$assignment, $task]) }}">
  @csrf
  <input type="hidden" name="on" value="{{ $isFlagged ? 0 : 1 }}">
  <button type="submit" class="review-toggle {{ $isFlagged ? 'is-on' : '' }}">
    {{ $isFlagged ? '✓ Разобрать' : '+ Разобрать' }}
  </button>
</form>
@if($isResolved)
  <span class="review-resolved">разобрано {{ $item->resolved_at?->format('d.m') }}</span>
@endif
```

3. Кандидаты: карточке с неверным ответом добавь класс-подсветку (`.sub-card.is-candidate` — тонкая жёлтая рамка слева). **Автоматически не отмечать.**

4. Стили в `@push('styles')`, в духе соседних `.act-btn` / `.badge`. Переключатель в состоянии `is-on` — акцентный фон `var(--accent)`.

**Step 5: Прогнать тест + глазами**

```bash
php artisan test --filter=HomeworkReviewToggleTest
```

Отрендерить страницу можно без БД харнессом из этой же сессии — см. `Приложение A`.

**Step 6: Коммит**

```bash
git add app/Http/Controllers/Pwa/TeacherController.php resources/views/pwa/teacher/homework-submissions.blade.php tests/Feature/HomeworkReviewToggleTest.php
git commit -m "feat(homework): переключатель «Разобрать» на странице проверки"
```

---

## Task 4: Ученический доступ к своему фото (безопасность)

**Files:**
- Modify: `routes/pwa.php` (в группе ученика, после `homework.topic.photo-log`, ~строка 87)
- Modify: `app/Http/Controllers/Pwa/StudentController.php`
- Test: `tests/Feature/StudentSolutionPhotoAccessTest.php`

Это **единственное место во всём плане, где можно случайно открыть чужую тетрадь.** Делать его отдельной задачей и не смешивать ни с чем.

Сегодня ученического маршрута к фото нет вовсе — ученик фото только загружает. Учительский `pwa.teacher.homework.solution-photo` защищён `canReviewHomework` и ученику недоступен.

**Step 1: Написать падающий тест — сначала negative-case**

`tests/Feature/StudentSolutionPhotoAccessTest.php`:

1. Ученик запрашивает фото из **своей** домашки → 200 (или 302 на подписанную ссылку hw-photos, если `isRemote()`).
2. Ученик запрашивает фото **другого ученика** → 403. Это главный тест задачи.
3. Гость без авторизации → редирект на логин.
4. Ученик запрашивает фото из домашки, назначенной другому, но по той же `homework_id` (общая домашка на класс) → 403. Проверка идёт по `assignment.student_id`, а не по `homework_id`.

**Step 2: Убедиться, что падает**

```bash
php artisan test --filter=StudentSolutionPhotoAccessTest
```

Ожидается: 404 — маршрута нет.

**Step 3: Маршрут**

```php
Route::get('/homework/solution-photo/{photo}', [StudentController::class, 'homeworkSolutionPhoto'])->name('pwa.student.homework.solution-photo');
```

**Step 4: Метод**

В `StudentController`, повторяя отдачу файла из `TeacherController::homeworkSolutionPhoto()` (строки 836–858) — hw-photos через `readUrl`, фолбэк с диска:

```php
/**
 * Фото собственного решения. Единственная проверка — принадлежность домашки
 * самому ученику: фото → попытка → назначение → student_id. Никаких
 * послаблений «по уроку» или «по токену»: одна ошибка здесь открывает
 * чужую тетрадь.
 */
public function homeworkSolutionPhoto(Request $request, HomeworkSolutionPhoto $photo)
{
    $user = $request->user();
    $photo->load('submission.assignment');

    $assignment = $photo->submission?->assignment;
    abort_unless($assignment !== null, 404);
    abort_unless((int) $assignment->student_id === (int) $user->id, 403);

    $width = in_array((int) $request->query('w'), [400, 800, 1600], true) ? (int) $request->query('w') : null;

    if ($photo->isRemote()) {
        $url = $this->photoStore->readUrl((string) $photo->remote_id, $width);
        abort_if($url === null, 404);

        return redirect()->away($url);
    }

    $path = (string) $photo->path;
    abort_if($path === '' || !Storage::disk('public')->exists($path), 404);

    return response()->file(Storage::disk('public')->path($path));
}
```

Проверь, что `HomeworkPhotoStore` уже инжектится в конструктор `StudentController` (он там есть — используется при загрузке фото). Если нет — добавь.

**Step 5: Прогнать тест**

```bash
php artisan test --filter=StudentSolutionPhotoAccessTest
```

Ожидается: 4 passed. **Если negative-case красный — останавливайся и разбирайся, дальше не иди.**

**Step 6: Коммит**

```bash
git add routes/pwa.php app/Http/Controllers/Pwa/StudentController.php tests/Feature/StudentSolutionPhotoAccessTest.php
git commit -m "feat(homework): ученик видит фото только своей домашки"
```

---

## Task 5: Сервис разбора — сборка карточки и гашение

**Files:**
- Create: `app/Services/HomeworkReviewService.php`
- Test: `tests/Feature/HomeworkReviewServiceTest.php`

Один сервис, чтобы три экрана (подготовка урока, урок учителя, урок ученика) не собирали карточку разбора каждый по-своему.

**Публичный интерфейс:**

```php
/** Живые пункты учеников для экрана урока. @return array<int, array> */
public function pendingFor(array $studentIds, int $teacherId): array;

/** Пункты, уже добавленные в этот урок. @return array<int, array> */
public function plannedFor(LessonSession $session): array;

/** Переводит pending → planned с привязкой к уроку. */
public function planInto(LessonSession $session, array $itemIds): int;

/** Возвращает planned-пункт обратно в pending (учитель передумал). */
public function unplan(LessonSession $session, int $itemId): void;

/** Все planned-пункты сессии → done. Вызывается при завершении урока. */
public function resolveForSession(LessonSession $session): int;

/** Карточка разбора для ученика: условие, ответы, фото, заметка. */
public function cardsForStudent(LessonSession $session, int $studentId): array;
```

Форма карточки (одна и та же для учителя и ученика, у ученика без `teacher_note`):

```php
[
  'id'            => 12,
  'student_id'    => 7,
  'student_name'  => 'Иван Петров',
  'task_order'    => 2,
  'text'          => 'В треугольнике $ABC$…',   // payload text_html|text|html|question|expression
  'svg'           => '<svg…>' | null,
  'correct'       => '28',
  'first_answer'  => '24',
  'second_answer' => null,
  'teacher_note'  => 'путает высоту и медиану',
  'photos'        => [['url' => …, 'full' => …, 'label' => 'первая попытка · стр. 1'], …],
  'homework_url'  => '/homework/assignment/19',
]
```

`photos[].url` строится **разными маршрутами** в зависимости от того, кому отдаём: `pwa.teacher.homework.solution-photo` для учителя, `pwa.student.homework.solution-photo` для ученика. Передавай режим параметром — не догадывайся по `auth()`.

Текст задачи достаётся тем же выражением, что уже используется во view проверки:
`$payload['text_html'] ?? $payload['text'] ?? $payload['html'] ?? $payload['question'] ?? $payload['expression'] ?? 'Задача'`.

**Step 1–6:** тест на каждый метод (`planInto` меняет статус и проставляет `lesson_session_id`; `resolveForSession` ставит `done` + `resolved_at` и **не трогает** пункты других сессий; `cardsForStudent` возвращает только карточки этого ученика), затем реализация, прогон, коммит:

```bash
git commit -m "feat(homework): сервис сборки карточек разбора"
```

---

## Task 6: Гашение при завершении урока

**Files:**
- Modify: `app/Services/LessonSessionService.php:196` (метод `end`)
- Test: `tests/Feature/HomeworkReviewServiceTest.php`

**Step 1: Тест**

Урок с двумя planned-пунктами → `end()` → оба `done` с `resolved_at`; пункт другого урока остался `planned`.

**Step 2: Убедиться, что падает.**

**Step 3: Врезка в `end()`**

В `LessonSessionService::end()`, после закрытия интервалов активности и **до** `return`:

```php
// Разобранное на уроке гаснет само: держать это на учителе — значит копить мусор.
app(HomeworkReviewService::class)->resolveForSession($session);
```

Через `app()`, а не через конструктор: `LessonSessionService` инжектится в кучу мест, менять его сигнатуру ради одной строки — лишняя связность.

**Step 4: Прогон. Step 5: Коммит**

```bash
git commit -m "feat(lessons): пункты разбора гаснут при завершении урока"
```

---

## Task 7: Блок «К разбору» на экране подготовки урока

**Files:**
- Modify: `routes/pwa.php` (три маршрута в группе `Lessons API`)
- Modify: `app/Http/Controllers/Pwa/TeacherLessonController.php`
- Modify: `resources/views/pwa/teacher/lesson-prep.blade.php`
- Test: `tests/Feature/LessonReviewPanelTest.php`

**Маршруты:**

```php
Route::get('/lessons/{id}/review-items',           [TeacherLessonController::class, 'reviewItems'])->name('pwa.teacher.lessons.review-items')->whereNumber('id');
Route::post('/lessons/{id}/review-items',          [TeacherLessonController::class, 'planReviewItems'])->name('pwa.teacher.lessons.plan-review')->whereNumber('id');
Route::delete('/lessons/{id}/review-items/{itemId}', [TeacherLessonController::class, 'unplanReviewItem'])->name('pwa.teacher.lessons.unplan-review')->whereNumber('id')->whereNumber('itemId');
```

`reviewItems` возвращает `{ pending: [...], planned: [...] }`, всё — через `HomeworkReviewService` и только для участников этой сессии. Авторизация — существующий `loadOwnSession()`.

**Тесты:** учитель видит pending своих учеников-участников; **не** видит пункты ученика, которого нет в уроке; чужой учитель получает 403/404; `planInto` переводит статус; повторный `plan` того же id не плодит дублей.

**Вёрстка:** в `lesson-prep.blade.php` блок вставляется **над** списком задач урока (после блока кода входа, ~строка 200). Alpine-состояние добавляется в существующую функцию `lessonPrep(...)` в `@push('scripts')` — не заводи вторую `x-data` на той же странице.

- Заголовок «К разбору (N)», сворачиваемый.
- Карточка: имя ученика, номер задачи, условие, эталон/его ответ, миниатюра тетради, заметка.
- Чекбоксы + кнопка «Добавить в урок» → POST, потом перерисовка.
- Для planned — кнопка «убрать» (DELETE).
- Условия содержат LaTeX: после каждой перерисовки списка вызывай `renderMathInElement` по контейнеру. KaTeX на этой странице надо подключить — `@push('katex')`, версия 0.16.21, как на `pwa/teacher/homework.blade.php:4-7`.
- `:disabled` у кнопки — только через `!!(...)`.

**Коммит:**

```bash
git commit -m "feat(lessons): блок «К разбору» на экране подготовки урока"
```

---

## Task 8: Панель «Разбор» на экране урока учителя

**Files:**
- Modify: `resources/views/pwa/teacher/lesson-prep.blade.php`
- Test: `tests/Feature/LessonReviewPanelTest.php`

Тот же экран (`lesson-prep` обслуживает и draft, и live). При `status === 'live'` блок «К разбору» превращается в панель разбора: карточки planned-пунктов разворачиваются целиком — крупное фото тетради, заметка, ответы.

Просмотрщик фото уже написан на странице проверки (`homework-submissions.blade.php`, блок `.viewer`). **Вынеси его в партиал** `resources/views/pwa/_shared/photo-viewer.blade.php` и подключи в обоих местах — второй копии быть не должно.

У каждой карточки — кнопка «Разобрано»: локально скрывает карточку. В `done` пункт переводится завершением урока (Task 6), отдельного эндпоинта не нужно.

**Коммит:**

```bash
git commit -m "feat(lessons): панель разбора на экране урока учителя"
```

---

## Task 9: Блок «Разбор» на экране ученика

**Files:**
- Modify: `app/Http/Controllers/Pwa/StudentLessonController.php:84` (метод `state`)
- Modify: `resources/views/pwa/student/lesson.blade.php`
- Test: `tests/Feature/StudentLessonReviewTest.php`

**Step 1: Тесты** — три штуки, и второй здесь главный:

1. `/lessons/{id}/state` содержит `review` с карточкой своего пункта.
2. Ученик **не видит** пункты другого участника того же урока. Ровно как с персональными задачами (`LessonSessionTask::visibleTo()`), только фильтр по `student_id` пункта.
3. Пункты в статусе `pending` (не добавленные в урок) в `state` не попадают — только `planned`.

**Step 2: Убедиться, что падают.**

**Step 3: Отдача в `state()`**

Рядом с ключом `tasks`:

```php
'review' => app(HomeworkReviewService::class)->cardsForStudent($session, $student->id),
```

Фото — ученическим маршрутом `pwa.student.homework.solution-photo` (Task 4).

**Step 4: Вёрстка**

В `resources/views/pwa/student/lesson.blade.php` над `<template x-for="task in tasks">` (строка 110):

```blade
<template x-if="review.length">
  <div class="review-block">
    <div class="review-head">Разбор с учителем</div>
    <template x-for="card in review" :key="'rev-' + card.id">
      {{-- условие, свой ответ, эталон, фото своей тетради; поля ввода нет --}}
    </template>
  </div>
</template>
```

В `studentLesson(...)`: поле `review: []`, заполняется из `d.review` в том же месте, где обновляются `tasks` (~строка 455). Обновляй по той же схеме «только при реальном изменении» через сравнение JSON — иначе поллинг будет дёргать DOM каждые несколько секунд.

Заметку учителя ученику **не показывать**: `cardsForStudent` её и не отдаёт.

KaTeX на странице урока ученика: проверь, подключён ли (`grep -n katex resources/views/pwa/student/lesson.blade.php`); если нет — `@push('katex')` + рендер по контейнеру после обновления `review`.

**Step 5: Прогон. Step 6: Коммит**

```bash
git commit -m "feat(lessons): блок разбора на экране ученика"
```

---

## Task 10: Документация модуля

**Files:**
- Modify: `.claude/product/modules/homework.md`

Согласно `CLAUDE.md` обновление модуля — часть definition of done. Опиши вторую стадию: сущность, статусы, четыре экрана, гашение, и отдельным абзацем — правило доступа ученика к фото.

```bash
git commit -m "docs(homework): вторая стадия — разбор на уроке"
```

---

## Финальная проверка перед пушем

```bash
php artisan test --filter=HomeworkReview
php artisan test --filter=StudentSolutionPhotoAccess
php artisan test --filter=StudentLessonReview
php artisan test --filter=LessonReviewPanel
php artisan test --filter=TeacherHomeworkReviewBoardTest   # регрессия соседней страницы
php artisan test --filter=StudentLessonControllerTest      # регрессия state()
php artisan test --filter=LessonSessionFlowTest            # регрессия end()
```

Все зелёные. Полный `php artisan test` не гоняем — он красный и на `main`.

Затем:

```bash
git push -u origin claude/hw-review-stage
```

Авто-мёрж в `main` пройдёт сам. **Прод после этого не обновится** — доставка отдельным решением: `bash scripts/deploy-prod.sh --dry-run`, посмотреть список файлов, затем без флага. В ревизии будет миграция — `deploy:refresh` её прогонит.

---

## Приложение A: как посмотреть страницу глазами без БД

Blade рендерится на стабах моделей — БД не нужна. Скрипт-харнесс, поднимающий вьюху из worktree и подставляющий фейковые модели, лежал в scratchpad-е сессии, где делался просмотрщик фото; при необходимости воспроизводится за 10 минут: `bootstrap/app.php` из основного чекаута (там есть `vendor/`), `getFinder()->prependLocation()` на views воркtree, `view()->share('errors', new ViewErrorBag)`, модели через `new Model([...])` + `setRelation()`. Скриншот — playwright-core с chromium из `~/.cache/ms-playwright/chromium-1181`.

## Приложение B: чего в этом плане намеренно нет

- Разбор не показывается родителю.
- Аналоги из банка не подтягиваются: `LessonHomeworkSuggestionService` это умеет, приделывается отдельно.
- «Текущая карточка» и синхронизация экранов не вводятся.
- `lesson_session_tasks`, live-грид, подсчёт решённых и «следующая задача» не трогаются.
- `bank`/`refs` в `homework_topic_tasks` не добавляются.
