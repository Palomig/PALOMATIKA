{{--
    Тип: fipi — задание ЕГЭ из открытого банка ФИПИ.

    Условие приходит готовой разметкой: формулы уже переведены в KaTeX
    (`$…$`), чертежи остаются растрами ФИПИ (решение по банку ЕГЭ, см.
    .claude/rules/ege-tasks.md). Поэтому разметка выводится как есть, а не
    через `e()`: остальные типы ЕГЭ показывают текст и вывели бы теги наружу.

    В одну колонку, а не в сетку из трёх, как `expression`: у части 2 условие
    занимает абзац-другой, а у планиметрии рядом стоит чертёж — в узкой
    карточке и то, и другое нечитаемо.
--}}

@php
    $tasks = $zadanie['tasks'] ?? [];
@endphp

<div class="space-y-4">
    @foreach($tasks as $task)
        @php
            $taskKey = "ege_topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
        @endphp

        <div class="bg-dark-100 rounded-xl p-5 border border-dark-400/50 task-review-item relative hover:border-accent/30 transition-colors"
             data-task-key="{{ $taskKey }}">
            <div class="flex items-start gap-3">
                <span class="text-accent-light font-bold shrink-0">{{ $task['id'] }})</span>
                <div class="min-w-0 flex-1">
                    <div class="ege-fipi-condition text-gray-200 leading-relaxed">
                        {!! $task['html'] ?? '' !!}
                    </div>

                    @if(!empty($task['options']))
                        <ol class="mt-3 space-y-1 text-gray-200">
                            @foreach($task['options'] as $option)
                                <li class="flex gap-2">
                                    <span class="text-gray-400 shrink-0">{{ $option['n'] }})</span>
                                    <span class="min-w-0">{!! $option['html'] !!}</span>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if(!empty($task['answer']))
                        <details class="mt-3 text-sm">
                            <summary class="cursor-pointer text-accent-light/80 hover:text-accent-light">
                                Ответ
                            </summary>
                            <div class="mt-1 text-gray-300">{{ $task['answer'] }}</div>
                            @if(!empty($task['answer_parts']))
                                {{-- Пункты а/б/в целиком: в поле ученика идёт
                                     только числовой, но по остальным проверяют
                                     решение. --}}
                                <div class="mt-1 text-gray-500">
                                    Полный ответ: {{ $task['answer_parts'] }}
                                </div>
                            @endif
                        </details>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

@once
    @push('styles')
    {{-- Обёртка <style> обязательна: в разделе /ege стек `styles` выводится
         как есть (partials/head-config), и каждый push несёт свой тег. В PWA
         тот же стек лежит ВНУТРИ <style>, и там теги не нужны — одноимённые
         стеки с разной семантикой легко перепутать, а видно это только на
         странице: правила вываливаются текстом поверх вёрстки. --}}
    <style>
        {{-- Растры ФИПИ нарисованы чёрным по прозрачному: на тёмном фоне
             раздела их не видно без белой подложки. Чертёж выглядит вклеенным
             листом, обозначение внутри предложения («SABCD») — набранным
             символом. Класс проставляет экспорт по высоте растра. --}}
        .ege-fipi-condition img { max-width: 100%; height: auto; }
        .ege-fipi-condition img.fipi-figure {
            background: #fff; border-radius: 8px; padding: 8px; margin: .4rem 0;
        }
        .ege-fipi-condition img.fipi-inline {
            /* display обязателен: Tailwind Preflight делает картинки
               блочными, и обозначения вроде «SABCD» вставали каждое с
               новой строки, разрывая предложение. */
            display: inline-block;
            background: #fff; border-radius: 3px; padding: 0 2px;
            height: 1.35em; width: auto; vertical-align: -0.28em;
        }
        .ege-fipi-condition p { margin: 0 0 .6rem; }
        .ege-fipi-condition p:last-child { margin-bottom: 0; }
        .ege-fipi-condition table { border-collapse: collapse; }
        .ege-fipi-condition td { vertical-align: top; padding: 2px 6px; }
    </style>
    @endpush
@endonce
