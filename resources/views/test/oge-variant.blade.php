<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ОГЭ-2025 Вариант {{ $variantNumber ?? 1 }} - PALOMATIKA</title>

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

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
        .number-line { font-family: 'Times New Roman', serif; }
        .geo-label {
            font-family: 'Times New Roman', serif;
            font-style: italic;
            font-weight: 500;
            user-select: none;
        }
        .katex { font-size: 1.1em; }

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
            .bg-slate-900, .bg-slate-800, .bg-slate-900\/50 {
                background: #f5f5f5 !important;
            }
            .text-white, .text-slate-200, .text-slate-300 {
                color: black !important;
            }
            .text-blue-400, .text-cyan-400, .text-emerald-400, .text-amber-400 {
                color: #1e40af !important;
            }
        }
    </style>

    <!-- Graph Rendering for Task 11 -->
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
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-5xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="no-print flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.oge.generator') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← К генератору</a>
        <div class="flex gap-3">
            @php $newHash = substr(base_convert(mt_rand(), 10, 36), 0, 6); @endphp
            <a href="{{ route('test.oge.show', ['hash' => $newHash]) }}" class="px-3 py-1.5 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition">🎲 Новый вариант</a>
            <a href="{{ route('test.generator') }}" class="px-3 py-1.5 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition">Генератор</a>
            <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 transition">🖨️ Печать</button>
        </div>
    </div>

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="flex justify-between items-center text-sm text-slate-500 mb-4">
            <span>ОГЭ–2025</span>
            <span>palomatika.ru</span>
        </div>
        <h1 class="text-4xl font-bold text-white mb-2">Тренировочная работа № {{ $variantNumber ?? rand(1, 99) }}</h1>
        <p class="text-slate-400 text-lg">Задания 6–19 (Часть 1)</p>
    </div>

    {{-- Instructions --}}
    <div class="bg-slate-800/70 rounded-xl p-5 mb-8 border border-slate-700">
        <p class="text-slate-300 text-sm italic leading-relaxed">
            <strong class="text-white">Инструкция:</strong> Ответами к заданиям 6–19 являются число или последовательность цифр.
            Запишите ответ в поле ответа. Если ответом является последовательность цифр, то запишите её без пробелов, запятых и других дополнительных символов.
        </p>
    </div>

    {{-- Stats --}}
    <div class="no-print flex justify-center gap-6 mb-10">
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-emerald-400 font-bold text-xl">{{ count($tasks) }}</span>
            <span class="text-slate-400 ml-2">заданий</span>
        </div>
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-emerald-400 font-bold text-xl">{{ now()->format('d.m.Y') }}</span>
            <span class="text-slate-400 ml-2">дата</span>
        </div>
    </div>

    {{-- Tasks - используем унифицированный адаптер --}}
    @foreach($tasks as $index => $taskData)
        @php
            $taskNumber = 6 + $index;
            $topicId = $taskData['topic_id'] ?? '';

            // Определяем цвет акцента для разных тем
            $accentColors = [
                '06' => 'blue',
                '07' => 'cyan',
                '08' => 'violet',
                '09' => 'pink',
                '10' => 'orange',
                '11' => 'rose',
                '12' => 'lime',
                '13' => 'teal',
                '14' => 'indigo',
                '15' => 'emerald',
                '16' => 'amber',
                '17' => 'fuchsia',
                '18' => 'sky',
                '19' => 'red',
            ];
            $color = $accentColors[$topicId] ?? 'blue';
        @endphp

        @include('tasks.variant-task', [
            'taskData' => $taskData,
            'taskNumber' => $taskNumber,
            'color' => $color,
        ])
    @endforeach

    {{-- Footer --}}
    <div class="no-print text-center mt-10">
        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
            <p class="text-slate-400 mb-2">Вариант: <code class="bg-slate-700 px-2 py-1 rounded text-emerald-400">{{ $variantHash ?? 'unknown' }}</code></p>
            <p class="text-slate-500 text-sm mb-4">Ссылка на этот вариант сохраняется — можно поделиться</p>
            <div class="flex justify-center gap-4">
                <button onclick="window.print()" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">
                    🖨️ Распечатать
                </button>
                @php $footerHash = substr(base_convert(mt_rand(), 10, 36), 0, 6); @endphp
                <a href="{{ route('test.oge.show', ['hash' => $footerHash]) }}" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-white rounded-lg transition-colors">
                    🎲 Новый вариант
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
