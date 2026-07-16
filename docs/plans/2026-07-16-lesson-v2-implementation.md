# Урок v2 — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use palomatika:executing-plans to implement this plan task-by-task.
> Дизайн: `docs/plans/2026-07-16-lesson-v2-design.md`. Работать из worktree от `origin/main`
> (dev-чекаут `/home/dev/palomatika` — песочница, НЕ прод-паритет). Деплой — ветка `claude/lesson-v2`
> (auto-merge в main), пушить только когда фича готова целиком.

**Goal:** Picker заданий «как база заданий» (общий для урока и ДЗ), вход ученика в урок по 4-значному коду, лок ученика на странице урока на 60 минут с ручным отпусканием.

**Architecture:** Расширяем JSON-picker (`LessonTaskPickerService` + Alpine-партиал `task-picker.blade.php`): у банка `oge` появляются разделы part1/part2/new, карточки отдаются с полным контентом и верстаются по стилям страниц базы. Вход — `join_code` на `lesson_sessions` вместо `invite_token`; лок — `locked_until/released_at` на `lesson_session_participants` + middleware на student-роуты.

**Tech Stack:** Laravel 10, MySQL 8, Alpine.js 3 (CDN), KaTeX (CDN), тесты на `palomatika_test` MySQL (`php artisan test`).

**Схема БД не меняется** у `lesson_session_tasks` и `homework_topic_tasks` — блок раскрывается в отдельные `{bank:'oge', refs}` на клиенте.

---

## Данные-факты (проверены по коду)

- Разделы ОГЭ: **part1** = темы `06`–`19` (`Pwa/StudentController::tasksPart1:556`), **part2** = `20,21,23,24,25` (`::part2:357`, темы 22 нет), **new** = внутри тем `09,10,15,16,17` задания с `label === 'Новые задания'` (`::newTasks:258,281`).
- Все три раздела читаются из одних JSON через `TaskDataService` → `LessonTaskPickerService::resolveBlocks('oge', …)` уже их видит; `supportedTasks()` уже пропускает word_problem/geometry/statements и т.п. (`LessonTaskPickerService:250-290`).
- `TaskBankResolver::resolve('oge', {topic_id, zadanie_number, task_id})` находит задачу в любом блоке темы — part2/new резолвятся **без изменений резолвера** (только тест на это).
- Текущий флоу picker'а: `taskPicker()` в `resources/views/pwa/_shared/task-picker.blade.php`, подключён в `pwa/teacher/lesson-prep.blade.php` и `pwa/teacher/homework.blade.php`; `confirmAdd()` отдаёт `onAdd([{bank, refs}])` — контракт сохраняем, ДЗ (`TeacherController::assignFromPicker`) не трогаем.
- `lesson_session_participants`: `source` enum('schedule','invite'), `joined_at` (миграция `2026_05_25_120002`).
- Тесты урока: `tests/Unit/TaskBankResolverTest.php`, `tests/Unit/LessonSessionServiceTest.php`, `tests/Feature/TeacherLessonControllerTest.php`, `tests/Feature/StudentLessonControllerTest.php`, `tests/Feature/LessonSessionFlowTest.php`.

---

## Часть A — Picker v2

### Task A1: Разделы ОГЭ в LessonTaskPickerService

**Files:**
- Modify: `app/Services/LessonTaskPickerService.php`
- Test: `tests/Unit/LessonTaskPickerServiceTest.php` (создать)

**Step 1: Написать падающие тесты**

```php
<?php

namespace Tests\Unit;

use App\Services\LessonTaskPickerService;
use Tests\TestCase;

class LessonTaskPickerServiceTest extends TestCase
{
    private LessonTaskPickerService $picker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->picker = new LessonTaskPickerService();
    }

    public function test_sections_for_oge(): void
    {
        $sections = $this->picker->sections('oge');
        $this->assertSame(['part1', 'part2', 'new'], array_column($sections, 'id'));
    }

    public function test_part1_topics_are_06_to_19(): void
    {
        $ids = array_column($this->picker->topics('oge', null, 'part1'), 'id');
        $this->assertContains('06', $ids);
        $this->assertContains('19', $ids);
        $this->assertNotContains('20', $ids);
    }

    public function test_part2_topics(): void
    {
        $ids = array_column($this->picker->topics('oge', null, 'part2'), 'id');
        $this->assertSame(['20', '21', '23', '24', '25'], $ids);
    }

    public function test_new_section_returns_only_new_zadaniya_tasks(): void
    {
        $tasks = $this->picker->tasks('oge', ['topic_id' => '09'], 'new');
        $this->assertNotEmpty($tasks);
        // Все задачи — из задания с label 'Новые задания'
        foreach ($tasks as $t) {
            $this->assertSame('new', $t['section'] ?? 'new');
        }
    }

    public function test_part2_tasks_have_text_expression(): void
    {
        $tasks = $this->picker->tasks('oge', ['topic_id' => '20'], 'part2');
        $this->assertNotEmpty($tasks);
        $this->assertNotSame('', $tasks[0]['expression']);
        $this->assertArrayHasKey('group_label', $tasks[0]);
    }

    public function test_section_null_keeps_legacy_behaviour(): void
    {
        // Без section — как раньше (все темы ОГЭ)
        $ids = array_column($this->picker->topics('oge'), 'id');
        $this->assertGreaterThan(14, count($ids));
    }
}
```

**Step 2: Запустить — убедиться, что падают**

Run: `php artisan test --filter=LessonTaskPickerServiceTest`
Expected: FAIL (`sections()` не существует / topics не принимает 3-й аргумент).

**Step 3: Минимальная реализация**

В `LessonTaskPickerService` добавить:

```php
/** Разделы ОГЭ. У прочих банков разделов нет. */
public const OGE_SECTIONS = [
    'part1' => ['title' => '1я часть',       'topics' => ['06','07','08','09','10','11','12','13','14','15','16','17','18','19']],
    'part2' => ['title' => '2я часть',       'topics' => ['20','21','23','24','25']],
    'new'   => ['title' => 'Новые задания',  'topics' => ['09','10','15','16','17']],
];

public const NEW_ZADANIE_LABEL = 'Новые задания';

/** @return array<int, array{id:string, title:string}> */
public function sections(string $bank): array
{
    if ($bank !== 'oge') return [];
    $out = [];
    foreach (self::OGE_SECTIONS as $id => $s) {
        $out[] = ['id' => $id, 'title' => $s['title']];
    }
    return $out;
}
```

Изменить сигнатуры `topics(string $bank, ?int $grade = null, ?string $section = null)`
и `tasks(string $bank, array $refs, ?string $section = null)`:

- `topics`: после получения списка тем ОГЭ отфильтровать по
  `self::OGE_SECTIONS[$section]['topics']` (если `$section` задан и bank='oge'),
  сохранив порядок из констант. Для 'new' у тем preview не считать (дорого) — можно оставить как есть.
- `tasks`: для `section === 'new'` пропускать задания, у которых
  `($z['label'] ?? '') !== self::NEW_ZADANIE_LABEL`; для остальных секций —
  наоборот, пропускать задания с этим label (чтобы «новые» не дублировались в part1).
  В каждый элемент результата добавить `'section' => $section`.
- В карточку добавить поле `'text' => (string)($t['text'] ?? '')` и
  `'image' => (string)($t['image'] ?? '')` — part1/part2 карточки показывают текст и картинки,
  как страницы базы (см. `tasks-part1.blade.php:152-201`).

**Step 4: Прогнать тесты** — `php artisan test --filter=LessonTaskPickerServiceTest` → PASS.
Также `php artisan test --filter=TaskBankResolverTest` (не сломали смежное).

**Step 5: Commit** — `feat(picker): разделы ОГЭ part1/part2/new в LessonTaskPickerService`

### Task A2: section в endpoint picker-options

**Files:**
- Modify: `app/Http/Controllers/Pwa/TeacherLessonController.php:49-86` (`pickerOptions`)
- Test: `tests/Feature/TeacherLessonControllerTest.php`

**Step 1: Падающий тест** (в существующий файл, по образцу соседних):

```php
public function test_picker_options_returns_sections_and_filters_topics(): void
{
    $teacher = $this->makeTeacher(); // использовать существующий хелпер файла

    $r = $this->actingAs($teacher)
        ->getJson('https://teacher.palomatika.ru/lessons/picker-options?bank=oge');
    $r->assertOk()->assertJsonPath('sections.0.id', 'part1');

    $r2 = $this->actingAs($teacher)
        ->getJson('https://teacher.palomatika.ru/lessons/picker-options?bank=oge&section=part2');
    $ids = array_column($r2->json('topics'), 'id');
    $this->assertSame(['20','21','23','24','25'], $ids);
}
```

(Точный способ формирования URL/домена взять из существующих тестов файла.)

**Step 2:** Run → FAIL. **Step 3:** в `pickerOptions()`:

```php
$section = $request->query('section') ?: null;
if ($section !== null && !array_key_exists($section, LessonTaskPickerService::OGE_SECTIONS)) {
    return response()->json(['error' => 'Unknown section'], 422);
}
// ...
$response = ['grades' => $picker->grades($bank), 'sections' => $picker->sections($bank)];
// в ветке не-alg-skill передавать $section:
$response['topics'] = $picker->topics($bank, $refs['grade'] ?? null, $section);
// ...
$response['tasks'] = $picker->tasks($bank, $refs, $section);
```

**Step 4:** тест PASS. **Step 5:** Commit `feat(picker): параметр section в picker-options`.

### Task A3: Тест-фиксация — резолв part2 и new задач

**Files:**
- Test: `tests/Unit/TaskBankResolverTest.php`

Резолвер менять не нужно (находит задачу в любом блоке темы). Зафиксировать тестами:

```php
public function test_resolves_part2_task_topic_20(): void
{
    $picker = new \App\Services\LessonTaskPickerService();
    $t = $picker->tasks('oge', ['topic_id' => '20'], 'part2')[0];
    $resolved = (new TaskBankResolver())->resolve('oge', [
        'topic_id' => '20', 'zadanie_number' => $t['zadanie_number'], 'task_id' => $t['id'],
    ]);
    $this->assertSame('expression', $resolved['type']);
    $this->assertNotSame('', $resolved['expression']);
}
```

Аналогичный тест для 'new' (тема 09). Если тест по 24 (доказательства без answer)
покажет, что задача не проходит `supportedTasks` (нет answer) — это ОК: такие задачи
в v1 просто не попадают в picker; отметить в доке модуля. Прогнать, закоммитить:
`test(picker): резолв задач 2 части и новых заданий`.

### Task A4: Переписать Alpine-компонент picker'а

**Files:**
- Rewrite: `resources/views/pwa/_shared/task-picker.blade.php`
- Test: ручная проверка через страницу урока (feature-тесты UI нет — Blade+Alpine)

Новый флоу состояний: `step: class → (9: section → topics → zadaniya) | (7/8: strips → buckets → tasks)`.

Ключевые требования (стили копировать из `tasks-part1.blade.php`: `.topic-pill`, `.spoiler`, `.task-item`):

1. `PICKER_CLASSES` остаётся; **9 класс выбран по умолчанию** (`init()` вызывает `chooseClass(классы[2])`, но не сбрасывает корзину).
2. Для 9 класса шаг `section`: три пилюли из `d.sections`; выбор → загрузка тем раздела (`section` в query).
3. Шаг тем — горизонтальные пилюли-номера как в базе; выбор темы → `tasks` c `section`, группировка по `group_key` → **спойлеры** `<details class="spoiler">` с заголовком `group_label (count)`.
4. В summary спойлера кнопка «Выбрать блок» (`@click.stop`), toggle всех задач блока: если все выбраны — снять все, иначе — добавить недостающие.
5. Карточка = разметка как в базе (`svg`/`image`/`text`/`expression`/`options`/`answer`) + состояние выбора (рамка/галка). Тап — toggle.
6. **Корзина глобальная**: `selected: []` НЕ очищается при навигации (chooseClass/chooseSection/chooseStrip больше не сбрасывают `selected`); уникальность по расширенному `uid` = `bank + ':' + (topic_id||skill_slug) + ':' + uid`.
7. Sticky-панель снизу: «Выбрано N · Добавить» + кнопка «Очистить». После `confirmAdd` — `reset()` как сейчас.
8. `taskRefs(t)` — как сейчас (`{bank, refs:{topic_id, zadanie_number, task_id, …}}`), т.е. контракт `onAdd` не меняется — урок и ДЗ работают без правок обработчиков.
9. Картинки: `image` (не svg) рендерить как `<img :src="'/images/tasks/' + topicId + '/' + t.image">` по образцу базы (`tasks-part1.blade.php:134`).

После вёрстки: открыть урок на dev (`php artisan serve` либо dev-домен), пройти флоу: 9 класс → 2я часть → тема 21 → выбрать блок + пару отдельных задач из 1й части → «Добавить» → задачи появились в списке урока. Проверить то же в создании ДЗ.

Commit: `feat(picker): интерфейс как база заданий — разделы, спойлеры, выбор блока, глобальная корзина`.

### Task A5: KaTeX на странице ДЗ учителя

**Files:**
- Modify: `resources/views/pwa/teacher/homework.blade.php` (добавить `@include('partials.head-katex')` в push, как в `lesson-prep`)

Известный пробел из `.claude/product/modules/homework.md:123`. Проверить рендер формул в picker'е на странице ДЗ. Commit: `fix(homework): подключить KaTeX для picker'а`.

---

## Часть B — Вход по 4-значному коду

### Task B1: Миграция join_code + source='code'

**Files:**
- Create: `database/migrations/2026_07_16_000001_add_join_code_to_lesson_sessions.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_sessions', function (Blueprint $table) {
            $table->char('join_code', 4)->nullable()->after('invite_token')->index();
        });
        // source: + 'code' (mysql only; sqlite-ветки в проекте пропускаются — см. 2026_04_23_000001)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lesson_session_participants MODIFY source ENUM('schedule','invite','code') NOT NULL DEFAULT 'code'");
        }
    }

    public function down(): void
    {
        Schema::table('lesson_sessions', fn (Blueprint $t) => $t->dropColumn('join_code'));
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lesson_session_participants MODIFY source ENUM('schedule','invite') NOT NULL DEFAULT 'schedule'");
        }
    }
};
```

`invite_token` пока НЕ дропаем (данные живых сессий); удалить отдельной миграцией после релиза.
Run: `php artisan migrate` на dev-базе. Commit: `feat(lesson): миграция join_code + source=code`.

### Task B2: joinByCode в LessonSessionService

**Files:**
- Modify: `app/Services/LessonSessionService.php`
- Modify: `app/Models/LessonSession.php` (fillable + `join_code`), `app/Models/LessonSessionParticipant.php` (const SOURCE_CODE = 'code')
- Test: `tests/Unit/LessonSessionServiceTest.php`

**Step 1: Падающие тесты**

```php
public function test_create_generates_unique_4_digit_join_code(): void
{
    $s = $this->service->createAdhoc($this->teacher);
    $this->assertMatchesRegularExpression('/^\d{4}$/', $s->join_code);
}

public function test_join_by_code_adds_participant_with_lock(): void
{
    $s = $this->makeLiveSession(); // хелпер: createAdhoc + addTask + start
    $joined = $this->service->joinByCode($s->join_code, $this->student);
    $this->assertSame($s->id, $joined->id);
    $p = $joined->participants()->where('student_id', $this->student->id)->first();
    $this->assertSame('code', $p->source);
    $this->assertTrue($p->locked_until->greaterThan(now()->addMinutes(59)));
}

public function test_join_by_wrong_code_throws(): void
{
    $this->expectException(DomainException::class);
    $this->service->joinByCode('0000', $this->student);
}

public function test_join_by_code_of_ended_session_throws(): void
{
    $s = $this->makeLiveSession();
    $code = $s->join_code;
    $this->service->end($s);
    $this->expectException(DomainException::class);
    $this->service->joinByCode($code, $this->student);
}

public function test_schedule_sessions_no_longer_autoadd_participants(): void
{
    $slot = $this->makeScheduleSlot();
    $s = $this->service->createFromSchedule($slot);
    $this->assertSame(0, $s->participants()->count());
}
```

(Хелперы/сетап — по образцу существующих тестов файла; лок-часть теста заработает после C1/C2 — на этом этапе допустимо пометить `locked_until`-ассерты TODO и включить в Task C2.)

**Step 2:** Run → FAIL.

**Step 3: Реализация**

```php
public const JOIN_CODE_STATUSES = [LessonSession::STATUS_DRAFT, LessonSession::STATUS_LIVE];

private function generateJoinCode(): string
{
    do {
        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    } while (LessonSession::whereIn('status', self::JOIN_CODE_STATUSES)
        ->where('join_code', $code)->exists());
    return $code;
}

public function joinByCode(string $code, User $student): LessonSession
{
    $session = LessonSession::whereIn('status', self::JOIN_CODE_STATUSES)
        ->where('join_code', $code)->first();
    if (!$session) {
        throw new DomainException('Урок с таким кодом не найден');
    }

    LessonSessionParticipant::firstOrCreate(
        ['lesson_session_id' => $session->id, 'student_id' => $student->id],
        [
            'source'       => LessonSessionParticipant::SOURCE_CODE,
            'locked_until' => now()->addMinutes(self::LOCK_MINUTES), // LOCK_MINUTES = 60 (Task C2)
        ]
    );

    return $session;
}
```

- Во всех `create*` добавить `'join_code' => $this->generateJoinCode()`.
- Удалить `joinByToken()` и `generateInviteToken()`; `invite_token` больше не заполняется.
- Из `createFromSchedule` и `createFromEvriumSlot` убрать создание participants
  (и фильтрацию по TeacherStudent — она больше не нужна там).
- Решение: вход по коду разрешён и в draft (ученик ждёт начала — submitAnswer всё равно требует live), это упрощает «зашли до старта».

**Step 4:** `php artisan test --filter=LessonSessionServiceTest` → PASS.
**Step 5:** Commit `feat(lesson): joinByCode вместо invite_token, без автоучастников`.

### Task B3: Роуты и контроллеры кода

**Files:**
- Modify: `routes/pwa.php` (+ `POST /lessons/join` в student-группу), `routes/web.php` (удалить `/lesson/join/{token}`)
- Delete: `app/Http/Controllers/LessonJoinController.php` (+ его тесты, если есть — `grep -r LessonJoin tests/`)
- Modify: `app/Http/Controllers/Pwa/StudentLessonController.php` (+`join()`), `TeacherLessonController.php` (`serializeSession`: заменить `invite_token` на `join_code`)
- Test: `tests/Feature/StudentLessonControllerTest.php`

Тест: POST `/lessons/join {code}` → 200 + `{lesson_id}`; неверный код → 422; после join — `GET /lessons/{id}` открывается. Реализация `join()`:

```php
public function join(Request $request): JsonResponse
{
    $data = $request->validate(['code' => 'required|digits:4']);
    try {
        $session = $this->sessions->joinByCode($data['code'], $request->user());
    } catch (DomainException $e) {
        return response()->json(['error' => $e->getMessage()], 422);
    }
    return response()->json(['lesson_id' => $session->id]);
}
```

Обновить упавшие тесты (joinByToken/инвайт). Commit: `feat(lesson): POST /lessons/join, удалён LessonJoinController`.

### Task B4: UI кода

**Files:**
- Modify: `resources/views/pwa/teacher/lesson-prep.blade.php` — вместо блока инвайт-ссылки показать код крупно (`font-size:48px, letter-spacing:8px`), и в draft, и в live.
- Modify: `resources/views/pwa/student/partials/lesson-tile.blade.php` + `resources/views/pwa/student/dashboard.blade.php` — плитка «УРОК» всегда видна; если активной сессии нет — ведёт на экран ввода кода.
- Create: экран ввода кода — простейший вариант: Alpine-модал в dashboard с одним полем на 4 цифры (`inputmode="numeric" maxlength="4"`), submit → `fetchPost('/lessons/join')` → redirect `/lessons/{lesson_id}`. Ошибку показывать под полем.

Ручная проверка: учитель видит код; ученик входит по коду; неверный код — ошибка. Commit: `feat(lesson): UI кода — показ учителю, экран входа ученику`.

---

## Часть C — Лок 60 минут

### Task C1: Миграция полей лока

**Files:**
- Create: `database/migrations/2026_07_16_000002_add_lock_to_lesson_session_participants.php`

```php
Schema::table('lesson_session_participants', function (Blueprint $table) {
    $table->timestamp('locked_until')->nullable()->after('joined_at')->index();
    $table->timestamp('released_at')->nullable()->after('locked_until');
    $table->foreignId('released_by')->nullable()->after('released_at')
        ->constrained('users')->nullOnDelete();
});
```

(down — dropConstrainedForeignId + dropColumn). `php artisan migrate`. Commit.

### Task C2: Логика лока в сервисе + модели

**Files:**
- Modify: `app/Models/LessonSessionParticipant.php` (fillable, casts `locked_until/released_at => datetime`, `hasActiveLock(): bool`)
- Modify: `app/Services/LessonSessionService.php`
- Test: `tests/Unit/LessonSessionServiceTest.php`

```php
public const LOCK_MINUTES = 60;

/** Участие с активным локом (урок не завершён, не отпущен, время не вышло). */
public function activeLockFor(User $student): ?LessonSessionParticipant
{
    return LessonSessionParticipant::where('student_id', $student->id)
        ->whereNull('released_at')
        ->where('locked_until', '>', now())
        ->whereHas('session', fn ($q) => $q->whereIn('status', self::JOIN_CODE_STATUSES))
        ->latest('joined_at')
        ->first();
}

public function release(LessonSession $session, int $studentId, User $teacher): void
{
    $p = LessonSessionParticipant::where('lesson_session_id', $session->id)
        ->where('student_id', $studentId)->firstOrFail();
    $p->update(['released_at' => now(), 'released_by' => $teacher->id]);
}
```

(В `LessonSessionParticipant` добавить relation `session()`.) Тесты: активный лок находится; после `release` — нет; после `end()` сессии — нет; по истечении времени (travel) — нет. Включить сюда отложенные ассерты из B2. Commit: `feat(lesson): лок участника — activeLockFor/release`.

### Task C3: Middleware лока

**Files:**
- Create: `app/Http/Middleware/EnforceLessonLock.php`
- Modify: `app/Http/Kernel.php` (alias `pwa.lesson-lock`), `routes/pwa.php` (в student-группу `['auth','pwa.onboarding']` добавить alias — на группу целиком)
- Test: `tests/Feature/LessonLockTest.php` (создать)

```php
<?php

namespace App\Http\Middleware;

use App\Services\LessonSessionService;
use Closure;
use Illuminate\Http\Request;

/**
 * Ученик с активным локом урока не может пользоваться другими страницами PWA:
 * любой запрос редиректится на страницу урока. Разрешены сама страница урока,
 * её API (state/answer/join) и logout.
 */
class EnforceLessonLock
{
    public function __construct(private readonly LessonSessionService $sessions) {}

    private const ALLOWED_ROUTES = [
        'pwa.student.lessons.show', 'pwa.student.lessons.state',
        'pwa.student.lessons.answer', 'pwa.student.lessons.active',
        'pwa.student.lessons.join', 'logout',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !$user->isStudent()) {
            return $next($request);
        }
        $route = $request->route()?->getName();
        if (in_array($route, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }
        $lock = $this->sessions->activeLockFor($user);
        if (!$lock) {
            return $next($request);
        }
        $url = route('pwa.student.lessons.show', ['id' => $lock->lesson_session_id]);
        return $request->expectsJson()
            ? response()->json(['error' => 'lesson_lock', 'lesson_id' => $lock->lesson_session_id], 423)
            : redirect($url);
    }
}
```

(Проверить точное имя метода роли на `User` — `isStudent()`; если нет — по `role`.)
Тесты: локнутый ученик получает redirect с dashboard на урок; страница урока доступна;
после release/end/истечения — dashboard снова доступен. Перф: `activeLockFor` — один
индексированный запрос на каждый студенческий запрос; приемлемо (по образцу `pwa.onboarding`).
Commit: `feat(lesson): middleware лока ученика`.

### Task C4: Release-endpoint + кнопка у учителя

**Files:**
- Modify: `routes/pwa.php` (teacher: `POST /lessons/{id}/participants/{studentId}/release`), `TeacherLessonController.php`, `resources/views/pwa/teacher/lesson-prep.blade.php`
- Test: `tests/Feature/TeacherLessonControllerTest.php`

Контроллер: `loadOwnSession` + `$this->sessions->release($session, $studentId, $request->user())` → `{ok:true}`. Чужой учитель → 403 (покрыть тестом). В `state()` в participants добавить `'locked' => $p->hasActiveLock()`. В live-гриде рядом с учеником кнопка «Отпустить» (видна при `locked`), по клику POST + обновление state. Commit: `feat(lesson): ручное отпускание ученика учителем`.

### Task C5: Таймер и join-экран у ученика

**Files:**
- Modify: `resources/views/pwa/student/lesson.blade.php`, `app/Http/Controllers/Pwa/StudentLessonController.php` (`state()`: добавить `locked_until` своего participant)

Alpine-таймер: каждую секунду `mm:ss` до `locked_until`; по нулю скрыть (лок снят — навигация свободна). Плюс бейдж «Учитель отпустил» если `released_at` пришёл в state. Ручная проверка полного флоу на dev: вход по коду → лок → таймер → release учителем → навигация свободна. Commit: `feat(lesson): таймер лока на странице ученика`.

---

## Часть D — Хвосты

### Task D1: Чистка тестов и полный прогон

- Обновить/удалить тесты инвайт-флоу (`grep -rn 'invite\|joinByToken' tests/`).
- `LessonSessionFlowTest` переписать на код-флоу (e2e: create → addTask из part2 → start → joinByCode → submit → release → end).
- Прогнать всё: `php artisan test` → 0 failures.
- Commit: `test(lesson): миграция тестов на код-флоу`.

### Task D2: Документация модулей

- Обновить `.claude/product/modules/lessons.md`: код входа, лок, picker v2, удалённый LessonJoinController; поправить устаревший раздел «поддерживаемые типы v1» (уже шире).
- Обновить `.claude/product/modules/homework.md`: picker v2 общий, KaTeX подключён.
- Commit: `docs(product): урок v2 в картах модулей`.

### Task D3: Деплой

По скиллу @deploy-ops: ветка `claude/lesson-v2` → push → auto-merge → миграции на проде (`php artisan migrate` через webhook/run_artisan whitelist — проверить, что migrate разрешён; иначе вручную). Smoke на проде: создать урок, войти кодом с тестового ученика, отпустить.

---

## Порядок и зависимости

A1→A2→A3→A4→A5 (picker независим), B1→B2→B3→B4, C1→C2→C3→C4→C5 (C2 зависит от B2), D после всего. Части A и B/C можно вести параллельно.
