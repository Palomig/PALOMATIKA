{{--
    Тип: matching, matching_signs, matching_4 (тема 11)
    Соответствие графиков и формул

    Формат ОГЭ: 3 графика (А, Б, В) + 3 формулы (1, 2, 3)

    Поддерживает:
    - task['svg'] — предзаготовленный SVG (Static SVG System)
    - task['image'] — PNG/JPEG файл (временное решение)
    - task['options'] — варианты ответов (формулы)
--}}

@php
    $type = $zadanie['type'] ?? 'matching';
    $tasks = $zadanie['tasks'] ?? [];
    $graphLabels = ['А', 'Б', 'В', 'Г'];
    $showTaskAnswer = !($isVariant ?? false);
    $answerResolver = app(\App\Services\TaskAnswerResolver::class);

    // Для темы 11 рендерим каждый task как отдельный пример: внутри task.svg уже 3 графика (А/Б/В)
    $topicIdStr = (string) ($topicId ?? '');
    $isTopic11PerTaskMode = $topicIdStr === '11' || str_starts_with($zadanie['instruction'] ?? '', 'Установите соответствие между графиками');

    // Стандарт: группа по 3 графика. Для темы 11: 1 задача = 1 группа.
    $taskGroups = $isTopic11PerTaskMode
        ? array_map(static fn ($t) => [$t], $tasks)
        : array_chunk($tasks, 3);
@endphp

<div class="space-y-10">
    @foreach($taskGroups as $groupIndex => $group)
        @php
            $groupNumber = $groupIndex + 1;
            $taskKeys = [];

            // Формулы для отображения:
            // - обычный режим: берём первую формулу из каждой задачи (А,Б,В)
            // - three-panel режим: берём все формулы из текущей задачи
            $groupFormulas = [];
            if ($isTopic11PerTaskMode) {
                $task0 = $group[0] ?? null;
                $groupFormulas = is_array($task0['options'] ?? null) ? $task0['options'] : [];
            } else {
                foreach ($group as $task) {
                    if (!empty($task['options'])) {
                        $groupFormulas[] = $task['options'][0];
                    }
                }
            }
            $displayFormulas = $groupFormulas;
        @endphp

        <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 task-review-item relative"
             data-task-key="topic_{{ $topicId }}_block_{{ $block['number'] }}_zadanie_{{ $zadanie['number'] }}_group_{{ $groupNumber }}">

            {{-- Номер группы (если несколько групп) --}}
            @if(count($taskGroups) > 1)
                <div class="mb-4">
                    <span class="text-cyan-400 font-bold text-lg">{{ $groupNumber }})</span>
                </div>
            @endif

            {{-- ГРАФИКИ секция --}}
            <div class="mb-6">
                <h4 class="text-slate-400 text-sm font-medium mb-4 uppercase tracking-wide">Графики</h4>

                @if($isTopic11PerTaskMode)
                    @php
                        $task = $group[0] ?? [];
                        $hasSvg = !empty($task['svg']);
                        $hasImage = !empty($task['image']);
                        $imageName = $task['image'] ?? '';
                        $imageUrl = $imageName ? asset("images/tasks/{$topicId}/{$imageName}") : null;
                    @endphp

                    <div class="p-2 min-h-[240px] flex items-center justify-center w-full [&>svg]:w-full [&>svg]:max-w-[1200px]">
                        @if($hasSvg)
                            @php
                                $svgHtml = (string) $task['svg'];
                                $svgHtml = str_replace('max-w-[830px]', 'max-w-[1200px]', $svgHtml);
                            @endphp
                            {!! $svgHtml !!}
                        @elseif($hasImage && \Illuminate\Support\Str::startsWith($imageName, '<svg'))
                            {!! $imageName !!}
                        @elseif($hasImage)
                            <div class="bg-white rounded-lg p-2 w-full flex justify-center">
                                <img src="{{ $imageUrl }}"
                                     alt="График"
                                     class="max-w-full max-h-56 object-contain"
                                     onerror="this.onerror=null; this.parentElement.innerHTML='<span class=\'text-slate-500 text-sm\'>Изображение не загружено</span>';">
                            </div>
                        @else
                            <span class="text-slate-500 text-sm">Нет изображения</span>
                        @endif
                    </div>

                    <div class="px-1 pt-1">
                        @include('tasks.partials.task-answer', [
                            'showTaskAnswer' => $showTaskAnswer,
                            'taskAnswer' => $answerResolver->resolveFromTaskAndZadanie($zadanie, $task),
                        ])
                        @include('tasks.partials.task-status-badge')
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($group as $taskIndex => $task)
                            @php
                                $hasSvg = !empty($task['svg']);
                                $hasImage = !empty($task['image']);
                                $imageName = $task['image'] ?? '';
                                $imageUrl = $imageName ? asset("images/tasks/{$topicId}/{$imageName}") : null;
                                $label = $graphLabels[$taskIndex] ?? ($taskIndex + 1);
                            @endphp

                            <div class="bg-slate-900/60 rounded-xl border border-slate-700 overflow-hidden">
                                <div class="bg-slate-700/50 px-4 py-2">
                                    <span class="text-cyan-400 font-bold text-lg">{{ $label }})</span>
                                </div>
                                <div class="p-3 min-h-[180px] flex items-center justify-center">
                                    @if($hasSvg)
                                        {!! $task['svg'] !!}
                                    @elseif($hasImage && \Illuminate\Support\Str::startsWith($imageName, '<svg'))
                                        {!! $imageName !!}
                                    @elseif($hasImage)
                                        <div class="bg-white rounded-lg p-2 w-full flex justify-center">
                                            <img src="{{ $imageUrl }}" alt="График {{ $label }}" class="max-w-full max-h-40 object-contain">
                                        </div>
                                    @else
                                        <span class="text-slate-500 text-sm">Нет изображения</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ФОРМУЛЫ секция --}}
            @if(!empty($displayFormulas))
                <div class="mb-6">
                    <h4 class="text-slate-400 text-sm font-medium mb-4 uppercase tracking-wide">Формулы</h4>
                    <div class="flex flex-wrap gap-4 justify-center">
                        @foreach($displayFormulas as $i => $formula)
                            @php
                                $formulaText = is_array($formula)
                                    ? ($formula['label'] ?? $formula['text'] ?? $formula['value'] ?? '')
                                    : (string) $formula;
                            @endphp
                            <div class="bg-slate-700/50 rounded-lg px-5 py-3">
                                <span class="text-amber-400 font-bold">{{ $i + 1 }})</span>
                                <span class="text-slate-200 math-serif ml-2">${{ $formulaText }}$</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Таблица ответов --}}
            @php
                $tableLabels = $isTopic11PerTaskMode ? ['А', 'Б', 'В'] : collect($group)->keys()->map(fn($i) => $graphLabels[$i] ?? ($i + 1))->values()->all();
            @endphp
            <div class="flex flex-col items-center gap-3">
                <span class="text-slate-400 text-sm">В таблице укажите соответствующий номер формулы для каждого графика:</span>
                <table class="border-collapse">
                    <thead>
                        <tr>
                            @foreach($tableLabels as $lbl)
                                <th class="w-14 h-10 border border-slate-600 text-center text-cyan-400 font-medium bg-slate-800/50">
                                    {{ $lbl }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach($tableLabels as $lbl)
                                <td class="w-14 h-10 border border-slate-600 text-center bg-slate-700/30">
                                    <input type="text"
                                           maxlength="1"
                                           class="w-full h-full text-center bg-transparent text-white focus:outline-none focus:bg-slate-600/50"
                                           placeholder="">
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
