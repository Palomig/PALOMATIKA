<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Palomatika — Подготовка к ОГЭ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Russo+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #111318;
            --surface: #1c1f27;
            --border: #2a2e3a;
            --text: #eef0f6;
            --muted: #6b7280;
            --accent: #4f8ef7;
            --display: 'Russo One', sans-serif;
            --body: 'Nunito', sans-serif;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: var(--body);
            color: var(--text);
            background:
                radial-gradient(circle at top, rgba(79, 142, 247, 0.22), transparent 35%),
                linear-gradient(180deg, #171a22 0%, var(--bg) 100%);
        }

        .shell {
            width: 100%;
            max-width: 420px;
            padding: 28px;
            border-radius: 28px;
            background: rgba(28, 31, 39, 0.92);
            border: 1px solid var(--border);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
            text-align: center;
        }

        .brand {
            font-family: var(--display);
            font-size: 32px;
            color: var(--accent);
            text-transform: lowercase;
        }

        .subtitle {
            margin: 10px 0 28px;
            color: var(--muted);
            line-height: 1.55;
            font-size: 14px;
        }

        .cta {
            display: block;
            width: 100%;
            padding: 18px 20px;
            border-radius: 18px;
            text-decoration: none;
            font-family: var(--display);
            font-size: 16px;
            transition: transform 0.15s ease, filter 0.15s ease;
        }

        .cta + .cta {
            margin-top: 12px;
        }

        .cta:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
        }

        .cta-student {
            background: var(--accent);
            color: #fff;
        }

        .cta-teacher {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .links {
            margin-top: 24px;
            font-size: 12px;
            color: var(--muted);
        }

        .links a {
            color: inherit;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <main class="shell">
        <div class="brand">palomatika</div>
        <p class="subtitle">Подготовка к ОГЭ по математике в формате отдельных приложений для ученика и репетитора.</p>

        <a class="cta cta-student" href="https://student.palomatika.ru">Я ученик</a>
        <a class="cta cta-teacher" href="https://teacher.palomatika.ru">Я репетитор</a>

    </main>
</body>
</html>
