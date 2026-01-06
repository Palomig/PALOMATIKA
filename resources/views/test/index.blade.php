<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Темы ОГЭ - PALOMATIKA</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-6xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-white mb-3">Темы ОГЭ</h1>
        <p class="text-slate-400 text-lg">Интерактивные задания с SVG иллюстрациями</p>
    </div>

    {{-- Topics Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @php
            $topics = [
                6 => ['title' => 'Дроби и степени', 'tasks' => 54, 'icon' => 'fraction', 'ready' => true],
                7 => ['title' => 'Числа, координатная прямая', 'tasks' => 45, 'icon' => 'number-line', 'ready' => true],
                8 => ['title' => 'Алгебраические выражения', 'tasks' => 0, 'icon' => 'algebra', 'ready' => false],
                9 => ['title' => 'Уравнения', 'tasks' => 0, 'icon' => 'equation', 'ready' => false],
                10 => ['title' => 'Неравенства', 'tasks' => 0, 'icon' => 'inequality', 'ready' => false],
                11 => ['title' => 'Последовательности', 'tasks' => 0, 'icon' => 'sequence', 'ready' => false],
                12 => ['title' => 'Функции и графики', 'tasks' => 0, 'icon' => 'graph', 'ready' => false],
                13 => ['title' => 'Расчёты по формулам', 'tasks' => 0, 'icon' => 'formula', 'ready' => false],
                14 => ['title' => 'Система уравнений', 'tasks' => 0, 'icon' => 'system', 'ready' => false],
                15 => ['title' => 'Треугольники', 'tasks' => 108, 'icon' => 'triangle', 'ready' => true],
                16 => ['title' => 'Окружность, круг', 'tasks' => 72, 'icon' => 'circle', 'ready' => true],
                17 => ['title' => 'Четырёхугольники', 'tasks' => 112, 'icon' => 'quadrilateral', 'ready' => true],
                18 => ['title' => 'Фигуры на решётке', 'tasks' => 72, 'icon' => 'grid', 'ready' => true],
                19 => ['title' => 'Анализ геом. высказываний', 'tasks' => 54, 'icon' => 'analysis', 'ready' => true],
            ];
        @endphp

        @foreach($topics as $num => $topic)
            <a href="{{ route('test.topic' . str_pad($num, 2, '0', STR_PAD_LEFT)) }}"
               class="group relative bg-slate-800/70 rounded-2xl p-5 border border-slate-700 hover:border-cyan-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-cyan-500/10 {{ !$topic['ready'] ? 'opacity-50 pointer-events-none' : '' }}">

                {{-- Topic Number Badge --}}
                <div class="absolute -top-3 -left-3 w-10 h-10 rounded-xl {{ $topic['ready'] ? 'bg-cyan-500' : 'bg-slate-600' }} flex items-center justify-center text-white font-bold text-lg shadow-lg">
                    {{ $num }}
                </div>

                {{-- Icon --}}
                <div class="flex justify-center mb-4 mt-2">
                    @switch($topic['icon'])
                        @case('fraction')
                            <svg class="w-16 h-16 text-cyan-400" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="32" x2="52" y2="32"/>
                                <text x="32" y="22" text-anchor="middle" fill="currentColor" font-size="14" font-weight="bold">a</text>
                                <text x="32" y="48" text-anchor="middle" fill="currentColor" font-size="14" font-weight="bold">b</text>
                            </svg>
                            @break
                        @case('number-line')
                            <svg class="w-16 h-16 text-cyan-400" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="32" x2="56" y2="32"/>
                                <line x1="16" y1="28" x2="16" y2="36"/>
                                <line x1="32" y1="26" x2="32" y2="38"/>
                                <line x1="48" y1="28" x2="48" y2="36"/>
                                <circle cx="24" cy="32" r="4" fill="currentColor"/>
                            </svg>
                            @break
                        @case('triangle')
                            <svg class="w-16 h-16 text-cyan-400" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polygon points="32,8 8,56 56,56" stroke-linejoin="round"/>
                            </svg>
                            @break
                        @case('circle')
                            <svg class="w-16 h-16 text-cyan-400" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="32" cy="32" r="24"/>
                                <circle cx="32" cy="32" r="2" fill="currentColor"/>
                                <line x1="32" y1="32" x2="50" y2="20"/>
                            </svg>
                            @break
                        @case('quadrilateral')
                            <svg class="w-16 h-16 text-cyan-400" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polygon points="12,48 8,16 48,8 56,44" stroke-linejoin="round"/>
                            </svg>
                            @break
                        @case('grid')
                            <svg class="w-16 h-16 text-cyan-400" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="8" y="8" width="48" height="48" stroke-width="2"/>
                                <line x1="8" y1="20" x2="56" y2="20"/>
                                <line x1="8" y1="32" x2="56" y2="32"/>
                                <line x1="8" y1="44" x2="56" y2="44"/>
                                <line x1="20" y1="8" x2="20" y2="56"/>
                                <line x1="32" y1="8" x2="32" y2="56"/>
                                <line x1="44" y1="8" x2="44" y2="56"/>
                                <polygon points="20,20 20,44 44,44" fill="none" stroke="#10b981" stroke-width="2.5"/>
                            </svg>
                            @break
                        @case('analysis')
                            <svg class="w-16 h-16 text-cyan-400" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="12" y="8" width="40" height="48" rx="4"/>
                                <line x1="20" y1="20" x2="44" y2="20"/>
                                <line x1="20" y1="28" x2="40" y2="28"/>
                                <line x1="20" y1="36" x2="36" y2="36"/>
                                <circle cx="20" cy="44" r="3" fill="currentColor"/>
                                <circle cx="32" cy="44" r="3" fill="currentColor"/>
                            </svg>
                            @break
                        @default
                            <svg class="w-16 h-16 text-slate-500" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="12" y="12" width="40" height="40" rx="4"/>
                                <line x1="22" y1="24" x2="42" y2="24"/>
                                <line x1="22" y1="32" x2="38" y2="32"/>
                                <line x1="22" y1="40" x2="34" y2="40"/>
                            </svg>
                    @endswitch
                </div>

                {{-- Title --}}
                <h3 class="text-white font-semibold text-center mb-2 group-hover:text-cyan-400 transition-colors">
                    {{ $topic['title'] }}
                </h3>

                {{-- Task Count --}}
                @if($topic['ready'])
                    <div class="text-center">
                        <span class="inline-flex items-center gap-1 text-sm text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            {{ $topic['tasks'] }} заданий
                        </span>
                    </div>
                @else
                    <div class="text-center">
                        <span class="inline-flex items-center gap-1 text-sm text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Скоро
                        </span>
                    </div>
                @endif

                {{-- Ready Badge --}}
                @if($topic['ready'])
                    <div class="absolute top-3 right-3">
                        <span class="flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                        </span>
                    </div>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Stats --}}
    <div class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-slate-800/50 rounded-xl p-4 text-center border border-slate-700">
            <div class="text-3xl font-bold text-cyan-400">14</div>
            <div class="text-slate-400 text-sm">Тем</div>
        </div>
        <div class="bg-slate-800/50 rounded-xl p-4 text-center border border-slate-700">
            <div class="text-3xl font-bold text-green-400">7</div>
            <div class="text-slate-400 text-sm">Готово</div>
        </div>
        <div class="bg-slate-800/50 rounded-xl p-4 text-center border border-slate-700">
            <div class="text-3xl font-bold text-amber-400">517</div>
            <div class="text-slate-400 text-sm">Заданий</div>
        </div>
        <div class="bg-slate-800/50 rounded-xl p-4 text-center border border-slate-700">
            <div class="text-3xl font-bold text-purple-400">SVG</div>
            <div class="text-slate-400 text-sm">Иллюстрации</div>
        </div>
    </div>

    {{-- Links --}}
    <div class="mt-8 flex justify-center gap-4">
        <a href="{{ route('test.pdf.index') }}" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-lg transition-colors">
            PDF Парсер
        </a>
        <a href="{{ route('test.generator') }}" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-lg transition-colors">
            Генератор тестов
        </a>
    </div>

    <p class="text-center text-slate-500 text-sm mt-8">PALOMATIKA — подготовка к ОГЭ по математике</p>
</div>

</body>
</html>
