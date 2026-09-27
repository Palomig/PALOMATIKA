{{-- Карточка примера банка «Скиллы»: условие уже в $…$, ответ виден всегда —
     экран учительский, прятать эталон не от кого. Ждёт $task. --}}
<div class="task-item">
  <div class="task-item-text">{!! $task['expression'] !!}</div>

  @if($task['answer'] !== '')
    <div class="answer-row">
      <span class="answer-label">Ответ:</span>
      <span class="answer-value">{{ $task['answer'] }}</span>
    </div>
  @endif

  @if(!empty($task['id']))
    <div class="task-item-meta">#{{ $task['id'] }}</div>
  @endif
</div>
