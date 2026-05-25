@extends('layouts.pwa')
@section('title', 'Урок — palomatika')

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
      <div style="font-size: 12px; font-weight: 700; color: var(--text);">Ссылка для приглашения</div>
      <div class="invite-link" x-text="inviteLink"></div>
      <div class="btn-row">
        <button class="btn btn-icon" @click="copyInvite">📋 Скопировать</button>
        <a class="btn btn-icon" :href="`https://wa.me/?text=${encodeURIComponent('Заходи на урок: ' + inviteLink)}`" target="_blank">WhatsApp</a>
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
          <div class="lesson-task-expr" x-text="task.task_payload.expression"></div>
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

  {{-- Task picker (alg-skill 7 only in v1; raw-form для других банков) --}}
  <div class="lesson-card" x-show="pickerOpen" x-cloak>
    <div style="font-size: 14px; font-weight: 700;">Выбор задачи из банка</div>

    <div>
      <div style="font-size: 12px; color: var(--muted); margin-bottom: 6px;">Банк</div>
      <div class="picker-row">
        <template x-for="b in ['alg-skill','oge','ege','vpr','alg-topic']" :key="b">
          <button class="btn btn-icon" :class="picker.bank === b ? 'btn-primary' : ''" @click="picker.bank = b; pickerInit()" x-text="b"></button>
        </template>
      </div>
    </div>

    {{-- alg-skill picker (cascading): skill → level → task --}}
    <template x-if="picker.bank === 'alg-skill'">
      <div style="display: flex; flex-direction: column; gap: 8px;">
        <div class="picker-row">
          <select x-model="picker.refs.grade">
            <option value="7">7 класс</option>
          </select>
          <select x-model="picker.refs.skill_slug" @change="loadSkillTasks()">
            <option value="">— навык —</option>
            <template x-for="s in availableSkills" :key="s.slug">
              <option :value="s.slug" x-text="`${s.id}. ${s.title}`"></option>
            </template>
          </select>
        </div>
        <div class="picker-row" x-show="picker.refs.skill_slug">
          <select x-model="picker.refs.level_id" @change="picker.refs.task_id = ''">
            <option value="simple">Простой</option>
            <option value="medium">Средний</option>
            <option value="high">Высокий</option>
          </select>
          <select x-model="picker.refs.task_id">
            <option value="">— задача —</option>
            <template x-for="t in availableTasks" :key="t.id">
              <option :value="t.id" x-text="`#${t.id}: ${t.expression.slice(0,40)}`"></option>
            </template>
          </select>
        </div>
      </div>
    </template>

    {{-- raw refs form for other banks (oge/ege/vpr/alg-topic) --}}
    <template x-if="picker.bank !== 'alg-skill'">
      <div class="picker-row">
        <template x-if="picker.bank === 'vpr' || picker.bank === 'alg-topic'">
          <input placeholder="grade" type="number" x-model="picker.refs.grade">
        </template>
        <input placeholder="topic_id (e.g. 06)" x-model="picker.refs.topic_id">
        <input placeholder="zadanie_number" type="number" x-model="picker.refs.zadanie_number">
        <input placeholder="task_id" type="number" x-model="picker.refs.task_id">
      </div>
    </template>

    <div class="btn-row">
      <button class="btn btn-primary" @click="addTask">Добавить</button>
      <button class="btn" @click="pickerOpen = false">Отмена</button>
    </div>
    <div x-show="picker.error" style="color: var(--red); font-size: 12px;" x-text="picker.error"></div>
  </div>

  {{-- Action buttons --}}
  <div class="btn-row">
    <button class="btn btn-primary" x-show="status === 'draft'" @click="startLesson" :disabled="tasks.length === 0">▶ Запустить</button>
    <button class="btn btn-danger" x-show="status === 'live'" @click="endLesson">■ Завершить</button>
  </div>

  {{-- Live grid (Task 7 — basic version здесь) --}}
  <div class="lesson-card" x-show="status === 'live' && tasks.length">
    <div style="font-size: 14px; font-weight: 700;">Ответы (обновляется каждые 4 сек)</div>
    <div style="overflow-x: auto;">
      <table class="live-grid">
        <thead>
          <tr>
            <th>Ученик</th>
            <template x-for="t in tasks" :key="t.id">
              <th>
                <div style="font-weight: 800;" x-text="t.position + ')'"></div>
                <div style="font-family: ui-monospace, monospace; font-size: 11px; color: var(--text);" x-text="t.task_payload.expression.slice(0,30)"></div>
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
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  // Preloaded alg-skill bundle (grade 7) for picker
  const ALG_SKILLS_7 = @json($algSkillsBundle7);
  async function fetchAlgSkillsBundle(grade) {
    return grade === 7 ? ALG_SKILLS_7 : { skills: [] };
  }

  function lessonPrep(sessionId, initialStatus) {
    return {
      sessionId,
      status: initialStatus,
      inviteToken: null,
      tasks: [],
      participants: [],
      grid: {},
      pickerOpen: false,
      picker: { bank: 'alg-skill', refs: { grade: 7, skill_slug: '', level_id: 'simple', task_id: '' }, error: '' },
      availableSkills: [],
      availableTasks: [],
      pollTimer: null,

      async init() {
        await this.refreshState();
        this.startPollingIfLive();
      },

      get inviteLink() {
        return this.inviteToken ? `https://palomatika.ru/lesson/join/${this.inviteToken}` : '';
      },

      statusLabel(s) {
        return { draft: 'черновик', live: 'идёт', ended: 'завершён' }[s] || s;
      },

      async pickerInit() {
        this.picker.refs = { grade: 7, skill_slug: '', level_id: 'simple', task_id: '' };
        this.picker.error = '';
        if (this.picker.bank === 'alg-skill') {
          const bundle = await fetchAlgSkillsBundle(7);
          this.availableSkills = bundle.skills || [];
        }
      },

      async loadSkillTasks() {
        const bundle = await fetchAlgSkillsBundle(7);
        const skill = (bundle.skills || []).find(s => s.slug === this.picker.refs.skill_slug);
        if (!skill) { this.availableTasks = []; return; }
        const level = (skill.levels || []).find(l => l.id === this.picker.refs.level_id);
        this.availableTasks = level?.tasks || [];
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

      async addTask() {
        this.picker.error = '';
        try {
          const refs = {};
          for (const k of Object.keys(this.picker.refs)) {
            const v = this.picker.refs[k];
            if (v === '' || v === null) continue;
            refs[k] = isNaN(v) || ['skill_slug', 'level_id', 'topic_id'].includes(k) ? v : Number(v);
          }
          const r = await fetch(`/lessons/${this.sessionId}/tasks`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ bank: this.picker.bank, refs }),
          });
          if (!r.ok) {
            const err = await r.json();
            this.picker.error = err.error || JSON.stringify(err);
            return;
          }
          this.pickerOpen = false;
          await this.refreshState();
        } catch (e) {
          this.picker.error = String(e);
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
        navigator.clipboard.writeText(this.inviteLink).then(() => alert('Скопировано'));
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
    };
  }
</script>
@endsection
