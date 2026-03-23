@extends('layouts.parent')
@section('title', 'Посещаемость — ' . $child->name)

@push('styles')
  .topbar { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; opacity: 0; animation: fadeDown 0.3s ease 0s forwards; }
  .back { color: var(--text); text-decoration: none; font-size: 18px; padding: 6px 10px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface); }
  .topbar-name { font-family: var(--display); font-size: 18px; color: var(--text); }

  .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 20px; opacity: 0; animation: fadeUp 0.3s ease 0.1s forwards; }
  .stat-box { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px 8px; text-align: center; }
  .stat-val { font-family: var(--display); font-size: 22px; color: var(--text); }
  .stat-label { font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-top: 3px; letter-spacing: 0.04em; }
  .stat-val.green { color: var(--green); }
  .stat-val.red   { color: var(--red); }

  .attend-item {
    display: flex; align-items: center; gap: 12px;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 13px 16px; margin-bottom: 8px;
    opacity: 0; animation: fadeUp 0.3s ease calc(var(--i, 0) * 0.03s) forwards;
  }
  .attend-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
  .dot-present  { background: #16a34a; }
  .dot-absent   { background: #dc2626; }
  .dot-late     { background: #d97706; }
  .dot-cancelled{ background: #9ca3af; }
  .attend-date { font-size: 14px; color: var(--text); font-weight: 700; flex: 1; }
  .attend-status { font-size: 12px; color: var(--muted); }
  .attend-note { font-size: 11px; color: var(--muted); margin-top: 2px; }

  .empty { text-align: center; padding: 40px 0; color: var(--muted); font-size: 14px; }

  .pct-bar-wrap { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 16px; margin-bottom: 20px; opacity: 0; animation: fadeUp 0.3s ease 0.2s forwards; }
  .pct-label { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
  .pct-bar { height: 8px; background: var(--border); border-radius: 4px; }
  .pct-fill { height: 8px; background: var(--green); border-radius: 4px; }
@endpush

@section('content')

<div class="topbar">
  <a href="{{ route('parent.dashboard') }}" class="back">←</a>
  <div class="topbar-name">{{ $child->name }}</div>
</div>

@php
  $pct = $stats['total'] > 0 ? round($stats['present'] / $stats['total'] * 100) : 0;
@endphp

<div class="pct-bar-wrap">
  <div class="pct-label">
    <span>Посещаемость</span>
    <span>{{ $pct }}%</span>
  </div>
  <div class="pct-bar">
    <div class="pct-fill" style="width: {{ $pct }}%"></div>
  </div>
</div>

<div class="stats-row">
  <div class="stat-box">
    <div class="stat-val">{{ $stats['total'] }}</div>
    <div class="stat-label">Всего</div>
  </div>
  <div class="stat-box">
    <div class="stat-val green">{{ $stats['present'] }}</div>
    <div class="stat-label">Был</div>
  </div>
  <div class="stat-box">
    <div class="stat-val red">{{ $stats['absent'] }}</div>
    <div class="stat-label">Пропустил</div>
  </div>
  <div class="stat-box">
    <div class="stat-val" style="color:var(--yellow)">{{ $stats['late'] }}</div>
    <div class="stat-label">Опоздал</div>
  </div>
</div>

@if($attendance->isEmpty())
<div class="empty">Записей о посещаемости пока нет</div>
@else
@foreach($attendance as $i => $rec)
@php
  $dotClass = match($rec->status) {
    'present'   => 'dot-present',
    'absent'    => 'dot-absent',
    'late'      => 'dot-late',
    'cancelled' => 'dot-cancelled',
    default     => 'dot-cancelled',
  };
  $label = match($rec->status) {
    'present'   => 'Присутствовал',
    'absent'    => 'Отсутствовал',
    'late'      => 'Опоздал',
    'cancelled' => 'Отменён',
    default     => $rec->status,
  };
@endphp
<div class="attend-item" style="--i: {{ $i }}">
  <div class="attend-dot {{ $dotClass }}"></div>
  <div style="flex:1">
    <div class="attend-date">{{ $rec->lesson_date->format('d.m.Y') }}{{ $rec->start_time ? ' · ' . substr($rec->start_time, 0, 5) : '' }}</div>
    @if($rec->note)
    <div class="attend-note">{{ $rec->note }}</div>
    @endif
  </div>
  <div class="attend-status">{{ $label }}</div>
</div>
@endforeach

{{ $attendance->links() }}
@endif

@endsection
