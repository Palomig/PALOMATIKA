<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>11. Графики функций - Тест парсинга PDF</title>

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
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-7xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← Назад к темам</a>
        <div class="flex gap-2 flex-wrap justify-center">
            <a href="{{ route('test.topic06') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">06</a>
            <a href="{{ route('test.topic07') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">07</a>
            <a href="{{ route('test.topic08') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">08</a>
            <a href="{{ route('test.topic09') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">09</a>
            <a href="{{ route('test.topic10') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">10</a>
            <span class="px-2 py-1 rounded bg-cyan-500 text-white font-bold">11</span>
            <a href="{{ route('test.topic12') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">12</a>
            <a href="{{ route('test.topic15') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">15</a>
            <a href="{{ route('test.topic16') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">16</a>
            <a href="{{ route('test.topic17') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">17</a>
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
        <h1 class="text-4xl font-bold text-white mb-2">11. Графики функций</h1>
        <p class="text-slate-400 text-lg">Соответствие графиков и формул</p>
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
            <h2 class="text-2xl font-bold text-white">11. Графики функций</h2>
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
                            <div class="text-cyan-400 font-semibold mb-3">{{ $task['id'] }}.</div>

                            {{-- SVG Graph --}}
                            <div class="bg-slate-900/50 rounded-lg p-2 mb-4" x-data="graphData({{ json_encode($task) }}, {{ $index }})">
                                <svg :viewBox="viewBox" class="w-full h-48 rounded">
                                    {{-- Grid pattern --}}
                                    <defs>
                                        <pattern :id="'grid-' + index" width="20" height="20" patternUnits="userSpaceOnUse">
                                            <path d="M 20 0 L 0 0 0 20" fill="none" stroke="#334155" stroke-width="0.5"/>
                                        </pattern>
                                    </defs>
                                    <rect x="0" y="0" width="100%" height="100%" :fill="'url(#grid-' + index + ')'"/>

                                    {{-- Axes --}}
                                    <line :x1="padding" :y1="centerY" :x2="width - padding" :y2="centerY" stroke="#64748b" stroke-width="1.5"/>
                                    <line :x1="centerX" :y1="padding" :x2="centerX" :y2="height - padding" stroke="#64748b" stroke-width="1.5"/>

                                    {{-- Axis arrows --}}
                                    <polygon :points="arrowX" fill="#64748b"/>
                                    <polygon :points="arrowY" fill="#64748b"/>

                                    {{-- Axis labels --}}
                                    <text :x="width - padding + 5" :y="centerY + 4" fill="#94a3b8" font-size="12">x</text>
                                    <text :x="centerX + 5" :y="padding - 5" fill="#94a3b8" font-size="12">y</text>

                                    {{-- Origin --}}
                                    <text :x="centerX - 12" :y="centerY + 14" fill="#94a3b8" font-size="11">0</text>

                                    {{-- Tick marks --}}
                                    <template x-for="i in ticks">
                                        <g>
                                            <line :x1="centerX + i * scale" :y1="centerY - 3" :x2="centerX + i * scale" :y2="centerY + 3" stroke="#64748b" stroke-width="1"/>
                                            <line :x1="centerX - 3" :y1="centerY - i * scale" :x2="centerX + 3" :y2="centerY - i * scale" stroke="#64748b" stroke-width="1"/>
                                        </g>
                                    </template>

                                    {{-- Function graphs (3-4 per task) --}}
                                    <template x-for="(graph, gi) in graphs">
                                        <g>
                                            {{-- Graph label (А, Б, В, Г) --}}
                                            <text :x="graph.labelX" :y="graph.labelY" :fill="graph.color" font-size="14" font-weight="bold" x-text="graph.label"></text>

                                            {{-- The graph path --}}
                                            <path :d="graph.path" :stroke="graph.color" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                                        </g>
                                    </template>
                                </svg>
                            </div>

                            {{-- Options --}}
                            <div class="flex flex-wrap gap-2">
                                @if(isset($task['options']))
                                    @foreach($task['options'] as $i => $opt)
                                        <span class="bg-slate-700 text-slate-200 px-3 py-1.5 rounded-lg text-sm">
                                            {{ chr(1040 + $i) }}) ${{ $opt }}$
                                        </span>
                                    @endforeach
                                @elseif(isset($task['statements']))
                                    @foreach($task['statements'] as $i => $stmt)
                                        <span class="bg-slate-700 text-slate-200 px-3 py-1.5 rounded-lg text-sm">
                                            {{ $i + 1 }}) {{ $stmt }}
                                        </span>
                                    @endforeach
                                @endif
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
            <p><strong class="text-slate-300">Тема:</strong> 11. Графики функций</p>
            <p><strong class="text-slate-300">Источник:</strong> {{ $source ?? 'Manual' }}</p>
            <p><strong class="text-slate-300">Контроллер:</strong> <code class="bg-slate-700 px-2 py-1 rounded text-xs">TestPdfController::getAllBlocksData11()</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1">
                <li>Линейные функции y = kx + b</li>
                <li>Квадратичные функции y = ax² + bx + c</li>
                <li>Гиперболы y = k/x</li>
                <li>Всего: {{ $totalTasks }} задач с SVG графиками</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-slate-500 text-sm mt-8">Все графики генерируются программно через SVG + Alpine.js</p>
</div>

<script>
    // Graph generation for function matching tasks
    function graphData(task, index) {
        const width = 240;
        const height = 180;
        const padding = 25;
        const centerX = width / 2;
        const centerY = height / 2;
        const scale = 20; // pixels per unit

        const colors = ['#f59e0b', '#10b981', '#60a5fa', '#f472b6'];
        const labels = ['А)', 'Б)', 'В)', 'Г)'];

        // Parse options to determine function types and generate graphs
        const graphs = [];
        const options = task.options || [];

        options.forEach((opt, i) => {
            const graph = parseAndDrawFunction(opt, i, {
                centerX, centerY, scale, padding, width, height,
                color: colors[i % colors.length],
                label: labels[i]
            });
            if (graph) graphs.push(graph);
        });

        return {
            width,
            height,
            padding,
            centerX,
            centerY,
            scale,
            index,
            viewBox: `0 0 ${width} ${height}`,
            arrowX: `${width - padding},${centerY - 4} ${width - padding},${centerY + 4} ${width - padding + 8},${centerY}`,
            arrowY: `${centerX - 4},${padding} ${centerX + 4},${padding} ${centerX},${padding - 8}`,
            ticks: [-4, -3, -2, -1, 1, 2, 3, 4],
            graphs
        };
    }

    function parseAndDrawFunction(formula, index, params) {
        const { centerX, centerY, scale, padding, width, height, color, label } = params;

        // Clean formula
        let f = formula.replace(/\s+/g, '').replace('y=', '');

        let path = '';
        let labelX = padding + 10 + index * 50;
        let labelY = padding + 15;

        // Detect function type and draw
        if (f.includes('x²') || f.includes('x^2')) {
            // Quadratic function
            path = drawQuadratic(f, params);
            labelY = padding + 20 + index * 12;
        } else if (f.includes('/x') || f.includes('\\frac')) {
            // Hyperbola
            path = drawHyperbola(f, params);
            labelX = index < 2 ? width - padding - 30 : padding + 10;
            labelY = index < 2 ? padding + 30 : height - padding - 10;
        } else {
            // Linear function
            path = drawLinear(f, params);
            labelX = width - padding - 25;
            labelY = padding + 15 + index * 15;
        }

        return {
            path,
            color,
            label,
            labelX,
            labelY
        };
    }

    function drawLinear(f, params) {
        const { centerX, centerY, scale, padding, width, height } = params;

        // Parse y = kx + b
        let k = 1, b = 0;

        // Handle various formats
        f = f.replace(/\\frac\{(\d+)\}\{(\d+)\}/g, (m, n, d) => n/d);
        f = f.replace(/−/g, '-');

        if (f.match(/^(-?\d*\.?\d*)x([+-]\d+\.?\d*)?$/)) {
            const match = f.match(/^(-?\d*\.?\d*)x([+-]\d+\.?\d*)?$/);
            k = match[1] === '' || match[1] === '+' ? 1 : (match[1] === '-' ? -1 : parseFloat(match[1]));
            b = match[2] ? parseFloat(match[2]) : 0;
        } else if (f.match(/^(-?\d+\.?\d*)$/)) {
            k = 0;
            b = parseFloat(f);
        } else if (f.match(/^(-?\d*\.?\d*)x$/)) {
            const match = f.match(/^(-?\d*\.?\d*)x$/);
            k = match[1] === '' || match[1] === '+' ? 1 : (match[1] === '-' ? -1 : parseFloat(match[1]));
        }

        // Generate path
        const xMin = -5, xMax = 5;
        const points = [];

        for (let x = xMin; x <= xMax; x += 0.5) {
            const y = k * x + b;
            const px = centerX + x * scale;
            const py = centerY - y * scale;

            if (px >= padding && px <= width - padding && py >= padding && py <= height - padding) {
                points.push(`${px},${py}`);
            }
        }

        return points.length > 1 ? `M ${points.join(' L ')}` : '';
    }

    function drawQuadratic(f, params) {
        const { centerX, centerY, scale, padding, width, height } = params;

        // Parse y = ax² + bx + c
        let a = 1, b = 0, c = 0;

        f = f.replace(/\\frac\{(\d+)\}\{(\d+)\}/g, (m, n, d) => n/d);
        f = f.replace(/−/g, '-');
        f = f.replace(/x²/g, 'x^2');

        // Simple parsing for common formats
        const aMatch = f.match(/^(-?\d*\.?\d*)x\^2/);
        if (aMatch) {
            a = aMatch[1] === '' || aMatch[1] === '+' ? 1 : (aMatch[1] === '-' ? -1 : parseFloat(aMatch[1]));
        }

        const bMatch = f.match(/([+-]\d*\.?\d*)x(?!\^)/);
        if (bMatch) {
            const bStr = bMatch[1];
            b = bStr === '+' || bStr === '' ? 1 : (bStr === '-' ? -1 : parseFloat(bStr));
        }

        const cMatch = f.match(/([+-]\d+\.?\d*)$/);
        if (cMatch && !cMatch[0].includes('x')) {
            c = parseFloat(cMatch[1]);
        }

        // Generate parabola path
        const points = [];
        for (let x = -6; x <= 6; x += 0.2) {
            const y = a * x * x + b * x + c;
            const px = centerX + x * scale;
            const py = centerY - y * scale;

            if (px >= padding - 5 && px <= width - padding + 5 && py >= padding - 5 && py <= height - padding + 5) {
                points.push(`${px},${py}`);
            }
        }

        return points.length > 1 ? `M ${points.join(' L ')}` : '';
    }

    function drawHyperbola(f, params) {
        const { centerX, centerY, scale, padding, width, height } = params;

        // Parse y = k/x
        let k = 1;

        f = f.replace(/\\frac\{(-?\d+)\}\{x\}/g, (m, n) => n + '/x');
        f = f.replace(/−/g, '-');

        const match = f.match(/(-?\d+\.?\d*)\/x/);
        if (match) {
            k = parseFloat(match[1]);
        }

        // Generate hyperbola path (two branches)
        const points1 = [], points2 = [];

        // Positive x branch
        for (let x = 0.3; x <= 6; x += 0.1) {
            const y = k / x;
            const px = centerX + x * scale;
            const py = centerY - y * scale;

            if (px >= padding && px <= width - padding && py >= padding && py <= height - padding) {
                points1.push(`${px},${py}`);
            }
        }

        // Negative x branch
        for (let x = -6; x <= -0.3; x += 0.1) {
            const y = k / x;
            const px = centerX + x * scale;
            const py = centerY - y * scale;

            if (px >= padding && px <= width - padding && py >= padding && py <= height - padding) {
                points2.push(`${px},${py}`);
            }
        }

        let path = '';
        if (points1.length > 1) path += `M ${points1.join(' L ')}`;
        if (points2.length > 1) path += ` M ${points2.join(' L ')}`;

        return path;
    }
</script>

</body>
</html>
