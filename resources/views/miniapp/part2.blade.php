@extends('layouts.miniapp')
@section('title', '2я часть ОГЭ — palomatika')

@push('styles')
  .topics-row {
    display: flex; gap: 8px; overflow-x: auto; padding-bottom: 2px;
    opacity: 0; animation: fadeUp 0.3s ease 0.08s forwards;
  }
  .topic-chip {
    display: flex; align-items: center; gap: 6px;
    min-width: max-content;
    padding: 8px 14px; border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text); text-decoration: none;
    font-family: var(--display); font-size: 13px;
    transition: all .15s ease;
  }
  .topic-chip.active {
    border-color: var(--purple-bd);
    background: var(--purple-bg);
    color: var(--purple);
  }
  .topic-chip.disabled {
    opacity: 0.4; cursor: default; pointer-events: none;
  }
  .topic-chip-icon { font-size: 16px; }
  .topic-chip-num { font-weight: 700; }

  .spoiler {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
  }
  .spoiler summary {
    list-style: none;
    cursor: pointer;
    padding: 12px 14px;
    font-family: var(--display);
    font-size: 13px;
    color: var(--text);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
  }
  .spoiler summary::-webkit-details-marker { display: none; }
  .spoiler summary::after {
    content: '▾';
    color: var(--muted);
    transition: transform .15s ease;
    flex-shrink: 0;
  }
  .spoiler[open] summary::after { transform: rotate(180deg); }
  .spoiler-body { padding: 0 10px 10px; display: flex; flex-direction: column; gap: 8px; }
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

  .hint-box {
    font-size: 11px; color: var(--muted); font-style: italic;
    padding: 6px 10px; margin-bottom: 4px;
  }
@endpush

@section('body')
<div class="page">
  <a href="/tg/dashboard" class="back-btn">‹</a>

  <div class="hero" style="opacity:0; animation: fadeUp 0.3s ease 0.04s forwards;">
    <div class="hero-title">2я часть ОГЭ</div>
    <div class="hero-sub">задания 20–25</div>
  </div>

  <div class="sec-label">Выбери тему</div>
  <div class="topics-row">
    @foreach($topicsMeta as $tid => $meta)
      <a class="topic-chip {{ $selectedTopic === $tid ? 'active' : '' }} {{ !in_array($tid, $topics) ? 'disabled' : '' }}"
         href="{{ in_array($tid, $topics) ? url('/tg/part2?topic=' . $tid) : '#' }}">
        <span class="topic-chip-icon">{{ $meta['icon'] }}</span>
        <span class="topic-chip-num">{{ $tid }}</span>
      </a>
    @endforeach
    {{-- Show coming soon topics --}}
    @foreach([
      '22' => ['title' => 'Графики', 'icon' => '📈'],
      '23' => ['title' => 'Геометрия (выч.)', 'icon' => '📐'],
      '24' => ['title' => 'Геометрия (док.)', 'icon' => '✏️'],
      '25' => ['title' => 'Геометрия (пов.)', 'icon' => '🔺'],
    ] as $tid => $meta)
      @if(!isset($topicsMeta[$tid]))
        <span class="topic-chip disabled">
          <span class="topic-chip-icon">{{ $meta['icon'] }}</span>
          <span class="topic-chip-num">{{ $tid }}</span>
        </span>
      @endif
    @endforeach
  </div>

  <div class="sec-label" style="margin-top:14px;">
    {{ $topicsMeta[$selectedTopic]['icon'] ?? '' }}
    {{ $topicsMeta[$selectedTopic]['title'] ?? 'Тема ' . $selectedTopic }}
  </div>

  <div class="task-list">
    @forelse($zadaniya as $group)
      <details class="spoiler" {{ $loop->first ? 'open' : '' }}>
        <summary>{{ $group['title'] }}</summary>
        <div class="spoiler-body">
          @if($group['hint'])
            <div class="hint-box">{{ $group['hint'] }}</div>
          @endif
          @foreach($group['tasks'] as $task)
            <div class="task-item">
              @if(!empty($task['image']))
                <img src="{{ asset('images/tasks/' . $selectedTopic . '/' . ltrim($task['image'], '/')) }}"
                     alt="" style="display:block;max-width:100%;height:auto;margin-bottom:10px;border:1px solid var(--border);border-radius:10px;background:#fff;padding:4px;" loading="lazy">
              @endif
              <div class="task-item-text">{{ $task['text'] }}</div>
              @if(!empty($task['id']))
                <div class="task-item-meta">{{ $task['id'] }}</div>
              @endif
            </div>
          @endforeach
        </div>
      </details>
    @empty
      <div class="task-item">
        <div class="task-item-text">Задания для этой темы скоро появятся.</div>
      </div>
    @endforelse
  </div>
</div>
@endsection
