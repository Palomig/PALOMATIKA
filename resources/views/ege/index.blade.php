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
                        dark: {
                            DEFAULT: '#06090f',
                            50: '#0a0f1a',
                            100: '#0d1320',
                            200: '#111827',
                            300: '#1a2332',
                            400: '#243044',
                            500: '#2e3d56'
                        },
                        accent: {
                            DEFAULT: '#8b5cf6',
                            light: '#a78bfa',
                            dark: '#7c3aed'
                        }
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
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0a0f1a; }
        ::-webkit-scrollbar-thumb { background: #2e3d56; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #3d4f6a; }
    </style>
</head>
<body class="min-h-screen bg-dark text-gray-200">

<div class="max-w-6xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-dark-100 rounded-xl p-4 border border-dark-400/50">
        <a href="{{ route('landing') }}" class="text-accent-light hover:text-accent transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            На главную
        </a>

        <div class="flex gap-4">
            <a href="{{ route('topics.index') }}" class="text-gray-400 hover:text-gray-200 transition">ОГЭ</a>
            <span class="text-accent-light font-semibold">ЕГЭ</span>
        </div>

        <span class="text-gray-500 text-xs">Профильный уровень</span>
    </div>

    {{-- Header --}}
    <div class="text-center mb-12">
        <div class="inline-block bg-accent/20 text-accent-light px-4 py-1 rounded-full text-sm font-medium mb-4 border border-accent/30">
            Единый Государственный Экзамен
        </div>
        <h1 class="text-5xl font-bold text-white mb-4">ЕГЭ профиль 2026</h1>
        <p class="text-gray-400 text-xl max-w-2xl mx-auto">
            Задания для подготовки к профильному уровню ЕГЭ по математике
        </p>
    </div>

    {{-- Stats overview --}}
    @php
        $availableCount = collect($topics)->filter(fn($t) => $t['exists'])->count();
        $totalTasks = collect($topics)->filter(fn($t) => $t['exists'])->sum(fn($t) => $t['stats']['tasks'] ?? 0);
    @endphp

    <div class="flex justify-center gap-6 mb-10">
        <div class="bg-dark-100 px-8 py-4 rounded-xl border border-dark-400/50">
            <span class="text-accent-light font-bold text-3xl">{{ $availableCount }}</span>
            <span class="text-gray-500 ml-2">из 19 заданий</span>
        </div>
        <div class="bg-dark-100 px-8 py-4 rounded-xl border border-dark-400/50">
            <span class="text-accent-light font-bold text-3xl">{{ $totalTasks }}</span>
            <span class="text-gray-500 ml-2">задач</span>
        </div>
    </div>

    {{-- Topics Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($topics as $topicId => $topic)
            @php
                $exists = $topic['exists'];
            @endphp

            @if($exists)
                <a href="{{ route('ege.show', ['id' => ltrim($topicId, '0')]) }}"
                   class="group bg-dark-100 hover:bg-dark-200 rounded-xl p-5 border border-dark-400/50 hover:border-accent/50 transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="bg-accent/20 text-accent-light w-12 h-12 rounded-xl flex items-center justify-center text-lg font-bold shrink-0 group-hover:bg-accent/30 transition">
                            {{ ltrim($topicId, '0') }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-white font-semibold text-lg mb-1 group-hover:text-accent-light transition">{{ $topic['title'] }}</h3>
                            <p class="text-gray-500 text-sm mb-3 line-clamp-2">{{ $topic['description'] }}</p>
                            @if($topic['stats'])
                                <div class="flex gap-4 text-xs text-gray-600">
                                    <span>{{ $topic['stats']['blocks'] }} блоков</span>
                                    <span>{{ $topic['stats']['tasks'] }} задач</span>
                                </div>
                            @endif
                        </div>
                        <svg class="w-5 h-5 text-gray-600 group-hover:text-accent-light transition shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            @else
                <div class="bg-dark-50 rounded-xl p-5 border border-dark-300/30 opacity-40">
                    <div class="flex items-start gap-4">
                        <div class="bg-dark-300/30 text-gray-600 w-12 h-12 rounded-xl flex items-center justify-center text-lg font-bold shrink-0">
                            {{ ltrim($topicId, '0') }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-gray-500 font-semibold text-lg mb-1">{{ $topic['title'] }}</h3>
                            <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ $topic['description'] }}</p>
                            <span class="text-xs text-gray-700">Скоро</span>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Footer --}}
    <div class="text-center mt-12 text-gray-600 text-sm">
        <p>PALOMATIKA - подготовка к ЕГЭ и ОГЭ по математике</p>
    </div>
</div>

</body>
</html>
