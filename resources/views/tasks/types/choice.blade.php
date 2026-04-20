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
    $zadanieSvg = is_string($zadanie['svg'] ?? null) ? trim($zadanie['svg']) : '';
    $hasExplicitZadanieVisual = $zadanieSvg !== '' || !empty($zadanie['image']);
    $hasAnyTaskSvg = false;
    foreach ($tasks as $rawTask) {
        $candidateSvg = is_string($rawTask['svg'] ?? null) ? trim($rawTask['svg']) : '';
        if ($candidateSvg !== '') {
            $hasAnyTaskSvg = true;
            break;
        }
    }
    $optionsRenderMode = $zadanie['options_render_mode'] ?? null;
    $isVariant = $isVariant ?? false;
    $showTaskAnswer = !$isVariant;
    $answerResolver = app(\App\Services\TaskAnswerResolver::class);
    $optionRenderPolicy = app(\App\Services\OptionRenderModePolicy::class);
    $isTopic13 = (string) ($topicId ?? '') === '13';
@endphp

{{-- Если есть общие points на уровне задания --}}
@if($svgType && !empty($points) && !$hasAnyTaskSvg)
    <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 mb-4">
        @if($hasExplicitZadanieVisual && !empty($zadanieSvg))
            <div class="mt-1 mb-2">
                <div class="rounded-lg overflow-hidden border border-slate-700 {{ (string) ($topicId ?? '') === '07' ? 'bg-transparent p-0 border-0' : 'bg-white p-2' }}">
                    {!! $zadanieSvg !!}
                </div>
            </div>
        @else
            @include('tasks.partials.number-line', [
                'points' => $points,
                'svgType' => $svgType,
                'task' => $zadanie,
            ])
        @endif

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
                    $taskSvg = is_string($task['svg'] ?? null) ? trim($task['svg']) : '';
                    $hasTaskImage = !empty($task['image']);
                    $hasExplicitTaskVisual = !empty($taskSvg) || $hasTaskImage;
                    $isTopic13Z11PromptSvg = (string) ($topicId ?? '') === '13'
                        && (int) ($zadanie['number'] ?? 0) === 11
                        && str_contains($taskSvg, 'data-runtime-svg="topic13-b1-z11-prompt-');
                    $isTopic13Z12PromptSvg = (string) ($topicId ?? '') === '13'
                        && (int) ($zadanie['number'] ?? 0) === 12
                        && str_contains($taskSvg, 'data-runtime-svg="topic13-b1-z12-prompt-');
                    $hidePromptSvgForTopic13Z357 = (string) ($topicId ?? '') === '13'
                        && in_array((int) ($zadanie['number'] ?? 0), [3, 5, 7], true);
                @endphp

            <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 task-review-item relative"
                 data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

                <div class="flex items-start gap-3 mb-3">
                    @if(!$isVariant)
                        <span class="text-cyan-400 font-bold text-lg">{{ $taskId }})</span>
                    @endif
                    @if(isset($task['expression']))
                        <span class="text-slate-200 math-serif text-lg">${{ $task['expression'] }}$</span>
                    @elseif(($zadanie['type'] ?? null) === 'decimal_choice' && isset($task['numbers']) && isset($task['target']))
                        <span class="text-slate-200">
                            {{ $task['numbers'] }}. Какой точке соответствует число {{ $task['target'] }}?
                        </span>
                    @elseif(isset($task['left']) && isset($task['right']))
                        <span class="text-slate-200">
                            Какое число заключено между ${{ $task['left'] }}$ и ${{ $task['right'] }}$?
                        </span>
                    @elseif(isset($task['segment']) && in_array(($zadanie['type'] ?? ''), ['segment_choice', 'sqrt_segment', 'negative_segment'], true))
                        <span class="text-slate-200">
                            Какое из данных чисел принадлежит промежутку {{ $task['segment'] }}?
                        </span>
                    @endif
                </div>

                {{-- SVG для отдельной задачи --}}
                @php
                    // svg_type может быть на уровне task или zadanie
                    // НЕ ставим дефолт, чтобы не показывать number-line когда он не нужен
                    $taskSvgType = $task['svg_type'] ?? $svgType ?? null;
                @endphp
                @if(!$hasExplicitTaskVisual && (!empty($taskPoints) || ($taskSvgType !== null) || isset($task['point_value'])))
                    @include('tasks.partials.number-line', [
                        'points' => $taskPoints,
                        'svgType' => $taskSvgType,
                        'task' => $task,
                    ])
                @endif

                @if(!empty($taskSvg) && empty($taskGraphOptions) && !$hidePromptSvgForTopic13Z357)
                    <div class="mt-4 mb-2">
                        <div class="rounded-lg overflow-hidden border border-slate-700 {{ $isTopic13 ? 'topic13-svg-card bg-slate-900/50 p-3' : ((string) ($topicId ?? '') === '07' ? 'bg-transparent p-0 border-0' : 'bg-white p-2') }} {{ $isTopic13Z11PromptSvg ? 'topic13-z11-prompt-svg-size mx-auto w-full max-w-[270px] sm:max-w-[330px] md:max-w-[360px] [&>svg]:w-full [&>svg]:h-auto' : '' }} {{ $isTopic13Z12PromptSvg ? 'topic13-z12-prompt-svg-size mx-auto w-full max-w-[270px] sm:max-w-[330px] md:max-w-[360px] [&>svg]:w-full [&>svg]:h-auto' : '' }}">
                            {!! $taskSvg !!}
                        </div>
                    </div>
                @elseif(!empty($task['image']))
                    @php
                        $imageName = (string) $task['image'];
                    @endphp
                    <div class="mt-4 mb-2">
                        @if(\Illuminate\Support\Str::startsWith($imageName, '<svg'))
                            <div class="rounded-lg overflow-hidden {{ $isTopic13 ? 'topic13-svg-card bg-slate-900/50 p-3 border border-slate-700' : 'bg-white p-2' }}">
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
                                 tabindex="0"
                                 role="button"
                                 aria-label="Выбрать вариант {{ $graphOptionIndex }}"
                                 @if($graphOptionText !== '') data-option-text="{{ $graphOptionText }}" @endif>
                                <div class="flex items-start gap-2">
                                    <span class="text-cyan-400 font-bold shrink-0">{{ $graphOptionIndex }})</span>
                                    <div class="flex-1 rounded overflow-hidden {{ $isTopic13 ? 'topic13-svg-card bg-slate-900/50 p-2 border border-slate-700' : 'bg-white p-1' }}">
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
                                @php
                                    $optionText = is_array($option)
                                        ? (string) ($option['label'] ?? $option['text'] ?? $option['value'] ?? '')
                                        : (string) $option;
                                @endphp
                                <span class="bg-slate-700/70 text-slate-300 px-4 py-2 rounded-lg hover:bg-slate-600 cursor-pointer transition">
                                    @if(str_contains($optionText, '\\'))
                                        {{ $i + 1 }}) ${{ $optionText }}$
                                    @else
                                        {{ $i + 1 }}) {{ $optionText }}
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
                @include('tasks.partials.task-status-badge')
            </div>
        @endforeach
    </div>
@endif
