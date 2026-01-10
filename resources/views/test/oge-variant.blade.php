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
        /* Размеры KaTeX формул по типам */
        .katex { font-size: 1.3em; }                                /* Десятичные дроби */
        .katex .mfrac .sizing { font-size: 1em !important; }        /* Двухэтажные дроби */
        .katex .mfrac .mfrac .sizing { font-size: 0.9em !important; } /* Трёхэтажные дроби */

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

                {{-- ===== TOPIC 11: Graphs ===== --}}
                @elseif($topicId === '11')
                    @php
                        $options = $task['options'] ?? [];
                        $graphLabels = ['А', 'Б', 'В', 'Г'];
                        $uniqueId = $taskNumber;
                        // Check if options contain formulas (y = ...) or coefficient signs (a < 0, c > 0)
                        $isFormulaMatching = !empty($options) && str_contains($options[0] ?? '', 'y =');
                    @endphp

                    @if($isFormulaMatching && count($options) >= 3)
                        {{-- Type: matching formulas - render graphs from formulas --}}
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
                    @elseif(!empty($task['image']))
                        {{-- Type: matching_signs - load image from file --}}
                        <div class="bg-slate-900/50 rounded-xl p-4 mb-4 flex justify-center">
                            <img src="/images/tasks/11/{{ $task['image'] }}" alt="График" class="max-h-64 rounded">
                        </div>

                        {{-- Options to match --}}
                        <div class="flex flex-wrap gap-4 mb-4 justify-center">
                            @foreach($options as $i => $opt)
                                <span class="bg-slate-700 text-slate-200 px-4 py-2 rounded-lg text-sm">
                                    {{ $i + 1 }}) {{ $opt }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                {{-- ===== TOPIC 14: Word Problems (Progressions) ===== --}}
                @elseif($topicId === '14')
                    @if(!empty($task['text']))
                        <div class="text-slate-300 leading-relaxed text-lg">{{ $task['text'] }}</div>
                    @endif

                {{-- ===== TOPIC 15: Triangles with Interactive SVG ===== --}}
                @elseif($topicId === '15')
                    @php
                        $taskText = $task['text'] ?? $instruction ?? '';
                        $geoId = 'geo-' . $taskNumber;

                        // Определяем тип геометрии по тексту задания
                        $geoType = 'triangle'; // default
                        if (str_contains($taskText, 'биссектрис')) $geoType = 'bisector';
                        elseif (str_contains($taskText, 'медиан')) $geoType = 'median';
                        elseif (str_contains($taskText, 'два угла') && str_contains($taskText, 'третий')) $geoType = 'anglesSum';
                        elseif (str_contains($taskText, 'внешний угол')) $geoType = 'externalAngle';
                        elseif (str_contains($taskText, 'равнобедренн') && str_contains($taskText, 'внешний')) $geoType = 'isoscelesExternal';
                        elseif (str_contains($taskText, 'равнобедренн')) $geoType = 'isosceles';
                        elseif (str_contains($taskText, 'равносторонн') && (str_contains($taskText, 'высот') || str_contains($taskText, 'медиан') || str_contains($taskText, 'биссектрис'))) $geoType = 'equilateral';
                        elseif (str_contains($taskText, 'прямоугольн') && str_contains($taskText, 'острый')) $geoType = 'rightAcute';
                        elseif (str_contains($taskText, 'высот') && str_contains($taskText, 'BH')) $geoType = 'height';
                        elseif (str_contains($taskText, 'катет') && str_contains($taskText, 'площадь')) $geoType = 'areaCatheti';
                        elseif (str_contains($taskText, 'сторон') && str_contains($taskText, 'площадь')) $geoType = 'areaHeight';
                        elseif (str_contains($taskText, 'середин') && str_contains($taskText, 'MN')) $geoType = 'midline';
                        elseif (str_contains($taskText, 'Катеты') && str_contains($taskText, 'гипотенуз')) $geoType = 'pythagoras';
                        elseif (str_contains($taskText, 'катет') && str_contains($taskText, 'гипотенуз')) $geoType = 'pythagorasCathetus';
                        elseif (str_contains($taskText, 'радиус описанной')) $geoType = 'circumcircle';
                        elseif (str_contains($taskText, 'sin') || str_contains($taskText, 'cos') || str_contains($taskText, 'tg')) $geoType = 'trig';
                    @endphp

                    @if(!empty($task['text']))
                        <div class="text-slate-300 mb-4 leading-relaxed">{{ $task['text'] }}</div>
                    @endif

                    {{-- Interactive SVG with Alpine.js --}}
                    <div x-data="geometryDemo('{{ $geoType }}', '{{ $geoId }}')" class="mb-4">
                        <div class="bg-slate-900/50 rounded-xl p-4 flex justify-center">
                            <svg viewBox="0 0 300 220" class="w-full max-w-xs h-52">
                                {{-- Triangle --}}
                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                    fill="none"
                                    :stroke="highlight === 'sides' ? '#f59e0b' : '#dc2626'"
                                    :stroke-width="highlight === 'sides' ? 5 : 3.5"
                                    stroke-linejoin="round" class="transition-all duration-200"/>

                                {{-- Special elements based on type --}}
                                <template x-if="hasMedian">
                                    <g>
                                        <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                            :stroke="highlight === 'special' ? '#10b981' : '#dc2626'"
                                            :stroke-width="highlight === 'special' ? 3 : 2"
                                            :stroke-dasharray="highlight === 'special' ? '8,5' : 'none'"/>
                                        <circle :cx="M.x" :cy="M.y" r="4" :fill="highlight === 'special' ? '#10b981' : '#dc2626'"/>
                                        <text :x="M.x" :y="M.y + 18" :fill="highlight === 'special' ? '#10b981' : '#60a5fa'" font-size="16" class="geo-label" text-anchor="middle">M</text>
                                    </g>
                                </template>

                                <template x-if="hasBisector">
                                    <g>
                                        <line :x1="B.x" :y1="B.y" :x2="D.x" :y2="D.y"
                                            :stroke="highlight === 'special' ? '#10b981' : '#dc2626'"
                                            :stroke-width="highlight === 'special' ? 3 : 2"
                                            :stroke-dasharray="highlight === 'special' ? '8,5' : 'none'"/>
                                        <circle :cx="D.x" :cy="D.y" r="4" :fill="highlight === 'special' ? '#10b981' : '#dc2626'"/>
                                        <text :x="D.x" :y="D.y + 18" :fill="highlight === 'special' ? '#10b981' : '#60a5fa'" font-size="16" class="geo-label" text-anchor="middle">D</text>
                                    </g>
                                </template>

                                <template x-if="hasHeight">
                                    <g>
                                        <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y"
                                            :stroke="highlight === 'special' ? '#3b82f6' : '#dc2626'"
                                            :stroke-width="highlight === 'special' ? 3 : 2"
                                            :stroke-dasharray="highlight === 'special' ? '8,5' : 'none'"/>
                                        <circle :cx="H.x" :cy="H.y" r="4" :fill="highlight === 'special' ? '#3b82f6' : '#dc2626'"/>
                                        <text :x="H.x + 12" :y="H.y - 5" :fill="highlight === 'special' ? '#3b82f6' : '#60a5fa'" font-size="16" class="geo-label">H</text>
                                    </g>
                                </template>

                                <template x-if="hasRightAngle">
                                    <path :d="rightAnglePath()" fill="none"
                                        :stroke="highlight === 'angle' ? '#f59e0b' : '#666'"
                                        :stroke-width="highlight === 'angle' ? 3 : 2"/>
                                </template>

                                <template x-if="hasCircumcircle">
                                    <circle :cx="circleCenter.x" :cy="circleCenter.y" :r="circleRadius"
                                        fill="none"
                                        :stroke="highlight === 'special' ? '#10b981' : '#666'"
                                        :stroke-width="highlight === 'special' ? 2 : 1"
                                        stroke-dasharray="5,3"/>
                                </template>

                                {{-- Angle arcs when highlighted --}}
                                <template x-if="highlight === 'angles'">
                                    <g>
                                        <path :d="makeAngleArc(A, C, B, 25)" fill="none" stroke="#f59e0b" stroke-width="2.5"/>
                                        <path :d="makeAngleArc(B, A, C, 22)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                                        <path :d="makeAngleArc(C, B, A, 25)" fill="none" stroke="#3b82f6" stroke-width="2.5"/>
                                    </g>
                                </template>

                                {{-- Equal sides markers --}}
                                <template x-if="highlight === 'equalSides' && isIsosceles">
                                    <g>
                                        <line :x1="markAB.x - 5" :y1="markAB.y - 5" :x2="markAB.x + 5" :y2="markAB.y + 5" stroke="#3b82f6" stroke-width="3"/>
                                        <line :x1="markBC.x - 5" :y1="markBC.y + 5" :x2="markBC.x + 5" :y2="markBC.y - 5" stroke="#3b82f6" stroke-width="3"/>
                                    </g>
                                </template>

                                {{-- Vertices --}}
                                <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                                <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                                <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                                {{-- Labels --}}
                                <text :x="labelA.x" :y="labelA.y" fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                                <text :x="labelB.x" :y="labelB.y" fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                                <text :x="labelC.x" :y="labelC.y" fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            </svg>
                        </div>

                        {{-- Hint buttons --}}
                        <div class="flex flex-wrap gap-2 mt-3 justify-center">
                            <template x-for="h in hints" :key="h.id">
                                <button @mouseenter="highlight = h.id" @mouseleave="highlight = null"
                                    :class="highlight === h.id ? 'bg-amber-500 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
                                    class="px-3 py-1.5 rounded-full text-sm font-medium transition-all" x-text="h.label"></button>
                            </template>
                        </div>

                        {{-- Hint description --}}
                        <div x-show="highlight && currentHintDesc" x-cloak class="mt-3 bg-amber-500/20 border-l-4 border-amber-500 p-3 rounded">
                            <p class="text-slate-200 text-sm" x-text="currentHintDesc"></p>
                        </div>
                    </div>

                {{-- ===== TOPIC 16: Circles with Interactive SVG ===== --}}
                @elseif($topicId === '16')
                    @php
                        $taskText = $task['text'] ?? $instruction ?? '';
                        $circleId = 'circle-' . $taskNumber;

                        // Определяем тип задачи по тексту
                        $circleType = 'basic'; // default
                        if (str_contains($taskText, 'касательн')) $circleType = 'tangent';
                        elseif (str_contains($taskText, 'центральн') && str_contains($taskText, 'угол')) $circleType = 'centralAngle';
                        elseif (str_contains($taskText, 'вписанн') && str_contains($taskText, 'угол')) $circleType = 'inscribedAngle';
                        elseif (str_contains($taskText, 'хорд')) $circleType = 'chord';
                        elseif (str_contains($taskText, 'секущ')) $circleType = 'secant';
                        elseif (str_contains($taskText, 'дуг')) $circleType = 'arc';
                        elseif (str_contains($taskText, 'площадь') && str_contains($taskText, 'сектор')) $circleType = 'sector';
                    @endphp

                    @if(!empty($task['text']))
                        <div class="text-slate-300 mb-4 leading-relaxed">{{ $task['text'] }}</div>
                    @endif

                    {{-- Interactive Circle SVG --}}
                    <div x-data="circleDemo('{{ $circleType }}', '{{ $circleId }}')" class="mb-4">
                        <div class="bg-slate-900/50 rounded-xl p-4 flex justify-center">
                            <svg viewBox="0 0 220 220" class="w-52 h-52">
                                {{-- Main circle --}}
                                <circle cx="110" cy="110" r="80" fill="none"
                                    :stroke="highlight === 'circle' ? '#f59e0b' : '#dc2626'"
                                    :stroke-width="highlight === 'circle' ? 4 : 3"/>

                                {{-- Center point --}}
                                <circle cx="110" cy="110" r="4" fill="#dc2626"/>
                                <text x="118" y="106" fill="#60a5fa" font-size="16" class="geo-label">O</text>

                                {{-- Radius --}}
                                <template x-if="showRadius">
                                    <g>
                                        <line x1="110" y1="110" x2="190" y2="110"
                                            :stroke="highlight === 'radius' ? '#10b981' : '#3b82f6'"
                                            :stroke-width="highlight === 'radius' ? 3 : 2"
                                            stroke-dasharray="5,3"/>
                                        <text x="150" y="102" :fill="highlight === 'radius' ? '#10b981' : '#3b82f6'" font-size="14" class="geo-label">r</text>
                                    </g>
                                </template>

                                {{-- Tangent line --}}
                                <template x-if="showTangent">
                                    <g>
                                        <line x1="190" y1="50" x2="190" y2="170"
                                            :stroke="highlight === 'tangent' ? '#f59e0b' : '#10b981'"
                                            :stroke-width="highlight === 'tangent' ? 3 : 2"/>
                                        <circle cx="190" cy="110" r="4" fill="#10b981"/>
                                        <text x="198" y="114" fill="#10b981" font-size="14" class="geo-label">A</text>
                                        {{-- Right angle marker --}}
                                        <path d="M 180 110 L 180 100 L 190 100" fill="none" stroke="#666" stroke-width="1.5"/>
                                    </g>
                                </template>

                                {{-- Chord --}}
                                <template x-if="showChord">
                                    <g>
                                        <line x1="50" y1="60" x2="170" y2="160"
                                            :stroke="highlight === 'chord' ? '#f59e0b' : '#3b82f6'"
                                            :stroke-width="highlight === 'chord' ? 3 : 2"/>
                                        <circle cx="50" cy="60" r="4" fill="#3b82f6"/>
                                        <circle cx="170" cy="160" r="4" fill="#3b82f6"/>
                                        <text x="38" y="55" fill="#60a5fa" font-size="14" class="geo-label">A</text>
                                        <text x="178" y="165" fill="#60a5fa" font-size="14" class="geo-label">B</text>
                                    </g>
                                </template>

                                {{-- Central angle --}}
                                <template x-if="showCentralAngle">
                                    <g>
                                        <line x1="110" y1="110" x2="50" y2="60" stroke="#dc2626" stroke-width="2"/>
                                        <line x1="110" y1="110" x2="170" y2="60" stroke="#dc2626" stroke-width="2"/>
                                        <circle cx="50" cy="60" r="4" fill="#dc2626"/>
                                        <circle cx="170" cy="60" r="4" fill="#dc2626"/>
                                        <text x="38" y="55" fill="#60a5fa" font-size="14" class="geo-label">A</text>
                                        <text x="178" y="55" fill="#60a5fa" font-size="14" class="geo-label">B</text>
                                        <path :d="centralAngleArc" fill="none"
                                            :stroke="highlight === 'angle' ? '#f59e0b' : '#10b981'" stroke-width="2"/>
                                    </g>
                                </template>

                                {{-- Inscribed angle --}}
                                <template x-if="showInscribedAngle">
                                    <g>
                                        <line x1="50" y1="60" x2="110" y2="190" stroke="#dc2626" stroke-width="2"/>
                                        <line x1="170" y1="60" x2="110" y2="190" stroke="#dc2626" stroke-width="2"/>
                                        <circle cx="50" cy="60" r="4" fill="#dc2626"/>
                                        <circle cx="170" cy="60" r="4" fill="#dc2626"/>
                                        <circle cx="110" cy="190" r="4" fill="#10b981"/>
                                        <text x="38" y="55" fill="#60a5fa" font-size="14" class="geo-label">A</text>
                                        <text x="178" y="55" fill="#60a5fa" font-size="14" class="geo-label">B</text>
                                        <text x="118" y="200" fill="#10b981" font-size="14" class="geo-label">C</text>
                                        <path :d="inscribedAngleArc" fill="none"
                                            :stroke="highlight === 'angle' ? '#f59e0b' : '#3b82f6'" stroke-width="2"/>
                                    </g>
                                </template>
                            </svg>
                        </div>

                        {{-- Hint buttons --}}
                        <div class="flex flex-wrap gap-2 mt-3 justify-center">
                            <template x-for="h in hints" :key="h.id">
                                <button @mouseenter="highlight = h.id" @mouseleave="highlight = null"
                                    :class="highlight === h.id ? 'bg-amber-500 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
                                    class="px-3 py-1.5 rounded-full text-sm font-medium transition-all" x-text="h.label"></button>
                            </template>
                        </div>

                        {{-- Hint description --}}
                        <div x-show="highlight && currentHintDesc" x-cloak class="mt-3 bg-amber-500/20 border-l-4 border-amber-500 p-3 rounded">
                            <p class="text-slate-200 text-sm" x-text="currentHintDesc"></p>
                        </div>
                    </div>

                {{-- ===== TOPIC 17: Quadrilaterals with Interactive SVG ===== --}}
                @elseif($topicId === '17')
                    @php
                        $taskText = $task['text'] ?? $instruction ?? '';
                        $quadId = 'quad-' . $taskNumber;

                        // Определяем тип четырёхугольника
                        $quadType = 'trapezoid'; // default
                        if (str_contains($taskText, 'параллелограмм')) $quadType = 'parallelogram';
                        elseif (str_contains($taskText, 'прямоугольник')) $quadType = 'rectangle';
                        elseif (str_contains($taskText, 'ромб')) $quadType = 'rhombus';
                        elseif (str_contains($taskText, 'квадрат')) $quadType = 'square';
                        elseif (str_contains($taskText, 'трапеци') && str_contains($taskText, 'равнобедренн')) $quadType = 'isoscelesTrapezoid';
                        elseif (str_contains($taskText, 'трапеци') && str_contains($taskText, 'прямоугольн')) $quadType = 'rightTrapezoid';
                    @endphp

                    @if(!empty($task['text']))
                        <div class="text-slate-300 mb-4 leading-relaxed">{{ $task['text'] }}</div>
                    @endif

                    {{-- Interactive Quadrilateral SVG --}}
                    <div x-data="quadDemo('{{ $quadType }}', '{{ $quadId }}')" class="mb-4">
                        <div class="bg-slate-900/50 rounded-xl p-4 flex justify-center">
                            <svg viewBox="0 0 260 180" class="w-64 h-44">
                                {{-- Quadrilateral shape --}}
                                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                                    fill="none"
                                    :stroke="highlight === 'sides' ? '#f59e0b' : '#dc2626'"
                                    :stroke-width="highlight === 'sides' ? 4 : 3"
                                    stroke-linejoin="round"/>

                                {{-- Diagonals --}}
                                <template x-if="showDiagonals">
                                    <g>
                                        <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y"
                                            :stroke="highlight === 'diagonals' ? '#10b981' : '#3b82f6'"
                                            :stroke-width="highlight === 'diagonals' ? 3 : 2"
                                            stroke-dasharray="8,4"/>
                                        <line :x1="B.x" :y1="B.y" :x2="D.x" :y2="D.y"
                                            :stroke="highlight === 'diagonals' ? '#10b981' : '#3b82f6'"
                                            :stroke-width="highlight === 'diagonals' ? 3 : 2"
                                            stroke-dasharray="8,4"/>
                                    </g>
                                </template>

                                {{-- Height for trapezoid --}}
                                <template x-if="showHeight">
                                    <g>
                                        <line :x1="heightStart.x" :y1="heightStart.y" :x2="heightEnd.x" :y2="heightEnd.y"
                                            :stroke="highlight === 'height' ? '#f59e0b' : '#10b981'"
                                            :stroke-width="highlight === 'height' ? 3 : 2"/>
                                        <text :x="heightEnd.x + 8" :y="(heightStart.y + heightEnd.y)/2" fill="#10b981" font-size="14" class="geo-label">h</text>
                                    </g>
                                </template>

                                {{-- Parallel marks --}}
                                <template x-if="highlight === 'parallel'">
                                    <g>
                                        <line :x1="(A.x + D.x)/2 - 5" :y1="A.y" :x2="(A.x + D.x)/2 + 5" :y2="A.y" stroke="#f59e0b" stroke-width="3"/>
                                        <line :x1="(A.x + D.x)/2 - 5" :y1="A.y + 5" :x2="(A.x + D.x)/2 + 5" :y2="A.y + 5" stroke="#f59e0b" stroke-width="3"/>
                                        <line :x1="(B.x + C.x)/2 - 5" :y1="B.y" :x2="(B.x + C.x)/2 + 5" :y2="B.y" stroke="#f59e0b" stroke-width="3"/>
                                        <line :x1="(B.x + C.x)/2 - 5" :y1="B.y + 5" :x2="(B.x + C.x)/2 + 5" :y2="B.y + 5" stroke="#f59e0b" stroke-width="3"/>
                                    </g>
                                </template>

                                {{-- Right angle markers --}}
                                <template x-if="showRightAngles">
                                    <g>
                                        <path :d="rightAngleA" fill="none" :stroke="highlight === 'angles' ? '#f59e0b' : '#666'" stroke-width="1.5"/>
                                        <path :d="rightAngleD" fill="none" :stroke="highlight === 'angles' ? '#f59e0b' : '#666'" stroke-width="1.5"/>
                                    </g>
                                </template>

                                {{-- Vertices --}}
                                <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                                <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                                <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                                <circle :cx="D.x" :cy="D.y" r="4" fill="#dc2626"/>

                                {{-- Labels --}}
                                <text :x="A.x - 15" :y="A.y + 5" fill="#60a5fa" font-size="16" class="geo-label">A</text>
                                <text :x="B.x - 5" :y="B.y - 8" fill="#60a5fa" font-size="16" class="geo-label">B</text>
                                <text :x="C.x + 8" :y="C.y - 5" fill="#60a5fa" font-size="16" class="geo-label">C</text>
                                <text :x="D.x + 8" :y="D.y + 5" fill="#60a5fa" font-size="16" class="geo-label">D</text>
                            </svg>
                        </div>

                        {{-- Hint buttons --}}
                        <div class="flex flex-wrap gap-2 mt-3 justify-center">
                            <template x-for="h in hints" :key="h.id">
                                <button @mouseenter="highlight = h.id" @mouseleave="highlight = null"
                                    :class="highlight === h.id ? 'bg-amber-500 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
                                    class="px-3 py-1.5 rounded-full text-sm font-medium transition-all" x-text="h.label"></button>
                            </template>
                        </div>

                        {{-- Hint description --}}
                        <div x-show="highlight && currentHintDesc" x-cloak class="mt-3 bg-amber-500/20 border-l-4 border-amber-500 p-3 rounded">
                            <p class="text-slate-200 text-sm" x-text="currentHintDesc"></p>
                        </div>
                    </div>

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

    // === Geometry Demo for Topic 15 (Triangles) ===

    // Helper functions
    function labelPos(point, center, distance = 20) {
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

    // Main geometry demo function
    function geometryDemo(type, id) {
        // Base triangle coordinates
        let A, B, C;

        // Configure based on type
        if (type === 'rightAcute' || type === 'areaCatheti' || type === 'pythagoras' || type === 'pythagorasCathetus') {
            A = { x: 30, y: 190 };
            B = { x: 250, y: 40 };
            C = { x: 250, y: 190 }; // Right angle at C
        } else if (type === 'isosceles' || type === 'isoscelesExternal') {
            A = { x: 50, y: 190 };
            B = { x: 150, y: 30 };
            C = { x: 250, y: 190 };
        } else if (type === 'externalAngle') {
            A = { x: 30, y: 170 };
            B = { x: 130, y: 30 };
            C = { x: 200, y: 170 };
        } else {
            A = { x: 30, y: 190 };
            B = { x: 160, y: 30 };
            C = { x: 270, y: 190 };
        }

        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };

        // Special points
        const M = { x: (A.x + C.x) / 2, y: A.y }; // Midpoint of AC
        const D = { x: (A.x + C.x * 2) / 3, y: A.y }; // Bisector point on AC
        const H = { x: B.x, y: A.y }; // Height foot

        // Hints based on geometry type
        let hints = [];
        let hasMedian = false, hasBisector = false, hasHeight = false;
        let hasRightAngle = false, hasCircumcircle = false, isIsosceles = false;

        switch (type) {
            case 'bisector':
                hints = [
                    { id: 'special', label: 'Биссектриса', desc: 'BD — биссектриса. ∠ABD = ∠DBC' },
                    { id: 'angles', label: 'Углы', desc: '∠A + ∠B + ∠C = 180°' }
                ];
                hasBisector = true;
                break;
            case 'median':
                hints = [
                    { id: 'special', label: 'Медиана', desc: 'BM — медиана. AM = MC = AC/2' },
                    { id: 'sides', label: 'Стороны', desc: 'Медиана делит сторону пополам' }
                ];
                hasMedian = true;
                break;
            case 'anglesSum':
                hints = [
                    { id: 'angles', label: 'Сумма углов', desc: '∠A + ∠B + ∠C = 180°. Третий угол = 180° − (первый + второй)' }
                ];
                break;
            case 'externalAngle':
                hints = [
                    { id: 'angles', label: 'Внешний угол', desc: 'Внешний угол = ∠A + ∠B (сумма несмежных углов)' }
                ];
                break;
            case 'isosceles':
            case 'isoscelesExternal':
                hints = [
                    { id: 'equalSides', label: 'Равные стороны', desc: 'AB = BC — боковые стороны равны' },
                    { id: 'angles', label: 'Углы при основании', desc: '∠A = ∠C — углы при основании равны' }
                ];
                isIsosceles = true;
                break;
            case 'equilateral':
                hints = [
                    { id: 'sides', label: 'Равные стороны', desc: 'AB = BC = CA — все стороны равны' },
                    { id: 'angles', label: 'Углы', desc: 'Все углы по 60°' }
                ];
                break;
            case 'rightAcute':
                hints = [
                    { id: 'angle', label: 'Прямой угол', desc: '∠C = 90°' },
                    { id: 'angles', label: 'Острые углы', desc: '∠A + ∠B = 90° — сумма острых углов' }
                ];
                hasRightAngle = true;
                break;
            case 'height':
                hints = [
                    { id: 'special', label: 'Высота', desc: 'BH ⊥ AC — высота из B' },
                    { id: 'angles', label: 'Углы', desc: '∠BHA = 90°' }
                ];
                hasHeight = true;
                break;
            case 'areaCatheti':
            case 'areaHeight':
                hints = [
                    { id: 'sides', label: 'Катеты', desc: 'AC и BC — катеты прямоугольного треугольника' },
                    { id: 'angle', label: 'Площадь', desc: 'S = (AC × BC) / 2' }
                ];
                hasRightAngle = true;
                break;
            case 'midline':
                hints = [
                    { id: 'special', label: 'Средняя линия', desc: 'MN ∥ AC и MN = AC/2' },
                    { id: 'sides', label: 'Стороны', desc: 'M и N — середины сторон' }
                ];
                hasMedian = true;
                break;
            case 'pythagoras':
            case 'pythagorasCathetus':
                hints = [
                    { id: 'sides', label: 'Теорема Пифагора', desc: 'AB² = AC² + BC²' },
                    { id: 'angle', label: 'Прямой угол', desc: '∠C = 90°' }
                ];
                hasRightAngle = true;
                break;
            case 'circumcircle':
                hints = [
                    { id: 'special', label: 'Описанная окр.', desc: 'R = AB / (2 sin C)' },
                    { id: 'sides', label: 'Радиус', desc: 'Центр — пересечение срединных перпендикуляров' }
                ];
                hasCircumcircle = true;
                break;
            case 'trig':
                hints = [
                    { id: 'angles', label: 'Тригонометрия', desc: 'sin α = противолежащий / гипотенуза, cos α = прилежащий / гипотенуза' },
                    { id: 'angle', label: 'Прямой угол', desc: '∠C = 90°' }
                ];
                hasRightAngle = true;
                break;
            default:
                hints = [
                    { id: 'sides', label: 'Стороны', desc: 'AB, BC, CA — стороны треугольника' },
                    { id: 'angles', label: 'Углы', desc: '∠A + ∠B + ∠C = 180°' }
                ];
        }

        // Return Alpine.js data object
        return {
            A, B, C, M, D, H, center,
            highlight: null,
            hints,
            hasMedian, hasBisector, hasHeight, hasRightAngle, hasCircumcircle, isIsosceles,

            // Label positions
            get labelA() { return labelPos(this.A, this.center, 22); },
            get labelB() { return labelPos(this.B, this.center, 22); },
            get labelC() { return labelPos(this.C, this.center, 22); },

            // Markers for isosceles triangles
            get markAB() { return { x: (this.A.x + this.B.x) / 2, y: (this.A.y + this.B.y) / 2 }; },
            get markBC() { return { x: (this.B.x + this.C.x) / 2, y: (this.B.y + this.C.y) / 2 }; },

            // Circumcircle (simplified - approximate center)
            get circleCenter() { return { x: (this.A.x + this.B.x + this.C.x) / 3, y: (this.A.y + this.B.y + this.C.y) / 3 }; },
            get circleRadius() {
                const cx = this.circleCenter.x, cy = this.circleCenter.y;
                return Math.sqrt((this.A.x - cx) ** 2 + (this.A.y - cy) ** 2);
            },

            // Current hint description
            get currentHintDesc() {
                const h = this.hints.find(h => h.id === this.highlight);
                return h ? h.desc : '';
            },

            // Helper methods
            makeAngleArc(v, p1, p2, r) { return makeAngleArc(v, p1, p2, r); },
            rightAnglePath() {
                // For right angle at C
                return rightAnglePath(this.C, this.A, this.B, 12);
            }
        };
    }

    // === Circle Demo for Topic 16 ===
    function circleDemo(type, id) {
        let hints = [];
        let showRadius = true;
        let showTangent = false;
        let showChord = false;
        let showCentralAngle = false;
        let showInscribedAngle = false;

        switch (type) {
            case 'tangent':
                hints = [
                    { id: 'tangent', label: 'Касательная', desc: 'Касательная перпендикулярна радиусу в точке касания: OA ⊥ касательной' },
                    { id: 'radius', label: 'Радиус', desc: 'r — радиус окружности' }
                ];
                showTangent = true;
                break;
            case 'centralAngle':
                hints = [
                    { id: 'angle', label: 'Центральный угол', desc: 'Центральный угол равен дуге, на которую он опирается' },
                    { id: 'circle', label: 'Окружность', desc: 'Дуга AB = ∠AOB' }
                ];
                showCentralAngle = true;
                showRadius = false;
                break;
            case 'inscribedAngle':
                hints = [
                    { id: 'angle', label: 'Вписанный угол', desc: 'Вписанный угол равен половине дуги: ∠ACB = (дуга AB) / 2' },
                    { id: 'circle', label: 'Окружность', desc: 'Вписанный угол в 2 раза меньше центрального' }
                ];
                showInscribedAngle = true;
                showRadius = false;
                break;
            case 'chord':
                hints = [
                    { id: 'chord', label: 'Хорда', desc: 'AB — хорда (отрезок, соединяющий две точки окружности)' },
                    { id: 'radius', label: 'Радиус', desc: 'Перпендикуляр из центра на хорду делит её пополам' }
                ];
                showChord = true;
                break;
            case 'arc':
                hints = [
                    { id: 'circle', label: 'Дуга', desc: 'Длина дуги = πr × (α / 180°), где α — центральный угол' },
                    { id: 'radius', label: 'Радиус', desc: 'r — радиус окружности' }
                ];
                break;
            case 'sector':
                hints = [
                    { id: 'circle', label: 'Сектор', desc: 'Площадь сектора = πr² × (α / 360°)' },
                    { id: 'radius', label: 'Радиус', desc: 'r — радиус окружности' }
                ];
                break;
            default: // basic
                hints = [
                    { id: 'circle', label: 'Окружность', desc: 'Длина окружности = 2πr, площадь круга = πr²' },
                    { id: 'radius', label: 'Радиус', desc: 'r — расстояние от центра до любой точки окружности' }
                ];
        }

        return {
            highlight: null,
            hints,
            showRadius,
            showTangent,
            showChord,
            showCentralAngle,
            showInscribedAngle,

            get currentHintDesc() {
                const h = this.hints.find(h => h.id === this.highlight);
                return h ? h.desc : '';
            },

            // Arc paths for angles
            get centralAngleArc() {
                return makeAngleArc({ x: 110, y: 110 }, { x: 50, y: 60 }, { x: 170, y: 60 }, 25);
            },
            get inscribedAngleArc() {
                return makeAngleArc({ x: 110, y: 190 }, { x: 50, y: 60 }, { x: 170, y: 60 }, 20);
            }
        };
    }

    // === Quadrilateral Demo for Topic 17 ===
    function quadDemo(type, id) {
        let A, B, C, D;
        let hints = [];
        let showDiagonals = true;
        let showHeight = false;
        let showRightAngles = false;

        // Configure coordinates based on quadrilateral type
        switch (type) {
            case 'parallelogram':
                A = { x: 30, y: 140 };
                B = { x: 70, y: 40 };
                C = { x: 230, y: 40 };
                D = { x: 190, y: 140 };
                hints = [
                    { id: 'sides', label: 'Стороны', desc: 'AB ∥ CD, AD ∥ BC — противоположные стороны параллельны и равны' },
                    { id: 'diagonals', label: 'Диагонали', desc: 'Диагонали точкой пересечения делятся пополам' },
                    { id: 'parallel', label: 'Параллельные', desc: 'AB = CD, AD = BC' }
                ];
                break;
            case 'rectangle':
                A = { x: 30, y: 140 };
                B = { x: 30, y: 40 };
                C = { x: 230, y: 40 };
                D = { x: 230, y: 140 };
                showRightAngles = true;
                hints = [
                    { id: 'sides', label: 'Стороны', desc: 'Все углы прямые (90°)' },
                    { id: 'diagonals', label: 'Диагонали', desc: 'Диагонали равны и делятся пополам: AC = BD' },
                    { id: 'angles', label: 'Углы', desc: '∠A = ∠B = ∠C = ∠D = 90°' }
                ];
                break;
            case 'rhombus':
                A = { x: 130, y: 150 };
                B = { x: 50, y: 90 };
                C = { x: 130, y: 30 };
                D = { x: 210, y: 90 };
                hints = [
                    { id: 'sides', label: 'Стороны', desc: 'Все стороны равны: AB = BC = CD = DA' },
                    { id: 'diagonals', label: 'Диагонали', desc: 'Диагонали перпендикулярны и делят друг друга пополам' },
                    { id: 'parallel', label: 'Параллельные', desc: 'AB ∥ CD, AD ∥ BC' }
                ];
                break;
            case 'square':
                A = { x: 50, y: 140 };
                B = { x: 50, y: 40 };
                C = { x: 150, y: 40 };
                D = { x: 150, y: 140 };
                showRightAngles = true;
                hints = [
                    { id: 'sides', label: 'Стороны', desc: 'Все стороны равны: AB = BC = CD = DA' },
                    { id: 'diagonals', label: 'Диагонали', desc: 'Диагонали равны, перпендикулярны, делятся пополам' },
                    { id: 'angles', label: 'Углы', desc: 'Все углы по 90°' }
                ];
                break;
            case 'isoscelesTrapezoid':
                A = { x: 30, y: 140 };
                B = { x: 80, y: 40 };
                C = { x: 180, y: 40 };
                D = { x: 230, y: 140 };
                showHeight = true;
                hints = [
                    { id: 'sides', label: 'Боковые стороны', desc: 'AB = CD — боковые стороны равны' },
                    { id: 'parallel', label: 'Основания', desc: 'AD ∥ BC — основания параллельны' },
                    { id: 'diagonals', label: 'Диагонали', desc: 'Диагонали равны: AC = BD' },
                    { id: 'height', label: 'Высота', desc: 'h — высота трапеции' }
                ];
                break;
            case 'rightTrapezoid':
                A = { x: 30, y: 140 };
                B = { x: 30, y: 40 };
                C = { x: 180, y: 40 };
                D = { x: 230, y: 140 };
                showHeight = true;
                showRightAngles = true;
                hints = [
                    { id: 'sides', label: 'Стороны', desc: 'AB ⊥ AD — прямой угол' },
                    { id: 'parallel', label: 'Основания', desc: 'AD ∥ BC — основания параллельны' },
                    { id: 'height', label: 'Высота', desc: 'h = AB — боковая сторона является высотой' }
                ];
                break;
            default: // trapezoid
                A = { x: 30, y: 140 };
                B = { x: 80, y: 40 };
                C = { x: 180, y: 40 };
                D = { x: 230, y: 140 };
                showHeight = true;
                hints = [
                    { id: 'parallel', label: 'Основания', desc: 'AD ∥ BC — основания параллельны' },
                    { id: 'height', label: 'Высота', desc: 'S = (AD + BC) × h / 2' },
                    { id: 'diagonals', label: 'Диагонали', desc: 'AC и BD — диагонали' }
                ];
        }

        return {
            A, B, C, D,
            highlight: null,
            hints,
            showDiagonals,
            showHeight,
            showRightAngles,

            // Height line coordinates
            get heightStart() { return { x: this.B.x, y: this.B.y }; },
            get heightEnd() { return { x: this.B.x, y: this.A.y }; },

            // Right angle markers
            get rightAngleA() {
                return `M ${this.A.x + 12} ${this.A.y} L ${this.A.x + 12} ${this.A.y - 12} L ${this.A.x} ${this.A.y - 12}`;
            },
            get rightAngleD() {
                if (this.showRightAngles && this.D.x === this.C.x) {
                    return `M ${this.D.x - 12} ${this.D.y} L ${this.D.x - 12} ${this.D.y - 12} L ${this.D.x} ${this.D.y - 12}`;
                }
                return '';
            },

            get currentHintDesc() {
                const h = this.hints.find(h => h.id === this.highlight);
                return h ? h.desc : '';
            }
        };
    }
</script>

</body>
</html>
