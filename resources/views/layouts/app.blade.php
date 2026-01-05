<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PALOMATIKA')</title>
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathInElement(document.body, {delimiters: [{left: '$$', right: '$$', display: true}, {left: '$', right: '$', display: false}, {left: '\\\\(', right: '\\\\)', display: false}, {left: '\\\\[', right: '\\\\]', display: true}]});"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        /* Custom scrollbar for dark theme */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1a1a2e; }
        ::-webkit-scrollbar-thumb { background: #3d3d5c; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #4d4d6a; }
    </style>
    @stack('styles')
</head>
<body class="bg-dark min-h-screen" x-data="appData()">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 w-64 bg-dark-light border-r border-gray-800 z-30 transform transition-transform duration-200"
               :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
               @click.away="sidebarOpen = window.innerWidth >= 1024">
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-800">
                    <a href="/dashboard" class="flex items-center">
                        <div class="w-8 h-8 bg-coral rounded-lg flex items-center justify-center mr-2">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-white">PALOMATIKA</span>
                    </a>
                    <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- User info -->
                <div class="p-4 border-b border-gray-800">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-coral/20 rounded-full flex items-center justify-center">
                            <span class="text-coral font-medium" x-text="user?.name?.charAt(0) || '?'"></span>
                        </div>
                        <div class="ml-3">
                            <div class="font-medium text-white" x-text="user?.name || 'Загрузка...'"></div>
                            <div class="text-sm text-gray-400" x-text="'Уровень ' + (user?.level || 1)"></div>
                        </div>
                    </div>
                    <!-- XP Progress -->
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span x-text="(user?.xp || 0) + ' XP'"></span>
                            <span x-text="(user?.next_level_xp || 100) + ' XP'"></span>
                        </div>
                        <div class="bg-dark rounded-full h-2">
                            <div class="bg-gradient-to-r from-coral to-coral-light rounded-full h-2 transition-all"
                                 :style="'width: ' + ((user?.xp || 0) / (user?.next_level_xp || 100) * 100) + '%'"></div>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                    <a href="/dashboard" class="flex items-center px-3 py-2.5 rounded-xl transition {{ request()->is('dashboard') ? 'bg-coral/10 text-coral' : 'text-gray-400 hover:bg-dark-lighter hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Главная
                    </a>
                    <a href="/topics" class="flex items-center px-3 py-2.5 rounded-xl transition {{ request()->is('topics*') ? 'bg-coral/10 text-coral' : 'text-gray-400 hover:bg-dark-lighter hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        Темы ОГЭ
                    </a>
                    <a href="/practice" class="flex items-center px-3 py-2.5 rounded-xl transition {{ request()->is('practice*') ? 'bg-coral/10 text-coral' : 'text-gray-400 hover:bg-dark-lighter hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        Тренировка
                    </a>
                    <a href="/leaderboard" class="flex items-center px-3 py-2.5 rounded-xl transition {{ request()->is('leaderboard*') ? 'bg-coral/10 text-coral' : 'text-gray-400 hover:bg-dark-lighter hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Рейтинг
                    </a>
                    <a href="/badges" class="flex items-center px-3 py-2.5 rounded-xl transition {{ request()->is('badges*') ? 'bg-coral/10 text-coral' : 'text-gray-400 hover:bg-dark-lighter hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                        Достижения
                    </a>
                    <a href="/duels" class="flex items-center px-3 py-2.5 rounded-xl transition {{ request()->is('duels*') ? 'bg-coral/10 text-coral' : 'text-gray-400 hover:bg-dark-lighter hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Дуэли
                    </a>
                </nav>

                <!-- Streak -->
                <div class="p-4 border-t border-gray-800">
                    <div class="bg-gradient-to-r from-orange-500 to-coral rounded-xl p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold" x-text="streak?.current_streak || 0"></div>
                                <div class="text-orange-100 text-sm">дней подряд</div>
                            </div>
                            <div class="text-4xl">🔥</div>
                        </div>
                    </div>
                </div>

                <!-- Logout -->
                <div class="p-4 border-t border-gray-800">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center w-full px-3 py-2.5 text-gray-400 hover:text-white hover:bg-dark-lighter rounded-xl transition">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Выйти
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 lg:ml-64">
            <!-- Top bar -->
            <header class="sticky top-0 z-20 bg-dark-light/80 backdrop-blur-lg border-b border-gray-800 h-16 flex items-center px-4">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-400 hover:text-white mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-lg font-semibold text-white">@yield('header', 'Личный кабинет')</h1>
            </header>

            <!-- Page content -->
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Global app data -->
    <script>
    function appData() {
        return {
            sidebarOpen: window.innerWidth >= 1024,
            user: null,
            streak: null,

            async init() {
                window.addEventListener('resize', () => {
                    this.sidebarOpen = window.innerWidth >= 1024;
                });
                await this.loadUserData();
            },

            async loadUserData() {
                const token = localStorage.getItem('auth_token');
                if (!token) return;

                try {
                    const response = await fetch('/api/auth/me', {
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Accept': 'application/json'
                        }
                    });
                    if (response.ok) {
                        const data = await response.json();
                        this.user = data.user;
                        this.streak = data.streak;
                    }
                } catch (e) {
                    console.error('Failed to load user data', e);
                }
            },

            getAuthHeaders() {
                const token = localStorage.getItem('auth_token');
                return {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                };
            }
        }
    }
    </script>

    @stack('scripts')
</body>
</html>
