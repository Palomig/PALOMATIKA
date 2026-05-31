@extends('layouts.pwa')
@section('title', 'Кабинет учителя — palomatika')

@push('styles')
  .top-actions { display: flex; gap: 8px; flex-wrap: wrap; }
  .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .metric { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 14px; }
  .metric-label { font-size: 11px; color: var(--muted); margin-bottom: 4px; }
  .metric-value { font-family: var(--display); font-size: 24px; color: var(--text); }
  .list { display: flex; flex-direction: column; gap: 8px; }
  .list-item { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px; text-decoration: none; display: block; }
  .list-item-link { color: inherit; text-decoration: none; }
  .list-title { font-size: 13px; font-weight: 700; color: var(--text); }
  .list-meta { font-size: 11px; color: var(--muted); margin-top: 2px; }
  .attempt-row { display: flex; justify-content: space-between; align-items: center; }
  .attempt-info { flex: 1; min-width: 0; }
  .attempt-score { font-family: var(--display); font-size: 16px; font-weight: 700; white-space: nowrap; margin-left: 12px; }
  .score-good { color: var(--green, #22c55e); }
  .score-mid { color: var(--yellow, #eab308); }
  .score-bad { color: var(--red, #ef4444); }
  .attempt-time { font-size: 10px; color: var(--muted); }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="/" class="back-btn">‹</a>
    <div class="topbar-title">Кабинет учителя</div>
    <div class="topbar-account-id" style="margin-left:auto; font-size:12px; color:var(--muted);">ID: {{ auth()->id() }}</div>
  </div>


  <div class="grid">
    <div class="metric">
      <div class="metric-label">Ученики</div>
      <div class="metric-value">{{ $studentCount }}</div>
    </div>
    <div class="metric">
      <div class="metric-label">С алиасом</div>
      <div class="metric-value">{{ $aliasedCount }}</div>
    </div>
    <div class="metric">
      <div class="metric-label">Варианты</div>
      <div class="metric-value">{{ $variantsCount }}</div>
    </div>
    <div class="metric">
      <div class="metric-label">Кураторские</div>
      <div class="metric-value">{{ $curatedCount }}</div>
    </div>
  </div>

  <a class="btn btn-accent" href="/students">Ученики и алиасы</a>
  <a class="btn btn-accent" href="/homework">Домашка</a>
  <a class="btn btn-accent" href="/lessons">Урок</a>

  <div class="sec-label">Недавние попытки учеников</div>
  <div class="list">
    @forelse($recentAttempts as $att)
      <a class="list-item list-item-link" href="/students/{{ $att['student_id'] }}/attempt/{{ $att['attempt_id'] }}">
        <div class="attempt-row">
          <div class="attempt-info">
            <div class="list-title">{{ $att['student_name'] }}</div>
            <div class="list-meta">
              {{ $att['label'] }}
              · {{ $att['date']?->format('d.m H:i') }}
              @if($att['time'])<span class="attempt-time">· {{ intdiv($att['time'], 60) }}:{{ str_pad($att['time'] % 60, 2, '0', STR_PAD_LEFT) }}</span>@endif
            </div>
          </div>
          @php
            $pct = $att['total'] > 0 ? ($att['correct'] / $att['total']) * 100 : 0;
            $cls = $pct >= 70 ? 'score-good' : ($pct >= 40 ? 'score-mid' : 'score-bad');
          @endphp
          <div class="attempt-score {{ $cls }}">{{ $att['correct'] }}/{{ $att['total'] }}</div>
        </div>
      </a>
    @empty
      <div class="note">Пока нет решённых вариантов.</div>
    @endforelse
  </div>
</div>
@endsection
