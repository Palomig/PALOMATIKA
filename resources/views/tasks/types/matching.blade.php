{{--
    Тип: matching, matching_signs, matching_4 (тема 11)
    Соответствие графиков и формул
--}}

@php
    $type = $zadanie['type'] ?? 'matching';
    $tasks = $zadanie['tasks'] ?? [];
    $graphLabels = ['А', 'Б', 'В', 'Г'];

    // Функция для загрузки изображения и извлечения графиков
    function extractGraphsFromImage($topicId, $imageName) {
        $imagePath = public_path("images/tasks/{$topicId}/{$imageName}");
        if (!file_exists($imagePath)) {
            return null;
        }
        return "/images/tasks/{$topicId}/{$imageName}";
    }
@endphp

<div class="space-y-8">
    @foreach($tasks as $taskIndex => $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $options = $task['options'] ?? [];
            $imageName = $task['image'] ?? '';
            $imageUrl = extractGraphsFromImage($topicId, $imageName);
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br>Изображение: {$imageName}";
        @endphp

        <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

            <div class="flex items-center gap-2 mb-4">
                <span class="text-cyan-400 font-bold text-lg">{{ $task['id'] }})</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Изображение с графиками --}}
                <div class="bg-slate-900/50 rounded-lg p-3">
                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" alt="Графики функций" class="w-full h-auto rounded">
                    @else
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($graphLabels as $label)
                                <div class="bg-slate-900/50 rounded-lg p-2 text-center">
                                    <div class="text-cyan-400 font-bold mb-1">{{ $label }}</div>
                                    <div class="w-full aspect-square bg-slate-800 rounded flex items-center justify-center">
                                        <svg viewBox="0 0 100 100" class="w-full h-full">
                                            <line x1="10" y1="50" x2="90" y2="50" stroke="#475569" stroke-width="1"/>
                                            <line x1="50" y1="10" x2="50" y2="90" stroke="#475569" stroke-width="1"/>
                                        </svg>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Формулы --}}
                <div class="space-y-3">
                    @foreach($options as $i => $option)
                        <div class="flex items-center gap-3 bg-slate-700/50 rounded-lg px-4 py-3 hover:bg-slate-700 transition cursor-pointer">
                            <span class="text-amber-400 font-bold">{{ $i + 1 }})</span>
                            <span class="text-slate-200 math-serif">${{ $option }}$</span>
                        </div>
                    @endforeach
                </div>
            </div>

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
