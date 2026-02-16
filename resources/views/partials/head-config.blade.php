{{-- PALOMATIKA Design System — Single Source of Truth --}}
{{-- All layouts include this partial for consistent Tailwind config, fonts, and base styles --}}

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Tailwind CSS via CDN --}}
<script src="https://cdn.tailwindcss.com"></script>
<script>
    // === Theme System (29 palettes from ui-catalog) ===
    window.__themeCatalog = [
        { key: 'saas-general', label: 'SaaS (General)', primary: '#2563EB', secondary: '#3B82F6', cta: '#F97316', bg: '#F8FAFC', text: '#1E293B', border: '#E2E8F0' },
        { key: 'micro-saas', label: 'Micro SaaS', primary: '#6366F1', secondary: '#818CF8', cta: '#10B981', bg: '#F5F3FF', text: '#1E1B4B', border: '#E0E7FF' },
        { key: 'e-commerce', label: 'E-commerce', primary: '#059669', secondary: '#10B981', cta: '#F97316', bg: '#ECFDF5', text: '#064E3B', border: '#A7F3D0' },
        { key: 'e-commerce-luxury', label: 'E-commerce Luxury', primary: '#1C1917', secondary: '#44403C', cta: '#CA8A04', bg: '#FAFAF9', text: '#0C0A09', border: '#D6D3D1' },
        { key: 'service-landing', label: 'Service Landing', primary: '#0EA5E9', secondary: '#38BDF8', cta: '#F97316', bg: '#F0F9FF', text: '#0C4A6E', border: '#BAE6FD' },
        { key: 'b2b-service', label: 'B2B Service', primary: '#0F172A', secondary: '#334155', cta: '#0369A1', bg: '#F8FAFC', text: '#020617', border: '#E2E8F0' },
        { key: 'financial-dashboard', label: 'Financial Dashboard', primary: '#0F172A', secondary: '#1E293B', cta: '#22C55E', bg: '#020617', text: '#F8FAFC', border: '#334155' },
        { key: 'analytics-dashboard', label: 'Analytics Dashboard', primary: '#1E40AF', secondary: '#3B82F6', cta: '#F59E0B', bg: '#F8FAFC', text: '#1E3A8A', border: '#DBEAFE' },
        { key: 'healthcare-app', label: 'Healthcare App', primary: '#0891B2', secondary: '#22D3EE', cta: '#059669', bg: '#ECFEFF', text: '#164E63', border: '#A5F3FC' },
        { key: 'educational-app', label: 'Educational App', primary: '#4F46E5', secondary: '#818CF8', cta: '#F97316', bg: '#EEF2FF', text: '#1E1B4B', border: '#C7D2FE' },
        { key: 'creative-agency', label: 'Creative Agency', primary: '#EC4899', secondary: '#F472B6', cta: '#06B6D4', bg: '#FDF2F8', text: '#831843', border: '#FBCFE8' },
        { key: 'portfolio', label: 'Portfolio', primary: '#18181B', secondary: '#3F3F46', cta: '#2563EB', bg: '#FAFAFA', text: '#09090B', border: '#E4E4E7' },
        { key: 'gaming', label: 'Gaming', primary: '#7C3AED', secondary: '#A78BFA', cta: '#F43F5E', bg: '#0F0F23', text: '#E2E8F0', border: '#4C1D95' },
        { key: 'government', label: 'Government', primary: '#0F172A', secondary: '#334155', cta: '#0369A1', bg: '#F8FAFC', text: '#020617', border: '#E2E8F0' },
        { key: 'fintech-crypto', label: 'Fintech / Crypto', primary: '#F59E0B', secondary: '#FBBF24', cta: '#8B5CF6', bg: '#0F172A', text: '#F8FAFC', border: '#334155' },
        { key: 'social-media', label: 'Social Media', primary: '#E11D48', secondary: '#FB7185', cta: '#2563EB', bg: '#FFF1F2', text: '#881337', border: '#FECDD3' },
        { key: 'productivity-tool', label: 'Productivity Tool', primary: '#0D9488', secondary: '#14B8A6', cta: '#F97316', bg: '#F0FDFA', text: '#134E4A', border: '#99F6E4' },
        { key: 'design-system', label: 'Design System', primary: '#4F46E5', secondary: '#6366F1', cta: '#F97316', bg: '#EEF2FF', text: '#312E81', border: '#C7D2FE' },
        { key: 'ai-chatbot', label: 'AI / Chatbot', primary: '#7C3AED', secondary: '#A78BFA', cta: '#06B6D4', bg: '#FAF5FF', text: '#1E1B4B', border: '#DDD6FE' },
        { key: 'nft-web3', label: 'NFT / Web3', primary: '#8B5CF6', secondary: '#A78BFA', cta: '#FBBF24', bg: '#0F0F23', text: '#F8FAFC', border: '#4C1D95' },
        { key: 'creator-economy', label: 'Creator Economy', primary: '#EC4899', secondary: '#F472B6', cta: '#F97316', bg: '#FDF2F8', text: '#831843', border: '#FBCFE8' },
        { key: 'sustainability-esg', label: 'Sustainability / ESG', primary: '#059669', secondary: '#10B981', cta: '#0891B2', bg: '#ECFDF5', text: '#064E3B', border: '#A7F3D0' },
        { key: 'remote-work', label: 'Remote Work', primary: '#6366F1', secondary: '#818CF8', cta: '#10B981', bg: '#F5F3FF', text: '#312E81', border: '#E0E7FF' },
        { key: 'mental-health', label: 'Mental Health', primary: '#8B5CF6', secondary: '#C4B5FD', cta: '#10B981', bg: '#FAF5FF', text: '#4C1D95', border: '#EDE9FE' },
        { key: 'pet-tech', label: 'Pet Tech', primary: '#F97316', secondary: '#FB923C', cta: '#2563EB', bg: '#FFF7ED', text: '#9A3412', border: '#FED7AA' },
        { key: 'smart-home-iot', label: 'Smart Home / IoT', primary: '#1E293B', secondary: '#334155', cta: '#22C55E', bg: '#0F172A', text: '#F8FAFC', border: '#475569' },
        { key: 'ev-charging', label: 'EV / Charging', primary: '#0891B2', secondary: '#22D3EE', cta: '#22C55E', bg: '#ECFEFF', text: '#164E63', border: '#A5F3FC' },
        { key: 'subscription-box', label: 'Subscription Box', primary: '#D946EF', secondary: '#E879F9', cta: '#F97316', bg: '#FDF4FF', text: '#86198F', border: '#F5D0FE' },
        { key: 'podcast-platform', label: 'Podcast Platform', primary: '#1E1B4B', secondary: '#312E81', cta: '#F97316', bg: '#0F0F23', text: '#F8FAFC', border: '#4338CA' }
    ];

    function clamp(n, min, max) { return Math.min(max, Math.max(min, n)); }
    function hexToRgb(hex) {
        const h = hex.replace('#', '');
        const m = h.length === 3
            ? h.split('').map(c => c + c).join('')
            : h;
        return {
            r: parseInt(m.slice(0, 2), 16),
            g: parseInt(m.slice(2, 4), 16),
            b: parseInt(m.slice(4, 6), 16),
        };
    }
    function rgbToHex(r, g, b) {
        const toHex = (v) => clamp(Math.round(v), 0, 255).toString(16).padStart(2, '0');
        return '#' + toHex(r) + toHex(g) + toHex(b);
    }
    function rgbaFromHex(hex, alpha) {
        const c = hexToRgb(hex);
        return 'rgba(' + c.r + ', ' + c.g + ', ' + c.b + ', ' + clamp(alpha, 0, 1) + ')';
    }
    function mix(hexA, hexB, ratio) {
        const a = hexToRgb(hexA);
        const b = hexToRgb(hexB);
        const p = clamp(ratio, 0, 1);
        return rgbToHex(
            a.r + (b.r - a.r) * p,
            a.g + (b.g - a.g) * p,
            a.b + (b.b - a.b) * p
        );
    }
    function makeThemeFromPalette(p) {
        const darkBase = p.bg;
        const track = mix(darkBase, '#000000', 0.08);
        const thumb = mix(darkBase, p.text, 0.25);
        const hover = mix(darkBase, p.text, 0.38);
        return {
            label: p.label,
            dark: {
                DEFAULT: darkBase,
                light: mix(darkBase, '#ffffff', 0.08),
                lighter: mix(darkBase, '#ffffff', 0.14),
                50: mix(darkBase, '#000000', 0.12),
                100: mix(darkBase, '#000000', 0.06),
                200: darkBase,
                300: mix(darkBase, '#ffffff', 0.08),
                400: mix(darkBase, '#ffffff', 0.14),
                500: mix(darkBase, '#ffffff', 0.22),
                600: mix(darkBase, '#ffffff', 0.30),
            },
            coral: {
                DEFAULT: p.primary,
                dark: p.cta,
                light: p.secondary,
                50: mix(p.primary, '#ffffff', 0.92),
                100: mix(p.primary, '#ffffff', 0.82),
                200: mix(p.primary, '#ffffff', 0.68),
                300: mix(p.primary, '#ffffff', 0.50),
                400: mix(p.primary, '#ffffff', 0.28),
                500: p.primary,
                600: mix(p.primary, '#000000', 0.14),
                700: mix(p.primary, '#000000', 0.24),
                800: mix(p.primary, '#000000', 0.34),
                900: mix(p.primary, '#000000', 0.44),
            },
            scrollTrack: track,
            scrollThumb: thumb,
            scrollHover: hover,
            skeletonA: mix(darkBase, '#ffffff', 0.05),
            skeletonB: mix(darkBase, '#ffffff', 0.11),
        };
    }

    window.__themes = Object.fromEntries(
        window.__themeCatalog.map(p => [p.key, makeThemeFromPalette(p)])
    );

    // Legacy alias for older saved value
    window.__themes.coral = window.__themes['educational-app'];

    // Read theme from localStorage
    window.__currentTheme = localStorage.getItem('palomatika_theme') || 'educational-app';
    if (!window.__themes[window.__currentTheme]) {
        window.__currentTheme = window.__themes['educational-app'] ? 'educational-app' : Object.keys(window.__themes)[0];
    }
    const _t = window.__themes[window.__currentTheme];
    const _p = (window.__themeCatalog || []).find(p => p.key === window.__currentTheme)
        || (window.__themeCatalog || []).find(p => p.key === 'educational-app')
        || null;

    // Set CSS custom properties for non-Tailwind styles
    document.documentElement.style.setProperty('--scroll-track', _t.scrollTrack);
    document.documentElement.style.setProperty('--scroll-thumb', _t.scrollThumb);
    document.documentElement.style.setProperty('--scroll-hover', _t.scrollHover);
    document.documentElement.style.setProperty('--skeleton-a', _t.skeletonA);
    document.documentElement.style.setProperty('--skeleton-b', _t.skeletonB);
    document.documentElement.style.setProperty('--focus-ring', _t.coral.DEFAULT);
    if (_p) {
        document.documentElement.style.setProperty('--site-primary', _p.primary);
        document.documentElement.style.setProperty('--site-secondary', _p.secondary);
        document.documentElement.style.setProperty('--site-cta', _p.cta);
        document.documentElement.style.setProperty('--site-bg', _p.bg);
        document.documentElement.style.setProperty('--site-surface', mix(_p.bg, '#ffffff', 0.88));
        document.documentElement.style.setProperty('--site-surface-soft', mix(_p.bg, '#ffffff', 0.72));
        document.documentElement.style.setProperty('--site-text', _p.text);
        document.documentElement.style.setProperty('--site-muted', mix(_p.text, '#64748b', 0.45));
        document.documentElement.style.setProperty('--site-subtle', mix(_p.text, '#94a3b8', 0.62));
        document.documentElement.style.setProperty('--site-border', _p.border);
        document.documentElement.style.setProperty('--site-border-soft', mix(_p.border, '#ffffff', 0.34));
        document.documentElement.style.setProperty('--site-hover', rgbaFromHex(_p.primary, 0.10));

        document.documentElement.style.setProperty('--site-dark-bg', mix('#0b1028', _p.primary, 0.22));
        document.documentElement.style.setProperty('--site-dark-surface', mix('#111a3c', _p.primary, 0.16));
        document.documentElement.style.setProperty('--site-dark-surface-soft', mix('#1a2750', _p.primary, 0.20));
        document.documentElement.style.setProperty('--site-dark-text', mix('#ffffff', _p.secondary, 0.08));
        document.documentElement.style.setProperty('--site-dark-muted', mix('#cbd5e1', _p.secondary, 0.26));
        document.documentElement.style.setProperty('--site-dark-subtle', mix('#94a3b8', _p.secondary, 0.24));
        document.documentElement.style.setProperty('--site-dark-border', rgbaFromHex(_p.secondary, 0.34));
        document.documentElement.style.setProperty('--site-dark-border-soft', rgbaFromHex(_p.secondary, 0.22));
        document.documentElement.style.setProperty('--site-dark-hover', rgbaFromHex(_p.secondary, 0.18));
    }

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
