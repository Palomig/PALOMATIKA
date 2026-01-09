{{--
    Тип: grid_image, grid_image_with_question (тема 18)
    Фигуры на клетчатой бумаге
--}}

@php
    $type = $zadanie['type'] ?? 'grid_image';
    $tasks = $zadanie['tasks'] ?? [];
@endphp

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @foreach($tasks as $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br>Изображение: {$task['image']}";
            $hasQuestion = !empty($task['question']);
        @endphp

        <div class="bg-slate-800/70 rounded-xl border border-slate-700 overflow-hidden task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

            {{-- Номер задачи --}}
            <div class="bg-slate-700/50 px-3 py-1.5 flex items-center gap-2">
                <span class="text-fuchsia-400 font-bold">{{ $task['id'] }})</span>
                @if($hasQuestion)
                    <span class="text-slate-400 text-sm truncate">{{ $task['question'] }}</span>
                @endif
            </div>

            {{-- Изображение --}}
            <div class="p-3 bg-white">
                @if(str_starts_with($task['image'] ?? '', '<svg'))
                    {!! $task['image'] !!}
                @else
                    <img src="{{ asset('images/tasks/18/' . $task['image']) }}"
                         alt="Клетчатая бумага {{ $task['id'] }}"
                         class="w-full h-auto">
                @endif
            </div>
        </div>
    @endforeach
</div>
