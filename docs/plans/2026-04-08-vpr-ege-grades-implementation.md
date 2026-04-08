# ВПР/ЕГЭ Grades 5–11 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Добавить поддержку классов 5–11: ВПР для 5–8 (grade 8 также имеет ОГЭ), ЕГЭ с полным attempt-flow для 10–11, автоматический перевод класса каждый июнь.

**Architecture:** Один новый столбец `exam_type` в `oge_variants` различает ОГЭ/ВПР/ЕГЭ варианты; все attempt-таблицы переиспользуются без изменений. Данные задний ВПР хранятся в `storage/app/tasks/vpr/grade_{N}/`, отдельный `VprTaskDataService(grade)`. Новые контроллеры и views для ВПР и ЕГЭ в PWA.

**Tech Stack:** PHP 8.2, Laravel 10, MySQL 8, Blade/Alpine.js, PHPUnit 10, Tailwind CDN

---

## Task 1: Миграция — добавить `exam_type` в `oge_variants`

**Files:**
- Create: `database/migrations/2026_04_08_000001_add_exam_type_to_oge_variants.php`
- Modify: `app/Models/OgeVariant.php`

**Step 1: Создать файл миграции**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('oge_variants', function (Blueprint $table) {
            $table->enum('exam_type', ['oge', 'vpr_5', 'vpr_6', 'vpr_7', 'vpr_8', 'ege'])
                  ->default('oge')
                  ->after('hash');
            $table->index('exam_type');
        });
    }

    public function down(): void
    {
        Schema::table('oge_variants', function (Blueprint $table) {
            $table->dropIndex(['exam_type']);
            $table->dropColumn('exam_type');
        });
    }
};
```

**Step 2: Добавить константы и `exam_type` в `OgeVariant` fillable/casts**

В `app/Models/OgeVariant.php` добавить константы и поле:

```php
public const EXAM_OGE  = 'oge';
public const EXAM_VPR5 = 'vpr_5';
public const EXAM_VPR6 = 'vpr_6';
public const EXAM_VPR7 = 'vpr_7';
public const EXAM_VPR8 = 'vpr_8';
public const EXAM_EGE  = 'ege';
```

В `$fillable` добавить `'exam_type'`.

**Step 3: Запустить миграцию**

```bash
php artisan migrate
```

Ожидается: `Migrating: 2026_04_08_000001_add_exam_type_to_oge_variants` → `Migrated`

**Step 4: Написать тест**

Файл: `tests/Unit/OgeVariantExamTypeTest.php`

```php
<?php
namespace Tests\Unit;

use App\Models\OgeVariant;
use PHPUnit\Framework\TestCase;

class OgeVariantExamTypeTest extends TestCase
{
    public function test_exam_type_constants_defined(): void
    {
        $this->assertSame('oge',   OgeVariant::EXAM_OGE);
        $this->assertSame('vpr_5', OgeVariant::EXAM_VPR5);
        $this->assertSame('vpr_6', OgeVariant::EXAM_VPR6);
        $this->assertSame('vpr_7', OgeVariant::EXAM_VPR7);
        $this->assertSame('vpr_8', OgeVariant::EXAM_VPR8);
        $this->assertSame('ege',   OgeVariant::EXAM_EGE);
    }

    public function test_exam_type_is_in_fillable(): void
    {
        $model = new OgeVariant();
        $this->assertContains('exam_type', $model->getFillable());
    }
}
```

**Step 5: Запустить тест**

```bash
php artisan test --filter OgeVariantExamTypeTest
```

Ожидается: 2 passed

**Step 6: Commit**

```bash
git add database/migrations/2026_04_08_000001_add_exam_type_to_oge_variants.php app/Models/OgeVariant.php tests/Unit/OgeVariantExamTypeTest.php
git commit -m "feat: add exam_type to oge_variants (oge/vpr_5-8/ege)"
```

---

## Task 2: `VprTaskDataService`

**Files:**
- Create: `app/Services/VprTaskDataService.php`
- Create: `tests/Unit/VprTaskDataServiceTest.php`

**Step 1: Написать тест**

```php
<?php
namespace Tests\Unit;

use App\Services\VprTaskDataService;
use PHPUnit\Framework\TestCase;

class VprTaskDataServiceTest extends TestCase
{
    public function test_base_path_resolves_by_grade(): void
    {
        $svc5 = new VprTaskDataService(5);
        $svc8 = new VprTaskDataService(8);

        // basePath is protected — test via topicExists returning false for missing file
        $this->assertFalse($svc5->topicDataExists('01')); // no files yet
        $this->assertFalse($svc8->topicDataExists('01'));
    }

    public function test_get_topic_meta_returns_defaults_for_unknown_topic(): void
    {
        $svc = new VprTaskDataService(5);
        $meta = $svc->getTopicMeta('99');
        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('color', $meta);
    }

    public function test_grade_5_has_18_topic_metas(): void
    {
        $svc = new VprTaskDataService(5);
        $this->assertCount(18, $svc->getAllTopicsMeta());
    }

    public function test_all_grades_have_18_topics(): void
    {
        foreach ([5, 6, 7, 8] as $grade) {
            $svc = new VprTaskDataService($grade);
            $this->assertCount(18, $svc->getAllTopicsMeta(),
                "Grade $grade should have 18 topics");
        }
    }
}
```

**Step 2: Запустить тест — убедиться что падает**

```bash
php artisan test --filter VprTaskDataServiceTest
```

Ожидается: FAIL — `VprTaskDataService not found`

**Step 3: Создать `VprTaskDataService`**

```php
<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Сервис данных ВПР — аналог TaskDataService, но per-grade.
 * Данные: storage/app/tasks/vpr/grade_{N}/topic_NN.json
 */
class VprTaskDataService
{
    protected string $basePath;

    // Метаданные тем одинаковы для всех классов — 18 заданий.
    // Заголовки будут уточнены когда придут PDF.
    protected array $topicsMeta = [
        '01' => ['title' => 'Задание 1',  'description' => '', 'color' => 'blue',    'icon' => 'calculator'],
        '02' => ['title' => 'Задание 2',  'description' => '', 'color' => 'cyan',    'icon' => 'calculator'],
        '03' => ['title' => 'Задание 3',  'description' => '', 'color' => 'teal',    'icon' => 'calculator'],
        '04' => ['title' => 'Задание 4',  'description' => '', 'color' => 'emerald', 'icon' => 'calculator'],
        '05' => ['title' => 'Задание 5',  'description' => '', 'color' => 'green',   'icon' => 'calculator'],
        '06' => ['title' => 'Задание 6',  'description' => '', 'color' => 'lime',    'icon' => 'calculator'],
        '07' => ['title' => 'Задание 7',  'description' => '', 'color' => 'yellow',  'icon' => 'calculator'],
        '08' => ['title' => 'Задание 8',  'description' => '', 'color' => 'amber',   'icon' => 'calculator'],
        '09' => ['title' => 'Задание 9',  'description' => '', 'color' => 'orange',  'icon' => 'calculator'],
        '10' => ['title' => 'Задание 10', 'description' => '', 'color' => 'red',     'icon' => 'calculator'],
        '11' => ['title' => 'Задание 11', 'description' => '', 'color' => 'rose',    'icon' => 'calculator'],
        '12' => ['title' => 'Задание 12', 'description' => '', 'color' => 'pink',    'icon' => 'calculator'],
        '13' => ['title' => 'Задание 13', 'description' => '', 'color' => 'fuchsia', 'icon' => 'calculator'],
        '14' => ['title' => 'Задание 14', 'description' => '', 'color' => 'purple',  'icon' => 'calculator'],
        '15' => ['title' => 'Задание 15', 'description' => '', 'color' => 'violet',  'icon' => 'calculator'],
        '16' => ['title' => 'Задание 16', 'description' => '', 'color' => 'indigo',  'icon' => 'calculator'],
        '17' => ['title' => 'Задание 17', 'description' => '', 'color' => 'sky',     'icon' => 'calculator'],
        '18' => ['title' => 'Задание 18', 'description' => '', 'color' => 'slate',   'icon' => 'calculator'],
    ];

    public function __construct(protected int $grade)
    {
        $this->basePath = storage_path("app/tasks/vpr/grade_{$grade}");

        if (!File::isDirectory($this->basePath)) {
            File::makeDirectory($this->basePath, 0755, true);
        }
    }

    public function getGrade(): int { return $this->grade; }

    public function getTopicMeta(string $topicId): array
    {
        return $this->topicsMeta[$topicId] ?? [
            'title' => "Задание $topicId", 'description' => '',
            'color' => 'gray', 'icon' => 'book',
        ];
    }

    public function getAllTopicsMeta(): array { return $this->topicsMeta; }

    public function topicDataExists(string $topicId): bool
    {
        return File::exists("{$this->basePath}/topic_{$topicId}.json");
    }

    public function getTopicData(string $topicId): array
    {
        $cacheKey = "vpr_g{$this->grade}_topic_{$topicId}";

        return Cache::remember($cacheKey, 3600, function () use ($topicId) {
            $path = "{$this->basePath}/topic_{$topicId}.json";
            if (!File::exists($path)) return [];
            $data = json_decode(File::get($path), true) ?? [];
            return $data;
        });
    }

    public function getBlocks(string $topicId): array
    {
        return $this->getTopicData($topicId)['blocks'] ?? [];
    }

    public function getTopicStats(string $topicId): array
    {
        $data  = $this->getTopicData($topicId);
        $total = 0;
        foreach ($data['blocks'] ?? [] as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $total += count($zadanie['tasks'] ?? []);
            }
        }
        return ['total_tasks' => $total];
    }

    /**
     * Выбрать случайную задачу из топика (статус production).
     */
    public function getRandomTaskFromTopic(string $topicId, ?string $status = 'production'): ?array
    {
        $data = $this->getTopicData($topicId);
        $candidates = [];

        foreach ($data['blocks'] ?? [] as $blockIdx => $block) {
            foreach ($block['zadaniya'] ?? [] as $zadIdx => $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    if ($status && ($task['status'] ?? 'production') !== $status) continue;
                    $candidates[] = [
                        'task'           => $task,
                        'topic_id'       => $topicId,
                        'block_number'   => $block['number'] ?? ($blockIdx + 1),
                        'zadanie_number' => $zadanie['number'] ?? ($zadIdx + 1),
                        'task_number'    => (int) ltrim($topicId, '0'),
                        'type'           => $zadanie['type'] ?? 'expression',
                        'instruction'    => $zadanie['instruction'] ?? '',
                    ];
                }
            }
        }

        if (empty($candidates)) return null;
        return $candidates[array_rand($candidates)];
    }
}
```

**Step 4: Запустить тест**

```bash
php artisan test --filter VprTaskDataServiceTest
```

Ожидается: 4 passed

**Step 5: Commit**

```bash
git add app/Services/VprTaskDataService.php tests/Unit/VprTaskDataServiceTest.php
git commit -m "feat: add VprTaskDataService for grade-specific VPR task data"
```

---

## Task 3: VPR JSON-заглушки (topic_01–18 для каждого класса)

**Files:**
- Create: `storage/app/tasks/vpr/grade_5/topic_01.json` … `topic_18.json` (и аналогично для 6, 7, 8)

**Step 1: Создать скрипт-генератор заглушек**

Файл: `scripts/create-vpr-stubs.php`

```php
<?php
// php scripts/create-vpr-stubs.php
// Создаёт пустые JSON-заглушки topic_01-18 для классов 5-8

foreach ([5, 6, 7, 8] as $grade) {
    $dir = __DIR__ . "/../storage/app/tasks/vpr/grade_{$grade}";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    for ($n = 1; $n <= 18; $n++) {
        $topicId = str_pad($n, 2, '0', STR_PAD_LEFT);
        $file = "{$dir}/topic_{$topicId}.json";

        if (file_exists($file)) {
            echo "SKIP  grade_{$grade}/topic_{$topicId}.json (already exists)\n";
            continue;
        }

        $stub = [
            'topic_id'  => $topicId,
            'exam_type' => 'vpr',
            'grade'     => $grade,
            'blocks'    => [],
        ];

        file_put_contents($file, json_encode($stub, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "OK    grade_{$grade}/topic_{$topicId}.json\n";
    }
}
echo "Done.\n";
```

**Step 2: Запустить скрипт**

```bash
php scripts/create-vpr-stubs.php
```

Ожидается: `OK grade_5/topic_01.json` … всего 72 файла.

**Step 3: Проверить что сервис видит файлы**

```bash
php artisan tinker --no-interaction <<'EOF'
$svc = new \App\Services\VprTaskDataService(5);
echo $svc->topicDataExists('01') ? "EXISTS\n" : "MISSING\n";
EOF
```

Ожидается: `EXISTS`

**Step 4: Commit**

```bash
git add storage/app/tasks/vpr/ scripts/create-vpr-stubs.php
git commit -m "feat: add VPR JSON stubs (grades 5-8, topics 01-18)"
```

---

## Task 4: `VprVariantBuilderService`

**Files:**
- Create: `app/Services/VprVariantBuilderService.php`
- Create: `tests/Unit/VprVariantBuilderServiceTest.php`

**Step 1: Написать тест**

```php
<?php
namespace Tests\Unit;

use App\Services\VprTaskDataService;
use App\Services\VprVariantBuilderService;
use PHPUnit\Framework\TestCase;

class VprVariantBuilderServiceTest extends TestCase
{
    private function makeService(int $grade, array $fakeTopicData = []): VprVariantBuilderService
    {
        $taskData = $this->getMockBuilder(VprTaskDataService::class)
            ->setConstructorArgs([$grade])
            ->onlyMethods(['getRandomTaskFromTopic'])
            ->getMock();

        $taskData->method('getRandomTaskFromTopic')
            ->willReturnCallback(function (string $topicId) use ($grade) {
                return [
                    'task'           => ['id' => 1, 'expression' => '2+2', 'answer' => '4'],
                    'topic_id'       => $topicId,
                    'block_number'   => 1,
                    'zadanie_number' => 1,
                    'task_number'    => (int) ltrim($topicId, '0'),
                    'type'           => 'expression',
                    'instruction'    => 'Вычислите',
                ];
            });

        return new VprVariantBuilderService($taskData);
    }

    public function test_build_returns_18_tasks(): void
    {
        $builder = $this->makeService(5);
        $result  = $builder->build('abc123');
        $this->assertCount(18, $result['tasks']);
    }

    public function test_build_is_deterministic(): void
    {
        $builder = $this->makeService(5);
        $r1 = $builder->build('hash-xyz');
        $r2 = $builder->build('hash-xyz');
        $this->assertSame($r1['variantNumber'], $r2['variantNumber']);
    }

    public function test_build_task_numbers_are_1_to_18(): void
    {
        $builder  = $this->makeService(5);
        $result   = $builder->build('test-hash');
        $numbers  = array_column($result['tasks'], 'task_number');
        sort($numbers);
        $this->assertSame(range(1, 18), $numbers);
    }
}
```

**Step 2: Запустить тест — убедиться что падает**

```bash
php artisan test --filter VprVariantBuilderServiceTest
```

Ожидается: FAIL — `VprVariantBuilderService not found`

**Step 3: Реализовать сервис**

```php
<?php
namespace App\Services;

class VprVariantBuilderService
{
    /** Все 18 топиков ВПР */
    protected array $allTopics = [
        '01','02','03','04','05','06','07','08','09',
        '10','11','12','13','14','15','16','17','18',
    ];

    public function __construct(private readonly VprTaskDataService $taskData) {}

    /**
     * Собрать вариант ВПР детерминированно из хэша.
     *
     * @return array{tasks: array, variantNumber: int}
     */
    public function build(string $hash): array
    {
        $seed = crc32($hash);
        mt_srand($seed);

        $variantNumber = (abs($seed) % 999) + 1;
        $tasks = [];

        foreach ($this->allTopics as $topicId) {
            $item = $this->taskData->getRandomTaskFromTopic($topicId, 'production');
            if (!$item) continue;

            $tasks[] = array_merge($item['task'], [
                'topic_id'       => $topicId,
                'topic_title'    => $this->taskData->getTopicMeta($topicId)['title'],
                'task_number'    => (int) ltrim($topicId, '0'),
                'type'           => $item['type'],
                'instruction'    => $item['instruction'],
                'block_number'   => $item['block_number'],
                'zadanie_number' => $item['zadanie_number'],
                'correct_answer' => $item['task']['answer'] ?? null,
            ]);
        }

        mt_srand(); // restore randomness

        return [
            'tasks'         => $tasks,
            'variantNumber' => $variantNumber,
        ];
    }
}
```

**Step 4: Запустить тест**

```bash
php artisan test --filter VprVariantBuilderServiceTest
```

Ожидается: 3 passed

**Step 5: Commit**

```bash
git add app/Services/VprVariantBuilderService.php tests/Unit/VprVariantBuilderServiceTest.php
git commit -m "feat: add VprVariantBuilderService (deterministic, 18 tasks)"
```

---

## Task 5: `VprVariantPoolService`

**Files:**
- Create: `app/Services/VprVariantPoolService.php`

**Step 1: Реализовать сервис**

Логика идентична `OgeVariantPoolService` — выбрать незашенный вариант из пула или создать новый. Отличие: использует `VprTaskDataService`, `VprVariantBuilderService`, и `exam_type` = `vpr_{grade}`.

```php
<?php
namespace App\Services;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Models\OgeVariantPoolEntry;
use App\Models\OgeVariantPoolTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VprVariantPoolService
{
    public function __construct(
        private readonly VprTaskDataService $taskData,
        private readonly VprVariantBuilderService $builder,
    ) {}

    public function getOrCreateVariant(User $user): OgeVariant
    {
        $examType = 'vpr_' . $this->taskData->getGrade();
        $poolEntry = $this->findUnsolvedVariant($user, $examType);

        if ($poolEntry) {
            return $poolEntry->variant;
        }

        return $this->generateNewVariant($examType);
    }

    protected function findUnsolvedVariant(User $user, string $examType): ?OgeVariantPoolEntry
    {
        $attempted = OgeAttempt::where('student_id', $user->id)->pluck('variant_id');

        return OgeVariantPoolEntry::active()
            ->whereHas('variant', fn($q) => $q->where('exam_type', $examType))
            ->whereNotIn('variant_id', $attempted)
            ->inRandomOrder()
            ->first();
    }

    protected function generateNewVariant(string $examType, int $maxRetries = 8): OgeVariant
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            $built = $this->builder->build(Str::random(12));

            if (empty($built['tasks'])) {
                throw new \RuntimeException("No production tasks for $examType");
            }

            $fingerprint = md5(json_encode(
                array_map(fn($t) => $t['topic_id'] . '_' . ($t['id'] ?? ''), $built['tasks'])
            ));

            if (OgeVariantPoolEntry::where('task_fingerprint', $fingerprint)->exists()) {
                continue;
            }

            return DB::transaction(function () use ($examType, $built, $fingerprint) {
                $hash = strtolower(Str::random(6));
                while (OgeVariant::where('hash', $hash)->exists()) {
                    $hash = strtolower(Str::random(6));
                }

                $grade = (int) explode('_', $examType)[1];

                $variant = OgeVariant::create([
                    'hash'       => $hash,
                    'exam_type'  => $examType,
                    'title'      => "Вариант ВПР {$grade} класс",
                    'mode'       => OgeVariant::MODE_FULL,
                    'source'     => OgeVariant::SOURCE_MINIAPP,
                    'config_json'=> ['tasks' => $built['tasks']],
                ]);

                $poolEntry = OgeVariantPoolEntry::create([
                    'variant_id'       => $variant->id,
                    'type'             => 'full',
                    'status'           => 'active',
                    'task_fingerprint' => $fingerprint,
                    'created_at'       => now(),
                ]);

                foreach ($built['tasks'] as $idx => $task) {
                    OgeVariantPoolTask::create([
                        'pool_id'        => $poolEntry->id,
                        'topic_id'       => $task['topic_id'],
                        'block_number'   => $task['block_number'] ?? 1,
                        'zadanie_number' => $task['zadanie_number'] ?? 1,
                        'task_id'        => $task['id'] ?? 0,
                        'sort_order'     => $idx + 1,
                    ]);
                }

                return $variant;
            });
        }

        throw new \RuntimeException("Could not generate unique VPR variant after {$maxRetries} retries");
    }
}
```

**Step 2: Зарегистрировать в ServiceProvider**

В `app/Providers/AppServiceProvider.php` в метод `register()` добавить:

```php
// VPR services — grade-specific, bound with factory
$this->app->bind(\App\Services\VprTaskDataService::class, function ($app, $params) {
    $grade = $params['grade'] ?? (auth()->user()?->grade_num ?? 5);
    return new \App\Services\VprTaskDataService((int)$grade);
});
```

**Step 3: Commit**

```bash
git add app/Services/VprVariantPoolService.php app/Providers/AppServiceProvider.php
git commit -m "feat: add VprVariantPoolService"
```

---

## Task 6: `VprController` и PWA роуты

**Files:**
- Create: `app/Http/Controllers/Pwa/VprController.php`
- Modify: `routes/pwa.php`

**Step 1: Создать контроллер**

```php
<?php
namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Services\OgeAttemptService;
use App\Services\VprTaskDataService;
use App\Services\VprVariantBuilderService;
use App\Services\VprVariantPoolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VprController extends Controller
{
    private function makePool(int $grade): VprVariantPoolService
    {
        $taskData = new VprTaskDataService($grade);
        $builder  = new VprVariantBuilderService($taskData);
        return new VprVariantPoolService($taskData, $builder);
    }

    /**
     * ВПР-дашборд (home page).
     */
    public function home(Request $request)
    {
        $user  = Auth::user();
        $grade = $user->grade_num;

        // Активные незавершённые попытки ВПР этого ученика
        $examType   = 'vpr_' . $grade;
        $activeAttempts = OgeAttempt::where('student_id', $user->id)
            ->where('status', 'active')
            ->where('last_seen_at', '>=', now()->subDays(7))
            ->whereHas('variant', fn($q) => $q->where('exam_type', $examType))
            ->with('variant')
            ->orderByDesc('last_seen_at')
            ->get();

        $activeList = $activeAttempts->map(function ($att) {
            $variant = $att->variant;
            return [
                'id'            => $att->id,
                'title'         => $variant->title ?? 'Вариант ВПР',
                'answeredCount' => $att->answers()->count(),
                'totalCount'    => count($variant->config_json['tasks'] ?? []),
                'startedAt'     => $att->started_at,
            ];
        })->all();

        return view('pwa.student.vpr-home', compact('user', 'grade', 'activeList'));
    }

    /**
     * Старт нового варианта ВПР (POST).
     */
    public function startFull(Request $request, OgeAttemptService $attemptService)
    {
        $user  = Auth::user();
        $grade = $user->grade_num;

        if ($grade < 5 || $grade > 8) {
            abort(403, 'ВПР доступно только для 5–8 классов');
        }

        $pool    = $this->makePool($grade);
        $variant = $pool->getOrCreateVariant($user);

        [$variant, $attempt] = $attemptService->startAttempt($user, $variant->hash, [
            'user_agent' => $request->userAgent(),
            'ip'         => $request->ip(),
        ]);

        return redirect()->route('pwa.student.vpr.test', $attempt->id);
    }

    /**
     * Страница решения варианта ВПР.
     */
    public function test(Request $request, int $attemptId, OgeAttemptService $attemptService)
    {
        $user    = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->with('variant')
            ->firstOrFail();

        $variant    = $attempt->variant;
        $tasks      = $attemptService->buildAttemptPayload($attempt);
        $answers    = $attempt->answers()->pluck('current_answer', 'task_number');
        $isSubmitted = $attempt->status === 'submitted';

        return view('pwa.student.vpr-test', compact(
            'user', 'attempt', 'variant', 'tasks', 'answers', 'isSubmitted'
        ));
    }

    /**
     * Страница результатов ВПР.
     */
    public function results(Request $request, int $attemptId)
    {
        $user    = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->where('status', 'submitted')
            ->with(['variant', 'scorings'])
            ->firstOrFail();

        $scoring = OgeAttemptScoring::where('attempt_id', $attempt->id)->first();

        return view('pwa.student.vpr-results', compact('user', 'attempt', 'scoring'));
    }
}
```

**Step 2: Добавить роуты в `routes/pwa.php`**

Внутри блока `Route::middleware(['auth', 'pwa.onboarding'])->group(...)` добавить:

```php
// ВПР (5–8 класс)
Route::prefix('vpr')->name('pwa.student.vpr.')->group(function () {
    Route::get('/',                  [VprController::class, 'home'])      ->name('home');
    Route::post('/full/start',       [VprController::class, 'startFull']) ->name('start');
    Route::get('/test/{attemptId}',  [VprController::class, 'test'])      ->name('test');
    Route::get('/results/{attemptId}',[VprController::class, 'results'])  ->name('results');
});
```

И добавить `use App\Http\Controllers\Pwa\VprController;` вверху файла.

**Step 3: Проверить что роуты зарегистрированы**

```bash
php artisan route:list | grep vpr
```

Ожидается: 4 строки с `pwa.student.vpr.*`

**Step 4: Commit**

```bash
git add app/Http/Controllers/Pwa/VprController.php routes/pwa.php
git commit -m "feat: add VprController and VPR PWA routes"
```

---

## Task 7: VPR Views (vpr-home, vpr-test, vpr-results)

**Files:**
- Create: `resources/views/pwa/student/vpr-home.blade.php`
- Create: `resources/views/pwa/student/vpr-test.blade.php`
- Create: `resources/views/pwa/student/vpr-results.blade.php`

**Step 1: Создать `vpr-home.blade.php`**

Скопировать структуру из `resources/views/pwa/student/dashboard.blade.php`, заменить:
- Заголовок и `<title>` на "ВПР по математике — N класс"
- Кнопку старта на POST к `route('pwa.student.vpr.start')`
- Навигацию — убрать ОГЭ-специфичные ссылки
- Бейдж "ОГЭ" на "ВПР"
- Обратную ссылку к ОГЭ показывать только для 8-го класса (`@if($grade === 8)`)

Минимальный вариант `vpr-home.blade.php`:

```blade
@extends('layouts.pwa')
@section('title', 'ВПР ' . $grade . ' класс — palomatika')

@section('body')
<div class="home-container" style="min-height:100dvh;padding:24px 20px;max-width:480px;margin:0 auto;">

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;">
    <div style="font-family:var(--display);font-size:15px;color:var(--accent);">palomatika</div>
    <div style="background:var(--accent-bg);border:1px solid var(--accent);color:var(--accent);
                font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;letter-spacing:.08em;">
      ВПР · {{ $grade }} КЛАСС
    </div>
  </div>

  <div style="text-align:center;padding:32px 0 40px;">
    <div style="font-size:11px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;
                color:var(--muted);margin-bottom:16px;">Математика</div>
    <h1 style="font-family:var(--display);font-size:clamp(28px,8vw,40px);line-height:1.1;margin-bottom:8px;">
      Подготовка к <em style="color:var(--accent);">ВПР</em>
    </h1>
    <p style="font-size:14px;color:var(--muted);font-weight:600;">{{ $grade }} класс · 18 заданий</p>
  </div>

  {{-- Незавершённые попытки --}}
  @foreach($activeList as $att)
  <a href="{{ route('pwa.student.vpr.test', $att['id']) }}"
     style="display:block;background:var(--surface);border:1.5px solid var(--accent);
            border-radius:16px;padding:16px;margin-bottom:12px;text-decoration:none;">
    <div style="font-size:13px;font-weight:800;color:var(--text);">{{ $att['title'] }}</div>
    <div style="font-size:12px;color:var(--muted);margin-top:4px;">
      Отвечено {{ $att['answeredCount'] }} из {{ $att['totalCount'] }} · Продолжить →
    </div>
  </a>
  @endforeach

  <form method="POST" action="{{ route('pwa.student.vpr.start') }}">
    @csrf
    <button type="submit"
            style="width:100%;background:var(--accent);color:#fff;border:none;border-radius:14px;
                   padding:18px;font-family:var(--body);font-size:16px;font-weight:800;cursor:pointer;">
      Начать вариант ВПР
    </button>
  </form>

  @if($grade === 8)
  <a href="{{ route('pwa.student.dashboard') }}"
     style="display:block;text-align:center;margin-top:16px;font-size:13px;
            color:var(--muted);font-weight:600;">
    Перейти к подготовке к ОГЭ →
  </a>
  @endif

  {{-- История --}}
  <a href="{{ route('pwa.student.history') }}"
     style="display:block;text-align:center;margin-top:20px;font-size:13px;
            color:var(--muted);font-weight:600;">История попыток</a>
</div>
@endsection
```

**Step 2: Создать `vpr-test.blade.php`**

Скопировать `resources/views/pwa/student/test.blade.php` полностью.
Заменить все `route('pwa.student.results', ...)` на `route('pwa.student.vpr.results', ...)`.
Заголовок изменить на "ВПР · $grade класс".

**Step 3: Создать `vpr-results.blade.php`**

Скопировать `resources/views/pwa/student/results.blade.php` полностью.
Заменить ссылку "на главную" на `route('pwa.student.vpr.home')`.
Заголовок — "Результаты ВПР".

**Step 4: Commit**

```bash
git add resources/views/pwa/student/vpr-home.blade.php \
        resources/views/pwa/student/vpr-test.blade.php \
        resources/views/pwa/student/vpr-results.blade.php
git commit -m "feat: add VPR PWA views (home, test, results)"
```

---

## Task 8: `EgeVariantBuilderService`

**Files:**
- Create: `app/Services/EgeVariantBuilderService.php`
- Create: `tests/Unit/EgeVariantBuilderServiceTest.php`

**Step 1: Написать тест**

```php
<?php
namespace Tests\Unit;

use App\Services\EgeTaskDataService;
use App\Services\EgeVariantBuilderService;
use PHPUnit\Framework\TestCase;

class EgeVariantBuilderServiceTest extends TestCase
{
    private function makeService(): EgeVariantBuilderService
    {
        $taskData = $this->getMockBuilder(EgeTaskDataService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRandomTaskFromTopic'])
            ->getMock();

        $taskData->method('getRandomTaskFromTopic')
            ->willReturnCallback(function (string $topicId) {
                return [
                    'task'           => ['id' => 1, 'expression' => 'x+1', 'answer' => '2'],
                    'topic_id'       => $topicId,
                    'block_number'   => 1,
                    'zadanie_number' => 1,
                    'task_number'    => (int) ltrim($topicId, '0'),
                    'type'           => 'expression',
                    'instruction'    => 'Решите',
                ];
            });

        return new EgeVariantBuilderService($taskData);
    }

    public function test_build_returns_20_tasks(): void
    {
        $result = $this->makeService()->build('hash123');
        $this->assertCount(20, $result['tasks']);
    }

    public function test_build_is_deterministic(): void
    {
        $svc = $this->makeService();
        $this->assertSame(
            $svc->build('same-hash')['variantNumber'],
            $svc->build('same-hash')['variantNumber']
        );
    }
}
```

**Step 2: Добавить `getRandomTaskFromTopic` в `EgeTaskDataService`**

В `app/Services/EgeTaskDataService.php` добавить метод (по образцу из `VprTaskDataService`):

```php
public function getRandomTaskFromTopic(string $topicId, ?string $status = 'production'): ?array
{
    $data = $this->getTopicData($topicId); // уже существует
    $candidates = [];

    foreach ($data['blocks'] ?? [] as $blockIdx => $block) {
        foreach ($block['zadaniya'] ?? [] as $zadIdx => $zadanie) {
            foreach ($zadanie['tasks'] ?? [] as $task) {
                if ($status && ($task['status'] ?? 'production') !== $status) continue;
                $candidates[] = [
                    'task'           => $task,
                    'topic_id'       => $topicId,
                    'block_number'   => $block['number'] ?? ($blockIdx + 1),
                    'zadanie_number' => $zadanie['number'] ?? ($zadIdx + 1),
                    'task_number'    => (int) ltrim($topicId, '0'),
                    'type'           => $zadanie['type'] ?? 'expression',
                    'instruction'    => $zadanie['instruction'] ?? '',
                ];
            }
        }
    }

    if (empty($candidates)) return null;
    return $candidates[array_rand($candidates)];
}
```

**Step 3: Реализовать `EgeVariantBuilderService`**

```php
<?php
namespace App\Services;

class EgeVariantBuilderService
{
    protected array $allTopics = [
        '01','02','03','04','05','06','07','08','09','10',
        '11','12','13','14','15','16','17','18','19','20',
    ];

    public function __construct(private readonly EgeTaskDataService $taskData) {}

    public function build(string $hash): array
    {
        $seed = crc32($hash);
        mt_srand($seed);

        $variantNumber = (abs($seed) % 999) + 1;
        $tasks = [];

        foreach ($this->allTopics as $topicId) {
            $item = $this->taskData->getRandomTaskFromTopic($topicId, 'production');
            if (!$item) continue;

            $tasks[] = array_merge($item['task'], [
                'topic_id'       => $topicId,
                'topic_title'    => $this->taskData->getTopicMeta($topicId)['title'],
                'task_number'    => (int) ltrim($topicId, '0'),
                'type'           => $item['type'],
                'instruction'    => $item['instruction'],
                'block_number'   => $item['block_number'],
                'zadanie_number' => $item['zadanie_number'],
                'correct_answer' => $item['task']['answer'] ?? null,
            ]);
        }

        mt_srand();

        return ['tasks' => $tasks, 'variantNumber' => $variantNumber];
    }
}
```

**Step 4: Запустить тесты**

```bash
php artisan test --filter EgeVariantBuilderServiceTest
```

Ожидается: 2 passed

**Step 5: Commit**

```bash
git add app/Services/EgeVariantBuilderService.php \
        app/Services/EgeTaskDataService.php \
        tests/Unit/EgeVariantBuilderServiceTest.php
git commit -m "feat: add EgeVariantBuilderService + getRandomTaskFromTopic in EgeTaskDataService"
```

---

## Task 9: `EgeVariantPoolService`

**Files:**
- Create: `app/Services/EgeVariantPoolService.php`

**Step 1: Реализовать (аналог `VprVariantPoolService`, `exam_type = 'ege'`)**

```php
<?php
namespace App\Services;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Models\OgeVariantPoolEntry;
use App\Models\OgeVariantPoolTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EgeVariantPoolService
{
    public function __construct(
        private readonly EgeTaskDataService $taskData,
        private readonly EgeVariantBuilderService $builder,
    ) {}

    public function getOrCreateVariant(User $user): OgeVariant
    {
        $attempted = OgeAttempt::where('student_id', $user->id)->pluck('variant_id');

        $poolEntry = OgeVariantPoolEntry::active()
            ->whereHas('variant', fn($q) => $q->where('exam_type', OgeVariant::EXAM_EGE))
            ->whereNotIn('variant_id', $attempted)
            ->inRandomOrder()
            ->first();

        if ($poolEntry) return $poolEntry->variant;

        return $this->generateNewVariant(8);
    }

    protected function generateNewVariant(int $maxRetries = 8): OgeVariant
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            $built = $this->builder->build(Str::random(12));
            if (empty($built['tasks'])) throw new \RuntimeException('No EGE production tasks');

            $fingerprint = md5(json_encode(
                array_map(fn($t) => $t['topic_id'] . '_' . ($t['id'] ?? ''), $built['tasks'])
            ));

            if (OgeVariantPoolEntry::where('task_fingerprint', $fingerprint)->exists()) continue;

            return DB::transaction(function () use ($built, $fingerprint) {
                $hash = strtolower(Str::random(6));
                while (OgeVariant::where('hash', $hash)->exists()) {
                    $hash = strtolower(Str::random(6));
                }

                $variant = OgeVariant::create([
                    'hash'        => $hash,
                    'exam_type'   => OgeVariant::EXAM_EGE,
                    'title'       => 'Вариант ЕГЭ',
                    'mode'        => OgeVariant::MODE_FULL,
                    'source'      => OgeVariant::SOURCE_MINIAPP,
                    'config_json' => ['tasks' => $built['tasks']],
                ]);

                $poolEntry = OgeVariantPoolEntry::create([
                    'variant_id'       => $variant->id,
                    'type'             => 'full',
                    'status'           => 'active',
                    'task_fingerprint' => $fingerprint,
                    'created_at'       => now(),
                ]);

                foreach ($built['tasks'] as $idx => $task) {
                    OgeVariantPoolTask::create([
                        'pool_id'        => $poolEntry->id,
                        'topic_id'       => $task['topic_id'],
                        'block_number'   => $task['block_number'] ?? 1,
                        'zadanie_number' => $task['zadanie_number'] ?? 1,
                        'task_id'        => $task['id'] ?? 0,
                        'sort_order'     => $idx + 1,
                    ]);
                }

                return $variant;
            });
        }
        throw new \RuntimeException('Could not generate unique EGE variant');
    }
}
```

**Step 2: Commit**

```bash
git add app/Services/EgeVariantPoolService.php
git commit -m "feat: add EgeVariantPoolService"
```

---

## Task 10: EGE PWA Controller + Routes + Views

**Files:**
- Create: `app/Http/Controllers/Pwa/EgeStudentController.php`
- Modify: `routes/pwa.php`
- Create: `resources/views/pwa/student/ege-home.blade.php`
- Create: `resources/views/pwa/student/ege-test.blade.php`
- Create: `resources/views/pwa/student/ege-results.blade.php`

**Step 1: Создать `EgeStudentController`**

По образцу `VprController`, но:
- Проверка `grade_num IN (10, 11)`
- `exam_type = 'ege'`
- Роуты `pwa.student.ege.*`

```php
<?php
namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Services\EgeTaskDataService;
use App\Services\EgeVariantBuilderService;
use App\Services\EgeVariantPoolService;
use App\Services\OgeAttemptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EgeStudentController extends Controller
{
    private function makePool(): EgeVariantPoolService
    {
        $taskData = app(EgeTaskDataService::class);
        $builder  = new EgeVariantBuilderService($taskData);
        return new EgeVariantPoolService($taskData, $builder);
    }

    public function home(Request $request)
    {
        $user  = Auth::user();
        $grade = $user->grade_num;

        $activeAttempts = OgeAttempt::where('student_id', $user->id)
            ->where('status', 'active')
            ->where('last_seen_at', '>=', now()->subDays(7))
            ->whereHas('variant', fn($q) => $q->where('exam_type', 'ege'))
            ->with('variant')
            ->orderByDesc('last_seen_at')
            ->get();

        $activeList = $activeAttempts->map(function ($att) {
            $v = $att->variant;
            return [
                'id'            => $att->id,
                'title'         => $v->title ?? 'Вариант ЕГЭ',
                'answeredCount' => $att->answers()->count(),
                'totalCount'    => count($v->config_json['tasks'] ?? []),
                'startedAt'     => $att->started_at,
            ];
        })->all();

        return view('pwa.student.ege-home', compact('user', 'grade', 'activeList'));
    }

    public function startFull(Request $request, OgeAttemptService $attemptService)
    {
        $user  = Auth::user();
        if ($user->grade_num < 10 || $user->grade_num > 11) {
            abort(403, 'ЕГЭ доступно только для 10–11 классов');
        }

        $variant = $this->makePool()->getOrCreateVariant($user);
        [$variant, $attempt] = $attemptService->startAttempt($user, $variant->hash, [
            'user_agent' => $request->userAgent(),
            'ip'         => $request->ip(),
        ]);

        return redirect()->route('pwa.student.ege.test', $attempt->id);
    }

    public function test(Request $request, int $attemptId, OgeAttemptService $attemptService)
    {
        $user    = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->with('variant')
            ->firstOrFail();

        $variant     = $attempt->variant;
        $tasks       = $attemptService->buildAttemptPayload($attempt);
        $answers     = $attempt->answers()->pluck('current_answer', 'task_number');
        $isSubmitted = $attempt->status === 'submitted';

        return view('pwa.student.ege-test', compact(
            'user', 'attempt', 'variant', 'tasks', 'answers', 'isSubmitted'
        ));
    }

    public function results(Request $request, int $attemptId)
    {
        $user    = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->where('status', 'submitted')
            ->with(['variant', 'scorings'])
            ->firstOrFail();

        $scoring = OgeAttemptScoring::where('attempt_id', $attempt->id)->first();

        return view('pwa.student.ege-results', compact('user', 'attempt', 'scoring'));
    }
}
```

**Step 2: Добавить ЕГЭ роуты в `routes/pwa.php`**

```php
// ЕГЭ (10–11 класс)
Route::prefix('ege-app')->name('pwa.student.ege.')->group(function () {
    Route::get('/',                   [EgeStudentController::class, 'home'])      ->name('home');
    Route::post('/full/start',        [EgeStudentController::class, 'startFull']) ->name('start');
    Route::get('/test/{attemptId}',   [EgeStudentController::class, 'test'])      ->name('test');
    Route::get('/results/{attemptId}',[EgeStudentController::class, 'results'])   ->name('results');
});
```

**Step 3: Создать views**

`ege-home.blade.php` — копия `vpr-home.blade.php`, заменить:
- Бейдж "ВПР · N КЛАСС" на "ЕГЭ · N КЛАСС"
- "18 заданий" на "20 заданий"
- Кнопку старта на `route('pwa.student.ege.start')`
- Убрать ссылку "Перейти к ОГЭ"

`ege-test.blade.php` — копия `vpr-test.blade.php`, заменить route на `pwa.student.ege.results`.

`ege-results.blade.php` — копия `vpr-results.blade.php`, заменить route на `pwa.student.ege.home`.

**Step 4: Commit**

```bash
git add app/Http/Controllers/Pwa/EgeStudentController.php \
        routes/pwa.php \
        resources/views/pwa/student/ege-home.blade.php \
        resources/views/pwa/student/ege-test.blade.php \
        resources/views/pwa/student/ege-results.blade.php
git commit -m "feat: add EGE PWA controller, routes and views"
```

---

## Task 11: Grade-based routing в PWA StudentController

**Files:**
- Modify: `app/Http/Controllers/Pwa/StudentController.php`

**Step 1: Добавить метод `gradeHome()` и вызов в `dashboard()`**

В начало метода `dashboard()` в `StudentController` добавить редирект по классу:

```php
public function dashboard(Request $request)
{
    $user  = Auth::user();
    $grade = (int) ($user->grade_num ?? 9);

    // Перенаправить на нужный дашборд по классу
    if ($grade >= 5 && $grade <= 8) {
        return redirect()->route('pwa.student.vpr.home');
    }
    if ($grade >= 10 && $grade <= 11) {
        return redirect()->route('pwa.student.ege.home');
    }
    if ($grade === 12) {
        return redirect()->route('pwa.student.history'); // выпускник
    }

    // grade === 9 → существующий ОГЭ dashboard (продолжаем)
    // ... (существующий код без изменений)
```

> **Важно:** редирект вставить **до** любой логики загрузки данных, чтобы не делать лишних запросов.

**Step 2: Написать Feature тест**

```php
// tests/Feature/GradeRoutingTest.php
<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(int $grade): User
    {
        return User::factory()->create([
            'role'                   => 'student',
            'grade_num'              => $grade,
            'grade_letter'           => 'А',
            'school_number'          => '1',
            'city'                   => 'Чехов',
            'onboarding_completed_at'=> now(),
        ]);
    }

    public function test_grade_5_redirects_to_vpr(): void
    {
        $user = $this->makeStudent(5);
        $this->actingAs($user)
             ->get('https://student.' . config('app.base_domain') . '/')
             ->assertRedirect(route('pwa.student.vpr.home'));
    }

    public function test_grade_9_stays_on_oge_dashboard(): void
    {
        $user = $this->makeStudent(9);
        $response = $this->actingAs($user)
             ->get('https://student.' . config('app.base_domain') . '/');
        // Grade 9 — no redirect, renders OGE dashboard
        $response->assertStatus(200);
    }

    public function test_grade_10_redirects_to_ege(): void
    {
        $user = $this->makeStudent(10);
        $this->actingAs($user)
             ->get('https://student.' . config('app.base_domain') . '/')
             ->assertRedirect(route('pwa.student.ege.home'));
    }

    public function test_grade_12_redirects_to_history(): void
    {
        $user = $this->makeStudent(12);
        $this->actingAs($user)
             ->get('https://student.' . config('app.base_domain') . '/')
             ->assertRedirect(route('pwa.student.history'));
    }
}
```

**Step 3: Запустить тесты**

```bash
php artisan test --filter GradeRoutingTest
```

Ожидается: 4 passed

**Step 4: Commit**

```bash
git add app/Http/Controllers/Pwa/StudentController.php tests/Feature/GradeRoutingTest.php
git commit -m "feat: grade-based PWA routing (5-8→VPR, 10-11→EGE, 12→history)"
```

---

## Task 12: Онбординг — расширить классы и буквы

**Files:**
- Modify: `app/Http/Controllers/Pwa/StudentController.php` (saveOnboarding)
- Modify: `app/Http/Controllers/MiniAppAuthController.php`
- Modify: `resources/views/pwa/student/onboarding.blade.php`

**Step 1: Обновить валидацию в `StudentController@saveOnboarding`**

```php
// Было:
'grade_num'    => 'required|integer|in:9',
'grade_letter' => 'required|string|in:А,Б,В,Г,Д,К,М',

// Стало:
'grade_num'    => 'required|integer|in:5,6,7,8,9,10,11',
'grade_letter' => 'required|string|in:А,Б,В,Г,Д,И,К,М',
```

**Step 2: Обновить валидацию в `MiniAppAuthController`** (такие же строки)

**Step 3: Обновить чипы в onboarding view**

В `resources/views/pwa/student/onboarding.blade.php` найти строку:
```js
<template x-for="g in [8, 9]" :key="g">
```
Заменить на:
```js
<template x-for="g in [5, 6, 7, 8, 9, 10, 11]" :key="g">
```

Найти строку с буквами:
```js
<template x-for="l in ['А','Б','В','Г','Д','К','М']" :key="l">
```
Заменить на:
```js
<template x-for="l in ['А','Б','В','Г','Д','И','К','М']" :key="l">
```

**Step 4: Написать тест**

```php
// tests/Feature/OnboardingGradeTest.php
<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingGradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_5_saves_correctly(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user)
             ->post('https://student.' . config('app.base_domain') . '/onboarding', [
                 'name'          => 'Тест',
                 'grade_num'     => 5,
                 'grade_letter'  => 'А',
                 'school_number' => '1',
                 'city'          => 'Москва',
             ])->assertRedirect();

        $this->assertSame(5, $user->fresh()->grade_num);
    }

    public function test_grade_4_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user)
             ->post('https://student.' . config('app.base_domain') . '/onboarding', [
                 'name'          => 'Тест',
                 'grade_num'     => 4,
                 'grade_letter'  => 'А',
                 'school_number' => '1',
             ])->assertSessionHasErrors('grade_num');
    }

    public function test_letter_и_is_accepted(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user)
             ->post('https://student.' . config('app.base_domain') . '/onboarding', [
                 'name'          => 'Тест',
                 'grade_num'     => 7,
                 'grade_letter'  => 'И',
                 'school_number' => '1',
             ])->assertRedirect();

        $this->assertSame('И', $user->fresh()->grade_letter);
    }
}
```

**Step 5: Запустить тесты**

```bash
php artisan test --filter OnboardingGradeTest
```

Ожидается: 3 passed

**Step 6: Commit**

```bash
git add app/Http/Controllers/Pwa/StudentController.php \
        app/Http/Controllers/MiniAppAuthController.php \
        resources/views/pwa/student/onboarding.blade.php \
        tests/Feature/OnboardingGradeTest.php
git commit -m "feat: expand onboarding to grades 5-11, add letter И"
```

---

## Task 13: Artisan-команда `grades:promote` + Scheduler

**Files:**
- Create: `app/Console/Commands/PromoteGrades.php`
- Modify: `app/Console/Kernel.php`
- Create: `tests/Feature/PromoteGradesCommandTest.php`

**Step 1: Написать тест**

```php
<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoteGradesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotes_grades_5_to_10_by_one(): void
    {
        foreach ([5, 6, 7, 8, 9, 10] as $grade) {
            User::factory()->create(['role' => 'student', 'grade_num' => $grade]);
        }

        $this->artisan('grades:promote')->assertExitCode(0);

        foreach ([6, 7, 8, 9, 10, 11] as $expected) {
            $this->assertDatabaseHas('users', ['grade_num' => $expected]);
        }
    }

    public function test_promotes_grade_11_to_12(): void
    {
        User::factory()->create(['role' => 'student', 'grade_num' => 11]);

        $this->artisan('grades:promote')->assertExitCode(0);

        $this->assertDatabaseHas('users', ['grade_num' => 12]);
    }

    public function test_does_not_change_teachers(): void
    {
        User::factory()->create(['role' => 'teacher', 'grade_num' => 9]);

        $this->artisan('grades:promote')->assertExitCode(0);

        $this->assertDatabaseHas('users', ['role' => 'teacher', 'grade_num' => 9]);
    }

    public function test_does_not_change_grade_12(): void
    {
        User::factory()->create(['role' => 'student', 'grade_num' => 12]);

        $this->artisan('grades:promote')->assertExitCode(0);

        $this->assertDatabaseHas('users', ['grade_num' => 12]);
        $this->assertDatabaseMissing('users', ['grade_num' => 13]);
    }
}
```

**Step 2: Запустить тест — убедиться что падает**

```bash
php artisan test --filter PromoteGradesCommandTest
```

Ожидается: FAIL — `grades:promote not found`

**Step 3: Создать команду**

```php
<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromoteGrades extends Command
{
    protected $signature   = 'grades:promote {--dry-run : Показать что будет изменено без реального обновления}';
    protected $description = 'Перевести учеников в следующий класс (запускать 1 июня ежегодно)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Считаем по текущим данным
        $counts = User::where('role', 'student')
            ->whereIn('grade_num', range(5, 11))
            ->selectRaw('grade_num, COUNT(*) as cnt')
            ->groupBy('grade_num')
            ->pluck('cnt', 'grade_num');

        $this->table(['Класс', 'Учеников', 'Станет'], collect($counts)->map(fn($cnt, $grade) => [
            $grade, $cnt, $grade < 11 ? $grade + 1 : '12 (выпускник)'
        ])->values()->all());

        if ($dryRun) {
            $this->warn('--dry-run: изменения НЕ применены.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Применить перевод классов?', true)) {
            $this->info('Отменено.');
            return self::SUCCESS;
        }

        DB::transaction(function () {
            // Сначала переводим 11→12, потом 10→11 ... 5→6 (обратный порядок важен)
            foreach (range(11, 5) as $grade) {
                $count = User::where('role', 'student')
                    ->where('grade_num', $grade)
                    ->update(['grade_num' => $grade + 1]);

                $this->line("  {$grade} → " . ($grade + 1) . ": {$count} учеников");
                Log::info("grades:promote: {$count} students promoted from grade {$grade} to " . ($grade + 1));
            }
        });

        $this->info('Готово. Классы обновлены.');
        return self::SUCCESS;
    }
}
```

**Step 4: Добавить в Kernel**

В `app/Console/Kernel.php` в метод `schedule()`:

```php
// Ежегодный перевод классов — 1 июня в 03:00
$schedule->command('grades:promote', ['--no-interaction'])
         ->yearlyOn(6, 1, '03:00')
         ->withoutOverlapping()
         ->runInBackground();
```

**Step 5: Запустить тесты**

```bash
php artisan test --filter PromoteGradesCommandTest
```

Ожидается: 4 passed

**Step 6: Commit**

```bash
git add app/Console/Commands/PromoteGrades.php \
        app/Console/Kernel.php \
        tests/Feature/PromoteGradesCommandTest.php
git commit -m "feat: add grades:promote command + yearly June 1st scheduler"
```

---

## Task 14: Учитель — показ номера класса

**Files:**
- Modify: `resources/views/pwa/teacher/` (partials со списком учеников)

**Step 1: Найти где отображаются имена учеников**

```bash
grep -rn "student.*name\|alias\|ученик" resources/views/pwa/teacher/ --include="*.blade.php" -l
```

**Step 2: В каждом таком файле добавить бейдж класса**

Рядом с именем ученика найти паттерн типа `{{ $student->name }}` или `{{ $alias }}` и добавить после:

```blade
@if($student->grade_num)
  <span style="font-size:10px;font-weight:800;color:var(--muted);
               background:var(--surface);border:1px solid var(--border);
               padding:2px 6px;border-radius:6px;margin-left:6px;">
    {{ $student->grade_num }}
  </span>
@endif
```

**Step 3: Commit**

```bash
git add resources/views/pwa/teacher/
git commit -m "feat: show grade number badge on student cards in teacher view"
```

---

## Task 15: Финальная проверка и deploy

**Step 1: Полный прогон тестов**

```bash
php artisan test
```

Ожидается: все тесты кроме заранее известного `LocalWebLoginTest` (pre-existing MySQL driver issue) — passed.

**Step 2: Проверить миграцию на production**

```bash
php artisan migrate:status
```

Убедиться что `2026_04_08_000001_add_exam_type_to_oge_variants` в статусе `Ran`.

**Step 3: Проверить роуты**

```bash
php artisan route:list | grep -E "vpr|ege-app"
```

Ожидается: 8 строк (4 VPR + 4 EGE-app).

**Step 4: Dry-run команды grades:promote**

```bash
php artisan grades:promote --dry-run
```

Убедиться что команда работает и показывает таблицу без изменений.

**Step 5: Commit финальный**

```bash
git add .
git commit -m "chore: final cleanup and route verification"
```

**Step 6: Push → auto-deploy**

```bash
git push
```

Ветки `claude/*` автоматически мержатся в `main` и деплоятся на production.

---

## Что НЕ в scope этого плана

- Наполнение JSON-файлов реальными заданиями ВПР (требует PDF — отдельная задача)
- Дизайн ВПР-дашборда (уточняется в процессе)
- Рейтинги, дуэли, домашние задания для ВПР/ЕГЭ
- Telegram Mini App обновление для новых классов
