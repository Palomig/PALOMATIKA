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

    // Группируем tasks по 3 для формата ОГЭ
    $taskGroups = array_chunk($tasks, 3);
@endphp

<div class="space-y-10">
    @foreach($taskGroups as $groupIndex => $group)
        @php
            $groupNumber = $groupIndex + 1;
            $taskKeys = [];

            // Собираем все формулы группы (первая формула каждой задачи - правильный ответ)
            $groupFormulas = [];
            foreach ($group as $task) {
                if (!empty($task['options'])) {
                    $groupFormulas[] = $task['options'][0]; // Первая формула - правильный ответ
                }
            }
            // Перемешиваем для отображения (но сохраняем оригинальный порядок)
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
                            {{-- Метка графика --}}
                            <div class="bg-slate-700/50 px-4 py-2">
                                <span class="text-cyan-400 font-bold text-lg">{{ $label }})</span>
                            </div>

                            {{-- Изображение графика --}}
                            <div class="p-3 min-h-[180px] flex items-center justify-center">
                                @if($hasSvg)
                                    {{-- Предзаготовленный SVG (Static SVG System) --}}
                                    {!! $task['svg'] !!}
                                @elseif($hasImage && str_starts_with($imageName, '<svg'))
                                    {{-- Inline SVG --}}
                                    {!! $imageName !!}
                                @elseif($hasImage)
                                    {{-- PNG/JPEG изображение --}}
                                    <div class="bg-white rounded-lg p-2 w-full flex justify-center">
                                        <img src="{{ $imageUrl }}"
                                             alt="График {{ $label }}"
                                             class="max-w-full max-h-40 object-contain"
                                             onerror="this.onerror=null; this.parentElement.innerHTML='<span class=\'text-slate-500 text-sm\'>Изображение не загружено</span>';">
                                    </div>
                                @else
                                    <span class="text-slate-500 text-sm">Нет изображения</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ФОРМУЛЫ секция --}}
            @if(!empty($displayFormulas))
                <div class="mb-6">
                    <h4 class="text-slate-400 text-sm font-medium mb-4 uppercase tracking-wide">Формулы</h4>
                    <div class="flex flex-wrap gap-4 justify-center">
                        @foreach($displayFormulas as $i => $formula)
                            <div class="bg-slate-700/50 rounded-lg px-5 py-3">
                                <span class="text-amber-400 font-bold">{{ $i + 1 }})</span>
                                <span class="text-slate-200 math-serif ml-2">${{ $formula }}$</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Таблица ответов --}}
            <div class="flex flex-col items-center gap-3">
                <span class="text-slate-400 text-sm">В таблице укажите соответствующий номер формулы для каждого графика:</span>
                <table class="border-collapse">
                    <thead>
                        <tr>
                            @foreach($group as $taskIndex => $task)
                                <th class="w-14 h-10 border border-slate-600 text-center text-cyan-400 font-medium bg-slate-800/50">
                                    {{ $graphLabels[$taskIndex] ?? ($taskIndex + 1) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach($group as $taskIndex => $task)
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
