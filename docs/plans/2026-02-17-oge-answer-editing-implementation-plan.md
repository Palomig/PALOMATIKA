# OGE Answer Editing And Provenance Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Restrict OGE task-base access to teachers/admins, allow only admins to edit task answers instantly, and show answer provenance (`AI` or `Ручной by {name}`) for tasks with initial rollout on topics 06 and 07.

**Architecture:** Keep JSON files as canonical parsed content and add a DB override layer keyed by existing `task_key` (`topic_*_block_*_zadanie_*_task_*`). Read path merges override metadata onto resolved answers; write path is a protected admin-only API that upserts overrides and appends audit logs. UI keeps current layout and extends the existing answer block plus review tool inline controls.

**Tech Stack:** Laravel (routes/controllers/middleware/validation), Blade + vanilla JS, Eloquent + migrations, PHPUnit feature/unit tests.

---

### Task 1: Lock down topic base access (teacher/admin only)

**Files:**
- Modify: `routes/web.php`
- Test: `tests/Feature/OgeTopicAccessControlTest.php`

**Step 1: Write the failing test**

```php
public function test_student_cannot_open_topics_base(): void
{
    $student = User::factory()->make(['role' => 'student']);

    $this->actingAs($student)->get('/topics')->assertStatus(403);
    $this->actingAs($student)->get('/topics/6')->assertStatus(403);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/OgeTopicAccessControlTest.php --filter=student_cannot_open_topics_base`
Expected: FAIL (`200` now, should be `403`).

**Step 3: Write minimal implementation**

- Wrap `/topics` routes with `Route::middleware(['auth', 'role:teacher,admin'])`.
- Keep `/oge/{hash}` access unchanged.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/OgeTopicAccessControlTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add routes/web.php tests/Feature/OgeTopicAccessControlTest.php
git commit -m "feat(auth): restrict OGE topics base to teacher/admin"
```

### Task 2: Add answer override and audit schema

**Files:**
- Create: `database/migrations/2026_02_17_200000_create_task_answer_override_tables.php`
- Create: `app/Models/TaskAnswerOverride.php`
- Create: `app/Models/TaskAnswerOverrideLog.php`
- Test: `tests/Feature/AdminTaskAnswerUpdateApiTest.php`

**Step 1: Write the failing test**

```php
public function test_admin_can_patch_task_answer_and_persist_override(): void
{
    $admin = User::factory()->create(['role' => 'admin']);

    $payload = [
        'task_key' => 'topic_06_block_1_zadanie_1_task_1',
        'answer' => '42',
    ];

    $this->actingAs($admin)
        ->patchJson('/api/topics/06/answers', $payload)
        ->assertOk();

    $this->assertDatabaseHas('task_answer_overrides', [
        'task_key' => $payload['task_key'],
        'answer' => '42',
        'source' => 'manual',
    ]);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/AdminTaskAnswerUpdateApiTest.php --filter=admin_can_patch_task_answer_and_persist_override`
Expected: FAIL (route/table missing).

**Step 3: Write minimal implementation**

- Create migration with two tables:
  - `task_answer_overrides`: unique `task_key`, `answer`, `source`, `updated_by_user_id`.
  - `task_answer_override_logs`: `override_id`, `old_answer`, `new_answer`, `changed_by_user_id`.
- Create minimal Eloquent models with `$fillable` and relationships.

**Step 4: Run test to verify schema loads**

Run: `php artisan test tests/Feature/AdminTaskAnswerUpdateApiTest.php --filter=admin_can_patch_task_answer_and_persist_override`
Expected: still FAIL on route/controller, but no SQL table-not-found from migrations.

**Step 5: Commit**

```bash
git add database/migrations/2026_02_17_200000_create_task_answer_override_tables.php app/Models/TaskAnswerOverride.php app/Models/TaskAnswerOverrideLog.php
git commit -m "feat(db): add task answer override and audit tables"
```

### Task 3: Implement admin-only answer update API

**Files:**
- Create: `app/Http/Controllers/AdminTaskAnswerController.php`
- Modify: `routes/web.php`
- Modify: `app/Services/TaskDataService.php`
- Test: `tests/Feature/AdminTaskAnswerUpdateApiTest.php`

**Step 1: Write failing tests for auth + validation**

```php
public function test_teacher_cannot_patch_task_answer(): void
{
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->patchJson('/api/topics/06/answers', [
            'task_key' => 'topic_06_block_1_zadanie_1_task_1',
            'answer' => '10',
        ])
        ->assertStatus(403);
}

public function test_patch_requires_non_empty_answer(): void
{
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->patchJson('/api/topics/06/answers', [
            'task_key' => 'topic_06_block_1_zadanie_1_task_1',
            'answer' => '',
        ])
        ->assertStatus(422);
}
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/AdminTaskAnswerUpdateApiTest.php`
Expected: FAIL.

**Step 3: Write minimal implementation**

- Add route in `routes/web.php`:
  - `PATCH /api/topics/{topicId}/answers` under `auth + role:admin`.
- Controller method:
  - validate `task_key` and `answer`;
  - verify `task_key` belongs to given topic and exists in JSON via `TaskDataService`;
  - upsert override (`source=manual`, `updated_by_user_id=auth()->id()`);
  - append log row;
  - return payload: `task_key`, `answer`, `source`, `source_label`, `updated_by_name`.

**Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/AdminTaskAnswerUpdateApiTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/AdminTaskAnswerController.php app/Services/TaskDataService.php routes/web.php tests/Feature/AdminTaskAnswerUpdateApiTest.php
git commit -m "feat(api): add admin-only endpoint for task answer updates"
```

### Task 4: Merge overrides into answer read path

**Files:**
- Create: `app/Services/TaskAnswerProvenanceService.php`
- Modify: `resources/views/tasks/partials/task-answer.blade.php`
- Modify: `resources/views/tasks/types/expression.blade.php`
- Modify: `resources/views/tasks/types/choice.blade.php`
- Modify: other type templates that include `task-answer` (matching/geometry/grid/word-problem/graphic/graph-statements)
- Test: `tests/Feature/TopicAnswerProvenanceViewTest.php`

**Step 1: Write failing view test**

```php
public function test_topic_page_shows_manual_provenance_badge_for_overridden_answer(): void
{
    $teacher = User::factory()->create(['role' => 'teacher']);
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Ivan']);

    TaskAnswerOverride::create([
        'task_key' => 'topic_06_block_1_zadanie_1_task_1',
        'answer' => '77',
        'source' => 'manual',
        'updated_by_user_id' => $admin->id,
    ]);

    $this->actingAs($teacher)
        ->get('/topics/6')
        ->assertOk()
        ->assertSee('77')
        ->assertSee('Ручной by Ivan');
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/TopicAnswerProvenanceViewTest.php`
Expected: FAIL.

**Step 3: Write minimal implementation**

- New service resolves answer presentation model:
  - input: `topicId`, `blockNumber`, `zadanieNumber`, `taskId`, fallback answer.
  - output: final answer + badge label + editor permissions.
- Update task templates to pass `task_key` parts into `task-answer` partial.
- Update `task-answer` partial to render:
  - `Ответ: ...`
  - provenance badge (`AI` by default; `Ручной by {name}` when override exists).

**Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/TopicAnswerProvenanceViewTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/TaskAnswerProvenanceService.php resources/views/tasks/partials/task-answer.blade.php resources/views/tasks/types/*.blade.php tests/Feature/TopicAnswerProvenanceViewTest.php
git commit -m "feat(answers): show provenance badge and apply answer overrides"
```

### Task 5: Add inline admin edit controls in answer block and review flow

**Files:**
- Modify: `resources/views/tasks/partials/task-answer.blade.php`
- Modify: `resources/views/components/task-review-tool.blade.php`
- Modify: `resources/views/layouts/topic.blade.php`
- Test: `tests/Feature/AdminTaskAnswerUpdateApiTest.php`

**Step 1: Write failing UX tests (API-level contract used by JS)**

```php
public function test_api_response_contains_source_label_and_updated_by_name(): void
{
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Maria']);

    $this->actingAs($admin)
        ->patchJson('/api/topics/06/answers', [
            'task_key' => 'topic_06_block_1_zadanie_1_task_1',
            'answer' => '91',
        ])
        ->assertOk()
        ->assertJsonPath('source_label', 'Ручной by Maria')
        ->assertJsonPath('updated_by_name', 'Maria');
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/AdminTaskAnswerUpdateApiTest.php --filter=source_label`
Expected: FAIL.

**Step 3: Write minimal implementation**

- In `task-answer` partial:
  - render `✏️` button for admins only;
  - inline editor form;
  - JS handler with CSRF for `PATCH /api/topics/{topicId}/answers`.
- In `task-review-tool`:
  - detect answer editor in flagged task context;
  - keep existing comment mechanism, add quick jump/open edit for answer fixes.

**Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/AdminTaskAnswerUpdateApiTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/tasks/partials/task-answer.blade.php resources/views/components/task-review-tool.blade.php resources/views/layouts/topic.blade.php tests/Feature/AdminTaskAnswerUpdateApiTest.php
git commit -m "feat(ui): add inline admin answer editing and review integration"
```

### Task 6: Fill and verify answers for topics 06 and 07

**Files:**
- Modify: `storage/app/tasks/topic_06.json` (only if missing/incorrect answers discovered)
- Modify: `storage/app/tasks/topic_07.json` (only if missing/incorrect answers discovered)
- Create: `tests/Feature/Topic06Topic07AnswersIntegrityTest.php`

**Step 1: Write failing integrity test**

```php
public function test_topics_06_07_have_non_empty_answers_for_all_tasks(): void
{
    foreach (['06', '07'] as $topicId) {
        $data = json_decode(file_get_contents(storage_path("app/tasks/topic_{$topicId}.json")), true);
        foreach ($data['blocks'] as $block) {
            foreach ($block['zadaniya'] as $zadanie) {
                foreach ($zadanie['tasks'] as $task) {
                    $answer = trim((string) ($task['answer'] ?? ''));
                    $this->assertNotSame('', $answer, "Missing answer in topic {$topicId}, task {$task['id']}");
                }
            }
        }
    }
}
```

**Step 2: Run test to verify current state**

Run: `php artisan test tests/Feature/Topic06Topic07AnswersIntegrityTest.php`
Expected: FAIL only if any answers are missing/empty.

**Step 3: Write minimal implementation**

- Use resolver/backfill path for 06/07:
  - `php artisan oge:backfill-answers --topic=06 --topic=07`
- If specific wrong answers are found manually, correct precise JSON entries.

**Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Topic06Topic07AnswersIntegrityTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add storage/app/tasks/topic_06.json storage/app/tasks/topic_07.json tests/Feature/Topic06Topic07AnswersIntegrityTest.php
git commit -m "data(oge): finalize answers for topics 06 and 07"
```

### Task 7: End-to-end regression and cleanup

**Files:**
- Modify: `tests/Feature/OgePagesTest.php` (if assertions changed due to access policy)
- Modify: `tests/Feature/OgeAccessControlTest.php` (if needed)

**Step 1: Write/adjust final regression assertions**

```php
public function test_teacher_can_open_topics_base(): void
{
    $teacher = User::factory()->make(['role' => 'teacher']);
    $this->actingAs($teacher)->get('/topics')->assertOk();
}
```

**Step 2: Run full targeted suite**

Run: `php artisan test tests/Feature/OgeTopicAccessControlTest.php tests/Feature/AdminTaskAnswerUpdateApiTest.php tests/Feature/TopicAnswerProvenanceViewTest.php tests/Feature/Topic06Topic07AnswersIntegrityTest.php tests/Unit/TaskAnswerResolverTest.php`
Expected: all PASS.

**Step 3: Run lint/style checks (project-standard)**

Run: `./vendor/bin/pint --dirty`
Expected: no style violations.

**Step 4: Smoke check in browser**

Run app and verify manually:
- teacher sees topics but no edit buttons;
- admin sees `✏️`, can save answer, gets `Ручной by ...`;
- student cannot access `/topics`.

**Step 5: Commit**

```bash
git add tests/Feature/OgePagesTest.php tests/Feature/OgeAccessControlTest.php
git commit -m "test(oge): add regression coverage for answer editing and access rules"
```

