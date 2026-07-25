@extends('layouts.pwa')

@section('title', 'Профиль ученика')

@push('styles')
  .page { min-height: 100vh; background: var(--bg); color: var(--text); padding: 16px; }
  .topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom: 12px; }
  .back { color: var(--text); text-decoration:none; font-size: 18px; padding:6px 8px; border:1px solid var(--border); border-radius:10px; }
  .card { background: var(--surface); border:1px solid var(--border); border-radius:14px; padding:12px; margin-bottom:12px; }
  /* Сворачиваемые блоки — на нативном <details>, чтобы состояние не зависело от JS */
  .fold > .fold-head { font-weight:700; cursor:pointer; list-style:none; display:flex; align-items:center; gap:8px; user-select:none; }
  .fold > .fold-head::-webkit-details-marker { display:none; }
  .fold > .fold-head::before { content:'▾'; color: var(--muted); font-size:12px; transition: transform .15s; }
  .fold:not([open]) > .fold-head::before { transform: rotate(-90deg); }
  .fold[open] > .fold-head { margin-bottom:8px; }
  .fold-count { font-weight:600; font-size:12px; color: var(--muted); }
  .name { font-size:18px; font-weight:800; }
  .muted { color: var(--muted); font-size:12px; }
  .stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; margin-top:10px; }
  .stat { background: var(--surface2); border:1px solid var(--border); border-radius:10px; padding:10px; text-align:center; }
  .stat b { font-size:18px; display:block; color:#fff; }
  .row { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:8px 0; border-bottom:1px dashed var(--border); }
  .row:last-child { border-bottom:none; }
  .bad { color:#fca5a5; font-weight:700; }
  .good { color:#86efac; font-weight:700; }
  .attempt-card {
    display: flex; align-items: center; justify-content: space-between;
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 10px; padding: 10px 12px;
    text-decoration: none; color: inherit;
    margin-bottom: 6px;
  }
  .attempt-left { flex: 1; min-width: 0; }
  .attempt-label { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
  .attempt-meta { font-size: 11px; font-weight: 600; color: var(--muted); }
  .attempt-score {
    font-weight: 800; font-size: 18px; line-height: 1;
    margin-left: 12px; white-space: nowrap;
  }
  .attempt-score small { font-size: 13px; color: var(--muted); }
  .score-good { color: #86efac; }
  .score-mid { color: #fde68a; }
  .score-bad { color: #fca5a5; }
  .note-item { padding:10px 0; border-bottom:1px dashed var(--border); }
  .note-item:last-child { border-bottom:none; }
  .note-head { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px; }
  .note-badge { font-size:11px; font-weight:700; padding:2px 8px; border-radius:999px; background:var(--surface2); border:1px solid var(--border); white-space:nowrap; }
  .note-tag { font-size:11px; font-weight:600; color:var(--muted); background:var(--surface2); border:1px solid var(--border); border-radius:6px; padding:2px 6px; }
  .note-date { font-size:11px; color:var(--muted); margin-left:auto; }
  .note-body { font-size:13px; color:var(--text); line-height:1.4; white-space:pre-wrap; }
  .note-filters { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; }
  .note-pill {
    font-size:12px; font-weight:700; padding:5px 12px; border-radius:999px;
    background:var(--surface2); border:1px solid var(--border); color:var(--muted);
    cursor:pointer; line-height:1;
  }
  .note-pill.active { background:var(--accent-bg); border-color:var(--accent-bd); color:var(--text); }
  .note-actions { display:flex; gap:8px; margin-top:6px; }
  .note-btn {
    font-size:12px; font-weight:700; padding:4px 10px; border-radius:8px;
    background:var(--surface2); border:1px solid var(--border); color:var(--text); cursor:pointer;
  }
  .note-btn-danger { color:#fca5a5; }
  .note-btn-primary { background:var(--accent-bg); border-color:var(--accent-bd); }
  .note-edit { display:flex; flex-direction:column; gap:8px; }
  .note-edit-body {
    width:100%; font-family:inherit; font-size:13px; line-height:1.4; resize:vertical;
    background:var(--surface2); border:1px solid var(--border); border-radius:8px;
    color:var(--text); padding:8px;
  }
  .note-edit-kind {
    align-self:flex-start; font-family:inherit; font-size:12px; font-weight:700;
    background:var(--surface2); border:1px solid var(--border); border-radius:8px;
    color:var(--text); padding:6px 8px;
  }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="/students" class="back">←</a>
    <div style="font-weight:800;">Профиль ученика</div>
    <div style="width:34px;"></div>
  </div>

  @php
    $alias = $teacherRelation?->student_alias;
    $displayName = $alias ?: $student->name;
  @endphp
  <div class="card">
    <div class="name">
      {{ $displayName }}
      @if($student->grade_num)
        <span style="font-size:10px;font-weight:800;color:var(--muted);
                     background:var(--surface);border:1px solid var(--border);
                     padding:2px 6px;border-radius:6px;margin-left:6px;">
          {{ $student->grade_num }}
        </span>
      @endif
    </div>
    @if($alias)
      <div class="muted">{{ $student->name }} · ID: {{ $student->id }}</div>
    @else
      <div class="muted">ID: {{ $student->id }} · {{ $student->email ?: 'без email' }}</div>
    @endif
    <div class="stats">
      <div class="stat"><b>{{ $attempts->count() }}</b><span class="muted">попыток</span></div>
      <div class="stat"><b>{{ $scoredTotal }}</b><span class="muted">проверено</span></div>
      <div class="stat"><b>{{ $accuracy === null ? '—' : ($accuracy.'%') }}</b><span class="muted">точность</span></div>
    </div>
  </div>

  <details class="card fold" open>
    <summary class="fold-head">История вариантов <span class="fold-count">{{ count($historyList) }}</span></summary>
    @forelse($historyList as $h)
      @php
        $pct = $h['total'] > 0 ? round(($h['correct'] / $h['total']) * 100) : 0;
        $scoreClass = $pct >= 70 ? 'score-good' : ($pct >= 40 ? 'score-mid' : 'score-bad');
        $timeStr = $h['time'] !== null ? (floor($h['time'] / 60) . ':' . str_pad($h['time'] % 60, 2, '0', STR_PAD_LEFT)) : null;
      @endphp
      <a href="/students/{{ $student->id }}/attempt/{{ $h['id'] }}" class="attempt-card">
        <div class="attempt-left">
          <div class="attempt-label">{{ $h['label'] }}</div>
          <div class="attempt-meta">
            {{ $h['date']?->format('d.m.Y H:i') }}
            @if($timeStr) &middot; {{ $timeStr }} @endif
            @if($h['hash']) &middot; {{ $h['hash'] }} @endif
          </div>
        </div>
        <div class="attempt-score {{ $scoreClass }}">
          {{ $h['correct'] }}<small>/{{ $h['total'] }}</small>
        </div>
      </a>
    @empty
      <div class="muted">Пока нет завершённых попыток.</div>
    @endforelse
  </details>

  <details class="card fold" open>
    <summary class="fold-head">Темы/задания: точность <span class="fold-count">{{ count($topicStats) }}</span></summary>
    <div class="muted" style="margin-bottom:8px;">Формат X/Y: верно из числа попыток, где это задание реально встречалось у ученика.</div>
    @forelse($topicStats as $ts)
      @php $p = $ts['total'] > 0 ? (int) round(($ts['correct']/$ts['total'])*100) : 0; @endphp
      <div class="row">
        <div>Задание {{ $ts['task_number'] }}</div>
        <div class="{{ $p < 60 ? 'bad' : 'good' }}">{{ $p }}% ({{ $ts['correct'] }}/{{ $ts['total'] }})</div>
      </div>
    @empty
      <div class="muted">Пока нет данных по оценке.</div>
    @endforelse
  </details>

  <div class="card" x-data="notesSection(@js($notes->map(fn($n) => [
        'id'         => $n->id,
        'body'       => $n->body,
        'kind'       => $n->kind,
        'topic_tag'  => $n->topic_tag,
        'created_at' => $n->created_at?->format('d.m.Y'),
      ])))">
    <div style="font-weight:700;margin-bottom:8px;">Наблюдения</div>

    <div class="note-filters">
      <button type="button" class="note-pill" :class="{ active: filterKind === '' }" @click="filterKind = ''">Все</button>
      <button type="button" class="note-pill" :class="{ active: filterKind === 'weakness' }" @click="filterKind = 'weakness'" title="западает">🔴</button>
      <button type="button" class="note-pill" :class="{ active: filterKind === 'strength' }" @click="filterKind = 'strength'" title="сильная">🟢</button>
      <button type="button" class="note-pill" :class="{ active: filterKind === 'todo' }" @click="filterKind = 'todo'" title="todo">📌</button>
      <button type="button" class="note-pill" :class="{ active: filterKind === 'general' }" @click="filterKind = 'general'" title="общее">💬</button>
    </div>

    <template x-for="note in filtered" :key="note.id">
      <div class="note-item">
        <template x-if="editingId !== note.id">
          <div>
            <div class="note-head">
              <span class="note-badge" x-text="badge(note.kind)"></span>
              <template x-if="note.topic_tag">
                <span class="note-tag" x-text="note.topic_tag"></span>
              </template>
              <span class="note-date" x-text="note.created_at"></span>
            </div>
            <div class="note-body" x-text="note.body"></div>
            <div class="note-actions">
              <button type="button" class="note-btn" @click="startEdit(note)">править</button>
              <button type="button" class="note-btn note-btn-danger" @click="deleteNote(note.id)">удалить</button>
            </div>
          </div>
        </template>
        <template x-if="editingId === note.id">
          <div class="note-edit">
            <textarea class="note-edit-body" rows="3" x-model="editBody"></textarea>
            <select class="note-edit-kind" x-model="editKind">
              <option value="weakness">🔴 западает</option>
              <option value="strength">🟢 сильная</option>
              <option value="todo">📌 todo</option>
              <option value="general">💬 общее</option>
            </select>
            <div class="note-actions">
              <button type="button" class="note-btn note-btn-primary" @click="saveNote(note.id, editBody, editKind)">сохранить</button>
              <button type="button" class="note-btn" @click="cancelEdit()">отмена</button>
            </div>
          </div>
        </template>
      </div>
    </template>

    <template x-if="!filtered.length">
      <div class="muted" x-text="notes.length ? 'Нет записей этого типа.' : 'Записей пока нет.'"></div>
    </template>

    {{-- Fallback без JS: серверный рендер, чтобы записи были видны и без Alpine --}}
    <noscript>
      @php
        $kindMeta = [
          'weakness' => '🔴 западает',
          'strength' => '🟢 сильная',
          'todo'     => '📌 todo',
          'general'  => '💬 общее',
        ];
      @endphp
      @forelse($notes as $note)
        <div class="note-item">
          <div class="note-head">
            <span class="note-badge">{{ $kindMeta[$note->kind] ?? '💬 общее' }}</span>
            @if($note->topic_tag)
              <span class="note-tag">{{ $note->topic_tag }}</span>
            @endif
            <span class="note-date">{{ optional($note->created_at)->format('d.m.Y') }}</span>
          </div>
          <div class="note-body">{{ $note->body }}</div>
        </div>
      @empty
        <div class="muted">Записей пока нет.</div>
      @endforelse
    </noscript>
  </div>

</div>

@push('scripts')
<script>
  function notesSection(initial) {
    const KIND_META = {
      weakness: '🔴 западает',
      strength: '🟢 сильная',
      todo:     '📌 todo',
      general:  '💬 общее',
    };
    const csrf = () => document.querySelector('meta[name=csrf-token]').content;

    return {
      notes: Array.isArray(initial) ? initial : [],
      filterKind: '',
      editingId: null,
      editBody: '',
      editKind: '',

      get filtered() {
        if (!this.filterKind) return this.notes;
        return this.notes.filter(n => n.kind === this.filterKind);
      },

      badge(kind) {
        return KIND_META[kind] || KIND_META.general;
      },

      startEdit(note) {
        this.editingId = note.id;
        this.editBody = note.body || '';
        this.editKind = note.kind || 'general';
      },

      cancelEdit() {
        this.editingId = null;
        this.editBody = '';
        this.editKind = '';
      },

      async saveNote(id, body, kind) {
        const text = (body || '').trim();
        if (!text) return;
        try {
          const res = await fetch('/student-notes/' + id, {
            method: 'PATCH',
            credentials: 'include',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({ body: text, kind: kind }),
          });
          if (!res.ok) return;
          const data = await res.json();
          const note = data.note || {};
          const idx = this.notes.findIndex(n => n.id === id);
          if (idx !== -1) {
            this.notes[idx] = Object.assign({}, this.notes[idx], {
              body: note.body != null ? note.body : text,
              kind: note.kind != null ? note.kind : kind,
              topic_tag: note.topic_tag != null ? note.topic_tag : this.notes[idx].topic_tag,
            });
          }
          this.cancelEdit();
        } catch (e) { /* сеть недоступна — оставляем режим правки */ }
      },

      async deleteNote(id) {
        if (!confirm('Удалить запись?')) return;
        try {
          const res = await fetch('/student-notes/' + id, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrf(),
            },
          });
          if (!res.ok) return;
          this.notes = this.notes.filter(n => n.id !== id);
          if (this.editingId === id) this.cancelEdit();
        } catch (e) { /* сеть недоступна */ }
      },
    };
  }
</script>
@endpush
@endsection
