# Variant Task Numbering Refactor

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Eliminate 19 duplicated task-number resolution patterns across 6 files by writing canonical `slot` and `exam_number` fields once at variant creation time, centralizing legacy resolution in one place, and migrating existing data.

**Architecture:** Every task in `config_json['tasks']` gets two canonical integer fields written at creation time: `slot` (1-based position used for answer storage, scoring, and attempt APIs) and `exam_number` (OGE topic number for display, e.g. 8, 14, 17). A single static resolver handles legacy variants that lack these fields. After a one-time migration command, all fallback code becomes dead and can be removed.

**Tech Stack:** PHP 8.2, Laravel 10, MySQL 8, PHPUnit

---

## Background: The Problem

The pattern `$taskData['attempt_task_number'] ?? $taskData['task_number'] ?? $taskData['test_number'] ?? (fallback)` appears **19 times in 6 files**, each with slightly different fallback logic. This has already caused bugs:
- History detail showed no task conditions (taskMap keyed by exam number, scoring keyed by slot)
- History showed wrong total (scored tasks only, missing unanswered)
- MiniAppTeacherController has the same unfixed bug right now

### Files with duplicated resolution (will all be touched):

| File | Occurrences |
|------|-------------|
| `app/Services/OgeAttemptService.php` | 7 |
| `app/Http/Controllers/MiniAppStudentController.php` | 4 |
| `app/Http/Controllers/MiniAppTeacherController.php` | 1 |
| `app/Http/Controllers/Teacher/StudentsController.php` | 4 |
| `app/Http/Controllers/Teacher/OgeReviewController.php` | 3 |
| `app/Http/Controllers/TestPdfController.php` | 2 |

### Current DB variant counts (small — safe to migrate):
- miniapp variants: 3 (mini_algebra, mini_mixed, mini_part2)
- custom_random: 12
- legacy (hash-based): 10

---

## Task 1: Create VariantTaskNumberResolver

**Files:**
- Create: `app/Services/VariantTaskNumberResolver.php`
- Test: `tests/Unit/VariantTaskNumberResolverTest.php`

### Step 1: Write the failing tests

```php
<?php
// tests/Unit/VariantTaskNumberResolverTest.php

namespace Tests\Unit;

use App\Models\OgeVariant;
use App\Services\VariantTaskNumberResolver;
use PHPUnit\Framework\TestCase;

class VariantTaskNumberResolverTest extends TestCase
{
    public function test_canonical_fields_returned_when_present(): void
    {
        $task = ['slot' => 3, 'exam_number' => 15, 'task_number' => 99];
        $variant = $this->makeVariant(null, null);

        $result = VariantTaskNumberResolver::resolve($task, 0, $variant);

        $this->assertSame(3, $result['slot']);
        $this->assertSame(15, $result['exam_number']);
    }

    public function test_mini_mode_uses_sequential_slot(): void
    {
        $task = ['task_number' => 15, 'topic_id' => '15'];
        $variant = $this->makeVariant('miniapp', 'mini_mixed');

        $result = VariantTaskNumberResolver::resolve($task, 2, $variant);

        $this->assertSame(3, $result['slot']);       // index 2 → slot 3
        $this->assertSame(15, $result['exam_number']); // from task_number
    }

    public function test_full_mode_slot_equals_exam_number(): void
    {
        $task = ['task_number' => 12];
        $variant = $this->makeVariant('miniapp', 'full');

        $result = VariantTaskNumberResolver::resolve($task, 4, $variant);

        $this->assertSame(12, $result['slot']);
        $this->assertSame(12, $result['exam_number']);
    }

    public function test_custom_random_uses_sequential_slot(): void
    {
        $task = ['task_number' => 8, 'test_number' => 5];
        $variant = $this->makeVariant('custom_random', null);

        $result = VariantTaskNumberResolver::resolve($task, 4, $variant);

        $this->assertSame(5, $result['slot']);      // from test_number
        $this->assertSame(8, $result['exam_number']);
    }

    public function test_legacy_hash_variant_with_no_fields_uses_topic_offset(): void
    {
        $task = ['topic_id' => '08'];
        $variant = $this->makeVariant(null, null);

        $result = VariantTaskNumberResolver::resolve($task, 2, $variant);

        $this->assertSame(8, $result['slot']);       // 6 + 2 = 8, but topic_id gives 8
        $this->assertSame(8, $result['exam_number']);
    }

    public function test_legacy_hash_variant_fallback_when_no_topic(): void
    {
        $task = [];
        $variant = $this->makeVariant(null, null);

        $result = VariantTaskNumberResolver::resolve($task, 2, $variant);

        $this->assertSame(8, $result['slot']);       // 6 + 2
        $this->assertSame(8, $result['exam_number']);
    }

    public function test_resolve_all_returns_indexed_by_slot(): void
    {
        $tasks = [
            ['task_number' => 8, 'topic_id' => '08', 'correct_answer' => '42'],
            ['task_number' => 15, 'topic_id' => '15', 'correct_answer' => '7'],
        ];
        $variant = $this->makeVariant('miniapp', 'mini_mixed');

        $result = VariantTaskNumberResolver::resolveAll($tasks, $variant);

        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertSame(8, $result[1]['exam_number']);
        $this->assertSame(15, $result[2]['exam_number']);
        $this->assertSame('42', $result[1]['task']['correct_answer']);
    }

    /**
     * Helper: create a minimal OgeVariant-like object without DB.
     */
    private function makeVariant(?string $source, ?string $mode): OgeVariant
    {
        $variant = new OgeVariant();
        $variant->source = $source;
        $variant->mode = $mode;
        return $variant;
    }
}
```

### Step 2: Run tests to verify they fail

Run: `php artisan test --filter=VariantTaskNumberResolverTest`
Expected: FAIL — class not found

### Step 3: Write the implementation

```php
<?php
// app/Services/VariantTaskNumberResolver.php

namespace App\Services;

use App\Models\OgeVariant;

class VariantTaskNumberResolver
{
    /**
     * Resolve the two canonical numbering fields for a single task.
     *
     * @param array  $task    Task data from config_json['tasks']
     * @param int    $index   0-based position in the tasks array
     * @param OgeVariant $variant The variant this task belongs to
     * @return array{slot: int, exam_number: int}
     */
    public static function resolve(array $task, int $index, OgeVariant $variant): array
    {
        // Canonical fields — if present, trust them unconditionally.
        if (isset($task['slot']) && isset($task['exam_number'])) {
            return [
                'slot' => (int) $task['slot'],
                'exam_number' => (int) $task['exam_number'],
            ];
        }

        // --- Legacy resolution (single place for all fallback logic) ---

        $examNumber = self::resolveExamNumber($task);
        $mode = (string) ($variant->mode ?? '');
        $isMini = str_starts_with($mode, 'mini_');
        $isCustomRandom = $variant->source === OgeVariant::SOURCE_CUSTOM_RANDOM;

        if ($isMini) {
            $slot = $index + 1;
        } elseif ($isCustomRandom) {
            $slot = (int) ($task['attempt_task_number'] ?? $task['test_number'] ?? ($index + 1));
        } else {
            // Legacy hash-based full variants: slot = exam number
            $slot = $examNumber > 0 ? $examNumber : (6 + $index);
        }

        if ($examNumber <= 0) {
            $examNumber = $slot;
        }

        return [
            'slot' => $slot,
            'exam_number' => $examNumber,
        ];
    }

    /**
     * Resolve all tasks in a variant, returning an array indexed by slot.
     *
     * @param array<int, array> $tasks  Tasks from config_json['tasks']
     * @param OgeVariant        $variant
     * @return array<int, array{slot: int, exam_number: int, task: array}>
     */
    public static function resolveAll(array $tasks, OgeVariant $variant): array
    {
        $result = [];

        foreach (array_values($tasks) as $index => $task) {
            if (!is_array($task)) {
                continue;
            }

            $numbers = self::resolve($task, $index, $variant);

            if ($numbers['slot'] < 1 || $numbers['slot'] > 255) {
                continue;
            }

            $result[$numbers['slot']] = [
                'slot' => $numbers['slot'],
                'exam_number' => $numbers['exam_number'],
                'task' => $task,
            ];
        }

        return $result;
    }

    /**
     * Extract exam number from any known field.
     */
    private static function resolveExamNumber(array $task): int
    {
        // task_number is the most common field for exam number
        $raw = $task['task_number'] ?? $task['zadanie_number'] ?? null;
        if ($raw !== null) {
            return (int) $raw;
        }

        // Fall back to topic_id (e.g. '08' → 8)
        $topicId = $task['topic_id'] ?? null;
        if ($topicId !== null) {
            return (int) ltrim((string) $topicId, '0');
        }

        return 0;
    }
}
```

### Step 4: Run tests to verify they pass

Run: `php artisan test --filter=VariantTaskNumberResolverTest`
Expected: All 7 tests PASS

### Step 5: Commit

```bash
git add app/Services/VariantTaskNumberResolver.php tests/Unit/VariantTaskNumberResolverTest.php
git commit -m "feat: add VariantTaskNumberResolver — single source of truth for slot/exam_number"
```

---

## Task 2: Write slot/exam_number at variant creation time

**Files:**
- Modify: `app/Services/OgeVariantPoolService.php:126` (config_json write)
- Modify: `app/Services/OgeVariantPoolService.php:228` (task_number assignment in generateVariantTasks)
- Test: `tests/Unit/OgeVariantPoolServiceTest.php` (extend existing test)

### Step 1: Write the failing test

Add to `tests/Unit/OgeVariantPoolServiceTest.php`:

```php
public function test_generated_tasks_have_canonical_slot_and_exam_number(): void
{
    // Reuse the anonymous class pattern from existing test
    $service = new class extends OgeVariantPoolService {
        public function __construct()
        {
            parent::__construct(
                app(TaskDataService::class),
                app(MiniAppTaskCanonicalizer::class),
            );
        }

        public function exposeGenerateVariantTasks(string $type): array
        {
            return $this->generateVariantTasks($type);
        }

        protected function pickRandomTopics(array $topics, int $count): array
        {
            return array_slice($topics, 0, $count);
        }

        protected function getUsedTaskIdsByTopic(): array
        {
            return [];
        }

        protected function pickTaskForTopic(string $topicId, ?string $status, array $excludeTaskIds): ?array
        {
            return [
                'topic_id' => $topicId,
                'task' => ['id' => (int) $topicId, 'answer' => $topicId],
            ];
        }
    };

    // Mixed: 4 alg + 3 geo = 7 tasks
    $tasks = $service->exposeGenerateVariantTasks('mixed');

    foreach ($tasks as $index => $task) {
        $this->assertArrayHasKey('slot', $task, "Task at index {$index} missing 'slot'");
        $this->assertArrayHasKey('exam_number', $task, "Task at index {$index} missing 'exam_number'");
        $this->assertSame($index + 1, $task['slot'], "Task at index {$index} has wrong slot");
        $this->assertGreaterThan(0, $task['exam_number']);
    }

    // Full: tasks 06-19
    $fullTasks = $service->exposeGenerateVariantTasks('full');
    foreach ($fullTasks as $task) {
        $this->assertSame($task['exam_number'], $task['slot'],
            "Full variant tasks should have slot == exam_number");
    }
}
```

### Step 2: Run test to verify it fails

Run: `php artisan test --filter=test_generated_tasks_have_canonical_slot_and_exam_number`
Expected: FAIL — 'slot' key not found

### Step 3: Implement — add slot/exam_number in generateVariantTasks

In `app/Services/OgeVariantPoolService.php`, modify `generateVariantTasks()`. After all tasks are collected and backfilled (around line 289, before `return $result`), add the slot assignment:

```php
// At end of generateVariantTasks(), before return $result:

// Assign canonical numbering: slot (1-based sequential) and exam_number (topic).
$isMini = in_array($type, ['geometry', 'algebra', 'mixed', 'part2'], true);
foreach ($result as $index => &$task) {
    $examNumber = (int) ($task['task_number'] ?? 0);
    $task['exam_number'] = $examNumber;
    $task['slot'] = $isMini ? ($index + 1) : $examNumber;
}
unset($task);

return $result;
```

**Note:** The existing `normalizeTaskForMiniApp()` call already runs `$this->taskCanonicalizer->normalizeForUi()` which sets `correct_answer`, `answer_kind`, etc. We just need to add `slot` and `exam_number` *after* that normalization, because the canonicalizer doesn't touch them.

### Step 4: Run all pool tests

Run: `php artisan test --filter=OgeVariantPoolServiceTest`
Expected: All tests PASS (both old and new)

### Step 5: Commit

```bash
git add app/Services/OgeVariantPoolService.php tests/Unit/OgeVariantPoolServiceTest.php
git commit -m "feat: write slot/exam_number at variant creation time in pool service"
```

---

## Task 3: Replace all 19 resolution sites with the resolver

This is the largest task. Work through each file methodically.

**Files:**
- Modify: `app/Services/OgeAttemptService.php` (7 sites)
- Modify: `app/Http/Controllers/MiniAppStudentController.php` (4 sites)
- Modify: `app/Http/Controllers/MiniAppTeacherController.php` (1 site — also fixes unfixed bug)
- Modify: `app/Http/Controllers/Teacher/StudentsController.php` (4 sites)
- Modify: `app/Http/Controllers/Teacher/OgeReviewController.php` (3 sites)
- Modify: `app/Http/Controllers/TestPdfController.php` (2 sites)
- Test: `tests/Feature/MiniAppAttemptFlowTest.php` (extend existing)

### Step 1: Add end-to-end test for mini history flow

Add to `tests/Feature/MiniAppAttemptFlowTest.php`:

```php
public function test_mini_history_detail_shows_task_conditions_and_correct_total(): void
{
    $student = User::factory()->create([
        'role' => 'student',
        'onboarding_completed_at' => now(),
    ]);

    $variant = OgeVariant::create([
        'hash' => 'histdet1',
        'title' => 'Мини-ОГЭ: Смешанное',
        'source' => OgeVariant::SOURCE_MINIAPP,
        'mode' => OgeVariant::MODE_MINI_MIXED,
        'config_json' => [
            'tasks' => [
                [
                    'task_number' => 8, 'topic_id' => '08',
                    'slot' => 1, 'exam_number' => 8,
                    'instruction' => 'Найдите значение выражения',
                    'expression' => '2+2',
                    'correct_answer' => '4',
                ],
                [
                    'task_number' => 15, 'topic_id' => '15',
                    'slot' => 2, 'exam_number' => 15,
                    'instruction' => 'Найдите площадь',
                    'expression' => '3*4',
                    'correct_answer' => '12',
                ],
                [
                    'task_number' => 10, 'topic_id' => '10',
                    'slot' => 3, 'exam_number' => 10,
                    'instruction' => 'Решите уравнение',
                    'expression' => 'x=5',
                    'correct_answer' => '5',
                ],
            ],
        ],
    ]);

    [, $attempt] = app(OgeAttemptService::class)->startAttempt($student, $variant->hash);

    // Submit answers for only 2 of 3 tasks (skip task in slot 2)
    $this->actingAs($student)
        ->postJson("/api/oge/attempts/{$attempt->id}/submit", [
            'answers' => [1 => '4', 3 => '99'],
        ])
        ->assertOk();

    // History detail page
    $response = $this->actingAs($student)
        ->get("/tg/history/{$attempt->id}")
        ->assertOk();

    // Total should be 3 (all tasks), not 2 (only scored)
    $this->assertSame(3, $response->viewData('total'));

    // Wrong tasks should include: slot 2 (unanswered) and slot 3 (wrong answer)
    $wrongTasks = $response->viewData('wrongTasks');
    $wrongNumbers = array_column($wrongTasks, 'task_number');
    sort($wrongNumbers);

    // task_number in view = exam_number, not slot
    $this->assertContains(10, $wrongNumbers, 'Slot 3 (exam 10) should be wrong');
    $this->assertContains(15, $wrongNumbers, 'Slot 2 (exam 15) should be unanswered/wrong');

    // Task conditions should be present
    $task15 = collect($wrongTasks)->firstWhere('task_number', 15);
    $this->assertNotEmpty($task15['task_text'] ?? $task15['task_instruction'] ?? '',
        'Unanswered task should have condition text');
}
```

### Step 2: Run test to verify it fails or passes

Run: `php artisan test --filter=test_mini_history_detail_shows_task_conditions_and_correct_total`
(It may pass with our recent fixes — that's OK. The test locks the behavior.)

### Step 3: Replace resolution sites file by file

**3a. OgeAttemptService.php**

Replace `normalizeConfiguredTasksForAttempt()` to use resolver:

```php
private function normalizeConfiguredTasksForAttempt(OgeAttempt $attempt, array $tasks): array
{
    $variant = $attempt->variant;
    $canonicalizer = $this->taskCanonicalizer ?? app(MiniAppTaskCanonicalizer::class);

    foreach ($tasks as $index => $taskData) {
        if (!is_array($taskData)) {
            continue;
        }

        $numbers = VariantTaskNumberResolver::resolve($taskData, $index, $variant);
        $taskData['attempt_task_number'] = $numbers['slot'];
        $taskData['display_task_number'] = $numbers['exam_number'];
        $taskData['slot'] = $numbers['slot'];
        $taskData['exam_number'] = $numbers['exam_number'];

        $taskData = $canonicalizer->normalizeForUi($taskData);
        $tasks[$index] = $taskData;
    }

    return $tasks;
}
```

Delete `shouldUseSequentialAttemptNumbers()` — no longer needed.

Replace the `$tn = (int) ($taskData['attempt_task_number'] ?? ...)` pattern in:
- `resolveVariantTasksByTaskNumber()` (line 726)
- `buildMapsFromCustomRandom()` (line 790)
- `buildMapsFromTaskArray()` (line 815)
- `resolveAttemptTaskNumbers()` (lines 1151, 1166)

All become:
```php
$numbers = VariantTaskNumberResolver::resolve($taskData, $index, $variant);
$tn = $numbers['slot'];
```

**3b. MiniAppStudentController.php**

Replace `normalizeAttemptTasksForMiniApp()`:

```php
private function normalizeAttemptTasksForMiniApp(OgeVariant $variant, array $tasks): array
{
    foreach ($tasks as $index => $task) {
        if (!is_array($task)) {
            continue;
        }

        $numbers = VariantTaskNumberResolver::resolve($task, $index, $variant);
        $task['display_task_number'] = $numbers['exam_number'];
        $task['attempt_task_number'] = $numbers['slot'];
        $task['slot'] = $numbers['slot'];
        $task['exam_number'] = $numbers['exam_number'];
        $tasks[$index] = $task;
    }

    return $tasks;
}
```

Replace `historyDetail()` taskMap building:

```php
$resolved = VariantTaskNumberResolver::resolveAll(
    $cfg['tasks'] ?? [],
    $attempt->variant
);
$taskMap = [];
$displayNumMap = [];
foreach ($resolved as $slot => $entry) {
    $taskMap[$slot] = $entry['task'];
    $displayNumMap[$slot] = $entry['exam_number'];
}
```

Replace `resolveExistingAttemptAnswersForMiniApp()` to use resolver.

**3c. MiniAppTeacherController.php**

Same pattern as student historyDetail. Replace lines 415-426 with resolver call. This also **fixes the existing bug** where teacher can't see task conditions for mini variants.

**3d. StudentsController.php**

Replace both branches of `resolveVariantTaskDefinitions()` to use `VariantTaskNumberResolver::resolve()`.

**3e. OgeReviewController.php**

Replace `resolveTaskColumns()` to use resolver for config tasks branch.

**3f. TestPdfController.php**

Replace the two sites (lines 4405 and 4856) to use resolver.

### Step 4: Run full test suite

Run: `php artisan test --filter=MiniAppAttemptFlowTest`
Run: `php artisan test --filter=OgeVariantPoolServiceTest`
Run: `php artisan test --filter=VariantTaskNumberResolverTest`
Expected: All PASS

### Step 5: Commit

```bash
git add app/Services/OgeAttemptService.php app/Services/VariantTaskNumberResolver.php \
    app/Http/Controllers/MiniAppStudentController.php \
    app/Http/Controllers/MiniAppTeacherController.php \
    app/Http/Controllers/Teacher/StudentsController.php \
    app/Http/Controllers/Teacher/OgeReviewController.php \
    app/Http/Controllers/TestPdfController.php \
    tests/Feature/MiniAppAttemptFlowTest.php
git commit -m "refactor: replace 19 duplicated task-number resolution sites with VariantTaskNumberResolver"
```

---

## Task 4: Migration command for existing variants

**Files:**
- Modify: `app/Console/Commands/NormalizeMiniAppVariants.php` (extend to add slot/exam_number)
- Test: manual verification via `php artisan` on prod

### Step 1: Update the existing normalize command

Rename to `variants:normalize-slots` and extend it to handle ALL variant sources (not just miniapp):

```php
<?php

namespace App\Console\Commands;

use App\Models\OgeVariant;
use App\Services\MiniAppTaskCanonicalizer;
use App\Services\VariantTaskNumberResolver;
use Illuminate\Console\Command;

class NormalizeMiniAppVariants extends Command
{
    protected $signature = 'variants:normalize-slots {--dry-run}';
    protected $description = 'Add canonical slot/exam_number to all variant config_json tasks';

    public function handle(MiniAppTaskCanonicalizer $canonicalizer): int
    {
        $dryRun = $this->option('dry-run');
        $variants = OgeVariant::whereNotNull('config_json')->get();

        $updated = 0;
        $skipped = 0;

        foreach ($variants as $variant) {
            $config = is_array($variant->config_json) ? $variant->config_json : [];
            $tasks = $config['tasks'] ?? null;

            if (!is_array($tasks) || empty($tasks)) {
                $skipped++;
                continue;
            }

            $changed = false;
            $normalizedTasks = [];

            foreach (array_values($tasks) as $index => $task) {
                if (!is_array($task)) {
                    $normalizedTasks[] = $task;
                    continue;
                }

                // Add slot/exam_number if missing
                if (!isset($task['slot']) || !isset($task['exam_number'])) {
                    $numbers = VariantTaskNumberResolver::resolve($task, $index, $variant);
                    $task['slot'] = $numbers['slot'];
                    $task['exam_number'] = $numbers['exam_number'];
                    $changed = true;
                }

                // Also run canonicalizer for consistency
                $result = $canonicalizer->normalizeForUi($task);
                if ($result !== $task) {
                    $changed = true;
                }

                $normalizedTasks[] = $result;
            }

            if ($changed) {
                if (!$dryRun) {
                    $config['tasks'] = $normalizedTasks;
                    $variant->forceFill(['config_json' => $config])->save();
                }
                $updated++;
                $this->line("  {$variant->id} ({$variant->mode ?? 'legacy'}) — " . ($dryRun ? 'would update' : 'updated'));
            } else {
                $skipped++;
            }
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Done. Updated: {$updated}, Unchanged: {$skipped}");

        return 0;
    }
}
```

### Step 2: Test locally with dry-run

Run: `php artisan variants:normalize-slots --dry-run`
Expected: Shows which variants would be updated

### Step 3: Run for real locally

Run: `php artisan variants:normalize-slots`
Expected: All variants updated

### Step 4: Verify via DB query

Run: `php artisan tinker --execute="echo OgeVariant::where('source','miniapp')->first()->config_json['tasks'][0]['slot'] ?? 'MISSING'"`
Expected: `1`

### Step 5: Commit

```bash
git add app/Console/Commands/NormalizeMiniAppVariants.php
git commit -m "feat: variants:normalize-slots command — backfill slot/exam_number for all variants"
```

### Step 6: Run on production after deploy

```bash
# Via MCP or deploy webhook:
php artisan variants:normalize-slots --dry-run
php artisan variants:normalize-slots
```

---

## Task 5: End-to-end test — full mini flow

**Files:**
- Modify: `tests/Feature/MiniAppAttemptFlowTest.php`

### Step 1: Add comprehensive flow test

```php
public function test_full_mini_mixed_flow_start_to_history(): void
{
    $student = User::factory()->create([
        'role' => 'student',
        'onboarding_completed_at' => now(),
    ]);

    // Create a mini_mixed variant with canonical fields
    $variant = OgeVariant::create([
        'hash' => 'e2eflow1',
        'title' => 'Мини-ОГЭ: Смешанное',
        'source' => OgeVariant::SOURCE_MINIAPP,
        'mode' => OgeVariant::MODE_MINI_MIXED,
        'config_json' => [
            'tasks' => [
                ['slot' => 1, 'exam_number' => 8, 'task_number' => 8, 'topic_id' => '08',
                 'instruction' => 'Найдите значение', 'expression' => '2*3', 'correct_answer' => '6'],
                ['slot' => 2, 'exam_number' => 10, 'task_number' => 10, 'topic_id' => '10',
                 'instruction' => 'Решите', 'expression' => 'x+1=3', 'correct_answer' => '2'],
                ['slot' => 3, 'exam_number' => 7, 'task_number' => 7, 'topic_id' => '07',
                 'instruction' => 'Укажите', 'expression' => '1<x<5', 'correct_answer' => 'b'],
                ['slot' => 4, 'exam_number' => 14, 'task_number' => 14, 'topic_id' => '14',
                 'instruction' => 'Вычислите', 'expression' => '3^2', 'correct_answer' => '9'],
                ['slot' => 5, 'exam_number' => 15, 'task_number' => 15, 'topic_id' => '15',
                 'instruction' => 'Найдите', 'expression' => 'S=?', 'correct_answer' => '12'],
                ['slot' => 6, 'exam_number' => 17, 'task_number' => 17, 'topic_id' => '17',
                 'instruction' => 'Определите', 'expression' => 'AB=?', 'correct_answer' => '5'],
                ['slot' => 7, 'exam_number' => 18, 'task_number' => 18, 'topic_id' => '18',
                 'instruction' => 'Найдите', 'expression' => 'r=?', 'correct_answer' => '3'],
            ],
        ],
    ]);

    // 1. Start attempt
    [, $attempt] = app(OgeAttemptService::class)->startAttempt($student, $variant->hash);

    // 2. Render test page — verify tasks use sequential slots but display exam numbers
    $testPage = $this->actingAs($student)->get("/tg/test/{$attempt->id}")->assertOk();
    $tasks = $testPage->viewData('tasks');
    $this->assertCount(7, $tasks);

    // 3. Submit answers (skip slot 5 intentionally)
    $this->actingAs($student)
        ->postJson("/api/oge/attempts/{$attempt->id}/submit", [
            'answers' => [1 => '6', 2 => '2', 3 => 'b', 4 => '9', 6 => '5', 7 => '99'],
        ])
        ->assertOk();

    // 4. History list — total should be 7, not 6
    $historyPage = $this->actingAs($student)->get('/tg/history')->assertOk();
    $list = $historyPage->viewData('list');
    $this->assertCount(1, $list);
    $this->assertSame(7, $list[0]['total']);

    // 5. History detail — should show conditions and exam numbers
    $detailPage = $this->actingAs($student)->get("/tg/history/{$attempt->id}")->assertOk();
    $this->assertSame(7, $detailPage->viewData('total'));

    $wrongTasks = $detailPage->viewData('wrongTasks');
    // Wrong: slot 5 (unanswered, exam 15), slot 6 (wrong answer '5' vs '5'...wait that's correct)
    // slot 7 (wrong: '99' vs '3')
    // slot 5 unanswered → wrong
    $wrongExamNumbers = array_column($wrongTasks, 'task_number');
    $this->assertContains(15, $wrongExamNumbers, 'Unanswered slot 5 (exam 15) should appear as error');
    $this->assertContains(18, $wrongExamNumbers, 'Wrong answer slot 7 (exam 18) should appear as error');
    $this->assertNotContains(8, $wrongExamNumbers, 'Correct slot 1 (exam 8) should not be in errors');
}
```

### Step 2: Run test

Run: `php artisan test --filter=test_full_mini_mixed_flow_start_to_history`
Expected: PASS

### Step 3: Commit

```bash
git add tests/Feature/MiniAppAttemptFlowTest.php
git commit -m "test: end-to-end mini mixed flow from start to history detail"
```

---

## Task 6 (optional, after migration is confirmed): Remove dead fallback code

**Files:**
- Modify: `app/Services/VariantTaskNumberResolver.php` — simplify legacy branch
- Modify: `app/Services/OgeAttemptService.php` — remove `normalizeConfiguredTasksForAttempt` re-canonicalization

Only do this after `variants:normalize-slots` has run on production and all variants have canonical fields. Verify with:

```sql
SELECT COUNT(*) FROM oge_variants
WHERE config_json IS NOT NULL
AND JSON_EXTRACT(config_json, '$.tasks[0].slot') IS NULL
AND JSON_LENGTH(JSON_EXTRACT(config_json, '$.tasks')) > 0;
```

If count = 0, legacy fallback is dead code and can be removed.

---

## Summary: execution order

| # | Task | Risk | Deploy independently? |
|---|------|------|-----------------------|
| 1 | VariantTaskNumberResolver | None (new code, no callers) | Yes |
| 2 | Write slot/exam_number at creation | None (additive, backwards compatible) | Yes |
| 3 | Replace 19 resolution sites | Medium (core change) | Yes, with test coverage |
| 4 | Migration command | Low (additive to existing data) | Yes, run on prod after deploy |
| 5 | End-to-end test | None (test only) | Yes |
| 6 | Remove dead code | Low (after migration confirmed) | Yes, after step 4 verified |
