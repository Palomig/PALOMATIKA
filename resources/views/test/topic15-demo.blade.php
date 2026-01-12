<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>15. Треугольники (DEMO v3) - GEOMETRY_SPEC</title>

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

{{-- ========================================
    GEOMETRY_SPEC — Все функции из CLAUDE.md
    ======================================== --}}
<script>
    // 1. Позиционирует подписи в направлении от центра фигуры
    function labelPos(point, center, distance = 22) {
        const dx = point.x - center.x;
        const dy = point.y - center.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        if (len === 0) return { x: point.x, y: point.y - distance };
        return {
            x: point.x + (dx / len) * distance,
            y: point.y + (dy / len) * distance
        };
    }

    // 2. Рисует дугу угла строго между двумя сторонами
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

    // 3. Рисует квадратик для прямого угла
    function rightAnglePath(vertex, p1, p2, size = 12) {
        const angle1 = Math.atan2(p1.y - vertex.y, p1.x - vertex.x);
        const angle2 = Math.atan2(p2.y - vertex.y, p2.x - vertex.x);
        const c1 = { x: vertex.x + size * Math.cos(angle1), y: vertex.y + size * Math.sin(angle1) };
        const c2 = { x: vertex.x + size * Math.cos(angle2), y: vertex.y + size * Math.sin(angle2) };
        const diag = { x: c1.x + size * Math.cos(angle2), y: c1.y + size * Math.sin(angle2) };
        return `M ${c1.x} ${c1.y} L ${diag.x} ${diag.y} L ${c2.x} ${c2.y}`;
    }

    // 4. Точка на отрезке (t=0 → p1, t=1 → p2, t=0.5 → середина)
    function pointOnLine(p1, p2, t) {
        return {
            x: p1.x + (p2.x - p1.x) * t,
            y: p1.y + (p2.y - p1.y) * t
        };
    }

    // 5. Подпись длины стороны — перпендикулярно отрезку
    function labelOnSegment(p1, p2, offset = 15, flipSide = false) {
        const mid = { x: (p1.x + p2.x) / 2, y: (p1.y + p2.y) / 2 };
        const dx = p2.x - p1.x;
        const dy = p2.y - p1.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        // Нормаль перпендикулярна отрезку
        let nx = -dy / len;
        let ny = dx / len;
        if (flipSide) { nx = -nx; ny = -ny; }
        return {
            x: mid.x + nx * offset,
            y: mid.y + ny * offset
        };
    }

    // 6. Позиция метки угла — ровно посередине между двумя сторонами
    //    bias: 0.5 = точная середина, <0.5 = ближе к p1, >0.5 = ближе к p2
    function angleLabelPos(vertex, p1, p2, labelRadius, bias = 0.5) {
        const angle1 = Math.atan2(p1.y - vertex.y, p1.x - vertex.x);
        const angle2 = Math.atan2(p2.y - vertex.y, p2.x - vertex.x);

        // Нормализуем разницу углов к диапазону (-π, π]
        let diff = angle2 - angle1;
        while (diff > Math.PI) diff -= 2 * Math.PI;
        while (diff < -Math.PI) diff += 2 * Math.PI;

        // Позиция = angle1 + bias * diff
        const midAngle = angle1 + diff * bias;

        return {
            x: vertex.x + labelRadius * Math.cos(midAngle),
            y: vertex.y + labelRadius * Math.sin(midAngle)
        };
    }

    // 7. Точка D на стороне BC для биссектрисы из A
    function bisectorPoint(A, B, C) {
        const AB = Math.sqrt((B.x - A.x)**2 + (B.y - A.y)**2);
        const AC = Math.sqrt((C.x - A.x)**2 + (C.y - A.y)**2);
        const t = AB / (AB + AC);
        return pointOnLine(B, C, t);
    }

    // Экспортируем в глобальную область
    window.labelPos = labelPos;
    window.makeAngleArc = makeAngleArc;
    window.rightAnglePath = rightAnglePath;
    window.pointOnLine = pointOnLine;
    window.labelOnSegment = labelOnSegment;
    window.angleLabelPos = angleLabelPos;
    window.bisectorPoint = bisectorPoint;
</script>

<div class="max-w-6xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">15. Треугольники (DEMO v3)</h1>
        <p class="text-slate-400 text-lg">Полная реализация GEOMETRY_SPEC из CLAUDE.md</p>
    </div>

    {{-- Navigation --}}
    <div class="flex justify-center gap-4 mb-8">
        <a href="{{ route('test.topic15') }}" class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600">
            ← Старая версия (PNG)
        </a>
        <a href="{{ route('test.topic15.interactive') }}" class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600">
            Интерактивная версия
        </a>
    </div>

    {{-- Блок 1. ФИПИ --}}
    <div class="mb-12">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-white">Блок 1. ФИПИ</h2>
        </div>

        {{-- I) Биссектриса --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-white">I) Биссектриса треугольника</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 1: угол BAC = 68°, найти BAD --}}
                <div x-data="task1Bisector()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">1</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что $\angle BAC = 68°$, AD – биссектриса. Найдите угол BAD.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            {{-- Треугольник ABC --}}
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Биссектриса AD (доходит до стороны BC!) --}}
                            <line :x1="A.x" :y1="A.y" :x2="D.x" :y2="D.y"
                                stroke="#10b981" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Дуга полного угла BAC — увеличенный радиус для места под текст --}}
                            <path :d="makeAngleArc(A, B, C, 45)" fill="none" stroke="#f59e0b" stroke-width="2"/>

                            {{-- Дуга искомого угла BAD --}}
                            <path :d="makeAngleArc(A, B, D, 30)" fill="none" stroke="#10b981" stroke-width="2"/>

                            {{-- Точки вершин --}}
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>
                            <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>

                            {{-- Подписи вершин (от центра) --}}
                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Подпись D (справа от точки D) --}}
                            <text :x="D.x + 14" :y="D.y - 8"
                                fill="#10b981" font-size="16" class="geo-label" text-anchor="start" dominant-baseline="middle">D</text>

                            {{-- Метка угла 68° — bias=0.6 смещает ниже (к биссектрисе но не на неё) --}}
                            <text :x="angleLabelPos(A, B, D, 62, 0.6).x" :y="angleLabelPos(A, B, D, 62, 0.6).y"
                                fill="#f59e0b" font-size="13" class="geo-label" text-anchor="middle" dominant-baseline="middle">68°</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 34°
                    </div>
                </div>

                {{-- Задача 2: угол BAC = 82° --}}
                <div x-data="task2Bisector()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">2</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что $\angle BAC = 82°$, AD – биссектриса. Найдите угол BAD.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="A.x" :y1="A.y" :x2="D.x" :y2="D.y"
                                stroke="#10b981" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Увеличенные радиусы дуг --}}
                            <path :d="makeAngleArc(A, B, C, 45)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(A, B, D, 30)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>
                            <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="D.x + 14" :y="D.y - 8" fill="#10b981" font-size="16" class="geo-label">D</text>

                            {{-- Метка угла 82° — bias=0.6 смещает ниже --}}
                            <text :x="angleLabelPos(A, B, D, 62, 0.6).x" :y="angleLabelPos(A, B, D, 62, 0.6).y"
                                fill="#f59e0b" font-size="13" class="geo-label" text-anchor="middle">82°</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 41°
                    </div>
                </div>

                {{-- Задача 3: угол BAC = 26° --}}
                <div x-data="task3Bisector()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">3</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что $\angle BAC = 26°$, AD – биссектриса. Найдите угол BAD.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="A.x" :y1="A.y" :x2="D.x" :y2="D.y"
                                stroke="#10b981" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="makeAngleArc(A, B, C, 50)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(A, B, D, 35)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>
                            <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="D.x + 14" :y="D.y - 8" fill="#10b981" font-size="16" class="geo-label">D</text>

                            <text :x="angleLabelPos(A, B, D, 68, 0.6).x" :y="angleLabelPos(A, B, D, 68, 0.6).y"
                                fill="#f59e0b" font-size="13" class="geo-label" text-anchor="middle">26°</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 13°
                    </div>
                </div>

                {{-- Задача 4: угол BAC = 24° --}}
                <div x-data="task4Bisector()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">4</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что $\angle BAC = 24°$, AD – биссектриса. Найдите угол BAD.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="A.x" :y1="A.y" :x2="D.x" :y2="D.y"
                                stroke="#10b981" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="makeAngleArc(A, B, C, 50)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(A, B, D, 35)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>
                            <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="D.x + 14" :y="D.y - 8" fill="#10b981" font-size="16" class="geo-label">D</text>

                            <text :x="angleLabelPos(A, B, D, 68, 0.6).x" :y="angleLabelPos(A, B, D, 68, 0.6).y"
                                fill="#f59e0b" font-size="13" class="geo-label" text-anchor="middle">24°</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 12°
                    </div>
                </div>
            </div>
        </div>

        {{-- II) Медиана --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-white">II) Медиана треугольника</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 5: AC=14, BM медиана --}}
                <div x-data="task5Median()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">5</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AC=14, BM – медиана, BM=10. Найдите AM.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Медиана BM --}}
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#10b981" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Маркеры равенства AM = MC --}}
                            <line :x1="tickAM.x - 4" :y1="tickAM.y - 5" :x2="tickAM.x + 4" :y2="tickAM.y + 3" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="tickMC.x - 4" :y1="tickMC.y - 5" :x2="tickMC.x + 4" :y2="tickMC.y + 3" stroke="#3b82f6" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#10b981"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 18" fill="#10b981" font-size="16" class="geo-label" text-anchor="middle">M</text>

                            {{-- Метка длины AC: по центру основания, ниже --}}
                            <text :x="(A.x + C.x) / 2" :y="A.y + 38"
                                fill="#f59e0b" font-size="13" class="geo-label" text-anchor="middle">14</text>

                            {{-- Метка длины BM: справа от середины медианы --}}
                            <text :x="(B.x + M.x) / 2 + 18" :y="(B.y + M.y) / 2"
                                fill="#10b981" font-size="13" class="geo-label" text-anchor="middle">10</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 7 (AM = AC/2)
                    </div>
                </div>

                {{-- Задача 6: AC=16 --}}
                <div x-data="task6Median()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">6</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AC=16, BM – медиана, BM=12. Найдите AM.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#10b981" stroke-width="2" stroke-dasharray="6,4"/>

                            <line :x1="tickAM.x - 4" :y1="tickAM.y - 5" :x2="tickAM.x + 4" :y2="tickAM.y + 3" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="tickMC.x - 4" :y1="tickMC.y - 5" :x2="tickMC.x + 4" :y2="tickMC.y + 3" stroke="#3b82f6" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#10b981"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 18" fill="#10b981" font-size="16" class="geo-label" text-anchor="middle">M</text>

                            {{-- Метка длины AC: по центру основания, ниже --}}
                            <text :x="(A.x + C.x) / 2" :y="A.y + 38"
                                fill="#f59e0b" font-size="13" class="geo-label" text-anchor="middle">16</text>

                            {{-- Метка длины BM: справа от середины медианы --}}
                            <text :x="(B.x + M.x) / 2 + 18" :y="(B.y + M.y) / 2"
                                fill="#10b981" font-size="13" class="geo-label" text-anchor="middle">12</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 8
                    </div>
                </div>

                {{-- Задача 7: AC=38, BM=17 --}}
                <div x-data="task7Median()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">7</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AC=38, BM – медиана, BM=17. Найдите AM.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#10b981" stroke-width="2" stroke-dasharray="6,4"/>

                            <line :x1="tickAM.x - 4" :y1="tickAM.y - 5" :x2="tickAM.x + 4" :y2="tickAM.y + 3" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="tickMC.x - 4" :y1="tickMC.y - 5" :x2="tickMC.x + 4" :y2="tickMC.y + 3" stroke="#3b82f6" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#10b981"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 18" fill="#10b981" font-size="16" class="geo-label" text-anchor="middle">M</text>

                            <text :x="(A.x + C.x) / 2" :y="A.y + 38"
                                fill="#f59e0b" font-size="13" class="geo-label" text-anchor="middle">38</text>
                            <text :x="(B.x + M.x) / 2 + 18" :y="(B.y + M.y) / 2"
                                fill="#10b981" font-size="13" class="geo-label" text-anchor="middle">17</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 19
                    </div>
                </div>

                {{-- Задача 8: AC=54, BM=43 --}}
                <div x-data="task8Median()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">8</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AC=54, BM – медиана, BM=43. Найдите AM.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#10b981" stroke-width="2" stroke-dasharray="6,4"/>

                            <line :x1="tickAM.x - 4" :y1="tickAM.y - 5" :x2="tickAM.x + 4" :y2="tickAM.y + 3" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="tickMC.x - 4" :y1="tickMC.y - 5" :x2="tickMC.x + 4" :y2="tickMC.y + 3" stroke="#3b82f6" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#10b981"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 18" fill="#10b981" font-size="16" class="geo-label" text-anchor="middle">M</text>

                            <text :x="(A.x + C.x) / 2" :y="A.y + 38"
                                fill="#f59e0b" font-size="13" class="geo-label" text-anchor="middle">54</text>
                            <text :x="(B.x + M.x) / 2 + 18" :y="(B.y + M.y) / 2"
                                fill="#10b981" font-size="13" class="geo-label" text-anchor="middle">43</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 27
                    </div>
                </div>
            </div>
        </div>

        {{-- III) Сумма углов --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-white">III) Сумма углов треугольника</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 9: углы 72° и 42° --}}
                <div x-data="task9Angles()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">9</span>
                        <div class="text-slate-200">
                            В треугольнике два угла равны 72° и 42°. Найдите его третий угол.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Дуги углов --}}
                            <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 28)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 28)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки углов — используем angleLabelPos для правильного позиционирования --}}
                            <text :x="angleLabelPos(A, C, B, 45).x" :y="angleLabelPos(A, C, B, 45).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">72°</text>
                            <text :x="angleLabelPos(B, A, C, 45).x" :y="angleLabelPos(B, A, C, 45).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">42°</text>
                            <text :x="angleLabelPos(C, B, A, 45).x" :y="angleLabelPos(C, B, A, 45).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 66° (180° − 72° − 42°)
                    </div>
                </div>

                {{-- Задача 10: углы 43° и 88° --}}
                <div x-data="task10Angles()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">10</span>
                        <div class="text-slate-200">
                            В треугольнике два угла равны 43° и 88°. Найдите его третий угол.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 22)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 28)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки углов — используем angleLabelPos для правильного позиционирования --}}
                            <text :x="angleLabelPos(A, C, B, 45).x" :y="angleLabelPos(A, C, B, 45).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">43°</text>
                            <text :x="angleLabelPos(B, A, C, 38).x" :y="angleLabelPos(B, A, C, 38).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">88°</text>
                            <text :x="angleLabelPos(C, B, A, 45).x" :y="angleLabelPos(C, B, A, 45).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 49°
                    </div>
                </div>

                {{-- Задача 11: углы 38° и 89° --}}
                <div x-data="task11Angles()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">11</span>
                        <div class="text-slate-200">
                            В треугольнике два угла равны 38° и 89°. Найдите его третий угол.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Дуги углов --}}
                            <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 20)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 28)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки углов --}}
                            <text :x="angleLabelPos(A, C, B, 45).x" :y="angleLabelPos(A, C, B, 45).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">38°</text>
                            <text :x="angleLabelPos(B, A, C, 36).x" :y="angleLabelPos(B, A, C, 36).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">89°</text>
                            <text :x="angleLabelPos(C, B, A, 45).x" :y="angleLabelPos(C, B, A, 45).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 53° (180° − 38° − 89°)
                    </div>
                </div>

                {{-- Задача 12: углы 54° и 58° --}}
                <div x-data="task12Angles()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">12</span>
                        <div class="text-slate-200">
                            В треугольнике два угла равны 54° и 58°. Найдите его третий угол.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Дуги углов --}}
                            <path :d="makeAngleArc(A, C, B, 28)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 28)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 28)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки углов --}}
                            <text :x="angleLabelPos(A, C, B, 45).x" :y="angleLabelPos(A, C, B, 45).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">54°</text>
                            <text :x="angleLabelPos(B, A, C, 45).x" :y="angleLabelPos(B, A, C, 45).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">58°</text>
                            <text :x="angleLabelPos(C, B, A, 45).x" :y="angleLabelPos(C, B, A, 45).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 68° (180° − 54° − 58°)
                    </div>
                </div>
            </div>
        </div>

        {{-- IV) Внешний угол --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-white">IV) Внешний угол треугольника</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 13: угол C = 115° --}}
                <div x-data="task13External()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">13</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 115°. Найдите внешний угол при вершине C.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            {{-- Треугольник ABC --}}
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Продолжение стороны AC за точку C --}}
                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#94a3b8" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Дуга внутреннего угла C --}}
                            <path :d="makeAngleArc(C, A, B, 25)" fill="none" stroke="#f59e0b" stroke-width="2"/>

                            {{-- Дуга внешнего угла C (искомый) --}}
                            <path :d="makeAngleArc(C, B, ext, 35)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метка внутреннего угла 115° --}}
                            <text :x="angleLabelPos(C, A, B, 40).x" :y="angleLabelPos(C, A, B, 40).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">115°</text>

                            {{-- Метка внешнего угла (?) --}}
                            <text :x="angleLabelPos(C, B, ext, 50).x" :y="angleLabelPos(C, B, ext, 50).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 65° (180° − 115°)
                    </div>
                </div>

                {{-- Задача 14: угол C = 177° --}}
                <div x-data="task14External()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">14</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 177°. Найдите внешний угол при вершине C.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#94a3b8" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="makeAngleArc(C, A, B, 30)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, ext, 45)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, A, B, 50).x" :y="angleLabelPos(C, A, B, 50).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">177°</text>
                            <text :x="angleLabelPos(C, B, ext, 60).x" :y="angleLabelPos(C, B, ext, 60).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 3° (180° − 177°)
                    </div>
                </div>

                {{-- Задача 15: угол C = 106° --}}
                <div x-data="task15External()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">15</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 106°. Найдите внешний угол при вершине C.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#94a3b8" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="makeAngleArc(C, A, B, 25)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, ext, 35)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, A, B, 40).x" :y="angleLabelPos(C, A, B, 40).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">106°</text>
                            <text :x="angleLabelPos(C, B, ext, 50).x" :y="angleLabelPos(C, B, ext, 50).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 74° (180° − 106°)
                    </div>
                </div>

                {{-- Задача 16: угол C = 142° --}}
                <div x-data="task16External()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">16</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 142°. Найдите внешний угол при вершине C.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#94a3b8" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="makeAngleArc(C, A, B, 25)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, ext, 40)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, A, B, 40).x" :y="angleLabelPos(C, A, B, 40).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">142°</text>
                            <text :x="angleLabelPos(C, B, ext, 55).x" :y="angleLabelPos(C, B, ext, 55).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 38° (180° − 142°)
                    </div>
                </div>
            </div>
        </div>

        {{-- V) Равнобедренный треугольник --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-white">V) Равнобедренный треугольник</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 17: AB=BC, угол ABC = 106° --}}
                <div x-data="task17Isosceles()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">17</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AB=BC, $\angle ABC = 106°$. Найдите угол BCA.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Маркеры равенства AB = BC --}}
                            <line :x1="tickAB.x - 4" :y1="tickAB.y - 5" :x2="tickAB.x + 4" :y2="tickAB.y + 3" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="tickBC.x - 4" :y1="tickBC.y - 5" :x2="tickBC.x + 4" :y2="tickBC.y + 3" stroke="#3b82f6" stroke-width="2.5"/>

                            {{-- Дуга угла B (известный) --}}
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#f59e0b" stroke-width="2"/>

                            {{-- Дуга угла C (искомый) --}}
                            <path :d="makeAngleArc(C, B, A, 28)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(B, A, C, 40).x" :y="angleLabelPos(B, A, C, 40).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">106°</text>
                            <text :x="angleLabelPos(C, B, A, 45).x" :y="angleLabelPos(C, B, A, 45).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 37° ((180° − 106°) / 2)
                    </div>
                </div>

                {{-- Задача 18: AB=BC, угол ABC = 108° --}}
                <div x-data="task18Isosceles()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">18</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AB=BC, $\angle ABC = 108°$. Найдите угол BCA.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="tickAB.x - 4" :y1="tickAB.y - 5" :x2="tickAB.x + 4" :y2="tickAB.y + 3" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="tickBC.x - 4" :y1="tickBC.y - 5" :x2="tickBC.x + 4" :y2="tickBC.y + 3" stroke="#3b82f6" stroke-width="2.5"/>

                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 28)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(B, A, C, 40).x" :y="angleLabelPos(B, A, C, 40).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">108°</text>
                            <text :x="angleLabelPos(C, B, A, 45).x" :y="angleLabelPos(C, B, A, 45).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 36° ((180° − 108°) / 2)
                    </div>
                </div>

                {{-- Задача 19: AB=BC, угол ABC = 132° --}}
                <div x-data="task19Isosceles()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">19</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AB=BC, $\angle ABC = 132°$. Найдите угол BCA.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="tickAB.x - 4" :y1="tickAB.y - 5" :x2="tickAB.x + 4" :y2="tickAB.y + 3" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="tickBC.x - 4" :y1="tickBC.y - 5" :x2="tickBC.x + 4" :y2="tickBC.y + 3" stroke="#3b82f6" stroke-width="2.5"/>

                            <path :d="makeAngleArc(B, A, C, 22)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 32)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(B, A, C, 38).x" :y="angleLabelPos(B, A, C, 38).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">132°</text>
                            <text :x="angleLabelPos(C, B, A, 48).x" :y="angleLabelPos(C, B, A, 48).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 24° ((180° − 132°) / 2)
                    </div>
                </div>

                {{-- Задача 20: AB=BC, угол ABC = 144° --}}
                <div x-data="task20Isosceles()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">20</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AB=BC, $\angle ABC = 144°$. Найдите угол BCA.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="tickAB.x - 4" :y1="tickAB.y - 5" :x2="tickAB.x + 4" :y2="tickAB.y + 3" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="tickBC.x - 4" :y1="tickBC.y - 5" :x2="tickBC.x + 4" :y2="tickBC.y + 3" stroke="#3b82f6" stroke-width="2.5"/>

                            <path :d="makeAngleArc(B, A, C, 20)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 35)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(B, A, C, 36).x" :y="angleLabelPos(B, A, C, 36).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">144°</text>
                            <text :x="angleLabelPos(C, B, A, 50).x" :y="angleLabelPos(C, B, A, 50).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 18° ((180° − 144°) / 2)
                    </div>
                </div>
            </div>
        </div>

        {{-- VI) Равнобедренный + внешний угол --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-white">VI) Равнобедренный треугольник + внешний угол</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 21: внешний угол при C = 129° --}}
                <div x-data="task21IsoscelesExt()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">21</span>
                        <div class="text-slate-200">
                            В равнобедренном треугольнике ABC с основанием AC внешний угол при вершине C равен 129°. Найдите величину угла ABC.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            {{-- Треугольник ABC --}}
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Продолжение стороны AC за точку C --}}
                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#dc2626" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Маркеры равных сторон AB = BC --}}
                            <line :x1="tickAB.x - 4" :y1="tickAB.y - 6" :x2="tickAB.x + 4" :y2="tickAB.y + 2" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="tickBC.x - 4" :y1="tickBC.y - 6" :x2="tickBC.x + 4" :y2="tickBC.y + 2" stroke="#3b82f6" stroke-width="2.5"/>

                            {{-- Дуга внешнего угла при C --}}
                            <path :d="makeAngleArc(C, B, ext, 30)" fill="none" stroke="#f59e0b" stroke-width="2"/>

                            {{-- Дуга искомого угла ABC --}}
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метка внешнего угла 129° --}}
                            <text :x="angleLabelPos(C, B, ext, 48).x" :y="angleLabelPos(C, B, ext, 48).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">129°</text>
                            {{-- Метка искомого угла --}}
                            <text :x="angleLabelPos(B, A, C, 42).x" :y="angleLabelPos(B, A, C, 42).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 78° (180° − 2×51°)
                    </div>
                </div>

                {{-- Задача 22: внешний угол при C = 124° --}}
                <div x-data="task22IsoscelesExt()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">22</span>
                        <div class="text-slate-200">
                            В равнобедренном треугольнике ABC с основанием AC внешний угол при вершине C равен 124°. Найдите величину угла ABC.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#dc2626" stroke-width="2" stroke-dasharray="6,4"/>

                            <line :x1="tickAB.x - 4" :y1="tickAB.y - 6" :x2="tickAB.x + 4" :y2="tickAB.y + 2" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="tickBC.x - 4" :y1="tickBC.y - 6" :x2="tickBC.x + 4" :y2="tickBC.y + 2" stroke="#3b82f6" stroke-width="2.5"/>

                            <path :d="makeAngleArc(C, B, ext, 30)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, B, ext, 48).x" :y="angleLabelPos(C, B, ext, 48).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">124°</text>
                            <text :x="angleLabelPos(B, A, C, 42).x" :y="angleLabelPos(B, A, C, 42).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 68° (180° − 2×56°)
                    </div>
                </div>

                {{-- Задача 23: внешний угол при C = 107° --}}
                <div x-data="task23IsoscelesExt()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">23</span>
                        <div class="text-slate-200">
                            В равнобедренном треугольнике ABC с основанием AC внешний угол при вершине C равен 107°. Найдите величину угла ABC.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#dc2626" stroke-width="2" stroke-dasharray="6,4"/>

                            <line :x1="tickAB.x - 4" :y1="tickAB.y - 6" :x2="tickAB.x + 4" :y2="tickAB.y + 2" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="tickBC.x - 4" :y1="tickBC.y - 6" :x2="tickBC.x + 4" :y2="tickBC.y + 2" stroke="#3b82f6" stroke-width="2.5"/>

                            <path :d="makeAngleArc(C, B, ext, 30)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, B, ext, 48).x" :y="angleLabelPos(C, B, ext, 48).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">107°</text>
                            <text :x="angleLabelPos(B, A, C, 42).x" :y="angleLabelPos(B, A, C, 42).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 34° (180° − 2×73°)
                    </div>
                </div>

                {{-- Задача 24: внешний угол при C = 111° --}}
                <div x-data="task24IsoscelesExt()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">24</span>
                        <div class="text-slate-200">
                            В равнобедренном треугольнике ABC с основанием AC внешний угол при вершине C равен 111°. Найдите величину угла ABC.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#dc2626" stroke-width="2" stroke-dasharray="6,4"/>

                            <line :x1="tickAB.x - 4" :y1="tickAB.y - 6" :x2="tickAB.x + 4" :y2="tickAB.y + 2" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="tickBC.x - 4" :y1="tickBC.y - 6" :x2="tickBC.x + 4" :y2="tickBC.y + 2" stroke="#3b82f6" stroke-width="2.5"/>

                            <path :d="makeAngleArc(C, B, ext, 30)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#10b981" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, B, ext, 48).x" :y="angleLabelPos(C, B, ext, 48).y"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">111°</text>
                            <text :x="angleLabelPos(B, A, C, 42).x" :y="angleLabelPos(B, A, C, 42).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 42° (180° − 2×69°)
                    </div>
                </div>
            </div>
        </div>

        {{-- VII) Теорема Пифагора --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-white">VII) Теорема Пифагора</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 45: катеты 7 и 24 --}}
                <div x-data="task45Pythagoras()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">45</span>
                        <div class="text-slate-200">
                            Катеты прямоугольного треугольника равны 7 и 24. Найдите гипотенузу.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Прямой угол в A --}}
                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#666666" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки длин катетов — ближе к сторонам --}}
                            <text :x="labelOnSegment(A, C, 8, true).x" :y="labelOnSegment(A, C, 8, true).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">24</text>
                            <text :x="labelOnSegment(A, B, 8).x" :y="labelOnSegment(A, B, 8).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">7</text>

                            {{-- Метка гипотенузы (искомая) — чуть дальше от стороны --}}
                            <text :x="labelOnSegment(B, C, 16).x" :y="labelOnSegment(B, C, 16).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 25
                    </div>
                </div>

                {{-- Задача 46: катеты 8 и 15 --}}
                <div x-data="task46Pythagoras()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">46</span>
                        <div class="text-slate-200">
                            Катеты прямоугольного треугольника равны 8 и 15. Найдите гипотенузу.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#666666" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки длин катетов — ближе к сторонам --}}
                            <text :x="labelOnSegment(A, C, 8, true).x" :y="labelOnSegment(A, C, 8, true).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">15</text>
                            <text :x="labelOnSegment(A, B, 8).x" :y="labelOnSegment(A, B, 8).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">8</text>

                            {{-- Метка гипотенузы (искомая) — чуть дальше от стороны --}}
                            <text :x="labelOnSegment(B, C, 16).x" :y="labelOnSegment(B, C, 16).y"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 17
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Чек-лист --}}
    <div class="bg-emerald-900/30 border border-emerald-500/30 rounded-xl p-6 mt-10">
        <h4 class="text-emerald-400 font-semibold mb-4">✅ Чек-лист GEOMETRY_SPEC</h4>
        <ul class="text-slate-300 space-y-1 text-sm">
            <li>☑ Все подписи через <code class="bg-slate-700 px-1 rounded">labelPos()</code></li>
            <li>☑ Дуги строго между сторонами через <code class="bg-slate-700 px-1 rounded">makeAngleArc()</code></li>
            <li>☑ Биссектриса доходит до стороны через <code class="bg-slate-700 px-1 rounded">bisectorPoint()</code></li>
            <li>☑ Медиана до середины через <code class="bg-slate-700 px-1 rounded">pointOnLine(A, C, 0.5)</code></li>
            <li>☑ Метки углов через <code class="bg-slate-700 px-1 rounded">angleLabelPos()</code></li>
            <li>☑ Метки длин через <code class="bg-slate-700 px-1 rounded">labelOnSegment()</code></li>
            <li>☑ Прямой угол через <code class="bg-slate-700 px-1 rounded">rightAnglePath()</code></li>
        </ul>
    </div>

</div>

{{-- Alpine.js данные --}}
<script>
    // Биссектриса: задача 1
    function task1Bisector() {
        const A = { x: 40, y: 180 };
        const B = { x: 160, y: 40 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const D = window.bisectorPoint(A, B, C); // Точка на BC
        return {
            A, B, C, D, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r, bias) => window.angleLabelPos(v, p1, p2, r, bias),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Биссектриса: задача 2
    function task2Bisector() {
        const A = { x: 40, y: 180 };
        const B = { x: 140, y: 40 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const D = window.bisectorPoint(A, B, C);
        return {
            A, B, C, D, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r, bias) => window.angleLabelPos(v, p1, p2, r, bias),
        };
    }

    // Биссектриса: задача 3 (угол 26°)
    function task3Bisector() {
        const A = { x: 30, y: 180 };
        const B = { x: 200, y: 50 };
        const C = { x: 270, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const D = window.bisectorPoint(A, B, C);
        return {
            A, B, C, D, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r, bias) => window.angleLabelPos(v, p1, p2, r, bias),
        };
    }

    // Биссектриса: задача 4 (угол 24°)
    function task4Bisector() {
        const A = { x: 30, y: 180 };
        const B = { x: 210, y: 60 };
        const C = { x: 270, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const D = window.bisectorPoint(A, B, C);
        return {
            A, B, C, D, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r, bias) => window.angleLabelPos(v, p1, p2, r, bias),
        };
    }

    // Медиана: задача 5
    function task5Median() {
        const A = { x: 40, y: 180 };
        const B = { x: 160, y: 40 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const M = window.pointOnLine(A, C, 0.5); // Середина AC
        const tickAM = window.pointOnLine(A, M, 0.5);
        const tickMC = window.pointOnLine(M, C, 0.5);
        return {
            A, B, C, M, center, tickAM, tickMC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Медиана: задача 6
    function task6Median() {
        const A = { x: 40, y: 180 };
        const B = { x: 180, y: 40 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const M = window.pointOnLine(A, C, 0.5);
        const tickAM = window.pointOnLine(A, M, 0.5);
        const tickMC = window.pointOnLine(M, C, 0.5);
        return {
            A, B, C, M, center, tickAM, tickMC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Медиана: задача 7 (AC=38, BM=17)
    function task7Median() {
        const A = { x: 40, y: 180 };
        const B = { x: 140, y: 40 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const M = window.pointOnLine(A, C, 0.5);
        const tickAM = window.pointOnLine(A, M, 0.5);
        const tickMC = window.pointOnLine(M, C, 0.5);
        return {
            A, B, C, M, center, tickAM, tickMC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Медиана: задача 8 (AC=54, BM=43)
    function task8Median() {
        const A = { x: 40, y: 180 };
        const B = { x: 170, y: 35 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const M = window.pointOnLine(A, C, 0.5);
        const tickAM = window.pointOnLine(A, M, 0.5);
        const tickMC = window.pointOnLine(M, C, 0.5);
        return {
            A, B, C, M, center, tickAM, tickMC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Сумма углов: задача 9
    function task9Angles() {
        const A = { x: 40, y: 180 };
        const B = { x: 260, y: 180 };
        const C = { x: 140, y: 50 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Сумма углов: задача 10
    function task10Angles() {
        const A = { x: 40, y: 180 };
        const B = { x: 260, y: 180 };
        const C = { x: 150, y: 45 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Сумма углов: задача 11 (38° и 89°)
    function task11Angles() {
        const A = { x: 40, y: 180 };
        const B = { x: 260, y: 180 };
        const C = { x: 120, y: 40 }; // Угол B почти прямой (89°)
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Сумма углов: задача 12 (54° и 58°)
    function task12Angles() {
        const A = { x: 40, y: 180 };
        const B = { x: 260, y: 180 };
        const C = { x: 145, y: 50 }; // Углы примерно равны (~54° и ~58°)
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Внешний угол: задача 13 (угол C = 115°)
    function task13External() {
        const A = { x: 40, y: 180 };
        const B = { x: 180, y: 40 };
        const C = { x: 240, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        // Продолжение стороны AC за точку C
        const dx = C.x - A.x;
        const dy = C.y - A.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        const ext = { x: C.x + (dx / len) * 60, y: C.y + (dy / len) * 60 };
        return {
            A, B, C, center, ext,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Внешний угол: задача 14 (угол C = 177°)
    // Рисунок показывает концепцию внешнего угла, не буквальное значение
    function task14External() {
        const A = { x: 40, y: 180 };
        const B = { x: 180, y: 50 };
        const C = { x: 240, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const dx = C.x - A.x;
        const dy = C.y - A.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        const ext = { x: C.x + (dx / len) * 60, y: C.y + (dy / len) * 60 };
        return {
            A, B, C, center, ext,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Внешний угол: задача 15 (угол C = 106°)
    function task15External() {
        const A = { x: 40, y: 180 };
        const B = { x: 170, y: 45 };
        const C = { x: 240, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const dx = C.x - A.x;
        const dy = C.y - A.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        const ext = { x: C.x + (dx / len) * 60, y: C.y + (dy / len) * 60 };
        return {
            A, B, C, center, ext,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Внешний угол: задача 16 (угол C = 142°)
    function task16External() {
        const A = { x: 30, y: 180 };
        const B = { x: 200, y: 60 };
        const C = { x: 250, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const dx = C.x - A.x;
        const dy = C.y - A.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        const ext = { x: C.x + (dx / len) * 55, y: C.y + (dy / len) * 55 };
        return {
            A, B, C, center, ext,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Равнобедренный: задача 17 (AB=BC, угол ABC = 106°)
    function task17Isosceles() {
        const A = { x: 40, y: 180 };
        const B = { x: 150, y: 40 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const tickAB = window.pointOnLine(A, B, 0.5);
        const tickBC = window.pointOnLine(B, C, 0.5);
        return {
            A, B, C, center, tickAB, tickBC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Равнобедренный: задача 18 (AB=BC, угол ABC = 108°)
    function task18Isosceles() {
        const A = { x: 40, y: 180 };
        const B = { x: 150, y: 45 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const tickAB = window.pointOnLine(A, B, 0.5);
        const tickBC = window.pointOnLine(B, C, 0.5);
        return {
            A, B, C, center, tickAB, tickBC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Равнобедренный: задача 19 (AB=BC, угол ABC = 132°)
    function task19Isosceles() {
        const A = { x: 30, y: 180 };
        const B = { x: 150, y: 60 };
        const C = { x: 270, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const tickAB = window.pointOnLine(A, B, 0.5);
        const tickBC = window.pointOnLine(B, C, 0.5);
        return {
            A, B, C, center, tickAB, tickBC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Равнобедренный: задача 20 (AB=BC, угол ABC = 144°)
    function task20Isosceles() {
        const A = { x: 25, y: 180 };
        const B = { x: 150, y: 75 };
        const C = { x: 275, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const tickAB = window.pointOnLine(A, B, 0.5);
        const tickBC = window.pointOnLine(B, C, 0.5);
        return {
            A, B, C, center, tickAB, tickBC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Равнобедренный + внешний угол: задача 21 (внешний угол 129°)
    function task21IsoscelesExt() {
        const A = { x: 30, y: 180 };
        const B = { x: 140, y: 45 };
        const C = { x: 250, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        // Продолжение AC за точку C
        const dx = C.x - A.x;
        const dy = C.y - A.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        const ext = { x: C.x + (dx / len) * 55, y: C.y + (dy / len) * 55 };
        // Маркеры равенства AB = BC
        const tickAB = window.pointOnLine(A, B, 0.5);
        const tickBC = window.pointOnLine(B, C, 0.5);
        return {
            A, B, C, center, ext, tickAB, tickBC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Равнобедренный + внешний угол: задача 22 (внешний угол 124°)
    function task22IsoscelesExt() {
        const A = { x: 30, y: 180 };
        const B = { x: 140, y: 50 };
        const C = { x: 250, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const dx = C.x - A.x;
        const dy = C.y - A.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        const ext = { x: C.x + (dx / len) * 55, y: C.y + (dy / len) * 55 };
        const tickAB = window.pointOnLine(A, B, 0.5);
        const tickBC = window.pointOnLine(B, C, 0.5);
        return {
            A, B, C, center, ext, tickAB, tickBC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Равнобедренный + внешний угол: задача 23 (внешний угол 107°)
    function task23IsoscelesExt() {
        const A = { x: 35, y: 180 };
        const B = { x: 140, y: 60 };
        const C = { x: 245, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const dx = C.x - A.x;
        const dy = C.y - A.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        const ext = { x: C.x + (dx / len) * 55, y: C.y + (dy / len) * 55 };
        const tickAB = window.pointOnLine(A, B, 0.5);
        const tickBC = window.pointOnLine(B, C, 0.5);
        return {
            A, B, C, center, ext, tickAB, tickBC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Равнобедренный + внешний угол: задача 24 (внешний угол 111°)
    function task24IsoscelesExt() {
        const A = { x: 35, y: 180 };
        const B = { x: 140, y: 55 };
        const C = { x: 245, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const dx = C.x - A.x;
        const dy = C.y - A.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        const ext = { x: C.x + (dx / len) * 55, y: C.y + (dy / len) * 55 };
        const tickAB = window.pointOnLine(A, B, 0.5);
        const tickBC = window.pointOnLine(B, C, 0.5);
        return {
            A, B, C, center, ext, tickAB, tickBC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Пифагор: задача 45
    function task45Pythagoras() {
        // Прямой угол в A, катеты AB=7, AC=24
        const A = { x: 50, y: 180 };
        const B = { x: 50, y: 60 };  // Вертикальный катет
        const C = { x: 250, y: 180 }; // Горизонтальный катет
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Пифагор: задача 46
    function task46Pythagoras() {
        const A = { x: 50, y: 180 };
        const B = { x: 50, y: 70 };
        const C = { x: 230, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }
</script>

</body>
</html>
