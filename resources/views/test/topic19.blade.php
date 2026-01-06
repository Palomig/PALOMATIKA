<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>19. Анализ геометрических высказываний - Тест парсинга PDF</title>

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

        .section-title {
            font-weight: 600;
            font-size: 16px;
            color: #8e44ad;
            margin: 30px 0 15px;
            padding: 10px 15px;
            background: #f5f0fa;
            border-radius: 6px;
            border-left: 4px solid #8e44ad;
        }

        .zadanie {
            margin-bottom: 25px;
        }

        .zadanie-header {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 10px;
            color: #6c757d;
        }

        /* Statements list */
        .statements-list {
            list-style: none;
            padding: 0;
        }

        .statement-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            padding: 12px 15px;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .statement-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .statement-item.correct {
            border-left: 4px solid #27ae60;
            background: #f8fff8;
        }

        .statement-item.incorrect {
            border-left: 4px solid #e74c3c;
            background: #fff8f8;
        }

        .statement-number {
            min-width: 40px;
            flex-shrink: 0;
            font-weight: 700;
            color: #3498db;
            font-size: 15px;
        }

        .statement-text {
            flex: 1;
            line-height: 1.6;
        }

        .statement-badge {
            margin-left: 10px;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .statement-badge.true {
            background: #d4edda;
            color: #155724;
        }

        .statement-badge.false {
            background: #f8d7da;
            color: #721c24;
        }

        /* Toggle answers button */
        .toggle-answers {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: background 0.2s;
        }

        .toggle-answers:hover {
            background: #2980b9;
        }

        .hide-answers .statement-badge,
        .hide-answers .statement-item.correct,
        .hide-answers .statement-item.incorrect {
            display: none;
        }

        .hide-answers .statement-item {
            border-left: 4px solid #e9ecef;
            background: #fff;
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

        /* Stats */
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }

        .stat-item {
            padding: 8px 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .stat-item.true {
            background: #d4edda;
            color: #155724;
        }

        .stat-item.false {
            background: #f8d7da;
            color: #721c24;
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
            .statement-item {
                flex-direction: column;
            }
            .statement-number {
                margin-bottom: 5px;
            }
            .statement-badge {
                margin-left: 0;
                margin-top: 8px;
            }
        }

        /* Print */
        @media print {
            .nav-bar, .info-box, .toggle-answers {
                display: none;
            }
            body {
                padding: 20px;
            }
            .statement-item {
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
        <a href="{{ route('test.topic18') }}">18</a> |
        <strong>19</strong>
    </div>
</div>

<button class="toggle-answers" onclick="toggleAnswers()">Показать/скрыть ответы</button>

@php
    $currentSection = '';
@endphp

@foreach($blocks as $block)
<div class="page" id="statements-container">
    <!-- Page Header -->
    <div class="header">
        <span>Е. А. Ширяева</span>
        <span>Задачник ОГЭ 2026 (тренажер)</span>
    </div>

    <!-- Title -->
    <div class="title">19. Анализ геометрических высказываний</div>
    <div class="subtitle">Блок {{ $block['number'] }}. {{ $block['title'] }}</div>

    @php
        $trueCount = 0;
        $falseCount = 0;
        foreach ($block['zadaniya'] as $zadanie) {
            foreach ($zadanie['tasks'] ?? [] as $task) {
                if (isset($task['correct'])) {
                    if ($task['correct']) $trueCount++;
                    else $falseCount++;
                }
            }
        }
    @endphp

    <div class="stats">
        <div class="stat-item">Всего утверждений: {{ $trueCount + $falseCount }}</div>
        <div class="stat-item true">Верных: {{ $trueCount }}</div>
        <div class="stat-item false">Неверных: {{ $falseCount }}</div>
    </div>

    @foreach($block['zadaniya'] as $zadanie)
        @if(isset($zadanie['section']) && $zadanie['section'] !== $currentSection)
            @php $currentSection = $zadanie['section']; @endphp
            <div class="section-title">{{ $currentSection }}</div>
        @endif

        <div class="zadanie">
            <ul class="statements-list">
                @foreach($zadanie['tasks'] ?? [] as $task)
                    @php
                        $isCorrect = isset($task['correct']) ? $task['correct'] : null;
                        $itemClass = '';
                        if ($isCorrect === true) $itemClass = 'correct';
                        elseif ($isCorrect === false) $itemClass = 'incorrect';
                    @endphp
                    <li class="statement-item {{ $itemClass }}">
                        <span class="statement-number">{{ $task['id'] }}.</span>
                        <span class="statement-text">{{ $task['text'] }}</span>
                        @if($isCorrect !== null)
                            <span class="statement-badge {{ $isCorrect ? 'true' : 'false' }}">
                                {{ $isCorrect ? 'Верно' : 'Неверно' }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
@php $currentSection = ''; @endphp
@endforeach

<!-- Info box about parsing -->
<div class="info-box">
    <h4>Информация о парсинге</h4>
    <p><strong>Тема:</strong> 19. Анализ геометрических высказываний</p>
    <p><strong>Источник данных:</strong> {{ $source ?? 'Manual' }}</p>
    <p><strong>PDF файл:</strong> <code>storage/app/pdf/task_19.pdf</code></p>
    <p><strong>Контроллер:</strong> <code>TestPdfController::getAllBlocksData19()</code></p>
    <p><strong>Структура данных:</strong></p>
    <ul>
        <li>Блок 1: ФИПИ (117 утверждений по 9 разделам геометрии)</li>
        <li>Блок 2: ФИПИ. Расширенная версия - старый ОБЗ (71 утверждение по 7 разделам)</li>
        <li>Всего: 188 утверждений с ответами (верно/неверно)</li>
    </ul>
    <p><strong>Разделы:</strong> Начальные сведения, Параллельные прямые, Треугольники, Четырёхугольники, Параллелограмм, Прямоугольник/Квадрат, Трапеция, Ромб, Окружность</p>
</div>

<script>
    function toggleAnswers() {
        const container = document.body;
        container.classList.toggle('hide-answers');
    }
</script>

</body>
</html>
