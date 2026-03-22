{{-- PALOMATIKA Design System — Single Source of Truth --}}
{{-- All layouts include this partial for consistent Tailwind config, fonts, and base styles --}}

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Tailwind CSS via CDN --}}
<script src="https://cdn.tailwindcss.com"></script>
<script>
    // === Theme System ===
    window.__themes = {
        coral: {
            label: 'Коралл',
            dark: {
                DEFAULT: '#1a1a2e', light: '#252542', lighter: '#2d2d4a',
                50: '#0f0f1e', 100: '#12122a', 200: '#1a1a2e',
                300: '#252542', 400: '#2d2d4a', 500: '#3d3d5c', 600: '#4d4d6a',
            },
            coral: {
                DEFAULT: '#ff6b6b', dark: '#e85555', light: '#ff8585',
                50: '#fff1f1', 100: '#ffe0e0', 200: '#ffc7c7', 300: '#ff9e9e',
                400: '#ff8585', 500: '#ff6b6b', 600: '#e85555',
                700: '#c43030', 800: '#a32828', 900: '#872525',
            },
            scrollTrack: '#1a1a2e', scrollThumb: '#3d3d5c', scrollHover: '#4d4d6a',
            skeletonA: '#252542', skeletonB: '#2d2d4a',
        },
        ocean: {
            label: 'Океан',
            dark: {
                DEFAULT: '#0c1222', light: '#162032', lighter: '#1e2d42',
                50: '#060b14', 100: '#081019', 200: '#0c1222',
                300: '#162032', 400: '#1e2d42', 500: '#2a3f5a', 600: '#3a5070',
            },
            coral: {
                DEFAULT: '#06b6d4', dark: '#0891b2', light: '#22d3ee',
                50: '#ecfeff', 100: '#cffafe', 200: '#a5f3fc', 300: '#67e8f9',
                400: '#22d3ee', 500: '#06b6d4', 600: '#0891b2',
                700: '#0e7490', 800: '#155e75', 900: '#164e63',
            },
            scrollTrack: '#0c1222', scrollThumb: '#2a3f5a', scrollHover: '#3a5070',
            skeletonA: '#162032', skeletonB: '#1e2d42',
        },
        emerald: {
            label: 'Изумруд',
            dark: {
                DEFAULT: '#0a1518', light: '#12272a', lighter: '#1a3538',
                50: '#050c0e', 100: '#071012', 200: '#0a1518',
                300: '#12272a', 400: '#1a3538', 500: '#264a4e', 600: '#356064',
            },
            coral: {
                DEFAULT: '#10b981', dark: '#059669', light: '#34d399',
                50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 300: '#6ee7b7',
                400: '#34d399', 500: '#10b981', 600: '#059669',
                700: '#047857', 800: '#065f46', 900: '#064e3b',
            },
            scrollTrack: '#0a1518', scrollThumb: '#264a4e', scrollHover: '#356064',
            skeletonA: '#12272a', skeletonB: '#1a3538',
        },
        amethyst: {
            label: 'Аметист',
            dark: {
                DEFAULT: '#150f28', light: '#221b3a', lighter: '#2e254c',
                50: '#0b0816', 100: '#0f0b1e', 200: '#150f28',
                300: '#221b3a', 400: '#2e254c', 500: '#3f3566', 600: '#504580',
            },
            coral: {
                DEFAULT: '#a855f7', dark: '#9333ea', light: '#c084fc',
                50: '#faf5ff', 100: '#f3e8ff', 200: '#e9d5ff', 300: '#d8b4fe',
                400: '#c084fc', 500: '#a855f7', 600: '#9333ea',
                700: '#7e22ce', 800: '#6b21a8', 900: '#581c87',
            },
            scrollTrack: '#150f28', scrollThumb: '#3f3566', scrollHover: '#504580',
            skeletonA: '#221b3a', skeletonB: '#2e254c',
        },
        sunset: {
            label: 'Закат',
            dark: {
                DEFAULT: '#1a1410', light: '#2a2118', lighter: '#382e22',
                50: '#100c08', 100: '#14100b', 200: '#1a1410',
                300: '#2a2118', 400: '#382e22', 500: '#4d4030', 600: '#625340',
            },
            coral: {
                DEFAULT: '#f59e0b', dark: '#d97706', light: '#fbbf24',
                50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d',
                400: '#fbbf24', 500: '#f59e0b', 600: '#d97706',
                700: '#b45309', 800: '#92400e', 900: '#78350f',
            },
            scrollTrack: '#1a1410', scrollThumb: '#4d4030', scrollHover: '#625340',
            skeletonA: '#2a2118', skeletonB: '#382e22',
        },
    };

    // Read theme from localStorage (default: coral)
    window.__currentTheme = localStorage.getItem('palomatika_theme') || 'coral';
    if (!window.__themes[window.__currentTheme]) window.__currentTheme = 'coral';
    const _t = window.__themes[window.__currentTheme];

    // Set CSS custom properties for non-Tailwind styles
    document.documentElement.style.setProperty('--scroll-track', _t.scrollTrack);
    document.documentElement.style.setProperty('--scroll-thumb', _t.scrollThumb);
    document.documentElement.style.setProperty('--scroll-hover', _t.scrollHover);
    document.documentElement.style.setProperty('--skeleton-a', _t.skeletonA);
    document.documentElement.style.setProperty('--skeleton-b', _t.skeletonB);
    document.documentElement.style.setProperty('--focus-ring', _t.coral.DEFAULT);

    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    dark: _t.dark,
                    coral: _t.coral,
                    // === EGE Accent (Purple) — independent of theme ===
                    accent: {
                        DEFAULT: '#8b5cf6',
                        light: '#a78bfa',
                        dark: '#7c3aed',
                        50: '#f5f3ff',
                        100: '#ede9fe',
                        200: '#ddd6fe',
                    },
                    // === Semantic Colors — independent of theme ===
                    success: {
                        DEFAULT: '#10b981',
                        light: '#34d399',
                        dark: '#059669',
                    },
                    warning: {
                        DEFAULT: '#f59e0b',
                        light: '#fbbf24',
                        dark: '#d97706',
                    },
                    danger: {
                        DEFAULT: '#ef4444',
                        light: '#f87171',
                        dark: '#dc2626',
                    },
                    info: {
                        DEFAULT: '#3b82f6',
                        light: '#60a5fa',
                        dark: '#2563eb',
                    },
                },
                borderRadius: {
                    'card': '1rem',
                    'button': '0.75rem',
                    'input': '0.75rem',
                    'badge': '9999px',
                },
                boxShadow: {
                    'glow-coral': '0 0 20px ' + _t.coral.DEFAULT + '26',
                    'glow-green': '0 0 20px rgba(16, 185, 129, 0.15)',
                    'glow-blue': '0 0 20px rgba(59, 130, 246, 0.15)',
                    'glow-purple': '0 0 20px rgba(139, 92, 246, 0.15)',
                    'card': '0 4px 6px -1px rgba(0, 0, 0, 0.3)',
                    'card-hover': '0 10px 25px -3px rgba(0, 0, 0, 0.4)',
                },
                animation: {
                    'fade-in': 'fadeIn 0.3s ease-out',
                    'slide-up': 'slideUp 0.3s ease-out',
                    'slide-down': 'slideDown 0.3s ease-out',
                    'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
                    'shake': 'shake 0.5s ease-in-out',
                    'count-up': 'countUp 0.6s ease-out',
                },
                keyframes: {
                    fadeIn: {
                        '0%': { opacity: '0' },
                        '100%': { opacity: '1' },
                    },
                    slideUp: {
                        '0%': { opacity: '0', transform: 'translateY(8px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' },
                    },
                    slideDown: {
                        '0%': { opacity: '0', transform: 'translateY(-8px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' },
                    },
                    pulseSoft: {
                        '0%, 100%': { opacity: '1' },
                        '50%': { opacity: '0.7' },
                    },
                    shake: {
                        '0%, 100%': { transform: 'translateX(0)' },
                        '25%': { transform: 'translateX(-4px)' },
                        '75%': { transform: 'translateX(4px)' },
                    },
                    countUp: {
                        '0%': { opacity: '0', transform: 'translateY(10px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' },
                    },
                },
            }
        }
    }
</script>

{{-- Telegram WebApp SDK (must load before Alpine.js and telegram-auth.js) --}}
<script src="https://telegram.org/js/telegram-web-app.js"></script>

{{-- Alpine.js --}}
<script defer src="/js/alpine.min.js"></script>

{{-- Google Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

{{-- Base Styles --}}
<style>
    body { font-family: 'Inter', sans-serif; }
    [x-cloak] { display: none !important; }

    /* Scrollbar — uses theme CSS variables */
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: var(--scroll-track, #1a1a2e); }
    ::-webkit-scrollbar-thumb { background: var(--scroll-thumb, #3d3d5c); border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--scroll-hover, #4d4d6a); }

    /* Focus ring system — uses theme accent */
    .focus-ring:focus-visible,
    button:focus-visible,
    a:focus-visible,
    input:focus-visible,
    select:focus-visible,
    textarea:focus-visible {
        outline: 2px solid var(--focus-ring, #ff6b6b);
        outline-offset: 2px;
        border-radius: 4px;
    }

    /* Card hover transitions */
    .transition-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .transition-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.4);
    }

    /* Skeleton loading animation — uses theme CSS variables */
    .skeleton {
        background: linear-gradient(90deg, var(--skeleton-a, #252542) 25%, var(--skeleton-b, #2d2d4a) 50%, var(--skeleton-a, #252542) 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s ease-in-out infinite;
    }
    @keyframes skeleton-loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
</style>

@stack('styles')
