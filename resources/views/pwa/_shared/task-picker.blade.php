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
  /* Формула-пример — тот же шрифт, что в банке заданий (2 часть, .expr) */
  .strip-preview { font-family:"KaTeX_Main","Times New Roman",serif; font-size:18px; font-weight:650; color:#f8fafc; white-space:nowrap; }
  .strip-preview .katex { font-size:1.08em; }
  .strip-preview svg { max-height:32px; width:auto; }
  .bucket { display:flex; gap:4px; align-items:center; }
  .picker-card.is-existing { opacity:.5; cursor:default; }
</style>

<script>
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
</script>
