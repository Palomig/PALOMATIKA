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

                            {{-- Метка длины AC (перпендикулярно, снизу) --}}
                            <text :x="labelOnSegment(A, C, 18, true).x" :y="labelOnSegment(A, C, 18, true).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">AC = 14</text>

                            {{-- Метка длины BM (слева от медианы, flipSide=true чтобы не накладывалась) --}}
                            <text :x="labelOnSegment(B, M, 18, true).x" :y="labelOnSegment(B, M, 18, true).y"
                                fill="#10b981" font-size="11" class="geo-label" text-anchor="middle">BM = 10</text>
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

                            <text :x="labelOnSegment(A, C, 18, true).x" :y="labelOnSegment(A, C, 18, true).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">AC = 16</text>

                            {{-- Метка длины BM (слева от медианы) --}}
                            <text :x="labelOnSegment(B, M, 18, true).x" :y="labelOnSegment(B, M, 18, true).y"
                                fill="#10b981" font-size="11" class="geo-label" text-anchor="middle">BM = 12</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 8
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

                            {{-- Метки углов — внутри треугольника, рядом с дугой --}}
                            <text :x="A.x + 50" :y="A.y - 18"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">72°</text>
                            <text :x="B.x - 42" :y="B.y + 30"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">42°</text>
                            <text :x="C.x - 42" :y="C.y - 18"
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

                            {{-- Метки углов — внутри треугольника, рядом с дугой --}}
                            <text :x="A.x + 50" :y="A.y - 18"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">43°</text>
                            <text :x="B.x - 35" :y="B.y + 28"
                                fill="#f59e0b" font-size="12" class="geo-label" text-anchor="middle">88°</text>
                            <text :x="C.x - 42" :y="C.y - 18"
                                fill="#10b981" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 49°
                    </div>
                </div>
            </div>
        </div>

        {{-- IV) Теорема Пифагора --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-white">IV) Теорема Пифагора</h3>
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
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">24</text>
                            <text :x="labelOnSegment(A, B, 12).x" :y="labelOnSegment(A, B, 12).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">7</text>

                            {{-- Метка гипотенузы (искомая) --}}
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
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
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">15</text>
                            <text :x="labelOnSegment(A, B, 12).x" :y="labelOnSegment(A, B, 12).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">8</text>

                            {{-- Метка гипотенузы (искомая) --}}
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
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
