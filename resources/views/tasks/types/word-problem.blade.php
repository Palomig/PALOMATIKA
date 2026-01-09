{{--
    Тип: word_problem (темы 10, 12, 14)
    Текстовые задачи
--}}

@php
    $tasks = $zadanie['tasks'] ?? [];
@endphp

<div class="space-y-4">
    @foreach($tasks as $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br><code>" . substr($task['text'] ?? '', 0, 100) . "...</code>";
        @endphp

        <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

            <div class="flex items-start gap-3">
                <span class="text-green-400 font-bold text-lg shrink-0">{{ $task['id'] }})</span>
                <p class="text-slate-200 leading-relaxed">{!! nl2br(e($task['text'] ?? '')) !!}</p>
            </div>
        </div>
    @endforeach
</div>
