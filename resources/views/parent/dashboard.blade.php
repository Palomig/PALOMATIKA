@extends('layouts.parent')
@section('title', 'Мои дети — Palomatika')

@push('styles')
  .p-header { padding: 20px 0 16px; opacity: 0; animation: fadeDown 0.3s ease 0s forwards; }
  .p-header-title { font-family: var(--display); font-size: 22px; color: var(--text); }
  .p-header-sub { font-size: 13px; color: var(--muted); margin-top: 4px; }

  .child-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 18px;
    margin-bottom: 12px;
    opacity: 0; animation: fadeUp 0.3s ease calc(var(--i, 0) * 0.08s) forwards;
    text-decoration: none; color: inherit; display: block;
  }
  .child-card:hover { border-color: var(--accent); }

  .child-top { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
  .child-avatar {
    width: 48px; height: 48px; border-radius: 50%;
    background: linear-gradient(135deg, #3b6ef8, #7c3aed);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fff; font-weight: 700; flex-shrink: 0;
  }
  .child-name { font-family: var(--display); font-size: 17px; color: var(--text); }
  .child-grade { font-size: 12px; color: var(--muted); margin-top: 2px; }

  .child-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
  .stat-box { background: var(--surface2); border-radius: 10px; padding: 10px 8px; text-align: center; }
  .stat-val { font-family: var(--display); font-size: 20px; color: var(--text); }
  .stat-label { font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2px; }
  .stat-val.red { color: var(--red); }
  .stat-val.green { color: var(--green); }

  .child-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; border-top: 1px solid var(--border); padding-top: 12px; }
  .child-links { display: flex; gap: 8px; }
  .child-link {
    font-size: 12px; font-weight: 700; color: var(--accent);
    text-decoration: none; padding: 5px 10px;
    background: rgba(59,110,248,0.08); border-radius: 8px;
  }
  .diag-badge { font-size: 11px; color: var(--muted); }
  .diag-badge.done { color: var(--green); }

  .no-children {
    text-align: center; padding: 60px 0;
    opacity: 0; animation: fadeUp 0.3s ease 0.1s forwards;
  }
  .no-children-icon { font-size: 48px; margin-bottom: 16px; }
  .no-children-text { font-size: 15px; color: var(--muted); line-height: 1.6; }
@endpush

@section('content')

<div class="p-header">
  <div class="p-header-title">Мои дети</div>
  <div class="p-header-sub">{{ now()->format('d.m.Y') }}</div>
</div>

@if($childSummaries->isEmpty())
<div class="no-children">
  <div class="no-children-icon">👶</div>
  <div class="no-children-text">
    Пока нет привязанных учеников.<br>
    Обратитесь к преподавателю<br>для привязки аккаунта ребёнка.
  </div>
</div>
@else
@foreach($childSummaries as $i => $s)
@php
  $child = $s['child'];
  $initials = mb_strtoupper(mb_substr($child->name, 0, 1));
  $avgPct = round($s['avg_skill_weight'] * 100);
@endphp
<div class="child-card" style="--i: {{ $i }}">
  <div class="child-top">
    <div class="child-avatar">{{ $initials }}</div>
    <div>
      <div class="child-name">{{ $child->name }}</div>
      <div class="child-grade">{{ $child->grade ?? 'Ученик' }}</div>
    </div>
  </div>

  <div class="child-stats">
    <div class="stat-box">
      <div class="stat-val {{ $avgPct >= 70 ? 'green' : ($avgPct >= 50 ? '' : 'red') }}">{{ $avgPct }}%</div>
      <div class="stat-label">Уровень</div>
    </div>
    <div class="stat-box">
      <div class="stat-val {{ $s['weak_skills_count'] > 0 ? 'red' : 'green' }}">{{ $s['weak_skills_count'] }}</div>
      <div class="stat-label">Слабых</div>
    </div>
    <div class="stat-box">
      <div class="stat-val {{ $s['pending_homework'] > 0 ? 'red' : 'green' }}">{{ $s['pending_homework'] }}</div>
      <div class="stat-label">ДЗ</div>
    </div>
  </div>

  <div class="child-footer">
    <div class="child-links">
      <a href="{{ route('parent.child.skills', $child->id) }}" class="child-link">📊 Навыки</a>
      <a href="{{ route('parent.child.attendance', $child->id) }}" class="child-link">📅 Посещ.</a>
      <a href="{{ route('parent.child.homework', $child->id) }}" class="child-link">📝 ДЗ</a>
    </div>
    <div class="diag-badge {{ $s['diagnostic_completed'] ? 'done' : '' }}">
      {{ $s['diagnostic_completed'] ? '✓ Диагностика' : '⚠ Без диагностики' }}
    </div>
  </div>
</div>
@endforeach
@endif

@endsection
