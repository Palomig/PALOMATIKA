{{-- Карточка задачи вступительной работы. Ожидает $task (подготовленный сервисом). --}}
@php
    $ballWord = function (int $n): string {
        $n = abs($n) % 100;
        if ($n >= 11 && $n <= 14) return 'баллов';
        $d = $n % 10;
        return $d === 1 ? 'балл' : (($d >= 2 && $d <= 4) ? 'балла' : 'баллов');
    };
@endphp
<div class="e10-task" data-number="{{ $task['number'] }}">
  <div class="e10-task-head">
    <span class="e10-num">№{{ $task['number'] }}</span>
    <div class="e10-task-titles">
      <div class="e10-task-title">{{ $task['title'] }}</div>
      @if(!empty($task['source']))
        <div class="e10-task-source {{ $task['generated'] ? 'is-gen' : '' }}">{{ $task['source'] }}</div>
      @endif
    </div>
  </div>

  @if(!empty($task['stem']))
    <div class="e10-stem">{!! $task['stem']['instruction'] !!}</div>
    @if(!empty($task['stem']['expression']))<div class="e10-expr">{!! $task['stem']['expression'] !!}</div>@endif
  @endif

  @foreach($task['parts'] as $part)
    <div class="e10-part" data-token="{{ $part['token'] }}" data-check="{{ $part['check'] }}">
      <div class="e10-part-head">
        @if(!empty($part['label']))<span class="e10-label">{{ $part['label'] }})</span>@endif
        <span class="e10-points">{{ $part['points'] }} {{ $ballWord((int) $part['points']) }}</span>
      </div>
      @if(!empty($part['instruction']))
        <div class="e10-text">{!! $part['instruction'] !!}</div>
        @if(!empty($part['expression']))<div class="e10-expr">{!! $part['expression'] !!}</div>@endif
      @else
        <div class="e10-text">{!! $part['text'] !!}</div>
      @endif
      <div class="e10-answer">
        @if($part['check'] !== 'display')
          @php
            // Панель символов: корень для числовых ответов, знаки сравнения —
            // для условий на параметр. Для «да/нет» она не нужна.
            $pad = match ($part['check']) {
                'number_set' => 'full',
                'number' => 'roots',
                'param_condition' => 'compare',
                default => null,
            };
          @endphp
          <div class="e10-input-row" data-mathpad-anchor>
            <input type="text" class="e10-input" placeholder="Ответ" autocomplete="off" autocapitalize="off" spellcheck="false" inputmode="text"
                   @if($pad) data-mathpad="{{ $pad }}" @endif>
            <button type="button" class="e10-btn e10-check">Проверить</button>
          </div>
          @if($part['check'] === 'number_set')
            <div class="e10-hint">Несколько ответов — через «;» или пробел. Корень: пишите √6 или sqrt6.</div>
          @elseif($part['check'] === 'param_condition')
            <div class="e10-hint">Например: <code>b ≠ 1</code> или <code>b &gt; 0, b ≠ 1</code>.</div>
          @endif
        @endif
        <button type="button" class="e10-btn-ghost e10-reveal">{{ $part['check'] === 'display' ? 'Показать ответ и решение' : 'Показать ответ' }}</button>
        <div class="e10-result" hidden></div>
      </div>
    </div>
  @endforeach
</div>
