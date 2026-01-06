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
            <a href="{{ route('test.topic10') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">10</a>
            <span class="px-2 py-1 rounded bg-cyan-500 text-white font-bold">11</span>
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

                {{-- Tasks Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($zadanie['tasks'] ?? [] as $taskIndex => $task)
                        @php
                            $uniqueId = $blockIndex . '-' . $zadanieIndex . '-' . $taskIndex;
                        @endphp
                        <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 hover:border-cyan-500/50 transition-colors">
                            <div class="text-cyan-400 font-semibold mb-3">{{ $task['id'] }}.</div>

                            {{-- SVG Graph Container --}}
                            <div class="bg-slate-900/50 rounded-lg p-2 mb-4">
                                <div id="graph-{{ $uniqueId }}" class="w-full h-48"></div>
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

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                renderGraph('graph-{{ $uniqueId }}', @json($task['options'] ?? []), {{ $taskIndex }});
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
    const WIDTH = 280;
    const HEIGHT = 200;
    const PADDING = 30;
    const CENTER_X = WIDTH / 2;
    const CENTER_Y = HEIGHT / 2;
    const SCALE = 20;
    const COLORS = ['#f59e0b', '#10b981', '#60a5fa', '#f472b6'];
    const LABELS = ['А)', 'Б)', 'В)', 'Г)'];

    function renderGraph(containerId, options, taskIndex) {
        const container = document.getElementById(containerId);
        if (!container || !options.length) return;

        // Create SVG
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', `0 0 ${WIDTH} ${HEIGHT}`);
        svg.setAttribute('class', 'w-full h-full rounded');

        // Grid pattern
        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        const pattern = document.createElementNS('http://www.w3.org/2000/svg', 'pattern');
        pattern.setAttribute('id', `grid-${containerId}`);
        pattern.setAttribute('width', '20');
        pattern.setAttribute('height', '20');
        pattern.setAttribute('patternUnits', 'userSpaceOnUse');
        const patternPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        patternPath.setAttribute('d', 'M 20 0 L 0 0 0 20');
        patternPath.setAttribute('fill', 'none');
        patternPath.setAttribute('stroke', '#334155');
        patternPath.setAttribute('stroke-width', '0.5');
        pattern.appendChild(patternPath);
        defs.appendChild(pattern);
        svg.appendChild(defs);

        // Background with grid
        const bg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        bg.setAttribute('x', '0');
        bg.setAttribute('y', '0');
        bg.setAttribute('width', WIDTH);
        bg.setAttribute('height', HEIGHT);
        bg.setAttribute('fill', `url(#grid-${containerId})`);
        svg.appendChild(bg);

        // X axis
        const xAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        xAxis.setAttribute('x1', PADDING);
        xAxis.setAttribute('y1', CENTER_Y);
        xAxis.setAttribute('x2', WIDTH - PADDING);
        xAxis.setAttribute('y2', CENTER_Y);
        xAxis.setAttribute('stroke', '#64748b');
        xAxis.setAttribute('stroke-width', '1.5');
        svg.appendChild(xAxis);

        // Y axis
        const yAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        yAxis.setAttribute('x1', CENTER_X);
        yAxis.setAttribute('y1', PADDING);
        yAxis.setAttribute('x2', CENTER_X);
        yAxis.setAttribute('y2', HEIGHT - PADDING);
        yAxis.setAttribute('stroke', '#64748b');
        yAxis.setAttribute('stroke-width', '1.5');
        svg.appendChild(yAxis);

        // X arrow
        const xArrow = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        xArrow.setAttribute('points', `${WIDTH-PADDING},${CENTER_Y-4} ${WIDTH-PADDING},${CENTER_Y+4} ${WIDTH-PADDING+8},${CENTER_Y}`);
        xArrow.setAttribute('fill', '#64748b');
        svg.appendChild(xArrow);

        // Y arrow
        const yArrow = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        yArrow.setAttribute('points', `${CENTER_X-4},${PADDING} ${CENTER_X+4},${PADDING} ${CENTER_X},${PADDING-8}`);
        yArrow.setAttribute('fill', '#64748b');
        svg.appendChild(yArrow);

        // Labels
        const xLabel = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        xLabel.setAttribute('x', WIDTH - PADDING + 5);
        xLabel.setAttribute('y', CENTER_Y + 4);
        xLabel.setAttribute('fill', '#94a3b8');
        xLabel.setAttribute('font-size', '12');
        xLabel.textContent = 'x';
        svg.appendChild(xLabel);

        const yLabel = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        yLabel.setAttribute('x', CENTER_X + 5);
        yLabel.setAttribute('y', PADDING - 5);
        yLabel.setAttribute('fill', '#94a3b8');
        yLabel.setAttribute('font-size', '12');
        yLabel.textContent = 'y';
        svg.appendChild(yLabel);

        // Origin
        const origin = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        origin.setAttribute('x', CENTER_X - 12);
        origin.setAttribute('y', CENTER_Y + 14);
        origin.setAttribute('fill', '#94a3b8');
        origin.setAttribute('font-size', '11');
        origin.textContent = '0';
        svg.appendChild(origin);

        // Tick marks
        for (let i = -5; i <= 5; i++) {
            if (i === 0) continue;
            // X ticks
            const xTick = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            xTick.setAttribute('x1', CENTER_X + i * SCALE);
            xTick.setAttribute('y1', CENTER_Y - 3);
            xTick.setAttribute('x2', CENTER_X + i * SCALE);
            xTick.setAttribute('y2', CENTER_Y + 3);
            xTick.setAttribute('stroke', '#64748b');
            xTick.setAttribute('stroke-width', '1');
            svg.appendChild(xTick);

            // Y ticks
            const yTick = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            yTick.setAttribute('x1', CENTER_X - 3);
            yTick.setAttribute('y1', CENTER_Y - i * SCALE);
            yTick.setAttribute('x2', CENTER_X + 3);
            yTick.setAttribute('y2', CENTER_Y - i * SCALE);
            yTick.setAttribute('stroke', '#64748b');
            yTick.setAttribute('stroke-width', '1');
            svg.appendChild(yTick);
        }

        // Draw each function
        options.forEach((formula, i) => {
            const color = COLORS[i % COLORS.length];
            const label = LABELS[i];
            const pathData = parseAndDrawFunction(formula);

            if (pathData.path) {
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', pathData.path);
                path.setAttribute('stroke', color);
                path.setAttribute('stroke-width', '2.5');
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke-linecap', 'round');
                svg.appendChild(path);

                // Label
                const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                text.setAttribute('x', pathData.labelX);
                text.setAttribute('y', pathData.labelY);
                text.setAttribute('fill', color);
                text.setAttribute('font-size', '13');
                text.setAttribute('font-weight', 'bold');
                text.textContent = label;
                svg.appendChild(text);
            }
        });

        container.appendChild(svg);
    }

    function parseAndDrawFunction(formula) {
        // Clean formula
        let f = formula.replace(/\s+/g, '').replace('y=', '');
        f = f.replace(/−/g, '-');

        let path = '';
        let labelX = PADDING + 5;
        let labelY = PADDING + 15;

        // Detect function type
        if (f.includes('x²') || f.includes('x^2') || f.match(/\d*x\^?2/)) {
            // Quadratic
            const result = drawQuadratic(f);
            path = result.path;
            labelX = result.labelX;
            labelY = result.labelY;
        } else if (f.includes('/x') || f.match(/\\frac\{[^}]+\}\{x\}/)) {
            // Hyperbola
            const result = drawHyperbola(f);
            path = result.path;
            labelX = result.labelX;
            labelY = result.labelY;
        } else {
            // Linear
            const result = drawLinear(f);
            path = result.path;
            labelX = result.labelX;
            labelY = result.labelY;
        }

        return { path, labelX, labelY };
    }

    function drawLinear(f) {
        let k = 0, b = 0;

        // Handle fractions like \frac{2}{5}
        f = f.replace(/\\frac\{(-?\d+)\}\{(\d+)\}/g, (m, n, d) => (parseFloat(n) / parseFloat(d)));

        // Parse different formats
        // y = kx + b, y = kx, y = b, y = -x, etc.

        // Check for constant: y = 3
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
        for (let x = -7; x <= 7; x += 0.5) {
            const y = k * x + b;
            const px = CENTER_X + x * SCALE;
            const py = CENTER_Y - y * SCALE;

            if (px >= PADDING - 5 && px <= WIDTH - PADDING + 5 && py >= PADDING - 5 && py <= HEIGHT - PADDING + 5) {
                points.push(`${px.toFixed(1)},${py.toFixed(1)}`);
            }
        }

        // Find good label position
        let labelX = WIDTH - PADDING - 25;
        let labelY = CENTER_Y - (k * 4 + b) * SCALE - 5;
        if (labelY < PADDING + 15) labelY = PADDING + 15;
        if (labelY > HEIGHT - PADDING - 5) labelY = HEIGHT - PADDING - 15;

        return {
            path: points.length > 1 ? `M ${points.join(' L ')}` : '',
            labelX,
            labelY
        };
    }

    function drawQuadratic(f) {
        let a = 1, b = 0, c = 0;

        // Handle fractions
        f = f.replace(/\\frac\{(-?\d+)\}\{(\d+)\}/g, (m, n, d) => (parseFloat(n) / parseFloat(d)));
        f = f.replace(/x²/g, 'x^2');

        // Parse ax^2 + bx + c formats
        // Simple cases first

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
        for (let x = -7; x <= 7; x += 0.15) {
            const y = a * x * x + b * x + c;
            const px = CENTER_X + x * SCALE;
            const py = CENTER_Y - y * SCALE;

            if (px >= PADDING - 10 && px <= WIDTH - PADDING + 10 && py >= PADDING - 10 && py <= HEIGHT - PADDING + 10) {
                points.push(`${px.toFixed(1)},${py.toFixed(1)}`);
            }
        }

        // Label at vertex
        const vertexX = -b / (2 * a);
        const vertexY = a * vertexX * vertexX + b * vertexX + c;
        let labelX = CENTER_X + vertexX * SCALE + 10;
        let labelY = CENTER_Y - vertexY * SCALE - 10;
        if (a < 0) labelY = CENTER_Y - vertexY * SCALE + 20;
        if (labelX > WIDTH - PADDING - 20) labelX = WIDTH - PADDING - 25;
        if (labelX < PADDING) labelX = PADDING + 5;
        if (labelY < PADDING + 10) labelY = PADDING + 15;
        if (labelY > HEIGHT - PADDING) labelY = HEIGHT - PADDING - 5;

        return {
            path: points.length > 1 ? `M ${points.join(' L ')}` : '',
            labelX,
            labelY
        };
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
        for (let x = 0.25; x <= 7; x += 0.1) {
            const y = k / x;
            const px = CENTER_X + x * SCALE;
            const py = CENTER_Y - y * SCALE;

            if (px >= PADDING && px <= WIDTH - PADDING && py >= PADDING && py <= HEIGHT - PADDING) {
                points1.push(`${px.toFixed(1)},${py.toFixed(1)}`);
            }
        }

        // Negative x branch
        for (let x = -7; x <= -0.25; x += 0.1) {
            const y = k / x;
            const px = CENTER_X + x * SCALE;
            const py = CENTER_Y - y * SCALE;

            if (px >= PADDING && px <= WIDTH - PADDING && py >= PADDING && py <= HEIGHT - PADDING) {
                points2.push(`${px.toFixed(1)},${py.toFixed(1)}`);
            }
        }

        let path = '';
        if (points1.length > 1) path += `M ${points1.join(' L ')}`;
        if (points2.length > 1) path += ` M ${points2.join(' L ')}`;

        // Label position based on k sign
        let labelX = k > 0 ? WIDTH - PADDING - 30 : PADDING + 5;
        let labelY = k > 0 ? PADDING + 25 : HEIGHT - PADDING - 10;

        return { path, labelX, labelY };
    }
</script>

</body>
</html>
