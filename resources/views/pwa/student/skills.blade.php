@extends('layouts.pwa')
@section('title', 'Скиллы — palomatika')

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
    padding: 8px 12px; border-radius: 10px; white-space: nowrap; flex-shrink: 0;
    border: 1px solid var(--border); background: var(--surface);
    color: var(--text); text-decoration: none;
    font-family: var(--display); font-size: 14px;
  }
  .topic-pill.active { border-color: var(--purple-bd); background: var(--purple-bg); color: var(--purple); }
  .teacher-note {
    margin-top: 10px; padding: 10px 12px; border-radius: 10px;
    background: var(--purple-bg); border: 1px solid var(--purple-bd);
    color: var(--purple); font-size: 12px; font-weight: 700; line-height: 1.4;
  }
  .spoiler { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
  .spoiler summary {
    list-style: none; cursor: pointer; padding: 12px 14px;
    font-family: var(--display); font-size: 13px; color: var(--text);
    display: flex; justify-content: space-between; align-items: center; gap: 8px;
  }
  .spoiler summary::-webkit-details-marker { display: none; }
  .spoiler summary::after { content: '▾'; color: var(--muted); transition: transform .15s ease; flex-shrink: 0; }
  .spoiler[open] summary::after { transform: rotate(180deg); }
  .spoiler-body { padding: 0 10px 10px; display: flex; flex-direction: column; gap: 8px; }
  /* Второй уровень: внутри класса задачи разложены по уровням сложности. */
  .subtype { border: 1px solid var(--border); border-radius: 10px; background: rgba(255,255,255,.02); }
  .subtype summary {
    list-style: none; cursor: pointer; padding: 10px 12px;
    display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text);
  }
  .subtype summary::-webkit-details-marker { display: none; }
  .subtype summary::after { content: '▾'; margin-left: auto; color: var(--muted); transition: transform .15s ease; }
  .subtype[open] summary::after { transform: rotate(180deg); }
  .subtype-count { font-size: 11px; color: var(--muted); font-weight: 400; }
  .subtype-body { padding: 0 8px 8px; display: flex; flex-direction: column; gap: 8px; }
  .task-list { display: flex; flex-direction: column; gap: 8px; opacity: 0; animation: fadeUp 0.3s ease 0.12s forwards; }
  .task-item { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px 14px; }
  .task-item-text { font-size: 15px; line-height: 1.5; color: var(--text); }
  .task-item-meta { margin-top: 6px; font-size: 10px; color: var(--muted); font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
  .answer-row { margin-top: 8px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .answer-label { font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); white-space: nowrap; }
  .answer-value { font-family: ui-monospace, monospace; font-size: 13px; color: var(--green); }
  .empty-state { text-align: center; padding: 40px 20px; color: var(--muted); font-size: 14px; font-weight: 600; line-height: 1.6; }
</style>
@endpush

@section('body')
<div class="page task-render-scope">

  <div style="display:flex;align-items:center;gap:12px;opacity:0;animation:fadeDown .3s ease forwards;">
    <a href="{{ url()->previous() === url()->current() ? '/' : url()->previous() }}" class="back-btn">‹</a>
    <div style="flex:1;">
      <div style="font-family:var(--display);font-size:16px;color:var(--text);">Скиллы</div>
      <div style="font-size:11px;color:var(--muted);font-weight:700;margin-top:1px;">
        {{ $meta['title'] }}@if($taskCount > 0) · {{ $taskCount }} примеров @endif
      </div>
    </div>
    <div style="background:var(--purple-bg);border:1px solid var(--purple-bd);color:var(--purple);
                font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;letter-spacing:.08em;flex-shrink:0;">
      УЧИТЕЛЬ
    </div>
  </div>

  @if(count($topics) > 1)
    <div class="sec-label" style="margin-top:12px;">Навык</div>
    <div class="topics-row">
      @foreach($topics as $topic)
        <a class="topic-pill {{ $selectedTopic === $topic['id'] ? 'active' : '' }}"
           href="{{ route('pwa.student.skills', ['topic' => $topic['id']]) }}">{{ $topic['title'] }}</a>
      @endforeach
    </div>
  @endif

  @if($meta['description'] ?? '')
    <div class="teacher-note">{{ $meta['description'] }}</div>
  @endif

  <div class="task-list" style="margin-top:14px;">
    @forelse($groups as $group)
      <details class="spoiler">
        <summary>
          {{ $group['title'] }}
          <span style="font-size:11px;color:var(--muted);font-weight:400;">({{ count($group['tasks']) }})</span>
        </summary>
        <div class="spoiler-body">
          @if(!empty($group['subtypes']))
            @foreach($group['subtypes'] as $subtype)
              <details class="subtype">
                <summary>
                  {{ $subtype['title'] }}
                  <span class="subtype-count">{{ count($subtype['tasks']) }}</span>
                </summary>
                <div class="subtype-body">
                  @foreach($subtype['tasks'] as $task)
                    @include('pwa.student.partials.skills-task', ['task' => $task, 'instruction' => $group['instruction']])
                  @endforeach
                </div>
              </details>
            @endforeach
          @else
            @foreach($group['tasks'] as $task)
              @include('pwa.student.partials.skills-task', ['task' => $task, 'instruction' => $group['instruction']])
            @endforeach
          @endif
        </div>
      </details>
    @empty
      <div class="empty-state">В этом навыке пока нет примеров.</div>
    @endforelse
  </div>
</div>
@endsection
