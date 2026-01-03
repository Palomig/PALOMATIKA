<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PALOMATIKA - Подготовка к ОГЭ по математике</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-800 min-h-screen">
    <!-- Header -->
    <header class="container mx-auto px-4 py-6">
        <nav class="flex justify-between items-center">
            <div class="text-2xl font-bold text-white">
                PALOMATIKA
            </div>
            <div class="space-x-4">
                <a href="/login" class="text-white/80 hover:text-white transition">Войти</a>
                <a href="/register" class="bg-white text-purple-900 px-4 py-2 rounded-lg font-medium hover:bg-purple-100 transition">
                    Начать бесплатно
                </a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <main class="container mx-auto px-4 py-16">
        <div class="text-center max-w-4xl mx-auto">
            <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">
                Сдай ОГЭ по математике на <span class="text-yellow-400">5</span>
            </h1>
            <p class="text-xl text-white/80 mb-8">
                Интерактивная платформа с пазловым методом обучения.
                Разбирай задачи по шагам, отслеживай прогресс, соревнуйся с друзьями.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/register" class="bg-yellow-400 text-purple-900 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-yellow-300 transition shadow-lg">
                    Начать подготовку
                </a>
                <a href="#features" class="border-2 border-white/30 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-white/10 transition">
                    Узнать больше
                </a>
            </div>
            <p class="text-white/60 mt-4 text-sm">3 дня бесплатного доступа ко всем функциям</p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-16 max-w-4xl mx-auto">
            <div class="bg-white/10 backdrop-blur rounded-xl p-6 text-center">
                <div class="text-3xl font-bold text-white">25</div>
                <div class="text-white/70 text-sm">Номеров ОГЭ</div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-xl p-6 text-center">
                <div class="text-3xl font-bold text-white">1000+</div>
                <div class="text-white/70 text-sm">Задач</div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-xl p-6 text-center">
                <div class="text-3xl font-bold text-white">50+</div>
                <div class="text-white/70 text-sm">Навыков</div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-xl p-6 text-center">
                <div class="text-3xl font-bold text-white">AI</div>
                <div class="text-white/70 text-sm">Подсказки</div>
            </div>
        </div>
    </main>

    <!-- Features -->
    <section id="features" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">
                Почему PALOMATIKA?
            </h2>
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="p-6 rounded-xl bg-gradient-to-br from-purple-50 to-indigo-50">
                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Пазловый метод</h3>
                    <p class="text-gray-600">
                        Разбивай сложные задачи на простые шаги. Собирай решение как пазл из готовых блоков.
                    </p>
                </div>

                <div class="p-6 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Умная аналитика</h3>
                    <p class="text-gray-600">
                        Система отслеживает твои слабые места и подбирает задачи для их проработки.
                    </p>
                </div>

                <div class="p-6 rounded-xl bg-gradient-to-br from-orange-50 to-yellow-50">
                    <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Дуэли и лиги</h3>
                    <p class="text-gray-600">
                        Соревнуйся с одноклассниками, поднимайся в рейтинге, зарабатывай бейджи.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">
                Тарифы
            </h2>
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <h3 class="text-xl font-semibold text-gray-900">Старт</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold">499</span>
                        <span class="text-gray-500">/мес</span>
                    </div>
                    <ul class="mt-6 space-y-3 text-gray-600">
                        <li class="flex items-center"><svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Все номера ОГЭ</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Базовая аналитика</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Дуэли</li>
                    </ul>
                    <button class="mt-8 w-full py-3 border-2 border-purple-600 text-purple-600 rounded-lg font-medium hover:bg-purple-50 transition">
                        Выбрать
                    </button>
                </div>

                <div class="bg-purple-600 rounded-2xl shadow-lg p-8 transform scale-105">
                    <div class="text-yellow-400 text-sm font-medium mb-2">Популярный</div>
                    <h3 class="text-xl font-semibold text-white">Стандарт</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold text-white">799</span>
                        <span class="text-purple-200">/мес</span>
                    </div>
                    <ul class="mt-6 space-y-3 text-purple-100">
                        <li class="flex items-center"><svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Всё из Старт</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Расширенная аналитика</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Теория по темам</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Домашние задания</li>
                    </ul>
                    <button class="mt-8 w-full py-3 bg-white text-purple-600 rounded-lg font-medium hover:bg-purple-50 transition">
                        Выбрать
                    </button>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <h3 class="text-xl font-semibold text-gray-900">Премиум</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold">1299</span>
                        <span class="text-gray-500">/мес</span>
                    </div>
                    <ul class="mt-6 space-y-3 text-gray-600">
                        <li class="flex items-center"><svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Всё из Стандарт</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>AI-подсказки</li>
                        <li class="flex items-center"><svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Приоритетная поддержка</li>
                    </ul>
                    <button class="mt-8 w-full py-3 border-2 border-purple-600 text-purple-600 rounded-lg font-medium hover:bg-purple-50 transition">
                        Выбрать
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 py-12">
        <div class="container mx-auto px-4 text-center">
            <div class="text-2xl font-bold text-white mb-4">PALOMATIKA</div>
            <p class="text-gray-400 mb-8">Подготовка к ОГЭ по математике нового поколения</p>
            <div class="flex justify-center space-x-6 text-gray-400">
                <a href="#" class="hover:text-white transition">Политика конфиденциальности</a>
                <a href="#" class="hover:text-white transition">Условия использования</a>
                <a href="#" class="hover:text-white transition">Контакты</a>
            </div>
            <p class="text-gray-500 mt-8 text-sm">&copy; {{ date('Y') }} PALOMATIKA. Все права защищены.</p>
        </div>
    </footer>
</body>
</html>
