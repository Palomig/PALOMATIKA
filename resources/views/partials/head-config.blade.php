{{-- PALOMATIKA Design System — Single Source of Truth --}}
{{-- All layouts include this partial for consistent Tailwind config, fonts, and base styles --}}

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Tailwind CSS via CDN --}}
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    // === Base Dark Palette ===
                    dark: {
                        DEFAULT: '#1a1a2e',
                        light: '#252542',
                        lighter: '#2d2d4a',
                        50: '#0f0f1e',
                        100: '#12122a',
                        200: '#1a1a2e',
                        300: '#252542',
                        400: '#2d2d4a',
                        500: '#3d3d5c',
                        600: '#4d4d6a',
                    },
                    // === Primary Accent (Coral) ===
                    coral: {
                        DEFAULT: '#ff6b6b',
                        dark: '#e85555',
                        light: '#ff8585',
                        50: '#fff1f1',
                        100: '#ffe0e0',
                        200: '#ffc7c7',
                        300: '#ff9e9e',
                        400: '#ff8585',
                        500: '#ff6b6b',
                        600: '#e85555',
                        700: '#c43030',
                        800: '#a32828',
                        900: '#872525',
                    },
                    // === EGE Accent (Purple) ===
                    accent: {
                        DEFAULT: '#8b5cf6',
                        light: '#a78bfa',
                        dark: '#7c3aed',
                        50: '#f5f3ff',
                        100: '#ede9fe',
                        200: '#ddd6fe',
                    },
                    // === Semantic Colors ===
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
                    'glow-coral': '0 0 20px rgba(255, 107, 107, 0.15)',
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

{{-- Alpine.js --}}
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

{{-- Google Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

{{-- Base Styles --}}
<style>
    body { font-family: 'Inter', sans-serif; }
    [x-cloak] { display: none !important; }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: #1a1a2e; }
    ::-webkit-scrollbar-thumb { background: #3d3d5c; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #4d4d6a; }

    /* Focus ring system */
    .focus-ring:focus-visible,
    button:focus-visible,
    a:focus-visible,
    input:focus-visible,
    select:focus-visible,
    textarea:focus-visible {
        outline: 2px solid #ff6b6b;
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

    /* Skeleton loading animation */
    .skeleton {
        background: linear-gradient(90deg, #252542 25%, #2d2d4a 50%, #252542 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s ease-in-out infinite;
    }
    @keyframes skeleton-loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
</style>

@stack('styles')
