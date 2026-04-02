<!DOCTYPE html>
<html lang="ru" x-data="{ theme: localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') }" :data-theme="theme">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
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

  @stack('styles')
</style>
</head>
<body>

@yield('body')

{{-- iOS PWA install prompt --}}
@include('pwa.shared.ios-install-prompt')

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

</body>
</html>
