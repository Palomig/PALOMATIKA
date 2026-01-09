<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>База заданий ОГЭ - PALOMATIKA</title>

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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-6xl mx-auto px-4 py-12">
    {{-- Header --}}
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-white mb-4">База заданий ОГЭ</h1>
        <p class="text-slate-400 text-lg">Выберите тему для просмотра заданий</p>
    </div>

    {{-- Topics Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($topics as $topicId => $topic)
            @php
                $color = $topic['color'] ?? 'gray';
                $hasData = $topic['exists'] ?? false;
                $stats = $topic['stats'] ?? null;
            @endphp

            <a href="{{ route('topics.show', ['id' => ltrim($topicId, '0')]) }}"
               class="block bg-slate-800/50 rounded-2xl border border-slate-700 p-6 hover:border-{{ $color }}-500/50 hover:bg-slate-800 transition-all group {{ !$hasData ? 'opacity-50' : '' }}">

                {{-- Topic Number --}}
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-{{ $color }}-500/20 flex items-center justify-center">
                        <span class="text-{{ $color }}-400 font-bold text-lg">{{ $topicId }}</span>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold group-hover:text-{{ $color }}-400 transition">
                            {{ $topic['title'] }}
                        </h3>
                        <p class="text-slate-500 text-sm">{{ $topic['description'] }}</p>
                    </div>
                </div>

                {{-- Stats --}}
                @if($stats)
                    <div class="flex gap-4 text-sm">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <span class="text-slate-400">{{ $stats['blocks'] }} блоков</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="text-slate-400">{{ $stats['tasks'] }} заданий</span>
                        </div>
                    </div>
                @else
                    <div class="text-slate-600 text-sm italic">Данные не загружены</div>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Links --}}
    <div class="mt-12 flex justify-center gap-4">
        <a href="{{ route('test.oge.generator') }}" class="px-6 py-3 bg-coral rounded-xl text-white font-semibold hover:bg-coral-dark transition">
            Генератор ОГЭ вариантов
        </a>
        <a href="{{ route('test.index') }}" class="px-6 py-3 bg-slate-700 rounded-xl text-slate-300 hover:bg-slate-600 transition">
            Старая версия (legacy)
        </a>
    </div>
</div>

</body>
</html>
