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

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900" x-data="ogeGenerator()">

<div class="max-w-4xl mx-auto px-4 py-8">
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
        <p class="text-slate-400 text-lg">Выберите задания для своего варианта (темы 6–19)</p>
    </div>

    {{-- Info Box --}}
    <div class="bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-2xl p-6 mb-8 border border-blue-500/30">
        <h3 class="text-blue-400 font-semibold text-lg mb-2">ℹ️ Как это работает?</h3>
        <p class="text-slate-300 leading-relaxed">
            Выберите нужные темы из списка ниже и создайте свой персональный вариант ОГЭ.
            Можно использовать готовые пресеты или настроить набор заданий вручную.
            Каждый вариант получает уникальную ссылку — можно делиться с друзьями!
        </p>
    </div>

    {{-- Presets --}}
    <div class="bg-slate-800 rounded-2xl p-6 mb-6 border border-slate-700">
        <h2 class="text-white font-semibold text-lg mb-4">Быстрый выбор</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button @click="selectPreset('full')"
                    class="px-4 py-3 bg-gradient-to-r from-emerald-500/20 to-emerald-600/20 hover:from-emerald-500/30 hover:to-emerald-600/30 text-emerald-400 font-medium rounded-lg border border-emerald-500/30 transition-all">
                🎯 Полный вариант
            </button>
            <button @click="selectPreset('algebra')"
                    class="px-4 py-3 bg-gradient-to-r from-blue-500/20 to-blue-600/20 hover:from-blue-500/30 hover:to-blue-600/30 text-blue-400 font-medium rounded-lg border border-blue-500/30 transition-all">
                📊 Только алгебра
            </button>
            <button @click="selectPreset('geometry')"
                    class="px-4 py-3 bg-gradient-to-r from-violet-500/20 to-violet-600/20 hover:from-violet-500/30 hover:to-violet-600/30 text-violet-400 font-medium rounded-lg border border-violet-500/30 transition-all">
                📐 Только геометрия
            </button>
            <button @click="selectPreset('mini')"
                    class="px-4 py-3 bg-gradient-to-r from-amber-500/20 to-amber-600/20 hover:from-amber-500/30 hover:to-amber-600/30 text-amber-400 font-medium rounded-lg border border-amber-500/30 transition-all">
                ⚡ Мини-вариант
            </button>
        </div>
    </div>

    {{-- Topics Selection --}}
    <div class="bg-slate-800 rounded-2xl p-6 mb-8 border border-slate-700">
        <div class="flex justify-between items-center mb-5">
            <h2 class="text-white font-semibold text-lg">Выберите темы</h2>
            <div class="flex gap-2">
                <button @click="selectAll()"
                        class="px-3 py-1.5 text-sm bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-lg transition">
                    Выбрать все
                </button>
                <button @click="clearAll()"
                        class="px-3 py-1.5 text-sm bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-lg transition">
                    Очистить
                </button>
            </div>
        </div>

        @php
            $topics = [
                ['num' => '06', 'title' => 'Дроби и степени', 'color' => 'blue', 'category' => 'algebra'],
                ['num' => '07', 'title' => 'Числа, координатная прямая', 'color' => 'cyan', 'category' => 'algebra'],
                ['num' => '08', 'title' => 'Квадратные корни и степени', 'color' => 'violet', 'category' => 'algebra'],
                ['num' => '09', 'title' => 'Уравнения', 'color' => 'pink', 'category' => 'algebra'],
                ['num' => '10', 'title' => 'Теория вероятностей', 'color' => 'orange', 'category' => 'algebra'],
                ['num' => '11', 'title' => 'Графики функций', 'color' => 'rose', 'category' => 'algebra'],
                ['num' => '12', 'title' => 'Расчёты по формулам', 'color' => 'lime', 'category' => 'algebra'],
                ['num' => '13', 'title' => 'Неравенства', 'color' => 'teal', 'category' => 'algebra'],
                ['num' => '14', 'title' => 'Прогрессии', 'color' => 'indigo', 'category' => 'algebra'],
                ['num' => '15', 'title' => 'Треугольники', 'color' => 'emerald', 'category' => 'geometry'],
                ['num' => '16', 'title' => 'Окружность', 'color' => 'amber', 'category' => 'geometry'],
                ['num' => '17', 'title' => 'Четырёхугольники', 'color' => 'fuchsia', 'category' => 'geometry'],
                ['num' => '18', 'title' => 'Фигуры на клетчатой бумаге', 'color' => 'sky', 'category' => 'geometry'],
                ['num' => '19', 'title' => 'Анализ геом. высказываний', 'color' => 'red', 'category' => 'geometry'],
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($topics as $topic)
                <label class="flex items-center gap-3 p-4 bg-slate-700/30 hover:bg-slate-700/50 rounded-xl border border-slate-600/50 hover:border-slate-500 cursor-pointer transition-all group">
                    <input type="checkbox"
                           x-model="selected"
                           value="{{ $topic['num'] }}"
                           class="w-5 h-5 rounded border-slate-500 text-{{ $topic['color'] }}-500 focus:ring-{{ $topic['color'] }}-500 focus:ring-offset-slate-800">
                    <div class="flex-1 flex items-center gap-3">
                        <div class="text-2xl font-bold text-{{ $topic['color'] }}-400 group-hover:scale-110 transition-transform">
                            {{ ltrim($topic['num'], '0') }}
                        </div>
                        <div class="text-slate-300 text-sm leading-tight">{{ $topic['title'] }}</div>
                    </div>
                    <div class="text-xs px-2 py-1 rounded-md bg-slate-600/50 text-slate-400">
                        {{ $topic['category'] === 'algebra' ? '📊' : '📐' }}
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Generate Button --}}
    <div class="bg-slate-800 rounded-2xl p-6 mb-8 border border-slate-700">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-white font-semibold text-lg">Готовы начать?</h2>
                <p class="text-slate-400 text-sm mt-1">Каждый клик создаёт новый уникальный вариант</p>
            </div>
            <div class="bg-slate-700/50 rounded-xl px-5 py-3 border border-slate-600 text-center">
                <span class="text-slate-400 text-sm">Выбрано</span>
                <div class="font-bold text-2xl" :class="selected.length > 0 ? 'text-emerald-400' : 'text-slate-500'"
                     x-text="selected.length + ' ' + (selected.length === 1 ? 'задание' : selected.length > 1 && selected.length < 5 ? 'задания' : 'заданий')">
                </div>
            </div>
        </div>

        {{-- Warning if no topics selected --}}
        <div x-show="selected.length === 0" class="mb-4 p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl">
            <p class="text-amber-400 text-sm">⚠️ Выберите хотя бы одну тему для генерации варианта</p>
        </div>

        <button @click="generateVariant()"
                :disabled="selected.length === 0"
                :class="selected.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-600' : 'bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40'"
                class="block w-full py-4 text-white font-bold text-lg rounded-xl transition-all text-center">
            🎯 Сгенерировать вариант
        </button>
    </div>

    {{-- Features --}}
    <div class="grid grid-cols-3 gap-4 mt-10">
        <div class="bg-slate-800/50 rounded-xl p-5 text-center border border-slate-700">
            <div class="text-3xl mb-2">🎲</div>
            <div class="text-slate-400 text-sm">Случайные задания</div>
        </div>
        <div class="bg-slate-800/50 rounded-xl p-5 text-center border border-slate-700">
            <div class="text-3xl mb-2">🔗</div>
            <div class="text-slate-400 text-sm">Уникальная ссылка</div>
        </div>
        <div class="bg-slate-800/50 rounded-xl p-5 text-center border border-slate-700">
            <div class="text-3xl mb-2">🖨️</div>
            <div class="text-slate-400 text-sm">Готов к печати</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-center mt-10 text-slate-500 text-sm">
        Задания взяты из базы <a href="{{ route('test.index') }}" class="text-blue-400 hover:underline">PALOMATIKA</a>
    </div>
</div>

<script>
function ogeGenerator() {
    return {
        // По умолчанию выбраны все темы, кроме 18 и 19 (они не готовы)
        selected: ['06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17'],

        allTopics: ['06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19'],
        algebraTopics: ['06', '07', '08', '09', '10', '11', '12', '13', '14'],
        geometryTopics: ['15', '16', '17', '18', '19'],
        miniTopics: ['06', '07', '08', '09', '10', '11'], // 6 первых тем для мини-варианта

        selectAll() {
            this.selected = [...this.allTopics];
        },

        clearAll() {
            this.selected = [];
        },

        selectPreset(preset) {
            switch(preset) {
                case 'full':
                    this.selected = [...this.allTopics];
                    break;
                case 'algebra':
                    this.selected = [...this.algebraTopics];
                    break;
                case 'geometry':
                    this.selected = [...this.geometryTopics];
                    break;
                case 'mini':
                    this.selected = [...this.miniTopics];
                    break;
            }
        },

        generateVariant() {
            if (this.selected.length === 0) return;

            // Generate random hash
            const hash = Math.random().toString(36).substring(2, 12);

            // Build URL with selected topics as query parameter
            const topics = this.selected.sort().join(',');
            const url = `{{ route('test.oge.show', ['hash' => '__HASH__']) }}`.replace('__HASH__', hash) + '?topics=' + topics;

            // Navigate to generated variant
            window.location.href = url;
        }
    }
}
</script>

</body>
</html>
