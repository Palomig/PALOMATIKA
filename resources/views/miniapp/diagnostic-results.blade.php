@extends('layouts.miniapp')
@section('title', 'Результаты диагностики — palomatika')

@push('styles')
  .dr-header {
    text-align: center; padding: 24px 0 20px;
    opacity: 0; animation: fadeDown 0.3s ease 0s forwards;
  }
  .dr-score { font-family: var(--display); font-size: 48px; color: var(--text); line-height: 1; }
  .dr-score-label { font-size: 12px; color: var(--muted); margin-top: 4px; }

  .section-label {
    font-size: 11px; font-weight: 800; color: var(--muted);
    text-transform: uppercase; letter-spacing: 0.1em;
    margin: 20px 0 10px;
  }

  .category-block { margin-bottom: 20px; opacity: 0; animation: fadeUp 0.3s ease calc(var(--i, 0) * 0.06s) forwards; }
  .category-name { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 10px; }

  .skill-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
  .skill-name { font-size: 13px; color: var(--text); flex: 1; min-width: 0; }
  .skill-bar-wrap { width: 80px; }
  .skill-bar { height: 6px; background: var(--border); border-radius: 3px; }
  .skill-fill { height: 6px; border-radius: 3px; }
  .skill-pct { font-size: 11px; font-weight: 700; width: 32px; text-align: right; }

  .fill-red    { background: #ef4444; }
  .fill-yellow { background: #f59e0b; }
  .fill-green  { background: #22c55e; }
  .pct-red    { color: #ef4444; }
  .pct-yellow { color: #f59e0b; }
  .pct-green  { color: #22c55e; }

  .btn-go {
    display: block; width: 100%; padding: 14px;
    background: var(--accent); color: #fff;
    border: none; border-radius: 14px; font-size: 16px; font-weight: 700;
    text-align: center; text-decoration: none; cursor: pointer;
    margin-top: 24px;
    opacity: 0; animation: fadeUp 0.4s ease 0.5s forwards;
  }

  .weak-alert {
    background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3);
    border-radius: var(--r); padding: 14px 16px; margin-bottom: 16px;
    opacity: 0; animation: fadeUp 0.3s ease 0.2s forwards;
  }
  .weak-alert-title { font-size: 13px; font-weight: 700; color: #ef4444; margin-bottom: 6px; }
  .weak-alert-item { font-size: 12px; color: var(--text); margin-bottom: 3px; }
@endpush

@section('content')

<div class="dr-header">
  <div class="dr-score">{{ round($averageWeight * 100) }}%</div>
  <div class="dr-score-label">средний уровень знаний</div>
</div>

@if($weakSkills->count() > 0)
<div class="weak-alert">
  <div class="weak-alert-title">Слабые навыки ({{ $weakSkills->count() }})</div>
  @foreach($weakSkills->take(5) as $us)
  <div class="weak-alert-item">· {{ $us->skill->name }}</div>
  @endforeach
  @if($weakSkills->count() > 5)
  <div class="weak-alert-item" style="color:var(--muted)">и ещё {{ $weakSkills->count() - 5 }}...</div>
  @endif
</div>
@endif

<div class="section-label">Навыки по категориям</div>

@foreach($byCategory as $category => $skills)
@php $catIndex = $loop->index; @endphp
<div class="category-block" style="--i: {{ $catIndex }}">
  <div class="category-name">{{ $category }}</div>
  @foreach($skills as $us)
  @php
    $pct = round(($us->weight ?? 0) * 100);
    $colorClass = $pct >= 70 ? 'fill-green' : ($pct >= 50 ? 'fill-yellow' : 'fill-red');
    $pctClass = $pct >= 70 ? 'pct-green' : ($pct >= 50 ? 'pct-yellow' : 'pct-red');
  @endphp
  <div class="skill-row">
    <div class="skill-name">{{ $us->skill->name }}</div>
    <div class="skill-bar-wrap">
      <div class="skill-bar">
        <div class="skill-fill {{ $colorClass }}" style="width: {{ $pct }}%"></div>
      </div>
    </div>
    <div class="skill-pct {{ $pctClass }}">{{ $pct }}%</div>
  </div>
  @endforeach
</div>
@endforeach

<a href="{{ route('miniapp.dashboard') }}" class="btn-go">Начать подготовку →</a>

@endsection
