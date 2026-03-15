@extends('layouts.miniapp')

@section('title', 'Профиль ученика')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
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
  .task-image { margin-top:8px; max-width:100%; border-radius:8px; border:1px solid var(--border); }
  .task-options { margin-top:8px; padding-left:18px; }
  .task-options li { margin:4px 0; color: var(--text); }

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
</style>
@endpush

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof renderMathInElement === 'function') {
      document.querySelectorAll('.task-render-scope').forEach(function (el) {
        renderMathInElement(el, {
          delimiters: [
            { left: '$$', right: '$$', display: true },
            { left: '\\[', right: '\\]', display: true },
            { left: '\\(', right: '\\)', display: false },
            { left: '$', right: '$', display: false }
          ],
          throwOnError: false
        });
      });
    }
  });
</script>
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
    <div style="font-weight:700;margin-bottom:8px;">История вариантов</div>
    @forelse($historyList as $h)
      @php
        $pct = $h['total'] > 0 ? round(($h['correct'] / $h['total']) * 100) : 0;
        $scoreClass = $pct >= 70 ? 'score-good' : ($pct >= 40 ? 'score-mid' : 'score-bad');
        $timeStr = $h['time'] !== null ? (floor($h['time'] / 60) . ':' . str_pad($h['time'] % 60, 2, '0', STR_PAD_LEFT)) : null;
      @endphp
      <div class="attempt-card">
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
      </div>
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
    <div style="font-weight:700;margin-bottom:8px;">Последние ошибки</div>
    @forelse($wrongTasks as $w)
      <details class="err">
        <summary>
          Вариант {{ $w['variant_hash'] ?: ('#'.$w['attempt_id']) }} · Задание {{ $w['task_number'] }}@if(!empty($w['task_id'])) · #{{ $w['task_id'] }}@endif
        </summary>
        <div class="err-meta task-render-scope">
          Ответ ученика: <b>{{ $w['student_answer'] }}</b> · Верный: <b>{{ $w['correct_answer'] }}</b>
          @if(!empty($w['task_locator'])) · ID: <b>{{ $w['task_locator'] }}</b>@endif
        </div>
        <div class="task-box task-render-scope">
          @if(!empty($w['task_instruction']))
            <div class="muted" style="font-weight:600;margin-bottom:4px;">{{ $w['task_instruction'] }}</div>
          @endif
          @if(!empty($w['task_text']))
            <div>{!! nl2br(e($w['task_text'])) !!}</div>
          @endif
          @if(!empty($w['task_expression']))
            <div style="margin-top:6px;">\({{ $w['task_expression'] }}\)</div>
          @endif
          @if(!empty($w['task_svg']))
            <div class="task-svg">{!! $w['task_svg'] !!}</div>
          @elseif(!empty($w['task_image']))
            <img class="task-image" src="{{ str_starts_with($w['task_image'], 'http') || str_starts_with($w['task_image'], '/') ? $w['task_image'] : '/images/tasks/' . str_pad((string)$w['task_number'], 2, '0', STR_PAD_LEFT) . '/' . $w['task_image'] }}" alt="task image">
          @endif

          @if(!empty($w['task_options']) && is_array($w['task_options']))
            <ol class="task-options">
              @foreach($w['task_options'] as $opt)
                <li>{!! is_string($opt) ? e($opt) : e((string)($opt['label'] ?? $opt['text'] ?? json_encode($opt, JSON_UNESCAPED_UNICODE))) !!}</li>
              @endforeach
            </ol>
          @endif
        </div>
      </details>
    @empty
      <div class="muted">Ошибок не найдено 🎉</div>
    @endforelse
  </div>
</div>
@endsection
