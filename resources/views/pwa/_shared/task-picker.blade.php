{{-- Общий picker задач «как база заданий». Подключение:
     @include('pwa._shared.task-picker') внутри x-data="taskPicker({ onAdd, existingUids })"
     Контракт: onAdd(payload: Array<{bank, refs}>), existingUids: () => string[] --}}
<div class="task-picker">
  {{-- Класс — всегда видимые пилюли, «9 ОГЭ» выбран по умолчанию --}}
  {{-- На экране темы класс не нужен — назад к карте ведёт «‹ Темы». --}}
  <div class="tp-pills tp-classes" x-show="screen === 'map' || !isTopicBank">
    <template x-for="c in PICKER_CLASSES" :key="c.id">
      <button type="button" class="topic-pill" :class="{ active: cls && cls.id === c.id }"
              @click="chooseClass(c)" x-text="c.label"></button>
    </template>
  </div>

  {{-- У 5–8 классов два банка: навыки алгебры и задания ВПР. --}}
  <div class="tp-pills" x-show="cls && cls.banks && (screen === 'map' || !isTopicBank)" x-cloak style="margin-top:6px">
    <template x-for="b in (cls?.banks || [])" :key="b.bank">
      <button type="button" class="topic-pill" :class="{ active: bank === b.bank }"
              @click="chooseBank(b.bank)" x-text="b.label"></button>
    </template>
  </div>

  <div x-show="loading" class="picker-group-label">загружаем…</div>
  <div x-show="error" style="color:var(--red);font-size:12px" x-text="error"></div>

  {{-- ====== Банки по темам (ОГЭ / ВПР / ЕГЭ / Скиллы) — два экрана ======
       1) карта: все темы экзамена списком с названиями, по частям, и сколько
          из каждой уже в уроке; 2) тема: задания вкладками, подтипы и
          подуровни — пилюлями, задачи карточками. Вложенных спойлеров нет:
          на телефоне три уровня раскрытия съедали экран до первой задачи. --}}
  <div x-show="isTopicBank">
    {{-- Экран 1. Карта тем --}}
    <div x-show="screen === 'map'" class="tp-map">
      <template x-for="sec in mapSections" :key="'sec-' + (sec.id || 'all')">
        <div>
          <div class="tp-map-sec" x-show="sec.title" x-text="sec.title"></div>
          <template x-for="t in sec.topics" :key="'tile-' + (sec.id || '') + '-' + t.id">
            <button type="button" class="tp-tile" @click="chooseTopic(t.id, sec.id)">
              <span class="tp-tile-no" x-text="topicNumber(t.id)"></span>
              <span class="tp-tile-name" x-text="t.title || ('Тема ' + topicNumber(t.id))"></span>
              <span class="tp-added" x-show="topicExistingCount(t.id)" x-text="'в уроке ' + topicExistingCount(t.id)"></span>
              <span class="tp-tile-chev">›</span>
            </button>
          </template>
        </div>
      </template>
      <div x-show="!loading && !mapSections.some(s => s.topics.length)" class="picker-group-label">Нет тем</div>
    </div>

    {{-- Экран 2. Задачи темы --}}
    <div x-show="screen === 'topic'" x-cloak>
      <div class="tp-topic-head">
        <button type="button" class="tp-back" @click="backToMap()">‹ Темы</button>
        <span class="tp-topic-title" x-text="topicNumber(topicId) + ' · ' + currentTopicTitle"></span>
      </div>

      {{-- Задания темы — вкладки; точка: из задания уже есть задачи в уроке --}}
      <div class="tp-tabs" x-show="groups.length > 1">
        <template x-for="g in groups" :key="'tab-' + g.key">
          <button type="button" class="topic-pill tp-tab" :class="{ active: currentGroup && currentGroup.key === g.key, 'has-added': existingCount(g) }"
                  @click="chooseGroup(g.key)" x-text="g.label" :title="g.label"></button>
        </template>
      </div>

      <template x-if="currentGroup">
        <div>
          <div class="tp-group-title">
            <span x-text="currentGroup.label"></span>
            <span class="tp-added" x-show="existingCount(currentGroup)" x-text="'в уроке ' + existingCount(currentGroup)"></span>
          </div>

          {{-- Подтипы задания --}}
          <div class="tp-pills tp-subpills" x-show="currentGroup.subs.length">
            <button type="button" class="topic-pill" :class="{ active: !activeSub }" @click="chooseSub(null)">Все</button>
            <template x-for="sub in currentGroup.subs" :key="'sub-' + sub.key">
              <button type="button" class="topic-pill" :class="{ active: activeSub === sub.key }" @click="chooseSub(sub.key)">
                <span x-text="sub.label"></span> <span class="tp-count" x-text="sub.tasks.length"></span>
              </button>
            </template>
          </div>

          {{-- Подуровни по двадцать: задачи идут от простых к сложным --}}
          <div class="tp-pills tp-subpills" x-show="currentChunks.length">
            <template x-for="(chunk, ci) in currentChunks" :key="'chunk-' + chunk.key">
              <button type="button" class="topic-pill" :class="{ active: activeChunk === ci }" @click="chooseChunk(ci)"
                      x-text="chunk.short"></button>
            </template>
          </div>

          <div class="tp-list-head">
            <span class="tp-count" x-text="visibleTasks.length + ' ' + taskWord(visibleTasks.length)"></span>
            <button type="button" class="tp-block-btn" @click="toggleGroup({ tasks: visibleTasks })"
                    x-text="groupAllSelected({ tasks: visibleTasks }) ? 'Снять все' : 'Выбрать все'"></button>
          </div>
          <div class="task-list">
            <template x-for="t in visibleTasks" :key="t.uid">
              @include('pwa._shared.partials.task-picker-card')
            </template>
          </div>
        </div>
      </template>
      <div x-show="!loading && !groups.length" class="picker-group-label">Нет задач</div>
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

@include('pwa._shared.partials.fipi-condition-js')

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
  .task-picker .topic-pill.has-added { position:relative; }
  .task-picker .topic-pill.has-added::after { content:''; position:absolute; top:4px; right:4px; width:6px; height:6px; border-radius:50%; background:var(--green, #16a34a); }
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
  /* Второй уровень: серии с разными условиями внутри одного задания */
  .task-picker .tp-sub {
    border:1px solid var(--border); border-radius:10px; background:rgba(255,255,255,.02);
  }
  .task-picker .tp-sub summary {
    list-style:none; cursor:pointer; padding:9px 12px;
    display:flex; align-items:center; gap:8px;
  }
  .task-picker .tp-sub summary::-webkit-details-marker { display:none; }
  .task-picker .tp-sub summary::after {
    content:'⌄'; margin-left:auto; color:var(--muted); transition:transform .15s ease;
  }
  .task-picker .tp-sub[open] summary::after { transform:rotate(180deg); }
  .task-picker .tp-sub-label { font-size:13px; line-height:1.3; color:var(--text); min-width:0; }
  .task-picker .tp-sub-body { padding:0 8px 8px; display:flex; flex-direction:column; gap:8px; }
  /* Третий уровень: подуровни по двадцать задач внутри уровня сложности */
  .task-picker .tp-chunk { border:1px solid var(--border); border-radius:8px; background:rgba(255,255,255,.02); }
  .task-picker .tp-chunk summary { list-style:none; cursor:pointer; padding:8px 10px; display:flex; align-items:center; gap:8px; }
  .task-picker .tp-chunk summary::-webkit-details-marker { display:none; }
  .task-picker .tp-chunk summary::after { content:'⌄'; margin-left:auto; color:var(--muted); transition:transform .15s ease; }
  .task-picker .tp-chunk[open] summary::after { transform:rotate(180deg); }
  .task-picker .tp-chunk-label { font-size:12px; color:var(--muted); font-weight:700; }
  .task-picker .tp-chunk-body { padding:0 6px 6px; display:flex; flex-direction:column; gap:8px; }
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
  /* Условие ЕГЭ в разметке ФИПИ — общие правила экранов урока. */
  @include('pwa._shared.partials.fipi-condition-css')
  .task-picker .fipi-condition { font-size:13px; }
  .task-picker .fipi-condition img.fipi-figure { max-height:160px; }
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
  .task-picker .tp-added { margin-left:6px; padding:1px 6px; border-radius:6px; font-size:11px; color:var(--green, #16a34a); background:var(--green-bg, rgba(22,163,74,.12)); white-space:nowrap; }

  .task-picker .tp-classes { flex-wrap:nowrap; overflow-x:auto; scrollbar-width:none; padding-bottom:2px; }
  .task-picker .tp-classes::-webkit-scrollbar { display:none; }
  /* Карта тем */
  .task-picker .tp-map { margin-top:12px; }
  .task-picker .tp-map-sec { font-size:11px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); margin:12px 2px 6px; }
  .task-picker .tp-tile { display:flex; align-items:center; gap:10px; width:100%; text-align:left; margin-bottom:6px;
    background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:9px 12px; cursor:pointer; color:var(--text); }
  .task-picker .tp-tile:active { border-color:var(--purple-bd); }
  .task-picker .tp-tile-no { width:32px; height:32px; border-radius:9px; background:var(--surface2); display:grid; place-items:center; font-family:var(--display); font-size:14px; flex-shrink:0; }
  .task-picker .tp-tile-name { flex:1; min-width:0; font-size:14px; line-height:1.3; }
  .task-picker .tp-tile-chev { color:var(--muted); font-size:18px; }
  /* Экран темы */
  .task-picker .tp-topic-head { display:flex; align-items:center; gap:10px; margin:12px 0 10px; }
  .task-picker .tp-back { flex-shrink:0; border:1px solid var(--border); background:var(--surface); color:var(--text); border-radius:10px; padding:7px 11px; font-size:13px; font-weight:700; cursor:pointer; }
  .task-picker .tp-topic-title { font-family:var(--display); font-size:15px; min-width:0; }
  .task-picker .tp-tabs { display:flex; gap:6px; overflow-x:auto; padding-bottom:4px; margin-bottom:8px; scrollbar-width:none; }
  .task-picker .tp-tabs::-webkit-scrollbar { display:none; }
  .task-picker .tp-tab { max-width:240px; overflow:hidden; text-overflow:ellipsis; font-size:13px; }
  .task-picker .tp-group-title { font-size:14px; font-weight:700; line-height:1.35; margin:2px 2px 8px; display:flex; flex-wrap:wrap; align-items:baseline; gap:6px; }
  .task-picker .tp-subpills { margin-bottom:8px; }
  .task-picker .tp-subpills .topic-pill { font-size:12px; padding:6px 9px; min-width:0; max-width:100%; flex-shrink:1; white-space:normal; text-align:left; }
  .task-picker .tp-list-head { display:flex; align-items:center; justify-content:space-between; margin:4px 2px 0; }
  .task-picker .tp-list-head + .task-list { margin-top:8px; }

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

// Ключ задачи банка для сравнения «уже добавлено». Класс у ОГЭ/ЕГЭ не
// входит: в одних ссылках он есть (11), в других пуст, а задача та же.
window.paloTaskKey = window.paloTaskKey || function (bank, refs) {
  refs = refs || {};
  const grade = ['oge', 'ege', 'ege_b'].includes(bank) ? '' : (refs.grade ?? '');
  return [bank, grade, refs.topic_id ?? '', refs.zadanie_number ?? '', refs.skill_slug ?? '', refs.level_id ?? '', refs.task_id ?? '']
    .map(v => String(v)).join('|');
};

function taskPicker(config) {
  // Не в reactive-состоянии: запись из renderLatex во время рендера не должна
  // триггерить Alpine-эффекты (риск цикла).
  let typesetTimer = null;

  return {
    onAdd: config.onAdd,                 // (Array<{bank, refs}>) => Promise|void
    existingUids: config.existingUids || (() => []), // дедуп с уже добавленными
    cls: null,                           // выбранный элемент PICKER_CLASSES
    refs: { grade: '', topic_id: '', skill_slug: '' },
    // Банки по темам: карта тем (все части сразу) → экран темы
    sections: [], sectionId: null,
    topics: [], topicId: null,
    screen: 'map',                       // map | topic
    mapSections: [],                     // [{ id, title, topics }]
    activeGroup: null, activeSub: null, activeChunk: 0,
    // 7/8: навык → уровень → задачи
    step: 'strips',                      // strips | buckets | tasks
    strips: [], bucketKey: null,
    tasks: [],                           // задачи выбранной темы/навыка
    chunkSize: 20,                       // размер подуровня внутри уровня
    selected: [],                        // ГЛОБАЛЬНАЯ корзина: не сбрасывается при навигации
    loading: false, error: '',
    katexReady: !!window.katex,
    _reqId: 0,

    bankOverride: null,

    get bank() { return this.bankOverride || this.cls?.bank; },
    /** ОГЭ, ВПР, оба уровня ЕГЭ и «Скиллы» ходят одинаково: тема → задания. */
    get isTopicBank() { return ['oge', 'vpr', 'ege', 'ege_b', 'alg-topic', 'skills'].includes(this.bank); },
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
      this.screen = 'map';
      this.mapSections = [];
      const d = await this.fetchOptions();
      if (!d) return;

      if (this.isTopicBank) {
        // У ОГЭ и ЕГЭ темы разложены по частям экзамена, у ВПР и Скиллов —
        // одним списком. Карта показывает все части сразу: тянем темы
        // каждой части параллельно.
        this.sections = d.sections || [];
        if (!this.sections.length) {
          this.mapSections = [{ id: null, title: '', topics: d.topics || [] }];
          return;
        }
        const my = this._reqId;
        this.loading = true;
        const parts = await Promise.all(this.sections.map(async sec => {
          const data = await this.fetchRaw({ section: sec.id });
          return { id: sec.id, title: sec.title, topics: data?.topics || [] };
        }));
        if (my !== this._reqId) return;   // за это время выбрали другой класс
        this.loading = false;
        this.mapSections = parts.filter(p => p.topics.length);
      } else {
        this.strips = d.skills || [];
      }
    },
    async chooseTopic(id, sectionId = null) {
      this.sectionId = sectionId;
      this.topicId = String(id);
      this.refs.topic_id = String(id);
      this.topics = (this.mapSections.find(s => s.id === sectionId) || {}).topics || [];
      this.tasks = [];
      this.activeGroup = null; this.activeSub = null; this.activeChunk = 0;
      this.screen = 'topic';
      this.$root.closest('.picker-overlay')?.scrollTo({ top: 0 });
      const d = await this.fetchOptions();
      if (d) this.tasks = d.tasks || [];
      this.typeset();
    },
    backToMap() {
      this.screen = 'map';
      this.topicId = null; this.refs.topic_id = '';
      this.tasks = [];
      this.$root.closest('.picker-overlay')?.scrollTo({ top: 0 });
    },
    chooseGroup(key) { this.activeGroup = key; this.activeSub = null; this.activeChunk = 0; this.typeset(); },
    chooseSub(key) { this.activeSub = key; this.activeChunk = 0; this.typeset(); },
    chooseChunk(i) { this.activeChunk = i; this.typeset(); },
    get currentGroup() {
      const gs = this.groups;
      return gs.find(g => g.key === this.activeGroup) || gs[0] || null;
    },
    get currentSet() {
      const g = this.currentGroup;
      if (!g) return null;
      return (this.activeSub && g.subs.find(s => s.key === this.activeSub)) || g;
    },
    get currentChunks() { return this.currentSet?.chunks || []; },
    get visibleTasks() {
      const set = this.currentSet;
      if (!set) return [];
      const chunks = set.chunks || [];
      return chunks.length ? (chunks[this.activeChunk] || chunks[0]).tasks : set.tasks;
    },
    topicNumber(id) { const n = Number(id); return Number.isNaN(n) ? String(id ?? '') : String(n); },
    taskWord(n) {
      const m10 = n % 10, m100 = n % 100;
      if (m10 === 1 && m100 !== 11) return 'задача';
      if (m10 >= 2 && m10 <= 4 && (m100 < 10 || m100 >= 20)) return 'задачи';
      return 'задач';
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
    /** Запрос без отмены устаревших — для параллельной подгрузки частей карты. */
    async fetchRaw(extra = {}) {
      try {
        const params = new URLSearchParams({ bank: this.bank });
        for (const [k, v] of Object.entries({ ...this.refs, ...extra }))
          if (v !== '' && v != null) params.set(k, v);
        const r = await fetch(`/lessons/picker-options?${params}`,
          { headers: { Accept: 'application/json' }, credentials: 'include' });
        return r.ok ? await r.json() : null;
      } catch (e) { return null; }
    },
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
      const out = [], seen = new Map(), subSeen = new Map();
      for (const t of this.tasks) {
        const k = String(t.group_key ?? '');
        if (!seen.has(k)) { seen.set(k, { key: k, label: t.group_label || '', tasks: [], subs: [] }); out.push(seen.get(k)); }
        const g = seen.get(k);
        g.tasks.push(t);
        if (t.subtype_key) {
          const sk = k + '|' + t.subtype_key;
          if (!subSeen.has(sk)) { const s = { key: sk, label: t.subtype_label || '', tasks: [] }; subSeen.set(sk, s); g.subs.push(s); }
          subSeen.get(sk).tasks.push(t);
        }
      }
      // Подтипы показываем, только когда по ним разложены все задачи задания
      // и их больше одного — иначе лишний уровень раскрытия ни к чему.
      for (const g of out) {
        const covered = g.subs.reduce((n, s) => n + s.tasks.length, 0);
        if (g.subs.length < 2 || covered !== g.tasks.length) g.subs = [];
        g.chunks = this.chunksOf(g);
        for (const s of g.subs) s.chunks = this.chunksOf(s);
      }
      return out;
    },
    // Подуровень — двадцать задач подряд. Они уже отсортированы от простых к
    // сложным, поэтому «№21–40» это ровно следующая ступень. Короткие списки
    // не режем: спойлер ради пяти карточек только мешает.
    chunksOf(group) {
      const size = this.chunkSize;
      if (group.tasks.length <= size * 2) return [];
      const chunks = [];
      for (let i = 0; i < group.tasks.length; i += size) {
        const tasks = group.tasks.slice(i, i + size);
        chunks.push({
          key: group.key + '#' + (i / size),
          label: 'Подуровень ' + (i / size + 1) + ' · №' + (i + 1) + '–' + (i + tasks.length),
          short: '№' + (i + 1) + '–' + (i + tasks.length),
          tasks,
        });
      }
      return chunks;
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
    // Уже в уроке/домашке: сверяем по банку и ссылке на задачу, а не по uid
    // карточки — снаружи uid нет, и отметка «уже добавлено» не загоралась никогда.
    isExisting(t) {
      const r = this.taskRefs(t);
      return this.existingUids().includes(window.paloTaskKey(r.bank, r.refs));
    },
    // «в уроке N» на плитке темы — сколько задач урока из неё, не открывая.
    topicExistingCount(topicId) {
      const r = this.taskRefs({ id: '' }).refs;
      const prefix = window.paloTaskKey(this.bank, { ...r, topic_id: topicId, zadanie_number: '', task_id: '' }).split('|').slice(0, 3).join('|') + '|';
      return this.existingUids().filter(k => k.startsWith(prefix)).length;
    },
    existingCount(g) { return g.tasks.filter(t => this.isExisting(t)).length; },
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

    // После добавления остаёмся в той же теме: добрать ещё из неё — частый
    // случай, а карта тем в одном тапе («‹ Темы»). Корзина очищается.
    reset() {
      this.selected = []; this.error = '';
    },
    async confirmAdd() {
      if (!this.selected.length) return;
      // Контракт onAdd прежний: массив {bank, refs} (без служебного key корзины).
      const payload = this.selected.map(({ key, ...rest }) => rest);
      await this.onAdd(payload);
      this.reset();
    },

    // Условие ЕГЭ в разметке ФИПИ (см. fipi-condition-js); формулы $…$
    // внутри дорисует auto-render.
    fipiHtml(html) {
      if (!html) return '';
      this.typeset();
      return window.paloFipiHtml ? window.paloFipiHtml(html) : String(html);
    },

    renderLatex(expr) {
      if (!expr) return '';
      const s = String(expr);
      const escaped = window.paloEscapeKeepingFipiImages
        ? window.paloEscapeKeepingFipiImages(s)
        : s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
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
