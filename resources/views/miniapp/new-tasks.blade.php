@extends('layouts.miniapp')
@section('title', 'Новые задания — palomatika')

@push('styles')
  .topics-row {
    display: flex; gap: 8px; overflow-x: auto; padding-bottom: 2px;
    opacity: 0; animation: fadeUp 0.3s ease 0.08s forwards;
  }
  .topic-pill {
    min-width: 52px; text-align: center;
    padding: 8px 12px; border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text); text-decoration: none;
    font-family: var(--display); font-size: 14px;
  }
  .topic-pill.active {
    border-color: var(--purple-bd);
    background: var(--purple-bg);
    color: var(--purple);
  }

  .task-list {
    margin-top: 12px;
    display: flex; flex-direction: column; gap: 8px;
    opacity: 0; animation: fadeUp 0.3s ease 0.12s forwards;
  }
  .task-item {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 12px 14px;
  }
  .task-item-text { font-size: 13px; line-height: 1.45; color: var(--text); }
  .task-item-meta { margin-top: 6px; font-size: 10px; color: var(--muted); font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
@endpush

@section('body')
<div class="page">
  <a href="/tg/dashboard" class="back-btn">‹</a>

  <div class="hero" style="opacity:0; animation: fadeUp 0.3s ease 0.04s forwards;">
    <div class="hero-title">Новые задания</div>
    <div class="hero-sub">из банка ФИПИ</div>
  </div>

  <div class="sec-label">Выбери тему</div>
  <div class="topics-row">
    @foreach($topics as $topic)
      <a class="topic-pill {{ $selectedTopic === $topic ? 'active' : '' }}" href="{{ url('/tg/new-tasks?topic=' . (int)$topic) }}">
        {{ (int)$topic }}
      </a>
    @endforeach
  </div>

  <div class="sec-label" style="margin-top:14px;">Список заданий</div>
  <div class="task-list">
    @forelse(($newByTopic[$selectedTopic] ?? []) as $task)
      <div class="task-item">
        <div class="task-item-text">{{ $task['text'] }}</div>
        @if(!empty($task['id']))
          <div class="task-item-meta">ID {{ $task['id'] }}</div>
        @endif
      </div>
    @empty
      <div class="task-item">
        <div class="task-item-text">Пока нет новых заданий для этой темы.</div>
      </div>
    @endforelse
  </div>
</div>
@endsection
