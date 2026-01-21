{{--
    Тип: geometry (темы 15, 16, 17)
    Геометрические задачи
--}}

@php
    $tasks = $zadanie['tasks'] ?? [];
@endphp

<div class="space-y-6">
    @foreach($tasks as $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br><code>" . substr($task['text'] ?? '', 0, 100) . "...</code>";
            $hasImage = !empty($task['image']);
        @endphp

        <div class="bg-slate-800/70 rounded-xl border border-slate-700 overflow-hidden task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

            <div class="p-5 {{ $hasImage ? 'lg:flex lg:gap-6' : '' }}">
                {{-- Изображение --}}
                @if($hasImage)
                    <div class="lg:w-72 lg:shrink-0 mb-4 lg:mb-0">
                        <div class="bg-slate-900/50 rounded-lg p-3 flex items-center justify-center min-h-[180px]">
                            @if(str_starts_with($task['image'], '<svg'))
                                {!! $task['image'] !!}
                            @else
                                <img src="{{ asset('images/tasks/' . $topicId . '/' . $task['image']) }}"
                                     alt="Геометрия {{ $task['id'] }}"
                                     class="max-w-full max-h-full object-contain">
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Текст задачи --}}
                <div class="flex-1">
                    <div class="flex items-start gap-3">
                        <span class="text-red-400 font-bold text-lg shrink-0">{{ $task['id'] }})</span>
                        <p class="text-slate-200 leading-relaxed">{!! $task['text'] ?? '' !!}</p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
