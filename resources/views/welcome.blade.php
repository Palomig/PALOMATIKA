<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PALOMATIKA - Подготовка к ОГЭ по математике</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: {
                            DEFAULT: '#1a1a2e',
                            light: '#252542',
                            lighter: '#2d2d4a',
                        },
                        coral: {
                            DEFAULT: '#ff6b6b',
                            dark: '#e85555',
                            light: '#ff8585',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-dark min-h-screen">
    <!-- Header -->
    <header class="container mx-auto px-4 py-6">
        <nav class="flex justify-between items-center">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-coral rounded-xl flex items-center justify-center mr-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-white">PALOMATIKA</span>
            </div>
            <div class="space-x-4">
                <a href="/login" class="text-gray-300 hover:text-white transition">Войти</a>
                <a href="/register" class="bg-coral text-white px-5 py-2.5 rounded-xl font-medium hover:bg-coral-dark transition">
                    Начать бесплатно
                </a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <main class="container mx-auto px-4 py-16">
        <div class="text-center max-w-4xl mx-auto">
            <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">
                Сдай ОГЭ по математике на <span class="text-coral">5</span>
            </h1>
            <p class="text-xl text-gray-400 mb-8">
                Интерактивная платформа с пазловым методом обучения.
                Разбирай задачи по шагам, отслеживай прогресс, соревнуйся с друзьями.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/register" class="bg-coral text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-coral-dark transition shadow-lg shadow-coral/30">
                    Начать подготовку
                </a>
                <a href="#features" class="border-2 border-gray-600 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-dark-light transition">
                    Узнать больше
                </a>
            </div>
            <p class="text-gray-500 mt-4 text-sm">7 дней бесплатного доступа ко всем функциям</p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-16 max-w-4xl mx-auto">
            <div class="bg-dark-light border border-gray-800 rounded-2xl p-6 text-center">
                <div class="text-3xl font-bold text-coral">25</div>
                <div class="text-gray-400 text-sm mt-1">Номеров ОГЭ</div>
            </div>
            <div class="bg-dark-light border border-gray-800 rounded-2xl p-6 text-center">
                <div class="text-3xl font-bold text-coral">5600+</div>
                <div class="text-gray-400 text-sm mt-1">Задач</div>
            </div>
            <div class="bg-dark-light border border-gray-800 rounded-2xl p-6 text-center">
                <div class="text-3xl font-bold text-coral">107</div>
                <div class="text-gray-400 text-sm mt-1">Навыков</div>
            </div>
            <div class="bg-dark-light border border-gray-800 rounded-2xl p-6 text-center">
                <div class="text-3xl font-bold text-coral">AI</div>
                <div class="text-gray-400 text-sm mt-1">Подсказки</div>
            </div>
        </div>
    </main>

    <!-- Features -->
    <section id="features" class="py-20 bg-dark-light">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-white mb-12">
                Почему PALOMATIKA?
            </h2>
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="p-6 rounded-2xl bg-dark border border-gray-800">
                    <div class="w-12 h-12 bg-coral/20 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-coral" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Пазловый метод</h3>
                    <p class="text-gray-400">
                        Разбивай сложные задачи на простые шаги. Собирай решение как пазл из готовых блоков.
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-dark border border-gray-800">
                    <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Умная аналитика</h3>
                    <p class="text-gray-400">
                        Система отслеживает твои слабые места и подбирает задачи для их проработки.
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-dark border border-gray-800">
                    <div class="w-12 h-12 bg-amber-500/20 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Дуэли и лиги</h3>
                    <p class="text-gray-400">
                        Соревнуйся с одноклассниками, поднимайся в рейтинге, зарабатывай бейджи.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section class="py-20 bg-dark">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-white mb-12">
                Тарифы
            </h2>
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="bg-dark-light border border-gray-800 rounded-2xl p-8">
                    <h3 class="text-xl font-semibold text-white">Старт</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold text-white">499</span>
                        <span class="text-gray-500">/мес</span>
                    </div>
                    <ul class="mt-6 space-y-3 text-gray-400">
                        <li class="flex items-center"><svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Все номера ОГЭ</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Базовая аналитика</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Дуэли</li>
                    </ul>
                    <button class="mt-8 w-full py-3 border-2 border-coral text-coral rounded-xl font-medium hover:bg-coral/10 transition">
                        Выбрать
                    </button>
                </div>

                <div class="bg-gradient-to-br from-coral to-coral-dark rounded-2xl p-8 transform scale-105 shadow-xl shadow-coral/20">
                    <div class="text-white/80 text-sm font-medium mb-2">Популярный</div>
                    <h3 class="text-xl font-semibold text-white">Стандарт</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold text-white">799</span>
                        <span class="text-white/70">/мес</span>
                    </div>
                    <ul class="mt-6 space-y-3 text-white/90">
                        <li class="flex items-center"><svg class="w-5 h-5 text-white mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Всё из Старт</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-white mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Расширенная аналитика</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-white mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Теория по темам</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-white mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Домашние задания</li>
                    </ul>
                    <button class="mt-8 w-full py-3 bg-white text-coral rounded-xl font-medium hover:bg-gray-100 transition">
                        Выбрать
                    </button>
                </div>

                <div class="bg-dark-light border border-gray-800 rounded-2xl p-8">
                    <h3 class="text-xl font-semibold text-white">Премиум</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold text-white">1299</span>
                        <span class="text-gray-500">/мес</span>
                    </div>
                    <ul class="mt-6 space-y-3 text-gray-400">
                        <li class="flex items-center"><svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Всё из Стандарт</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>AI-подсказки</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Приоритетная поддержка</li>
                    </ul>
                    <button class="mt-8 w-full py-3 border-2 border-coral text-coral rounded-xl font-medium hover:bg-coral/10 transition">
                        Выбрать
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark-light border-t border-gray-800 py-12">
        <div class="container mx-auto px-4 text-center">
            <div class="flex items-center justify-center mb-4">
                <div class="w-10 h-10 bg-coral rounded-xl flex items-center justify-center mr-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-white">PALOMATIKA</span>
            </div>
            <p class="text-gray-400 mb-8">Подготовка к ОГЭ по математике нового поколения</p>
            <div class="flex justify-center space-x-6 text-gray-500">
                <a href="#" class="hover:text-coral transition">Политика конфиденциальности</a>
                <a href="#" class="hover:text-coral transition">Условия использования</a>
                <a href="#" class="hover:text-coral transition">Контакты</a>
            </div>
            <p class="text-gray-600 mt-8 text-sm">&copy; {{ date('Y') }} PALOMATIKA. Все права защищены.</p>
        </div>
    </footer>
</body>
</html>
