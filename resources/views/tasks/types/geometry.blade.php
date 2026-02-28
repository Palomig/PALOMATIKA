{{--
    Тип: geometry (темы 15, 16, 17)
    Геометрические задачи
    SVG хранится в task['svg'] (предзаготовлен через svg:bake)
--}}

@php
    $tasks = $zadanie['tasks'] ?? [];
    // Определяем тип экзамена (OGE/EGE) по контексту или URL
    $examType = request()->is('ege*') ? 'EGE' : 'OGE';
    $isVariant = $isVariant ?? false;
    $showTaskAnswer = !$isVariant;
    $answerResolver = app(\App\Services\TaskAnswerResolver::class);
@endphp

@once
<style>
    /* Как на теме 18: ограничиваем SVG до 350px и центрируем.
       Важно: применяем к любому вложенному <svg>, т.к. в payload бывает обёртка. */
    .geo-svg-force { max-width: 350px; width: 100%; margin: 0 auto; }
    .geo-svg-force svg { max-width: 350px !important; width: auto !important; height: auto !important; display: block; margin: 0 auto; }
</style>
@endonce

<div class="space-y-6">
    @foreach($tasks as $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br><code>" . substr($task['text'] ?? '', 0, 100) . "...</code>";
            // SVG может быть в task['svg'] (новый формат) или task['image'] (старый формат)
            $hasSvg = !empty($task['svg']);
            $hasImage = !empty($task['image']);
            $hasVisual = $hasSvg || $hasImage;
            // Уникальный ID для редактора (task.id повторяется в разных заданиях).
            $editorTaskId = $topicId . $examType . 'B' . $block['number'] . 'Z' . $zadanie['number'] . 'T' . $task['id'];
        @endphp

        <div class="bg-slate-800/70 rounded-xl border border-slate-700 overflow-hidden task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

            <div class="p-5 {{ $hasVisual ? 'lg:flex lg:gap-6' : '' }}">
                {{-- SVG или изображение --}}
                @if($hasVisual)
                    <div class="lg:w-[400px] lg:shrink-0 mb-4 lg:mb-0">
                        <div class="bg-slate-900/50 rounded-lg p-4 flex items-center justify-center min-h-[280px] relative group">
                            @if($hasSvg)
                                {{-- Предзаготовленный SVG из JSON --}}
                                <div class="geo-svg-force">
                                    {!! $task['svg'] !!}
                                </div>
                            @elseif(!empty($task['image']) && \Illuminate\Support\Str::startsWith($task['image'], '<svg'))
                                {{-- Inline SVG (legacy) --}}
                                <div class="geo-svg-force">
                                    {!! $task['image'] !!}
                                </div>
                            @else
                                {{-- PNG/JPG файл --}}
                                <img src="{{ asset('images/tasks/' . $topicId . '/' . $task['image']) }}"
                                     alt="Геометрия {{ $task['id'] }}"
                                     class="max-w-full max-h-full object-contain">
                            @endif

                            {{-- Кнопка редактирования SVG --}}
                            <button onclick="openGeometryEditor('{{ $editorTaskId }}', {{ json_encode($task['svg'] ?? null) }})"
                                    class="absolute bottom-2 right-2 p-2 bg-purple-600/80 hover:bg-purple-500 rounded-lg text-white opacity-0 group-hover:opacity-100 transition-all duration-200"
                                    title="Редактировать изображение">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Текст задачи --}}
                <div class="flex-1">
                    <div class="flex items-start gap-3">
                        @if(!$isVariant)
                            <span class="text-red-400 font-bold text-lg shrink-0">{{ $task['id'] }})</span>
                        @endif
                        <p class="text-slate-200 leading-relaxed">{!! $task['text'] ?? '' !!}</p>
                    </div>

                    @include('tasks.partials.task-answer', [
                        'showTaskAnswer' => $showTaskAnswer,
                        'taskAnswer' => $answerResolver->resolveFromTaskAndZadanie($zadanie, $task),
                    ])
                    @include('tasks.partials.task-status-badge')
                </div>
            </div>
        </div>
    @endforeach
</div>
