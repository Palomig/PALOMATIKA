<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ЕГЭ профиль - Задания - PALOMATIKA</title>

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { DEFAULT: '#1a1a2e', light: '#252542', lighter: '#2d2d4a' },
                        coral: { DEFAULT: '#ff6b6b', dark: '#e85555', light: '#ff8585' }
                    }
                }
            }
        }
    </script>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-950 via-slate-900 to-slate-900">

<div class="max-w-6xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-purple-900/30 rounded-xl p-4 border border-purple-800/50">
        <a href="{{ route('landing') }}" class="text-purple-400 hover:text-purple-300 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            На главную
        </a>

        <div class="flex gap-4">
            <a href="{{ route('topics.index') }}" class="text-purple-400 hover:text-purple-300 transition">ОГЭ</a>
            <span class="text-purple-300 font-semibold">ЕГЭ</span>
        </div>

        <span class="text-purple-500 text-xs">Профильный уровень</span>
    </div>

    {{-- Header --}}
    <div class="text-center mb-12">
        <div class="inline-block bg-purple-600/20 text-purple-300 px-4 py-1 rounded-full text-sm font-medium mb-4">
            Единый Государственный Экзамен
        </div>
        <h1 class="text-5xl font-bold text-white mb-4">ЕГЭ профиль 2026</h1>
        <p class="text-purple-300/70 text-xl max-w-2xl mx-auto">
            Задания для подготовки к профильному уровню ЕГЭ по математике
        </p>
    </div>

    {{-- Stats overview --}}
    @php
        $availableCount = collect($topics)->filter(fn($t) => $t['exists'])->count();
        $totalTasks = collect($topics)->filter(fn($t) => $t['exists'])->sum(fn($t) => $t['stats']['tasks'] ?? 0);
    @endphp

    <div class="flex justify-center gap-6 mb-10">
        <div class="bg-purple-900/30 px-8 py-4 rounded-xl border border-purple-800/50">
            <span class="text-purple-400 font-bold text-3xl">{{ $availableCount }}</span>
            <span class="text-purple-300/60 ml-2">из 19 заданий</span>
        </div>
        <div class="bg-purple-900/30 px-8 py-4 rounded-xl border border-purple-800/50">
            <span class="text-purple-400 font-bold text-3xl">{{ $totalTasks }}</span>
            <span class="text-purple-300/60 ml-2">задач</span>
        </div>
    </div>

    {{-- Topics Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($topics as $topicId => $topic)
            @php
                $exists = $topic['exists'];
                $colorClass = $topic['color'] ?? 'purple';
            @endphp

            @if($exists)
                <a href="{{ route('ege.show', ['id' => ltrim($topicId, '0')]) }}"
                   class="group bg-purple-900/30 hover:bg-purple-800/40 rounded-xl p-5 border border-purple-800/50 hover:border-purple-600/50 transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="bg-purple-600/30 text-purple-300 w-12 h-12 rounded-xl flex items-center justify-center text-lg font-bold shrink-0 group-hover:bg-purple-500/40 transition">
                            {{ ltrim($topicId, '0') }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-white font-semibold text-lg mb-1 group-hover:text-purple-200 transition">{{ $topic['title'] }}</h3>
                            <p class="text-purple-300/60 text-sm mb-3 line-clamp-2">{{ $topic['description'] }}</p>
                            @if($topic['stats'])
                                <div class="flex gap-4 text-xs text-purple-400/70">
                                    <span>{{ $topic['stats']['blocks'] }} блоков</span>
                                    <span>{{ $topic['stats']['tasks'] }} задач</span>
                                </div>
                            @endif
                        </div>
                        <svg class="w-5 h-5 text-purple-500 group-hover:text-purple-300 transition shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            @else
                <div class="bg-slate-800/30 rounded-xl p-5 border border-slate-700/30 opacity-50">
                    <div class="flex items-start gap-4">
                        <div class="bg-slate-700/30 text-slate-500 w-12 h-12 rounded-xl flex items-center justify-center text-lg font-bold shrink-0">
                            {{ ltrim($topicId, '0') }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-slate-400 font-semibold text-lg mb-1">{{ $topic['title'] }}</h3>
                            <p class="text-slate-500 text-sm mb-3 line-clamp-2">{{ $topic['description'] }}</p>
                            <span class="text-xs text-slate-600">Скоро</span>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Footer --}}
    <div class="text-center mt-12 text-purple-500/50 text-sm">
        <p>PALOMATIKA - подготовка к ЕГЭ и ОГЭ по математике</p>
    </div>
</div>

</body>
</html>
