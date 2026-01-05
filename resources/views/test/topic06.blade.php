<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>06. Дроби и степени - Тест парсинга PDF</title>

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

        /* Grid for fraction tasks - 4 columns */
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px 20px;
            margin-bottom: 15px;
        }

        .task-item {
            display: flex;
            align-items: center;
            font-size: 18px;
            line-height: 2;
            padding: 4px 0;
        }

        .task-number {
            min-width: 30px;
            flex-shrink: 0;
            font-weight: 500;
            color: #555;
        }

        .task-expression {
            white-space: nowrap;
        }

        /* Zadanie 3 - paragraph style */
        .zadanie-paragraph {
            margin-bottom: 15px;
        }

        .zadanie-paragraph p {
            margin-bottom: 12px;
            font-size: 17px;
            line-height: 1.8;
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
    <div class="title">06. Дроби и степени</div>
    <div class="subtitle">Блок {{ $block['number'] }}. {{ $block['title'] }}</div>

    @foreach($block['zadaniya'] as $zadanie)
        <div class="zadanie">
            <p class="zadanie-header">Задание {{ $zadanie['number'] }}. <span>{{ $zadanie['instruction'] }}</span></p>

            @if(isset($zadanie['tasks'][0]['denominator']))
                {{-- Zadanie with denominator - paragraph format --}}
                <div class="zadanie-paragraph">
                    @foreach($zadanie['tasks'] as $task)
                        <p><strong>{{ $task['id'] }})</strong> Представьте выражение ${{ $task['expression'] }}$ в виде дроби со знаменателем {{ $task['denominator'] }}. В ответ запишите числитель полученной дроби.</p>
                    @endforeach
                </div>
            @else
                {{-- Grid layout --}}
                <div class="tasks-grid">
                    @foreach($zadanie['tasks'] as $task)
                        <div class="task-item">
                            <span class="task-number">{{ $task['id'] }})</span>
                            <span class="task-expression">${{ $task['expression'] }}$;</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
@endforeach

<!-- Info box about parsing -->
<div class="info-box">
    <h4>📊 Информация о парсинге</h4>
    <p><strong>Источник данных:</strong> {{ $source ?? 'Manual' }}</p>
    <p><strong>PDF файл:</strong> <code>storage/app/pdf/task_06.pdf</code></p>
    <p><strong>Парсер:</strong> <code>app/Services/PdfTaskParser.php</code></p>
    <p><strong>Структура данных:</strong></p>
    <ul>
        <li>Блок → Задание → Задачи</li>
        <li>Каждая задача: id, expression (LaTeX), answer, [denominator]</li>
    </ul>
</div>

</body>
</html>
