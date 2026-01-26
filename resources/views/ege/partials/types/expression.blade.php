{{--
    Тип: expression (ЕГЭ вычисления)
    Вычисление математических выражений
--}}

@php
    $tasks = $zadanie['tasks'] ?? [];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($tasks as $task)
        @php
            $taskKey = "ege_topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br><code>" . ($task['expression'] ?? '') . "</code>";
        @endphp

        <div class="bg-purple-900/30 rounded-xl p-5 border border-purple-800/40 task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">
            <div class="flex items-start gap-3">
                <span class="text-purple-400 font-bold shrink-0">{{ $task['id'] }})</span>
                <span class="text-purple-100/90 math-serif text-lg">${!! $task['expression'] ?? '' !!}$</span>
            </div>
        </div>
    @endforeach
</div>
