<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Вариант ОГЭ №{{ $variantNumber ?? 1 }} - PALOMATIKA</title>
    @include('partials.head-config')
    @include('partials.head-katex')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,500;7..72,700&display=swap" rel="stylesheet">

    <style>
        .exam-title { font-family: 'Literata', serif; }
        .number-line { font-family: 'Times New Roman', serif; }
        .geo-label {
            font-family: 'Times New Roman', serif;
            font-style: italic;
            font-weight: 500;
            user-select: none;
            pointer-events: none;
        }
        .geo-label-bold {
            font-family: 'Times New Roman', serif;
            font-style: normal;
            font-weight: 700;
            user-select: none;
            pointer-events: none;
        }
        .katex { font-size: 1.02em; }

        /* Print styles */
        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            .no-print {
                display: none !important;
            }
            .task-card {
                break-inside: avoid;
                border: 1px solid #ccc !important;
                background: white !important;
            }
            .bg-dark-50, .bg-dark-light {
                background: #f5f5f5 !important;
            }
            .text-white, .text-slate-200, .text-slate-300 {
                color: black !important;
            }
            .text-emerald-400, .text-emerald-300, .text-slate-400 {
                color: #1e40af !important;
            }
        }
    </style>
</head>
@php
    $isLegacyRoute = request()->routeIs('test.*');
    $generatorRoute = $isLegacyRoute ? route('test.oge.generator') : route('oge.generator');
    $variantRouteName = $isLegacyRoute ? 'test.oge.show' : 'oge.show';
    $newHash = substr(base_convert(mt_rand(), 10, 36), 0, 6);
    $footerHash = substr(base_convert(mt_rand(), 10, 36), 0, 6);
@endphp
<body class="min-h-screen bg-dark-50 text-slate-200">

<div class="max-w-5xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="no-print flex flex-wrap justify-between items-center mb-8 text-sm bg-dark-light/40 rounded-xl p-4 border border-slate-800 gap-3">
        <a href="{{ $generatorRoute }}" class="text-slate-300 hover:text-white transition">← К генератору</a>
        <div class="flex gap-2">
            <a href="{{ route($variantRouteName, ['hash' => $newHash]) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 transition">Новый вариант</a>
            <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-emerald-700 text-white hover:bg-emerald-600 transition">Печать</button>
        </div>
    </div>

    {{-- Header --}}
    <header class="mb-8">
        <div class="flex justify-between items-center text-sm text-slate-500 mb-3">
            <span>ОГЭ. Тренировочный вариант</span>
            <span>palomatika.ru</span>
        </div>
        <h1 class="exam-title text-3xl sm:text-4xl text-white mb-2">Вариант № {{ $variantNumber ?? rand(1, 99) }}</h1>
        <p class="text-slate-400">Задания 6–19</p>
    </header>

    {{-- Instructions --}}
    <div class="bg-dark-light/40 rounded-xl p-5 mb-8 border border-slate-800">
        <p class="text-slate-300 text-sm leading-relaxed">
            <strong class="text-white">Инструкция:</strong> Ответами к заданиям 6–19 являются число или последовательность цифр.
            Запишите ответ в поле ответа. Если ответом является последовательность цифр, то запишите её без пробелов, запятых и других дополнительных символов.
        </p>
    </div>

    {{-- Stats --}}
    <div class="no-print flex justify-center gap-4 mb-10">
        <div class="bg-dark-light/40 px-5 py-2.5 rounded-lg border border-slate-800">
            <span class="text-emerald-300 font-semibold text-lg">{{ count($tasks) }}</span>
            <span class="text-slate-400 ml-2 text-sm">заданий</span>
        </div>
        <div class="bg-dark-light/40 px-5 py-2.5 rounded-lg border border-slate-800">
            <span class="text-emerald-300 font-semibold text-lg">{{ now()->format('d.m.Y') }}</span>
            <span class="text-slate-400 ml-2 text-sm">дата</span>
        </div>
    </div>

    {{-- Tasks - используем унифицированный адаптер --}}
    @foreach($tasks as $index => $taskData)
        @php
            $taskNumber = 6 + $index;
            $topicId = $taskData['topic_id'] ?? '';

            // Определяем цвет акцента для разных тем
            $accentColors = [
                '06' => 'blue',
                '07' => 'cyan',
                '08' => 'violet',
                '09' => 'pink',
                '10' => 'orange',
                '11' => 'rose',
                '12' => 'lime',
                '13' => 'teal',
                '14' => 'indigo',
                '15' => 'emerald',
                '16' => 'amber',
                '17' => 'fuchsia',
                '18' => 'sky',
                '19' => 'red',
            ];
            $color = $accentColors[$topicId] ?? 'blue';
        @endphp

        @include('tasks.variant-task', [
            'taskData' => $taskData,
            'taskNumber' => $taskNumber,
            'color' => $color,
        ])
    @endforeach

    {{-- Footer --}}
    <div class="no-print text-center mt-10">
        <div class="bg-dark-light/40 rounded-xl p-6 border border-slate-800">
            <p class="text-slate-400 mb-2">Вариант: <code class="bg-slate-800 px-2 py-1 rounded text-emerald-300">{{ $variantHash ?? 'unknown' }}</code></p>
            <p class="text-slate-500 text-sm mb-4">Ссылка на этот вариант сохраняется — можно поделиться</p>
            <div class="flex justify-center gap-4">
                <button onclick="window.print()" class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-lg transition-colors">
                    Распечатать
                </button>
                <a href="{{ route($variantRouteName, ['hash' => $footerHash]) }}" class="px-6 py-3 bg-emerald-700 hover:bg-emerald-600 text-white rounded-lg transition-colors">
                    Новый вариант
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
