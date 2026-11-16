@forelse($previewTasks as $task)
  <section class="homework-preview-task">
    <div class="homework-preview-number">Задание {{ $task['number'] }}</div>
    <div class="homework-preview-condition">
      @if($task['instruction'])<div>{{ $task['instruction'] }}</div>@endif
      @if($task['text_html'])<div>{!! $task['text_html'] !!}</div>@endif
      @if($task['expression_html'])<div>{!! $task['expression_html'] !!}</div>@endif
      @if($task['svg_html'])<div>{!! $task['svg_html'] !!}</div>@endif
      @if($task['image_url'])<div><img src="{{ $task['image_url'] }}" loading="lazy" alt="Иллюстрация к заданию {{ $task['number'] }}"></div>@endif
      @foreach($task['statements'] as $statement)
        <div class="homework-preview-option"><b>{{ $statement['label'] }})</b><div>{!! $statement['html'] !!}</div></div>
      @endforeach
      @foreach($task['options'] as $option)
        <div class="homework-preview-option"><b>{{ $option['label'] }})</b><div>{!! $option['html'] !!}</div></div>
      @endforeach
      @if(!$task['text_html'] && !$task['expression_html'] && !$task['svg_html'] && !$task['image_url'] && !$task['options'] && !$task['statements'])
        <p>Сохранённое условие задания недоступно.</p>
      @endif
    </div>
  </section>
@empty
  <p>Сохранённые задания этой домашки недоступны.</p>
@endforelse
