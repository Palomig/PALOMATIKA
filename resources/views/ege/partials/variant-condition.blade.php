{{--
    Условие задачи в печатном варианте ЕГЭ.

    У банка ФИПИ условие целиком лежит в `html` — размеченным, с формулами в
    KaTeX и растровыми чертежами. Шаблон варианта печатал только `text` и
    `expression`, которых у этого банка нет вовсе, поэтому в варианте
    оставалась одна подпись подтипа и ни одного чертежа. Прежние поля
    сохранены: варианты умеют собираться и из старых банков.
--}}
@if(!empty($task['html']))
    <div class="fipi-condition text-gray-200 leading-relaxed">{!! $task['html'] !!}</div>
    @if(!empty($task['options']))
        <ol class="fipi-options mt-3 space-y-1 text-gray-200">
            @foreach($task['options'] as $option)
                <li class="flex gap-2">
                    <span class="text-gray-500 shrink-0">{{ $option['n'] ?? '' }})</span>
                    <span class="min-w-0">{!! $option['html'] ?? '' !!}</span>
                </li>
            @endforeach
        </ol>
    @endif
@else
    @if(!empty($task['text']))
        <p class="text-gray-200 leading-relaxed latex-content">{{ $task['text'] }}</p>
    @endif
    @if(!empty($task['expression']))
        <p class="text-gray-200 text-lg mt-2 latex-content">${{ $task['expression'] }}$</p>
    @endif
@endif

@once
    @include('tasks.partials.fipi-styles')
@endonce
