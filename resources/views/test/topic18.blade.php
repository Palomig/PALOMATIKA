<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>18. Фигуры на квадратной решётке - Тест парсинга PDF</title>

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
            max-width: 1200px;
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

        .section-title {
            font-weight: 600;
            font-size: 16px;
            color: #8e44ad;
            margin: 25px 0 15px;
            padding-left: 10px;
            border-left: 3px solid #8e44ad;
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

        /* Grid tasks */
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            padding: 10px 0;
        }

        .task-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            transition: box-shadow 0.2s;
        }

        .task-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .task-card .task-number {
            font-weight: 600;
            color: #3498db;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .task-card .task-text {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }

        .task-card .task-image-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 4px;
            padding: 30px 10px;
            color: #adb5bd;
            font-size: 12px;
        }

        .task-card img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
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
            flex-wrap: wrap;
            gap: 10px;
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
            .tasks-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 500px) {
            .tasks-grid {
                grid-template-columns: 1fr;
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
            .task-card {
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
        <a href="{{ route('test.topic10') }}">10</a> |
        <strong>18</strong> |
        <a href="{{ route('test.topic19') }}">19</a>
    </div>
</div>

@php
    $currentSection = '';
@endphp

@foreach($blocks as $block)
<div class="page">
    <!-- Page Header -->
    <div class="header">
        <span>Е. А. Ширяева</span>
        <span>Задачник ОГЭ 2026 (тренажер)</span>
    </div>

    <!-- Title -->
    <div class="title">18. Фигуры на квадратной решётке</div>
    <div class="subtitle">Блок {{ $block['number'] }}. {{ $block['title'] }}</div>

    @foreach($block['zadaniya'] as $zadanie)
        @if(isset($zadanie['section']) && $zadanie['section'] !== $currentSection)
            @php $currentSection = $zadanie['section']; @endphp
            <div class="section-title">{{ $currentSection }}</div>
        @endif

        <div class="zadanie">
            <div class="zadanie-header">Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}</div>

            <div class="tasks-grid">
                @foreach($zadanie['tasks'] ?? [] as $task)
                    <div class="task-card">
                        <div class="task-number">{{ $task['id'] }})</div>
                        @if(isset($task['text']))
                            <div class="task-text">{{ $task['text'] }}</div>
                        @endif
                        @if(isset($task['image']))
                            @php
                                $imagePath = '/images/tasks/18/' . $task['image'];
                            @endphp
                            @if(file_exists(public_path($imagePath)))
                                <img src="{{ $imagePath }}" alt="Задача {{ $task['id'] }}">
                            @else
                                <div class="task-image-placeholder">
                                    {{ $task['image'] }}<br>
                                    <small>(изображение)</small>
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@php $currentSection = ''; @endphp
@endforeach

<!-- Info box about parsing -->
<div class="info-box">
    <h4>Информация о парсинге</h4>
    <p><strong>Тема:</strong> 18. Фигуры на квадратной решётке</p>
    <p><strong>Источник данных:</strong> {{ $source ?? 'Manual' }}</p>
    <p><strong>PDF файл:</strong> <code>storage/app/pdf/task_18.pdf</code></p>
    <p><strong>Контроллер:</strong> <code>TestPdfController::getAllBlocksData18()</code></p>
    <p><strong>Структура данных:</strong></p>
    <ul>
        <li>Блок 1: ФИПИ (12 заданий - длина, теорема Фалеса, площадь, теорема Пифагора, подобные треугольники, площадь круга)</li>
        <li>Блок 2: ФИПИ. Расширенная версия (11 заданий - расстояние, площадь, углы)</li>
        <li>Тип заданий: геометрия на клетчатой бумаге (требуются изображения)</li>
    </ul>
    <p><strong>Примечание:</strong> Изображения для заданий должны быть извлечены из PDF и размещены в <code>public/images/tasks/18/</code></p>
</div>

</body>
</html>
