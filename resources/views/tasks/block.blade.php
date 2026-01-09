{{--
    Partial для отображения блока заданий
    @param array $block - данные блока
    @param string $topicId - ID темы
    @param string $color - цвет темы
--}}

<div class="mb-12">
    {{-- Block Header --}}
    <div class="flex justify-between items-center mb-6 text-sm text-slate-500 italic border-b border-slate-700 pb-4">
        <span>Е. А. Ширяева</span>
        <span>Задачник ОГЭ 2026 (тренажер)</span>
    </div>

    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-white">{{ $topicId }}. {{ $topicMeta['title'] ?? '' }}</h2>
        <p class="text-{{ $color }}-400 text-lg mt-1">Блок {{ $block['number'] }}. {{ $block['title'] }}</p>
    </div>

    @foreach($block['zadaniya'] ?? [] as $zadanie)
        @include('tasks.zadanie', [
            'zadanie' => $zadanie,
            'block' => $block,
            'topicId' => $topicId,
            'color' => $color,
        ])
    @endforeach
</div>
