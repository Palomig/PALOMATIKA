@extends('layouts.pwa')

@section('title', 'Профиль ученика')

@push('styles')
  .page { min-height: 100vh; background: var(--bg); color: var(--text); padding: 16px; }
  .topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom: 12px; }
  .back { color: var(--text); text-decoration:none; font-size: 18px; padding:6px 8px; border:1px solid var(--border); border-radius:10px; }
  .card { background: var(--surface); border:1px solid var(--border); border-radius:14px; padding:12px; margin-bottom:12px; }
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
  .note-body { font-size:13px; color:var(--text); line-height:1.4; }
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

  <div class="card">
    <div style="font-weight:700;margin-bottom:8px;">История вариантов</div>
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
  </div>

  <div class="card">
    <div style="font-weight:700;margin-bottom:4px;">Темы/задания: точность</div>
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
  </div>

  <div class="card">
    <div style="font-weight:700;margin-bottom:8px;">Наблюдения</div>
    @if(!empty($notes) && count($notes))
      @php
        $kindMeta = [
          'weakness' => '🔴 западает',
          'strength' => '🟢 сильная',
          'todo'     => '📌 todo',
          'general'  => '💬 общее',
        ];
      @endphp
      @foreach($notes as $note)
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
      @endforeach
    @else
      <div class="muted">Записей пока нет.</div>
    @endif
  </div>

</div>
@endsection
