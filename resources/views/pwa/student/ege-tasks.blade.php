@extends('layouts.pwa')
@section('title', 'База заданий ЕГЭ — palomatika')

@push('katex')
@include('partials.head-katex')
@endpush

@push('styles')
<style>
  .topics-row {
    display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px;
    opacity: 0; animation: fadeUp 0.3s ease 0.08s forwards;
    scrollbar-width: none;
  }
  .topics-row::-webkit-scrollbar { display: none; }
  .topic-pill {
    min-width: 42px; text-align: center;
    padding: 8px 12px; border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text); text-decoration: none;
    font-family: var(--display); font-size: 14px;
    flex-shrink: 0;
  }
  .topic-pill.active {
    border-color: var(--accent-bd);
    background: var(--accent-bg);
    color: var(--accent);
  }
  .spoiler {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; overflow: hidden;
  }
  .spoiler summary {
    list-style: none; cursor: pointer;
    padding: 12px 14px;
    font-family: var(--display); font-size: 13px; color: var(--text);
    display: flex; justify-content: space-between; align-items: center; gap: 8px;
  }
  .spoiler summary::-webkit-details-marker { display: none; }
  .spoiler summary::after { content: '▾'; color: var(--muted); transition: transform .15s ease; flex-shrink: 0; }
  .spoiler[open] summary::after { transform: rotate(180deg); }
  .spoiler-body { padding: 0 10px 10px; display: flex; flex-direction: column; gap: 8px; }
  .task-list {
    display: flex; flex-direction: column; gap: 8px;
    opacity: 0; animation: fadeUp 0.3s ease 0.12s forwards;
  }
  .task-item { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px 14px; }
  .task-item-text { font-size: 13px; line-height: 1.5; color: var(--text); }
  .task-item-meta { margin-top: 6px; font-size: 10px; color: var(--muted); font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
  .answer-row { margin-top: 8px; display: flex; align-items: center; gap: 8px; }
  .answer-label { font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); white-space: nowrap; }
  .answer-value { font-family: var(--display); font-size: 14px; color: var(--green); }
  .empty-state { text-align: center; padding: 40px 20px; color: var(--muted); font-size: 14px; font-weight: 600; line-height: 1.6; }

/* Растры банка ФИПИ: чёрным по прозрачному, на тёмном экране нужна
     подложка. Класс проставляет экспорт по натуральной высоте растра. */
  .fipi-condition img { max-width: 100%; height: auto; }
  .fipi-condition img.fipi-figure {
    display: block; background: #fff; border-radius: 10px;
    padding: 8px; margin: 10px auto;
  }
  .fipi-condition img.fipi-inline {
    display: inline-block; background: #fff; border-radius: 3px;
    padding: 0 2px; height: 1.35em; width: auto; vertical-align: -0.28em;
  }
  .fipi-condition p { margin: 0 0 .5rem; }
  .fipi-condition p:last-child { margin-bottom: 0; }
</style>
@endpush

@section('body')
<div class="page task-render-scope">

  <div style="display:flex;align-items:center;gap:12px;opacity:0;animation:fadeDown .3s ease forwards;">
    <a href="{{ route('pwa.student.ege.home') }}" class="back-btn">‹</a>
    <div style="flex:1;">
      <div style="font-family:var(--display);font-size:16px;color:var(--text);">База заданий ЕГЭ</div>
      <div style="font-size:11px;color:var(--muted);font-weight:700;margin-top:1px;">Профиль · {{ $maxTopic }} заданий</div>
    </div>
    <div style="background:var(--accent-bg);border:1px solid var(--accent-bd);color:var(--accent);
                font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;letter-spacing:.08em;flex-shrink:0;">
      ЕГЭ · ПРОФИЛЬ
    </div>
  </div>

  {{-- Topic pills --}}
  <div class="sec-label">Выбери задание</div>
  <div class="topics-row">
    @foreach($topicIds as $tid)
      <a class="topic-pill {{ $selected === $tid ? 'active' : '' }}"
         href="{{ route('pwa.student.ege.tasks', ['topic' => (int)$tid]) }}">
        {{ (int)$tid }}
      </a>
    @endforeach
  </div>

  {{-- Selected topic header --}}
  <div style="display:flex;align-items:center;justify-content:space-between;margin-top:4px;">
    <div class="sec-label">Задание {{ (int)$selected }}</div>
    @if($taskCount > 0)
      <div style="font-size:11px;color:var(--muted);font-weight:700;">{{ $taskCount }} {{ $taskCount === 1 ? 'задача' : ($taskCount < 5 ? 'задачи' : 'задач') }}</div>
    @endif
  </div>

  {{-- Task list --}}
  <div class="task-list">
    @forelse($zadaniya as $group)
      <details class="spoiler">
        <summary>
          {{ $group['title'] }}
          <span style="font-size:11px;color:var(--muted);font-weight:400;">({{ count($group['tasks']) }})</span>
        </summary>
        <div class="spoiler-body">
          @foreach($group['tasks'] as $task)
            <div class="task-item">
              @php
                $svg   = is_string($task['svg']   ?? null) ? $task['svg']   : '';
                $image = is_string($task['image']  ?? null) ? $task['image'] : '';
                $svgMarkup = $svg !== '' ? $svg : (\Illuminate\Support\Str::startsWith($image, '<svg') ? $image : '');
              @endphp

              @if($svgMarkup !== '')
                <div style="margin-bottom:10px;border:1px solid var(--border);border-radius:10px;background:#0a1628;padding:8px;">
                  {!! $svgMarkup !!}
                </div>
              @elseif($image !== '' && !str_starts_with($image, '<svg'))
                <img src="{{ $image }}" style="max-width:100%;border-radius:10px;margin-bottom:10px;" loading="lazy">
              @endif

              @if(!empty($task['question']))
                <div class="task-item-text" style="color:var(--muted);font-size:12px;margin-bottom:6px;">{{ $task['question'] }}</div>
              @endif

              {{-- Условие банка ФИПИ приходит готовой разметкой с формулами
                   в $…$ — выводим как есть, KaTeX разберёт её на месте. --}}
              @if(!empty($task['html']))
                <div class="task-item-text fipi-condition">{!! $task['html'] !!}</div>
              @elseif($task['text'] !== '')
                <div class="task-item-text">{!! nl2br(e($task['text'])) !!}</div>
              @elseif(!empty($task['expression']))
                <div class="task-item-text" style="font-size:15px;">$${{ $task['expression'] }}$$</div>
              @endif

              @if(!empty($task['options']) && is_array($task['options']))
                <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">
                  @foreach($task['options'] as $opt)
                    <span style="padding:4px 10px;border:1px solid var(--border);border-radius:8px;font-size:12px;color:var(--muted);">
                      @if(is_array($opt)){{ $opt['text'] ?? $opt['value'] ?? '' }}@else{{ $opt }}@endif
                    </span>
                  @endforeach
                </div>
              @endif

              @if(!empty($task['answer']))
                <div class="answer-row">
                  <span class="answer-label">Ответ:</span>
                  <span class="answer-value">{{ is_array($task['answer']) ? implode(', ', $task['answer']) : $task['answer'] }}</span>
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
      <div class="empty-state">
        Задания для темы {{ (int)$selected }} ещё не добавлены.<br>
        <span style="font-size:12px;color:var(--muted2);">Заполним скоро!</span>
      </div>
    @endforelse
  </div>

</div>
@endsection
