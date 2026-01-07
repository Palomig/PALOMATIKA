<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ОГЭ-2025 Вариант {{ $variantNumber ?? 1 }} - PALOMATIKA</title>

    <!-- KaTeX for math rendering -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathInElement(document.body, {delimiters: [{left: '$$', right: '$$', display: true}, {left: '$', right: '$', display: false}]});"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
        .number-line { font-family: 'Times New Roman', serif; }
        .geo-label {
            font-family: 'Times New Roman', serif;
            font-style: italic;
            font-weight: 500;
            user-select: none;
        }

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
            .bg-slate-900, .bg-slate-800, .bg-slate-900\/50 {
                background: #f5f5f5 !important;
            }
            .text-white, .text-slate-200, .text-slate-300 {
                color: black !important;
            }
            .text-blue-400, .text-cyan-400, .text-emerald-400, .text-amber-400 {
                color: #1e40af !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

<div class="max-w-5xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="no-print flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.oge.generator') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← Новый вариант</a>
        <div class="flex gap-3">
            <a href="{{ route('test.generator') }}" class="px-3 py-1.5 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition">Генератор</a>
            <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 transition">🖨️ Печать</button>
        </div>
    </div>

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="flex justify-between items-center text-sm text-slate-500 mb-4">
            <span>ОГЭ–2025</span>
            <span>palomatika.ru</span>
        </div>
        <h1 class="text-4xl font-bold text-white mb-2">Тренировочная работа № {{ $variantNumber ?? rand(1, 99) }}</h1>
        <p class="text-slate-400 text-lg">Задания 6–19 (Часть 1)</p>
    </div>

    {{-- Instructions --}}
    <div class="bg-slate-800/70 rounded-xl p-5 mb-8 border border-slate-700">
        <p class="text-slate-300 text-sm italic leading-relaxed">
            <strong class="text-white">Инструкция:</strong> Ответами к заданиям 6–19 являются число или последовательность цифр.
            Запишите ответ в поле ответа. Если ответом является последовательность цифр, то запишите её без пробелов, запятых и других дополнительных символов.
        </p>
    </div>

    {{-- Stats --}}
    <div class="no-print flex justify-center gap-6 mb-10">
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-emerald-400 font-bold text-xl">{{ count($tasks) }}</span>
            <span class="text-slate-400 ml-2">заданий</span>
        </div>
        <div class="bg-slate-800 px-6 py-3 rounded-xl border border-slate-700">
            <span class="text-emerald-400 font-bold text-xl">{{ now()->format('d.m.Y') }}</span>
            <span class="text-slate-400 ml-2">дата</span>
        </div>
    </div>

    {{-- Tasks --}}
    @foreach($tasks as $index => $taskData)
        @php
            $taskNumber = 6 + $index;
            $task = $taskData['task'] ?? [];
            $topicTitle = $taskData['topic_title'] ?? '';
            $type = $taskData['type'] ?? 'expression';
            $instruction = $taskData['instruction'] ?? 'Найдите значение выражения.';

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
            $accent = $accentColors[$taskData['topic_id'] ?? '06'] ?? 'blue';
        @endphp

        <div class="task-card mb-8 bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
            {{-- Task Header --}}
            <div class="bg-slate-800 p-4 border-b border-slate-700 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-{{ $accent }}-500 to-{{ $accent }}-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">
                    {{ $taskNumber }}
                </div>
                <div class="flex-1">
                    <div class="text-white font-medium">{{ $instruction }}</div>
                    <div class="text-slate-500 text-sm mt-1">{{ $topicTitle }}</div>
                </div>
            </div>

            {{-- Task Content --}}
            <div class="p-5">
                {{-- Expression (LaTeX) --}}
                @if(!empty($task['expression']))
                    <div class="bg-slate-900/50 rounded-xl p-5 mb-4 text-center">
                        <span class="text-slate-200 text-xl">${{ $task['expression'] }}$</span>
                    </div>
                @endif

                {{-- Text description --}}
                @if(!empty($task['text']))
                    <div class="text-slate-300 mb-4 leading-relaxed">{{ $task['text'] }}</div>
                @endif

                {{-- SVG for number line (topic 07) --}}
                @if(($taskData['topic_id'] ?? '') === '07' && isset($task['point_value']))
                    @php
                        $pointVal = $task['point_value'];
                        $pointLabel = $task['point_label'] ?? 'a';
                        $maxTick = ceil($pointVal) + 2;
                        $tickWidth = 280 / $maxTick;
                        $pointX = 15 + ($pointVal / $maxTick) * 280;
                    @endphp
                    <div class="bg-slate-900/50 rounded-xl p-4 mb-4">
                        <svg viewBox="0 0 320 55" class="w-full h-16 number-line">
                            <defs>
                                <marker id="arrowR-{{ $taskNumber }}" markerWidth="8" markerHeight="8" refX="0" refY="3" orient="auto">
                                    <path d="M0,0 L0,6 L8,3 z" fill="#8B0000"/>
                                </marker>
                            </defs>
                            <line x1="15" y1="25" x2="305" y2="25" stroke="#8B0000" stroke-width="2" marker-end="url(#arrowR-{{ $taskNumber }})"/>
                            @for($i = 0; $i <= $maxTick; $i++)
                                <line x1="{{ 15 + $i * $tickWidth }}" y1="18" x2="{{ 15 + $i * $tickWidth }}" y2="32" stroke="#8B0000" stroke-width="1.5"/>
                            @endfor
                            <text x="15" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">0</text>
                            <text x="{{ 15 + $tickWidth }}" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">1</text>
                            <circle cx="{{ $pointX }}" cy="25" r="6" fill="#22c55e"/>
                            <text x="{{ $pointX }}" y="12" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold">{{ $pointLabel }}</text>
                        </svg>
                    </div>
                @endif

                {{-- SVG for two points (topic 07) --}}
                @if(($taskData['topic_id'] ?? '') === '07' && isset($task['points']) && is_array($task['points']))
                    @php
                        $pts = $task['points'];
                        $values = array_column($pts, 'value');
                        $minVal = min(min($values), 0);
                        $maxVal = max($values);
                        $minTick = floor($minVal) - 1;
                        $maxTick = ceil($maxVal) + 1;
                        $range = $maxTick - $minTick;
                        $tickWidth = 280 / $range;
                    @endphp
                    <div class="bg-slate-900/50 rounded-xl p-4 mb-4">
                        <svg viewBox="0 0 320 55" class="w-full h-16 number-line">
                            <defs>
                                <marker id="arrowR2-{{ $taskNumber }}" markerWidth="8" markerHeight="8" refX="0" refY="3" orient="auto">
                                    <path d="M0,0 L0,6 L8,3 z" fill="#8B0000"/>
                                </marker>
                            </defs>
                            <line x1="15" y1="25" x2="305" y2="25" stroke="#8B0000" stroke-width="2" marker-end="url(#arrowR2-{{ $taskNumber }})"/>
                            @for($i = $minTick; $i <= $maxTick; $i++)
                                <line x1="{{ 15 + ($i - $minTick) * $tickWidth }}" y1="18" x2="{{ 15 + ($i - $minTick) * $tickWidth }}" y2="32" stroke="#8B0000" stroke-width="1.5"/>
                            @endfor
                            <text x="{{ 15 + (0 - $minTick) * $tickWidth }}" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">0</text>
                            @foreach($pts as $pt)
                                @php $px = 15 + ($pt['value'] - $minTick) * $tickWidth; @endphp
                                <circle cx="{{ $px }}" cy="25" r="6" fill="#22c55e"/>
                                <text x="{{ $px }}" y="12" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold">{{ $pt['label'] }}</text>
                            @endforeach
                        </svg>
                    </div>
                @endif

                {{-- SVG from task data --}}
                @if(!empty($task['svg']))
                    <div class="bg-slate-900/50 rounded-xl p-4 mb-4 flex justify-center">
                        {!! $task['svg'] !!}
                    </div>
                @endif

                {{-- Image --}}
                @if(!empty($task['image']))
                    <div class="bg-slate-900/50 rounded-xl p-4 mb-4 flex justify-center">
                        <img src="/images/tasks/{{ $taskData['topic_id'] ?? '' }}/{{ $task['image'] }}" alt="Задание {{ $taskNumber }}" class="max-h-48 rounded">
                    </div>
                @endif

                {{-- Options for choice questions --}}
                @if(!empty($task['options']))
                    <div class="grid {{ count($task['options']) <= 4 ? 'grid-cols-2 md:grid-cols-4' : 'grid-cols-1 md:grid-cols-2' }} gap-3 mt-4">
                        @foreach($task['options'] as $optIndex => $option)
                            <div class="bg-slate-700/50 hover:bg-slate-700 rounded-lg px-4 py-3 cursor-pointer transition-colors border border-slate-600 hover:border-slate-500"
                                 onclick="selectOption(this, {{ $taskNumber }}, {{ $optIndex + 1 }})">
                                <span class="text-{{ $accent }}-400 font-semibold">{{ $optIndex + 1 }})</span>
                                <span class="text-slate-300 ml-2">{{ $option }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Matching table (for topic 11 - graphs) --}}
                @if($type === 'matching' && !empty($task['headers']))
                    <div class="overflow-x-auto mt-4">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    @foreach($task['headers'] as $header)
                                        <th class="border border-slate-600 bg-slate-700 px-4 py-2 text-slate-200">{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    @foreach($task['headers'] as $i => $header)
                                        <td class="border border-slate-600 px-4 py-2 text-center">
                                            <input type="text" maxlength="1" class="w-10 h-10 text-center bg-slate-800 border border-slate-500 rounded text-white text-lg focus:border-{{ $accent }}-400 focus:outline-none">
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Graphs for function matching (topic 11) --}}
                @if(!empty($task['graphs']))
                    <div class="grid grid-cols-3 gap-4 mt-4">
                        @foreach($task['graphs'] as $gIndex => $graph)
                            <div class="bg-slate-900/50 rounded-lg p-3 text-center">
                                @if(is_string($graph) && str_starts_with($graph, '<svg'))
                                    {!! $graph !!}
                                @elseif(is_string($graph))
                                    <img src="{{ $graph }}" alt="График {{ $gIndex + 1 }}" class="max-h-32 mx-auto">
                                @endif
                                <div class="text-slate-400 mt-2 font-semibold">{{ $gIndex + 1 }})</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Statements for topic 19 --}}
                @if(($taskData['topic_id'] ?? '') === '19' && !empty($task['statements']))
                    <div class="space-y-3 mt-4">
                        @foreach($task['statements'] as $stIndex => $statement)
                            <div class="bg-slate-700/50 rounded-lg px-4 py-3 border border-slate-600">
                                <span class="text-red-400 font-semibold">{{ $stIndex + 1 }})</span>
                                <span class="text-slate-300 ml-2">{{ is_array($statement) ? ($statement['text'] ?? '') : $statement }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Answer field --}}
                <div class="mt-6 flex items-center gap-4">
                    <span class="text-slate-400 font-medium">Ответ:</span>
                    <input type="text"
                           id="answer-{{ $taskNumber }}"
                           class="w-40 px-4 py-2 bg-slate-900 border-2 border-slate-600 rounded-lg text-white text-lg focus:border-{{ $accent }}-400 focus:outline-none transition-colors"
                           placeholder="">
                </div>
            </div>
        </div>
    @endforeach

    {{-- Footer --}}
    <div class="no-print text-center mt-10">
        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
            <p class="text-slate-400 mb-4">Вариант сгенерирован {{ now()->format('d.m.Y H:i') }}</p>
            <div class="flex justify-center gap-4">
                <button onclick="window.print()" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">
                    🖨️ Распечатать
                </button>
                <a href="{{ route('test.oge.generator') }}" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-white rounded-lg transition-colors">
                    🎲 Новый вариант
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function selectOption(element, taskNumber, value) {
        // Deselect all options in this task
        const taskCard = element.closest('.task-card');
        taskCard.querySelectorAll('.bg-slate-700\\/50').forEach(opt => {
            opt.classList.remove('ring-2', 'ring-emerald-400');
        });

        // Select this option
        element.classList.add('ring-2', 'ring-emerald-400');

        // Update answer field
        const answerInput = document.getElementById('answer-' + taskNumber);
        if (answerInput) {
            answerInput.value = value;
        }
    }
</script>

</body>
</html>
