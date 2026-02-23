{{--
    Unified Dashboard Layout
    ========================
    Shared layout for student and teacher dashboards.
    Usage:
      @extends('layouts.dashboard', ['role' => 'student'])
      @extends('layouts.dashboard', ['role' => 'teacher'])

    Sections: @yield('title'), @yield('header'), @yield('content')
    Stacks:   @stack('scripts')
--}}
@php
    $role = $role ?? 'student';
    $isAdmin = auth()->check() && auth()->user()->role === 'admin';
    $viewAsRole = $isAdmin ? (session('view_as_role') ?? null) : null;
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>@yield('title', $role === 'teacher' ? 'Панель учителя' : 'PALOMATIKA') - PALOMATIKA</title>
    @include('partials.head-config')
    @if($role === 'student')
        @include('partials.head-katex')
    @endif
</head>
<body class="bg-dark min-h-screen" x-data="dashboardApp('{{ $role }}')">
    <div class="flex">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 w-64 bg-dark-light border-r border-gray-800 z-30 transform transition-transform duration-200"
               :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
               @click.away="sidebarOpen = window.innerWidth >= 1024"
               aria-label="Навигация">
            <div class="flex flex-col h-full">
                {{-- Logo --}}
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-800">
                    <a href="{{ $role === 'teacher' ? '/teacher' : '/dashboard' }}" class="flex items-center">
                        <div class="w-8 h-8 bg-coral rounded-lg flex items-center justify-center mr-2">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-white">PALOMATIKA</span>
                    </a>
                    @if($role === 'teacher')
                        <span class="text-xs bg-coral/20 text-coral px-2 py-1 rounded-lg">Учитель</span>
                    @else
                        <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white" aria-label="Закрыть меню">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>

                {{-- User info --}}
                <div class="p-4 border-b border-gray-800">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-coral/20 rounded-full flex items-center justify-center">
                            <span class="font-medium text-coral" x-text="user?.name?.charAt(0) || '?'"></span>
                        </div>
                        <div class="ml-3">
                            <div class="font-medium text-white" x-text="user?.name || 'Загрузка...'"></div>
                            @if($role === 'teacher')
                                <div class="text-sm text-gray-400">Учитель</div>
                            @else
                                <div class="text-sm text-gray-400" x-text="'Уровень ' + (user?.level || 1)"></div>
                            @endif
                        </div>
                    </div>
                    @if($role === 'student')
                        {{-- XP Progress --}}
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
                    @endif
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 p-4 space-y-1 overflow-y-auto" aria-label="Основная навигация">
                    @if($role === 'teacher')
                        @include('layouts.partials.nav-teacher')
                    @else
                        @include('layouts.partials.nav-student')
                    @endif
                </nav>

                {{-- Bottom widget --}}
                @if($role === 'teacher')
                    {{-- Referral link --}}
                    <div class="p-4 border-t border-gray-800">
                        <div class="bg-dark rounded-xl p-4 border border-gray-700">
                            <div class="text-sm text-gray-400 mb-2">Ваша реферальная ссылка:</div>
                            <div class="flex items-center">
                                <input type="text" readonly :value="referralLink"
                                       class="flex-1 bg-dark-light text-white text-sm px-3 py-2 rounded-lg border border-gray-700 focus:outline-none focus:border-coral">
                                <button @click="copyReferralLink()" class="ml-2 text-gray-400 hover:text-coral transition p-2" aria-label="Копировать ссылку">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Streak --}}
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
                @endif

                @if($isAdmin)
                {{-- Admin role switcher (sidebar) --}}
                <div class="p-4 border-t border-gray-800">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-2 px-1">Просмотр как</div>
                    <div class="flex gap-2">
                        <form action="{{ route('view-as.set', ['role' => 'student']) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="w-full px-3 py-2 rounded-lg text-xs font-medium border transition {{ $viewAsRole === 'student' ? 'border-coral text-coral bg-coral/10' : 'border-gray-700 text-gray-400 hover:text-white hover:border-gray-500' }}">
                                Ученик
                            </button>
                        </form>
                        <form action="{{ route('view-as.set', ['role' => 'teacher']) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="w-full px-3 py-2 rounded-lg text-xs font-medium border transition {{ $viewAsRole === 'teacher' ? 'border-coral text-coral bg-coral/10' : 'border-gray-700 text-gray-400 hover:text-white hover:border-gray-500' }}">
                                Учитель
                            </button>
                        </form>
                        @if($viewAsRole)
                        <form action="{{ route('view-as.clear') }}" method="POST">
                            @csrf
                            <button type="submit" title="Сброс"
                                    class="px-3 py-2 rounded-lg text-xs border border-gray-700 text-gray-400 hover:text-white hover:border-gray-500 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Logout --}}
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

        {{-- Main content --}}
        <div class="flex-1 lg:ml-64">
            {{-- Top bar --}}
            <header class="sticky top-0 z-20 bg-dark-light/80 backdrop-blur-lg border-b border-gray-800 h-16 flex items-center px-4">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-400 hover:text-white mr-4" aria-label="Открыть меню">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-lg font-semibold text-white flex-1">@yield('header', $role === 'teacher' ? 'Панель учителя' : 'Личный кабинет')</h1>
                {{-- Theme switcher --}}
                <div x-data="themeSwitcher()" class="relative ml-3">
                    <button @click="open = !open" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border border-gray-700 hover:border-gray-500 transition" title="Цветовая схема">
                        <span class="w-3 h-3 rounded-full ring-1 ring-white/20" :style="'background:' + currentAccent"></span>
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak @click.away="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-52 bg-dark-light rounded-xl border border-gray-700 shadow-xl p-2 z-50">
                        <div class="text-xs text-gray-500 px-2 py-1.5 uppercase tracking-wider">Цветовая схема</div>
                        <template x-for="(theme, key) in themes" :key="key">
                            <button @click="setTheme(key)"
                                    class="w-full flex items-center gap-3 px-2.5 py-2 rounded-lg transition text-sm"
                                    :class="current === key ? 'bg-coral/10 text-white' : 'text-gray-400 hover:bg-dark-lighter hover:text-white'">
                                <span class="w-4 h-4 rounded-full ring-1 ring-white/20 flex-shrink-0"
                                      :style="'background:' + theme.coral.DEFAULT"></span>
                                <span class="flex-1 text-left" x-text="theme.label"></span>
                                <svg x-show="current === key" class="w-4 h-4 text-coral flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                @if($isAdmin)
                    <div class="flex items-center gap-1.5 ml-2 sm:gap-2 sm:ml-3">
                        <form action="{{ route('view-as.set', ['role' => 'student']) }}" method="POST">
                            @csrf
                            <button type="submit" title="Режим ученика"
                                    class="px-2 py-1 sm:px-2.5 rounded-lg text-xs border transition {{ $viewAsRole === 'student' ? 'border-coral text-coral bg-coral/10' : 'border-gray-700 text-gray-300 hover:border-gray-500' }}">
                                <span class="hidden sm:inline">Ученик</span>
                                <svg class="w-4 h-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </button>
                        </form>
                        <form action="{{ route('view-as.set', ['role' => 'teacher']) }}" method="POST">
                            @csrf
                            <button type="submit" title="Режим учителя"
                                    class="px-2 py-1 sm:px-2.5 rounded-lg text-xs border transition {{ $viewAsRole === 'teacher' ? 'border-coral text-coral bg-coral/10' : 'border-gray-700 text-gray-300 hover:border-gray-500' }}">
                                <span class="hidden sm:inline">Учитель</span>
                                <svg class="w-4 h-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                            </button>
                        </form>
                        @if($viewAsRole)
                        <form action="{{ route('view-as.clear') }}" method="POST">
                            @csrf
                            <button type="submit" title="Вернуться в режим админа"
                                    class="px-2 py-1 sm:px-2.5 rounded-lg text-xs border border-gray-700 text-gray-300 hover:border-gray-500 transition">
                                <span class="hidden sm:inline">Сброс</span>
                                <svg class="w-4 h-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                        @endif
                    </div>
                @endif
            </header>

            @if($isAdmin && $viewAsRole)
            {{-- Admin view-as banner --}}
            <div class="bg-coral/10 border-b border-coral/20 px-4 py-2 flex items-center justify-between">
                <span class="text-xs text-coral">
                    Вы просматриваете как <strong>{{ $viewAsRole === 'teacher' ? 'учитель' : 'ученик' }}</strong>
                </span>
                <form action="{{ route('view-as.clear') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-xs text-coral hover:text-coral-light underline transition">Выйти из режима</button>
                </form>
            </div>
            @endif

            {{-- Page content --}}
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
    function themeSwitcher() {
        return {
            open: false,
            themes: window.__themes,
            current: window.__currentTheme,
            get currentAccent() {
                return this.themes[this.current]?.coral?.DEFAULT || '#ff6b6b';
            },
            setTheme(key) {
                if (key === this.current) { this.open = false; return; }
                localStorage.setItem('palomatika_theme', key);
                window.location.reload();
            }
        };
    }

    function dashboardApp(role) {
        return {
            sidebarOpen: window.innerWidth >= 1024,
            user: null,
            streak: null,
            referralLink: '',
            role: role,

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
                        if (this.role === 'student') {
                            this.streak = data.streak;
                        }
                        if (this.role === 'teacher') {
                            this.referralLink = window.location.origin + '/ref/' + (data.user?.referral_code || 'TEACHER');
                        }
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
