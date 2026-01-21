<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ОГЭ-2025 Вариант {{ $variantNumber ?? 1 }} - PALOMATIKA</title>

    <!-- KaTeX for math rendering -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathWithDisplayStyle()"></script>
    <script>
        function renderMathWithDisplayStyle() {
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

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
        .number-line { font-family: 'Times New Roman', serif; }
        .geo-label {
            font-family: 'Times New Roman', serif;
            font-style: italic;
            font-weight: 500;
            user-select: none;
        }
        .katex { font-size: 1.1em; }

        /* Print styles */
        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            .no-print {
                display: none !important;
            }
            .task-card {
                break-inside: avoid;
                border: 1px solid #ccc !important;
                background: white !important;
            }
            .bg-slate-900, .bg-slate-800, .bg-slate-900\/50 {
                background: #f5f5f5 !important;
            }
            .text-white, .text-slate-200, .text-slate-300 {
                color: black !important;
            }
            .text-blue-400, .text-cyan-400, .text-emerald-400, .text-amber-400 {
                color: #1e40af !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-5xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="no-print flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.oge.generator') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← К генератору</a>
        <div class="flex gap-3">
            @php $newHash = substr(md5(uniqid(mt_rand(), true)), 0, 10); @endphp
            <a href="{{ route('test.oge.show', ['hash' => $newHash]) }}" class="px-3 py-1.5 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition">🎲 Новый вариант</a>
            <a href="{{ route('test.generator') }}" class="px-3 py-1.5 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition">Генератор</a>
            <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 transition">🖨️ Печать</button>
        </div>
    </div>

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="flex justify-between items-center text-sm text-slate-500 mb-4">
            <span>ОГЭ–2025</span>
            <span>palomatika.ru</span>
        </div>
        <h1 class="text-4xl font-bold text-white mb-2">Тренировочная работа № {{ $variantNumber ?? rand(1, 99) }}</h1>
        <p class="text-slate-400 text-lg">Задания 6–19 (Часть 1)</p>
    </div>

    {{-- Instructions --}}
    <div class="bg-slate-800/70 rounded-xl p-5 mb-8 border border-slate-700">
        <p class="text-slate-300 text-sm italic leading-relaxed">
            <strong class="text-white">Инструкция:</strong> Ответами к заданиям 6–19 являются число или последовательность цифр.
            Запишите ответ в поле ответа. Если ответом является последовательность цифр, то запишите её без пробелов, запятых и других дополнительных символов.
        </p>
    </div>

    {{-- Stats --}}
    <div class="no-print flex justify-center gap-6 mb-10">
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-emerald-400 font-bold text-xl">{{ count($tasks) }}</span>
            <span class="text-slate-400 ml-2">заданий</span>
        </div>
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-emerald-400 font-bold text-xl">{{ now()->format('d.m.Y') }}</span>
            <span class="text-slate-400 ml-2">дата</span>
        </div>
    </div>

    {{-- Tasks - используем унифицированный адаптер --}}
    @foreach($tasks as $index => $taskData)
        @php
            $taskNumber = 6 + $index;
            $topicId = $taskData['topic_id'] ?? '';

            // Определяем цвет акцента для разных тем
            $accentColors = [
                '06' => 'blue',
                '07' => 'cyan',
                '08' => 'violet',
                '09' => 'pink',
                '10' => 'orange',
                '11' => 'rose',
                '12' => 'lime',
                '13' => 'teal',
                '14' => 'indigo',
                '15' => 'emerald',
                '16' => 'amber',
                '17' => 'fuchsia',
                '18' => 'sky',
                '19' => 'red',
            ];
            $color = $accentColors[$topicId] ?? 'blue';
        @endphp

        @include('tasks.variant-task', [
            'taskData' => $taskData,
            'taskNumber' => $taskNumber,
            'color' => $color,
        ])
    @endforeach

    {{-- Footer --}}
    <div class="no-print text-center mt-10">
        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
            <p class="text-slate-400 mb-2">Вариант: <code class="bg-slate-700 px-2 py-1 rounded text-emerald-400">{{ $variantHash ?? 'unknown' }}</code></p>
            <p class="text-slate-500 text-sm mb-4">Ссылка на этот вариант сохраняется — можно поделиться</p>
            <div class="flex justify-center gap-4">
                <button onclick="window.print()" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">
                    🖨️ Распечатать
                </button>
                @php $footerHash = substr(md5(uniqid(mt_rand(), true)), 0, 10); @endphp
                <a href="{{ route('test.oge.show', ['hash' => $footerHash]) }}" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-white rounded-lg transition-colors">
                    🎲 Новый вариант
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
