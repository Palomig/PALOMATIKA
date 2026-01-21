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
            Выберите нужные типы заданий из каждой темы. Рядом с каждым типом показан пример задачи,
            чтобы вы понимали, о чём этот тип. Можно выбирать отдельные типы или целые темы.
        </p>
    </div>

    {{-- Quick Presets --}}
    <div class="bg-slate-800 rounded-2xl p-6 mb-6 border border-slate-700">
        <h2 class="text-white font-semibold text-lg mb-4">Быстрый выбор</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button @click="selectAllZadaniya()"
                    class="px-4 py-3 bg-gradient-to-r from-emerald-500/20 to-emerald-600/20 hover:from-emerald-500/30 hover:to-emerald-600/30 text-emerald-400 font-medium rounded-lg border border-emerald-500/30 transition-all">
                🎯 Все типы
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

    {{-- Saved Templates --}}
    <div class="bg-slate-800 rounded-2xl p-6 mb-6 border border-slate-700">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-white font-semibold text-lg">💾 Мои шаблоны</h2>
            <button @click="showSaveTemplateModal = true"
                    :disabled="selectedZadaniya.length === 0"
                    :class="selectedZadaniya.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-emerald-600'"
                    class="px-4 py-2 bg-emerald-500 text-white text-sm font-medium rounded-lg transition-all">
                ➕ Сохранить текущий выбор
            </button>
        </div>

        <div x-show="templates.length === 0" class="text-slate-400 text-sm text-center py-4">
            У вас пока нет сохранённых шаблонов
        </div>

        <div x-show="templates.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <template x-for="template in templates" :key="template.id">
                <div class="p-4 bg-slate-700/30 rounded-lg border border-slate-600/50 hover:border-slate-500 transition-all group">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="text-slate-200 font-medium" x-text="template.name"></h3>
                        <button @click="deleteTemplate(template.id)"
                                class="text-slate-400 hover:text-red-400 transition-colors opacity-0 group-hover:opacity-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-slate-400 text-xs mb-3">
                        <span x-text="template.zadaniya.length"></span> типов заданий
                    </p>
                    <button @click="loadTemplate(template.id)"
                            class="w-full px-3 py-2 bg-slate-600 hover:bg-slate-500 text-slate-200 text-sm rounded-lg transition-all">
                        Загрузить
                    </button>
                </div>
            </template>
        </div>
    </div>

    {{-- Save Template Modal --}}
    <div x-show="showSaveTemplateModal"
         x-cloak
         @click.self="showSaveTemplateModal = false"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div @click.away="showSaveTemplateModal = false"
             class="bg-slate-800 rounded-2xl p-6 max-w-md w-full border border-slate-700 shadow-2xl">
            <h3 class="text-white font-semibold text-xl mb-4">Сохранить шаблон</h3>

            <div class="mb-4">
                <label class="block text-slate-300 text-sm mb-2">Название шаблона</label>
                <input type="text"
                       x-model="newTemplateName"
                       @keydown.enter="saveTemplate()"
                       placeholder="Например: Лёгкий уровень"
                       class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition-colors">
            </div>

            <div class="mb-6">
                <p class="text-slate-400 text-sm">
                    Будет сохранено <span class="text-emerald-400 font-semibold" x-text="selectedZadaniya.length"></span> типов заданий
                </p>
            </div>

            <div class="flex gap-3">
                <button @click="showSaveTemplateModal = false"
                        class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-lg transition-all">
                    Отмена
                </button>
                <button @click="saveTemplate()"
                        :disabled="!newTemplateName.trim()"
                        :class="!newTemplateName.trim() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-emerald-600'"
                        class="flex-1 px-4 py-3 bg-emerald-500 text-white font-medium rounded-lg transition-all">
                    Сохранить
                </button>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    {{-- Topics with Blocks --}}
    <div class="space-y-4 mb-8">
        @foreach($topicsWithZadaniya as $topic)
            <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
                {{-- Topic Header --}}
                <div class="p-4 bg-slate-700/30 border-b border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-{{ $topic['color'] }}-500/20 flex items-center justify-center text-{{ $topic['color'] }}-400 font-bold text-xl border border-{{ $topic['color'] }}-500/30">
                            {{ $topic['topic_number'] }}
                        </div>
                        <div>
                            <h3 class="text-white font-semibold">{{ $topic['title'] }}</h3>
                            <p class="text-slate-400 text-sm">{{ count($topic['zadaniya']) }} {{ count($topic['zadaniya']) === 1 ? 'тип задания' : (count($topic['zadaniya']) < 5 ? 'типа заданий' : 'типов заданий') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="toggleAllZadaniyaInTopic('{{ $topic['topic_id'] }}')"
                                class="px-3 py-1.5 text-sm bg-slate-600 hover:bg-slate-500 text-slate-300 rounded-lg transition">
                            <span x-show="!areAllZadaniyaSelectedInTopic('{{ $topic['topic_id'] }}')">Выбрать все</span>
                            <span x-show="areAllZadaniyaSelectedInTopic('{{ $topic['topic_id'] }}')">Снять все</span>
                        </button>
                        <button @click="toggleTopic('{{ $topic['topic_id'] }}')"
                                class="text-slate-400 hover:text-white transition">
                            <svg class="w-5 h-5 transition-transform" :class="expandedTopics.includes('{{ $topic['topic_id'] }}') ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Zadaniya Grid --}}
                <div x-show="expandedTopics.includes('{{ $topic['topic_id'] }}')"
                     x-transition
                     class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach($topic['zadaniya'] as $zadanie)
                        <label class="flex flex-col gap-3 p-3 bg-slate-700/20 hover:bg-slate-700/40 rounded-xl border border-slate-600/50 hover:border-slate-500 cursor-pointer transition-all group">
                            <div class="flex items-start gap-3">
                                <input type="checkbox"
                                       x-model="selectedZadaniya"
                                       value="{{ $zadanie['zadanie_id'] }}"
                                       class="w-5 h-5 mt-0.5 rounded border-slate-500 text-{{ $topic['color'] }}-500 focus:ring-{{ $topic['color'] }}-500 focus:ring-offset-slate-800 flex-shrink-0">

                                <div class="flex-1 min-w-0">
                                    <h4 class="text-slate-200 font-medium text-sm leading-snug">{{ $zadanie['instruction'] }}</h4>
                                </div>
                            </div>

                            @if($zadanie['example'])
                                <div class="pl-8 pr-2">
                                    <div class="p-2 bg-slate-800/50 rounded-lg border border-slate-600/30">
                                        <p class="text-slate-400 text-xs mb-1.5">Пример:</p>
                                        @if($zadanie['example']['type'] === 'statements')
                                            <p class="text-slate-300 text-xs latex-content">{{ $zadanie['example']['text'] }}</p>
                                        @else
                                            @if(!empty($zadanie['example']['expression']))
                                                <p class="text-slate-200 text-sm latex-content">${{ $zadanie['example']['expression'] }}$</p>
                                            @endif
                                            @if(!empty($zadanie['example']['text']))
                                                <p class="text-slate-300 text-xs latex-content">{{ $zadanie['example']['text'] }}</p>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endif
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
                <span class="text-slate-400 text-sm">Выбрано типов</span>
                <div class="font-bold text-2xl" :class="selectedZadaniya.length > 0 ? 'text-emerald-400' : 'text-slate-500'"
                     x-text="selectedZadaniya.length">
                </div>
            </div>
        </div>

        {{-- Warning if no types selected --}}
        <div x-show="selectedZadaniya.length === 0" class="mb-4 p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl">
            <p class="text-amber-400 text-sm">⚠️ Выберите хотя бы один тип задания для генерации варианта</p>
        </div>

        <button @click="generateVariant()"
                :disabled="selectedZadaniya.length === 0"
                :class="selectedZadaniya.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-600' : 'bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40'"
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
const topicsData = @json($topicsWithZadaniya);

function ogeGenerator() {
    // Получаем все zadanie_id для дефолтного выбора (все кроме тем 18 и 19)
    const defaultZadaniya = [];
    topicsData.forEach(topic => {
        if (!['18', '19'].includes(topic.topic_id)) {
            topic.zadaniya.forEach(zadanie => {
                defaultZadaniya.push(zadanie.zadanie_id);
            });
        }
    });

    return {
        selectedZadaniya: defaultZadaniya,
        expandedTopics: topicsData.map(t => t.topic_id), // Все темы развёрнуты по умолчанию
        templates: JSON.parse(localStorage.getItem('ogeTemplates') || '[]'),
        showSaveTemplateModal: false,
        newTemplateName: '',

        toggleTopic(topicId) {
            const index = this.expandedTopics.indexOf(topicId);
            if (index > -1) {
                this.expandedTopics.splice(index, 1);
            } else {
                this.expandedTopics.push(topicId);
            }
        },

        toggleAllZadaniyaInTopic(topicId) {
            const topic = topicsData.find(t => t.topic_id === topicId);
            if (!topic) return;

            const topicZadaniyaIds = topic.zadaniya.map(z => z.zadanie_id);
            const allSelected = topicZadaniyaIds.every(id => this.selectedZadaniya.includes(id));

            if (allSelected) {
                // Снять все zadaniya этой темы
                this.selectedZadaniya = this.selectedZadaniya.filter(id => !topicZadaniyaIds.includes(id));
            } else {
                // Выбрать все zadaniya этой темы
                topicZadaniyaIds.forEach(id => {
                    if (!this.selectedZadaniya.includes(id)) {
                        this.selectedZadaniya.push(id);
                    }
                });
            }
        },

        areAllZadaniyaSelectedInTopic(topicId) {
            const topic = topicsData.find(t => t.topic_id === topicId);
            if (!topic) return false;

            const topicZadaniyaIds = topic.zadaniya.map(z => z.zadanie_id);
            return topicZadaniyaIds.every(id => this.selectedZadaniya.includes(id));
        },

        selectAllZadaniya() {
            this.selectedZadaniya = [];
            topicsData.forEach(topic => {
                topic.zadaniya.forEach(zadanie => {
                    this.selectedZadaniya.push(zadanie.zadanie_id);
                });
            });
        },

        selectCategory(category) {
            this.selectedZadaniya = [];
            topicsData.forEach(topic => {
                if (topic.category === category) {
                    topic.zadaniya.forEach(zadanie => {
                        this.selectedZadaniya.push(zadanie.zadanie_id);
                    });
                }
            });
        },

        clearAll() {
            this.selectedZadaniya = [];
        },

        generateVariant() {
            if (this.selectedZadaniya.length === 0) return;

            // Generate short beautiful hash (6 characters)
            const hash = Math.random().toString(36).substring(2, 8);

            // Build URL with selected zadaniya as query parameter
            const zadaniya = this.selectedZadaniya.sort().join(',');
            const url = `{{ route('test.oge.show', ['hash' => '__HASH__']) }}`.replace('__HASH__', hash) + '?zadaniya=' + zadaniya;

            // Open in new tab
            window.open(url, '_blank');
        },

        // Template management
        saveTemplate() {
            if (!this.newTemplateName.trim()) return;

            const newTemplate = {
                id: Date.now().toString(),
                name: this.newTemplateName.trim(),
                zadaniya: [...this.selectedZadaniya],
                createdAt: new Date().toISOString()
            };

            this.templates.push(newTemplate);
            localStorage.setItem('ogeTemplates', JSON.stringify(this.templates));

            // Reset modal
            this.newTemplateName = '';
            this.showSaveTemplateModal = false;

            // Show success message (optional)
            console.log('Шаблон сохранён:', newTemplate.name);
        },

        loadTemplate(templateId) {
            const template = this.templates.find(t => t.id === templateId);
            if (!template) return;

            // Load zadaniya from template
            this.selectedZadaniya = [...template.zadaniya];

            console.log('Шаблон загружен:', template.name);
        },

        deleteTemplate(templateId) {
            if (!confirm('Удалить этот шаблон?')) return;

            this.templates = this.templates.filter(t => t.id !== templateId);
            localStorage.setItem('ogeTemplates', JSON.stringify(this.templates));

            console.log('Шаблон удалён');
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
