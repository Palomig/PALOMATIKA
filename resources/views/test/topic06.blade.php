<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>06. Дроби и степени - Тест парсинга PDF</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathWithDisplayStyle()"></script>
    <script>
        function renderMathWithDisplayStyle() {
            // Добавляем \displaystyle ко ВСЕМ дробям (включая вложенные)
            document.querySelectorAll('body *').forEach(el => {
                if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
                    let text = el.childNodes[0].textContent;
                    if (text.includes('$') && text.includes('\\frac')) {
                        // Заменяем ВСЕ \frac на \displaystyle\frac
                        text = text.replace(/\\frac\{/g, '\\displaystyle\\frac{');
                        el.childNodes[0].textContent = text;
                    }
                }
            });
            renderMathInElement(document.body, {
                delimiters: [
                    {left: '$$', right: '$$', display: true},
                    {left: '$', right: '$', display: false}
                ]
            });
        }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        .katex { font-size: 1.1em; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-5xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← Назад к темам</a>
        <div class="flex gap-2 flex-wrap justify-center">
            <span class="px-2 py-1 rounded bg-blue-500 text-white font-bold">06</span>
            <a href="{{ route('test.topic07') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">07</a>
            <a href="{{ route('test.topic08') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">08</a>
            <a href="{{ route('test.topic09') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">09</a>
            <a href="{{ route('test.topic10') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">10</a>
            <a href="{{ route('test.topic11') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">11</a>
            <a href="{{ route('test.topic12') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">12</a>
            <a href="{{ route('test.topic13') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">13</a>
            <a href="{{ route('test.topic14') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">14</a>
            <a href="{{ route('test.topic15') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">15</a>
            <a href="{{ route('test.topic16') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">16</a>
            <a href="{{ route('test.topic17') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">17</a>
            <a href="{{ route('test.topic18') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">18</a>
            <a href="{{ route('test.topic19') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">19</a>
        </div>
        <span class="text-slate-500">Формулы</span>
    </div>

    @php
        $totalTasks = 0;
        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] as $zadanie) {
                $totalTasks += count($zadanie['tasks'] ?? []);
            }
        }
    @endphp

    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">06. Дроби и степени</h1>
        <p class="text-slate-400 text-lg">Вычисления с дробями и степенями</p>
    </div>

    {{-- Stats --}}
    <div class="flex justify-center gap-6 mb-10">
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-blue-400 font-bold text-xl">{{ count($blocks) }}</span>
            <span class="text-slate-400 ml-2">блоков</span>
        </div>
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-blue-400 font-bold text-xl">{{ $totalTasks }}</span>
            <span class="text-slate-400 ml-2">заданий</span>
        </div>
    </div>

    @foreach($blocks as $blockIndex => $block)
    <div class="mb-12">
        {{-- Block Header --}}
        <div class="flex justify-between items-center mb-6 text-sm text-slate-500 italic border-b border-slate-700 pb-4">
            <span>Е. А. Ширяева</span>
            <span>Задачник ОГЭ 2026 (тренажер)</span>
        </div>

        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-white">06. Дроби и степени</h2>
            <p class="text-blue-400 text-lg mt-1">Блок {{ $block['number'] }}. {{ $block['title'] }}</p>
        </div>

        @foreach($block['zadaniya'] as $zadanie)
            <div class="mb-10">
                {{-- Zadanie Header --}}
                <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-blue-500">
                    <h3 class="text-lg font-semibold text-white">
                        Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}
                    </h3>
                </div>

                @if(isset($zadanie['tasks'][0]['denominator']))
                    {{-- Задание с знаменателем - формат параграфа --}}
                    <div class="space-y-3">
                        @foreach($zadanie['tasks'] as $task)
                            @php
                                $taskKey = "topic_06_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
                                $taskInfo = "Блок {$block['number']} ({$block['title']}), Задание {$zadanie['number']}, Задача {$task['id']}<br>Выражение: <code>{$task['expression']}</code>";
                            @endphp
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 task-review-item" data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">
                                <span class="text-blue-400 font-bold">{{ $task['id'] }})</span>
                                <span class="text-slate-200 ml-2">Представьте выражение ${{ $task['expression'] }}$ в виде дроби со знаменателем {{ $task['denominator'] }}. В ответ запишите числитель полученной дроби.</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Сетка задач --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($zadanie['tasks'] as $task)
                            @php
                                $taskKey = "topic_06_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
                                $taskInfo = "Блок {$block['number']} ({$block['title']}), Задание {$zadanie['number']}, Задача {$task['id']}<br>Выражение: <code>{$task['expression']}</code>";
                            @endphp
                            <div class="bg-slate-800/70 rounded-lg p-3 border border-slate-700 hover:border-slate-600 transition-colors task-review-item" data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">
                                <span class="text-blue-400 font-bold">{{ $task['id'] }})</span>
                                <span class="text-slate-200 ml-2">${{ $task['expression'] }}$</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    @endforeach

    {{-- Info Box --}}
    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 mt-10">
        <h4 class="text-white font-semibold mb-4">Информация о парсинге</h4>
        <div class="text-slate-400 text-sm space-y-2">
            <p><strong class="text-slate-300">Тема:</strong> 06. Дроби и степени</p>
            <p><strong class="text-slate-300">Источник:</strong> {{ $source ?? 'Manual' }}</p>
            <p><strong class="text-slate-300">Контроллер:</strong> <code class="bg-slate-700 px-2 py-1 rounded text-xs">TestPdfController::getAllBlocksData06()</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1">
                <li>Каждая задача: id, expression (LaTeX), answer, [denominator]</li>
                <li>Всего: {{ $totalTasks }} задач</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-slate-500 text-sm mt-8">Формулы отображаются с помощью KaTeX</p>
</div>

{{-- Инструмент для пометки заданий --}}
@include('components.task-review-tool', ['topicId' => '06'])

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Добавляем кнопки флагов ко всем заданиям
    document.querySelectorAll('.task-review-item').forEach(function(item) {
        const taskKey = item.dataset.taskKey;
        const taskInfo = item.dataset.taskInfo;
        if (taskKey && window.TaskReview) {
            window.TaskReview.addFlagButton(item, taskKey, taskInfo);
        }
    });
    // Обновляем UI после добавления кнопок
    if (window.TaskReview) {
        window.TaskReview.loadReviews();
    }
});
</script>

</body>
</html>
