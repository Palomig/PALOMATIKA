<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Кастомный тест ОГЭ - PALOMATIKA</title>
    @include('partials.head-config')
    @include('partials.head-katex')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,500;7..72,700&display=swap" rel="stylesheet">
    <style>
        .exam-title { font-family: 'Literata', serif; }
        .katex { font-size: 1.02em; }
        .task-image-wrap img { max-width: 100%; max-height: 320px; object-fit: contain; }
        .task-svg-wrap svg { width: 100%; height: auto; max-height: 340px; }
        @media print {
            body { background: #fff !important; color: #000 !important; }
            .no-print { display: none !important; }
            .task-card { break-inside: avoid; border: 1px solid #ccc !important; background: #fff !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-dark-50 text-slate-200">
@php
    $shareUrl = isset($testHash) ? route('test.generator.show', ['hash' => $testHash]) : null;
@endphp

<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="no-print flex flex-wrap items-center justify-between gap-3 mb-6 rounded-xl border border-slate-800 bg-dark-light/40 px-4 py-3 text-sm">
        <a href="{{ route('test.generator') }}" class="text-slate-300 hover:text-white transition">← К генератору</a>
        <div class="flex items-center gap-2">
            @if($shareUrl)
                <button type="button" onclick="copyShareUrl()" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 transition">Копировать ссылку</button>
            @endif
            <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-emerald-700 text-white hover:bg-emerald-600 transition">Печать</button>
        </div>
    </div>

    <header class="mb-6">
        <h1 class="exam-title text-3xl sm:text-4xl text-white">Кастомный тест ОГЭ</h1>
        <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-400">
            <span>Заданий: <strong class="text-emerald-300">{{ count($testTasks) }}</strong></span>
            <span>Дата: {{ now()->format('d.m.Y H:i') }}</span>
            @if(isset($testHash))
                <span>Hash: <code class="bg-slate-800 px-2 py-0.5 rounded text-emerald-300">{{ $testHash }}</code></span>
            @endif
        </div>
    </header>

    @forelse($testTasks as $testTask)
        @php
            $task = $testTask['task'] ?? [];
            $resolvedImage = null;
            $inlineSvgFromImage = null;
            $svgType = $testTask['svg_type'] ?? ($task['svg_type'] ?? null);
            $points = $task['points'] ?? ($testTask['points'] ?? null);

            if (!empty($task['image'])) {
                $imageRaw = (string) $task['image'];

                if (\Illuminate\Support\Str::startsWith($imageRaw, '<svg')) {
                    $inlineSvgFromImage = $imageRaw;
                } else {
                    $image = ltrim($imageRaw, '/');
                    $candidates = [
                        "images/tasks/{$testTask['topic_id']}/{$image}",
                        "images/tasks/{$image}",
                        $image,
                    ];

                    foreach ($candidates as $candidate) {
                        if (file_exists(public_path($candidate))) {
                            $resolvedImage = asset($candidate);
                            break;
                        }
                    }
                }
            }
        @endphp

        <article class="task-card rounded-xl border border-slate-800 bg-dark-light/35 p-5 mb-4">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-emerald-700/70 text-white font-semibold flex items-center justify-center"
                     data-exam-number="{{ (int) ($testTask['topic_id'] ?? 0) }}">
                    {{ (int) ($testTask['topic_id'] ?? 0) }}
                </div>
                <div class="text-right text-xs text-slate-500">
                    <div class="inline-block px-2 py-1 rounded bg-slate-800 text-slate-300 mb-1">
                        {{ $testTask['topic_id'] }}. {{ \Illuminate\Support\Str::limit($testTask['topic_title'] ?? '', 36) }}
                    </div>
                    <div>Блок {{ $testTask['block_number'] }} · Задание {{ $testTask['zadanie_number'] }}</div>
                </div>
            </div>

            <p class="text-slate-100 mb-4">{{ $testTask['instruction'] }}</p>

            @if(!empty($task['svg']))
                <div class="task-svg-wrap rounded-lg border border-slate-700 bg-slate-900/40 p-3 mb-4 overflow-auto">{!! $task['svg'] !!}</div>
            @elseif($inlineSvgFromImage)
                <div class="task-svg-wrap rounded-lg border border-slate-700 bg-slate-900/40 p-3 mb-4 overflow-auto">{!! $inlineSvgFromImage !!}</div>
            @elseif($svgType)
                <div class="rounded-lg border border-slate-700 bg-slate-900/40 p-3 mb-4">
                    @include('tasks.partials.number-line', [
                        'points' => $points,
                        'svgType' => $svgType,
                        'task' => $task,
                    ])
                </div>
            @elseif($resolvedImage)
                <div class="task-image-wrap rounded-lg border border-slate-700 bg-slate-900/40 p-3 mb-4 text-center">
                    <img src="{{ $resolvedImage }}" alt="Иллюстрация задания" loading="lazy">
                </div>
            @endif

            @if(!empty($task['expression']))
                <div class="rounded-lg border border-slate-700 bg-slate-900/40 p-3 mb-4 latex-content text-center text-slate-100">
                    ${{ $task['expression'] }}$
                </div>
            @endif

            @if(!empty($task['text']))
                <div class="rounded-lg border border-slate-700 bg-slate-900/40 p-3 mb-4 text-slate-300 latex-content">{{ $task['text'] }}</div>
            @endif

            @if(!empty($task['options']))
                <div class="grid gap-2">
                    @foreach($task['options'] as $index => $option)
                        <div class="rounded-lg border border-slate-700 bg-slate-900/35 px-3 py-2 text-slate-300 latex-content">
                            {{ $index + 1 }}. {{ $option }}
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">
                    <label class="block text-sm text-slate-400 mb-2">Номер ответа:</label>
                    <input type="text" class="w-full sm:w-64 px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-100 placeholder-slate-500" placeholder="Введите номер ответа">
                </div>
            @else
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Ваш ответ:</label>
                    <input type="text" class="w-full sm:w-64 px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-100 placeholder-slate-500" placeholder="Введите ответ">
                </div>
            @endif
        </article>
    @empty
        <div class="rounded-xl border border-slate-800 bg-dark-light/35 p-6 text-center text-slate-400">
            Не удалось сгенерировать задания. Попробуйте выбрать другие темы.
        </div>
    @endforelse
</div>

<script>
const shareUrl = @json($shareUrl);

function copyShareUrl() {
    if (!shareUrl) return;
    navigator.clipboard.writeText(shareUrl);
}

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(() => {
        renderMathInElement(document.body, {
            delimiters: [
                { left: '$$', right: '$$', display: true },
                { left: '$', right: '$', display: false }
            ],
            throwOnError: false,
            trust: true
        });
    }, 100);
});
</script>
</body>
</html>
