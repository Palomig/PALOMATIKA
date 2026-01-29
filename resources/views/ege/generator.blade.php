<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Генератор вариантов ЕГЭ - PALOMATIKA</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: {
                            DEFAULT: '#06090f',
                            50: '#0a0f1a',
                            100: '#0d1320',
                            200: '#111827',
                            300: '#1a2332',
                            400: '#243044',
                            500: '#2e3d56'
                        },
                        accent: {
                            DEFAULT: '#8b5cf6',
                            light: '#a78bfa',
                            dark: '#7c3aed'
                        }
                    }
                }
            }
        }
    </script>

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
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0a0f1a; }
        ::-webkit-scrollbar-thumb { background: #2e3d56; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #3d4f6a; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen bg-dark text-gray-200" x-data="egeGenerator()">

<div class="max-w-7xl mx-auto px-4 py-8">
    {{-- Navigation --}}
    <div class="flex justify-between items-center mb-8 text-sm bg-dark-100 rounded-xl p-4 border border-dark-400/50">
        <a href="{{ route('ege.index') }}" class="text-accent-light hover:text-accent transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Все задания ЕГЭ
        </a>
        <div class="flex gap-4">
            <a href="{{ route('topics.index') }}" class="text-gray-400 hover:text-gray-200 transition">ОГЭ</a>
            <span class="text-accent-light font-semibold">ЕГЭ</span>
        </div>
    </div>

    {{-- Header --}}
    <div class="text-center mb-10">
        <div class="inline-block bg-accent/20 text-accent-light px-4 py-1 rounded-full text-sm font-medium mb-4 border border-accent/30">
            Профильный уровень
        </div>
        <h1 class="text-4xl font-bold text-white mb-3">Генератор вариантов ЕГЭ</h1>
        <p class="text-gray-400 text-lg">Выберите типы заданий для генерации тренировочного варианта</p>
    </div>

    {{-- Info Box --}}
    <div class="bg-gradient-to-r from-accent/10 to-purple-500/10 rounded-2xl p-6 mb-8 border border-accent/30">
        <h3 class="text-accent-light font-semibold text-lg mb-2">Как это работает?</h3>
        <p class="text-gray-300 leading-relaxed">
            Выберите нужные типы заданий из каждой темы. Для каждого типа показан пример задачи.
            При генерации варианта из каждой выбранной темы будет выбрано по одному случайному заданию.
        </p>
    </div>

    {{-- Quick Presets --}}
    <div class="bg-dark-100 rounded-2xl p-6 mb-6 border border-dark-400/50">
        <h2 class="text-white font-semibold text-lg mb-4">Быстрый выбор</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button @click="selectAllZadaniya()"
                    class="px-4 py-3 bg-gradient-to-r from-accent/20 to-accent/30 hover:from-accent/30 hover:to-accent/40 text-accent-light font-medium rounded-lg border border-accent/30 transition-all">
                Все типы
            </button>
            <button @click="selectCategory('part1')"
                    class="px-4 py-3 bg-gradient-to-r from-blue-500/20 to-blue-600/20 hover:from-blue-500/30 hover:to-blue-600/30 text-blue-400 font-medium rounded-lg border border-blue-500/30 transition-all">
                Часть 1 (1-12)
            </button>
            <button @click="selectCategory('part2')"
                    class="px-4 py-3 bg-gradient-to-r from-fuchsia-500/20 to-fuchsia-600/20 hover:from-fuchsia-500/30 hover:to-fuchsia-600/30 text-fuchsia-400 font-medium rounded-lg border border-fuchsia-500/30 transition-all">
                Часть 2 (13-19)
            </button>
            <button @click="clearAll()"
                    class="px-4 py-3 bg-gradient-to-r from-red-500/20 to-red-600/20 hover:from-red-500/30 hover:to-red-600/30 text-red-400 font-medium rounded-lg border border-red-500/30 transition-all">
                Очистить
            </button>
        </div>
    </div>

    {{-- Saved Templates --}}
    <div class="bg-dark-100 rounded-2xl p-6 mb-6 border border-dark-400/50">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-white font-semibold text-lg">Мои шаблоны</h2>
            <button @click="showSaveTemplateModal = true"
                    :disabled="selectedZadaniya.length === 0"
                    :class="selectedZadaniya.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-accent-dark'"
                    class="px-4 py-2 bg-accent text-white text-sm font-medium rounded-lg transition-all">
                + Сохранить текущий выбор
            </button>
        </div>

        <div x-show="templates.length === 0" class="text-gray-500 text-sm text-center py-4">
            У вас пока нет сохранённых шаблонов
        </div>

        <div x-show="templates.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <template x-for="template in templates" :key="template.id">
                <div class="p-4 bg-dark-300/30 rounded-lg border border-dark-400/50 hover:border-accent/30 transition-all group">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="text-gray-200 font-medium" x-text="template.name"></h3>
                        <button @click="deleteTemplate(template.id)"
                                class="text-gray-500 hover:text-red-400 transition-colors opacity-0 group-hover:opacity-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-gray-500 text-xs mb-3">
                        <span x-text="template.zadaniya.length"></span> типов заданий
                    </p>
                    <button @click="loadTemplate(template.id)"
                            class="w-full px-3 py-2 bg-dark-400 hover:bg-dark-500 text-gray-200 text-sm rounded-lg transition-all">
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
             class="bg-dark-100 rounded-2xl p-6 max-w-md w-full border border-dark-400 shadow-2xl">
            <h3 class="text-white font-semibold text-xl mb-4">Сохранить шаблон</h3>

            <div class="mb-4">
                <label class="block text-gray-300 text-sm mb-2">Название шаблона</label>
                <input type="text"
                       x-model="newTemplateName"
                       @keydown.enter="saveTemplate()"
                       placeholder="Например: Часть 1 без геометрии"
                       class="w-full px-4 py-3 bg-dark-300 border border-dark-400 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-accent transition-colors">
            </div>

            <div class="mb-6">
                <p class="text-gray-400 text-sm">
                    Будет сохранено <span class="text-accent-light font-semibold" x-text="selectedZadaniya.length"></span> типов заданий
                </p>
            </div>

            <div class="flex gap-3">
                <button @click="showSaveTemplateModal = false"
                        class="flex-1 px-4 py-3 bg-dark-300 hover:bg-dark-400 text-gray-300 rounded-lg transition-all">
                    Отмена
                </button>
                <button @click="saveTemplate()"
                        :disabled="!newTemplateName.trim()"
                        :class="!newTemplateName.trim() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-accent-dark'"
                        class="flex-1 px-4 py-3 bg-accent text-white font-medium rounded-lg transition-all">
                    Сохранить
                </button>
            </div>
        </div>
    </div>

    {{-- Topics with Blocks --}}
    <div class="space-y-4 mb-8">
        @foreach($topicsWithZadaniya as $topic)
            <div class="bg-dark-100 rounded-2xl border border-dark-400/50 overflow-hidden">
                {{-- Topic Header --}}
                <div class="p-4 bg-dark-200/50 border-b border-dark-400/30 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-accent/20 flex items-center justify-center text-accent-light font-bold text-xl border border-accent/30">
                            {{ $topic['topic_number'] }}
                        </div>
                        <div>
                            <h3 class="text-white font-semibold">{{ $topic['title'] }}</h3>
                            <p class="text-gray-500 text-sm">
                                {{ count($topic['zadaniya']) }} {{ count($topic['zadaniya']) === 1 ? 'тип задания' : (count($topic['zadaniya']) < 5 ? 'типа заданий' : 'типов заданий') }}
                                <span class="ml-2 text-xs px-2 py-0.5 rounded-full {{ $topic['category'] === 'part1' ? 'bg-blue-500/20 text-blue-400' : 'bg-fuchsia-500/20 text-fuchsia-400' }}">
                                    {{ $topic['category'] === 'part1' ? 'Часть 1' : 'Часть 2' }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="toggleAllZadaniyaInTopic('{{ $topic['topic_id'] }}')"
                                class="px-3 py-1.5 text-sm bg-dark-400 hover:bg-dark-500 text-gray-300 rounded-lg transition">
                            <span x-show="!areAllZadaniyaSelectedInTopic('{{ $topic['topic_id'] }}')">Выбрать все</span>
                            <span x-show="areAllZadaniyaSelectedInTopic('{{ $topic['topic_id'] }}')">Снять все</span>
                        </button>
                        <button @click="toggleTopic('{{ $topic['topic_id'] }}')"
                                class="text-gray-400 hover:text-white transition">
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
                        <label class="flex flex-col gap-3 p-3 bg-dark-200/40 hover:bg-dark-200/70 rounded-xl border border-dark-400/30 hover:border-accent/30 cursor-pointer transition-all group">
                            <div class="flex items-start gap-3">
                                <input type="checkbox"
                                       x-model="selectedZadaniya"
                                       value="{{ $zadanie['zadanie_id'] }}"
                                       class="w-5 h-5 mt-0.5 rounded border-dark-500 text-accent focus:ring-accent focus:ring-offset-dark flex-shrink-0">

                                <div class="flex-1 min-w-0">
                                    <h4 class="text-gray-200 font-medium text-sm leading-snug">{{ $zadanie['instruction'] }}</h4>
                                </div>
                            </div>

                            @if($zadanie['example'])
                                <div class="pl-8 pr-2">
                                    <div class="p-2 bg-dark/50 rounded-lg border border-dark-400/20">
                                        <p class="text-gray-500 text-xs mb-1.5">Пример:</p>
                                        @if(!empty($zadanie['example']['expression']))
                                            <p class="text-gray-200 text-sm latex-content">${{ $zadanie['example']['expression'] }}$</p>
                                        @endif
                                        @if(!empty($zadanie['example']['text']))
                                            <p class="text-gray-300 text-xs latex-content leading-relaxed">{{ Str::limit($zadanie['example']['text'], 120) }}</p>
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
    <div class="bg-dark-100 rounded-2xl p-6 mb-8 border border-dark-400/50">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-white font-semibold text-lg">Готовы начать?</h2>
                <p class="text-gray-500 text-sm mt-1">Каждый клик создаёт новый уникальный вариант</p>
            </div>
            <div class="bg-dark-300/50 rounded-xl px-5 py-3 border border-dark-400 text-center">
                <span class="text-gray-500 text-sm">Выбрано типов</span>
                <div class="font-bold text-2xl" :class="selectedZadaniya.length > 0 ? 'text-accent-light' : 'text-gray-600'"
                     x-text="selectedZadaniya.length">
                </div>
            </div>
        </div>

        {{-- Warning if no types selected --}}
        <div x-show="selectedZadaniya.length === 0" class="mb-4 p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl">
            <p class="text-amber-400 text-sm">Выберите хотя бы один тип задания для генерации варианта</p>
        </div>

        <button @click="generateVariant()"
                :disabled="selectedZadaniya.length === 0"
                :class="selectedZadaniya.length === 0 ? 'opacity-50 cursor-not-allowed bg-dark-400' : 'bg-gradient-to-r from-accent to-accent-dark hover:from-accent-light hover:to-accent shadow-lg shadow-accent/25 hover:shadow-accent/40'"
                class="block w-full py-4 text-white font-bold text-lg rounded-xl transition-all text-center">
            Сгенерировать вариант
        </button>
    </div>

    {{-- Footer --}}
    <div class="text-center mt-10 text-gray-600 text-sm">
        Задания взяты из базы <a href="{{ route('ege.index') }}" class="text-accent-light hover:underline">PALOMATIKA</a>
    </div>
</div>

<script>
const topicsData = @json($topicsWithZadaniya);

function egeGenerator() {
    // По умолчанию выбираем все zadaniya
    const defaultZadaniya = [];
    topicsData.forEach(topic => {
        topic.zadaniya.forEach(zadanie => {
            defaultZadaniya.push(zadanie.zadanie_id);
        });
    });

    return {
        selectedZadaniya: defaultZadaniya,
        expandedTopics: topicsData.map(t => t.topic_id),
        templates: JSON.parse(localStorage.getItem('egeTemplates') || '[]'),
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
                this.selectedZadaniya = this.selectedZadaniya.filter(id => !topicZadaniyaIds.includes(id));
            } else {
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
            return topic.zadaniya.map(z => z.zadanie_id).every(id => this.selectedZadaniya.includes(id));
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

        async generateVariant() {
            if (this.selectedZadaniya.length === 0) return;

            const hash = Math.random().toString(36).substring(2, 8);

            try {
                const response = await fetch('{{ route('api.ege.save-variant') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        hash: hash,
                        zadaniya: this.selectedZadaniya.sort()
                    })
                });

                if (!response.ok) {
                    console.error('Failed to save variant configuration');
                    return;
                }

                const url = `{{ route('ege.variant', ['hash' => '__HASH__']) }}`.replace('__HASH__', hash);
                window.open(url, '_blank');
            } catch (error) {
                console.error('Error generating variant:', error);
            }
        },

        saveTemplate() {
            if (!this.newTemplateName.trim()) return;

            const newTemplate = {
                id: Date.now().toString(),
                name: this.newTemplateName.trim(),
                zadaniya: [...this.selectedZadaniya],
                createdAt: new Date().toISOString()
            };

            this.templates.push(newTemplate);
            localStorage.setItem('egeTemplates', JSON.stringify(this.templates));

            this.newTemplateName = '';
            this.showSaveTemplateModal = false;
        },

        loadTemplate(templateId) {
            const template = this.templates.find(t => t.id === templateId);
            if (!template) return;
            this.selectedZadaniya = [...template.zadaniya];
        },

        deleteTemplate(templateId) {
            if (!confirm('Удалить этот шаблон?')) return;
            this.templates = this.templates.filter(t => t.id !== templateId);
            localStorage.setItem('egeTemplates', JSON.stringify(this.templates));
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
