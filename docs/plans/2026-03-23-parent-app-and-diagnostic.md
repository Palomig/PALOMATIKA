# Приложение для родителей и диагностический тест — План реализации

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Цель:** Создать отдельное приложение для родителей (Telegram Mini App + Android SDK + веб), отображающее дерево навыков ребёнка, посещаемость и статус ДЗ. Добавить диагностический тест в Mini App ученика, который заполняет систему скиллов.

**Архитектура:** Один Laravel-бэкенд обслуживает три фронтенда: (1) существующий Mini App ученика/учителя на `/tg/*`, (2) новое приложение для родителей на `/parent/*`, (3) JSON API на `/api/parent/*` для Android SDK. Родитель авторизуется через отдельного Telegram-бота (`@palomatika_parent_bot`) или веб-вход. Диагностический тест живёт в Mini App ученика как обязательный шаг после онбоардинга.

**Стек:** Laravel 10, Blade + Alpine.js + Tailwind CDN, Telegram Bot API, существующие таблицы `skills`/`user_skills`.

---

## Уточнения (2026-03-23)

### Фаза 2 — Диагностика
- Задания в диагностике — **конкретные, выбранные учителем**, а не случайные 2 из банка.
- Нужен **веб-редактор** (`/tg/teacher/diagnostic-editor`), где учитель:
  - Видит все навыки из дерева
  - Для каждого навыка выбирает **конкретные задания** из банка ОГЭ (по теме → блоку → заданию)
  - Может добавлять задания **не ко всем навыкам** — только к нужным
  - Сохраняет тест — он становится единственным статичным диагностическим тестом
- Хранение: JSON-файл `storage/app/diagnostic_test.json`
- `DiagnosticService::generateQuestions()` должен читать этот файл, а не генерировать автоматически

### Фаза 3 — Приложение для родителей
- Аватарка ребёнка — **убрать** (никаких инициалов/аватаров)

### Фаза 6 — Уведомления
- Только два триггера (без спама):
  1. **Ребёнок не пришёл на урок** — но НЕ если у ученика статус «Болеет»
  2. **Ребёнок не сделал ДЗ** — после дедлайна
- Статус **«Болеет»**: новое поле `sick_until` (дата) у пользователя
  - Пока `sick_until` в будущем: уведомления о посещаемости не отправляются
  - В приложении родителя: бейдж «Болеет до ДД.ММ»
  - В посещаемости: пропуски помечаются как «Болезнь»
  - Снять/поставить могут: учитель (через интерфейс ученика) ИЛИ родитель (через своё приложение)

### Фаза 7 — Привязка родитель↔ученик
- Джарвис должен уметь привязывать через API-эндпоинт `POST /api/parent/link` (защищён `X-Deploy-Secret`)

---

## Текущее состояние (что уже есть)

| Компонент | Статус | Расположение |
|-----------|--------|--------------|
| Таблица `skills` (60 навыков, иерархия) | ✅ Готово | `database/migrations/2026_01_02_000002_create_skills_tables.php` |
| Таблица `user_skills` | ✅ Схема готова, 0 записей | Та же миграция |
| Таблица `skill_dependencies` | ✅ Схема готова | Та же миграция |
| Модель `Skill` (parent/children/dependencies) | ✅ Готово | `app/Models/Skill.php` |
| Модель `UserSkill` (weight, mastery_level) | ✅ Готово | `app/Models/UserSkill.php` |
| `Api/SkillController` (index, show, userProgress) | ✅ Готово | `app/Http/Controllers/Api/SkillController.php` |
| `StudentAnalyticsService` (mastery, слабые/сильные зоны) | ✅ Готово | `app/Services/StudentAnalyticsService.php` |
| `AdaptiveVariantService` | ✅ Готово | `app/Services/AdaptiveVariantService.php` |
| Таблица `lesson_schedule` | ✅ Готово | Evrium-бот использует |
| Трекинг посещаемости | ❌ Нет таблицы | Нужна `lesson_attendance` |
| Роль `parent` | ❌ Нет в enum | Нужна миграция |
| Связка родитель-ученик | ❌ Нет таблицы | Нужна `parent_student` |
| Диагностический тест | ❌ Не создан | Нужен `DiagnosticService` + views |
| Views для родителей | ❌ Не созданы | Нужна `resources/views/parent/` |

---

## Фаза 1: Фундамент БД

### Задача 1: Добавить роль parent и таблицу parent_student

**Файлы:**
- Создать: `database/migrations/2026_03_23_000001_add_parent_role_and_parent_student.php`
- Изменить: `app/Models/User.php` (добавить `parent` в проверки ролей)

**Шаг 1: Создать миграцию**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Добавить 'parent' в enum ролей
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student','teacher','admin','parent') NOT NULL DEFAULT 'student'");

        // Связка родитель-ученик (привязка вручную)
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('relation', 50)->default('parent'); // parent, mother, father, guardian
            $table->timestamps();

            $table->unique(['parent_id', 'student_id']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student');
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student','teacher','admin') NOT NULL DEFAULT 'student'");
    }
};
```

**Шаг 2: Добавить связи в модель User**

В `app/Models/User.php` добавить:

```php
// Родитель видит этих учеников
public function children(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'parent_student', 'parent_id', 'student_id')
        ->withPivot('relation')
        ->withTimestamps();
}

// Родители ученика
public function parents(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
        ->withPivot('relation')
        ->withTimestamps();
}

public function isParent(): bool
{
    return $this->role === 'parent';
}
```

**Шаг 3: Запустить миграцию**

```bash
php artisan migrate
```

**Шаг 4: Коммит**

```bash
git add database/migrations/2026_03_23_000001_add_parent_role_and_parent_student.php app/Models/User.php
git commit -m "feat: добавить роль parent и таблицу parent_student"
```

---

### Задача 2: Добавить таблицу lesson_attendance

**Файлы:**
- Создать: `database/migrations/2026_03_23_000002_create_lesson_attendance.php`
- Создать: `app/Models/LessonAttendance.php`

**Шаг 1: Создать миграцию**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('lesson_schedule')->nullOnDelete();
            $table->date('lesson_date');
            $table->time('start_time')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'cancelled'])->default('present');
            $table->text('note')->nullable();
            $table->string('source', 50)->default('manual'); // manual, evrium_bot
            $table->timestamps();

            $table->unique(['student_id', 'lesson_date', 'start_time'], 'unique_attendance');
            $table->index(['student_id', 'lesson_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_attendance');
    }
};
```

**Шаг 2: Создать модель**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonAttendance extends Model
{
    protected $table = 'lesson_attendance';

    protected $fillable = [
        'student_id',
        'schedule_id',
        'lesson_date',
        'start_time',
        'status',
        'note',
        'source',
    ];

    protected $casts = [
        'lesson_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(LessonSchedule::class, 'schedule_id');
    }
}
```

**Шаг 3: Запустить миграцию и закоммитить**

```bash
php artisan migrate
git add database/migrations/2026_03_23_000002_create_lesson_attendance.php app/Models/LessonAttendance.php
git commit -m "feat: добавить таблицу lesson_attendance для трекинга посещаемости"
```

---

## Фаза 2: Диагностический тест (Mini App ученика)

### Задача 3: Создать DiagnosticService

Диагностический тест выбирает 2-3 задания на каждую категорию навыков для определения слабых мест.
Обновляет веса `user_skills` по результатам.

**Файлы:**
- Создать: `app/Services/DiagnosticService.php`
- Тест: `tests/Feature/DiagnosticServiceTest.php`

**Шаг 1: Написать тест**

```php
<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use App\Services\DiagnosticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_diagnostic_questions(): void
    {
        // Засеиваем навыки
        $root = Skill::create(['name' => 'Алгебра', 'slug' => 'algebra', 'category' => 'Алгебра']);
        $child1 = Skill::create(['name' => 'Сложение дробей', 'slug' => 'slozenie-drobei', 'category' => 'Алгебра', 'parent_id' => $root->id, 'oge_numbers' => ['06']]);
        $child2 = Skill::create(['name' => 'Умножение дробей', 'slug' => 'umnozenie-drobei', 'category' => 'Алгебра', 'parent_id' => $root->id, 'oge_numbers' => ['06']]);

        $service = app(DiagnosticService::class);
        $questions = $service->generateQuestions();

        $this->assertNotEmpty($questions);
        $this->assertArrayHasKey('skill_id', $questions[0]);
        $this->assertArrayHasKey('question', $questions[0]);
        $this->assertArrayHasKey('correct_answer', $questions[0]);
    }

    public function test_processes_results_and_fills_user_skills(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $root = Skill::create(['name' => 'Алгебра', 'slug' => 'algebra', 'category' => 'Алгебра']);
        $skill = Skill::create(['name' => 'Сложение дробей', 'slug' => 'slozenie-drobei', 'category' => 'Алгебра', 'parent_id' => $root->id, 'oge_numbers' => ['06']]);

        $service = app(DiagnosticService::class);
        $service->processResults($user->id, [
            ['skill_id' => $skill->id, 'is_correct' => true],
            ['skill_id' => $skill->id, 'is_correct' => false],
        ]);

        $userSkill = UserSkill::where('user_id', $user->id)->where('skill_id', $skill->id)->first();
        $this->assertNotNull($userSkill);
        $this->assertEquals(2, $userSkill->attempts_count);
        $this->assertEquals(1, $userSkill->correct_count);
        $this->assertGreaterThan(0, (float) $userSkill->weight);
    }
}
```

**Шаг 2: Запустить тест, убедиться что падает**

```bash
php artisan test --filter=DiagnosticServiceTest
```

**Шаг 3: Реализовать DiagnosticService**

```php
<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\UserSkill;

class DiagnosticService
{
    /** Количество вопросов на каждый листовой навык */
    private const QUESTIONS_PER_SKILL = 2;

    /** ELO-подобный коэффициент обучения для диагностики */
    private const K = 0.25;

    public function __construct(
        private readonly TaskDataService $taskDataService,
    ) {}

    /**
     * Сгенерировать диагностические вопросы, покрывающие все листовые навыки.
     * Каждый вопрос — задание из JSON банка, привязанное к skill_id.
     *
     * @return array<int, array{skill_id: int, skill_name: string, category: string, question: array, correct_answer: string}>
     */
    public function generateQuestions(): array
    {
        $leafSkills = Skill::active()
            ->whereNotNull('parent_id')
            ->whereDoesntHave('children')
            ->with('parent')
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get();

        $questions = [];

        foreach ($leafSkills as $skill) {
            $ogeNumbers = $skill->oge_numbers ?? [];
            if (empty($ogeNumbers)) {
                continue;
            }

            // Выбрать задания из связанных тем ОГЭ
            $picked = $this->pickTasksForSkill($skill, self::QUESTIONS_PER_SKILL);

            foreach ($picked as $task) {
                $questions[] = [
                    'skill_id' => $skill->id,
                    'skill_name' => $skill->name,
                    'category' => $skill->category ?? $skill->parent?->name ?? '',
                    'question' => $task,
                    'correct_answer' => $task['answer'] ?? '',
                ];
            }
        }

        // Перемешать, чтобы вопросы не шли кластерами по категориям
        shuffle($questions);

        return $questions;
    }

    /**
     * Выбрать N случайных production-заданий из тем ОГЭ, связанных с навыком.
     */
    private function pickTasksForSkill(Skill $skill, int $count): array
    {
        $ogeNumbers = $skill->oge_numbers ?? [];
        $allTasks = [];

        foreach ($ogeNumbers as $ogeNum) {
            $topicId = str_pad($ogeNum, 2, '0', STR_PAD_LEFT);
            $blocks = $this->taskDataService->getBlocks($topicId, 'production');

            foreach ($blocks as $block) {
                foreach ($block['zadaniya'] ?? [] as $zadanie) {
                    foreach ($zadanie['tasks'] ?? [] as $task) {
                        $allTasks[] = array_merge($task, [
                            'topic_id' => $topicId,
                            'block_number' => $block['number'] ?? null,
                            'zadanie_number' => $zadanie['number'] ?? null,
                        ]);
                    }
                }
            }
        }

        if (empty($allTasks)) {
            return [];
        }

        shuffle($allTasks);
        return array_slice($allTasks, 0, $count);
    }

    /**
     * Обработать результаты диагностики и заполнить user_skills.
     *
     * @param array<int, array{skill_id: int, is_correct: bool}> $results
     */
    public function processResults(int $userId, array $results): void
    {
        // Группировка по навыку
        $bySkill = [];
        foreach ($results as $r) {
            $bySkill[$r['skill_id']][] = $r['is_correct'];
        }

        foreach ($bySkill as $skillId => $answers) {
            $correct = count(array_filter($answers));
            $total = count($answers);
            $accuracy = $total > 0 ? $correct / $total : 0;

            // Начальный вес на основе точности диагностики
            $weight = round($accuracy, 3);

            UserSkill::updateOrCreate(
                ['user_id' => $userId, 'skill_id' => $skillId],
                [
                    'weight' => $weight,
                    'attempts_count' => $total,
                    'correct_count' => $correct,
                    'last_practiced_at' => now(),
                ]
            );
        }

        // Создать записи с нулевым весом для навыков, которые не были протестированы
        $testedSkillIds = array_keys($bySkill);
        $untestedSkills = Skill::active()
            ->whereNotNull('parent_id')
            ->whereDoesntHave('children')
            ->whereNotIn('id', $testedSkillIds)
            ->pluck('id');

        foreach ($untestedSkills as $skillId) {
            UserSkill::firstOrCreate(
                ['user_id' => $userId, 'skill_id' => $skillId],
                ['weight' => 0, 'attempts_count' => 0, 'correct_count' => 0]
            );
        }
    }

    /**
     * Проверить, прошёл ли пользователь диагностику.
     */
    public function hasCompletedDiagnostic(int $userId): bool
    {
        return UserSkill::where('user_id', $userId)->exists();
    }
}
```

**Шаг 4: Запустить тест, убедиться что проходит**

```bash
php artisan test --filter=DiagnosticServiceTest
```

**Шаг 5: Коммит**

```bash
git add app/Services/DiagnosticService.php tests/Feature/DiagnosticServiceTest.php
git commit -m "feat: добавить DiagnosticService для скилл-диагностики учеников"
```

---

### Задача 4: Добавить маршруты и методы диагностики в Mini App ученика

**Файлы:**
- Изменить: `app/Http/Controllers/MiniAppStudentController.php`
- Изменить: `routes/web.php` (добавить маршруты `/tg/diagnostic`)
- Создать: `resources/views/miniapp/diagnostic.blade.php`
- Создать: `resources/views/miniapp/diagnostic-results.blade.php`

**Шаг 1: Добавить маршруты**

В `routes/web.php`, внутри группы `Route::middleware([EnsureOnboardingComplete::class])` (примерно строка 449):

```php
Route::get('/diagnostic', [MiniAppStudentController::class, 'diagnostic'])->name('miniapp.diagnostic');
Route::post('/diagnostic/submit', [MiniAppStudentController::class, 'submitDiagnostic'])->name('miniapp.diagnostic.submit');
Route::get('/diagnostic/results', [MiniAppStudentController::class, 'diagnosticResults'])->name('miniapp.diagnostic.results');
```

**Шаг 2: Добавить методы в контроллер**

В `MiniAppStudentController.php` добавить:

```php
use App\Services\DiagnosticService;

public function diagnostic(Request $request, DiagnosticService $diagnosticService)
{
    $user = Auth::user();

    // Если уже пройдена — редирект на результаты
    if ($diagnosticService->hasCompletedDiagnostic($user->id)) {
        return redirect()->route('miniapp.diagnostic.results');
    }

    $questions = $diagnosticService->generateQuestions();

    // Сохраняем вопросы в сессии для валидации при сабмите
    $request->session()->put('diagnostic_questions', $questions);

    return view('miniapp.diagnostic', [
        'questions' => $questions,
        'totalQuestions' => count($questions),
    ]);
}

public function submitDiagnostic(Request $request, DiagnosticService $diagnosticService)
{
    $user = Auth::user();
    $questions = $request->session()->pull('diagnostic_questions', []);

    if (empty($questions)) {
        return redirect()->route('miniapp.diagnostic');
    }

    $answers = $request->input('answers', []);
    $results = [];

    foreach ($questions as $i => $q) {
        $userAnswer = trim($answers[$i] ?? '');
        $correctAnswer = trim($q['correct_answer'] ?? '');

        // Нормализация: lowercase, запятая→точка, убрать лишние нули
        $normalizedUser = $this->normalizeAnswer($userAnswer);
        $normalizedCorrect = $this->normalizeAnswer($correctAnswer);

        $results[] = [
            'skill_id' => $q['skill_id'],
            'is_correct' => $normalizedUser === $normalizedCorrect,
        ];
    }

    $diagnosticService->processResults($user->id, $results);

    return redirect()->route('miniapp.diagnostic.results');
}

public function diagnosticResults(DiagnosticService $diagnosticService)
{
    $user = Auth::user();

    if (!$diagnosticService->hasCompletedDiagnostic($user->id)) {
        return redirect()->route('miniapp.diagnostic');
    }

    $userSkills = $user->skills()
        ->with('skill.parent')
        ->orderBy('weight', 'asc')
        ->get();

    $byCategory = $userSkills->groupBy(fn ($us) => $us->skill->category ?? $us->skill->parent?->name ?? 'Другое');

    return view('miniapp.diagnostic-results', [
        'byCategory' => $byCategory,
        'weakSkills' => $userSkills->where('weight', '<', 0.5)->values(),
        'strongSkills' => $userSkills->where('weight', '>=', 0.7)->values(),
        'averageWeight' => round($userSkills->avg('weight') ?? 0, 3),
    ]);
}

private function normalizeAnswer(string $answer): string
{
    $answer = mb_strtolower(trim($answer));
    $answer = str_replace(',', '.', $answer);
    if (is_numeric($answer)) {
        $answer = rtrim(rtrim((string) floatval($answer), '0'), '.');
    }
    return $answer;
}
```

**Шаг 3: Добавить связь `skills()` в модель User** (если ещё нет)

В `app/Models/User.php`:

```php
public function skills(): HasMany
{
    return $this->hasMany(UserSkill::class);
}
```

**Шаг 4: Создать view диагностики** (`resources/views/miniapp/diagnostic.blade.php`)

UI теста, где ученик отвечает на вопросы один за другим. Реализация на Blade + Alpine.js — карточки с пролистыванием, прогресс-бар, поле ввода ответа, кнопка отправки в конце.

> **Заметка для реализатора:** Следовать паттернам Tailwind + Alpine.js из `resources/views/miniapp/test.blade.php`. Mobile-first, тёмная тема Telegram. View получает массив `$questions` и `$totalQuestions`. Каждая карточка показывает `question.question.text`, опциональное изображение и поле ввода. При сабмите POST на `/tg/diagnostic/submit` с массивом `answers[]`.

**Шаг 5: Создать view результатов** (`resources/views/miniapp/diagnostic-results.blade.php`)

Показывает дерево навыков с цветовой кодировкой весов: красный (<0.5), жёлтый (0.5-0.7), зелёный (>0.7). Группировка по категориям (Алгебра, Геометрия, Практика). Секция «Слабые навыки» выделена.

**Шаг 6: Коммит**

```bash
git add app/Http/Controllers/MiniAppStudentController.php routes/web.php resources/views/miniapp/diagnostic.blade.php resources/views/miniapp/diagnostic-results.blade.php app/Models/User.php
git commit -m "feat: добавить поток диагностического теста в Mini App ученика"
```

---

### Задача 5: Авторедирект новых учеников на диагностику после онбоардинга

**Файлы:**
- Изменить: `app/Http/Controllers/MiniAppStudentController.php` — метод `dashboard()`
- Изменить: `app/Http/Middleware/EnsureOnboardingComplete.php` (при необходимости)

**Шаг 1: В `dashboard()` проверить, пройдена ли диагностика**

В начало метода `dashboard()` добавить:

```php
$diagnosticService = app(DiagnosticService::class);
if (!$diagnosticService->hasCompletedDiagnostic($user->id)) {
    return redirect()->route('miniapp.diagnostic');
}
```

Это гарантирует, что новые ученики пройдут диагностику перед доступом к дашборду.

**Шаг 2: Коммит**

```bash
git add app/Http/Controllers/MiniAppStudentController.php
git commit -m "feat: редирект новых учеников на диагностику перед дашбордом"
```

---

## Фаза 3: Приложение для родителей (Telegram Mini App + Веб)

### Задача 6: Создать ParentAppController и поток авторизации

Приложение для родителей использует ту же HMAC-авторизацию Telegram, что и ученическое, но с отдельным токеном бота и создаёт пользователей с `role=parent`.

**Файлы:**
- Создать: `app/Http/Controllers/ParentAppController.php`
- Создать: `app/Http/Controllers/ParentAuthController.php`
- Изменить: `routes/web.php` — добавить маршруты `/parent/*`
- Изменить: `app/Http/Middleware/VerifyCsrfToken.php` — исключить `/parent/auth`

**Шаг 1: Создать ParentAuthController**

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramMiniAppAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ParentAuthController extends Controller
{
    public function __construct(
        private readonly TelegramMiniAppAuthService $tgMiniAuth,
    ) {}

    public function home(Request $request)
    {
        if (Auth::check() && Auth::user()->isParent()) {
            return redirect('/parent/dashboard');
        }

        return view('parent.home');
    }

    /**
     * Авторизация родителя через Telegram Mini App initData.
     * Использует токен РОДИТЕЛЬСКОГО бота (настраивается отдельно).
     */
    public function authenticate(Request $request)
    {
        $initData = trim((string) $request->input('initData', ''));

        if ($initData === '') {
            return redirect('/parent')->with('error', 'Нет данных Telegram');
        }

        try {
            // Верификация через токен родительского бота
            [$authFields, $telegramUser] = $this->tgMiniAuth->extractAndVerify(
                $initData,
                config('services.telegram.parent_bot_token')
            );
        } catch (\Throwable $e) {
            return redirect('/parent')->with('error', 'Данные Telegram недействительны');
        }

        // Найти или создать пользователя-родителя
        $user = User::where('oauth_provider', 'telegram')
            ->where('oauth_id', $telegramUser['id'])
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => trim(($telegramUser['first_name'] ?? '') . ' ' . ($telegramUser['last_name'] ?? '')),
                'oauth_provider' => 'telegram',
                'oauth_id' => $telegramUser['id'],
                'tg_username' => $telegramUser['username'] ?? null,
                'role' => 'parent',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $handoffToken = Str::random(40);
        Cache::put('parent_auth_handoff:' . $handoffToken, [
            'user_id' => $user->id,
        ], now()->addMinutes(2));

        return response()->view('parent.auth-bridge', [
            'redirectTo' => '/parent/dashboard',
            'handoffToken' => $handoffToken,
        ]);
    }

    public function authContinue(Request $request)
    {
        $token = trim((string) $request->query('token', ''));
        $handoff = $token !== '' ? Cache::pull('parent_auth_handoff:' . $token) : null;

        if (is_array($handoff) && !empty($handoff['user_id'])) {
            $user = User::find((int) $handoff['user_id']);
            if ($user) {
                Auth::login($user, true);
                $request->session()->regenerate();
            }
        }

        if (!Auth::check()) {
            return redirect('/parent')->with('error', 'Сессия истекла');
        }

        return redirect('/parent/dashboard');
    }
}
```

**Шаг 2: Создать ParentAppController**

```php
<?php

namespace App\Http\Controllers;

use App\Models\LessonAttendance;
use App\Models\User;
use App\Models\UserSkill;
use App\Services\DiagnosticService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentAppController extends Controller
{
    /**
     * Дашборд родителя — карточки привязанных детей с кратким саммари.
     */
    public function dashboard()
    {
        $parent = Auth::user();
        $children = $parent->children()->get();

        $childSummaries = $children->map(function (User $child) {
            $skills = UserSkill::where('user_id', $child->id)->get();
            $weakCount = $skills->where('weight', '<', 0.5)->count();
            $avgWeight = round($skills->avg('weight') ?? 0, 2);

            // Последняя посещаемость
            $lastAttendance = LessonAttendance::where('student_id', $child->id)
                ->orderByDesc('lesson_date')
                ->first();

            // Количество невыполненных ДЗ
            $pendingHomework = $child->homeworkAssignments()
                ->whereNull('completed_at')
                ->count();

            return [
                'child' => $child,
                'avg_skill_weight' => $avgWeight,
                'weak_skills_count' => $weakCount,
                'total_skills' => $skills->count(),
                'last_attendance' => $lastAttendance,
                'pending_homework' => $pendingHomework,
                'diagnostic_completed' => $skills->isNotEmpty(),
            ];
        });

        return view('parent.dashboard', [
            'childSummaries' => $childSummaries,
        ]);
    }

    /**
     * Детальное дерево навыков конкретного ребёнка.
     */
    public function childSkills(int $studentId)
    {
        $parent = Auth::user();
        $child = $parent->children()->findOrFail($studentId);

        $userSkills = UserSkill::where('user_id', $child->id)
            ->with('skill.parent')
            ->orderBy('weight', 'asc')
            ->get();

        $byCategory = $userSkills->groupBy(fn ($us) => $us->skill->category ?? $us->skill->parent?->name ?? 'Другое');

        return view('parent.child-skills', [
            'child' => $child,
            'byCategory' => $byCategory,
            'weakSkills' => $userSkills->where('weight', '<', 0.5)->values(),
            'strongSkills' => $userSkills->where('weight', '>=', 0.7)->values(),
            'averageWeight' => round($userSkills->avg('weight') ?? 0, 3),
        ]);
    }

    /**
     * История посещаемости ребёнка.
     */
    public function childAttendance(int $studentId)
    {
        $parent = Auth::user();
        $child = $parent->children()->findOrFail($studentId);

        $attendance = LessonAttendance::where('student_id', $child->id)
            ->orderByDesc('lesson_date')
            ->paginate(30);

        $stats = [
            'total' => LessonAttendance::where('student_id', $child->id)->count(),
            'present' => LessonAttendance::where('student_id', $child->id)->where('status', 'present')->count(),
            'absent' => LessonAttendance::where('student_id', $child->id)->where('status', 'absent')->count(),
            'late' => LessonAttendance::where('student_id', $child->id)->where('status', 'late')->count(),
        ];

        return view('parent.child-attendance', [
            'child' => $child,
            'attendance' => $attendance,
            'stats' => $stats,
        ]);
    }

    /**
     * Статус домашних заданий ребёнка.
     */
    public function childHomework(int $studentId)
    {
        $parent = Auth::user();
        $child = $parent->children()->findOrFail($studentId);

        $assignments = $child->homeworkAssignments()
            ->with('homework')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('parent.child-homework', [
            'child' => $child,
            'assignments' => $assignments,
        ]);
    }
}
```

**Шаг 3: Добавить маршруты в `routes/web.php`**

После блока `/tg` (после строки 493):

```php
// Маршруты приложения для родителей (/parent/*)
Route::prefix('parent')->group(function () {
    Route::get('/', [ParentAuthController::class, 'home'])->name('parent.home');
    Route::post('/auth', [ParentAuthController::class, 'authenticate'])->name('parent.auth');
    Route::get('/auth/continue', [ParentAuthController::class, 'authContinue'])->name('parent.auth.continue');

    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', [ParentAppController::class, 'dashboard'])->name('parent.dashboard');
        Route::get('/child/{studentId}/skills', [ParentAppController::class, 'childSkills'])->name('parent.child.skills');
        Route::get('/child/{studentId}/attendance', [ParentAppController::class, 'childAttendance'])->name('parent.child.attendance');
        Route::get('/child/{studentId}/homework', [ParentAppController::class, 'childHomework'])->name('parent.child.homework');
    });
});
```

**Шаг 4: Исключить auth родителя из CSRF**

В `app/Http/Middleware/VerifyCsrfToken.php`, добавить в `$except`:

```php
'parent/auth',
```

**Шаг 5: Добавить токен родительского бота в конфиг**

В `config/services.php`, внутри массива `'telegram'`:

```php
'parent_bot_token' => env('TELEGRAM_PARENT_BOT_TOKEN'),
```

**Шаг 6: Коммит**

```bash
git add app/Http/Controllers/ParentAuthController.php app/Http/Controllers/ParentAppController.php routes/web.php app/Http/Middleware/VerifyCsrfToken.php config/services.php
git commit -m "feat: добавить контроллеры и маршруты приложения для родителей на /parent/*"
```

---

### Задача 7: Создать Blade views приложения для родителей

**Файлы:**
- Создать: `resources/views/parent/home.blade.php` — лендинг/страница входа
- Создать: `resources/views/parent/auth-bridge.blade.php` — handoff авторизации (скопировать из miniapp версии)
- Создать: `resources/views/parent/dashboard.blade.php` — карточки детей с саммари
- Создать: `resources/views/parent/child-skills.blade.php` — визуализация дерева навыков
- Создать: `resources/views/parent/child-attendance.blade.php` — календарь/список посещаемости
- Создать: `resources/views/parent/child-homework.blade.php` — список ДЗ со статусами
- Создать: `resources/views/parent/partials/nav.blade.php` — нижняя навигация

**Заметки по дизайну для реализатора:**
- Использовать те же паттерны Tailwind CDN + Alpine.js + self-hosted `telegram-web-app.js`, что и в Mini App ученика
- Цветовая схема: отличная от ученического приложения. Рекомендуются тёплые тона (мягкий синий/белый) вместо тёмной темы ученика
- Нижняя навигация: Дети | Уведомления | Профиль
- Страница навыков: визуальные полоски (0-100%) для каждого навыка, цветовая кодировка красный/жёлтый/зелёный
- Посещаемость: вид календаря с цветными точками (зелёный=присутствовал, красный=отсутствовал, жёлтый=опоздал)
- ДЗ: список с бейджами статуса (✅ Выполнено, ⏳ В процессе, ❌ Не выполнено, ⚠️ Просрочено)
- Все views должны работать и как Telegram Mini App, И как обычная веб-страница

**Шаг 1: Создать все views по паттернам из существующих `resources/views/miniapp/` файлов**

**Шаг 2: Коммит**

```bash
git add resources/views/parent/
git commit -m "feat: добавить Blade views приложения для родителей (дашборд, навыки, посещаемость, ДЗ)"
```

---

## Фаза 4: API для Android SDK

### Задача 8: Создать Api/ParentController для Android-приложения

Те же данные, что и ParentAppController, но возвращает JSON.

**Файлы:**
- Создать: `app/Http/Controllers/Api/ParentController.php`
- Изменить: `routes/api.php` — добавить маршруты API для родителей

**Шаг 1: Создать контроллер**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LessonAttendance;
use App\Models\UserSkill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    public function children(Request $request): JsonResponse
    {
        $parent = $request->user();
        $children = $parent->children()->get(['users.id', 'users.name', 'users.avatar', 'users.grade', 'users.last_active_at']);

        return response()->json(['children' => $children]);
    }

    public function childSkills(Request $request, int $studentId): JsonResponse
    {
        $parent = $request->user();
        $child = $parent->children()->findOrFail($studentId);

        $skills = UserSkill::where('user_id', $child->id)
            ->with('skill:id,name,slug,category,parent_id')
            ->get()
            ->map(fn ($us) => [
                'skill' => $us->skill,
                'weight' => round((float) $us->weight, 3),
                'mastery_level' => $us->mastery_level,
                'attempts' => $us->attempts_count,
                'accuracy' => $us->accuracy,
            ]);

        return response()->json([
            'child_id' => $child->id,
            'child_name' => $child->name,
            'skills' => $skills,
            'average_weight' => round($skills->avg('weight') ?? 0, 3),
        ]);
    }

    public function childAttendance(Request $request, int $studentId): JsonResponse
    {
        $parent = $request->user();
        $child = $parent->children()->findOrFail($studentId);

        $attendance = LessonAttendance::where('student_id', $child->id)
            ->orderByDesc('lesson_date')
            ->limit(50)
            ->get();

        return response()->json([
            'child_id' => $child->id,
            'attendance' => $attendance,
        ]);
    }

    public function childHomework(Request $request, int $studentId): JsonResponse
    {
        $parent = $request->user();
        $child = $parent->children()->findOrFail($studentId);

        $assignments = $child->homeworkAssignments()
            ->with('homework:id,title,due_date')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return response()->json([
            'child_id' => $child->id,
            'assignments' => $assignments,
        ]);
    }
}
```

**Шаг 2: Добавить API-маршруты** в `routes/api.php`:

```php
use App\Http\Controllers\Api\ParentController;

Route::middleware('auth:sanctum')->prefix('parent')->group(function () {
    Route::get('/children', [ParentController::class, 'children']);
    Route::get('/child/{studentId}/skills', [ParentController::class, 'childSkills']);
    Route::get('/child/{studentId}/attendance', [ParentController::class, 'childAttendance']);
    Route::get('/child/{studentId}/homework', [ParentController::class, 'childHomework']);
});
```

**Шаг 3: Коммит**

```bash
git add app/Http/Controllers/Api/ParentController.php routes/api.php
git commit -m "feat: добавить API-эндпоинты для родительского Android-приложения"
```

---

## Фаза 5: Трекинг посещаемости (интеграция с Evrium-ботом)

### Задача 9: Добавить webhook-эндпоинт для посещаемости

Evrium-бот (`@evrium_bot`) отправляет данные о посещаемости при начале урока. Нужен API-эндпоинт для приёма этих данных.

**Файлы:**
- Изменить: `app/Http/Controllers/Api/ScheduleController.php` — добавить метод `recordAttendance`
- Изменить: `routes/api.php` — добавить маршрут посещаемости

**Шаг 1: Добавить эндпоинт в ScheduleController**

```php
/**
 * POST /api/attendance — записать посещаемость урока от Evrium-бота.
 * Защищён X-Deploy-Secret.
 */
public function recordAttendance(Request $request): JsonResponse
{
    if ($err = $this->auth($request)) return $err;

    $request->validate([
        'student_id' => 'required|integer|exists:users,id',
        'lesson_date' => 'required|date',
        'start_time' => 'nullable|date_format:H:i',
        'status' => 'required|in:present,absent,late,cancelled',
        'schedule_id' => 'nullable|integer|exists:lesson_schedule,id',
        'note' => 'nullable|string|max:500',
    ]);

    $attendance = LessonAttendance::updateOrCreate(
        [
            'student_id' => $request->student_id,
            'lesson_date' => $request->lesson_date,
            'start_time' => $request->start_time,
        ],
        [
            'status' => $request->status,
            'schedule_id' => $request->schedule_id,
            'note' => $request->note,
            'source' => 'evrium_bot',
        ]
    );

    // TODO Фаза 6: Отправить уведомление родителю

    return response()->json(['ok' => true, 'attendance_id' => $attendance->id]);
}
```

**Шаг 2: Добавить маршрут** в `routes/api.php`:

```php
Route::post('/attendance', [ScheduleController::class, 'recordAttendance']);
```

**Шаг 3: Коммит**

```bash
git add app/Http/Controllers/Api/ScheduleController.php routes/api.php
git commit -m "feat: добавить webhook посещаемости для интеграции с Evrium-ботом"
```

---

## Фаза 6: Уведомления

### Задача 10: Сервис уведомлений для родителей

Отправка уведомлений родителям в зависимости от платформы:
- Android SDK: push-уведомление (через Firebase FCM)
- Telegram (iPhone/веб): сообщение от `@palomatika_parent_bot`

**Файлы:**
- Создать: `app/Services/ParentNotificationService.php`
- Создать: `app/Notifications/ParentHomeworkReminder.php`
- Создать: `app/Notifications/ParentAttendanceUpdate.php`

**Шаг 1: Создать ParentNotificationService**

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ParentNotificationService
{
    /**
     * Уведомить родителя о событии, связанном с ребёнком.
     */
    public function notify(User $parent, string $message, string $type = 'info'): void
    {
        // Сначала пробуем Telegram-бота (работает для всех платформ)
        if ($parent->oauth_provider === 'telegram' && $parent->oauth_id) {
            $this->sendTelegramMessage($parent->oauth_id, $message);
        }

        // TODO: Добавить Firebase FCM push для пользователей Android SDK
        // if ($parent->fcm_token) { $this->sendFcmPush($parent, $message); }
    }

    /**
     * Уведомить всех родителей ученика.
     */
    public function notifyParentsOf(int $studentId, string $message, string $type = 'info'): void
    {
        $student = User::find($studentId);
        if (!$student) return;

        foreach ($student->parents as $parent) {
            $this->notify($parent, $message, $type);
        }
    }

    private function sendTelegramMessage(string $chatId, string $text): void
    {
        $token = config('services.telegram.parent_bot_token');
        if (!$token) {
            Log::warning('parent_bot_token не настроен, пропускаем Telegram-уведомление');
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);
        } catch (\Throwable $e) {
            Log::error('Не удалось отправить Telegram-уведомление родителю', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

**Шаг 2: Интегрировать с записью посещаемости** (в `ScheduleController::recordAttendance`)

После `LessonAttendance::updateOrCreate(...)`:

```php
// Уведомить родителя
$student = User::find($request->student_id);
$statusText = match ($request->status) {
    'present' => '✅ пришёл на урок',
    'absent' => '❌ не пришёл на урок',
    'late' => '⏰ опоздал на урок',
    'cancelled' => '🚫 урок отменён',
};
$message = "📚 {$student->name} — {$statusText}\n📅 {$request->lesson_date}";
app(ParentNotificationService::class)->notifyParentsOf($student->id, $message);
```

**Шаг 3: Коммит**

```bash
git add app/Services/ParentNotificationService.php app/Http/Controllers/Api/ScheduleController.php
git commit -m "feat: добавить сервис уведомлений родителей через Telegram-бота"
```

---

## Фаза 7: Админка — Привязка родитель-ученик

### Задача 11: Добавить artisan-команду для привязки родителей к ученикам

Поскольку привязка выполняется вручную, простая artisan-команда — самый быстрый путь.

**Файлы:**
- Создать: `app/Console/Commands/LinkParentCommand.php`

**Шаг 1: Создать команду**

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class LinkParentCommand extends Command
{
    protected $signature = 'parent:link {parent_id} {student_id} {--relation=parent}';
    protected $description = 'Привязать пользователя-родителя к пользователю-ученику';

    public function handle(): int
    {
        $parent = User::findOrFail($this->argument('parent_id'));
        $student = User::findOrFail($this->argument('student_id'));

        if ($parent->role !== 'parent') {
            $this->error("Пользователь #{$parent->id} ({$parent->name}) имеет роль '{$parent->role}', а не 'parent'");
            return 1;
        }

        if ($student->role !== 'student') {
            $this->error("Пользователь #{$student->id} ({$student->name}) имеет роль '{$student->role}', а не 'student'");
            return 1;
        }

        $parent->children()->syncWithoutDetaching([
            $student->id => ['relation' => $this->option('relation')],
        ]);

        $this->info("✅ Привязан родитель '{$parent->name}' (#{$parent->id}) → ученик '{$student->name}' (#{$student->id})");

        return 0;
    }
}
```

**Шаг 2: Добавить в whitelist artisan-команд DeployController**

Добавить `'parent:link'` в whitelist, чтобы можно было запускать удалённо.

**Шаг 3: Коммит**

```bash
git add app/Console/Commands/LinkParentCommand.php
git commit -m "feat: добавить artisan-команду parent:link для привязки родителей к ученикам"
```

---

## Итого — Инвентарь файлов

| Фаза | Новые файлы | Изменённые файлы |
|------|-------------|------------------|
| 1. БД | 2 миграции, 1 модель | `User.php` |
| 2. Диагностика | `DiagnosticService.php`, 2 views, 1 тест | `MiniAppStudentController.php`, `routes/web.php` |
| 3. Приложение для родителей | `ParentAuthController.php`, `ParentAppController.php`, 6+ views | `routes/web.php`, `VerifyCsrfToken.php`, `config/services.php` |
| 4. API | `Api/ParentController.php` | `routes/api.php` |
| 5. Посещаемость | — | `ScheduleController.php`, `routes/api.php` |
| 6. Уведомления | `ParentNotificationService.php` | `ScheduleController.php` |
| 7. Админка | `LinkParentCommand.php` | — |

## Порядок выполнения

```
Задача 1  → Задача 2  → Задача 3  → Задача 4  → Задача 5
(БД)        (БД)        (Сервис)    (Маршруты)   (Редирект)
                             ↓
Задача 6  → Задача 7  → Задача 8  → Задача 9  → Задача 10 → Задача 11
(Авторизация) (Views)    (API)     (Посещаем.)  (Уведомл.)   (Админка)
```

Задачи 1-2 должны идти первыми. Задачи 3-5 (диагностика) и 6-11 (приложение для родителей) можно выполнять параллельно разными агентами при желании.
