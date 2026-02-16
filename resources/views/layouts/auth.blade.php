<!DOCTYPE html>
<html lang="ru">
<head>
    <title>@yield('title') - PALOMATIKA</title>
    @include('partials.head-config')
    @push('styles')
    <style>
        .auth-bg {
            background:
                radial-gradient(ellipse at 20% 50%, rgba(255, 107, 107, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(139, 92, 246, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 100%, rgba(59, 130, 246, 0.05) 0%, transparent 50%);
        }
        .float-shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.04;
            animation: float 20s ease-in-out infinite;
        }
        .float-shape:nth-child(2) { animation-delay: -7s; animation-duration: 25s; }
        .float-shape:nth-child(3) { animation-delay: -14s; animation-duration: 30s; }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(30px, -30px) rotate(90deg); }
            50% { transform: translate(-20px, 20px) rotate(180deg); }
            75% { transform: translate(10px, -10px) rotate(270deg); }
        }
    </style>
    @endpush
</head>
<body class="bg-dark min-h-screen flex items-center justify-center p-4 auth-bg relative overflow-hidden">
    {{-- Floating decorative shapes --}}
    <div class="float-shape w-96 h-96 bg-coral -top-48 -left-48" aria-hidden="true"></div>
    <div class="float-shape w-64 h-64 bg-accent top-1/3 -right-32" aria-hidden="true"></div>
    <div class="float-shape w-80 h-80 bg-info -bottom-40 left-1/4" aria-hidden="true"></div>

    <div class="w-full max-w-md relative z-10">
        {{-- Logo --}}
        <a href="/" class="block text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-coral rounded-2xl mb-4 shadow-glow-coral">
                <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div class="text-3xl font-bold text-white">PALOMATIKA</div>
        </a>

        {{-- Card --}}
        <div class="bg-dark-light rounded-3xl shadow-2xl p-8 border border-gray-800 animate-fade-in">
            @yield('content')
        </div>

        {{-- Trust indicators --}}
        <div class="flex items-center justify-center gap-6 mt-6 text-gray-500 text-xs">
            <span class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Безопасный вход
            </span>
            <span class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                5 600+ учеников
            </span>
        </div>

        {{-- Footer --}}
        <p class="text-center text-gray-600 text-xs mt-4">
            &copy; {{ date('Y') }} PALOMATIKA
        </p>
    </div>

    @include('layouts.partials.palette-switcher')
</body>
</html>
