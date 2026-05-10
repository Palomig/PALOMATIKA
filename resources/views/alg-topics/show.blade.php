<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Алгебра · {{ $grade }} кл. · Тема {{ (int)$topicId }} — PALOMATIKA</title>
    @include('partials.head-config')
    @include('partials.head-katex')
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .math-serif { font-family: 'PT Serif', Georgia, serif; }
        .geo-label  { font-family: 'PT Serif', serif; font-style: italic; }
        .katex      { font-size: 1.1em; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-dark-50 via-dark to-dark-50">

<div class="max-w-6xl mx-auto px-4 py-8">

    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-dark-light/50 rounded-xl p-4 border border-gray-800 gap-3 flex-wrap">
        <a href="{{ route('alg-topics.index') }}" class="text-emerald-400 hover:text-emerald-300 transition-colors flex items-center gap-2 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Назад
        </a>

        {{-- Grade switcher --}}
        <div class="flex gap-1.5">
            @foreach(\App\Services\AlgTaskDataService::GRADES as $g)
                @if($g === $grade)
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-bold text-xs">{{ $g }} кл</span>
                @else
                    <a href="{{ route('alg-topics.index') }}#grade-{{ $g }}"
                       class="px-2.5 py-1 rounded-lg bg-gray-800 text-gray-400 hover:bg-gray-700 transition text-xs">
                        {{ $g }} кл
                    </a>
                @endif
            @endforeach
        </div>

        {{-- Topic pills --}}
        <div class="flex gap-1 flex-wrap justify-center">
            @foreach($allTopicIds as $tid)
                @if($tid === $topicId)
                    <span class="px-2.5 py-1 rounded-lg bg-{{ $topicMeta['color'] ?? 'emerald' }}-500 text-white font-bold text-xs">{{ (int)$tid }}</span>
                @else
                    <a href="{{ route('alg-topics.show', ['grade' => $grade, 'id' => ltrim($tid, '0')]) }}"
                       class="px-2.5 py-1 rounded-lg bg-gray-800 text-gray-400 hover:bg-gray-700 transition text-xs">{{ (int)$tid }}</a>
                @endif
            @endforeach
        </div>

        <span class="text-gray-500 text-xs shrink-0">{{ $stats['tasks'] ?? 0 }} задач</span>
    </div>

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="text-slate-500 text-sm mb-1">Алгебра · {{ $grade }} класс</div>
        <h1 class="text-4xl font-bold text-white mb-2">Тема {{ (int)$topicId }}. {{ $topicMeta['title'] }}</h1>
        @if(!empty($topicMeta['description']))
        <p class="text-gray-400 text-lg">{{ $topicMeta['description'] }}</p>
        @endif
    </div>

    {{-- Stats --}}
    <div class="flex justify-center gap-6 mb-10">
        <div class="bg-dark-light px-6 py-3 rounded-xl border border-gray-800">
            <span class="text-{{ $topicMeta['color'] ?? 'emerald' }}-400 font-bold text-xl">{{ $stats['blocks'] ?? 0 }}</span>
            <span class="text-gray-400 ml-2">блоков</span>
        </div>
        <div class="bg-dark-light px-6 py-3 rounded-xl border border-gray-800">
            <span class="text-{{ $topicMeta['color'] ?? 'emerald' }}-400 font-bold text-xl">{{ $stats['tasks'] ?? 0 }}</span>
            <span class="text-gray-400 ml-2">задач</span>
        </div>
    </div>

    {{-- Curriculum summary --}}
    @if(!empty($curriculum) || !empty($microSkills) || !empty($homeworkSets))
        <section class="bg-dark-light rounded-xl p-6 border border-gray-800 mb-8">
            @if(!empty($curriculum['main_idea']))
                <div class="mb-5">
                    <h2 class="text-white font-semibold mb-2">Главная идея</h2>
                    <p class="text-gray-300">{{ $curriculum['main_idea'] }}</p>
                </div>
            @endif

            @if(!empty($microSkills))
                <h3 class="text-white font-semibold mb-3">Микронавыки</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($microSkills as $skill)
                        <div class="rounded-lg border border-gray-800 bg-dark/30 p-3">
                            <div class="text-{{ $topicMeta['color'] ?? 'emerald' }}-300 font-medium">
                                {{ $skill['title'] ?? $skill['id'] ?? 'Навык' }}
                            </div>
                            @if(!empty($skill['goal']))
                                <div class="text-sm text-gray-400 mt-1">{{ $skill['goal'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!empty($homeworkSets))
                <h3 class="text-white font-semibold mt-5 mb-3">Домашние работы</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($homeworkSets as $set)
                        <div class="rounded-lg border border-gray-800 bg-dark/30 p-3">
                            <div class="text-gray-200 font-medium">{{ $set['title'] ?? 'Домашняя работа' }}</div>
                            <div class="text-sm text-gray-500 mt-1">
                                {{ $set['tasks_count'] ?? count($set['tasks'] ?? []) }} заданий
                                @if(!empty($set['target_minutes']))
                                    · {{ $set['target_minutes'] }} мин
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    {{-- Content --}}
    @if(empty($blocks))
        <div class="text-center py-20 text-slate-500">
            <div class="text-5xl mb-4">📭</div>
            <p class="text-lg font-semibold">Задания ещё не добавлены</p>
            <p class="text-sm mt-2">Добавьте данные в <code class="bg-gray-800 px-2 py-1 rounded text-xs">storage/app/tasks/alg/grade_{{ $grade }}/topic_{{ $topicId }}.json</code></p>
        </div>
    @else
        @foreach($blocks as $block)
            @include('tasks.block', [
                'block'     => $block,
                'topicId'   => $topicId,
                'topicMeta' => $topicMeta,
                'color'     => $topicMeta['color'] ?? 'emerald',
            ])
        @endforeach
    @endif

    {{-- Info box --}}
    <div class="bg-dark-light rounded-xl p-6 border border-gray-800 mt-10">
        <h4 class="text-white font-semibold mb-3">Информация</h4>
        <div class="text-gray-400 text-sm space-y-1">
            <p><strong class="text-gray-300">Предмет:</strong> Алгебра</p>
            <p><strong class="text-gray-300">Класс:</strong> {{ $grade }}</p>
            <p><strong class="text-gray-300">Тема:</strong> {{ (int)$topicId }} ({{ count($allTopicIds) }} тем в классе)</p>
            <p><strong class="text-gray-300">Источник данных:</strong>
               <code class="bg-gray-800 px-2 py-1 rounded text-xs">storage/app/tasks/alg/grade_{{ $grade }}/topic_{{ $topicId }}.json</code>
            </p>
        </div>
    </div>

    <p class="text-center text-gray-500 text-sm mt-8">Формулы отображаются с помощью KaTeX</p>
</div>

@stack('scripts')

</body>
</html>
