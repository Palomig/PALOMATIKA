{{--
    Тип: matching, matching_signs, matching_4 (тема 11)
    Соответствие графиков и формул
--}}

@php
    $type = $zadanie['type'] ?? 'matching';
    $tasks = $zadanie['tasks'] ?? [];
    $graphLabels = ['А', 'Б', 'В', 'Г'];

    // Получаем options из zadanie (исправлено в TaskDataService)
    $options = $zadanie['options'] ?? [];
@endphp

<div class="space-y-8">
    @foreach($tasks as $taskIndex => $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $imageName = $task['image'] ?? '';
            $imageUrl = $imageName ? asset("images/tasks/{$topicId}/{$imageName}") : null;
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br>Изображение: {$imageName}";
        @endphp

        <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

            <div class="flex items-center gap-2 mb-4">
                <span class="text-cyan-400 font-bold text-lg">{{ $task['id'] }})</span>
            </div>

            @php
                $uniqueId = uniqid();
            @endphp

            @if(!empty($options) && count($options) >= 3)
                {{-- SVG Графики: генерируются динамически из формул --}}
                <div class="grid grid-cols-{{ count($options) == 3 ? '3' : '2' }} gap-3 mb-4">
                    @foreach($options as $optIndex => $formula)
                        <div class="bg-slate-900/50 rounded-lg p-3">
                            <div class="text-cyan-400 font-bold text-center mb-2">{{ $graphLabels[$optIndex] ?? ($optIndex + 1) }})</div>
                            <div id="graph-{{ $uniqueId }}-{{ $optIndex }}" class="w-full aspect-square"></div>
                        </div>
                    @endforeach
                </div>

                <script>
                    (function() {
                        const graphFormulas = @json($options);
                        const uniqueId = "{{ $uniqueId }}";

                        // Ждём загрузки DOM и наличия функции renderSingleGraph
                        function initGraphs() {
                            if (typeof renderSingleGraph === 'function') {
                                graphFormulas.forEach((formula, i) => {
                                    renderSingleGraph(`graph-${uniqueId}-${i}`, formula);
                                });
                            } else {
                                // Функция ещё не загружена, пробуем через 100ms
                                setTimeout(initGraphs, 100);
                            }
                        }

                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', initGraphs);
                        } else {
                            initGraphs();
                        }
                    })();
                </script>

                {{-- Формулы для соответствия --}}
                <div class="flex flex-wrap gap-3 justify-center mb-4">
                    @foreach($options as $i => $option)
                        <div class="bg-slate-700/50 rounded-lg px-4 py-2">
                            <span class="text-amber-400 font-bold">{{ $i + 1 }})</span>
                            <span class="text-slate-200 math-serif ml-2">${{ $option }}$</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-red-400 text-center p-4">
                    <p class="font-bold mb-2">⚠️ Формулы не найдены</p>
                    <p class="text-sm text-slate-400">Для отображения графиков требуется минимум 3 формулы</p>
                </div>
            @endif

            {{-- Таблица ответов --}}
            <div class="mt-4 flex items-center gap-4">
                <span class="text-slate-400 text-sm">Ответ:</span>
                <div class="flex gap-1">
                    @foreach($graphLabels as $label)
                        <div class="w-10 h-10 border border-slate-600 rounded flex items-center justify-center bg-slate-800">
                            <span class="text-slate-500 text-sm">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
