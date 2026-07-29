# Topic 16 Pedagogical Taxonomy Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the source-order grouping of all 322 FIPI topic 16 tasks with the approved four-section, 23-group pedagogical taxonomy without changing other topics or historical lesson snapshots.

**Architecture:** Store the curated topic 16 classification as a committed manifest keyed by stable FIPI GUIDs. A small taxonomy service validates complete one-to-one coverage and returns ordered group definitions; `tasks:import-fipi` uses it when available and keeps the existing `subtype_id` path for every other topic. The existing repository then exposes the four blocks to both the bank UI and lesson picker.

**Tech Stack:** Laravel 11, PHP 8.2, Eloquent/MySQL, PHPUnit, Blade.

---

### Task 1: Add a validated taxonomy service

**Files:**
- Create: `app/Services/FipiTaskTaxonomy.php`
- Create: `tests/Unit/FipiTaskTaxonomyTest.php`

**Step 1: Write the failing service tests**

Cover these cases with a small in-memory manifest:

```php
public function test_it_groups_tasks_in_manifest_order(): void
{
    $taxonomy = new FipiTaskTaxonomy([
        'topic' => '16',
        'expected_tasks' => 2,
        'blocks' => [[
            'number' => 1,
            'title' => 'Углы в окружности',
            'groups' => [[
                'key' => 'central-angle',
                'number' => 1,
                'title' => 'Центральный и вписанный углы',
                'expected_tasks' => 2,
                'guids' => ['AAA', 'BBB'],
            ]],
        ]],
    ]);

    $groups = $taxonomy->group([
        ['guid' => 'BBB', 'order' => [1]],
        ['guid' => 'AAA', 'order' => [0]],
    ]);

    $this->assertSame('Углы в окружности', $groups[0]['block_title']);
    $this->assertSame(['AAA', 'BBB'], array_column($groups[0]['items'], 'guid'));
}
```

Also assert that duplicate GUIDs, unknown source GUIDs, missing source GUIDs,
wrong per-group counts and a wrong total throw `InvalidArgumentException`
with a message containing the topic and offending GUID/group.

**Step 2: Run the test and verify it fails**

Run:

```bash
php artisan test tests/Unit/FipiTaskTaxonomyTest.php
```

Expected: FAIL because `App\Services\FipiTaskTaxonomy` does not exist.

**Step 3: Implement the minimal service**

Implement:

```php
final class FipiTaskTaxonomy
{
    public function __construct(private readonly array $manifest) {}

    public static function forTopic(string $topic): ?self
    {
        $path = resource_path("task-taxonomies/oge-topic-{$topic}.php");
        return is_file($path) ? new self(require $path) : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function group(array $tasks): array
    {
        // Validate manifest topic, unique GUIDs, source/manifest set equality,
        // expected totals and expected count of every group.
        // Return flat ordered group definitions containing:
        // block_number, block_title, number, key, title and items.
        // Items follow the original source order, not manifest list order.
    }
}
```

Build a source `guid => task` map and a source-order index. Flatten block/group
definitions in manifest order, validate them, then filter source tasks into
each group and sort by the source-order index.

**Step 4: Run the service tests**

Run:

```bash
php artisan test tests/Unit/FipiTaskTaxonomyTest.php
```

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/FipiTaskTaxonomy.php tests/Unit/FipiTaskTaxonomyTest.php
git commit -m "feat(tasks): добавить проверяемую таксономию ФИПИ"
```

### Task 2: Add the curated topic 16 manifest

**Files:**
- Create: `resources/task-taxonomies/oge-topic-16.php`
- Create: `tests/Unit/FipiTopic16TaxonomyTest.php`

**Step 1: Write the failing manifest test**

Load the manifest through `FipiTaskTaxonomy::forTopic('16')` and assert:

```php
$this->assertCount(4, $manifest['blocks']);
$this->assertSame(23, array_sum(array_map(
    fn (array $block) => count($block['groups']),
    $manifest['blocks'],
)));
$this->assertSame(322, count(array_unique($allGuids)));
$this->assertSame(
    ['Углы в окружности', 'Вписанные четырёхугольники',
     'Вписанная окружность', 'Описанная окружность'],
    array_column($manifest['blocks'], 'title'),
);
$this->assertSame(
    [10, 20, 10, 1, 10, 20, 10, 20, 10, 10, 30, 20,
     10, 10, 30, 10, 10, 20, 20, 10, 9, 10, 12],
    $groupCounts,
);
```

Add representative GUID assertions for every one of the 23 solution methods,
using GUIDs taken from the published `bank_katex.json`.

**Step 2: Run the test and verify it fails**

Run:

```bash
php artisan test tests/Unit/FipiTopic16TaxonomyTest.php
```

Expected: FAIL because the manifest does not exist.

**Step 3: Build the committed manifest**

Create a PHP manifest with this shape:

```php
return [
    'topic' => '16',
    'expected_tasks' => 322,
    'blocks' => [
        [
            'number' => 1,
            'title' => 'Углы в окружности',
            'groups' => [
                [
                    'key' => 'central-and-inscribed-angle',
                    'number' => 1,
                    'title' => 'Центральный и вписанный углы: вписанный угол вдвое меньше',
                    'expected_tasks' => 10,
                    'guids' => [
                        // Explicit stable FIPI GUIDs.
                    ],
                ],
            ],
        ],
        // Blocks 2–4 and groups 2–23 from the approved design.
    ],
];
```

Classify the published topic 16 export by normalized condition text. Review
all source templates and place every GUID explicitly in exactly one group.
Do not keep runtime regex classification: the committed GUID map is the
source of truth.

**Step 4: Run the manifest tests**

Run:

```bash
php artisan test tests/Unit/FipiTopic16TaxonomyTest.php
```

Expected: PASS with 322 unique GUIDs and all approved counts.

**Step 5: Commit**

```bash
git add resources/task-taxonomies/oge-topic-16.php tests/Unit/FipiTopic16TaxonomyTest.php
git commit -m "feat(tasks): классифицировать 322 задачи темы 16"
```

### Task 3: Integrate the taxonomy into the FIPI importer

**Files:**
- Modify: `app/Console/Commands/ImportFipiBank.php`
- Modify: `tests/Feature/FipiBankImportTest.php`

**Step 1: Write the failing import tests**

After `tasks:import-fipi`, assert:

```php
$blocks = (new TaskDataService())->getBlocks('16');

$this->assertSame(
    ['Углы в окружности', 'Вписанные четырёхугольники',
     'Вписанная окружность', 'Описанная окружность'],
    array_column($blocks, 'title'),
);
$this->assertSame(23, array_sum(array_map(
    fn (array $block) => count($block['zadaniya']),
    $blocks,
)));
$this->assertSame(322, /* sum all task counts */);
$this->assertSame(range(1, 23), /* ordered zadanie numbers */);
```

Assert exact group titles and counts. Import a second time and assert the same
structure and task count. Assert a non-curated topic still uses its original
FIPI subtype titles and one `ФИПИ` block.

**Step 2: Run the tests and verify the taxonomy assertions fail**

Run:

```bash
php artisan test tests/Feature/FipiBankImportTest.php
```

Expected: FAIL because topic 16 still has one `ФИПИ` block and 20 groups.

**Step 3: Refactor grouping before the transaction**

In `ImportFipiBank::handle()`:

1. Sort source tasks exactly as today.
2. Group them by topic only.
3. For each topic, call `FipiTaskTaxonomy::forTopic($topic)`.
4. If a taxonomy exists, call `group($items)`.
5. Otherwise build the current subtype groups unchanged.
6. Catch `InvalidArgumentException`, print the validation message and return
   `self::FAILURE` before opening the database transaction.

Change `createGroup()` to accept an ordered group definition:

```php
private function createGroup(string $topic, array $definition, int $position): void
{
    $items = $definition['items'];
    TaskGroup::create([
        'block_number' => $definition['block_number'],
        'block_title' => $definition['block_title'],
        'zadanie_number' => $definition['number'],
        'position' => $position,
        'instruction' => $definition['title'],
        // Existing payload/status/source fields remain unchanged.
    ]);
}
```

Preserve task payload, answers, SVG, `fipi_guid`, production status and
`--and-retire` behavior exactly.

**Step 4: Run importer and regression tests**

Run:

```bash
php artisan test tests/Feature/FipiBankImportTest.php
php artisan test tests/Feature/FipiLessonTaskResolveTest.php
php artisan test tests/Feature/LessonPickerShowsFipiTasksTest.php
```

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Console/Commands/ImportFipiBank.php tests/Feature/FipiBankImportTest.php
git commit -m "feat(tasks): применять учебную группировку темы 16"
```

### Task 4: Show the four sections in the task bank

**Files:**
- Modify: `app/Http/Controllers/Pwa/StudentController.php:653-752`
- Modify: `resources/views/pwa/student/tasks-part1.blade.php:174-183`
- Modify: `tests/Feature/Pwa/PwaFipiTaskBankTest.php`

**Step 1: Write the failing UI test**

For topic 16, assert the rendered HTML contains the four section headings in
the approved order and each heading appears once. For topic 15, assert the
technical block title `ФИПИ` is not rendered.

**Step 2: Run the test and verify it fails**

Run:

```bash
php artisan test tests/Feature/Pwa/PwaFipiTaskBankTest.php
```

Expected: FAIL because the controller currently flattens block titles.

**Step 3: Pass section metadata to the view**

When adding each group to `$zadaniya`, include:

```php
'section' => ($block['title'] ?? '') !== 'ФИПИ'
    ? trim((string) ($block['title'] ?? ''))
    : '',
```

Apply this to both controller branches that append a group.

In Blade, remember the preceding section and render a compact section heading
before the first group of each new non-empty section. Add only the minimal CSS
needed to distinguish the heading from a task accordion.

**Step 4: Run the UI tests**

Run:

```bash
php artisan test tests/Feature/Pwa/PwaFipiTaskBankTest.php
```

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Pwa/StudentController.php \
  resources/views/pwa/student/tasks-part1.blade.php \
  tests/Feature/Pwa/PwaFipiTaskBankTest.php
git commit -m "feat(pwa): показать учебные разделы темы 16"
```

### Task 5: Verify lesson picker labels and adding tasks

**Files:**
- Modify: `tests/Feature/LessonPickerShowsFipiTasksTest.php`
- Modify: `tests/Feature/FipiLessonTaskResolveTest.php`

**Step 1: Add picker order assertions**

Assert topic 16 still returns 322 tasks and its unique `group_label` values
match the 23 approved numbered titles in order.

**Step 2: Add representative lesson-add assertions**

Add one task from a source subtype that was split and one from two source
subtypes that were merged. Assert each POST returns 201 with non-empty
condition, correct answer and SVG.

**Step 3: Run the tests**

Run:

```bash
php artisan test tests/Feature/LessonPickerShowsFipiTasksTest.php
php artisan test tests/Feature/FipiLessonTaskResolveTest.php
```

Expected: PASS.

**Step 4: Commit**

```bash
git add tests/Feature/LessonPickerShowsFipiTasksTest.php \
  tests/Feature/FipiLessonTaskResolveTest.php
git commit -m "test(lesson): закрепить группы темы 16 в picker"
```

### Task 6: Full verification and production deployment

**Files:**
- No new source files.

**Step 1: Run focused tests**

```bash
php artisan test \
  tests/Unit/FipiTaskTaxonomyTest.php \
  tests/Unit/FipiTopic16TaxonomyTest.php \
  tests/Feature/FipiBankImportTest.php \
  tests/Feature/Pwa/PwaFipiTaskBankTest.php \
  tests/Feature/LessonPickerShowsFipiTasksTest.php \
  tests/Feature/FipiLessonTaskResolveTest.php
```

Expected: PASS.

**Step 2: Run the complete test suite**

```bash
php artisan test
```

Expected: PASS, apart from any documented pre-existing unrelated failures.

**Step 3: Review the final diff**

```bash
git status --short
git diff --check origin/main...HEAD
git diff --stat origin/main...HEAD
```

Expected: only the design, implementation plan, taxonomy, importer, UI and
related tests are changed.

**Step 4: Push and deploy**

Push `codex/topic16-taxonomy`, merge through the repository workflow, then
manually FTP-upload the exact changed runtime files from the merged
`origin/main` commit because GitHub Actions FTP is currently unreliable.
Verify SHA-256 after upload.

**Step 5: Refresh and reimport**

Call:

```json
POST /api/deploy/refresh
POST /api/deploy/artisan
{"command":"tasks:import-fipi","params":{"--url":"https://palomig.ru/fipi-bank-export/bank_katex.json","--and-retire":true}}
POST /api/deploy/artisan
{"command":"tasks:attach-legacy-solutions","params":{}}
```

Expected: successful refresh; 23 topic 16 groups, 322 tasks, four blocks.

**Step 6: Production smoke test**

As `qa-teacher@palomatika.ru`:

1. Open `/tasks-part1?topic=16`.
2. Verify the four section headings and 23 groups in order.
3. Open the lesson picker and verify the same group titles/order.
4. Add representative tasks from split and merged groups to QA lesson.
5. Verify condition, answer and SVG; remove the QA tasks afterward.

Record production SQL counts and remote hashes in the handoff.
