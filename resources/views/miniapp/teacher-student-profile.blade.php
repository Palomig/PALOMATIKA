@extends('layouts.miniapp')

@section('title', 'Профиль ученика')

@push('styles')
<style>
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
  details.err { border:1px solid var(--border); border-radius:10px; padding:8px 10px; margin:8px 0; background:var(--surface2); }
  details.err summary { cursor:pointer; list-style:none; font-weight:700; }
  details.err summary::-webkit-details-marker { display:none; }
  .err-meta { margin-top:6px; font-size:12px; color:var(--muted); }
  .task-box { margin-top:8px; padding:8px; border:1px dashed var(--border); border-radius:8px; }
  .task-svg { margin-top:8px; }
  .task-svg svg { max-width:100%; height:auto; display:block; }
</style>
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="/tg/teacher/students" class="back">←</a>
    <div style="font-weight:800;">Профиль ученика</div>
    <div style="width:34px;"></div>
  </div>

  <div class="card">
    <div class="name">{{ $student->name }}</div>
    <div class="muted">ID: {{ $student->id }} · {{ $student->email ?: 'без email' }}</div>
    <div class="stats">
      <div class="stat"><b>{{ $attempts->count() }}</b><span class="muted">попыток</span></div>
      <div class="stat"><b>{{ $scoredTotal }}</b><span class="muted">проверено</span></div>
      <div class="stat"><b>{{ $accuracy === null ? '—' : ($accuracy.'%') }}</b><span class="muted">точность</span></div>
    </div>
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
    <div style="font-weight:700;margin-bottom:8px;">Последние ошибки</div>
    @forelse($wrongTasks as $w)
      <details class="err">
        <summary>
          Вариант {{ $w['variant_hash'] ?: ('#'.$w['attempt_id']) }} · Задание {{ $w['task_number'] }}
        </summary>
        <div class="err-meta">Ответ ученика: <b>{{ $w['student_answer'] }}</b> · Верный: <b>{{ $w['correct_answer'] }}</b></div>
        <div class="task-box">
          @if(!empty($w['task_text']))
            <div>{{ $w['task_text'] }}</div>
          @endif
          @if(!empty($w['task_expression']))
            <div style="margin-top:6px;"><code>{{ $w['task_expression'] }}</code></div>
          @endif
          @if(!empty($w['task_svg']))
            <div class="task-svg">{!! $w['task_svg'] !!}</div>
          @endif
        </div>
      </details>
    @empty
      <div class="muted">Ошибок не найдено 🎉</div>
    @endforelse
  </div>
</div>
@endsection
