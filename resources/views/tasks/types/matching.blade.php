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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Изображение с 4 графиками (А, Б, В, Г) --}}
                <div class="bg-slate-900/50 rounded-lg p-3">
                    @if($imageUrl && file_exists(public_path("images/tasks/{$topicId}/{$imageName}")))
                        <img src="{{ $imageUrl }}" alt="Графики функций А, Б, В, Г" class="w-full h-auto rounded border border-slate-700">
                    @else
                        <div class="text-red-400 text-center p-4">
                            <p class="font-bold mb-2">⚠️ Изображение не найдено</p>
                            <p class="text-sm text-slate-400">{{ $imageName }}</p>
                        </div>
                    @endif
                </div>

                {{-- Формулы для соответствия --}}
                <div class="space-y-3">
                    @if(!empty($options))
                        @foreach($options as $i => $option)
                            <div class="flex items-center gap-3 bg-slate-700/50 rounded-lg px-4 py-3 hover:bg-slate-700 transition cursor-pointer">
                                <span class="text-amber-400 font-bold">{{ $i + 1 }})</span>
                                <span class="text-slate-200 math-serif">${{ $option }}$</span>
                            </div>
                        @endforeach
                    @else
                        <div class="text-red-400 text-center p-4">
                            <p class="font-bold">⚠️ Формулы не найдены</p>
                            <p class="text-sm text-slate-400">Проверьте структуру данных</p>
                        </div>
                    @endif
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
