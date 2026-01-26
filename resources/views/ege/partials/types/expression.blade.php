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

        <div class="bg-dark-100 rounded-xl p-5 border border-dark-400/50 task-review-item relative hover:border-accent/30 transition-colors"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">
            <div class="flex items-start gap-3">
                <span class="text-accent-light font-bold shrink-0">{{ $task['id'] }})</span>
                <span class="text-gray-200 math-serif text-lg">${!! $task['expression'] ?? '' !!}$</span>
            </div>
        </div>
    @endforeach
</div>
