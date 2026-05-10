{{--
    Тип: expression (темы 06, 08, 09)
    Математические выражения для вычисления
--}}

@php
    $tasks = $zadanie['tasks'] ?? [];
    $tasksCount = count($tasks);
    $hasLongExpressions = collect($tasks)->contains(fn($t) => strlen($t['expression'] ?? '') > 50);
    $hasDenominator = isset($tasks[0]['denominator']);
    $isVariant = $isVariant ?? false;
    $showTaskAnswer = !$isVariant;
    $answerResolver = app(\App\Services\TaskAnswerResolver::class);
    $isCoreExpressionTopic = in_array((string) $topicId, ['06', '08', '09'], true);
    $usesNumberWording = isset($grade) && (int) $grade === 5 && (string) $topicId === '01';
    $expressionStyle = ($isVariant && $isCoreExpressionTopic) ? 'font-size:115%;' : '';
    $renderMathText = static function (string $text): string {
        $normalized = preg_replace('/\s+/u', ' ', trim($text));
        $escaped = e($normalized);

        if (!preg_match('/[А-Яа-яЁё]/u', $normalized)) {
            return '$' . $escaped . '$';
        }

        return preg_replace_callback(
            '/(?<![A-Za-zА-Яа-яЁё])(-?\d+(?:,\d+)?(?:\s*(?:\\\\cdot|[:=+\-])\s*-?\d+(?:,\d+)?|\s*[+\-]\s*\(-?\d+(?:,\d+)?\)|\s*[:=+\-]\s*\([^)]*\)|\s*\\\\cdot\s*\([^)]*\))*)/u',
            static function (array $matches): string {
                $fragment = trim($matches[1]);

                if ($fragment === '' || !preg_match('/\d/u', $fragment)) {
                    return $matches[0];
                }

                return '$' . $fragment . '$';
            },
            $escaped
        );
    };
@endphp

@if($tasksCount === 1 && !$hasDenominator)
    @php
        $task = $tasks[0] ?? [];
        $expression = preg_replace('/\s+/u', ' ', trim((string) ($task['expression'] ?? '')));
        $hasTextExpression = preg_match('/[А-Яа-яЁё]/u', $expression) === 1;
        $taskId = $task['id'] ?? 1;
        $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$taskId}";
        $taskInfo = "Блок {$block['number']} ({$block['title']}), Задание {$zadanie['number']}, Задача {$taskId}<br>Выражение: <code>{$expression}</code>";
    @endphp
    <div class="task-review-item relative py-1 overflow-x-auto overflow-y-hidden"
         data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">
        @if(!$isVariant)
            <span class="text-blue-400 font-bold">{{ $taskId }})</span>
        @endif
        <span class="text-slate-200 {{ $isVariant ? '' : 'ml-2' }} math-serif {{ $hasTextExpression ? 'whitespace-normal break-words' : 'whitespace-nowrap' }}" style="{{ $expressionStyle }}">{!! $renderMathText($expression) !!}</span>
        @include('tasks.partials.task-answer', [
            'showTaskAnswer' => $showTaskAnswer,
            'taskAnswer' => $answerResolver->resolveFromTaskAndZadanie($zadanie, $task),
        ])
        @include('tasks.partials.task-status-badge')
    </div>
@elseif($hasDenominator)
    {{-- Задание со знаменателем - формат параграфа --}}
    <div class="space-y-3">
        @foreach($tasks as $task)
            @php
                $expression = preg_replace('/\s+/u', ' ', trim((string) ($task['expression'] ?? '')));
                $hasTextExpression = preg_match('/[А-Яа-яЁё]/u', $expression) === 1;
                $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
                $taskInfo = "Блок {$block['number']} ({$block['title']}), Задание {$zadanie['number']}, Задача {$task['id']}<br>Выражение: <code>{$expression}</code>";
            @endphp
            <div class="bg-slate-800/70 rounded-xl p-4 border border-slate-700 task-review-item relative"
                 data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">
                @if(!$isVariant)
                    <span class="text-blue-400 font-bold">{{ $task['id'] }})</span>
                @endif
                <span class="text-slate-200 ml-2">
                    Представьте {{ $usesNumberWording ? 'число' : 'выражение' }} {!! $renderMathText($expression) !!} в виде дроби со знаменателем {{ $task['denominator'] }}.
                    В ответ запишите {{ $usesNumberWording ? 'только ' : '' }}числитель полученной дроби.
                </span>
                @include('tasks.partials.task-answer', [
                    'showTaskAnswer' => $showTaskAnswer,
                    'taskAnswer' => $answerResolver->resolveFromTaskAndZadanie($zadanie, $task),
                ])
                @include('tasks.partials.task-status-badge')
            </div>
        @endforeach
    </div>
@elseif($hasLongExpressions)
    {{-- Длинные выражения - по одному в ряд --}}
    <div class="space-y-4">
        @foreach($tasks as $task)
            @php
                $expression = preg_replace('/\s+/u', ' ', trim((string) ($task['expression'] ?? '')));
                $hasTextExpression = preg_match('/[А-Яа-яЁё]/u', $expression) === 1;
                $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
                $taskInfo = "Блок {$block['number']} ({$block['title']}), Задание {$zadanie['number']}, Задача {$task['id']}<br>Выражение: <code>" . substr($expression, 0, 80) . "...</code>";
            @endphp
            <div class="bg-slate-800/70 rounded-lg p-4 border border-slate-700 hover:border-slate-600 transition-colors task-review-item relative"
                 data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">
                @if(!$isVariant)
                    <span class="text-blue-400 font-bold">{{ $task['id'] }})</span>
                @endif
                <div class="{{ $hasTextExpression ? 'block' : 'inline-block overflow-x-auto overflow-y-hidden' }} max-w-full align-middle ml-2">
                    <span class="text-slate-200 math-serif {{ $hasTextExpression ? 'whitespace-normal break-words' : 'whitespace-nowrap' }}" style="{{ $expressionStyle }}">{!! $renderMathText($expression) !!}</span>
                </div>
                @include('tasks.partials.task-answer', [
                    'showTaskAnswer' => $showTaskAnswer,
                    'taskAnswer' => $answerResolver->resolveFromTaskAndZadanie($zadanie, $task),
                ])
                @include('tasks.partials.task-status-badge')
            </div>
        @endforeach
    </div>
@else
    {{-- Короткие выражения - сетка 4 --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($tasks as $task)
            @php
                $expression = preg_replace('/\s+/u', ' ', trim((string) ($task['expression'] ?? '')));
                $hasTextExpression = preg_match('/[А-Яа-яЁё]/u', $expression) === 1;
                $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
                $taskInfo = "Блок {$block['number']} ({$block['title']}), Задание {$zadanie['number']}, Задача {$task['id']}<br>Выражение: <code>{$expression}</code>";
            @endphp
            <div class="bg-slate-800/70 rounded-lg p-3 border border-slate-700 hover:border-slate-600 transition-colors task-review-item relative"
                 data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">
                @if(!$isVariant)
                    <span class="text-blue-400 font-bold">{{ $task['id'] }})</span>
                @endif
                <span class="text-slate-200 {{ $isVariant ? '' : 'ml-2' }} math-serif {{ $hasTextExpression ? 'whitespace-normal break-words' : '' }}" style="{{ $expressionStyle }}">{!! $renderMathText($expression) !!}</span>
                @include('tasks.partials.task-answer', [
                    'showTaskAnswer' => $showTaskAnswer,
                    'taskAnswer' => $answerResolver->resolveFromTaskAndZadanie($zadanie, $task),
                ])
                @include('tasks.partials.task-status-badge')
            </div>
        @endforeach
    </div>
@endif
