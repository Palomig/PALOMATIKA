# Mini OGE Part 2 + Resume Banner Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add Part 2 tasks (topics 20+21) to mini-OGE variants, with optional photo upload for solutions, and add a "resume unfinished test" banner to the dashboard.

**Architecture:** Extend `OgeVariantPoolService` with a new `part2` topic group (topics 20, 21) that appends 2 tasks to every variant. Part 2 tasks use `task_number` 20 and 21 and are stored/scored using the same `OgeAttemptAnswer` + `OgeAttemptScoring` pipeline. Photo uploads are saved to `storage/app/solution_photos/` via a new API endpoint. The dashboard queries for the user's most recent `active` attempt and shows a resume banner.

**Tech Stack:** Laravel 10, Alpine.js 3, Tailwind CSS (CDN), vanilla JS FileReader for photo preview

---

## Key Files Reference

| File | Role |
|------|------|
| `app/Services/OgeVariantPoolService.php` | Variant generation — picks tasks per topic |
| `app/Http/Controllers/MiniAppController.php` | Dashboard, test, results views |
| `app/Http/Controllers/OgeAttemptController.php` | Answer commit/submit API |
| `app/Services/OgeAttemptService.php` | Attempt lifecycle + scoring |
| `app/Models/OgeVariant.php` | Mode constants |
| `resources/views/miniapp/dashboard.blade.php` | Dashboard view |
| `resources/views/miniapp/test.blade.php` | Test view (Alpine.js component) |
| `resources/views/miniapp/results.blade.php` | Results view |

---

### Task 1: Add Part 2 topics to pool service

**Files:**
- Modify: `app/Services/OgeVariantPoolService.php:16-20` (topic arrays)
- Modify: `app/Services/OgeVariantPoolService.php:157-270` (generateVariantTasks)

**Step 1: Add part2Topics array**

In `OgeVariantPoolService.php`, after line 20 (`geometryTopics`), add:

```php
// Part 2 topics: 20-21
protected array $part2Topics = ['20', '21'];
```

**Step 2: Append Part 2 tasks to all variant types**

At the end of `generateVariantTasks()` (before `return $result`), add logic to pick one task from each Part 2 topic and append to the result. This runs for ALL variant types (geometry, algebra, mixed, full):

```php
// Append Part 2 tasks (topics 20, 21) to every variant
foreach ($this->part2Topics as $topicId) {
    $picked = $tryPickForTopic($topicId);
    if ($picked === null) {
        continue;
    }
    $picked['task_number'] = (int) ltrim($topicId, '0');
    $result[] = $this->normalizeTaskForMiniApp($picked);
}
```

**Step 3: Commit**

```bash
git add app/Services/OgeVariantPoolService.php
git commit -m "feat(pool): add Part 2 topics (20, 21) to all variant types"
```

---

### Task 2: Add resume banner to dashboard

**Files:**
- Modify: `app/Http/Controllers/MiniAppController.php:224-280` (dashboard method)
- Modify: `resources/views/miniapp/dashboard.blade.php`

**Step 1: Query active attempt in dashboard controller**

In `MiniAppController::dashboard()`, before the `return view(...)` line (~line 277), add:

```php
// Find unfinished attempt for resume banner
$activeAttempt = OgeAttempt::where('student_id', $user->id)
    ->where('status', 'active')
    ->orderByDesc('last_seen_at')
    ->first();

$activeAttemptInfo = null;
if ($activeAttempt) {
    $variant = $activeAttempt->variant;
    $answeredCount = $activeAttempt->answers()->count();
    $totalCount = count($variant->config_json['tasks'] ?? []);
    if ($totalCount === 0 && $variant->hash) {
        // Legacy variants without config_json
        $builtVariant = $this->variantBuilder->build($variant->hash);
        $totalCount = count($builtVariant['tasks'] ?? []);
    }
    $activeAttemptInfo = [
        'id' => $activeAttempt->id,
        'title' => $variant->title ?? 'Вариант ОГЭ',
        'answeredCount' => $answeredCount,
        'totalCount' => $totalCount,
        'startedAt' => $activeAttempt->started_at,
    ];
}
```

Add `'activeAttemptInfo'` to the `compact()` call.

**Step 2: Add resume banner markup in dashboard view**

In `dashboard.blade.php`, right after the `{{-- COUNTDOWN STRIP --}}` section and before `{{-- LAST RESULT --}}`, add:

```blade
{{-- RESUME BANNER --}}
@if($activeAttemptInfo)
<a href="/tg/test/{{ $activeAttemptInfo['id'] }}" class="resume-banner">
  <div class="resume-left">
    <div class="resume-pulse"></div>
    <div class="resume-info">
      <div class="resume-title">{{ $activeAttemptInfo['title'] }}</div>
      <div class="resume-sub">
        Отвечено {{ $activeAttemptInfo['answeredCount'] }} из {{ $activeAttemptInfo['totalCount'] }}
        · начат {{ $activeAttemptInfo['startedAt']?->diffForHumans() }}
      </div>
    </div>
  </div>
  <div class="resume-btn">Продолжить →</div>
</a>
@endif
```

**Step 3: Add resume banner styles**

In the `@push('styles')` section of dashboard.blade.php:

```css
/* RESUME BANNER */
.resume-banner {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(124,58,237,.18), rgba(59,130,246,.12));
  border: 1.5px solid rgba(124,58,237,.4);
  border-radius: var(--r); padding: 14px 16px;
  text-decoration: none; color: inherit;
  opacity: 0; animation: fadeUp 0.3s ease 0.07s forwards;
}
.resume-banner:active { opacity: 0.85; }
.resume-left { display: flex; align-items: center; gap: 12px; }
.resume-pulse {
  width: 10px; height: 10px; background: var(--green);
  border-radius: 50%; flex-shrink: 0;
  animation: pulse 1.5s ease infinite;
}
.resume-title { font-family: var(--display); font-size: 14px; color: var(--text); }
.resume-sub { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 2px; }
.resume-btn {
  font-size: 12px; font-weight: 800; color: var(--purple);
  white-space: nowrap;
}
```

**Step 4: Commit**

```bash
git add app/Http/Controllers/MiniAppController.php resources/views/miniapp/dashboard.blade.php
git commit -m "feat(miniapp): add resume banner for unfinished tests on dashboard"
```

---

### Task 3: Photo upload for Part 2 solutions

**Files:**
- Modify: `app/Http/Controllers/OgeAttemptController.php` (new endpoint)
- Modify: `routes/web.php:370-376` (new route)
- Modify: `resources/views/miniapp/test.blade.php` (camera button UI)

**Step 1: Add upload route**

In `routes/web.php`, after line 374 (commit route), add:

```php
Route::post('/attempts/{attempt}/tasks/{taskNumber}/photo', [OgeAttemptController::class, 'uploadPhoto'])->name('api.oge.attempt.photo');
```

**Step 2: Add upload controller method**

In `OgeAttemptController.php`, add:

```php
public function uploadPhoto(Request $request, int $attempt, int $taskNumber)
{
    $attempt = OgeAttempt::where('id', $attempt)
        ->where('student_id', Auth::id())
        ->where('status', 'active')
        ->firstOrFail();

    $request->validate([
        'photo' => 'required|image|max:5120', // 5MB max
    ]);

    $dir = "solution_photos/{$attempt->id}";
    $filename = "task_{$taskNumber}_" . time() . '.' . $request->file('photo')->extension();
    $path = $request->file('photo')->storeAs($dir, $filename);

    return response()->json(['success' => true, 'path' => $path]);
}
```

**Step 3: Add camera button in test.blade.php**

In the answer input section of `test.blade.php`, after the answer input area (around line 768), add a photo upload button that appears for Part 2 tasks (task_number >= 20):

```html
{{-- Photo upload for Part 2 tasks --}}
<template x-if="currentTask && currentTask.task_number >= 20">
  <div class="photo-upload-area">
    <input type="file" accept="image/*" capture="environment"
           x-ref="photoInput" style="display:none"
           @change="uploadPhoto($event)">
    <button class="photo-btn" @click="$refs.photoInput.click()"
            :class="{ 'has-photo': photos[currentTask.task_number] }">
      <span x-text="photos[currentTask.task_number] ? '📷 Фото прикреплено' : '📷 Сфотографировать решение'"></span>
    </button>
    <div class="photo-hint">Необязательно, но поможет при проверке</div>
  </div>
</template>
```

**Step 4: Add photo state and upload function in Alpine.js**

In the `testApp()` function, add to state:

```js
photos: {},  // task_number → true (uploaded)
```

Add method:

```js
async uploadPhoto(event) {
  const file = event.target.files?.[0];
  if (!file) return;

  const tn = this.currentTask.task_number;
  const formData = new FormData();
  formData.append('photo', file);

  try {
    const res = await fetch(`/api/oge/attempts/${this.attemptId}/tasks/${tn}/photo`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: formData,
    });
    if (res.ok) {
      this.photos[tn] = true;
    } else {
      alert('Ошибка загрузки фото');
    }
  } catch (e) {
    alert('Ошибка сети при загрузке фото');
  }
  // Reset input so same file can be re-selected
  event.target.value = '';
},
```

**Step 5: Add photo button styles**

In the `@push('styles')` of `test.blade.php`:

```css
.photo-upload-area { margin-top: 12px; }
.photo-btn {
  width: 100%; padding: 12px; border-radius: var(--r);
  border: 1.5px dashed var(--border); background: var(--surface);
  color: var(--muted); font-size: 13px; font-weight: 700;
  cursor: pointer; transition: all 0.15s;
}
.photo-btn:active { background: var(--surface2); }
.photo-btn.has-photo {
  border-color: var(--green); border-style: solid;
  color: var(--green); background: rgba(34, 197, 94, 0.08);
}
.photo-hint {
  font-size: 10px; font-weight: 600; color: var(--muted);
  text-align: center; margin-top: 4px; opacity: 0.7;
}
```

**Step 6: Commit**

```bash
git add app/Http/Controllers/OgeAttemptController.php routes/web.php resources/views/miniapp/test.blade.php
git commit -m "feat(miniapp): add photo upload for Part 2 task solutions"
```

---

### Task 4: Flush existing pool variants

Old pool variants don't include Part 2 tasks. After deploying, flush the pool so new variants are generated with Part 2 tasks.

**Step 1: Run pool:flush on production**

```bash
# After deploy completes:
run_artisan pool:flush
run_artisan cache:clear
```

**Step 2: Verify**

Check that new variants include task_numbers 20 and 21 by starting a test in the miniapp.

---

## Out of Scope (Future Tasks)

- **Answer templates** (visual fraction input, sqrt input, system-of-equations input) — complex UI work, separate plan
- **Photo review UI** for teachers/admins
- **Part 2 scoring weight** (2 points vs 1 point)
- **Solution photo display** on results page
