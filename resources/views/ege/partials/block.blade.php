{{--
    Partial для отображения блока заданий ЕГЭ
    @param array $block - данные блока
    @param string $topicId - ID задания
    @param string $color - цвет темы
--}}

<div class="mb-12">
    {{-- Block Header --}}
    <div class="flex justify-between items-center mb-6 text-sm text-gray-600 italic border-b border-dark-400/30 pb-4">
        <span>Е. А. Ширяева</span>
        <span>Задачник ЕГЭ профиль 2026 (тренажер)</span>
    </div>

    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-white">Задание {{ ltrim($topicId, '0') }}. {{ $topicMeta['title'] ?? '' }}</h2>
        <p class="text-accent-light text-lg mt-1">Блок {{ $block['number'] }}. {{ $block['title'] }}</p>
    </div>

    @foreach($block['zadaniya'] ?? [] as $zadanie)
        @include('ege.partials.zadanie', [
            'zadanie' => $zadanie,
            'block' => $block,
            'topicId' => $topicId,
            'color' => $color,
        ])
    @endforeach
</div>
