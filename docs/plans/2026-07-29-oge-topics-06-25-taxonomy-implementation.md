# OGE Topics 6–25 Pedagogical Taxonomy Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Give every OGE topic from 6 through 25 a complete, ordered pedagogical taxonomy and render all group headers with a clear number-only design.

**Architecture:** Keep exact task membership in per-topic PHP manifests consumed by the existing `FipiTaskTaxonomy`. Generate those manifests from the current FIPI export plus a human-curated curriculum catalog that maps source subtype IDs to learning sections, solution-method names, and order. Keep import atomic and strict: a source/manifest mismatch aborts before database writes.

**Tech Stack:** Laravel 10, PHP 8.2, Blade/CSS, PHPUnit, MySQL task bank, FIPI JSON export, manual FTP production deployment.

---

### Task 1: Add strict cross-topic manifest validation

**Files:**
- Modify: `app/Services/FipiTaskTaxonomy.php`
- Create: `tests/Unit/FipiTopicsTaxonomyTest.php`
- Modify: `tests/Unit/FipiTaskTaxonomyTest.php`

**Step 1: Write the failing structural tests**

Add a data-driven test for topic IDs `06` through `25`:

```php
public function test_every_topic_from_06_through_25_has_a_valid_manifest(): void
{
    foreach (range(6, 25) as $number) {
        $topic = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
        $taxonomy = FipiTaskTaxonomy::forTopic($topic);

        $this->assertNotNull($taxonomy, "missing topic {$topic}");
        $taxonomy->validateManifest();

        $manifest = $taxonomy->manifest();
        $groups = array_merge(...array_column($manifest['blocks'], 'groups'));
        $guids = array_merge(...array_column($groups, 'guids'));

        $this->assertSame($manifest['expected_tasks'], count($guids), $topic);
        $this->assertCount(count($guids), array_unique($guids), $topic);
        $this->assertSame(range(1, count($groups)), array_column($groups, 'number'), $topic);
    }
}
```

Add focused unit tests that reject:

- duplicate block numbers;
- duplicate group numbers;
- empty section/group titles;
- empty taxonomy keys;
- a group number that breaks the global sequence.

**Step 2: Run tests and verify failure**

Run:

```bash
php artisan test tests/Unit/FipiTaskTaxonomyTest.php tests/Unit/FipiTopicsTaxonomyTest.php
```

Expected: FAIL because `validateManifest()` and topic manifests other than 16 do not exist.

**Step 3: Implement manifest-only validation**

Add:

```php
public function validateManifest(): void
{
    $topic = (string) ($this->manifest['topic'] ?? '');
    $expected = (int) ($this->manifest['expected_tasks'] ?? 0);
    $seenBlocks = [];
    $seenGroups = [];
    $seenGuids = [];
    $nextGroup = 1;

    foreach ($this->manifest['blocks'] ?? [] as $block) {
        $blockNumber = (int) ($block['number'] ?? 0);
        $blockTitle = trim((string) ($block['title'] ?? ''));
        if ($blockNumber < 1 || isset($seenBlocks[$blockNumber]) || $blockTitle === '') {
            throw new InvalidArgumentException("Тема {$topic}: некорректный раздел {$blockNumber}");
        }
        $seenBlocks[$blockNumber] = true;

        foreach ($block['groups'] ?? [] as $group) {
            $number = (int) ($group['number'] ?? 0);
            $key = trim((string) ($group['key'] ?? ''));
            $title = trim((string) ($group['title'] ?? ''));
            if ($number !== $nextGroup || isset($seenGroups[$number]) || $key === '' || $title === '') {
                throw new InvalidArgumentException("Тема {$topic}: некорректная группа {$number}");
            }
            $seenGroups[$number] = true;
            $nextGroup++;

            $guids = array_values($group['guids'] ?? []);
            if (count($guids) !== (int) ($group['expected_tasks'] ?? 0)) {
                throw new InvalidArgumentException("Тема {$topic}: неверный размер группы {$key}");
            }
            foreach ($guids as $guid) {
                if ($guid === '' || isset($seenGuids[$guid])) {
                    throw new InvalidArgumentException("Тема {$topic}: GUID {$guid} повторяется");
                }
                $seenGuids[$guid] = true;
            }
        }
    }

    if (count($seenGuids) !== $expected) {
        throw new InvalidArgumentException("Тема {$topic}: карта содержит " . count($seenGuids) . " GUID вместо {$expected}");
    }
}
```

Call it at the start of `group()` so both tests and production import use one validation path.

**Step 4: Run focused tests**

Expected: existing generic tests PASS; cross-topic test still FAILS only for missing manifests.

**Step 5: Commit**

```bash
git add app/Services/FipiTaskTaxonomy.php tests/Unit/FipiTaskTaxonomyTest.php tests/Unit/FipiTopicsTaxonomyTest.php
git commit -m "test: validate OGE taxonomy manifests"
```

### Task 2: Build a reproducible taxonomy inventory and generator

**Files:**
- Create: `scripts/oge-taxonomy-inventory.php`
- Create: `scripts/build-oge-taxonomies.php`
- Create: `tests/Unit/OgeTaxonomyGeneratorTest.php`
- Create: `tests/fixtures/fipi-taxonomy-small.json`

**Step 1: Write a failing generator test**

The fixture contains two topics, stable GUIDs, subtype IDs/titles and source
orders. Test that:

- curriculum order overrides source subtype order;
- GUIDs are sorted by source `order` inside a group;
- block/group counts are emitted;
- a source subtype missing from the curriculum causes a non-zero exit;
- an unknown curriculum subtype causes a non-zero exit.

Run:

```bash
php artisan test tests/Unit/OgeTaxonomyGeneratorTest.php
```

Expected: FAIL because the generator does not exist.

**Step 2: Implement the inventory script**

CLI:

```bash
php scripts/oge-taxonomy-inventory.php \
  /home/dev/fipi-sync/out/export/bank_katex.json \
  --topics=06-25
```

For each topic/subtype print:

```text
topic | subtype | count | source title | first GUID | representative condition
```

Normalize HTML only for reporting; never use normalized text as task identity.

**Step 3: Implement the generator**

CLI:

```bash
php scripts/build-oge-taxonomies.php \
  /home/dev/fipi-sync/out/export/bank_katex.json \
  resources/task-taxonomies/oge-topics-06-25-curriculum.php
```

The generator must:

1. read tasks for topics 6–25;
2. group by `(topic, subtype_id)`;
3. require every source subtype exactly once in the curriculum;
4. preserve the current topic-16 manifest unchanged;
5. write deterministic `oge-topic-NN.php` manifests;
6. validate generated manifests through `FipiTaskTaxonomy`;
7. print per-topic sections/groups/tasks and a final total of 3525.

Generated PHP uses `var_export()` with a fixed header and trailing newline.

**Step 4: Run generator tests**

Expected: PASS.

**Step 5: Commit**

```bash
git add scripts/oge-taxonomy-inventory.php scripts/build-oge-taxonomies.php \
  tests/Unit/OgeTaxonomyGeneratorTest.php tests/fixtures/fipi-taxonomy-small.json
git commit -m "feat: add OGE taxonomy generator"
```

### Task 3: Curate algebra and arithmetic topics 6–14

**Files:**
- Create: `resources/task-taxonomies/oge-topics-06-25-curriculum.php`
- Create: `resources/task-taxonomies/oge-topic-06.php`
- Create: `resources/task-taxonomies/oge-topic-07.php`
- Create: `resources/task-taxonomies/oge-topic-08.php`
- Create: `resources/task-taxonomies/oge-topic-09.php`
- Create: `resources/task-taxonomies/oge-topic-10.php`
- Create: `resources/task-taxonomies/oge-topic-11.php`
- Create: `resources/task-taxonomies/oge-topic-12.php`
- Create: `resources/task-taxonomies/oge-topic-13.php`
- Create: `resources/task-taxonomies/oge-topic-14.php`
- Test: `tests/Unit/FipiTopicsTaxonomyTest.php`

**Step 1: Add expected task counts to the failing test**

```php
private const EXPECTED_TASKS = [
    '06' => 81, '07' => 171, '08' => 321, '09' => 111, '10' => 211,
    '11' => 101, '12' => 175, '13' => 141, '14' => 110,
    '15' => 252, '16' => 322, '17' => 316, '18' => 154, '19' => 150,
    '20' => 200, '21' => 190, '22' => 157, '23' => 172, '24' => 60, '25' => 130,
];
```

Expected before generation: FAIL for topics 6–14.

**Step 2: Produce the topic inventory**

Run the inventory script and review representative conditions for every
subtype. Put each subtype into a section and give it a concise solution-method
title. Required progression:

- 6: numeric forms → fractions → powers → combined calculations;
- 7: number line → comparison/estimation → intervals and distances;
- 8: roots/powers basics → transformations → combined expressions;
- 9: linear → quadratic → rational and reducible equations;
- 10: equally likely outcomes → complements → combinatorics → trees/Euler;
- 11: reading graphs → coefficients → formula/graph matching → piecewise;
- 12: substitute values → rearrange a formula → practical formulas;
- 13: linear inequalities → systems → quadratic/rational sign analysis;
- 14: sequence recognition → arithmetic progression → geometric progression.

No curriculum title may start with `Задание` or copy a full task condition.

**Step 3: Generate manifests 6–14**

Run the generator and verify counts:

```text
06 81
07 171
08 321
09 111
10 211
11 101
12 175
13 141
14 110
```

**Step 4: Run structural tests**

Expected: topics 6–14 PASS; later missing topics remain the only failures.

**Step 5: Commit**

```bash
git add resources/task-taxonomies/oge-topics-06-25-curriculum.php \
  resources/task-taxonomies/oge-topic-{06,07,08,09,10,11,12,13,14}.php \
  tests/Unit/FipiTopicsTaxonomyTest.php
git commit -m "feat: classify OGE topics 6-14 by solution method"
```

### Task 4: Curate first-part geometry topics 15–19

**Files:**
- Modify: `resources/task-taxonomies/oge-topics-06-25-curriculum.php`
- Create: `resources/task-taxonomies/oge-topic-15.php`
- Preserve: `resources/task-taxonomies/oge-topic-16.php`
- Create: `resources/task-taxonomies/oge-topic-17.php`
- Create: `resources/task-taxonomies/oge-topic-18.php`
- Create: `resources/task-taxonomies/oge-topic-19.php`
- Test: `tests/Unit/FipiTopicsTaxonomyTest.php`

**Step 1: Add a regression assertion for topic 16**

Keep the approved values:

```php
$this->assertSame(4, count($manifest16['blocks']));
$this->assertSame(23, count($groups16));
$this->assertSame(322, $manifest16['expected_tasks']);
```

Record the file SHA-256 before generation and assert it is unchanged afterward.

**Step 2: Review representative conditions**

Curate by knowledge rather than figure name:

- 15: angles and sides → congruence/similarity → heights/medians/bisectors → area;
- 17: parallelogram/rectangle/rhombus/square → trapezoid → combined properties;
- 18: lengths → areas → coordinate/vector methods → composite grid figures;
- 19: triangle statements → quadrilateral statements → circle statements → mixed logic.

**Step 3: Generate manifests and verify totals**

Expected:

```text
15 252
16 322 (unchanged)
17 316
18 154
19 150
```

**Step 4: Run structural and topic-16 regression tests**

```bash
php artisan test tests/Unit/FipiTaskTaxonomyTest.php \
  tests/Unit/FipiTopic16TaxonomyTest.php tests/Unit/FipiTopicsTaxonomyTest.php
```

Expected: topics 6–19 PASS; 20–25 missing.

**Step 5: Commit**

```bash
git add resources/task-taxonomies/oge-topics-06-25-curriculum.php \
  resources/task-taxonomies/oge-topic-{15,17,18,19}.php \
  tests/Unit/FipiTopicsTaxonomyTest.php
git commit -m "feat: classify OGE geometry topics 15-19"
```

### Task 5: Curate second-part topics 20–25, including topic 22

**Files:**
- Modify: `resources/task-taxonomies/oge-topics-06-25-curriculum.php`
- Create: `resources/task-taxonomies/oge-topic-20.php`
- Create: `resources/task-taxonomies/oge-topic-21.php`
- Create: `resources/task-taxonomies/oge-topic-22.php`
- Create: `resources/task-taxonomies/oge-topic-23.php`
- Create: `resources/task-taxonomies/oge-topic-24.php`
- Create: `resources/task-taxonomies/oge-topic-25.php`
- Test: `tests/Unit/FipiTopicsTaxonomyTest.php`

**Step 1: Verify the topic-22 source discrepancy**

Run inventory against the FIPI export, not legacy JSON. Expected:

```text
topic 22: 157 tasks, 18 source subtypes
```

This is the regression guard for the production/local discrepancy found during
design.

**Step 2: Curate by solution plan**

- 20: transformations → equations → systems → inequalities;
- 21: motion → work → mixtures → percentages → other algebraic models;
- 22: removable discontinuity → modulus → piecewise functions → intersections with `y=m`;
- 23: triangles → quadrilaterals → circles → combined computations;
- 24: congruence/similarity → quadrilateral properties → circles → area arguments;
- 25: one central theorem → two-theorem combinations → auxiliary constructions.

For topic 24, missing numerical answers are expected because these are proofs;
they remain draft for student assessment but must remain covered by taxonomy.

**Step 3: Generate manifests and verify totals**

Expected:

```text
20 200
21 190
22 157
23 172
24 60
25 130
all 06-25 3525
```

**Step 4: Run all taxonomy tests**

Expected: PASS, including all 20 topic manifests.

**Step 5: Commit**

```bash
git add resources/task-taxonomies/oge-topics-06-25-curriculum.php \
  resources/task-taxonomies/oge-topic-{20,21,22,23,24,25}.php \
  tests/Unit/FipiTopicsTaxonomyTest.php
git commit -m "feat: classify OGE second-part topics 20-25"
```

### Task 6: Prove exact source coverage in the importer

**Files:**
- Modify: `tests/Feature/ImportFipiBankTest.php`
- Modify: `app/Console/Commands/ImportFipiBank.php`

**Step 1: Write failing import tests**

Add tests that import the current bank fixture/path and assert:

```php
$this->artisan('tasks:import-fipi', ['--file' => $path, '--dry-run' => true])
    ->expectsOutputToContain('тем 25')
    ->expectsOutputToContain('задач 3884')
    ->assertSuccessful();
```

Add a second assertion that topics 6–25 contribute exactly 3525 tasks and each
created `TaskGroup` carries:

- curated `block_title`;
- sequential `zadanie_number`;
- non-empty `taxonomy_key`;
- instruction without `Задание N`.

Add a corrupted-bank test with one changed GUID. Expected: command fails and
the pre-existing database rows are untouched.

**Step 2: Run tests and verify failure**

Expected: missing range summary and/or incomplete assertions fail.

**Step 3: Add a dry-run range summary**

Keep import logic unchanged except for an explicit checked summary:

```text
КУРАТОРСКИЕ ТЕМЫ 06–25: тем 20, задач 3525
```

Do not weaken `FipiTaskTaxonomy::group()` mismatch failures.

**Step 4: Run import tests**

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Console/Commands/ImportFipiBank.php tests/Feature/ImportFipiBankTest.php
git commit -m "test: enforce FIPI taxonomy source coverage"
```

### Task 7: Redesign first-part group headers

**Files:**
- Modify: `app/Http/Controllers/Pwa/StudentController.php`
- Modify: `resources/views/pwa/student/tasks-part1.blade.php`
- Modify: `tests/Feature/Pwa/PwaFipiTaskBankTest.php`

**Step 1: Write failing view assertions**

For topics 6 and 16 assert:

```php
$response->assertViewHas('zadaniya', fn ($groups) =>
    isset($groups[0]['number'], $groups[0]['title'], $groups[0]['section'])
);

$html = $response->getContent();
$this->assertStringContainsString('class="spoiler-num">1</span>', $html);
$this->assertStringNotContainsString('class="spoiler-num">01</span>', $html);
$this->assertDoesNotMatchRegularExpression('/spoiler-title[^>]*>\\s*Задание\\s+\\d+/u', $html);
```

Also assert the count and explicit chevron exist.

**Step 2: Run the PWA test and verify failure**

Expected: controller does not expose number and old summary markup remains.

**Step 3: Expose group number separately**

In both statement/task branches of `tasksPart1()` append:

```php
$zadaniya[] = [
    'number' => (int) ($zadanie['number'] ?? count($zadaniya) + 1),
    'section' => $section,
    'title' => $label !== '' ? $label : ($section !== '' ? $section : $instruction),
    'tasks' => $tasks,
];
```

For taxonomy groups, `instruction` is already the concise group title and
`section` is the curated block title. Do not synthesize `Задание N`.

**Step 4: Port the second-part header pattern**

Use:

```blade
<summary>
  <span class="spoiler-num">{{ (int)($group['number'] ?? $loop->iteration) }}</span>
  <span class="spoiler-body-text">
    <span class="spoiler-title">{{ $group['title'] }}</span>
    <span class="spoiler-subtitle">{{ count($group['tasks']) }} заданий</span>
  </span>
  <span class="spoiler-chevron">›</span>
</summary>
```

Set `.spoiler-num` to `font-size: 16px`, `min-width: 28px`, strong weight and
theme-muted color. Keep the title at 14px and rotate the chevron when open.

**Step 5: Run PWA tests**

Expected: PASS.

**Step 6: Commit**

```bash
git add app/Http/Controllers/Pwa/StudentController.php \
  resources/views/pwa/student/tasks-part1.blade.php \
  tests/Feature/Pwa/PwaFipiTaskBankTest.php
git commit -m "feat: simplify first-part task group headers"
```

### Task 8: Render pedagogical sections consistently in part two

**Files:**
- Modify: `app/Http/Controllers/Pwa/StudentController.php`
- Modify: `resources/views/pwa/student/part2.blade.php`
- Modify: `tests/Feature/Pwa/PwaFipiTaskBankTest.php`

**Step 1: Write failing tests for topics 20–25**

Assert every group has `section`, `number`, a concise title and task list.
For topic 22 assert section headings occur once and in order. Assert no
`.spoiler-title` begins with `Задание`.

Keep existing teacher solution/drawing tests green.

**Step 2: Run tests and verify failure**

Expected: part-two groups do not expose block section and fallback titles can
contain full `Задание N` text.

**Step 3: Pass section and use curated instruction**

Add:

```php
'section' => trim((string) ($block['title'] ?? '')),
```

Use taxonomy instruction as title. Keep the existing short-label fallback only
for non-taxonomy/legacy data.

**Step 4: Add section heading markup**

Use the same `bank-section-title` transition logic as first part. Keep the
current teacher solution button, task drawings and answer rendering untouched.

**Step 5: Run PWA and teacher tests**

```bash
php artisan test tests/Feature/Pwa/PwaFipiTaskBankTest.php \
  tests/Feature/LessonPickerShowsFipiTasksTest.php \
  tests/Feature/TeacherLessonControllerTest.php
```

Expected: PASS.

**Step 6: Commit**

```bash
git add app/Http/Controllers/Pwa/StudentController.php \
  resources/views/pwa/student/part2.blade.php \
  tests/Feature/Pwa/PwaFipiTaskBankTest.php
git commit -m "feat: show pedagogical sections in OGE part two"
```

### Task 9: Full regression and visual QA

**Files:**
- Modify if needed: `.claude/product/task-banks/_overview.md`
- Modify if needed: `.claude/product/modules/lessons.md`

**Step 1: Run generator determinism check**

Run the generator twice and verify:

```bash
git diff --exit-code resources/task-taxonomies
```

Expected: no diff.

**Step 2: Run focused suite in isolated groups**

```bash
php artisan test tests/Unit/FipiTaskTaxonomyTest.php \
  tests/Unit/FipiTopic16TaxonomyTest.php \
  tests/Unit/FipiTopicsTaxonomyTest.php \
  tests/Unit/OgeTaxonomyGeneratorTest.php

php artisan test tests/Feature/ImportFipiBankTest.php

php artisan test tests/Feature/Pwa/PwaFipiTaskBankTest.php \
  tests/Feature/LessonPickerShowsFipiTasksTest.php \
  tests/Feature/TeacherLessonControllerTest.php
```

Expected: all PASS.

**Step 3: Run local smoke**

Import the current FIPI bank into the test/local database and inspect:

- mobile first part topics 6, 10, 16, 19;
- mobile second part topics 20, 22, 24, 25;
- teacher solution button and drawings;
- lesson picker adds/removes one task from both parts.

**Step 4: Update product documentation**

Document:

- topics 6–25 are curated by GUID;
- source mismatch makes import fail;
- taxonomy manifests are generated from curriculum plus the canonical export;
- the regeneration command.

**Step 5: Commit**

```bash
git add .claude/product/task-banks/_overview.md .claude/product/modules/lessons.md
git commit -m "docs: describe curated OGE task taxonomy"
```

### Task 10: Publish and verify production

**Files:**
- Runtime files from `git diff --name-only origin/main...HEAD`

**Step 1: Push and open a pull request**

```bash
git push -u origin codex/oge-topics-06-25-taxonomy
```

Open a PR, confirm checks, and merge to `main`.

**Step 2: Perform mandatory manual FTP deployment**

Upload every changed runtime file under `/OGE/<repo-path>` using credentials
from `/home/dev/.agent-secrets/timeweb.env`. Do not upload tests or plans.

Download/inspect remote files and verify local/remote SHA-256 for every runtime
file.

**Step 3: Refresh the application**

POST `/api/deploy/refresh` with the deploy secret. Expected:

```json
{"success":true}
```

**Step 4: Dry-run the canonical production import**

Run:

```text
tasks:import-fipi
--url=https://palomig.ru/fipi-bank-export/bank_katex.json
--dry-run
```

Expected:

- 25 topics;
- 3884 total tasks;
- topics 6–25: 20 topics and 3525 tasks;
- no taxonomy mismatch.

**Step 5: Atomically reimport**

Run the same command with `--and-retire` and without `--dry-run`.
Expected: success; no interval where legacy and FIPI banks are both active.

**Step 6: Verify production data read-only**

Use `/api/deploy/query` to assert:

- topic task counts match the approved count table;
- every topic 6–25 has non-empty curated `block_title`;
- group numbers are unique/sequential per topic;
- all groups have `taxonomy_key`;
- no topic has duplicate `fipi_guid`.

**Step 7: Production browser smoke**

As the QA teacher:

- open all topics 6–25;
- verify section headings and number-only group headers;
- verify topic 22 has 157 tasks;
- verify topic 16 remains 322 tasks, 4 sections, 23 groups;
- add one task from topics 6, 16, 22 and 25 to a lesson;
- verify second-part teacher solutions and drawings;
- remove QA tasks so the lesson returns to its initial state.

**Step 8: Record deployment status**

Update the shared Agent Brain project note only with durable rollout status and
production verification results. Pull/rebase the memory vault first, then
commit and push immediately.
