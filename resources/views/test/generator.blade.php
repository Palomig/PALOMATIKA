<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Генератор тестов ОГЭ - PALOMATIKA</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f0f1a;
            color: #e0e0e0;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #888;
            margin-bottom: 30px;
        }

        /* Card */
        .card {
            background: #1a1a2e;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .card h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #fff;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
            color: #aaa;
        }

        .form-group select,
        .form-group input[type="number"] {
            width: 100%;
            padding: 12px 16px;
            background: #252542;
            border: 1px solid #374151;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #ff6b6b;
        }

        /* Topics Grid */
        .topics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
        }

        .topic-card {
            background: #252542;
            border: 2px solid #374151;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .topic-card:hover {
            border-color: #60a5fa;
            background: #2d2d4a;
        }

        .topic-card.selected {
            border-color: #ff6b6b;
            background: rgba(255, 107, 107, 0.15);
        }

        .topic-card input {
            display: none;
        }

        .topic-number {
            font-size: 28px;
            font-weight: 700;
            color: #ff6b6b;
            margin-bottom: 5px;
        }

        .topic-title {
            font-size: 12px;
            color: #888;
            margin-bottom: 8px;
            min-height: 30px;
        }

        .topic-count {
            font-size: 11px;
            color: #4ade80;
            background: rgba(74, 222, 128, 0.15);
            padding: 3px 8px;
            border-radius: 10px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background: #ff6b6b;
            color: #fff;
            width: 100%;
        }

        .btn-primary:hover {
            background: #ff5252;
        }

        .btn-primary:disabled {
            background: #666;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #374151;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        /* Links */
        .nav-links {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .nav-links a {
            color: #60a5fa;
            text-decoration: none;
            font-size: 14px;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-info {
            background: rgba(96, 165, 250, 0.15);
            border: 1px solid #60a5fa;
            color: #60a5fa;
        }

        /* Settings row */
        .settings-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 600px) {
            .settings-row {
                grid-template-columns: 1fr;
            }
            .topics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="{{ route('test.pdf.index') }}">← PDF Парсер</a>
            <a href="{{ route('test.topic06') }}">Задание 06</a>
            <a href="{{ route('test.topic07') }}">Задание 07</a>
        </div>

        <h1>🎲 Генератор тестов ОГЭ</h1>
        <p class="subtitle">Выберите темы для генерации случайного теста</p>

        <form action="{{ route('test.generator.generate') }}" method="POST">
            @csrf

            <div class="card">
                <h2>1. Выберите темы</h2>

                <div class="topics-grid">
                    @foreach($allTopics as $topic)
                        <label class="topic-card" data-topic="{{ $topic['topic_id'] }}">
                            <input type="checkbox" name="topics[]" value="{{ $topic['topic_id'] }}">
                            <div class="topic-number">{{ $topic['topic_id'] }}</div>
                            <div class="topic-title">{{ Str::limit($topic['title'] ?? 'Тема', 30) }}</div>
                            @if(isset($topic['tasks_count']))
                                <span class="topic-count">{{ $topic['tasks_count'] }} задач</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <h2>2. Настройки теста</h2>

                <div class="settings-row">
                    <div class="form-group">
                        <label for="tasks_per_topic">Заданий из каждой темы</label>
                        <input type="number" id="tasks_per_topic" name="tasks_per_topic" value="1" min="1" max="10">
                    </div>
                    <div class="form-group">
                        <label>Выбрано тем</label>
                        <div style="padding: 12px 16px; background: #252542; border-radius: 8px; font-size: 20px; font-weight: 600; color: #ff6b6b;">
                            <span id="selectedCount">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="generateBtn" disabled>
                🎯 Сгенерировать тест
            </button>
        </form>

        <div class="alert alert-info" style="margin-top: 30px;">
            <strong>ℹ️ Как это работает:</strong><br>
            Система случайно выберет указанное количество заданий из каждой выбранной темы
            и составит из них тест. Каждая генерация даёт уникальный набор задач.
        </div>
    </div>

    <script>
        const topicCards = document.querySelectorAll('.topic-card');
        const selectedCount = document.getElementById('selectedCount');
        const generateBtn = document.getElementById('generateBtn');

        topicCards.forEach(card => {
            card.addEventListener('click', function() {
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('selected', checkbox.checked);
                updateCount();
            });
        });

        function updateCount() {
            const checked = document.querySelectorAll('.topic-card input:checked').length;
            selectedCount.textContent = checked;
            generateBtn.disabled = checked === 0;
        }
    </script>
</body>
</html>
