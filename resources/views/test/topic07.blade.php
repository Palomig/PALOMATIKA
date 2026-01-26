<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>07. Числа, координатная прямая - Тест парсинга PDF</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathWithDisplayStyle()"></script>
    <script>
        function renderMathWithDisplayStyle() {
            // Добавляем \displaystyle к формулам с дробями для увеличения размера
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
        .number-line { font-family: 'Times New Roman', serif; }
        .katex { font-size: 1.1em; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-5xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← Назад к темам</a>
        <div class="flex gap-2 flex-wrap justify-center">
            <a href="{{ route('test.topic06') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">06</a>
            <span class="px-2 py-1 rounded bg-cyan-500 text-white font-bold">07</span>
            <a href="{{ route('test.topic08') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">08</a>
            <a href="{{ route('test.topic09') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">09</a>
            <a href="{{ route('test.topic10') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">10</a>
            <a href="{{ route('test.topic11') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">11</a>
            <a href="{{ route('test.topic12') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">12</a>
            <a href="{{ route('test.topic13') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">13</a>
            <a href="{{ route('test.topic14') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">14</a>
            <a href="{{ route('test.topic15') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">15</a>
            <a href="{{ route('test.topic16') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">16</a>
            <a href="{{ route('test.topic17') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">17</a>
            <a href="{{ route('test.topic18') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">18</a>
            <a href="{{ route('test.topic19') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">19</a>
        </div>
        <span class="text-slate-500">SVG</span>
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
        <h1 class="text-4xl font-bold text-white mb-2">07. Числа, координатная прямая</h1>
        <p class="text-slate-400 text-lg">Сравнение чисел и работа с координатной прямой</p>
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
            <h2 class="text-2xl font-bold text-white">07. Числа, координатная прямая</h2>
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

                @php $svgType = $zadanie['svg_type'] ?? null; @endphp

                @if($zadanie['type'] === 'simple_choice' && $svgType === 'three_points')
                    {{-- Simple choice with three points SVG --}}
                    <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                        <div class="bg-slate-900/50 rounded-lg p-4 mb-4">
                            @if(isset($zadanie['svg']))
                                {{-- Предзаготовленный SVG из JSON --}}
                                {!! $zadanie['svg'] !!}
                            @else
                                {{-- Fallback: динамическая генерация --}}
                                @php
                                    $pts = $zadanie['points'] ?? [];
                                    $values = array_column($pts, 'value');
                                    $minVal = min(min($values), 0);
                                    $maxVal = max($values);
                                    $minTick = floor($minVal) - 1;
                                    $maxTick = ceil($maxVal) + 1;
                                    $range = $maxTick - $minTick;
                                    $tickWidth = 280 / $range;
                                @endphp
                                <svg viewBox="0 0 320 55" class="w-full h-16 number-line">
                                    <defs>
                                        <marker id="arrowR3" markerWidth="8" markerHeight="8" refX="0" refY="3" orient="auto">
                                            <path d="M0,0 L0,6 L8,3 z" fill="#8B0000"/>
                                        </marker>
                                    </defs>
                                    <line x1="15" y1="25" x2="305" y2="25" stroke="#8B0000" stroke-width="2" marker-end="url(#arrowR3)"/>
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
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-4">
                            @foreach($zadanie['options'] as $i => $option)
                                <span class="bg-slate-700/70 text-slate-300 px-4 py-2 rounded-lg">
                                    {{ $i + 1 }}) {{ $option }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                @elseif($zadanie['type'] === 'simple_choice')
                    {{-- Simple choice with options (no image) --}}
                    <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                        <div class="flex flex-wrap gap-4">
                            @foreach($zadanie['options'] as $i => $option)
                                <span class="bg-slate-700/70 text-slate-300 px-4 py-2 rounded-lg">
                                    {{ $i + 1 }}) {{ $option }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                @elseif(in_array($zadanie['type'], ['choice', 'comparison', 'power_choice', 'ordering', 'fraction_options']) && in_array($svgType, ['single_point', 'two_points']))
                    {{-- Tasks with SVG number lines --}}
                    <div class="space-y-4">
                        @foreach($zadanie['tasks'] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 hover:border-slate-600 transition-colors">
                                <div class="flex items-start gap-3">
                                    <span class="text-cyan-400 font-bold flex-shrink-0">{{ $task['id'] }}</span>
                                    <div class="flex-1">
                                        {{-- SVG Number Line --}}
                                        <div class="bg-slate-900/50 rounded-lg p-4 mb-3">
                                            @if(isset($task['svg']))
                                                {{-- Предзаготовленный SVG из JSON --}}
                                                {!! $task['svg'] !!}
                                            @elseif($svgType === 'single_point' && isset($task['point_value']))
                                                {{-- Fallback: динамическая генерация для single_point --}}
                                                @php
                                                    $pointVal = $task['point_value'];
                                                    $pointLabel = $task['point_label'] ?? 'a';
                                                    $maxTick = ceil($pointVal) + 2;
                                                    $tickWidth = 280 / $maxTick;
                                                    $pointX = 15 + ($pointVal / $maxTick) * 280;
                                                @endphp
                                                <svg viewBox="0 0 320 55" class="w-full h-16 number-line">
                                                    <defs>
                                                        <marker id="arrowR-{{ $zadanie['number'] }}-{{ $task['id'] }}" markerWidth="8" markerHeight="8" refX="0" refY="3" orient="auto">
                                                            <path d="M0,0 L0,6 L8,3 z" fill="#8B0000"/>
                                                        </marker>
                                                    </defs>
                                                    <line x1="15" y1="25" x2="305" y2="25" stroke="#8B0000" stroke-width="2" marker-end="url(#arrowR-{{ $zadanie['number'] }}-{{ $task['id'] }})"/>
                                                    @for($i = 0; $i <= $maxTick; $i++)
                                                        <line x1="{{ 15 + $i * $tickWidth }}" y1="18" x2="{{ 15 + $i * $tickWidth }}" y2="32" stroke="#8B0000" stroke-width="1.5"/>
                                                    @endfor
                                                    <text x="{{ 15 }}" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">0</text>
                                                    <text x="{{ 15 + $tickWidth }}" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">1</text>
                                                    <circle cx="{{ $pointX }}" cy="25" r="6" fill="#22c55e"/>
                                                    <text x="{{ $pointX }}" y="12" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold">{{ $pointLabel }}</text>
                                                </svg>
                                            @elseif($svgType === 'two_points' && isset($task['points']))
                                                {{-- Fallback: динамическая генерация для two_points --}}
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
                                                <svg viewBox="0 0 320 55" class="w-full h-16 number-line">
                                                    <defs>
                                                        <marker id="arrowR2-{{ $zadanie['number'] }}-{{ $task['id'] }}" markerWidth="8" markerHeight="8" refX="0" refY="3" orient="auto">
                                                            <path d="M0,0 L0,6 L8,3 z" fill="#8B0000"/>
                                                        </marker>
                                                    </defs>
                                                    <line x1="15" y1="25" x2="305" y2="25" stroke="#8B0000" stroke-width="2" marker-end="url(#arrowR2-{{ $zadanie['number'] }}-{{ $task['id'] }})"/>
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
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap gap-3">
                                            @foreach($task['options'] as $i => $option)
                                                <span class="bg-slate-700/70 text-slate-300 px-3 py-1 rounded text-sm">
                                                    {{ $i + 1 }})
                                                    @if(str_contains($option, '\frac') || str_contains($option, '\sqrt'))
                                                        ${{ $option }}$
                                                    @else
                                                        {{ $option }}
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                @elseif(in_array($zadanie['type'], ['fraction_choice', 'fraction_point', 'point_value', 'sqrt_options']) && $svgType === 'four_points_abcd')
                    {{-- Four points A,B,C,D tasks --}}
                    <div class="space-y-4">
                        @foreach($zadanie['tasks'] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 hover:border-slate-600 transition-colors">
                                <div class="flex items-start gap-3">
                                    <span class="text-cyan-400 font-bold flex-shrink-0">{{ $task['id'] }}</span>
                                    <div class="flex-1">
                                        {{-- SVG with four points --}}
                                        <div class="bg-slate-900/50 rounded-lg p-4 mb-3">
                                            @if(isset($task['svg']))
                                                {{-- Предзаготовленный SVG из JSON --}}
                                                {!! $task['svg'] !!}
                                            @else
                                                {{-- Fallback: динамическая генерация --}}
                                                @php
                                                    $fourPts = $task['four_points'] ?? [5, 6, 7, 8];
                                                    $rangeArr = $task['range'] ?? [4, 9];
                                                    $minV = $rangeArr[0];
                                                    $maxV = $rangeArr[1];
                                                    $labels = ['A', 'B', 'C', 'D'];
                                                    $getX = function($v) use ($minV, $maxV) {
                                                        return 15 + (($v - $minV) / ($maxV - $minV)) * 280;
                                                    };
                                                @endphp
                                                <svg viewBox="0 0 320 55" class="w-full h-16 number-line">
                                                    <defs>
                                                        <marker id="arrowR4-{{ $zadanie['number'] }}-{{ $task['id'] }}" markerWidth="8" markerHeight="8" refX="0" refY="3" orient="auto">
                                                            <path d="M0,0 L0,6 L8,3 z" fill="#8B0000"/>
                                                        </marker>
                                                    </defs>
                                                    <line x1="15" y1="25" x2="305" y2="25" stroke="#8B0000" stroke-width="2" marker-end="url(#arrowR4-{{ $zadanie['number'] }}-{{ $task['id'] }})"/>
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
                                            @endif
                                        </div>
                                        @if(isset($task['expression']))
                                            <div class="text-slate-200 mb-2">${{ $task['expression'] }}$</div>
                                        @endif
                                        @if(isset($task['point']))
                                            <div class="text-slate-200 mb-2">Точка {{ $task['point'] }}:</div>
                                        @endif
                                        <div class="flex flex-wrap gap-3">
                                            @foreach($task['options'] as $i => $option)
                                                <span class="bg-slate-700/70 text-slate-300 px-3 py-1 rounded text-sm">
                                                    {{ $i + 1 }})
                                                    @if(str_contains($option, '\frac') || str_contains($option, '\sqrt'))
                                                        ${{ $option }}$
                                                    @else
                                                        {{ $option }}
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                @elseif($zadanie['type'] === 'sqrt_options' && $svgType === 'point_a_on_range')
                    {{-- Sqrt with point A on range --}}
                    <div class="space-y-4">
                        @foreach($zadanie['tasks'] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 hover:border-slate-600 transition-colors">
                                <div class="flex items-start gap-3">
                                    <span class="text-cyan-400 font-bold flex-shrink-0">{{ $task['id'] }}</span>
                                    <div class="flex-1">
                                        <div class="bg-slate-900/50 rounded-lg p-4 mb-3">
                                            @if(isset($task['svg']))
                                                {{-- Предзаготовленный SVG из JSON --}}
                                                {!! $task['svg'] !!}
                                            @else
                                                {{-- Fallback: динамическая генерация --}}
                                                @php
                                                    $rangeArr = $task['range'] ?? [5, 7];
                                                    $minV = $rangeArr[0];
                                                    $maxV = $rangeArr[1];
                                                    $pointA = $task['point_a'] ?? 6;
                                                    $getX = function($v) use ($minV, $maxV) {
                                                        return 15 + (($v - $minV) / ($maxV - $minV)) * 280;
                                                    };
                                                    $pointX = $getX($pointA);
                                                @endphp
                                                <svg viewBox="0 0 320 55" class="w-full h-16 number-line">
                                                    <defs>
                                                        <marker id="arrowRA-{{ $zadanie['number'] }}-{{ $task['id'] }}" markerWidth="8" markerHeight="8" refX="0" refY="3" orient="auto">
                                                            <path d="M0,0 L0,6 L8,3 z" fill="#8B0000"/>
                                                        </marker>
                                                    </defs>
                                                    <line x1="15" y1="25" x2="305" y2="25" stroke="#8B0000" stroke-width="2" marker-end="url(#arrowRA-{{ $zadanie['number'] }}-{{ $task['id'] }})"/>
                                                    @for($i = ceil($minV); $i <= floor($maxV); $i++)
                                                        <line x1="{{ $getX($i) }}" y1="18" x2="{{ $getX($i) }}" y2="32" stroke="#8B0000" stroke-width="1.5"/>
                                                    @endfor
                                                    <text x="{{ $getX(ceil($minV)) }}" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">{{ ceil($minV) }}</text>
                                                    <text x="{{ $getX(floor($maxV)) }}" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">{{ floor($maxV) }}</text>
                                                    <circle cx="{{ $pointX }}" cy="25" r="6" fill="#22c55e"/>
                                                    <text x="{{ $pointX }}" y="12" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold">A</text>
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap gap-3">
                                            @foreach($task['options'] as $i => $option)
                                                <span class="bg-slate-700/70 text-slate-300 px-3 py-1 rounded text-sm">
                                                    {{ $i + 1 }}) ${{ $option }}$
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                @elseif(in_array($zadanie['type'], ['interval_choice', 'sqrt_interval', 'negative_interval', 'sqrt_choice']))
                    {{-- Interval/sqrt choices - grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($zadanie['tasks'] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                                <span class="text-cyan-400 font-bold">{{ $task['id'] }}</span>
                                <span class="text-slate-200 ml-2">${{ $task['expression'] }}$</span>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach($task['options'] as $i => $option)
                                        <span class="bg-slate-700/70 text-slate-300 px-3 py-1 rounded text-sm">
                                            {{ $i + 1 }}) {{ $option }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                @elseif($zadanie['type'] === 'between_fractions')
                    {{-- Between two fractions --}}
                    <div class="space-y-3">
                        @foreach($zadanie['tasks'] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 flex flex-wrap items-center gap-3">
                                <span class="text-cyan-400 font-bold">{{ $task['id'] }}</span>
                                <span class="text-slate-200">${{ $task['left'] }}$ и ${{ $task['right'] }}$?</span>
                                <div class="flex gap-2">
                                    @foreach($task['options'] as $i => $option)
                                        <span class="bg-slate-700/70 text-slate-300 px-3 py-1 rounded text-sm">
                                            {{ $i + 1 }}) {{ $option }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                @elseif(in_array($zadanie['type'], ['segment_choice', 'negative_segment', 'sqrt_segment']))
                    {{-- Segment choice --}}
                    <div class="space-y-3">
                        @foreach($zadanie['tasks'] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 flex flex-wrap items-center gap-3">
                                <span class="text-cyan-400 font-bold">{{ $task['id'] }}</span>
                                <span class="text-slate-300 font-medium">{{ $task['segment'] }}:</span>
                                @foreach($task['options'] as $i => $option)
                                    <span class="bg-slate-700/70 text-slate-300 px-3 py-1 rounded text-sm">
                                        {{ $i + 1 }}) ${{ $option }}$
                                    </span>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                @elseif(in_array($zadanie['type'], ['fraction_options', 'sqrt_options']) && !$svgType)
                    {{-- Fraction/sqrt options without SVG --}}
                    <div class="space-y-3">
                        @foreach($zadanie['tasks'] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 flex flex-wrap items-center gap-3">
                                <span class="text-cyan-400 font-bold">{{ $task['id'] }}</span>
                                @foreach($task['options'] as $i => $option)
                                    <span class="bg-slate-700/70 text-slate-300 px-3 py-1 rounded text-sm">
                                        {{ $i + 1 }}) ${{ $option }}$
                                    </span>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                @elseif($zadanie['type'] === 'decimal_choice')
                    {{-- Decimal choice --}}
                    <div class="space-y-4">
                        @foreach($zadanie['tasks'] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                                <span class="text-cyan-400 font-bold">{{ $task['id'] }}</span>
                                <div class="text-slate-400 text-sm mt-1">Числа: {{ $task['numbers'] }}</div>
                                <div class="text-slate-200 mt-1">Число <strong class="text-cyan-300">{{ $task['target'] }}</strong>?</div>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach($task['options'] as $i => $option)
                                        <span class="bg-slate-700/70 text-slate-300 px-3 py-1 rounded text-sm">
                                            {{ $i + 1 }}) {{ $option }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                @elseif($zadanie['type'] === 'compare_fractions')
                    {{-- Compare fractions --}}
                    <div class="space-y-4">
                        @foreach($zadanie['tasks'] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                                <span class="text-cyan-400 font-bold">{{ $task['id'] }}</span>
                                <div class="text-slate-200 mt-1">{{ $task['condition'] }}: ${{ $task['question'] }}$</div>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach($task['options'] as $i => $option)
                                        <span class="bg-slate-700/70 text-slate-300 px-3 py-1 rounded text-sm">
                                            {{ $i + 1 }}) {{ $option }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                @elseif(in_array($zadanie['type'], ['false_statements']))
                    {{-- False statements --}}
                    <div class="space-y-3">
                        @foreach($zadanie['tasks'] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 flex flex-wrap items-center gap-3">
                                <span class="text-cyan-400 font-bold">{{ $task['id'] }}</span>
                                @foreach($task['options'] as $i => $option)
                                    <span class="bg-slate-700/70 text-slate-300 px-3 py-1 rounded text-sm">
                                        {{ $i + 1 }}) {{ $option }}
                                    </span>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                @elseif($zadanie['type'] === 'count_integers')
                    {{-- Count integers --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach($zadanie['tasks'] as $task)
                            <div class="bg-slate-800/70 rounded-lg p-3 border border-slate-700">
                                <span class="text-cyan-400 font-bold">{{ $task['id'] }}</span>
                                <span class="text-slate-200 ml-1">${{ $task['left'] }}$ и ${{ $task['right'] }}$?</span>
                            </div>
                        @endforeach
                    </div>

                @else
                    {{-- Default --}}
                    <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                        <p class="text-slate-400 italic">Тип задания: {{ $zadanie['type'] }}</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    @endforeach

    {{-- Info Box --}}
    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 mt-10">
        <h4 class="text-white font-semibold mb-4">Информация о парсинге</h4>
        <div class="text-slate-400 text-sm space-y-2">
            <p><strong class="text-slate-300">Тема:</strong> 07. Числа, координатная прямая</p>
            <p><strong class="text-slate-300">Источник:</strong> {{ $source ?? 'Manual' }}</p>
            <p><strong class="text-slate-300">Контроллер:</strong> <code class="bg-slate-700 px-2 py-1 rounded text-xs">TestPdfController::getAllBlocksData07()</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1">
                <li>Типы заданий: choice, fraction_choice, interval_choice, sqrt_choice и др.</li>
                <li>Графика: SVG числовые прямые с динамической генерацией</li>
                <li>Всего: {{ $totalTasks }} задач</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-slate-500 text-sm mt-8">SVG числовые прямые генерируются на сервере (PHP)</p>
</div>

{{-- Инструмент для пометки заданий --}}
@include('components.task-review-tool', ['topicId' => '07'])

</body>
</html>
