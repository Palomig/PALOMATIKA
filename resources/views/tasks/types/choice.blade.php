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

    // Функция для определения, является ли опция интервалом
    if (!function_exists('isIntervalOption')) {
        function isIntervalOption($option) {
            // Паттерны интервалов: (-∞; a], [a; b], (a; +∞), etc.
            $option = trim($option);
            // Проверяем наличие точки с запятой и скобок
            if (preg_match('/^[\(\[].*?;.*?[\)\]]$/', $option)) {
                return true;
            }
            // Объединение интервалов: (-∞; a] ∪ [b; +∞)
            if (str_contains($option, '∪')) {
                return true;
            }
            return false;
        }
    }

    // Проверяем, все ли опции являются интервалами (для темы 13)
    if (!function_exists('allOptionsAreIntervals')) {
        function allOptionsAreIntervals($options) {
            if (empty($options)) return false;
            foreach ($options as $opt) {
                if (!isIntervalOption($opt) && $opt !== 'нет решений' && $opt !== '(-∞; +∞)') {
                    return false;
                }
            }
            return true;
        }
    }
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
                @php
                    // svg_type может быть на уровне task или zadanie
                    $taskSvgType = $task['svg_type'] ?? $svgType ?? 'single_point';
                @endphp
                @if(!empty($taskPoints) || !empty($taskSvgType) || isset($task['point_value']))
                    @include('tasks.partials.number-line', [
                        'points' => $taskPoints,
                        'svgType' => $taskSvgType,
                        'task' => $task,
                    ])
                @endif

                {{-- Варианты ответа --}}
                @if(!empty($taskOptions))
                    @php
                        $useIntervalSvg = allOptionsAreIntervals($taskOptions);
                    @endphp

                    @if($useIntervalSvg)
                        {{-- SVG интервалы для темы 13 --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                            @foreach($taskOptions as $i => $option)
                                <div class="bg-slate-700/50 rounded-lg p-3 hover:bg-slate-700 cursor-pointer transition border border-slate-600">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-cyan-400 font-bold">{{ $i + 1 }})</span>
                                        @if($option === 'нет решений')
                                            <span class="text-slate-400 italic">нет решений</span>
                                        @elseif($option === '(-∞; +∞)')
                                            <span class="text-slate-300">все числа</span>
                                        @endif
                                    </div>
                                    @if($option !== 'нет решений' && $option !== '(-∞; +∞)')
                                        @if(str_contains($option, '∪'))
                                            {{-- Объединение интервалов --}}
                                            @php
                                                $parts = explode('∪', $option);
                                            @endphp
                                            <div class="space-y-2">
                                                @foreach($parts as $part)
                                                    @include('tasks.partials.interval-line', ['interval' => trim($part), 'index' => $i + 1])
                                                @endforeach
                                            </div>
                                        @else
                                            @include('tasks.partials.interval-line', ['interval' => $option, 'index' => $i + 1])
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Обычные текстовые варианты --}}
                        <div class="flex flex-wrap gap-3 mt-3">
                            @foreach($taskOptions as $i => $option)
                                <span class="bg-slate-700/70 text-slate-300 px-4 py-2 rounded-lg hover:bg-slate-600 cursor-pointer transition">
                                    @if(str_contains($option, '\\'))
                                        {{ $i + 1 }}) ${{ $option }}$
                                    @else
                                        {{ $i + 1 }}) {{ $option }}
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
@endif
