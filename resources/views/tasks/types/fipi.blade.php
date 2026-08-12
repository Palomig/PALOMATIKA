{{--
    Задание из открытого банка ФИПИ.

    Условие приходит готовой разметкой: формулы уже переведены в KaTeX
    (`$…$`), чертежи вставлены инлайновыми SVG, растров нет — кроме
    практико-ориентированного блока (задания 1–5), где рисунок сознательно
    остался картинкой. Поэтому разметка выводится как есть, а не через `e()`:
    остальные шаблоны экранируют текст и показали бы теги наружу.

    Варианты ответа нумеруются, а не помечаются буквами: ответы банка —
    номера, и в PWA `optionAnswerValue()` при отсутствии `id` отдаёт как раз
    порядковый номер.
--}}
@php
    $tasks = $zadanie['tasks'] ?? [];
@endphp

<div class="space-y-4">
    @foreach($tasks as $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
        @endphp
        <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 task-review-item relative"
             data-task-key="{{ $taskKey }}">
            <div class="flex items-start gap-3">
                <span class="text-blue-400 font-bold shrink-0">{{ $task['id'] }})</span>
                <div class="min-w-0 flex-1">
                    <div class="fipi-condition text-slate-200 leading-relaxed">
                        {!! $task['html'] ?? '' !!}
                    </div>

                    @if(!empty($task['options']))
                        <ol class="fipi-options mt-3 space-y-1 text-slate-200">
                            @foreach($task['options'] as $option)
                                <li class="flex gap-2">
                                    <span class="text-slate-400 shrink-0">{{ $option['n'] }})</span>
                                    <span class="min-w-0">{!! $option['html'] !!}</span>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @include('tasks.partials.task-answer', [
                        'task' => $task,
                        'taskKey' => $taskKey,
                        'topicId' => $topicId,
                    ])
                </div>
            </div>
        </div>
    @endforeach
</div>

@once
    @push('styles')
        @include('tasks.partials.fipi-styles')
    @endpush
@endonce
