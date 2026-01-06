<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>17. Четырехугольники - Тест парсинга PDF</title>

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
            <a href="{{ route('test.topic15') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">15</a>
            <a href="{{ route('test.topic16') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">16</a>
            <span class="px-2 py-1 rounded bg-emerald-500 text-white font-bold">17</span>
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
        <h1 class="text-4xl font-bold text-white mb-2">17. Четырехугольники</h1>
        <p class="text-slate-400 text-lg">Параллелограмм, трапеция, прямоугольник, ромб</p>
    </div>

    {{-- Stats --}}
    <div class="flex justify-center gap-6 mb-10">
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-emerald-400 font-bold text-xl">{{ count($blocks) }}</span>
            <span class="text-slate-400 ml-2">блоков</span>
        </div>
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-emerald-400 font-bold text-xl">{{ $totalTasks }}</span>
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
            <h2 class="text-2xl font-bold text-white">17. Четырехугольники</h2>
            <p class="text-emerald-400 text-lg mt-1">Блок {{ $block['number'] }}. {{ $block['title'] }}</p>
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($zadanie['tasks'] ?? [] as $task)
                        <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 hover:border-emerald-500/50 transition-colors">
                            <div class="text-emerald-400 font-semibold mb-2">{{ $task['id'] }}.</div>
                            <div class="text-slate-300 text-sm leading-relaxed mb-3">{{ $task['text'] }}</div>

                            {{-- SVG Image based on zadanie number --}}
                            <div class="bg-slate-900/50 rounded-lg p-3">
                                @switch($zadanie['number'])
                                    @case(1)
                                    @case(2)
                                    @case(3)
                                    @case(4)
                                        {{-- Параллелограмм --}}
                                        <div x-data="parallelogramSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                {{-- Параллелограмм ABCD --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Диагонали --}}
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                <line :x1="B.x" :y1="B.y" :x2="D.x" :y2="D.y" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                {{-- Точка пересечения O --}}
                                                <circle :cx="O.x" :cy="O.y" r="3" fill="#f59e0b"/>
                                                {{-- Угол при A --}}
                                                <path :d="makeAngleArc(A, D, B, 18)" fill="none" stroke="#ec4899" stroke-width="2"/>
                                                {{-- Точки вершин --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 8" :y="B.y - 8" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 12" :y="C.y - 8" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 12" :y="D.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">D</text>
                                                <text :x="O.x + 8" :y="O.y - 5" fill="#f59e0b" font-size="12" class="geo-label" text-anchor="start">O</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(5)
                                    @case(6)
                                        {{-- Равнобедренная трапеция --}}
                                        <div x-data="isoscelesTrapezoidSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                {{-- Трапеция ABCD --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Угол при A --}}
                                                <path :d="makeAngleArc(A, D, B, 18)" fill="none" stroke="#ec4899" stroke-width="2"/>
                                                {{-- Угол при D --}}
                                                <path :d="makeAngleArc(D, A, C, 18)" fill="none" stroke="#ec4899" stroke-width="2"/>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 8" :y="B.y - 8" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 8" :y="C.y - 8" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 12" :y="D.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">D</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(7)
                                        {{-- Прямоугольная трапеция --}}
                                        <div x-data="rightTrapezoidSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                {{-- Трапеция --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Прямой угол при A --}}
                                                <path :d="rightAnglePath(A, D, B, 12)" fill="none" stroke="#ec4899" stroke-width="2"/>
                                                {{-- Прямой угол при B --}}
                                                <path :d="rightAnglePath(B, A, C, 12)" fill="none" stroke="#ec4899" stroke-width="2"/>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 12" :y="B.y - 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 8" :y="C.y - 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 12" :y="D.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">D</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(8)
                                    @case(9)
                                        {{-- Трапеция с высотой --}}
                                        <div x-data="trapezoidWithHeightSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                {{-- Трапеция --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Высота CH --}}
                                                <line :x1="C.x" :y1="C.y" :x2="H.x" :y2="H.y" stroke="#f59e0b" stroke-width="2"/>
                                                {{-- Прямой угол при H --}}
                                                <path :d="rightAnglePath(H, C, D, 10)" fill="none" stroke="#f59e0b" stroke-width="1.5"/>
                                                {{-- Диагональ (для задания 9) --}}
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#ec4899" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                <circle :cx="H.x" :cy="H.y" r="3" fill="#f59e0b"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 8" :y="B.y - 8" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 8" :y="C.y - 8" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 12" :y="D.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">D</text>
                                                <text :x="H.x" :y="H.y + 18" fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">H</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(10)
                                        {{-- Прямоугольник с диагоналями --}}
                                        <div x-data="rectangleSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                {{-- Прямоугольник --}}
                                                <rect :x="A.x" :y="B.y" :width="D.x - A.x" :height="A.y - B.y"
                                                    fill="none" stroke="#10b981" stroke-width="2"/>
                                                {{-- Диагонали --}}
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="2"/>
                                                <line :x1="B.x" :y1="B.y" :x2="D.x" :y2="D.y" stroke="#f59e0b" stroke-width="2"/>
                                                {{-- Угол между диагональю и стороной --}}
                                                <path :d="makeAngleArc(A, D, C, 20)" fill="none" stroke="#ec4899" stroke-width="2"/>
                                                {{-- Точка O --}}
                                                <circle :cx="O.x" :cy="O.y" r="3" fill="#f59e0b"/>
                                                {{-- Точки вершин --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 12" :y="B.y - 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 12" :y="C.y - 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 12" :y="D.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">D</text>
                                                <text :x="O.x + 8" :y="O.y - 5" fill="#f59e0b" font-size="12" class="geo-label" text-anchor="start">O</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(11)
                                    @case(12)
                                    @case(13)
                                        {{-- Ромб --}}
                                        <div x-data="rhombusSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                {{-- Ромб ABCD --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Диагонали --}}
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                <line :x1="B.x" :y1="B.y" :x2="D.x" :y2="D.y" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                {{-- Угол при B --}}
                                                <path :d="makeAngleArc(B, A, C, 18)" fill="none" stroke="#ec4899" stroke-width="2"/>
                                                {{-- Высота (для задания 13) --}}
                                                <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y" stroke="#3b82f6" stroke-width="2"/>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x" :y="B.y - 12" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">B</text>
                                                <text :x="C.x + 12" :y="C.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x" :y="D.y + 18" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">D</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(14)
                                        {{-- Параллелограмм для площади --}}
                                        <div x-data="parallelogramAreaSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                {{-- Параллелограмм --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="rgba(16, 185, 129, 0.1)" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Высота --}}
                                                <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y" stroke="#f59e0b" stroke-width="2"/>
                                                {{-- Прямой угол --}}
                                                <path :d="rightAnglePath(H, B, D, 10)" fill="none" stroke="#f59e0b" stroke-width="1.5"/>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                <circle :cx="H.x" :cy="H.y" r="3" fill="#f59e0b"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 8" :y="B.y - 8" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 12" :y="C.y - 8" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 12" :y="D.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">D</text>
                                                <text :x="H.x" :y="H.y + 18" fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">H</text>
                                                {{-- Метка высоты --}}
                                                <text :x="(B.x + H.x)/2 - 10" :y="(B.y + H.y)/2" fill="#f59e0b" font-size="11" class="geo-label">h</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(15)
                                        {{-- Трапеция для площади --}}
                                        <div x-data="trapezoidAreaSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                {{-- Трапеция --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="rgba(16, 185, 129, 0.1)" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Высота --}}
                                                <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y" stroke="#f59e0b" stroke-width="2"/>
                                                {{-- Прямой угол --}}
                                                <path :d="rightAnglePath(H, B, D, 10)" fill="none" stroke="#f59e0b" stroke-width="1.5"/>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                {{-- Метки оснований --}}
                                                <text :x="(B.x + C.x)/2" :y="B.y - 10" fill="#ec4899" font-size="11" class="geo-label" text-anchor="middle">a</text>
                                                <text :x="(A.x + D.x)/2" :y="A.y + 18" fill="#ec4899" font-size="11" class="geo-label" text-anchor="middle">b</text>
                                                <text :x="(B.x + H.x)/2 - 10" :y="(B.y + H.y)/2" fill="#f59e0b" font-size="11" class="geo-label">h</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(16)
                                        {{-- Ромб для площади --}}
                                        <div x-data="rhombusAreaSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                {{-- Ромб --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="rgba(16, 185, 129, 0.1)" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Диагонали --}}
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="2"/>
                                                <line :x1="B.x" :y1="B.y" :x2="D.x" :y2="D.y" stroke="#ec4899" stroke-width="2"/>
                                                {{-- Угол 30° --}}
                                                <path :d="makeAngleArc(A, D, B, 18)" fill="none" stroke="#3b82f6" stroke-width="2"/>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x" :y="B.y - 12" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">B</text>
                                                <text :x="C.x + 12" :y="C.y + 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x" :y="D.y + 18" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">D</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(17)
                                        {{-- Квадрат с диагональю --}}
                                        <div x-data="squareDiagonalSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                {{-- Квадрат --}}
                                                <rect :x="A.x" :y="B.y" :width="side" :height="side"
                                                    fill="rgba(16, 185, 129, 0.1)" stroke="#10b981" stroke-width="2"/>
                                                {{-- Диагональ --}}
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="2.5"/>
                                                {{-- Сторона a --}}
                                                <text :x="A.x - 15" :y="(A.y + B.y)/2" fill="#ec4899" font-size="12" class="geo-label" text-anchor="end">a</text>
                                                {{-- Диагональ d --}}
                                                <text :x="(A.x + C.x)/2 + 10" :y="(A.y + C.y)/2 - 5" fill="#f59e0b" font-size="12" class="geo-label" text-anchor="start">d</text>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                            </svg>
                                        </div>
                                        @break

                                    @case(18)
                                        {{-- Трапеция со средней линией --}}
                                        <div x-data="trapezoidMidlineSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                {{-- Трапеция --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Средняя линия MN --}}
                                                <line :x1="M.x" :y1="M.y" :x2="N.x" :y2="N.y" stroke="#f59e0b" stroke-width="2.5"/>
                                                {{-- Диагональ AC --}}
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#ec4899" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                {{-- Точка пересечения P --}}
                                                <circle :cx="P.x" :cy="P.y" r="3" fill="#ec4899"/>
                                                {{-- Точки --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                <circle :cx="M.x" :cy="M.y" r="3" fill="#f59e0b"/>
                                                <circle :cx="N.x" :cy="N.y" r="3" fill="#f59e0b"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 8" :y="B.y - 8" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 8" :y="C.y - 8" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 12" :y="D.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">D</text>
                                                <text :x="M.x - 10" :y="M.y + 5" fill="#f59e0b" font-size="12" class="geo-label" text-anchor="end">M</text>
                                                <text :x="N.x + 10" :y="N.y + 5" fill="#f59e0b" font-size="12" class="geo-label" text-anchor="start">N</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(19)
                                        {{-- Трапеция с углом 30° (страница 18 PDF) --}}
                                        <div x-data="trapezoid30AngleSVG()">
                                            <svg viewBox="0 0 200 130" class="w-full h-32">
                                                {{-- Трапеция ABCD --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="rgba(16, 185, 129, 0.1)" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Высота BH --}}
                                                <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y" stroke="#f59e0b" stroke-width="2" stroke-dasharray="4,3"/>
                                                {{-- Прямой угол при H --}}
                                                <path :d="rightAnglePath(H, B, A, 10)" fill="none" stroke="#f59e0b" stroke-width="1.5"/>
                                                {{-- Угол 30° при A --}}
                                                <path :d="makeAngleArc(A, D, B, 25)" fill="none" stroke="#ec4899" stroke-width="2"/>
                                                <text :x="A.x + 30" :y="A.y - 8" fill="#ec4899" font-size="11" class="geo-label">30°</text>
                                                {{-- Точки вершин --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 8" :y="B.y - 8" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 8" :y="C.y - 8" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 12" :y="D.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">D</text>
                                                {{-- Метка боковой стороны --}}
                                                <text :x="(A.x + B.x)/2 - 12" :y="(A.y + B.y)/2" fill="#3b82f6" font-size="11" class="geo-label" text-anchor="end">a</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(20)
                                        {{-- Прямоугольник с точкой E на BC (страница 18 PDF) --}}
                                        <div x-data="rectangleWithPointESVG()">
                                            <svg viewBox="0 0 200 130" class="w-full h-32">
                                                {{-- Прямоугольник ABCD --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Отрезок AE --}}
                                                <line :x1="A.x" :y1="A.y" :x2="E.x" :y2="E.y" stroke="#f59e0b" stroke-width="2"/>
                                                {{-- Отрезок ED --}}
                                                <line :x1="E.x" :y1="E.y" :x2="D.x" :y2="D.y" stroke="#ec4899" stroke-width="2.5"/>
                                                {{-- Угол EAB = 45° --}}
                                                <path :d="makeAngleArc(A, B, E, 20)" fill="none" stroke="#3b82f6" stroke-width="2"/>
                                                <text :x="A.x + 22" :y="A.y - 25" fill="#3b82f6" font-size="11" class="geo-label">45°</text>
                                                {{-- Прямые углы --}}
                                                <path :d="rightAnglePath(A, D, B, 10)" fill="none" stroke="#10b981" stroke-width="1.5"/>
                                                <path :d="rightAnglePath(B, A, C, 10)" fill="none" stroke="#10b981" stroke-width="1.5"/>
                                                {{-- Точки вершин --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                <circle :cx="E.x" :cy="E.y" r="4" fill="#f59e0b"/>
                                                {{-- Метки --}}
                                                <text :x="A.x - 12" :y="A.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">A</text>
                                                <text :x="B.x - 12" :y="B.y - 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="end">B</text>
                                                <text :x="C.x + 12" :y="C.y - 5" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">C</text>
                                                <text :x="D.x + 12" :y="D.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">D</text>
                                                <text :x="E.x" :y="E.y - 10" fill="#f59e0b" font-size="14" class="geo-label" text-anchor="middle">E</text>
                                                {{-- Метка ED --}}
                                                <text :x="(E.x + D.x)/2 + 10" :y="(E.y + D.y)/2" fill="#ec4899" font-size="11" class="geo-label">?</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(21)
                                        {{-- Трапеция на координатной сетке (задание 61) --}}
                                        <div x-data="gridTrapezoidSVG('a')">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                {{-- Сетка --}}
                                                <template x-for="line in gridLines">
                                                    <line :x1="line.x1" :y1="line.y1" :x2="line.x2" :y2="line.y2"
                                                        stroke="#475569" stroke-width="0.5"/>
                                                </template>
                                                {{-- Трапеция --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="rgba(16, 185, 129, 0.2)" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
                                                {{-- Точки вершин --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                            </svg>
                                        </div>
                                        @break

                                    @case(22)
                                        {{-- Квадрат с площадью по диагонали --}}
                                        <div x-data="squareAreaSVG()">
                                            <svg viewBox="0 0 200 130" class="w-full h-32">
                                                {{-- Квадрат --}}
                                                <rect :x="A.x" :y="B.y" :width="side" :height="side"
                                                    fill="rgba(16, 185, 129, 0.1)" stroke="#10b981" stroke-width="2"/>
                                                {{-- Обе диагонали --}}
                                                <line :x1="d1.x1" :y1="d1.y1" :x2="d1.x2" :y2="d1.y2" stroke="#f59e0b" stroke-width="2"/>
                                                <line :x1="d2.x1" :y1="d2.y1" :x2="d2.x2" :y2="d2.y2" stroke="#f59e0b" stroke-width="2"/>
                                                {{-- Центр O --}}
                                                <circle :cx="O.x" :cy="O.y" r="3" fill="#ec4899"/>
                                                {{-- Точки вершин --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#10b981"/>
                                                <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>
                                                {{-- Метка диагонали --}}
                                                <text :x="(d1.x1 + d1.x2)/2 + 10" :y="(d1.y1 + d1.y2)/2 - 5" fill="#f59e0b" font-size="12" class="geo-label">d</text>
                                                <text :x="O.x + 8" :y="O.y + 12" fill="#ec4899" font-size="11" class="geo-label">O</text>
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
            <p><strong class="text-slate-300">Тема:</strong> 17. Четырехугольники</p>
            <p><strong class="text-slate-300">Источник:</strong> {{ $source ?? 'Manual' }}</p>
            <p><strong class="text-slate-300">Контроллер:</strong> <code class="bg-slate-700 px-2 py-1 rounded text-xs">TestPdfController::getAllBlocksData17()</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1">
                <li>I) Параллелограмм: углы, диагонали, биссектриса</li>
                <li>II) Трапеция: равнобедренная, прямоугольная, высота</li>
                <li>III) Прямоугольник: диагонали, углы</li>
                <li>IV) Ромб: углы, диагонали, высота</li>
                <li>V) Площадь: параллелограмм, трапеция, ромб</li>
                <li>VI) Теорема Пифагора: диагональ квадрата</li>
                <li>VII) Средняя линия трапеции</li>
                <li>VIII) Площадь трапеции с углом 30°</li>
                <li>IX) Прямоугольник с точкой E (теорема Пифагора)</li>
                <li>X) Трапеция на координатной сетке</li>
                <li>XI) Площадь квадрата по диагонали</li>
                <li>Всего: {{ $totalTasks }} задач с SVG изображениями</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-slate-500 text-sm mt-8">Все изображения генерируются программно через SVG + Alpine.js</p>
</div>

<script>
    // Функция для создания дуги угла
    function makeAngleArc(vertex, point1, point2, radius) {
        const angle1 = Math.atan2(point1.y - vertex.y, point1.x - vertex.x);
        const angle2 = Math.atan2(point2.y - vertex.y, point2.x - vertex.x);

        let startAngle = angle1;
        let endAngle = angle2;

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

    // 1. Параллелограмм
    function parallelogramSVG() {
        const A = { x: 30, y: 100 };
        const B = { x: 60, y: 40 };
        const C = { x: 170, y: 40 };
        const D = { x: 140, y: 100 };
        const O = { x: (A.x + C.x) / 2, y: (A.y + C.y) / 2 };

        return {
            A, B, C, D, O,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r)
        };
    }

    // 2. Равнобедренная трапеция
    function isoscelesTrapezoidSVG() {
        const A = { x: 25, y: 100 };
        const B = { x: 55, y: 40 };
        const C = { x: 145, y: 40 };
        const D = { x: 175, y: 100 };

        return {
            A, B, C, D,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r)
        };
    }

    // 3. Прямоугольная трапеция
    function rightTrapezoidSVG() {
        const A = { x: 30, y: 100 };
        const B = { x: 30, y: 40 };
        const C = { x: 140, y: 40 };
        const D = { x: 170, y: 100 };

        return {
            A, B, C, D,
            rightAnglePath: (v, p1, p2, s) => rightAnglePath(v, p1, p2, s)
        };
    }

    // 4. Трапеция с высотой
    function trapezoidWithHeightSVG() {
        const A = { x: 25, y: 100 };
        const B = { x: 55, y: 40 };
        const C = { x: 145, y: 40 };
        const D = { x: 175, y: 100 };
        const H = { x: C.x, y: A.y };

        return {
            A, B, C, D, H,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => rightAnglePath(v, p1, p2, s)
        };
    }

    // 5. Прямоугольник с диагоналями
    function rectangleSVG() {
        const A = { x: 30, y: 100 };
        const B = { x: 30, y: 40 };
        const C = { x: 170, y: 40 };
        const D = { x: 170, y: 100 };
        const O = { x: (A.x + C.x) / 2, y: (A.y + C.y) / 2 };

        return {
            A, B, C, D, O,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r)
        };
    }

    // 6. Ромб
    function rhombusSVG() {
        const cx = 100, cy = 70;
        const dx = 70, dy = 45;
        const A = { x: cx - dx, y: cy };
        const B = { x: cx, y: cy - dy };
        const C = { x: cx + dx, y: cy };
        const D = { x: cx, y: cy + dy };
        // Высота от B к AD
        const H = { x: B.x, y: A.y };

        return {
            A, B, C, D, H,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r)
        };
    }

    // 7. Параллелограмм для площади
    function parallelogramAreaSVG() {
        const A = { x: 30, y: 100 };
        const B = { x: 60, y: 40 };
        const C = { x: 170, y: 40 };
        const D = { x: 140, y: 100 };
        const H = { x: B.x, y: A.y };

        return {
            A, B, C, D, H,
            rightAnglePath: (v, p1, p2, s) => rightAnglePath(v, p1, p2, s)
        };
    }

    // 8. Трапеция для площади
    function trapezoidAreaSVG() {
        const A = { x: 25, y: 100 };
        const B = { x: 55, y: 40 };
        const C = { x: 145, y: 40 };
        const D = { x: 175, y: 100 };
        const H = { x: B.x, y: A.y };

        return {
            A, B, C, D, H,
            rightAnglePath: (v, p1, p2, s) => rightAnglePath(v, p1, p2, s)
        };
    }

    // 9. Ромб для площади
    function rhombusAreaSVG() {
        const cx = 100, cy = 70;
        const dx = 70, dy = 45;
        const A = { x: cx - dx, y: cy };
        const B = { x: cx, y: cy - dy };
        const C = { x: cx + dx, y: cy };
        const D = { x: cx, y: cy + dy };

        return {
            A, B, C, D,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r)
        };
    }

    // 10. Квадрат с диагональю
    function squareDiagonalSVG() {
        const side = 80;
        const startX = 60;
        const startY = 30;

        const A = { x: startX, y: startY + side };
        const B = { x: startX, y: startY };
        const C = { x: startX + side, y: startY };
        const D = { x: startX + side, y: startY + side };

        return { A, B, C, D, side };
    }

    // 11. Трапеция со средней линией
    function trapezoidMidlineSVG() {
        const A = { x: 25, y: 110 };
        const B = { x: 55, y: 30 };
        const C = { x: 145, y: 30 };
        const D = { x: 175, y: 110 };

        // Середины боковых сторон
        const M = { x: (A.x + B.x) / 2, y: (A.y + B.y) / 2 };
        const N = { x: (C.x + D.x) / 2, y: (C.y + D.y) / 2 };

        // Точка пересечения средней линии с диагональю AC
        const t = (M.y - A.y) / (C.y - A.y);
        const P = { x: A.x + t * (C.x - A.x), y: M.y };

        return {
            A, B, C, D, M, N, P
        };
    }

    // 12. Трапеция с углом 30° для площади (страница 18 PDF)
    function trapezoid30AngleSVG() {
        // Трапеция с боковой стороной и углом 30° при основании
        const A = { x: 25, y: 105 };  // нижний левый
        const D = { x: 175, y: 105 }; // нижний правый
        // Боковая сторона наклонена под углом 30°
        const sideLen = 60;
        const angle30 = 30 * Math.PI / 180;
        const B = { x: A.x + sideLen * Math.cos(angle30), y: A.y - sideLen * Math.sin(angle30) };
        const C = { x: 145, y: B.y }; // верхняя правая на той же высоте

        // Высота трапеции
        const H = { x: B.x, y: A.y };

        return {
            A, B, C, D, H,
            sideLen,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => rightAnglePath(v, p1, p2, s)
        };
    }

    // 13. Прямоугольник с точкой E на BC (страница 18 PDF)
    function rectangleWithPointESVG() {
        // Прямоугольник ABCD с точкой E на стороне BC
        const A = { x: 30, y: 110 };  // нижний левый
        const B = { x: 30, y: 35 };   // верхний левый
        const C = { x: 170, y: 35 };  // верхний правый
        const D = { x: 170, y: 110 }; // нижний правый

        // Точка E на BC такая, что угол EAB = 45°
        // При угле 45° и AB вертикальном, AE образует угол 45° с AB
        // BE = AB (т.к. угол 45°)
        const AB = A.y - B.y; // высота
        const E = { x: B.x + AB, y: B.y }; // E на BC на расстоянии AB от B

        return {
            A, B, C, D, E,
            makeAngleArc: (v, p1, p2, r) => makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => rightAnglePath(v, p1, p2, s)
        };
    }

    // 14. Трапеция на координатной сетке (страница 18 PDF, задание 61)
    function gridTrapezoidSVG(variant = 'a') {
        const gridSize = 20;
        const offsetX = 10;
        const offsetY = 10;

        // Разные варианты трапеций для (a), (b), (c), (d)
        const variants = {
            'a': { // Типичная трапеция
                A: { gx: 1, gy: 5 },
                B: { gx: 2, gy: 2 },
                C: { gx: 6, gy: 2 },
                D: { gx: 8, gy: 5 }
            },
            'b': { // Другая трапеция
                A: { gx: 0, gy: 4 },
                B: { gx: 2, gy: 1 },
                C: { gx: 7, gy: 1 },
                D: { gx: 9, gy: 4 }
            },
            'c': { // Узкая трапеция
                A: { gx: 1, gy: 5 },
                B: { gx: 3, gy: 1 },
                C: { gx: 5, gy: 1 },
                D: { gx: 7, gy: 5 }
            },
            'd': { // Широкая трапеция
                A: { gx: 0, gy: 4 },
                B: { gx: 1, gy: 1 },
                C: { gx: 8, gy: 1 },
                D: { gx: 9, gy: 4 }
            }
        };

        const v = variants[variant] || variants['a'];

        // Преобразуем grid координаты в SVG координаты
        const toSVG = (gx, gy) => ({
            x: offsetX + gx * gridSize,
            y: offsetY + gy * gridSize
        });

        const A = toSVG(v.A.gx, v.A.gy);
        const B = toSVG(v.B.gx, v.B.gy);
        const C = toSVG(v.C.gx, v.C.gy);
        const D = toSVG(v.D.gx, v.D.gy);

        // Grid lines
        const gridLines = [];
        for (let i = 0; i <= 9; i++) {
            // Вертикальные
            gridLines.push({ x1: offsetX + i * gridSize, y1: offsetY, x2: offsetX + i * gridSize, y2: offsetY + 6 * gridSize });
            // Горизонтальные
            if (i <= 6) {
                gridLines.push({ x1: offsetX, y1: offsetY + i * gridSize, x2: offsetX + 9 * gridSize, y2: offsetY + i * gridSize });
            }
        }

        return {
            A, B, C, D,
            gridLines,
            gridSize,
            offsetX,
            offsetY
        };
    }

    // 15. Квадрат с площадью (для задач "площадь = d²/2")
    function squareAreaSVG() {
        const side = 80;
        const startX = 60;
        const startY = 30;

        const A = { x: startX, y: startY + side };
        const B = { x: startX, y: startY };
        const C = { x: startX + side, y: startY };
        const D = { x: startX + side, y: startY + side };

        // Диагонали
        const d1 = { x1: A.x, y1: A.y, x2: C.x, y2: C.y };
        const d2 = { x1: B.x, y1: B.y, x2: D.x, y2: D.y };

        // Центр
        const O = { x: (A.x + C.x) / 2, y: (A.y + C.y) / 2 };

        return { A, B, C, D, O, d1, d2, side };
    }
</script>

</body>
</html>
