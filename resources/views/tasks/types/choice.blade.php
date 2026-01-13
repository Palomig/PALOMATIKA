{{--
    Тип: choice, simple_choice, fraction_choice, interval_choice, between_fractions (тема 07, 13)
    Выбор из вариантов, часто с координатной прямой
--}}

@php
    $type = $zadanie['type'] ?? 'choice';
    $svgType = $zadanie['svg_type'] ?? null;
    $points = $zadanie['points'] ?? [];
    $options = $zadanie['options'] ?? [];
    $tasks = $zadanie['tasks'] ?? [];
@endphp

{{-- Если есть общие points на уровне задания --}}
@if($svgType && !empty($points))
    <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 mb-4">
        @include('tasks.partials.number-line', [
            'points' => $points,
            'svgType' => $svgType,
            'task' => $zadanie,
        ])

        @if(!empty($options))
            <div class="flex flex-wrap gap-4 mt-4">
                @foreach($options as $i => $option)
                    <span class="bg-slate-700/70 text-slate-300 px-4 py-2 rounded-lg">
                        {{ $i + 1 }}) ${{ $option }}$
                    </span>
                @endforeach
            </div>
        @endif
    </div>
@endif

{{-- Задачи --}}
@if(!empty($tasks))
    <div class="space-y-4">
        @foreach($tasks as $task)
            @php
                $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
                $taskText = $task['expression'] ?? $task['text'] ?? '';
                $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br><code>" . substr($taskText, 0, 80) . "</code>";
                $taskPoints = $task['points'] ?? [];
                $taskOptions = $task['options'] ?? $options;
            @endphp

            <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 task-review-item relative"
                 data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

                <div class="flex items-start gap-3 mb-3">
                    <span class="text-cyan-400 font-bold text-lg">{{ $task['id'] }})</span>
                    @if(isset($task['expression']))
                        <span class="text-slate-200 math-serif text-lg">${{ $task['expression'] }}$</span>
                    @elseif(isset($task['left']) && isset($task['right']))
                        <span class="text-slate-200">
                            Какое число заключено между ${{ $task['left'] }}$ и ${{ $task['right'] }}$?
                        </span>
                    @endif
                </div>

                {{-- SVG для отдельной задачи --}}
                @if(!empty($taskPoints) || !empty($task['svg_type']) || isset($task['point_value']))
                    @include('tasks.partials.number-line', [
                        'points' => $taskPoints,
                        'svgType' => $task['svg_type'] ?? 'single_point',
                        'task' => $task,
                    ])
                @endif

                {{-- Варианты ответа --}}
                @if(!empty($taskOptions))
                    <div class="flex flex-wrap gap-3 mt-3">
                        @foreach($taskOptions as $i => $option)
                            <span class="bg-slate-700/70 text-slate-300 px-4 py-2 rounded-lg hover:bg-slate-600 cursor-pointer transition">
                                {{ $i + 1 }}) {{ $option }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
