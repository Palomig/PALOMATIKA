@extends('layouts.pwa')
@section('title', 'История ученика — palomatika')

@push('styles')
  .topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
    opacity: 0; animation: fadeDown 0.3s ease 0s forwards;
  }
  .back { color: var(--text); text-decoration: none; font-size: 18px; padding: 6px 8px; border: 1px solid var(--border); border-radius: 10px; }
  .topbar-title { font-family: var(--display); font-size: 18px; color: var(--text); }

  .student-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 16px;
    margin-bottom: 14px;
    opacity: 0; animation: fadeUp 0.3s ease 0.04s forwards;
  }
  .student-name { font-family: var(--display); font-size: 18px; color: var(--text); }
  .student-meta { margin-top: 4px; font-size: 12px; font-weight: 600; color: var(--muted); }

  .attempt-card {
    display: flex; align-items: center; justify-content: space-between;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 14px 16px;
    text-decoration: none; color: inherit;
    margin-bottom: 8px;
    opacity: 0; animation: fadeUp 0.3s ease calc(0.08s + var(--i, 0) * 0.04s) forwards;
  }
  .attempt-card:active { background: var(--surface2); }
  .attempt-left { flex: 1; min-width: 0; }
  .attempt-label { font-family: var(--display); font-size: 14px; color: var(--text); margin-bottom: 3px; }
  .attempt-meta { font-size: 11px; font-weight: 600; color: var(--muted); }
  .attempt-score {
    font-family: var(--display); font-size: 20px; line-height: 1;
    margin-left: 12px; white-space: nowrap;
  }
  .attempt-score small { font-size: 14px; color: var(--muted); }
  .score-good { color: var(--green); }
  .score-mid { color: var(--yellow); }
  .score-bad { color: var(--red); }

  .empty-state {
    text-align: center; padding: 40px 20px;
    color: var(--muted); font-size: 14px; font-weight: 600;
    opacity: 0; animation: fadeUp 0.3s ease 0.1s forwards;
  }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="{{ $backUrl }}" class="back">←</a>
    <div class="topbar-title">История ученика</div>
    <div style="width:34px;"></div>
  </div>

  <div class="student-card">
    <div class="student-name">{{ $student->name }}</div>
    <div class="student-meta">
      ID: {{ $student->id }}
      @if($student->grade_num)
        · {{ $student->grade_num }} класс
      @endif
      @if($list)
        · {{ count($list) }} заверш. вариантов
      @endif
    </div>
  </div>

  @forelse($list as $i => $att)
    @php
      $pct = $att['total'] > 0 ? round(($att['correct'] / $att['total']) * 100) : 0;
      $scoreClass = $pct >= 70 ? 'score-good' : ($pct >= 40 ? 'score-mid' : 'score-bad');
      $timeStr = $att['time'] !== null ? (floor($att['time'] / 60) . ':' . str_pad($att['time'] % 60, 2, '0', STR_PAD_LEFT)) : null;
    @endphp
    <a href="{{ route('pwa.student.history.student-detail', ['studentId' => $student->id, 'attemptId' => $att['id']]) }}" class="attempt-card" style="--i:{{ $i }}">
      <div class="attempt-left">
        <div class="attempt-label">{{ $att['label'] }}</div>
        <div class="attempt-meta">
          {{ $att['date']?->format('d.m.Y H:i') }}
          @if($timeStr) · {{ $timeStr }} @endif
          @if($att['hash']) · {{ $att['hash'] }} @endif
        </div>
      </div>
      <div class="attempt-score {{ $scoreClass }}">
        {{ $att['correct'] }}<small>/{{ $att['total'] }}</small>
      </div>
    </a>
  @empty
    <div class="empty-state">
      У этого ученика пока нет завершённых попыток.
    </div>
  @endforelse
</div>
@endsection
