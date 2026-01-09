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

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
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
        <a href="{{ route('test.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← Назад к темам</a>
        <div class="flex gap-2 flex-wrap justify-center">
            <a href="{{ route('test.topic06') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">06</a>
            <a href="{{ route('test.topic07') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">07</a>
            <a href="{{ route('test.topic08') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">08</a>
            <a href="{{ route('test.topic09') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">09</a>
            <a href="{{ route('test.topic10') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">10</a>
            <span class="px-2 py-1 rounded bg-cyan-500 text-white font-bold">11</span>
            <a href="{{ route('test.topic12') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">12</a>
            <a href="{{ route('test.topic13') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">13</a>
            <a href="{{ route('test.topic14') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">14</a>
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

    @foreach($blocks as $blockIndex => $block)
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

        @foreach($block['zadaniya'] as $zadanieIndex => $zadanie)
            <div class="mb-10">
                {{-- Zadanie Header --}}
                <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-cyan-500">
                    <h3 class="text-lg font-semibold text-white">
                        Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}
                    </h3>
                </div>

                {{-- Tasks --}}
                <div class="space-y-8">
                    @foreach($zadanie['tasks'] ?? [] as $taskIndex => $task)
                        @php
                            $uniqueId = $blockIndex . '-' . $zadanieIndex . '-' . $taskIndex;
                            $options = $task['options'] ?? [];
                            $graphLabels = ['А', 'Б', 'В', 'Г'];
                        @endphp

                        <div class="bg-slate-800/70 rounded-xl p-6 border border-slate-700">
                            {{-- Task number --}}
                            <div class="text-cyan-400 font-bold text-lg mb-4">{{ $task['id'] }}</div>

                            {{-- Three separate graphs in a row --}}
                            <div class="grid grid-cols-3 gap-4 mb-6">
                                @foreach($options as $optIndex => $formula)
                                    <div class="bg-slate-900/50 rounded-lg p-3">
                                        {{-- Graph label --}}
                                        <div class="text-center text-white font-bold mb-2">{{ $graphLabels[$optIndex] }})</div>
                                        {{-- SVG container --}}
                                        <div id="graph-{{ $uniqueId }}-{{ $optIndex }}" class="w-full aspect-square"></div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Formulas to match --}}
                            <div class="flex flex-wrap gap-4 mb-4 justify-center">
                                @foreach($options as $i => $opt)
                                    <span class="bg-slate-700 text-slate-200 px-4 py-2 rounded-lg text-sm">
                                        {{ $i + 1 }}) ${{ $opt }}$
                                    </span>
                                @endforeach
                            </div>

                            {{-- Answer table --}}
                            <div class="flex justify-center gap-1 mt-4">
                                @foreach($options as $i => $opt)
                                    <div class="flex flex-col items-center">
                                        <span class="text-slate-400 text-sm font-bold mb-1">{{ $graphLabels[$i] }}</span>
                                        <div class="w-10 h-8 border-2 border-slate-600 rounded bg-slate-800"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const options = @json($options);
                                options.forEach((formula, i) => {
                                    renderSingleGraph('graph-{{ $uniqueId }}-' + i, formula);
                                });
                            });
                        </script>
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

    <p class="text-center text-slate-500 text-sm mt-8">Все графики генерируются программно через SVG</p>
</div>

<script>
    const WIDTH = 180;
    const HEIGHT = 180;
    const PADDING = 25;
    const CENTER_X = WIDTH / 2;
    const CENTER_Y = HEIGHT / 2;
    const SCALE = 18;

    function renderSingleGraph(containerId, formula) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Create SVG
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', `0 0 ${WIDTH} ${HEIGHT}`);
        svg.setAttribute('class', 'w-full h-full');

        // Background
        const bg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        bg.setAttribute('width', WIDTH);
        bg.setAttribute('height', HEIGHT);
        bg.setAttribute('fill', '#0f172a');
        svg.appendChild(bg);

        // Grid
        const gridGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        for (let i = -5; i <= 5; i++) {
            // Vertical lines
            const vLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            vLine.setAttribute('x1', CENTER_X + i * SCALE);
            vLine.setAttribute('y1', PADDING - 5);
            vLine.setAttribute('x2', CENTER_X + i * SCALE);
            vLine.setAttribute('y2', HEIGHT - PADDING + 5);
            vLine.setAttribute('stroke', '#334155');
            vLine.setAttribute('stroke-width', '0.5');
            gridGroup.appendChild(vLine);

            // Horizontal lines
            const hLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            hLine.setAttribute('x1', PADDING - 5);
            hLine.setAttribute('y1', CENTER_Y + i * SCALE);
            hLine.setAttribute('x2', WIDTH - PADDING + 5);
            hLine.setAttribute('y2', CENTER_Y + i * SCALE);
            hLine.setAttribute('stroke', '#334155');
            hLine.setAttribute('stroke-width', '0.5');
            gridGroup.appendChild(hLine);
        }
        svg.appendChild(gridGroup);

        // X axis
        const xAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        xAxis.setAttribute('x1', PADDING - 5);
        xAxis.setAttribute('y1', CENTER_Y);
        xAxis.setAttribute('x2', WIDTH - PADDING + 5);
        xAxis.setAttribute('y2', CENTER_Y);
        xAxis.setAttribute('stroke', '#64748b');
        xAxis.setAttribute('stroke-width', '1.5');
        svg.appendChild(xAxis);

        // Y axis
        const yAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        yAxis.setAttribute('x1', CENTER_X);
        yAxis.setAttribute('y1', PADDING - 5);
        yAxis.setAttribute('x2', CENTER_X);
        yAxis.setAttribute('y2', HEIGHT - PADDING + 5);
        yAxis.setAttribute('stroke', '#64748b');
        yAxis.setAttribute('stroke-width', '1.5');
        svg.appendChild(yAxis);

        // X arrow
        const xArrow = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        xArrow.setAttribute('points', `${WIDTH-PADDING+2},${CENTER_Y-3} ${WIDTH-PADDING+2},${CENTER_Y+3} ${WIDTH-PADDING+8},${CENTER_Y}`);
        xArrow.setAttribute('fill', '#64748b');
        svg.appendChild(xArrow);

        // Y arrow
        const yArrow = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        yArrow.setAttribute('points', `${CENTER_X-3},${PADDING-2} ${CENTER_X+3},${PADDING-2} ${CENTER_X},${PADDING-8}`);
        yArrow.setAttribute('fill', '#64748b');
        svg.appendChild(yArrow);

        // Axis labels
        const xLabel = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        xLabel.setAttribute('x', WIDTH - PADDING + 10);
        xLabel.setAttribute('y', CENTER_Y + 4);
        xLabel.setAttribute('fill', '#94a3b8');
        xLabel.setAttribute('font-size', '11');
        xLabel.setAttribute('font-style', 'italic');
        xLabel.textContent = 'x';
        svg.appendChild(xLabel);

        const yLabel = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        yLabel.setAttribute('x', CENTER_X + 5);
        yLabel.setAttribute('y', PADDING - 10);
        yLabel.setAttribute('fill', '#94a3b8');
        yLabel.setAttribute('font-size', '11');
        yLabel.setAttribute('font-style', 'italic');
        yLabel.textContent = 'y';
        svg.appendChild(yLabel);

        // Origin label and unit marks
        const origin = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        origin.setAttribute('x', CENTER_X - 10);
        origin.setAttribute('y', CENTER_Y + 12);
        origin.setAttribute('fill', '#94a3b8');
        origin.setAttribute('font-size', '10');
        origin.textContent = '0';
        svg.appendChild(origin);

        // Mark "1" on x axis
        const oneX = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        oneX.setAttribute('x', CENTER_X + SCALE - 2);
        oneX.setAttribute('y', CENTER_Y + 12);
        oneX.setAttribute('fill', '#94a3b8');
        oneX.setAttribute('font-size', '10');
        oneX.textContent = '1';
        svg.appendChild(oneX);

        // Mark "1" on y axis
        const oneY = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        oneY.setAttribute('x', CENTER_X + 5);
        oneY.setAttribute('y', CENTER_Y - SCALE + 4);
        oneY.setAttribute('fill', '#94a3b8');
        oneY.setAttribute('font-size', '10');
        oneY.textContent = '1';
        svg.appendChild(oneY);

        // Tick marks
        for (let i = -4; i <= 4; i++) {
            if (i === 0) continue;
            // X ticks
            const xTick = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            xTick.setAttribute('x1', CENTER_X + i * SCALE);
            xTick.setAttribute('y1', CENTER_Y - 2);
            xTick.setAttribute('x2', CENTER_X + i * SCALE);
            xTick.setAttribute('y2', CENTER_Y + 2);
            xTick.setAttribute('stroke', '#64748b');
            xTick.setAttribute('stroke-width', '1');
            svg.appendChild(xTick);

            // Y ticks
            const yTick = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            yTick.setAttribute('x1', CENTER_X - 2);
            yTick.setAttribute('y1', CENTER_Y - i * SCALE);
            yTick.setAttribute('x2', CENTER_X + 2);
            yTick.setAttribute('y2', CENTER_Y - i * SCALE);
            yTick.setAttribute('stroke', '#64748b');
            yTick.setAttribute('stroke-width', '1');
            svg.appendChild(yTick);
        }

        // Draw the function
        const pathData = parseAndDrawFunction(formula);
        if (pathData) {
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', pathData);
            path.setAttribute('stroke', '#10b981');
            path.setAttribute('stroke-width', '2');
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke-linecap', 'round');
            svg.appendChild(path);
        }

        container.appendChild(svg);
    }

    function parseAndDrawFunction(formula) {
        // Clean formula
        let f = formula.replace(/\s+/g, '').replace('y=', '');
        f = f.replace(/−/g, '-');

        // Detect function type
        if (f.includes('x²') || f.includes('x^2') || f.match(/\d*x\^?2/)) {
            return drawQuadratic(f);
        } else if (f.includes('/x') || f.match(/\\frac\{[^}]+\}\{x\}/) || f.match(/\\frac\{[^}]+\}\{\d*x\}/)) {
            return drawHyperbola(f);
        } else {
            return drawLinear(f);
        }
    }

    function drawLinear(f) {
        let k = 0, b = 0;

        // Handle fractions like \frac{2}{5}
        f = f.replace(/\\frac\{(-?\d+)\}\{(\d+)\}/g, (m, n, d) => (parseFloat(n) / parseFloat(d)));

        // Check for constant: y = 3 or y = -1
        if (f.match(/^-?\d+\.?\d*$/) && !f.includes('x')) {
            k = 0;
            b = parseFloat(f);
        }
        // Check for y = kx + b or y = kx - b
        else if (f.match(/^(-?\d*\.?\d*)x([+-]\d+\.?\d*)?$/)) {
            const match = f.match(/^(-?\d*\.?\d*)x([+-]\d+\.?\d*)?$/);
            let kStr = match[1];
            if (kStr === '' || kStr === '+') k = 1;
            else if (kStr === '-') k = -1;
            else k = parseFloat(kStr);
            b = match[2] ? parseFloat(match[2]) : 0;
        }
        // Check for y = b + kx
        else if (f.match(/^(-?\d+\.?\d*)([+-]\d*\.?\d*)x$/)) {
            const match = f.match(/^(-?\d+\.?\d*)([+-]\d*\.?\d*)x$/);
            b = parseFloat(match[1]);
            let kStr = match[2];
            if (kStr === '+' || kStr === '') k = 1;
            else if (kStr === '-') k = -1;
            else k = parseFloat(kStr);
        }
        // Just y = x or y = -x
        else if (f === 'x') {
            k = 1; b = 0;
        } else if (f === '-x') {
            k = -1; b = 0;
        }

        // Generate path
        const points = [];
        for (let x = -6; x <= 6; x += 0.25) {
            const y = k * x + b;
            const px = CENTER_X + x * SCALE;
            const py = CENTER_Y - y * SCALE;

            if (px >= 0 && px <= WIDTH && py >= 0 && py <= HEIGHT) {
                points.push(`${px.toFixed(1)},${py.toFixed(1)}`);
            }
        }

        return points.length > 1 ? `M ${points.join(' L ')}` : '';
    }

    function drawQuadratic(f) {
        let a = 1, b = 0, c = 0;

        // Handle fractions
        f = f.replace(/\\frac\{(-?\d+)\}\{(\d+)\}/g, (m, n, d) => (parseFloat(n) / parseFloat(d)));
        f = f.replace(/x²/g, 'x^2');

        // Check for coefficient of x^2
        const aMatch = f.match(/^(-?\d*\.?\d*)x\^?2/);
        if (aMatch) {
            let aStr = aMatch[1];
            if (aStr === '' || aStr === '+') a = 1;
            else if (aStr === '-') a = -1;
            else a = parseFloat(aStr);
        }

        // Check for x coefficient (not x^2)
        const bMatch = f.match(/([+-]\d*\.?\d*)x(?!\^)/);
        if (bMatch) {
            let bStr = bMatch[1];
            if (bStr === '+' || bStr === '') b = 1;
            else if (bStr === '-') b = -1;
            else b = parseFloat(bStr);
        }

        // Check for constant
        const cMatch = f.match(/([+-]\d+\.?\d*)$/);
        if (cMatch && !cMatch[0].includes('x')) {
            c = parseFloat(cMatch[1]);
        }

        // Generate parabola path
        const points = [];
        for (let x = -6; x <= 6; x += 0.1) {
            const y = a * x * x + b * x + c;
            const px = CENTER_X + x * SCALE;
            const py = CENTER_Y - y * SCALE;

            if (px >= -10 && px <= WIDTH + 10 && py >= -10 && py <= HEIGHT + 10) {
                points.push(`${px.toFixed(1)},${py.toFixed(1)}`);
            }
        }

        return points.length > 1 ? `M ${points.join(' L ')}` : '';
    }

    function drawHyperbola(f) {
        let k = 1;

        // Handle \frac{k}{x}
        f = f.replace(/\\frac\{(-?\d+)\}\{x\}/g, (m, n) => n + '/x');
        f = f.replace(/\\frac\{(-?\d+)\}\{(\d+)x\}/g, (m, n, d) => (parseFloat(n) / parseFloat(d)) + '/x');

        const match = f.match(/(-?\d*\.?\d*)\/x/);
        if (match) {
            let kStr = match[1];
            if (kStr === '' || kStr === '+') k = 1;
            else if (kStr === '-') k = -1;
            else k = parseFloat(kStr);
        }

        // Generate hyperbola paths (two branches)
        const points1 = [];
        const points2 = [];

        // Positive x branch
        for (let x = 0.2; x <= 6; x += 0.05) {
            const y = k / x;
            const px = CENTER_X + x * SCALE;
            const py = CENTER_Y - y * SCALE;

            if (px >= 0 && px <= WIDTH && py >= 0 && py <= HEIGHT) {
                points1.push(`${px.toFixed(1)},${py.toFixed(1)}`);
            }
        }

        // Negative x branch
        for (let x = -6; x <= -0.2; x += 0.05) {
            const y = k / x;
            const px = CENTER_X + x * SCALE;
            const py = CENTER_Y - y * SCALE;

            if (px >= 0 && px <= WIDTH && py >= 0 && py <= HEIGHT) {
                points2.push(`${px.toFixed(1)},${py.toFixed(1)}`);
            }
        }

        let path = '';
        if (points1.length > 1) path += `M ${points1.join(' L ')}`;
        if (points2.length > 1) path += ` M ${points2.join(' L ')}`;

        return path;
    }
</script>

{{-- Инструмент для пометки заданий --}}
@include('components.task-review-tool', ['topicId' => '11'])

</body>
</html>
