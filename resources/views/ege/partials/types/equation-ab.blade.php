{{--
    Тип: equation_ab (ЕГЭ уравнения с пунктами а) и б))
    а) Решите уравнение
    б) Укажите корни, принадлежащие промежутку
--}}

@php
    $tasks = $zadanie['tasks'] ?? [];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @foreach($tasks as $task)
        @php
            $taskKey = "ege_topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $taskText = $task['text'] ?? '';
            $interval = $task['interval'] ?? '';
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br><code>" . htmlspecialchars(substr($taskText, 0, 80)) . "</code>";
        @endphp

        <div class="bg-dark-100 rounded-xl p-5 border border-dark-400/50 task-review-item relative hover:border-accent/30 transition-colors"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">
            <div class="flex items-start gap-3">
                <span class="text-accent-light font-bold shrink-0">{{ $task['id'] }})</span>
                <div class="flex flex-col gap-2">
                    {{-- Уравнение (пункт а) --}}
                    <span class="text-gray-200 math-serif text-lg">{!! $taskText !!}</span>

                    {{-- Промежуток (пункт б) --}}
                    @if(!empty($interval))
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-accent text-sm font-medium">б)</span>
                            <span class="text-gray-400 math-serif">{!! $interval !!}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
