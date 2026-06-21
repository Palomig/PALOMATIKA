# Drill-down task picker — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Заменить «ряд селектов» выбора задач на drill-down мастер с полосками (класс → навык/тема с примером → уровень/блок → карточки задач), единым компонентом для урока и домашки.

**Architecture:** Бэкенд (`LessonTaskPickerService`) расширяется одним полем `preview` в полосках; всё остальное (эндпоинт `picker-options`, `TaskBankResolver`, схема БД) переиспользуется без изменений. Фронт: общий Blade-партиал + Alpine-фабрика `taskPicker({ onAdd })` с конечным автоматом шагов; банк скрыт за таблицей класс→банк. Урок и домашка подключают партиал и передают свой колбэк `onAdd(refs[])`.

**Tech Stack:** Laravel 10 (PHP 8.2), PHPUnit; Alpine.js 3 + Tailwind (CDN, без сборки); KaTeX для формул.

**Дизайн-источник:** [docs/plans/2026-06-21-task-picker-drilldown-design.md](2026-06-21-task-picker-drilldown-design.md)

**Замечание о тестах:** в проекте нет JS-тест-харнесса (Alpine через CDN). TDD применяется к бэкенду (PHPUnit). Фронт проверяется ручным мокап-прогоном на dev-сборке (78.17.28.40:4310) — согласно дизайн-фидбеку: показывать макетом, не катить сразу на прод.

---

## Phase 1 — Backend: «1 пример» в полосках (TDD)

### Task 1: `preview` в навыках (alg-skill)

**Files:**
- Modify: `app/Services/LessonTaskPickerService.php` (метод `skills()`, ~51-59; добавить приватный `firstSkillExample()`)
- Test: `tests/Feature/LessonPickerPreviewTest.php` (создать)

**Step 1: Написать падающий тест**

```php
<?php

namespace Tests\Feature;

use App\Services\LessonTaskPickerService;
use Tests\TestCase;

class LessonPickerPreviewTest extends TestCase
{
    public function test_grade7_skills_carry_preview(): void
    {
        $picker = new LessonTaskPickerService();
        $skills = $picker->skills(7);

        $this->assertNotEmpty($skills, '7 класс не отдал ни одного навыка');
        foreach ($skills as $s) {
            $this->assertArrayHasKey('preview', $s);
            $hasPreview = ($s['preview'] ?? '') !== '' || ($s['preview_svg'] ?? '') !== '';
            $this->assertTrue($hasPreview, "Навык {$s['slug']} без примера");
        }
    }
}
```

**Step 2: Запустить — убедиться, что падает**

Run: `php artisan test --filter=test_grade7_skills_carry_preview`
Expected: FAIL (`Failed asserting that array has the key 'preview'`)

**Step 3: Реализация**

В `LessonTaskPickerService::skills()` заменить тело `map` и добавить хелпер:

```php
public function skills(int $grade): array
{
    $bundle = (new AlgTaskDataService($grade))->getSkillsBundle();
    return array_values(array_map(function ($s) {
        $ex = $this->firstSkillExample($s);
        return [
            'slug'        => (string) ($s['slug']  ?? ''),
            'id'          => (string) ($s['id']    ?? ''),
            'title'       => (string) ($s['title'] ?? ''),
            'preview'     => $ex['expression'],
            'preview_svg' => $ex['image_svg'],
        ];
    }, $bundle['skills'] ?? []));
}

/** Первый непустой пример навыка: expression или svg из первой задачи первого уровня. */
private function firstSkillExample(array $skill): array
{
    foreach ($skill['levels'] ?? [] as $lvl) {
        foreach ($lvl['tasks'] ?? [] as $t) {
            $expr = (string) ($t['expression'] ?? '');
            $svg  = (string) ($t['svg'] ?? '');
            if ($expr !== '' || $svg !== '') {
                return ['expression' => $expr, 'image_svg' => $svg];
            }
        }
    }
    return ['expression' => '', 'image_svg' => ''];
}
```

**Step 4: Запустить — убедиться, что проходит**

Run: `php artisan test --filter=test_grade7_skills_carry_preview`
Expected: PASS

**Step 5: Commit**

```bash
git add app/Services/LessonTaskPickerService.php tests/Feature/LessonPickerPreviewTest.php
git commit -m "feat(picker): preview-пример в полосках навыков"
```

---

### Task 2: `preview` в темах (oge и пр.)

**Files:**
- Modify: `app/Services/LessonTaskPickerService.php` (метод `topics()`, ~37-46; добавить приватный `firstTopicExample()`)
- Test: `tests/Feature/LessonPickerPreviewTest.php` (дописать)

**Step 1: Дописать падающий тест**

```php
    public function test_oge_topics_carry_preview(): void
    {
        $picker = new LessonTaskPickerService();
        $topics = $picker->topics('oge');

        $this->assertNotEmpty($topics, 'ОГЭ не отдал ни одной темы');
        foreach ($topics as $t) {
            $this->assertArrayHasKey('preview', $t);
        }
        // хотя бы у части тем пример непустой (полностью картиночные допустимы пустыми)
        $withExpr = array_filter($topics, fn ($t) => ($t['preview'] ?? '') !== '' || ($t['preview_svg'] ?? '') !== '');
        $this->assertNotEmpty($withExpr, 'Ни у одной темы ОГЭ нет примера');
    }
```

**Step 2: Запустить — убедиться, что падает**

Run: `php artisan test --filter=test_oge_topics_carry_preview`
Expected: FAIL (`Failed asserting that array has the key 'preview'`)

**Step 3: Реализация**

В `topics()` пост-обработать список (DRY для oge/ege/vpr/alg-topic), добавить хелпер:

```php
public function topics(string $bank, ?int $grade = null): array
{
    $topics = match ($bank) {
        'oge'       => $this->ogeTopics(),
        'ege'       => $this->egeTopics(),
        'vpr'       => $grade ? $this->vprTopics($grade) : [],
        'alg-topic' => $grade ? $this->algTopics($grade) : [],
        default     => [],
    };

    foreach ($topics as &$t) {
        $ex = $this->firstTopicExample($bank, (string) $t['id'], $grade);
        $t['preview']     = $ex['expression'];
        $t['preview_svg'] = $ex['image_svg'];
    }
    return $topics;
}

/** Первый пример темы — берём первую поддерживаемую задачу через существующий tasks(). */
private function firstTopicExample(string $bank, string $id, ?int $grade): array
{
    $refs = array_filter(['topic_id' => $id, 'grade' => $grade], fn ($v) => $v !== null && $v !== '');
    $first = $this->tasks($bank, $refs)[0] ?? null;
    return [
        'expression' => (string) ($first['expression'] ?? ''),
        'image_svg'  => (string) ($first['image_svg'] ?? ''),
    ];
}
```

**Step 4: Запустить весь preview-тест + смежные**

Run: `php artisan test --filter=LessonPickerPreviewTest`
Run: `php artisan test --filter=LessonPickerTopicsTest`
Expected: PASS (оба — регресса старого контракта быть не должно, поля только добавляются)

**Step 5: Commit**

```bash
git add app/Services/LessonTaskPickerService.php tests/Feature/LessonPickerPreviewTest.php
git commit -m "feat(picker): preview-пример в полосках тем"
```

---

## Phase 2 — Frontend: общий drill-down партиал

### Task 3: Создать общий партиал + Alpine-фабрику `taskPicker`

**Files:**
- Create: `resources/views/pwa/_shared/task-picker.blade.php`

Партиал содержит: разметку 4 шагов (`class` → `strips` → `buckets` → `tasks`), хлебные крошки, и `<script>` с фабрикой `taskPicker(config)`. Стили переиспользуют существующие классы `.picker-*` из [lesson-prep.blade.php](../../resources/views/pwa/teacher/lesson-prep.blade.php) — вынести их в партиал не нужно (они уже глобальны на странице); добавить только новые `.strip`, `.crumb`, `.bucket`.

**Step 1: Конфиг и автомат (script)**

```js
// Таблица класс→банк — ЕДИНСТВЕННОЕ место «скрытого банка».
const PICKER_CLASSES = [
  { id: '7',     label: '7 класс',  bank: 'alg-skill', grade: 7 },
  { id: '8',     label: '8 класс',  bank: 'alg-skill', grade: 8 },
  { id: '9_oge', label: '9 ОГЭ',    bank: 'oge',       grade: null },
];

function taskPicker(config) {
  return {
    onAdd: config.onAdd,                 // (refs[]) => Promise|void
    existingUids: config.existingUids || (() => []), // дедуп
    step: 'class',                       // class | strips | buckets | tasks
    cls: null,                           // выбранный элемент PICKER_CLASSES
    refs: { grade: '', topic_id: '', skill_slug: '' },
    strips: [],                          // навыки/темы с preview
    tasks: [],                           // все задачи выбранной полоски
    bucketKey: null,                     // выбранный уровень/блок (group_key)
    selected: [],                        // выбранные карточки
    loading: false, error: '',
    katexReady: !!window.katex,

    get bank() { return this.cls?.bank; },

    // --- навигация ---
    async chooseClass(c) {
      this.cls = c;
      this.refs = { grade: c.grade || '', topic_id: '', skill_slug: '' };
      this.strips = []; this.tasks = []; this.bucketKey = null;
      this.step = 'strips';
      await this.loadStrips();
    },
    async chooseStrip(s) {
      if (this.bank === 'alg-skill') this.refs.skill_slug = s.slug;
      else this.refs.topic_id = s.id;
      this.tasks = []; this.bucketKey = null;
      this.step = 'buckets';
      await this.loadTasks();
    },
    chooseBucket(key) { this.bucketKey = String(key); this.step = 'tasks'; },

    goTo(step) {            // клик по хлебной крошке
      this.step = step;
      this.error = '';
      if (step === 'class') { this.cls = null; }
    },

    // --- данные ---
    async fetchOptions(extra) {
      this.loading = true; this.error = '';
      try {
        const params = new URLSearchParams({ bank: this.bank });
        const refs = { ...this.refs, ...extra };
        for (const [k, v] of Object.entries(refs))
          if (v !== '' && v != null) params.set(k, v);
        const r = await fetch(`/lessons/picker-options?${params}`,
          { headers: { Accept: 'application/json' }, credentials: 'include' });
        if (!r.ok) { this.error = 'Не удалось загрузить'; return null; }
        return await r.json();
      } catch (e) { this.error = String(e); return null; }
      finally { this.loading = false; }
    },
    async loadStrips() {
      const d = await this.fetchOptions();
      if (!d) return;
      this.strips = this.bank === 'alg-skill' ? (d.skills || []) : (d.topics || []);
    },
    async loadTasks() {
      const d = await this.fetchOptions();
      if (!d) return;
      this.tasks = d.tasks || [];
    },

    // --- buckets из уже загруженных задач (group_key/group_label) ---
    get buckets() {
      const out = [], seen = new Map();
      for (const t of this.tasks) {
        const k = String(t.group_key ?? '');
        if (!seen.has(k)) { seen.set(k, { key: k, label: t.group_label || '', count: 0 }); out.push(seen.get(k)); }
        seen.get(k).count++;
      }
      return out;
    },
    get bucketTasks() {
      return this.tasks.filter(t => String(t.group_key ?? '') === this.bucketKey);
    },

    // --- выбор задач ---
    isSelected(t) { return this.selected.some(s => s.uid === t.uid); },
    isExisting(t) { return this.existingUids().includes(t.uid); },
    toggle(t) {
      if (this.isExisting(t)) return;
      const i = this.selected.findIndex(s => s.uid === t.uid);
      if (i >= 0) this.selected.splice(i, 1); else this.selected.push({ ...t });
    },
    get selectedCount() { return this.selected.length; },

    taskRefs(t) {
      const refs = { ...this.refs };
      if (t.zadanie_number) refs.zadanie_number = t.zadanie_number;
      if (t.level_id) refs.level_id = t.level_id;
      refs.task_id = t.id;
      for (const k of Object.keys(refs)) if (refs[k] === '' || refs[k] == null) delete refs[k];
      return { bank: this.bank, refs };
    },
    async confirmAdd() {
      if (!this.selected.length) return;
      const payload = this.selected.map(t => this.taskRefs(t));
      await this.onAdd(payload);
      this.selected = [];
    },

    renderLatex(expr) {
      if (!expr) return '';
      if (window.katex) { try { return window.katex.renderToString(String(expr), { throwOnError: false, output: 'html' }); } catch (e) {} }
      return String(expr).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    },
  };
}
```

**Step 2: Разметка шагов (Blade, тот же файл, над `<script>`)**

```blade
{{-- Общий drill-down picker. Подключение:
     @include('pwa._shared.task-picker') внутри x-data="taskPicker({ onAdd })" --}}
<div class="task-picker">
  {{-- Хлебные крошки --}}
  <div class="crumbs" x-show="step !== 'class'">
    <button class="crumb" @click="goTo('class')">Класс</button>
    <template x-if="cls"><button class="crumb" @click="goTo('strips')" x-text="'› ' + cls.label"></button></template>
    <template x-if="step === 'buckets' || step === 'tasks'">
      <button class="crumb" @click="goTo('buckets')"
        x-text="'› ' + (bank === 'alg-skill' ? refs.skill_slug : ('Тема ' + refs.topic_id))"></button>
    </template>
  </div>

  <div x-show="loading" class="picker-group-label">загружаем…</div>
  <div x-show="error" style="color:var(--red);font-size:12px" x-text="error"></div>

  {{-- Шаг 1: класс --}}
  <div class="picker-row" x-show="step === 'class'">
    <template x-for="c in PICKER_CLASSES" :key="c.id">
      <button class="btn btn-icon" @click="chooseClass(c)" x-text="c.label"></button>
    </template>
  </div>

  {{-- Шаг 2: полоски (навыки/темы) с примером --}}
  <div class="strips" x-show="step === 'strips' && !loading">
    <template x-for="s in strips" :key="s.slug || s.id">
      <button class="strip" @click="chooseStrip(s)">
        <span class="strip-title" x-text="(s.id ? s.id + '. ' : '') + s.title"></span>
        <span class="strip-preview" x-show="s.preview" x-html="renderLatex(s.preview)"></span>
        <span class="strip-preview" x-show="!s.preview && s.preview_svg" x-html="s.preview_svg"></span>
      </button>
    </template>
    <div x-show="!strips.length" class="picker-group-label">Скоро</div>
  </div>

  {{-- Шаг 3: уровни/блоки --}}
  <div class="picker-row" x-show="step === 'buckets' && !loading">
    <template x-for="b in buckets" :key="b.key">
      <button class="btn btn-icon bucket" @click="chooseBucket(b.key)">
        <span x-text="b.label"></span> · <span x-text="b.count + ' зад'"></span>
      </button>
    </template>
    <div x-show="!buckets.length" class="picker-group-label">Нет задач</div>
  </div>

  {{-- Шаг 4: карточки задач --}}
  <div x-show="step === 'tasks'">
    <div class="picker-cards">
      <template x-for="t in bucketTasks" :key="t.uid">
        <div class="picker-card" :class="{ active: isSelected(t), 'is-existing': isExisting(t) }" @click="toggle(t)">
          <div class="picker-card-image" x-show="t.image_svg" x-html="t.image_svg"></div>
          <div class="picker-card-expr" x-html="renderLatex(t.expression) || '(без формулы)'"></div>
          <div class="picker-card-meta">
            <span x-show="isExisting(t)">уже добавлено</span>
            <span class="picker-card-answer" x-show="t.answer" x-text="t.answer"></span>
          </div>
        </div>
      </template>
    </div>
    <div class="picker-row" style="margin-top:12px">
      <button class="btn btn-primary" @click="confirmAdd" :disabled="!selectedCount"
        x-text="selectedCount ? `Добавить (${selectedCount})` : 'Добавить'"></button>
    </div>
  </div>
</div>

<style>
  .crumbs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:10px; }
  .crumb { background:none; border:none; color:var(--muted); font-size:12px; cursor:pointer; padding:2px 4px; }
  .crumb:hover { color:var(--text); }
  .strips { display:flex; flex-direction:column; gap:6px; }
  .strip { display:flex; align-items:center; justify-content:space-between; gap:12px;
    background:var(--surface2); border:1px solid var(--border); border-radius:10px;
    padding:12px 14px; cursor:pointer; text-align:left; transition:border-color .12s; }
  .strip:hover { border-color:var(--accent); }
  .strip-title { font-size:13px; color:var(--text); }
  .strip-preview { font-size:13px; color:var(--muted); white-space:nowrap; }
  .strip-preview svg { max-height:32px; width:auto; }
  .bucket { display:flex; gap:4px; align-items:center; }
  .picker-card.is-existing { opacity:.5; cursor:default; }
</style>
```

**Step 3: Verify (ручной мокап)**

Залить dev-сборку на http://78.17.28.40:4310/, открыть страницу подготовки урока (после Task 4), пройти: 7 класс → навык → уровень → отметить задачи. Проверить: крошки кликабельны, preview рендерится формулой, «Скоро» для 8 ОГЭ нет в списке (его нет в PICKER_CLASSES). Скриншоты — Стасу макетом.

**Step 4: Commit**

```bash
git add resources/views/pwa/_shared/task-picker.blade.php
git commit -m "feat(picker): общий drill-down партиал taskPicker"
```

---

### Task 4: Подключить партиал на уроке (lesson-prep)

**Files:**
- Modify: `resources/views/pwa/teacher/lesson-prep.blade.php` (заменить блок picker'а ~103-187; почистить старый JS picker'а в `lessonPrep()` ~269-440)

**Step 1: Заменить разметку picker'а**

Заменить весь блок `{{-- Task picker (унифицированный каскад…) --}}` (`<div class="lesson-card" x-show="pickerOpen">…</div>`) на обёртку с вложенным `x-data`:

```blade
<div class="lesson-card" x-show="pickerOpen" x-cloak>
  <div x-data="taskPicker({
        onAdd: (items) => $dispatch('picker-add', { items }),
        existingUids: () => tasks.map(t => t.uid).filter(Boolean),
      })">
    @include('pwa._shared.task-picker')
  </div>
  <button class="btn" @click="pickerOpen = false">Отмена</button>
</div>
```

**Step 2: Принять событие в `lessonPrep()`**

В корневом `<div class="page" x-data="lessonPrep(...)">` добавить слушатель:

```blade
<div class="page" x-data="lessonPrep({{ $session->id }}, '{{ $session->status }}')"
     x-init="init()" @picker-add.window="onPickerAdd($event.detail.items)">
```

В фабрике `lessonPrep()` удалить устаревшие picker-методы (`fetchPickerOptions`, `selectBank`, `onGradeChange`, `onTopicChange`, `onSkillChange`, `taskGroups`, `taskRefs`, `toggleCard`, `isCardSelected`, `addSelectedTasks`, поля `picker`/`selectedTasks`/`BANKS`) и добавить один метод приёма — он переиспользует существующий POST `/lessons/{id}/tasks`:

```js
async onPickerAdd(items) {
  for (const it of items) {
    const r = await fetch(`/lessons/${this.sessionId}/tasks`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
      credentials: 'include',
      body: JSON.stringify(it),   // { bank, refs }
    });
    if (!r.ok) { alert('Не удалось добавить задачу'); break; }
  }
  await this.refreshState();
  this.pickerOpen = false;
},
```

> Примечание: убедиться, что `refreshState()`/`/state` отдаёт `uid` в каждой задаче (для дедупа `existingUids`). Если нет — добавить `uid` в сериализацию задач сессии в соответствующем контроллере/ресурсе. Проверить в `TeacherLessonController` метод `state`.

**Step 3: Verify**

Запустить локально: `php artisan serve`, открыть `/lessons/{id}` черновика, добавить задачу через новый picker, проверить что появилась в списке и стартует урок. Затем dev-сборка → скриншот Стасу.

**Step 4: Commit**

```bash
git add resources/views/pwa/teacher/lesson-prep.blade.php
git commit -m "refactor(lesson): урок использует общий drill-down picker"
```

---

## Phase 3 — Домашка

### Task 5: Подключить партиал в assign-flow домашки

**Files:**
- Modify: вью assign-flow домашки (найти: `grep -rl "homework" resources/views/pwa/teacher` — экран создания `topic_photo_practice`)
- Modify: `app/Http/Controllers/Pwa/TeacherController.php` (`assignHomework`, ~491) — принять массив `{bank, refs}` и резолвить в снапшот

**Step 1: Бэкенд — тест на резолв массива refs в payload**

`tests/Feature/HomeworkPickerResolveTest.php`:

```php
public function test_picker_refs_resolve_to_homework_payload(): void
{
    $resolver = new \App\Services\TaskBankResolver();
    $picker = new \App\Services\LessonTaskPickerService();

    $tasks = $picker->tasks('alg-skill', ['grade' => 7, 'skill_slug' => $picker->skills(7)[0]['slug']]);
    $first = $tasks[0];

    $resolved = $resolver->resolve('alg-skill', [
        'grade' => 7, 'skill_slug' => $tasks[0]['level_id'] ? null : null, // см. taskRefs
    ] + ['grade' => 7, 'skill_slug' => $picker->skills(7)[0]['slug'],
         'level_id' => $first['level_id'], 'task_id' => $first['id']]);

    $this->assertNotSame('', (string) $resolved['answer']);
    $this->assertNotSame('', (string) $resolved['expression']);
}
```

Run: `php artisan test --filter=test_picker_refs_resolve_to_homework_payload`
Expected: PASS (резолвер уже умеет alg-skill — это страховочный тест контракта)

**Step 2: Разметка — подключить тот же партиал**

В assign-flow вью обернуть `@include('pwa._shared.task-picker')` в `x-data="taskPicker({ onAdd })"`, где `onAdd` кладёт `items` в локальный черновик ДЗ (массив `draftTasks`), а не шлёт сразу:

```blade
<div x-data="taskPicker({ onAdd: (items) => draftTasks.push(...items),
      existingUids: () => draftTasks.map(t => t.refs.task_id) })">
  @include('pwa._shared.task-picker')
</div>
```

**Step 3: Бэкенд — на сохранении ДЗ резолвить в `homework_topic_tasks`**

В `assignHomework()` для каждого `{bank, refs}` черновика:

```php
$resolver = app(\App\Services\TaskBankResolver::class);
foreach ($request->input('tasks', []) as $i => $picked) {
    $r = $resolver->resolve($picked['bank'], $picked['refs']);   // DomainException → пропустить
    HomeworkTopicTask::create([
        'homework_id'    => $homework->id,
        'task_order'     => $i,
        'task_payload'   => $r['raw'] + ['source_label' => $r['source_label'], 'expression' => $r['expression']],
        'correct_answer' => $r['answer'],
        // topic_number — оставить как есть для совместимости отображения, либо взять из refs
    ]);
}
```

Обернуть `resolve()` в try/catch(`DomainException`) — недоступную задачу пропустить, собрать в `$skipped`, вернуть предупреждение во flash.

**Step 4: Verify**

Создать ДЗ через новый picker (7 класс → навык → уровень → задачи), сохранить, назначить ученику. Проверить в БД: `homework_topic_tasks` получили `task_payload` + `correct_answer`. Ученик в PWA видит задачи. Dev-сборка → скриншот Стасу.

**Step 5: Commit**

```bash
git add resources/views/pwa/teacher app/Http/Controllers/Pwa/TeacherController.php tests/Feature/HomeworkPickerResolveTest.php
git commit -m "feat(homework): drill-down picker в создании ДЗ"
```

---

## Phase 4 — Финальная проверка

### Task 6: Регресс + обновление карты модуля

**Step 1:** `php artisan test` — весь сьют зелёный.

**Step 2:** Обновить `.claude/product/modules/homework.md` и `task-banks/_overview.md` — описать общий picker (класс→полоски→уровень/блок→задачи) как точку входа выбора задач (это часть definition of done по CLAUDE.md).

**Step 3:** Финальный мокап-прогон обоих потоков на dev-сборке, ссылка Стасу.

**Step 4: Commit**

```bash
git add .claude/product/modules
git commit -m "docs(modules): общий drill-down picker для урока и домашки"
```

---

## Замечания по рискам

- **`uid` в задачах урока:** дедуп на уроке зависит от того, что `/state` отдаёт `uid`. Проверить на Task 4 шаг 2; если нет — добавить.
- **`taskRefs` для alg-skill:** в picker `refs` для навыка содержит `skill_slug` + `level_id` + `task_id` + `grade`; для oge — `topic_id` + `zadanie_number` + `task_id`. `TaskBankResolver` ждёт именно эти ключи (см. `fromAlgSkill`/`fromOge`) — формат `taskRefs()` совпадает.
- **Деплой:** ветки `claude/*` авто-мёрж в прод. Вести в отдельной `claude/*`-ветке только когда готово к выкатке; до этого — мокап на dev-VPS.
- **YAGNI:** ЕГЭ/ВПР/alg-topic в `PICKER_CLASSES` не добавляем — добавятся строкой в таблицу класс→банк, когда понадобятся.
```
