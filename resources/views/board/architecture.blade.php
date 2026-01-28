<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Архитектура проекта - PALOMATIKA</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        dark: {
                            DEFAULT: '#1a1a2e',
                            light: '#252542',
                            lighter: '#2d2d4a',
                        },
                        coral: {
                            DEFAULT: '#ff6b6b',
                            dark: '#e85555',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1a1a2e; }
        ::-webkit-scrollbar-thumb { background: #3d3d5c; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #4d4d6c; }
        .layer-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body class="bg-dark text-gray-100 min-h-screen font-sans">

<div x-data="{ expandedLayer: null, expandedFlow: null }">
    <!-- Header -->
    <header class="bg-dark-light border-b border-gray-700 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="/" class="text-2xl font-bold text-coral">PALOMATIKA</a>
                    <span class="text-gray-500">/</span>
                    <h1 class="text-xl font-semibold">Архитектура проекта</h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/kanban" class="text-gray-400 hover:text-white transition">Kanban</a>
                    <a href="/roadmap" class="text-gray-400 hover:text-white transition">Roadmap</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto p-4">
        <!-- Intro Section -->
        <section class="mb-12 text-center py-8">
            <h2 class="text-3xl font-bold mb-4">Как устроен PALOMATIKA</h2>
            <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                Простое объяснение всех частей проекта. Кликай на любой блок, чтобы узнать подробности.
            </p>
        </section>

        <!-- Visual Architecture Diagram -->
        <section class="mb-12">
            <div class="bg-dark-light rounded-2xl p-8">
                <h3 class="text-xl font-bold mb-6 text-center">Общая схема работы</h3>

                <!-- Simple flow diagram -->
                <div class="flex flex-col items-center gap-4">
                    <!-- User -->
                    <div class="bg-blue-500/20 border border-blue-500/50 rounded-xl p-4 w-64 text-center">
                        <div class="text-3xl mb-2">👤</div>
                        <div class="font-semibold text-blue-400">Пользователь</div>
                        <div class="text-sm text-gray-400">Ученик или учитель</div>
                    </div>

                    <div class="text-2xl text-gray-500">↓</div>

                    <!-- Browser -->
                    <div class="bg-green-500/20 border border-green-500/50 rounded-xl p-4 w-64 text-center">
                        <div class="text-3xl mb-2">🌐</div>
                        <div class="font-semibold text-green-400">Браузер</div>
                        <div class="text-sm text-gray-400">Chrome, Safari, Firefox...</div>
                    </div>

                    <div class="text-2xl text-gray-500">↓</div>

                    <!-- Server -->
                    <div class="grid grid-cols-3 gap-4 w-full max-w-3xl">
                        <div class="bg-purple-500/20 border border-purple-500/50 rounded-xl p-4 text-center">
                            <div class="text-2xl mb-2">📄</div>
                            <div class="font-semibold text-purple-400">Страницы</div>
                            <div class="text-xs text-gray-400">HTML + CSS</div>
                        </div>
                        <div class="bg-yellow-500/20 border border-yellow-500/50 rounded-xl p-4 text-center">
                            <div class="text-2xl mb-2">⚙️</div>
                            <div class="font-semibold text-yellow-400">Логика</div>
                            <div class="text-xs text-gray-400">PHP + Laravel</div>
                        </div>
                        <div class="bg-red-500/20 border border-red-500/50 rounded-xl p-4 text-center">
                            <div class="text-2xl mb-2">💾</div>
                            <div class="font-semibold text-red-400">Данные</div>
                            <div class="text-xs text-gray-400">JSON + MySQL</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Layer Details -->
        <section class="mb-12">
            <h3 class="text-2xl font-bold mb-6">Слои приложения</h3>
            <p class="text-gray-400 mb-8">Приложение состоит из нескольких слоёв. Каждый слой отвечает за свою задачу.</p>

            <div class="space-y-4">
                <!-- Layer 1: Views -->
                <div class="bg-dark-light rounded-xl overflow-hidden layer-card transition-all duration-200">
                    <button @click="expandedLayer = expandedLayer === 'views' ? null : 'views'" class="w-full p-6 flex items-center justify-between hover:bg-dark-lighter transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center">
                                <span class="text-2xl">📄</span>
                            </div>
                            <div class="text-left">
                                <h4 class="text-lg font-semibold">Страницы (Views)</h4>
                                <p class="text-gray-400 text-sm">То, что видит пользователь в браузере</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': expandedLayer === 'views' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="expandedLayer === 'views'" x-collapse class="px-6 pb-6">
                        <div class="bg-dark rounded-xl p-4 mb-4">
                            <p class="text-gray-300 mb-4">
                                <strong>Что это:</strong> HTML-файлы с расширением <code class="bg-dark-lighter px-2 py-1 rounded">.blade.php</code>
                                которые содержат то, что видит пользователь — текст, кнопки, картинки, формы.
                            </p>
                            <p class="text-gray-300">
                                <strong>Аналогия:</strong> Это как витрина магазина. Покупатель видит товары, цены, кнопки "Купить".
                                Но не видит склад и бухгалтерию.
                            </p>
                        </div>
                        <h5 class="font-semibold mb-3">Основные файлы:</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-green-400 text-sm">welcome.blade.php</code>
                                <p class="text-gray-400 text-sm mt-1">Главная страница сайта (landing). Первое, что видит посетитель.</p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-green-400 text-sm">dashboard.blade.php</code>
                                <p class="text-gray-400 text-sm mt-1">Личный кабинет ученика. Показывает прогресс, статистику.</p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-green-400 text-sm">topics/index.blade.php</code>
                                <p class="text-gray-400 text-sm mt-1">Список всех тем ОГЭ (от 6 до 19).</p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-green-400 text-sm">topics/show.blade.php</code>
                                <p class="text-gray-400 text-sm mt-1">Страница с заданиями конкретной темы.</p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-green-400 text-sm">ege/index.blade.php</code>
                                <p class="text-gray-400 text-sm mt-1">Список заданий ЕГЭ (от 1 до 19).</p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-green-400 text-sm">auth/login.blade.php</code>
                                <p class="text-gray-400 text-sm mt-1">Страница входа в аккаунт.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Layer 2: Routes -->
                <div class="bg-dark-light rounded-xl overflow-hidden layer-card transition-all duration-200">
                    <button @click="expandedLayer = expandedLayer === 'routes' ? null : 'routes'" class="w-full p-6 flex items-center justify-between hover:bg-dark-lighter transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-yellow-500/20 flex items-center justify-center">
                                <span class="text-2xl">🛣️</span>
                            </div>
                            <div class="text-left">
                                <h4 class="text-lg font-semibold">Маршруты (Routes)</h4>
                                <p class="text-gray-400 text-sm">Какая страница открывается по какому адресу</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': expandedLayer === 'routes' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="expandedLayer === 'routes'" x-collapse class="px-6 pb-6">
                        <div class="bg-dark rounded-xl p-4 mb-4">
                            <p class="text-gray-300 mb-4">
                                <strong>Что это:</strong> Файл <code class="bg-dark-lighter px-2 py-1 rounded">routes/web.php</code>
                                который связывает адреса в браузере с нужными страницами.
                            </p>
                            <p class="text-gray-300">
                                <strong>Аналогия:</strong> Это как указатели в торговом центре. "Одежда — 2 этаж", "Еда — 3 этаж".
                                Маршруты говорят: "/topics → показать список тем", "/login → показать форму входа".
                            </p>
                        </div>
                        <h5 class="font-semibold mb-3">Примеры маршрутов:</h5>
                        <div class="space-y-2">
                            <div class="bg-dark rounded-lg p-3 flex items-center gap-4">
                                <code class="text-yellow-400 font-mono">/</code>
                                <span class="text-gray-400">→</span>
                                <span class="text-gray-300">Главная страница (welcome)</span>
                            </div>
                            <div class="bg-dark rounded-lg p-3 flex items-center gap-4">
                                <code class="text-yellow-400 font-mono">/topics</code>
                                <span class="text-gray-400">→</span>
                                <span class="text-gray-300">Список тем ОГЭ</span>
                            </div>
                            <div class="bg-dark rounded-lg p-3 flex items-center gap-4">
                                <code class="text-yellow-400 font-mono">/topics/15</code>
                                <span class="text-gray-400">→</span>
                                <span class="text-gray-300">Тема 15 (треугольники)</span>
                            </div>
                            <div class="bg-dark rounded-lg p-3 flex items-center gap-4">
                                <code class="text-yellow-400 font-mono">/ege</code>
                                <span class="text-gray-400">→</span>
                                <span class="text-gray-300">Список заданий ЕГЭ</span>
                            </div>
                            <div class="bg-dark rounded-lg p-3 flex items-center gap-4">
                                <code class="text-yellow-400 font-mono">/login</code>
                                <span class="text-gray-400">→</span>
                                <span class="text-gray-300">Страница входа</span>
                            </div>
                            <div class="bg-dark rounded-lg p-3 flex items-center gap-4">
                                <code class="text-yellow-400 font-mono">/kanban</code>
                                <span class="text-gray-400">→</span>
                                <span class="text-gray-300">Доска задач проекта</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Layer 3: Controllers -->
                <div class="bg-dark-light rounded-xl overflow-hidden layer-card transition-all duration-200">
                    <button @click="expandedLayer = expandedLayer === 'controllers' ? null : 'controllers'" class="w-full p-6 flex items-center justify-between hover:bg-dark-lighter transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center">
                                <span class="text-2xl">🎮</span>
                            </div>
                            <div class="text-left">
                                <h4 class="text-lg font-semibold">Контроллеры (Controllers)</h4>
                                <p class="text-gray-400 text-sm">Мозг приложения — решает что делать</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': expandedLayer === 'controllers' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="expandedLayer === 'controllers'" x-collapse class="px-6 pb-6">
                        <div class="bg-dark rounded-xl p-4 mb-4">
                            <p class="text-gray-300 mb-4">
                                <strong>Что это:</strong> PHP-файлы в папке <code class="bg-dark-lighter px-2 py-1 rounded">app/Http/Controllers/</code>
                                которые содержат логику — что делать, когда пользователь открыл страницу.
                            </p>
                            <p class="text-gray-300">
                                <strong>Аналогия:</strong> Это как официант в ресторане. Принимает заказ (запрос), идёт на кухню (сервисы),
                                приносит блюдо (страницу).
                            </p>
                        </div>
                        <h5 class="font-semibold mb-3">Основные контроллеры:</h5>
                        <div class="grid grid-cols-1 gap-3">
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-purple-400 text-sm">TopicController.php</code>
                                <p class="text-gray-400 text-sm mt-1">
                                    Управляет темами ОГЭ. Когда открываешь /topics/15, этот контроллер загружает задания темы 15 и показывает страницу.
                                </p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-purple-400 text-sm">EgeController.php</code>
                                <p class="text-gray-400 text-sm mt-1">
                                    Такой же, но для ЕГЭ. Отдельный, потому что ЕГЭ и ОГЭ — разные экзамены с разной структурой.
                                </p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-purple-400 text-sm">AuthController.php</code>
                                <p class="text-gray-400 text-sm mt-1">
                                    Отвечает за вход и регистрацию. Проверяет пароль, создаёт аккаунт, выдаёт токен.
                                </p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-purple-400 text-sm">BoardController.php</code>
                                <p class="text-gray-400 text-sm mt-1">
                                    Управляет этой страницей, канбаном и роадмапом. Загружает задачи из tasks.json.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Layer 4: Services -->
                <div class="bg-dark-light rounded-xl overflow-hidden layer-card transition-all duration-200">
                    <button @click="expandedLayer = expandedLayer === 'services' ? null : 'services'" class="w-full p-6 flex items-center justify-between hover:bg-dark-lighter transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-orange-500/20 flex items-center justify-center">
                                <span class="text-2xl">⚙️</span>
                            </div>
                            <div class="text-left">
                                <h4 class="text-lg font-semibold">Сервисы (Services)</h4>
                                <p class="text-gray-400 text-sm">Рабочие лошадки — делают реальную работу</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': expandedLayer === 'services' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="expandedLayer === 'services'" x-collapse class="px-6 pb-6">
                        <div class="bg-dark rounded-xl p-4 mb-4">
                            <p class="text-gray-300 mb-4">
                                <strong>Что это:</strong> PHP-файлы в папке <code class="bg-dark-lighter px-2 py-1 rounded">app/Services/</code>
                                которые выполняют конкретные задачи — загрузка данных, генерация картинок и т.д.
                            </p>
                            <p class="text-gray-300">
                                <strong>Аналогия:</strong> Это как повара на кухне. Официант (контроллер) приносит заказ,
                                а повар (сервис) готовит блюдо. Каждый повар специализируется на своём.
                            </p>
                        </div>
                        <h5 class="font-semibold mb-3">Основные сервисы:</h5>
                        <div class="grid grid-cols-1 gap-3">
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-orange-400 text-sm">TaskDataService.php</code>
                                <p class="text-gray-400 text-sm mt-1">
                                    <strong>Главный сервис ОГЭ.</strong> Загружает задания из JSON файлов, кэширует их, выбирает случайные задания для генератора вариантов.
                                </p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-orange-400 text-sm">EgeTaskDataService.php</code>
                                <p class="text-gray-400 text-sm mt-1">
                                    <strong>Главный сервис ЕГЭ.</strong> Такой же, но для заданий ЕГЭ. Работает с файлами из папки ege/.
                                </p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <code class="text-orange-400 text-sm">GeometrySvgRenderer.php</code>
                                <p class="text-gray-400 text-sm mt-1">
                                    <strong>Рисователь геометрии.</strong> Создаёт SVG картинки треугольников, окружностей, четырёхугольников по координатам из JSON.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Layer 5: Data -->
                <div class="bg-dark-light rounded-xl overflow-hidden layer-card transition-all duration-200">
                    <button @click="expandedLayer = expandedLayer === 'data' ? null : 'data'" class="w-full p-6 flex items-center justify-between hover:bg-dark-lighter transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center">
                                <span class="text-2xl">💾</span>
                            </div>
                            <div class="text-left">
                                <h4 class="text-lg font-semibold">Данные (Data)</h4>
                                <p class="text-gray-400 text-sm">Где хранятся все задания и информация</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': expandedLayer === 'data' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="expandedLayer === 'data'" x-collapse class="px-6 pb-6">
                        <div class="bg-dark rounded-xl p-4 mb-4">
                            <p class="text-gray-300 mb-4">
                                <strong>Два типа хранения:</strong>
                            </p>
                            <ul class="text-gray-300 space-y-2 ml-4">
                                <li>📁 <strong>JSON файлы</strong> — все задания ОГЭ и ЕГЭ. Легко редактировать.</li>
                                <li>🗄️ <strong>MySQL база</strong> — пользователи, прогресс, достижения, дуэли.</li>
                            </ul>
                        </div>
                        <h5 class="font-semibold mb-3">Структура JSON файлов:</h5>
                        <div class="space-y-3">
                            <div class="bg-dark rounded-lg p-4">
                                <div class="text-red-400 font-mono text-sm mb-2">storage/app/tasks/</div>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div class="text-gray-400">topic_06.json</div>
                                    <div class="text-gray-500">Дроби и степени</div>
                                    <div class="text-gray-400">topic_15.json</div>
                                    <div class="text-gray-500">Треугольники</div>
                                    <div class="text-gray-400">topic_15_geometry.json</div>
                                    <div class="text-gray-500">Координаты для SVG</div>
                                </div>
                            </div>
                            <div class="bg-dark rounded-lg p-4">
                                <div class="text-red-400 font-mono text-sm mb-2">storage/app/tasks/ege/</div>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div class="text-gray-400">topic_01.json</div>
                                    <div class="text-gray-500">ЕГЭ задание 1</div>
                                    <div class="text-gray-400">topic_08.json</div>
                                    <div class="text-gray-500">ЕГЭ задание 8</div>
                                </div>
                            </div>
                            <div class="bg-dark rounded-lg p-4">
                                <div class="text-red-400 font-mono text-sm mb-2">.claude/</div>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div class="text-gray-400">tasks.json</div>
                                    <div class="text-gray-500">Задачи проекта (канбан)</div>
                                    <div class="text-gray-400">project-meta.json</div>
                                    <div class="text-gray-500">Метаданные проекта</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Layer 6: Database -->
                <div class="bg-dark-light rounded-xl overflow-hidden layer-card transition-all duration-200">
                    <button @click="expandedLayer = expandedLayer === 'database' ? null : 'database'" class="w-full p-6 flex items-center justify-between hover:bg-dark-lighter transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gray-500/20 flex items-center justify-center">
                                <span class="text-2xl">🗄️</span>
                            </div>
                            <div class="text-left">
                                <h4 class="text-lg font-semibold">База данных (MySQL)</h4>
                                <p class="text-gray-400 text-sm">Пользователи, прогресс, достижения</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': expandedLayer === 'database' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="expandedLayer === 'database'" x-collapse class="px-6 pb-6">
                        <div class="bg-dark rounded-xl p-4 mb-4">
                            <p class="text-gray-300">
                                <strong>Что это:</strong> MySQL — это программа для хранения данных в таблицах.
                                Каждая таблица как Excel-файл с колонками и строками.
                            </p>
                        </div>
                        <h5 class="font-semibold mb-3">Основные таблицы:</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="bg-dark rounded-lg p-3">
                                <div class="text-gray-300 font-semibold">users</div>
                                <p class="text-gray-500 text-sm">Все пользователи: имя, email, пароль, роль (ученик/учитель)</p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <div class="text-gray-300 font-semibold">attempts</div>
                                <p class="text-gray-500 text-sm">Попытки решения задач: кто решал, когда, правильно ли</p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <div class="text-gray-300 font-semibold">badges</div>
                                <p class="text-gray-500 text-sm">Достижения: "Первые шаги", "Серия 7 дней" и др.</p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <div class="text-gray-300 font-semibold">leagues</div>
                                <p class="text-gray-500 text-sm">Лиги: Bronze, Silver, Gold, Platinum, Diamond, Champion</p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <div class="text-gray-300 font-semibold">duels</div>
                                <p class="text-gray-500 text-sm">Дуэли между учениками: кто с кем, счёт, результат</p>
                            </div>
                            <div class="bg-dark rounded-lg p-3">
                                <div class="text-gray-300 font-semibold">homework</div>
                                <p class="text-gray-500 text-sm">Домашние задания от учителей</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How Things Work -->
        <section class="mb-12">
            <h3 class="text-2xl font-bold mb-6">Как это работает вместе</h3>
            <p class="text-gray-400 mb-8">Примеры реальных сценариев использования приложения.</p>

            <div class="space-y-4">
                <!-- Flow 1 -->
                <div class="bg-dark-light rounded-xl overflow-hidden">
                    <button @click="expandedFlow = expandedFlow === 'flow1' ? null : 'flow1'" class="w-full p-6 flex items-center justify-between hover:bg-dark-lighter transition">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 font-bold">1</div>
                            <div class="text-left">
                                <h4 class="font-semibold">Ученик открывает тему 15 (Треугольники)</h4>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': expandedFlow === 'flow1' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="expandedFlow === 'flow1'" x-collapse class="px-6 pb-6">
                        <div class="relative pl-6 border-l-2 border-blue-500/30 space-y-4">
                            <div class="relative">
                                <div class="absolute -left-[25px] w-4 h-4 rounded-full bg-blue-500"></div>
                                <div class="bg-dark rounded-lg p-3">
                                    <div class="text-blue-400 text-sm font-semibold">Шаг 1: Браузер</div>
                                    <p class="text-gray-400 text-sm">Ученик вводит адрес <code class="bg-dark-lighter px-1 rounded">/topics/15</code></p>
                                </div>
                            </div>
                            <div class="relative">
                                <div class="absolute -left-[25px] w-4 h-4 rounded-full bg-yellow-500"></div>
                                <div class="bg-dark rounded-lg p-3">
                                    <div class="text-yellow-400 text-sm font-semibold">Шаг 2: Маршрут</div>
                                    <p class="text-gray-400 text-sm">routes/web.php видит /topics/15 и направляет к TopicController</p>
                                </div>
                            </div>
                            <div class="relative">
                                <div class="absolute -left-[25px] w-4 h-4 rounded-full bg-purple-500"></div>
                                <div class="bg-dark rounded-lg p-3">
                                    <div class="text-purple-400 text-sm font-semibold">Шаг 3: Контроллер</div>
                                    <p class="text-gray-400 text-sm">TopicController вызывает метод show(15)</p>
                                </div>
                            </div>
                            <div class="relative">
                                <div class="absolute -left-[25px] w-4 h-4 rounded-full bg-orange-500"></div>
                                <div class="bg-dark rounded-lg p-3">
                                    <div class="text-orange-400 text-sm font-semibold">Шаг 4: Сервис</div>
                                    <p class="text-gray-400 text-sm">TaskDataService загружает topic_15.json с заданиями и SVG</p>
                                </div>
                            </div>
                            <div class="relative">
                                <div class="absolute -left-[25px] w-4 h-4 rounded-full bg-green-500"></div>
                                <div class="bg-dark rounded-lg p-3">
                                    <div class="text-green-400 text-sm font-semibold">Шаг 5: Страница</div>
                                    <p class="text-gray-400 text-sm">topics/show.blade.php показывает задания с картинками треугольников</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flow 2 -->
                <div class="bg-dark-light rounded-xl overflow-hidden">
                    <button @click="expandedFlow = expandedFlow === 'flow2' ? null : 'flow2'" class="w-full p-6 flex items-center justify-between hover:bg-dark-lighter transition">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center text-green-400 font-bold">2</div>
                            <div class="text-left">
                                <h4 class="font-semibold">Генерация варианта ОГЭ</h4>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': expandedFlow === 'flow2' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="expandedFlow === 'flow2'" x-collapse class="px-6 pb-6">
                        <div class="relative pl-6 border-l-2 border-green-500/30 space-y-4">
                            <div class="relative">
                                <div class="absolute -left-[25px] w-4 h-4 rounded-full bg-green-500"></div>
                                <div class="bg-dark rounded-lg p-3">
                                    <div class="text-green-400 text-sm font-semibold">Шаг 1</div>
                                    <p class="text-gray-400 text-sm">Ученик заходит на /oge и нажимает "Сгенерировать вариант"</p>
                                </div>
                            </div>
                            <div class="relative">
                                <div class="absolute -left-[25px] w-4 h-4 rounded-full bg-green-500"></div>
                                <div class="bg-dark rounded-lg p-3">
                                    <div class="text-green-400 text-sm font-semibold">Шаг 2</div>
                                    <p class="text-gray-400 text-sm">Создаётся уникальный код (hash), например: <code class="bg-dark-lighter px-1 rounded">abc123xyz</code></p>
                                </div>
                            </div>
                            <div class="relative">
                                <div class="absolute -left-[25px] w-4 h-4 rounded-full bg-green-500"></div>
                                <div class="bg-dark rounded-lg p-3">
                                    <div class="text-green-400 text-sm font-semibold">Шаг 3</div>
                                    <p class="text-gray-400 text-sm">TaskDataService берёт случайные задания из каждой темы (6-19)</p>
                                </div>
                            </div>
                            <div class="relative">
                                <div class="absolute -left-[25px] w-4 h-4 rounded-full bg-green-500"></div>
                                <div class="bg-dark rounded-lg p-3">
                                    <div class="text-green-400 text-sm font-semibold">Шаг 4</div>
                                    <p class="text-gray-400 text-sm">Redirect на /oge/abc123xyz — уникальный вариант</p>
                                </div>
                            </div>
                            <div class="relative">
                                <div class="absolute -left-[25px] w-4 h-4 rounded-full bg-green-500"></div>
                                <div class="bg-dark rounded-lg p-3">
                                    <div class="text-green-400 text-sm font-semibold">Магия</div>
                                    <p class="text-gray-400 text-sm">Если отправить эту ссылку другу — у него откроется ТОЧНО такой же вариант!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tech Stack -->
        <section class="mb-12">
            <h3 class="text-2xl font-bold mb-6">Технологии</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-dark-light rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">🐘</div>
                    <div class="font-semibold">PHP 8.2</div>
                    <div class="text-gray-500 text-sm">Язык программирования</div>
                </div>
                <div class="bg-dark-light rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">🔴</div>
                    <div class="font-semibold">Laravel 10</div>
                    <div class="text-gray-500 text-sm">Фреймворк</div>
                </div>
                <div class="bg-dark-light rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">🎨</div>
                    <div class="font-semibold">Tailwind CSS</div>
                    <div class="text-gray-500 text-sm">Стили</div>
                </div>
                <div class="bg-dark-light rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">⚡</div>
                    <div class="font-semibold">Alpine.js</div>
                    <div class="text-gray-500 text-sm">Интерактивность</div>
                </div>
                <div class="bg-dark-light rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">🗄️</div>
                    <div class="font-semibold">MySQL 8</div>
                    <div class="text-gray-500 text-sm">База данных</div>
                </div>
                <div class="bg-dark-light rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">📦</div>
                    <div class="font-semibold">JSON</div>
                    <div class="text-gray-500 text-sm">Хранение заданий</div>
                </div>
                <div class="bg-dark-light rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">🌐</div>
                    <div class="font-semibold">Timeweb</div>
                    <div class="text-gray-500 text-sm">Хостинг</div>
                </div>
                <div class="bg-dark-light rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">🐙</div>
                    <div class="font-semibold">GitHub Actions</div>
                    <div class="text-gray-500 text-sm">Автодеплой</div>
                </div>
            </div>
        </section>

        <!-- Quick Stats -->
        <section class="mb-12">
            <h3 class="text-2xl font-bold mb-6">Статистика проекта</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-blue-500/20 to-blue-600/10 rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-blue-400">14</div>
                    <div class="text-gray-400">Тем ОГЭ</div>
                </div>
                <div class="bg-gradient-to-br from-purple-500/20 to-purple-600/10 rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-purple-400">15</div>
                    <div class="text-gray-400">Заданий ЕГЭ</div>
                </div>
                <div class="bg-gradient-to-br from-green-500/20 to-green-600/10 rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-green-400">1349</div>
                    <div class="text-gray-400">Задач</div>
                </div>
                <div class="bg-gradient-to-br from-coral/20 to-red-600/10 rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-coral">62</div>
                    <div class="text-gray-400">Страниц</div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-dark-light border-t border-gray-700 py-6 mt-12">
        <div class="max-w-6xl mx-auto px-4 text-center text-gray-500">
            <p>PALOMATIKA — EdTech платформа для подготовки к ОГЭ и ЕГЭ</p>
            <p class="text-sm mt-2">Создано с помощью Claude Code</p>
        </div>
    </footer>
</div>

</body>
</html>
