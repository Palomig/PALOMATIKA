{{-- Одна задача второй части: условие, чертёж и ответ (учителю сразу, ученику — после проверки). --}}
<div class="task-item">
  @if(!empty($task['image']))
    <img src="{{ asset('images/tasks/' . $selectedTopic . '/' . ltrim($task['image'], '/')) }}"
         alt="" style="display:block;max-width:100%;height:auto;margin-bottom:10px;border:1px solid var(--border);border-radius:10px;background:#fff;padding:4px;" loading="lazy">
  @endif
  @if(!empty($task['drawing']))
    <div class="fipi-drawing">{!! $task['drawing'] !!}</div>
  @endif

  @if(!empty($task['html']))
    {{-- Банк ФИПИ: разметка уже готова (KaTeX + инлайновые SVG) --}}
    <div class="task-item-text fipi-html">{!! $task['html'] !!}</div>
  @else
    <div class="task-item-text">{{ $task['text'] }}</div>
  @endif
  @if(!empty($task['answer']))
    @if($isTeacher)
      {{-- Учителю ответ нужен как справка — показываем сразу. --}}
      <div class="answer-row">
        <span class="answer-label">Ответ:</span>
        <span class="answer-value">{{ $task['answer'] }}</span>
      </div>
    @else
      <div class="p2-answer" data-zadanie="{{ $group['number'] }}" data-task="{{ $task['id'] }}">
        <div class="p2-input-row" data-mathpad-anchor>
          <input type="text" class="p2-input" placeholder="Твой ответ"
                 autocomplete="off" autocapitalize="off" spellcheck="false" inputmode="text"
                 @if(in_array($selectedTopic, ['20', '23'], true))
                   data-mathpad="{{ $selectedTopic === '20' ? 'full' : 'roots' }}"
                 @endif>
          <button type="button" class="p2-btn p2-check">Проверить</button>
        </div>
        @if($selectedTopic === '20')
          <div class="p2-hint">Несколько корней — через «;». Промежуток — со скобками: (1; 1+√2).</div>
        @elseif($selectedTopic === '23')
          <div class="p2-hint">Корень пиши как √6 или sqrt(6). Ответ нужен точный, не десятичный.</div>
        @endif
        <div class="p2-result" hidden></div>
        <button type="button" class="p2-reveal">Показать ответ</button>
      </div>
    @endif
  @endif
  @if(!empty($task['id']))
    <div class="task-item-meta">{{ $task['id'] }}</div>
  @endif
</div>
