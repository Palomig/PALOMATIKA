<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>13. Неравенства - Тест парсинга PDF</title>

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
        .number-line { font-family: 'Times New Roman', serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-6xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← Назад к темам</a>
        <div class="flex gap-2 flex-wrap justify-center">
            <a href="{{ route('test.topic06') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">06</a>
            <a href="{{ route('test.topic07') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">07</a>
            <a href="{{ route('test.topic08') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">08</a>
            <a href="{{ route('test.topic09') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">09</a>
            <a href="{{ route('test.topic10') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">10</a>
            <a href="{{ route('test.topic11') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">11</a>
            <a href="{{ route('test.topic12') }}" class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition">12</a>
            <span class="px-2 py-1 rounded bg-purple-500 text-white font-bold">13</span>
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

        // Решения для графических заданий (блок 2)
        // Формат: [тип, точка1, включена1, точка2?, включена2?]
        // Типы: 'left' (луч влево), 'right' (луч вправо), 'segment' (отрезок)
        $solutions = [
            // Задание 1: Линейные неравенства (блок 2)
            '2-1-1' => ['left', 3.5, true],           // 4x+5 ≥ 6x-2 → x ≤ 3,5
            '2-1-2' => ['right', -8, true],          // -2x+5 ≥ -3x-3 → x ≥ -8
            '2-1-3' => ['left', -0.5, true],         // 3-x ≥ 3x+5 → x ≤ -0,5
            '2-1-4' => ['left', 3, true],            // x+4 ≥ 4x-5 → x ≤ 3
            '2-1-5' => ['left', 2.5, true],          // 2+x ≥ 5x-8 → x ≤ 2,5
            '2-1-6' => ['right', 0.5, true],         // 4x-5 ≥ 2x-4 → x ≥ 0,5
            '2-1-7' => ['left', -1.5, true],         // x-1 ≥ 3x+2 → x ≤ -1,5
            '2-1-8' => ['right', -0.5, true],        // 2x+4 ≥ -4x+1 → x ≥ -0,5
            '2-1-9' => ['left', -2, true],           // x-2 ≥ 4x+4 → x ≤ -2

            // Задание 4: Квадратные неравенства (блок 2)
            '2-4-1' => ['segment', 1, true, 3, true],      // x²-4x+3 ≤ 0 → [1; 3]
            '2-4-2' => ['segment', 3, true, 4, true],      // x²-7x+12 ≤ 0 → [3; 4]
            '2-4-3' => ['segment', -5, true, -4, true],    // x²+9x+20 ≤ 0 → [-5; -4]
            '2-4-4' => ['segment', -1, true, 6, true],     // x²-5x-6 ≤ 0 → [-1; 6]
            '2-4-5' => ['segment', 8, true, 9, true],      // x²-17x+72 ≤ 0 → [8; 9]
            '2-4-6' => ['segment', -3, true, 9, true],     // x²-6x-27 ≤ 0 → [-3; 9]
        ];
    @endphp

    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">13. Неравенства</h1>
        <p class="text-slate-400 text-lg">Решение линейных и квадратных неравенств</p>
    </div>

    {{-- Stats --}}
    <div class="flex justify-center gap-6 mb-10">
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-purple-400 font-bold text-xl">{{ count($blocks) }}</span>
            <span class="text-slate-400 ml-2">блоков</span>
        </div>
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-purple-400 font-bold text-xl">{{ $totalTasks }}</span>
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
            <h2 class="text-2xl font-bold text-white">13. Неравенства</h2>
            <p class="text-purple-400 text-lg mt-1">Блок {{ $block['number'] }}. {{ $block['title'] }}</p>
        </div>

        @foreach($block['zadaniya'] as $zadanieIndex => $zadanie)
            <div class="mb-10">
                {{-- Zadanie Header --}}
                <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-purple-500">
                    <h3 class="text-lg font-semibold text-white">
                        Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}
                    </h3>
                </div>

                @if(($zadanie['type'] ?? '') === 'graphic')
                    {{-- Graphic type: SVG number lines --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($zadanie['tasks'] ?? [] as $task)
                            @php
                                $solutionKey = $block['number'] . '-' . $zadanie['number'] . '-' . $task['id'];
                                $solution = $solutions[$solutionKey] ?? null;
                            @endphp
                            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 hover:border-purple-500/50 transition-colors">
                                <div class="text-purple-400 font-bold mb-2">{{ $task['id'] }}.</div>
                                <div class="text-slate-200 text-lg mb-3">${{ $task['expression'] ?? '' }}$</div>

                                {{-- SVG Number Line --}}
                                <div class="bg-slate-900/50 rounded-lg p-3">
                                    @if($solution)
                                        @if($solution[0] === 'left')
                                            {{-- Луч влево: (-∞; a] или (-∞; a) --}}
                                            <div x-data="numberLineLeft({{ $solution[1] }}, {{ $solution[2] ? 'true' : 'false' }})">
                                                <svg viewBox="0 0 280 60" class="w-full h-14 number-line">
                                                    <defs>
                                                        <marker id="arrowLeft-{{ $task['id'] }}" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                                                            <path d="M9,0 L9,6 L0,3 z" fill="#8b5cf6"/>
                                                        </marker>
                                                        <marker id="arrowRight-{{ $task['id'] }}" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto">
                                                            <path d="M0,0 L0,6 L9,3 z" fill="#64748b"/>
                                                        </marker>
                                                    </defs>

                                                    {{-- Числовая прямая --}}
                                                    <line x1="20" y1="30" x2="260" y2="30" stroke="#64748b" stroke-width="2" marker-end="url(#arrowRight-{{ $task['id'] }})"/>

                                                    {{-- Заштрихованный интервал --}}
                                                    <line x1="20" y1="30" :x2="pointX" y2="30" stroke="#8b5cf6" stroke-width="4" marker-start="url(#arrowLeft-{{ $task['id'] }})"/>

                                                    {{-- Точка --}}
                                                    <circle :cx="pointX" cy="30" r="6" :fill="inclusive ? '#8b5cf6' : '#1e293b'" stroke="#8b5cf6" stroke-width="2"/>

                                                    {{-- Подпись точки --}}
                                                    <text :x="pointX" y="52" fill="#e2e8f0" font-size="14" text-anchor="middle" x-text="pointLabel"></text>

                                                    {{-- Ноль --}}
                                                    <template x-if="showZero">
                                                        <g>
                                                            <line :x1="zeroX" y1="25" :x2="zeroX" y2="35" stroke="#64748b" stroke-width="1.5"/>
                                                            <text :x="zeroX" y="18" fill="#94a3b8" font-size="12" text-anchor="middle">0</text>
                                                        </g>
                                                    </template>
                                                </svg>
                                            </div>
                                        @elseif($solution[0] === 'right')
                                            {{-- Луч вправо: [a; +∞) или (a; +∞) --}}
                                            <div x-data="numberLineRight({{ $solution[1] }}, {{ $solution[2] ? 'true' : 'false' }})">
                                                <svg viewBox="0 0 280 60" class="w-full h-14 number-line">
                                                    <defs>
                                                        <marker id="arrowRightPurple-{{ $task['id'] }}" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto">
                                                            <path d="M0,0 L0,6 L9,3 z" fill="#8b5cf6"/>
                                                        </marker>
                                                        <marker id="arrowLeftGray-{{ $task['id'] }}" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                                                            <path d="M9,0 L9,6 L0,3 z" fill="#64748b"/>
                                                        </marker>
                                                    </defs>

                                                    {{-- Числовая прямая --}}
                                                    <line x1="20" y1="30" x2="260" y2="30" stroke="#64748b" stroke-width="2" marker-start="url(#arrowLeftGray-{{ $task['id'] }})"/>

                                                    {{-- Заштрихованный интервал --}}
                                                    <line :x1="pointX" y1="30" x2="260" y2="30" stroke="#8b5cf6" stroke-width="4" marker-end="url(#arrowRightPurple-{{ $task['id'] }})"/>

                                                    {{-- Точка --}}
                                                    <circle :cx="pointX" cy="30" r="6" :fill="inclusive ? '#8b5cf6' : '#1e293b'" stroke="#8b5cf6" stroke-width="2"/>

                                                    {{-- Подпись точки --}}
                                                    <text :x="pointX" y="52" fill="#e2e8f0" font-size="14" text-anchor="middle" x-text="pointLabel"></text>

                                                    {{-- Ноль --}}
                                                    <template x-if="showZero">
                                                        <g>
                                                            <line :x1="zeroX" y1="25" :x2="zeroX" y2="35" stroke="#64748b" stroke-width="1.5"/>
                                                            <text :x="zeroX" y="18" fill="#94a3b8" font-size="12" text-anchor="middle">0</text>
                                                        </g>
                                                    </template>
                                                </svg>
                                            </div>
                                        @elseif($solution[0] === 'segment')
                                            {{-- Отрезок: [a; b] --}}
                                            <div x-data="numberLineSegment({{ $solution[1] }}, {{ $solution[2] ? 'true' : 'false' }}, {{ $solution[3] }}, {{ $solution[4] ? 'true' : 'false' }})">
                                                <svg viewBox="0 0 280 60" class="w-full h-14 number-line">
                                                    <defs>
                                                        <marker id="arrowLeftSeg-{{ $task['id'] }}" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                                                            <path d="M9,0 L9,6 L0,3 z" fill="#64748b"/>
                                                        </marker>
                                                        <marker id="arrowRightSeg-{{ $task['id'] }}" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto">
                                                            <path d="M0,0 L0,6 L9,3 z" fill="#64748b"/>
                                                        </marker>
                                                    </defs>

                                                    {{-- Числовая прямая --}}
                                                    <line x1="20" y1="30" x2="260" y2="30" stroke="#64748b" stroke-width="2"
                                                        marker-start="url(#arrowLeftSeg-{{ $task['id'] }})" marker-end="url(#arrowRightSeg-{{ $task['id'] }})"/>

                                                    {{-- Заштрихованный интервал --}}
                                                    <line :x1="point1X" y1="30" :x2="point2X" y2="30" stroke="#8b5cf6" stroke-width="4"/>

                                                    {{-- Точка 1 --}}
                                                    <circle :cx="point1X" cy="30" r="6" :fill="inclusive1 ? '#8b5cf6' : '#1e293b'" stroke="#8b5cf6" stroke-width="2"/>

                                                    {{-- Точка 2 --}}
                                                    <circle :cx="point2X" cy="30" r="6" :fill="inclusive2 ? '#8b5cf6' : '#1e293b'" stroke="#8b5cf6" stroke-width="2"/>

                                                    {{-- Подписи точек --}}
                                                    <text :x="point1X" y="52" fill="#e2e8f0" font-size="14" text-anchor="middle" x-text="point1Label"></text>
                                                    <text :x="point2X" y="52" fill="#e2e8f0" font-size="14" text-anchor="middle" x-text="point2Label"></text>

                                                    {{-- Ноль --}}
                                                    <template x-if="showZero">
                                                        <g>
                                                            <line :x1="zeroX" y1="25" :x2="zeroX" y2="35" stroke="#64748b" stroke-width="1.5"/>
                                                            <text :x="zeroX" y="18" fill="#94a3b8" font-size="12" text-anchor="middle">0</text>
                                                        </g>
                                                    </template>
                                                </svg>
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-slate-500 text-center py-4 text-sm">Решение будет добавлено</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Choice type: regular list with options --}}
                    <div class="space-y-4">
                        @foreach($zadanie['tasks'] ?? [] as $task)
                            <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-slate-600 transition-colors">
                                <div class="flex items-start gap-4 mb-3">
                                    <span class="text-purple-400 font-bold text-lg flex-shrink-0">{{ $task['id'] }}.</span>
                                    <span class="text-slate-200 text-lg">${{ $task['expression'] ?? '' }}$</span>
                                </div>
                                @if(isset($task['options']))
                                    <div class="flex flex-wrap gap-3 ml-8">
                                        @foreach($task['options'] as $i => $opt)
                                            <span class="bg-slate-700/70 text-slate-300 px-4 py-2 rounded-lg text-sm hover:bg-slate-600 transition-colors cursor-default">
                                                {{ $i + 1 }}) {{ $opt }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
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
            <p><strong class="text-slate-300">Тема:</strong> 13. Неравенства</p>
            <p><strong class="text-slate-300">Источник:</strong> {{ $source ?? 'Manual' }}</p>
            <p><strong class="text-slate-300">Контроллер:</strong> <code class="bg-slate-700 px-2 py-1 rounded text-xs">TestPdfController::getAllBlocksData13()</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1">
                <li>Блок 1: ФИПИ (линейные, системы, квадратные неравенства)</li>
                <li>Блок 2: Расширенная версия (графическое решение)</li>
                <li>Всего: {{ $totalTasks }} задач</li>
                <li>Графики решений генерируются программно через SVG + Alpine.js</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-slate-500 text-sm mt-8">Все изображения числовых прямых генерируются программно</p>
</div>

<script>
    /**
     * SVG Number Line generators for inequalities
     * ==========================================
     */

    // Форматирование числа с запятой
    function formatNumber(n) {
        if (Number.isInteger(n)) return n.toString();
        return n.toString().replace('.', ',');
    }

    // Луч влево: (-∞; a]
    function numberLineLeft(point, inclusive) {
        const minX = 40;
        const maxX = 220;

        // Позиция точки (ближе к правому краю)
        const pointX = point >= 0 ? 180 : 200;

        // Позиция нуля
        let zeroX = null;
        let showZero = false;

        if (point > 0) {
            zeroX = pointX - (point / (point + 2)) * 100;
            showZero = zeroX > minX + 10;
        } else if (point < 0) {
            zeroX = pointX + 40;
            showZero = zeroX < maxX + 20;
        }

        return {
            pointX,
            inclusive,
            pointLabel: formatNumber(point),
            zeroX,
            showZero
        };
    }

    // Луч вправо: [a; +∞)
    function numberLineRight(point, inclusive) {
        const minX = 60;
        const maxX = 240;

        // Позиция точки (ближе к левому краю)
        const pointX = point <= 0 ? 100 : 80;

        // Позиция нуля
        let zeroX = null;
        let showZero = false;

        if (point > 0) {
            zeroX = pointX - 40;
            showZero = zeroX > minX - 20;
        } else if (point < 0) {
            zeroX = pointX + (Math.abs(point) / (Math.abs(point) + 2)) * 100;
            showZero = zeroX < maxX - 10;
        }

        return {
            pointX,
            inclusive,
            pointLabel: formatNumber(point),
            zeroX,
            showZero
        };
    }

    // Отрезок: [a; b]
    function numberLineSegment(point1, inclusive1, point2, inclusive2) {
        const minX = 50;
        const maxX = 230;
        const padding = 40;

        // Позиции точек
        const point1X = minX + padding;
        const point2X = maxX - padding;

        // Позиция нуля
        let zeroX = null;
        let showZero = false;

        const segmentWidth = point2X - point1X;

        if (point1 < 0 && point2 > 0) {
            // Ноль между точками
            const totalRange = point2 - point1;
            const zeroRatio = Math.abs(point1) / totalRange;
            zeroX = point1X + segmentWidth * zeroRatio;
            showZero = true;
        } else if (point1 >= 0) {
            // Обе положительные - ноль слева
            zeroX = minX + 15;
            showZero = true;
        } else {
            // Обе отрицательные - ноль справа
            zeroX = maxX - 15;
            showZero = true;
        }

        return {
            point1X,
            point2X,
            inclusive1,
            inclusive2,
            point1Label: formatNumber(point1),
            point2Label: formatNumber(point2),
            zeroX,
            showZero
        };
    }
</script>

</body>
</html>
