{{--
    Тип: matching, matching_signs, matching_4 (тема 11)
    Соответствие графиков и формул

    Поддерживает:
    - task['svg'] — предзаготовленный SVG (Static SVG System)
    - task['image'] — PNG/JPEG файл (временное решение)
    - task['options'] — варианты ответов (формулы)
--}}

@php
    $type = $zadanie['type'] ?? 'matching';
    $tasks = $zadanie['tasks'] ?? [];
    $graphLabels = ['А', 'Б', 'В', 'Г'];
@endphp

<div class="space-y-8">
    @foreach($tasks as $taskIndex => $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";

            // Проверяем наличие SVG или изображения
            $hasSvg = !empty($task['svg']);
            $hasImage = !empty($task['image']);
            $imageName = $task['image'] ?? '';
            $imageUrl = $imageName ? asset("images/tasks/{$topicId}/{$imageName}") : null;

            // Варианты ответов из задачи
            $taskOptions = $task['options'] ?? [];

            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br>Изображение: {$imageName}";
        @endphp

        <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

            <div class="flex items-center gap-2 mb-4">
                <span class="text-cyan-400 font-bold text-lg">{{ $task['id'] }})</span>
            </div>

            {{-- Отображение графика --}}
            @if($hasSvg)
                {{-- Предзаготовленный SVG (Static SVG System) --}}
                <div class="bg-slate-900/50 rounded-lg p-4 mb-4 flex justify-center">
                    {!! $task['svg'] !!}
                </div>
            @elseif($hasImage)
                {{-- PNG/JPEG изображение (временное решение) --}}
                <div class="bg-slate-900/50 rounded-lg p-4 mb-4 flex justify-center">
                    <img src="{{ $imageUrl }}"
                         alt="График {{ $task['id'] }}"
                         class="max-w-full max-h-48 object-contain">
                </div>
            @else
                <div class="text-red-400 text-center p-4 mb-4">
                    <p class="font-bold mb-2">⚠️ Изображение не найдено</p>
                    <p class="text-sm text-slate-400">task['svg'] или task['image'] отсутствуют</p>
                </div>
            @endif

            {{-- Варианты ответов (формулы) --}}
            @if(!empty($taskOptions))
                <div class="flex flex-wrap gap-3 justify-center mb-4">
                    @foreach($taskOptions as $i => $option)
                        <div class="bg-slate-700/50 rounded-lg px-4 py-2">
                            <span class="text-amber-400 font-bold">{{ $i + 1 }})</span>
                            <span class="text-slate-200 math-serif ml-2">${{ $option }}$</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Таблица ответов --}}
            <div class="mt-4 flex items-center gap-4">
                <span class="text-slate-400 text-sm">Ответ:</span>
                <div class="flex gap-1">
                    @php
                        $answerCount = count($taskOptions) > 0 ? count($taskOptions) : 4;
                    @endphp
                    @for($i = 0; $i < $answerCount; $i++)
                        <div class="w-10 h-10 border border-slate-600 rounded flex items-center justify-center bg-slate-800">
                            <span class="text-slate-500 text-sm">{{ $graphLabels[$i] ?? ($i + 1) }}</span>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @endforeach
</div>
