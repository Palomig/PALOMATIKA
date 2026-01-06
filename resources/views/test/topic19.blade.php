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

        .zadanie {
            margin-bottom: 35px;
        }

        .zadanie-header {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 10px;
            color: #2c3e50;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }

        .section-title {
            font-weight: 600;
            font-size: 15px;
            color: #7f8c8d;
            margin-bottom: 15px;
            padding-left: 15px;
            border-left: 3px solid #95a5a6;
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

        .statement-item.is-true {
            border-left: 4px solid #27ae60;
            background: #f0fff4;
        }

        .statement-item.is-false {
            border-left: 4px solid #e74c3c;
            background: #fff5f5;
        }

        .statement-number {
            min-width: 35px;
            flex-shrink: 0;
            font-weight: 600;
            color: #3498db;
        }

        .statement-text {
            flex: 1;
        }

        .statement-badge {
            margin-left: 10px;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .statement-badge.true {
            background: #27ae60;
            color: white;
        }

        .statement-badge.false {
            background: #e74c3c;
            color: white;
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

        /* Toggle buttons */
        .toggle-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .toggle-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }

        .toggle-btn:hover {
            background: #f8f9fa;
        }

        .toggle-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        /* Hidden statements */
        .statement-item.hidden {
            display: none;
        }

        @media (max-width: 768px) {
            body {
                padding: 20px;
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
            <a href="{{ route('test.topic14') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">14</a> |
            <a href="{{ route('test.topic15') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">15</a> |
            <a href="{{ route('test.topic16') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">16</a> |
            <a href="{{ route('test.topic18') }}" style="color: #60a5fa; text-decoration: none; font-size: 14px;">18</a> |
            <strong>19</strong>
        </div>
    </div>

    <h1 class="title">19. Анализ геометрических высказываний</h1>
    <p class="subtitle">Укажите номера верных утверждений</p>

    <div class="source-info">
        <strong>Источник данных:</strong> {{ $source ?? 'Manual' }}
    </div>

    @php
        $totalStatements = 0;
        $trueStatements = 0;
        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] as $zadanie) {
                foreach ($zadanie['statements'] ?? [] as $statement) {
                    $totalStatements++;
                    if ($statement['is_true'] ?? false) {
                        $trueStatements++;
                    }
                }
            }
        }
    @endphp

    <div class="stats">
        <div class="stat-item">
            <strong>{{ count($blocks) }}</strong> блоков
        </div>
        <div class="stat-item">
            <strong>{{ $totalStatements }}</strong> утверждений
        </div>
        <div class="stat-item">
            <strong style="color: #27ae60;">{{ $trueStatements }}</strong> верных
        </div>
        <div class="stat-item">
            <strong style="color: #e74c3c;">{{ $totalStatements - $trueStatements }}</strong> неверных
        </div>
    </div>

    <div class="toggle-buttons">
        <button class="toggle-btn active" onclick="filterStatements('all')">Все</button>
        <button class="toggle-btn" onclick="filterStatements('true')">Только верные</button>
        <button class="toggle-btn" onclick="filterStatements('false')">Только неверные</button>
        <button class="toggle-btn" onclick="toggleAnswers()">Показать/скрыть ответы</button>
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
                        {{ $zadanie['instruction'] }}
                    </div>

                    @if (!empty($zadanie['section']))
                        <div class="section-title">{{ $zadanie['section'] }}</div>
                    @endif

                    @if (($zadanie['type'] ?? '') === 'statements')
                        <ul class="statements-list">
                            @foreach ($zadanie['statements'] ?? [] as $statement)
                                <li class="statement-item {{ $statement['is_true'] ? 'is-true' : 'is-false' }}"
                                    data-is-true="{{ $statement['is_true'] ? 'true' : 'false' }}">
                                    <span class="statement-number">{{ $statement['id'] }}.</span>
                                    <span class="statement-text">{{ $statement['text'] }}</span>
                                    <span class="statement-badge answer-badge {{ $statement['is_true'] ? 'true' : 'false' }}">
                                        {{ $statement['is_true'] ? 'ВЕРНО' : 'НЕВЕРНО' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach

    <div style="text-align: center; padding: 20px; color: #666; font-size: 14px;">
        Всего утверждений: {{ $totalStatements }} (верных: {{ $trueStatements }}, неверных: {{ $totalStatements - $trueStatements }})
    </div>

    <script>
        let answersVisible = true;

        function filterStatements(filter) {
            const items = document.querySelectorAll('.statement-item');
            const buttons = document.querySelectorAll('.toggle-btn');

            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            items.forEach(item => {
                const isTrue = item.dataset.isTrue === 'true';

                if (filter === 'all') {
                    item.classList.remove('hidden');
                } else if (filter === 'true') {
                    item.classList.toggle('hidden', !isTrue);
                } else if (filter === 'false') {
                    item.classList.toggle('hidden', isTrue);
                }
            });
        }

        function toggleAnswers() {
            answersVisible = !answersVisible;
            const badges = document.querySelectorAll('.answer-badge');
            const items = document.querySelectorAll('.statement-item');

            badges.forEach(badge => {
                badge.style.display = answersVisible ? '' : 'none';
            });

            items.forEach(item => {
                if (!answersVisible) {
                    item.classList.remove('is-true', 'is-false');
                    item.style.borderLeftColor = '#ddd';
                    item.style.background = '#fff';
                } else {
                    const isTrue = item.dataset.isTrue === 'true';
                    item.classList.add(isTrue ? 'is-true' : 'is-false');
                    item.style.borderLeftColor = '';
                    item.style.background = '';
                }
            });
        }
    </script>
</body>
</html>
