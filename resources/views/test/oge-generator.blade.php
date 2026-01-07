<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Генератор вариантов ОГЭ - PALOMATIKA</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-4xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← Все задания</a>
        <div class="flex gap-3">
            <a href="{{ route('test.generator') }}" class="px-3 py-1.5 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition">Кастомный тест</a>
        </div>
    </div>

    {{-- Header --}}
    <div class="text-center mb-10">
        <h1 class="text-4xl font-bold text-white mb-3">📝 Вариант ОГЭ</h1>
        <p class="text-slate-400 text-lg">Генератор тренировочных вариантов (задания 6–19)</p>
    </div>

    {{-- Info Box --}}
    <div class="bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-2xl p-6 mb-8 border border-blue-500/30">
        <h3 class="text-blue-400 font-semibold text-lg mb-2">ℹ️ Что это?</h3>
        <p class="text-slate-300 leading-relaxed">
            Генератор создаёт полноценный тренировочный вариант ОГЭ по математике,
            включающий по одному случайному заданию из каждой темы 6–19.
            Каждый вариант получает уникальную ссылку — можно делиться с друзьями!
        </p>
    </div>

    {{-- Topics Preview --}}
    <div class="bg-slate-800 rounded-2xl p-6 mb-8 border border-slate-700">
        <h2 class="text-white font-semibold text-lg mb-5">Темы в варианте</h2>

        <div class="grid grid-cols-7 gap-3">
            @php
                $topics = [
                    ['num' => '6', 'title' => 'Вычисления', 'color' => 'blue'],
                    ['num' => '7', 'title' => 'Числа, прямая', 'color' => 'cyan'],
                    ['num' => '8', 'title' => 'Корни, степени', 'color' => 'violet'],
                    ['num' => '9', 'title' => 'Уравнения', 'color' => 'pink'],
                    ['num' => '10', 'title' => 'Вероятность', 'color' => 'orange'],
                    ['num' => '11', 'title' => 'Графики', 'color' => 'rose'],
                    ['num' => '12', 'title' => 'Формулы', 'color' => 'lime'],
                    ['num' => '13', 'title' => 'Неравенства', 'color' => 'teal'],
                    ['num' => '14', 'title' => 'Прогрессии', 'color' => 'indigo'],
                    ['num' => '15', 'title' => 'Треугольники', 'color' => 'emerald'],
                    ['num' => '16', 'title' => 'Окружность', 'color' => 'amber'],
                    ['num' => '17', 'title' => 'Четырёхуг.', 'color' => 'fuchsia'],
                    ['num' => '18', 'title' => 'Клетки', 'color' => 'sky'],
                    ['num' => '19', 'title' => 'Утверждения', 'color' => 'red'],
                ];
            @endphp

            @foreach($topics as $topic)
                <div class="bg-slate-700/50 rounded-xl p-3 text-center border border-slate-600">
                    <div class="text-2xl font-bold text-{{ $topic['color'] }}-400 mb-1">{{ $topic['num'] }}</div>
                    <div class="text-slate-500 text-xs leading-tight">{{ $topic['title'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Generate Button --}}
    <div class="bg-slate-800 rounded-2xl p-6 mb-8 border border-slate-700">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-white font-semibold text-lg">Готовы начать?</h2>
                <p class="text-slate-400 text-sm mt-1">Каждый клик создаёт новый уникальный вариант</p>
            </div>
            <div class="bg-slate-700/50 rounded-xl px-5 py-3 border border-slate-600 text-center">
                <span class="text-slate-400 text-sm">В варианте</span>
                <div class="text-emerald-400 font-bold text-2xl">14 заданий</div>
            </div>
        </div>

        @php
            $newHash = substr(md5(uniqid(mt_rand(), true)), 0, 10);
        @endphp

        <a href="{{ route('test.oge.show', ['hash' => $newHash]) }}"
           class="block w-full py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-white font-bold text-lg rounded-xl transition-all shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 text-center">
            🎯 Сгенерировать вариант
        </a>
    </div>

    {{-- Features --}}
    <div class="grid grid-cols-3 gap-4 mt-10">
        <div class="bg-slate-800/50 rounded-xl p-5 text-center border border-slate-700">
            <div class="text-3xl mb-2">🎲</div>
            <div class="text-slate-400 text-sm">Случайные задания</div>
        </div>
        <div class="bg-slate-800/50 rounded-xl p-5 text-center border border-slate-700">
            <div class="text-3xl mb-2">🔗</div>
            <div class="text-slate-400 text-sm">Уникальная ссылка</div>
        </div>
        <div class="bg-slate-800/50 rounded-xl p-5 text-center border border-slate-700">
            <div class="text-3xl mb-2">🖨️</div>
            <div class="text-slate-400 text-sm">Готов к печати</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-center mt-10 text-slate-500 text-sm">
        Задания взяты из базы <a href="{{ route('test.index') }}" class="text-blue-400 hover:underline">PALOMATIKA</a>
    </div>
</div>

</body>
</html>
