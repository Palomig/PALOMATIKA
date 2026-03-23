@extends('layouts.parent')
@section('title', 'Домашние задания — ' . $child->name)

@push('styles')
  .topbar { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; opacity: 0; animation: fadeDown 0.3s ease 0s forwards; }
  .back { color: var(--text); text-decoration: none; font-size: 18px; padding: 6px 10px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface); }
  .topbar-name { font-family: var(--display); font-size: 18px; color: var(--text); }

  .hw-item {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 14px 16px; margin-bottom: 8px;
    display: flex; align-items: flex-start; gap: 12px;
    opacity: 0; animation: fadeUp 0.3s ease calc(var(--i, 0) * 0.04s) forwards;
  }
  .hw-badge {
    font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;
    padding: 4px 8px; border-radius: 6px; flex-shrink: 0; margin-top: 2px;
  }
  .badge-done     { background: var(--green-bg); color: var(--green); }
  .badge-progress { background: rgba(59,110,248,0.1); color: #3b6ef8; }
  .badge-missed   { background: var(--red-bg); color: var(--red); }
  .badge-overdue  { background: var(--yellow-bg); color: var(--yellow); }

  .hw-title { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
  .hw-meta { font-size: 12px; color: var(--muted); }

  .empty { text-align: center; padding: 40px 0; color: var(--muted); font-size: 14px; }
@endpush

@section('content')

<div class="topbar">
  <a href="{{ route('parent.dashboard') }}" class="back">←</a>
  <div class="topbar-name">{{ $child->name }}</div>
</div>

@if($assignments->isEmpty())
<div class="empty">Домашних заданий пока нет</div>
@else
@foreach($assignments as $i => $assignment)
@php
  $hw = $assignment->homework;
  $isCompleted = !is_null($assignment->completed_at);
  $isStarted = !is_null($assignment->started_at);
  $deadline = $hw?->deadline_at;
  $isOverdue = $deadline && now()->isAfter($deadline) && !$isCompleted;

  if ($isCompleted) {
    $badgeClass = 'badge-done';
    $badgeLabel = '✅ Выполнено';
  } elseif ($isOverdue) {
    $badgeClass = 'badge-overdue';
    $badgeLabel = '⚠️ Просрочено';
  } elseif ($isStarted) {
    $badgeClass = 'badge-progress';
    $badgeLabel = '⏳ В процессе';
  } else {
    $badgeClass = 'badge-missed';
    $badgeLabel = '❌ Не начато';
  }
@endphp
<div class="hw-item" style="--i: {{ $i }}">
  <div class="hw-badge {{ $badgeClass }}">{{ $badgeLabel }}</div>
  <div style="flex:1; min-width:0">
    <div class="hw-title">{{ $hw?->title ?? 'Домашнее задание' }}</div>
    <div class="hw-meta">
      Задано: {{ $assignment->created_at->format('d.m.Y') }}
      @if($deadline) · Срок: {{ $deadline->format('d.m.Y') }} @endif
      @if($isCompleted) · Сдано: {{ $assignment->completed_at->format('d.m.Y') }} @endif
    </div>
  </div>
</div>
@endforeach

{{ $assignments->links() }}
@endif

@endsection
