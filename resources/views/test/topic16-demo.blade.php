<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>16. Окружность (DEMO) - GEOMETRY_SPEC</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathInElement(document.body, {delimiters: [{left: '$', right: '$', display: false}]})"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

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
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

{{-- Load shared geometry helpers --}}
<script src="/js/geometry-helpers.js"></script>

<script>
    // Circle-specific geometry functions

    // Draw circle arc for inscribed/central angles
    function makeCircleArc(cx, cy, r, startAngle, endAngle) {
        const x1 = cx + r * Math.cos(startAngle * Math.PI / 180);
        const y1 = cy + r * Math.sin(startAngle * Math.PI / 180);
        const x2 = cx + r * Math.cos(endAngle * Math.PI / 180);
        const y2 = cy + r * Math.sin(endAngle * Math.PI / 180);
        const largeArc = Math.abs(endAngle - startAngle) > 180 ? 1 : 0;
        return `M ${x1} ${y1} A ${r} ${r} 0 ${largeArc} 1 ${x2} ${y2}`;
    }

    // Point on circle at given angle (degrees)
    function pointOnCircle(cx, cy, r, angleDeg) {
        const rad = angleDeg * Math.PI / 180;
        return {
            x: cx + r * Math.cos(rad),
            y: cy + r * Math.sin(rad)
        };
    }

    // Square vertices for inscribed circle tasks
    function squareVertices(cx, cy, side) {
        const half = side / 2;
        return {
            A: { x: cx - half, y: cy - half },
            B: { x: cx + half, y: cy - half },
            C: { x: cx + half, y: cy + half },
            D: { x: cx - half, y: cy + half }
        };
    }

    // Trapezoid vertices (isosceles)
    function trapezoidVertices(cx, cy, topBase, bottomBase, height) {
        const halfTop = topBase / 2;
        const halfBottom = bottomBase / 2;
        return {
            A: { x: cx - halfBottom, y: cy + height/2 },
            B: { x: cx - halfTop, y: cy - height/2 },
            C: { x: cx + halfTop, y: cy - height/2 },
            D: { x: cx + halfBottom, y: cy + height/2 }
        };
    }

    // Equilateral triangle centered
    function equilateralTriangle(cx, cy, side) {
        const h = side * Math.sqrt(3) / 2;
        const r = h * 2 / 3; // circumradius
        return {
            A: { x: cx, y: cy - r },
            B: { x: cx - side/2, y: cy + h/3 },
            C: { x: cx + side/2, y: cy + h/3 }
        };
    }

    window.makeCircleArc = makeCircleArc;
    window.pointOnCircle = pointOnCircle;
    window.squareVertices = squareVertices;
    window.trapezoidVertices = trapezoidVertices;
    window.equilateralTriangle = equilateralTriangle;
</script>

<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">16. Окружность, круг и их элементы</h1>
        <p class="text-slate-400">Блок 1. ФИПИ — 126 заданий</p>
        <a href="/test" class="text-cyan-400 hover:text-cyan-300 text-sm">← Вернуться к списку тем</a>
    </div>

    {{-- ==================== I) Касательная к окружности (1-12) ==================== --}}
    <div class="mb-12">
        <h2 class="text-xl font-bold text-rose-400 mb-6 border-b border-slate-700 pb-2">
            I) Касательная к окружности
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {{-- Tasks 1-8: Square with circle through vertex --}}
            @php
                $squareCircleTasks = [
                    ['id' => 1, 'radius' => '2√5', 'answer' => '16'],
                    ['id' => 2, 'radius' => '3√5', 'answer' => '36'],
                    ['id' => 3, 'radius' => '√10', 'answer' => '8'],
                    ['id' => 4, 'radius' => '√5/2', 'answer' => '1'],
                    ['id' => 5, 'radius' => '1', 'answer' => '0.8'],
                    ['id' => 6, 'radius' => '3', 'answer' => '7.2'],
                    ['id' => 7, 'radius' => '0.5', 'answer' => '0.2'],
                    ['id' => 8, 'radius' => '1.5', 'answer' => '1.8'],
                ];
            @endphp

            @foreach($squareCircleTasks as $task)
            <div x-data="{
                // Концептуальная диаграмма: квадрат 60x60, сдвинут вверх чтобы окружность поместилась
                A: { x: 50, y: 25 },
                B: { x: 110, y: 25 },
                C: { x: 110, y: 85 },
                D: { x: 50, y: 85 },
                O: { x: 80, y: 85 },
                // Радиус подобран визуально: окружность проходит через A и помещается в viewBox
                R: 67
            }" class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Точка O является серединой стороны CD квадрата ABCD. Радиус окружности с центром в точке O, проходящей через вершину A, равен ${{ $task['radius'] }}$. Найдите площадь квадрата ABCD.
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 160 160" class="w-full max-w-[150px] h-auto">
                        {{-- Circle (проходит через A визуально) --}}
                        <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Square --}}
                        <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                            fill="none" stroke="#dc2626" stroke-width="2.5"/>

                        {{-- Center O --}}
                        <circle :cx="O.x" :cy="O.y" r="4" fill="#3b82f6"/>

                        {{-- Radius line to A --}}
                        <line :x1="O.x" :y1="O.y" :x2="A.x" :y2="A.y"
                            stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,3"/>

                        {{-- Vertex A highlighted --}}
                        <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>

                        {{-- Labels --}}
                        <text :x="A.x - 10" :y="A.y - 6" fill="#60a5fa" font-size="13" class="geo-label">A</text>
                        <text :x="B.x + 5" :y="B.y - 6" fill="#60a5fa" font-size="13" class="geo-label">B</text>
                        <text :x="C.x + 5" :y="C.y + 12" fill="#60a5fa" font-size="13" class="geo-label">C</text>
                        <text :x="D.x - 10" :y="D.y + 12" fill="#60a5fa" font-size="13" class="geo-label">D</text>
                        <text :x="O.x" :y="O.y + 14" fill="#3b82f6" font-size="12" class="geo-label" text-anchor="middle">O</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach

            {{-- Tasks 9-12: Tangent angle --}}
            @php
                $tangentTasks = [
                    ['id' => 9, 'angle' => 56, 'answer' => '28'],
                    ['id' => 10, 'angle' => 42, 'answer' => '21'],
                    ['id' => 11, 'angle' => 86, 'answer' => '43'],
                    ['id' => 12, 'angle' => 38, 'answer' => '19'],
                ];
            @endphp

            @foreach($tangentTasks as $task)
            {{-- Геометрически корректные касательные (масштабировано на весь viewBox):
                 - O = центр окружности
                 - P = внешняя точка (пересечение касательных)
                 - A, B = точки касания (OA ⊥ PA, OB ⊥ PB)
                 - |PA| = |PB| (равные касательные из одной точки)
            --}}
            <div x-data="{
                O: { x: 70, y: 115 },
                R: 65,
                // Точки касания вычислены геометрически корректно
                A: { x: 120, y: 157 },
                B: { x: 70, y: 50 },
                P: { x: 210, y: 50 }
            }" class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Касательные в точках A и B к окружности с центром O пересекаются под углом {{ $task['angle'] }}°. Найдите угол ABO.
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 230 185" class="w-full max-w-[240px] h-auto">
                        {{-- Circle --}}
                        <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#3b82f6" stroke-width="2.5"/>

                        {{-- Line AB (соединяет точки касания) --}}
                        <line :x1="A.x" :y1="A.y" :x2="B.x" :y2="B.y"
                            stroke="#dc2626" stroke-width="2.5"/>

                        {{-- Tangent lines (касаются окружности в одной точке) --}}
                        <line :x1="A.x" :y1="A.y" :x2="P.x" :y2="P.y"
                            stroke="#10b981" stroke-width="2.5"/>
                        <line :x1="B.x" :y1="B.y" :x2="P.x" :y2="P.y"
                            stroke="#10b981" stroke-width="2.5"/>

                        {{-- Radii to tangent points --}}
                        <line :x1="O.x" :y1="O.y" :x2="A.x" :y2="A.y"
                            stroke="#f59e0b" stroke-width="2"/>
                        <line :x1="O.x" :y1="O.y" :x2="B.x" :y2="B.y"
                            stroke="#f59e0b" stroke-width="2"/>

                        {{-- Points --}}
                        <circle :cx="O.x" :cy="O.y" r="5" fill="#3b82f6"/>
                        <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                        <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                        <circle :cx="P.x" :cy="P.y" r="5" fill="#10b981"/>

                        {{-- Labels --}}
                        <text :x="O.x - 20" :y="O.y + 6" fill="#3b82f6" font-size="16" class="geo-label">O</text>
                        <text :x="A.x + 8" :y="A.y + 16" fill="#60a5fa" font-size="16" class="geo-label">A</text>
                        <text :x="B.x - 6" :y="B.y - 14" fill="#60a5fa" font-size="16" class="geo-label">B</text>
                        <text :x="P.x + 8" :y="P.y + 6" fill="#10b981" font-size="16" class="geo-label">P</text>

                        {{-- Angle arc at P (внутри угла между касательными) --}}
                        <path d="M 185,50 A 25 25 0 0 1 194,69" fill="none" stroke="#f59e0b" stroke-width="2"/>
                        <text x="172" y="72" fill="#f59e0b" font-size="14" class="geo-label">{{ $task['angle'] }}°</text>

                        {{-- Angle arc at B (искомый угол ABO) с зелёным ? --}}
                        <path d="M 78,65 A 18 18 0 0 1 70,68" fill="none" stroke="#10b981" stroke-width="2"/>
                        <text x="82" y="78" fill="#10b981" font-size="16" font-weight="bold">?</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}°
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ==================== II) Центральные и вписанные углы (13-28) ==================== --}}
    <div class="mb-12">
        <h2 class="text-xl font-bold text-rose-400 mb-6 border-b border-slate-700 pb-2">
            II) Центральные и вписанные углы
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {{-- Tasks 13-16: Inscribed angle from central --}}
            @php
                $inscribedAngleTasks = [
                    ['id' => 13, 'aob' => 59, 'answer' => '29.5'],
                    ['id' => 14, 'aob' => 47, 'answer' => '23.5'],
                    ['id' => 15, 'aob' => 113, 'answer' => '56.5'],
                    ['id' => 16, 'aob' => 173, 'answer' => '86.5'],
                ];
            @endphp

            @foreach($inscribedAngleTasks as $task)
            <div x-data="{
                O: { x: 100, y: 110 },
                R: 70,
                A: { x: 45, y: 145 },
                B: { x: 155, y: 145 },
                C: { x: 70, y: 55 }
            }" class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Треугольник ABC вписан в окружность с центром O. Точки O и C лежат в одной полуплоскости относительно прямой AB. Найдите угол ACB, если угол AOB равен {{ $task['aob'] }}°.
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 200 200" class="w-full max-w-[180px] h-auto">
                        {{-- Circle --}}
                        <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Triangle --}}
                        <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                            fill="none" stroke="#dc2626" stroke-width="2.5"/>

                        {{-- Central angle --}}
                        <line :x1="O.x" :y1="O.y" :x2="A.x" :y2="A.y" stroke="#f59e0b" stroke-width="1.5"/>
                        <line :x1="O.x" :y1="O.y" :x2="B.x" :y2="B.y" stroke="#f59e0b" stroke-width="1.5"/>

                        {{-- Points --}}
                        <circle :cx="O.x" :cy="O.y" r="4" fill="#3b82f6"/>
                        <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                        <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                        <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>

                        {{-- Labels --}}
                        <text :x="O.x + 8" :y="O.y + 5" fill="#3b82f6" font-size="14" class="geo-label">O</text>
                        <text :x="A.x - 12" :y="A.y + 12" fill="#60a5fa" font-size="14" class="geo-label">A</text>
                        <text :x="B.x + 6" :y="B.y + 12" fill="#60a5fa" font-size="14" class="geo-label">B</text>
                        <text :x="C.x - 5" :y="C.y - 10" fill="#60a5fa" font-size="14" class="geo-label">C</text>

                        {{-- Angle label --}}
                        <text x="100" y="135" fill="#f59e0b" font-size="11" class="geo-label" text-anchor="middle">{{ $task['aob'] }}°</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}°
                </div>
            </div>
            @endforeach

            {{-- Tasks 17-24: Diameters AC and BD --}}
            @php
                $diameterTasks = [
                    ['id' => 17, 'acb' => 19, 'find' => 'AOD', 'answer' => '38'],
                    ['id' => 18, 'acb' => 16, 'find' => 'AOD', 'answer' => '32'],
                    ['id' => 19, 'aod' => 146, 'find' => 'ACB', 'answer' => '73'],
                    ['id' => 20, 'aod' => 108, 'find' => 'ACB', 'answer' => '54'],
                    ['id' => 21, 'acb' => 54, 'find' => 'AOD', 'answer' => '108'],
                    ['id' => 22, 'acb' => 78, 'find' => 'AOD', 'answer' => '156'],
                    ['id' => 23, 'aod' => 42, 'find' => 'ACB', 'answer' => '21'],
                    ['id' => 24, 'aod' => 50, 'find' => 'ACB', 'answer' => '25'],
                ];
            @endphp

            @foreach($diameterTasks as $task)
            <div x-data="{
                O: { x: 100, y: 100 },
                R: 60,
                A: { x: 40, y: 100 },
                C: { x: 160, y: 100 },
                B: { x: 70, y: 48 },
                D: { x: 130, y: 152 }
            }" class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        @if(isset($task['acb']))
                            AC и BD – диаметры окружности с центром O. Угол ACB равен {{ $task['acb'] }}°. Найдите угол AOD.
                        @else
                            В окружности с центром O отрезки AC и BD – диаметры. Угол AOD равен {{ $task['aod'] }}°. Найдите угол ACB.
                        @endif
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 200 200" class="w-full max-w-[180px] h-auto">
                        {{-- Circle --}}
                        <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Diameters --}}
                        <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#dc2626" stroke-width="2"/>
                        <line :x1="B.x" :y1="B.y" :x2="D.x" :y2="D.y" stroke="#dc2626" stroke-width="2"/>

                        {{-- Chord BC --}}
                        <line :x1="B.x" :y1="B.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="1.5"/>

                        {{-- Center --}}
                        <circle :cx="O.x" :cy="O.y" r="4" fill="#3b82f6"/>

                        {{-- Vertices --}}
                        <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                        <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                        <circle :cx="C.x" :cy="C.y" r="4" fill="#dc2626"/>
                        <circle :cx="D.x" :cy="D.y" r="4" fill="#dc2626"/>

                        {{-- Labels --}}
                        <text :x="A.x - 15" :y="A.y + 5" fill="#60a5fa" font-size="14" class="geo-label">A</text>
                        <text :x="B.x - 5" :y="B.y - 10" fill="#60a5fa" font-size="14" class="geo-label">B</text>
                        <text :x="C.x + 8" :y="C.y + 5" fill="#60a5fa" font-size="14" class="geo-label">C</text>
                        <text :x="D.x - 5" :y="D.y + 15" fill="#60a5fa" font-size="14" class="geo-label">D</text>
                        <text :x="O.x + 8" :y="O.y - 8" fill="#3b82f6" font-size="13" class="geo-label">O</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}°
                </div>
            </div>
            @endforeach

            {{-- Tasks 25-28: Points on opposite sides of diameter --}}
            @php
                $oppositeTasks = [
                    ['id' => 25, 'nba' => 32, 'answer' => '58'],
                    ['id' => 26, 'nba' => 43, 'answer' => '47'],
                    ['id' => 27, 'nba' => 71, 'answer' => '19'],
                    ['id' => 28, 'nba' => 68, 'answer' => '22'],
                ];
            @endphp

            @foreach($oppositeTasks as $task)
            <div x-data="{
                O: { x: 100, y: 100 },
                R: 60,
                A: { x: 40, y: 100 },
                B: { x: 160, y: 100 },
                N: { x: 80, y: 45 },
                M: { x: 120, y: 155 }
            }" class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        На окружности по разные стороны от диаметра AB взяты точки M и N. Известно, что $\angle NBA = {{ $task['nba'] }}°$. Найдите угол NMB.
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 200 200" class="w-full max-w-[180px] h-auto">
                        {{-- Circle --}}
                        <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Diameter AB --}}
                        <line :x1="A.x" :y1="A.y" :x2="B.x" :y2="B.y" stroke="#dc2626" stroke-width="2"/>

                        {{-- Lines to N --}}
                        <line :x1="N.x" :y1="N.y" :x2="A.x" :y2="A.y" stroke="#f59e0b" stroke-width="1.5"/>
                        <line :x1="N.x" :y1="N.y" :x2="B.x" :y2="B.y" stroke="#f59e0b" stroke-width="1.5"/>

                        {{-- Lines to M --}}
                        <line :x1="M.x" :y1="M.y" :x2="N.x" :y2="N.y" stroke="#10b981" stroke-width="1.5"/>
                        <line :x1="M.x" :y1="M.y" :x2="B.x" :y2="B.y" stroke="#10b981" stroke-width="1.5"/>

                        {{-- Points --}}
                        <circle :cx="A.x" :cy="A.y" r="4" fill="#dc2626"/>
                        <circle :cx="B.x" :cy="B.y" r="4" fill="#dc2626"/>
                        <circle :cx="N.x" :cy="N.y" r="4" fill="#f59e0b"/>
                        <circle :cx="M.x" :cy="M.y" r="4" fill="#10b981"/>

                        {{-- Labels --}}
                        <text :x="A.x - 12" :y="A.y + 5" fill="#60a5fa" font-size="14" class="geo-label">A</text>
                        <text :x="B.x + 8" :y="B.y + 5" fill="#60a5fa" font-size="14" class="geo-label">B</text>
                        <text :x="N.x - 5" :y="N.y - 10" fill="#60a5fa" font-size="14" class="geo-label">N</text>
                        <text :x="M.x - 5" :y="M.y + 15" fill="#60a5fa" font-size="14" class="geo-label">M</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}°
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ==================== III) Вписанная окружность (29-66) ==================== --}}
    <div class="mb-12">
        <h2 class="text-xl font-bold text-rose-400 mb-6 border-b border-slate-700 pb-2">
            III) Вписанная окружность
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {{-- Tasks 29-34: Inscribed circle in trapezoid --}}
            @php
                $trapezoidTasks = [
                    ['id' => 29, 'r' => 18, 'type' => 'трапецию', 'answer' => '36'],
                    ['id' => 30, 'r' => 26, 'type' => 'трапецию', 'answer' => '52'],
                    ['id' => 31, 'r' => 28, 'type' => 'прямоугольную трапецию', 'answer' => '56'],
                    ['id' => 32, 'r' => 32, 'type' => 'прямоугольную трапецию', 'answer' => '64'],
                    ['id' => 33, 'r' => 30, 'type' => 'равнобедренную трапецию', 'answer' => '60'],
                    ['id' => 34, 'r' => 44, 'type' => 'равнобедренную трапецию', 'answer' => '88'],
                ];
            @endphp

            @foreach($trapezoidTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Радиус окружности, вписанной в {{ $task['type'] }}, равен {{ $task['r'] }}. Найдите высоту этой трапеции.
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 200 140" class="w-full max-w-[180px] h-auto">
                        {{-- Trapezoid --}}
                        <polygon points="30,100 60,40 140,40 170,100"
                            fill="none" stroke="#dc2626" stroke-width="2.5"/>

                        {{-- Inscribed circle --}}
                        <circle cx="100" cy="70" r="30" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Height indicator --}}
                        <line x1="100" y1="40" x2="100" y2="100" stroke="#10b981" stroke-width="1.5" stroke-dasharray="4,3"/>

                        {{-- Labels --}}
                        <text x="108" y="75" fill="#f59e0b" font-size="12" class="geo-label">r={{ $task['r'] }}</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach

            {{-- Tasks 35-38: Inscribed circle in square (find radius) --}}
            @php
                $squareInscribedTasks = [
                    ['id' => 35, 'side' => 16, 'answer' => '8'],
                    ['id' => 36, 'side' => 22, 'answer' => '11'],
                    ['id' => 37, 'side' => 34, 'answer' => '17'],
                    ['id' => 38, 'side' => 62, 'answer' => '31'],
                ];
            @endphp

            @foreach($squareInscribedTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Сторона квадрата равна {{ $task['side'] }}. Найдите радиус окружности, вписанной в этот квадрат.
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 160 160" class="w-full max-w-[140px] h-auto">
                        {{-- Square --}}
                        <rect x="30" y="30" width="100" height="100" fill="none" stroke="#dc2626" stroke-width="2.5"/>

                        {{-- Inscribed circle --}}
                        <circle cx="80" cy="80" r="50" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Side label --}}
                        <text x="80" y="145" fill="#f59e0b" font-size="11" class="geo-label" text-anchor="middle">a={{ $task['side'] }}</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach

            {{-- Tasks 39-42: Square area from inscribed radius --}}
            @php
                $squareAreaTasks = [
                    ['id' => 39, 'r' => 40, 'answer' => '6400'],
                    ['id' => 40, 'r' => 9, 'answer' => '324'],
                    ['id' => 41, 'r' => 18, 'answer' => '1296'],
                    ['id' => 42, 'r' => 7, 'answer' => '196'],
                ];
            @endphp

            @foreach($squareAreaTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Найдите площадь квадрата, описанного вокруг окружности радиуса {{ $task['r'] }}.
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 160 160" class="w-full max-w-[140px] h-auto">
                        {{-- Square --}}
                        <rect x="30" y="30" width="100" height="100" fill="none" stroke="#dc2626" stroke-width="2.5"/>

                        {{-- Circle --}}
                        <circle cx="80" cy="80" r="50" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Radius --}}
                        <line x1="80" y1="80" x2="130" y2="80" stroke="#f59e0b" stroke-width="1.5"/>
                        <text x="105" y="72" fill="#f59e0b" font-size="11" class="geo-label">r={{ $task['r'] }}</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach

            {{-- Tasks 43-46: Square diagonal from inscribed radius --}}
            @php
                $diagonalTasks = [
                    ['id' => 43, 'r' => '6√2', 'answer' => '24'],
                    ['id' => 44, 'r' => '14√2', 'answer' => '56'],
                    ['id' => 45, 'r' => '18√2', 'answer' => '72'],
                    ['id' => 46, 'r' => '24√2', 'answer' => '96'],
                ];
            @endphp

            @foreach($diagonalTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Радиус вписанной в квадрат окружности равен ${{ $task['r'] }}$. Найдите диагональ этого квадрата.
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 160 160" class="w-full max-w-[140px] h-auto">
                        {{-- Square --}}
                        <rect x="30" y="30" width="100" height="100" fill="none" stroke="#dc2626" stroke-width="2.5"/>

                        {{-- Circle --}}
                        <circle cx="80" cy="80" r="50" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Diagonal --}}
                        <line x1="30" y1="30" x2="130" y2="130" stroke="#10b981" stroke-width="1.5" stroke-dasharray="4,3"/>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach

            {{-- Tasks 47-54: Circumscribed trapezoid/quadrilateral --}}
            @php
                $circumscribedTasks = [
                    ['id' => 47, 'ab' => 7, 'bc' => 5, 'cd' => 17, 'type' => 'Трапеция', 'answer' => '19'],
                    ['id' => 48, 'ab' => 14, 'bc' => 13, 'cd' => 22, 'type' => 'Трапеция', 'answer' => '23'],
                    ['id' => 49, 'ab' => 10, 'bc' => 16, 'cd' => 12, 'type' => 'Трапеция', 'answer' => '6'],
                    ['id' => 50, 'ab' => 13, 'bc' => 14, 'cd' => 11, 'type' => 'Трапеция', 'answer' => '10'],
                    ['id' => 51, 'ab' => 5, 'bc' => 12, 'cd' => 16, 'type' => 'Четырёхугольник', 'answer' => '9'],
                    ['id' => 52, 'ab' => 8, 'bc' => 20, 'cd' => 17, 'type' => 'Четырёхугольник', 'answer' => '5'],
                    ['id' => 53, 'ab' => 11, 'bc' => 15, 'cd' => 12, 'type' => 'Четырёхугольник', 'answer' => '8'],
                    ['id' => 54, 'ab' => 14, 'bc' => 21, 'cd' => 23, 'type' => 'Четырёхугольник', 'answer' => '16'],
                ];
            @endphp

            @foreach($circumscribedTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        {{ $task['type'] }} ABCD описан{{ $task['type'] == 'Трапеция' ? 'а' : '' }} около окружности{{ $task['type'] == 'Трапеция' ? ', основания AD и BC' : '' }}. AB={{ $task['ab'] }}, BC={{ $task['bc'] }}, CD={{ $task['cd'] }}. Найдите AD.
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 180 140" class="w-full max-w-[160px] h-auto">
                        {{-- Quadrilateral --}}
                        <polygon points="30,110 50,35 130,35 150,110"
                            fill="none" stroke="#dc2626" stroke-width="2.5"/>

                        {{-- Circle --}}
                        <circle cx="90" cy="72" r="35" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Labels --}}
                        <text x="25" y="120" fill="#60a5fa" font-size="12" class="geo-label">A</text>
                        <text x="45" y="28" fill="#60a5fa" font-size="12" class="geo-label">B</text>
                        <text x="132" y="28" fill="#60a5fa" font-size="12" class="geo-label">C</text>
                        <text x="152" y="120" fill="#60a5fa" font-size="12" class="geo-label">D</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach

            {{-- Tasks 55-58: Triangle area from perimeter and inradius --}}
            @php
                $triangleAreaTasks = [
                    ['id' => 55, 'p' => 48, 'side' => 18, 'r' => 3, 'answer' => '72'],
                    ['id' => 56, 'p' => 56, 'side' => 19, 'r' => 5, 'answer' => '140'],
                    ['id' => 57, 'p' => 140, 'side' => 56, 'r' => 9, 'answer' => '630'],
                    ['id' => 58, 'p' => 71, 'side' => 21, 'r' => 6, 'answer' => '213'],
                ];
            @endphp

            @foreach($triangleAreaTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Периметр треугольника равен {{ $task['p'] }}, одна из сторон равна {{ $task['side'] }}, а радиус вписанной окружности равен {{ $task['r'] }}. Найдите площадь.
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 160 140" class="w-full max-w-[140px] h-auto">
                        {{-- Triangle --}}
                        <polygon points="80,20 20,120 140,120"
                            fill="none" stroke="#dc2626" stroke-width="2.5"/>

                        {{-- Inscribed circle --}}
                        <circle cx="80" cy="87" r="30" fill="none" stroke="#3b82f6" stroke-width="2"/>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach

            {{-- Tasks 59-66: Equilateral triangle and inscribed circle --}}
            @php
                $equilateralInscribedTasks = [
                    ['id' => 59, 'side' => '6√3', 'find' => 'radius', 'answer' => '3'],
                    ['id' => 60, 'side' => '10√3', 'find' => 'radius', 'answer' => '5'],
                    ['id' => 61, 'side' => '18√3', 'find' => 'radius', 'answer' => '9'],
                    ['id' => 62, 'side' => '20√3', 'find' => 'radius', 'answer' => '10'],
                    ['id' => 63, 'r' => '5√3', 'find' => 'side', 'answer' => '30'],
                    ['id' => 64, 'r' => '7√3', 'find' => 'side', 'answer' => '42'],
                    ['id' => 65, 'r' => '11√3', 'find' => 'side', 'answer' => '66'],
                    ['id' => 66, 'r' => '9√3', 'find' => 'side', 'answer' => '54'],
                ];
            @endphp

            @foreach($equilateralInscribedTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        @if($task['find'] == 'radius')
                            Сторона равностороннего треугольника равна ${{ $task['side'] }}$. Найдите радиус вписанной окружности.
                        @else
                            Радиус окружности, вписанной в равносторонний треугольник, равен ${{ $task['r'] }}$. Найдите сторону.
                        @endif
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 160 140" class="w-full max-w-[140px] h-auto">
                        {{-- Equilateral triangle --}}
                        <polygon points="80,15 20,120 140,120"
                            fill="none" stroke="#dc2626" stroke-width="2.5"/>

                        {{-- Inscribed circle --}}
                        <circle cx="80" cy="85" r="32" fill="none" stroke="#3b82f6" stroke-width="2"/>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ==================== IV) Описанная окружность (67-114) ==================== --}}
    <div class="mb-12">
        <h2 class="text-xl font-bold text-rose-400 mb-6 border-b border-slate-700 pb-2">
            IV) Описанная окружность
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {{-- Tasks 67-74: Inscribed quadrilateral angles --}}
            @php
                $inscribedQuadTasks = [
                    ['id' => 67, 'abc' => 134, 'cad' => 81, 'find' => 'ABD', 'answer' => '53'],
                    ['id' => 68, 'abc' => 120, 'cad' => 74, 'find' => 'ABD', 'answer' => '46'],
                    ['id' => 69, 'abc' => 70, 'cad' => 49, 'find' => 'ABD', 'answer' => '21'],
                    ['id' => 70, 'abc' => 80, 'cad' => 34, 'find' => 'ABD', 'answer' => '46'],
                    ['id' => 71, 'abd' => 51, 'cad' => 42, 'find' => 'ABC', 'answer' => '93'],
                    ['id' => 72, 'abd' => 16, 'cad' => 32, 'find' => 'ABC', 'answer' => '48'],
                    ['id' => 73, 'abd' => 78, 'cad' => 40, 'find' => 'ABC', 'answer' => '118'],
                    ['id' => 74, 'abd' => 39, 'cad' => 55, 'find' => 'ABC', 'answer' => '94'],
                ];
            @endphp

            @foreach($inscribedQuadTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        @if(isset($task['abc']))
                            Четырёхугольник ABCD вписан в окружность. Угол ABC равен {{ $task['abc'] }}°, угол CAD равен {{ $task['cad'] }}°. Найдите угол ABD.
                        @else
                            Четырёхугольник ABCD вписан в окружность. Угол ABD равен {{ $task['abd'] }}°, угол CAD равен {{ $task['cad'] }}°. Найдите угол ABC.
                        @endif
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 180 180" class="w-full max-w-[160px] h-auto">
                        {{-- Circle --}}
                        <circle cx="90" cy="90" r="65" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Quadrilateral --}}
                        <polygon points="35,60 55,150 145,150 155,60"
                            fill="none" stroke="#dc2626" stroke-width="2"/>

                        {{-- Diagonal --}}
                        <line x1="35" y1="60" x2="145" y2="150" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,3"/>

                        {{-- Labels --}}
                        <text x="25" y="55" fill="#60a5fa" font-size="12" class="geo-label">A</text>
                        <text x="45" y="165" fill="#60a5fa" font-size="12" class="geo-label">B</text>
                        <text x="148" y="165" fill="#60a5fa" font-size="12" class="geo-label">C</text>
                        <text x="158" y="55" fill="#60a5fa" font-size="12" class="geo-label">D</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}°
                </div>
            </div>
            @endforeach

            {{-- Tasks 75-86: Center on side --}}
            @php
                $centerOnSideTasks = [
                    ['id' => 75, 'bac' => 24, 'find' => 'ABC', 'answer' => '66'],
                    ['id' => 76, 'bac' => 17, 'find' => 'ABC', 'answer' => '73'],
                    ['id' => 77, 'bac' => 9, 'find' => 'ABC', 'answer' => '81'],
                    ['id' => 78, 'bac' => 7, 'find' => 'ABC', 'answer' => '83'],
                    ['id' => 79, 'r' => 14.5, 'bc' => 21, 'find' => 'AC', 'answer' => '20'],
                    ['id' => 80, 'r' => 6.5, 'bc' => 12, 'find' => 'AC', 'answer' => '5'],
                    ['id' => 81, 'r' => 25, 'bc' => 48, 'find' => 'AC', 'answer' => '14'],
                    ['id' => 82, 'r' => 13, 'bc' => 24, 'find' => 'AC', 'answer' => '10'],
                    ['id' => 83, 'r' => 15, 'ac' => 24, 'find' => 'BC', 'answer' => '18'],
                    ['id' => 84, 'r' => 10, 'ac' => 16, 'find' => 'BC', 'answer' => '12'],
                    ['id' => 85, 'r' => 8.5, 'ac' => 8, 'find' => 'BC', 'answer' => '15'],
                    ['id' => 86, 'r' => 20.5, 'ac' => 9, 'find' => 'BC', 'answer' => '40'],
                ];
            @endphp

            @foreach($centerOnSideTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        @if(isset($task['bac']))
                            Центр окружности, описанной около треугольника ABC, лежит на стороне AB. Найдите угол ABC, если угол BAC равен {{ $task['bac'] }}°.
                        @elseif(isset($task['bc']))
                            Центр окружности, описанной около треугольника ABC, лежит на стороне AB. Радиус окружности равен {{ $task['r'] }}. Найдите AC, если BC={{ $task['bc'] }}.
                        @else
                            Центр окружности, описанной около треугольника ABC, лежит на стороне AB. Радиус окружности равен {{ $task['r'] }}. Найдите BC, если AC={{ $task['ac'] }}.
                        @endif
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 180 150" class="w-full max-w-[160px] h-auto">
                        {{-- Circle --}}
                        <circle cx="90" cy="90" r="60" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Triangle with right angle at C --}}
                        <polygon points="30,90 150,90 90,40"
                            fill="none" stroke="#dc2626" stroke-width="2"/>

                        {{-- Labels --}}
                        <text x="20" y="95" fill="#60a5fa" font-size="12" class="geo-label">A</text>
                        <text x="152" y="95" fill="#60a5fa" font-size="12" class="geo-label">B</text>
                        <text x="90" y="30" fill="#60a5fa" font-size="12" class="geo-label">C</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}@if(isset($task['bac']))°@endif
                </div>
            </div>
            @endforeach

            {{-- Tasks 87-98: Inscribed quadrilateral/trapezoid opposite angles --}}
            @php
                $oppositeAngleTasks = [
                    ['id' => 87, 'a' => 56, 'find' => 'C', 'shape' => 'четырёхугольника', 'answer' => '124'],
                    ['id' => 88, 'a' => 71, 'find' => 'C', 'shape' => 'четырёхугольника', 'answer' => '109'],
                    ['id' => 89, 'a' => 37, 'find' => 'C', 'shape' => 'четырёхугольника', 'answer' => '143'],
                    ['id' => 90, 'a' => 33, 'find' => 'C', 'shape' => 'четырёхугольника', 'answer' => '147'],
                    ['id' => 91, 'a' => 111, 'find' => 'C', 'shape' => 'трапеции', 'answer' => '69'],
                    ['id' => 92, 'a' => 114, 'find' => 'C', 'shape' => 'трапеции', 'answer' => '66'],
                    ['id' => 93, 'a' => 81, 'find' => 'C', 'shape' => 'трапеции', 'answer' => '99'],
                    ['id' => 94, 'a' => 47, 'find' => 'C', 'shape' => 'трапеции', 'answer' => '133'],
                    ['id' => 95, 'a' => 66, 'find' => 'B', 'shape' => 'трапеции', 'answer' => '114'],
                    ['id' => 96, 'a' => 54, 'find' => 'B', 'shape' => 'трапеции', 'answer' => '126'],
                    ['id' => 97, 'a' => 79, 'find' => 'B', 'shape' => 'трапеции', 'answer' => '101'],
                    ['id' => 98, 'a' => 62, 'find' => 'B', 'shape' => 'трапеции', 'answer' => '118'],
                ];
            @endphp

            @foreach($oppositeAngleTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Угол A {{ $task['shape'] }} ABCD, вписанн{{ $task['shape'] == 'трапеции' ? 'ой' : 'ого' }} в окружность{{ $task['shape'] == 'трапеции' ? ' (основания AD и BC)' : '' }}, равен {{ $task['a'] }}°. Найдите угол {{ $task['find'] }}.
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 180 180" class="w-full max-w-[160px] h-auto">
                        {{-- Circle --}}
                        <circle cx="90" cy="90" r="65" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Quadrilateral --}}
                        @if($task['shape'] == 'трапеции')
                        <polygon points="30,130 60,50 120,50 150,130"
                            fill="none" stroke="#dc2626" stroke-width="2"/>
                        @else
                        <polygon points="35,60 55,150 145,150 155,60"
                            fill="none" stroke="#dc2626" stroke-width="2"/>
                        @endif

                        {{-- Labels --}}
                        @if($task['shape'] == 'трапеции')
                        <text x="20" y="140" fill="#60a5fa" font-size="12" class="geo-label">A</text>
                        <text x="55" y="42" fill="#60a5fa" font-size="12" class="geo-label">B</text>
                        <text x="122" y="42" fill="#60a5fa" font-size="12" class="geo-label">C</text>
                        <text x="152" y="140" fill="#60a5fa" font-size="12" class="geo-label">D</text>
                        @else
                        <text x="25" y="55" fill="#60a5fa" font-size="12" class="geo-label">A</text>
                        <text x="45" y="165" fill="#60a5fa" font-size="12" class="geo-label">B</text>
                        <text x="148" y="165" fill="#60a5fa" font-size="12" class="geo-label">C</text>
                        <text x="158" y="55" fill="#60a5fa" font-size="12" class="geo-label">D</text>
                        @endif
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}°
                </div>
            </div>
            @endforeach

            {{-- Tasks 99-106: Square and circumscribed circle --}}
            @php
                $squareCircumscribedTasks = [
                    ['id' => 99, 'side' => '8√2', 'find' => 'R', 'answer' => '8'],
                    ['id' => 100, 'side' => '12√2', 'find' => 'R', 'answer' => '12'],
                    ['id' => 101, 'side' => '24√2', 'find' => 'R', 'answer' => '24'],
                    ['id' => 102, 'side' => '38√2', 'find' => 'R', 'answer' => '38'],
                    ['id' => 103, 'r' => '22√2', 'find' => 'side', 'answer' => '44'],
                    ['id' => 104, 'r' => '26√2', 'find' => 'side', 'answer' => '52'],
                    ['id' => 105, 'r' => '34√2', 'find' => 'side', 'answer' => '68'],
                    ['id' => 106, 'r' => '28√2', 'find' => 'side', 'answer' => '56'],
                ];
            @endphp

            @foreach($squareCircumscribedTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        @if($task['find'] == 'R')
                            Сторона квадрата равна ${{ $task['side'] }}$. Найдите радиус описанной окружности.
                        @else
                            Радиус описанной около квадрата окружности равен ${{ $task['r'] }}$. Найдите сторону.
                        @endif
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 160 160" class="w-full max-w-[140px] h-auto">
                        {{-- Circle --}}
                        <circle cx="80" cy="80" r="56" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Square --}}
                        <rect x="40" y="40" width="80" height="80" fill="none" stroke="#dc2626" stroke-width="2.5"/>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach

            {{-- Tasks 107-114: Equilateral triangle and circumscribed circle --}}
            @php
                $equilateralCircumscribedTasks = [
                    ['id' => 107, 'side' => '4√3', 'find' => 'R', 'answer' => '4'],
                    ['id' => 108, 'side' => '8√3', 'find' => 'R', 'answer' => '8'],
                    ['id' => 109, 'side' => '14√3', 'find' => 'R', 'answer' => '14'],
                    ['id' => 110, 'side' => '16√3', 'find' => 'R', 'answer' => '16'],
                    ['id' => 111, 'r' => '3√3', 'find' => 'side', 'answer' => '9'],
                    ['id' => 112, 'r' => '5√3', 'find' => 'side', 'answer' => '15'],
                    ['id' => 113, 'r' => '7√3', 'find' => 'side', 'answer' => '21'],
                    ['id' => 114, 'r' => '9√3', 'find' => 'side', 'answer' => '27'],
                ];
            @endphp

            @foreach($equilateralCircumscribedTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        @if($task['find'] == 'R')
                            Сторона равностороннего треугольника равна ${{ $task['side'] }}$. Найдите радиус описанной окружности.
                        @else
                            Радиус описанной окружности равностороннего треугольника равен ${{ $task['r'] }}$. Найдите сторону.
                        @endif
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 160 150" class="w-full max-w-[140px] h-auto">
                        {{-- Circle --}}
                        <circle cx="80" cy="80" r="55" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Triangle --}}
                        <polygon points="80,25 32,120 128,120"
                            fill="none" stroke="#dc2626" stroke-width="2.5"/>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ==================== V) Расширенная теорема синусов (115-126) ==================== --}}
    <div class="mb-12">
        <h2 class="text-xl font-bold text-rose-400 mb-6 border-b border-slate-700 pb-2">
            V) Расширенная теорема синусов
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @php
                $sineTasks = [
                    ['id' => 115, 'c' => 45, 'ab' => '8√2', 'answer' => '8'],
                    ['id' => 116, 'c' => 45, 'ab' => '6√2', 'answer' => '6'],
                    ['id' => 117, 'c' => 30, 'ab' => '26', 'answer' => '26'],
                    ['id' => 118, 'c' => 30, 'ab' => '16', 'answer' => '16'],
                    ['id' => 119, 'c' => 60, 'ab' => '12√3', 'answer' => '12'],
                    ['id' => 120, 'c' => 60, 'ab' => '10√3', 'answer' => '10'],
                    ['id' => 121, 'c' => 120, 'ab' => '18√3', 'answer' => '18'],
                    ['id' => 122, 'c' => 120, 'ab' => '22√3', 'answer' => '22'],
                    ['id' => 123, 'c' => 135, 'ab' => '14√2', 'answer' => '14'],
                    ['id' => 124, 'c' => 135, 'ab' => '16√2', 'answer' => '16'],
                    ['id' => 125, 'c' => 150, 'ab' => '20', 'answer' => '20'],
                    ['id' => 126, 'c' => 150, 'ab' => '24', 'answer' => '24'],
                ];
            @endphp

            @foreach($sineTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        В треугольнике ABC угол C равен {{ $task['c'] }}°, $AB = {{ $task['ab'] }}$. Найдите радиус описанной окружности.
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-3 flex justify-center">
                    <svg viewBox="0 0 180 160" class="w-full max-w-[160px] h-auto">
                        {{-- Circle --}}
                        <circle cx="90" cy="85" r="60" fill="none" stroke="#3b82f6" stroke-width="2"/>

                        {{-- Triangle --}}
                        @if($task['c'] < 90)
                        <polygon points="40,130 140,130 90,30"
                            fill="none" stroke="#dc2626" stroke-width="2.5"/>
                        <text x="35" y="142" fill="#60a5fa" font-size="12" class="geo-label">A</text>
                        <text x="142" y="142" fill="#60a5fa" font-size="12" class="geo-label">B</text>
                        <text x="90" y="22" fill="#60a5fa" font-size="12" class="geo-label">C</text>
                        @else
                        <polygon points="50,50 130,50 90,140"
                            fill="none" stroke="#dc2626" stroke-width="2.5"/>
                        <text x="40" y="45" fill="#60a5fa" font-size="12" class="geo-label">A</text>
                        <text x="132" y="45" fill="#60a5fa" font-size="12" class="geo-label">B</text>
                        <text x="90" y="155" fill="#60a5fa" font-size="12" class="geo-label">C</text>
                        @endif

                        {{-- Angle label --}}
                        <text x="{{ $task['c'] < 90 ? 90 : 90 }}" y="{{ $task['c'] < 90 ? 55 : 125 }}" fill="#f59e0b" font-size="11" class="geo-label" text-anchor="middle">{{ $task['c'] }}°</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-center text-slate-500 text-sm mt-8 pb-8">
        <p>Всего заданий: 126 | Блок 1. ФИПИ</p>
        <p class="mt-2">
            <a href="/test" class="text-cyan-400 hover:text-cyan-300">← Вернуться к списку тем</a>
        </p>
    </div>
</div>

</body>
</html>
