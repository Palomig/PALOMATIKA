<!DOCTYPE html>
<html lang="ru" x-data="{ theme: localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') }" :data-theme="theme">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="route-name" content="{{ Route::currentRouteName() ?? '' }}">
<meta name="theme-color" content="#111318" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#f7f8fc" media="(prefers-color-scheme: light)">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="@yield('app-name', 'Palomatika')">
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<title>@yield('title', 'Palomatika')</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Russo+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

{{-- Alpine.js --}}
<script defer src="/js/alpine.min.js"></script>

@stack('katex')
@stack('head')

<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
  [x-cloak] { display: none !important; }

  :root {
    --bg: #111318; --surface: #1c1f27; --surface2: #23272f; --border: #2a2e3a;
    --accent: #4f8ef7; --text: #eef0f6; --muted: #6b7280; --muted2: #3e4352;
    --green: #34d07e; --green-bg: rgba(52,208,126,0.1); --green-bd: rgba(52,208,126,0.22);
    --red: #f06060; --red-bg: rgba(240,96,96,0.1); --red-bd: rgba(240,96,96,0.22);
    --yellow: #f0b440; --yellow-bg: rgba(240,180,64,0.1); --yellow-bd: rgba(240,180,64,0.22);
    --purple: #a78bfa; --purple-bg: rgba(167,139,250,0.1); --purple-bd: rgba(167,139,250,0.22);
    --accent-bg: rgba(79,142,247,0.1); --accent-bd: rgba(79,142,247,0.25);
    --display: 'Russo One', sans-serif; --body: 'Nunito', sans-serif;
    --safe-bottom: env(safe-area-inset-bottom, 0px); --safe-top: env(safe-area-inset-top, 0px);
    --r: 16px;
  }
  :root[data-theme="light"] {
    --bg: #f7f8fc; --surface: #ffffff; --surface2: #f0f1f5; --border: #e4e7f0;
    --text: #12182b; --muted: #8892a4; --muted2: #c5c9d4;
    --green: #22b468; --green-bg: rgba(34,180,104,0.08); --green-bd: rgba(34,180,104,0.18);
    --red: #e04848; --red-bg: rgba(224,72,72,0.08); --red-bd: rgba(224,72,72,0.18);
    --yellow: #d49a20; --yellow-bg: rgba(212,154,32,0.08); --yellow-bd: rgba(212,154,32,0.18);
    --purple: #8b6be0; --purple-bg: rgba(139,107,224,0.08); --purple-bd: rgba(139,107,224,0.18);
    --accent-bg: rgba(79,142,247,0.08); --accent-bd: rgba(79,142,247,0.18);
  }
  html, body { height: 100%; background: var(--bg); color: var(--text); font-family: var(--body); -webkit-font-smoothing: antialiased; overflow-x: hidden; }
  .page { max-width: 480px; margin: 0 auto; padding: calc(16px + var(--safe-top)) 16px calc(32px + var(--safe-bottom)); display: flex; flex-direction: column; gap: 14px; min-height: 100vh; }
  .topbar { display: flex; align-items: center; gap: 12px; opacity: 0; animation: fadeDown 0.3s ease 0s forwards; }
  .back-btn { width: 36px; height: 36px; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--muted); cursor: pointer; flex-shrink: 0; transition: background 0.15s; text-decoration: none; }
  .back-btn:active { background: var(--surface2); }
  .topbar-title { font-family: var(--display); font-size: 16px; color: var(--text); }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 20px; }
  .pill { padding: 3px 9px; border-radius: 6px; font-size: 9px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; flex-shrink: 0; }
  .pill-purple { background: var(--purple-bg); border: 1px solid var(--purple-bd); color: var(--purple); }
  .pill-green  { background: var(--green-bg);  border: 1px solid var(--green-bd);  color: var(--green); }
  .pill-yellow { background: var(--yellow-bg); border: 1px solid var(--yellow-bd); color: var(--yellow); }
  .pill-accent { background: var(--accent-bg); border: 1px solid var(--accent-bd); color: var(--accent); }
  .pill-red    { background: var(--red-bg);    border: 1px solid var(--red-bd);    color: var(--red); }
  .stat-pills { display: flex; gap: 8px; flex-wrap: wrap; }
  .stat-pill { background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; padding: 6px 12px; font-size: 11px; font-weight: 700; color: var(--muted); display: flex; align-items: center; gap: 5px; }
  .stat-pill span { color: var(--text); }
  .sec-label { font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: var(--muted); }
  .btn { display: flex; align-items: center; justify-content: center; gap: 10px; border: none; border-radius: 14px; padding: 16px; font-family: var(--display); font-size: 15px; cursor: pointer; user-select: none; -webkit-user-select: none; transition: transform 0.1s, filter 0.15s; text-decoration: none; }
  .btn:active { transform: scale(0.97); }
  .btn-accent { background: var(--accent); color: #fff; }
  .btn-accent:hover { filter: brightness(1.1); }
  .btn-green { background: var(--green); color: #fff; }
  .btn-surface { background: var(--surface); border: 1px solid var(--border); color: var(--text); }
  .btn-left { justify-content: flex-start !important; gap: 14px !important; width: 100%; }
  .note { background: var(--surface); border: 1px solid var(--border); border-left: 3px solid var(--muted2); border-radius: var(--r); padding: 13px 16px; font-size: 12px; font-weight: 600; color: var(--muted); line-height: 1.6; }
  .hidden { display: none !important; }
  @keyframes fadeUp   { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
  @keyframes fadeDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
  @keyframes fadeIn   { from { opacity:0; } to { opacity:1; } }
  .anim-up   { opacity: 0; animation: fadeUp 0.3s ease forwards; }
  .anim-down { opacity: 0; animation: fadeDown 0.3s ease forwards; }
  .anim-in   { opacity: 0; animation: fadeIn 0.3s ease forwards; }
  .display { font-family: var(--display); }
  .text-muted { color: var(--muted); }
  .text-accent { color: var(--accent); }
  .flex-center { display: flex; align-items: center; justify-content: center; }

  .pwa-role-switcher-shell { position: sticky; top: 0; z-index: 800; padding: calc(8px + var(--safe-top)) 12px 8px; background: color-mix(in srgb, var(--bg) 88%, transparent); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border-bottom: 1px solid var(--border); }
  .pwa-role-switcher { display: flex; gap: 6px; justify-content: center; }
  .pwa-role-switcher__btn { flex: 0 1 140px; text-align: center; padding: 8px 14px; border-radius: 999px; border: 1px solid var(--border); background: var(--surface2); color: var(--muted); font-family: var(--body); font-size: 13px; font-weight: 700; text-decoration: none; letter-spacing: 0.02em; transition: background 0.15s, color 0.15s, border-color 0.15s; }
  .pwa-role-switcher__btn:active { transform: scale(0.97); }
  .pwa-role-switcher__btn.is-active { background: var(--accent); color: #fff; border-color: var(--accent); }
  .pwa-context-switcher { margin-top: 8px; display: flex; flex-direction: column; gap: 6px; align-items: center; }
  .pwa-context-switcher__row { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }
  .pwa-context-switcher__row form { margin: 0; }
  .pwa-context-switcher__btn { min-width: 74px; text-align: center; padding: 7px 12px; border-radius: 999px; border: 1px solid var(--border); background: var(--surface2); color: var(--muted); font-family: var(--body); font-size: 12px; font-weight: 700; cursor: pointer; }
  .pwa-context-switcher__btn--grade { min-width: 42px; }
  .pwa-context-switcher__btn.is-active { background: var(--accent); color: #fff; border-color: var(--accent); }
  @supports not (backdrop-filter: blur(8px)) { .pwa-role-switcher-shell { background: var(--bg); } }

  .bug-report-trigger {
    position: fixed;
    right: max(16px, env(safe-area-inset-right, 0px));
    bottom: calc(20px + var(--safe-bottom));
    z-index: 1100;
    min-width: 82px;
    height: 26px;
    border: 1px solid rgba(255,255,255,.16);
    border-radius: 999px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 0 9px;
    box-shadow: 0 10px 20px rgba(127,29,29,.28), 0 3px 8px rgba(0,0,0,.24);
    transition: transform .18s ease, opacity .18s ease, box-shadow .18s ease, filter .18s ease;
    font-family: var(--display);
    font-size: 11px;
    font-weight: 700;
    touch-action: manipulation;
    -webkit-appearance: none;
    appearance: none;
  }
  .bug-report-trigger::before {
    content: '';
    position: absolute;
    inset: -12px -10px;
  }
  .bug-report-trigger:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(127,29,29,.32), 0 5px 10px rgba(0,0,0,.28);
    filter: brightness(1.02);
  }
  .bug-report-trigger:active { transform: translateY(0) scale(.98); }
  .bug-report-trigger.is-hidden {
    opacity: 0;
    pointer-events: none;
  }
  .bug-report-trigger__icon {
    width: 10px;
    height: 10px;
    flex: 0 0 auto;
  }
  .bug-report-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1150;
    background: rgba(3,6,14,.72);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .bug-report-card {
    width: min(100%, 520px);
    background: linear-gradient(180deg, rgba(19,28,46,.98) 0%, rgba(12,18,31,.98) 100%);
    border: 1px solid rgba(148,163,184,.18);
    border-radius: 24px;
    padding: 22px 20px calc(20px + var(--safe-bottom));
    display: flex;
    flex-direction: column;
    gap: 14px;
    box-shadow: 0 24px 80px rgba(0,0,0,.45);
  }
  .bug-report-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }
  .bug-report-lead {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
  }
  .bug-report-lead__badge {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: rgba(239,68,68,.14);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f87171;
    flex: 0 0 auto;
  }
  .bug-report-lead__title {
    font-family: var(--display);
    font-size: 18px;
    color: var(--text);
    line-height: 1.2;
  }
  .bug-report-lead__text {
    margin-top: 4px;
    font-size: 12px;
    color: var(--muted);
    line-height: 1.45;
  }
  .bug-report-close {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: rgba(255,255,255,.04);
    color: var(--muted);
    cursor: pointer;
    font-size: 22px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    transition: background .15s ease, color .15s ease, transform .15s ease;
  }
  .bug-report-close:hover {
    background: rgba(255,255,255,.08);
    color: var(--text);
  }
  .bug-report-close:active { transform: scale(.96); }
  .bug-report-success {
    text-align: center;
    padding: 20px 0;
    color: var(--green);
    font-weight: 700;
    font-size: 15px;
  }
  .bug-report-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .bug-report-textarea {
    width: 100%;
    min-height: 128px;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(148,163,184,.18);
    border-radius: 16px;
    padding: 14px 15px;
    color: var(--text);
    font-family: var(--body);
    font-size: 14px;
    line-height: 1.5;
    resize: vertical;
    outline: none;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.03);
    transition: border-color .15s ease, box-shadow .15s ease;
  }
  .bug-report-textarea:focus {
    border-color: rgba(248,113,113,.42);
    box-shadow: 0 0 0 3px rgba(239,68,68,.12), inset 0 1px 0 rgba(255,255,255,.03);
  }
  .bug-report-error {
    font-size: 12px;
    color: var(--red);
    font-weight: 700;
  }
  .bug-report-submit {
    width: 100%;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    border: none;
    border-radius: 16px;
    padding: 15px 16px;
    font-family: var(--display);
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 12px 26px rgba(127,29,29,.28);
    transition: transform .15s ease, opacity .15s ease, filter .15s ease;
    -webkit-appearance: none;
    appearance: none;
  }
  .bug-report-submit:hover { filter: brightness(1.03); }
  .bug-report-submit:active { transform: scale(.985); }
  .bug-report-submit.is-loading,
  .bug-report-submit:disabled {
    opacity: .65;
    cursor: default;
    filter: none;
  }

  @media (max-width: 640px) {
    .bug-report-trigger {
      min-width: 0;
      width: 28px;
      height: 26px;
      padding: 0;
      border-radius: 12px;
    }
    .bug-report-trigger__label {
      display: none;
    }
    .bug-report-trigger__icon {
      width: 10px;
      height: 10px;
    }
    .bug-report-backdrop {
      align-items: flex-end;
      padding: 12px;
    }
    .bug-report-card {
      width: 100%;
      border-radius: 24px 24px 18px 18px;
      padding-bottom: calc(18px + var(--safe-bottom));
    }
  }

  @stack('styles')
</style>
</head>
<body>

@include('pwa.shared.role-switcher')

@yield('body')

{{-- iOS PWA install prompt (only on dashboard, injected via @stack) --}}
@stack('install-prompt')

<script>
  window._csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  window.fetchPost = (url, data = {}) => fetch(url, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window._csrf, 'Accept': 'application/json' },
    body: JSON.stringify(data),
  });

  // Register service worker
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  }

  // Android install prompt
  let deferredPrompt;
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    document.getElementById('pwa-install-btn')?.classList.remove('hidden');
  });

  window.installPwa = async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    await deferredPrompt.userChoice;
    deferredPrompt = null;
    document.getElementById('pwa-install-btn')?.classList.add('hidden');
  };
</script>

@stack('scripts')

@include('pwa.shared.bug-report')

</body>
</html>
