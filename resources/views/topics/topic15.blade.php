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

    // 8. Проверка: является ли угол в вершине прямым (90°)
    //    Использует скалярное произведение: если dot ≈ 0, угол = 90°
    function isRightAngle(vertex, p1, p2) {
        const v1 = { x: p1.x - vertex.x, y: p1.y - vertex.y };
        const v2 = { x: p2.x - vertex.x, y: p2.y - vertex.y };
        const dot = v1.x * v2.x + v1.y * v2.y;
        return Math.abs(dot) < 1; // допуск для погрешности округления
    }

    // 9. Маркер равенства сторон (черточка перпендикулярна отрезку)
    //    p1, p2 — концы отрезка
    //    t — позиция на отрезке (0.5 = середина)
    //    length — длина черточки (по умолчанию 8px)
    //    Возвращает объект с координатами начала и конца черточки
    function equalityTick(p1, p2, t = 0.5, length = 8) {
        // Точка на середине отрезка
        const mid = {
            x: p1.x + (p2.x - p1.x) * t,
            y: p1.y + (p2.y - p1.y) * t
        };
        // Вектор вдоль отрезка
        const dx = p2.x - p1.x;
        const dy = p2.y - p1.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        // Нормаль (перпендикуляр) к отрезку
        const nx = -dy / len;
        const ny = dx / len;
        // Черточка по обе стороны от середины
        const half = length / 2;
        return {
            x1: mid.x - nx * half,
            y1: mid.y - ny * half,
            x2: mid.x + nx * half,
            y2: mid.y + ny * half
        };
    }

    // Двойная черточка (для второй пары равных отрезков)
    function doubleEqualityTick(p1, p2, t = 0.5, length = 8, gap = 4) {
        const dx = p2.x - p1.x;
        const dy = p2.y - p1.y;
        const len = Math.sqrt(dx * dx + dy * dy);
        // Единичный вектор вдоль отрезка
        const ux = dx / len;
        const uy = dy / len;
        // Нормаль (перпендикуляр) к отрезку
        const nx = -dy / len;
        const ny = dx / len;
        // Центральная точка
        const mid = {
            x: p1.x + dx * t,
            y: p1.y + dy * t
        };
        const half = length / 2;
        const halfGap = gap / 2;
        // Первая черточка (смещена назад вдоль отрезка)
        const tick1 = {
            x1: mid.x - ux * halfGap - nx * half,
            y1: mid.y - uy * halfGap - ny * half,
            x2: mid.x - ux * halfGap + nx * half,
            y2: mid.y - uy * halfGap + ny * half
        };
        // Вторая черточка (смещена вперёд вдоль отрезка)
        const tick2 = {
            x1: mid.x + ux * halfGap - nx * half,
            y1: mid.y + uy * halfGap - ny * half,
            x2: mid.x + ux * halfGap + nx * half,
            y2: mid.y + uy * halfGap + ny * half
        };
        return { tick1, tick2 };
    }

    // Экспортируем в глобальную область
    window.labelPos = labelPos;
    window.makeAngleArc = makeAngleArc;
    window.rightAnglePath = rightAnglePath;
    window.pointOnLine = pointOnLine;
    window.labelOnSegment = labelOnSegment;
    window.angleLabelPos = angleLabelPos;
    window.bisectorPoint = bisectorPoint;
    window.isRightAngle = isRightAngle;
    window.equalityTick = equalityTick;
    window.doubleEqualityTick = doubleEqualityTick;
</script>

<div class="max-w-6xl mx-auto px-4 py-8">
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
                @if($tid === '15')
                    <span class="px-2.5 py-1 rounded-lg bg-red-500 text-white font-bold text-xs">{{ $tid }}</span>
                @else
                    <a href="{{ route('topics.show', ['id' => ltrim($tid, '0')]) }}"
                       class="px-2.5 py-1 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition text-xs">{{ $tid }}</a>
                @endif
            @endforeach
        </div>

        <span class="text-slate-500 text-xs">170 заданий</span>
    </div>

    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">15. Треугольники</h1>
        <p class="text-slate-400 text-lg">Геометрия: треугольники, биссектрисы, медианы, высоты</p>
    </div>

    {{-- Version Navigation --}}
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            {{-- Треугольник ABC --}}
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Биссектриса AD (доходит до стороны BC!) --}}
                            <line :x1="A.x" :y1="A.y" :x2="D.x" :y2="D.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Дуга полного угла BAC — увеличенный радиус для места под текст --}}
                            <path :d="makeAngleArc(A, B, C, 45)" fill="none" stroke="#d4a855" stroke-width="2"/>

                            {{-- Дуга искомого угла BAD --}}
                            <path :d="makeAngleArc(A, B, D, 30)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            {{-- Точки вершин --}}
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="D.x" :cy="D.y" r="4" fill="#5a9fcf"/>

                            {{-- Подписи вершин (от центра) --}}
                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Подпись D (справа от точки D) --}}
                            <text :x="D.x + 14" :y="D.y - 8"
                                fill="#5a9fcf" font-size="16" class="geo-label" text-anchor="start" dominant-baseline="middle">D</text>

                            {{-- Метка угла 68° — bias=0.6 смещает ниже (к биссектрисе но не на неё) --}}
                            <text :x="angleLabelPos(A, B, D, 62, 0.6).x" :y="angleLabelPos(A, B, D, 62, 0.6).y"
                                fill="#d4a855" font-size="13" class="geo-label" text-anchor="middle" dominant-baseline="middle">68°</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="A.x" :y1="A.y" :x2="D.x" :y2="D.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Увеличенные радиусы дуг --}}
                            <path :d="makeAngleArc(A, B, C, 45)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(A, B, D, 30)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="D.x" :cy="D.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="D.x + 14" :y="D.y - 8" fill="#5a9fcf" font-size="16" class="geo-label">D</text>

                            {{-- Метка угла 82° — bias=0.6 смещает ниже --}}
                            <text :x="angleLabelPos(A, B, D, 62, 0.6).x" :y="angleLabelPos(A, B, D, 62, 0.6).y"
                                fill="#d4a855" font-size="13" class="geo-label" text-anchor="middle">82°</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="A.x" :y1="A.y" :x2="D.x" :y2="D.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="makeAngleArc(A, B, C, 50)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(A, B, D, 35)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="D.x" :cy="D.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="D.x + 14" :y="D.y - 8" fill="#5a9fcf" font-size="16" class="geo-label">D</text>

                            <text :x="angleLabelPos(A, B, D, 68, 0.6).x" :y="angleLabelPos(A, B, D, 68, 0.6).y"
                                fill="#d4a855" font-size="13" class="geo-label" text-anchor="middle">26°</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="A.x" :y1="A.y" :x2="D.x" :y2="D.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="makeAngleArc(A, B, C, 50)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(A, B, D, 35)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="D.x" :cy="D.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="D.x + 14" :y="D.y - 8" fill="#5a9fcf" font-size="16" class="geo-label">D</text>

                            <text :x="angleLabelPos(A, B, D, 68, 0.6).x" :y="angleLabelPos(A, B, D, 68, 0.6).y"
                                fill="#d4a855" font-size="13" class="geo-label" text-anchor="middle">24°</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Медиана BM --}}
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Маркеры равенства AM = MC --}}
                            <line :x1="tickAM.x1" :y1="tickAM.y1" :x2="tickAM.x2" :y2="tickAM.y2" stroke="#7eb8da" stroke-width="2.5"/>
                            <line :x1="tickMC.x1" :y1="tickMC.y1" :x2="tickMC.x2" :y2="tickMC.y2" stroke="#7eb8da" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 18" fill="#5a9fcf" font-size="16" class="geo-label" text-anchor="middle">M</text>

                            {{-- Метка длины AC: по центру основания, ниже --}}
                            <text :x="(A.x + C.x) / 2" :y="A.y + 38"
                                fill="#d4a855" font-size="13" class="geo-label" text-anchor="middle">14</text>

                            {{-- Метка длины BM: справа от середины медианы --}}
                            <text :x="(B.x + M.x) / 2 + 18" :y="(B.y + M.y) / 2"
                                fill="#5a9fcf" font-size="13" class="geo-label" text-anchor="middle">10</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            <line :x1="tickAM.x1" :y1="tickAM.y1" :x2="tickAM.x2" :y2="tickAM.y2" stroke="#7eb8da" stroke-width="2.5"/>
                            <line :x1="tickMC.x1" :y1="tickMC.y1" :x2="tickMC.x2" :y2="tickMC.y2" stroke="#7eb8da" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 18" fill="#5a9fcf" font-size="16" class="geo-label" text-anchor="middle">M</text>

                            {{-- Метка длины AC: по центру основания, ниже --}}
                            <text :x="(A.x + C.x) / 2" :y="A.y + 38"
                                fill="#d4a855" font-size="13" class="geo-label" text-anchor="middle">16</text>

                            {{-- Метка длины BM: справа от середины медианы --}}
                            <text :x="(B.x + M.x) / 2 + 18" :y="(B.y + M.y) / 2"
                                fill="#5a9fcf" font-size="13" class="geo-label" text-anchor="middle">12</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            <line :x1="tickAM.x1" :y1="tickAM.y1" :x2="tickAM.x2" :y2="tickAM.y2" stroke="#7eb8da" stroke-width="2.5"/>
                            <line :x1="tickMC.x1" :y1="tickMC.y1" :x2="tickMC.x2" :y2="tickMC.y2" stroke="#7eb8da" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 18" fill="#5a9fcf" font-size="16" class="geo-label" text-anchor="middle">M</text>

                            <text :x="(A.x + C.x) / 2" :y="A.y + 38"
                                fill="#d4a855" font-size="13" class="geo-label" text-anchor="middle">38</text>
                            <text :x="(B.x + M.x) / 2 + 18" :y="(B.y + M.y) / 2"
                                fill="#5a9fcf" font-size="13" class="geo-label" text-anchor="middle">17</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            <line :x1="tickAM.x1" :y1="tickAM.y1" :x2="tickAM.x2" :y2="tickAM.y2" stroke="#7eb8da" stroke-width="2.5"/>
                            <line :x1="tickMC.x1" :y1="tickMC.y1" :x2="tickMC.x2" :y2="tickMC.y2" stroke="#7eb8da" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 18" fill="#5a9fcf" font-size="16" class="geo-label" text-anchor="middle">M</text>

                            <text :x="(A.x + C.x) / 2" :y="A.y + 38"
                                fill="#d4a855" font-size="13" class="geo-label" text-anchor="middle">54</text>
                            <text :x="(B.x + M.x) / 2 + 18" :y="(B.y + M.y) / 2"
                                fill="#5a9fcf" font-size="13" class="geo-label" text-anchor="middle">43</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Дуги углов --}}
                            <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 28)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 28)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки углов — используем angleLabelPos для правильного позиционирования --}}
                            <text :x="angleLabelPos(A, C, B, 45).x" :y="angleLabelPos(A, C, B, 45).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">72°</text>
                            <text :x="angleLabelPos(B, A, C, 45).x" :y="angleLabelPos(B, A, C, 45).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">42°</text>
                            <text :x="angleLabelPos(C, B, A, 45).x" :y="angleLabelPos(C, B, A, 45).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 22)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 28)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки углов — используем angleLabelPos для правильного позиционирования --}}
                            <text :x="angleLabelPos(A, C, B, 45).x" :y="angleLabelPos(A, C, B, 45).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">43°</text>
                            <text :x="angleLabelPos(B, A, C, 38).x" :y="angleLabelPos(B, A, C, 38).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">88°</text>
                            <text :x="angleLabelPos(C, B, A, 45).x" :y="angleLabelPos(C, B, A, 45).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Дуги углов --}}
                            <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 20)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 28)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки углов --}}
                            <text :x="angleLabelPos(A, C, B, 45).x" :y="angleLabelPos(A, C, B, 45).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">38°</text>
                            <text :x="angleLabelPos(B, A, C, 36).x" :y="angleLabelPos(B, A, C, 36).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">89°</text>
                            <text :x="angleLabelPos(C, B, A, 45).x" :y="angleLabelPos(C, B, A, 45).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Дуги углов --}}
                            <path :d="makeAngleArc(A, C, B, 28)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 28)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 28)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки углов --}}
                            <text :x="angleLabelPos(A, C, B, 45).x" :y="angleLabelPos(A, C, B, 45).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">54°</text>
                            <text :x="angleLabelPos(B, A, C, 45).x" :y="angleLabelPos(B, A, C, 45).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">58°</text>
                            <text :x="angleLabelPos(C, B, A, 45).x" :y="angleLabelPos(C, B, A, 45).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            {{-- Треугольник ABC --}}
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Продолжение стороны AC за точку C --}}
                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#94a3b8" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Дуга внутреннего угла C --}}
                            <path :d="makeAngleArc(C, A, B, 25)" fill="none" stroke="#d4a855" stroke-width="2"/>

                            {{-- Дуга внешнего угла C (искомый) --}}
                            <path :d="makeAngleArc(C, B, ext, 35)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метка внутреннего угла 115° --}}
                            <text :x="angleLabelPos(C, A, B, 40).x" :y="angleLabelPos(C, A, B, 40).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">115°</text>

                            {{-- Метка внешнего угла (?) --}}
                            <text :x="angleLabelPos(C, B, ext, 50).x" :y="angleLabelPos(C, B, ext, 50).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#94a3b8" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="makeAngleArc(C, A, B, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, ext, 45)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, A, B, 50).x" :y="angleLabelPos(C, A, B, 50).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">177°</text>
                            <text :x="angleLabelPos(C, B, ext, 60).x" :y="angleLabelPos(C, B, ext, 60).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#94a3b8" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="makeAngleArc(C, A, B, 25)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, ext, 35)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, A, B, 40).x" :y="angleLabelPos(C, A, B, 40).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">106°</text>
                            <text :x="angleLabelPos(C, B, ext, 50).x" :y="angleLabelPos(C, B, ext, 50).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#94a3b8" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="makeAngleArc(C, A, B, 25)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, ext, 40)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, A, B, 40).x" :y="angleLabelPos(C, A, B, 40).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">142°</text>
                            <text :x="angleLabelPos(C, B, ext, 55).x" :y="angleLabelPos(C, B, ext, 55).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Маркеры равенства AB = BC --}}
                            <line :x1="tickAB.x1" :y1="tickAB.y1" :x2="tickAB.x2" :y2="tickAB.y2" stroke="#7eb8da" stroke-width="2.5"/>
                            <line :x1="tickBC.x1" :y1="tickBC.y1" :x2="tickBC.x2" :y2="tickBC.y2" stroke="#7eb8da" stroke-width="2.5"/>

                            {{-- Дуга угла B (известный) --}}
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#d4a855" stroke-width="2"/>

                            {{-- Дуга угла C (искомый) --}}
                            <path :d="makeAngleArc(C, B, A, 28)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(B, A, C, 40).x" :y="angleLabelPos(B, A, C, 40).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">106°</text>
                            <text :x="angleLabelPos(C, B, A, 45).x" :y="angleLabelPos(C, B, A, 45).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="tickAB.x1" :y1="tickAB.y1" :x2="tickAB.x2" :y2="tickAB.y2" stroke="#7eb8da" stroke-width="2.5"/>
                            <line :x1="tickBC.x1" :y1="tickBC.y1" :x2="tickBC.x2" :y2="tickBC.y2" stroke="#7eb8da" stroke-width="2.5"/>

                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 28)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(B, A, C, 40).x" :y="angleLabelPos(B, A, C, 40).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">108°</text>
                            <text :x="angleLabelPos(C, B, A, 45).x" :y="angleLabelPos(C, B, A, 45).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="tickAB.x1" :y1="tickAB.y1" :x2="tickAB.x2" :y2="tickAB.y2" stroke="#7eb8da" stroke-width="2.5"/>
                            <line :x1="tickBC.x1" :y1="tickBC.y1" :x2="tickBC.x2" :y2="tickBC.y2" stroke="#7eb8da" stroke-width="2.5"/>

                            <path :d="makeAngleArc(B, A, C, 22)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 32)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(B, A, C, 38).x" :y="angleLabelPos(B, A, C, 38).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">132°</text>
                            <text :x="angleLabelPos(C, B, A, 48).x" :y="angleLabelPos(C, B, A, 48).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="tickAB.x1" :y1="tickAB.y1" :x2="tickAB.x2" :y2="tickAB.y2" stroke="#7eb8da" stroke-width="2.5"/>
                            <line :x1="tickBC.x1" :y1="tickBC.y1" :x2="tickBC.x2" :y2="tickBC.y2" stroke="#7eb8da" stroke-width="2.5"/>

                            <path :d="makeAngleArc(B, A, C, 20)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 35)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(B, A, C, 36).x" :y="angleLabelPos(B, A, C, 36).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">144°</text>
                            <text :x="angleLabelPos(C, B, A, 50).x" :y="angleLabelPos(C, B, A, 50).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            {{-- Треугольник ABC --}}
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Продолжение стороны AC за точку C --}}
                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#c8dce8" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Маркеры равных сторон AB = BC --}}
                            <line :x1="tickAB.x1" :y1="tickAB.y1" :x2="tickAB.x2" :y2="tickAB.y2" stroke="#7eb8da" stroke-width="2.5"/>
                            <line :x1="tickBC.x1" :y1="tickBC.y1" :x2="tickBC.x2" :y2="tickBC.y2" stroke="#7eb8da" stroke-width="2.5"/>

                            {{-- Дуга внешнего угла при C --}}
                            <path :d="makeAngleArc(C, B, ext, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>

                            {{-- Дуга искомого угла ABC --}}
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            {{-- C размещаем ниже точки, чтобы не налезать на линию внешнего угла --}}
                            <text :x="C.x" :y="C.y + 25"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метка внешнего угла 129° --}}
                            <text :x="angleLabelPos(C, B, ext, 48).x" :y="angleLabelPos(C, B, ext, 48).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">129°</text>
                            {{-- Метка искомого угла --}}
                            <text :x="angleLabelPos(B, A, C, 42).x" :y="angleLabelPos(B, A, C, 42).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#c8dce8" stroke-width="2" stroke-dasharray="6,4"/>

                            <line :x1="tickAB.x1" :y1="tickAB.y1" :x2="tickAB.x2" :y2="tickAB.y2" stroke="#7eb8da" stroke-width="2.5"/>
                            <line :x1="tickBC.x1" :y1="tickBC.y1" :x2="tickBC.x2" :y2="tickBC.y2" stroke="#7eb8da" stroke-width="2.5"/>

                            <path :d="makeAngleArc(C, B, ext, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            {{-- C размещаем ниже точки, чтобы не налезать на линию внешнего угла --}}
                            <text :x="C.x" :y="C.y + 25"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, B, ext, 48).x" :y="angleLabelPos(C, B, ext, 48).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">124°</text>
                            <text :x="angleLabelPos(B, A, C, 42).x" :y="angleLabelPos(B, A, C, 42).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#c8dce8" stroke-width="2" stroke-dasharray="6,4"/>

                            <line :x1="tickAB.x1" :y1="tickAB.y1" :x2="tickAB.x2" :y2="tickAB.y2" stroke="#7eb8da" stroke-width="2.5"/>
                            <line :x1="tickBC.x1" :y1="tickBC.y1" :x2="tickBC.x2" :y2="tickBC.y2" stroke="#7eb8da" stroke-width="2.5"/>

                            <path :d="makeAngleArc(C, B, ext, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            {{-- C размещаем ниже точки, чтобы не налезать на линию внешнего угла --}}
                            <text :x="C.x" :y="C.y + 25"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, B, ext, 48).x" :y="angleLabelPos(C, B, ext, 48).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">107°</text>
                            <text :x="angleLabelPos(B, A, C, 42).x" :y="angleLabelPos(B, A, C, 42).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 320 220" class="w-full max-w-[320px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="C.x" :y1="C.y" :x2="ext.x" :y2="ext.y"
                                stroke="#c8dce8" stroke-width="2" stroke-dasharray="6,4"/>

                            <line :x1="tickAB.x1" :y1="tickAB.y1" :x2="tickAB.x2" :y2="tickAB.y2" stroke="#7eb8da" stroke-width="2.5"/>
                            <line :x1="tickBC.x1" :y1="tickBC.y1" :x2="tickBC.x2" :y2="tickBC.y2" stroke="#7eb8da" stroke-width="2.5"/>

                            <path :d="makeAngleArc(C, B, ext, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            {{-- C размещаем ниже точки, чтобы не налезать на линию внешнего угла --}}
                            <text :x="C.x" :y="C.y + 25"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, B, ext, 48).x" :y="angleLabelPos(C, B, ext, 48).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">111°</text>
                            <text :x="angleLabelPos(B, A, C, 42).x" :y="angleLabelPos(B, A, C, 42).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 42° (180° − 2×69°)
                    </div>
                </div>
            </div>
        </div>

        {{-- VII) Острые углы прямоугольного треугольника --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-white">VII) Острые углы прямоугольного треугольника</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 25: острый угол 21° --}}
                <div x-data="task25RightAngles()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">25</span>
                        <div class="text-slate-200">
                            Один из острых углов прямоугольного треугольника равен 21°. Найдите его другой острый угол.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Прямой угол в A --}}
                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            {{-- Дуга угла C (данный) --}}
                            <path :d="makeAngleArc(C, A, B, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>

                            {{-- Дуга угла B (искомый) --}}
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, A, B, 48).x" :y="angleLabelPos(C, A, B, 48).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">21°</text>
                            <text :x="angleLabelPos(B, A, C, 42).x" :y="angleLabelPos(B, A, C, 42).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 69° (90° − 21°)
                    </div>
                </div>

                {{-- Задача 26: острый угол 33° --}}
                <div x-data="task26RightAngles()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">26</span>
                        <div class="text-slate-200">
                            Один из острых углов прямоугольного треугольника равен 33°. Найдите его другой острый угол.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(C, A, B, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, A, B, 48).x" :y="angleLabelPos(C, A, B, 48).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">33°</text>
                            <text :x="angleLabelPos(B, A, C, 42).x" :y="angleLabelPos(B, A, C, 42).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 57° (90° − 33°)
                    </div>
                </div>

                {{-- Задача 27: острый угол 47° --}}
                <div x-data="task27RightAngles()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">27</span>
                        <div class="text-slate-200">
                            Один из острых углов прямоугольного треугольника равен 47°. Найдите его другой острый угол.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(C, A, B, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, A, B, 48).x" :y="angleLabelPos(C, A, B, 48).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">47°</text>
                            <text :x="angleLabelPos(B, A, C, 42).x" :y="angleLabelPos(B, A, C, 42).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 43° (90° − 47°)
                    </div>
                </div>

                {{-- Задача 28: острый угол 63° --}}
                <div x-data="task28RightAngles()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">28</span>
                        <div class="text-slate-200">
                            Один из острых углов прямоугольного треугольника равен 63°. Найдите его другой острый угол.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(C, A, B, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="angleLabelPos(C, A, B, 48).x" :y="angleLabelPos(C, A, B, 48).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">63°</text>
                            <text :x="angleLabelPos(B, A, C, 42).x" :y="angleLabelPos(B, A, C, 42).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 27° (90° − 63°)
                    </div>
                </div>
            </div>
        </div>

        {{-- VIII) Высота, угол ABH --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-white">VIII) Высота, угол ABH</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 29: угол BAC = 37° --}}
                <div x-data="task29Height()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">29</span>
                        <div class="text-slate-200">
                            В остроугольном треугольнике ABC проведена высота BH, ∠BAC = 37°. Найдите угол ABH.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Высота BH --}}
                            <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Прямой угол в H --}}
                            <path :d="rightAnglePath(H, B, C, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            {{-- Дуга угла A (данный) --}}
                            <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>

                            {{-- Дуга угла ABH (искомый) --}}
                            <path :d="makeAngleArc(B, A, H, 25)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="H.x" :cy="H.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="H.x" :y="H.y + 20"
                                fill="#5a9fcf" font-size="16" class="geo-label" text-anchor="middle">H</text>

                            <text :x="angleLabelPos(A, C, B, 48).x" :y="angleLabelPos(A, C, B, 48).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">37°</text>
                            <text :x="angleLabelPos(B, A, H, 42).x" :y="angleLabelPos(B, A, H, 42).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 53° (90° − 37°)
                    </div>
                </div>

                {{-- Задача 30: угол BAC = 29° --}}
                <div x-data="task30Height()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">30</span>
                        <div class="text-slate-200">
                            В остроугольном треугольнике ABC проведена высота BH, ∠BAC = 29°. Найдите угол ABH.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>
                            <path :d="rightAnglePath(H, B, C, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, H, 25)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="H.x" :cy="H.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="H.x" :y="H.y + 20"
                                fill="#5a9fcf" font-size="16" class="geo-label" text-anchor="middle">H</text>

                            <text :x="angleLabelPos(A, C, B, 48).x" :y="angleLabelPos(A, C, B, 48).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">29°</text>
                            <text :x="angleLabelPos(B, A, H, 42).x" :y="angleLabelPos(B, A, H, 42).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 61° (90° − 29°)
                    </div>
                </div>

                {{-- Задача 31: угол BAC = 46° --}}
                <div x-data="task31Height()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">31</span>
                        <div class="text-slate-200">
                            В остроугольном треугольнике ABC проведена высота BH, ∠BAC = 46°. Найдите угол ABH.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>
                            <path :d="rightAnglePath(H, B, C, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, H, 25)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="H.x" :cy="H.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="H.x" :y="H.y + 20"
                                fill="#5a9fcf" font-size="16" class="geo-label" text-anchor="middle">H</text>

                            <text :x="angleLabelPos(A, C, B, 48).x" :y="angleLabelPos(A, C, B, 48).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">46°</text>
                            <text :x="angleLabelPos(B, A, H, 42).x" :y="angleLabelPos(B, A, H, 42).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 44° (90° − 46°)
                    </div>
                </div>

                {{-- Задача 32: угол BAC = 82° --}}
                <div x-data="task32Height()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">32</span>
                        <div class="text-slate-200">
                            В остроугольном треугольнике ABC проведена высота BH, ∠BAC = 82°. Найдите угол ABH.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>
                            <path :d="rightAnglePath(H, B, C, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#d4a855" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, H, 25)" fill="none" stroke="#5a9fcf" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="H.x" :cy="H.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="H.x" :y="H.y + 20"
                                fill="#5a9fcf" font-size="16" class="geo-label" text-anchor="middle">H</text>

                            <text :x="angleLabelPos(A, C, B, 48).x" :y="angleLabelPos(A, C, B, 48).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">82°</text>
                            <text :x="angleLabelPos(B, A, H, 42).x" :y="angleLabelPos(B, A, H, 42).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 8° (90° − 82°)
                    </div>
                </div>
            </div>
        </div>

        {{-- IX) Площадь по катетам --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-white">IX) Площадь по катетам</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 33: катеты 4 и 10 --}}
                <div x-data="task33Area()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">33</span>
                        <div class="text-slate-200">
                            Два катета прямоугольного треугольника равны 4 и 10. Найдите площадь этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Прямой угол в A --}}
                            <path :d="rightAnglePath(A, B, C, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки длин катетов --}}
                            <text :x="labelOnSegment(A, B, 10, true).x" :y="labelOnSegment(A, B, 10, true).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">4</text>
                            <text :x="labelOnSegment(A, C, 14).x" :y="labelOnSegment(A, C, 14).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">10</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 20 (½ · 4 · 10)
                    </div>
                </div>

                {{-- Задача 34: катеты 14 и 5 --}}
                <div x-data="task34Area()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">34</span>
                        <div class="text-slate-200">
                            Два катета прямоугольного треугольника равны 14 и 5. Найдите площадь этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, B, C, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="labelOnSegment(A, B, 10, true).x" :y="labelOnSegment(A, B, 10, true).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">5</text>
                            <text :x="labelOnSegment(A, C, 14).x" :y="labelOnSegment(A, C, 14).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">14</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 35 (½ · 14 · 5)
                    </div>
                </div>

                {{-- Задача 35: катеты 7 и 12 --}}
                <div x-data="task35Area()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">35</span>
                        <div class="text-slate-200">
                            Два катета прямоугольного треугольника равны 7 и 12. Найдите площадь этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, B, C, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="labelOnSegment(A, B, 10, true).x" :y="labelOnSegment(A, B, 10, true).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">7</text>
                            <text :x="labelOnSegment(A, C, 14).x" :y="labelOnSegment(A, C, 14).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">12</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 42 (½ · 7 · 12)
                    </div>
                </div>

                {{-- Задача 36: катеты 18 и 7 --}}
                <div x-data="task36Area()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">36</span>
                        <div class="text-slate-200">
                            Два катета прямоугольного треугольника равны 18 и 7. Найдите площадь этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, B, C, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="labelOnSegment(A, B, 10, true).x" :y="labelOnSegment(A, B, 10, true).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">7</text>
                            <text :x="labelOnSegment(A, C, 14).x" :y="labelOnSegment(A, C, 14).y"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">18</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 63 (½ · 18 · 7)
                    </div>
                </div>
            </div>
        </div>

        {{-- X) Площадь по высоте --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-amber-500">
                <h3 class="text-lg font-semibold text-white">X) Площадь по высоте</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 37: сторона 16, высота 19 --}}
                <div x-data="task37AreaHeight()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">37</span>
                        <div class="text-slate-200">
                            Сторона треугольника равна 16, а высота, проведённая к этой стороне, равна 19. Найдите площадь треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            {{-- Треугольник --}}
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Высота BH --}}
                            <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Прямой угол в H --}}
                            <path :d="rightAnglePath(H, B, C, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            {{-- Вершины --}}
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="H.x" :cy="H.y" r="4" fill="#5a9fcf"/>

                            {{-- Подписи вершин --}}
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="H.x + 12" :y="H.y + 15"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">H</text>

                            {{-- Метка стороны AC --}}
                            <text :x="(A.x + C.x) / 2" :y="A.y + 22"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">16</text>

                            {{-- Метка высоты BH --}}
                            <text :x="(B.x + H.x) / 2 + 16" :y="(B.y + H.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">19</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 152 (½ · 16 · 19)
                    </div>
                </div>

                {{-- Задача 38: сторона 14, высота 31 --}}
                <div x-data="task38AreaHeight()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">38</span>
                        <div class="text-slate-200">
                            Сторона треугольника равна 14, а высота, проведённая к этой стороне, равна 31. Найдите площадь треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="rightAnglePath(H, B, C, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="H.x" :cy="H.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="H.x + 12" :y="H.y + 15"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">H</text>

                            <text :x="(A.x + C.x) / 2" :y="A.y + 22"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">14</text>

                            <text :x="(B.x + H.x) / 2 + 16" :y="(B.y + H.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">31</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 217 (½ · 14 · 31)
                    </div>
                </div>

                {{-- Задача 39: сторона 29, высота 12 --}}
                <div x-data="task39AreaHeight()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">39</span>
                        <div class="text-slate-200">
                            Сторона треугольника равна 29, а высота, проведённая к этой стороне, равна 12. Найдите площадь треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="rightAnglePath(H, B, C, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="H.x" :cy="H.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="H.x + 12" :y="H.y + 15"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">H</text>

                            <text :x="(A.x + C.x) / 2" :y="A.y + 22"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">29</text>

                            <text :x="(B.x + H.x) / 2 + 16" :y="(B.y + H.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">12</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 174 (½ · 29 · 12)
                    </div>
                </div>

                {{-- Задача 40: сторона 18, высота 17 --}}
                <div x-data="task40AreaHeight()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">40</span>
                        <div class="text-slate-200">
                            Сторона треугольника равна 18, а высота, проведённая к этой стороне, равна 17. Найдите площадь треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="6,4"/>

                            <path :d="rightAnglePath(H, B, C, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="H.x" :cy="H.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="H.x + 12" :y="H.y + 15"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">H</text>

                            <text :x="(A.x + C.x) / 2" :y="A.y + 22"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">18</text>

                            <text :x="(B.x + H.x) / 2 + 16" :y="(B.y + H.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">17</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 153 (½ · 18 · 17)
                    </div>
                </div>
            </div>
        </div>

        {{-- XI) Подобные треугольники (средняя линия) --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-purple-500">
                <h3 class="text-lg font-semibold text-white">XI) Подобные треугольники (средняя линия)</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 41: AB=21, BC=22, AC=28 --}}
                <div x-data="task41Midline()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">41</span>
                        <div class="text-slate-200">
                            Точки M и N являются серединами сторон AB и BC треугольника ABC, сторона AB равна 21, сторона BC равна 22, сторона AC равна 28. Найдите MN.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Средняя линия MN --}}
                            <line :x1="M.x" :y1="M.y" :x2="N.x" :y2="N.y"
                                stroke="#5a9fcf" stroke-width="2.5"/>

                            {{-- Маркеры равенства AM = MB --}}
                            <line :x1="tickAM.x1" :y1="tickAM.y1" :x2="tickAM.x2" :y2="tickAM.y2"
                                stroke="#7eb8da" stroke-width="2"/>
                            <line :x1="tickMB.x1" :y1="tickMB.y1" :x2="tickMB.x2" :y2="tickMB.y2"
                                stroke="#7eb8da" stroke-width="2"/>

                            {{-- Маркеры равенства BN = NC (двойные черточки) --}}
                            <line :x1="dblTickBN.tick1.x1" :y1="dblTickBN.tick1.y1" :x2="dblTickBN.tick1.x2" :y2="dblTickBN.tick1.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="dblTickBN.tick2.x1" :y1="dblTickBN.tick2.y1" :x2="dblTickBN.tick2.x2" :y2="dblTickBN.tick2.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="dblTickNC.tick1.x1" :y1="dblTickNC.tick1.y1" :x2="dblTickNC.tick1.x2" :y2="dblTickNC.tick1.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="dblTickNC.tick2.x1" :y1="dblTickNC.tick2.y1" :x2="dblTickNC.tick2.x2" :y2="dblTickNC.tick2.y2"
                                stroke="#d4a855" stroke-width="2"/>

                            {{-- Вершины --}}
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <circle :cx="N.x" :cy="N.y" r="4" fill="#5a9fcf"/>

                            {{-- Подписи --}}
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x - 16" :y="M.y"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">M</text>
                            <text :x="N.x + 16" :y="N.y"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">N</text>

                            {{-- Метка AC --}}
                            <text :x="(A.x + C.x) / 2" :y="A.y + 20"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">28</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 14 (MN = AC/2)
                    </div>
                </div>

                {{-- Задача 42: AB=66, BC=37, AC=74 --}}
                <div x-data="task42Midline()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">42</span>
                        <div class="text-slate-200">
                            Точки M и N являются серединами сторон AB и BC треугольника ABC, сторона AB равна 66, сторона BC равна 37, сторона AC равна 74. Найдите MN.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="M.x" :y1="M.y" :x2="N.x" :y2="N.y"
                                stroke="#5a9fcf" stroke-width="2.5"/>

                            <line :x1="tickAM.x1" :y1="tickAM.y1" :x2="tickAM.x2" :y2="tickAM.y2"
                                stroke="#7eb8da" stroke-width="2"/>
                            <line :x1="tickMB.x1" :y1="tickMB.y1" :x2="tickMB.x2" :y2="tickMB.y2"
                                stroke="#7eb8da" stroke-width="2"/>

                            {{-- Двойные черточки для BN = NC --}}
                            <line :x1="dblTickBN.tick1.x1" :y1="dblTickBN.tick1.y1" :x2="dblTickBN.tick1.x2" :y2="dblTickBN.tick1.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="dblTickBN.tick2.x1" :y1="dblTickBN.tick2.y1" :x2="dblTickBN.tick2.x2" :y2="dblTickBN.tick2.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="dblTickNC.tick1.x1" :y1="dblTickNC.tick1.y1" :x2="dblTickNC.tick1.x2" :y2="dblTickNC.tick1.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="dblTickNC.tick2.x1" :y1="dblTickNC.tick2.y1" :x2="dblTickNC.tick2.x2" :y2="dblTickNC.tick2.y2"
                                stroke="#d4a855" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <circle :cx="N.x" :cy="N.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x - 16" :y="M.y"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">M</text>
                            <text :x="N.x + 16" :y="N.y"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">N</text>

                            <text :x="(A.x + C.x) / 2" :y="A.y + 20"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">74</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 37 (MN = AC/2)
                    </div>
                </div>

                {{-- Задача 43: AB=26, BC=39, AC=48 --}}
                <div x-data="task43Midline()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">43</span>
                        <div class="text-slate-200">
                            Точки M и N являются серединами сторон AB и BC треугольника ABC, сторона AB равна 26, сторона BC равна 39, сторона AC равна 48. Найдите MN.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="M.x" :y1="M.y" :x2="N.x" :y2="N.y"
                                stroke="#5a9fcf" stroke-width="2.5"/>

                            <line :x1="tickAM.x1" :y1="tickAM.y1" :x2="tickAM.x2" :y2="tickAM.y2"
                                stroke="#7eb8da" stroke-width="2"/>
                            <line :x1="tickMB.x1" :y1="tickMB.y1" :x2="tickMB.x2" :y2="tickMB.y2"
                                stroke="#7eb8da" stroke-width="2"/>

                            {{-- Двойные черточки для BN = NC --}}
                            <line :x1="dblTickBN.tick1.x1" :y1="dblTickBN.tick1.y1" :x2="dblTickBN.tick1.x2" :y2="dblTickBN.tick1.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="dblTickBN.tick2.x1" :y1="dblTickBN.tick2.y1" :x2="dblTickBN.tick2.x2" :y2="dblTickBN.tick2.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="dblTickNC.tick1.x1" :y1="dblTickNC.tick1.y1" :x2="dblTickNC.tick1.x2" :y2="dblTickNC.tick1.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="dblTickNC.tick2.x1" :y1="dblTickNC.tick2.y1" :x2="dblTickNC.tick2.x2" :y2="dblTickNC.tick2.y2"
                                stroke="#d4a855" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <circle :cx="N.x" :cy="N.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x - 16" :y="M.y"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">M</text>
                            <text :x="N.x + 16" :y="N.y"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">N</text>

                            <text :x="(A.x + C.x) / 2" :y="A.y + 20"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">48</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 24 (MN = AC/2)
                    </div>
                </div>

                {{-- Задача 44: AB=42, BC=44, AC=62 --}}
                <div x-data="task44Midline()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">44</span>
                        <div class="text-slate-200">
                            Точки M и N являются серединами сторон AB и BC треугольника ABC, сторона AB равна 42, сторона BC равна 44, сторона AC равна 62. Найдите MN.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <line :x1="M.x" :y1="M.y" :x2="N.x" :y2="N.y"
                                stroke="#5a9fcf" stroke-width="2.5"/>

                            <line :x1="tickAM.x1" :y1="tickAM.y1" :x2="tickAM.x2" :y2="tickAM.y2"
                                stroke="#7eb8da" stroke-width="2"/>
                            <line :x1="tickMB.x1" :y1="tickMB.y1" :x2="tickMB.x2" :y2="tickMB.y2"
                                stroke="#7eb8da" stroke-width="2"/>

                            {{-- Двойные черточки для BN = NC --}}
                            <line :x1="dblTickBN.tick1.x1" :y1="dblTickBN.tick1.y1" :x2="dblTickBN.tick1.x2" :y2="dblTickBN.tick1.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="dblTickBN.tick2.x1" :y1="dblTickBN.tick2.y1" :x2="dblTickBN.tick2.x2" :y2="dblTickBN.tick2.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="dblTickNC.tick1.x1" :y1="dblTickNC.tick1.y1" :x2="dblTickNC.tick1.x2" :y2="dblTickNC.tick1.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="dblTickNC.tick2.x1" :y1="dblTickNC.tick2.y1" :x2="dblTickNC.tick2.x2" :y2="dblTickNC.tick2.y2"
                                stroke="#d4a855" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <circle :cx="N.x" :cy="N.y" r="4" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x - 16" :y="M.y"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">M</text>
                            <text :x="N.x + 16" :y="N.y"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">N</text>

                            <text :x="(A.x + C.x) / 2" :y="A.y + 20"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">62</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 31 (MN = AC/2)
                    </div>
                </div>
            </div>
        </div>

        {{-- XII) Теорема Пифагора --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-white">XII) Теорема Пифагора</h3>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Прямой угол в A --}}
                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

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
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
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

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

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
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 17
                    </div>
                </div>

                {{-- Задача 47: катеты 20 и 21 --}}
                <div x-data="task47Pythagoras()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">47</span>
                        <div class="text-slate-200">
                            Катеты прямоугольного треугольника равны 20 и 21. Найдите гипотенузу этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="labelOnSegment(A, C, 8, true).x" :y="labelOnSegment(A, C, 8, true).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">21</text>
                            <text :x="labelOnSegment(A, B, 8).x" :y="labelOnSegment(A, B, 8).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">20</text>

                            <text :x="labelOnSegment(B, C, 16).x" :y="labelOnSegment(B, C, 16).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 29
                    </div>
                </div>

                {{-- Задача 48: катеты 9 и 12 --}}
                <div x-data="task48Pythagoras()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">48</span>
                        <div class="text-slate-200">
                            Катеты прямоугольного треугольника равны 9 и 12. Найдите гипотенузу этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="labelOnSegment(A, C, 8, true).x" :y="labelOnSegment(A, C, 8, true).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">12</text>
                            <text :x="labelOnSegment(A, B, 8).x" :y="labelOnSegment(A, B, 8).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">9</text>

                            <text :x="labelOnSegment(B, C, 16).x" :y="labelOnSegment(B, C, 16).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 15
                    </div>
                </div>
            </div>
        </div>

        {{-- XIII) Теорема Пифагора: найти катет --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-cyan-500">
                <h3 class="text-lg font-semibold text-white">XIII) Теорема Пифагора: найти катет</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 49: катет 7, гипотенуза 25 --}}
                <div x-data="task49PythagorasLeg()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">49</span>
                        <div class="text-slate-200">
                            В прямоугольном треугольнике катет и гипотенуза равны 7 и 25 соответственно. Найдите другой катет этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Известный катет AB = 7 --}}
                            <text :x="labelOnSegment(A, B, 8).x" :y="labelOnSegment(A, B, 8).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">7</text>
                            {{-- Гипотенуза BC = 25 --}}
                            <text :x="labelOnSegment(B, C, 14).x" :y="labelOnSegment(B, C, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">25</text>
                            {{-- Искомый катет AC --}}
                            <text :x="labelOnSegment(A, C, 10, true).x" :y="labelOnSegment(A, C, 10, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 24
                    </div>
                </div>

                {{-- Задача 50: катет 40, гипотенуза 41 --}}
                <div x-data="task50PythagorasLeg()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">50</span>
                        <div class="text-slate-200">
                            В прямоугольном треугольнике катет и гипотенуза равны 40 и 41 соответственно. Найдите другой катет этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="labelOnSegment(A, B, 8).x" :y="labelOnSegment(A, B, 8).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">40</text>
                            <text :x="labelOnSegment(B, C, 14).x" :y="labelOnSegment(B, C, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">41</text>
                            <text :x="labelOnSegment(A, C, 10, true).x" :y="labelOnSegment(A, C, 10, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 9
                    </div>
                </div>

                {{-- Задача 51: катет 8, гипотенуза 17 --}}
                <div x-data="task51PythagorasLeg()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">51</span>
                        <div class="text-slate-200">
                            В прямоугольном треугольнике катет и гипотенуза равны 8 и 17 соответственно. Найдите другой катет этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="labelOnSegment(A, B, 8).x" :y="labelOnSegment(A, B, 8).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">8</text>
                            <text :x="labelOnSegment(B, C, 14).x" :y="labelOnSegment(B, C, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">17</text>
                            <text :x="labelOnSegment(A, C, 10, true).x" :y="labelOnSegment(A, C, 10, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 15
                    </div>
                </div>

                {{-- Задача 52: катет 16, гипотенуза 34 --}}
                <div x-data="task52PythagorasLeg()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">52</span>
                        <div class="text-slate-200">
                            В прямоугольном треугольнике катет и гипотенуза равны 16 и 34 соответственно. Найдите другой катет этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            <path :d="rightAnglePath(A, C, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="labelOnSegment(A, B, 8).x" :y="labelOnSegment(A, B, 8).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">16</text>
                            <text :x="labelOnSegment(B, C, 14).x" :y="labelOnSegment(B, C, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">34</text>
                            <text :x="labelOnSegment(A, C, 10, true).x" :y="labelOnSegment(A, C, 10, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 30
                    </div>
                </div>
            </div>
        </div>

        {{-- XIV) Равносторонний треугольник --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-emerald-500">
                <h3 class="text-lg font-semibold text-white">XIV) Равносторонний треугольник</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 53: биссектриса = 12√3 --}}
                <div x-data="task53Equilateral()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">53</span>
                        <div class="text-slate-200">
                            Биссектриса равностороннего треугольника равна $12\sqrt{3}$. Найдите сторону этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Высота/медиана/биссектриса BM --}}
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2.5" stroke-dasharray="6,4"/>

                            {{-- Прямой угол в M --}}
                            <path :d="rightAnglePath(M, A, B, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            {{-- Маркеры равенства сторон --}}
                            <line :x1="tickAB.x1" :y1="tickAB.y1" :x2="tickAB.x2" :y2="tickAB.y2"
                                stroke="#7eb8da" stroke-width="2"/>
                            <line :x1="tickBC.x1" :y1="tickBC.y1" :x2="tickBC.x2" :y2="tickBC.y2"
                                stroke="#7eb8da" stroke-width="2"/>

                            {{-- Вершины --}}
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>

                            {{-- Подписи --}}
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 20"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">M</text>

                            {{-- Метка биссектрисы --}}
                            <text :x="B.x + 22" :y="(B.y + M.y) / 2"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="start">$12\sqrt{3}$</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 24 (a = 2h/√3)
                    </div>
                </div>

                {{-- Задача 54: биссектриса = 13√3 --}}
                <div x-data="task54Equilateral()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">54</span>
                        <div class="text-slate-200">
                            Биссектриса равностороннего треугольника равна $13\sqrt{3}$. Найдите сторону этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2.5" stroke-dasharray="6,4"/>
                            <path :d="rightAnglePath(M, A, B, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <line :x1="tickAB.x1" :y1="tickAB.y1" :x2="tickAB.x2" :y2="tickAB.y2"
                                stroke="#7eb8da" stroke-width="2"/>
                            <line :x1="tickBC.x1" :y1="tickBC.y1" :x2="tickBC.x2" :y2="tickBC.y2"
                                stroke="#7eb8da" stroke-width="2"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 20"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">M</text>
                            <text :x="B.x + 22" :y="(B.y + M.y) / 2"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="start">$13\sqrt{3}$</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 26
                    </div>
                </div>

                {{-- Задача 55: медиана = 11√3 --}}
                <div x-data="task55Equilateral()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">55</span>
                        <div class="text-slate-200">
                            Медиана равностороннего треугольника равна $11\sqrt{3}$. Найдите сторону этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2.5" stroke-dasharray="6,4"/>
                            <path :d="rightAnglePath(M, A, B, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <line :x1="tickAC1.x1" :y1="tickAC1.y1" :x2="tickAC1.x2" :y2="tickAC1.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="tickAC2.x1" :y1="tickAC2.y1" :x2="tickAC2.x2" :y2="tickAC2.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 20"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">M</text>
                            <text :x="B.x + 22" :y="(B.y + M.y) / 2"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="start">$11\sqrt{3}$</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 22
                    </div>
                </div>

                {{-- Задача 56: медиана = 14√3 --}}
                <div x-data="task56Equilateral()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">56</span>
                        <div class="text-slate-200">
                            Медиана равностороннего треугольника равна $14\sqrt{3}$. Найдите сторону этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2.5" stroke-dasharray="6,4"/>
                            <path :d="rightAnglePath(M, A, B, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <line :x1="tickAC1.x1" :y1="tickAC1.y1" :x2="tickAC1.x2" :y2="tickAC1.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="tickAC2.x1" :y1="tickAC2.y1" :x2="tickAC2.x2" :y2="tickAC2.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 20"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">M</text>
                            <text :x="B.x + 22" :y="(B.y + M.y) / 2"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="start">$14\sqrt{3}$</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 28
                    </div>
                </div>

                {{-- Задача 57: сторона = 16√3, найти биссектрису --}}
                <div x-data="task57Equilateral()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">57</span>
                        <div class="text-slate-200">
                            Сторона равностороннего треугольника равна $16\sqrt{3}$. Найдите биссектрису этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2.5" stroke-dasharray="6,4"/>
                            <path :d="rightAnglePath(M, A, B, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <line :x1="tickAB.x1" :y1="tickAB.y1" :x2="tickAB.x2" :y2="tickAB.y2"
                                stroke="#7eb8da" stroke-width="2"/>
                            <line :x1="tickBC.x1" :y1="tickBC.y1" :x2="tickBC.x2" :y2="tickBC.y2"
                                stroke="#7eb8da" stroke-width="2"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="(A.x + C.x) / 2" :y="A.y + 20"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">$16\sqrt{3}$</text>
                            <text :x="B.x + 22" :y="(B.y + M.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="start">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 24 (h = a·√3/2)
                    </div>
                </div>

                {{-- Задача 58: сторона = 14√3, найти биссектрису --}}
                <div x-data="task58Equilateral()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">58</span>
                        <div class="text-slate-200">
                            Сторона равностороннего треугольника равна $14\sqrt{3}$. Найдите биссектрису этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2.5" stroke-dasharray="6,4"/>
                            <path :d="rightAnglePath(M, A, B, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <line :x1="tickAB.x1" :y1="tickAB.y1" :x2="tickAB.x2" :y2="tickAB.y2"
                                stroke="#7eb8da" stroke-width="2"/>
                            <line :x1="tickBC.x1" :y1="tickBC.y1" :x2="tickBC.x2" :y2="tickBC.y2"
                                stroke="#7eb8da" stroke-width="2"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="(A.x + C.x) / 2" :y="A.y + 20"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">$14\sqrt{3}$</text>
                            <text :x="B.x + 22" :y="(B.y + M.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="start">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 21
                    </div>
                </div>

                {{-- Задача 59: сторона = 10√3, найти медиану --}}
                <div x-data="task59Equilateral()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">59</span>
                        <div class="text-slate-200">
                            Сторона равностороннего треугольника равна $10\sqrt{3}$. Найдите медиану этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2.5" stroke-dasharray="6,4"/>
                            <path :d="rightAnglePath(M, A, B, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <line :x1="tickAC1.x1" :y1="tickAC1.y1" :x2="tickAC1.x2" :y2="tickAC1.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="tickAC2.x1" :y1="tickAC2.y1" :x2="tickAC2.x2" :y2="tickAC2.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 20"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">M</text>
                            <text :x="(A.x + C.x) / 2" :y="A.y + 20"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">$10\sqrt{3}$</text>
                            <text :x="B.x + 22" :y="(B.y + M.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="start">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 15
                    </div>
                </div>

                {{-- Задача 60: сторона = 8√3, найти медиану --}}
                <div x-data="task60Equilateral()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">60</span>
                        <div class="text-slate-200">
                            Сторона равностороннего треугольника равна $8\sqrt{3}$. Найдите медиану этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2.5" stroke-dasharray="6,4"/>
                            <path :d="rightAnglePath(M, A, B, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <line :x1="tickAC1.x1" :y1="tickAC1.y1" :x2="tickAC1.x2" :y2="tickAC1.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <line :x1="tickAC2.x1" :y1="tickAC2.y1" :x2="tickAC2.x2" :y2="tickAC2.y2"
                                stroke="#d4a855" stroke-width="2"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 20"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">M</text>
                            <text :x="(A.x + C.x) / 2" :y="A.y + 20"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">$8\sqrt{3}$</text>
                            <text :x="B.x + 22" :y="(B.y + M.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="start">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 12
                    </div>
                </div>

                {{-- Задача 61: сторона = 18√3, найти высоту --}}
                <div x-data="task61Equilateral()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">61</span>
                        <div class="text-slate-200">
                            Сторона равностороннего треугольника равна $18\sqrt{3}$. Найдите высоту этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2.5"/>
                            <path :d="rightAnglePath(M, A, B, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 20"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">H</text>
                            <text :x="(A.x + C.x) / 2" :y="A.y + 20"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">$18\sqrt{3}$</text>
                            <text :x="B.x + 22" :y="(B.y + M.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="start">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 27
                    </div>
                </div>

                {{-- Задача 62: сторона = 12√3, найти высоту --}}
                <div x-data="task62Equilateral()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">62</span>
                        <div class="text-slate-200">
                            Сторона равностороннего треугольника равна $12\sqrt{3}$. Найдите высоту этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#5a9fcf" stroke-width="2.5"/>
                            <path :d="rightAnglePath(M, A, B, 12)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y + 20"
                                fill="#5a9fcf" font-size="14" class="geo-label" text-anchor="middle">H</text>
                            <text :x="(A.x + C.x) / 2" :y="A.y + 20"
                                fill="#d4a855" font-size="12" class="geo-label" text-anchor="middle">$12\sqrt{3}$</text>
                            <text :x="B.x + 22" :y="(B.y + M.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="start">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 18
                    </div>
                </div>
            </div>
        </div>

        {{-- XV) Радиус описанной окружности --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-purple-500">
                <h3 class="text-lg font-semibold text-white">XV) Радиус описанной окружности</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 63: AC=6, BC=8 --}}
                <div x-data="task63Circumradius()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">63</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AC=6, BC=8, угол C равен 90°. Найдите радиус описанной окружности этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            {{-- Описанная окружность --}}
                            <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#a855f7" stroke-width="2" stroke-dasharray="6,4"/>

                            {{-- Треугольник --}}
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                            {{-- Прямой угол в C --}}
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>

                            {{-- Центр окружности (середина гипотенузы) --}}
                            <circle :cx="O.x" :cy="O.y" r="4" fill="#a855f7"/>

                            {{-- Радиус --}}
                            <line :x1="O.x" :y1="O.y" :x2="C.x" :y2="C.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="4,3"/>

                            {{-- Вершины --}}
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                            {{-- Подписи вершин --}}
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="O.x + 15" :y="O.y - 10"
                                fill="#a855f7" font-size="14" class="geo-label" text-anchor="start">O</text>

                            {{-- Метки длин --}}
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">6</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">8</text>
                            <text :x="(O.x + C.x) / 2 + 12" :y="(O.y + C.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="start">R=?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 5 (R = AB/2 = 10/2)
                    </div>
                </div>

                {{-- Задача 64: AC=40, BC=30 --}}
                <div x-data="task64Circumradius()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">64</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AC=40, BC=30, угол C равен 90°. Найдите радиус описанной окружности этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#a855f7" stroke-width="2" stroke-dasharray="6,4"/>
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <circle :cx="O.x" :cy="O.y" r="4" fill="#a855f7"/>
                            <line :x1="O.x" :y1="O.y" :x2="C.x" :y2="C.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="4,3"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="O.x + 15" :y="O.y - 10"
                                fill="#a855f7" font-size="14" class="geo-label" text-anchor="start">O</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">40</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">30</text>
                            <text :x="(O.x + C.x) / 2 + 12" :y="(O.y + C.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="start">R=?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 25 (R = AB/2 = 50/2)
                    </div>
                </div>

                {{-- Задача 65: AC=12, BC=5 --}}
                <div x-data="task65Circumradius()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">65</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AC=12, BC=5, угол C равен 90°. Найдите радиус описанной окружности этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#a855f7" stroke-width="2" stroke-dasharray="6,4"/>
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <circle :cx="O.x" :cy="O.y" r="4" fill="#a855f7"/>
                            <line :x1="O.x" :y1="O.y" :x2="C.x" :y2="C.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="4,3"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="O.x + 15" :y="O.y - 10"
                                fill="#a855f7" font-size="14" class="geo-label" text-anchor="start">O</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">12</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">5</text>
                            <text :x="(O.x + C.x) / 2 + 12" :y="(O.y + C.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="start">R=?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 6,5 (R = AB/2 = 13/2)
                    </div>
                </div>

                {{-- Задача 66: AC=7, BC=24 --}}
                <div x-data="task66Circumradius()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">66</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AC=7, BC=24, угол C равен 90°. Найдите радиус описанной окружности этого треугольника.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <circle :cx="O.x" :cy="O.y" :r="R" fill="none" stroke="#a855f7" stroke-width="2" stroke-dasharray="6,4"/>
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <circle :cx="O.x" :cy="O.y" r="4" fill="#a855f7"/>
                            <line :x1="O.x" :y1="O.y" :x2="C.x" :y2="C.y"
                                stroke="#5a9fcf" stroke-width="2" stroke-dasharray="4,3"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="O.x + 15" :y="O.y - 10"
                                fill="#a855f7" font-size="14" class="geo-label" text-anchor="start">O</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">7</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">24</text>
                            <text :x="(O.x + C.x) / 2 + 12" :y="(O.y + C.y) / 2"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="start">R=?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 12,5 (R = AB/2 = 25/2)
                    </div>
                </div>
            </div>
        </div>

        {{-- XVI) Синус, косинус, тангенс острого угла --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-orange-500">
                <h3 class="text-lg font-semibold text-white">XVI) Синус, косинус, тангенс острого угла</h3>
            </div>

            {{-- Подраздел: Найти sinB --}}
            <div class="bg-slate-700/30 rounded-lg p-3 mb-4">
                <span class="text-orange-400 font-medium">Найти sinB (sinB = противолежащий / гипотенуза = AC / AB)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                {{-- Задача 67: AC=11, AB=20, найти sinB --}}
                <div x-data="task67Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">67</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, AC=11, AB=20. Найдите sinB.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">11</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">20</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 0,55 (sinB = AC/AB = 11/20)
                    </div>
                </div>

                {{-- Задача 68 --}}
                <div x-data="task68Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">68</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, AC=7, AB=25. Найдите sinB.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">7</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">25</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 0,28 (sinB = 7/25)
                    </div>
                </div>

                {{-- Задача 69 --}}
                <div x-data="task69Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">69</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, AC=4, AB=5. Найдите sinB.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">4</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">5</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 0,8 (sinB = 4/5)
                    </div>
                </div>

                {{-- Задача 70 --}}
                <div x-data="task70Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">70</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, AC=24, AB=25. Найдите sinB.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">24</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">25</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 0,96 (sinB = 24/25)
                    </div>
                </div>
            </div>

            {{-- Подраздел: Найти cosB --}}
            <div class="bg-slate-700/30 rounded-lg p-3 mb-4">
                <span class="text-orange-400 font-medium">Найти cosB (cosB = прилежащий / гипотенуза = BC / AB)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                {{-- Задача 71 --}}
                <div x-data="task71Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">71</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, BC=13, AB=20. Найдите cosB.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">13</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">20</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 0,65 (cosB = 13/20)
                    </div>
                </div>

                {{-- Задача 72 --}}
                <div x-data="task72Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">72</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, BC=72, AB=75. Найдите cosB.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">72</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">75</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 0,96 (cosB = 72/75 = 24/25)
                    </div>
                </div>

                {{-- Задача 73 --}}
                <div x-data="task73Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">73</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, BC=30, AB=50. Найдите cosB.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">30</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">50</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 0,6 (cosB = 30/50 = 3/5)
                    </div>
                </div>

                {{-- Задача 74 --}}
                <div x-data="task74Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">74</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, BC=14, AB=50. Найдите cosB.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">14</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">50</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 0,28 (cosB = 14/50 = 7/25)
                    </div>
                </div>
            </div>

            {{-- Подраздел: Найти tgB --}}
            <div class="bg-slate-700/30 rounded-lg p-3 mb-4">
                <span class="text-orange-400 font-medium">Найти tgB (tgB = противолежащий / прилежащий = AC / BC)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                {{-- Задача 75 --}}
                <div x-data="task75Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">75</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, BC=10, AC=7. Найдите tgB.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">7</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">10</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 0,7 (tgB = AC/BC = 7/10)
                    </div>
                </div>

                {{-- Задача 76 --}}
                <div x-data="task76Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">76</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, BC=15, AC=3. Найдите tgB.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">3</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">15</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 0,2 (tgB = 3/15 = 1/5)
                    </div>
                </div>

                {{-- Задача 77 --}}
                <div x-data="task77Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">77</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, BC=9, AC=27. Найдите tgB.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">27</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">9</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 3 (tgB = 27/9)
                    </div>
                </div>

                {{-- Задача 78 --}}
                <div x-data="task78Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">78</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, BC=4, AC=28. Найдите tgB.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">28</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">4</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 7 (tgB = 28/4)
                    </div>
                </div>
            </div>

            {{-- Подраздел: Найти AC по sinB --}}
            <div class="bg-slate-700/30 rounded-lg p-3 mb-4">
                <span class="text-orange-400 font-medium">Найти AC по sinB (AC = AB · sinB)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                {{-- Задача 79 --}}
                <div x-data="task79Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">79</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, $\sin B = \frac{4}{9}$, AB=18. Найдите AC.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">18</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 8 (AC = 18 · 4/9)
                    </div>
                </div>

                {{-- Задача 80 --}}
                <div x-data="task80Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">80</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, $\sin B = \frac{5}{17}$, AB=51. Найдите AC.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">51</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 15 (AC = 51 · 5/17)
                    </div>
                </div>

                {{-- Задача 81 --}}
                <div x-data="task81Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">81</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, $\sin B = \frac{4}{11}$, AB=55. Найдите AC.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">55</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 20 (AC = 55 · 4/11)
                    </div>
                </div>

                {{-- Задача 82 --}}
                <div x-data="task82Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">82</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, $\sin B = \frac{7}{12}$, AB=48. Найдите AC.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">48</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 28 (AC = 48 · 7/12)
                    </div>
                </div>
            </div>

            {{-- Подраздел: Найти BC по cosB --}}
            <div class="bg-slate-700/30 rounded-lg p-3 mb-4">
                <span class="text-orange-400 font-medium">Найти BC по cosB (BC = AB · cosB)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                {{-- Задача 83 --}}
                <div x-data="task83Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">83</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, $\cos B = \frac{2}{5}$, AB=10. Найдите BC.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">10</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 4 (BC = 10 · 2/5)
                    </div>
                </div>

                {{-- Задача 84 --}}
                <div x-data="task84Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">84</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, $\cos B = \frac{7}{9}$, AB=54. Найдите BC.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">54</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 42 (BC = 54 · 7/9)
                    </div>
                </div>

                {{-- Задача 85 --}}
                <div x-data="task85Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">85</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, $\cos B = \frac{11}{15}$, AB=75. Найдите BC.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">75</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 55 (BC = 75 · 11/15)
                    </div>
                </div>

                {{-- Задача 86 --}}
                <div x-data="task86Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">86</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, $\cos B = \frac{13}{16}$, AB=96. Найдите BC.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                            <text :x="labelOnSegment(A, B, 14).x" :y="labelOnSegment(A, B, 14).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">96</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 78 (BC = 96 · 13/16)
                    </div>
                </div>
            </div>

            {{-- Подраздел: Найти AC по tgB --}}
            <div class="bg-slate-700/30 rounded-lg p-3 mb-4">
                <span class="text-orange-400 font-medium">Найти AC по tgB (AC = BC · tgB)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 87 --}}
                <div x-data="task87Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">87</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, $\mathrm{tg}\, B = \frac{7}{12}$, BC=48. Найдите AC.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">48</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 28 (AC = 48 · 7/12)
                    </div>
                </div>

                {{-- Задача 88 --}}
                <div x-data="task88Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">88</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, $\mathrm{tg}\, B = \frac{4}{7}$, BC=35. Найдите AC.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">35</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 20 (AC = 35 · 4/7)
                    </div>
                </div>

                {{-- Задача 89 --}}
                <div x-data="task89Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">89</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, $\mathrm{tg}\, B = \frac{8}{5}$, BC=20. Найдите AC.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">20</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 32 (AC = 20 · 8/5)
                    </div>
                </div>

                {{-- Задача 90 --}}
                <div x-data="task90Trig()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">90</span>
                        <div class="text-slate-200">
                            В треугольнике ABC угол C равен 90°, $\mathrm{tg}\, B = \frac{9}{7}$, BC=42. Найдите AC.
                        </div>
                    </div>

                    <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#4a6b8a" stroke-width="2"/>
                            <path :d="makeAngleArc(B, C, A, 25)" fill="none" stroke="#d4a855" stroke-width="2.5"/>
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>
                            <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="labelOnSegment(A, C, 12, true).x" :y="labelOnSegment(A, C, 12, true).y"
                                fill="#5a9fcf" font-size="12" class="geo-label" text-anchor="middle">?</text>
                            <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                                fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">42</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 54 (AC = 42 · 9/7)
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- XVII) Теорема о площади треугольника (задачи 91-94) --}}
    <div class="mt-10">
        <h3 class="text-xl font-semibold text-amber-400 mb-2">XVII) Теорема о площади треугольника</h3>
        <p class="text-slate-400 text-sm mb-6">$S = \frac{1}{2} \cdot AB \cdot BC \cdot \sin \angle ABC$</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Задача 91 --}}
            <div x-data="task91Area()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                <div class="flex items-start gap-3 mb-4">
                    <span class="text-red-400 font-bold text-xl">91</span>
                    <div class="text-slate-200">
                        В треугольнике ABC известно, что AB=15, BC=8, $\sin \angle ABC = \frac{5}{6}$. Найдите площадь треугольника ABC.
                    </div>
                </div>

                <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                        {{-- Треугольник --}}
                        <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                            fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                        {{-- Дуга угла B --}}
                        <path :d="makeAngleArc(B, A, C, 30)" fill="none" stroke="#d4a855" stroke-width="2.5"/>

                        {{-- Вершины --}}
                        <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                        {{-- Подписи вершин --}}
                        <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                            fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                        <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                            fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                        <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                            fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                        {{-- Длины сторон --}}
                        <text :x="labelOnSegment(A, B, 12).x" :y="labelOnSegment(A, B, 12).y"
                            fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">15</text>
                        <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                            fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">8</text>
                    </svg>
                </div>

                <div class="mt-3 text-slate-500 text-sm">
                    <span class="text-emerald-400">Ответ:</span> 50 ($S = \frac{1}{2} \cdot 15 \cdot 8 \cdot \frac{5}{6}$)
                </div>
            </div>

            {{-- Задача 92 --}}
            <div x-data="task92Area()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                <div class="flex items-start gap-3 mb-4">
                    <span class="text-red-400 font-bold text-xl">92</span>
                    <div class="text-slate-200">
                        В треугольнике ABC известно, что AB=10, BC=12, $\sin \angle ABC = \frac{8}{15}$. Найдите площадь треугольника ABC.
                    </div>
                </div>

                <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                        {{-- Треугольник --}}
                        <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                            fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                        {{-- Дуга угла B --}}
                        <path :d="makeAngleArc(B, A, C, 30)" fill="none" stroke="#d4a855" stroke-width="2.5"/>

                        {{-- Вершины --}}
                        <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                        {{-- Подписи вершин --}}
                        <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                            fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                        <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                            fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                        <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                            fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                        {{-- Длины сторон --}}
                        <text :x="labelOnSegment(A, B, 12).x" :y="labelOnSegment(A, B, 12).y"
                            fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">10</text>
                        <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                            fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">12</text>
                    </svg>
                </div>

                <div class="mt-3 text-slate-500 text-sm">
                    <span class="text-emerald-400">Ответ:</span> 32 ($S = \frac{1}{2} \cdot 10 \cdot 12 \cdot \frac{8}{15}$)
                </div>
            </div>

            {{-- Задача 93 --}}
            <div x-data="task93Area()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                <div class="flex items-start gap-3 mb-4">
                    <span class="text-red-400 font-bold text-xl">93</span>
                    <div class="text-slate-200">
                        В треугольнике ABC известно, что AB=12, BC=15, $\sin \angle ABC = \frac{4}{9}$. Найдите площадь треугольника ABC.
                    </div>
                </div>

                <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                        {{-- Треугольник --}}
                        <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                            fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                        {{-- Дуга угла B --}}
                        <path :d="makeAngleArc(B, A, C, 30)" fill="none" stroke="#d4a855" stroke-width="2.5"/>

                        {{-- Вершины --}}
                        <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                        {{-- Подписи вершин --}}
                        <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                            fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                        <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                            fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                        <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                            fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                        {{-- Длины сторон --}}
                        <text :x="labelOnSegment(A, B, 12).x" :y="labelOnSegment(A, B, 12).y"
                            fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">12</text>
                        <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                            fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">15</text>
                    </svg>
                </div>

                <div class="mt-3 text-slate-500 text-sm">
                    <span class="text-emerald-400">Ответ:</span> 40 ($S = \frac{1}{2} \cdot 12 \cdot 15 \cdot \frac{4}{9}$)
                </div>
            </div>

            {{-- Задача 94 --}}
            <div x-data="task94Area()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700">
                <div class="flex items-start gap-3 mb-4">
                    <span class="text-red-400 font-bold text-xl">94</span>
                    <div class="text-slate-200">
                        В треугольнике ABC известно, что AB=9, BC=16, $\sin \angle ABC = \frac{7}{12}$. Найдите площадь треугольника ABC.
                    </div>
                </div>

                <div class="rounded-lg p-4 flex justify-center" style="background-color: #0a1628;">
                    <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                        {{-- Треугольник --}}
                        <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                            fill="none" stroke="#c8dce8" stroke-width="3" stroke-linejoin="round"/>

                        {{-- Дуга угла B --}}
                        <path :d="makeAngleArc(B, A, C, 30)" fill="none" stroke="#d4a855" stroke-width="2.5"/>

                        {{-- Вершины --}}
                        <circle :cx="A.x" :cy="A.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="B.x" :cy="B.y" r="5" fill="#5a9fcf"/>
                        <circle :cx="C.x" :cy="C.y" r="5" fill="#5a9fcf"/>

                        {{-- Подписи вершин --}}
                        <text :x="labelPos(A, center, 22).x" :y="labelPos(A, center, 22).y"
                            fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                        <text :x="labelPos(B, center, 22).x" :y="labelPos(B, center, 22).y"
                            fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                        <text :x="labelPos(C, center, 22).x" :y="labelPos(C, center, 22).y"
                            fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                        {{-- Длины сторон --}}
                        <text :x="labelOnSegment(A, B, 12).x" :y="labelOnSegment(A, B, 12).y"
                            fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">9</text>
                        <text :x="labelOnSegment(B, C, 12).x" :y="labelOnSegment(B, C, 12).y"
                            fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">16</text>
                    </svg>
                </div>

                <div class="mt-3 text-slate-500 text-sm">
                    <span class="text-emerald-400">Ответ:</span> 42 ($S = \frac{1}{2} \cdot 9 \cdot 16 \cdot \frac{7}{12}$)
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
    // Визуально угол шире для удобства отображения метки
    function task3Bisector() {
        const A = { x: 40, y: 180 };
        const B = { x: 160, y: 45 };
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

    // Биссектриса: задача 4 (угол 24°)
    // Визуально угол шире для удобства отображения метки
    function task4Bisector() {
        const A = { x: 40, y: 180 };
        const B = { x: 155, y: 45 };
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
        const tickAM = window.equalityTick(A, M, 0.5, 10);
        const tickMC = window.equalityTick(M, C, 0.5, 10);
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
        const tickAM = window.equalityTick(A, M, 0.5, 10);
        const tickMC = window.equalityTick(M, C, 0.5, 10);
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
        const tickAM = window.equalityTick(A, M, 0.5, 10);
        const tickMC = window.equalityTick(M, C, 0.5, 10);
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
        const tickAM = window.equalityTick(A, M, 0.5, 10);
        const tickMC = window.equalityTick(M, C, 0.5, 10);
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
        const tickAB = window.equalityTick(A, B, 0.5, 10);
        const tickBC = window.equalityTick(B, C, 0.5, 10);
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
        const tickAB = window.equalityTick(A, B, 0.5, 10);
        const tickBC = window.equalityTick(B, C, 0.5, 10);
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
        const tickAB = window.equalityTick(A, B, 0.5, 10);
        const tickBC = window.equalityTick(B, C, 0.5, 10);
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
        const tickAB = window.equalityTick(A, B, 0.5, 10);
        const tickBC = window.equalityTick(B, C, 0.5, 10);
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
        const tickAB = window.equalityTick(A, B, 0.5, 10);
        const tickBC = window.equalityTick(B, C, 0.5, 10);
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
        const tickAB = window.equalityTick(A, B, 0.5, 10);
        const tickBC = window.equalityTick(B, C, 0.5, 10);
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
        const tickAB = window.equalityTick(A, B, 0.5, 10);
        const tickBC = window.equalityTick(B, C, 0.5, 10);
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
        const tickAB = window.equalityTick(A, B, 0.5, 10);
        const tickBC = window.equalityTick(B, C, 0.5, 10);
        return {
            A, B, C, center, ext, tickAB, tickBC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Острые углы прямоугольного треугольника: задача 25 (угол 21°)
    function task25RightAngles() {
        // Прямой угол в C
        const A = { x: 40, y: 180 };
        const B = { x: 240, y: 180 };
        const C = { x: 40, y: 50 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Острые углы прямоугольного треугольника: задача 26 (угол 33°)
    function task26RightAngles() {
        const A = { x: 40, y: 180 };
        const B = { x: 240, y: 180 };
        const C = { x: 40, y: 55 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Острые углы прямоугольного треугольника: задача 27 (угол 47°)
    function task27RightAngles() {
        const A = { x: 40, y: 180 };
        const B = { x: 250, y: 180 };
        const C = { x: 40, y: 60 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Острые углы прямоугольного треугольника: задача 28 (угол 63°)
    function task28RightAngles() {
        const A = { x: 40, y: 180 };
        const B = { x: 260, y: 180 };
        const C = { x: 40, y: 65 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Высота, угол ABH: задача 29 (угол BAC = 37°)
    function task29Height() {
        const A = { x: 30, y: 180 };
        const B = { x: 140, y: 40 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        // H — основание высоты из B на AC (AC горизонтальна)
        const H = { x: B.x, y: 180 };
        return {
            A, B, C, center, H,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Высота, угол ABH: задача 30 (угол BAC = 29°)
    function task30Height() {
        const A = { x: 30, y: 180 };
        const B = { x: 150, y: 45 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const H = { x: B.x, y: 180 };
        return {
            A, B, C, center, H,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Высота, угол ABH: задача 31 (угол BAC = 46°)
    function task31Height() {
        const A = { x: 30, y: 180 };
        const B = { x: 130, y: 50 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const H = { x: B.x, y: 180 };
        return {
            A, B, C, center, H,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Высота, угол ABH: задача 32 (угол BAC = 82°)
    function task32Height() {
        const A = { x: 30, y: 180 };
        const B = { x: 110, y: 55 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const H = { x: B.x, y: 180 };
        return {
            A, B, C, center, H,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            angleLabelPos: (v, p1, p2, r) => window.angleLabelPos(v, p1, p2, r),
        };
    }

    // Пифагор: задача 45 (катеты 7 и 24)
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

    // Пифагор: задача 46 (катеты 8 и 15)
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

    // Площадь по катетам: задача 33 (катеты 4 и 10)
    function task33Area() {
        const A = { x: 50, y: 180 };
        const B = { x: 50, y: 70 };
        const C = { x: 240, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Площадь по катетам: задача 34 (катеты 14 и 5)
    function task34Area() {
        const A = { x: 50, y: 180 };
        const B = { x: 50, y: 85 };
        const C = { x: 230, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Площадь по катетам: задача 35 (катеты 7 и 12)
    function task35Area() {
        const A = { x: 50, y: 180 };
        const B = { x: 50, y: 75 };
        const C = { x: 245, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Площадь по катетам: задача 36 (катеты 18 и 7)
    function task36Area() {
        const A = { x: 50, y: 180 };
        const B = { x: 50, y: 80 };
        const C = { x: 250, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Площадь по высоте: задача 37 (сторона 16, высота 19)
    function task37AreaHeight() {
        const A = { x: 40, y: 180 };
        const C = { x: 240, y: 180 };
        const B = { x: 150, y: 45 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        // H — основание высоты из B на AC
        const H = { x: B.x, y: 180 };
        return {
            A, B, C, H, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Площадь по высоте: задача 38 (сторона 14, высота 31)
    function task38AreaHeight() {
        const A = { x: 35, y: 180 };
        const C = { x: 245, y: 180 };
        const B = { x: 140, y: 40 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const H = { x: B.x, y: 180 };
        return {
            A, B, C, H, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Площадь по высоте: задача 39 (сторона 29, высота 12)
    function task39AreaHeight() {
        const A = { x: 30, y: 180 };
        const C = { x: 250, y: 180 };
        const B = { x: 160, y: 55 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const H = { x: B.x, y: 180 };
        return {
            A, B, C, H, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Площадь по высоте: задача 40 (сторона 18, высота 17)
    function task40AreaHeight() {
        const A = { x: 35, y: 180 };
        const C = { x: 245, y: 180 };
        const B = { x: 145, y: 50 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const H = { x: B.x, y: 180 };
        return {
            A, B, C, H, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Средняя линия: задача 41 (AB=21, BC=22, AC=28, MN=14)
    function task41Midline() {
        const A = { x: 40, y: 180 };
        const B = { x: 140, y: 40 };
        const C = { x: 260, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        // M — середина AB, N — середина BC
        const M = window.pointOnLine(A, B, 0.5);
        const N = window.pointOnLine(B, C, 0.5);
        // Одинарные черточки для AM = MB
        const tickAM = window.equalityTick(A, M, 0.5, 8);
        const tickMB = window.equalityTick(M, B, 0.5, 8);
        // Двойные черточки для BN = NC (другая пара равных отрезков)
        const dblTickBN = window.doubleEqualityTick(B, N, 0.5, 8, 4);
        const dblTickNC = window.doubleEqualityTick(N, C, 0.5, 8, 4);
        return {
            A, B, C, M, N, center, tickAM, tickMB, dblTickBN, dblTickNC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Средняя линия: задача 42 (AB=66, BC=37, AC=74, MN=37)
    function task42Midline() {
        const A = { x: 35, y: 180 };
        const B = { x: 130, y: 45 };
        const C = { x: 265, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const M = window.pointOnLine(A, B, 0.5);
        const N = window.pointOnLine(B, C, 0.5);
        // Одинарные черточки для AM = MB
        const tickAM = window.equalityTick(A, M, 0.5, 8);
        const tickMB = window.equalityTick(M, B, 0.5, 8);
        // Двойные черточки для BN = NC
        const dblTickBN = window.doubleEqualityTick(B, N, 0.5, 8, 4);
        const dblTickNC = window.doubleEqualityTick(N, C, 0.5, 8, 4);
        return {
            A, B, C, M, N, center, tickAM, tickMB, dblTickBN, dblTickNC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Средняя линия: задача 43 (AB=26, BC=39, AC=48, MN=24)
    function task43Midline() {
        const A = { x: 30, y: 180 };
        const B = { x: 150, y: 40 };
        const C = { x: 270, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const M = window.pointOnLine(A, B, 0.5);
        const N = window.pointOnLine(B, C, 0.5);
        // Одинарные черточки для AM = MB
        const tickAM = window.equalityTick(A, M, 0.5, 8);
        const tickMB = window.equalityTick(M, B, 0.5, 8);
        // Двойные черточки для BN = NC
        const dblTickBN = window.doubleEqualityTick(B, N, 0.5, 8, 4);
        const dblTickNC = window.doubleEqualityTick(N, C, 0.5, 8, 4);
        return {
            A, B, C, M, N, center, tickAM, tickMB, dblTickBN, dblTickNC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Средняя линия: задача 44 (AB=42, BC=44, AC=62, MN=31)
    function task44Midline() {
        const A = { x: 35, y: 180 };
        const B = { x: 145, y: 45 };
        const C = { x: 265, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const M = window.pointOnLine(A, B, 0.5);
        const N = window.pointOnLine(B, C, 0.5);
        // Одинарные черточки для AM = MB
        const tickAM = window.equalityTick(A, M, 0.5, 8);
        const tickMB = window.equalityTick(M, B, 0.5, 8);
        // Двойные черточки для BN = NC
        const dblTickBN = window.doubleEqualityTick(B, N, 0.5, 8, 4);
        const dblTickNC = window.doubleEqualityTick(N, C, 0.5, 8, 4);
        return {
            A, B, C, M, N, center, tickAM, tickMB, dblTickBN, dblTickNC,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Пифагор: задача 47 (катеты 20 и 21)
    function task47Pythagoras() {
        const A = { x: 50, y: 180 };
        const B = { x: 50, y: 55 };
        const C = { x: 245, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Пифагор: задача 48 (катеты 9 и 12)
    function task48Pythagoras() {
        const A = { x: 50, y: 180 };
        const B = { x: 50, y: 75 };
        const C = { x: 230, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Пифагор (найти катет): задача 49 (катет 7, гипотенуза 25)
    function task49PythagorasLeg() {
        const A = { x: 50, y: 180 };
        const B = { x: 50, y: 60 };
        const C = { x: 240, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Пифагор (найти катет): задача 50 (катет 40, гипотенуза 41)
    function task50PythagorasLeg() {
        const A = { x: 50, y: 180 };
        const B = { x: 50, y: 70 };
        const C = { x: 250, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Пифагор (найти катет): задача 51 (катет 8, гипотенуза 17)
    function task51PythagorasLeg() {
        const A = { x: 50, y: 180 };
        const B = { x: 50, y: 65 };
        const C = { x: 235, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Пифагор (найти катет): задача 52 (катет 16, гипотенуза 34)
    function task52PythagorasLeg() {
        const A = { x: 50, y: 180 };
        const B = { x: 50, y: 55 };
        const C = { x: 255, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // Равносторонний треугольник: задачи 53-62
    // В равностороннем треугольнике высота = медиана = биссектриса = a√3/2
    function taskEquilateral() {
        // Равносторонний треугольник
        const A = { x: 50, y: 195 };
        const C = { x: 250, y: 195 };
        const B = { x: 150, y: 40 }; // Опущен ниже для видимости подписи B
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        // M — середина AC (основание высоты/медианы)
        const M = window.pointOnLine(A, C, 0.5);
        // Маркеры равенства сторон
        const tickAB = window.equalityTick(A, B, 0.5, 8);
        const tickBC = window.equalityTick(B, C, 0.5, 8);
        const tickAC1 = window.equalityTick(A, M, 0.5, 8);
        const tickAC2 = window.equalityTick(M, C, 0.5, 8);
        return {
            A, B, C, M, center, tickAB, tickBC, tickAC1, tickAC2,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    function task53Equilateral() { return taskEquilateral(); }
    function task54Equilateral() { return taskEquilateral(); }
    function task55Equilateral() { return taskEquilateral(); }
    function task56Equilateral() { return taskEquilateral(); }
    function task57Equilateral() { return taskEquilateral(); }
    function task58Equilateral() { return taskEquilateral(); }
    function task59Equilateral() { return taskEquilateral(); }
    function task60Equilateral() { return taskEquilateral(); }
    function task61Equilateral() { return taskEquilateral(); }
    function task62Equilateral() { return taskEquilateral(); }

    // Радиус описанной окружности: задачи 63-66
    // Для прямоугольного треугольника: центр описанной окружности — середина гипотенузы
    // Радиус = гипотенуза / 2
    function taskCircumradius(ac, bc) {
        // Прямоугольный треугольник с прямым углом в C
        // Уменьшенный размер чтобы описанная окружность помещалась в viewBox
        const C = { x: 70, y: 155 };
        const A = { x: 230, y: 155 };
        const B = { x: 70, y: 55 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        // Центр описанной окружности — середина гипотенузы AB
        const O = window.pointOnLine(A, B, 0.5);
        // Радиус — расстояние от O до любой вершины
        const R = Math.sqrt((A.x - O.x) ** 2 + (A.y - O.y) ** 2);
        return {
            A, B, C, O, R, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    function task63Circumradius() { return taskCircumradius(6, 8); }
    function task64Circumradius() { return taskCircumradius(40, 30); }
    function task65Circumradius() { return taskCircumradius(12, 5); }
    function task66Circumradius() { return taskCircumradius(7, 24); }

    // Тригонометрия: задачи 67-90
    // Прямоугольный треугольник, угол C = 90°, угол B выделен
    // sinB = AC/AB, cosB = BC/AB, tgB = AC/BC
    function taskTrig() {
        // Прямоугольный треугольник: прямой угол в C
        const C = { x: 50, y: 180 };
        const A = { x: 250, y: 180 };
        const B = { x: 50, y: 55 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    // sinB: задачи 67-70
    function task67Trig() { return taskTrig(); }
    function task68Trig() { return taskTrig(); }
    function task69Trig() { return taskTrig(); }
    function task70Trig() { return taskTrig(); }

    // cosB: задачи 71-74
    function task71Trig() { return taskTrig(); }
    function task72Trig() { return taskTrig(); }
    function task73Trig() { return taskTrig(); }
    function task74Trig() { return taskTrig(); }

    // tgB: задачи 75-78
    function task75Trig() { return taskTrig(); }
    function task76Trig() { return taskTrig(); }
    function task77Trig() { return taskTrig(); }
    function task78Trig() { return taskTrig(); }

    // Найти AC по sinB и AB: задачи 79-82
    function task79Trig() { return taskTrig(); }
    function task80Trig() { return taskTrig(); }
    function task81Trig() { return taskTrig(); }
    function task82Trig() { return taskTrig(); }

    // Найти BC по cosB и AB: задачи 83-86
    function task83Trig() { return taskTrig(); }
    function task84Trig() { return taskTrig(); }
    function task85Trig() { return taskTrig(); }
    function task86Trig() { return taskTrig(); }

    // Найти AC по tgB и BC: задачи 87-90
    function task87Trig() { return taskTrig(); }
    function task88Trig() { return taskTrig(); }
    function task89Trig() { return taskTrig(); }
    function task90Trig() { return taskTrig(); }

    // Теорема о площади треугольника: задачи 91-94
    // S = (1/2) * AB * BC * sin(∠ABC)
    function taskArea() {
        // Общий треугольник с выделенным углом B
        const A = { x: 30, y: 180 };
        const B = { x: 150, y: 35 };
        const C = { x: 270, y: 180 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            labelOnSegment: (p1, p2, o, f) => window.labelOnSegment(p1, p2, o, f),
        };
    }

    function task91Area() { return taskArea(); }
    function task92Area() { return taskArea(); }
    function task93Area() { return taskArea(); }
    function task94Area() { return taskArea(); }
</script>

</body>
</html>
