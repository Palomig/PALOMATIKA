# Grade 7 Algebra Task Bank Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Move the grade 7 algebra prototype into the product task-bank structure and add a methodological layer for full-year tutoring, starting with "Раскрытие скобок".

**Architecture:** Keep JSON as the source of truth and preserve compatibility with the existing topic renderer. Add optional curriculum, micro-skill, task role, difficulty, answer, hint, and homework-set fields so existing tasks continue to render while richer tutor workflows become possible.

**Tech Stack:** Laravel/PHP, Blade, JSON task files, Node.js data transformation scripts, PHPUnit where service behavior changes.

---

### Task 1: Add A Fixture For The Extended Task Schema

**Files:**
- Create: `storage/app/tasks/alg/grade_7/topic_01.json`
- Reference: `storage/app/tasks/grade_7/topic_01.json`

**Step 1: Inspect the current topic file**

Run:

```bash
node -e "const t=require('./storage/app/tasks/grade_7/topic_01.json'); console.log(t.topic_id, t.meta.title, t.blocks.length)"
```

Expected: prints topic `01`, title, and block count.

**Step 2: Create the product directory**

Run:

```bash
mkdir -p storage/app/tasks/alg/grade_7
```

Expected: directory exists.

**Step 3: Copy the existing topic as a baseline**

Run:

```bash
cp storage/app/tasks/grade_7/topic_01.json storage/app/tasks/alg/grade_7/topic_01.json
```

Expected: file exists at the product path.

**Step 4: Add curriculum metadata manually**

Modify `storage/app/tasks/alg/grade_7/topic_01.json` and add this under `meta` or as a top-level `curriculum` key:

```json
{
  "curriculum": {
    "year_position": "weeks_3_5",
    "lesson_hours": 6,
    "homework_size": "15-20",
    "main_idea": "Скобки - это группа. Раскрыть скобки - значит выполнить действие с каждым элементом группы.",
    "prerequisites": [
      "Понимание переменной как числа",
      "Сложение и вычитание рациональных чисел",
      "Умножение числа на сумму",
      "Подобные слагаемые"
    ],
    "common_misconceptions": [
      "Меняет знак только первый член после минуса",
      "Не умножает коэффициент на каждый член скобки",
      "Смешивает раскрытие скобок и приведение подобных"
    ]
  }
}
```

**Step 5: Validate JSON**

Run:

```bash
node -e "JSON.parse(require('fs').readFileSync('storage/app/tasks/alg/grade_7/topic_01.json','utf8')); console.log('ok')"
```

Expected: `ok`.

**Step 6: Commit**

```bash
git add storage/app/tasks/alg/grade_7/topic_01.json
git commit -m "feat(algebra): seed grade 7 parentheses curriculum"
```

### Task 2: Add Micro-Skill And Homework Fields To Topic 01

**Files:**
- Modify: `storage/app/tasks/alg/grade_7/topic_01.json`

**Step 1: Add micro-skills**

Add a top-level `micro_skills` array:

```json
[
  {
    "id": "parentheses_as_group",
    "title": "Скобки как группа",
    "goal": "Ученик объясняет выражение со скобками через набор, чек, площадь или повторяющуюся ситуацию."
  },
  {
    "id": "plus_before_parentheses",
    "title": "Плюс перед скобкой",
    "goal": "Ученик раскрывает скобки после плюса без изменения знаков."
  },
  {
    "id": "minus_before_parentheses",
    "title": "Минус перед скобкой",
    "goal": "Ученик вычитает всю группу и меняет знак каждого члена."
  },
  {
    "id": "coefficient_before_parentheses",
    "title": "Коэффициент перед скобкой",
    "goal": "Ученик умножает каждый член группы на коэффициент."
  },
  {
    "id": "open_and_collect_like_terms",
    "title": "Раскрытие и подобные",
    "goal": "Ученик раскрывает скобки и приводит подобные слагаемые."
  },
  {
    "id": "error_analysis",
    "title": "Поиск ошибок",
    "goal": "Ученик находит типичные ошибки со знаками и объясняет исправление."
  }
]
```

**Step 2: Add a first homework set**

Add a top-level `homework_sets` array with one set of 20 task references or inline tasks. Prefer inline tasks for generated conceptual homework:

```json
{
  "id": "hw_01_parentheses_meaning",
  "title": "Раскрытие скобок: смысл и базовая техника",
  "target_minutes": 35,
  "tasks_count": 20,
  "tasks": []
}
```

Fill `tasks` with the 20 tasks from the design document.

**Step 3: Validate JSON**

Run:

```bash
node -e "const t=JSON.parse(require('fs').readFileSync('storage/app/tasks/alg/grade_7/topic_01.json','utf8')); console.log(t.micro_skills.length, t.homework_sets[0].tasks_count)"
```

Expected: `6 20`.

**Step 4: Commit**

```bash
git add storage/app/tasks/alg/grade_7/topic_01.json
git commit -m "feat(algebra): add parentheses micro skills and homework"
```

### Task 3: Add Service Tests For Extended Fields

**Files:**
- Create or modify: `tests/Feature/AlgTopicDataServiceTest.php`
- Modify: `app/Services/AlgTaskDataService.php` only if needed.

**Step 1: Write a test that extended fields survive loading**

Create a test:

```php
public function test_grade_7_algebra_topic_exposes_curriculum_fields(): void
{
    $service = new \App\Services\AlgTaskDataService(7);

    $data = $service->getTopicData('01');

    $this->assertSame('01', $data['topic_id']);
    $this->assertArrayHasKey('curriculum', $data);
    $this->assertArrayHasKey('micro_skills', $data);
    $this->assertArrayHasKey('homework_sets', $data);
    $this->assertNotEmpty($data['homework_sets'][0]['tasks']);
}
```

**Step 2: Run the test**

Run:

```bash
php artisan test --filter=AlgTopicDataServiceTest
```

Expected: pass if the service already returns raw data. If it fails because cache has stale data, clear cache and rerun.

**Step 3: Commit**

```bash
git add tests/Feature/AlgTopicDataServiceTest.php
git commit -m "test(algebra): cover extended grade 7 curriculum fields"
```

### Task 4: Show Curriculum Summary On Algebra Topic Page

**Files:**
- Modify: `app/Http/Controllers/AlgTopicController.php`
- Modify: `resources/views/alg-topics/show.blade.php`

**Step 1: Pass extended data to the view**

In `show`, after `$blocks`, add:

```php
$topicData    = $service->getTopicData($topicId);
$curriculum   = $topicData['curriculum'] ?? [];
$microSkills  = $topicData['micro_skills'] ?? [];
$homeworkSets = $topicData['homework_sets'] ?? [];
```

Update `compact`:

```php
return view('alg-topics.show', compact(
    'grade',
    'topicId',
    'topicMeta',
    'blocks',
    'stats',
    'allTopicIds',
    'curriculum',
    'microSkills',
    'homeworkSets'
));
```

**Step 2: Add a curriculum panel above content**

Render only when data exists:

```blade
@if(!empty($curriculum) || !empty($microSkills) || !empty($homeworkSets))
    <section class="bg-dark-light rounded-xl p-6 border border-gray-800 mb-8">
        @if(!empty($curriculum['main_idea']))
            <h2 class="text-white font-semibold mb-2">Главная идея</h2>
            <p class="text-gray-300">{{ $curriculum['main_idea'] }}</p>
        @endif

        @if(!empty($microSkills))
            <h3 class="text-white font-semibold mt-5 mb-3">Микронавыки</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($microSkills as $skill)
                    <div class="rounded-lg border border-gray-800 p-3">
                        <div class="text-emerald-300 font-medium">{{ $skill['title'] ?? $skill['id'] ?? 'Навык' }}</div>
                        @if(!empty($skill['goal']))
                            <div class="text-sm text-gray-400 mt-1">{{ $skill['goal'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if(!empty($homeworkSets))
            <h3 class="text-white font-semibold mt-5 mb-2">Домашние работы</h3>
            <div class="text-sm text-gray-400">
                {{ count($homeworkSets) }} набор(ов), по 15-20 заданий.
            </div>
        @endif
    </section>
@endif
```

**Step 3: Run the focused test**

Run:

```bash
php artisan test --filter=AlgTopicDataServiceTest
```

Expected: pass.

**Step 4: Smoke the route manually**

Run the app if needed and open:

```text
/alg-topics/7/1
```

Expected: topic page renders with the curriculum panel and existing task blocks.

**Step 5: Commit**

```bash
git add app/Http/Controllers/AlgTopicController.php resources/views/alg-topics/show.blade.php
git commit -m "feat(algebra): show curriculum metadata on topic pages"
```

### Task 5: Add A Script To Promote Prototype Grade 7 Topics

**Files:**
- Create: `scripts/promote-grade7-algebra.mjs`
- Source: `storage/app/tasks/grade_7/topic_*.json`
- Output: `storage/app/tasks/alg/grade_7/topic_*.json`

**Step 1: Write the script**

The script should:

- Create `storage/app/tasks/alg/grade_7`.
- Copy all `topic_*.json` from `storage/app/tasks/grade_7`.
- Preserve existing enriched files unless called with `--force`.
- Add a `curriculum_status: "needs_methodology"` field to files without curriculum data.

**Step 2: Run without force**

Run:

```bash
node scripts/promote-grade7-algebra.mjs
```

Expected: copies topics 02-11, skips enriched topic 01.

**Step 3: Validate all JSON files**

Run:

```bash
node - <<'NODE'
const fs=require('fs');
for (const f of fs.readdirSync('storage/app/tasks/alg/grade_7').filter(f=>f.endsWith('.json'))) {
  JSON.parse(fs.readFileSync('storage/app/tasks/alg/grade_7/'+f,'utf8'));
}
console.log('ok');
NODE
```

Expected: `ok`.

**Step 4: Commit**

```bash
git add scripts/promote-grade7-algebra.mjs storage/app/tasks/alg/grade_7
git commit -m "feat(algebra): promote grade 7 prototype topics"
```

### Task 6: Build The Next Topic Methodology Iteratively

**Files:**
- Modify: `storage/app/tasks/alg/grade_7/topic_02.json` through `topic_11.json`
- Optional scripts: generation/classification scripts in `scripts/`

**Step 1: Pick one topic at a time**

Start with:

```text
02 Линейные уравнения
```

Do not enrich all topics in one giant edit.

**Step 2: Add curriculum metadata**

For each topic, add:

- main idea
- prerequisites
- micro-skills
- common mistakes
- 4-8 homework sets
- 5-task mini-checks

**Step 3: Validate JSON and route**

Run:

```bash
node -e "JSON.parse(require('fs').readFileSync('storage/app/tasks/alg/grade_7/topic_02.json','utf8')); console.log('ok')"
php artisan test --filter=AlgTopicDataServiceTest
```

Expected: JSON parses and service tests pass.

**Step 4: Commit per topic**

```bash
git add storage/app/tasks/alg/grade_7/topic_02.json
git commit -m "feat(algebra): add linear equations methodology"
```

Repeat topic by topic.

