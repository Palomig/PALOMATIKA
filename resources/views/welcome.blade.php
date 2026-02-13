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

        @media (prefers-reduced-motion: reduce) {
            .reveal {
                opacity: 1;
                transform: none;
                animation: none;
            }
        }
    </style>
</head>
<body x-data="landingPage()" class="landing-bg min-h-screen text-white">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 bg-coral text-white px-4 py-2 rounded-lg">
        Перейти к содержимому
    </a>

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

    <main id="main-content">
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

                    <x-ui.card id="how" class="bg-dark-light/80 border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl" aria-labelledby="demo-title">
                        <h2 id="demo-title" class="text-2xl font-bold mb-3">Мини-демо пазла</h2>
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

                        <p class="mt-4 text-sm" :class="messageClass" x-text="message" aria-live="polite"></p>
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

        <section class="py-16 sm:py-20">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl sm:text-4xl text-center font-bold">Доверие строится на результате</h2>

                <div class="mt-10 grid sm:grid-cols-3 gap-4">
                    <x-ui.card class="text-center">
                        <div class="text-3xl font-bold text-success">82%</div>
                        <p class="mt-2 text-gray-300 text-sm">учеников повышают пробный балл за первые 4 недели</p>
                    </x-ui.card>
                    <x-ui.card class="text-center">
                        <div class="text-3xl font-bold text-coral">17 мин</div>
                        <p class="mt-2 text-gray-300 text-sm">средняя длительность одной эффективной сессии</p>
                    </x-ui.card>
                    <x-ui.card class="text-center">
                        <div class="text-3xl font-bold text-info">4.9/5</div>
                        <p class="mt-2 text-gray-300 text-sm">оценка учеников за понятность разбора ошибок</p>
                    </x-ui.card>
                </div>

                <div class="mt-10 grid lg:grid-cols-3 gap-5">
                    <x-ui.card class="lg:col-span-2">
                        <h3 class="text-xl font-bold mb-4">Отзывы учеников и родителей</h3>
                        <div class="space-y-4 text-gray-200">
                            <blockquote class="border-l-2 border-coral pl-4">
                                «За 3 недели дочка перестала бояться задач на геометрию. Не просто решает, а объясняет ход мысли».
                                <footer class="text-sm text-gray-400 mt-2">Марина, родитель</footer>
                            </blockquote>
                            <blockquote class="border-l-2 border-success pl-4">
                                «В обычных тестах я просто тыкал. Здесь понял, где ломалась логика, и поднял пробник с 12 до 19».
                                <footer class="text-sm text-gray-400 mt-2">Илья, 9 класс</footer>
                            </blockquote>
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <h3 class="text-xl font-bold mb-4">Частые возражения</h3>
                        <div class="space-y-2">
                            <button type="button" class="w-full text-left px-3 py-2 rounded-lg bg-dark hover:bg-dark-lighter transition"
                                    :aria-expanded="(objectionOpen === 1).toString()"
                                    aria-controls="objection-1"
                                    @click="objectionOpen = objectionOpen === 1 ? null : 1">
                                Это подойдет, если база слабая?
                            </button>
                            <p id="objection-1" x-show="objectionOpen === 1" x-cloak class="text-sm text-gray-300 px-3">Да. Платформа начинается с диагностики и ведет от базы к сложным номерам по шагам.</p>

                            <button type="button" class="w-full text-left px-3 py-2 rounded-lg bg-dark hover:bg-dark-lighter transition"
                                    :aria-expanded="(objectionOpen === 2).toString()"
                                    aria-controls="objection-2"
                                    @click="objectionOpen = objectionOpen === 2 ? null : 2">
                                Нужна ли помощь репетитора?
                            </button>
                            <p id="objection-2" x-show="objectionOpen === 2" x-cloak class="text-sm text-gray-300 px-3">Можно заниматься самостоятельно, но с репетитором эффект обычно еще выше за счет разбора ошибок.</p>

                            <button type="button" class="w-full text-left px-3 py-2 rounded-lg bg-dark hover:bg-dark-lighter transition"
                                    :aria-expanded="(objectionOpen === 3).toString()"
                                    aria-controls="objection-3"
                                    @click="objectionOpen = objectionOpen === 3 ? null : 3">
                                Что если не понравится?
                            </button>
                            <p id="objection-3" x-show="objectionOpen === 3" x-cloak class="text-sm text-gray-300 px-3">Есть бесплатный период. За это время можно пройти диагностику и оценить формат без риска.</p>
                        </div>
                    </x-ui.card>
                </div>
            </div>
        </section>

        <section class="py-16 sm:py-20 border-y border-white/10 bg-dark-light/60">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl sm:text-4xl text-center font-bold">Тарифы и сравнение</h2>
                <p class="mt-3 text-center text-gray-300">Начни с бесплатного периода, затем выбери комфортный ритм подготовки.</p>

                <div class="mt-10 grid md:grid-cols-3 gap-5">
                    <x-ui.card>
                        <p class="text-sm text-gray-400">Старт</p>
                        <p class="mt-2 text-4xl font-bold text-white">499₽<span class="text-base text-gray-400">/мес</span></p>
                        <ul class="mt-5 space-y-2 text-gray-300 text-sm">
                            <li>Все номера ОГЭ</li>
                            <li>Пазловый тренажер</li>
                            <li>Базовая аналитика</li>
                        </ul>
                        <x-ui.button href="/register" variant="outline" class="w-full mt-6 justify-center">Выбрать Старт</x-ui.button>
                    </x-ui.card>

                    <x-ui.card class="border-coral/60 shadow-glow-coral">
                        <p class="inline-flex px-3 py-1 rounded-badge bg-coral/20 text-coral text-xs font-semibold">Популярный</p>
                        <p class="mt-2 text-4xl font-bold text-white">799₽<span class="text-base text-gray-400">/мес</span></p>
                        <ul class="mt-5 space-y-2 text-gray-200 text-sm">
                            <li>Все из Старт</li>
                            <li>Расширенная диагностика пробелов</li>
                            <li>Домашние задания и трек прогресса</li>
                        </ul>
                        <x-ui.button href="/register" variant="primary" class="w-full mt-6 justify-center">Выбрать Стандарт</x-ui.button>
                    </x-ui.card>

                    <x-ui.card>
                        <p class="text-sm text-gray-400">Премиум</p>
                        <p class="mt-2 text-4xl font-bold text-white">1299₽<span class="text-base text-gray-400">/мес</span></p>
                        <ul class="mt-5 space-y-2 text-gray-300 text-sm">
                            <li>Все из Стандарт</li>
                            <li>Приоритетная поддержка</li>
                            <li>Персональные рекомендации</li>
                        </ul>
                        <x-ui.button href="/register" variant="outline" class="w-full mt-6 justify-center">Выбрать Премиум</x-ui.button>
                    </x-ui.card>
                </div>

                <div class="mt-10 overflow-x-auto rounded-card border border-white/10">
                    <table class="w-full min-w-[720px] text-sm">
                        <thead class="bg-dark/80">
                            <tr class="text-left">
                                <th scope="col" class="px-4 py-3 text-gray-300">Параметр</th>
                                <th scope="col" class="px-4 py-3 text-coral">PALOMATIKA</th>
                                <th scope="col" class="px-4 py-3 text-gray-300">Обычные тест-платформы</th>
                                <th scope="col" class="px-4 py-3 text-gray-300">Крупные онлайн-школы</th>
                            </tr>
                        </thead>
                        <tbody class="bg-dark-light/70">
                            <tr class="border-t border-white/10">
                                <td class="px-4 py-3 text-gray-300">Диагностика причин ошибок</td>
                                <td class="px-4 py-3 text-green-300 font-semibold">Да</td>
                                <td class="px-4 py-3 text-gray-400">Частично</td>
                                <td class="px-4 py-3 text-gray-400">Зависит от тарифа</td>
                            </tr>
                            <tr class="border-t border-white/10">
                                <td class="px-4 py-3 text-gray-300">Пазловый формат решения</td>
                                <td class="px-4 py-3 text-green-300 font-semibold">Да</td>
                                <td class="px-4 py-3 text-gray-400">Нет</td>
                                <td class="px-4 py-3 text-gray-400">Нет</td>
                            </tr>
                            <tr class="border-t border-white/10">
                                <td class="px-4 py-3 text-gray-300">Средняя цена в месяц</td>
                                <td class="px-4 py-3 text-white font-semibold">799₽</td>
                                <td class="px-4 py-3 text-gray-300">0-990₽</td>
                                <td class="px-4 py-3 text-gray-300">от 4000₽</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-8 text-center">
                    <x-ui.button href="/register" variant="primary" size="lg">Запустить 7-дневный бесплатный доступ</x-ui.button>
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
                objectionOpen: null,
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
