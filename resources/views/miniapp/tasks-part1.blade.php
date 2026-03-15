@extends('layouts.miniapp')
@section('title', '1я часть ОГЭ — palomatika')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<style>
  .topics-row {
    display: flex; gap: 6px; overflow-x: auto; padding-bottom: 2px;
    opacity: 0; animation: fadeUp 0.3s ease 0.08s forwards;
  }
  .topic-pill {
    min-width: 42px; text-align: center;
    padding: 8px 10px; border-radius: 10px;
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

  .spoiler {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
  }
  .spoiler summary {
    list-style: none; cursor: pointer;
    padding: 12px 14px;
    font-family: var(--display); font-size: 13px; color: var(--text);
    display: flex; justify-content: space-between; align-items: center; gap: 8px;
  }
  .spoiler summary::-webkit-details-marker { display: none; }
  .spoiler summary::after {
    content: '▾'; color: var(--muted);
    transition: transform .15s ease; flex-shrink: 0;
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
</style>
@endpush

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof renderMathInElement === 'function') {
      document.querySelectorAll('.task-render-scope').forEach(function (el) {
        renderMathInElement(el, {
          delimiters: [
            { left: '$$', right: '$$', display: true },
            { left: '\\[', right: '\\]', display: true },
            { left: '\\(', right: '\\)', display: false },
            { left: '$', right: '$', display: false }
          ],
          throwOnError: false
        });
      });
    }
  });
</script>
@endpush

@section('body')
<div class="page task-render-scope">
  <a href="/tg/dashboard" class="back-btn">‹</a>

  <div class="hero" style="opacity:0; animation: fadeUp 0.3s ease 0.04s forwards;">
    <div class="hero-title">1я часть ОГЭ</div>
    <div class="hero-sub">задания 6–19 · {{ $taskCount }} заданий</div>
  </div>

  <div class="sec-label">Выбери задание</div>
  <div class="topics-row">
    @foreach($topicIds as $tid)
      <a class="topic-pill {{ $selectedTopic === $tid ? 'active' : '' }}"
         href="{{ url('/tg/tasks-part1?topic=' . (int)$tid) }}">
        {{ (int)$tid }}
      </a>
    @endforeach
  </div>

  <div class="sec-label" style="margin-top:14px;">Задание {{ (int)$selectedTopic }}</div>

  <div class="task-list">
    @forelse($zadaniya as $group)
      <details class="spoiler" {{ $loop->first ? 'open' : '' }}>
        <summary>{{ $group['title'] }} <span style="font-size:11px;color:var(--muted);font-weight:400;">({{ count($group['tasks']) }})</span></summary>
        <div class="spoiler-body">
          @foreach($group['tasks'] as $task)
            <div class="task-item">
              @php
                $svg = is_string($task['svg'] ?? null) ? $task['svg'] : '';
                $image = is_string($task['image'] ?? null) ? $task['image'] : '';
              @endphp

              @if($svg !== '')
                <div style="margin-bottom:10px; border:1px solid var(--border); border-radius:10px; overflow-x:auto; -webkit-overflow-scrolling:touch; background:#0a1628; padding:8px;">
                  <div style="min-width:600px;">{!! $svg !!}</div>
                </div>
              @elseif($image !== '')
                @if(\Illuminate\Support\Str::startsWith($image, '<svg'))
                  <div style="margin-bottom:10px; border:1px solid var(--border); border-radius:10px; overflow-x:auto; -webkit-overflow-scrolling:touch; background:#0a1628; padding:8px;">
                    <div style="min-width:600px;">{!! $image !!}</div>
                  </div>
                @endif
              @endif

              @if(!empty($task['question']))
                <div class="task-item-text" style="margin-bottom:6px; color:var(--muted); font-size:12px;">{{ $task['question'] }}</div>
              @endif

              @if($task['text'] !== '')
                <div class="task-item-text">{!! nl2br(e($task['text'])) !!}</div>
              @elseif(!empty($task['expression']))
                <div class="task-item-text" style="font-size:15px;">$${{ $task['expression'] }}$$</div>
              @endif

              @if(!empty($task['options']) && is_array($task['options']))
                <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:6px;">
                  @foreach($task['options'] as $opt)
                    <span style="padding:4px 10px; border:1px solid var(--border); border-radius:8px; font-size:12px; color:var(--muted);">{{ is_array($opt) ? ($opt['label'] ?? $opt['text'] ?? json_encode($opt)) : $opt }}</span>
                  @endforeach
                </div>
              @endif

              @if(!empty($task['id']))
                <div class="task-item-meta">#{{ $task['id'] }}</div>
              @endif
            </div>
          @endforeach
        </div>
      </details>
    @empty
      <div class="task-item">
        <div class="task-item-text">Для этого задания пока нет заданий в статусе production.</div>
      </div>
    @endforelse
  </div>
</div>
@endsection
