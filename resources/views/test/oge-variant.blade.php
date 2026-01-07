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
            onload="renderMathInElement(document.body, {delimiters: [{left: '$$', right: '$$', display: true}, {left: '$', right: '$', display: false}]});"></script>

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
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-5xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="no-print flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.oge.generator') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← К генератору</a>
        <div class="flex gap-3">
            @php $newHash = substr(md5(uniqid(mt_rand(), true)), 0, 10); @endphp
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

    {{-- Tasks --}}
    @foreach($tasks as $index => $taskData)
        @php
            $taskNumber = 6 + $index;
            $task = $taskData['task'] ?? [];
            $topicId = $taskData['topic_id'] ?? '';
            $topicTitle = $taskData['topic_title'] ?? '';
            $type = $taskData['type'] ?? 'expression';
            $svgType = $taskData['svg_type'] ?? null;
            $instruction = $taskData['instruction'] ?? 'Найдите значение выражения.';

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
            $accent = $accentColors[$topicId] ?? 'blue';
        @endphp

        <div class="task-card mb-8 bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
            {{-- Task Header --}}
            <div class="bg-slate-800 p-4 border-b border-slate-700 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-{{ $accent }}-500 to-{{ $accent }}-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">
                    {{ $taskNumber }}
                </div>
                <div class="flex-1">
                    <div class="text-white font-medium">{{ $instruction }}</div>
                    <div class="text-slate-500 text-sm mt-1">{{ $topicTitle }}</div>
                </div>
            </div>

            {{-- Task Content --}}
            <div class="p-5">

                {{-- ===== TOPIC 06: Expressions (LaTeX) ===== --}}
                @if($topicId === '06')
                    @if(!empty($task['expression']))
                        <div class="bg-slate-900/50 rounded-xl p-5 mb-4 text-center">
                            <span class="text-slate-200 text-xl">${{ $task['expression'] }}$</span>
                        </div>
                    @endif

                {{-- ===== TOPIC 07: Number Line with SVG ===== --}}
                @elseif($topicId === '07')
                    {{-- Single point SVG --}}
                    @if(isset($task['point_value']))
                        @php
                            $pointVal = $task['point_value'];
                            $pointLabel = $task['point_label'] ?? 'a';
                            $maxTick = ceil($pointVal) + 2;
                            $tickWidth = 280 / $maxTick;
                            $pointX = 15 + ($pointVal / $maxTick) * 280;
                        @endphp
                        <div class="bg-slate-900/50 rounded-xl p-4 mb-4">
                            <svg viewBox="0 0 320 55" class="w-full h-16 number-line">
                                <defs>
                                    <marker id="arrowR-{{ $taskNumber }}" markerWidth="8" markerHeight="8" refX="0" refY="3" orient="auto">
                                        <path d="M0,0 L0,6 L8,3 z" fill="#8B0000"/>
                                    </marker>
                                </defs>
                                <line x1="15" y1="25" x2="305" y2="25" stroke="#8B0000" stroke-width="2" marker-end="url(#arrowR-{{ $taskNumber }})"/>
                                @for($i = 0; $i <= $maxTick; $i++)
                                    <line x1="{{ 15 + $i * $tickWidth }}" y1="18" x2="{{ 15 + $i * $tickWidth }}" y2="32" stroke="#8B0000" stroke-width="1.5"/>
                                @endfor
                                <text x="15" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">0</text>
                                <text x="{{ 15 + $tickWidth }}" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">1</text>
                                <circle cx="{{ $pointX }}" cy="25" r="6" fill="#22c55e"/>
                                <text x="{{ $pointX }}" y="12" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold">{{ $pointLabel }}</text>
                            </svg>
                        </div>
                    {{-- Two points SVG --}}
                    @elseif(isset($task['points']) && is_array($task['points']))
                        @php
                            $pts = $task['points'];
                            $values = array_column($pts, 'value');
                            $minVal = min(min($values), 0);
                            $maxVal = max($values);
                            $minTick = floor($minVal) - 1;
                            $maxTick = ceil($maxVal) + 1;
                            $range = $maxTick - $minTick;
                            $tickWidth = 280 / $range;
                        @endphp
                        <div class="bg-slate-900/50 rounded-xl p-4 mb-4">
                            <svg viewBox="0 0 320 55" class="w-full h-16 number-line">
                                <defs>
                                    <marker id="arrowR2-{{ $taskNumber }}" markerWidth="8" markerHeight="8" refX="0" refY="3" orient="auto">
                                        <path d="M0,0 L0,6 L8,3 z" fill="#8B0000"/>
                                    </marker>
                                </defs>
                                <line x1="15" y1="25" x2="305" y2="25" stroke="#8B0000" stroke-width="2" marker-end="url(#arrowR2-{{ $taskNumber }})"/>
                                @for($i = $minTick; $i <= $maxTick; $i++)
                                    <line x1="{{ 15 + ($i - $minTick) * $tickWidth }}" y1="18" x2="{{ 15 + ($i - $minTick) * $tickWidth }}" y2="32" stroke="#8B0000" stroke-width="1.5"/>
                                @endfor
                                <text x="{{ 15 + (0 - $minTick) * $tickWidth }}" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">0</text>
                                @foreach($pts as $pt)
                                    @php $px = 15 + ($pt['value'] - $minTick) * $tickWidth; @endphp
                                    <circle cx="{{ $px }}" cy="25" r="6" fill="#22c55e"/>
                                    <text x="{{ $px }}" y="12" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold">{{ $pt['label'] }}</text>
                                @endforeach
                            </svg>
                        </div>
                    {{-- Four points A,B,C,D --}}
                    @elseif(isset($task['four_points']))
                        @php
                            $fourPts = $task['four_points'];
                            $rangeArr = $task['range'] ?? [4, 9];
                            $minV = $rangeArr[0];
                            $maxV = $rangeArr[1];
                            $labels = ['A', 'B', 'C', 'D'];
                            $getX = function($v) use ($minV, $maxV) {
                                return 15 + (($v - $minV) / ($maxV - $minV)) * 280;
                            };
                        @endphp
                        <div class="bg-slate-900/50 rounded-xl p-4 mb-4">
                            <svg viewBox="0 0 320 55" class="w-full h-16 number-line">
                                <defs>
                                    <marker id="arrowR4-{{ $taskNumber }}" markerWidth="8" markerHeight="8" refX="0" refY="3" orient="auto">
                                        <path d="M0,0 L0,6 L8,3 z" fill="#8B0000"/>
                                    </marker>
                                </defs>
                                <line x1="15" y1="25" x2="305" y2="25" stroke="#8B0000" stroke-width="2" marker-end="url(#arrowR4-{{ $taskNumber }})"/>
                                @for($i = ceil($minV); $i <= floor($maxV); $i++)
                                    <line x1="{{ $getX($i) }}" y1="18" x2="{{ $getX($i) }}" y2="32" stroke="#8B0000" stroke-width="1.5"/>
                                @endfor
                                <text x="{{ $getX(ceil($minV)) }}" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">{{ ceil($minV) }}</text>
                                <text x="{{ $getX(floor($maxV)) }}" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">{{ floor($maxV) }}</text>
                                @foreach($fourPts as $idx => $ptVal)
                                    <circle cx="{{ $getX($ptVal) }}" cy="25" r="6" fill="#22c55e"/>
                                    <text x="{{ $getX($ptVal) }}" y="12" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold">{{ $labels[$idx] }}</text>
                                @endforeach
                            </svg>
                        </div>
                    @endif

                    {{-- Expression if present --}}
                    @if(!empty($task['expression']))
                        <div class="text-slate-200 mb-3 text-lg text-center">${{ $task['expression'] }}$</div>
                    @endif

                    {{-- Options --}}
                    @if(!empty($task['options']))
                        <div class="flex flex-wrap gap-3 justify-center">
                            @foreach($task['options'] as $i => $option)
                                <span class="bg-slate-700/70 text-slate-300 px-4 py-2 rounded-lg">
                                    {{ $i + 1 }})
                                    @if(str_contains($option, '\frac') || str_contains($option, '\sqrt'))
                                        ${{ $option }}$
                                    @else
                                        {{ $option }}
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @endif

                {{-- ===== TOPIC 08-10, 12-13: Expressions and Word Problems ===== --}}
                @elseif(in_array($topicId, ['08', '09', '10', '12', '13']))
                    @if(!empty($task['expression']))
                        <div class="bg-slate-900/50 rounded-xl p-5 mb-4 text-center">
                            <span class="text-slate-200 text-xl">${{ $task['expression'] }}$</span>
                        </div>
                    @endif
                    @if(!empty($task['text']))
                        <div class="text-slate-300 mb-4 leading-relaxed">{{ $task['text'] }}</div>
                    @endif
                    @if(!empty($task['options']))
                        <div class="flex flex-wrap gap-3 justify-center">
                            @foreach($task['options'] as $i => $option)
                                <span class="bg-slate-700/70 text-slate-300 px-4 py-2 rounded-lg">
                                    {{ $i + 1 }})
                                    @if(str_contains($option, '\frac') || str_contains($option, '\sqrt') || str_contains($option, '^'))
                                        ${{ $option }}$
                                    @else
                                        {{ $option }}
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @endif

                {{-- ===== TOPIC 11: Graphs with SVG rendering ===== --}}
                @elseif($topicId === '11')
                    @php
                        $options = $task['options'] ?? [];
                        $graphLabels = ['А', 'Б', 'В', 'Г'];
                        $uniqueId = $taskNumber;
                    @endphp

                    {{-- Three separate graphs in a row --}}
                    @if(count($options) >= 3)
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            @foreach(array_slice($options, 0, 3) as $optIndex => $formula)
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
                            @foreach(array_slice($options, 0, 3) as $i => $opt)
                                <div class="flex flex-col items-center">
                                    <span class="text-slate-400 text-sm font-bold mb-1">{{ $graphLabels[$i] }}</span>
                                    <div class="w-10 h-8 border-2 border-slate-600 rounded bg-slate-800"></div>
                                </div>
                            @endforeach
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const options = @json($options);
                                options.slice(0, 3).forEach((formula, i) => {
                                    if (typeof renderSingleGraph === 'function') {
                                        renderSingleGraph('graph-{{ $uniqueId }}-' + i, formula);
                                    }
                                });
                            });
                        </script>
                    @endif

                {{-- ===== TOPIC 14: Word Problems (Progressions) ===== --}}
                @elseif($topicId === '14')
                    @if(!empty($task['text']))
                        <div class="text-slate-300 leading-relaxed text-lg">{{ $task['text'] }}</div>
                    @endif

                {{-- ===== TOPIC 15-17: Geometry with Images ===== --}}
                @elseif(in_array($topicId, ['15', '16', '17']))
                    @if(!empty($task['text']))
                        <div class="text-slate-300 mb-4 leading-relaxed">{{ $task['text'] }}</div>
                    @endif
                    @if(!empty($task['image']))
                        <div class="bg-slate-900/50 rounded-xl p-4 mb-4 flex justify-center">
                            <img src="/images/tasks/{{ $topicId }}/{{ $task['image'] }}" alt="Геометрия {{ $taskNumber }}" class="max-h-48 rounded">
                        </div>
                    @endif
                    @if(!empty($task['options']))
                        <div class="flex flex-wrap gap-3 justify-center">
                            @foreach($task['options'] as $i => $option)
                                <span class="bg-slate-700/70 text-slate-300 px-4 py-2 rounded-lg">
                                    {{ $i + 1 }}) {{ $option }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                {{-- ===== TOPIC 18: Grid Geometry ===== --}}
                @elseif($topicId === '18')
                    @if(!empty($task['text']))
                        <div class="text-slate-300 mb-4 leading-relaxed">{{ $task['text'] }}</div>
                    @endif
                    @if(!empty($task['image']))
                        <div class="bg-slate-900/50 rounded-xl p-4 mb-4 flex justify-center">
                            <img src="/images/tasks/18/{{ $task['image'] }}" alt="Задание {{ $taskNumber }}" class="max-h-48 rounded">
                        </div>
                    @endif
                    @if(!empty($task['svg']))
                        <div class="bg-slate-900/50 rounded-xl p-4 mb-4 flex justify-center">
                            {!! $task['svg'] !!}
                        </div>
                    @endif

                {{-- ===== TOPIC 19: Statements Analysis ===== --}}
                @elseif($topicId === '19')
                    @if(!empty($task['statements']))
                        <div class="space-y-3">
                            @foreach($task['statements'] as $stIndex => $statement)
                                <div class="bg-slate-700/50 rounded-lg px-4 py-3 border border-slate-600">
                                    <span class="text-red-400 font-semibold">{{ $stIndex + 1 }})</span>
                                    <span class="text-slate-300 ml-2">{{ is_array($statement) ? ($statement['text'] ?? '') : $statement }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                {{-- ===== DEFAULT: Generic task rendering ===== --}}
                @else
                    @if(!empty($task['expression']))
                        <div class="bg-slate-900/50 rounded-xl p-5 mb-4 text-center">
                            <span class="text-slate-200 text-xl">${{ $task['expression'] }}$</span>
                        </div>
                    @endif
                    @if(!empty($task['text']))
                        <div class="text-slate-300 mb-4 leading-relaxed">{{ $task['text'] }}</div>
                    @endif
                    @if(!empty($task['image']))
                        <div class="bg-slate-900/50 rounded-xl p-4 mb-4 flex justify-center">
                            <img src="/images/tasks/{{ $topicId }}/{{ $task['image'] }}" alt="Задание {{ $taskNumber }}" class="max-h-48 rounded">
                        </div>
                    @endif
                    @if(!empty($task['options']))
                        <div class="flex flex-wrap gap-3 justify-center">
                            @foreach($task['options'] as $i => $option)
                                <span class="bg-slate-700/70 text-slate-300 px-4 py-2 rounded-lg">
                                    {{ $i + 1 }}) {{ $option }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                @endif

                {{-- Answer field --}}
                <div class="mt-6 flex items-center gap-4">
                    <span class="text-slate-400 font-medium">Ответ:</span>
                    <input type="text"
                           id="answer-{{ $taskNumber }}"
                           class="w-40 px-4 py-2 bg-slate-900 border-2 border-slate-600 rounded-lg text-white text-lg focus:border-{{ $accent }}-400 focus:outline-none transition-colors"
                           placeholder="">
                </div>
            </div>
        </div>
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
                @php $footerHash = substr(md5(uniqid(mt_rand(), true)), 0, 10); @endphp
                <a href="{{ route('test.oge.show', ['hash' => $footerHash]) }}" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-white rounded-lg transition-colors">
                    🎲 Новый вариант
                </a>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript for graph rendering (Topic 11) --}}
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
            const vLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            vLine.setAttribute('x1', CENTER_X + i * SCALE);
            vLine.setAttribute('y1', PADDING - 5);
            vLine.setAttribute('x2', CENTER_X + i * SCALE);
            vLine.setAttribute('y2', HEIGHT - PADDING + 5);
            vLine.setAttribute('stroke', '#334155');
            vLine.setAttribute('stroke-width', '0.5');
            gridGroup.appendChild(vLine);

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

        // Origin label
        const origin = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        origin.setAttribute('x', CENTER_X - 10);
        origin.setAttribute('y', CENTER_Y + 12);
        origin.setAttribute('fill', '#94a3b8');
        origin.setAttribute('font-size', '10');
        origin.textContent = '0';
        svg.appendChild(origin);

        // Mark "1" on axes
        const oneX = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        oneX.setAttribute('x', CENTER_X + SCALE - 2);
        oneX.setAttribute('y', CENTER_Y + 12);
        oneX.setAttribute('fill', '#94a3b8');
        oneX.setAttribute('font-size', '10');
        oneX.textContent = '1';
        svg.appendChild(oneX);

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
            const xTick = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            xTick.setAttribute('x1', CENTER_X + i * SCALE);
            xTick.setAttribute('y1', CENTER_Y - 2);
            xTick.setAttribute('x2', CENTER_X + i * SCALE);
            xTick.setAttribute('y2', CENTER_Y + 2);
            xTick.setAttribute('stroke', '#64748b');
            xTick.setAttribute('stroke-width', '1');
            svg.appendChild(xTick);

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
        let f = formula.replace(/\s+/g, '').replace('y=', '');
        f = f.replace(/−/g, '-');

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

        f = f.replace(/\\frac\{(-?\d+)\}\{(\d+)\}/g, (m, n, d) => (parseFloat(n) / parseFloat(d)));

        if (f.match(/^-?\d+\.?\d*$/) && !f.includes('x')) {
            k = 0;
            b = parseFloat(f);
        } else if (f.match(/^(-?\d*\.?\d*)x([+-]\d+\.?\d*)?$/)) {
            const match = f.match(/^(-?\d*\.?\d*)x([+-]\d+\.?\d*)?$/);
            let kStr = match[1];
            if (kStr === '' || kStr === '+') k = 1;
            else if (kStr === '-') k = -1;
            else k = parseFloat(kStr);
            b = match[2] ? parseFloat(match[2]) : 0;
        } else if (f.match(/^(-?\d+\.?\d*)([+-]\d*\.?\d*)x$/)) {
            const match = f.match(/^(-?\d+\.?\d*)([+-]\d*\.?\d*)x$/);
            b = parseFloat(match[1]);
            let kStr = match[2];
            if (kStr === '+' || kStr === '') k = 1;
            else if (kStr === '-') k = -1;
            else k = parseFloat(kStr);
        } else if (f === 'x') {
            k = 1; b = 0;
        } else if (f === '-x') {
            k = -1; b = 0;
        }

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

        f = f.replace(/\\frac\{(-?\d+)\}\{(\d+)\}/g, (m, n, d) => (parseFloat(n) / parseFloat(d)));
        f = f.replace(/x²/g, 'x^2');

        const aMatch = f.match(/^(-?\d*\.?\d*)x\^?2/);
        if (aMatch) {
            let aStr = aMatch[1];
            if (aStr === '' || aStr === '+') a = 1;
            else if (aStr === '-') a = -1;
            else a = parseFloat(aStr);
        }

        const bMatch = f.match(/([+-]\d*\.?\d*)x(?!\^)/);
        if (bMatch) {
            let bStr = bMatch[1];
            if (bStr === '+' || bStr === '') b = 1;
            else if (bStr === '-') b = -1;
            else b = parseFloat(bStr);
        }

        const cMatch = f.match(/([+-]\d+\.?\d*)$/);
        if (cMatch && !cMatch[0].includes('x')) {
            c = parseFloat(cMatch[1]);
        }

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

        f = f.replace(/\\frac\{(-?\d+)\}\{x\}/g, (m, n) => n + '/x');
        f = f.replace(/\\frac\{(-?\d+)\}\{(\d+)x\}/g, (m, n, d) => (parseFloat(n) / parseFloat(d)) + '/x');

        const match = f.match(/(-?\d*\.?\d*)\/x/);
        if (match) {
            let kStr = match[1];
            if (kStr === '' || kStr === '+') k = 1;
            else if (kStr === '-') k = -1;
            else k = parseFloat(kStr);
        }

        const points1 = [];
        const points2 = [];

        for (let x = 0.2; x <= 6; x += 0.05) {
            const y = k / x;
            const px = CENTER_X + x * SCALE;
            const py = CENTER_Y - y * SCALE;

            if (px >= 0 && px <= WIDTH && py >= 0 && py <= HEIGHT) {
                points1.push(`${px.toFixed(1)},${py.toFixed(1)}`);
            }
        }

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

    function selectOption(element, taskNumber, value) {
        const taskCard = element.closest('.task-card');
        taskCard.querySelectorAll('.bg-slate-700\\/50').forEach(opt => {
            opt.classList.remove('ring-2', 'ring-emerald-400');
        });
        element.classList.add('ring-2', 'ring-emerald-400');
        const answerInput = document.getElementById('answer-' + taskNumber);
        if (answerInput) {
            answerInput.value = value;
        }
    }
</script>

</body>
</html>
