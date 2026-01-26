{{--
    Тип: geometry (ЕГЭ планиметрия)
    Геометрические задачи
    SVG может храниться в task['svg'] (предзаготовлен через svg:bake)
--}}

@php
    $tasks = $zadanie['tasks'] ?? [];
@endphp

<div class="space-y-6">
    @foreach($tasks as $task)
        @php
            $taskKey = "ege_topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br><code>" . substr($task['text'] ?? '', 0, 100) . "...</code>";
            // SVG может быть в task['svg'] (новый формат) или task['image'] (старый формат)
            $hasSvg = !empty($task['svg']);
            $hasImage = !empty($task['image']);
            $hasVisual = $hasSvg || $hasImage;
        @endphp

        <div class="bg-purple-900/30 rounded-xl border border-purple-800/40 overflow-hidden task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

            <div class="p-5 {{ $hasVisual ? 'lg:flex lg:gap-6' : '' }}">
                {{-- SVG или изображение --}}
                @if($hasVisual)
                    <div class="lg:w-80 lg:shrink-0 mb-4 lg:mb-0">
                        <div class="bg-purple-950/50 rounded-lg p-3 flex items-center justify-center min-h-[200px]">
                            @if($hasSvg)
                                {{-- Предзаготовленный SVG из JSON --}}
                                {!! $task['svg'] !!}
                            @elseif(str_starts_with($task['image'], '<svg'))
                                {{-- Inline SVG (legacy) --}}
                                {!! $task['image'] !!}
                            @else
                                {{-- PNG/JPG файл --}}
                                <img src="{{ asset('images/tasks/ege/' . $topicId . '/' . $task['image']) }}"
                                     alt="Геометрия {{ $task['id'] }}"
                                     class="max-w-full max-h-full object-contain">
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Текст задачи --}}
                <div class="flex-1">
                    <div class="flex items-start gap-3">
                        <span class="text-purple-400 font-bold text-lg shrink-0">{{ $task['id'] }})</span>
                        <p class="text-purple-100/90 leading-relaxed">{!! $task['text'] ?? '' !!}</p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
