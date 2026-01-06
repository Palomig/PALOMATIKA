<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>13. Неравенства - Тест парсинга PDF</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathInElement(document.body, {delimiters: [{left: '$$', right: '$$', display: true}, {left: '$', right: '$', display: false}]});"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'PT Serif', Georgia, serif; font-size: 17px; line-height: 1.6; padding: 40px 60px; max-width: 1000px; margin: 0 auto; background: #fefefe; color: #1a1a1a; }
        .page { margin-bottom: 60px; padding-bottom: 40px; border-bottom: 2px solid #e0e0e0; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; color: #666; font-style: italic; }
        .title { text-align: center; font-weight: 700; font-size: 24px; margin-bottom: 8px; color: #2c3e50; }
        .subtitle { text-align: center; font-weight: 600; font-size: 18px; margin-bottom: 30px; color: #34495e; }
        .zadanie { margin-bottom: 35px; }
        .zadanie-header { font-weight: 700; font-size: 16px; margin-bottom: 15px; color: #2c3e50; background: #f8f9fa; padding: 10px 15px; border-radius: 6px; border-left: 4px solid #9b59b6; }
        .tasks-list { list-style: none; padding: 0; }
        .task-item { margin-bottom: 15px; padding: 12px 15px; background: #fff; border: 1px solid #e9ecef; border-radius: 8px; }
        .task-header { display: flex; align-items: flex-start; margin-bottom: 10px; }
        .task-number { min-width: 35px; flex-shrink: 0; font-weight: 600; color: #9b59b6; }
        .task-expression { font-size: 18px; }
        .task-options { display: flex; flex-wrap: wrap; gap: 10px; margin-left: 35px; }
        .option { background: #f5f0fa; padding: 5px 12px; border-radius: 4px; font-size: 14px; }
        .nav-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 15px 20px; background: #f8f9fa; border-radius: 8px; font-family: 'Inter', sans-serif; }
        .nav-bar a { color: #60a5fa; text-decoration: none; font-size: 14px; }
        .nav-bar a:hover { text-decoration: underline; }
        .info-box { margin-top: 40px; padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; }
        .info-box h4 { color: #495057; margin-bottom: 12px; }
        .info-box p { margin-bottom: 8px; color: #6c757d; }
        .info-box code { background: #e9ecef; padding: 3px 8px; border-radius: 4px; font-size: 13px; }
        .info-box ul { margin-left: 20px; margin-top: 8px; color: #6c757d; }
        @media (max-width: 900px) { body { padding: 20px; font-size: 15px; } .title { font-size: 20px; } .task-options { margin-left: 0; } }
        @media print { .nav-bar, .info-box { display: none; } .task-item { page-break-inside: avoid; } }
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
        <strong>13</strong>
    </div>
</div>

@foreach($blocks as $block)
<div class="page">
    <div class="header">
        <span>Е. А. Ширяева</span>
        <span>Задачник ОГЭ 2026 (тренажер)</span>
    </div>
    <div class="title">13. Неравенства</div>
    <div class="subtitle">Блок {{ $block['number'] }}. {{ $block['title'] }}</div>

    @foreach($block['zadaniya'] as $zadanie)
        <div class="zadanie">
            <div class="zadanie-header">Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}</div>
            <ul class="tasks-list">
                @foreach($zadanie['tasks'] ?? [] as $task)
                    <li class="task-item">
                        <div class="task-header">
                            <span class="task-number">{{ $task['id'] }}.</span>
                            <span class="task-expression">${{ $task['expression'] ?? '' }}$</span>
                        </div>
                        @if(isset($task['options']))
                            <div class="task-options">
                                @foreach($task['options'] as $i => $opt)
                                    <span class="option">{{ $i + 1 }}) {{ $opt }}</span>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
@endforeach

<div class="info-box">
    <h4>Информация о парсинге</h4>
    <p><strong>Тема:</strong> 13. Неравенства</p>
    <p><strong>Источник:</strong> {{ $source ?? 'Manual' }}</p>
    <p><strong>Контроллер:</strong> <code>TestPdfController::getAllBlocksData13()</code></p>
    <ul>
        <li>Блок 1: ФИПИ (линейные, системы, квадратные неравенства)</li>
        <li>Блок 2: Расширенная версия</li>
        <li>Всего: ~150 задач</li>
    </ul>
</div>

</body>
</html>
