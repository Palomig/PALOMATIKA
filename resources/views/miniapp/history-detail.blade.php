@extends('layouts.miniapp')
@section('title', $label . ' — palomatika')

@push('katex')
@include('partials.head-katex')
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<style>
  .topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
    opacity: 0; animation: fadeDown 0.3s ease 0s forwards;
  }
  .back { color: var(--text); text-decoration: none; font-size: 18px; padding: 6px 8px; border: 1px solid var(--border); border-radius: 10px; }
  .topbar-title { font-family: var(--display); font-size: 16px; color: var(--text); text-align: center; flex: 1; }

  .summary-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 16px;
    display: flex; align-items: center; gap: 16px;
    margin-bottom: 16px;
    opacity: 0; animation: fadeUp 0.3s ease 0.06s forwards;
  }
  .summary-score { font-family: var(--display); font-size: 32px; line-height: 1; }
  .summary-score small { font-size: 18px; color: var(--muted); }
  .score-good { color: var(--green); }
  .score-mid { color: var(--yellow); }
  .score-bad { color: var(--red); }
  .summary-details { flex: 1; }
  .summary-label { font-size: 12px; font-weight: 700; color: var(--muted); }
  .summary-time { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 2px; }
  .summary-date { font-size: 10px; font-weight: 600; color: var(--muted); margin-top: 2px; }

  .section-title {
    font-size: 11px; font-weight: 800; color: var(--muted);
    text-transform: uppercase; letter-spacing: 0.1em;
    margin-bottom: 8px;
    opacity: 0; animation: fadeUp 0.3s ease 0.1s forwards;
  }

  .err-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 12px 14px;
    margin-bottom: 8px;
    opacity: 0; animation: fadeUp 0.3s ease calc(0.12s + var(--i, 0) * 0.04s) forwards;
  }
  .err-card summary {
    cursor: pointer; list-style: none;
    display: flex; align-items: center; justify-content: space-between;
    font-weight: 700; font-size: 13px; color: var(--text);
  }
  .err-card summary::-webkit-details-marker { display: none; }
  .err-card[open] summary { margin-bottom: 10px; }
  .err-answers {
    font-size: 12px; color: var(--muted); font-weight: 600;
    margin-top: 8px; padding-top: 8px;
    border-top: 1px dashed var(--border);
  }
  .err-answers b { color: var(--text); }
  .err-student-answer { color: var(--red); }
  .err-correct-answer { color: var(--green); }

  .task-box { margin-top: 8px; }
  .task-instruction { font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 4px; }
  .task-text { font-size: 13px; color: var(--text); line-height: 1.5; }
  .task-expression { font-size: 14px; margin-top: 6px; }
  .task-svg { margin-top: 8px; border-radius: 8px; background: #0a1628; padding: 6px; }
  .task-svg svg { max-width: 100%; height: auto; display: block; }
  .task-svg-wide { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .task-svg-wide > div { min-width: 600px; }
  .task-svg-wide svg { width: 100%; max-width: none; }
  .task-options { margin-top: 8px; padding-left: 18px; font-size: 12px; }
  .task-options li { margin: 3px 0; color: var(--text); }

  .no-errors {
    text-align: center; padding: 30px 20px;
    color: var(--muted); font-size: 14px; font-weight: 600;
    opacity: 0; animation: fadeUp 0.3s ease 0.12s forwards;
  }
  .suspicion-card {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: var(--r);
    padding: 12px 14px;
    margin-bottom: 16px;
    opacity: 0; animation: fadeUp 0.3s ease 0.04s forwards;
  }
  .suspicion-card summary {
    cursor: pointer; list-style: none;
    display: flex; align-items: center; gap: 8px;
    font-weight: 700; font-size: 13px; color: #ef4444;
  }
  .suspicion-card summary::-webkit-details-marker { display: none; }
  .suspicion-card[open] summary { margin-bottom: 10px; }
  .suspicion-reasons {
    font-size: 12px; color: var(--text); line-height: 1.6;
    padding-left: 4px;
  }
  .suspicion-reasons li { margin: 4px 0; }
  .suspicion-note {
    font-size: 11px; color: var(--muted); margin-top: 8px;
    padding-top: 8px; border-top: 1px dashed rgba(239,68,68,0.2);
  }
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
    <a href="{{ $backUrl ?? '/tg/history' }}" class="back">←</a>
    <div class="topbar-title">{{ $label }}</div>
    <div style="width:34px;"></div>
  </div>

  @if($isSuspicious ?? false)
  <details class="suspicion-card">
    <summary>
      <span>⚠ Подозрительно</span>
      <span style="font-size:11px;font-weight:600;margin-left:auto;">Подробнее ›</span>
    </summary>
    <ul class="suspicion-reasons">
      @foreach($suspicionReasons as $reason)
        <li>{{ $reason }}</li>
      @endforeach
    </ul>
    <div class="suspicion-note">
      Это автоматическое обнаружение на основе поведения при решении. Не является доказательством.
    </div>
  </details>
  @endif

  @php
    $pct = $total > 0 ? round(($correct / $total) * 100) : 0;
    $scoreClass = $pct >= 70 ? 'score-good' : ($pct >= 40 ? 'score-mid' : 'score-bad');
    $timeStr = $time !== null ? (floor($time / 60) . ':' . str_pad($time % 60, 2, '0', STR_PAD_LEFT)) : null;
  @endphp

  <div class="summary-card">
    <div class="summary-score {{ $scoreClass }}">{{ $correct }}<small>/{{ $total }}</small></div>
    <div class="summary-details">
      <div class="summary-label">правильных ответов</div>
      @if($timeStr)
      <div class="summary-time">⏱ {{ $timeStr }}</div>
      @endif
      <div class="summary-date">{{ $attempt->submitted_at?->format('d.m.Y H:i') }}</div>
    </div>
  </div>

  @if(count($wrongTasks) > 0)
  <div class="section-title">Ошибки ({{ count($wrongTasks) }})</div>

  @foreach($wrongTasks as $i => $w)
  <details class="err-card task-render-scope" style="--i:{{ $i }}">
    <summary>
      <span>Задание {{ $w['task_number'] }}</span>
      <span style="font-size:11px;color:var(--red);">✕</span>
    </summary>

    <div class="task-box">
      @if(!empty($w['task_instruction']))
        <div class="task-instruction">{{ $w['task_instruction'] }}</div>
      @endif
      @if(!empty($w['task_text']))
        <div class="task-text">{!! nl2br(e($w['task_text'])) !!}</div>
      @endif
      @if(!empty($w['task_expression']))
        <div class="task-expression">\({{ $w['task_expression'] }}\)</div>
      @endif
      @php
        $svgContent = !empty($w['task_svg']) ? $w['task_svg'] : (!empty($w['task_image']) && str_starts_with(trim($w['task_image']), '<svg') ? $w['task_image'] : '');
      @endphp
      @if($svgContent)
        <div class="task-svg{{ $w['task_number'] == 11 ? ' task-svg-wide' : '' }}"><div>{!! $svgContent !!}</div></div>
      @endif

      @if(!empty($w['task_options']) && is_array($w['task_options']))
        <ol class="task-options">
          @foreach($w['task_options'] as $opt)
            @php
              $optText = \App\Support\OptionLabelFormatter::optionText($opt);
              $optLabel = \App\Support\OptionLabelFormatter::optionLabel($opt, $loop->index);
              $hasLatex = (bool) preg_match('/\\\\[a-zA-Z]/', $optText);
            @endphp
            <li>{{ $optLabel }}. @if($hasLatex)\({{ $optText }}\)@else{{ $optText }}@endif</li>
          @endforeach
        </ol>
      @endif
    </div>

    <div class="err-answers">
      Твой ответ: <b class="err-student-answer">{{ \App\Support\OptionLabelFormatter::formatAnswer($w['student_answer'], is_array($w['task_options'] ?? null) ? $w['task_options'] : []) }}</b>
      &nbsp;·&nbsp;
      Верный: <b class="err-correct-answer">{{ \App\Support\OptionLabelFormatter::formatAnswer($w['correct_answer'], is_array($w['task_options'] ?? null) ? $w['task_options'] : []) }}</b>
    </div>
  </details>
  @endforeach

  @else
  <div class="no-errors">
    Ошибок нет! 🎉
  </div>
  @endif
</div>
@endsection
