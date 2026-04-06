@extends('layouts.pwa')
@section('title', 'Домашка — palomatika')

@push('styles')
  .topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
    opacity: 0; animation: fadeDown 0.3s ease 0s forwards;
  }
  .back { color: var(--text); text-decoration: none; font-size: 18px; padding: 6px 8px; border: 1px solid var(--border); border-radius: 10px; }
  .topbar-title { font-family: var(--display); font-size: 18px; color: var(--text); }

  .hw-item {
    display: flex; align-items: center; justify-content: space-between;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 14px 16px;
    text-decoration: none; color: inherit;
    margin-bottom: 8px;
    opacity: 0; animation: fadeUp 0.3s ease calc(var(--i, 0) * 0.04s) forwards;
  }
  .hw-item:active { background: var(--surface2); }
  .hw-left { flex: 1; min-width: 0; }
  .hw-label { font-family: var(--display); font-size: 14px; color: var(--text); margin-bottom: 3px; }
  .hw-meta { font-size: 11px; font-weight: 600; color: var(--muted); }
  .hw-status {
    font-size: 10px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.06em; padding: 3px 8px; border-radius: 6px;
    margin-left: 12px; white-space: nowrap;
  }
  .status-assigned { background: rgba(59,130,246,.2); color: #93bbfd; }
  .status-started { background: rgba(234,179,8,.2); color: #fcd34d; }
  .status-completed { background: rgba(34,197,94,.2); color: #86efac; }

  .empty-state {
    text-align: center; padding: 40px 20px;
    color: var(--muted); font-size: 14px; font-weight: 600;
    opacity: 0; animation: fadeUp 0.3s ease 0.1s forwards;
  }
  .empty-icon { font-size: 40px; margin-bottom: 12px; }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="{{ route('miniapp.dashboard') }}" class="back">←</a>
    <div class="topbar-title">Домашка</div>
    <div style="width:34px;"></div>
  </div>

  @forelse($list as $i => $hw)
    @php
      if ($hw['type'] === 'full_variant' && $hw['variant_hash']) {
          $href = '/tg/test-start/' . $hw['variant_hash'];
      } elseif ($hw['type'] === 'topic_practice' && $hw['topic_number']) {
          $tn = (int) $hw['topic_number'];
          if ($tn <= 19) {
              $href = '/tg/tasks-part1?topic=' . $tn;
          } else {
              $href = '/tg/part2?topic=' . $tn;
          }
      } else {
          $href = '#';
      }
      $statusLabel = match($hw['status']) {
          'completed' => 'Выполнено',
          'started' => 'Начато',
          default => 'Назначено',
      };
    @endphp
    <a href="{{ $href }}" class="hw-item" style="--i:{{ $i }}">
      <div class="hw-left">
        <div class="hw-label">{{ $hw['title'] }}</div>
        <div class="hw-meta">{{ $hw['assigned_at']?->format('d.m.Y') }}</div>
      </div>
      <div class="hw-status status-{{ $hw['status'] }}">{{ $statusLabel }}</div>
    </a>
  @empty
    <div class="empty-state">
      <div class="empty-icon">📚</div>
      Пока нет домашних заданий.
    </div>
  @endforelse
</div>
@endsection
