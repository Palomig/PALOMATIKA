<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Панель учителя') - PALOMATIKA</title>
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
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1a1a2e; }
        ::-webkit-scrollbar-thumb { background: #3d3d5c; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #4d4d6a; }
    </style>
    @stack('styles')
</head>
<body class="bg-dark min-h-screen" x-data="teacherApp()">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 w-64 bg-dark-light border-r border-gray-800 z-30 transform transition-transform duration-200"
               :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-800">
                    <a href="/teacher" class="flex items-center">
                        <div class="w-8 h-8 bg-coral rounded-lg flex items-center justify-center mr-2">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-white">PALOMATIKA</span>
                    </a>
                    <span class="text-xs bg-coral/20 text-coral px-2 py-1 rounded-lg">Учитель</span>
                </div>

                <!-- User info -->
                <div class="p-4 border-b border-gray-800">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-coral/20 rounded-full flex items-center justify-center">
                            <span class="font-medium text-coral" x-text="user?.name?.charAt(0) || '?'"></span>
                        </div>
                        <div class="ml-3">
                            <div class="font-medium text-white" x-text="user?.name || 'Загрузка...'"></div>
                            <div class="text-sm text-gray-400">Учитель</div>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                    <a href="/teacher" class="flex items-center px-3 py-2.5 rounded-xl transition {{ request()->is('teacher') ? 'bg-coral/10 text-coral' : 'text-gray-400 hover:bg-dark-lighter hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Обзор
                    </a>
                    <a href="/teacher/students" class="flex items-center px-3 py-2.5 rounded-xl transition {{ request()->is('teacher/students*') ? 'bg-coral/10 text-coral' : 'text-gray-400 hover:bg-dark-lighter hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Ученики
                    </a>
                    <a href="/teacher/homework" class="flex items-center px-3 py-2.5 rounded-xl transition {{ request()->is('teacher/homework*') ? 'bg-coral/10 text-coral' : 'text-gray-400 hover:bg-dark-lighter hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Домашние задания
                    </a>
                    <a href="/teacher/analytics" class="flex items-center px-3 py-2.5 rounded-xl transition {{ request()->is('teacher/analytics*') ? 'bg-coral/10 text-coral' : 'text-gray-400 hover:bg-dark-lighter hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Аналитика
                    </a>
                    <a href="/teacher/earnings" class="flex items-center px-3 py-2.5 rounded-xl transition {{ request()->is('teacher/earnings*') ? 'bg-coral/10 text-coral' : 'text-gray-400 hover:bg-dark-lighter hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Заработок
                    </a>
                </nav>

                <!-- Referral link -->
                <div class="p-4 border-t border-gray-800">
                    <div class="bg-dark rounded-xl p-4 border border-gray-700">
                        <div class="text-sm text-gray-400 mb-2">Ваша реферальная ссылка:</div>
                        <div class="flex items-center">
                            <input type="text" readonly :value="referralLink"
                                   class="flex-1 bg-dark-light text-white text-sm px-3 py-2 rounded-lg border border-gray-700 focus:outline-none focus:border-coral">
                            <button @click="copyReferralLink" class="ml-2 text-gray-400 hover:text-coral transition p-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
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
                <h1 class="text-lg font-semibold text-white">@yield('header', 'Панель учителя')</h1>
            </header>

            <!-- Page content -->
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
    function teacherApp() {
        return {
            sidebarOpen: window.innerWidth >= 1024,
            user: null,
            referralLink: '',

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
                        this.referralLink = window.location.origin + '/ref/' + (data.user?.referral_code || 'TEACHER');
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
            },

            copyReferralLink() {
                navigator.clipboard.writeText(this.referralLink);
            }
        }
    }
    </script>

    @stack('scripts')
</body>
</html>
