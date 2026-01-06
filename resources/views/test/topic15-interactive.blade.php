<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>15. Треугольники - Интерактивные изображения</title>

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
<body class="min-h-screen bg-gradient-to-br from-slate-800 to-slate-900 p-6">

<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6 text-sm">
        <a href="{{ route('test.topic15') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← Статичная версия</a>
        <a href="{{ route('test.pdf.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors">Парсер PDF</a>
    </div>

    <h1 class="text-3xl font-bold text-center text-white mb-2">15. Треугольники</h1>
    <p class="text-center text-slate-400 mb-8">Наведите на кнопки для подсветки элементов</p>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">

        {{-- 1. Биссектриса треугольника --}}
        <div x-data="bisectorDemo()" class="bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-xl font-semibold text-white mb-4">1. Биссектриса треугольника</h3>

            <svg viewBox="0 0 300 220" class="w-full h-52 mb-4">
                {{-- Треугольник --}}
                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                    fill="none"
                    :stroke="isHighlighted('sides') ? '#f59e0b' : '#dc2626'"
                    :stroke-width="isHighlighted('sides') ? 5 : 3.5"
                    stroke-linejoin="round" class="geo-line"/>

                {{-- Биссектриса BD - всегда видна, подсвечивается при hint --}}
                <line :x1="B.x" :y1="B.y" :x2="D.x" :y2="D.y"
                    :stroke="isHighlighted('bisector') ? '#10b981' : '#dc2626'"
                    :stroke-width="isHighlighted('bisector') ? 3 : 2"
                    :stroke-dasharray="isHighlighted('bisector') ? '8,5' : 'none'"
                    class="geo-line"/>
                <circle :cx="D.x" :cy="D.y" r="4"
                    :fill="isHighlighted('bisector') ? '#10b981' : '#dc2626'"/>

                {{-- Дуги равных углов - только при подсветке --}}
                <g x-show="isHighlighted('bisector')">
                    <path :d="makeAngleArc(B, A, D, 28)" fill="none" stroke="#f59e0b" stroke-width="2.5"/>
                    <path :d="makeAngleArc(B, D, C, 32)" fill="none" stroke="#f59e0b" stroke-width="2.5"/>
                </g>

                {{-- Угол при A --}}
                <g x-show="isHighlighted('angleA')">
                    <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#f59e0b" stroke-width="2.5"/>
                </g>

                {{-- Вершины --}}
                <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                {{-- Подписи --}}
                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                <text :x="D.x" :y="D.y + 20" :fill="isHighlighted('bisector') ? '#10b981' : '#60a5fa'" font-size="18" class="geo-label" text-anchor="middle">D</text>
            </svg>

            <div class="flex flex-wrap gap-2 mb-3">
                <template x-for="h in hints" :key="h.id">
                    <button @mouseenter="hint = h.id" @mouseleave="hint = null"
                        :class="hint === h.id ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
                        class="px-3 py-1.5 rounded-full text-sm font-medium transition-all" x-text="h.label"></button>
                </template>
            </div>
            <div x-show="hint" x-cloak class="bg-amber-500/20 border-l-4 border-amber-500 p-3 rounded">
                <p class="text-slate-200" x-text="hints.find(h => h.id === hint)?.desc"></p>
            </div>
        </div>

        {{-- 2. Медиана треугольника --}}
        <div x-data="medianDemo()" class="bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-xl font-semibold text-white mb-4">2. Медиана треугольника</h3>

            <svg viewBox="0 0 300 220" class="w-full h-52 mb-4">
                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                    fill="none"
                    :stroke="isHighlighted('sides') ? '#f59e0b' : '#dc2626'"
                    :stroke-width="isHighlighted('sides') ? 5 : 3.5"
                    stroke-linejoin="round" class="geo-line"/>

                {{-- Медиана BM - всегда видна --}}
                <line :x1="B.x" :y1="B.y" :x2="M.x" :y2="M.y"
                    :stroke="isHighlighted('median') ? '#10b981' : '#dc2626'"
                    :stroke-width="isHighlighted('median') ? 3 : 2"
                    :stroke-dasharray="isHighlighted('median') ? '8,5' : 'none'"
                    class="geo-line"/>
                <circle :cx="M.x" :cy="M.y" r="4"
                    :fill="isHighlighted('median') ? '#10b981' : '#dc2626'"/>
                {{-- Маркеры равных отрезков - только при подсветке --}}
                <g x-show="isHighlighted('median')">
                    <line :x1="(A.x + M.x)/2 - 5" :y1="A.y - 5" :x2="(A.x + M.x)/2 + 5" :y2="A.y + 5" stroke="#3b82f6" stroke-width="3"/>
                    <line :x1="(M.x + C.x)/2 - 5" :y1="A.y - 5" :x2="(M.x + C.x)/2 + 5" :y2="A.y + 5" stroke="#3b82f6" stroke-width="3"/>
                </g>

                <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                <text :x="M.x" :y="M.y + 20" :fill="isHighlighted('median') ? '#10b981' : '#60a5fa'" font-size="18" class="geo-label" text-anchor="middle">M</text>
            </svg>

            <div class="flex flex-wrap gap-2 mb-3">
                <template x-for="h in hints" :key="h.id">
                    <button @mouseenter="hint = h.id" @mouseleave="hint = null"
                        :class="hint === h.id ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
                        class="px-3 py-1.5 rounded-full text-sm font-medium transition-all" x-text="h.label"></button>
                </template>
            </div>
            <div x-show="hint" x-cloak class="bg-amber-500/20 border-l-4 border-amber-500 p-3 rounded">
                <p class="text-slate-200" x-text="hints.find(h => h.id === hint)?.desc"></p>
            </div>
        </div>

        {{-- 3. Сумма углов треугольника --}}
        <div x-data="anglesSum()" class="bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-xl font-semibold text-white mb-4">3. Сумма углов треугольника</h3>

            <svg viewBox="0 0 300 220" class="w-full h-52 mb-4">
                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                    fill="none" stroke="#dc2626" stroke-width="3.5" stroke-linejoin="round" class="geo-line"/>

                {{-- Все три угла --}}
                <g x-show="isHighlighted('allAngles')">
                    <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#f59e0b" stroke-width="2.5"/>
                    <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                    <path :d="makeAngleArc(C, B, A, 30)" fill="none" stroke="#3b82f6" stroke-width="2.5"/>
                </g>

                <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
            </svg>

            <div class="flex flex-wrap gap-2 mb-3">
                <button @mouseenter="hint = 'allAngles'" @mouseleave="hint = null"
                    :class="hint === 'allAngles' ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
                    class="px-3 py-1.5 rounded-full text-sm font-medium transition-all">Подсказка</button>
            </div>
            <div x-show="hint" x-cloak class="bg-amber-500/20 border-l-4 border-amber-500 p-3 rounded">
                <p class="text-slate-200"><strong>∠A + ∠B + ∠C = 180°</strong><br>Третий угол = 180° − (первый + второй)</p>
            </div>
        </div>

        {{-- 4. Внешний угол треугольника --}}
        <div x-data="externalAngle()" class="bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-xl font-semibold text-white mb-4">4. Внешний угол</h3>

            <svg viewBox="0 0 300 200" class="w-full h-48 mb-4">
                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                    fill="none" stroke="#dc2626" stroke-width="3.5" stroke-linejoin="round"/>

                {{-- Продолжение стороны --}}
                <line :x1="C.x" :y1="C.y" :x2="D.x" :y2="D.y" stroke="#dc2626" stroke-width="2" stroke-dasharray="8,5"/>

                {{-- Внешний угол --}}
                <g x-show="isHighlighted('external')">
                    <path :d="makeAngleArc(C, B, D, 25)" fill="none" stroke="#f59e0b" stroke-width="3"/>
                </g>

                {{-- Внутренний угол --}}
                <g x-show="isHighlighted('internal')">
                    <path :d="makeAngleArc(C, A, B, 30)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                </g>

                {{-- Несмежные углы --}}
                <g x-show="isHighlighted('nonadjacent')">
                    <path :d="makeAngleArc(A, C, B, 28)" fill="none" stroke="#3b82f6" stroke-width="2.5"/>
                    <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#3b82f6" stroke-width="2.5"/>
                </g>

                <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                <text :x="C.x" :y="C.y + 22" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle">C</text>
            </svg>

            <div class="flex flex-wrap gap-2 mb-3">
                <template x-for="h in hints" :key="h.id">
                    <button @mouseenter="hint = h.id" @mouseleave="hint = null"
                        :class="hint === h.id ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
                        class="px-3 py-1.5 rounded-full text-sm font-medium transition-all" x-text="h.label"></button>
                </template>
            </div>
            <div x-show="hint" x-cloak class="bg-amber-500/20 border-l-4 border-amber-500 p-3 rounded">
                <p class="text-slate-200" x-text="hints.find(h => h.id === hint)?.desc"></p>
            </div>
        </div>

        {{-- 5. Равнобедренный треугольник --}}
        <div x-data="isoscelesDemo()" class="bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-xl font-semibold text-white mb-4">5. Равнобедренный треугольник</h3>

            <svg viewBox="0 0 300 220" class="w-full h-52 mb-4">
                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                    fill="none" stroke="#dc2626" stroke-width="3.5" stroke-linejoin="round"/>

                {{-- Маркеры равных сторон --}}
                <g x-show="isHighlighted('equalSides')">
                    <line :x1="markAB.x - 6" :y1="markAB.y - 6" :x2="markAB.x + 6" :y2="markAB.y + 6" stroke="#3b82f6" stroke-width="3"/>
                    <line :x1="markAB.x - 6 + 5" :y1="markAB.y - 6 + 5" :x2="markAB.x + 6 + 5" :y2="markAB.y + 6 + 5" stroke="#3b82f6" stroke-width="3"/>
                    <line :x1="markBC.x - 6" :y1="markBC.y + 6" :x2="markBC.x + 6" :y2="markBC.y - 6" stroke="#3b82f6" stroke-width="3"/>
                    <line :x1="markBC.x - 6 - 5" :y1="markBC.y + 6 + 5" :x2="markBC.x + 6 - 5" :y2="markBC.y - 6 + 5" stroke="#3b82f6" stroke-width="3"/>
                </g>

                {{-- Углы при основании --}}
                <g x-show="isHighlighted('baseAngles')">
                    <path :d="makeAngleArc(A, C, B, 35)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                    <path :d="makeAngleArc(C, B, A, 35)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                </g>

                {{-- Угол при вершине --}}
                <g x-show="isHighlighted('vertexAngle')">
                    <path :d="makeAngleArc(B, A, C, 30)" fill="none" stroke="#f59e0b" stroke-width="2.5"/>
                </g>

                <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
            </svg>

            <div class="flex flex-wrap gap-2 mb-3">
                <template x-for="h in hints" :key="h.id">
                    <button @mouseenter="hint = h.id" @mouseleave="hint = null"
                        :class="hint === h.id ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
                        class="px-3 py-1.5 rounded-full text-sm font-medium transition-all" x-text="h.label"></button>
                </template>
            </div>
            <div x-show="hint" x-cloak class="bg-amber-500/20 border-l-4 border-amber-500 p-3 rounded">
                <p class="text-slate-200" x-text="hints.find(h => h.id === hint)?.desc"></p>
            </div>
        </div>

        {{-- 6. Внешний угол равнобедренного --}}
        <div x-data="isoscelesExternal()" class="bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-xl font-semibold text-white mb-4">6. Внешний угол равнобедр.</h3>

            <svg viewBox="0 0 300 200" class="w-full h-48 mb-4">
                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                    fill="none" stroke="#dc2626" stroke-width="3.5" stroke-linejoin="round"/>

                <line :x1="C.x" :y1="C.y" :x2="D.x" :y2="D.y" stroke="#dc2626" stroke-width="2" stroke-dasharray="8,5"/>

                {{-- Внешний угол --}}
                <g x-show="isHighlighted('external')">
                    <path :d="makeAngleArc(C, B, D, 25)" fill="none" stroke="#f59e0b" stroke-width="3"/>
                </g>

                {{-- Углы при основании --}}
                <g x-show="isHighlighted('baseAngles')">
                    <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                    <path :d="makeAngleArc(C, B, A, 30)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                </g>

                {{-- Равные стороны --}}
                <g x-show="isHighlighted('equalSides')">
                    <line :x1="markAB.x - 5" :y1="markAB.y - 5" :x2="markAB.x + 5" :y2="markAB.y + 5" stroke="#3b82f6" stroke-width="3"/>
                    <line :x1="markBC.x - 5" :y1="markBC.y + 5" :x2="markBC.x + 5" :y2="markBC.y - 5" stroke="#3b82f6" stroke-width="3"/>
                </g>

                <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                <text :x="C.x" :y="C.y + 22" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle">C</text>
            </svg>

            <div class="flex flex-wrap gap-2 mb-3">
                <template x-for="h in hints" :key="h.id">
                    <button @mouseenter="hint = h.id" @mouseleave="hint = null"
                        :class="hint === h.id ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
                        class="px-3 py-1.5 rounded-full text-sm font-medium transition-all" x-text="h.label"></button>
                </template>
            </div>
            <div x-show="hint" x-cloak class="bg-amber-500/20 border-l-4 border-amber-500 p-3 rounded">
                <p class="text-slate-200" x-text="hints.find(h => h.id === hint)?.desc"></p>
            </div>
        </div>

        {{-- 7. Прямоугольный треугольник --}}
        <div x-data="rightTriangle()" class="bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-xl font-semibold text-white mb-4">7. Прямоугольный треугольник</h3>

            <svg viewBox="0 0 300 220" class="w-full h-52 mb-4">
                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                    fill="none" stroke="#dc2626" stroke-width="3.5" stroke-linejoin="round"/>

                {{-- Прямой угол (квадратик) --}}
                <path :d="rightAnglePath(C, A, B, 15)" fill="none"
                    :stroke="isHighlighted('rightAngle') ? '#f59e0b' : '#666'"
                    :stroke-width="isHighlighted('rightAngle') ? 3 : 2"/>

                {{-- Острые углы --}}
                <g x-show="isHighlighted('acuteAngles')">
                    <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                    <path :d="makeAngleArc(B, A, C, 25)" fill="none" stroke="#3b82f6" stroke-width="2.5"/>
                </g>

                <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
            </svg>

            <div class="flex flex-wrap gap-2 mb-3">
                <template x-for="h in hints" :key="h.id">
                    <button @mouseenter="hint = h.id" @mouseleave="hint = null"
                        :class="hint === h.id ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
                        class="px-3 py-1.5 rounded-full text-sm font-medium transition-all" x-text="h.label"></button>
                </template>
            </div>
            <div x-show="hint" x-cloak class="bg-amber-500/20 border-l-4 border-amber-500 p-3 rounded">
                <p class="text-slate-200" x-text="hints.find(h => h.id === hint)?.desc"></p>
            </div>
        </div>

        {{-- 8. Высота треугольника --}}
        <div x-data="heightDemo()" class="bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-xl font-semibold text-white mb-4">8. Высота треугольника</h3>

            <svg viewBox="0 0 300 220" class="w-full h-52 mb-4">
                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                    fill="none" stroke="#dc2626" stroke-width="3.5" stroke-linejoin="round"/>

                {{-- Высота BH - всегда видна --}}
                <line :x1="B.x" :y1="B.y" :x2="H.x" :y2="H.y"
                    :stroke="isHighlighted('height') ? '#3b82f6' : '#dc2626'"
                    :stroke-width="isHighlighted('height') ? 3 : 2"
                    :stroke-dasharray="isHighlighted('height') ? '8,5' : 'none'"
                    class="geo-line"/>
                <circle :cx="H.x" :cy="H.y" r="4"
                    :fill="isHighlighted('height') ? '#3b82f6' : '#dc2626'"/>
                {{-- Прямой угол - только при подсветке --}}
                <g x-show="isHighlighted('height')">
                    <path :d="rightAnglePath(H, A, B, 12)" fill="none" stroke="#3b82f6" stroke-width="2"/>
                </g>

                {{-- Угол BAC --}}
                <g x-show="isHighlighted('angleA')">
                    <path :d="makeAngleArc(A, C, B, 30)" fill="none" stroke="#f59e0b" stroke-width="2.5"/>
                </g>

                {{-- Угол ABH --}}
                <g x-show="isHighlighted('angleABH')">
                    <path :d="makeAngleArc(B, A, H, 25)" fill="none" stroke="#10b981" stroke-width="2.5"/>
                </g>

                <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
                <text :x="H.x + 15" :y="H.y - 5" :fill="isHighlighted('height') ? '#3b82f6' : '#60a5fa'" font-size="18" class="geo-label">H</text>
            </svg>

            <div class="flex flex-wrap gap-2 mb-3">
                <template x-for="h in hints" :key="h.id">
                    <button @mouseenter="hint = h.id" @mouseleave="hint = null"
                        :class="hint === h.id ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
                        class="px-3 py-1.5 rounded-full text-sm font-medium transition-all" x-text="h.label"></button>
                </template>
            </div>
            <div x-show="hint" x-cloak class="bg-amber-500/20 border-l-4 border-amber-500 p-3 rounded">
                <p class="text-slate-200" x-text="hints.find(h => h.id === hint)?.desc"></p>
            </div>
        </div>

        {{-- 9. Площадь прямоугольного треугольника --}}
        <div x-data="areaDemo()" class="bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-700">
            <h3 class="text-xl font-semibold text-white mb-4">9. Площадь прямоуг. треуг.</h3>

            <svg viewBox="0 0 300 220" class="w-full h-52 mb-4">
                <polygon :points="`${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`"
                    :fill="isHighlighted('area') ? 'rgba(245, 158, 11, 0.2)' : 'none'"
                    stroke="#dc2626" stroke-width="3.5" stroke-linejoin="round"/>

                {{-- Прямой угол --}}
                <path :d="rightAnglePath(C, A, B, 15)" fill="none" stroke="#666" stroke-width="2"/>

                {{-- Катеты --}}
                <g x-show="isHighlighted('catheti')">
                    <line :x1="A.x" :y1="A.y" :x2="C.x" :y2="C.y" stroke="#f59e0b" stroke-width="5"/>
                    <line :x1="C.x" :y1="C.y" :x2="B.x" :y2="B.y" stroke="#3b82f6" stroke-width="5"/>
                    <text :x="(A.x + C.x)/2 - 15" :y="(A.y + C.y)/2" fill="#f59e0b" font-size="18" class="geo-label">a</text>
                    <text :x="(C.x + B.x)/2 + 10" :y="(C.y + B.y)/2" fill="#3b82f6" font-size="18" class="geo-label">b</text>
                </g>

                <circle :cx="A.x" :cy="A.y" r="5" fill="#dc2626"/>
                <circle :cx="B.x" :cy="B.y" r="5" fill="#dc2626"/>
                <circle :cx="C.x" :cy="C.y" r="5" fill="#dc2626"/>

                <text :x="labelPos(A, center).x" :y="labelPos(A, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">A</text>
                <text :x="labelPos(B, center).x" :y="labelPos(B, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">B</text>
                <text :x="labelPos(C, center).x" :y="labelPos(C, center).y" fill="#60a5fa" font-size="20" class="geo-label" text-anchor="middle" dominant-baseline="middle">C</text>
            </svg>

            <div class="flex flex-wrap gap-2 mb-3">
                <template x-for="h in hints" :key="h.id">
                    <button @mouseenter="hint = h.id" @mouseleave="hint = null"
                        :class="hint === h.id ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
                        class="px-3 py-1.5 rounded-full text-sm font-medium transition-all" x-text="h.label"></button>
                </template>
            </div>
            <div x-show="hint" x-cloak class="bg-amber-500/20 border-l-4 border-amber-500 p-3 rounded">
                <p class="text-slate-200" x-text="hints.find(h => h.id === hint)?.desc"></p>
            </div>
            <div x-show="isHighlighted('area')" class="mt-3 p-3 bg-indigo-500/20 rounded text-center">
                <p class="text-white text-lg font-mono">S = <span class="text-amber-400">a</span> × <span class="text-blue-400">b</span> / 2</p>
            </div>
        </div>

    </div>

    <p class="text-center text-slate-500 text-sm mt-8">Все изображения генерируются программно</p>
</div>

<script>
    // Глобальные функции из спецификации
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

    window.labelPos = labelPos;
    window.makeAngleArc = makeAngleArc;
    window.rightAnglePath = rightAnglePath;

    // 1. Биссектриса
    function bisectorDemo() {
        const A = { x: 30, y: 190 };
        const B = { x: 180, y: 30 };
        const C = { x: 270, y: 190 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const D = { x: 150, y: 190 }; // Точка на AC
        return {
            hint: null, A, B, C, D, center,
            hints: [
                { id: 'bisector', label: 'Подсказка', desc: 'BD — биссектриса. ∠ABD = ∠DBC = ∠ABC / 2' },
                { id: 'angleA', label: 'Угол A', desc: '∠BAC — угол при вершине A' },
            ],
            isHighlighted(name) { return this.hint === name; },
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // 2. Медиана
    function medianDemo() {
        const A = { x: 30, y: 190 };
        const B = { x: 180, y: 30 };
        const C = { x: 270, y: 190 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const M = { x: (A.x + C.x) / 2, y: A.y };
        return {
            hint: null, A, B, C, M, center,
            hints: [
                { id: 'median', label: 'Подсказка', desc: 'BM — медиана. AM = MC = AC / 2' },
            ],
            isHighlighted(name) { return this.hint === name; },
            labelPos: (p, c) => window.labelPos(p, c),
        };
    }

    // 3. Сумма углов
    function anglesSum() {
        const A = { x: 30, y: 190 };
        const B = { x: 180, y: 30 };
        const C = { x: 270, y: 190 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            hint: null, A, B, C, center,
            isHighlighted(name) { return this.hint === name; },
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // 4. Внешний угол
    function externalAngle() {
        const A = { x: 30, y: 160 };
        const B = { x: 130, y: 30 };
        const C = { x: 200, y: 160 };
        const D = { x: 280, y: 160 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            hint: null, A, B, C, D, center,
            hints: [
                { id: 'external', label: 'Внешний', desc: 'Внешний угол = 180° − внутренний угол при C' },
                { id: 'internal', label: 'Внутренний', desc: 'Внутренний угол при C' },
                { id: 'nonadjacent', label: 'Несмежные', desc: 'Внешний угол = ∠A + ∠B (сумма несмежных)' },
            ],
            isHighlighted(name) { return this.hint === name; },
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // 5. Равнобедренный
    function isoscelesDemo() {
        const A = { x: 50, y: 190 };
        const B = { x: 150, y: 30 };
        const C = { x: 250, y: 190 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            hint: null, A, B, C, center,
            get markAB() { return { x: (this.A.x + this.B.x) / 2, y: (this.A.y + this.B.y) / 2 }; },
            get markBC() { return { x: (this.B.x + this.C.x) / 2, y: (this.B.y + this.C.y) / 2 }; },
            hints: [
                { id: 'equalSides', label: 'Равные стороны', desc: 'AB = BC — боковые стороны равны' },
                { id: 'baseAngles', label: 'Углы при основании', desc: '∠A = ∠C — углы при основании равны' },
                { id: 'vertexAngle', label: 'Угол при вершине', desc: '∠B — угол при вершине' },
            ],
            isHighlighted(name) { return this.hint === name; },
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // 6. Внешний угол равнобедренного
    function isoscelesExternal() {
        const A = { x: 30, y: 160 };
        const B = { x: 115, y: 30 };
        const C = { x: 200, y: 160 };
        const D = { x: 280, y: 160 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            hint: null, A, B, C, D, center,
            get markAB() { return { x: (this.A.x + this.B.x) / 2, y: (this.A.y + this.B.y) / 2 }; },
            get markBC() { return { x: (this.B.x + this.C.x) / 2, y: (this.B.y + this.C.y) / 2 }; },
            hints: [
                { id: 'external', label: 'Внешний угол', desc: 'Внешний угол при C' },
                { id: 'baseAngles', label: 'Углы при основании', desc: '∠A = внутр. ∠C. Угол при основании = 180° − внешний' },
                { id: 'equalSides', label: 'Равные стороны', desc: 'AB = BC' },
            ],
            isHighlighted(name) { return this.hint === name; },
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
        };
    }

    // 7. Прямоугольный
    function rightTriangle() {
        const A = { x: 30, y: 190 };
        const B = { x: 250, y: 40 };
        const C = { x: 250, y: 190 }; // Прямой угол
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            hint: null, A, B, C, center,
            hints: [
                { id: 'rightAngle', label: 'Прямой угол', desc: '∠C = 90° — прямой угол' },
                { id: 'acuteAngles', label: 'Острые углы', desc: '∠A + ∠B = 90° — сумма острых углов' },
            ],
            isHighlighted(name) { return this.hint === name; },
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
        };
    }

    // 8. Высота
    function heightDemo() {
        const A = { x: 30, y: 190 };
        const B = { x: 180, y: 30 };
        const C = { x: 270, y: 190 };
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        const H = { x: B.x, y: A.y }; // Основание высоты
        return {
            hint: null, A, B, C, H, center,
            hints: [
                { id: 'height', label: 'Высота', desc: 'BH ⊥ AC — высота из B' },
                { id: 'angleA', label: 'Угол A', desc: '∠BAC = α' },
                { id: 'angleABH', label: 'Угол ABH', desc: '∠ABH = 90° − α' },
            ],
            isHighlighted(name) { return this.hint === name; },
            labelPos: (p, c) => window.labelPos(p, c),
            makeAngleArc: (v, p1, p2, r) => window.makeAngleArc(v, p1, p2, r),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
        };
    }

    // 9. Площадь
    function areaDemo() {
        const A = { x: 30, y: 50 };
        const B = { x: 250, y: 190 };
        const C = { x: 30, y: 190 }; // Прямой угол
        const center = { x: (A.x + B.x + C.x) / 3, y: (A.y + B.y + C.y) / 3 };
        return {
            hint: null, A, B, C, center,
            hints: [
                { id: 'catheti', label: 'Катеты', desc: 'a и b — катеты прямоугольного треугольника' },
                { id: 'area', label: 'Площадь', desc: 'S = (a × b) / 2' },
            ],
            isHighlighted(name) { return this.hint === name; },
            labelPos: (p, c) => window.labelPos(p, c),
            rightAnglePath: (v, p1, p2, s) => window.rightAnglePath(v, p1, p2, s),
        };
    }
</script>

</body>
</html>
