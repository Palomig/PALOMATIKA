<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>16. Окружность, круг и их элементы - Тест парсинга PDF</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathInElement(document.body, {delimiters: [{left: '$$', right: '$$', display: true}, {left: '$', right: '$', display: false}]});"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
        .geo-line { transition: stroke 0.2s ease, stroke-width 0.2s ease; }
        .geo-point { transition: r 0.2s ease, fill 0.2s ease; }
        .geo-label {
            font-family: 'Times New Roman', serif;
            font-style: italic;
            font-weight: 500;
            user-select: none;
            pointer-events: none;
        }
        /* Увеличиваем размер KaTeX формул */
        .katex { font-size: 1.3em; }
        /* Для дробей делаем ещё крупнее */
        .katex .mfrac { font-size: 1.1em; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-7xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.pdf.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← Назад к парсеру</a>
        <div class="flex gap-2 flex-wrap justify-center">
            <a href="{{ route('test.topic06') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">06</a>
            <a href="{{ route('test.topic07') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">07</a>
            <a href="{{ route('test.topic08') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">08</a>
            <a href="{{ route('test.topic09') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">09</a>
            <a href="{{ route('test.topic10') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">10</a>
            <a href="{{ route('test.topic11') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">11</a>
            <a href="{{ route('test.topic12') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">12</a>
            <a href="{{ route('test.topic13') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">13</a>
            <a href="{{ route('test.topic14') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">14</a>
            <a href="{{ route('test.topic15') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">15</a>
            <span class="px-2 py-1 rounded bg-purple-500 text-white font-bold">16</span>
            <a href="{{ route('test.topic18') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">18</a>
            <a href="{{ route('test.topic19') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">19</a>
        </div>
        <span class="text-slate-500">SVG версия</span>
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
        <h1 class="text-4xl font-bold text-white mb-2">16. Окружность, круг и их элементы</h1>
        <p class="text-slate-400 text-lg">Геометрические задачи на окружности</p>
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

    @foreach($blocks as $block)
    <div class="mb-12">
        {{-- Block Header --}}
        <div class="flex justify-between items-center mb-6 text-sm text-slate-500 italic border-b border-slate-700 pb-4">
            <span>Е. А. Ширяева</span>
            <span>Задачник ОГЭ 2026 (тренажер)</span>
        </div>

        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-white">16. Окружность, круг и их элементы</h2>
            <p class="text-purple-400 text-lg mt-1">Блок {{ $block['number'] }}. {{ $block['title'] }}</p>
        </div>

        @foreach($block['zadaniya'] as $zadanie)
            <div class="mb-10">
                {{-- Zadanie Header --}}
                <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-purple-500">
                    <h3 class="text-lg font-semibold text-white">
                        Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}
                    </h3>
                </div>

                {{-- Tasks Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($zadanie['tasks'] ?? [] as $task)
                        <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 hover:border-purple-500/50 transition-colors">
                            <div class="text-purple-400 font-semibold mb-2">{{ $task['id'] }}.</div>
                            <div class="text-slate-300 text-sm leading-relaxed mb-3">{{ $task['text'] }}</div>

                            {{-- SVG Image based on zadanie number --}}
                            <div class="bg-slate-900/50 rounded-lg p-3">
                                @switch($zadanie['number'])
                                    @case(1)
                                        {{-- Квадрат ABCD с окружностью, центр O на середине CD --}}
                                        <div x-data="squareCircleSVG()">
                                            <svg viewBox="0 0 200 180" class="w-full h-40">
                                                {{-- Квадрат ABCD --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Окружность с центром O --}}
                                                <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Радиус OA --}}
                                                <line :x1="O.x" :y1="O.y" :x2="A.x" :y2="A.y" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                {{-- Точки вершин --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#dc2626"/>
                                                <circle :cx="O.x" :cy="O.y" r="4" fill="#8b5cf6"/>
                                                {{-- Метки вершин --}}
                                                <text :x="A.x - 15" :y="A.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 15" :y="B.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 12" :y="C.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 12" :y="D.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start">D</text>
                                                <text :x="O.x" :y="O.y + 18" fill="#8b5cf6" font-size="14" class="geo-label" text-anchor="middle">O</text>
                                                {{-- Метка радиуса --}}
                                                <text :x="(O.x + A.x)/2 - 10" :y="(O.y + A.y)/2" fill="#f59e0b" font-size="12" class="geo-label">R</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(2)
                                        {{-- Касательные из внешней точки к окружности --}}
                                        <div x-data="tangentLinesSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                {{-- Окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Касательные PA и PB --}}
                                                <line :x1="P.x" :y1="P.y" :x2="A.x" :y2="A.y" stroke="#dc2626" stroke-width="2"/>
                                                <line :x1="P.x" :y1="P.y" :x2="B.x" :y2="B.y" stroke="#dc2626" stroke-width="2"/>
                                                {{-- Радиусы OA и OB --}}
                                                <line :x1="O.x" :y1="O.y" :x2="A.x" :y2="A.y" stroke="#f59e0b" stroke-width="1.5"/>
                                                <line :x1="O.x" :y1="O.y" :x2="B.x" :y2="B.y" stroke="#f59e0b" stroke-width="1.5"/>
                                                {{-- Прямые углы при A и B --}}
                                                <path :d="rightAnglePath(A, P, O, 8)" fill="none" stroke="#10b981" stroke-width="1.5"/>
                                                <path :d="rightAnglePath(B, P, O, 8)" fill="none" stroke="#10b981" stroke-width="1.5"/>
                                                {{-- Угол при P --}}
                                                <path :d="makeAngleArc(P, A, B, 18)" fill="none" stroke="#ec4899" stroke-width="2"/>
                                                {{-- Точки --}}
                                                <circle :cx="O.x" :cy="O.y" r="4" fill="#8b5cf6"/>
                                                <circle :cx="P.x" :cy="P.y" r="4" fill="#dc2626"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#f59e0b"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#f59e0b"/>
                                                {{-- Метки --}}
                                                <text :x="O.x" :y="O.y - 12" fill="#8b5cf6" font-size="14" class="geo-label" text-anchor="middle">O</text>
                                                <text :x="P.x - 12" :y="P.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="end">P</text>
                                                <text :x="A.x + 10" :y="A.y - 8" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start">A</text>
                                                <text :x="B.x + 10" :y="B.y + 12" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start">B</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(3)
                                        {{-- Вписанный угол: треугольник ABC вписан в окружность --}}
                                        <div x-data="inscribedAngleSVG()">
                                            <svg viewBox="0 0 200 170" class="w-full h-40">
                                                {{-- Окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Треугольник ABC --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2"/>
                                                {{-- Центральный угол AOB --}}
                                                <line :x1="O.x" :y1="O.y" :x2="A.x" :y2="A.y" stroke="#f59e0b" stroke-width="1.5"/>
                                                <line :x1="O.x" :y1="O.y" :x2="B.x" :y2="B.y" stroke="#f59e0b" stroke-width="1.5"/>
                                                <path :d="makeAngleArc(O, A, B, 18)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                                                {{-- Вписанный угол ACB --}}
                                                <path :d="makeAngleArc(C, A, B, 22)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                                                {{-- Точки --}}
                                                <circle :cx="O.x" :cy="O.y" r="4" fill="#8b5cf6"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                {{-- Метки --}}
                                                <text :x="O.x + 12" :y="O.y + 5" fill="#8b5cf6" font-size="14" class="geo-label" text-anchor="start">O</text>
                                                <text :x="A.x - 12" :y="A.y + 15" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x + 12" :y="B.y + 15" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start">B</text>
                                                <text :x="C.x" :y="C.y - 12" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle">C</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(4)
                                        {{-- Два диаметра AC и BD --}}
                                        <div x-data="twodiametersSVG()">
                                            <svg viewBox="0 0 200 170" class="w-full h-40">
                                                {{-- Окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Диаметр AC --}}
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#3b82f6" stroke-width="2"/>
                                                {{-- Диаметр BD --}}
                                                <line :x1="B.x" :y1="B.y" :x2="D.x" :y2="D.y" stroke="#f59e0b" stroke-width="2"/>
                                                {{-- Хорда CB для угла ACB --}}
                                                <line :x1="C.x" :y1="C.y" :x2="B.x" :y2="B.y" stroke="#dc2626" stroke-width="2"/>
                                                {{-- Угол AOD --}}
                                                <path :d="makeAngleArc(O, A, D, 20)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                                                {{-- Угол ACB --}}
                                                <path :d="makeAngleArc(C, A, B, 18)" fill="none" stroke="#ec4899" stroke-width="2"/>
                                                {{-- Точки --}}
                                                <circle :cx="O.x" :cy="O.y" r="4" fill="#8b5cf6"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#3b82f6"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#f59e0b"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#3b82f6"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#f59e0b"/>
                                                {{-- Метки --}}
                                                <text :x="O.x - 12" :y="O.y - 8" fill="#8b5cf6" font-size="14" class="geo-label" text-anchor="end">O</text>
                                                <text :x="A.x - 12" :y="A.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x" :y="B.y - 12" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle">B</text>
                                                <text :x="C.x + 12" :y="C.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x" :y="D.y + 18" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle">D</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(5)
                                        {{-- Диаметр AB с точками M и N по разные стороны --}}
                                        <div x-data="diameterPointsSVG()">
                                            <svg viewBox="0 0 200 170" class="w-full h-40">
                                                {{-- Окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Диаметр AB --}}
                                                <line :x1="A.x" :y1="A.y" :x2="B.x" :y2="B.y" stroke="#3b82f6" stroke-width="2.5"/>
                                                {{-- Хорды NB и NM --}}
                                                <line :x1="N.x" :y1="N.y" :x2="B.x" :y2="B.y" stroke="#dc2626" stroke-width="2"/>
                                                <line :x1="N.x" :y1="N.y" :x2="M.x" :y2="M.y" stroke="#f59e0b" stroke-width="2"/>
                                                <line :x1="M.x" :y1="M.y" :x2="B.x" :y2="B.y" stroke="#10b981" stroke-width="2"/>
                                                {{-- Угол NBA --}}
                                                <path :d="makeAngleArc(B, N, A, 20)" fill="none" stroke="#ec4899" stroke-width="2.5"/>
                                                {{-- Угол NMB (прямой, опирается на диаметр) --}}
                                                <path :d="rightAnglePath(M, N, B, 10)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                {{-- Точки --}}
                                                <circle :cx="O.x" :cy="O.y" r="3" fill="#8b5cf6"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#3b82f6"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#3b82f6"/>
                                                <circle :cx="M.x" :cy="M.y" r="4" fill="#f59e0b"/>
                                                <circle :cx="N.x" :cy="N.y" r="4" fill="#dc2626"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x + 12" :y="B.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start">B</text>
                                                <text :x="M.x" :y="M.y + 18" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle">M</text>
                                                <text :x="N.x" :y="N.y - 12" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle">N</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(6)
                                        {{-- Вписанная окружность в трапецию --}}
                                        <div x-data="inscribedTrapezoidSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                {{-- Трапеция ABCD --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Вписанная окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="r" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Высота (радиус × 2) --}}
                                                <line :x1="O.x" :y1="A.y" :x2="O.x" :y2="B.y" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                {{-- Радиус r --}}
                                                <line :x1="O.x" :y1="O.y" :x2="O.x" :y2="O.y + r" stroke="#10b981" stroke-width="2"/>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#dc2626"/>
                                                <circle :cx="O.x" :cy="O.y" r="3" fill="#8b5cf6"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 10" :y="A.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 10" :y="B.y - 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 10" :y="C.y - 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 10" :y="D.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">D</text>
                                                <text :x="O.x + 12" :y="O.y + 5" fill="#8b5cf6" font-size="12" class="geo-label" text-anchor="start">O</text>
                                                <text :x="O.x + 8" :y="O.y + r/2 + 5" fill="#10b981" font-size="12" class="geo-label" text-anchor="start">r</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(7)
                                        {{-- Вписанная окружность в квадрат --}}
                                        <div x-data="inscribedSquareSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                {{-- Квадрат --}}
                                                <rect :x="A.x" :y="B.y" :width="side" :height="side"
                                                    fill="none" stroke="#dc2626" stroke-width="2"/>
                                                {{-- Вписанная окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="r" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Радиус --}}
                                                <line :x1="O.x" :y1="O.y" :x2="O.x + r" :y2="O.y" stroke="#10b981" stroke-width="2"/>
                                                {{-- Диагональ (для задач с диагональю) --}}
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                {{-- Точки вершин --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#dc2626"/>
                                                <circle :cx="O.x" :cy="O.y" r="3" fill="#8b5cf6"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 10" :y="A.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 10" :y="B.y - 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 10" :y="C.y - 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 10" :y="D.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">D</text>
                                                <text :x="O.x" :y="O.y - 10" fill="#8b5cf6" font-size="12" class="geo-label" text-anchor="middle">O</text>
                                                <text :x="O.x + r/2" :y="O.y - 6" fill="#10b981" font-size="11" class="geo-label" text-anchor="middle">r</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(8)
                                        {{-- Четырёхугольник описан около окружности --}}
                                        <div x-data="circumscribedQuadSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                {{-- Четырёхугольник ABCD --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Вписанная окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="r" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Метки сторон --}}
                                                <text :x="(A.x + B.x)/2 - 15" :y="(A.y + B.y)/2" fill="#f59e0b" font-size="11" class="geo-label" text-anchor="end">AB</text>
                                                <text :x="(B.x + C.x)/2" :y="(B.y + C.y)/2 - 10" fill="#f59e0b" font-size="11" class="geo-label" text-anchor="middle">BC</text>
                                                <text :x="(C.x + D.x)/2 + 15" :y="(C.y + D.y)/2" fill="#f59e0b" font-size="11" class="geo-label" text-anchor="start">CD</text>
                                                <text :x="(D.x + A.x)/2" :y="(D.y + A.y)/2 + 15" fill="#10b981" font-size="11" class="geo-label" text-anchor="middle">AD=?</text>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#dc2626"/>
                                                {{-- Метки вершин --}}
                                                <text :x="A.x - 10" :y="A.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x" :y="B.y - 10" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">B</text>
                                                <text :x="C.x + 10" :y="C.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x" :y="D.y + 18" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">D</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(9)
                                        {{-- Треугольник с вписанной окружностью --}}
                                        <div x-data="inscribedTriangleSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                {{-- Треугольник --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2"/>
                                                {{-- Вписанная окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="r" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Радиус --}}
                                                <line :x1="O.x" :y1="O.y" :x2="O.x" :y2="O.y + r" stroke="#10b981" stroke-width="2"/>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <circle :cx="O.x" :cy="O.y" r="3" fill="#8b5cf6"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x + 12" :y="B.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">B</text>
                                                <text :x="C.x" :y="C.y - 10" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">C</text>
                                                <text :x="O.x + 10" :y="O.y" fill="#8b5cf6" font-size="12" class="geo-label" text-anchor="start">O</text>
                                                <text :x="O.x + 8" :y="O.y + r/2 + 5" fill="#10b981" font-size="11" class="geo-label" text-anchor="start">r</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(10)
                                        {{-- Четырёхугольник вписан в окружность (углы) --}}
                                        <div x-data="inscribedQuadAngleSVG()">
                                            <svg viewBox="0 0 200 170" class="w-full h-40">
                                                {{-- Окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Четырёхугольник ABCD --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Диагональ AC --}}
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                {{-- Угол ABC --}}
                                                <path :d="makeAngleArc(B, A, C, 15)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                {{-- Угол CAD --}}
                                                <path :d="makeAngleArc(A, C, D, 18)" fill="none" stroke="#ec4899" stroke-width="2"/>
                                                {{-- Точки --}}
                                                <circle :cx="O.x" :cy="O.y" r="3" fill="#8b5cf6"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#dc2626"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x" :y="B.y + 18" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">B</text>
                                                <text :x="C.x + 12" :y="C.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x" :y="D.y - 12" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">D</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(11)
                                        {{-- Центр окружности на стороне треугольника --}}
                                        <div x-data="centerOnSideSVG()">
                                            <svg viewBox="0 0 200 170" class="w-full h-40">
                                                {{-- Окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Треугольник ABC (прямоугольный в C) --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2"/>
                                                {{-- Прямой угол в C --}}
                                                <path :d="rightAnglePath(C, A, B, 12)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                {{-- Радиус R --}}
                                                <line :x1="O.x" :y1="O.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                {{-- Точки --}}
                                                <circle :cx="O.x" :cy="O.y" r="4" fill="#8b5cf6"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x + 12" :y="B.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">B</text>
                                                <text :x="C.x" :y="C.y + 18" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">C</text>
                                                <text :x="O.x" :y="O.y - 10" fill="#8b5cf6" font-size="12" class="geo-label" text-anchor="middle">O</text>
                                                <text :x="(O.x + C.x)/2 + 8" :y="(O.y + C.y)/2" fill="#f59e0b" font-size="11" class="geo-label" text-anchor="start">R</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(12)
                                        {{-- Трапеция вписана в окружность --}}
                                        <div x-data="inscribedTrapezoidInCircleSVG()">
                                            <svg viewBox="0 0 200 170" class="w-full h-40">
                                                {{-- Окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Трапеция ABCD (равнобедренная) --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Углы A и C --}}
                                                <path :d="makeAngleArc(A, D, B, 18)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                <path :d="makeAngleArc(C, B, D, 18)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#dc2626"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 8" :y="B.y - 10" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 8" :y="C.y - 10" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 12" :y="D.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">D</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(13)
                                        {{-- Описанная окружность (квадрат/равносторонний) --}}
                                        <div x-data="circumscribedCircleSVG()">
                                            <svg viewBox="0 0 200 170" class="w-full h-40">
                                                {{-- Окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Квадрат или равносторонний треугольник --}}
                                                <polygon :points="shapePoints"
                                                    fill="none" stroke="#dc2626" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Радиус R --}}
                                                <line :x1="O.x" :y1="O.y" :x2="vertexX" :y2="vertexY" stroke="#f59e0b" stroke-width="2"/>
                                                {{-- Центр --}}
                                                <circle :cx="O.x" :cy="O.y" r="3" fill="#8b5cf6"/>
                                                {{-- Метка радиуса --}}
                                                <text :x="O.x" :y="O.y - 10" fill="#8b5cf6" font-size="12" class="geo-label" text-anchor="middle">O</text>
                                                <text :x="(O.x + vertexX)/2 + 8" :y="(O.y + vertexY)/2" fill="#f59e0b" font-size="12" class="geo-label" text-anchor="start">R</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(14)
                                        {{-- Расширенная теорема синусов --}}
                                        <div x-data="sineLawSVG()">
                                            <svg viewBox="0 0 200 170" class="w-full h-40">
                                                {{-- Окружность --}}
                                                <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Треугольник ABC --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2"/>
                                                {{-- Сторона AB (противолежит углу C) --}}
                                                <line :x1="A.x" :y1="A.y" :x2="B.x" :y2="B.y" stroke="#f59e0b" stroke-width="3"/>
                                                {{-- Угол C --}}
                                                <path :d="makeAngleArc(C, A, B, 20)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                                                {{-- Радиус R --}}
                                                <line :x1="O.x" :y1="O.y" :x2="A.x" :y2="A.y" stroke="#ec4899" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                {{-- Точки --}}
                                                <circle :cx="O.x" :cy="O.y" r="3" fill="#8b5cf6"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x + 12" :y="B.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">B</text>
                                                <text :x="C.x" :y="C.y - 12" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">C</text>
                                                <text :x="(A.x + B.x)/2" :y="(A.y + B.y)/2 + 18" fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">AB</text>
                                                <text :x="O.x + 10" :y="O.y" fill="#8b5cf6" font-size="12" class="geo-label" text-anchor="start">O</text>
                                            </svg>
                                        </div>
                                        @break

                                    @default
                                        <div class="text-slate-500 text-center py-8">Изображение</div>
                                @endswitch
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    @endforeach

    {{-- Info Box --}}
    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 mt-10">
        <h4 class="text-white font-semibold mb-4">Информация о парсинге</h4>
        <div class="text-slate-400 text-sm space-y-2">
            <p><strong class="text-slate-300">Тема:</strong> 16. Окружность, круг и их элементы</p>
            <p><strong class="text-slate-300">Источник:</strong> {{ $source ?? 'Manual' }}</p>
            <p><strong class="text-slate-300">Контроллер:</strong> <code class="bg-slate-700 px-2 py-1 rounded text-xs">TestPdfController::getAllBlocksData16()</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1">
                <li>Блок 1: ФИПИ (касательные, вписанные углы, диаметры, вписанные и описанные окружности)</li>
                <li>Задания 1-5: Базовые задачи на окружности (касательные, вписанные углы)</li>
                <li>Задания 6-9: Вписанные окружности (трапеция, квадрат, четырёхугольник)</li>
                <li>Задания 10-12: Описанные окружности (четырёхугольники в окружности)</li>
                <li>Задания 13-14: Радиусы описанных окружностей, теорема синусов</li>
                <li>Всего: {{ $totalTasks }} задач с SVG изображениями</li>
                <li>Все изображения генерируются программно через Alpine.js</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-slate-500 text-sm mt-8">Все изображения генерируются программно через SVG + Alpine.js</p>
</div>

<script>
    /**
     * ========================================================================
     * ПРАВИЛА ПОЗИЦИОНИРОВАНИЯ МЕТОК В SVG (GEOMETRY_SPEC) - Окружности
     * ========================================================================
     *
     * 1. МЕТКИ ТОЧЕК НА ОКРУЖНОСТИ:
     *    - Располагать снаружи окружности, вдоль радиуса от центра
     *    - Расстояние от точки: 12-15px
     *
     * 2. МЕТКА ЦЕНТРА O:
     *    - Располагать внутри окружности если есть свободное место
     *    - Или снизу/сбоку от центра
     *
     * 3. МЕТКИ УГЛОВ:
     *    - Угловые дуги рисовать радиусом 18-22px
     *    - Не перекрывать другие элементы
     *
     * 4. ИЗБЕЖАНИЕ НАЛОЖЕНИЙ:
     *    - Проверять что метки не накладываются на линии и дуги
     *    - Минимальное расстояние между метками: 15px
     *
     * ========================================================================
     */

    // Функция для создания дуги угла
    function makeAngleArc(vertex, point1, point2, radius) {
        const angle1 = Math.atan2(point1.y - vertex.y, point1.x - vertex.x);
        const angle2 = Math.atan2(point2.y - vertex.y, point2.x - vertex.x);

        let startAngle = angle1;
        let endAngle = angle2;

        // Ensure we draw the smaller arc
        let diff = endAngle - startAngle;
        while (diff < -Math.PI) diff += 2 * Math.PI;
        while (diff > Math.PI) diff -= 2 * Math.PI;

        if (diff < 0) {
            [startAngle, endAngle] = [endAngle, startAngle];
            diff = -diff;
        }

        const largeArc = diff > Math.PI ? 1 : 0;

        const x1 = vertex.x + radius * Math.cos(startAngle);
        const y1 = vertex.y + radius * Math.sin(startAngle);
        const x2 = vertex.x + radius * Math.cos(endAngle);
        const y2 = vertex.y + radius * Math.sin(endAngle);

        return `M ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2}`;
    }

    // Функция для прямого угла
    function rightAnglePath(vertex, point1, point2, size) {
        const dx1 = point1.x - vertex.x;
        const dy1 = point1.y - vertex.y;
        const len1 = Math.sqrt(dx1 * dx1 + dy1 * dy1);
        const ux1 = dx1 / len1;
        const uy1 = dy1 / len1;

        const dx2 = point2.x - vertex.x;
        const dy2 = point2.y - vertex.y;
        const len2 = Math.sqrt(dx2 * dx2 + dy2 * dy2);
        const ux2 = dx2 / len2;
        const uy2 = dy2 / len2;

        const p1 = { x: vertex.x + ux1 * size, y: vertex.y + uy1 * size };
        const p2 = { x: vertex.x + (ux1 + ux2) * size, y: vertex.y + (uy1 + uy2) * size };
        const p3 = { x: vertex.x + ux2 * size, y: vertex.y + uy2 * size };

        return `M ${p1.x} ${p1.y} L ${p2.x} ${p2.y} L ${p3.x} ${p3.y}`;
    }

    // 1. Квадрат ABCD с окружностью (центр O на середине CD)
    function squareCircleSVG() {
        // Квадрат: A - левый нижний, B - левый верхний, C - правый верхний, D - правый нижний
        const side = 100;
        const startX = 50;
        const startY = 30;

        const A = { x: startX, y: startY + side };          // левый нижний
        const B = { x: startX, y: startY };                  // левый верхний
        const C = { x: startX + side, y: startY };           // правый верхний
        const D = { x: startX + side, y: startY + side };    // правый нижний

        // O - середина CD
        const O = { x: (C.x + D.x) / 2, y: (C.y + D.y) / 2 };

        // Радиус = расстояние OA
        const R = Math.sqrt((A.x - O.x) ** 2 + (A.y - O.y) ** 2);

        return { A, B, C, D, O, R };
    }

    // 2. Касательные из точки P к окружности
    function tangentLinesSVG() {
        const O = { x: 120, y: 80 };
        const R = 40;
        const P = { x: 30, y: 80 };  // Внешняя точка слева

        // Расстояние от P до O
        const dist = Math.sqrt((O.x - P.x) ** 2 + (O.y - P.y) ** 2);

        // Угол от P к O
        const angleToO = Math.atan2(O.y - P.y, O.x - P.x);

        // Угол касательной относительно линии PO
        const tangentAngle = Math.acos(R / dist);

        // Точки касания A и B
        const A = {
            x: O.x + R * Math.cos(angleToO + Math.PI - tangentAngle),
            y: O.y + R * Math.sin(angleToO + Math.PI - tangentAngle)
        };
        const B = {
            x: O.x + R * Math.cos(angleToO + Math.PI + tangentAngle),
            y: O.y + R * Math.sin(angleToO + Math.PI + tangentAngle)
        };

        return {
            O, R, P, A, B,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => rightAnglePath(v, p1, p2, s)
        };
    }

    // 3. Вписанный угол (треугольник ABC в окружности)
    function inscribedAngleSVG() {
        const O = { x: 100, y: 90 };
        const R = 60;

        // A и B на окружности внизу
        const angleA = Math.PI * 0.8;  // ~144 градусов
        const angleB = Math.PI * 0.2;  // ~36 градусов
        const angleC = -Math.PI * 0.5; // сверху (90 градусов вверх)

        const A = { x: O.x + R * Math.cos(angleA), y: O.y + R * Math.sin(angleA) };
        const B = { x: O.x + R * Math.cos(angleB), y: O.y + R * Math.sin(angleB) };
        const C = { x: O.x + R * Math.cos(angleC), y: O.y + R * Math.sin(angleC) };

        return {
            O, R, A, B, C,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r)
        };
    }

    // 4. Два диаметра AC и BD
    function twodiametersSVG() {
        const O = { x: 100, y: 85 };
        const R = 60;

        // Диаметр AC - горизонтальный
        const A = { x: O.x - R, y: O.y };
        const C = { x: O.x + R, y: O.y };

        // Диаметр BD - под углом ~70 градусов к горизонту
        const angleBD = Math.PI * 0.35;  // ~63 градуса
        const B = { x: O.x + R * Math.cos(angleBD), y: O.y - R * Math.sin(angleBD) };
        const D = { x: O.x - R * Math.cos(angleBD), y: O.y + R * Math.sin(angleBD) };

        return {
            O, R, A, B, C, D,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r)
        };
    }

    // 5. Диаметр AB с точками M и N по разные стороны
    function diameterPointsSVG() {
        const O = { x: 100, y: 85 };
        const R = 60;

        // Диаметр AB - горизонтальный
        const A = { x: O.x - R, y: O.y };
        const B = { x: O.x + R, y: O.y };

        // M - снизу от диаметра
        const angleM = Math.PI * 0.7;  // внизу слева
        const M = { x: O.x + R * Math.cos(angleM), y: O.y + R * Math.sin(angleM) };

        // N - сверху от диаметра
        const angleN = -Math.PI * 0.3;  // вверху справа
        const N = { x: O.x + R * Math.cos(angleN), y: O.y + R * Math.sin(angleN) };

        return {
            O, R, A, B, M, N,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => rightAnglePath(v, p1, p2, s)
        };
    }

    // 6. Вписанная окружность в трапецию
    function inscribedTrapezoidSVG() {
        // Равнобедренная трапеция с вписанной окружностью
        const r = 30;  // Радиус вписанной окружности
        const h = 2 * r;  // Высота трапеции = 2r
        const topBase = 60;  // Верхнее основание
        const bottomBase = 120;  // Нижнее основание

        const centerX = 100;
        const centerY = 80;

        const O = { x: centerX, y: centerY };  // Центр окружности

        // Вершины трапеции (A - левый нижний, B - левый верхний, C - правый верхний, D - правый нижний)
        const A = { x: centerX - bottomBase/2, y: centerY + r };
        const B = { x: centerX - topBase/2, y: centerY - r };
        const C = { x: centerX + topBase/2, y: centerY - r };
        const D = { x: centerX + bottomBase/2, y: centerY + r };

        return { A, B, C, D, O, r };
    }

    // 7. Вписанная окружность в квадрат
    function inscribedSquareSVG() {
        const side = 90;
        const r = side / 2;  // Радиус вписанной окружности = половина стороны
        const startX = 55;
        const startY = 35;

        const A = { x: startX, y: startY + side };  // Левый нижний
        const B = { x: startX, y: startY };          // Левый верхний
        const C = { x: startX + side, y: startY };   // Правый верхний
        const D = { x: startX + side, y: startY + side }; // Правый нижний

        const O = { x: startX + side/2, y: startY + side/2 };  // Центр

        return { A, B, C, D, O, r, side };
    }

    // 8. Четырёхугольник описан около окружности
    function circumscribedQuadSVG() {
        const r = 30;  // Радиус вписанной окружности
        const O = { x: 100, y: 80 };

        // Произвольный выпуклый четырёхугольник, описанный около окружности
        // (сумма противоположных сторон равна)
        const A = { x: 35, y: 110 };   // Левый
        const B = { x: 50, y: 30 };    // Верхний левый
        const C = { x: 160, y: 45 };   // Верхний правый
        const D = { x: 140, y: 130 };  // Нижний

        return { A, B, C, D, O, r };
    }

    // 9. Треугольник с вписанной окружностью
    function inscribedTriangleSVG() {
        const O = { x: 100, y: 95 };  // Центр вписанной окружности (инцентр)
        const r = 25;  // Радиус вписанной окружности

        // Равносторонний или близкий к нему треугольник
        const A = { x: 40, y: 140 };   // Левый нижний
        const B = { x: 160, y: 140 };  // Правый нижний
        const C = { x: 100, y: 30 };   // Верхний

        return { A, B, C, O, r };
    }

    // 10. Четырёхугольник вписан в окружность (углы)
    function inscribedQuadAngleSVG() {
        const O = { x: 100, y: 85 };
        const R = 60;

        // Четырёхугольник ABCD вписан в окружность
        const angleA = Math.PI;           // Слева
        const angleB = Math.PI * 1.4;     // Внизу слева
        const angleC = 0;                 // Справа
        const angleD = -Math.PI * 0.4;    // Вверху

        const A = { x: O.x + R * Math.cos(angleA), y: O.y + R * Math.sin(angleA) };
        const B = { x: O.x + R * Math.cos(angleB), y: O.y + R * Math.sin(angleB) };
        const C = { x: O.x + R * Math.cos(angleC), y: O.y + R * Math.sin(angleC) };
        const D = { x: O.x + R * Math.cos(angleD), y: O.y + R * Math.sin(angleD) };

        return {
            O, R, A, B, C, D,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r)
        };
    }

    // 11. Центр окружности на стороне треугольника (прямоугольный треугольник)
    function centerOnSideSVG() {
        const O = { x: 100, y: 85 };
        const R = 60;

        // Прямоугольный треугольник ABC с прямым углом в C
        // AB - гипотенуза и диаметр окружности, центр O лежит на AB
        const A = { x: O.x - R, y: O.y };  // Слева на диаметре
        const B = { x: O.x + R, y: O.y };  // Справа на диаметре

        // C - на окружности, образует прямой угол (угол, опирающийся на диаметр = 90°)
        const angleC = Math.PI * 0.7;  // Снизу
        const C = { x: O.x + R * Math.cos(angleC), y: O.y + R * Math.sin(angleC) };

        return {
            O, R, A, B, C,
            rightAnglePath: (v, p1, p2, s) => rightAnglePath(v, p1, p2, s)
        };
    }

    // 12. Трапеция вписана в окружность (равнобедренная)
    function inscribedTrapezoidInCircleSVG() {
        const O = { x: 100, y: 85 };
        const R = 60;

        // Равнобедренная трапеция ABCD вписана в окружность
        // AD - нижнее основание, BC - верхнее основание
        const angleA = Math.PI * 0.85;     // Левый нижний
        const angleB = Math.PI * 0.65;     // Левый верхний
        const angleC = Math.PI * 0.35;     // Правый верхний
        const angleD = Math.PI * 0.15;     // Правый нижний

        const A = { x: O.x + R * Math.cos(angleA), y: O.y + R * Math.sin(angleA) };
        const B = { x: O.x + R * Math.cos(angleB), y: O.y - R * Math.sin(angleB) };
        const C = { x: O.x + R * Math.cos(angleC), y: O.y - R * Math.sin(angleC) };
        const D = { x: O.x + R * Math.cos(angleD), y: O.y + R * Math.sin(angleD) };

        return {
            O, R, A, B, C, D,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r)
        };
    }

    // 13. Описанная окружность (квадрат или равносторонний треугольник)
    function circumscribedCircleSVG() {
        const O = { x: 100, y: 85 };
        const R = 55;

        // Используем квадрат для демонстрации
        const side = R * Math.sqrt(2);  // Сторона квадрата, вписанного в окружность
        const angle = -Math.PI / 4;  // Начальный угол для вершин

        // 4 вершины квадрата на окружности
        const vertices = [];
        for (let i = 0; i < 4; i++) {
            const a = angle + (Math.PI / 2) * i;
            vertices.push({ x: O.x + R * Math.cos(a), y: O.y + R * Math.sin(a) });
        }

        const shapePoints = vertices.map(v => `${v.x},${v.y}`).join(' ');
        const vertexX = vertices[0].x;
        const vertexY = vertices[0].y;

        return { O, R, shapePoints, vertexX, vertexY };
    }

    // 14. Расширенная теорема синусов (треугольник вписан в окружность)
    function sineLawSVG() {
        const O = { x: 100, y: 85 };
        const R = 60;

        // Треугольник ABC вписан в окружность
        const angleA = Math.PI * 0.8;    // Левый нижний
        const angleB = Math.PI * 0.2;    // Правый нижний
        const angleC = -Math.PI * 0.5;   // Вверху

        const A = { x: O.x + R * Math.cos(angleA), y: O.y + R * Math.sin(angleA) };
        const B = { x: O.x + R * Math.cos(angleB), y: O.y + R * Math.sin(angleB) };
        const C = { x: O.x + R * Math.cos(angleC), y: O.y + R * Math.sin(angleC) };

        return {
            O, R, A, B, C,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r)
        };
    }
</script>

{{-- Инструмент для пометки заданий --}}
@include('components.task-review-tool', ['topicId' => '16'])

</body>
</html>
