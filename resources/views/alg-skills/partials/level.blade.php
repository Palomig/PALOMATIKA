<section class="panel {{ $levelClass[$level['id']] ?? '' }}">
  <div class="level-head">
    <div>
      <h2>{{ $level['title'] }}</h2>
      <div class="muted">{{ $level['description'] }}</div>
    </div>
    <div class="level-badge">{{ $levelName[$level['id']] ?? $level['title'] }}</div>
  </div>
  <div class="taskgrid">
    @foreach ($tasks as $task)
    <div class="task">
      <button class="flag" title="Пометить">⚑</button>
      <div class="expr-line">
        <span class="num">{{ $task['id'] }})</span>
        <span class="expr">{!! \App\Services\AlgTaskDataService::mathText($task['expression'] ?? '') !!}</span>
      </div>
      <div class="answer">
        <span>Ответ:</span>
        <b>@if(str_contains((string)($task['answer'] ?? ''), '\\')){!! \App\Services\AlgTaskDataService::mathText((string)$task['answer']) !!}@else{{ $task['answer'] ?? '' }}@endif</b>
        <span class="muted">[AI]</span>
      </div>
      <div class="status"><span></span>PROD</div>
    </div>
    @endforeach
  </div>
</section>
