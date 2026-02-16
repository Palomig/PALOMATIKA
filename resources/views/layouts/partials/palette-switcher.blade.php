<div x-data="paletteWidget()" class="fixed bottom-4 right-4 z-[90]">
    <div x-show="open" x-cloak @click.away="open = false"
         class="mb-2 w-[320px] max-h-[60vh] overflow-hidden rounded-2xl border border-white/15 bg-dark-light/95 backdrop-blur-xl shadow-2xl">
        <div class="px-4 py-3 border-b border-white/10 flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-white">Цветовая палитра</div>
                <div class="text-[11px] text-gray-400">29 вариантов из UI Catalog</div>
            </div>
            <button @click="open = false" class="w-7 h-7 rounded-lg border border-white/10 text-gray-400 hover:text-white hover:bg-white/10 transition" aria-label="Закрыть">
                <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-2 overflow-y-auto max-h-[48vh]">
            <template x-for="item in items" :key="item.key">
                <button @click="apply(item.key)"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-xl transition mb-1"
                        :class="item.key === current ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5'">
                    <span class="flex items-center gap-1.5">
                        <span class="w-4 h-4 rounded-md border border-white/15" :style="'background:' + item.primary"></span>
                        <span class="w-4 h-4 rounded-md border border-white/15" :style="'background:' + item.secondary"></span>
                        <span class="w-4 h-4 rounded-md border border-white/15" :style="'background:' + item.cta"></span>
                    </span>
                    <span class="flex-1 text-left text-[13px] truncate" x-text="item.label"></span>
                    <svg x-show="item.key === current" class="w-4 h-4 text-coral flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </template>
        </div>
    </div>

    <button @click="open = !open"
            class="w-12 h-12 rounded-2xl border border-white/15 bg-dark-light/90 text-white shadow-2xl backdrop-blur-xl hover:bg-dark-lighter transition"
            aria-label="Открыть выбор палитры">
        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3 3-3-3m3 3V8"/>
        </svg>
    </button>
</div>

<script>
    function paletteWidget() {
        return {
            open: false,
            items: window.__themeCatalog || [],
            current: window.__currentTheme || 'educational-app',
            apply(key) {
                if (!key || key === this.current) {
                    this.open = false;
                    return;
                }
                localStorage.setItem('palomatika_theme', key);
                window.location.reload();
            }
        };
    }
</script>
