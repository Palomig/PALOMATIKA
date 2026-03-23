@extends('layouts.parent')
@section('title', 'Навыки — ' . $child->name)

@push('styles')
  .topbar { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; opacity: 0; animation: fadeDown 0.3s ease 0s forwards; }
  .back { color: var(--text); text-decoration: none; font-size: 18px; padding: 6px 10px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface); }
  .topbar-name { font-family: var(--display); font-size: 18px; color: var(--text); }

  .score-card {
    background: linear-gradient(135deg, #3b6ef8, #7c3aed);
    border-radius: var(--r); padding: 20px; text-align: center; margin-bottom: 20px;
    color: #fff; opacity: 0; animation: fadeUp 0.3s ease 0.1s forwards;
  }
  .score-num { font-family: var(--display); font-size: 52px; line-height: 1; }
  .score-label { font-size: 13px; opacity: 0.8; margin-top: 4px; }

  .section-label { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin: 18px 0 10px; }

  .category-block { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 16px; margin-bottom: 12px; opacity: 0; animation: fadeUp 0.3s ease calc(var(--i, 0) * 0.06s) forwards; }
  .cat-name { font-size: 14px; font-weight: 800; color: var(--text); margin-bottom: 12px; }

  .skill-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
  .skill-row:last-child { margin-bottom: 0; }
  .skill-name { font-size: 13px; color: var(--text); flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .skill-bar-wrap { width: 90px; }
  .skill-bar { height: 6px; background: var(--border); border-radius: 3px; }
  .skill-fill { height: 6px; border-radius: 3px; }
  .skill-pct { font-size: 12px; font-weight: 700; width: 34px; text-align: right; }

  .fill-red    { background: #dc2626; }
  .fill-yellow { background: #d97706; }
  .fill-green  { background: #16a34a; }
  .pct-red    { color: #dc2626; }
  .pct-yellow { color: #d97706; }
  .pct-green  { color: #16a34a; }
@endpush

@section('content')

<div class="topbar">
  <a href="{{ route('parent.dashboard') }}" class="back">←</a>
  <div class="topbar-name">{{ $child->name }}</div>
</div>

<div class="score-card">
  <div class="score-num">{{ round($averageWeight * 100) }}%</div>
  <div class="score-label">средний уровень знаний</div>
</div>

@if($weakSkills->count() > 0)
<div class="section-label">Слабые навыки ({{ $weakSkills->count() }})</div>
@endif

<div class="section-label">По категориям</div>

@foreach($byCategory as $category => $skills)
@php $catIndex = $loop->index; @endphp
<div class="category-block" style="--i: {{ $catIndex }}">
  <div class="cat-name">{{ $category }}</div>
  @foreach($skills as $us)
  @php
    $pct = round(($us->weight ?? 0) * 100);
    $colorClass = $pct >= 70 ? 'fill-green' : ($pct >= 50 ? 'fill-yellow' : 'fill-red');
    $pctClass   = $pct >= 70 ? 'pct-green'  : ($pct >= 50 ? 'pct-yellow'  : 'pct-red');
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

@endsection
