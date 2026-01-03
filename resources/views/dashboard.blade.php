<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Личный кабинет - PALOMATIKA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" x-data="dashboard()">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="/" class="text-xl font-bold text-purple-600">PALOMATIKA</a>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600" x-text="user?.name || 'Загрузка...'"></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-gray-500 hover:text-gray-700">
                            Выйти
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main content -->
    <main class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Добро пожаловать!</h1>
            <p class="text-gray-600">Начни подготовку к ОГЭ прямо сейчас</p>
        </div>

        <!-- Stats cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-3xl font-bold text-purple-600" x-text="stats.streak || 0"></div>
                <div class="text-gray-500 text-sm">дней подряд</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-3xl font-bold text-green-600" x-text="stats.tasks_solved || 0"></div>
                <div class="text-gray-500 text-sm">задач решено</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-3xl font-bold text-blue-600" x-text="(stats.accuracy || 0) + '%'"></div>
                <div class="text-gray-500 text-sm">точность</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-3xl font-bold text-orange-500" x-text="stats.badges || 0"></div>
                <div class="text-gray-500 text-sm">бейджей</div>
            </div>
        </div>

        <!-- Topics grid -->
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Темы ОГЭ</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="topic in topics" :key="topic.id">
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition cursor-pointer">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-purple-600" x-text="'№' + topic.oge_number"></span>
                        <span class="text-sm text-gray-400" x-text="topic.progress + '%'"></span>
                    </div>
                    <h3 class="font-medium text-gray-900" x-text="topic.name"></h3>
                    <div class="mt-3 bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-600 rounded-full h-2 transition-all" :style="'width: ' + topic.progress + '%'"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty state -->
        <div x-show="topics.length === 0 && !loading" class="text-center py-12">
            <p class="text-gray-500">Загрузка тем...</p>
        </div>
    </main>

    <script>
    function dashboard() {
        return {
            user: null,
            stats: {},
            topics: [],
            loading: true,

            async init() {
                await this.loadUser();
                await this.loadTopics();
                await this.loadStats();
                this.loading = false;
            },

            async loadUser() {
                try {
                    const token = localStorage.getItem('auth_token');
                    const response = await fetch('/api/auth/me', {
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Accept': 'application/json'
                        }
                    });
                    if (response.ok) {
                        const data = await response.json();
                        this.user = data.user;
                    }
                } catch (e) {
                    console.error('Failed to load user', e);
                }
            },

            async loadTopics() {
                try {
                    const response = await fetch('/api/topics');
                    if (response.ok) {
                        const data = await response.json();
                        this.topics = (data.topics || []).map(t => ({
                            ...t,
                            progress: Math.floor(Math.random() * 100) // Placeholder
                        }));
                    }
                } catch (e) {
                    console.error('Failed to load topics', e);
                }
            },

            async loadStats() {
                try {
                    const token = localStorage.getItem('auth_token');
                    const response = await fetch('/api/progress/dashboard', {
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Accept': 'application/json'
                        }
                    });
                    if (response.ok) {
                        this.stats = await response.json();
                    }
                } catch (e) {
                    // Default stats
                    this.stats = { streak: 0, tasks_solved: 0, accuracy: 0, badges: 0 };
                }
            }
        }
    }
    </script>
</body>
</html>
