<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>15. Треугольники (DEMO) - SVG версия</title>

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

{{-- Глобальные функции из GEOMETRY_SPEC --}}
<script>
    // Позиционирует подписи в направлении от центра фигуры
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

    // Рисует дугу угла строго между двумя сторонами
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

    // Рисует квадратик для прямого угла
    function rightAnglePath(vertex, p1, p2, size = 12) {
        const angle1 = Math.atan2(p1.y - vertex.y, p1.x - vertex.x);
        const angle2 = Math.atan2(p2.y - vertex.y, p2.x - vertex.x);
        const c1 = { x: vertex.x + size * Math.cos(angle1), y: vertex.y + size * Math.sin(angle1) };
        const c2 = { x: vertex.x + size * Math.cos(angle2), y: vertex.y + size * Math.sin(angle2) };
        const diag = { x: c1.x + size * Math.cos(angle2), y: c1.y + size * Math.sin(angle2) };
        return `M ${c1.x} ${c1.y} L ${diag.x} ${diag.y} L ${c2.x} ${c2.y}`;
    }

    // Экспортируем в глобальную область
    window.labelPos = labelPos;
    window.makeAngleArc = makeAngleArc;
    window.rightAnglePath = rightAnglePath;
</script>

<div class="max-w-6xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">15. Треугольники (DEMO v2)</h1>
        <p class="text-slate-400 text-lg">С правильными SVG функциями из GEOMETRY_SPEC</p>
        <p class="text-emerald-400 mt-2">labelPos(), makeAngleArc(), rightAnglePath()</p>
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
                <h3 class="text-lg font-semibold text-white">
                    I) Биссектриса треугольника
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 1: угол 68° --}}
                <div x-data="task1()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-amber-500/50 transition-all">
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
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round" class="geo-line"/>

                            {{-- Биссектриса AD --}}
                            <line :x1="A.x" :y1="A.y" :x2="D.x" :y2="D.y"
                                stroke="#10b981" stroke-width="2" stroke-dasharray="6,4" class="geo-line"/>

                            {{-- Дуга полного угла BAC (68°) --}}
                            <path :d="makeAngleArc(A, B, C, 35)" fill="none" stroke="#f59e0b" stroke-width="2"/>

                            {{-- Дуга искомого угла BAD (34°) --}}
                            <path :d="makeAngleArc(A, B, D, 28)" fill="none" stroke="#10b981" stroke-width="2.5"/>

                            {{-- Точки --}}
                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>
                            <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>

                            {{-- Подписи через labelPos --}}
                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 24).x" :y="labelPos(B, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 24).x" :y="labelPos(C, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="D.x" :y="D.y + 18" fill="#10b981" font-size="16" class="geo-label" text-anchor="middle">D</text>

                            {{-- Метка угла --}}
                            <text x="75" y="175" fill="#f59e0b" font-size="14" class="geo-label">68°</text>
                            <text x="60" y="158" fill="#10b981" font-size="12" class="geo-label">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 34° (биссектриса делит угол пополам)
                    </div>
                </div>

                {{-- Задача 2: угол 82° --}}
                <div x-data="task2()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-amber-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">2</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что $\angle BAC = 82°$, AD – биссектриса. Найдите угол BAD.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round" class="geo-line"/>

                            <line :x1="A.x" :y1="A.y" :x2="D.x" :y2="D.y"
                                stroke="#10b981" stroke-width="2" stroke-dasharray="6,4" class="geo-line"/>

                            <path :d="makeAngleArc(A, B, C, 32)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(A, B, D, 25)" fill="none" stroke="#10b981" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>
                            <circle :cx="D.x" :cy="D.y" r="4" fill="#10b981"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 24).x" :y="labelPos(B, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 24).x" :y="labelPos(C, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="D.x" :y="D.y + 18" fill="#10b981" font-size="16" class="geo-label" text-anchor="middle">D</text>

                            <text x="70" y="172" fill="#f59e0b" font-size="14" class="geo-label">82°</text>
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
                <h3 class="text-lg font-semibold text-white">
                    II) Медиана треугольника
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 5 --}}
                <div x-data="task5()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-amber-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">5</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AC=14, BM – медиана, BM=10. Найдите AM.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round" class="geo-line"/>

                            {{-- Медиана BM --}}
                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#10b981" stroke-width="2" stroke-dasharray="6,4" class="geo-line"/>

                            {{-- Маркеры равных отрезков AM = MC --}}
                            <line :x1="(A.x + M.x)/2 - 4" :y1="A.y - 6" :x2="(A.x + M.x)/2 + 4" :y2="A.y + 2" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="(M.x + C.x)/2 - 4" :y1="C.y - 6" :x2="(M.x + C.x)/2 + 4" :y2="C.y + 2" stroke="#3b82f6" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#10b981"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 24).x" :y="labelPos(B, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 24).x" :y="labelPos(C, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y - 12" fill="#10b981" font-size="16" class="geo-label" text-anchor="middle">M</text>

                            {{-- Метки длин --}}
                            <text :x="(A.x + C.x)/2" :y="A.y + 22" fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">AC = 14</text>
                            <text :x="(B.x + M.x)/2 - 20" :y="(B.y + M.y)/2" fill="#10b981" font-size="11" class="geo-label">BM = 10</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 7 (AM = AC/2 = 14/2 = 7)
                    </div>
                </div>

                {{-- Задача 6 --}}
                <div x-data="task6()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-amber-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">6</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AC=16, BM – медиана, BM=12. Найдите AM.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round" class="geo-line"/>

                            <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                                stroke="#10b981" stroke-width="2" stroke-dasharray="6,4" class="geo-line"/>

                            <line :x1="(A.x + M.x)/2 - 4" :y1="A.y - 6" :x2="(A.x + M.x)/2 + 4" :y2="A.y + 2" stroke="#3b82f6" stroke-width="2.5"/>
                            <line :x1="(M.x + C.x)/2 - 4" :y1="C.y - 6" :x2="(M.x + C.x)/2 + 4" :y2="C.y + 2" stroke="#3b82f6" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>
                            <circle :cx="M.x" :cy="M.y" r="4" fill="#10b981"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 24).x" :y="labelPos(B, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 24).x" :y="labelPos(C, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                            <text :x="M.x" :y="M.y - 12" fill="#10b981" font-size="16" class="geo-label" text-anchor="middle">M</text>

                            <text :x="(A.x + C.x)/2" :y="A.y + 22" fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">AC = 16</text>
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
                <h3 class="text-lg font-semibold text-white">
                    III) Сумма углов треугольника
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 9 --}}
                <div x-data="task9()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-amber-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">9</span>
                        <div class="text-slate-200">
                            В треугольнике два угла равны 72° и 42°. Найдите его третий угол.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round" class="geo-line"/>

                            {{-- Дуги углов --}}
                            <path :d="makeAngleArc(A, C, B, 28)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 22)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 25)" fill="none" stroke="#10b981" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 24).x" :y="labelPos(B, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 24).x" :y="labelPos(C, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки углов --}}
                            <text x="60" y="175" fill="#f59e0b" font-size="12" class="geo-label">72°</text>
                            <text x="220" y="175" fill="#f59e0b" font-size="12" class="geo-label">42°</text>
                            <text :x="C.x - 5" :y="C.y + 35" fill="#10b981" font-size="12" class="geo-label">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 66° (180° − 72° − 42° = 66°)
                    </div>
                </div>

                {{-- Задача 10 --}}
                <div x-data="task10()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-amber-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">10</span>
                        <div class="text-slate-200">
                            В треугольнике два угла равны 43° и 88°. Найдите его третий угол.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round" class="geo-line"/>

                            <path :d="makeAngleArc(A, C, B, 28)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(B, A, C, 18)" fill="none" stroke="#f59e0b" stroke-width="2"/>
                            <path :d="makeAngleArc(C, B, A, 25)" fill="none" stroke="#10b981" stroke-width="2.5"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 24).x" :y="labelPos(B, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 24).x" :y="labelPos(C, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text x="55" y="175" fill="#f59e0b" font-size="12" class="geo-label">43°</text>
                            <text x="215" y="175" fill="#f59e0b" font-size="12" class="geo-label">88°</text>
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
                <h3 class="text-lg font-semibold text-white">
                    IV) Теорема Пифагора
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 45 --}}
                <div x-data="task45()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-amber-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">45</span>
                        <div class="text-slate-200">
                            Катеты прямоугольного треугольника равны 7 и 24. Найдите гипотенузу.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round" class="geo-line"/>

                            {{-- Прямой угол через rightAnglePath --}}
                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#666" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 24).x" :y="labelPos(B, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 24).x" :y="labelPos(C, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            {{-- Метки длин --}}
                            <text :x="(A.x + C.x)/2" :y="A.y + 22" fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">24</text>
                            <text :x="C.x - 20" :y="(C.y + B.y)/2" fill="#94a3b8" font-size="12" class="geo-label">7</text>
                            <text :x="(A.x + B.x)/2 + 15" :y="(A.y + B.y)/2 - 5" fill="#10b981" font-size="12" class="geo-label">?</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 25 ($\sqrt{7^2 + 24^2} = \sqrt{625} = 25$)
                    </div>
                </div>

                {{-- Задача 46 --}}
                <div x-data="task46()" class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-amber-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-red-400 font-bold text-xl">46</span>
                        <div class="text-slate-200">
                            Катеты прямоугольного треугольника равны 8 и 15. Найдите гипотенузу.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 300 220" class="w-full max-w-[300px] h-auto">
                            <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                                fill="none" stroke="#dc2626" stroke-width="3" stroke-linejoin="round" class="geo-line"/>

                            <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#666" stroke-width="2"/>

                            <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                            <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                            <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                            <text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                            <text :x="labelPos(B, center, 24).x" :y="labelPos(B, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                            <text :x="labelPos(C, center, 24).x" :y="labelPos(C, center, 24).y"
                                fill="#60a5fa" font-size="18" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>

                            <text :x="(A.x + C.x)/2" :y="A.y + 22" fill="#94a3b8" font-size="12" class="geo-label" text-anchor="middle">15</text>
                            <text :x="C.x - 20" :y="(C.y + B.y)/2" fill="#94a3b8" font-size="12" class="geo-label">8</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 17
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Информация --}}
    <div class="bg-emerald-900/30 border border-emerald-500/30 rounded-xl p-6 mt-10">
        <h4 class="text-emerald-400 font-semibold mb-4">SVG с правилами из GEOMETRY_SPEC</h4>
        <ul class="text-slate-300 space-y-2">
            <li>✅ <code class="bg-slate-700 px-1 rounded">labelPos()</code> — подписи вне фигуры, от центра</li>
            <li>✅ <code class="bg-slate-700 px-1 rounded">makeAngleArc()</code> — дуги строго между сторонами</li>
            <li>✅ <code class="bg-slate-700 px-1 rounded">rightAnglePath()</code> — квадратик для 90°</li>
            <li>✅ Цвета: red (#dc2626), amber (#f59e0b), green (#10b981), blue (#3b82f6)</li>
            <li>✅ Центр = центроид треугольника</li>
        </ul>
    </div>

</div>

{{-- Alpine.js данные для каждой задачи --}}
<script>
    // Биссектриса: задача 1 (угол 68°)
    function task1() {
        const A = { x: 30, y: 190 };
        const B = { x: 180, y: 35 };
        const C = { x: 270, y: 190 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        // D на стороне BC, биссектриса из A
        const D = { x: 210, y: 130 };
        return {
            A, B, C, D, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // Биссектриса: задача 2 (угол 82°)
    function task2() {
        const A = { x: 30, y: 190 };
        const B = { x: 160, y: 35 };
        const C = { x: 270, y: 190 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const D = { x: 200, y: 125 };
        return {
            A, B, C, D, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // Медиана: задача 5
    function task5() {
        const A = { x: 30, y: 190 };
        const B = { x: 180, y: 35 };
        const C = { x: 270, y: 190 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const M = { x: (A.x + C.x) / 2, y: (A.y + C.y) / 2 };
        return { A, B, C, M, center, labelPos: (p, c, d) => window.labelPos(p, c, d) };
    }

    // Медиана: задача 6
    function task6() {
        const A = { x: 30, y: 190 };
        const B = { x: 200, y: 35 };
        const C = { x: 270, y: 190 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const M = { x: (A.x + C.x) / 2, y: (A.y + C.y) / 2 };
        return { A, B, C, M, center, labelPos: (p, c, d) => window.labelPos(p, c, d) };
    }

    // Сумма углов: задача 9
    function task9() {
        const A = { x: 30, y: 190 };
        const B = { x: 250, y: 190 };
        const C = { x: 140, y: 45 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // Сумма углов: задача 10
    function task10() {
        const A = { x: 30, y: 190 };
        const B = { x: 260, y: 190 };
        const C = { x: 150, y: 40 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // Теорема Пифагора: задача 45
    function task45() {
        const A = { x: 30, y: 190 };
        const B = { x: 30, y: 50 };
        const C = { x: 260, y: 190 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
        };
    }

    // Теорема Пифагора: задача 46
    function task46() {
        const A = { x: 30, y: 190 };
        const B = { x: 30, y: 60 };
        const C = { x: 240, y: 190 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            A, B, C, center,
            labelPos: (p, c, d) => window.labelPos(p, c, d),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
        };
    }
</script>

{{-- Инструмент для пометки заданий --}}
@include('components.task-review-tool', ['topicId' => '15'])

</body>
</html>
