<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>18. Фигуры на квадратной решётке - Тест парсинга PDF</title>

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
        <a href="{{ route('topics.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← Все темы</a>
        <div class="flex gap-2 flex-wrap justify-center">
            @for($i = 6; $i <= 19; $i++)
                @php $num = str_pad($i, 2, '0', STR_PAD_LEFT); @endphp
                @if($i == 18)
                    <span class="px-2 py-1 rounded bg-cyan-500 text-white font-bold">{{ $num }}</span>
                @else
                    <a href="{{ route('topics.show', $i) }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">{{ $num }}</a>
                @endif
            @endfor
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
        <h1 class="text-4xl font-bold text-white mb-2">18. Фигуры на квадратной решётке</h1>
        <p class="text-slate-400 text-lg">Геометрические задачи на клетчатой бумаге</p>
    </div>

    {{-- Stats --}}
    <div class="flex justify-center gap-6 mb-10">
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-cyan-400 font-bold text-xl">{{ count($blocks) }}</span>
            <span class="text-slate-400 ml-2">блоков</span>
        </div>
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-cyan-400 font-bold text-xl">{{ $totalTasks }}</span>
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
            <h2 class="text-2xl font-bold text-white">18. Фигуры на квадратной решётке</h2>
            <p class="text-cyan-400 text-lg mt-1">Блок {{ $block['number'] }}. {{ $block['title'] }}</p>
        </div>

        @foreach($block['zadaniya'] as $zadanie)
            <div class="mb-10">
                {{-- Zadanie Header --}}
                <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-cyan-500">
                    <h3 class="text-lg font-semibold text-white">
                        Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}
                    </h3>
                </div>

                {{-- Tasks Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($zadanie['tasks'] ?? [] as $index => $task)
                        <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 hover:border-cyan-500/50 transition-colors">
                            <div class="text-cyan-400 font-semibold mb-2">{{ $task['id'] }}.</div>

                            @if(!empty($task['question']))
                                <div class="text-slate-300 text-sm leading-relaxed mb-3">{{ $task['question'] }}</div>
                            @endif

                            {{-- SVG Image based on zadanie number --}}
                            <div class="bg-slate-900/50 rounded-lg p-2">
                                @switch($zadanie['number'])
                                    @case(1)
                                        {{-- Прямоугольный треугольник - найти больший катет --}}
                                        <div x-data="rightTriangleOnGrid({{ $index }})">
                                            <svg :viewBox="`0 0 ${width} ${height}`" class="w-full h-auto rounded">
                                                {{-- Сетка через pattern --}}
                                                <defs>
                                                    <pattern id="grid1-{{ $index }}" width="25" height="25" patternUnits="userSpaceOnUse">
                                                        <path d="M 25 0 L 0 0 0 25" fill="none" stroke="#475569" stroke-width="1"/>
                                                    </pattern>
                                                </defs>
                                                <rect x="0" y="0" :width="width" :height="height" fill="url(#grid1-{{ $index }})"/>
                                                {{-- Треугольник --}}
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#10b981" stroke-width="2.5" stroke-linejoin="round"/>
                                                {{-- Прямой угол --}}
                                                <path :d="rightAngleMark" fill="none" stroke="#10b981" stroke-width="2"/>
                                            </svg>
                                        </div>
                                        @break

                                    @case(2)
                                        {{-- Ромб - найти большую диагональ --}}
                                        <div x-data="rhombusOnGrid({{ $index }})">
                                            <svg :viewBox="`0 0 ${width} ${height}`" class="w-full h-auto rounded">
                                                <defs>
                                                    <pattern id="grid2-{{ $index }}" width="25" height="25" patternUnits="userSpaceOnUse">
                                                        <path d="M 25 0 L 0 0 0 25" fill="none" stroke="#475569" stroke-width="1"/>
                                                    </pattern>
                                                </defs>
                                                <rect x="0" y="0" :width="width" :height="height" fill="url(#grid2-{{ $index }})"/>
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#10b981" stroke-width="2.5" stroke-linejoin="round"/>
                                                <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#10b981" stroke-width="2" stroke-dasharray="4,3"/>
                                                <line :x1="B.x" :y1="B.y" :x2="D.x" :y2="D.y" stroke="#10b981" stroke-width="2" stroke-dasharray="4,3"/>
                                            </svg>
                                        </div>
                                        @break

                                    @case(3)
                                        {{-- Треугольник ABC с точкой M (теорема Фалеса) --}}
                                        <div x-data="triangleWithPointM({{ $index }})">
                                            <svg :viewBox="`0 0 ${width} ${height}`" class="w-full h-auto rounded">
                                                <defs>
                                                    <pattern id="grid3-{{ $index }}" width="25" height="25" patternUnits="userSpaceOnUse">
                                                        <path d="M 25 0 L 0 0 0 25" fill="none" stroke="#475569" stroke-width="1"/>
                                                    </pattern>
                                                </defs>
                                                <rect x="0" y="0" :width="width" :height="height" fill="url(#grid3-{{ $index }})"/>
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#10b981" stroke-width="2.5" stroke-linejoin="round"/>
                                                <circle :cx="M.x" :cy="M.y" r="4" fill="#f59e0b"/>
                                                <text :x="A.x - 12" :y="A.y + 4" fill="#94a3b8" font-size="13" font-weight="bold" font-style="italic">A</text>
                                                <text :x="B.x + 4" :y="B.y - 6" fill="#94a3b8" font-size="13" font-weight="bold" font-style="italic">B</text>
                                                <text :x="C.x + 4" :y="C.y + 14" fill="#94a3b8" font-size="13" font-weight="bold" font-style="italic">C</text>
                                                <text :x="M.x + 4" :y="M.y - 6" fill="#f59e0b" font-size="13" font-weight="bold" font-style="italic">M</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(4)
                                    @case(5)
                                    @case(6)
                                    @case(7)
                                        {{-- Многоугольник для нахождения площади --}}
                                        <div x-data="polygonOnGrid({{ $zadanie['number'] }}, {{ $index }})">
                                            <svg :viewBox="`0 0 ${width} ${height}`" class="w-full h-auto rounded">
                                                <defs>
                                                    <pattern id="grid4-{{ $zadanie['number'] }}-{{ $index }}" width="25" height="25" patternUnits="userSpaceOnUse">
                                                        <path d="M 25 0 L 0 0 0 25" fill="none" stroke="#475569" stroke-width="1"/>
                                                    </pattern>
                                                </defs>
                                                <rect x="0" y="0" :width="width" :height="height" fill="url(#grid4-{{ $zadanie['number'] }}-{{ $index }})"/>
                                                <polygon :points="pointsString"
                                                    fill="none" stroke="#10b981" stroke-width="2.5" stroke-linejoin="round"/>
                                            </svg>
                                        </div>
                                        @break

                                    @case(8)
                                        {{-- Две точки на сетке - найти расстояние --}}
                                        <div x-data="twoPointsOnGrid({{ $index }})">
                                            <svg :viewBox="`0 0 ${width} ${height}`" class="w-full h-auto rounded">
                                                <defs>
                                                    <pattern id="grid8-{{ $index }}" width="25" height="25" patternUnits="userSpaceOnUse">
                                                        <path d="M 25 0 L 0 0 0 25" fill="none" stroke="#475569" stroke-width="1"/>
                                                    </pattern>
                                                </defs>
                                                <rect x="0" y="0" :width="width" :height="height" fill="url(#grid8-{{ $index }})"/>
                                                <line :x1="A.x" :y1="A.y" :x2="B.x" :y2="B.y" stroke="#10b981" stroke-width="2.5"/>
                                                <circle :cx="A.x" :cy="A.y" r="4" fill="#10b981"/>
                                                <circle :cx="B.x" :cy="B.y" r="4" fill="#10b981"/>
                                            </svg>
                                        </div>
                                        @break

                                    @case(9)
                                        {{-- Треугольник ABC - найти среднюю линию --}}
                                        <div x-data="triangleWithMidline({{ $index }})">
                                            <svg :viewBox="`0 0 ${width} ${height}`" class="w-full h-auto rounded">
                                                <defs>
                                                    <pattern id="grid9-{{ $index }}" width="25" height="25" patternUnits="userSpaceOnUse">
                                                        <path d="M 25 0 L 0 0 0 25" fill="none" stroke="#475569" stroke-width="1"/>
                                                    </pattern>
                                                </defs>
                                                <rect x="0" y="0" :width="width" :height="height" fill="url(#grid9-{{ $index }})"/>
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                                    fill="none" stroke="#10b981" stroke-width="2.5" stroke-linejoin="round"/>
                                                <line :x1="M1.x" :y1="M1.y" :x2="M2.x" :y2="M2.y" stroke="#f59e0b" stroke-width="2" stroke-dasharray="5,3"/>
                                                <text :x="A.x - 12" :y="A.y + 14" fill="#94a3b8" font-size="13" font-weight="bold" font-style="italic">A</text>
                                                <text :x="B.x - 2" :y="B.y - 8" fill="#94a3b8" font-size="13" font-weight="bold" font-style="italic">B</text>
                                                <text :x="C.x + 4" :y="C.y + 14" fill="#94a3b8" font-size="13" font-weight="bold" font-style="italic">C</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(10)
                                        {{-- Фигура с отрезком AB --}}
                                        <div x-data="figureWithSegmentAB({{ $index }})">
                                            <svg :viewBox="`0 0 ${width} ${height}`" class="w-full h-auto rounded">
                                                <defs>
                                                    <pattern id="grid10-{{ $index }}" width="25" height="25" patternUnits="userSpaceOnUse">
                                                        <path d="M 25 0 L 0 0 0 25" fill="none" stroke="#475569" stroke-width="1"/>
                                                    </pattern>
                                                </defs>
                                                <rect x="0" y="0" :width="width" :height="height" fill="url(#grid10-{{ $index }})"/>
                                                <polygon :points="shapePoints"
                                                    fill="none" stroke="#10b981" stroke-width="2.5" stroke-linejoin="round"/>
                                                <line :x1="A.x" :y1="A.y" :x2="B.x" :y2="B.y" stroke="#f59e0b" stroke-width="2.5"/>
                                                <text :x="labelA.x" :y="labelA.y" fill="#94a3b8" font-size="13" font-weight="bold" font-style="italic">A</text>
                                                <text :x="labelB.x" :y="labelB.y" fill="#94a3b8" font-size="13" font-weight="bold" font-style="italic">B</text>
                                            </svg>
                                        </div>
                                        @break

                                    @case(11)
                                        {{-- Трапеция - найти среднюю линию --}}
                                        <div x-data="trapezoidWithMidline({{ $index }})">
                                            <svg :viewBox="`0 0 ${width} ${height}`" class="w-full h-auto rounded">
                                                <defs>
                                                    <pattern id="grid11-{{ $index }}" width="25" height="25" patternUnits="userSpaceOnUse">
                                                        <path d="M 25 0 L 0 0 0 25" fill="none" stroke="#475569" stroke-width="1"/>
                                                    </pattern>
                                                </defs>
                                                <rect x="0" y="0" :width="width" :height="height" fill="url(#grid11-{{ $index }})"/>
                                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                                    fill="none" stroke="#10b981" stroke-width="2.5" stroke-linejoin="round"/>
                                                <line :x1="M1.x" :y1="M1.y" :x2="M2.x" :y2="M2.y" stroke="#f59e0b" stroke-width="2" stroke-dasharray="5,3"/>
                                            </svg>
                                        </div>
                                        @break

                                    @case(12)
                                        {{-- Два круга - сравнить площади --}}
                                        <div x-data="twoCirclesOnGrid({{ $index }})">
                                            <svg :viewBox="`0 0 ${width} ${height}`" class="w-full h-auto rounded">
                                                <defs>
                                                    <pattern id="grid12-{{ $index }}" width="25" height="25" patternUnits="userSpaceOnUse">
                                                        <path d="M 25 0 L 0 0 0 25" fill="none" stroke="#475569" stroke-width="1"/>
                                                    </pattern>
                                                </defs>
                                                <rect x="0" y="0" :width="width" :height="height" fill="url(#grid12-{{ $index }})"/>
                                                <circle :cx="c1.x" :cy="c1.y" :r="r1" fill="none" stroke="#10b981" stroke-width="2.5"/>
                                                <circle :cx="c2.x" :cy="c2.y" :r="r2" fill="none" stroke="#10b981" stroke-width="2.5"/>
                                                <circle :cx="c1.x" :cy="c1.y" r="3" fill="#10b981"/>
                                                <circle :cx="c2.x" :cy="c2.y" r="3" fill="#10b981"/>
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
            <p><strong class="text-slate-300">Тема:</strong> 18. Фигуры на квадратной решётке</p>
            <p><strong class="text-slate-300">Источник:</strong> {{ $source ?? 'Manual' }}</p>
            <p><strong class="text-slate-300">Контроллер:</strong> <code class="bg-slate-700 px-2 py-1 rounded text-xs">TestPdfController::getAllBlocksData18()</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1">
                <li>I) Длина: прямоугольные треугольники, ромбы</li>
                <li>II) Теорема Фалеса: треугольники с точкой M</li>
                <li>III) Площадь: различные многоугольники</li>
                <li>IV) Теорема Пифагора: расстояние между точками</li>
                <li>V) Средняя линия: треугольники и трапеции</li>
                <li>VI) Площадь круга: сравнение двух кругов</li>
                <li>Всего: {{ $totalTasks }} задач с SVG изображениями</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-slate-500 text-sm mt-8">Все изображения генерируются программно через SVG + Alpine.js</p>
</div>

<script>
    const GRID_SIZE = 25;
    const PADDING = 10;

    // Преобразование координат сетки в SVG
    function gridToSVG(gx, gy) {
        return {
            x: PADDING + gx * GRID_SIZE,
            y: PADDING + gy * GRID_SIZE
        };
    }

    // 1. Прямоугольный треугольник - унифицированные размеры 7x5 клеток
    function rightTriangleOnGrid(index) {
        const variants = [
            { A: {gx: 0, gy: 0}, B: {gx: 0, gy: 4}, C: {gx: 6, gy: 4} }, // гор=6, верт=4
            { A: {gx: 0, gy: 0}, B: {gx: 0, gy: 3}, C: {gx: 5, gy: 3} }, // гор=5, верт=3
            { A: {gx: 0, gy: 0}, B: {gx: 0, gy: 2}, C: {gx: 6, gy: 2} }, // гор=6, верт=2
            { A: {gx: 0, gy: 0}, B: {gx: 0, gy: 5}, C: {gx: 4, gy: 5} }, // гор=4, верт=5
            { A: {gx: 0, gy: 0}, B: {gx: 0, gy: 4}, C: {gx: 3, gy: 4} }, // гор=3, верт=4
            { A: {gx: 0, gy: 0}, B: {gx: 0, gy: 3}, C: {gx: 4, gy: 3} }, // гор=4, верт=3
        ];
        const v = variants[index % variants.length];
        const cols = Math.max(v.C.gx, v.A.gx, v.B.gx) + 1;
        const rows = Math.max(v.C.gy, v.A.gy, v.B.gy) + 1;
        const A = gridToSVG(v.A.gx, v.A.gy);
        const B = gridToSVG(v.B.gx, v.B.gy);
        const C = gridToSVG(v.C.gx, v.C.gy);
        const width = PADDING * 2 + cols * GRID_SIZE;
        const height = PADDING * 2 + rows * GRID_SIZE;
        const size = 8;
        const rightAngleMark = `M ${B.x + size} ${B.y} L ${B.x + size} ${B.y - size} L ${B.x} ${B.y - size}`;
        return { A, B, C, width, height, rightAngleMark };
    }

    // 2. Ромб - унифицированные размеры 7x6 клеток
    function rhombusOnGrid(index) {
        const variants = [
            { A: {gx: 0, gy: 3}, B: {gx: 3, gy: 0}, C: {gx: 6, gy: 3}, D: {gx: 3, gy: 6} },
            { A: {gx: 0, gy: 2}, B: {gx: 3, gy: 0}, C: {gx: 6, gy: 2}, D: {gx: 3, gy: 4} },
            { A: {gx: 0, gy: 3}, B: {gx: 2, gy: 0}, C: {gx: 4, gy: 3}, D: {gx: 2, gy: 6} },
            { A: {gx: 0, gy: 2}, B: {gx: 4, gy: 0}, C: {gx: 8, gy: 2}, D: {gx: 4, gy: 4} },
            { A: {gx: 0, gy: 2}, B: {gx: 2, gy: 0}, C: {gx: 4, gy: 2}, D: {gx: 2, gy: 4} },
            { A: {gx: 0, gy: 3}, B: {gx: 4, gy: 0}, C: {gx: 8, gy: 3}, D: {gx: 4, gy: 6} },
        ];
        const v = variants[index % variants.length];
        const cols = Math.max(v.A.gx, v.B.gx, v.C.gx, v.D.gx) + 1;
        const rows = Math.max(v.A.gy, v.B.gy, v.C.gy, v.D.gy) + 1;
        const A = gridToSVG(v.A.gx, v.A.gy);
        const B = gridToSVG(v.B.gx, v.B.gy);
        const C = gridToSVG(v.C.gx, v.C.gy);
        const D = gridToSVG(v.D.gx, v.D.gy);
        const width = PADDING * 2 + cols * GRID_SIZE;
        const height = PADDING * 2 + rows * GRID_SIZE;
        return { A, B, C, D, width, height };
    }

    // 3. Треугольник с точкой M - унифицированные размеры 8x6 клеток
    function triangleWithPointM(index) {
        const variants = [
            { A: {gx: 0, gy: 5}, B: {gx: 3, gy: 0}, C: {gx: 7, gy: 5}, M: {gx: 1, gy: 3} },
            { A: {gx: 0, gy: 4}, B: {gx: 3, gy: 0}, C: {gx: 6, gy: 4}, M: {gx: 5, gy: 2} },
            { A: {gx: 0, gy: 5}, B: {gx: 2, gy: 1}, C: {gx: 5, gy: 5}, M: {gx: 1, gy: 3} },
            { A: {gx: 0, gy: 5}, B: {gx: 4, gy: 0}, C: {gx: 6, gy: 5}, M: {gx: 2, gy: 3} },
            { A: {gx: 0, gy: 4}, B: {gx: 3, gy: 0}, C: {gx: 5, gy: 4}, M: {gx: 4, gy: 2} },
            { A: {gx: 0, gy: 5}, B: {gx: 3, gy: 0}, C: {gx: 7, gy: 5}, M: {gx: 2, gy: 3} },
        ];
        const v = variants[index % variants.length];
        const cols = Math.max(v.A.gx, v.B.gx, v.C.gx, v.M.gx) + 1;
        const rows = Math.max(v.A.gy, v.B.gy, v.C.gy, v.M.gy) + 1;
        const A = gridToSVG(v.A.gx, v.A.gy);
        const B = gridToSVG(v.B.gx, v.B.gy);
        const C = gridToSVG(v.C.gx, v.C.gy);
        const M = gridToSVG(v.M.gx, v.M.gy);
        const width = PADDING * 2 + cols * GRID_SIZE;
        const height = PADDING * 2 + rows * GRID_SIZE;
        return { A, B, C, M, width, height };
    }

    // 4-7. Многоугольники - унифицированные размеры max 7x5 клеток
    function polygonOnGrid(zadanieNum, index) {
        const allVariants = {
            4: [ // Простые фигуры
                [{gx: 0, gy: 4}, {gx: 0, gy: 0}, {gx: 5, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 2, gy: 0}, {gx: 5, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 0, gy: 0}, {gx: 4, gy: 0}, {gx: 4, gy: 4}],
                [{gx: 0, gy: 3}, {gx: 2, gy: 0}, {gx: 5, gy: 3}],
                [{gx: 0, gy: 4}, {gx: 2, gy: 0}, {gx: 6, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 0, gy: 0}, {gx: 3, gy: 0}, {gx: 3, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 1, gy: 0}, {gx: 5, gy: 4}],
                [{gx: 0, gy: 5}, {gx: 2, gy: 0}, {gx: 5, gy: 5}],
                [{gx: 0, gy: 3}, {gx: 0, gy: 0}, {gx: 4, gy: 0}, {gx: 4, gy: 3}],
            ],
            5: [ // Трапеции и четырёхугольники
                [{gx: 0, gy: 4}, {gx: 1, gy: 0}, {gx: 4, gy: 0}, {gx: 5, gy: 4}],
                [{gx: 0, gy: 3}, {gx: 2, gy: 0}, {gx: 5, gy: 0}, {gx: 5, gy: 3}],
                [{gx: 0, gy: 5}, {gx: 1, gy: 1}, {gx: 4, gy: 1}, {gx: 5, gy: 5}],
                [{gx: 0, gy: 4}, {gx: 1, gy: 0}, {gx: 4, gy: 0}, {gx: 6, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 0, gy: 0}, {gx: 3, gy: 0}, {gx: 4, gy: 3}, {gx: 2, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 0, gy: 1}, {gx: 2, gy: 0}, {gx: 5, gy: 1}, {gx: 5, gy: 4}],
                [{gx: 0, gy: 3}, {gx: 2, gy: 0}, {gx: 5, gy: 3}, {gx: 3, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 1, gy: 0}, {gx: 4, gy: 0}, {gx: 5, gy: 4}],
                [{gx: 0, gy: 5}, {gx: 1, gy: 0}, {gx: 4, gy: 0}, {gx: 5, gy: 5}],
            ],
            6: [ // Шестиугольники
                [{gx: 1, gy: 4}, {gx: 0, gy: 2}, {gx: 1, gy: 0}, {gx: 4, gy: 0}, {gx: 5, gy: 2}, {gx: 4, gy: 4}],
                [{gx: 1, gy: 4}, {gx: 0, gy: 2}, {gx: 2, gy: 0}, {gx: 4, gy: 0}, {gx: 5, gy: 2}, {gx: 4, gy: 4}],
                [{gx: 2, gy: 4}, {gx: 0, gy: 2}, {gx: 2, gy: 0}, {gx: 4, gy: 0}, {gx: 6, gy: 2}, {gx: 4, gy: 4}],
                [{gx: 1, gy: 4}, {gx: 0, gy: 2}, {gx: 1, gy: 0}, {gx: 3, gy: 0}, {gx: 4, gy: 2}, {gx: 3, gy: 4}],
                [{gx: 1, gy: 4}, {gx: 0, gy: 2}, {gx: 1, gy: 0}, {gx: 4, gy: 0}, {gx: 5, gy: 2}, {gx: 4, gy: 4}],
                [{gx: 1, gy: 4}, {gx: 0, gy: 1}, {gx: 2, gy: 0}, {gx: 4, gy: 0}, {gx: 6, gy: 1}, {gx: 5, gy: 4}],
                [{gx: 2, gy: 4}, {gx: 0, gy: 2}, {gx: 2, gy: 0}, {gx: 5, gy: 0}, {gx: 7, gy: 2}, {gx: 5, gy: 4}],
                [{gx: 1, gy: 4}, {gx: 0, gy: 2}, {gx: 1, gy: 0}, {gx: 4, gy: 0}, {gx: 5, gy: 2}, {gx: 4, gy: 4}],
                [{gx: 1, gy: 4}, {gx: 0, gy: 2}, {gx: 1, gy: 0}, {gx: 4, gy: 0}, {gx: 5, gy: 2}, {gx: 4, gy: 4}],
            ],
            7: [ // Сложные фигуры
                [{gx: 0, gy: 4}, {gx: 0, gy: 1}, {gx: 2, gy: 0}, {gx: 4, gy: 0}, {gx: 6, gy: 2}, {gx: 4, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 0, gy: 0}, {gx: 2, gy: 0}, {gx: 5, gy: 1}, {gx: 5, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 0, gy: 0}, {gx: 3, gy: 0}, {gx: 4, gy: 2}, {gx: 4, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 0, gy: 1}, {gx: 2, gy: 0}, {gx: 5, gy: 1}, {gx: 4, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 1, gy: 0}, {gx: 4, gy: 0}, {gx: 6, gy: 4}, {gx: 3, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 0, gy: 1}, {gx: 3, gy: 0}, {gx: 6, gy: 1}, {gx: 6, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 0, gy: 0}, {gx: 2, gy: 0}, {gx: 4, gy: 1}, {gx: 4, gy: 4}],
                [{gx: 0, gy: 4}, {gx: 0, gy: 2}, {gx: 1, gy: 0}, {gx: 5, gy: 0}, {gx: 6, gy: 2}, {gx: 4, gy: 4}],
                [{gx: 1, gy: 4}, {gx: 0, gy: 2}, {gx: 1, gy: 0}, {gx: 4, gy: 2}, {gx: 3, gy: 4}],
            ]
        };
        const variants = allVariants[zadanieNum] || allVariants[4];
        const coords = variants[index % variants.length];
        let maxX = 0, maxY = 0;
        coords.forEach(c => { maxX = Math.max(maxX, c.gx); maxY = Math.max(maxY, c.gy); });
        const cols = maxX + 1;
        const rows = maxY + 1;
        const points = coords.map(c => gridToSVG(c.gx, c.gy));
        const pointsString = points.map(p => `${p.x},${p.y}`).join(' ');
        const width = PADDING * 2 + cols * GRID_SIZE;
        const height = PADDING * 2 + rows * GRID_SIZE;
        return { points, pointsString, width, height };
    }

    // 8. Две точки на сетке - 6x5 клеток
    function twoPointsOnGrid(index) {
        const variants = [
            { A: {gx: 1, gy: 4}, B: {gx: 4, gy: 1} },
            { A: {gx: 0, gy: 3}, B: {gx: 5, gy: 1} },
            { A: {gx: 1, gy: 4}, B: {gx: 5, gy: 1} },
            { A: {gx: 1, gy: 4}, B: {gx: 4, gy: 0} },
            { A: {gx: 0, gy: 4}, B: {gx: 3, gy: 0} },
            { A: {gx: 1, gy: 4}, B: {gx: 5, gy: 0} },
        ];
        const v = variants[index % variants.length];
        const cols = Math.max(v.A.gx, v.B.gx) + 1;
        const rows = Math.max(v.A.gy, v.B.gy) + 1;
        const A = gridToSVG(v.A.gx, v.A.gy);
        const B = gridToSVG(v.B.gx, v.B.gy);
        const width = PADDING * 2 + cols * GRID_SIZE;
        const height = PADDING * 2 + rows * GRID_SIZE;
        return { A, B, width, height };
    }

    // 9. Треугольник со средней линией - 8x6 клеток
    function triangleWithMidline(index) {
        const variants = [
            { A: {gx: 0, gy: 5}, B: {gx: 3, gy: 0}, C: {gx: 7, gy: 5} },
            { A: {gx: 0, gy: 4}, B: {gx: 3, gy: 0}, C: {gx: 7, gy: 4} },
            { A: {gx: 0, gy: 5}, B: {gx: 2, gy: 0}, C: {gx: 5, gy: 5} },
            { A: {gx: 0, gy: 5}, B: {gx: 4, gy: 0}, C: {gx: 7, gy: 5} },
            { A: {gx: 0, gy: 4}, B: {gx: 3, gy: 0}, C: {gx: 5, gy: 4} },
            { A: {gx: 0, gy: 5}, B: {gx: 3, gy: 0}, C: {gx: 8, gy: 5} },
        ];
        const v = variants[index % variants.length];
        const cols = Math.max(v.A.gx, v.B.gx, v.C.gx) + 1;
        const rows = Math.max(v.A.gy, v.B.gy, v.C.gy) + 1;
        const A = gridToSVG(v.A.gx, v.A.gy);
        const B = gridToSVG(v.B.gx, v.B.gy);
        const C = gridToSVG(v.C.gx, v.C.gy);
        const M1 = { x: (A.x + B.x) / 2, y: (A.y + B.y) / 2 };
        const M2 = { x: (B.x + C.x) / 2, y: (B.y + C.y) / 2 };
        const width = PADDING * 2 + cols * GRID_SIZE;
        const height = PADDING * 2 + rows * GRID_SIZE;
        return { A, B, C, M1, M2, width, height };
    }

    // 10. Фигура с отрезком AB - 7x5 клеток
    function figureWithSegmentAB(index) {
        const variants = [
            { shape: [{gx: 0, gy: 4}, {gx: 2, gy: 0}, {gx: 5, gy: 0}, {gx: 7, gy: 4}], A: {gx: 1, gy: 2}, B: {gx: 6, gy: 2} },
            { shape: [{gx: 0, gy: 4}, {gx: 0, gy: 0}, {gx: 4, gy: 0}, {gx: 6, gy: 4}], A: {gx: 0, gy: 2}, B: {gx: 5, gy: 2} },
            { shape: [{gx: 0, gy: 4}, {gx: 2, gy: 0}, {gx: 5, gy: 4}], A: {gx: 1, gy: 2}, B: {gx: 4, gy: 2} },
            { shape: [{gx: 0, gy: 4}, {gx: 0, gy: 1}, {gx: 3, gy: 0}, {gx: 6, gy: 1}, {gx: 5, gy: 4}], A: {gx: 2, gy: 1}, B: {gx: 4, gy: 1} },
            { shape: [{gx: 0, gy: 4}, {gx: 1, gy: 0}, {gx: 4, gy: 0}, {gx: 6, gy: 4}], A: {gx: 1, gy: 2}, B: {gx: 5, gy: 2} },
            { shape: [{gx: 0, gy: 4}, {gx: 0, gy: 0}, {gx: 5, gy: 0}, {gx: 5, gy: 4}], A: {gx: 0, gy: 2}, B: {gx: 5, gy: 2} },
            { shape: [{gx: 0, gy: 4}, {gx: 2, gy: 0}, {gx: 6, gy: 4}], A: {gx: 1, gy: 2}, B: {gx: 4, gy: 2} },
            { shape: [{gx: 0, gy: 4}, {gx: 1, gy: 0}, {gx: 5, gy: 0}, {gx: 6, gy: 4}], A: {gx: 1, gy: 2}, B: {gx: 5, gy: 2} },
            { shape: [{gx: 0, gy: 4}, {gx: 0, gy: 0}, {gx: 4, gy: 0}, {gx: 4, gy: 4}], A: {gx: 0, gy: 2}, B: {gx: 4, gy: 2} },
        ];
        const v = variants[index % variants.length];
        let maxX = 0, maxY = 0;
        v.shape.forEach(c => { maxX = Math.max(maxX, c.gx); maxY = Math.max(maxY, c.gy); });
        maxX = Math.max(maxX, v.A.gx, v.B.gx);
        maxY = Math.max(maxY, v.A.gy, v.B.gy);
        const cols = maxX + 1;
        const rows = maxY + 1;
        const shapePoints = v.shape.map(c => `${gridToSVG(c.gx, c.gy).x},${gridToSVG(c.gx, c.gy).y}`).join(' ');
        const A = gridToSVG(v.A.gx, v.A.gy);
        const B = gridToSVG(v.B.gx, v.B.gy);
        const width = PADDING * 2 + cols * GRID_SIZE;
        const height = PADDING * 2 + rows * GRID_SIZE;
        const labelA = { x: A.x - 14, y: A.y + 4 };
        const labelB = { x: B.x + 6, y: B.y + 4 };
        return { shapePoints, A, B, labelA, labelB, width, height };
    }

    // 11. Трапеция со средней линией - 8x6 клеток
    function trapezoidWithMidline(index) {
        const variants = [
            { A: {gx: 0, gy: 4}, B: {gx: 2, gy: 0}, C: {gx: 5, gy: 0}, D: {gx: 7, gy: 4} },
            { A: {gx: 0, gy: 4}, B: {gx: 2, gy: 0}, C: {gx: 4, gy: 0}, D: {gx: 6, gy: 4} },
            { A: {gx: 0, gy: 4}, B: {gx: 1, gy: 1}, C: {gx: 4, gy: 1}, D: {gx: 6, gy: 4} },
            { A: {gx: 0, gy: 4}, B: {gx: 1, gy: 0}, C: {gx: 5, gy: 0}, D: {gx: 7, gy: 4} },
            { A: {gx: 0, gy: 4}, B: {gx: 2, gy: 0}, C: {gx: 4, gy: 0}, D: {gx: 5, gy: 4} },
            { A: {gx: 0, gy: 4}, B: {gx: 2, gy: 0}, C: {gx: 6, gy: 0}, D: {gx: 8, gy: 4} },
            { A: {gx: 0, gy: 4}, B: {gx: 1, gy: 0}, C: {gx: 4, gy: 0}, D: {gx: 5, gy: 4} },
            { A: {gx: 0, gy: 4}, B: {gx: 1, gy: 1}, C: {gx: 5, gy: 1}, D: {gx: 7, gy: 4} },
            { A: {gx: 0, gy: 4}, B: {gx: 1, gy: 0}, C: {gx: 5, gy: 0}, D: {gx: 7, gy: 4} },
        ];
        const v = variants[index % variants.length];
        const cols = Math.max(v.A.gx, v.B.gx, v.C.gx, v.D.gx) + 1;
        const rows = Math.max(v.A.gy, v.B.gy, v.C.gy, v.D.gy) + 1;
        const A = gridToSVG(v.A.gx, v.A.gy);
        const B = gridToSVG(v.B.gx, v.B.gy);
        const C = gridToSVG(v.C.gx, v.C.gy);
        const D = gridToSVG(v.D.gx, v.D.gy);
        const M1 = { x: (A.x + B.x) / 2, y: (A.y + B.y) / 2 };
        const M2 = { x: (C.x + D.x) / 2, y: (C.y + D.y) / 2 };
        const width = PADDING * 2 + cols * GRID_SIZE;
        const height = PADDING * 2 + rows * GRID_SIZE;
        return { A, B, C, D, M1, M2, width, height };
    }

    // 12. Два круга - 10x6 клеток
    function twoCirclesOnGrid(index) {
        const variants = [
            { c1: {gx: 2, gy: 3}, r1: 2, c2: {gx: 6, gy: 3}, r2: 1 },
            { c1: {gx: 2, gy: 3}, r1: 2, c2: {gx: 7, gy: 3}, r2: 2 },
            { c1: {gx: 3, gy: 3}, r1: 2, c2: {gx: 3, gy: 3}, r2: 1 },
            { c1: {gx: 2, gy: 2}, r1: 1, c2: {gx: 5, gy: 2}, r2: 2 },
            { c1: {gx: 2, gy: 3}, r1: 2, c2: {gx: 7, gy: 3}, r2: 2 },
            { c1: {gx: 3, gy: 3}, r1: 2, c2: {gx: 7, gy: 3}, r2: 2 },
            { c1: {gx: 2, gy: 2}, r1: 2, c2: {gx: 6, gy: 2}, r2: 1 },
            { c1: {gx: 2, gy: 2}, r1: 1, c2: {gx: 2, gy: 2}, r2: 2 },
            { c1: {gx: 2, gy: 3}, r1: 2, c2: {gx: 7, gy: 3}, r2: 2 },
        ];
        const v = variants[index % variants.length];
        const cols = Math.max(v.c1.gx + v.r1, v.c2.gx + v.r2) + 1;
        const rows = Math.max(v.c1.gy + v.r1, v.c2.gy + v.r2) + 1;
        const c1 = gridToSVG(v.c1.gx, v.c1.gy);
        const c2 = gridToSVG(v.c2.gx, v.c2.gy);
        const r1 = v.r1 * GRID_SIZE;
        const r2 = v.r2 * GRID_SIZE;
        const width = PADDING * 2 + cols * GRID_SIZE;
        const height = PADDING * 2 + rows * GRID_SIZE;
        return { c1, c2, r1, r2, width, height };
    }
</script>

{{-- Инструмент для пометки заданий --}}
@include('components.task-review-tool', ['topicId' => '18'])

</body>
</html>
