{{--
    Partial для отображения задания
    Диспетчеризует на нужный тип
    @param array $zadanie - данные задания
    @param array $block - данные блока
    @param string $topicId - ID темы
    @param string $color - цвет темы
--}}

@php
    $type = $zadanie['type'] ?? 'expression';
@endphp

<div class="mb-10">
    {{-- Zadanie Header --}}
    <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-{{ $color }}-500">
        <h3 class="text-lg font-semibold text-white">
            Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}
        </h3>
        @if(isset($zadanie['section']))
            <p class="text-slate-400 text-sm mt-1">{{ $zadanie['section'] }}</p>
        @endif
    </div>

    {{-- Задачи по типу --}}
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

        @default
            @include('tasks.types.expression', compact('zadanie', 'block', 'topicId'))
    @endswitch
</div>
