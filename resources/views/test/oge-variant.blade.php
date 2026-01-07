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

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'PT Serif', Georgia, 'Times New Roman', serif;
            font-size: 17px;
            line-height: 1.6;
            padding: 40px 60px;
            max-width: 900px;
            margin: 0 auto;
            background: #fefefe;
            color: #1a1a1a;
        }

        /* Header like OGE paper */
        .oge-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }

        .oge-header-line {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #555;
            margin-bottom: 15px;
        }

        .oge-title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .oge-subtitle {
            font-size: 16px;
            color: #444;
        }

        /* Actions bar (hidden on print) */
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }

        .actions-bar a,
        .actions-bar button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #ff6b6b;
            color: #fff;
        }

        .btn-primary:hover {
            background: #ff5252;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        /* Instructions block */
        .instructions {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 30px;
            font-size: 15px;
            font-style: italic;
            line-height: 1.7;
            border-radius: 4px;
        }

        /* Part header */
        .part-header {
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            margin: 30px 0 25px;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 4px;
        }

        /* Task block */
        .task-block {
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 1px solid #e0e0e0;
            page-break-inside: avoid;
        }

        .task-block:last-child {
            border-bottom: none;
        }

        .task-number-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: 2px solid #333;
            font-weight: 700;
            font-size: 16px;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .task-header-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .task-text {
            flex: 1;
        }

        /* Task content */
        .task-content {
            padding-left: 48px;
        }

        .task-expression {
            font-size: 20px;
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            text-align: center;
        }

        .task-image {
            margin: 15px 0;
            text-align: center;
        }

        .task-image img,
        .task-image svg {
            max-width: 100%;
            max-height: 280px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background: #fff;
        }

        /* Options */
        .options-list {
            list-style: none;
            margin-top: 15px;
        }

        .options-list.horizontal {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .options-list.vertical li {
            margin-bottom: 10px;
        }

        .option-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .option-item:hover {
            background: #f0f0f0;
        }

        .option-item.selected {
            background: rgba(255, 107, 107, 0.15);
        }

        .option-item input {
            display: none;
        }

        .option-num {
            font-weight: 600;
            min-width: 20px;
        }

        /* Answer line */
        .answer-line {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding-left: 48px;
        }

        .answer-label {
            font-weight: 600;
        }

        .answer-input {
            width: 200px;
            padding: 10px 15px;
            font-size: 18px;
            border: 2px solid #333;
            border-radius: 4px;
            font-family: inherit;
        }

        .answer-input:focus {
            outline: none;
            border-color: #ff6b6b;
        }

        .answer-boxes {
            display: flex;
            gap: 2px;
        }

        .answer-box {
            width: 30px;
            height: 36px;
            border: 1px solid #333;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            font-family: inherit;
        }

        /* Topic badge */
        .topic-badge {
            display: inline-block;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            padding: 2px 8px;
            background: #e8e8e8;
            border-radius: 4px;
            color: #666;
            margin-left: 10px;
        }

        /* SVG containers */
        .svg-container {
            display: flex;
            justify-content: center;
            margin: 15px 0;
        }

        .svg-container svg {
            max-width: 100%;
            height: auto;
        }

        /* Matching table */
        .matching-table {
            margin: 15px 0;
            border-collapse: collapse;
        }

        .matching-table th,
        .matching-table td {
            border: 1px solid #333;
            padding: 8px 15px;
            text-align: center;
        }

        .matching-table th {
            background: #f0f0f0;
            font-weight: 600;
        }

        .matching-table input {
            width: 40px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        /* Graphs row */
        .graphs-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
        }

        .graph-item {
            text-align: center;
        }

        .graph-item img,
        .graph-item svg {
            max-width: 180px;
            max-height: 150px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .graph-label {
            font-weight: 600;
            margin-top: 5px;
        }

        /* Page number */
        .page-number {
            text-align: center;
            margin-top: 40px;
            color: #888;
            font-size: 14px;
        }

        /* KaTeX */
        .katex {
            font-size: 1.1em !important;
        }

        /* Print styles */
        @media print {
            body {
                padding: 15px;
                font-size: 15px;
            }

            .actions-bar {
                display: none !important;
            }

            .task-block {
                break-inside: avoid;
            }

            .oge-header {
                margin-bottom: 20px;
            }

            .answer-input,
            .answer-box {
                border: 1px solid #000;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 20px;
                font-size: 15px;
            }

            .oge-header-line {
                flex-direction: column;
                gap: 5px;
            }

            .task-content {
                padding-left: 0;
            }

            .answer-line {
                padding-left: 0;
                flex-wrap: wrap;
            }

            .options-list.horizontal {
                flex-direction: column;
                gap: 10px;
            }

            .actions-bar {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>

<div class="actions-bar">
    <div>
        <a href="{{ route('test.oge.generator') }}" class="btn-secondary">← Новый вариант</a>
        <a href="{{ route('test.generator') }}" class="btn-secondary">🎲 Генератор</a>
    </div>
    <div>
        <button onclick="window.print()" class="btn-secondary">🖨️ Печать</button>
    </div>
</div>

<div class="oge-header">
    <div class="oge-header-line">
        <span>ОГЭ–2025</span>
        <span>Тренировочная работа</span>
        <span>palomatika.ru</span>
    </div>
    <h1 class="oge-title">Тренировочная работа № {{ $variantNumber ?? rand(1, 99) }}</h1>
    <p class="oge-subtitle">Задания 6–19 (Часть 1)</p>
</div>

<div class="instructions">
    <strong>Инструкция:</strong> Ответами к заданиям 6–19 являются число или последовательность цифр.
    Запишите ответ в поле ответа. Если ответом является последовательность цифр, то запишите её без пробелов, запятых и других дополнительных символов.
</div>

@foreach($tasks as $index => $taskData)
    @php
        $taskNumber = 6 + $index;
        $task = $taskData['task'] ?? [];
        $topicTitle = $taskData['topic_title'] ?? '';
    @endphp

    <div class="task-block">
        <div class="task-header-row">
            <div class="task-number-box">{{ $taskNumber }}</div>
            <div class="task-text">
                {{ $taskData['instruction'] ?? 'Найдите значение выражения.' }}
                @if($topicTitle)
                    <span class="topic-badge">{{ $topicTitle }}</span>
                @endif
            </div>
        </div>

        <div class="task-content">
            {{-- Expression --}}
            @if(!empty($task['expression']))
                <div class="task-expression">
                    ${{ $task['expression'] }}$
                </div>
            @endif

            {{-- SVG Image --}}
            @if(!empty($task['svg']))
                <div class="svg-container">
                    {!! $task['svg'] !!}
                </div>
            @endif

            {{-- Regular Image --}}
            @if(!empty($task['image']))
                <div class="task-image">
                    <img src="/images/tasks/{{ $taskData['topic_id'] ?? '' }}/{{ $task['image'] }}" alt="Задание {{ $taskNumber }}">
                </div>
            @endif

            {{-- Options for choice questions --}}
            @if(!empty($task['options']))
                <ul class="options-list {{ count($task['options']) <= 4 && strlen(implode('', $task['options'])) < 100 ? 'horizontal' : 'vertical' }}">
                    @foreach($task['options'] as $optIndex => $option)
                        <li class="option-item" onclick="selectOption(this, {{ $taskNumber }})">
                            <input type="radio" name="answer_{{ $taskNumber }}" value="{{ $optIndex + 1 }}">
                            <span class="option-num">{{ $optIndex + 1 }})</span>
                            <span class="option-text">{{ $option }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Matching table for correspondence tasks --}}
            @if(!empty($task['matching']))
                <table class="matching-table">
                    <tr>
                        @foreach($task['matching']['headers'] ?? ['А', 'Б', 'В'] as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($task['matching']['headers'] ?? ['А', 'Б', 'В'] as $i => $header)
                            <td><input type="text" maxlength="1" name="match_{{ $taskNumber }}_{{ $i }}"></td>
                        @endforeach
                    </tr>
                </table>
            @endif

            {{-- Graphs for function matching --}}
            @if(!empty($task['graphs']))
                <div class="graphs-row">
                    @foreach($task['graphs'] as $graphIndex => $graph)
                        <div class="graph-item">
                            @if(is_string($graph) && str_starts_with($graph, '<svg'))
                                {!! $graph !!}
                            @elseif(is_string($graph))
                                <img src="{{ $graph }}" alt="График {{ $graphIndex + 1 }}">
                            @endif
                            <div class="graph-label">{{ $graphIndex + 1 }})</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Answer line --}}
        <div class="answer-line">
            <span class="answer-label">Ответ:</span>
            @if(!empty($task['options']))
                {{-- For multiple choice, just show selected number --}}
                <input type="text" class="answer-input" style="width: 80px;" name="final_answer_{{ $taskNumber }}" placeholder="">
            @elseif(!empty($task['matching']))
                {{-- For matching, show boxes for sequence --}}
                <div class="answer-boxes">
                    @for($i = 0; $i < count($task['matching']['headers'] ?? ['А', 'Б', 'В']); $i++)
                        <input type="text" class="answer-box" maxlength="1">
                    @endfor
                </div>
            @else
                {{-- Default text input --}}
                <input type="text" class="answer-input" name="final_answer_{{ $taskNumber }}" placeholder="">
            @endif
        </div>
    </div>
@endforeach

<div class="page-number">
    Сгенерировано: {{ now()->format('d.m.Y H:i') }}
</div>

<script>
    function selectOption(element, taskNumber) {
        // Deselect all options in this task
        const taskBlock = element.closest('.task-block');
        taskBlock.querySelectorAll('.option-item').forEach(item => {
            item.classList.remove('selected');
        });

        // Select this option
        element.classList.add('selected');
        const radio = element.querySelector('input[type="radio"]');
        radio.checked = true;

        // Update answer field
        const answerInput = taskBlock.querySelector('input[name="final_answer_' + taskNumber + '"]');
        if (answerInput) {
            answerInput.value = radio.value;
        }
    }
</script>

</body>
</html>
