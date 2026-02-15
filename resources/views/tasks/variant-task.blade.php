{{--
    Адаптер для отображения задания в варианте ОГЭ
    Преобразует данные из TaskDataService в формат для tasks/types компонентов
    @param array $taskData - данные из getRandomTasksFromZadanie или getRandomMatchingSet
    @param int $taskNumber - номер задания в варианте (6-19)
    @param string $color - цвет акцента
    @param bool $studentMode - интерактивный режим для ученика
--}}

@php
    // Проверяем, это matching set (3 графика для ОГЭ формата)?
    $isMatchingSet = !empty($taskData['is_matching_set']);

    // Извлекаем данные
    $topicId = $taskData['topic_id'] ?? '';
    $instruction = $taskData['instruction'] ?? '';
    $type = $taskData['type'] ?? 'expression';

    // Создаём структуру zadanie для компонентов tasks/types
    $zadanie = [
        'number' => $taskData['zadanie_number'] ?? 1,
        'instruction' => $instruction,
        'type' => $type,
        'svg_type' => $taskData['svg_type'] ?? null,
        'points' => $taskData['points'] ?? null,
        'options' => $taskData['options'] ?? null,
        'section' => $taskData['section'] ?? null,
    ];

    // Для statements - выбираем 3 случайных утверждения для варианта ОГЭ
    if ($type === 'statements' && (isset($taskData['selected_statements']) || isset($taskData['statements']))) {
        if (isset($taskData['selected_statements']) && is_array($taskData['selected_statements'])) {
            $zadanie['statements'] = $taskData['selected_statements'];
        } else {
            $allStatements = $taskData['statements'];

            // Выбираем 3 случайных утверждения из набора (как в официальном ОГЭ)
            if (count($allStatements) > 3) {
                $keys = array_rand($allStatements, 3);
                if (!is_array($keys)) $keys = [$keys];
                sort($keys); // Сохраняем порядок для детерминированности

                $selectedStatements = [];
                $newNumber = 1;
                foreach ($keys as $key) {
                    $statement = $allStatements[$key];
                    // Перенумеровываем для варианта (1, 2, 3)
                    $statement['display_number'] = $newNumber++;
                    $selectedStatements[] = $statement;
                }
                $zadanie['statements'] = $selectedStatements;
            } else {
                // Если утверждений 3 или меньше - берём все
                $selectedStatements = [];
                $newNumber = 1;
                foreach ($allStatements as $statement) {
                    $statement['display_number'] = $newNumber++;
                    $selectedStatements[] = $statement;
                }
                $zadanie['statements'] = $selectedStatements;
            }
        }
        $zadanie['tasks'] = [];
        $zadanie['is_oge_variant'] = true; // Флаг для компонента
    } else {
        // Для обычных типов - оборачиваем одну задачу в массив
        $zadanie['tasks'] = [$taskData['task'] ?? []];
    }

    // Блок для передачи в компоненты
    $block = [
        'number' => $taskData['block_number'] ?? 1,
        'title' => $taskData['block_title'] ?? '',
    ];
@endphp

{{-- Matching Set (3 графика для ОГЭ) --}}
@if($isMatchingSet)
    <div class="task-card mb-8 bg-dark-light/35 rounded-xl border border-slate-800 overflow-hidden" data-task-number="{{ $taskNumber }}">
        {{-- Task Header --}}
        <div class="bg-slate-900/40 p-4 border-b border-slate-800 flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg border border-{{ $color }}-700/60 bg-{{ $color }}-900/20 text-{{ $color }}-300 flex items-center justify-center font-semibold text-lg">
                {{ $taskNumber }}
            </div>
            <div class="flex-1">
                <div class="text-white font-medium leading-snug">{{ $instruction }}</div>
            </div>
        </div>

        {{-- Matching Variant Content (3 графика + 3 формулы) --}}
        <div class="p-5">
            @php
                try {
                    echo view('tasks.types.matching-variant', compact('taskData', 'taskNumber', 'color'))->render();
                } catch (\Throwable $e) {
                    \Log::warning('Matching variant render failed', [
                        'task_number' => $taskNumber ?? null,
                        'topic_id' => $topicId ?? null,
                        'view' => 'tasks.types.matching-variant',
                        'error' => $e->getMessage(),
                    ]);

                    echo '<div class="rounded-lg border border-amber-700/50 bg-amber-900/20 p-3 text-sm text-amber-200">Не удалось отрисовать блок сопоставления. Попробуйте открыть другой вариант.</div>';
                }
            @endphp
        </div>

        <div class="p-5 border-t border-slate-800">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-slate-400 text-sm font-medium">Ответ:</span>
                <input type="text"
                       class="js-answer-input flex-1 max-w-xs px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-{{ $color }}-600 transition-colors"
                       placeholder="Введите ответ">

                @if(!empty($studentMode))
                    <button type="button"
                            class="js-answer-ok px-3 py-2 rounded-lg bg-emerald-700 text-white hover:bg-emerald-600 transition text-sm">
                        ОК
                    </button>
                    <button type="button"
                            class="js-answer-edit hidden px-3 py-2 rounded-lg bg-slate-800 text-slate-200 hover:bg-slate-700 transition text-sm">
                        Редактировать
                    </button>
                    <span class="js-save-status text-xs text-emerald-400 opacity-0"></span>
                @endif
            </div>
        </div>
    </div>
@else
    {{-- Обычный формат задания --}}
    <div class="task-card mb-8 bg-dark-light/35 rounded-xl border border-slate-800 overflow-hidden" data-task-number="{{ $taskNumber }}">
        {{-- Task Header --}}
        <div class="bg-slate-900/40 p-4 border-b border-slate-800 flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg border border-{{ $color }}-700/60 bg-{{ $color }}-900/20 text-{{ $color }}-300 flex items-center justify-center font-semibold text-lg">
                {{ $taskNumber }}
            </div>
            <div class="flex-1">
                <div class="text-white font-medium leading-snug">{{ $instruction }}</div>
            </div>
        </div>

        {{-- Task Content - используем существующие компоненты --}}
        <div class="p-5">
            @php
                $taskView = 'tasks.types.expression';

                if (in_array($type, [
                    'choice', 'simple_choice', 'fraction_choice', 'interval_choice',
                    'between_fractions', 'segment_choice', 'fraction_options',
                    'decimal_choice', 'sqrt_choice', 'sqrt_interval', 'sqrt_segment',
                    'sqrt_options', 'comparison', 'power_choice', 'compare_fractions',
                    'false_statements', 'ordering', 'point_value', 'fraction_point',
                    'count_integers', 'negative_segment', 'negative_interval'
                ], true)) {
                    $taskView = 'tasks.types.choice';
                } elseif ($type === 'word_problem') {
                    $taskView = 'tasks.types.word-problem';
                } elseif (in_array($type, ['matching', 'matching_signs', 'matching_4'], true)) {
                    $taskView = 'tasks.types.matching';
                } elseif ($type === 'geometry') {
                    $taskView = 'tasks.types.geometry';
                } elseif (in_array($type, ['grid_image', 'grid_image_with_question'], true)) {
                    $taskView = 'tasks.types.grid';
                } elseif ($type === 'statements') {
                    $taskView = 'tasks.types.statements';
                } elseif ($type === 'graph_statements') {
                    $taskView = 'tasks.types.graph-statements';
                } elseif ($type === 'graphic') {
                    $taskView = 'tasks.types.graphic';
                }

                try {
                    echo view($taskView, compact('zadanie', 'block', 'topicId') + ['isVariant' => true])->render();
                } catch (\Throwable $e) {
                    \Log::warning('Task variant render failed', [
                        'task_number' => $taskNumber ?? null,
                        'topic_id' => $topicId ?? null,
                        'type' => $type ?? null,
                        'view' => $taskView,
                        'error' => $e->getMessage(),
                    ]);

                    echo '<div class="rounded-lg border border-amber-700/50 bg-amber-900/20 p-3 text-sm text-amber-200">Не удалось отрисовать это задание полностью. Попробуйте открыть другой вариант.</div>';
                }
            @endphp
        </div>

        {{-- Answer Field --}}
        <div class="p-5 border-t border-slate-800">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-slate-400 text-sm font-medium">Ответ:</span>
                <input type="text"
                       class="js-answer-input flex-1 max-w-xs px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-{{ $color }}-600 transition-colors"
                       placeholder="Введите ответ">

                @if(!empty($studentMode))
                    <button type="button"
                            class="js-answer-ok px-3 py-2 rounded-lg bg-emerald-700 text-white hover:bg-emerald-600 transition text-sm">
                        ОК
                    </button>
                    <button type="button"
                            class="js-answer-edit hidden px-3 py-2 rounded-lg bg-slate-800 text-slate-200 hover:bg-slate-700 transition text-sm">
                        Редактировать
                    </button>
                    <span class="js-save-status text-xs text-emerald-400 opacity-0"></span>
                @endif
            </div>
        </div>
    </div>
@endif
