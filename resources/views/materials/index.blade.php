<!doctype html>
<html lang="ru">
<head>
    @include('partials.head-config')
    <title>Материалы | JARVIS</title>
    <style>
        .material-bg {
            background: radial-gradient(circle at top, #243b6b 0%, #101323 50%, #0b0d17 100%);
            min-height: 100vh;
        }
        .cat-btn.active {
            background: rgba(6, 182, 212, 0.18);
            border-color: rgba(6, 182, 212, 0.5);
            color: #67e8f9;
        }
        .material-card {
            transition: background 0.15s, border-color 0.15s;
        }
    </style>
</head>
<body class="material-bg text-slate-100">

<div class="max-w-5xl mx-auto px-5 py-12"
     x-data="{
         activeCategory: null,
         materials: {{ Js::from($materials->map(fn($m) => ['id' => $m->id, 'slug' => $m->slug, 'title' => $m->title, 'excerpt' => $m->excerpt, 'category' => $m->category, 'published_at' => $m->published_at?->format('d.m.Y')])) }},
         get filtered() {
             if (!this.activeCategory) return this.materials;
             return this.materials.filter(m => m.category === this.activeCategory);
         },
         setCategory(cat) {
             this.activeCategory = this.activeCategory === cat ? null : cat;
         }
     }">

    {{-- Header --}}
    <header class="mb-10">
        <p class="text-cyan-300 uppercase tracking-[0.3em] text-xs mb-2">JARVIS</p>
        <h1 class="text-4xl md:text-5xl font-bold leading-tight">Учебные материалы</h1>
        <p class="text-slate-400 mt-3 max-w-2xl text-sm">Конспекты и разборы задач с пошаговыми решениями.</p>
    </header>

    <div class="flex flex-col md:flex-row gap-8">

        {{-- Sidebar --}}
        <aside class="md:w-52 flex-shrink-0">
            <p class="text-xs uppercase tracking-widest text-slate-500 mb-3">Разделы</p>
            <nav class="flex flex-col gap-1.5">
                <button
                    @click="activeCategory = null"
                    :class="activeCategory === null ? 'active' : ''"
                    class="cat-btn text-left text-sm px-3.5 py-2 rounded-xl border border-white/10 text-slate-300 hover:text-white hover:border-cyan-400/30 transition">
                    Все материалы
                    <span class="ml-1 text-xs text-slate-500">({{ $materials->count() }})</span>
                </button>

                @foreach($categories as $cat)
                    <button
                        @click="setCategory('{{ $cat }}')"
                        :class="activeCategory === '{{ $cat }}' ? 'active' : ''"
                        class="cat-btn text-left text-sm px-3.5 py-2 rounded-xl border border-white/10 text-slate-300 hover:text-white hover:border-cyan-400/30 transition">
                        {{ $cat }}
                        <span class="ml-1 text-xs text-slate-500">({{ $materials->where('category', $cat)->count() }})</span>
                    </button>
                @endforeach
            </nav>
        </aside>

        {{-- Materials grid --}}
        <div class="flex-1 min-w-0">

            {{-- Active category label --}}
            <div class="flex items-center justify-between mb-4 min-h-[1.5rem]">
                <p class="text-sm text-slate-400">
                    <span x-show="!activeCategory">Все материалы</span>
                    <span x-show="activeCategory" x-text="activeCategory"></span>
                    &nbsp;·&nbsp;
                    <span x-text="filtered.length"></span> материал<span x-text="filtered.length === 1 ? '' : (filtered.length >= 2 && filtered.length <= 4 ? 'а' : 'ов')"></span>
                </p>
                <template x-if="activeCategory">
                    <button @click="activeCategory = null" class="text-xs text-slate-500 hover:text-slate-300 transition">
                        × сбросить
                    </button>
                </template>
            </div>

            {{-- Cards --}}
            <div class="grid gap-3">
                <template x-for="m in filtered" :key="m.id">
                    <a :href="'/materials/' + m.slug"
                       class="material-card block rounded-2xl border border-white/10 bg-white/5 hover:bg-white/[0.08] hover:border-white/20 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h2 class="text-base font-semibold text-white leading-snug" x-text="m.title"></h2>
                                <p class="text-slate-400 text-sm mt-1 leading-snug" x-show="m.excerpt" x-text="m.excerpt"></p>
                            </div>
                            <div class="flex-shrink-0 flex flex-col items-end gap-1.5 pt-0.5">
                                <template x-if="m.category">
                                    <span class="text-xs px-2.5 py-1 rounded-full bg-cyan-500/15 text-cyan-300 border border-cyan-400/20 whitespace-nowrap"
                                          x-text="m.category"></span>
                                </template>
                                <span class="text-xs text-slate-600" x-text="m.published_at"></span>
                            </div>
                        </div>
                    </a>
                </template>

                <template x-if="filtered.length === 0">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-8 text-slate-400 text-sm">
                        Нет материалов в этом разделе.
                    </div>
                </template>
            </div>
        </div>

    </div>
</div>

</body>
</html>
