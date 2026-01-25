{{--
    Адаптер для отображения задания в варианте ОГЭ
    Преобразует данные из TaskDataService в формат для tasks/types компонентов
    @param array $taskData - данные из getRandomTasksFromZadanie
    @param int $taskNumber - номер задания в варианте (6-19)
    @param string $color - цвет акцента
--}}

@php
    // Извлекаем данные
    $topicId = $taskData['topic_id'] ?? '';
    $topicTitle = $taskData['topic_title'] ?? '';
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
    if ($type === 'statements' && isset($taskData['statements'])) {
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

<div class="task-card mb-8 bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
    {{-- Task Header --}}
    <div class="bg-slate-800 p-4 border-b border-slate-700 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-{{ $color }}-500 to-{{ $color }}-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">
            {{ $taskNumber }}
        </div>
        <div class="flex-1">
            <div class="text-white font-medium">{{ $instruction }}</div>
            <div class="text-slate-500 text-sm mt-1">{{ $topicTitle }}</div>
        </div>
    </div>

    {{-- Task Content - используем существующие компоненты --}}
    <div class="p-5">
        @switch($type)
            @case('expression')
                @include('tasks.types.expression', compact('zadanie', 'block', 'topicId'))
                @break

            @case('choice')
            @case('simple_choice')
            @case('fraction_choice')
            @case('interval_choice')
            @case('between_fractions')
            @case('segment_choice')
            @case('fraction_options')
            @case('decimal_choice')
            @case('sqrt_choice')
            @case('sqrt_interval')
            @case('sqrt_segment')
            @case('sqrt_options')
            @case('comparison')
            @case('power_choice')
            @case('compare_fractions')
            @case('false_statements')
            @case('ordering')
            @case('point_value')
            @case('fraction_point')
            @case('count_integers')
            @case('negative_segment')
            @case('negative_interval')
                @include('tasks.types.choice', compact('zadanie', 'block', 'topicId'))
                @break

            @case('word_problem')
                @include('tasks.types.word-problem', compact('zadanie', 'block', 'topicId'))
                @break

            @case('matching')
            @case('matching_signs')
            @case('matching_4')
                @include('tasks.types.matching', compact('zadanie', 'block', 'topicId'))
                @break

            @case('geometry')
                @include('tasks.types.geometry', compact('zadanie', 'block', 'topicId'))
                @break

            @case('grid_image')
            @case('grid_image_with_question')
                @include('tasks.types.grid', compact('zadanie', 'block', 'topicId'))
                @break

            @case('statements')
                @include('tasks.types.statements', compact('zadanie', 'block', 'topicId'))
                @break

            @case('graphic')
                @include('tasks.types.graphic', compact('zadanie', 'block', 'topicId'))
                @break

            @default
                @include('tasks.types.expression', compact('zadanie', 'block', 'topicId'))
        @endswitch
    </div>

    {{-- Answer Field --}}
    <div class="p-5 border-t border-slate-700">
        <div class="flex items-center gap-4">
            <span class="text-slate-400 text-sm font-medium">Ответ:</span>
            <input type="text"
                   class="flex-1 max-w-xs px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-{{ $color }}-500 transition-colors"
                   placeholder="Введите ответ">
        </div>
    </div>
</div>
