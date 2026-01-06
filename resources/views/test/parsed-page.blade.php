<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['title'] ?? "Задание {$topicId}" }} - Результат парсинга</title>

    <!-- KaTeX for math rendering -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathInElement(document.body, {delimiters: [{left: '$$', right: '$$', display: true}, {left: '$', right: '$', display: false}]});"></script>

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
            font-size: 18px;
            line-height: 1.6;
            padding: 40px 60px;
            max-width: 1200px;
            margin: 0 auto;
            background: #fefefe;
            color: #1a1a1a;
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .page-header h1 {
            font-size: 28px;
            color: #2c3e50;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background: #ff6b6b;
            color: #fff;
        }

        .btn-primary:hover {
            background: #ff5252;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #ff6b6b;
            font-family: 'Inter', sans-serif;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            font-family: 'Inter', sans-serif;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 0;
        }

        .tab {
            padding: 12px 24px;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }

        .tab:hover {
            color: #333;
        }

        .tab.active {
            color: #ff6b6b;
            border-bottom-color: #ff6b6b;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Images Grid */
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .image-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }

        .image-card img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .image-card .filename {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
            font-family: monospace;
        }

        /* Text Preview */
        .text-preview {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            max-height: 600px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            white-space: pre-wrap;
            line-height: 1.4;
        }

        /* Blocks */
        .block {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .block h3 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        /* Zadaniya list */
        .zadaniya-list {
            list-style: none;
        }

        .zadaniya-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .zadaniya-list li:last-child {
            border-bottom: none;
        }

        .zadanie-num {
            font-weight: 700;
            color: #ff6b6b;
            margin-right: 10px;
        }

        /* KaTeX */
        .katex {
            font-size: 1.1em !important;
        }

        /* Info box */
        .info-box {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
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
        }

        /* Responsive */
        @media (max-width: 900px) {
            body {
                padding: 20px;
                font-size: 16px;
            }
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="page-header">
    <h1>{{ $topicId }}. {{ $data['title'] ?? 'Результат парсинга' }}</h1>
    <div class="header-actions">
        <a href="{{ route('test.pdf.index') }}" class="btn btn-secondary">← Назад</a>
        <a href="{{ route('test.pdf.download-json', $topicId) }}" class="btn btn-secondary">📥 JSON</a>
    </div>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-value">{{ count($data['blocks'] ?? []) }}</div>
        <div class="stat-label">Блоков</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ count($data['zadaniya'] ?? []) }}</div>
        <div class="stat-label">Заданий</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $data['images_count'] ?? 0 }}</div>
        <div class="stat-label">Изображений</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ number_format(strlen($data['text'] ?? '') / 1024, 1) }}</div>
        <div class="stat-label">КБ текста</div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <div class="tab active" data-tab="images">📷 Изображения</div>
    <div class="tab" data-tab="text">📄 Текст</div>
    <div class="tab" data-tab="structure">🏗️ Структура</div>
</div>

<!-- Tab: Images -->
<div class="tab-content active" id="tab-images">
    @if(!empty($data['images']))
        <div class="images-grid">
            @foreach($data['images'] as $image)
                <div class="image-card">
                    <img src="/images/tasks/{{ $topicId }}/{{ $image }}" alt="{{ $image }}">
                    <div class="filename">{{ $image }}</div>
                </div>
            @endforeach
        </div>
    @else
        <p style="text-align: center; color: #888; padding: 40px;">Изображения не найдены</p>
    @endif
</div>

<!-- Tab: Text -->
<div class="tab-content" id="tab-text">
    <div class="text-preview">{{ $data['text'] ?? 'Текст не извлечён' }}</div>
</div>

<!-- Tab: Structure -->
<div class="tab-content" id="tab-structure">
    @if(!empty($data['blocks']))
        <h3 style="margin-bottom: 15px;">Найденные блоки:</h3>
        @foreach($data['blocks'] as $block)
            <div class="block">
                <h3>Блок {{ $block['number'] }}. {{ $block['title'] }}</h3>
                <p style="color: #888; font-size: 14px;">Строка {{ $block['line'] }}</p>
            </div>
        @endforeach
    @endif

    @if(!empty($data['zadaniya']))
        <h3 style="margin: 20px 0 15px;">Найденные задания ({{ count($data['zadaniya']) }}):</h3>
        <ul class="zadaniya-list">
            @foreach($data['zadaniya'] as $z)
                <li>
                    <span class="zadanie-num">Задание {{ $z['number'] }}.</span>
                    {{ Str::limit($z['instruction'], 100) }}
                </li>
            @endforeach
        </ul>
    @endif
</div>

<!-- Info box -->
<div class="info-box">
    <h4>📊 Информация о парсинге</h4>
    <p><strong>PDF файл:</strong> <code>{{ $data['pdf_filename'] ?? 'unknown' }}</code></p>
    <p><strong>Дата парсинга:</strong> {{ $data['created_at'] ?? 'неизвестно' }}</p>
    <p><strong>JSON данные:</strong> <code>storage/app/parsed/topic_{{ $topicId }}.json</code></p>
    <p><strong>Изображения:</strong> <code>public/images/tasks/{{ $topicId }}/</code></p>
</div>

<script>
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active from all tabs and contents
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            // Add active to clicked tab and corresponding content
            tab.classList.add('active');
            document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
        });
    });
</script>

</body>
</html>
