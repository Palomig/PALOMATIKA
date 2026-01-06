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
            border: none;
            cursor: pointer;
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

        /* Block styles (like topic07) */
        .block-section {
            margin-bottom: 50px;
            padding-bottom: 40px;
            border-bottom: 2px solid #e0e0e0;
        }

        .block-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
            font-style: italic;
        }

        .block-title {
            text-align: center;
            font-weight: 700;
            font-size: 24px;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .block-subtitle {
            text-align: center;
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 30px;
            color: #34495e;
        }

        /* Zadanie styles */
        .zadanie {
            margin-bottom: 35px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
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

        .zadanie-content {
            padding: 10px 0;
        }

        /* Task row */
        .task-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            padding: 12px;
            background: #fff;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }

        .task-number {
            min-width: 35px;
            font-weight: 600;
            color: #ff6b6b;
            flex-shrink: 0;
        }

        .task-content {
            flex: 1;
        }

        .task-text {
            margin-bottom: 10px;
            line-height: 1.5;
        }

        /* Task images */
        .task-image {
            margin: 10px 0;
            max-width: 400px;
        }

        .task-image img {
            max-width: 100%;
            height: auto;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: #fff;
        }

        /* Options */
        .task-options {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }

        .option {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 12px;
            background: #e9ecef;
            border-radius: 4px;
        }

        .option-num {
            color: #666;
            font-size: 14px;
        }

        /* Images Grid */
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
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

        /* Edit mode notice */
        .edit-notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #856404;
        }

        .edit-notice strong {
            display: block;
            margin-bottom: 5px;
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
            .images-grid {
                grid-template-columns: repeat(2, 1fr);
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
    <div class="tab active" data-tab="preview">📝 Предпросмотр</div>
    <div class="tab" data-tab="images">📷 Изображения</div>
    <div class="tab" data-tab="text">📄 Текст</div>
</div>

<!-- Tab: Preview (formatted like topic07) -->
<div class="tab-content active" id="tab-preview">
    @if(!empty($data['structured_blocks']))
        {{-- If we have fully structured data (like topic07) --}}
        @foreach($data['structured_blocks'] as $block)
            <div class="block-section">
                <div class="block-header">
                    <span>Е. А. Ширяева</span>
                    <span>Задачник ОГЭ 2026 (тренажер)</span>
                </div>

                <div class="block-title">{{ $topicId }}. {{ $data['title'] ?? 'Задание' }}</div>
                <div class="block-subtitle">Блок {{ $block['number'] }}. {{ $block['title'] }}</div>

                @foreach($block['zadaniya'] as $zadanie)
                    <div class="zadanie">
                        <p class="zadanie-header">Задание {{ $zadanie['number'] }}. <span>{{ $zadanie['instruction'] ?? '' }}</span></p>

                        <div class="zadanie-content">
                            @if(isset($zadanie['tasks']))
                                @foreach($zadanie['tasks'] as $task)
                                    <div class="task-row">
                                        <span class="task-number">{{ $task['id'] ?? $loop->iteration }}</span>
                                        <div class="task-content">
                                            @if(isset($task['image']))
                                                <div class="task-image">
                                                    <img src="/images/tasks/{{ $topicId }}/{{ $task['image'] }}" alt="Задание">
                                                </div>
                                            @endif

                                            @if(isset($task['expression']))
                                                <div class="task-text">${{ $task['expression'] }}$</div>
                                            @endif

                                            @if(isset($task['options']))
                                                <div class="task-options">
                                                    @foreach($task['options'] as $i => $option)
                                                        <span class="option">
                                                            <span class="option-num">{{ $i + 1 }})</span>
                                                            <span>{{ $option }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @elseif(isset($zadanie['options']))
                                <div class="task-options">
                                    @foreach($zadanie['options'] as $i => $option)
                                        <span class="option">
                                            <span class="option-num">{{ $i + 1 }})</span>
                                            <span>{{ $option }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    @else
        {{-- Basic parsed data - show blocks and zadaniya with images --}}
        <div class="edit-notice">
            <strong>Автоматический парсинг</strong>
            Данные извлечены автоматически из PDF. Для полноценного использования заданий в тестах
            необходимо структурировать данные (добавить типы заданий, варианты ответов и связать изображения с задачами).
        </div>

        @php
            $images = $data['images'] ?? [];
            $imageIndex = 0;
            $blocksData = $data['blocks'] ?? [];
            $zadaniyaData = $data['zadaniya'] ?? [];
        @endphp

        @if(count($blocksData) > 0)
            @foreach($blocksData as $blockIdx => $block)
                <div class="block-section">
                    <div class="block-header">
                        <span>Е. А. Ширяева</span>
                        <span>Задачник ОГЭ 2026 (тренажер)</span>
                    </div>

                    <div class="block-title">{{ $topicId }}. {{ $data['title'] ?? 'Задание' }}</div>
                    <div class="block-subtitle">Блок {{ $block['number'] }}. {{ $block['title'] }}</div>

                    {{-- Find zadaniya for this block --}}
                    @php
                        // Calculate line ranges for this block
                        $blockStart = $block['line'] ?? 0;
                        $nextBlockLine = isset($blocksData[$blockIdx + 1]) ? $blocksData[$blockIdx + 1]['line'] : PHP_INT_MAX;

                        $blockZadaniya = array_filter($zadaniyaData, function($z) use ($blockStart, $nextBlockLine) {
                            $zLine = $z['line'] ?? 0;
                            return $zLine >= $blockStart && $zLine < $nextBlockLine;
                        });
                    @endphp

                    @forelse($blockZadaniya as $zadanie)
                        <div class="zadanie">
                            <p class="zadanie-header">Задание {{ $zadanie['number'] }}. <span>{{ $zadanie['instruction'] ?? '' }}</span></p>

                            <div class="zadanie-content">
                                {{-- Show a few images that might belong to this zadanie --}}
                                @if($imageIndex < count($images))
                                    <div class="task-row">
                                        <span class="task-number">1</span>
                                        <div class="task-content">
                                            <div class="task-image">
                                                <img src="/images/tasks/{{ $topicId }}/{{ $images[$imageIndex] }}" alt="Изображение задания">
                                            </div>
                                            <p class="task-text" style="color: #888; font-size: 14px;">
                                                Изображение: {{ $images[$imageIndex] }}
                                            </p>
                                        </div>
                                    </div>
                                    @php $imageIndex++; @endphp
                                @endif

                                @if(isset($zadanie['content']))
                                    <div class="task-text">{{ Str::limit($zadanie['content'], 500) }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p style="color: #888; padding: 20px;">Задания для этого блока не найдены</p>
                    @endforelse
                </div>
            @endforeach
        @else
            {{-- No blocks found, show zadaniya directly --}}
            @if(count($zadaniyaData) > 0)
                <div class="block-section">
                    <div class="block-title">{{ $topicId }}. {{ $data['title'] ?? 'Задание' }}</div>

                    @foreach($zadaniyaData as $zadanie)
                        <div class="zadanie">
                            <p class="zadanie-header">Задание {{ $zadanie['number'] }}. <span>{{ $zadanie['instruction'] ?? '' }}</span></p>

                            <div class="zadanie-content">
                                @if($imageIndex < count($images))
                                    <div class="task-row">
                                        <span class="task-number">1</span>
                                        <div class="task-content">
                                            <div class="task-image">
                                                <img src="/images/tasks/{{ $topicId }}/{{ $images[$imageIndex] }}" alt="Изображение задания">
                                            </div>
                                        </div>
                                    </div>
                                    @php $imageIndex++; @endphp
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- No structure found, show all images --}}
                <div class="block-section">
                    <div class="block-title">{{ $topicId }}. {{ $data['title'] ?? 'Задание' }}</div>
                    <p style="color: #888; margin-bottom: 20px;">Структура не определена. Показаны все извлечённые изображения.</p>

                    @foreach($images as $index => $image)
                        <div class="zadanie">
                            <p class="zadanie-header">Изображение {{ $index + 1 }}</p>
                            <div class="task-image">
                                <img src="/images/tasks/{{ $topicId }}/{{ $image }}" alt="{{ $image }}">
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    @endif
</div>

<!-- Tab: Images -->
<div class="tab-content" id="tab-images">
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

<!-- Info box -->
<div class="info-box">
    <h4>📊 Информация о парсинге</h4>
    <p><strong>PDF файл:</strong> <code>{{ $data['pdf_filename'] ?? 'unknown' }}</code></p>
    <p><strong>Дата парсинга:</strong> {{ $data['created_at'] ?? 'неизвестно' }}</p>
    <p><strong>JSON данные:</strong> <code>storage/app/parsed/topic_{{ $topicId }}.json</code></p>
    <p><strong>Изображения:</strong> <code>public/images/tasks/{{ $topicId }}/</code></p>
    <p><strong>Для генерации тестов:</strong> Необходимо добавить <code>structured_blocks</code> в JSON с типами заданий и вариантами ответов.</p>
</div>

<script>
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
        });
    });
</script>

</body>
</html>
