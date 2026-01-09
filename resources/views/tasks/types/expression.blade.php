{{--
    Тип: expression (темы 06, 08, 09)
    Математические выражения для вычисления
--}}

@php
    $tasks = $zadanie['tasks'] ?? [];
    $hasLongExpressions = collect($tasks)->contains(fn($t) => strlen($t['expression'] ?? '') > 50);
    $hasDenominator = isset($tasks[0]['denominator']);
@endphp

@if($hasDenominator)
    {{-- Задание со знаменателем - формат параграфа --}}
    <div class="space-y-3">
        @foreach($tasks as $task)
            @php
                $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
                $taskInfo = "Блок {$block['number']} ({$block['title']}), Задание {$zadanie['number']}, Задача {$task['id']}<br>Выражение: <code>{$task['expression']}</code>";
            @endphp
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 task-review-item relative"
                 data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">
                <span class="text-blue-400 font-bold">{{ $task['id'] }})</span>
                <span class="text-slate-200 ml-2">
                    Представьте выражение ${{ $task['expression'] }}$ в виде дроби со знаменателем {{ $task['denominator'] }}.
                    В ответ запишите числитель полученной дроби.
                </span>
            </div>
        @endforeach
    </div>
@elseif($hasLongExpressions)
    {{-- Длинные выражения - по 2 в ряд --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($tasks as $task)
            @php
                $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
                $taskInfo = "Блок {$block['number']} ({$block['title']}), Задание {$zadanie['number']}, Задача {$task['id']}<br>Выражение: <code>" . substr($task['expression'], 0, 80) . "...</code>";
            @endphp
            <div class="bg-slate-800/70 rounded-lg p-4 border border-slate-700 hover:border-slate-600 transition-colors task-review-item relative"
                 data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">
                <span class="text-blue-400 font-bold">{{ $task['id'] }})</span>
                <span class="text-slate-200 ml-2 math-serif">${{ $task['expression'] }}$</span>
            </div>
        @endforeach
    </div>
@else
    {{-- Короткие выражения - сетка 4 --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($tasks as $task)
            @php
                $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
                $taskInfo = "Блок {$block['number']} ({$block['title']}), Задание {$zadanie['number']}, Задача {$task['id']}<br>Выражение: <code>{$task['expression']}</code>";
            @endphp
            <div class="bg-slate-800/70 rounded-lg p-3 border border-slate-700 hover:border-slate-600 transition-colors task-review-item relative"
                 data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">
                <span class="text-blue-400 font-bold">{{ $task['id'] }})</span>
                <span class="text-slate-200 ml-2 math-serif">${{ $task['expression'] }}$</span>
            </div>
        @endforeach
    </div>
@endif
