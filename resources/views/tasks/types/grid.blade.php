{{--
    Тип: grid_image, grid_image_with_question (тема 18)
    Фигуры на клетчатой бумаге
--}}

@php
    $type = $zadanie['type'] ?? 'grid_image';
    $tasks = $zadanie['tasks'] ?? [];
    $examType = request()->is('ege*') ? 'EGE' : 'OGE';
    $isVariant = $isVariant ?? false;
    $showTaskAnswer = !$isVariant;
    $answerResolver = app(\App\Services\TaskAnswerResolver::class);

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
    /* Масштабируем SVG в теме 18 так же, как в темах 15-17 (до 350px). */
    .topic18-svg-force { width: 350px; max-width: 100%; margin-left: auto; margin-right: auto; }
    .topic18-svg-force > svg { max-width: 350px !important; width: 100%; height: auto; }
</style>
@endonce

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @foreach($tasks as $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $imageName = $getGridImagePath($task['image'] ?? '', $block['number'], $zadanie['number'], $task['id']);
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br>Изображение: {$imageName}";
            $hasQuestion = !empty($task['question']);
            $hasSvg = !empty($task['svg']);
            // Уникальный ID для редактора (иначе task.id повторяется между заданиями).
            $editorTaskId = $topicId . $examType . 'B' . $block['number'] . 'Z' . $zadanie['number'] . 'T' . $task['id'];
            $editorSvg = $task['svg'] ?? ((\Illuminate\Support\Str::startsWith($task['image'] ?? '', '<svg')) ? $task['image'] : null);
        @endphp

        <div class="bg-slate-800/70 rounded-xl border border-slate-700 overflow-hidden task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

            @if(!$isVariant)
                {{-- Номер задачи --}}
                <div class="bg-slate-700/50 px-3 py-1.5 flex items-center gap-2">
                    <span class="text-fuchsia-400 font-bold">{{ $task['id'] }})</span>
                    @if($hasQuestion)
                        <span class="text-slate-400 text-sm truncate">{{ $task['question'] }}</span>
                    @endif
                </div>
            @endif

            {{-- Изображение --}}
            <div class="p-3 relative group">
                @if($hasSvg)
                    <div class="topic18-svg-force">
                        {!! $task['svg'] !!}
                    </div>
                @elseif(\Illuminate\Support\Str::startsWith($task['image'] ?? '', '<svg'))
                    <div class="topic18-svg-force">
                        {!! $task['image'] !!}
                    </div>
                @else
                    <div class="bg-white rounded topic18-svg-force">
                        <img src="{{ asset('images/tasks/18/' . $imageName) }}"
                             alt="Клетчатая бумага {{ $task['id'] }}"
                             class="w-full h-auto"
                             onerror="this.onerror=null; this.src='{{ asset('images/placeholder-grid.svg') }}'; this.alt='Изображение не найдено';">
                    </div>
                @endif

                @if($editorSvg)
                    {{-- Кнопка редактирования SVG --}}
                    <button onclick="openGeometryEditor('{{ $editorTaskId }}', {{ json_encode($editorSvg) }})"
                            class="absolute bottom-5 right-5 p-2 bg-purple-600/80 hover:bg-purple-500 rounded-lg text-white opacity-0 group-hover:opacity-100 transition-all duration-200"
                            title="Редактировать изображение">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                @endif
            </div>

            <div class="px-3 pb-3">
                @include('tasks.partials.task-answer', [
                    'showTaskAnswer' => $showTaskAnswer,
                    'taskAnswer' => $answerResolver->resolveFromTaskAndZadanie($zadanie, $task),
                ])
            </div>
        </div>
    @endforeach
</div>
