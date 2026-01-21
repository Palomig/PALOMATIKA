<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $topicId }}. {{ $topicMeta['title'] }} (SVG) - PALOMATIKA</title>

    {{-- KaTeX --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMath()"></script>
    <script>
        function renderMath() {
            renderMathInElement(document.body, {
                delimiters: [
                    {left: '$$', right: '$$', display: true},
                    {left: '$', right: '$', display: false}
                ]
            });
        }
    </script>

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .katex { font-size: 1.1em; }
        /* GEOMETRY_SPEC: стили для SVG меток (синхронизировано с topic16.blade.php) */
        .geo-label {
            font-family: 'Times New Roman', serif;
            font-style: italic;
            font-weight: 500;
            user-select: none;
            pointer-events: none;
        }
        .geo-label-bold {
            font-family: 'Times New Roman', serif;
            font-style: normal;
            font-weight: 700;
            user-select: none;
            pointer-events: none;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-7xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.topic' . $topicId) }}" class="text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Назад к Alpine.js версии
        </a>

        <div class="flex gap-1.5 flex-wrap justify-center items-center">
            <span class="text-emerald-400 text-xs mr-2 px-2 py-1 bg-emerald-500/20 rounded-lg">SERVER SVG</span>
            @foreach(['15','16','17'] as $tid)
                @if($tid === $topicId)
                    <span class="px-2.5 py-1 rounded-lg bg-{{ $topicMeta['color'] ?? 'red' }}-500 text-white font-bold text-xs">{{ $tid }}</span>
                @else
                    <a href="{{ route('topics.svg', ['id' => ltrim($tid, '0')]) }}"
                       class="px-2.5 py-1 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition text-xs">{{ $tid }}</a>
                @endif
            @endforeach
        </div>

        <span class="text-slate-500 text-xs">{{ $stats['tasks'] ?? 0 }} заданий</span>
    </div>

    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">{{ $topicId }}. {{ $topicMeta['title'] }}</h1>
        <p class="text-slate-400 text-lg">{{ $topicMeta['description'] }}</p>
        <p class="text-emerald-400 text-sm mt-2">SVG рендерится на сервере (PHP)</p>
    </div>

    {{-- Stats --}}
    <div class="flex justify-center gap-6 mb-10">
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-{{ $topicMeta['color'] ?? 'red' }}-400 font-bold text-xl">{{ $stats['blocks'] ?? 0 }}</span>
            <span class="text-slate-400 ml-2">блоков</span>
        </div>
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-{{ $topicMeta['color'] ?? 'red' }}-400 font-bold text-xl">{{ $stats['zadaniya'] ?? 0 }}</span>
            <span class="text-slate-400 ml-2">типов заданий</span>
        </div>
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-{{ $topicMeta['color'] ?? 'red' }}-400 font-bold text-xl">{{ $stats['tasks'] ?? 0 }}</span>
            <span class="text-slate-400 ml-2">задач</span>
        </div>
    </div>

    {{-- Blocks --}}
    @foreach($blocks as $block)
    <div class="mb-12">
        {{-- Block Header --}}
        <div class="flex justify-between items-center mb-6 text-sm text-slate-500 italic border-b border-slate-700 pb-4">
            <span>Е. А. Ширяева</span>
            <span>Задачник ОГЭ 2026 (тренажер)</span>
        </div>

        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-white">{{ $topicId }}. {{ $topicMeta['title'] }}</h2>
            <p class="text-amber-400 text-lg mt-1">Блок {{ $block['number'] }}. {{ $block['title'] }}</p>
        </div>

        @foreach($block['zadaniya'] as $zadanie)
            <div class="mb-10">
                {{-- Zadanie Header --}}
                <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-emerald-500">
                    <h3 class="text-lg font-semibold text-white">
                        Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}
                    </h3>
                </div>

                {{-- Tasks Grid --}}
                <div class="grid md:grid-cols-2 {{ count($zadanie['tasks'] ?? []) > 6 ? 'lg:grid-cols-3' : '' }} gap-4">
                    @foreach($zadanie['tasks'] ?? [] as $task)
                        <div class="bg-slate-800 rounded-xl p-5 border border-slate-700 hover:border-slate-600 transition-all hover:shadow-lg hover:shadow-slate-900/50">
                            <div class="text-emerald-400 font-semibold mb-3">{{ $task['id'] ?? $loop->iteration }}.</div>
                            <div class="text-slate-200 text-sm leading-relaxed mb-4">{{ $task['text'] ?? '' }}</div>

                            {{-- Server-rendered SVG --}}
                            @if(isset($task['rendered_svg']))
                                <div class="bg-slate-900/50 rounded-lg p-3 flex items-center justify-center">
                                    {!! $task['rendered_svg'] !!}
                                </div>
                            @else
                                <div class="bg-slate-900/50 rounded-lg p-3 text-center text-slate-500 py-8">
                                    SVG не отрендерен
                                </div>
                            @endif


                            {{-- Answer --}}
                            @if(isset($task['answer']))
                                <div class="mt-3 text-sm">
                                    <span class="text-slate-500">Ответ:</span>
                                    <span class="text-emerald-400 font-mono ml-2">{{ $task['answer'] }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    @endforeach

    {{-- Info Box --}}
    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 mt-10">
        <h4 class="text-white font-semibold mb-4">Информация о рендеринге</h4>
        <div class="text-slate-400 text-sm space-y-2">
            <p><strong class="text-slate-300">Тема:</strong> {{ $topicId }}. {{ $topicMeta['title'] }}</p>
            <p><strong class="text-slate-300">Источник данных:</strong> <code class="bg-slate-700 px-2 py-1 rounded text-xs">storage/app/tasks/topic_{{ $topicId }}_geometry.json</code></p>
            <p><strong class="text-slate-300">Рендерер:</strong> <code class="bg-slate-700 px-2 py-1 rounded text-xs">App\Services\GeometrySvgRenderer</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1">
                <li>SVG генерируется на сервере без JavaScript</li>
                <li>Поддерживает 16 типов геометрии для треугольников</li>
                <li>Координаты и производные точки вычисляются в PHP</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-slate-500 text-sm mt-8">SVG отрисован на сервере через GeometrySvgRenderer</p>
</div>

</body>
</html>
