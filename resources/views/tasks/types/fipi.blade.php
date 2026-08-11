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
    {{-- Обёртка <style> обязательна: `head-config` выводит стек `styles`
         ПОСЛЕ закрытия своего тега, и push без тега печатает правила текстом
         поверх страницы. В PWA тот же стек лежит ВНУТРИ <style>, и там тег не
         нужен — одноимённые стеки с разной семантикой легко перепутать. --}}
    <style>
        /* Инлайновые SVG приходят с классами Tailwind (`max-w-[320px]`),
           ширину задают они; здесь только страховка от переполнения. */
        .fipi-condition svg, .fipi-options svg { max-width: 100%; height: auto; }
        .fipi-condition img, .fipi-options img { max-width: 100%; height: auto; }
        /* Растры ЕГЭ. Своих SVG для этого банка нет, и по решению Стаса
           чертежи остаются картинками ФИПИ — а они чёрным по прозрачному и
           на тёмном фоне интерфейса почти не читаются. Отсюда белая
           подложка: чертёж выглядит вклеенным листом, обозначение внутри
           предложения — просто набранным символом. */
        /* Как чертежи ОГЭ: во всю ширину колонки, до 460px. Растры ФИПИ
           мелкие, в натуральном размере читаются плохо. */
        .fipi-condition img.fipi-figure {
            display: block; width: 100%; max-width: 460px; height: auto;
            background: #fff; border-radius: 8px; padding: 8px; margin: .5rem 0;
        }
        .fipi-condition img.fipi-inline, .fipi-options img.fipi-inline {
            /* display обязателен: Tailwind Preflight делает картинки
               блочными, и обозначения вроде «SABCD» вставали каждое с
               новой строки, разрывая предложение. */
            display: inline-block;
            background: #fff; border-radius: 3px; padding: 0 2px;
            height: 1.35em; width: auto; vertical-align: -0.28em;
        }
        .fipi-condition p { margin: 0 0 .6rem; }
        .fipi-condition p:last-child { margin-bottom: 0; }
        /* Условие и чертёж — соседние ячейки таблицы; в узкой колонке
           рисунок сжимается. На малой ширине раскладываем в столбик. */
        @media (max-width: 700px) {
            .fipi-condition table, .fipi-condition tbody,
            .fipi-condition tr, .fipi-condition td { display: block; width: 100%; }
        }
        .fipi-condition table { border-collapse: collapse; }
        .fipi-condition td { vertical-align: top; padding: 2px 6px; }
    </style>
    @endpush
@endonce
