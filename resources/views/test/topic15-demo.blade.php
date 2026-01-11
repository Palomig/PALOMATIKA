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

        /* Геометрические стили */
        .geo-line {
            stroke: #22c55e;
            stroke-width: 2;
            fill: none;
            transition: stroke 0.2s ease, stroke-width 0.2s ease;
        }
        .geo-line-main {
            stroke: #22c55e;
            stroke-width: 2.5;
        }
        .geo-line-aux {
            stroke: #60a5fa;
            stroke-width: 1.5;
            stroke-dasharray: 5,3;
        }
        .geo-point {
            fill: #22c55e;
            transition: r 0.2s ease, fill 0.2s ease;
        }
        .geo-label {
            font-family: 'Times New Roman', serif;
            font-style: italic;
            font-weight: 500;
            fill: #60a5fa;
            font-size: 16px;
            user-select: none;
        }
        .geo-angle {
            stroke: #f59e0b;
            stroke-width: 1.5;
            fill: rgba(245, 158, 11, 0.15);
        }
        .geo-angle-label {
            font-family: 'Times New Roman', serif;
            font-style: italic;
            fill: #f59e0b;
            font-size: 12px;
        }

        /* Интерактивность */
        .task-card:hover .geo-line-aux {
            stroke: #93c5fd;
            stroke-width: 2;
        }
        .task-card:hover .geo-angle {
            fill: rgba(245, 158, 11, 0.25);
        }

        .katex { font-size: 1.1em; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-6xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">15. Треугольники (DEMO)</h1>
        <p class="text-slate-400 text-lg">Демонстрация SVG версии для одобрения</p>
        <p class="text-emerald-400 mt-2">Все рисунки созданы как SVG, без PNG</p>
    </div>

    {{-- Navigation --}}
    <div class="flex justify-center gap-4 mb-8">
        <a href="{{ route('test.topic15') }}" class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600">
            ← Старая версия (PNG)
        </a>
        <span class="px-4 py-2 bg-emerald-500 text-white rounded-lg font-bold">
            Новая версия (SVG)
        </span>
    </div>

    {{-- Блок 1. ФИПИ --}}
    <div class="mb-12">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-white">Блок 1. ФИПИ</h2>
        </div>

        {{-- I) Биссектриса --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-emerald-500">
                <h3 class="text-lg font-semibold text-white">
                    I) Биссектриса треугольника
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 1 --}}
                <div class="task-card bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-emerald-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-emerald-400 font-bold text-xl">1</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что $\angle BAC = 68°$, AD – биссектриса. Найдите угол BAD. Ответ дайте в градусах.
                        </div>
                    </div>

                    {{-- SVG рисунок --}}
                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 280 200" class="w-full max-w-[280px] h-auto">
                            {{-- Треугольник ABC --}}
                            <polygon points="40,170 240,170 140,30" class="geo-line-main" fill="none"/>

                            {{-- Биссектриса AD --}}
                            <line x1="40" y1="170" x2="190" y2="170" class="geo-line-aux"/>
                            <line x1="40" y1="170" x2="140" y2="30" class="geo-line-aux" style="stroke-dasharray: none; stroke: #22c55e;"/>

                            {{-- Точка D на BC --}}
                            @php
                                // Биссектриса делит противоположную сторону в отношении смежных сторон
                                // Для угла 68° при A, D находится между B и C
                                $dx = 140 + (240-140) * 0.45; // примерно 45% от B к C
                            @endphp
                            <line x1="40" y1="170" x2="{{ $dx }}" y2="170" class="geo-line-aux" style="stroke-dasharray: none; stroke: #60a5fa; stroke-width: 2;"/>

                            {{-- Дуга угла BAC (68°) --}}
                            <path d="M 70,170 A 30,30 0 0,0 55,145" class="geo-angle"/>
                            <text x="78" y="155" class="geo-angle-label">68°</text>

                            {{-- Дуга угла BAD (искомый = 34°) --}}
                            <path d="M 60,170 A 20,20 0 0,0 52,158" class="geo-angle" style="fill: rgba(34, 197, 94, 0.15); stroke: #22c55e;"/>
                            <text x="62" y="163" class="geo-angle-label" style="fill: #22c55e; font-size: 10px;">?</text>

                            {{-- Точки --}}
                            <circle cx="40" cy="170" r="4" class="geo-point"/>
                            <circle cx="240" cy="170" r="4" class="geo-point"/>
                            <circle cx="140" cy="30" r="4" class="geo-point"/>
                            <circle cx="{{ $dx }}" cy="170" r="4" class="geo-point" style="fill: #60a5fa;"/>

                            {{-- Подписи --}}
                            <text x="25" y="180" class="geo-label">A</text>
                            <text x="245" y="180" class="geo-label">B</text>
                            <text x="135" y="20" class="geo-label">C</text>
                            <text x="{{ $dx - 5 }}" y="190" class="geo-label" style="fill: #60a5fa;">D</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 34
                    </div>
                </div>

                {{-- Задача 2 --}}
                <div class="task-card bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-emerald-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-emerald-400 font-bold text-xl">2</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что $\angle BAC = 82°$, AD – биссектриса. Найдите угол BAD. Ответ дайте в градусах.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 280 200" class="w-full max-w-[280px] h-auto">
                            <polygon points="40,170 240,170 140,30" class="geo-line-main" fill="none"/>
                            @php $dx2 = 140 + (240-140) * 0.42; @endphp
                            <line x1="40" y1="170" x2="{{ $dx2 }}" y2="170" class="geo-line-aux" style="stroke-dasharray: none; stroke: #60a5fa; stroke-width: 2;"/>

                            <path d="M 70,170 A 30,30 0 0,0 50,140" class="geo-angle"/>
                            <text x="75" y="150" class="geo-angle-label">82°</text>

                            <circle cx="40" cy="170" r="4" class="geo-point"/>
                            <circle cx="240" cy="170" r="4" class="geo-point"/>
                            <circle cx="140" cy="30" r="4" class="geo-point"/>
                            <circle cx="{{ $dx2 }}" cy="170" r="4" class="geo-point" style="fill: #60a5fa;"/>

                            <text x="25" y="180" class="geo-label">A</text>
                            <text x="245" y="180" class="geo-label">B</text>
                            <text x="135" y="20" class="geo-label">C</text>
                            <text x="{{ $dx2 - 5 }}" y="190" class="geo-label" style="fill: #60a5fa;">D</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 41
                    </div>
                </div>

                {{-- Задача 3 --}}
                <div class="task-card bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-emerald-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-emerald-400 font-bold text-xl">3</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что $\angle BAC = 26°$, AD – биссектриса. Найдите угол BAD. Ответ дайте в градусах.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 280 200" class="w-full max-w-[280px] h-auto">
                            {{-- Более острый треугольник для угла 26° --}}
                            <polygon points="20,170 260,170 200,40" class="geo-line-main" fill="none"/>
                            @php $dx3 = 200 + (260-200) * 0.5; @endphp
                            <line x1="20" y1="170" x2="{{ $dx3 }}" y2="170" class="geo-line-aux" style="stroke-dasharray: none; stroke: #60a5fa; stroke-width: 2;"/>

                            <path d="M 55,170 A 35,35 0 0,0 48,158" class="geo-angle"/>
                            <text x="60" y="162" class="geo-angle-label">26°</text>

                            <circle cx="20" cy="170" r="4" class="geo-point"/>
                            <circle cx="260" cy="170" r="4" class="geo-point"/>
                            <circle cx="200" cy="40" r="4" class="geo-point"/>
                            <circle cx="{{ $dx3 }}" cy="170" r="4" class="geo-point" style="fill: #60a5fa;"/>

                            <text x="5" y="180" class="geo-label">A</text>
                            <text x="265" y="180" class="geo-label">B</text>
                            <text x="195" y="30" class="geo-label">C</text>
                            <text x="{{ $dx3 - 5 }}" y="190" class="geo-label" style="fill: #60a5fa;">D</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 13
                    </div>
                </div>

                {{-- Задача 4 --}}
                <div class="task-card bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-emerald-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-emerald-400 font-bold text-xl">4</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что $\angle BAC = 24°$, AD – биссектриса. Найдите угол BAD. Ответ дайте в градусах.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 280 200" class="w-full max-w-[280px] h-auto">
                            <polygon points="20,170 260,170 210,45" class="geo-line-main" fill="none"/>
                            @php $dx4 = 210 + (260-210) * 0.48; @endphp
                            <line x1="20" y1="170" x2="{{ $dx4 }}" y2="170" class="geo-line-aux" style="stroke-dasharray: none; stroke: #60a5fa; stroke-width: 2;"/>

                            <path d="M 55,170 A 35,35 0 0,0 50,160" class="geo-angle"/>
                            <text x="60" y="164" class="geo-angle-label">24°</text>

                            <circle cx="20" cy="170" r="4" class="geo-point"/>
                            <circle cx="260" cy="170" r="4" class="geo-point"/>
                            <circle cx="210" cy="45" r="4" class="geo-point"/>
                            <circle cx="{{ $dx4 }}" cy="170" r="4" class="geo-point" style="fill: #60a5fa;"/>

                            <text x="5" y="180" class="geo-label">A</text>
                            <text x="265" y="180" class="geo-label">B</text>
                            <text x="205" y="35" class="geo-label">C</text>
                            <text x="{{ $dx4 - 5 }}" y="190" class="geo-label" style="fill: #60a5fa;">D</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 12
                    </div>
                </div>
            </div>
        </div>

        {{-- II) Медиана --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-emerald-500">
                <h3 class="text-lg font-semibold text-white">
                    II) Медиана треугольника
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 5 --}}
                <div class="task-card bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-emerald-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-emerald-400 font-bold text-xl">5</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AC=14, BM – медиана, BM=10. Найдите AM.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 280 200" class="w-full max-w-[280px] h-auto">
                            {{-- Треугольник ABC --}}
                            <polygon points="30,170 250,170 180,40" class="geo-line-main" fill="none"/>

                            {{-- M - середина AC --}}
                            @php
                                $mx = (30 + 250) / 2; // середина AC по X
                                $my = 170; // на основании
                            @endphp

                            {{-- Медиана BM --}}
                            <line x1="180" y1="40" x2="{{ $mx }}" y2="{{ $my }}" class="geo-line-aux" style="stroke-dasharray: none; stroke: #60a5fa; stroke-width: 2;"/>

                            {{-- Метка длины AC = 14 --}}
                            <text x="{{ $mx }}" y="188" class="geo-label" style="font-size: 11px; fill: #94a3b8;">AC = 14</text>

                            {{-- Метка длины BM = 10 --}}
                            <text x="125" y="100" class="geo-label" style="font-size: 11px; fill: #60a5fa; transform: rotate(-50deg); transform-origin: 125px 100px;">BM = 10</text>

                            {{-- Точки --}}
                            <circle cx="30" cy="170" r="4" class="geo-point"/>
                            <circle cx="250" cy="170" r="4" class="geo-point"/>
                            <circle cx="180" cy="40" r="4" class="geo-point"/>
                            <circle cx="{{ $mx }}" cy="{{ $my }}" r="4" class="geo-point" style="fill: #60a5fa;"/>

                            {{-- Подписи --}}
                            <text x="15" y="180" class="geo-label">A</text>
                            <text x="255" y="180" class="geo-label">C</text>
                            <text x="175" y="30" class="geo-label">B</text>
                            <text x="{{ $mx - 5 }}" y="165" class="geo-label" style="fill: #60a5fa;">M</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 7 (AM = AC/2 = 14/2 = 7)
                    </div>
                </div>

                {{-- Задача 6 --}}
                <div class="task-card bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-emerald-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-emerald-400 font-bold text-xl">6</span>
                        <div class="text-slate-200">
                            В треугольнике ABC известно, что AC=16, BM – медиана, BM=12. Найдите AM.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 280 200" class="w-full max-w-[280px] h-auto">
                            <polygon points="30,170 250,170 200,35" class="geo-line-main" fill="none"/>
                            @php $mx2 = (30 + 250) / 2; @endphp
                            <line x1="200" y1="35" x2="{{ $mx2 }}" y2="170" class="geo-line-aux" style="stroke-dasharray: none; stroke: #60a5fa; stroke-width: 2;"/>

                            <text x="{{ $mx2 }}" y="188" class="geo-label" style="font-size: 11px; fill: #94a3b8;">AC = 16</text>

                            <circle cx="30" cy="170" r="4" class="geo-point"/>
                            <circle cx="250" cy="170" r="4" class="geo-point"/>
                            <circle cx="200" cy="35" r="4" class="geo-point"/>
                            <circle cx="{{ $mx2 }}" cy="170" r="4" class="geo-point" style="fill: #60a5fa;"/>

                            <text x="15" y="180" class="geo-label">A</text>
                            <text x="255" y="180" class="geo-label">C</text>
                            <text x="195" y="25" class="geo-label">B</text>
                            <text x="{{ $mx2 - 5 }}" y="165" class="geo-label" style="fill: #60a5fa;">M</text>
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
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-emerald-500">
                <h3 class="text-lg font-semibold text-white">
                    III) Сумма углов треугольника
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 9 --}}
                <div class="task-card bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-emerald-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-emerald-400 font-bold text-xl">9</span>
                        <div class="text-slate-200">
                            В треугольнике два угла равны 72° и 42°. Найдите его третий угол. Ответ дайте в градусах.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 280 200" class="w-full max-w-[280px] h-auto">
                            <polygon points="30,170 250,170 120,40" class="geo-line-main" fill="none"/>

                            {{-- Угол A = 72° --}}
                            <path d="M 60,170 A 30,30 0 0,0 45,145" class="geo-angle"/>
                            <text x="65" y="155" class="geo-angle-label">72°</text>

                            {{-- Угол B = 42° --}}
                            <path d="M 220,170 A 30,30 0 0,1 235,148" class="geo-angle"/>
                            <text x="205" y="155" class="geo-angle-label">42°</text>

                            {{-- Угол C = ? (66°) --}}
                            <path d="M 120,65 A 25,25 0 0,0 105,55" class="geo-angle" style="stroke: #22c55e; fill: rgba(34, 197, 94, 0.15);"/>
                            <text x="105" y="78" class="geo-angle-label" style="fill: #22c55e;">?</text>

                            <circle cx="30" cy="170" r="4" class="geo-point"/>
                            <circle cx="250" cy="170" r="4" class="geo-point"/>
                            <circle cx="120" cy="40" r="4" class="geo-point"/>

                            <text x="15" y="180" class="geo-label">A</text>
                            <text x="255" y="180" class="geo-label">B</text>
                            <text x="115" y="30" class="geo-label">C</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 66 (180° - 72° - 42° = 66°)
                    </div>
                </div>

                {{-- Задача 10 --}}
                <div class="task-card bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-emerald-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-emerald-400 font-bold text-xl">10</span>
                        <div class="text-slate-200">
                            В треугольнике два угла равны 43° и 88°. Найдите его третий угол. Ответ дайте в градусах.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 280 200" class="w-full max-w-[280px] h-auto">
                            <polygon points="30,170 250,170 140,30" class="geo-line-main" fill="none"/>

                            <path d="M 60,170 A 30,30 0 0,0 52,158" class="geo-angle"/>
                            <text x="65" y="160" class="geo-angle-label">43°</text>

                            <path d="M 220,170 A 30,30 0 0,1 222,140" class="geo-angle"/>
                            <text x="200" y="150" class="geo-angle-label">88°</text>

                            <path d="M 140,55 A 25,25 0 0,0 125,48" class="geo-angle" style="stroke: #22c55e; fill: rgba(34, 197, 94, 0.15);"/>
                            <text x="125" y="68" class="geo-angle-label" style="fill: #22c55e;">?</text>

                            <circle cx="30" cy="170" r="4" class="geo-point"/>
                            <circle cx="250" cy="170" r="4" class="geo-point"/>
                            <circle cx="140" cy="30" r="4" class="geo-point"/>

                            <text x="15" y="180" class="geo-label">A</text>
                            <text x="255" y="180" class="geo-label">B</text>
                            <text x="135" y="20" class="geo-label">C</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 49
                    </div>
                </div>
            </div>
        </div>

        {{-- IV) Теорема Пифагора --}}
        <div class="mb-10">
            <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-emerald-500">
                <h3 class="text-lg font-semibold text-white">
                    IV) Теорема Пифагора
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Задача 45 --}}
                <div class="task-card bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-emerald-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-emerald-400 font-bold text-xl">45</span>
                        <div class="text-slate-200">
                            Катеты прямоугольного треугольника равны 7 и 24. Найдите гипотенузу этого треугольника.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 280 200" class="w-full max-w-[280px] h-auto">
                            {{-- Прямоугольный треугольник --}}
                            <polygon points="40,170 250,170 40,50" class="geo-line-main" fill="none"/>

                            {{-- Прямой угол --}}
                            <rect x="40" y="150" width="20" height="20" fill="none" stroke="#f59e0b" stroke-width="1.5"/>

                            {{-- Метки сторон --}}
                            <text x="145" y="188" class="geo-label" style="font-size: 12px; fill: #94a3b8;">24</text>
                            <text x="18" y="115" class="geo-label" style="font-size: 12px; fill: #94a3b8;">7</text>
                            <text x="150" y="100" class="geo-label" style="font-size: 12px; fill: #22c55e;">?</text>

                            <circle cx="40" cy="170" r="4" class="geo-point"/>
                            <circle cx="250" cy="170" r="4" class="geo-point"/>
                            <circle cx="40" cy="50" r="4" class="geo-point"/>

                            <text x="25" y="180" class="geo-label">A</text>
                            <text x="255" y="180" class="geo-label">B</text>
                            <text x="25" y="45" class="geo-label">C</text>
                        </svg>
                    </div>

                    <div class="mt-3 text-slate-500 text-sm">
                        <span class="text-emerald-400">Ответ:</span> 25 ($\sqrt{7^2 + 24^2} = \sqrt{625} = 25$)
                    </div>
                </div>

                {{-- Задача 46 --}}
                <div class="task-card bg-slate-800/70 rounded-xl p-5 border border-slate-700 hover:border-emerald-500/50 transition-all">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="text-emerald-400 font-bold text-xl">46</span>
                        <div class="text-slate-200">
                            Катеты прямоугольного треугольника равны 8 и 15. Найдите гипотенузу этого треугольника.
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 flex justify-center">
                        <svg viewBox="0 0 280 200" class="w-full max-w-[280px] h-auto">
                            <polygon points="40,170 240,170 40,60" class="geo-line-main" fill="none"/>

                            <rect x="40" y="150" width="20" height="20" fill="none" stroke="#f59e0b" stroke-width="1.5"/>

                            <text x="140" y="188" class="geo-label" style="font-size: 12px; fill: #94a3b8;">15</text>
                            <text x="18" y="120" class="geo-label" style="font-size: 12px; fill: #94a3b8;">8</text>
                            <text x="145" y="105" class="geo-label" style="font-size: 12px; fill: #22c55e;">?</text>

                            <circle cx="40" cy="170" r="4" class="geo-point"/>
                            <circle cx="240" cy="170" r="4" class="geo-point"/>
                            <circle cx="40" cy="60" r="4" class="geo-point"/>

                            <text x="25" y="180" class="geo-label">A</text>
                            <text x="245" y="180" class="geo-label">B</text>
                            <text x="25" y="55" class="geo-label">C</text>
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
        <h4 class="text-emerald-400 font-semibold mb-4">Демонстрация SVG рисунков</h4>
        <ul class="text-slate-300 space-y-2">
            <li>✅ Все рисунки — чистые SVG (без PNG)</li>
            <li>✅ Тёмная тема, соответствует дизайну сайта</li>
            <li>✅ Интерактивные эффекты при наведении</li>
            <li>✅ Правильные подписи точек (A, B, C, D, M)</li>
            <li>✅ Отображение углов с дугами</li>
            <li>✅ Прямой угол обозначен квадратиком</li>
        </ul>
        <p class="text-slate-400 mt-4">
            <strong>Если одобряете,</strong> продолжу создание SVG для всех 192 задач темы 15.
        </p>
    </div>

</div>

{{-- Инструмент для пометки заданий --}}
@include('components.task-review-tool', ['topicId' => '15'])

</body>
</html>
