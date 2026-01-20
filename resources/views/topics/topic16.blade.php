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
        .geo-label-bold {
            font-family: 'Times New Roman', serif;
            font-style: normal;
            font-weight: 700;
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
                @if($tid === '16')
                    <span class="px-2.5 py-1 rounded-lg bg-cyan-500 text-white font-bold text-xs">{{ $tid }}</span>
                @else
                    <a href="{{ route('topics.show', ['id' => ltrim($tid, '0')]) }}"
                       class="px-2.5 py-1 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition text-xs">{{ $tid }}</a>
                @endif
            @endforeach
        </div>

        <span class="text-slate-500 text-xs">126 заданий</span>
    </div>

    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">16. Окружность, круг и их элементы</h1>
        <p class="text-slate-400">Геометрия: окружности, касательные, вписанные углы</p>
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
                // 85% заполнение viewBox 220×200
                // Квадрат 76×76, O — середина CD, R=85
                A: { x: 72, y: 34 },
                B: { x: 148, y: 34 },
                C: { x: 148, y: 110 },
                D: { x: 72, y: 110 },
                O: { x: 110, y: 110 },
                R: 85
            }" class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Точка O является серединой стороны CD квадрата ABCD. Радиус окружности с центром в точке O, проходящей через вершину A, равен ${{ $task['radius'] }}$. Найдите площадь квадрата ABCD.
                    </div>
                </div>

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Circle (проходит через A визуально) --}}
                        <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Square --}}
                        <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Center O --}}
                        <circle :cx="O.x" :cy="O.y" r="5" fill="#5a9fcf"/>

                        {{-- Radius line to A --}}
                        <line :x1="O.x" :y1="O.y" :x2="A.x" :y2="A.y"
                            stroke="#d4a855" stroke-width="2" stroke-dasharray="5,4"/>

                        {{-- Vertex A highlighted --}}
                        <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>

                        {{-- Labels --}}
                        <text :x="A.x - 12" :y="A.y - 8" fill="#60a5fa" font-size="16" class="geo-label">A</text>
                        <text :x="B.x + 6" :y="B.y - 8" fill="#60a5fa" font-size="16" class="geo-label">B</text>
                        <text :x="C.x + 6" :y="C.y + 14" fill="#60a5fa" font-size="16" class="geo-label">C</text>
                        <text :x="D.x - 12" :y="D.y + 14" fill="#60a5fa" font-size="16" class="geo-label">D</text>
                        <text :x="O.x" :y="O.y + 18" fill="#5a9fcf" font-size="15" class="geo-label" text-anchor="middle">O</text>
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
                // 85% заполнение viewBox 220×200
                // O - центр, R=70, A и B - точки касания НА окружности
                O: { x: 75, y: 110 },
                R: 70,
                // A на окружности: угол 50° вниз от горизонтали (cos50°≈0.64, sin50°≈0.77)
                A: { x: 120, y: 164 },
                // B на окружности: вверх (угол -90°)
                B: { x: 75, y: 40 },
                P: { x: 195, y: 40 }
            }" class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Касательные в точках A и B к окружности с центром O пересекаются под углом {{ $task['angle'] }}°. Найдите угол ABO.
                    </div>
                </div>

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Circle --}}
                        <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Line AB (соединяет точки касания) --}}
                        <line :x1="A.x" :y1="A.y" :x2="B.x" :y2="B.y"
                            stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Tangent lines (касаются окружности в одной точке) --}}
                        <line :x1="A.x" :y1="A.y" :x2="P.x" :y2="P.y"
                            stroke="#5a9fcf" stroke-width="2.5"/>
                        <line :x1="B.x" :y1="B.y" :x2="P.x" :y2="P.y"
                            stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Radii to tangent points --}}
                        <line :x1="O.x" :y1="O.y" :x2="A.x" :y2="A.y"
                            stroke="#d4a855" stroke-width="2"/>
                        <line :x1="O.x" :y1="O.y" :x2="B.x" :y2="B.y"
                            stroke="#d4a855" stroke-width="2"/>

                        {{-- Points --}}
                        <circle :cx="O.x" :cy="O.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="P.x" :cy="P.y" r="5" fill="#5a9fcf"/>

                        {{-- Labels --}}
                        <text :x="O.x - 20" :y="O.y + 6" fill="#5a9fcf" font-size="16" class="geo-label">O</text>
                        <text :x="A.x + 8" :y="A.y + 16" fill="#60a5fa" font-size="16" class="geo-label">A</text>
                        <text :x="B.x - 6" :y="B.y - 14" fill="#60a5fa" font-size="16" class="geo-label">B</text>
                        <text :x="P.x + 8" :y="P.y + 6" fill="#5a9fcf" font-size="16" class="geo-label">P</text>

                        {{-- Angle arc at P (динамический) --}}
                        <path :d="makeAngleArc(P, A, B, 25)" fill="none" stroke="#d4a855" stroke-width="2"/>
                        <text :x="angleLabelPos(P, A, B, 40).x" :y="angleLabelPos(P, A, B, 40).y" fill="#d4a855" font-size="16" class="geo-label-bold" text-anchor="middle">{{ $task['angle'] }}°</text>

                        {{-- Angle arc at B (искомый угол ABO, динамический) --}}
                        <path :d="makeAngleArc(B, A, O, 18)" fill="none" stroke="#5a9fcf" stroke-width="2"/>
                        <text :x="angleLabelPos(B, A, O, 28).x" :y="angleLabelPos(B, A, O, 28).y" fill="#5a9fcf" font-size="16" font-weight="bold" text-anchor="middle">?</text>
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
                // 85% заполнение viewBox 220×200
                // Все точки A, B, C лежат НА окружности
                O: { x: 110, y: 100 },
                R: 80,
                // A: угол 150° (внизу слева)
                A: { x: 41, y: 140 },
                // B: угол 30° (внизу справа)
                B: { x: 179, y: 140 },
                // C: угол -120° (вверху слева)
                C: { x: 70, y: 31 }
            }" class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Треугольник ABC вписан в окружность с центром O. Точки O и C лежат в одной полуплоскости относительно прямой AB. Найдите угол ACB, если угол AOB равен {{ $task['aob'] }}°.
                    </div>
                </div>

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Circle --}}
                        <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Triangle --}}
                        <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Central angle --}}
                        <line :x1="O.x" :y1="O.y" :x2="A.x" :y2="A.y" stroke="#d4a855" stroke-width="2"/>
                        <line :x1="O.x" :y1="O.y" :x2="B.x" :y2="B.y" stroke="#d4a855" stroke-width="2"/>

                        {{-- Points --}}
                        <circle :cx="O.x" :cy="O.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                        {{-- Labels --}}
                        <text :x="O.x + 10" :y="O.y + 6" fill="#5a9fcf" font-size="16" class="geo-label">O</text>
                        <text :x="A.x - 14" :y="A.y + 14" fill="#60a5fa" font-size="16" class="geo-label">A</text>
                        <text :x="B.x + 8" :y="B.y + 14" fill="#60a5fa" font-size="16" class="geo-label">B</text>
                        <text :x="C.x - 16" :y="C.y - 8" fill="#60a5fa" font-size="16" class="geo-label">C</text>

                        {{-- Angle label (отступ минимум 20px от ближайшей линии) --}}
                        <text x="115" y="165" fill="#d4a855" font-size="16" class="geo-label-bold" text-anchor="middle">{{ $task['aob'] }}°</text>
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
                // 85% заполнение viewBox 220×200
                // A, C - горизонтальный диаметр; B, D - диаметр под углом
                // Все точки НА окружности с R=85
                O: { x: 110, y: 100 },
                R: 85,
                A: { x: 25, y: 100 },
                C: { x: 195, y: 100 },
                // B: угол ~-60° (вверху слева)
                B: { x: 67, y: 26 },
                // D: противоположная точка диаметра
                D: { x: 153, y: 174 }
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Circle --}}
                        <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Diameters --}}
                        <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#c8dce8" stroke-width="2.5"/>
                        <line :x1="B.x" :y1="B.y" :x2="D.x" :y2="D.y" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Chord BC --}}
                        <line :x1="B.x" :y1="B.y" :x2="C.x" :y2="C.y" stroke="#d4a855" stroke-width="2"/>

                        {{-- Center --}}
                        <circle :cx="O.x" :cy="O.y" r="5" fill="#5a9fcf"/>

                        {{-- Vertices --}}
                        <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="D.x" :cy="D.y" r="5" fill="#5a9fcf"/>

                        {{-- Labels - увеличены отступы --}}
                        <text :x="A.x - 18" :y="A.y + 5" fill="#60a5fa" font-size="16" class="geo-label">A</text>
                        <text :x="B.x - 16" :y="B.y - 6" fill="#60a5fa" font-size="16" class="geo-label">B</text>
                        <text :x="C.x + 10" :y="C.y + 6" fill="#60a5fa" font-size="16" class="geo-label">C</text>
                        <text :x="D.x + 8" :y="D.y + 16" fill="#60a5fa" font-size="16" class="geo-label">D</text>
                        <text :x="O.x + 10" :y="O.y - 10" fill="#5a9fcf" font-size="16" class="geo-label">O</text>
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
                // Все точки A, B, N, M НА окружности с R=85
                O: { x: 110, y: 105 },
                R: 85,
                A: { x: 25, y: 105 },
                B: { x: 195, y: 105 },
                // N: вверху слева (угол ~-110°)
                N: { x: 81, y: 25 },
                // M: внизу справа (угол ~70°)
                M: { x: 139, y: 185 }
            }" class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        На окружности по разные стороны от диаметра AB взяты точки M и N. Известно, что $\angle NBA = {{ $task['nba'] }}°$. Найдите угол NMB.
                    </div>
                </div>

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 210" class="w-full max-w-[250px] h-auto">
                        {{-- Circle --}}
                        <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Diameter AB --}}
                        <line :x1="A.x" :y1="A.y" :x2="B.x" :y2="B.y" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Lines to N --}}
                        <line :x1="N.x" :y1="N.y" :x2="A.x" :y2="A.y" stroke="#d4a855" stroke-width="2"/>
                        <line :x1="N.x" :y1="N.y" :x2="B.x" :y2="B.y" stroke="#d4a855" stroke-width="2"/>

                        {{-- Lines to M --}}
                        <line :x1="M.x" :y1="M.y" :x2="N.x" :y2="N.y" stroke="#5a9fcf" stroke-width="2"/>
                        <line :x1="M.x" :y1="M.y" :x2="B.x" :y2="B.y" stroke="#5a9fcf" stroke-width="2"/>

                        {{-- Points --}}
                        <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="N.x" :cy="N.y" r="5" fill="#d4a855"/>
                        <circle :cx="M.x" :cy="M.y" r="5" fill="#5a9fcf"/>

                        {{-- Labels --}}
                        <text :x="A.x - 18" :y="A.y + 5" fill="#60a5fa" font-size="16" class="geo-label">A</text>
                        <text :x="B.x + 10" :y="B.y + 6" fill="#60a5fa" font-size="16" class="geo-label">B</text>
                        <text :x="N.x - 6" :y="N.y - 12" fill="#60a5fa" font-size="16" class="geo-label">N</text>
                        <text :x="M.x - 6" :y="M.y + 18" fill="#60a5fa" font-size="16" class="geo-label">M</text>
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
            {{-- Tasks 29-30: Inscribed circle in regular trapezoid (не равнобедренная) --}}
            @php
                $regularTrapezoidTasks = [
                    ['id' => 29, 'r' => 18, 'answer' => '36'],
                    ['id' => 30, 'r' => 26, 'answer' => '52'],
                ];
            @endphp

            @foreach($regularTrapezoidTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Радиус окружности, вписанной в трапецию, равен {{ $task['r'] }}. Найдите высоту этой трапеции.
                    </div>
                </div>

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Трапеция - 85% заполнение viewBox
                             center=(110,100), r=70, a×b=4900, a=45, b=109 --}}
                        <polygon points="1,170 65,30 155,30 219,170"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Inscribed circle: касается всех 4 сторон --}}
                        <circle cx="110" cy="100" r="70" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Radius indicator --}}
                        <line x1="110" y1="100" x2="110" y2="170" stroke="#d4a855" stroke-width="2.5"/>
                        <circle cx="110" cy="100" r="4" fill="#d4a855"/>

                        {{-- Labels --}}
                        <text x="122" y="145" fill="#d4a855" font-size="16" class="geo-label">r={{ $task['r'] }}</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach

            {{-- Tasks 31-32: Inscribed circle in right trapezoid (прямоугольная) --}}
            @php
                $rightTrapezoidTasks = [
                    ['id' => 31, 'r' => 28, 'answer' => '56'],
                    ['id' => 32, 'r' => 32, 'answer' => '64'],
                ];
            @endphp

            @foreach($rightTrapezoidTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Радиус окружности, вписанной в прямоугольную трапецию, равен {{ $task['r'] }}. Найдите высоту этой трапеции.
                    </div>
                </div>

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Прямоугольная трапеция - 85% заполнение viewBox
                             center=(110,100), r=70, left vertical at x=40 --}}
                        <polygon points="40,170 40,30 160,30 208,170"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Inscribed circle: касается всех 4 сторон --}}
                        <circle cx="110" cy="100" r="70" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Right angle marker (левый верхний угол) --}}
                        <path d="M 40,45 L 55,45 L 55,30" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                        {{-- Radius indicator --}}
                        <line x1="110" y1="100" x2="110" y2="170" stroke="#d4a855" stroke-width="2.5"/>
                        <circle cx="110" cy="100" r="4" fill="#d4a855"/>

                        {{-- Labels --}}
                        <text x="122" y="145" fill="#d4a855" font-size="16" class="geo-label">r={{ $task['r'] }}</text>
                    </svg>
                </div>

                <div class="mt-2 text-slate-500 text-xs">
                    <span class="text-emerald-400">Ответ:</span> {{ $task['answer'] }}
                </div>
            </div>
            @endforeach

            {{-- Tasks 33-34: Inscribed circle in isosceles trapezoid (равнобедренная) --}}
            @php
                $isoscelesTrapezoidTasks = [
                    ['id' => 33, 'r' => 30, 'answer' => '60'],
                    ['id' => 34, 'r' => 44, 'answer' => '88'],
                ];
            @endphp

            @foreach($isoscelesTrapezoidTasks as $task)
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700">
                <div class="flex items-start gap-2 mb-3">
                    <span class="text-red-400 font-bold">{{ $task['id'] }}</span>
                    <div class="text-slate-200 text-sm">
                        Радиус окружности, вписанной в равнобедренную трапецию, равен {{ $task['r'] }}. Найдите высоту этой трапеции.
                    </div>
                </div>

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Равнобедренная трапеция - 85% заполнение viewBox
                             center=(110,100), r=70, a×b=4900, a=45, b=109 --}}
                        <polygon points="1,170 65,30 155,30 219,170"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Inscribed circle: касается всех 4 сторон --}}
                        <circle cx="110" cy="100" r="70" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Radius indicator --}}
                        <line x1="110" y1="100" x2="110" y2="170" stroke="#d4a855" stroke-width="2.5"/>
                        <circle cx="110" cy="100" r="4" fill="#d4a855"/>

                        {{-- Labels --}}
                        <text x="122" y="145" fill="#d4a855" font-size="16" class="geo-label">r={{ $task['r'] }}</text>
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Square - 85% заполнение: 170×170 --}}
                        <rect x="25" y="10" width="170" height="170" fill="none" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Inscribed circle --}}
                        <circle cx="110" cy="95" r="85" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Side label --}}
                        <text x="110" y="195" fill="#d4a855" font-size="16" class="geo-label-bold" text-anchor="middle">a={{ $task['side'] }}</text>
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Square - 85% заполнение: 170×170 --}}
                        <rect x="25" y="15" width="170" height="170" fill="none" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Circle --}}
                        <circle cx="110" cy="100" r="85" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Radius --}}
                        <line x1="110" y1="100" x2="195" y2="100" stroke="#d4a855" stroke-width="2"/>
                        <text x="152" y="90" fill="#d4a855" font-size="16" class="geo-label-bold">r={{ $task['r'] }}</text>
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Square - 85% заполнение: 170×170 --}}
                        <rect x="25" y="15" width="170" height="170" fill="none" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Circle --}}
                        <circle cx="110" cy="100" r="85" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Diagonal --}}
                        <line x1="25" y1="15" x2="195" y2="185" stroke="#5a9fcf" stroke-width="2" stroke-dasharray="5,4"/>
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
                    ['id' => 47, 'ab' => 7, 'bc' => 5, 'cd' => 17, 'type' => 'Трапеция', 'answer' => '19', 'shape' => 1],
                    ['id' => 48, 'ab' => 14, 'bc' => 13, 'cd' => 22, 'type' => 'Трапеция', 'answer' => '23', 'shape' => 1],
                    ['id' => 49, 'ab' => 10, 'bc' => 16, 'cd' => 12, 'type' => 'Трапеция', 'answer' => '6', 'shape' => 2],
                    ['id' => 50, 'ab' => 13, 'bc' => 14, 'cd' => 11, 'type' => 'Трапеция', 'answer' => '10', 'shape' => 2],
                    ['id' => 51, 'ab' => 5, 'bc' => 12, 'cd' => 16, 'type' => 'Четырёхугольник', 'answer' => '9', 'shape' => 3],
                    ['id' => 52, 'ab' => 8, 'bc' => 20, 'cd' => 17, 'type' => 'Четырёхугольник', 'answer' => '5', 'shape' => 3],
                    ['id' => 53, 'ab' => 11, 'bc' => 15, 'cd' => 12, 'type' => 'Четырёхугольник', 'answer' => '8', 'shape' => 4],
                    ['id' => 54, 'ab' => 14, 'bc' => 21, 'cd' => 23, 'type' => 'Четырёхугольник', 'answer' => '16', 'shape' => 4],
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        @if($task['shape'] == 1)
                        {{-- Форма 1: Трапеция, широкое основание внизу (AD > BC)
                             85% заполнение: r=70, center=(110,100), a×b=4900, a=45, b=109 --}}
                        <polygon points="1,170 65,30 155,30 219,170"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
                        <circle cx="110" cy="100" r="70" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>
                        <text x="-12" y="183" fill="#60a5fa" font-size="15" class="geo-label">A</text>
                        <text x="57" y="18" fill="#60a5fa" font-size="15" class="geo-label">B</text>
                        <text x="157" y="18" fill="#60a5fa" font-size="15" class="geo-label">C</text>
                        <text x="221" y="183" fill="#60a5fa" font-size="15" class="geo-label">D</text>
                        @elseif($task['shape'] == 2)
                        {{-- Форма 2: Трапеция, широкое основание вверху (BC > AD)
                             85% заполнение: r=70, a=109, b=45 --}}
                        <polygon points="65,170 1,30 219,30 155,170"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
                        <circle cx="110" cy="100" r="70" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>
                        <text x="52" y="185" fill="#60a5fa" font-size="15" class="geo-label">A</text>
                        <text x="-12" y="23" fill="#60a5fa" font-size="15" class="geo-label">B</text>
                        <text x="221" y="23" fill="#60a5fa" font-size="15" class="geo-label">C</text>
                        <text x="157" y="185" fill="#60a5fa" font-size="15" class="geo-label">D</text>
                        @elseif($task['shape'] == 3)
                        {{-- Форма 3: Прямоугольный четырёхугольник
                             85% заполнение: r=70, левая сторона вертикальная x=40 --}}
                        <polygon points="40,170 40,30 160,30 208,170"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
                        <circle cx="110" cy="100" r="70" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>
                        <text x="27" y="185" fill="#60a5fa" font-size="15" class="geo-label">A</text>
                        <text x="27" y="23" fill="#60a5fa" font-size="15" class="geo-label">B</text>
                        <text x="162" y="23" fill="#60a5fa" font-size="15" class="geo-label">C</text>
                        <text x="210" y="185" fill="#60a5fa" font-size="15" class="geo-label">D</text>
                        @else
                        {{-- Форма 4: Симметричная трапеция
                             85% заполнение: r=70, center=(110,100)
                             Для касания боковых сторон: a×b=4900, a=85, b=58 --}}
                        <polygon points="25,170 52,30 168,30 195,170"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
                        <circle cx="110" cy="100" r="70" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>
                        <text x="12" y="185" fill="#60a5fa" font-size="15" class="geo-label">A</text>
                        <text x="40" y="18" fill="#60a5fa" font-size="15" class="geo-label">B</text>
                        <text x="170" y="18" fill="#60a5fa" font-size="15" class="geo-label">C</text>
                        <text x="197" y="185" fill="#60a5fa" font-size="15" class="geo-label">D</text>
                        @endif
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Triangle - 85% заполнение
                             A=(110,15), B=(15,185), C=(205,185)
                             Инцентр (110,130), r=55 касается всех сторон --}}
                        <polygon points="110,15 15,185 205,185"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Inscribed circle - касается всех 3 сторон --}}
                        <circle cx="110" cy="130" r="55" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Equilateral triangle - 85% заполнение
                             Инцентр (110,130), r=55 касается всех сторон --}}
                        <polygon points="110,15 15,185 205,185"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Inscribed circle - касается всех 3 сторон --}}
                        <circle cx="110" cy="130" r="55" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Circle - O=(110,100), R=85 --}}
                        <circle cx="110" cy="100" r="85" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Quadrilateral - все вершины НА окружности R=85
                             A: угол 205°, B: угол 125°, C: угол 55°, D: угол 335° --}}
                        <polygon points="33,64 62,170 158,170 187,64"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>

                        {{-- Diagonals AC and BD --}}
                        <line x1="33" y1="64" x2="158" y2="170" stroke="#d4a855" stroke-width="2"/>
                        <line x1="62" y1="170" x2="187" y2="64" stroke="#d4a855" stroke-width="2"/>

                        {{-- Labels --}}
                        <text x="18" y="58" fill="#60a5fa" font-size="15" class="geo-label">A</text>
                        <text x="50" y="188" fill="#60a5fa" font-size="15" class="geo-label">B</text>
                        <text x="160" y="188" fill="#60a5fa" font-size="15" class="geo-label">C</text>
                        <text x="192" y="58" fill="#60a5fa" font-size="15" class="geo-label">D</text>
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Circle - O=(110,110), R=85; A,B на диаметре (y=110), C на окружности --}}
                        <circle cx="110" cy="110" r="85" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Triangle: A и B на диаметре, C на окружности (разные формы)
                             Центр на AB означает прямой угол в C
                             Разные формы для разных заданий --}}
                        @if(($task['id'] - 75) % 4 == 0)
                        {{-- Форма 1: C левее центра, x=70, y=35 --}}
                        <polygon points="25,110 195,110 70,35"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
                        <text x="58" y="26" fill="#60a5fa" font-size="15" class="geo-label">C</text>
                        @elseif(($task['id'] - 75) % 4 == 1)
                        {{-- Форма 2: C правее центра, x=150, y=35 --}}
                        <polygon points="25,110 195,110 150,35"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
                        <text x="152" y="22" fill="#60a5fa" font-size="15" class="geo-label">C</text>
                        @elseif(($task['id'] - 75) % 4 == 2)
                        {{-- Форма 3: C ещё левее, x=55, y=45 --}}
                        <polygon points="25,110 195,110 55,45"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
                        <text x="40" y="38" fill="#60a5fa" font-size="15" class="geo-label">C</text>
                        @else
                        {{-- Форма 4: C ещё правее, x=165, y=45 --}}
                        <polygon points="25,110 195,110 165,45"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
                        <text x="168" y="38" fill="#60a5fa" font-size="15" class="geo-label">C</text>
                        @endif

                        {{-- Labels A и B --}}
                        <text x="10" y="116" fill="#60a5fa" font-size="15" class="geo-label">A</text>
                        <text x="198" y="116" fill="#60a5fa" font-size="15" class="geo-label">B</text>
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Circle - O=(110,100), R=85 --}}
                        <circle cx="110" cy="100" r="85" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Quadrilateral/Trapezoid - все вершины НА окружности R=85 --}}
                        @if($task['shape'] == 'трапеции')
                        {{-- Трапеция: A(150°), B(240°), C(300°), D(30°) на окружности --}}
                        <polygon points="36,142 68,26 152,26 184,142"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
                        <text x="20" y="152" fill="#60a5fa" font-size="15" class="geo-label">A</text>
                        <text x="55" y="16" fill="#60a5fa" font-size="15" class="geo-label">B</text>
                        <text x="155" y="16" fill="#60a5fa" font-size="15" class="geo-label">C</text>
                        <text x="187" y="152" fill="#60a5fa" font-size="15" class="geo-label">D</text>
                        @else
                        {{-- Четырёхугольник: A(205°), B(125°), C(55°), D(335°) --}}
                        <polygon points="33,64 62,170 158,170 187,64"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
                        <text x="18" y="58" fill="#60a5fa" font-size="15" class="geo-label">A</text>
                        <text x="50" y="188" fill="#60a5fa" font-size="15" class="geo-label">B</text>
                        <text x="160" y="188" fill="#60a5fa" font-size="15" class="geo-label">C</text>
                        <text x="192" y="58" fill="#60a5fa" font-size="15" class="geo-label">D</text>
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Circle - 85% заполнение: R=85 --}}
                        <circle cx="110" cy="100" r="85" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Square 120×120 inscribed in circle --}}
                        <rect x="50" y="40" width="120" height="120" fill="none" stroke="#c8dce8" stroke-width="2.5"/>
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Circle - O=(110,100), R=85 --}}
                        <circle cx="110" cy="100" r="85" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Equilateral triangle - все вершины НА окружности
                             A(270°)=(110,15), B(30°)=(184,143), C(150°)=(36,143) --}}
                        <polygon points="110,15 184,143 36,143"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
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

                <div class="rounded-lg p-3 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 220 200" class="w-full max-w-[250px] h-auto">
                        {{-- Circle - O=(110,100), R=85 --}}
                        <circle cx="110" cy="100" r="85" fill="none" stroke="#5a9fcf" stroke-width="2.5"/>

                        {{-- Triangle - все вершины НА окружности R=85 --}}
                        @if($task['c'] < 90)
                        {{-- Острый угол C: A(150°), B(30°), C(270°) на окружности --}}
                        <polygon points="36,143 184,143 110,15"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
                        <text x="20" y="153" fill="#60a5fa" font-size="15" class="geo-label">A</text>
                        <text x="188" y="153" fill="#60a5fa" font-size="15" class="geo-label">B</text>
                        <text x="110" y="8" fill="#60a5fa" font-size="15" class="geo-label" text-anchor="middle">C</text>
                        @else
                        {{-- Тупой угол C: A(210°), B(330°), C(90°) на окружности --}}
                        <polygon points="36,58 184,58 110,185"
                            fill="none" stroke="#c8dce8" stroke-width="2.5"/>
                        <text x="20" y="50" fill="#60a5fa" font-size="15" class="geo-label">A</text>
                        <text x="188" y="50" fill="#60a5fa" font-size="15" class="geo-label">B</text>
                        <text x="110" y="198" fill="#60a5fa" font-size="15" class="geo-label" text-anchor="middle">C</text>
                        @endif

                        {{-- Angle label --}}
                        <text x="110" y="{{ $task['c'] < 90 ? 58 : 155 }}" fill="#d4a855" font-size="16" class="geo-label-bold" text-anchor="middle">{{ $task['c'] }}°</text>
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
