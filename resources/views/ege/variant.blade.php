<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ЕГЭ-2026 Вариант {{ $variantNumber ?? 1 }} - PALOMATIKA</title>

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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: {
                            DEFAULT: '#06090f',
                            50: '#0a0f1a',
                            100: '#0d1320',
                            200: '#111827',
                            300: '#1a2332',
                            400: '#243044',
                            500: '#2e3d56'
                        },
                        accent: {
                            DEFAULT: '#8b5cf6',
                            light: '#a78bfa',
                            dark: '#7c3aed'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        .katex { font-size: 1.1em; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0a0f1a; }
        ::-webkit-scrollbar-thumb { background: #2e3d56; border-radius: 4px; }

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
            .bg-dark, .bg-dark-100, .bg-dark-200, .bg-dark-300 {
                background: #f5f5f5 !important;
            }
            .text-white, .text-gray-200, .text-gray-300 {
                color: black !important;
            }
            .text-accent-light, .text-accent {
                color: #4c1d95 !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-dark text-gray-200">

<div class="max-w-5xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="no-print flex justify-between items-center mb-8 text-sm bg-dark-100 rounded-xl p-4 border border-dark-400/50">
        <a href="{{ route('ege.generator') }}" class="text-accent-light hover:text-accent transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            К генератору
        </a>
        <div class="flex gap-3">
            @php $newHash = substr(base_convert(mt_rand(), 10, 36), 0, 6); @endphp
            <a href="{{ route('ege.variant', ['hash' => $newHash]) }}" class="px-3 py-1.5 rounded-lg bg-dark-300 text-gray-300 hover:bg-dark-400 transition">Новый вариант</a>
            <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-accent text-white hover:bg-accent-dark transition">Печать</button>
        </div>
    </div>

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="flex justify-between items-center text-sm text-gray-600 mb-4">
            <span>ЕГЭ–2026 (профиль)</span>
            <span>palomatika.ru</span>
        </div>
        <h1 class="text-4xl font-bold text-white mb-2">Тренировочная работа № {{ $variantNumber ?? rand(1, 99) }}</h1>
        <p class="text-gray-400 text-lg">Профильный уровень</p>
    </div>

    {{-- Instructions --}}
    <div class="bg-dark-100 rounded-xl p-5 mb-8 border border-dark-400/50">
        <p class="text-gray-300 text-sm italic leading-relaxed">
            <strong class="text-white">Инструкция.</strong> Работа состоит из заданий с кратким и развёрнутым ответом.
            Задания 1–12 — это задания с кратким ответом (часть 1).
            Задания 13–19 — это задания с развёрнутым ответом (часть 2).
        </p>
    </div>

    {{-- Stats --}}
    <div class="no-print flex justify-center gap-6 mb-10">
        <div class="bg-dark-100 px-6 py-3 rounded-xl border border-dark-400/50">
            <span class="text-accent-light font-bold text-xl">{{ count($tasks) }}</span>
            <span class="text-gray-500 ml-2">заданий</span>
        </div>
        <div class="bg-dark-100 px-6 py-3 rounded-xl border border-dark-400/50">
            <span class="text-accent-light font-bold text-xl">{{ now()->format('d.m.Y') }}</span>
            <span class="text-gray-500 ml-2">дата</span>
        </div>
    </div>

    {{-- Part 1 Header --}}
    @php
        $part1Tasks = [];
        $part2Tasks = [];
        foreach ($tasks as $t) {
            $num = (int) ltrim($t['topic_id'] ?? '00', '0');
            if ($num <= 12) {
                $part1Tasks[] = $t;
            } else {
                $part2Tasks[] = $t;
            }
        }
    @endphp

    @if(!empty($part1Tasks))
        <div class="mb-6">
            <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl px-5 py-3 mb-4">
                <h2 class="text-blue-400 font-semibold text-lg">Часть 1</h2>
                <p class="text-gray-400 text-sm">Задания с кратким ответом</p>
            </div>

            @foreach($part1Tasks as $index => $taskData)
                @php
                    $taskNumber = (int) ltrim($taskData['topic_id'] ?? '00', '0');
                @endphp

                <div class="task-card mb-6 bg-dark-100 rounded-xl border border-dark-400/50 overflow-hidden">
                    <div class="p-4 border-b border-dark-400/30 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">
                            {{ $taskNumber }}
                        </div>
                        <div class="flex-1">
                            <div class="text-white font-medium">{{ $taskData['instruction'] ?? '' }}</div>
                            <div class="text-gray-600 text-sm mt-1">{{ $taskData['topic_title'] ?? '' }}</div>
                        </div>
                    </div>
                    <div class="p-5">
                        @php $task = $taskData['task'] ?? []; @endphp
                        @if(!empty($task['text']))
                            <p class="text-gray-200 leading-relaxed latex-content">{{ $task['text'] }}</p>
                        @endif
                        @if(!empty($task['expression']))
                            <p class="text-gray-200 text-lg mt-2 latex-content">${{ $task['expression'] }}$</p>
                        @endif
                    </div>
                    <div class="p-5 border-t border-dark-400/30">
                        <div class="flex items-center gap-4">
                            <span class="text-gray-500 text-sm font-medium">Ответ:</span>
                            <input type="text"
                                   class="flex-1 max-w-xs px-4 py-2 bg-dark-300 border border-dark-400 rounded-lg text-white placeholder-gray-600 focus:outline-none focus:border-accent transition-colors"
                                   placeholder="Введите ответ">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Part 2 Header --}}
    @if(!empty($part2Tasks))
        <div class="mb-6">
            <div class="bg-fuchsia-500/10 border border-fuchsia-500/30 rounded-xl px-5 py-3 mb-4">
                <h2 class="text-fuchsia-400 font-semibold text-lg">Часть 2</h2>
                <p class="text-gray-400 text-sm">Задания с развёрнутым ответом</p>
            </div>

            @foreach($part2Tasks as $index => $taskData)
                @php
                    $taskNumber = (int) ltrim($taskData['topic_id'] ?? '00', '0');
                @endphp

                <div class="task-card mb-6 bg-dark-100 rounded-xl border border-dark-400/50 overflow-hidden">
                    <div class="p-4 border-b border-dark-400/30 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-fuchsia-500 to-fuchsia-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">
                            {{ $taskNumber }}
                        </div>
                        <div class="flex-1">
                            <div class="text-white font-medium">{{ $taskData['instruction'] ?? '' }}</div>
                            <div class="text-gray-600 text-sm mt-1">{{ $taskData['topic_title'] ?? '' }}</div>
                        </div>
                    </div>
                    <div class="p-5">
                        @php $task = $taskData['task'] ?? []; @endphp
                        @if(!empty($task['text']))
                            <p class="text-gray-200 leading-relaxed latex-content">{{ $task['text'] }}</p>
                        @endif
                        @if(!empty($task['expression']))
                            <p class="text-gray-200 text-lg mt-2 latex-content">${{ $task['expression'] }}$</p>
                        @endif
                        @if(!empty($task['interval']))
                            <p class="text-gray-300 mt-4 latex-content">
                                <span class="text-fuchsia-400 font-medium">б)</span>
                                Укажите корни, принадлежащие промежутку {{ $task['interval'] }}
                            </p>
                        @endif
                    </div>
                    <div class="p-5 border-t border-dark-400/30">
                        <p class="text-gray-500 text-sm italic">Запишите решение и ответ на отдельном листе.</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Footer --}}
    <div class="no-print text-center mt-10">
        <div class="bg-dark-100 rounded-xl p-6 border border-dark-400/50">
            <p class="text-gray-400 mb-2">Вариант: <code class="bg-dark-300 px-2 py-1 rounded text-accent-light">{{ $variantHash ?? 'unknown' }}</code></p>
            <p class="text-gray-600 text-sm mb-4">Ссылка на этот вариант сохраняется — можно поделиться</p>
            <div class="flex justify-center gap-4">
                <button onclick="window.print()" class="px-6 py-3 bg-dark-300 hover:bg-dark-400 text-white rounded-lg transition-colors">
                    Распечатать
                </button>
                @php $footerHash = substr(base_convert(mt_rand(), 10, 36), 0, 6); @endphp
                <a href="{{ route('ege.variant', ['hash' => $footerHash]) }}" class="px-6 py-3 bg-gradient-to-r from-accent to-accent-dark hover:from-accent-light hover:to-accent text-white rounded-lg transition-colors">
                    Новый вариант
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
