{{-- Общий picker задач «как база заданий». Подключение:
     @include('pwa._shared.task-picker') внутри x-data="taskPicker({ onAdd, existingUids })"
     Контракт: onAdd(payload: Array<{bank, refs}>), existingUids: () => string[] --}}
<div class="task-picker">
  {{-- Класс — всегда видимые пилюли, «9 ОГЭ» выбран по умолчанию --}}
  <div class="tp-pills">
    <template x-for="c in PICKER_CLASSES" :key="c.id">
      <button type="button" class="topic-pill" :class="{ active: cls && cls.id === c.id }"
              @click="chooseClass(c)" x-text="c.label"></button>
    </template>
  </div>

  {{-- У 5–8 классов два банка: навыки алгебры и задания ВПР. --}}
  <div class="tp-pills" x-show="cls && cls.banks" x-cloak style="margin-top:6px">
    <template x-for="b in (cls?.banks || [])" :key="b.bank">
      <button type="button" class="topic-pill" :class="{ active: bank === b.bank }"
              @click="chooseBank(b.bank)" x-text="b.label"></button>
    </template>
  </div>

  <div x-show="loading" class="picker-group-label">загружаем…</div>
  <div x-show="error" style="color:var(--red);font-size:12px" x-text="error"></div>

  {{-- ====== Банки по темам (ОГЭ / ВПР / ЕГЭ): раздел → тема → задания ======
       Разделы есть у ОГЭ и ЕГЭ (части экзамена), у ВПР сразу темы. ====== --}}
  <div x-show="isTopicBank">
    {{-- Разделы (1я часть / 2я часть / Новые задания) --}}
    <div class="tp-pills" x-show="sections.length" style="margin-top:10px">
      <template x-for="s in sections" :key="s.id">
        <button type="button" class="topic-pill" :class="{ active: sectionId === s.id }"
                @click="chooseSection(s.id)" x-text="s.title"></button>
      </template>
    </div>

    {{-- Темы — номера, горизонтальная прокрутка как в базе.
         У ОГЭ и ЕГЭ темы появляются после выбора части, у ВПР — сразу. --}}
    <div class="topics-row" x-show="topics.length && (sections.length === 0 || sectionId)" style="margin-top:10px">
      <template x-for="t in topics" :key="t.id">
        <button type="button" class="topic-pill" :class="{ active: topicId === String(t.id) }"
                @click="chooseTopic(t.id)" :title="t.title" x-text="Number(t.id)"></button>
      </template>
    </div>

    {{-- Название выбранной темы: у ВПР и ЕГЭ по одному номеру не догадаться. --}}
    <div class="picker-group-label" x-show="topicId && currentTopicTitle" x-text="currentTopicTitle" style="margin-top:6px"></div>

    {{-- Спойлеры-задания с карточками задач --}}
    <div class="task-list" x-show="topicId && !loading">
      <template x-for="g in groups" :key="g.key">
        <details class="spoiler">
          <summary>
            <span class="tp-summary-label">
              <span x-text="g.label"></span>
              <span class="tp-count" x-text="'(' + g.tasks.length + ')'"></span>
            </span>
            {{-- Toggle всего блока: все выбраны — снять, иначе добрать недостающие --}}
            <button type="button" class="tp-block-btn" @click.stop.prevent="toggleGroup(g)"
                    x-text="groupAllSelected(g) ? 'Снять блок' : 'Выбрать блок'"></button>
          </summary>
          <div class="spoiler-body">
            <template x-for="t in g.tasks" :key="t.uid">
              <div class="task-item tp-card" :class="{ 'tp-selected': isSelected(t), 'is-existing': isExisting(t) }" @click="toggle(t)">
                <span class="tp-check" x-show="isSelected(t)">✓</span>
                <div class="tp-illus" x-show="cardSvg(t)" x-html="cardSvg(t)"></div>
                <template x-if="cardImage(t)">
                  <div class="tp-illus">
                    {{-- Путь из банка ЕГЭ уже абсолютный (/ege-bank/img/…),
                         у ОГЭ — имя файла внутри папки темы. --}}
                    <img :src="cardImageSrc(t)" alt="" loading="lazy">
                  </div>
                </template>
                <div class="task-item-text" x-show="t.text" x-text="t.text"></div>
                <div class="task-item-text tp-expr" x-show="!t.text && t.expression" x-html="renderLatex(t.expression)"></div>
                <div class="answer-row">
                  <template x-if="t.answer">
                    <span><span class="answer-label">Ответ:</span> <span class="tp-answer" x-text="t.answer"></span></span>
                  </template>
                  <template x-if="!t.answer">
                    <span class="tp-badge">без автопроверки</span>
                  </template>
                  <span class="tp-existing-note" x-show="isExisting(t)">уже добавлено</span>
                </div>
              </div>
            </template>
          </div>
        </details>
      </template>
      <div x-show="!groups.length" class="picker-group-label">Нет задач</div>
    </div>
  </div>

  {{-- ============ 7/8: прежний флоу навык → уровень → задачи ============ --}}
  <div x-show="bank === 'alg-skill'">
    <div class="crumbs" x-show="step !== 'strips'" style="margin-top:10px">
      <button type="button" class="crumb" @click="goTo('strips')">Навыки</button>
      <button type="button" class="crumb" x-show="step === 'tasks'" @click="goTo('buckets')"
              x-text="'› ' + refs.skill_slug"></button>
    </div>

    {{-- Полоски навыков с примером --}}
    <div class="strips" x-show="step === 'strips' && !loading" style="margin-top:10px">
      <template x-for="s in strips" :key="s.slug || s.id">
        <button type="button" class="strip" @click="chooseStrip(s)">
          <span class="strip-title" x-text="(s.id ? s.id + '. ' : '') + s.title"></span>
          <span class="strip-preview" x-show="s.preview" x-html="renderLatex(s.preview)"></span>
          <span class="strip-preview" x-show="!s.preview && s.preview_svg" x-html="s.preview_svg"></span>
        </button>
      </template>
      <div x-show="!strips.length" class="picker-group-label">Скоро</div>
    </div>

    {{-- Уровни --}}
    <div class="buckets" x-show="step === 'buckets' && !loading" style="margin-top:10px">
      <template x-for="b in buckets" :key="b.key">
        <button type="button" class="btn bucket" @click="chooseBucket(b.key)">
          <span x-text="b.label"></span> · <span x-text="b.count + ' зад'"></span>
        </button>
      </template>
      <div x-show="!buckets.length" class="picker-group-label">Нет задач</div>
    </div>

    {{-- Карточки задач уровня --}}
    <div class="task-list" x-show="step === 'tasks'">
      <template x-for="t in bucketTasks" :key="t.uid">
        <div class="task-item tp-card" :class="{ 'tp-selected': isSelected(t), 'is-existing': isExisting(t) }" @click="toggle(t)">
          <span class="tp-check" x-show="isSelected(t)">✓</span>
          <div class="tp-illus" x-show="t.image_svg" x-html="t.image_svg"></div>
          <div class="task-item-text tp-expr" x-show="t.expression" x-html="renderLatex(t.expression)"></div>
          <div class="answer-row">
            <template x-if="t.answer">
              <span><span class="answer-label">Ответ:</span> <span class="tp-answer" x-text="t.answer"></span></span>
            </template>
            <template x-if="!t.answer">
              <span class="tp-badge">без автопроверки</span>
            </template>
            <span class="tp-existing-note" x-show="isExisting(t)">уже добавлено</span>
          </div>
        </div>
      </template>
      <div x-show="!bucketTasks.length" class="picker-group-label">Нет задач</div>
    </div>
  </div>

  {{-- Глобальная корзина: sticky-панель снизу, видна при N>0 --}}
  <div class="tp-cart" x-show="selectedCount > 0" x-cloak>
    <button type="button" class="tp-cart-add" @click="confirmAdd"
            x-text="'Выбрано ' + selectedCount + ' · Добавить'"></button>
    <button type="button" class="tp-cart-clear" @click="clearCart">Очистить</button>
  </div>
</div>

<style>
  /* Пилюли классов/разделов/тем — как .topic-pill в базе (tasks-part1) */
  .task-picker .tp-pills { display:flex; gap:6px; flex-wrap:wrap; }
  .task-picker .topics-row { display:flex; gap:6px; overflow-x:auto; padding-bottom:2px; }
  .task-picker .topic-pill {
    min-width:42px; text-align:center; padding:8px 10px; border-radius:10px;
    border:1px solid var(--border); background:var(--surface);
    color:var(--text); cursor:pointer;
    font-family:var(--display); font-size:14px; white-space:nowrap; flex-shrink:0;
  }
  .task-picker .topic-pill.active {
    border-color:var(--purple-bd); background:var(--purple-bg); color:var(--purple);
  }

  /* Спойлеры — как .spoiler в базе */
  .task-picker .task-list { margin-top:12px; display:flex; flex-direction:column; gap:8px; }
  .task-picker .spoiler {
    background:var(--surface); border:1px solid var(--border);
    border-radius:12px; overflow:hidden;
  }
  .task-picker .spoiler summary {
    list-style:none; cursor:pointer; padding:12px 14px;
    font-family:var(--display); font-size:13px; color:var(--text);
    display:flex; justify-content:space-between; align-items:center; gap:8px;
  }
  .task-picker .spoiler summary::-webkit-details-marker { display:none; }
  .task-picker .spoiler summary::after {
    content:'▾'; color:var(--muted); transition:transform .15s ease; flex-shrink:0;
  }
  .task-picker .spoiler[open] summary::after { transform:rotate(180deg); }
  .task-picker .spoiler-body { padding:0 10px 10px; display:flex; flex-direction:column; gap:8px; }
  .task-picker .tp-summary-label { flex:1; min-width:0; }
  .task-picker .tp-count { font-size:11px; color:var(--muted); font-weight:400; }
  .task-picker .tp-block-btn {
    flex-shrink:0; padding:5px 10px; border-radius:8px; cursor:pointer;
    border:1px solid var(--purple-bd); background:var(--purple-bg); color:var(--purple);
    font-size:11px; font-weight:800; white-space:nowrap;
  }
  .task-picker .tp-block-btn:active { opacity:.7; }

  /* Карточка задачи — как .task-item в базе + состояние выбора */
  .task-picker .task-item {
    background:var(--surface); border:1px solid var(--border);
    border-radius:12px; padding:12px 14px;
  }
  .task-picker .tp-card { position:relative; cursor:pointer; user-select:none; -webkit-user-select:none; transition:border-color .12s, background .12s; }
  .task-picker .tp-card.tp-selected { border-color:var(--purple); background:var(--purple-bg); }
  .task-picker .tp-card.is-existing { opacity:.5; cursor:default; }
  .task-picker .tp-check {
    position:absolute; top:8px; right:10px;
    color:var(--purple); font-size:14px; font-weight:800;
  }
  .task-picker .tp-illus {
    margin-bottom:10px; border:1px solid var(--border); border-radius:10px;
    background:var(--surface2); padding:8px;
    display:flex; align-items:center; justify-content:center;
  }
  .task-picker .tp-illus svg { max-width:100%; max-height:160px; height:auto; }
  .task-picker .tp-illus img { max-width:100%; max-height:160px; height:auto; }
  .task-picker .task-item-text { font-size:13px; line-height:1.45; color:var(--text); white-space:pre-line; }
  /* Растры банка ЕГЭ в карточке: обозначения внутри предложения строкой,
     чертёж блоком. Оба чёрным по прозрачному — нужна белая подложка. */
  .task-picker .tp-expr img.fipi-inline {
    display: inline-block; background: #fff; border-radius: 3px;
    padding: 0 2px; height: 1.25em; width: auto; vertical-align: -0.24em;
  }
  .task-picker .tp-illus img { background: #fff; border-radius: 6px; padding: 4px; }
  .task-picker .tp-expr { font-size:15px; white-space:normal; }
  .task-picker .answer-row { margin-top:8px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
  .task-picker .answer-label { font-size:10px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); white-space:nowrap; }
  /* Ответ — мелко и muted (это picker учителя, не витрина базы) */
  .task-picker .tp-answer { font-family:ui-monospace, monospace; font-size:12px; color:var(--muted); }
  .task-picker .tp-badge {
    font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.06em;
    padding:2px 7px; border-radius:5px;
    background:var(--surface2); border:1px solid var(--border); color:var(--muted);
  }
  .task-picker .tp-existing-note { font-size:11px; color:var(--muted); margin-left:auto; }

  /* Прежний флоу 7/8 (навыки/уровни) */
  .task-picker .crumbs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:6px; }
  .task-picker .crumb { background:none; border:none; color:var(--muted); font-size:12px; cursor:pointer; padding:2px 4px; }
  .task-picker .crumb:hover { color:var(--text); }
  .task-picker .strips { display:flex; flex-direction:column; gap:6px; }
  .task-picker .strip { display:flex; align-items:center; justify-content:space-between; gap:12px;
    background:var(--surface2); border:1px solid var(--border); border-radius:10px;
    padding:12px 14px; cursor:pointer; text-align:left; transition:border-color .12s; }
  .task-picker .strip:hover { border-color:var(--accent); }
  .task-picker .strip-title { font-size:13px; color:var(--text); }
  /* Формула-пример — тот же шрифт, что в банке заданий */
  .task-picker .strip-preview { font-family:"KaTeX_Main","Times New Roman",serif; font-size:18px; font-weight:650; color:var(--text); white-space:nowrap; }
  .task-picker .strip-preview .katex { font-size:1.08em; }
  .task-picker .strip-preview svg { max-height:32px; width:auto; }
  .task-picker .buckets { display:flex; flex-direction:column; gap:6px; }
  .task-picker .bucket { display:flex; gap:4px; align-items:center; justify-content:flex-start; width:100%; text-align:left; font-weight:400; }

  /* Sticky-корзина снизу */
  .task-picker .tp-cart {
    position:sticky; bottom:0; z-index:20;
    display:flex; gap:8px; align-items:stretch;
    margin-top:12px; padding:10px 0 calc(10px + var(--safe-bottom, 0px));
    background:var(--bg); border-top:1px solid var(--border);
  }
  .task-picker .tp-cart-add {
    flex:1; padding:12px; border:none; border-radius:12px; cursor:pointer;
    background:var(--purple); color:#fff; font-family:var(--display); font-size:14px;
  }
  .task-picker .tp-cart-add:active { filter:brightness(.9); }
  .task-picker .tp-cart-clear {
    padding:12px 14px; border:1px solid var(--border); border-radius:12px; cursor:pointer;
    background:var(--surface); color:var(--muted); font-size:13px; font-weight:700;
  }
</style>

<script>
// Таблица класс→банк — ЕДИНСТВЕННОЕ место «скрытого банка».
// window.* вместо const: в homework партиал живёт внутри <template x-if>,
// и скрипт может выполниться повторно при каждом открытии модалки.
// Один инструмент для всех классов. Список приходит с сервера и зависит от
// того, где реально есть задачи: пустых вкладок быть не должно.
window.PICKER_CLASSES = window.PICKER_CLASSES
  || @json(app(App\Services\LessonTaskPickerService::class)->availableClasses());

function taskPicker(config) {
  // Не в reactive-состоянии: запись из renderLatex во время рендера не должна
  // триггерить Alpine-эффекты (риск цикла).
  let typesetTimer = null;

  return {
    onAdd: config.onAdd,                 // (Array<{bank, refs}>) => Promise|void
    existingUids: config.existingUids || (() => []), // дедуп с уже добавленными
    cls: null,                           // выбранный элемент PICKER_CLASSES
    refs: { grade: '', topic_id: '', skill_slug: '' },
    // ОГЭ: раздел → тема → спойлеры
    sections: [], sectionId: null,
    topics: [], topicId: null,
    // 7/8: навык → уровень → задачи
    step: 'strips',                      // strips | buckets | tasks
    strips: [], bucketKey: null,
    tasks: [],                           // задачи выбранной темы/навыка
    selected: [],                        // ГЛОБАЛЬНАЯ корзина: не сбрасывается при навигации
    loading: false, error: '',
    katexReady: !!window.katex,
    _reqId: 0,

    bankOverride: null,

    get bank() { return this.bankOverride || this.cls?.bank; },
    /** ОГЭ, ВПР и ЕГЭ ходят одинаково: тема → задания. */
    get isTopicBank() { return ['oge', 'vpr', 'ege', 'alg-topic'].includes(this.bank); },
    get currentTopicTitle() {
      return this.topics.find(t => String(t.id) === String(this.topicId))?.title || '';
    },

    // Alpine вызывает init() автоматически: «9 ОГЭ» выбран по умолчанию.
    init() {
      this.chooseClass(PICKER_CLASSES.find(c => c.bank === 'oge'));
      this.waitForKatex();
    },

    waitForKatex() {
      if (window.katex) { this.katexReady = true; return; }
      const t0 = Date.now();
      const tick = () => {
        if (window.katex) { this.katexReady = true; return; }
        if (Date.now() - t0 < 8000) setTimeout(tick, 80);
      };
      tick();
    },

    // --- навигация (корзину selected НЕ трогаем — она глобальная) ---
    async chooseClass(c) {
      this.cls = c;
      this.bankOverride = null;
      this.refs = { grade: c.grade || '', topic_id: '', skill_slug: '' };
      this.sections = []; this.sectionId = null;
      this.topics = []; this.topicId = null;
      this.strips = []; this.tasks = []; this.bucketKey = null;
      this.step = 'strips';
      await this.loadForBank();
    },

    /** Переключение банка внутри класса (навыки ↔ ВПР), класс и grade те же. */
    async chooseBank(bank) {
      this.bankOverride = bank;
      this.refs = { grade: this.cls?.grade || '', topic_id: '', skill_slug: '' };
      this.sections = []; this.sectionId = null;
      this.topics = []; this.topicId = null;
      this.strips = []; this.tasks = []; this.bucketKey = null;
      this.step = 'strips';
      await this.loadForBank();
    },

    async loadForBank() {
      const d = await this.fetchOptions();
      if (!d) return;

      if (this.isTopicBank) {
        // У ОГЭ и ЕГЭ сначала разделы (части экзамена), у ВПР их нет.
        this.sections = d.sections || [];
        this.topics = d.topics || [];
      } else {
        this.strips = d.skills || [];
      }
    },
    async chooseSection(id) {
      this.sectionId = id;
      this.topicId = null; this.refs.topic_id = '';
      this.topics = []; this.tasks = [];
      const d = await this.fetchOptions();
      if (d) this.topics = d.topics || [];
    },
    async chooseTopic(id) {
      this.topicId = String(id);
      this.refs.topic_id = String(id);
      this.tasks = [];
      const d = await this.fetchOptions();
      if (d) this.tasks = d.tasks || [];
      this.typeset();
    },
    async chooseStrip(s) {
      this.refs.skill_slug = s.slug;
      this.tasks = []; this.bucketKey = null;
      this.step = 'buckets';
      const d = await this.fetchOptions();
      if (d) this.tasks = d.tasks || [];
    },
    chooseBucket(key) { this.bucketKey = String(key); this.step = 'tasks'; this.typeset(); },
    goTo(step) { this.step = step; this.error = ''; },

    // --- данные ---
    async fetchOptions() {
      const my = ++this._reqId;
      this.loading = true; this.error = '';
      try {
        const params = new URLSearchParams({ bank: this.bank });
        // Раздел шлём для любого банка, где он есть: у ЕГЭ это части
        // экзамена, и без параметра сервер отдавал все 19 номеров сразу.
        if (this.sectionId) params.set('section', this.sectionId);
        for (const [k, v] of Object.entries(this.refs))
          if (v !== '' && v != null) params.set(k, v);
        const r = await fetch(`/lessons/picker-options?${params}`,
          { headers: { Accept: 'application/json' }, credentials: 'include' });
        if (!r.ok) { this.error = 'Не удалось загрузить'; return null; }
        const data = await r.json();
        if (my !== this._reqId) return null; // пришёл устаревший ответ — игнорируем
        return data;
      } catch (e) { this.error = String(e); return null; }
      finally { if (my === this._reqId) this.loading = false; }
    },

    // --- группировка (спойлеры для ОГЭ, buckets для 7/8) ---
    get groups() {
      const out = [], seen = new Map();
      for (const t of this.tasks) {
        const k = String(t.group_key ?? '');
        if (!seen.has(k)) { seen.set(k, { key: k, label: t.group_label || '', tasks: [] }); out.push(seen.get(k)); }
        seen.get(k).tasks.push(t);
      }
      return out;
    },
    get buckets() {
      return this.groups.map(g => ({ key: g.key, label: g.label, count: g.tasks.length }));
    },
    get bucketTasks() {
      return this.tasks.filter(t => String(t.group_key ?? '') === this.bucketKey);
    },

    // --- карточка ---
    cardSvg(t) {
      const img = t.image || '';
      return t.image_svg || (img.startsWith('<svg') ? img : '');
    },
    cardImage(t) {
      const img = t.image || '';
      return img !== '' && !img.startsWith('<svg') ? img : '';
    },
    cardImageSrc(t) {
      const img = this.cardImage(t);
      if (img === '') return '';
      return img.startsWith('/') || img.startsWith('http')
        ? img
        : `/images/tasks/${Number(this.refs.topic_id)}/${img}`;
    },

    // --- глобальная корзина ---
    // Составной uid: bank + grade + тема/навык + uid задачи — исключает коллизии
    // между темами (uid бэкенда уникален только внутри темы/навыка).
    cartKey(t) {
      const scope = this.refs.topic_id || this.refs.skill_slug || '';
      return `${this.bank}:${this.refs.grade || ''}:${scope}:${t.uid}`;
    },
    isSelected(t) { return this.selected.some(s => s.key === this.cartKey(t)); },
    isExisting(t) { return this.existingUids().includes(t.uid); },
    toggle(t) {
      if (this.isExisting(t)) return;
      if (this.isSelected(t)) this.removeFromCart(t); else this.addToCart(t);
    },
    addToCart(t) {
      // Снимок {bank, refs} делаем сразу — this.refs меняется при навигации,
      // а корзина переживает смену класса/раздела/темы.
      this.selected.push({ key: this.cartKey(t), ...this.taskRefs(t) });
    },
    removeFromCart(t) {
      const key = this.cartKey(t);
      const i = this.selected.findIndex(s => s.key === key);
      if (i >= 0) this.selected.splice(i, 1);
    },
    get selectedCount() { return this.selected.length; },
    clearCart() { this.selected = []; },

    // «Выбрать блок»: если все доступные задачи блока выбраны — снять, иначе добрать.
    groupAllSelected(g) {
      const avail = g.tasks.filter(t => !this.isExisting(t));
      return avail.length > 0 && avail.every(t => this.isSelected(t));
    },
    toggleGroup(g) {
      const avail = g.tasks.filter(t => !this.isExisting(t));
      if (!avail.length) return;
      if (avail.every(t => this.isSelected(t))) {
        for (const t of avail) this.removeFromCart(t);
      } else {
        for (const t of avail) if (!this.isSelected(t)) this.addToCart(t);
      }
    },

    taskRefs(t) {
      const refs = { ...this.refs };
      // «Новые задания» живут в задании №0 — zadanie_number: 0 обязан попасть в refs,
      // поэтому проверяем на null/undefined, а НЕ на falsy (`if (t.zadanie_number)` потерял бы 0).
      if (t.zadanie_number != null) refs.zadanie_number = t.zadanie_number;
      if (t.level_id) refs.level_id = t.level_id;
      refs.task_id = t.id;
      // Чистка пустых значений. 0 здесь сохраняется: `0 === ''` → false и `0 == null` → false.
      for (const k of Object.keys(refs)) if (refs[k] === '' || refs[k] == null) delete refs[k];
      return { bank: this.bank, refs };
    },

    // Возврат в начало мастера: корзина очищена, «9 ОГЭ» снова по умолчанию.
    reset() {
      this.selected = []; this.error = '';
      this.chooseClass(PICKER_CLASSES.find(c => c.bank === 'oge'));
    },
    async confirmAdd() {
      if (!this.selected.length) return;
      // Контракт onAdd прежний: массив {bank, refs} (без служебного key корзины).
      const payload = this.selected.map(({ key, ...rest }) => rest);
      await this.onAdd(payload);
      this.reset();
    },

    renderLatex(expr) {
      if (!expr) return '';
      const s = String(expr);
      const escaped = s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
      // Проза (кириллица) или текст с $...$ — рендерим как текст (иначе KaTeX в
      // math-режиме съедает пробелы и не переносит строку); формулы дорисует auto-render.
      if (s.includes('$') || /[а-яё]/i.test(s)) { this.typeset(); return escaped; }
      // Обращение к katexReady делает вывод реактивным, когда KaTeX догрузится.
      const ready = this.katexReady;
      if (ready && window.katex) {
        try { return window.katex.renderToString(s, { throwOnError: false, output: 'html' }); } catch (e) {}
      }
      return escaped;
    },

    // Прогон KaTeX auto-render по DOM picker'а (тексты задач с $...$).
    // Дебаунс + ожидание догрузки deferred-скрипта contrib/auto-render.
    typeset() {
      if (typesetTimer) return;
      const root = this.$root;
      const t0 = Date.now();
      const run = () => {
        typesetTimer = null;
        if (!window.renderMathInElement) {
          if (Date.now() - t0 < 8000) typesetTimer = setTimeout(run, 150);
          return;
        }
        window.renderMathInElement(root, {
          delimiters: [
            { left: '$$', right: '$$', display: true },
            { left: '$', right: '$', display: false },
            { left: '\\(', right: '\\)', display: false },
            { left: '\\[', right: '\\]', display: true },
          ],
          throwOnError: false,
        });
      };
      typesetTimer = setTimeout(run, 60);
    },
  };
}
</script>
