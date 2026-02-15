<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Генератор вариантов ОГЭ - PALOMATIKA</title>
    @include('partials.head-config')
    @include('partials.head-katex')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,500;7..72,700&display=swap" rel="stylesheet">
    <style>
        .exam-title { font-family: 'Literata', serif; }
        .katex { font-size: 1.02em; }
    </style>
</head>
@php
    $isLegacyRoute = request()->routeIs('test.*');
    $backRoute = $isLegacyRoute ? route('test.index') : route('topics.index');
    $backLabel = $isLegacyRoute ? 'Все задания' : 'Темы ОГЭ';
    $variantRouteTemplate = $isLegacyRoute
        ? route('test.oge.show', ['hash' => '__HASH__'])
        : route('oge.show', ['hash' => '__HASH__']);
@endphp
<body class="min-h-screen bg-dark-50 text-slate-200" x-data="ogeGenerator()">

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-6 rounded-xl border border-slate-800 bg-dark-light/40 px-4 py-3 flex flex-wrap items-center justify-between gap-3 text-sm">
        <a href="{{ $backRoute }}" class="text-slate-300 hover:text-white transition">← {{ $backLabel }}</a>
        <div class="flex items-center gap-2">
            <a href="{{ route('topics.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 transition">Темы</a>
            <a href="{{ route('test.generator') }}" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 transition">Кастомный тест</a>
        </div>
    </div>

    <header class="mb-8">
        <h1 class="exam-title text-3xl sm:text-4xl text-white">Генератор вариантов ОГЭ</h1>
        <p class="text-slate-400 mt-2">Выберите типы заданий для варианта. На каждую тему попадёт по одному случайному заданию из выбранных типов.</p>
    </header>

    <section class="mb-6 rounded-xl border border-slate-800 bg-dark-light/30 p-4">
        <div class="flex flex-wrap gap-2">
            <button @click="selectAllZadaniya()" class="px-4 py-2 rounded-lg border border-emerald-700/60 text-emerald-300 hover:bg-emerald-900/20 transition">Выбрать все</button>
            <button @click="selectCategory('algebra')" class="px-4 py-2 rounded-lg border border-sky-700/60 text-sky-300 hover:bg-sky-900/20 transition">Только алгебра</button>
            <button @click="selectCategory('geometry')" class="px-4 py-2 rounded-lg border border-amber-700/60 text-amber-300 hover:bg-amber-900/20 transition">Только геометрия</button>
            <button @click="clearAll()" class="px-4 py-2 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 transition">Очистить</button>
        </div>
    </section>

    <section class="mb-6 rounded-xl border border-slate-800 bg-dark-light/30 p-4">
        <div class="flex items-center justify-between gap-3 mb-3">
            <h2 class="exam-title text-lg text-white">Шаблоны выбора</h2>
            <button @click="showSaveTemplateModal = true"
                    :disabled="selectedZadaniya.length === 0"
                    :class="selectedZadaniya.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                    class="px-3 py-2 rounded-lg bg-emerald-700 text-white hover:bg-emerald-600 transition text-sm">
                Сохранить текущий
            </button>
        </div>

        <div x-show="isTemplatesLoading" class="text-slate-500 text-sm">Загрузка шаблонов...</div>
        <div x-show="!isTemplatesLoading && templates.length === 0" class="text-slate-500 text-sm">Сохранённых шаблонов пока нет.</div>

        <div x-show="templates.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <template x-for="template in templates" :key="template.id">
                <div class="rounded-lg border border-slate-700 bg-dark-light/40 p-3">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-slate-200 font-medium" x-text="template.name"></h3>
                        <button @click="deleteTemplate(template.id)" class="text-slate-500 hover:text-red-400">✕</button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1"><span x-text="template.zadaniya.length"></span> типов</p>
                    <button @click="loadTemplate(template.id)" class="mt-3 w-full px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-200 text-sm transition">Применить</button>
                </div>
            </template>
        </div>
    </section>

    <div x-show="showSaveTemplateModal"
         x-cloak
         @click.self="showSaveTemplateModal = false"
         class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="showSaveTemplateModal = false" class="w-full max-w-md rounded-xl border border-slate-700 bg-dark-light p-5 shadow-2xl">
            <h3 class="exam-title text-xl text-white mb-3">Сохранить шаблон</h3>
            <input type="text"
                   x-model="newTemplateName"
                   @keydown.enter="saveTemplate()"
                   placeholder="Например: Повтор алгебры"
                   class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-600">
            <div class="mt-2 text-sm text-slate-400">Будет сохранено <span class="text-emerald-300" x-text="selectedZadaniya.length"></span> типов.</div>
            <div class="mt-4 flex gap-2">
                <button @click="showSaveTemplateModal = false" class="flex-1 px-4 py-2 rounded-lg bg-slate-700 text-slate-200 hover:bg-slate-600 transition">Отмена</button>
                <button @click="saveTemplate()"
                        :disabled="!newTemplateName.trim()"
                        :class="!newTemplateName.trim() ? 'opacity-50 cursor-not-allowed' : ''"
                        class="flex-1 px-4 py-2 rounded-lg bg-emerald-700 text-white hover:bg-emerald-600 transition">
                    Сохранить
                </button>
            </div>
        </div>
    </div>

    <section class="space-y-3 mb-8">
        @foreach($topicsWithZadaniya as $topic)
            <div class="rounded-xl border border-slate-800 bg-dark-light/30 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-lg border border-{{ $topic['color'] }}-700/60 text-{{ $topic['color'] }}-300 bg-{{ $topic['color'] }}-900/20 flex items-center justify-center font-semibold shrink-0">
                            {{ $topic['topic_number'] }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-white font-medium truncate">{{ $topic['title'] }}</h3>
                            <p class="text-slate-500 text-xs">{{ count($topic['zadaniya']) }} типов</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button @click="toggleAllZadaniyaInTopic('{{ $topic['topic_id'] }}')" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 text-xs hover:bg-slate-700 transition">
                            <span x-show="!areAllZadaniyaSelectedInTopic('{{ $topic['topic_id'] }}')">Выбрать всё</span>
                            <span x-show="areAllZadaniyaSelectedInTopic('{{ $topic['topic_id'] }}')">Снять всё</span>
                        </button>
                        <button @click="toggleTopic('{{ $topic['topic_id'] }}')" class="px-2 py-1 rounded text-slate-400 hover:text-white transition">
                            <span :class="expandedTopics.includes('{{ $topic['topic_id'] }}') ? 'rotate-180' : ''" class="inline-block transition">⌄</span>
                        </button>
                    </div>
                </div>

                <div x-show="expandedTopics.includes('{{ $topic['topic_id'] }}')" x-transition class="p-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach($topic['zadaniya'] as $zadanie)
                        <label class="rounded-lg border border-slate-700 bg-slate-900/40 hover:bg-slate-900/70 p-3 transition cursor-pointer">
                            <div class="flex items-start gap-2">
                                <input type="checkbox"
                                       x-model="selectedZadaniya"
                                       value="{{ $zadanie['zadanie_id'] }}"
                                       class="w-4 h-4 mt-0.5 rounded border-slate-500 text-{{ $topic['color'] }}-500 focus:ring-{{ $topic['color'] }}-500 focus:ring-offset-slate-900">
                                <h4 class="text-slate-200 text-sm leading-snug">{{ $zadanie['instruction'] }}</h4>
                            </div>

                            @if($zadanie['example'])
                                <div class="mt-2 ml-6 rounded-md border border-slate-800 bg-slate-950/60 p-2">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500 mb-1">Пример</p>
                                    @if($zadanie['example']['type'] === 'statements')
                                        <p class="text-xs text-slate-300 latex-content">{{ $zadanie['example']['text'] }}</p>
                                    @else
                                        @if(!empty($zadanie['example']['expression']))
                                            <p class="text-sm text-slate-200 latex-content">${{ $zadanie['example']['expression'] }}$</p>
                                        @endif
                                        @if(!empty($zadanie['example']['text']))
                                            <p class="text-xs text-slate-300 latex-content">{{ $zadanie['example']['text'] }}</p>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>

    <section class="rounded-xl border border-slate-800 bg-dark-light/30 p-5">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="exam-title text-xl text-white">Сформировать вариант</h2>
                <p class="text-sm text-slate-400">Каждая генерация создаёт новый детерминированный hash.</p>
            </div>
            <div class="px-4 py-2 rounded-lg border border-slate-700 bg-slate-900/50 text-center">
                <div class="text-xs text-slate-500 uppercase tracking-wide">Выбрано типов</div>
                <div class="text-2xl font-semibold" :class="selectedZadaniya.length ? 'text-emerald-300' : 'text-slate-500'" x-text="selectedZadaniya.length"></div>
            </div>
        </div>

        <p x-show="selectedZadaniya.length === 0" class="text-sm text-amber-300 mb-3">Выберите хотя бы один тип задания.</p>

        <button @click="generateVariant()"
                :disabled="selectedZadaniya.length === 0"
                :class="selectedZadaniya.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-700' : 'bg-emerald-700 hover:bg-emerald-600'"
                class="w-full py-3 rounded-lg text-white font-semibold transition">
            Сгенерировать вариант
        </button>
    </section>
</div>

<script>
const topicsData = @json($topicsWithZadaniya);
const variantRouteTemplate = @json($variantRouteTemplate);

function ogeGenerator() {
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
        expandedTopics: topicsData.map(t => t.topic_id),
        templates: [],
        isTemplatesLoading: false,
        showSaveTemplateModal: false,
        newTemplateName: '',
        api: {
            list: '/api/oge/templates',
            store: '/api/oge/templates',
            destroy: (id) => `/api/oge/templates/${id}`,
        },

        init() {
            this.fetchTemplates();
        },

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

        async generateVariant() {
            if (this.selectedZadaniya.length === 0) return;

            const hash = Math.random().toString(36).substring(2, 8);

            try {
                const response = await fetch('{{ route('test.oge.save') }}', {
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

                const url = variantRouteTemplate.replace('__HASH__', hash);
                window.open(url, '_blank');
            } catch (error) {
                console.error('Error generating variant:', error);
            }
        },

        async fetchTemplates() {
            this.isTemplatesLoading = true;

            try {
                const response = await fetch(this.api.list, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Failed to load templates: ${response.status}`);
                }

                const payload = await response.json();
                this.templates = Array.isArray(payload.templates) ? payload.templates : [];
            } catch (error) {
                console.error('Error loading templates:', error);
                this.templates = [];
            } finally {
                this.isTemplatesLoading = false;
            }
        },

        async saveTemplate() {
            if (!this.newTemplateName.trim()) return;

            try {
                const response = await fetch(this.api.store, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        name: this.newTemplateName.trim(),
                        zadaniya: [...this.selectedZadaniya]
                    })
                });

                if (!response.ok) {
                    throw new Error(`Failed to save template: ${response.status}`);
                }

                const payload = await response.json();
                if (payload?.template) {
                    this.templates.unshift(payload.template);
                }

                this.newTemplateName = '';
                this.showSaveTemplateModal = false;
            } catch (error) {
                console.error('Error saving template:', error);
            }
        },

        loadTemplate(templateId) {
            const template = this.templates.find(t => String(t.id) === String(templateId));
            if (!template) return;
            this.selectedZadaniya = [...template.zadaniya];
        },

        async deleteTemplate(templateId) {
            if (!confirm('Удалить этот шаблон?')) return;

            try {
                const response = await fetch(this.api.destroy(templateId), {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) {
                    throw new Error(`Failed to delete template: ${response.status}`);
                }

                this.templates = this.templates.filter(t => String(t.id) !== String(templateId));
            } catch (error) {
                console.error('Error deleting template:', error);
            }
        }
    }
}

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
