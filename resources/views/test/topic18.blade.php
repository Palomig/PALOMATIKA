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

        /* Grid layout for images */
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .tasks-grid.cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .task-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            background: #fff;
            transition: box-shadow 0.2s;
        }

        .task-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .task-number {
            font-weight: 600;
            color: #3498db;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .task-question {
            font-size: 14px;
            color: #555;
            margin-bottom: 10px;
            font-style: italic;
        }

        .task-image {
            width: 100%;
            max-width: 200px;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: block;
            margin: 0 auto;
        }

        .task-image-container {
            text-align: center;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }

        /* Source info */
        .source-info {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .source-info strong {
            color: #2c3e50;
        }

        /* Back link */
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Stats */
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
        }

        .stat-item strong {
            color: #3498db;
        }

        @media (max-width: 768px) {
            body {
                padding: 20px;
            }

            .tasks-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .tasks-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="nav-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 15px 20px; background: #f8f9fa; border-radius: 8px; font-family: 'Inter', sans-serif;">
        <a href="{{ route('test.pdf.index') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">← Назад к парсеру</a>
        <div>
            <a href="{{ route('test.topic06') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">06</a> |
            <a href="{{ route('test.topic07') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">07</a> |
            <a href="{{ route('test.topic08') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">08</a> |
            <a href="{{ route('test.topic09') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">09</a> |
            <a href="{{ route('test.topic10') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">10</a> |
            <a href="{{ route('test.topic11') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">11</a> |
            <a href="{{ route('test.topic12') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">12</a> |
            <a href="{{ route('test.topic13') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">13</a> |
            <strong>18</strong> |
            <a href="{{ route('test.topic19') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">19</a>
        </div>
    </div>

    <h1 class="title">18. Фигуры на квадратной решётке</h1>
    <p class="subtitle">Геометрические задачи на клетчатой бумаге</p>

    <div class="source-info">
        <strong>Источник данных:</strong> {{ $source ?? 'Manual' }}
    </div>

    @php
        $totalTasks = 0;
        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] as $zadanie) {
                $totalTasks += count($zadanie['tasks'] ?? []);
            }
        }
    @endphp

    <div class="stats">
        <div class="stat-item">
            <strong>{{ count($blocks) }}</strong> блоков
        </div>
        <div class="stat-item">
            <strong>{{ $totalTasks }}</strong> заданий
        </div>
    </div>

    @foreach ($blocks as $block)
        <div class="page">
            <div class="header">
                <span>Е. А. Ширяева</span>
                <span>Задачник ОГЭ 2026 (тренажер)</span>
            </div>

            <h2 class="subtitle">Блок {{ $block['number'] }}. {{ $block['title'] }}</h2>

            @foreach ($block['zadaniya'] as $zadanie)
                <div class="zadanie">
                    <div class="zadanie-header">
                        Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}
                    </div>

                    @if (($zadanie['type'] ?? '') === 'grid_image' || ($zadanie['type'] ?? '') === 'grid_image_with_question')
                        <div class="tasks-grid {{ count($zadanie['tasks'] ?? []) <= 4 ? 'cols-2' : '' }}">
                            @foreach ($zadanie['tasks'] ?? [] as $task)
                                @php
                                    // Generate image path: task18_b{block}_z{zadanie}_{task}.png
                                    $imagePath = '/images/tasks/18/task18_b' . $block['number'] . '_z' . $zadanie['number'] . '_' . $task['id'] . '.png';
                                @endphp
                                <div class="task-card">
                                    <div class="task-number">{{ $task['id'] }})</div>

                                    @if (!empty($task['question']))
                                        <div class="task-question">{{ $task['question'] }}</div>
                                    @endif

                                    <div class="task-image-container">
                                        <img src="{{ $imagePath }}"
                                             alt="Задание {{ $task['id'] }}"
                                             class="task-image"
                                             onerror="this.style.display='none'; this.parentNode.innerHTML='<span style=\'color:#999;\'>Изображение: {{ basename($imagePath) }}</span>';">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach

    <div style="text-align: center; padding: 20px; color: #666; font-size: 14px;">
        Всего заданий: {{ $totalTasks }}
    </div>
</body>
</html>
