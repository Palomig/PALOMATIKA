<!DOCTYPE html>
<html lang="ru">
<head>
    <title>{{ $topicId }}. {{ $topicMeta['title'] }} - PALOMATIKA</title>
    @include('partials.head-config')
    @include('partials.head-katex')
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&display=swap" rel="stylesheet">
    @push('styles')
    <style>
        .math-serif { font-family: 'PT Serif', Georgia, serif; }
        .number-line { font-family: 'Times New Roman', serif; }
        .geo-label { font-family: 'PT Serif', serif; font-style: italic; }
        .katex { font-size: 1.1em; }
    </style>
    @endpush
</head>
<body class="min-h-screen bg-gradient-to-br from-dark-50 via-dark to-dark-50">

<div class="max-w-6xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-dark-light/50 rounded-xl p-4 border border-gray-800">
        <a href="{{ route('topics.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Назад к темам
        </a>

        <div class="flex gap-1.5 flex-wrap justify-center">
            @foreach(['06','07','08','09','10','11','12','13','14','15','16','17','18','19'] as $tid)
                @if($tid === $topicId)
                    <span class="px-2.5 py-1 rounded-lg bg-{{ $topicMeta['color'] ?? 'blue' }}-500 text-white font-bold text-xs">{{ $tid }}</span>
                @else
                    <a href="{{ route('topics.show', ['id' => ltrim($tid, '0')]) }}"
                       class="px-2.5 py-1 rounded-lg bg-gray-800 text-gray-400 hover:bg-dark-500 transition text-xs">{{ $tid }}</a>
                @endif
            @endforeach
        </div>

        <span class="text-gray-500 text-xs">{{ $stats['tasks'] ?? 0 }} заданий</span>
    </div>

    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">{{ $topicId }}. {{ $topicMeta['title'] }}</h1>
        <p class="text-gray-400 text-lg">{{ $topicMeta['description'] }}</p>
    </div>

    {{-- Stats --}}
    <div class="flex justify-center gap-6 mb-10">
        <div class="bg-dark-light px-6 py-3 rounded-xl border border-gray-800">
            <span class="text-{{ $topicMeta['color'] ?? 'blue' }}-400 font-bold text-xl">{{ $stats['blocks'] ?? 0 }}</span>
            <span class="text-gray-400 ml-2">блоков</span>
        </div>
        <div class="bg-dark-light px-6 py-3 rounded-xl border border-gray-800">
            <span class="text-{{ $topicMeta['color'] ?? 'blue' }}-400 font-bold text-xl">{{ $stats['tasks'] ?? 0 }}</span>
            <span class="text-gray-400 ml-2">заданий</span>
        </div>
    </div>

    {{-- Content --}}
    @yield('content')

    {{-- Info Box --}}
    <div class="bg-dark-light rounded-xl p-6 border border-gray-800 mt-10">
        <h4 class="text-white font-semibold mb-4">Информация</h4>
        <div class="text-gray-400 text-sm space-y-2">
            <p><strong class="text-gray-400">Тема:</strong> {{ $topicId }}. {{ $topicMeta['title'] }}</p>
            <p><strong class="text-gray-400">Источник данных:</strong> <code class="bg-gray-800 px-2 py-1 rounded text-xs">storage/app/tasks/topic_{{ $topicId }}.json</code></p>
            <ul class="list-disc list-inside mt-3 space-y-1">
                <li>Блоков: {{ $stats['blocks'] ?? 0 }}</li>
                <li>Заданий: {{ $stats['zadaniya'] ?? 0 }}</li>
                <li>Всего задач: {{ $stats['tasks'] ?? 0 }}</li>
            </ul>
        </div>
    </div>

    <p class="text-center text-gray-500 text-sm mt-8">Формулы отображаются с помощью KaTeX</p>
</div>

{{-- Инструмент для пометки заданий --}}
@include('components.task-review-tool', ['topicId' => $topicId])

{{-- Редактор геометрии (только для тем 15-18) --}}
@if(in_array($topicId, ['15', '16', '17', '18']))
    @include('components.geometry-editor')
@endif

@stack('scripts')
@include('layouts.partials.palette-switcher')

</body>
</html>
