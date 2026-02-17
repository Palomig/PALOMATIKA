<!DOCTYPE html>
<html lang="ru">
<head>
    <title>{{ $topicId }}. {{ $topicMeta['title'] }} - ЕГЭ - PALOMATIKA</title>
    @include('partials.head-config')
    @include('partials.head-katex')
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&display=swap" rel="stylesheet">
    @push('styles')
    <style>
        .math-serif { font-family: 'PT Serif', Georgia, serif; }
        .number-line { font-family: 'Times New Roman', serif; }
        .geo-label { font-family: 'PT Serif', serif; font-style: italic; }
        .katex { font-size: 1.1em; }

        /* EGE scrollbar override */
        ::-webkit-scrollbar-track { background: #0a0f1a; }
        ::-webkit-scrollbar-thumb { background: #2e3d56; }
        ::-webkit-scrollbar-thumb:hover { background: #3d4f6a; }

        /* EGE darker theme */
        body.ege-theme { background: #06090f; }
    </style>
    @endpush
</head>
<body class="min-h-screen bg-dark text-gray-200 ege-theme">

<div class="max-w-6xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-dark-100 rounded-xl p-4 border border-dark-400/50">
        <a href="{{ route('ege.index') }}" class="text-accent-light hover:text-accent transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Назад к заданиям ЕГЭ
        </a>

        <div class="flex gap-1.5 flex-wrap justify-center">
            @foreach(['01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19'] as $tid)
                @if($tid === $topicId)
                    <span class="px-2.5 py-1 rounded-lg bg-accent text-white font-bold text-xs">{{ $tid }}</span>
                @else
                    <a href="{{ route('ege.show', ['id' => ltrim($tid, '0')]) }}"
                       class="px-2.5 py-1 rounded-lg bg-dark-300 text-gray-400 hover:bg-dark-400 hover:text-gray-200 transition text-xs">{{ $tid }}</a>
                @endif
            @endforeach
        </div>

        <span class="text-gray-500 text-xs">{{ $stats['tasks'] ?? 0 }} заданий</span>
    </div>

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="inline-block bg-accent/20 text-accent-light px-4 py-1 rounded-full text-sm font-medium mb-4 border border-accent/30">
            ЕГЭ профиль
        </div>
        <h1 class="text-4xl font-bold text-white mb-2">Задание {{ ltrim($topicId, '0') }}. {{ $topicMeta['title'] }}</h1>
        <p class="text-gray-400 text-lg">{{ $topicMeta['description'] }}</p>
    </div>

    {{-- Stats --}}
    <div class="flex justify-center gap-6 mb-10">
        <div class="bg-dark-100 px-6 py-3 rounded-xl border border-dark-400/50">
            <span class="text-accent-light font-bold text-xl">{{ $stats['blocks'] ?? 0 }}</span>
            <span class="text-gray-500 ml-2">блоков</span>
        </div>
        <div class="bg-dark-100 px-6 py-3 rounded-xl border border-dark-400/50">
            <span class="text-accent-light font-bold text-xl">{{ $stats['tasks'] ?? 0 }}</span>
            <span class="text-gray-500 ml-2">заданий</span>
        </div>
    </div>

    {{-- Content --}}
    @yield('content')

    {{-- Info Box --}}
    <div class="bg-dark-100 rounded-xl p-6 border border-dark-400/50 mt-10">
        <h4 class="text-white font-semibold mb-4">Информация</h4>
        <div class="text-gray-400 text-sm space-y-2">
            <p><strong class="text-gray-300">Задание:</strong> № {{ ltrim($topicId, '0') }}. {{ $topicMeta['title'] }}</p>
            <p><strong class="text-gray-300">Экзамен:</strong> ЕГЭ профильный уровень</p>
            <p><strong class="text-gray-300">Источник данных:</strong> <code class="bg-dark-300 px-2 py-1 rounded text-xs text-gray-300">storage/app/tasks/ege/topic_{{ $topicId }}.json</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1 text-gray-500">
                <li>Блоков: {{ $stats['blocks'] ?? 0 }}</li>
                <li>Заданий: {{ $stats['zadaniya'] ?? 0 }}</li>
                <li>Всего задач: {{ $stats['tasks'] ?? 0 }}</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-gray-600 text-sm mt-8">Формулы отображаются с помощью KaTeX</p>
</div>

{{-- Инструмент для пометки заданий ЕГЭ --}}
@include('components.task-review-tool-ege', ['topicId' => $topicId])

{{-- Редактор геометрии (для заданий 1 - планиметрия, 3 - стереометрия) --}}
@if(in_array($topicId, ['01', '03']))
    @include('components.geometry-editor')
@endif

@stack('scripts')

</body>
</html>
