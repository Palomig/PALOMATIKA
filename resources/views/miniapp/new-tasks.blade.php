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
    font-size: 14px;
    color: var(--text);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .spoiler summary::-webkit-details-marker { display: none; }
  .spoiler summary::after {
    content: '▾';
    color: var(--muted);
    transition: transform .15s ease;
  }
  .spoiler[open] summary::after { transform: rotate(180deg); }
  .spoiler-body { padding: 0 10px 10px; display: flex; flex-direction: column; gap: 8px; }
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
    @php
      $groups = $groupedByTopic[$selectedTopic] ?? [];
    @endphp

    @if($selectedTopic === '10' && !empty($groups))
      @foreach($groups as $group)
        <details class="spoiler">
          <summary>{{ $group['title'] }}</summary>
          <div class="spoiler-body">
            @foreach($group['tasks'] as $task)
              <div class="task-item">
                @php
                  $svg = is_string($task['svg'] ?? null) ? $task['svg'] : '';
                  $image = is_string($task['image'] ?? null) ? $task['image'] : '';
                @endphp

                @if($svg !== '')
                  <div style="margin-bottom:10px; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:#0a1628; padding:8px;">
                    {!! $svg !!}
                  </div>
                @elseif($image !== '')
                  @if(\Illuminate\Support\Str::startsWith($image, '<svg'))
                    <div style="margin-bottom:10px; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:#0a1628; padding:8px;">
                      {!! $image !!}
                    </div>
                  @else
                    <img src="{{ asset('images/tasks/' . (int)$selectedTopic . '/' . ltrim($image, '/')) }}"
                         alt="Иллюстрация"
                         style="display:block;max-width:100%;height:auto;margin-bottom:10px;border:1px solid var(--border);border-radius:10px;background:#fff;padding:4px;"
                         loading="lazy">
                  @endif
                @endif

                <div class="task-item-text">{{ $task['text'] }}</div>
                @if(!empty($task['id']))
                  <div class="task-item-meta">ID {{ $task['id'] }}</div>
                @endif
              </div>
            @endforeach
          </div>
        </details>
      @endforeach
    @else
      @forelse(($newByTopic[$selectedTopic] ?? []) as $task)
        <div class="task-item">
          @php
            $svg = is_string($task['svg'] ?? null) ? $task['svg'] : '';
            $image = is_string($task['image'] ?? null) ? $task['image'] : '';
          @endphp

          @if($svg !== '')
            <div style="margin-bottom:10px; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:#0a1628; padding:8px;">
              {!! $svg !!}
            </div>
          @elseif($image !== '')
            @if(\Illuminate\Support\Str::startsWith($image, '<svg'))
              <div style="margin-bottom:10px; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:#0a1628; padding:8px;">
                {!! $image !!}
              </div>
            @else
              <img src="{{ asset('images/tasks/' . (int)$selectedTopic . '/' . ltrim($image, '/')) }}"
                   alt="Иллюстрация"
                   style="display:block;max-width:100%;height:auto;margin-bottom:10px;border:1px solid var(--border);border-radius:10px;background:#fff;padding:4px;"
                   loading="lazy">
            @endif
          @endif

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
    @endif
  </div>
</div>
@endsection
