<!DOCTYPE html>
<html lang="ru">
<head>
    <title>База заданий ВПР — PALOMATIKA</title>
    @include('partials.head-config')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,500;7..72,700&display=swap" rel="stylesheet">
    <style>.exam-title { font-family: 'Literata', serif; }</style>
</head>
<body class="min-h-screen bg-dark-50 text-slate-200">

<div class="max-w-6xl mx-auto px-4 py-10">
    <header class="mb-8 flex items-start justify-between gap-4">
        <div>
            <h1 class="exam-title text-3xl sm:text-4xl text-white">База заданий ВПР</h1>
            <p class="text-slate-400 mt-2">5–8 классы · Математика. Выберите класс и задание.</p>
        </div>
        <div class="flex gap-2 shrink-0 flex-wrap">
            <a href="{{ route('topics.index') }}"
               class="px-4 py-2 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 transition text-sm">
                → ОГЭ
            </a>
            <a href="{{ route('alg-topics.index') }}"
               class="px-4 py-2 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 transition text-sm">
                → Алгебра
            </a>
            <a href="{{ route('geom-topics.index') }}"
               class="px-4 py-2 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 transition text-sm">
                → Геометрия
            </a>
        </div>
    </header>

    @foreach($gradeData as $grade => $data)
    @php $maxTopic = $data['max_topic']; @endphp
    <section class="mb-10">
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-xl font-bold text-white">{{ $grade }} класс</h2>
            <span class="text-slate-500 text-sm">{{ $maxTopic }} заданий</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
            @foreach($data['topics'] as $topicId => $topic)
            @php
                $color   = $topic['color'] ?? 'blue';
                $hasData = $topic['exists'] ?? false;
                $stats   = $topic['stats'] ?? null;
            @endphp
            <a href="{{ route('vpr-topics.show', ['grade' => $grade, 'id' => ltrim($topicId, '0')]) }}"
               class="group block rounded-xl border border-slate-800 bg-dark-light/30 p-4 hover:border-slate-700 hover:bg-dark-light/50 transition {{ !$hasData ? 'opacity-50' : '' }}">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg border border-{{ $color }}-700/60 bg-{{ $color }}-900/20 text-{{ $color }}-300 font-semibold flex items-center justify-center shrink-0 text-sm">
                        {{ (int)$topicId }}
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-white font-medium group-hover:text-{{ $color }}-300 transition truncate">{{ $topic['title'] }}</h3>
                        @if($topic['description'])
                        <p class="text-sm text-slate-500 leading-snug">{{ $topic['description'] }}</p>
                        @endif
                    </div>
                </div>
                @if($stats && $stats['total_tasks'] > 0)
                    <div class="text-xs text-slate-400">{{ $stats['total_tasks'] }} {{ $stats['total_tasks'] === 1 ? 'задача' : ($stats['total_tasks'] < 5 ? 'задачи' : 'задач') }}</div>
                @elseif(!$hasData)
                    <p class="text-xs text-slate-600">Данные пока не добавлены.</p>
                @endif
            </a>
            @endforeach
        </div>
    </section>
    @endforeach
</div>

</body>
</html>
