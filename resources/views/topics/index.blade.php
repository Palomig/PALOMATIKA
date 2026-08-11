@php
    /**
     * Общая витрина банка заданий: ОГЭ и ЕГЭ. Раньше у ЕГЭ была своя копия
     * страницы, которая расходилась с оригиналом; данные контроллеры отдают
     * одинаковые, отличаются только подписи и маршруты.
     */
    $bank = $bank ?? 'oge';
    $isEge = $bank === 'ege';
    $pageTitle = $isEge ? 'База заданий ЕГЭ' : 'База заданий ОГЭ';
    $pageLead = $isEge
        ? 'Задания 1–19 профильного уровня. Выберите номер задания.'
        : 'Темы 6–19. Выберите тему для решения и повторения.';
    $showRoute = $isEge ? 'ege.show' : 'topics.show';
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>{{ $pageTitle }} - PALOMATIKA</title>
    @include('partials.head-config')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,500;7..72,700&display=swap" rel="stylesheet">
    <style>
        .exam-title { font-family: 'Literata', serif; }
    </style>
</head>
<body class="min-h-screen bg-dark-50 text-slate-200">

<div class="max-w-6xl mx-auto px-4 py-10">
    <header class="mb-8">
        <h1 class="exam-title text-3xl sm:text-4xl text-white">{{ $pageTitle }}</h1>
        <p class="text-slate-400 mt-2">{{ $pageLead }}</p>
    </header>

    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
        @foreach($topics as $topicId => $topic)
            @php
                $color = $topic['color'] ?? 'gray';
                $hasData = $topic['exists'] ?? false;
                $stats = $topic['stats'] ?? null;
            @endphp

            <a href="{{ route($showRoute, ['id' => ltrim($topicId, '0')]) }}"
               class="group block rounded-xl border border-slate-800 bg-dark-light/30 p-4 hover:border-slate-700 hover:bg-dark-light/50 transition {{ !$hasData ? 'opacity-60' : '' }}">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg border border-{{ $color }}-700/60 bg-{{ $color }}-900/20 text-{{ $color }}-300 font-semibold flex items-center justify-center shrink-0">
                        {{ $topicId }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-white font-medium group-hover:text-{{ $color }}-300 transition truncate">{{ $topic['title'] }}</h2>
                        <p class="text-sm text-slate-500 leading-snug">{{ $topic['description'] }}</p>
                    </div>
                </div>

                @if($stats)
                    <div class="flex items-center gap-4 text-xs text-slate-400">
                        <span>{{ $stats['blocks'] }} блоков</span>
                        <span>{{ $stats['tasks'] }} заданий</span>
                    </div>
                @else
                    <p class="text-xs text-slate-600">Данные темы пока не готовы.</p>
                @endif
            </a>
        @endforeach
    </section>

    <div class="mt-8 flex flex-wrap gap-2">
        @unless($isEge)
            <a href="{{ route('oge.generator') }}" class="px-4 py-2 rounded-lg bg-emerald-700 text-white hover:bg-emerald-600 transition">Генератор вариантов</a>
        @endunless
        @if($isEge)
            <a href="{{ route('topics.index') }}" class="px-4 py-2 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 transition">→ ОГЭ</a>
        @else
            <a href="{{ route('ege.index') }}" class="px-4 py-2 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 transition">→ ЕГЭ</a>
        @endif
        <a href="{{ route('vpr-topics.index') }}" class="px-4 py-2 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 transition">→ ВПР</a>
        <a href="{{ route('alg-topics.index') }}" class="px-4 py-2 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 transition">→ Алгебра 5–8</a>
        <a href="{{ route('geom-topics.index') }}" class="px-4 py-2 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 transition">→ Геометрия 7–9</a>
        {{-- Legacy-раздел зарегистрирован не во всех окружениях: на публичной
             витрине ЕГЭ отсутствующий маршрут ронял всю страницу. --}}
        @if(Route::has('test.index'))
            <a href="{{ route('test.index') }}" class="px-4 py-2 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 transition">Legacy-раздел</a>
        @endif
    </div>
</div>

</body>
</html>
