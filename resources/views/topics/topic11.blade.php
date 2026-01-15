<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>11. Графики функций - Тест парсинга PDF</title>

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

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        .katex { font-size: 1.1em; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-7xl mx-auto px-4 py-8">
    {{-- Topic Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('topics.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Назад к темам
        </a>

        <div class="flex gap-1.5 flex-wrap justify-center">
            @foreach(['06','07','08','09','10','11','12','13','14','15','16','17','18','19'] as $tid)
                @if($tid === '11')
                    <span class="px-2.5 py-1 rounded-lg bg-lime-500 text-white font-bold text-xs">{{ $tid }}</span>
                @else
                    <a href="{{ route('topics.show', ['id' => ltrim($tid, '0')]) }}"
                       class="px-2.5 py-1 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition text-xs">{{ $tid }}</a>
                @endif
            @endforeach
        </div>

        <span class="text-slate-500 text-xs">SVG графики</span>
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
            @php
                $zadanieType = $zadanie['type'] ?? 'matching';
            @endphp
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
                            $statements = $task['statements'] ?? [];
                            $graphLabels = ['А', 'Б', 'В', 'Г'];
                        @endphp

                        <div class="bg-slate-800/70 rounded-xl p-6 border border-slate-700">
                            {{-- Task number --}}
                            <div class="text-cyan-400 font-bold text-lg mb-4">{{ $task['id'] }}</div>

                            @if($zadanieType === 'matching' || $zadanieType === 'matching_4')
                                {{-- MATCHING: Generate SVG graphs from formula options --}}
                                <div class="grid grid-cols-3 gap-4 mb-6">
                                    @foreach($options as $optIndex => $formula)
                                        <div class="bg-slate-900/50 rounded-lg p-3">
                                            <div class="text-center text-white font-bold mb-2">{{ $graphLabels[$optIndex] }})</div>
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

                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const options = @json($options);
                                        options.forEach((formula, i) => {
                                            renderSingleGraph('graph-{{ $uniqueId }}-' + i, formula);
                                        });
                                    });
                                </script>

                            @elseif($zadanieType === 'matching_signs')
                                {{-- MATCHING SIGNS: 3 SVG графика + выбор знаков коэффициентов --}}
                                @php
                                    $isQuadratic = !empty($options) && strpos($options[0], 'a') !== false;
                                    $funcType = $isQuadratic ? 'y = ax² + c' : 'y = kx + b';
                                    $graphLabels = ['А', 'Б', 'В'];

                                    // Парсим options чтобы получить параметры графиков
                                    $graphParams = [];
                                    foreach ($options as $opt) {
                                        if ($isQuadratic) {
                                            // Парсим "a > 0, c < 0" и т.д.
                                            $a = strpos($opt, 'a > 0') !== false ? 0.8 : -0.8;
                                            $c = strpos($opt, 'c > 0') !== false ? 1.5 : -1.5;
                                            $graphParams[] = ['a' => $a, 'c' => $c];
                                        } else {
                                            // Парсим "k > 0, b < 0" и т.д.
                                            $k = strpos($opt, 'k > 0') !== false ? 1.5 : -1.5;
                                            $b = strpos($opt, 'b > 0') !== false ? 1.5 : -1.5;
                                            $graphParams[] = ['k' => $k, 'b' => $b];
                                        }
                                    }
                                @endphp

                                {{-- 3 SVG графика в ряд --}}
                                <div class="grid grid-cols-3 gap-3 mb-4">
                                    @foreach($graphParams as $gi => $gp)
                                        <div class="bg-slate-900/50 rounded-lg p-2">
                                            <div class="text-center text-cyan-400 font-bold text-lg mb-1">{{ $graphLabels[$gi] }})</div>
                                            <svg viewBox="0 0 120 120" class="w-full">
                                                {{-- Фон --}}
                                                <rect width="120" height="120" fill="#0f172a"/>
                                                {{-- Сетка --}}
                                                @for($gi2 = 1; $gi2 < 6; $gi2++)
                                                    <line x1="{{ $gi2 * 20 }}" y1="10" x2="{{ $gi2 * 20 }}" y2="110" stroke="#334155" stroke-width="0.5"/>
                                                    <line x1="10" y1="{{ $gi2 * 20 }}" x2="110" y2="{{ $gi2 * 20 }}" stroke="#334155" stroke-width="0.5"/>
                                                @endfor
                                                {{-- Оси --}}
                                                <line x1="10" y1="60" x2="110" y2="60" stroke="#64748b" stroke-width="1.5"/>
                                                <line x1="60" y1="10" x2="60" y2="110" stroke="#64748b" stroke-width="1.5"/>
                                                {{-- Стрелки --}}
                                                <polygon points="107,57 107,63 113,60" fill="#64748b"/>
                                                <polygon points="57,13 63,13 60,7" fill="#64748b"/>
                                                {{-- Метки осей --}}
                                                <text x="113" y="64" fill="#94a3b8" font-size="10" font-style="italic">x</text>
                                                <text x="64" y="14" fill="#94a3b8" font-size="10" font-style="italic">y</text>
                                                <text x="52" y="72" fill="#94a3b8" font-size="8">0</text>

                                                @if($isQuadratic)
                                                    {{-- Парабола y = ax² + c --}}
                                                    @php
                                                        $a = $gp['a'];
                                                        $c = $gp['c'];
                                                        $pts = [];
                                                        for ($x = -3; $x <= 3; $x += 0.2) {
                                                            $y = $a * $x * $x + $c;
                                                            $px = 60 + $x * 15;
                                                            $py = 60 - $y * 15;
                                                            if ($py >= 5 && $py <= 115) {
                                                                $pts[] = round($px, 1) . ',' . round($py, 1);
                                                            }
                                                        }
                                                        $pathD = count($pts) > 1 ? 'M ' . implode(' L ', $pts) : '';
                                                    @endphp
                                                    <path d="{{ $pathD }}" stroke="#10b981" stroke-width="2" fill="none"/>
                                                @else
                                                    {{-- Линия y = kx + b --}}
                                                    @php
                                                        $k = $gp['k'];
                                                        $b = $gp['b'];
                                                        // Точки линии от x=-3 до x=3
                                                        $x1 = -3; $y1 = $k * $x1 + $b;
                                                        $x2 = 3; $y2 = $k * $x2 + $b;
                                                        // Конвертируем в пиксели (центр 60,60, масштаб 15)
                                                        $px1 = 60 + $x1 * 15;
                                                        $py1 = 60 - $y1 * 15;
                                                        $px2 = 60 + $x2 * 15;
                                                        $py2 = 60 - $y2 * 15;
                                                    @endphp
                                                    <line x1="{{ $px1 }}" y1="{{ $py1 }}" x2="{{ $px2 }}" y2="{{ $py2 }}"
                                                          stroke="#10b981" stroke-width="2" stroke-linecap="round"/>
                                                @endif
                                            </svg>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Варианты ответа --}}
                                <div class="flex flex-wrap gap-3 justify-center">
                                    @foreach($options as $i => $opt)
                                        <div class="bg-slate-700/50 hover:bg-slate-700 px-4 py-2 rounded-lg cursor-pointer transition flex items-center gap-2">
                                            <span class="text-amber-400 font-bold">{{ $i + 1 }})</span>
                                            <span class="text-slate-200">{{ $opt }}</span>
                                        </div>
                                    @endforeach
                                </div>

                            @elseif($zadanieType === 'statements')
                                {{-- STATEMENTS: Show image and statements to evaluate --}}
                                <div class="grid md:grid-cols-2 gap-6 mb-4">
                                    @if(!empty($task['image']))
                                        <div class="bg-slate-900/50 rounded-lg p-4">
                                            <img src="{{ asset('images/tasks/11/' . $task['image']) }}"
                                                 alt="График функции"
                                                 class="w-full max-w-xs mx-auto rounded">
                                        </div>
                                    @endif
                                    <div class="space-y-3">
                                        @foreach($statements as $i => $statement)
                                            <div class="flex items-start gap-3 bg-slate-700/50 p-3 rounded-lg">
                                                <span class="text-cyan-400 font-bold">{{ $i + 1 }})</span>
                                                <span class="text-slate-200">{{ $statement }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            @else
                                {{-- DEFAULT: Simple display --}}
                                <div class="text-slate-400 text-center py-4">
                                    Неизвестный тип задания: {{ $zadanieType }}
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
