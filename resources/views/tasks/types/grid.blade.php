{{--
    Тип: grid_image, grid_image_with_question (тема 18)
    Фигуры на клетчатой бумаге
--}}

@php
    $type = $zadanie['type'] ?? 'grid_image';
    $tasks = $zadanie['tasks'] ?? [];

    // Функция для преобразования имени файла из JSON в реальное имя
    // Формат в JSON: oge18_pX_imgY.png → Формат реальных файлов: task18_bBLOCK_zZADANIE_ID.png
    $getGridImagePath = function($originalImage, $blockNumber, $zadanieNumber, $taskId) {
        // Если файл уже в новом формате - возвращаем как есть
        if (\Illuminate\Support\Str::startsWith($originalImage, 'task18_')) {
            return $originalImage;
        }

        // Преобразуем в новый формат: task18_b{block}_z{zadanie}_{id}.png
        return "task18_b{$blockNumber}_z{$zadanieNumber}_{$taskId}.png";
    };
@endphp

@once
<style>
    .grid-svg-wrap { max-width: 250px; margin: 0 auto; }
    .grid-svg-wrap svg { max-width: 250px !important; width: 100% !important; height: auto !important; display: block; margin: 0 auto; }
</style>
@endonce

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @foreach($tasks as $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $imageName = $getGridImagePath($task['image'] ?? '', $block['number'], $zadanie['number'], $task['id']);
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br>Изображение: {$imageName}";
            $hasQuestion = !empty($task['question']);
            $hasSvg = !empty($task['svg']);
        @endphp

        <div class="bg-slate-800/70 rounded-xl border border-slate-700 overflow-hidden task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

            {{-- Номер задачи --}}
            <div class="bg-slate-700/50 px-3 py-1.5 flex items-center gap-2">
                <span class="text-fuchsia-400 font-bold">{{ $task['id'] }})</span>
                @if($hasQuestion)
                    <span class="text-slate-400 text-sm truncate">{{ $task['question'] }}</span>
                @endif
            </div>

            {{-- Изображение --}}
            <div class="p-3 bg-white">
                @if($hasSvg)
                    <div class="grid-svg-wrap">
                        {!! $task['svg'] !!}
                    </div>
                @elseif(\Illuminate\Support\Str::startsWith($task['image'] ?? '', '<svg'))
                    <div class="grid-svg-wrap">
                        {!! $task['image'] !!}
                    </div>
                @else
                    <img src="{{ asset('images/tasks/18/' . $imageName) }}"
                         alt="Клетчатая бумага {{ $task['id'] }}"
                         class="w-full h-auto"
                         onerror="this.onerror=null; this.src='{{ asset('images/placeholder-grid.svg') }}'; this.alt='Изображение не найдено';">
                @endif
            </div>
        </div>
    @endforeach
</div>
