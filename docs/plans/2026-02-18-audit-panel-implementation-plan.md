# Unified Audit Panel Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a full audit panel for teachers/admins with unified event storage, filters, details, CSV export, and 90-day retention.

**Architecture:** Introduce a centralized `audit_events` table and `AuditLogger` service. Migrate key action points (auth, OGE attempt flow, admin edits) to write standardized audit entries. Expose filtered API endpoints and a teacher UI page under `/teacher/audit`, plus a scheduled prune command for 90-day retention.

**Tech Stack:** Laravel 11 (routes/controllers/services/migrations/commands), Blade + Alpine.js, MySQL indexes, PHPUnit feature/unit tests.

---

### Task 1: Create audit_events storage and model

**Files:**
- Create: `database/migrations/2026_02_18_000001_create_audit_events_table.php`
- Create: `app/Models/AuditEvent.php`
- Test: `tests/Feature/Audit/AuditEventsMigrationTest.php`

**Step 1: Write the failing test**

```php
public function test_audit_events_table_has_required_columns_and_indexes(): void
{
    $this->assertTrue(Schema::hasTable('audit_events'));
    $this->assertTrue(Schema::hasColumns('audit_events', [
        'occurred_at','event_type','category','severity','actor_user_id','actor_role',
        'subject_type','subject_id','request_id','ip','user_agent','payload_json'
    ]));
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Audit/AuditEventsMigrationTest.php`
Expected: FAIL because table/migration do not exist.

**Step 3: Write minimal implementation**

- Add migration with required columns + indexes.
- Add `AuditEvent` model with fillable + casts (`payload_json` array, `occurred_at` datetime).

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Audit/AuditEventsMigrationTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add database/migrations/2026_02_18_000001_create_audit_events_table.php app/Models/AuditEvent.php tests/Feature/Audit/AuditEventsMigrationTest.php
git commit -m "Create audit_events table and model"
```

### Task 2: Implement AuditLogger service

**Files:**
- Create: `app/Services/AuditLogger.php`
- Create: `tests/Unit/AuditLoggerTest.php`

**Step 1: Write the failing test**

```php
public function test_logger_persists_actor_subject_and_payload(): void
{
    $event = app(AuditLogger::class)->log([
        'event_type' => 'login_success',
        'category' => 'auth',
        'severity' => 'info',
        'actor_user_id' => 7,
        'actor_role' => 'student',
        'subject_type' => 'user',
        'subject_id' => 7,
        'payload_json' => ['method' => 'password'],
    ]);

    $this->assertDatabaseHas('audit_events', ['id' => $event->id, 'event_type' => 'login_success']);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/AuditLoggerTest.php`
Expected: FAIL because service class does not exist.

**Step 3: Write minimal implementation**

- `AuditLogger::log(array $data): AuditEvent`
- Normalize defaults: `occurred_at=now`, `severity=info`, nullable tech fields.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/AuditLoggerTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/AuditLogger.php tests/Unit/AuditLoggerTest.php
git commit -m "Add centralized AuditLogger service"
```

### Task 3: Log auth events (web login/logout and failures)

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Auth/SocialAuthController.php`
- Modify: `app/Http/Controllers/Auth/TelegramBotAuthController.php`
- Test: `tests/Feature/Audit/AuthAuditLoggingTest.php`

**Step 1: Write the failing test**

```php
public function test_web_login_success_and_failure_are_logged(): void
{
    $this->postJson('/login', ['email' => 'bad@example.com', 'password' => 'bad'])->assertStatus(422);
    $this->assertDatabaseHas('audit_events', ['event_type' => 'login_failed', 'category' => 'auth']);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Audit/AuthAuditLoggingTest.php`
Expected: FAIL because auth events are not logged.

**Step 3: Write minimal implementation**

- On web login success/failure: call `AuditLogger`.
- On logout: call `AuditLogger`.
- On OAuth/Telegram callback success/failure: call `AuditLogger`.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Audit/AuthAuditLoggingTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add routes/web.php app/Http/Controllers/Auth/SocialAuthController.php app/Http/Controllers/Auth/TelegramBotAuthController.php tests/Feature/Audit/AuthAuditLoggingTest.php
git commit -m "Log authentication events into audit_events"
```

### Task 4: Log OGE attempt lifecycle into audit_events

**Files:**
- Modify: `app/Http/Controllers/OgeAttemptController.php`
- Optionally modify: `app/Services/OgeAttemptService.php`
- Test: `tests/Feature/Audit/OgeAuditLoggingTest.php`

**Step 1: Write the failing test**

```php
public function test_oge_focus_blur_commit_submit_are_logged_in_audit_events(): void
{
    // create attempt and hit endpoints
    $this->assertDatabaseHas('audit_events', ['event_type' => 'task_focused', 'category' => 'oge']);
    $this->assertDatabaseHas('audit_events', ['event_type' => 'attempt_submitted', 'category' => 'oge']);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Audit/OgeAuditLoggingTest.php`
Expected: FAIL because only old event table is written.

**Step 3: Write minimal implementation**

- In OGE endpoints (`start/focus/blur/commit/heartbeat/submit`) call `AuditLogger`.
- Include payload fields: `attempt_id`, `variant_id/hash`, `task_number`, `away_ms`, `visible`.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Audit/OgeAuditLoggingTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/OgeAttemptController.php app/Services/OgeAttemptService.php tests/Feature/Audit/OgeAuditLoggingTest.php
git commit -m "Log OGE attempt lifecycle events to unified audit"
```

### Task 5: Log admin/teacher critical mutations

**Files:**
- Modify: `app/Http/Controllers/AdminTaskAnswerController.php`
- Modify: `app/Http/Controllers/Teacher/StudentGroupController.php`
- Modify: `routes/web.php` (view-as endpoints)
- Test: `tests/Feature/Audit/AdminTeacherAuditLoggingTest.php`

**Step 1: Write the failing test**

```php
public function test_admin_answer_update_is_logged_with_old_and_new_values(): void
{
    // call patch answer endpoint
    $this->assertDatabaseHas('audit_events', ['event_type' => 'admin_answer_updated', 'category' => 'admin']);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Audit/AdminTeacherAuditLoggingTest.php`
Expected: FAIL because mutation logs are missing.

**Step 3: Write minimal implementation**

- Log answer updates with old/new answer in payload.
- Log group add/remove/create/delete operations.
- Log view-as set/clear.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Audit/AdminTeacherAuditLoggingTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/AdminTaskAnswerController.php app/Http/Controllers/Teacher/StudentGroupController.php routes/web.php tests/Feature/Audit/AdminTeacherAuditLoggingTest.php
git commit -m "Audit log admin and teacher mutations"
```

### Task 6: Build audit API with filters, details, CSV export

**Files:**
- Create: `app/Http/Controllers/Teacher/AuditController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Audit/AuditApiFiltersTest.php`
- Create: `tests/Feature/Audit/AuditExportTest.php`

**Step 1: Write the failing test**

```php
public function test_teacher_can_filter_audit_events_by_date_type_actor_and_subject(): void
{
    $response = $this->actingAs($teacher)->getJson('/api/audit/events?from=2026-02-01&to=2026-02-18&event_type[]=task_focused');
    $response->assertOk()->assertJsonStructure(['data','meta']);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Audit/AuditApiFiltersTest.php tests/Feature/Audit/AuditExportTest.php`
Expected: FAIL because routes/controller are missing.

**Step 3: Write minimal implementation**

- Add routes under `auth + role:teacher,admin`:
  - `GET /api/audit/events`
  - `GET /api/audit/events/{id}`
  - `GET /api/audit/meta`
  - `GET /api/audit/events/export`
- Implement validation and filters (date window max 90 days).
- Implement CSV export using same filter pipeline.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Audit/AuditApiFiltersTest.php tests/Feature/Audit/AuditExportTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Teacher/AuditController.php routes/web.php tests/Feature/Audit/AuditApiFiltersTest.php tests/Feature/Audit/AuditExportTest.php
git commit -m "Add audit API with filters details and CSV export"
```

### Task 7: Build teacher/admin audit panel UI

**Files:**
- Create: `resources/views/teacher/audit/index.blade.php`
- Modify: `resources/views/layouts/partials/nav-teacher.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Audit/AuditPageAccessTest.php`

**Step 1: Write the failing test**

```php
public function test_teacher_and_admin_can_open_audit_page_but_student_cannot(): void
{
    $this->actingAs($teacher)->get('/teacher/audit')->assertOk();
    $this->actingAs($admin)->get('/teacher/audit')->assertOk();
    $this->actingAs($student)->get('/teacher/audit')->assertStatus(403);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Audit/AuditPageAccessTest.php`
Expected: FAIL because page route/view not present.

**Step 3: Write minimal implementation**

- Add page route `/teacher/audit` with `role:teacher,admin`.
- Build Blade + Alpine UI:
  - filter bar
  - events table
  - details drawer
  - CSV export action.
- Add nav link in teacher sidebar.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Audit/AuditPageAccessTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/teacher/audit/index.blade.php resources/views/layouts/partials/nav-teacher.blade.php routes/web.php tests/Feature/Audit/AuditPageAccessTest.php
git commit -m "Add teacher/admin audit panel page"
```

### Task 8: Add retention command and scheduler (90 days)

**Files:**
- Create: `app/Console/Commands/PruneAuditEventsCommand.php`
- Modify: `app/Console/Kernel.php`
- Create: `tests/Feature/Audit/PruneAuditEventsCommandTest.php`

**Step 1: Write the failing test**

```php
public function test_prune_command_deletes_events_older_than_90_days(): void
{
    // seed old + fresh audit rows
    $this->artisan('audit:prune --days=90')->assertSuccessful();
    // assert old deleted, fresh remains
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Audit/PruneAuditEventsCommandTest.php`
Expected: FAIL because command is missing.

**Step 3: Write minimal implementation**

- Implement command `audit:prune`.
- Register daily schedule at `03:30`.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Audit/PruneAuditEventsCommandTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Console/Commands/PruneAuditEventsCommand.php app/Console/Kernel.php tests/Feature/Audit/PruneAuditEventsCommandTest.php
git commit -m "Add 90-day audit retention command and schedule"
```

### Task 9: Add support script to inspect OGE variant logs (requested)

**Files:**
- Create: `scripts/oge_variant_dump.php`
- Create: `docs/ops/oge-variant-dump.md`
- Test: `tests/Feature/Audit/OgeVariantDumpScriptSmokeTest.php`

**Step 1: Write the failing test**

```php
public function test_dump_script_outputs_expected_top_level_keys(): void
{
    // execute script in test mode and assert JSON has variant, summary, attempts
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Audit/OgeVariantDumpScriptSmokeTest.php`
Expected: FAIL because script is missing.

**Step 3: Write minimal implementation**

- Add reusable script version of current one-off dump utility.
- Document usage and output file conventions.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Audit/OgeVariantDumpScriptSmokeTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add scripts/oge_variant_dump.php docs/ops/oge-variant-dump.md tests/Feature/Audit/OgeVariantDumpScriptSmokeTest.php
git commit -m "Add reusable OGE variant dump script"
```

### Task 10: Final integrated verification

**Files:**
- Modify: `README.md` (optional short section link)
- Modify: `docs/plans/2026-02-18-audit-panel-design.md` (status note if needed)

**Step 1: Run targeted tests**

Run:
`php artisan test tests/Feature/Audit`

Expected: PASS.

**Step 2: Run related regression tests**

Run:
`php artisan test tests/Feature/OgeAttemptHeartbeatVisibilityTest.php tests/Feature/LoginIntendedRedirectTest.php`

Expected: PASS.

**Step 3: Manual smoke checklist**

- Open `/teacher/audit` as teacher/admin.
- Apply filters and verify row counts change.
- Open event details and verify payload.
- Export CSV and verify encoding/data.
- Trigger prune command and verify old rows deletion in DB.

**Step 4: Final commit**

```bash
git add README.md docs/plans/2026-02-18-audit-panel-design.md
git commit -m "Document unified audit panel operations and verification"
```
