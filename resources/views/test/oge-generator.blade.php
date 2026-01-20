<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Генератор вариантов ОГЭ - PALOMATIKA</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- KaTeX -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/contrib/auto-render.min.js"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .katex { font-size: 1.1em; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900" x-data="ogeGenerator()">

<div class="max-w-7xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-slate-800/50 rounded-xl p-4 border border-slate-700">
        <a href="{{ route('test.index') }}" class="text-blue-400 hover:text-blue-300 transition-colors">← Все задания</a>
        <div class="flex gap-3">
            <a href="{{ route('test.generator') }}" class="px-3 py-1.5 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition">Кастомный тест</a>
        </div>
    </div>

    {{-- Header --}}
    <div class="text-center mb-10">
        <h1 class="text-4xl font-bold text-white mb-3">📝 Генератор вариантов ОГЭ</h1>
        <p class="text-slate-400 text-lg">Выберите конкретные темы из каждого задания для своего варианта</p>
    </div>

    {{-- Info Box --}}
    <div class="bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-2xl p-6 mb-8 border border-blue-500/30">
        <h3 class="text-blue-400 font-semibold text-lg mb-2">ℹ️ Как это работает?</h3>
        <p class="text-slate-300 leading-relaxed">
            Выберите нужные темы-блоки из каждого задания. Рядом с каждым блоком показан пример задачи,
            чтобы вы понимали, о чём эта тема. Можно выбирать отдельные блоки или целые задания.
        </p>
    </div>

    {{-- Quick Presets --}}
    <div class="bg-slate-800 rounded-2xl p-6 mb-6 border border-slate-700">
        <h2 class="text-white font-semibold text-lg mb-4">Быстрый выбор</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button @click="selectAllBlocks()"
                    class="px-4 py-3 bg-gradient-to-r from-emerald-500/20 to-emerald-600/20 hover:from-emerald-500/30 hover:to-emerald-600/30 text-emerald-400 font-medium rounded-lg border border-emerald-500/30 transition-all">
                🎯 Все блоки
            </button>
            <button @click="selectCategory('algebra')"
                    class="px-4 py-3 bg-gradient-to-r from-blue-500/20 to-blue-600/20 hover:from-blue-500/30 hover:to-blue-600/30 text-blue-400 font-medium rounded-lg border border-blue-500/30 transition-all">
                📊 Только алгебра
            </button>
            <button @click="selectCategory('geometry')"
                    class="px-4 py-3 bg-gradient-to-r from-violet-500/20 to-violet-600/20 hover:from-violet-500/30 hover:to-violet-600/30 text-violet-400 font-medium rounded-lg border border-violet-500/30 transition-all">
                📐 Только геометрия
            </button>
            <button @click="clearAll()"
                    class="px-4 py-3 bg-gradient-to-r from-red-500/20 to-red-600/20 hover:from-red-500/30 hover:to-red-600/30 text-red-400 font-medium rounded-lg border border-red-500/30 transition-all">
                ❌ Очистить
            </button>
        </div>
    </div>

    {{-- Topics with Blocks --}}
    <div class="space-y-4 mb-8">
        @foreach($topicsWithBlocks as $topic)
            <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
                {{-- Topic Header --}}
                <div class="p-4 bg-slate-700/30 border-b border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-{{ $topic['color'] }}-500/20 flex items-center justify-center text-{{ $topic['color'] }}-400 font-bold text-xl border border-{{ $topic['color'] }}-500/30">
                            {{ $topic['topic_number'] }}
                        </div>
                        <div>
                            <h3 class="text-white font-semibold">{{ $topic['title'] }}</h3>
                            <p class="text-slate-400 text-sm">{{ count($topic['blocks']) }} {{ count($topic['blocks']) === 1 ? 'блок' : (count($topic['blocks']) < 5 ? 'блока' : 'блоков') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="toggleAllBlocksInTopic('{{ $topic['topic_id'] }}')"
                                class="px-3 py-1.5 text-sm bg-slate-600 hover:bg-slate-500 text-slate-300 rounded-lg transition">
                            <span x-show="!areAllBlocksSelectedInTopic('{{ $topic['topic_id'] }}')">Выбрать все</span>
                            <span x-show="areAllBlocksSelectedInTopic('{{ $topic['topic_id'] }}')">Снять все</span>
                        </button>
                        <button @click="toggleTopic('{{ $topic['topic_id'] }}')"
                                class="text-slate-400 hover:text-white transition">
                            <svg class="w-5 h-5 transition-transform" :class="expandedTopics.includes('{{ $topic['topic_id'] }}') ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Blocks --}}
                <div x-show="expandedTopics.includes('{{ $topic['topic_id'] }}')"
                     x-transition
                     class="p-4 space-y-3">
                    @foreach($topic['blocks'] as $block)
                        <label class="flex gap-4 p-4 bg-slate-700/20 hover:bg-slate-700/40 rounded-xl border border-slate-600/50 hover:border-slate-500 cursor-pointer transition-all group">
                            <input type="checkbox"
                                   x-model="selectedBlocks"
                                   value="{{ $block['block_id'] }}"
                                   class="w-5 h-5 mt-1 rounded border-slate-500 text-{{ $topic['color'] }}-500 focus:ring-{{ $topic['color'] }}-500 focus:ring-offset-slate-800 flex-shrink-0">

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="text-slate-200 font-medium">{{ $block['title'] }}</h4>
                                    <span class="text-xs px-2 py-1 rounded-md bg-slate-600/50 text-slate-400 flex-shrink-0">
                                        {{ $topic['category'] === 'algebra' ? '📊' : '📐' }}
                                    </span>
                                </div>

                                @if($block['example'])
                                    <div class="mt-2 p-3 bg-slate-800/50 rounded-lg border border-slate-600/30">
                                        <p class="text-slate-400 text-xs mb-2">Пример:</p>
                                        @if($block['example']['type'] === 'statements')
                                            <p class="text-slate-300 text-sm latex-content">{{ $block['example']['text'] }}</p>
                                        @else
                                            @if(!empty($block['example']['instruction']))
                                                <p class="text-slate-400 text-xs mb-1">{{ $block['example']['instruction'] }}</p>
                                            @endif
                                            @if(!empty($block['example']['expression']))
                                                <p class="text-slate-200 latex-content">${{ $block['example']['expression'] }}$</p>
                                            @endif
                                            @if(!empty($block['example']['text']))
                                                <p class="text-slate-300 text-sm latex-content">{{ $block['example']['text'] }}</p>
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Generate Button --}}
    <div class="bg-slate-800 rounded-2xl p-6 mb-8 border border-slate-700">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-white font-semibold text-lg">Готовы начать?</h2>
                <p class="text-slate-400 text-sm mt-1">Каждый клик создаёт новый уникальный вариант</p>
            </div>
            <div class="bg-slate-700/50 rounded-xl px-5 py-3 border border-slate-600 text-center">
                <span class="text-slate-400 text-sm">Выбрано блоков</span>
                <div class="font-bold text-2xl" :class="selectedBlocks.length > 0 ? 'text-emerald-400' : 'text-slate-500'"
                     x-text="selectedBlocks.length">
                </div>
            </div>
        </div>

        {{-- Warning if no blocks selected --}}
        <div x-show="selectedBlocks.length === 0" class="mb-4 p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl">
            <p class="text-amber-400 text-sm">⚠️ Выберите хотя бы один блок для генерации варианта</p>
        </div>

        <button @click="generateVariant()"
                :disabled="selectedBlocks.length === 0"
                :class="selectedBlocks.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-600' : 'bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40'"
                class="block w-full py-4 text-white font-bold text-lg rounded-xl transition-all text-center">
            🎯 Сгенерировать вариант
        </button>
    </div>

    {{-- Footer --}}
    <div class="text-center mt-10 text-slate-500 text-sm">
        Задания взяты из базы <a href="{{ route('test.index') }}" class="text-blue-400 hover:underline">PALOMATIKA</a>
    </div>
</div>

<script>
// Передаём данные из PHP в JavaScript
const topicsData = @json($topicsWithBlocks);

function ogeGenerator() {
    // Получаем все block_id для дефолтного выбора (все кроме тем 18 и 19)
    const defaultBlocks = [];
    topicsData.forEach(topic => {
        if (!['18', '19'].includes(topic.topic_id)) {
            topic.blocks.forEach(block => {
                defaultBlocks.push(block.block_id);
            });
        }
    });

    return {
        selectedBlocks: defaultBlocks,
        expandedTopics: topicsData.map(t => t.topic_id), // Все темы развёрнуты по умолчанию

        toggleTopic(topicId) {
            const index = this.expandedTopics.indexOf(topicId);
            if (index > -1) {
                this.expandedTopics.splice(index, 1);
            } else {
                this.expandedTopics.push(topicId);
            }
        },

        toggleAllBlocksInTopic(topicId) {
            const topic = topicsData.find(t => t.topic_id === topicId);
            if (!topic) return;

            const topicBlockIds = topic.blocks.map(b => b.block_id);
            const allSelected = topicBlockIds.every(id => this.selectedBlocks.includes(id));

            if (allSelected) {
                // Снять все блоки этой темы
                this.selectedBlocks = this.selectedBlocks.filter(id => !topicBlockIds.includes(id));
            } else {
                // Выбрать все блоки этой темы
                topicBlockIds.forEach(id => {
                    if (!this.selectedBlocks.includes(id)) {
                        this.selectedBlocks.push(id);
                    }
                });
            }
        },

        areAllBlocksSelectedInTopic(topicId) {
            const topic = topicsData.find(t => t.topic_id === topicId);
            if (!topic) return false;

            const topicBlockIds = topic.blocks.map(b => b.block_id);
            return topicBlockIds.every(id => this.selectedBlocks.includes(id));
        },

        selectAllBlocks() {
            this.selectedBlocks = [];
            topicsData.forEach(topic => {
                topic.blocks.forEach(block => {
                    this.selectedBlocks.push(block.block_id);
                });
            });
        },

        selectCategory(category) {
            this.selectedBlocks = [];
            topicsData.forEach(topic => {
                if (topic.category === category) {
                    topic.blocks.forEach(block => {
                        this.selectedBlocks.push(block.block_id);
                    });
                }
            });
        },

        clearAll() {
            this.selectedBlocks = [];
        },

        generateVariant() {
            if (this.selectedBlocks.length === 0) return;

            // Generate random hash
            const hash = Math.random().toString(36).substring(2, 12);

            // Build URL with selected blocks as query parameter
            const blocks = this.selectedBlocks.sort().join(',');
            const url = `{{ route('test.oge.show', ['hash' => '__HASH__']) }}`.replace('__HASH__', hash) + '?blocks=' + blocks;

            // Navigate to generated variant
            window.location.href = url;
        }
    }
}

// Render LaTeX after page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        renderMathInElement(document.body, {
            delimiters: [
                {left: "$$", right: "$$", display: true},
                {left: "$", right: "$", display: false}
            ],
            throwOnError: false,
            trust: true
        });
    }, 100);
});
</script>

</body>
</html>
