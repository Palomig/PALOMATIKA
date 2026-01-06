<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PDF Парсер - PALOMATIKA</title>

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
            max-width: 1000px;
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

        /* Upload Section */
        .upload-section {
            background: #1a1a2e;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .upload-section h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #fff;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #aaa;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* File Upload */
        .file-upload {
            border: 2px dashed #374151;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #252542;
        }

        .file-upload:hover {
            border-color: #ff6b6b;
            background: #2d2d4a;
        }

        .file-upload.dragover {
            border-color: #4ade80;
            background: rgba(74, 222, 128, 0.1);
        }

        .file-upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .file-upload p {
            color: #888;
            margin-bottom: 10px;
        }

        .file-upload .file-name {
            color: #4ade80;
            font-weight: 500;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-primary {
            background: #ff6b6b;
            color: #fff;
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

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        /* Progress */
        .progress-section {
            display: none;
            margin-top: 20px;
        }

        .progress-section.active {
            display: block;
        }

        .progress-bar {
            height: 8px;
            background: #374151;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6b6b, #ff8e53);
            width: 0%;
            transition: width 0.3s;
        }

        .progress-text {
            font-size: 14px;
            color: #888;
        }

        /* Results Section */
        .results-section {
            background: #1a1a2e;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            display: none;
        }

        .results-section.active {
            display: block;
        }

        .results-section h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #fff;
        }

        .result-card {
            background: #252542;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .result-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #4ade80;
        }

        .result-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: #1a1a2e;
            border-radius: 8px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #ff6b6b;
        }

        .stat-label {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }

        /* Preview */
        .preview-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: #60a5fa;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .text-preview {
            background: #1a1a2e;
            border-radius: 8px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            color: #aaa;
            display: none;
        }

        .text-preview.active {
            display: block;
        }

        /* Image Grid */
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
            background: #1a1a2e;
            border-radius: 8px;
        }

        .image-item {
            background: #fff;
            border-radius: 4px;
            padding: 5px;
            text-align: center;
        }

        .image-item img {
            max-width: 100%;
            height: auto;
        }

        .image-item span {
            display: block;
            font-size: 10px;
            color: #333;
            margin-top: 4px;
        }

        /* Parsed Pages List */
        .pages-section {
            background: #1a1a2e;
            border-radius: 16px;
            padding: 30px;
        }

        .pages-section h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #fff;
        }

        .page-list {
            list-style: none;
        }

        .page-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: #252542;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .page-list li:hover {
            background: #2d2d4a;
        }

        .page-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .page-title {
            font-weight: 500;
            color: #fff;
        }

        .page-meta {
            font-size: 12px;
            color: #888;
        }

        .page-actions {
            display: flex;
            gap: 8px;
        }

        .page-actions a {
            padding: 8px 16px;
            background: #374151;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            transition: background 0.2s;
        }

        .page-actions a:hover {
            background: #4b5563;
        }

        .page-actions a.primary {
            background: #ff6b6b;
        }

        .page-actions a.primary:hover {
            background: #ff5252;
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(74, 222, 128, 0.15);
            border: 1px solid #4ade80;
            color: #4ade80;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .result-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 PDF Парсер</h1>
        <p class="subtitle">Загрузите PDF файл с заданиями ОГЭ для автоматического парсинга</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <!-- Upload Section -->
        <div class="upload-section">
            <h2>Загрузить PDF</h2>

            <form id="uploadForm" action="{{ route('test.pdf.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-row">
                    <div class="form-group">
                        <label for="topic_id">Номер задания (Topic ID)</label>
                        <input type="text" id="topic_id" name="topic_id" placeholder="07" required pattern="[0-9]{1,2}">
                    </div>
                    <div class="form-group">
                        <label for="title">Название темы</label>
                        <input type="text" id="title" name="title" placeholder="Числа, координатная прямая">
                    </div>
                </div>

                <div class="form-group">
                    <label>PDF Файл</label>
                    <div class="file-upload" id="dropZone">
                        <div class="file-upload-icon">📁</div>
                        <p>Перетащите PDF файл сюда или <strong>нажмите для выбора</strong></p>
                        <p class="file-name" id="fileName"></p>
                        <input type="file" id="pdfFile" name="pdf_file" accept=".pdf" required>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary" id="parseBtn">
                        🔍 Распарсить PDF
                    </button>
                </div>
            </form>

            <div class="progress-section" id="progressSection">
                <div class="progress-bar">
                    <div class="progress-bar-fill" id="progressFill"></div>
                </div>
                <p class="progress-text" id="progressText">Загрузка файла...</p>
            </div>
        </div>

        <!-- Results Section -->
        <div class="results-section" id="resultsSection">
            <h2>Результат парсинга</h2>
            <div id="resultsContent"></div>
        </div>

        <!-- Existing Parsed Pages -->
        <div class="pages-section">
            <h2>Созданные страницы</h2>

            @if(count($parsedPages) > 0)
                <ul class="page-list">
                    @foreach($parsedPages as $page)
                        <li>
                            <div class="page-info">
                                <div class="page-icon">{{ $page['topic_id'] }}</div>
                                <div>
                                    <div class="page-title">{{ $page['title'] }}</div>
                                    <div class="page-meta">
                                        {{ $page['images_count'] }} изображений •
                                        {{ $page['blocks_count'] }} блоков •
                                        {{ $page['created_at'] }}
                                    </div>
                                </div>
                            </div>
                            <div class="page-actions">
                                <a href="{{ route('test.parsed', $page['topic_id']) }}" class="primary">Открыть</a>
                                <a href="{{ route('test.pdf.download-json', $page['topic_id']) }}">JSON</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p style="color: #888; text-align: center; padding: 40px;">
                    Пока нет созданных страниц. Загрузите PDF для начала.
                </p>
            @endif
        </div>
    </div>

    <script>
        // File upload handling
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('pdfFile');
        const fileName = document.getElementById('fileName');
        const uploadForm = document.getElementById('uploadForm');
        const progressSection = document.getElementById('progressSection');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        const parseBtn = document.getElementById('parseBtn');

        // Click to upload
        dropZone.addEventListener('click', () => fileInput.click());

        // File selected
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                fileName.textContent = '✅ ' + e.target.files[0].name;
            }
        });

        // Drag and drop
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');

            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                fileName.textContent = '✅ ' + e.dataTransfer.files[0].name;
            }
        });

        // Form submit with progress
        uploadForm.addEventListener('submit', function(e) {
            parseBtn.disabled = true;
            progressSection.classList.add('active');
            progressFill.style.width = '30%';
            progressText.textContent = 'Загрузка PDF файла...';

            // Simulate progress (actual upload is synchronous form submit)
            setTimeout(() => {
                progressFill.style.width = '60%';
                progressText.textContent = 'Извлечение текста и изображений...';
            }, 500);

            setTimeout(() => {
                progressFill.style.width = '90%';
                progressText.textContent = 'Генерация страницы...';
            }, 1000);
        });
    </script>
</body>
</html>
