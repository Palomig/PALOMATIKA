<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Генератор вариантов ОГЭ - PALOMATIKA</title>

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
            max-width: 700px;
            margin: 0 auto;
        }

        h1 {
            font-size: 32px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
            text-align: center;
        }

        .subtitle {
            color: #888;
            margin-bottom: 40px;
            text-align: center;
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

        /* Info box */
        .info-box {
            background: rgba(96, 165, 250, 0.1);
            border: 1px solid rgba(96, 165, 250, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-box h3 {
            color: #60a5fa;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .info-box p {
            color: #aaa;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Topics preview */
        .topics-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .topic-chip {
            background: #252542;
            border: 1px solid #374151;
            border-radius: 8px;
            padding: 12px 8px;
            text-align: center;
            font-size: 12px;
        }

        .topic-chip .num {
            font-size: 24px;
            font-weight: 700;
            color: #ff6b6b;
            display: block;
            margin-bottom: 4px;
        }

        .topic-chip .title {
            color: #888;
            font-size: 10px;
            line-height: 1.3;
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

        .form-group input[type="number"] {
            width: 100%;
            padding: 14px 18px;
            background: #252542;
            border: 1px solid #374151;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ff6b6b;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            color: #fff;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
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
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .nav-links a {
            color: #60a5fa;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .nav-links a:hover {
            background: rgba(96, 165, 250, 0.15);
        }

        /* Features */
        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .feature {
            text-align: center;
            padding: 15px;
            background: #252542;
            border-radius: 10px;
        }

        .feature-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .feature-text {
            font-size: 12px;
            color: #888;
        }

        @media (max-width: 600px) {
            .features {
                grid-template-columns: 1fr;
            }

            .topics-preview {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="{{ route('test.index') }}">← Все задания</a>
            <a href="{{ route('test.generator') }}">Кастомный тест</a>
        </div>

        <h1>📝 Вариант ОГЭ</h1>
        <p class="subtitle">Генератор тренировочных вариантов (задания 6–19)</p>

        <div class="info-box">
            <h3>ℹ️ Что это?</h3>
            <p>
                Генератор создаёт полноценный тренировочный вариант ОГЭ по математике,
                включающий по одному случайному заданию из каждой темы 6–19.
                Каждый вариант уникален — можно тренироваться бесконечно!
            </p>
        </div>

        <div class="card">
            <h2>Темы в варианте</h2>

            <div class="topics-preview">
                <div class="topic-chip">
                    <span class="num">6</span>
                    <span class="title">Вычисления</span>
                </div>
                <div class="topic-chip">
                    <span class="num">7</span>
                    <span class="title">Числа, прямая</span>
                </div>
                <div class="topic-chip">
                    <span class="num">8</span>
                    <span class="title">Корни, степени</span>
                </div>
                <div class="topic-chip">
                    <span class="num">9</span>
                    <span class="title">Уравнения</span>
                </div>
                <div class="topic-chip">
                    <span class="num">10</span>
                    <span class="title">Вероятность</span>
                </div>
                <div class="topic-chip">
                    <span class="num">11</span>
                    <span class="title">Графики</span>
                </div>
                <div class="topic-chip">
                    <span class="num">12</span>
                    <span class="title">Формулы</span>
                </div>
                <div class="topic-chip">
                    <span class="num">13</span>
                    <span class="title">Неравенства</span>
                </div>
                <div class="topic-chip">
                    <span class="num">14</span>
                    <span class="title">Прогрессии</span>
                </div>
                <div class="topic-chip">
                    <span class="num">15</span>
                    <span class="title">Треугольники</span>
                </div>
                <div class="topic-chip">
                    <span class="num">16</span>
                    <span class="title">Окружность</span>
                </div>
                <div class="topic-chip">
                    <span class="num">17</span>
                    <span class="title">Четырёхуг.</span>
                </div>
                <div class="topic-chip">
                    <span class="num">18</span>
                    <span class="title">Клетки</span>
                </div>
                <div class="topic-chip">
                    <span class="num">19</span>
                    <span class="title">Утверждения</span>
                </div>
            </div>
        </div>

        <form action="{{ route('test.oge.generate') }}" method="POST">
            @csrf

            <div class="card">
                <h2>Настройки</h2>

                <div class="form-group">
                    <label for="variant_number">Номер варианта (необязательно)</label>
                    <input type="number" id="variant_number" name="variant_number" min="1" max="999" placeholder="Случайный номер">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                🎯 Сгенерировать вариант
            </button>
        </form>

        <div class="features">
            <div class="feature">
                <div class="feature-icon">🎲</div>
                <div class="feature-text">Случайные задания</div>
            </div>
            <div class="feature">
                <div class="feature-icon">🖨️</div>
                <div class="feature-text">Готов к печати</div>
            </div>
            <div class="feature">
                <div class="feature-icon">📱</div>
                <div class="feature-text">На любом устройстве</div>
            </div>
        </div>
    </div>
</body>
</html>
