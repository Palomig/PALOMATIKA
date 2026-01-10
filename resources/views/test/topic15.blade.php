<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>15. Треугольники - Тест парсинга PDF</title>

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
        .katex { font-size: 1.1em; }
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
            <span class="px-2 py-1 rounded bg-amber-500 text-white font-bold">15</span>
            <a href="{{ route('test.topic16') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">16</a>
            <a href="{{ route('test.topic18') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">18</a>
            <a href="{{ route('test.topic19') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">19</a>
        </div>
        <a href="{{ route('test.topic15.interactive') }}" class="text-emerald-400 hover:text-emerald-300 transition-colors">Интерактивная версия →</a>
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
        <h1 class="text-4xl font-bold text-white mb-2">15. Треугольники</h1>
        <p class="text-slate-400 text-lg">Геометрические задачи на треугольники</p>
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
            <h2 class="text-2xl font-bold text-white">15. Треугольники</h2>
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
                            <div class="text-emerald-400 font-semibold mb-3">{{ $task['id'] }}.</div>
                            <div class="text-slate-200 text-sm leading-relaxed mb-4">{{ $task['text'] }}</div>

                            {{-- SVG Image based on zadanie type --}}
                            <div class="bg-slate-900/50 rounded-lg p-3">
                                @switch($zadanie['number'])
                                    @case(1)
                                        {{-- Биссектриса --}}
                                        <div x-data="bisectorSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <line :x1="A.x" :y1="A.y" :x2="D.x" :y2="D.y"
                                                    stroke="#dc2626" stroke-width="1.5"/>
                                                <circle :cx="D.x" :cy="D.y" r="3" fill="#dc2626"/>
                                                <path :d="makeAngleArc(A, C, D, 20)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                <path :d="makeAngleArc(A, D, B, 25)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                                                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                                                <text :x="D.x" :y="D.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">D</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(2)
                                        {{-- Медиана --}}
                                        <div x-data="medianSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                                    stroke="#dc2626" stroke-width="1.5"/>
                                                <circle :cx="M.x" :cy="M.y" r="3" fill="#dc2626"/>
                                                <line :x1="(A.x + M.x)/2 - 4" :y1="A.y - 4" :x2="(A.x + M.x)/2 + 4" :y2="A.y + 4" stroke="#3b82f6" stroke-width="2"/>
                                                <line :x1="(M.x + C.x)/2 - 4" :y1="A.y - 4" :x2="(M.x + C.x)/2 + 4" :y2="A.y + 4" stroke="#3b82f6" stroke-width="2"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                                                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                                                <text :x="M.x" :y="M.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="middle">M</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(3)
                                        {{-- Сумма углов --}}
                                        <div x-data="anglesSumSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <path :d="makeAngleArc(A, C, B, 22)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                                                <path :d="makeAngleArc(B, A, C, 18)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                <path :d="makeAngleArc(C, B, A, 22)" fill="none" stroke="#3b82f6" stroke-width="2"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                                                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(4)
                                        {{-- Внешний угол --}}
                                        <div x-data="externalAngleSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <line :x1="C.x" :y1="C.y" :x2="D.x" :y2="D.y" stroke="#dc2626" stroke-width="1.5" stroke-dasharray="6,4"/>
                                                <path :d="makeAngleArc(C, A, B, 20)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                <path :d="makeAngleArc(C, B, D, 18)" fill="none" stroke="#f59e0b" stroke-width="2.5"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                                                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="C.x" :y="C.y + 18" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle">C</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(5)
                                        {{-- Равнобедренный --}}
                                        <div x-data="isoscelesSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <line :x1="markAB.x - 5" :y1="markAB.y - 5" :x2="markAB.x + 5" :y2="markAB.y + 5" stroke="#3b82f6" stroke-width="2.5"/>
                                                <line :x1="markAB.x - 5 + 4" :y1="markAB.y - 5 + 4" :x2="markAB.x + 5 + 4" :y2="markAB.y + 5 + 4" stroke="#3b82f6" stroke-width="2.5"/>
                                                <line :x1="markBC.x - 5" :y1="markBC.y + 5" :x2="markBC.x + 5" :y2="markBC.y - 5" stroke="#3b82f6" stroke-width="2.5"/>
                                                <line :x1="markBC.x - 5 - 4" :y1="markBC.y + 5 + 4" :x2="markBC.x + 5 - 4" :y2="markBC.y - 5 + 4" stroke="#3b82f6" stroke-width="2.5"/>
                                                <path :d="makeAngleArc(A, C, B, 25)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                <path :d="makeAngleArc(C, B, A, 25)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                <path :d="makeAngleArc(B, A, C, 20)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                                                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(6)
                                        {{-- Внешний угол равнобедренного --}}
                                        <div x-data="isoscelesExternalSVG()">
                                            <svg viewBox="0 0 200 140" class="w-full h-32">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <line :x1="C.x" :y1="C.y" :x2="D.x" :y2="D.y" stroke="#dc2626" stroke-width="1.5" stroke-dasharray="6,4"/>
                                                <line :x1="markAB.x - 4" :y1="markAB.y - 4" :x2="markAB.x + 4" :y2="markAB.y + 4" stroke="#3b82f6" stroke-width="2"/>
                                                <line :x1="markBC.x - 4" :y1="markBC.y + 4" :x2="markBC.x + 4" :y2="markBC.y - 4" stroke="#3b82f6" stroke-width="2"/>
                                                <path :d="makeAngleArc(C, B, D, 18)" fill="none" stroke="#f59e0b" stroke-width="2.5"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                                                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="C.x" :y="C.y + 18" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle">C</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(7)
                                        {{-- Прямоугольный --}}
                                        <div x-data="rightTriangleSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <path :d="rightAnglePath(C, A, B, 12)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                                                <path :d="makeAngleArc(A, C, B, 22)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                <path :d="makeAngleArc(B, A, C, 18)" fill="none" stroke="#3b82f6" stroke-width="2"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                                                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(8)
                                        {{-- Высота --}}
                                        <div x-data="heightSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y"
                                                    stroke="#dc2626" stroke-width="1.5"/>
                                                <path :d="rightAnglePath(H, A, B, 10)" fill="none" stroke="#3b82f6" stroke-width="2"/>
                                                <circle :cx="H.x" :cy="H.y" r="3" fill="#dc2626"/>
                                                <path :d="makeAngleArc(A, C, B, 22)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                                                <path :d="makeAngleArc(B, A, H, 18)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                                                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                                                <text :x="H.x + 12" :y="H.y - 5" fill="#60a5fa" font-size="14" class="geo-label">H</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(9)
                                        {{-- Площадь прямоугольного --}}
                                        <div x-data="areaSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="rgba(245, 158, 11, 0.15)" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <path :d="rightAnglePath(C, A, B, 12)" fill="none" stroke="#666" stroke-width="2"/>
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="3"/>
                                                <line :x1="C.x" :y1="C.y" :x2="B.x" :y2="B.y" stroke="#3b82f6" stroke-width="3"/>
                                                <text :x="(A.x + C.x)/2 - 12" :y="(A.y + C.y)/2" fill="#f59e0b" font-size="14" class="geo-label">a</text>
                                                <text :x="(C.x + B.x)/2 + 8" :y="(C.y + B.y)/2" fill="#3b82f6" font-size="14" class="geo-label">b</text>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                                                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(10)
                                        {{-- Площадь с высотой: S = 1/2 * a * h --}}
                                        <div x-data="areaHeightSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="rgba(245, 158, 11, 0.15)" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y" stroke="#10b981" stroke-width="2" stroke-dasharray="5,3"/>
                                                <path :d="rightAnglePath(H, A, B, 10)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                {{-- Метки сторон --}}
                                                <text :x="(A.x + C.x)/2" :y="A.y + 18" fill="#f59e0b" font-size="14" class="geo-label" text-anchor="middle">a</text>
                                                <text :x="(B.x + H.x)/2 + 12" :y="(B.y + H.y)/2" fill="#10b981" font-size="14" class="geo-label">h</text>
                                                {{-- Вершины --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <circle :cx="H.x" :cy="H.y" r="3" fill="#10b981"/>
                                                {{-- Метки вершин --}}
                                                <text :x="A.x - 15" :y="A.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="end" dominant-baseline="middle">A</text>
                                                <text :x="B.x" :y="B.y - 12" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="C.x + 15" :y="C.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start" dominant-baseline="middle">C</text>
                                                <text :x="H.x + 5" :y="H.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">H</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(11)
                                        {{-- Средняя линия --}}
                                        <div x-data="midlineSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <line :x1="M.x" :y1="M.y" :x2="N.x" :y2="N.y" stroke="#10b981" stroke-width="2.5"/>
                                                <line :x1="M.x - 4" :y1="M.y - 6" :x2="M.x + 4" :y2="M.y + 6" stroke="#3b82f6" stroke-width="2"/>
                                                <line :x1="N.x - 4" :y1="N.y + 6" :x2="N.x + 4" :y2="N.y - 6" stroke="#3b82f6" stroke-width="2"/>
                                                <circle :cx="M.x" :cy="M.y" r="4" fill="#10b981"/>
                                                <circle :cx="N.x" :cy="N.y" r="4" fill="#10b981"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                                                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                                                <text :x="M.x - 12" :y="M.y" fill="#60a5fa" font-size="14" class="geo-label">M</text>
                                                <text :x="N.x + 10" :y="N.y" fill="#60a5fa" font-size="14" class="geo-label">N</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(12)
                                    @case(13)
                                        {{-- Теорема Пифагора: a² + b² = c² --}}
                                        <div x-data="pythagorasSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <path :d="rightAnglePath(C, A, B, 12)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                {{-- Катеты и гипотенуза --}}
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="3"/>
                                                <line :x1="C.x" :y1="C.y" :x2="B.x" :y2="B.y" stroke="#3b82f6" stroke-width="3"/>
                                                <line :x1="A.x" :y1="A.y" :x2="B.x" :y2="B.y" stroke="#ec4899" stroke-width="3"/>
                                                {{-- Метки сторон --}}
                                                <text :x="(A.x + C.x)/2" :y="A.y + 18" fill="#f59e0b" font-size="14" class="geo-label" text-anchor="middle">a</text>
                                                <text :x="C.x + 15" :y="(C.y + B.y)/2" fill="#3b82f6" font-size="14" class="geo-label">b</text>
                                                <text :x="(A.x + B.x)/2 - 12" :y="(A.y + B.y)/2 - 5" fill="#ec4899" font-size="14" class="geo-label">c</text>
                                                {{-- Вершины --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                {{-- Метки вершин --}}
                                                <text :x="A.x - 15" :y="A.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="end" dominant-baseline="middle">A</text>
                                                <text :x="B.x + 5" :y="B.y - 12" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start" dominant-baseline="middle">B</text>
                                                <text :x="C.x + 15" :y="C.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start" dominant-baseline="middle">C</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(14)
                                    @case(15)
                                    @case(16)
                                    @case(17)
                                    @case(18)
                                        {{-- Равносторонний треугольник с высотой --}}
                                        <div x-data="equilateralSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                {{-- Высота BH --}}
                                                <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y" stroke="#10b981" stroke-width="2" stroke-dasharray="5,3"/>
                                                <path :d="rightAnglePath(H, A, B, 10)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                {{-- Вершины --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <circle :cx="H.x" :cy="H.y" r="3" fill="#10b981"/>
                                                {{-- Метки вершин --}}
                                                <text :x="A.x - 15" :y="A.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="end" dominant-baseline="middle">A</text>
                                                <text :x="B.x" :y="B.y - 12" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="C.x + 15" :y="C.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start" dominant-baseline="middle">C</text>
                                                <text :x="H.x + 5" :y="H.y + 15" fill="#60a5fa" font-size="14" class="geo-label" text-anchor="start">H</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(19)
                                        {{-- Радиус описанной окружности --}}
                                        <div x-data="circumcircleSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#6366f1" stroke-width="1.5" stroke-dasharray="4,3"/>
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                <path :d="rightAnglePath(C, A, B, 10)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                <line :x1="O.x" :y1="O.y" :x2="A.x" :y2="A.y" stroke="#f59e0b" stroke-width="2"/>
                                                <circle :cx="O.x" :cy="O.y" r="3" fill="#f59e0b"/>
                                                <text :x="(O.x + A.x)/2 - 8" :y="(O.y + A.y)/2 - 5" fill="#f59e0b" font-size="12" class="geo-label">R</text>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                                                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(20)
                                    @case(21)
                                    @case(22)
                                    @case(23)
                                    @case(24)
                                    @case(25)
                                        {{-- Тригонометрия в прямоугольном треугольнике --}}
                                        {{-- ПРАВИЛА ПОЗИЦИОНИРОВАНИЯ:
                                             1. Метки вершин (A,B,C) - через labelPos() от центра, distance=18
                                             2. Метки сторон - на середине стороны, смещение перпендикулярно стороне
                                             3. Метки углов - внутри угловой дуги или рядом с radius+10
                                             4. Не показывать одновременно метку вершины и угла в одной точке --}}
                                        <div x-data="trigSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                {{-- Основной треугольник --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                {{-- Прямой угол при C --}}
                                                <path :d="rightAnglePath(C, A, B, 12)" fill="none" stroke="#10b981" stroke-width="2"/>
                                                {{-- Выделенные стороны --}}
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#3b82f6" stroke-width="2"/>
                                                <line :x1="C.x" :y1="C.y" :x2="B.x" :y2="B.y" stroke="#ec4899" stroke-width="2"/>
                                                {{-- Метки сторон: AC внизу, BC справа --}}
                                                <text :x="(A.x + C.x)/2" :y="A.y + 18" fill="#3b82f6" font-size="12" class="geo-label" text-anchor="middle">AC</text>
                                                <text :x="C.x + 18" :y="(C.y + B.y)/2 + 10" fill="#ec4899" font-size="12" class="geo-label">BC</text>
                                                {{-- Гипотенуза AB --}}
                                                <text :x="(A.x + B.x)/2 - 18" :y="(A.y + B.y)/2 - 5" fill="#dc2626" font-size="12" class="geo-label">AB</text>
                                                {{-- Вершины --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                {{-- Метки вершин --}}
                                                <text :x="A.x - 15" :y="A.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="end" dominant-baseline="middle">A</text>
                                                <text :x="B.x + 5" :y="B.y - 12" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start" dominant-baseline="middle">B</text>
                                                <text :x="C.x + 15" :y="C.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start" dominant-baseline="middle">C</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(26)
                                        {{-- Теорема о площади: S = 1/2 * AB * BC * sin(B) --}}
                                        {{-- ПРАВИЛА ПОЗИЦИОНИРОВАНИЯ:
                                             1. Все три вершины должны иметь метки
                                             2. Угол показываем дугой, метку угла внутри дуги
                                             3. Стороны подписываем снаружи треугольника --}}
                                        <div x-data="areaTheoremSVG()">
                                            <svg viewBox="0 0 200 160" class="w-full h-36">
                                                {{-- Заливка треугольника (показывает площадь) --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="rgba(245, 158, 11, 0.15)" stroke="#dc2626" stroke-width="2.5" stroke-linejoin="round"/>
                                                {{-- Угол B с дугой --}}
                                                <path :d="makeAngleArc(B, A, C, 22)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                                                {{-- Стороны AB и BC выделены --}}
                                                <line :x1="A.x" :y1="A.y" :x2="B.x" :y2="B.y" stroke="#3b82f6" stroke-width="3"/>
                                                <line :x1="B.x" :y1="B.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="3"/>
                                                {{-- Метки сторон --}}
                                                <text :x="(A.x + B.x)/2 - 15" :y="(A.y + B.y)/2 - 5" fill="#3b82f6" font-size="12" class="geo-label">AB</text>
                                                <text :x="(B.x + C.x)/2 + 12" :y="(B.y + C.y)/2" fill="#f59e0b" font-size="12" class="geo-label">BC</text>
                                                {{-- Вершины --}}
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                                {{-- Метки вершин --}}
                                                <text :x="A.x - 15" :y="A.y + 8" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="end" dominant-baseline="middle">A</text>
                                                <text :x="B.x" :y="B.y - 15" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                                <text :x="C.x + 15" :y="C.y + 5" fill="#60a5fa" font-size="16" class="geo-label" text-anchor="start" dominant-baseline="middle">C</text>
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
            <p><strong class="text-slate-300">Тема:</strong> 15. Треугольники</p>
            <p><strong class="text-slate-300">Источник:</strong> {{ $source ?? 'Manual' }}</p>
            <p><strong class="text-slate-300">Контроллер:</strong> <code class="bg-slate-700 px-2 py-1 rounded text-xs">TestPdfController::getAllBlocksData15()</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1">
                <li>Блок 1: ФИПИ (углы, биссектрисы, медианы)</li>
                <li>Всего: {{ $totalTasks }} задач с SVG изображениями</li>
                <li>Все изображения генерируются программно</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-slate-500 text-sm mt-8">Все изображения генерируются программно через SVG + Alpine.js</p>
</div>

<script>
    /**
     * ========================================================================
     * ПРАВИЛА ПОЗИЦИОНИРОВАНИЯ МЕТОК В SVG (GEOMETRY_SPEC)
     * ========================================================================
     *
     * 1. МЕТКИ ВЕРШИН (A, B, C, H, M, N, O):
     *    - ВСЕГДА добавлять метки для ВСЕХ вершин треугольника
     *    - Использовать фиксированные смещения относительно позиции вершины:
     *      * Левый нижний угол: x - 15, text-anchor="end"
     *      * Верхний угол: y - 12, text-anchor="middle"
     *      * Правый нижний угол: x + 15, text-anchor="start"
     *    - НЕ использовать labelPos() для вершин в углах viewBox - может выйти за границы
     *    - Размер шрифта: 16px для вершин треугольника, 14px для вспомогательных точек
     *
     * 2. МЕТКИ СТОРОН (a, b, c, AB, BC, AC, h):
     *    - Располагать НА СЕРЕДИНЕ стороны со смещением НАРУЖУ от треугольника
     *    - Нижняя сторона: y + 18, text-anchor="middle"
     *    - Правая вертикальная сторона: x + 15-18
     *    - Гипотенуза: смещение - 12-15 от центра стороны
     *    - Размер шрифта: 12-14px
     *
     * 3. МЕТКИ УГЛОВ (∠A, ∠B, ∠C):
     *    - НЕ показывать одновременно метку вершины и метку угла у одной точки
     *    - Угловая дуга достаточно указывает на угол
     *    - Если нужна метка угла - располагать внутри дуги или рядом с radius + 10
     *
     * 4. ИЗБЕЖАНИЕ НАЛОЖЕНИЙ:
     *    - Проверять, что метки вершин не накладываются на угловые дуги
     *    - Метки сторон не должны накладываться на метки вершин
     *    - Минимальное расстояние между метками: 15px
     *
     * 5. СТАНДАРТНЫЙ ПРЯМОУГОЛЬНЫЙ ТРЕУГОЛЬНИК:
     *    - A = левый нижний (30, 130) → метка слева: A.x - 15
     *    - B = правый верхний (160, 30) → метка сверху: B.y - 12
     *    - C = правый нижний (160, 130) → метка справа: C.x + 15
     *    - Прямой угол при C (правый нижний)
     *
     * ========================================================================
     */

    // Глобальные функции из спецификации GEOMETRY_SPEC
    function labelPos(point, center, distance = 15) {
        const dx = point.x - center.x;
        const dy = point.y - center.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        if (len === 0) return { x: point.x, y: point.y - distance };
        return {
            x: point.x + (dx / len) * distance,
            y: point.y + (dy / len) * distance
        };
    }

    function makeAngleArc(vertex, point1, point2, radius) {
        const angle1 = Math.atan2(point1.y - vertex.y, point1.x - vertex.x);
        const angle2 = Math.atan2(point2.y - vertex.y, point2.x - vertex.x);
        const x1 = vertex.x + radius * Math.cos(angle1);
        const y1 = vertex.y + radius * Math.sin(angle1);
        const x2 = vertex.x + radius * Math.cos(angle2);
        const y2 = vertex.y + radius * Math.sin(angle2);
        let angleDiff = angle2 - angle1;
        while (angleDiff > Math.PI) angleDiff -= 2 * Math.PI;
        while (angleDiff < -Math.PI) angleDiff += 2 * Math.PI;
        const sweep = angleDiff > 0 ? 1 : 0;
        return `M ${x1} ${y1} A ${radius} ${radius} 0 0 ${sweep} ${x2} ${y2}`;
    }

    function rightAnglePath(vertex, p1, p2, size = 12) {
        const angle1 = Math.atan2(p1.y - vertex.y, p1.x - vertex.x);
        const angle2 = Math.atan2(p2.y - vertex.y, p2.x - vertex.x);
        const c1 = { x: vertex.x + size * Math.cos(angle1), y: vertex.y + size * Math.sin(angle1) };
        const c2 = { x: vertex.x + size * Math.cos(angle2), y: vertex.y + size * Math.sin(angle2) };
        const diag = { x: c1.x + size * Math.cos(angle2), y: c1.y + size * Math.sin(angle2) };
        return `M ${c1.x} ${c1.y} L ${diag.x} ${diag.y} L ${c2.x} ${c2.y}`;
    }

    window.labelPos = labelPos;
    window.makeAngleArc = makeAngleArc;
    window.rightAnglePath = rightAnglePath;

    // 1. Биссектриса
    function bisectorSVG() {
        const A = { x: 20, y: 130 };
        const B = { x: 180, y: 130 };
        const C = { x: 80, y: 25 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const D = { x: 130, y: 77 }; // Точка на BC (биссектриса из A)
        return {
            A, B, C, D, center,
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // 2. Медиана
    function medianSVG() {
        const A = { x: 20, y: 130 };
        const B = { x: 120, y: 25 };
        const C = { x: 180, y: 130 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const M = { x: (A.x + C.x) / 2, y: A.y };
        return {
            A, B, C, M, center,
            labelPos: (p, c) => window.labelPos(p, c),
        };
    }

    // 3. Сумма углов
    function anglesSumSVG() {
        const A = { x: 20, y: 130 };
        const B = { x: 120, y: 25 };
        const C = { x: 180, y: 130 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // 4. Внешний угол
    function externalAngleSVG() {
        const A = { x: 20, y: 110 };
        const B = { x: 90, y: 25 };
        const C = { x: 140, y: 110 };
        const D = { x: 190, y: 110 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, D, center,
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // 5. Равнобедренный
    function isoscelesSVG() {
        const A = { x: 30, y: 130 };
        const B = { x: 100, y: 25 };
        const C = { x: 170, y: 130 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            get markAB() { return { x: (this.A.x + this.B.x) / 2, y: (this.A.y + this.B.y) / 2 }; },
            get markBC() { return { x: (this.B.x + this.C.x) / 2, y: (this.B.y + this.C.y) / 2 }; },
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // 6. Внешний угол равнобедренного
    function isoscelesExternalSVG() {
        const A = { x: 20, y: 110 };
        const B = { x: 80, y: 25 };
        const C = { x: 140, y: 110 };
        const D = { x: 190, y: 110 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, D, center,
            get markAB() { return { x: (this.A.x + this.B.x) / 2, y: (this.A.y + this.B.y) / 2 }; },
            get markBC() { return { x: (this.B.x + this.C.x) / 2, y: (this.B.y + this.C.y) / 2 }; },
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // 7. Прямоугольный
    function rightTriangleSVG() {
        const A = { x: 20, y: 130 };
        const B = { x: 170, y: 30 };
        const C = { x: 170, y: 130 }; // Прямой угол
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
        };
    }

    // 8. Высота
    function heightSVG() {
        const A = { x: 20, y: 130 };
        const B = { x: 120, y: 25 };
        const C = { x: 180, y: 130 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const H = { x: B.x, y: A.y }; // Основание высоты
        return {
            A, B, C, H, center,
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
        };
    }

    // 9. Площадь
    function areaSVG() {
        const A = { x: 20, y: 35 };
        const B = { x: 170, y: 130 };
        const C = { x: 20, y: 130 }; // Прямой угол
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c) => window.labelPos(p, c),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
        };
    }

    // 10. Площадь с высотой
    function areaHeightSVG() {
        const A = { x: 20, y: 130 };
        const B = { x: 100, y: 25 };
        const C = { x: 180, y: 130 };
        const H = { x: B.x, y: A.y };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, H, center,
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
        };
    }

    // 11. Средняя линия
    function midlineSVG() {
        const A = { x: 20, y: 130 };
        const B = { x: 100, y: 25 };
        const C = { x: 180, y: 130 };
        const M = { x: (A.x + B.x) / 2, y: (A.y + B.y) / 2 };
        const N = { x: (B.x + C.x) / 2, y: (B.y + C.y) / 2 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, M, N, center,
            labelPos: (p, c) => window.labelPos(p, c),
        };
    }

    // 12-13. Теорема Пифагора
    function pythagorasSVG() {
        const A = { x: 20, y: 130 };
        const B = { x: 170, y: 30 };
        const C = { x: 170, y: 130 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
        };
    }

    // 14-18. Равносторонний треугольник
    function equilateralSVG() {
        const A = { x: 30, y: 140 };
        const B = { x: 100, y: 20 };
        const C = { x: 170, y: 140 };
        const H = { x: B.x, y: A.y };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, H, center,
            get markAB() { return { x: (this.A.x + this.B.x) / 2, y: (this.A.y + this.B.y) / 2 }; },
            get markBC() { return { x: (this.B.x + this.C.x) / 2, y: (this.B.y + this.C.y) / 2 }; },
            get markAC() { return { x: (this.A.x + this.C.x) / 2, y: this.A.y }; },
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
        };
    }

    // 19. Радиус описанной окружности
    function circumcircleSVG() {
        const A = { x: 30, y: 120 };
        const B = { x: 150, y: 30 };
        const C = { x: 150, y: 120 };
        const O = { x: (A.x + B.x) / 2, y: (A.y + B.y) / 2 }; // Центр на гипотенузе
        const R = Math.sqrt(Math.pow(O.x - A.x, 2) + Math.pow(O.y - A.y, 2));
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, O, R, center,
            labelPos: (p, c) => window.labelPos(p, c),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
        };
    }

    // 20-25. Тригонометрия
    function trigSVG() {
        const A = { x: 30, y: 130 };
        const B = { x: 160, y: 30 };
        const C = { x: 160, y: 130 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c) => window.labelPos(p, c),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // 26. Теорема о площади треугольника
    function areaTheoremSVG() {
        const A = { x: 30, y: 130 };
        const B = { x: 100, y: 25 };
        const C = { x: 180, y: 110 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }
</script>

{{-- Инструмент для пометки заданий --}}
@include('components.task-review-tool', ['topicId' => '15'])

</body>
</html>
