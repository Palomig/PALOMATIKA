<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Palomatika для родителей')</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Russo+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

<script src="/js/telegram-web-app.js"></script>
<script defer src="/js/alpine.min.js"></script>

@stack('head')

<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
  [x-cloak] { display: none !important; }

  :root {
    --bg:        #f0f4fb;
    --surface:   #ffffff;
    --surface2:  #f5f8ff;
    --border:    #dde3f0;
    --accent:    #3b6ef8;
    --text:      #1a2340;
    --muted:     #6b7a9a;
    --green:     #16a34a;
    --green-bg:  rgba(22,163,74,0.1);
    --red:       #dc2626;
    --red-bg:    rgba(220,38,38,0.1);
    --yellow:    #d97706;
    --yellow-bg: rgba(217,119,6,0.1);
    --r:         14px;
    --display:   'Russo One', sans-serif;
    --body:      'Nunito', sans-serif;
  }

  html, body { height: 100%; }
  body {
    font-family: var(--body);
    background: var(--bg);
    color: var(--text);
    min-height: 100%;
    padding-bottom: 72px;
  }

  .page { padding: 16px; max-width: 480px; margin: 0 auto; }

  @keyframes fadeUp   { from { opacity:0; transform:translateY(8px) } to { opacity:1; transform:none } }
  @keyframes fadeDown { from { opacity:0; transform:translateY(-8px) } to { opacity:1; transform:none } }

  /* bottom nav */
  .p-nav {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: var(--surface); border-top: 1px solid var(--border);
    display: flex; padding: 8px 0 max(8px, env(safe-area-inset-bottom));
    z-index: 100;
  }
  .p-nav-item {
    flex: 1; display: flex; flex-direction: column; align-items: center; gap: 3px;
    text-decoration: none; color: var(--muted); font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
    padding: 4px 0;
  }
  .p-nav-item.active { color: var(--accent); }
  .p-nav-icon { font-size: 20px; }

  @stack('styles')
</style>
</head>
<body>

<div class="page">
  @yield('content')
</div>

@include('parent.partials.nav')

</body>
</html>
