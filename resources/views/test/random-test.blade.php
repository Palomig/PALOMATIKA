<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест ОГЭ - PALOMATIKA</title>

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

        /* Header */
        .test-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .test-header h1 {
            font-size: 24px;
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

        /* Test info */
        .test-info {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #666;
        }

        .test-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Task card */
        .task-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .task-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            color: #fff;
            font-weight: 700;
            font-size: 18px;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
        }

        .task-meta {
            text-align: right;
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: #888;
        }

        .task-topic {
            background: #f0f0f0;
            padding: 3px 10px;
            border-radius: 4px;
            margin-bottom: 5px;
            display: inline-block;
        }

        .task-instruction {
            font-weight: 500;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        /* Task content */
        .task-content {
            padding: 10px 0;
        }

        .task-expression {
            font-size: 20px;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }

        .task-image {
            margin: 15px 0;
            text-align: center;
        }

        .task-image img {
            max-width: 100%;
            max-height: 300px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #fff;
        }

        /* Options */
        .options-list {
            list-style: none;
            display: grid;
            gap: 10px;
        }

        .option-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .option-item:hover {
            background: #e9ecef;
            border-color: #60a5fa;
        }

        .option-item.selected {
            background: rgba(255, 107, 107, 0.1);
            border-color: #ff6b6b;
        }

        .option-item input {
            display: none;
        }

        .option-num {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: #e0e0e0;
            border-radius: 50%;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            flex-shrink: 0;
        }

        .option-item.selected .option-num {
            background: #ff6b6b;
            color: #fff;
        }

        .option-text {
            flex: 1;
        }

        /* Answer input */
        .answer-input {
            width: 200px;
            padding: 12px 15px;
            font-size: 18px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .answer-input:focus {
            outline: none;
            border-color: #ff6b6b;
        }

        /* Submit section */
        .submit-section {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-top: 30px;
        }

        .submit-section p {
            margin-bottom: 15px;
            font-family: 'Inter', sans-serif;
            color: #666;
        }

        /* KaTeX */
        .katex {
            font-size: 1.1em !important;
        }

        /* Print styles */
        @media print {
            body {
                padding: 20px;
            }
            .btn, .header-actions {
                display: none;
            }
            .task-card {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ccc;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 20px;
                font-size: 16px;
            }
            .test-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .task-header {
                flex-direction: column;
                gap: 10px;
            }
            .task-meta {
                text-align: left;
            }
        }
    </style>
</head>
<body>

<div class="test-header">
    <h1>📝 Тест ОГЭ по математике</h1>
    <div class="header-actions">
        <a href="{{ route('test.generator') }}" class="btn btn-secondary">← Новый тест</a>
        <button onclick="window.print()" class="btn btn-secondary">🖨️ Печать</button>
    </div>
</div>

<div class="test-info">
    <span>📊 Заданий: {{ count($testTasks) }}</span>
    <span>📅 {{ now()->format('d.m.Y H:i') }}</span>
</div>

@forelse($testTasks as $testTask)
    <div class="task-card" data-task="{{ $loop->iteration }}">
        <div class="task-header">
            <div class="task-number">{{ $testTask['test_number'] }}</div>
            <div class="task-meta">
                <div class="task-topic">{{ $testTask['topic_id'] }}. {{ Str::limit($testTask['topic_title'], 25) }}</div>
                <div>Блок {{ $testTask['block_number'] }} • Задание {{ $testTask['zadanie_number'] }}</div>
            </div>
        </div>

        <div class="task-instruction">{{ $testTask['instruction'] }}</div>

        <div class="task-content">
            @php $task = $testTask['task']; @endphp

            {{-- Image --}}
            @if(!empty($task['image']))
                <div class="task-image">
                    <img src="/images/tasks/{{ $testTask['topic_id'] }}/{{ $task['image'] }}" alt="Задание">
                </div>
            @endif

            {{-- Expression --}}
            @if(!empty($task['expression']))
                <div class="task-expression">
                    ${{ $task['expression'] }}$
                </div>
            @endif

            {{-- Options --}}
            @if(!empty($task['options']))
                <ul class="options-list">
                    @foreach($task['options'] as $index => $option)
                        <li class="option-item" onclick="selectOption(this)">
                            <input type="radio" name="answer_{{ $testTask['test_number'] }}" value="{{ $index }}">
                            <span class="option-num">{{ $index + 1 }}</span>
                            <span class="option-text">{{ $option }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                {{-- Free input for expression tasks --}}
                <div style="margin-top: 15px;">
                    <label style="font-family: Inter, sans-serif; font-size: 14px; color: #666; display: block; margin-bottom: 8px;">
                        Ваш ответ:
                    </label>
                    <input type="text" class="answer-input" name="answer_{{ $testTask['test_number'] }}" placeholder="Введите ответ">
                </div>
            @endif
        </div>
    </div>
@empty
    <div class="task-card" style="text-align: center; padding: 60px;">
        <p style="font-size: 48px; margin-bottom: 20px;">😕</p>
        <p style="color: #888;">Не удалось сгенерировать задания. Попробуйте выбрать другие темы.</p>
        <a href="{{ route('test.generator') }}" class="btn btn-primary" style="margin-top: 20px;">
            Вернуться к генератору
        </a>
    </div>
@endforelse

@if(count($testTasks) > 0)
<div class="submit-section">
    <p>После выполнения всех заданий вы можете распечатать тест или начать заново</p>
    <div style="display: flex; gap: 15px; justify-content: center;">
        <button onclick="window.print()" class="btn btn-secondary">🖨️ Распечатать</button>
        <a href="{{ route('test.generator') }}" class="btn btn-primary">🎲 Новый тест</a>
    </div>
</div>
@endif

<script>
    function selectOption(element) {
        // Deselect all options in this task
        const taskCard = element.closest('.task-card');
        taskCard.querySelectorAll('.option-item').forEach(item => {
            item.classList.remove('selected');
        });

        // Select this option
        element.classList.add('selected');
        element.querySelector('input').checked = true;
    }
</script>

</body>
</html>
