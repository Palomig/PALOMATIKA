<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>13. Неравенства - Тест парсинга PDF</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathInElement(document.body, {delimiters: [{left: '$$', right: '$$', display: true}, {left: '$', right: '$', display: false}]});"></script>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-6xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← Назад к темам</a>
        <div class="flex gap-2 flex-wrap justify-center">
            <a href="{{ route('test.topic06') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">06</a>
            <a href="{{ route('test.topic07') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">07</a>
            <a href="{{ route('test.topic08') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">08</a>
            <a href="{{ route('test.topic09') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">09</a>
            <a href="{{ route('test.topic10') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">10</a>
            <a href="{{ route('test.topic11') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">11</a>
            <a href="{{ route('test.topic12') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">12</a>
            <span class="px-2 py-1 rounded bg-purple-500 text-white font-bold">13</span>
            <a href="{{ route('test.topic14') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">14</a>
            <a href="{{ route('test.topic15') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">15</a>
            <a href="{{ route('test.topic16') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">16</a>
            <a href="{{ route('test.topic17') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">17</a>
            <a href="{{ route('test.topic18') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">18</a>
            <a href="{{ route('test.topic19') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">19</a>
        </div>
        <span class="text-slate-500">PNG</span>
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
        <h1 class="text-4xl font-bold text-white mb-2">13. Неравенства</h1>
        <p class="text-slate-400 text-lg">Решение линейных и квадратных неравенств</p>
    </div>

    {{-- Stats --}}
    <div class="flex justify-center gap-6 mb-10">
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-purple-400 font-bold text-xl">{{ count($blocks) }}</span>
            <span class="text-slate-400 ml-2">блоков</span>
        </div>
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-purple-400 font-bold text-xl">{{ $totalTasks }}</span>
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
            <h2 class="text-2xl font-bold text-white">13. Неравенства</h2>
            <p class="text-purple-400 text-lg mt-1">Блок {{ $block['number'] }}. {{ $block['title'] }}</p>
        </div>

        @foreach($block['zadaniya'] as $zadanieIndex => $zadanie)
            <div class="mb-10">
                {{-- Zadanie Header --}}
                <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-purple-500">
                    <h3 class="text-lg font-semibold text-white">
                        Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}
                    </h3>
                </div>

                @if(($zadanie['type'] ?? '') === 'graphic')
                    {{-- Graphic type: display with images in grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($zadanie['tasks'] ?? [] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 hover:border-purple-500/50 transition-colors">
                                <div class="text-purple-400 font-bold mb-2">{{ $task['id'] }}.</div>
                                <div class="text-slate-200 text-lg mb-3">${{ $task['expression'] ?? '' }}$</div>

                                @if(isset($task['image']))
                                    <div class="bg-slate-900/50 rounded-lg p-3 text-center">
                                        @php
                                            $imgPath = '/images/tasks/13/' . $task['image'];
                                        @endphp
                                        <img src="{{ $imgPath }}"
                                             alt="Решение {{ $task['id'] }}"
                                             class="max-w-full h-auto rounded border border-slate-600 mx-auto"
                                             style="filter: invert(0.9) hue-rotate(180deg);"
                                             onerror="this.style.display='none'; this.parentNode.innerHTML='<span class=\'text-slate-500 text-sm\'>{{ $task['image'] }}</span>';">
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Choice type: regular list with options --}}
                    <div class="space-y-4">
                        @foreach($zadanie['tasks'] ?? [] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-slate-600 transition-colors">
                                <div class="flex items-start gap-4 mb-3">
                                    <span class="text-purple-400 font-bold text-lg flex-shrink-0">{{ $task['id'] }}.</span>
                                    <span class="text-slate-200 text-lg">${{ $task['expression'] ?? '' }}$</span>
                                </div>
                                @if(isset($task['options']))
                                    <div class="flex flex-wrap gap-3 ml-8">
                                        @foreach($task['options'] as $i => $opt)
                                            <span class="bg-slate-700/70 text-slate-300 px-4 py-2 rounded-lg text-sm hover:bg-slate-600 transition-colors cursor-default">
                                                {{ $i + 1 }}) {{ $opt }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
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
            <p><strong class="text-slate-300">Тема:</strong> 13. Неравенства</p>
            <p><strong class="text-slate-300">Источник:</strong> {{ $source ?? 'Manual' }}</p>
            <p><strong class="text-slate-300">Контроллер:</strong> <code class="bg-slate-700 px-2 py-1 rounded text-xs">TestPdfController::getAllBlocksData13()</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1">
                <li>Блок 1: ФИПИ (линейные, системы, квадратные неравенства)</li>
                <li>Блок 2: Расширенная версия (графическое решение)</li>
                <li>Всего: {{ $totalTasks }} задач</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-slate-500 text-sm mt-8">Изображения числовых прямых из PDF</p>
</div>

</body>
</html>
