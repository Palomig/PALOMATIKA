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

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            line-height: 1.5;
            padding: 30px 50px;
            max-width: 850px;
            margin: 0 auto;
            background: #fff;
            color: #000;
        }

        .page {
            margin-bottom: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 11px;
            font-style: italic;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 3px;
        }

        .subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .zadanie {
            margin-bottom: 20px;
        }

        .zadanie-header {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .zadanie-instruction {
            margin-bottom: 10px;
        }

        /* Grid for fraction tasks - 4 columns */
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 5px 10px;
            margin-bottom: 10px;
        }

        .task-item {
            display: flex;
            align-items: baseline;
            font-size: 13px;
            line-height: 1.8;
        }

        .task-number {
            min-width: 22px;
            flex-shrink: 0;
        }

        .task-expression {
            white-space: nowrap;
        }

        /* Zadanie 3 - paragraph style */
        .zadanie-paragraph {
            margin-bottom: 8px;
            text-indent: 0;
        }

        .zadanie-paragraph p {
            margin-bottom: 5px;
        }

        /* Make KaTeX slightly smaller to fit */
        .katex {
            font-size: 1em !important;
        }

        /* Info box */
        .info-box {
            margin-top: 30px;
            padding: 15px;
            background: #f0f8ff;
            border: 1px solid #4a90d9;
            border-radius: 5px;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .info-box h4 {
            color: #2563eb;
            margin-bottom: 10px;
        }

        .info-box code {
            background: #e5e7eb;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 11px;
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
            <p class="zadanie-header">Задание {{ $zadanie['number'] }}. <span style="font-weight: normal">{{ $zadanie['instruction'] }}</span></p>

            @if($zadanie['number'] == 3)
                {{-- Zadanie 3 - Special paragraph format --}}
                <div class="zadanie-paragraph">
                    @foreach($zadanie['tasks'] as $task)
                        <p><strong>{{ $task['id'] }})</strong> Представьте выражение ${{ $task['expression'] }}$ в виде дроби со знаменателем {{ $task['denominator'] }}. В ответ запишите числитель полученной дроби.</p>
                    @endforeach
                </div>
            @else
                {{-- Zadanie 1 & 2 - Grid layout --}}
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
    <p><strong>Источник данных:</strong> Ручной ввод на основе скриншотов PDF</p>
    <p><strong>Файл:</strong> <code>app/Http/Controllers/TestPdfController.php</code></p>
    <p><strong>Структура данных:</strong></p>
    <ul style="margin-left: 20px; margin-top: 5px;">
        <li>Блок → Задание → Задачи</li>
        <li>Каждая задача: id, expression (LaTeX), answer, [denominator]</li>
    </ul>
    <p style="margin-top: 10px;"><strong>Для парсинга PDF:</strong> Загрузите PDF в <code>storage/app/pdf/</code> и реализуйте парсер</p>
</div>

</body>
</html>
