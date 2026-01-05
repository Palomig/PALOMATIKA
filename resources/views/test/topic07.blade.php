<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>07. Числа, координатная прямая - Тест парсинга PDF</title>

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
            font-size: 18px;
            line-height: 1.6;
            padding: 40px 60px;
            max-width: 1000px;
            margin: 0 auto;
            background: #fefefe;
            color: #1a1a1a;
        }

        .page {
            margin-bottom: 60px;
            padding-bottom: 40px;
            border-bottom: 2px solid #e0e0e0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
            font-style: italic;
        }

        .title {
            text-align: center;
            font-weight: 700;
            font-size: 24px;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .subtitle {
            text-align: center;
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 30px;
            color: #34495e;
        }

        .zadanie {
            margin-bottom: 35px;
        }

        .zadanie-header {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .zadanie-header span {
            font-weight: 400;
        }

        /* Task with numbered variants */
        .task-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .task-number {
            min-width: 35px;
            font-weight: 600;
            color: #555;
            flex-shrink: 0;
        }

        .task-content {
            flex: 1;
        }

        .task-expression {
            font-weight: 500;
            margin-bottom: 6px;
        }

        .task-options {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .option {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .option-num {
            color: #666;
            font-size: 16px;
        }

        /* Simple choice (single question with options) */
        .simple-choice {
            margin: 15px 0;
        }

        .simple-choice .options {
            display: flex;
            gap: 25px;
            margin-top: 10px;
        }

        /* Grid layout for multi-task */
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .tasks-grid.cols-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        /* Between fractions */
        .between-task {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        /* Segment choice */
        .segment-task {
            margin-bottom: 12px;
        }

        .segment-label {
            font-weight: 500;
            margin-right: 10px;
        }

        /* Decimal choice */
        .decimal-task {
            margin-bottom: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 6px;
        }

        .decimal-numbers {
            font-size: 16px;
            color: #666;
            margin-bottom: 8px;
        }

        .decimal-target {
            font-weight: 500;
        }

        /* Count integers */
        .count-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .count-item {
            padding: 5px;
        }

        /* KaTeX font size */
        .katex {
            font-size: 1.1em !important;
        }

        /* Info box */
        .info-box {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 14px;
        }

        .info-box h4 {
            color: #495057;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .info-box p {
            margin-bottom: 8px;
            color: #6c757d;
        }

        .info-box code {
            background: #e9ecef;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 13px;
            color: #495057;
        }

        .info-box ul {
            margin-left: 20px;
            margin-top: 8px;
            color: #6c757d;
        }

        /* Responsive */
        @media (max-width: 900px) {
            body {
                padding: 20px;
                font-size: 16px;
            }
            .tasks-grid {
                grid-template-columns: 1fr;
            }
            .count-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

@foreach($blocks as $block)
<div class="page">
    <!-- Page Header -->
    <div class="header">
        <span>Е. А. Ширяева</span>
        <span>Задачник ОГЭ 2026 (тренажер)</span>
    </div>

    <!-- Title -->
    <div class="title">07. Числа, координатная прямая</div>
    <div class="subtitle">Блок {{ $block['number'] }}. {{ $block['title'] }}</div>

    @foreach($block['zadaniya'] as $zadanie)
        <div class="zadanie">
            <p class="zadanie-header">Задание {{ $zadanie['number'] }}. <span>{{ $zadanie['instruction'] }}</span></p>

            @if($zadanie['type'] === 'simple_choice')
                {{-- Simple choice with options --}}
                <div class="simple-choice">
                    <div class="options">
                        @foreach($zadanie['options'] as $i => $option)
                            <span class="option">
                                <span class="option-num">{{ $i + 1 }})</span>
                                <span>{{ $option }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>

            @elseif($zadanie['type'] === 'choice' || $zadanie['type'] === 'comparison' || $zadanie['type'] === 'power_choice')
                {{-- Multiple tasks with options --}}
                @foreach($zadanie['tasks'] as $task)
                    <div class="task-row">
                        <span class="task-number">{{ $task['id'] }}</span>
                        <div class="task-options">
                            @foreach($task['options'] as $i => $option)
                                <span class="option">
                                    <span class="option-num">{{ $i + 1 }})</span>
                                    <span>{{ $option }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach

            @elseif($zadanie['type'] === 'fraction_choice' || $zadanie['type'] === 'sqrt_choice' || $zadanie['type'] === 'fraction_point')
                {{-- Fraction/sqrt with point options --}}
                @foreach($zadanie['tasks'] as $task)
                    <div class="task-row">
                        <span class="task-number">{{ $task['id'] }}</span>
                        <div class="task-content">
                            <span class="task-expression">${{ $task['expression'] }}$</span>
                            <div class="task-options">
                                @foreach($task['options'] as $i => $option)
                                    <span class="option">
                                        <span class="option-num">{{ $i + 1 }})</span>
                                        <span>{{ $option }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

            @elseif($zadanie['type'] === 'interval_choice' || $zadanie['type'] === 'sqrt_interval' || $zadanie['type'] === 'negative_interval')
                {{-- Interval choices --}}
                <div class="tasks-grid">
                    @foreach($zadanie['tasks'] as $task)
                        <div class="task-row">
                            <span class="task-number">{{ $task['id'] }}</span>
                            <div class="task-content">
                                <span class="task-expression">${{ $task['expression'] }}$</span>
                                <div class="task-options">
                                    @foreach($task['options'] as $i => $option)
                                        <span class="option">
                                            <span class="option-num">{{ $i + 1 }})</span>
                                            <span>{{ $option }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            @elseif($zadanie['type'] === 'between_fractions')
                {{-- Between two fractions --}}
                @foreach($zadanie['tasks'] as $task)
                    <div class="between-task">
                        <span class="task-number">{{ $task['id'] }}</span>
                        <span>${{ $task['left'] }}$ и ${{ $task['right'] }}$?</span>
                        <div class="task-options">
                            @foreach($task['options'] as $i => $option)
                                <span class="option">
                                    <span class="option-num">{{ $i + 1 }})</span>
                                    <span>{{ $option }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach

            @elseif($zadanie['type'] === 'segment_choice' || $zadanie['type'] === 'negative_segment' || $zadanie['type'] === 'sqrt_segment')
                {{-- Segment choice --}}
                @foreach($zadanie['tasks'] as $task)
                    <div class="segment-task">
                        <span class="task-number">{{ $task['id'] }}</span>
                        <span class="segment-label">{{ $task['segment'] }}:</span>
                        @foreach($task['options'] as $i => $option)
                            <span class="option">
                                <span class="option-num">{{ $i + 1 }})</span>
                                <span>${{ $option }}$</span>
                            </span>
                        @endforeach
                    </div>
                @endforeach

            @elseif($zadanie['type'] === 'fraction_options' || $zadanie['type'] === 'sqrt_options')
                {{-- Fraction/sqrt options --}}
                @foreach($zadanie['tasks'] as $task)
                    <div class="task-row">
                        <span class="task-number">{{ $task['id'] }}</span>
                        <div class="task-options">
                            @foreach($task['options'] as $i => $option)
                                <span class="option">
                                    <span class="option-num">{{ $i + 1 }})</span>
                                    <span>${{ $option }}$</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach

            @elseif($zadanie['type'] === 'decimal_choice')
                {{-- Decimal choice --}}
                @foreach($zadanie['tasks'] as $task)
                    <div class="decimal-task">
                        <span class="task-number">{{ $task['id'] }}</span>
                        <div class="decimal-numbers">Числа: {{ $task['numbers'] }}</div>
                        <div class="decimal-target">Число <strong>{{ $task['target'] }}</strong>?</div>
                        <div class="task-options">
                            @foreach($task['options'] as $i => $option)
                                <span class="option">
                                    <span class="option-num">{{ $i + 1 }})</span>
                                    <span>{{ $option }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach

            @elseif($zadanie['type'] === 'compare_fractions')
                {{-- Compare fractions --}}
                @foreach($zadanie['tasks'] as $task)
                    <div class="task-row">
                        <span class="task-number">{{ $task['id'] }}</span>
                        <div class="task-content">
                            <div>{{ $task['condition'] }}: ${{ $task['question'] }}$</div>
                            <div class="task-options">
                                @foreach($task['options'] as $i => $option)
                                    <span class="option">
                                        <span class="option-num">{{ $i + 1 }})</span>
                                        <span>{{ $option }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

            @elseif($zadanie['type'] === 'false_statements' || $zadanie['type'] === 'ordering')
                {{-- False statements or ordering --}}
                @foreach($zadanie['tasks'] as $task)
                    <div class="task-row">
                        <span class="task-number">{{ $task['id'] }}</span>
                        <div class="task-options">
                            @foreach($task['options'] as $i => $option)
                                <span class="option">
                                    <span class="option-num">{{ $i + 1 }})</span>
                                    <span>{{ $option }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach

            @elseif($zadanie['type'] === 'point_value')
                {{-- Point value --}}
                @foreach($zadanie['tasks'] as $task)
                    <div class="task-row">
                        <span class="task-number">{{ $task['id'] }}.</span>
                        <div class="task-content">
                            <span>Точка {{ $task['point'] }}:</span>
                            <div class="task-options">
                                @foreach($task['options'] as $i => $option)
                                    <span class="option">
                                        <span class="option-num">{{ $i + 1 }})</span>
                                        @if(str_contains($option, '\frac'))
                                            <span>${{ $option }}$</span>
                                        @else
                                            <span>{{ $option }}</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

            @elseif($zadanie['type'] === 'count_integers')
                {{-- Count integers --}}
                <div class="count-grid">
                    @foreach($zadanie['tasks'] as $task)
                        <div class="count-item">
                            <span class="task-number">{{ $task['id'] }}</span>
                            ${{ $task['left'] }}$ и ${{ $task['right'] }}$?
                        </div>
                    @endforeach
                </div>

            @else
                {{-- Default: just show the type --}}
                <p><em>Тип задания: {{ $zadanie['type'] }}</em></p>
            @endif
        </div>
    @endforeach
</div>
@endforeach

<!-- Info box about parsing -->
<div class="info-box">
    <h4>📊 Информация о парсинге</h4>
    <p><strong>Источник данных:</strong> {{ $source ?? 'Manual' }}</p>
    <p><strong>PDF файл:</strong> <code>storage/app/pdf/task_07.pdf</code></p>
    <p><strong>Тема:</strong> 07. Числа, координатная прямая</p>
    <p><strong>Структура данных:</strong></p>
    <ul>
        <li>Блок → Задание → Задачи с вариантами ответов</li>
        <li>Типы заданий: choice, fraction_choice, interval_choice, sqrt_choice и др.</li>
    </ul>
</div>

</body>
</html>
