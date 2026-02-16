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
    @if($role !== 'teacher')
    <script>
        window.__uiMode = localStorage.getItem('palomatika_ui_mode') || 'dark';
        if (!['light', 'dark'].includes(window.__uiMode)) window.__uiMode = 'dark';
        document.documentElement.setAttribute('data-ui-mode', window.__uiMode);
    </script>
    @endif
    <style>
        .sidebar-gradient { background: linear-gradient(180deg, var(--scroll-track, #1a1a2e) 0%, rgba(0,0,0,0.2) 100%); }

        @if($role !== 'teacher')
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
        @endif

        /* === SugarCRM-inspired Teacher Shell === */
        .teacher-shell {
            --tsh-bg: #f5f6f8;
            --tsh-surface: #ffffff;
            --tsh-surface-soft: #f7f8fa;
            --tsh-text: #222630;
            --tsh-muted: #5f6775;
            --tsh-subtle: #8c95a6;
            --tsh-border: rgba(0, 0, 0, 0.07);
            --tsh-border-soft: rgba(0, 0, 0, 0.04);
            --tsh-hover: rgba(0, 0, 0, 0.025);
            --tsh-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            --tsh-shadow-lg: 0 4px 12px rgba(0, 0, 0, 0.05);
            --tsh-accent: #2c3345;
            --tsh-accent-soft: #eef0f4;
            --tsh-blue: #4a8af5;
            --tsh-blue-soft: #edf3ff;
            --tsh-sidebar-w: 64px;
            background: var(--tsh-bg);
            color: var(--tsh-text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', Roboto, Helvetica, Arial, sans-serif;
        }

        .teacher-shell .bg-dark,
        .teacher-shell .bg-dark-light,
        .teacher-shell .bg-dark-lighter {
            background-color: var(--tsh-surface) !important;
        }

        .teacher-shell .bg-dark\/50,
        .teacher-shell .bg-dark\/60,
        .teacher-shell .bg-dark\/70,
        .teacher-shell .bg-dark\/80 {
            background-color: var(--tsh-surface-soft) !important;
        }

        .teacher-shell .text-white { color: var(--tsh-text) !important; }
        .teacher-shell .text-gray-600,
        .teacher-shell .text-gray-500,
        .teacher-shell .text-gray-400 { color: var(--tsh-muted) !important; }
        .teacher-shell .text-gray-300 { color: #374151 !important; }
        .teacher-shell .text-gray-200 { color: #1f2937 !important; }
        .teacher-shell .text-gray-700 { color: #9ca3af !important; }

        .teacher-shell [class*="border-white"] { border-color: var(--tsh-border) !important; }
        .teacher-shell [class*="bg-white/"] { background-color: var(--tsh-hover) !important; }

        .teacher-shell .rounded-2xl {
            border-radius: 12px !important;
        }

        /* Sidebar — compact icon-only */
        .teacher-shell aside.tsh-sidebar {
            width: var(--tsh-sidebar-w);
            background: #ffffff !important;
            border-right: 1px solid rgba(0,0,0,0.05) !important;
            box-shadow: none;
        }

        .teacher-shell header.tsh-header {
            background: rgba(255,255,255,0.96) !important;
            backdrop-filter: blur(12px) saturate(140%);
            -webkit-backdrop-filter: blur(12px) saturate(140%);
            border-bottom: 1px solid rgba(0,0,0,0.05) !important;
            min-height: 54px;
        }

        /* Tab navigation in header */
        .tsh-tabs {
            display: flex;
            align-items: center;
            gap: 1px;
        }
        .tsh-tab {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--tsh-subtle);
            transition: all 0.15s ease;
            white-space: nowrap;
            text-decoration: none;
        }
        .tsh-tab:hover {
            color: var(--tsh-text);
            background: rgba(0,0,0,0.035);
        }
        .tsh-tab.active {
            background: var(--tsh-accent);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        }

        /* Card styles */
        .tsh-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .tsh-card-soft {
            background: var(--tsh-surface-soft);
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.03);
        }

        /* Hero typography */
        .tsh-page-kicker {
            font-size: 10.5px;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: .14em;
            font-weight: 600;
            margin-bottom: .5rem;
            color: var(--tsh-blue);
        }
        .tsh-page-title {
            font-size: clamp(1.35rem, 1.8vw, 1.6rem);
            line-height: 1.2;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: var(--tsh-text);
        }
        .tsh-page-subtitle {
            margin-top: .4rem;
            font-size: .85rem;
            line-height: 1.45;
            color: var(--tsh-muted);
            max-width: 60ch;
        }

        /* Stat cards — clean white, no colored borders */
        .tsh-stat-blue,
        .tsh-stat-green,
        .tsh-stat-amber,
        .tsh-stat-coral,
        .tsh-stat-violet { background: #ffffff; }

        /* Avatar with status badge */
        .tsh-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
            position: relative;
        }
        .tsh-avatar-sm { width: 32px; height: 32px; font-size: 12px; }
        .tsh-avatar-lg { width: 48px; height: 48px; font-size: 16px; }

        /* Badge on avatar */
        .tsh-badge {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--tsh-surface);
        }

        /* Action buttons like in SugarCRM */
        .tsh-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.06);
            background: transparent;
            color: var(--tsh-subtle);
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .tsh-btn:hover {
            background: rgba(0,0,0,0.035);
            color: var(--tsh-text);
        }

        .tsh-btn-primary {
            background: var(--tsh-accent);
            color: #fff;
            border: none;
        }
        .tsh-btn-primary:hover {
            background: #3a4055;
            color: #fff;
        }

        /* App action buttons */
        .tsh-action-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            background: var(--tsh-accent);
            border: 1px solid transparent;
            transition: all .15s ease;
        }
        .tsh-action-primary:hover {
            background: #3a4055;
        }

        .tsh-action-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--tsh-text);
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.1);
            transition: all .15s ease;
        }
        .tsh-action-secondary:hover {
            background: #f7f8fa;
            border-color: rgba(0,0,0,0.14);
        }

        /* Progress bar SugarCRM style */
        .tsh-progress {
            height: 3px;
            border-radius: 2px;
            background: #eef0f3;
            overflow: hidden;
        }
        .tsh-progress-bar {
            height: 100%;
            border-radius: 2px;
            transition: width 0.4s ease;
        }

        /* Table styles */
        .teacher-shell table th {
            background: #fafbfc;
            font-size: 11px;
        }
        .teacher-shell table tr:hover td {
            background: #fafbfc;
        }

        /* Sticky sidebar cell in teacher-shell */
        .teacher-shell .sticky { background: #ffffff !important; }

        /* Scrollbar for teacher-shell */
        .teacher-shell ::-webkit-scrollbar { width: 6px; height: 6px; }
        .teacher-shell ::-webkit-scrollbar-track { background: transparent; }
        .teacher-shell ::-webkit-scrollbar-thumb { background: #d4d6db; border-radius: 3px; }
        .teacher-shell ::-webkit-scrollbar-thumb:hover { background: #a1a5ae; }

        /* Page-in animation */
        .teacher-shell main {
            animation: teacherPageIn .3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes teacherPageIn {
            0% { opacity: 0; transform: translateY(4px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Mobile: expand sidebar on open */
        @media (max-width: 1023px) {
            .teacher-shell aside.tsh-sidebar {
                width: 280px;
            }
            .teacher-shell aside.tsh-sidebar .tsh-nav-label { display: block; }
        }

        /* Input overrides for light theme */
        .teacher-shell input,
        .teacher-shell select,
        .teacher-shell textarea {
            background-color: #ffffff !important;
            border-color: rgba(0,0,0,0.1) !important;
            color: var(--tsh-text) !important;
        }
        .teacher-shell input::placeholder,
        .teacher-shell textarea::placeholder {
            color: var(--tsh-subtle) !important;
        }
        .teacher-shell input:focus,
        .teacher-shell select:focus,
        .teacher-shell textarea:focus {
            border-color: var(--tsh-blue) !important;
            box-shadow: 0 0 0 3px rgba(74, 138, 245, 0.1) !important;
        }

        /* Modal overrides */
        .teacher-shell .fixed.inset-0 .bg-dark-light,
        .teacher-shell .fixed.inset-0 [class*="bg-dark"] {
            background-color: #ffffff !important;
        }
    </style>
</head>
<body class="{{ $role === 'teacher' ? 'bg-transparent teacher-shell' : 'bg-dark' }} min-h-screen antialiased" x-data="dashboardApp('{{ $role }}')">
    @if($role === 'teacher')
    {{-- ========== TEACHER LAYOUT (SugarCRM-style) ========== --}}
    <div class="flex min-h-screen">
        {{-- Compact Icon Sidebar --}}
        <aside class="tsh-sidebar fixed inset-y-0 left-0 z-30 flex flex-col items-center py-4 transition-transform duration-300 ease-out"
               :class="{ '-translate-x-full lg:translate-x-0': !sidebarOpen, 'translate-x-0': sidebarOpen }"
               @click.away="if(window.innerWidth < 1024) sidebarOpen = false"
               aria-label="Навигация">

            {{-- Logo --}}
            <a href="/teacher" class="w-9 h-9 rounded-lg flex items-center justify-center mb-4 bg-[#2c3345] text-white hover:bg-[#3a4055] transition-colors" title="PALOMATIKA">
                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </a>

            {{-- Navigation icons --}}
            <nav class="flex-1 flex flex-col items-center gap-1" aria-label="Основная навигация">
                @include('layouts.partials.nav-teacher')
            </nav>

            {{-- Bottom actions --}}
            <div class="flex flex-col items-center gap-1.5 mt-3">
                {{-- Settings --}}
                <button class="tsh-btn w-8 h-8" title="Настройки">
                    <svg class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>

                {{-- Logout --}}
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="tsh-btn w-8 h-8" title="Выйти">
                        <svg class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen && window.innerWidth < 1024" x-cloak
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/30 backdrop-blur-sm z-20 lg:hidden"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

        {{-- Main content area --}}
        <div class="flex-1 lg:ml-[64px]">
            {{-- Top header with tabs --}}
            <header class="tsh-header sticky top-0 z-20 flex items-center px-4 sm:px-6 gap-3">
                {{-- Mobile menu button --}}
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden w-9 h-9 flex items-center justify-center rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition mr-1" aria-label="Открыть меню">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                {{-- Tab navigation --}}
                <nav class="tsh-tabs flex-1 overflow-x-auto scrollbar-none justify-center">
                    @php
                        $teacherTabs = [
                            ['url' => '/teacher', 'match' => 'teacher', 'exact' => true, 'label' => 'Обзор'],
                            ['url' => '/teacher/students', 'match' => 'teacher/students*', 'label' => 'Ученики'],
                            ['url' => '/teacher/groups', 'match' => 'teacher/groups*', 'label' => 'Группы'],
                            ['url' => '/teacher/homework', 'match' => 'teacher/homework*', 'label' => 'Задания'],
                            ['url' => '/teacher/analytics', 'match' => 'teacher/analytics*', 'label' => 'Аналитика'],
                            ['url' => '/teacher/oge/teachers', 'match' => 'teacher/oge*', 'label' => 'ОГЭ'],
                            ['url' => '/teacher/earnings', 'match' => 'teacher/earnings*', 'label' => 'Заработок'],
                        ];
                    @endphp
                    @foreach($teacherTabs as $tab)
                        @php
                            $tabActive = isset($tab['exact']) && $tab['exact']
                                ? request()->is($tab['match']) && !request()->is($tab['match'] . '/*')
                                : request()->is($tab['match']);
                        @endphp
                        <a href="{{ $tab['url'] }}" class="tsh-tab {{ $tabActive ? 'active' : '' }}">{{ $tab['label'] }}</a>
                    @endforeach
                </nav>

                {{-- Right actions --}}
                <div class="flex items-center gap-1 flex-shrink-0">
                    {{-- Search --}}
                    <button class="tsh-btn" title="Поиск">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>

                    {{-- Notifications --}}
                    <button class="tsh-btn relative" title="Уведомления">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/>
                        </svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>

                    {{-- Help --}}
                    <button class="tsh-btn" title="Помощь">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>

                    {{-- User avatar --}}
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center text-[#4a5568] text-xs font-semibold cursor-pointer ml-1" title="Профиль" x-text="user?.name?.charAt(0) || '?'"></div>
                </div>
            </header>

            {{-- Page content --}}
            <main class="p-4 sm:p-5 lg:p-6">
                <div class="max-w-[1440px] mx-auto">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @else
    {{-- ========== STUDENT LAYOUT (original) ========== --}}
    <div class="flex">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 w-72 bg-dark-light z-30 transform transition-transform duration-300 ease-out flex flex-col border-r border-white/[0.06]"
               :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
               @click.away="sidebarOpen = window.innerWidth >= 1024"
               aria-label="Навигация">

            {{-- Logo --}}
            <div class="flex items-center justify-between h-16 px-5 flex-shrink-0">
                <a href="/dashboard" class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-coral to-coral-dark rounded-xl flex items-center justify-center shadow-lg shadow-coral/20">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-bold text-white tracking-tight">PALOMATIKA</span>
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
                        <div class="text-xs text-gray-500" x-text="'Уровень ' + (user?.level || 1)"></div>
                    </div>
                </div>
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
            </div>

            {{-- Section label --}}
            <div class="px-6 pt-3 pb-2">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Меню</span>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 overflow-y-auto" aria-label="Основная навигация">
                @include('layouts.partials.nav-student')
            </nav>

            {{-- Bottom widget --}}
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
                    <h1 class="text-base sm:text-lg font-semibold text-white truncate">@yield('header', 'Личный кабинет')</h1>
                </div>

                <div class="flex items-center gap-2">
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
    @endif

    <script>
    @if($role !== 'teacher')
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
    @endif

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
