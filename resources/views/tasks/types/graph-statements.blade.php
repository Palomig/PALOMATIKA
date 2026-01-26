{{--
    Тип: graph_statements (тема 11, блок 2)
    График квадратичной функции + утверждения для проверки

    Структура задачи:
    - task['svg'] — SVG график функции
    - task['image'] — PNG/JPEG (fallback)
    - task['statements'] — массив утверждений для проверки
--}}

@php
    $tasks = $zadanie['tasks'] ?? [];
@endphp

<div class="space-y-8">
    @foreach($tasks as $taskIndex => $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $hasSvg = !empty($task['svg']);
            $hasImage = !empty($task['image']);
            $imageName = $task['image'] ?? '';
            $imageUrl = $imageName ? asset("images/tasks/{$topicId}/{$imageName}") : null;
            $statements = $task['statements'] ?? [];
        @endphp

        <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 task-review-item relative"
             data-task-key="{{ $taskKey }}">

            <div class="flex items-center gap-2 mb-4">
                <span class="text-cyan-400 font-bold text-lg">{{ $task['id'] }})</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- График функции --}}
                <div class="flex justify-center items-center">
                    @if($hasSvg)
                        <div class="bg-slate-900/80 rounded-lg p-4">
                            {!! $task['svg'] !!}
                        </div>
                    @elseif($hasImage)
                        <div class="bg-white rounded-lg p-4">
                            <img src="{{ $imageUrl }}"
                                 alt="График функции"
                                 class="max-w-full max-h-48 object-contain"
                                 onerror="this.onerror=null; this.src='{{ asset('images/placeholder.svg') }}';">
                        </div>
                    @else
                        <div class="text-slate-500 text-center p-4">
                            График отсутствует
                        </div>
                    @endif
                </div>

                {{-- Утверждения --}}
                <div class="space-y-3">
                    <h4 class="text-slate-400 text-sm font-medium uppercase tracking-wide mb-3">Утверждения</h4>
                    @foreach($statements as $i => $statement)
                        <div class="bg-slate-700/50 rounded-lg px-4 py-3 text-slate-200">
                            <span class="text-amber-400 font-bold">{{ $i + 1 }})</span>
                            <span class="ml-2">{{ $statement }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Поле для ответа --}}
            <div class="mt-4 flex items-center gap-4">
                <span class="text-slate-400 text-sm">Номера верных утверждений:</span>
                <input type="text"
                       class="w-24 px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white text-center"
                       placeholder="123">
            </div>
        </div>
    @endforeach
</div>
