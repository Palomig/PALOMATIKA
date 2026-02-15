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
    <script>
        window.__uiMode = localStorage.getItem('palomatika_ui_mode') || 'dark';
        if (!['light', 'dark'].includes(window.__uiMode)) window.__uiMode = 'dark';
        document.documentElement.setAttribute('data-ui-mode', window.__uiMode);
    </script>
    <style>
        .sidebar-gradient { background: linear-gradient(180deg, var(--scroll-track, #1a1a2e) 0%, rgba(0,0,0,0.2) 100%); }

        :root[data-ui-mode="dark"] {
            --teacher-bg: #0d111d;
            --teacher-surface: #171d2e;
            --teacher-surface-soft: #20283b;
            --teacher-text: #f8fafc;
            --teacher-muted: #94a3b8;
            --teacher-subtle: #64748b;
            --teacher-border: rgba(148, 163, 184, 0.2);
            --teacher-border-soft: rgba(148, 163, 184, 0.12);
            --teacher-hover: rgba(148, 163, 184, 0.08);
            --teacher-shadow: 0 14px 36px rgba(2, 6, 23, 0.42);
            --teacher-topbar: rgba(13, 17, 29, 0.72);
        }

        :root[data-ui-mode="light"] {
            --teacher-bg: #f2f5fb;
            --teacher-surface: #ffffff;
            --teacher-surface-soft: #eef3fb;
            --teacher-text: #0f172a;
            --teacher-muted: #475569;
            --teacher-subtle: #64748b;
            --teacher-border: rgba(15, 23, 42, 0.12);
            --teacher-border-soft: rgba(15, 23, 42, 0.08);
            --teacher-hover: rgba(15, 23, 42, 0.04);
            --teacher-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            --teacher-topbar: rgba(242, 245, 251, 0.85);
        }

        .teacher-shell {
            background:
                radial-gradient(circle at 5% -10%, color-mix(in oklab, var(--focus-ring) 14%, transparent), transparent 45%),
                radial-gradient(circle at 90% 0%, color-mix(in oklab, var(--focus-ring) 10%, transparent), transparent 38%),
                var(--teacher-bg);
            color: var(--teacher-text);
        }

        .teacher-shell .bg-dark,
        .teacher-shell .bg-dark-light,
        .teacher-shell .bg-dark-lighter {
            background-color: var(--teacher-surface) !important;
        }

        .teacher-shell .bg-dark\/50,
        .teacher-shell .bg-dark\/60,
        .teacher-shell .bg-dark\/70,
        .teacher-shell .bg-dark\/80 {
            background-color: var(--teacher-surface-soft) !important;
        }

        .teacher-shell .text-white { color: var(--teacher-text) !important; }
        .teacher-shell .text-gray-600,
        .teacher-shell .text-gray-500,
        .teacher-shell .text-gray-400 { color: var(--teacher-muted) !important; }
        .teacher-shell .text-gray-300 { color: color-mix(in oklab, var(--teacher-text) 82%, transparent) !important; }
        .teacher-shell .text-gray-200 { color: color-mix(in oklab, var(--teacher-text) 90%, transparent) !important; }

        .teacher-shell [class*="border-white"] { border-color: var(--teacher-border-soft) !important; }
        .teacher-shell [class*="bg-white/"] { background-color: var(--teacher-hover) !important; }

        .teacher-shell .rounded-2xl {
            box-shadow: var(--teacher-shadow);
        }

        .teacher-shell aside {
            background: color-mix(in oklab, var(--teacher-surface) 95%, transparent) !important;
            border-color: var(--teacher-border) !important;
        }

        .teacher-shell header {
            background: var(--teacher-topbar) !important;
            border-color: var(--teacher-border-soft) !important;
        }

        .teacher-shell main {
            animation: teacherPageIn .42s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes teacherPageIn {
            0% { opacity: 0; transform: translateY(6px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-dark min-h-screen antialiased {{ $role === 'teacher' ? 'teacher-shell' : '' }}" x-data="dashboardApp('{{ $role }}')">
    <div class="flex">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 w-72 bg-dark-light z-30 transform transition-transform duration-300 ease-out flex flex-col border-r border-white/[0.06]"
               :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
               @click.away="sidebarOpen = window.innerWidth >= 1024"
               aria-label="Навигация">

            {{-- Logo --}}
            <div class="flex items-center justify-between h-16 px-5 flex-shrink-0">
                <a href="{{ $role === 'teacher' ? '/teacher' : '/dashboard' }}" class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-coral to-coral-dark rounded-xl flex items-center justify-center shadow-lg shadow-coral/20">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-bold text-white tracking-tight">PALOMATIKA</span>
                        @if($role === 'teacher')
                            <span class="block text-[10px] font-semibold text-coral uppercase tracking-widest">Teacher</span>
                        @endif
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-dark-lighter transition" aria-label="Закрыть меню">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- User card --}}
            <div class="px-4 py-3 mx-3 mt-1 mb-2 bg-dark/50 rounded-xl border border-white/[0.04]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-coral/30 to-coral/10 rounded-xl flex items-center justify-center ring-1 ring-coral/20">
                        <span class="font-semibold text-coral text-sm" x-text="user?.name?.charAt(0) || '?'"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-white text-sm truncate" x-text="user?.name || 'Загрузка...'"></div>
                        @if($role === 'teacher')
                            <div class="text-xs text-gray-500">Преподаватель</div>
                        @else
                            <div class="text-xs text-gray-500" x-text="'Уровень ' + (user?.level || 1)"></div>
                        @endif
                    </div>
                </div>
                @if($role === 'student')
                    <div class="mt-3">
                        <div class="flex justify-between text-[10px] text-gray-500 mb-1.5 font-medium">
                            <span x-text="(user?.xp || 0) + ' XP'"></span>
                            <span x-text="(user?.next_level_xp || 100) + ' XP'"></span>
                        </div>
                        <div class="bg-dark rounded-full h-1.5 overflow-hidden">
                            <div class="bg-gradient-to-r from-coral to-coral-light rounded-full h-1.5 transition-all duration-500"
                                 :style="'width: ' + ((user?.xp || 0) / (user?.next_level_xp || 100) * 100) + '%'"></div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Section label --}}
            <div class="px-6 pt-3 pb-2">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Меню</span>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 overflow-y-auto" aria-label="Основная навигация">
                @if($role === 'teacher')
                    @include('layouts.partials.nav-teacher')
                @else
                    @include('layouts.partials.nav-student')
                @endif
            </nav>

            {{-- Bottom widget --}}
            @if($role === 'teacher')
                <div class="px-3 py-4 border-t border-white/[0.04]">
                    <div class="bg-dark/50 rounded-xl p-3.5 border border-white/[0.04]">
                        <div class="flex items-center gap-2 mb-2.5">
                            <svg class="w-4 h-4 text-coral" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            <span class="text-xs font-medium text-gray-300">Реферальная ссылка</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly :value="referralLink"
                                   class="flex-1 bg-dark text-white text-xs px-3 py-2 rounded-lg border border-white/[0.06] focus:outline-none focus:border-coral/50 font-mono truncate">
                            <button @click="copyReferralLink()" class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg bg-coral/10 text-coral hover:bg-coral/20 transition" aria-label="Копировать">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="px-3 py-4 border-t border-white/[0.04]">
                    <div class="bg-gradient-to-br from-orange-500/90 to-coral rounded-xl p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold" x-text="streak?.current_streak || 0"></div>
                                <div class="text-orange-100 text-xs font-medium">дней подряд</div>
                            </div>
                            <div class="text-3xl">🔥</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Logout --}}
            <div class="px-3 pb-4">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center w-full gap-3 px-3 py-2.5 text-gray-500 hover:text-gray-300 hover:bg-dark-lighter/40 rounded-xl transition text-sm">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Выйти
                    </button>
                </form>
            </div>
        </aside>

        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen && window.innerWidth < 1024" x-cloak
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm z-20 lg:hidden"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

        {{-- Main content --}}
        <div class="flex-1 lg:ml-72">
            {{-- Top bar --}}
            <header class="sticky top-0 z-20 bg-dark/70 backdrop-blur-xl border-b border-white/[0.06] h-16 flex items-center px-4 sm:px-6">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden w-9 h-9 flex items-center justify-center rounded-xl text-gray-400 hover:text-white hover:bg-dark-lighter transition mr-3" aria-label="Открыть меню">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex-1 min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-white truncate">@yield('header', $role === 'teacher' ? 'Панель учителя' : 'Личный кабинет')</h1>
                </div>

                <div class="flex items-center gap-2">
                    @if($role === 'teacher')
                        <div x-data="uiModeSwitcher()" class="relative">
                            <button @click="toggleMode()"
                                    class="inline-flex items-center gap-1.5 h-9 px-2.5 rounded-xl border border-white/[0.08] hover:border-white/[0.15] bg-dark-light/50 transition text-xs font-semibold"
                                    :title="mode === 'dark' ? 'Включить светлую тему' : 'Включить тёмную тему'">
                                <svg x-show="mode === 'dark'" x-cloak class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8-9h1M3 12H2m15.364 6.364l.707.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <svg x-show="mode === 'light'" x-cloak class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                </svg>
                                <span x-text="mode === 'dark' ? 'Тёмная' : 'Светлая'"></span>
                            </button>
                        </div>
                    @endif

                    {{-- Theme switcher --}}
                    <div x-data="themeSwitcher()" class="relative">
                        <button @click="open = !open"
                                class="w-9 h-9 flex items-center justify-center rounded-xl border border-white/[0.08] hover:border-white/[0.15] bg-dark-light/50 transition"
                                title="Цветовая схема">
                            <span class="w-3.5 h-3.5 rounded-full ring-2 ring-white/10" :style="'background:' + currentAccent"></span>
                        </button>
                        <div x-show="open" x-cloak @click.away="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-dark-light rounded-xl border border-white/[0.08] shadow-2xl shadow-black/40 p-1.5 z-50">
                            <div class="text-[10px] text-gray-500 px-2.5 py-1.5 uppercase tracking-widest font-semibold">Тема</div>
                            <template x-for="(theme, key) in themes" :key="key">
                                <button @click="setTheme(key)"
                                        class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg transition text-sm"
                                        :class="current === key ? 'bg-coral/10 text-white' : 'text-gray-400 hover:bg-dark-lighter/60 hover:text-white'">
                                    <span class="w-3.5 h-3.5 rounded-full ring-1 ring-white/20 flex-shrink-0"
                                          :style="'background:' + theme.coral.DEFAULT"></span>
                                    <span class="flex-1 text-left text-[13px]" x-text="theme.label"></span>
                                    <svg x-show="current === key" class="w-3.5 h-3.5 text-coral flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    @if($isAdmin)
                        <div class="hidden md:flex items-center gap-1.5 ml-1 pl-2 border-l border-white/[0.06]">
                            <span class="text-[10px] text-gray-500 font-medium uppercase tracking-wider mr-1">Режим</span>
                            <form action="{{ route('view-as.set', ['role' => 'student']) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-2.5 py-1 rounded-lg text-xs font-medium border transition {{ $viewAsRole === 'student' ? 'border-coral/40 text-coral bg-coral/10' : 'border-white/[0.08] text-gray-400 hover:text-white hover:border-white/[0.15]' }}">
                                    Ученик
                                </button>
                            </form>
                            <form action="{{ route('view-as.set', ['role' => 'teacher']) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-2.5 py-1 rounded-lg text-xs font-medium border transition {{ $viewAsRole === 'teacher' ? 'border-coral/40 text-coral bg-coral/10' : 'border-white/[0.08] text-gray-400 hover:text-white hover:border-white/[0.15]' }}">
                                    Учитель
                                </button>
                            </form>
                            <form action="{{ route('view-as.clear') }}" method="POST">
                                @csrf
                                <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-medium border border-white/[0.08] text-gray-400 hover:text-white hover:border-white/[0.15] transition">
                                    Сброс
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </header>

            {{-- Page content --}}
            <main class="p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
    function uiModeSwitcher() {
        return {
            mode: window.__uiMode || 'dark',
            apply() {
                document.documentElement.setAttribute('data-ui-mode', this.mode);
                localStorage.setItem('palomatika_ui_mode', this.mode);
            },
            toggleMode() {
                this.mode = this.mode === 'dark' ? 'light' : 'dark';
                this.apply();
            }
        };
    }

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
