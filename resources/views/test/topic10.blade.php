<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>10. Теория вероятностей - Тест парсинга PDF</title>

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
            font-size: 16px;
            margin-bottom: 15px;
            color: #2c3e50;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }

        /* Word problems list */
        .tasks-list {
            list-style: none;
            padding: 0;
        }

        .task-item {
            display: flex;
            margin-bottom: 15px;
            padding: 12px 15px;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: box-shadow 0.2s;
        }

        .task-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .task-number {
            min-width: 35px;
            flex-shrink: 0;
            font-weight: 600;
            color: #3498db;
            font-size: 16px;
        }

        .task-text {
            flex: 1;
            line-height: 1.7;
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

        /* Navigation */
        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 15px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
        }

        .nav-bar a {
            color: #60a5fa;
            text-decoration: none;
            font-size: 14px;
        }

        .nav-bar a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 900px) {
            body {
                padding: 20px;
                font-size: 15px;
            }
            .title {
                font-size: 20px;
            }
            .task-item {
                flex-direction: column;
            }
            .task-number {
                margin-bottom: 5px;
            }
        }

        /* Print */
        @media print {
            .nav-bar, .info-box {
                display: none;
            }
            body {
                padding: 20px;
            }
            .task-item {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="nav-bar">
    <a href="{{ route('test.pdf.index') }}">← Назад к парсеру</a>
    <div>
        <a href="{{ route('test.topic06') }}">06</a> |
        <a href="{{ route('test.topic07') }}">07</a> |
        <a href="{{ route('test.topic08') }}">08</a> |
        <a href="{{ route('test.topic09') }}">09</a> |
        <strong>10</strong> |
        <a href="{{ route('test.topic11') }}">11</a> |
        <a href="{{ route('test.topic12') }}">12</a> |
        <a href="{{ route('test.topic13') }}">13</a> |
        <a href="{{ route('test.topic18') }}">18</a> |
        <a href="{{ route('test.topic19') }}">19</a>
    </div>
</div>

@foreach($blocks as $block)
<div class="page">
    <!-- Page Header -->
    <div class="header">
        <span>Е. А. Ширяева</span>
        <span>Задачник ОГЭ 2026 (тренажер)</span>
    </div>

    <!-- Title -->
    <div class="title">10. Теория вероятностей</div>
    <div class="subtitle">Блок {{ $block['number'] }}. {{ $block['title'] }}</div>

    @foreach($block['zadaniya'] as $zadanie)
        <div class="zadanie">
            <div class="zadanie-header">{{ $zadanie['instruction'] }}</div>

            <ul class="tasks-list">
                @foreach($zadanie['tasks'] ?? [] as $task)
                    <li class="task-item">
                        <span class="task-number">{{ $task['id'] }}.</span>
                        <span class="task-text">{{ $task['text'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
@endforeach

<!-- Info box about parsing -->
<div class="info-box">
    <h4>Информация о парсинге</h4>
    <p><strong>Тема:</strong> 10. Теория вероятностей</p>
    <p><strong>Источник данных:</strong> {{ $source ?? 'Manual' }}</p>
    <p><strong>PDF файл:</strong> <code>storage/app/pdf/task_10.pdf</code></p>
    <p><strong>Контроллер:</strong> <code>TestPdfController::getAllBlocksData10()</code></p>
    <p><strong>Структура данных:</strong></p>
    <ul>
        <li>Блок 1: ФИПИ (56 задач - классическая, статистическая вероятность, формулы)</li>
        <li>Блок 2: ФИПИ. Расширенная версия (60 задач)</li>
        <li>Блок 3: Типовые экзаменационные варианты (32 задачи)</li>
        <li>Всего: ~148 задач (текстовые задачи на вероятность)</li>
    </ul>
</div>

</body>
</html>
