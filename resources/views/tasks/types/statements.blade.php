{{--
    Тип: statements (тема 19)
    Анализ геометрических высказываний

    Особенность: statements находятся на уровне задания, не в tasks!
--}}

@php
    $statements = $zadanie['statements'] ?? [];
@endphp

<div class="space-y-4" x-data="{ showAnswers: false }">
    {{-- Кнопка показать ответы --}}
    <div class="flex justify-end">
        <button @click="showAnswers = !showAnswers"
                class="text-sm px-4 py-2 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition">
            <span x-text="showAnswers ? 'Скрыть ответы' : 'Показать ответы'"></span>
        </button>
    </div>

    @foreach($statements as $statement)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_statement_{$statement['id']}";
            $isTrue = $statement['is_true'] ?? false;
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Утверждение {$statement['id']}<br>Верно: " . ($isTrue ? 'Да' : 'Нет');
        @endphp

        <div class="bg-slate-800/70 rounded-xl p-5 border-l-4 transition-colors task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}"
             :class="{
                 'border-slate-600': !showAnswers,
                 'border-green-500 bg-green-900/20': showAnswers && {{ $isTrue ? 'true' : 'false' }},
                 'border-red-500 bg-red-900/20': showAnswers && !{{ $isTrue ? 'true' : 'false' }}
             }">

            <div class="flex items-start gap-4">
                <span class="text-purple-400 font-bold text-lg shrink-0">{{ $statement['id'] }}.</span>
                <p class="text-slate-200 leading-relaxed flex-1">{{ $statement['text'] }}</p>

                {{-- Индикатор ответа --}}
                <div x-show="showAnswers" x-cloak class="shrink-0">
                    @if($isTrue)
                        <span class="inline-flex items-center gap-1 text-green-400 text-sm font-medium">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Верно
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-red-400 text-sm font-medium">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Неверно
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
