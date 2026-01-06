<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $topicId }}. {{ $data['title'] ?? 'Результат парсинга' }}</title>

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
            font-size: 18px;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .zadanie-header span {
            font-weight: 400;
        }

        /* Task with numbered variants */
        .task-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .task-number {
            min-width: 35px;
            font-weight: 600;
            color: #555;
            flex-shrink: 0;
        }

        .task-content {
            flex: 1;
        }

        .task-expression {
            font-weight: 500;
            margin-bottom: 6px;
        }

        .task-options {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .option {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .option-num {
            color: #666;
            font-size: 16px;
        }

        /* Simple choice (single question with options) */
        .simple-choice {
            margin: 15px 0;
        }

        .simple-choice .options {
            display: flex;
            gap: 25px;
            margin-top: 10px;
        }

        /* Grid layout for multi-task */
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .tasks-grid.cols-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        /* Task images */
        .task-image {
            margin: 8px 0;
        }

        .task-image img {
            max-width: 100%;
            height: auto;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: #fff;
        }

        .zadanie-image {
            margin: 15px 0;
            text-align: center;
        }

        .zadanie-image img {
            max-width: 500px;
            height: auto;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: #fff;
        }

        /* KaTeX font size */
        .katex {
            font-size: 1.1em !important;
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
        }

        .nav-bar a {
            color: #60a5fa;
            text-decoration: none;
            font-size: 14px;
        }

        .nav-bar a:hover {
            text-decoration: underline;
        }

        .nav-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
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

        /* Responsive */
        @media (max-width: 900px) {
            body {
                padding: 20px;
                font-size: 16px;
            }
            .tasks-grid {
                grid-template-columns: 1fr;
            }
            .title {
                font-size: 20px;
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
        }
    </style>
</head>
<body>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="nav-bar">
    <a href="{{ route('test.pdf.index') }}">← Назад к парсеру</a>
    <div class="nav-actions">
        <a href="{{ route('test.pdf.download-json', $topicId) }}" class="btn btn-secondary">📥 Скачать JSON</a>
        <button onclick="window.print()" class="btn btn-secondary">🖨️ Печать</button>
    </div>
</div>

@if(!empty($data['structured_blocks']))
    @foreach($data['structured_blocks'] as $block)
    <div class="page">
        <!-- Page Header -->
        <div class="header">
            <span>Е. А. Ширяева</span>
            <span>Задачник ОГЭ 2026 (тренажер)</span>
        </div>

        <!-- Title -->
        <div class="title">{{ $topicId }}. {{ $data['title'] ?? 'Задание' }}</div>
        <div class="subtitle">Блок {{ $block['number'] }}. {{ $block['title'] }}</div>

        @foreach($block['zadaniya'] ?? [] as $zadanie)
            <div class="zadanie">
                <p class="zadanie-header">Задание {{ $zadanie['number'] }}. <span>{{ $zadanie['instruction'] ?? '' }}</span></p>

                @if(isset($zadanie['image']))
                    <div class="zadanie-image">
                        <img src="/images/tasks/{{ $topicId }}/{{ $zadanie['image'] }}" alt="Задание">
                    </div>
                @endif

                @if(!empty($zadanie['tasks']))
                    {{-- Tasks with variants --}}
                    @php
                        $type = $zadanie['type'] ?? 'choice';
                        $useGrid = in_array($type, ['interval_choice', 'sqrt_interval', 'expression']);
                    @endphp

                    @if($useGrid)
                    <div class="tasks-grid">
                    @endif

                    @foreach($zadanie['tasks'] as $task)
                        <div class="task-row">
                            <span class="task-number">{{ $task['id'] ?? $loop->iteration }}</span>
                            <div class="task-content">
                                @if(isset($task['image']))
                                    <div class="task-image">
                                        <img src="/images/tasks/{{ $topicId }}/{{ $task['image'] }}" alt="Задание {{ $task['id'] ?? '' }}">
                                    </div>
                                @endif

                                @if(isset($task['expression']))
                                    <div class="task-expression">${{ $task['expression'] }}$</div>
                                @endif

                                @if(!empty($task['options']))
                                    <div class="task-options">
                                        @foreach($task['options'] as $i => $option)
                                            <span class="option">
                                                <span class="option-num">{{ $i + 1 }})</span>
                                                @if(str_contains($option, '\\') || str_contains($option, '_') || str_contains($option, '^'))
                                                    <span>${{ $option }}$</span>
                                                @else
                                                    <span>{{ $option }}</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if($useGrid)
                    </div>
                    @endif

                @elseif(!empty($zadanie['options']))
                    {{-- Simple choice with options --}}
                    <div class="simple-choice">
                        <div class="options">
                            @foreach($zadanie['options'] as $i => $option)
                                <span class="option">
                                    <span class="option-num">{{ $i + 1 }})</span>
                                    @if(str_contains($option, '\\') || str_contains($option, '_') || str_contains($option, '^'))
                                        <span>${{ $option }}$</span>
                                    @else
                                        <span>{{ $option }}</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    @endforeach
@else
    {{-- No structured blocks - show message --}}
    <div class="page">
        <div class="title">{{ $topicId }}. {{ $data['title'] ?? 'Задание' }}</div>
        <p style="text-align: center; color: #888; padding: 40px;">
            Структурированные данные не найдены. Попробуйте загрузить PDF заново.
        </p>

        @if(!empty($data['images']))
            <h3 style="margin: 20px 0;">Извлечённые изображения:</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                @foreach($data['images'] as $image)
                    <div style="text-align: center;">
                        <img src="/images/tasks/{{ $topicId }}/{{ $image }}" alt="{{ $image }}" style="max-width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                        <div style="font-size: 11px; color: #888; margin-top: 5px;">{{ $image }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif

<!-- Info box about parsing -->
<div class="info-box">
    <h4>📊 Информация о парсинге</h4>
    <p><strong>Источник:</strong> <code>{{ $data['pdf_filename'] ?? 'unknown' }}</code></p>
    <p><strong>Дата:</strong> {{ $data['created_at'] ?? 'неизвестно' }}</p>
    <p><strong>Блоков:</strong> {{ count($data['structured_blocks'] ?? []) }}</p>
    <p><strong>Изображений:</strong> {{ $data['images_count'] ?? 0 }}</p>
    <p><strong>JSON:</strong> <code>storage/app/parsed/topic_{{ $topicId }}.json</code></p>
</div>

</body>
</html>
