@extends('layouts.pwa')
@section('title', 'Урок — palomatika')

@push('katex')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/contrib/auto-render.min.js"></script>
@endpush

@push('styles')
  .lesson-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 16px; display: flex; flex-direction: column; gap: 10px; }
  .lesson-task { display: flex; gap: 10px; align-items: flex-start; padding: 10px; background: var(--surface2); border-radius: 10px; }
  .lesson-task-num { font-weight: 800; color: var(--accent); width: 24px; flex-shrink: 0; }
  .lesson-task-body { flex: 1; min-width: 0; }
  .lesson-task-expr { font-size: 15px; color: var(--text); margin-bottom: 4px; word-break: break-word; }
  .lesson-task-meta { font-size: 11px; color: var(--muted); }
  .lesson-task-answer { font-family: ui-monospace, monospace; color: var(--green); font-weight: 700; font-size: 13px; }
  .picker-row { display: flex; gap: 8px; flex-wrap: wrap; }
  .picker-row select, .picker-row input { background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; padding: 8px 10px; font-size: 13px; min-width: 90px; }
  .picker-group-label { font-size: 11px; color: var(--muted); margin: 12px 0 6px; text-transform: uppercase; letter-spacing: 0.04em; }
  .picker-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8px; }
  .picker-card { background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 10px; cursor: pointer; user-select: none; display: flex; flex-direction: column; gap: 6px; transition: border-color .12s, background .12s; }
  .picker-card:hover { border-color: var(--accent-bd); }
  .picker-card.active { background: var(--accent-bg); border-color: var(--accent); }
  .picker-card-expr { font-size: 13px; color: var(--text); word-break: break-word; line-height: 1.3; }
  .picker-card-meta { font-size: 11px; color: var(--muted); display: flex; justify-content: space-between; gap: 8px; }
  .picker-card-answer { font-family: ui-monospace, monospace; color: var(--green); font-weight: 700; }
  .invite-block { background: var(--accent-bg); border: 1px solid var(--accent-bd); border-radius: 10px; padding: 12px; display: flex; flex-direction: column; gap: 8px; }
  .invite-link { font-family: ui-monospace, monospace; font-size: 12px; word-break: break-all; color: var(--text); }
  .btn-row { display: flex; gap: 8px; flex-wrap: wrap; }
  .btn { padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; border: 1px solid var(--border); background: var(--surface2); color: var(--text); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
  .btn-primary { background: var(--accent); border-color: var(--accent); color: white; }
  .btn-danger { background: var(--red-bg); border-color: var(--red-bd); color: var(--red); }
  .btn-icon { padding: 6px 9px; font-size: 12px; }
  .status-row { display: flex; gap: 8px; align-items: center; font-size: 13px; }
  .status-badge-draft { background: var(--yellow-bg); border: 1px solid var(--yellow-bd); color: var(--yellow); padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
  .status-badge-live { background: var(--green-bg); border: 1px solid var(--green-bd); color: var(--green); padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
  .status-badge-ended { background: var(--red-bg); border: 1px solid var(--red-bd); color: var(--red); padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
  .live-grid { width: 100%; border-collapse: collapse; font-size: 12px; }
  .live-grid th, .live-grid td { border: 1px solid var(--border); padding: 8px; text-align: left; vertical-align: top; }
  .live-grid th { background: var(--surface2); color: var(--muted); font-weight: 700; }
  .live-cell-ok { background: var(--green-bg); color: var(--green); }
  .live-cell-bad { background: var(--red-bg); color: var(--red); }
  .live-cell-empty { color: var(--muted); }
@endpush

@section('body')
<div class="page" x-data="lessonPrep({{ $session->id }}, '{{ $session->status }}')" x-init="init()">
  <div class="topbar">
    <a href="{{ route('pwa.teacher.lessons') }}" class="back-btn">‹</a>
    <div class="topbar-title">Урок #{{ $session->id }}</div>
    <span :class="'status-badge-' + status" x-text="statusLabel(status)" class="status-badge-{{ $session->status }}">{{ $session->status }}</span>
  </div>

  {{-- Invite block (only when live + invite_token exists) --}}
  <template x-if="status === 'live' && inviteToken">
    <div class="invite-block">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div style="font-size: 13px; font-weight: 800; color: var(--text);">🔗 Ссылка для приглашения</div>
        <div style="font-size: 11px; color: var(--muted);" x-text="`${participants.length} в уроке`"></div>
      </div>
      <div class="invite-link" x-text="inviteLink"></div>
      <div class="btn-row">
        <button class="btn btn-icon" @click="copyInvite" x-text="copiedAt ? '✓ Скопировано' : '📋 Скопировать'"
                :style="copiedAt ? 'background:var(--green-bg);color:var(--green);border-color:var(--green-bd);' : ''"></button>
        <a class="btn btn-icon" :href="waLink" target="_blank" rel="noopener">📱 WhatsApp</a>
        <a class="btn btn-icon" :href="tgLink" target="_blank" rel="noopener">✈ Telegram</a>
      </div>
      <div style="font-size: 11px; color: var(--muted); line-height: 1.5;">
        Отправь ссылку ученикам. Когда они откроют — увидят кнопку «УРОК» и попадут в эту сессию.
      </div>
    </div>
  </template>

  {{-- Tasks list --}}
  <div class="lesson-card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <div style="font-size: 14px; font-weight: 700;">Задачи (<span x-text="tasks.length"></span>)</div>
      <button class="btn btn-icon" @click="pickerOpen = !pickerOpen" x-show="status !== 'ended'">+ Добавить</button>
    </div>

    <template x-for="task in tasks" :key="task.id">
      <div class="lesson-task">
        <div class="lesson-task-num" x-text="task.position + ')'"></div>
        <div class="lesson-task-body">
          <div class="lesson-task-expr" x-html="renderLatex(task.task_payload.expression)"></div>
          <div class="lesson-task-meta">
            <span x-text="task.bank"></span>
            · Ответ: <span class="lesson-task-answer" x-text="task.correct_answer"></span>
          </div>
        </div>
        <button class="btn btn-icon btn-danger" x-show="status === 'draft'" @click="removeTask(task.id)">×</button>
      </div>
    </template>

    <div x-show="tasks.length === 0" style="color: var(--muted); font-size: 13px; padding: 12px; text-align: center;">
      Пока ни одной задачи. Жми «Добавить» — выбери из банка alg-skill 7 класса.
    </div>
  </div>

  {{-- Task picker (унифицированный каскад для всех банков) --}}
  <div class="lesson-card" x-show="pickerOpen" x-cloak>
    <div style="font-size: 14px; font-weight: 700;">Выбор задачи из банка</div>

    <div>
      <div style="font-size: 12px; color: var(--muted); margin-bottom: 6px;">Банк</div>
      <div class="picker-row">
        <template x-for="b in BANKS" :key="b.id">
          <button class="btn btn-icon" :class="picker.bank === b.id ? 'btn-primary' : ''" @click="selectBank(b.id)" x-text="b.label"></button>
        </template>
      </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 8px;">
      {{-- Класс (для банков с несколькими классами) --}}
      <div class="picker-row" x-show="(picker.options.grades || []).length > 1">
        <select x-model.number="picker.refs.grade" @change="onGradeChange()">
          <option value="">— класс —</option>
          <template x-for="g in (picker.options.grades || [])" :key="g">
            <option :value="g" x-text="`${g} класс`"></option>
          </template>
        </select>
      </div>

      {{-- alg-skill: выбор навыка --}}
      <template x-if="picker.bank === 'alg-skill'">
        <div class="picker-row" x-show="picker.options.skills">
          <select x-model="picker.refs.skill_slug" @change="onSkillChange()" style="flex:1;min-width:240px;">
            <option value="">— навык —</option>
            <template x-for="s in (picker.options.skills || [])" :key="s.slug">
              <option :value="s.slug" x-text="`${s.id}. ${s.title}`"></option>
            </template>
          </select>
        </div>
      </template>

      {{-- остальные банки: выбор темы --}}
      <template x-if="picker.bank !== 'alg-skill'">
        <div class="picker-row" x-show="picker.options.topics">
          <select x-model="picker.refs.topic_id" @change="onTopicChange()" style="flex:1;min-width:240px;">
            <option value="">— тема —</option>
            <template x-for="t in (picker.options.topics || [])" :key="t.id">
              <option :value="t.id" x-text="`${t.id}. ${t.title}`"></option>
            </template>
          </select>
        </div>
      </template>

      <div x-show="picker.loading" style="font-size: 12px; color: var(--muted);">загружаем…</div>

      {{-- Карточки задач (мультивыбор по клику) --}}
      <template x-for="group in taskGroups" :key="group.key">
        <div>
          <div class="picker-group-label" x-text="group.label"></div>
          <div class="picker-cards">
            <template x-for="t in group.tasks" :key="t.uid">
              <div class="picker-card"
                   :class="isCardSelected(t) ? 'active' : ''"
                   @click="toggleCard(t)">
                <div class="picker-card-expr" x-html="renderLatex(t.expression) || '(без формулы)'"></div>
                <div class="picker-card-meta">
                  <span x-text="`#${t.id}`"></span>
                  <span class="picker-card-answer" x-show="t.answer" x-text="t.answer"></span>
                </div>
              </div>
            </template>
          </div>
        </div>
      </template>
    </div>

    <div class="btn-row">
      <button class="btn btn-primary" @click="addSelectedTasks" :disabled="!selectedCount || picker.loading">
        <span x-text="selectedCount ? `Добавить (${selectedCount})` : 'Добавить'"></span>
      </button>
      <button class="btn" @click="pickerOpen = false">Отмена</button>
    </div>
    <div x-show="picker.error" style="color: var(--red); font-size: 12px;" x-text="picker.error"></div>
  </div>

  {{-- Action buttons --}}
  <div class="btn-row">
    <button class="btn btn-primary" x-show="status === 'draft'" @click="startLesson" :disabled="tasks.length === 0">▶ Запустить</button>
    <button class="btn btn-danger" x-show="status === 'live'" @click="endLesson">■ Завершить</button>
  </div>

  {{-- Live grid + summary --}}
  <div class="lesson-card" x-show="status === 'live' && tasks.length">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <div style="font-size: 14px; font-weight: 700;">Ответы</div>
      <div style="font-size: 11px; color: var(--muted);">обновляется каждые 4 сек</div>
    </div>

    <div style="overflow-x: auto;">
      <table class="live-grid">
        <thead>
          <tr>
            <th>Ученик</th>
            <template x-for="t in tasks" :key="t.id">
              <th>
                <div style="font-weight: 800;" x-text="t.position + ')'"></div>
                <div style="font-size: 11px; color: var(--text);" x-html="renderLatex(String(t.task_payload.expression || '').slice(0,40))"></div>
                <div style="color: var(--green); font-family: monospace;" x-text="t.correct_answer"></div>
              </th>
            </template>
          </tr>
        </thead>
        <tbody>
          <template x-for="p in participants" :key="p.id">
            <tr>
              <td x-text="p.name || ('#' + p.id)"></td>
              <template x-for="t in tasks" :key="t.id">
                <td :class="cellClass(p.id, t.id)" x-text="cellLabel(p.id, t.id)"></td>
              </template>
            </tr>
          </template>
          {{-- Summary row: % правильных по задаче --}}
          <tr style="background: var(--surface2);">
            <td style="font-weight: 700; color: var(--muted);">% верно</td>
            <template x-for="t in tasks" :key="'sum-' + t.id">
              <td :style="`color: ${taskCorrectPct(t.id) >= 70 ? 'var(--green)' : (taskCorrectPct(t.id) >= 40 ? 'var(--yellow)' : 'var(--red)')}; font-weight: 700;`"
                  x-text="taskAnsweredCount(t.id) ? `${taskCorrectPct(t.id)}% (${taskCorrectCount(t.id)}/${taskAnsweredCount(t.id)})` : '—'"></td>
            </template>
          </tr>
        </tbody>
      </table>
    </div>

    {{-- Кто не ответил вообще ни на одну задачу --}}
    <template x-if="silentStudents.length">
      <div style="font-size: 12px; color: var(--muted); padding-top: 8px; border-top: 1px solid var(--border);">
        Не отвечают: <span style="color: var(--red); font-weight: 700;" x-text="silentStudents.map(p => p.name || '#'+p.id).join(', ')"></span>
      </div>
    </template>
  </div>
</div>

<script>
  const BANKS = [
    { id: 'oge',       label: 'ОГЭ' },
    { id: 'ege',       label: 'ЕГЭ' },
    { id: 'vpr',       label: 'ВПР' },
    { id: 'alg-topic', label: 'Алгебра (темы)' },
    { id: 'alg-skill', label: 'Алгебра (навыки)' },
  ];

  function emptyRefs() {
    return { grade: '', topic_id: '', zadanie_number: '', task_id: '', skill_slug: '', level_id: '' };
  }

  function lessonPrep(sessionId, initialStatus) {
    return {
      BANKS,
      sessionId,
      status: initialStatus,
      inviteToken: null,
      tasks: [],
      participants: [],
      grid: {},
      pickerOpen: false,
      picker: {
        bank: 'oge',
        refs: emptyRefs(),
        options: { grades: [], topics: [], skills: [], tasks: [] },
        loading: false,
        error: '',
      },
      selectedTasks: [],
      pollTimer: null,
      copiedAt: null,
      katexReady: false,

      async init() {
        await this.refreshState();
        this.startPollingIfLive();
        await this.selectBank(this.picker.bank);
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

      get inviteLink() {
        return this.inviteToken ? `https://palomatika.ru/lesson/join/${this.inviteToken}` : '';
      },

      get waLink() {
        return `https://wa.me/?text=${encodeURIComponent('Заходи на урок: ' + this.inviteLink)}`;
      },

      get tgLink() {
        return `https://t.me/share/url?url=${encodeURIComponent(this.inviteLink)}&text=${encodeURIComponent('Заходи на урок')}`;
      },

      statusLabel(s) {
        return { draft: 'черновик', live: 'идёт', ended: 'завершён' }[s] || s;
      },

      async fetchPickerOptions() {
        this.picker.loading = true;
        try {
          const params = new URLSearchParams({ bank: this.picker.bank });
          for (const [k, v] of Object.entries(this.picker.refs)) {
            if (v !== '' && v !== null && v !== undefined) params.set(k, v);
          }
          const r = await fetch(`/lessons/picker-options?${params}`, {
            headers: { 'Accept': 'application/json' },
            credentials: 'include',
          });
          if (!r.ok) {
            this.picker.error = 'Не удалось загрузить опции';
            return;
          }
          const d = await r.json();
          this.picker.options = {
            grades: d.grades || [],
            topics: d.topics || null,
            skills: d.skills || null,
            tasks:  d.tasks  || [],
          };
        } catch (e) {
          this.picker.error = String(e);
        } finally {
          this.picker.loading = false;
        }
      },

      async selectBank(bank) {
        this.picker.bank = bank;
        this.picker.refs = emptyRefs();
        this.picker.options = { grades: [], topics: null, skills: null, tasks: [] };
        this.selectedTasks = [];
        this.picker.error = '';
        await this.fetchPickerOptions();
        const grades = this.picker.options.grades || [];
        if (grades.length === 1) {
          this.picker.refs.grade = grades[0];
          await this.onGradeChange();
        }
      },

      async onGradeChange() {
        this.picker.refs.topic_id = '';
        this.picker.refs.skill_slug = '';
        this.selectedTasks = [];
        await this.fetchPickerOptions();
      },

      async onTopicChange() {
        this.selectedTasks = [];
        await this.fetchPickerOptions();
      },

      async onSkillChange() {
        this.selectedTasks = [];
        await this.fetchPickerOptions();
      },

      renderLatex(expr) {
        if (!expr) return '';
        // referencing katexReady makes this Alpine-reactive when KaTeX finishes loading
        const ready = this.katexReady;
        if (ready && window.katex) {
          try {
            return window.katex.renderToString(String(expr), { throwOnError: false, output: 'html' });
          } catch (e) { /* fallthrough */ }
        }
        return this.escapeHtml(expr);
      },

      escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
      },

      isCardSelected(t) {
        return this.selectedTasks.some(s => s.uid === t.uid);
      },

      toggleCard(t) {
        const i = this.selectedTasks.findIndex(s => s.uid === t.uid);
        if (i >= 0) {
          this.selectedTasks.splice(i, 1);
        } else {
          this.selectedTasks.push({ ...t });
        }
      },

      get selectedCount() {
        return this.selectedTasks.length;
      },

      get taskGroups() {
        const out = [];
        const byKey = new Map();
        for (const t of (this.picker.options.tasks || [])) {
          const k = String(t.group_key ?? '');
          if (!byKey.has(k)) {
            byKey.set(k, { key: k, label: t.group_label || '', tasks: [] });
            out.push(byKey.get(k));
          }
          byKey.get(k).tasks.push(t);
        }
        return out;
      },

      taskRefs(t) {
        const refs = { ...this.picker.refs };
        if (t.zadanie_number) refs.zadanie_number = t.zadanie_number;
        if (t.level_id)       refs.level_id       = t.level_id;
        refs.task_id = t.id;
        // strip empty
        for (const k of Object.keys(refs)) {
          if (refs[k] === '' || refs[k] === null || refs[k] === undefined) delete refs[k];
        }
        return refs;
      },

      async addSelectedTasks() {
        if (!this.selectedTasks.length) return;
        this.picker.error = '';
        this.picker.loading = true;
        let added = 0;
        try {
          for (const t of this.selectedTasks) {
            const refs = this.taskRefs(t);
            const r = await fetch(`/lessons/${this.sessionId}/tasks`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
              credentials: 'include',
              body: JSON.stringify({ bank: this.picker.bank, refs }),
            });
            if (!r.ok) {
              const err = await r.json().catch(() => ({}));
              this.picker.error = `Задача #${t.id}: ${err.error || r.statusText}`;
              break;
            }
            added++;
          }
        } catch (e) {
          this.picker.error = String(e);
        } finally {
          this.picker.loading = false;
        }
        if (added > 0) {
          this.selectedTasks = [];
          await this.refreshState();
          if (!this.picker.error) {
            this.pickerOpen = false;
          }
        }
      },

      async refreshState() {
        const r = await fetch(`/lessons/${this.sessionId}/state`, { headers: { 'Accept': 'application/json' }, credentials: 'include' });
        if (!r.ok) return;
        const d = await r.json();
        this.status = d.session.status;
        this.inviteToken = d.session.invite_token;
        this.tasks = d.tasks;
        this.participants = d.participants;
        this.grid = d.grid || {};
      },

      startPollingIfLive() {
        if (this.pollTimer) clearInterval(this.pollTimer);
        if (this.status === 'live') {
          this.pollTimer = setInterval(() => {
            if (document.hidden) return;
            this.refreshState();
          }, 4000);
        }
      },

      async removeTask(taskId) {
        if (!confirm('Удалить задачу?')) return;
        await fetch(`/lessons/${this.sessionId}/tasks/${taskId}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
          credentials: 'include',
        });
        await this.refreshState();
      },

      async startLesson() {
        const r = await fetch(`/lessons/${this.sessionId}/start`, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
          credentials: 'include',
        });
        if (!r.ok) { alert('Не удалось запустить'); return; }
        await this.refreshState();
        this.startPollingIfLive();
      },

      async endLesson() {
        if (!confirm('Завершить урок?')) return;
        await fetch(`/lessons/${this.sessionId}/end`, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
          credentials: 'include',
        });
        await this.refreshState();
        if (this.pollTimer) clearInterval(this.pollTimer);
      },

      copyInvite() {
        navigator.clipboard.writeText(this.inviteLink).then(() => {
          this.copiedAt = Date.now();
          setTimeout(() => { if (Date.now() - this.copiedAt >= 1900) this.copiedAt = null; }, 2000);
        });
      },

      cellLabel(studentId, taskId) {
        const a = this.grid[studentId]?.[taskId];
        if (!a) return '—';
        const mark = a.is_correct === true ? '✓ ' : (a.is_correct === false ? '✗ ' : '');
        return mark + a.answer;
      },

      cellClass(studentId, taskId) {
        const a = this.grid[studentId]?.[taskId];
        if (!a) return 'live-cell-empty';
        return a.is_correct ? 'live-cell-ok' : 'live-cell-bad';
      },

      // Summary helpers
      taskAnsweredCount(taskId) {
        let n = 0;
        for (const p of this.participants) {
          if (this.grid[p.id]?.[taskId]) n++;
        }
        return n;
      },

      taskCorrectCount(taskId) {
        let n = 0;
        for (const p of this.participants) {
          if (this.grid[p.id]?.[taskId]?.is_correct === true) n++;
        }
        return n;
      },

      taskCorrectPct(taskId) {
        const a = this.taskAnsweredCount(taskId);
        return a === 0 ? 0 : Math.round((this.taskCorrectCount(taskId) / a) * 100);
      },

      get silentStudents() {
        return this.participants.filter(p => {
          const myRow = this.grid[p.id];
          return !myRow || Object.keys(myRow).length === 0;
        });
      },
    };
  }
</script>
@endsection
