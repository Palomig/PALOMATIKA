{{--
    Partial для отображения задания ЕГЭ
    Диспетчеризует на нужный тип
    @param array $zadanie - данные задания
    @param array $block - данные блока
    @param string $topicId - ID темы
    @param string $color - цвет темы
--}}

@php
    $type = $zadanie['type'] ?? 'geometry';
@endphp

<div class="mb-10">
    {{-- Zadanie Header --}}
    <div class="bg-dark-200 rounded-xl p-4 mb-6 border-l-4 border-accent">
        <h3 class="text-lg font-semibold text-white">
            {{ $zadanie['instruction'] }}
        </h3>
        @if(isset($zadanie['section']))
            <p class="text-gray-500 text-sm mt-1">{{ $zadanie['section'] }}</p>
        @endif
    </div>

    {{-- Задачи ЕГЭ --}}
    @switch($type)
        @case('geometry')
            @include('ege.partials.types.geometry', compact('zadanie', 'block', 'topicId'))
            @break

        @case('expression')
            @include('ege.partials.types.expression', compact('zadanie', 'block', 'topicId'))
            @break

        @case('word_problem')
            @include('ege.partials.types.word-problem', compact('zadanie', 'block', 'topicId'))
            @break

        @default
            @include('ege.partials.types.geometry', compact('zadanie', 'block', 'topicId'))
    @endswitch
</div>
