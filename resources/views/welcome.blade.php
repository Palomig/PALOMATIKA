<!DOCTYPE html>
<html lang="ru">
<head>
    <title>PALOMATIKA - Подготовка к ОГЭ по математике</title>
    <meta name="description" content="PALOMATIKA помогает сдать ОГЭ по математике через пазловый формат задач, диагностику пробелов и адаптивный трек обучения.">
    @include('partials.head-config')

    <style>
        .landing-bg {
            background:
                radial-gradient(1200px 600px at 85% -10%, rgba(255, 107, 107, 0.20), transparent 60%),
                radial-gradient(900px 420px at 0% 0%, rgba(56, 189, 248, 0.12), transparent 50%),
                #090c16;
        }

        .grid-pattern {
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
            background-size: 36px 36px;
            mask-image: radial-gradient(circle at center, black 42%, transparent 85%);
        }

        .reveal {
            opacity: 0;
            transform: translateY(18px);
            animation: revealUp .7s ease forwards;
        }
        .reveal.delay-1 { animation-delay: .1s; }
        .reveal.delay-2 { animation-delay: .2s; }
        .reveal.delay-3 { animation-delay: .3s; }

        @keyframes revealUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body x-data="landingPage()" class="landing-bg min-h-screen text-white">
    <div class="absolute inset-0 pointer-events-none grid-pattern"></div>

    <header class="sticky top-0 z-40 transition-all duration-300"
            :class="scrolled ? 'bg-dark/90 backdrop-blur border-b border-white/10' : 'bg-transparent'">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="h-20 flex items-center justify-between">
                <a href="#top" class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-coral flex items-center justify-center shadow-glow-coral">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </span>
                    <span class="text-2xl font-bold">PALOMATIKA</span>
                </a>

                <div class="hidden sm:flex items-center gap-3">
                    <x-ui.button href="/login" variant="ghost">Войти</x-ui.button>
                    <x-ui.button href="/register" variant="primary">Попробовать бесплатно</x-ui.button>
                </div>

                <button type="button"
                        class="sm:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg border border-white/20 text-gray-200 hover:bg-white/10 transition"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        :aria-expanded="mobileMenuOpen.toString()"
                        aria-label="Открыть меню">
                    <svg x-show="!mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </nav>

            <div x-show="mobileMenuOpen" x-cloak x-transition
                 class="sm:hidden pb-4 border-t border-white/10">
                <div class="pt-4 grid gap-3">
                    <x-ui.button href="/register" variant="primary" class="w-full justify-center" @click="mobileMenuOpen = false">
                        🎯 Начать бесплатно
                    </x-ui.button>
                    <x-ui.button href="/login" variant="outline" class="w-full justify-center" @click="mobileMenuOpen = false">
                        Войти в аккаунт
                    </x-ui.button>
                    <x-ui.button href="#how" variant="ghost" class="w-full justify-center" @click="mobileMenuOpen = false">
                        Как это работает
                    </x-ui.button>
                </div>
            </div>
        </div>
    </header>

    <main id="top">
        <section class="relative pt-14 pb-20 sm:pt-20 sm:pb-24">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <p class="reveal inline-flex items-center gap-2 text-sm text-gray-300 border border-white/15 rounded-full px-4 py-2 mb-6">
                            <span class="w-2 h-2 rounded-full bg-success"></span>
                            7 дней бесплатно, без привязки карты
                        </p>
                        <h1 class="reveal delay-1 text-4xl sm:text-5xl lg:text-6xl leading-tight font-bold">
                            Сдай ОГЭ по математике на <span class="text-coral">5</span>
                            через понимание, а не зубрежку
                        </h1>
                        <p class="reveal delay-2 mt-6 text-lg text-gray-300 max-w-xl">
                            Система показывает, где именно пробел, и ведет по шагам: от ошибки к теме, от темы к уверенному решению.
                        </p>
                        <div class="reveal delay-3 mt-8 flex flex-col sm:flex-row gap-4">
                            <x-ui.button href="/register" variant="primary" size="lg">🎯 Начать бесплатно</x-ui.button>
                            <x-ui.button href="#how" variant="outline" size="lg">Посмотреть демо</x-ui.button>
                        </div>
                        <div class="mt-8 grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-xl">
                            <x-ui.stat value="5600+" label="задач" />
                            <x-ui.stat value="25" label="номеров ОГЭ" />
                            <x-ui.stat value="107" label="навыков" />
                            <x-ui.stat value="AI" label="подсказки" />
                        </div>
                    </div>

                    <x-ui.card id="how" class="bg-dark-light/80 border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl">
                        <h2 class="text-2xl font-bold mb-3">Мини-демо пазла</h2>
                        <p class="text-gray-300 text-sm mb-6">Выбери блоки для формулы Пифагора: <span class="font-semibold">c² = ? + ?</span></p>

                        <div class="flex items-center justify-center gap-3 flex-wrap mb-5">
                            <template x-for="item in ['a²', 'b²', 'a+b', 'ab']" :key="item">
                                <button type="button" @click="pick(item)"
                                    class="px-4 py-2 rounded-lg border transition"
                                    :class="selected.includes(item) ? 'bg-coral border-coral text-white' : 'border-white/20 text-gray-200 hover:bg-white/10'"
                                    x-text="item"></button>
                            </template>
                        </div>

                        <div class="rounded-xl bg-dark-lighter border border-white/10 p-4 text-center">
                            <div class="text-lg sm:text-xl">
                                c² =
                                <span class="inline-flex min-w-[70px] justify-center font-semibold" x-text="selected[0] || '____'"></span>
                                +
                                <span class="inline-flex min-w-[70px] justify-center font-semibold" x-text="selected[1] || '____'"></span>
                            </div>
                        </div>

                        <p class="mt-4 text-sm" :class="messageClass" x-text="message"></p>
                        <button type="button" @click="reset()" class="mt-4 text-sm text-gray-300 hover:text-white underline">Сбросить</button>
                    </x-ui.card>
                </div>
            </div>
        </section>

        <section class="py-16 sm:py-20 border-y border-white/10 bg-dark-light/60">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl sm:text-4xl text-center font-bold">Почему обычные тесты тормозят прогресс</h2>
                <div class="mt-10 grid md:grid-cols-2 gap-6">
                    <x-ui.card class="border-danger/40 bg-danger/10">
                        <h3 class="text-xl text-red-200 mb-3 font-bold">❌ Обычные тесты</h3>
                        <ul class="space-y-2 text-gray-300">
                            <li>Ты ошибся, но не понимаешь почему.</li>
                            <li>Непонятно, что повторить в первую очередь.</li>
                            <li>Зубрежка без устойчивого результата.</li>
                        </ul>
                    </x-ui.card>
                    <x-ui.card class="border-success/40 bg-success/10">
                        <h3 class="text-xl text-green-200 mb-3 font-bold">✅ PALOMATIKA</h3>
                        <ul class="space-y-2 text-gray-200">
                            <li>Показываем причину ошибки, а не только факт.</li>
                            <li>Даем персональный маршрут закрытия пробелов.</li>
                            <li>Учишься мыслить шагами, а не угадывать.</li>
                        </ul>
                    </x-ui.card>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-white/10 bg-dark/90">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 text-center">
            <p class="text-gray-400">© {{ date('Y') }} PALOMATIKA. Подготовка к ОГЭ по математике нового поколения.</p>
        </div>
    </footer>

    <script>
        function landingPage() {
            return {
                scrolled: false,
                mobileMenuOpen: false,
                selected: [],
                message: 'Выбери два блока для формулы.',
                messageClass: 'text-gray-300',

                init() {
                    this.onScroll();
                    window.addEventListener('scroll', () => this.onScroll());
                },

                onScroll() {
                    this.scrolled = window.scrollY > 10;
                },

                pick(item) {
                    if (this.selected.includes(item)) {
                        this.selected = this.selected.filter(v => v !== item);
                    } else if (this.selected.length < 2) {
                        this.selected.push(item);
                    }
                    this.evaluate();
                },

                evaluate() {
                    if (this.selected.length < 2) {
                        this.message = 'Выбери два блока для формулы.';
                        this.messageClass = 'text-gray-300';
                        return;
                    }

                    const ok = this.selected.includes('a²') && this.selected.includes('b²');
                    if (ok) {
                        this.message = 'Верно. Так и строится первый шаг решения.';
                        this.messageClass = 'text-green-300';
                    } else {
                        this.message = 'Почти. Для теоремы Пифагора нужно a² и b².';
                        this.messageClass = 'text-red-300';
                    }
                },

                reset() {
                    this.selected = [];
                    this.message = 'Выбери два блока для формулы.';
                    this.messageClass = 'text-gray-300';
                }
            };
        }
    </script>
</body>
</html>
