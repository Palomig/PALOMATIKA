<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $topicId }}. {{ $topicMeta['title'] }} - ЕГЭ - PALOMATIKA</title>

    {{-- KaTeX для формул --}}
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

    {{-- Alpine.js для интерактивности --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { DEFAULT: '#1a1a2e', light: '#252542', lighter: '#2d2d4a' },
                        coral: { DEFAULT: '#ff6b6b', dark: '#e85555', light: '#ff8585' }
                    }
                }
            }
        }
    </script>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        .math-serif { font-family: 'PT Serif', Georgia, serif; }
        .number-line { font-family: 'Times New Roman', serif; }
        .geo-label { font-family: 'PT Serif', serif; font-style: italic; }
        .katex { font-size: 1.1em; }
    </style>

    @stack('styles')
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-950 via-slate-900 to-slate-900">

<div class="max-w-6xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-purple-900/30 rounded-xl p-4 border border-purple-800/50">
        <a href="{{ route('ege.index') }}" class="text-purple-400 hover:text-purple-300 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Назад к заданиям ЕГЭ
        </a>

        <div class="flex gap-1.5 flex-wrap justify-center">
            @foreach(['01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19'] as $tid)
                @if($tid === $topicId)
                    <span class="px-2.5 py-1 rounded-lg bg-purple-500 text-white font-bold text-xs">{{ $tid }}</span>
                @else
                    <a href="{{ route('ege.show', ['id' => ltrim($tid, '0')]) }}"
                       class="px-2.5 py-1 rounded-lg bg-purple-800/50 text-purple-300 hover:bg-purple-700/50 transition text-xs">{{ $tid }}</a>
                @endif
            @endforeach
        </div>

        <span class="text-purple-500 text-xs">{{ $stats['tasks'] ?? 0 }} заданий</span>
    </div>

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="inline-block bg-purple-600/20 text-purple-300 px-4 py-1 rounded-full text-sm font-medium mb-4">
            ЕГЭ профиль
        </div>
        <h1 class="text-4xl font-bold text-white mb-2">Задание {{ ltrim($topicId, '0') }}. {{ $topicMeta['title'] }}</h1>
        <p class="text-purple-300/70 text-lg">{{ $topicMeta['description'] }}</p>
    </div>

    {{-- Stats --}}
    <div class="flex justify-center gap-6 mb-10">
        <div class="bg-purple-900/30 px-6 py-3 rounded-xl border border-purple-800/50">
            <span class="text-purple-400 font-bold text-xl">{{ $stats['blocks'] ?? 0 }}</span>
            <span class="text-purple-300/60 ml-2">блоков</span>
        </div>
        <div class="bg-purple-900/30 px-6 py-3 rounded-xl border border-purple-800/50">
            <span class="text-purple-400 font-bold text-xl">{{ $stats['tasks'] ?? 0 }}</span>
            <span class="text-purple-300/60 ml-2">заданий</span>
        </div>
    </div>

    {{-- Content --}}
    @yield('content')

    {{-- Info Box --}}
    <div class="bg-purple-900/30 rounded-xl p-6 border border-purple-800/50 mt-10">
        <h4 class="text-white font-semibold mb-4">Информация</h4>
        <div class="text-purple-300/70 text-sm space-y-2">
            <p><strong class="text-purple-200">Задание:</strong> № {{ ltrim($topicId, '0') }}. {{ $topicMeta['title'] }}</p>
            <p><strong class="text-purple-200">Экзамен:</strong> ЕГЭ профильный уровень</p>
            <p><strong class="text-purple-200">Источник данных:</strong> <code class="bg-purple-800/50 px-2 py-1 rounded text-xs">storage/app/tasks/ege/topic_{{ $topicId }}.json</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1">
                <li>Блоков: {{ $stats['blocks'] ?? 0 }}</li>
                <li>Заданий: {{ $stats['zadaniya'] ?? 0 }}</li>
                <li>Всего задач: {{ $stats['tasks'] ?? 0 }}</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-purple-500/50 text-sm mt-8">Формулы отображаются с помощью KaTeX</p>
</div>

{{-- Инструмент для пометки заданий ЕГЭ --}}
@include('components.task-review-tool-ege', ['topicId' => $topicId])

@stack('scripts')

</body>
</html>
