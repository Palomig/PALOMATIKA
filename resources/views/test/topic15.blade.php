<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>15. Треугольники - Тест парсинга PDF</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathInElement(document.body, {delimiters: [{left: '$$', right: '$$', display: true}, {left: '$', right: '$', display: false}]});"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'PT Serif', Georgia, serif; font-size: 17px; line-height: 1.6; padding: 40px 60px; max-width: 1200px; margin: 0 auto; background: #fefefe; color: #1a1a1a; }
        .page { margin-bottom: 60px; padding-bottom: 40px; border-bottom: 2px solid #e0e0e0; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; color: #666; font-style: italic; }
        .title { text-align: center; font-weight: 700; font-size: 24px; margin-bottom: 8px; color: #2c3e50; }
        .subtitle { text-align: center; font-weight: 600; font-size: 18px; margin-bottom: 30px; color: #34495e; }
        .zadanie { margin-bottom: 35px; }
        .zadanie-header { font-weight: 700; font-size: 16px; margin-bottom: 15px; color: #2c3e50; background: #f8f9fa; padding: 10px 15px; border-radius: 6px; border-left: 4px solid #27ae60; }
        .tasks-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .tasks-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
        .task-card { border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; background: #fff; transition: box-shadow 0.2s; }
        .task-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .task-number { font-weight: 600; color: #27ae60; margin-bottom: 10px; font-size: 14px; }
        .task-text { font-size: 15px; line-height: 1.6; margin-bottom: 10px; color: #333; }
        .task-image { width: 100%; max-width: 280px; height: auto; border: 1px solid #ddd; border-radius: 4px; display: block; margin: 0 auto; }
        .task-image-container { text-align: center; background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 10px; }
        .nav-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 15px 20px; background: #f8f9fa; border-radius: 8px; font-family: 'Inter', sans-serif; }
        .nav-bar a { color: #60a5fa; text-decoration: none; font-size: 14px; }
        .nav-bar a:hover { text-decoration: underline; }
        .info-box { margin-top: 40px; padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; }
        .info-box h4 { color: #495057; margin-bottom: 12px; }
        .info-box p { margin-bottom: 8px; color: #6c757d; }
        .info-box code { background: #e9ecef; padding: 3px 8px; border-radius: 4px; font-size: 13px; }
        .info-box ul { margin-left: 20px; margin-top: 8px; color: #6c757d; }
        .stats { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-item { background: #f8f9fa; padding: 10px 20px; border-radius: 6px; font-size: 14px; }
        .stat-item strong { color: #27ae60; }
        @media (max-width: 900px) { body { padding: 20px; font-size: 15px; } .title { font-size: 20px; } .tasks-grid { grid-template-columns: 1fr; } }
        @media print { .nav-bar, .info-box { display: none; } }
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
        <a href="{{ route('test.topic11') }}">11</a> |
        <a href="{{ route('test.topic12') }}">12</a> |
        <a href="{{ route('test.topic13') }}">13</a> |
        <a href="{{ route('test.topic14') }}">14</a> |
        <strong>15</strong> |
        <a href="{{ route('test.topic16') }}">16</a> |
        <a href="{{ route('test.topic18') }}">18</a> |
        <a href="{{ route('test.topic19') }}">19</a>
    </div>
</div>

@php
    $totalTasks = 0;
    foreach ($blocks as $block) {
        foreach ($block['zadaniya'] as $zadanie) {
            $totalTasks += count($zadanie['tasks'] ?? []);
        }
    }
@endphp

<h1 class="title">15. Треугольники</h1>
<p class="subtitle">Геометрические задачи на треугольники</p>

<div class="stats">
    <div class="stat-item">
        <strong>{{ count($blocks) }}</strong> блоков
    </div>
    <div class="stat-item">
        <strong>{{ $totalTasks }}</strong> заданий
    </div>
</div>

@foreach($blocks as $block)
<div class="page">
    <div class="header">
        <span>Е. А. Ширяева</span>
        <span>Задачник ОГЭ 2026 (тренажер)</span>
    </div>
    <div class="title">15. Треугольники</div>
    <div class="subtitle">Блок {{ $block['number'] }}. {{ $block['title'] }}</div>

    @foreach($block['zadaniya'] as $zadanie)
        <div class="zadanie">
            <div class="zadanie-header">Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}</div>
            <div class="tasks-grid {{ count($zadanie['tasks'] ?? []) > 6 ? 'cols-3' : '' }}">
                @foreach($zadanie['tasks'] ?? [] as $task)
                    <div class="task-card">
                        <div class="task-number">{{ $task['id'] }}.</div>
                        <div class="task-text">{{ $task['text'] }}</div>

                        @if(isset($task['image']))
                            <div class="task-image-container">
                                @php
                                    $imgPath = '/images/tasks/15/' . $task['image'];
                                @endphp
                                <img src="{{ $imgPath }}"
                                     alt="Рисунок к задаче {{ $task['id'] }}"
                                     class="task-image"
                                     onerror="this.style.display='none'; this.parentNode.innerHTML='<span style=\'color:#999;\'>{{ $task['image'] }}</span>';">
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endforeach

<div class="info-box">
    <h4>Информация о парсинге</h4>
    <p><strong>Тема:</strong> 15. Треугольники</p>
    <p><strong>Источник:</strong> {{ $source ?? 'Manual' }}</p>
    <p><strong>Контроллер:</strong> <code>TestPdfController::getAllBlocksData15()</code></p>
    <ul>
        <li>Блок 1: ФИПИ (углы, биссектрисы, медианы)</li>
        <li>Блок 2: Расширенная версия (подобие, площади)</li>
        <li>Всего: ~{{ $totalTasks }} задач с изображениями</li>
    </ul>
</div>

</body>
</html>
