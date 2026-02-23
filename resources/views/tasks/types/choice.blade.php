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
    $optionsRenderMode = $zadanie['options_render_mode'] ?? null;
    $isVariant = $isVariant ?? false;
    $showTaskAnswer = !$isVariant;
    $answerResolver = app(\App\Services\TaskAnswerResolver::class);
    $optionRenderPolicy = app(\App\Services\OptionRenderModePolicy::class);
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
        @foreach($tasks as $taskRaw)
                @php
                    $task = is_array($taskRaw) ? $taskRaw : [];
                $taskId = $task['id'] ?? ($loop->index + 1);
                $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$taskId}";
                $taskText = $task['expression'] ?? $task['text'] ?? '';
                $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$taskId}<br><code>" . substr((string) $taskText, 0, 80) . "</code>";
                $taskPoints = $task['points'] ?? [];
                    $taskOptions = $task['options'] ?? $options;
                    $taskGraphOptions = is_array($task['graph_options'] ?? null) ? $task['graph_options'] : [];
                    $taskOptionsRenderMode = $task['options_render_mode'] ?? $optionsRenderMode ?? null;
                @endphp

            <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 task-review-item relative"
                 data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

                <div class="flex items-start gap-3 mb-3">
                    @if(!$isVariant)
                        <span class="text-cyan-400 font-bold text-lg">{{ $taskId }})</span>
                    @endif
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
                    // НЕ ставим дефолт, чтобы не показывать number-line когда он не нужен
                    $taskSvgType = $task['svg_type'] ?? $svgType ?? null;
                @endphp
                @if(!empty($taskPoints) || ($taskSvgType !== null) || isset($task['point_value']))
                    @include('tasks.partials.number-line', [
                        'points' => $taskPoints,
                        'svgType' => $taskSvgType,
                        'task' => $task,
                    ])
                @endif

                @if(empty($taskGraphOptions) && !empty($task['svg']) && is_string($task['svg']))
                    <div class="mt-4 mb-2">
                        <div class="bg-white rounded-lg p-2 overflow-hidden">
                            {!! $task['svg'] !!}
                        </div>
                    </div>
                @elseif(!empty($task['image']))
                    @php
                        $imageName = (string) $task['image'];
                    @endphp
                    <div class="mt-4 mb-2">
                        @if(\Illuminate\Support\Str::startsWith($imageName, '<svg'))
                            <div class="bg-white rounded-lg p-2 overflow-hidden">
                                {!! $imageName !!}
                            </div>
                        @else
                            <img src="{{ asset('images/tasks/' . $topicId . '/' . $imageName) }}"
                                 alt="Иллюстрация к заданию {{ $taskId }}"
                                 class="max-w-full h-auto rounded-lg bg-white p-1 border border-slate-600"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='{{ asset('images/placeholder.svg') }}';">
                        @endif
                    </div>
                @endif

                {{-- Варианты ответа --}}
                @if(!empty($taskGraphOptions))
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4" data-graph-options="topic13-z10">
                        @foreach($taskGraphOptions as $graphOption)
                            @php
                                $graphOptionIndex = (int) ($graphOption['index'] ?? ($loop->index + 1));
                                $graphOptionSvg = is_string($graphOption['svg'] ?? null) ? $graphOption['svg'] : '';
                                $graphOptionText = (string) ($graphOption['text'] ?? '');
                            @endphp
                            <div class="bg-slate-700/50 rounded-lg p-3 hover:bg-slate-700 cursor-pointer transition border border-slate-600"
                                 data-z10-option-panel="{{ $graphOptionIndex }}"
                                 data-option-index="{{ $graphOptionIndex }}"
                                 @if($graphOptionText !== '') data-option-text="{{ $graphOptionText }}" @endif>
                                <div class="flex items-start gap-2">
                                    <span class="text-cyan-400 font-bold shrink-0">{{ $graphOptionIndex }})</span>
                                    <div class="flex-1 bg-white rounded p-1 overflow-hidden">
                                        {!! $graphOptionSvg !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif(!empty($taskOptions))
                    @php
                        $useIntervalSvg = $optionRenderPolicy->shouldRenderIntervalSvg(
                            (string) ($topicId ?? ''),
                            $taskOptionsRenderMode,
                            $optionsRenderMode,
                            is_array($taskOptions) ? $taskOptions : []
                        );
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
                                        @if(str_contains((string) $option, '∪'))
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
                                    @if(str_contains((string) $option, '\\'))
                                        {{ $i + 1 }}) ${{ $option }}$
                                    @else
                                        {{ $i + 1 }}) {{ $option }}
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @endif
                @endif

                @include('tasks.partials.task-answer', [
                    'showTaskAnswer' => $showTaskAnswer,
                    'taskAnswer' => $answerResolver->resolveFromTaskAndZadanie($zadanie, $task),
                ])
            </div>
        @endforeach
    </div>
@endif
