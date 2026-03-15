<!DOCTYPE html>
@php
  $isTeacherMiniApp = request()->is('tg/teacher*') || request()->is('tg/admin/variants');
  $teacherNavItems = [
    ['label' => 'Сегодня', 'href' => '/tg/teacher/dashboard', 'icon' => '◌', 'active' => request()->is('tg/teacher/dashboard')],
    ['label' => 'Ученики', 'href' => '/tg/teacher/students', 'icon' => '◎', 'active' => request()->is('tg/teacher/students*')],
    ['label' => 'Домашка', 'href' => '/tg/teacher/homework', 'icon' => '◍', 'active' => request()->is('tg/teacher/homework*')],
    ['label' => 'Ещё', 'href' => '/tg/teacher/variants', 'icon' => '⋯', 'active' => request()->is('tg/teacher/variants*') || request()->is('tg/teacher/referrals*') || request()->is('tg/admin/variants*')],
  ];
@endphp
<html lang="ru" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'palomatika')</title>

{{-- Google Fonts: Russo One (display) + Nunito (body) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Russo+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

{{-- Telegram WebApp SDK --}}
<script src="https://telegram.org/js/telegram-web-app.js"></script>

{{-- Alpine.js --}}
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

{{-- KaTeX math rendering --}}
@include('partials.head-katex')

@stack('head')

<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
  [x-cloak] { display: none !important; }

  :root {
    /* Dark theme (default) */
    --bg:        #111318;
    --surface:   #1c1f27;
    --surface2:  #23272f;
    --border:    #2a2e3a;
    --accent:    #4f8ef7;
    --text:      #eef0f6;
    --muted:     #6b7280;
    --muted2:    #3e4352;
    --green:     #34d07e;
    --green-bg:  rgba(52,208,126,0.1);
    --green-bd:  rgba(52,208,126,0.22);
    --red:       #f06060;
    --red-bg:    rgba(240,96,96,0.1);
    --red-bd:    rgba(240,96,96,0.22);
    --yellow:    #f0b440;
    --yellow-bg: rgba(240,180,64,0.1);
    --yellow-bd: rgba(240,180,64,0.22);
    --purple:    #a78bfa;
    --purple-bg: rgba(167,139,250,0.1);
    --purple-bd: rgba(167,139,250,0.22);
    --accent-bg: rgba(79,142,247,0.1);
    --accent-bd: rgba(79,142,247,0.25);
    --display:   'Russo One', sans-serif;
    --body:      'Nunito', sans-serif;
    --safe-bottom: env(safe-area-inset-bottom, 0px);
    --safe-top:    env(safe-area-inset-top, 0px);
    --r: 16px;
  }

  :root[data-theme="light"] {
    --bg:        #f7f8fc;
    --surface:   #ffffff;
    --surface2:  #f0f1f5;
    --border:    #e4e7f0;
    --accent:    #4f8ef7;
    --text:      #12182b;
    --muted:     #8892a4;
    --muted2:    #c5c9d4;
    --green:     #22b468;
    --green-bg:  rgba(34,180,104,0.08);
    --green-bd:  rgba(34,180,104,0.18);
    --red:       #e04848;
    --red-bg:    rgba(224,72,72,0.08);
    --red-bd:    rgba(224,72,72,0.18);
    --yellow:    #d49a20;
    --yellow-bg: rgba(212,154,32,0.08);
    --yellow-bd: rgba(212,154,32,0.18);
    --purple:    #8b6be0;
    --purple-bg: rgba(139,107,224,0.08);
    --purple-bd: rgba(139,107,224,0.18);
    --accent-bg: rgba(79,142,247,0.08);
    --accent-bd: rgba(79,142,247,0.18);
  }

  html, body {
    height: 100%;
    background: var(--bg);
    color: var(--text);
    font-family: var(--body);
    -webkit-font-smoothing: antialiased;
    overflow-x: hidden;
  }

  .page {
    max-width: 480px;
    margin: 0 auto;
    padding: calc(16px + var(--safe-top)) 16px calc(32px + var(--safe-bottom));
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-height: 100vh;
  }

  /* TOP BAR */
  .topbar {
    display: flex; align-items: center; gap: 12px;
    opacity: 0; animation: fadeDown 0.3s ease 0s forwards;
  }
  .back-btn {
    width: 36px; height: 36px;
    background: var(--surface); border: 1px solid var(--border); border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: var(--muted); cursor: pointer; flex-shrink: 0;
    transition: background 0.15s; text-decoration: none;
  }
  .back-btn:active { background: var(--surface2); }
  .topbar-title { font-family: var(--display); font-size: 16px; color: var(--text); }

  /* CARD */
  .card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 20px;
  }

  /* PILL / BADGE */
  .pill {
    padding: 3px 9px; border-radius: 6px;
    font-size: 9px; font-weight: 800;
    letter-spacing: 0.08em; text-transform: uppercase;
    flex-shrink: 0;
  }
  .pill-purple { background: var(--purple-bg); border: 1px solid var(--purple-bd); color: var(--purple); }
  .pill-green  { background: var(--green-bg); border: 1px solid var(--green-bd); color: var(--green); }
  .pill-yellow { background: var(--yellow-bg); border: 1px solid var(--yellow-bd); color: var(--yellow); }
  .pill-accent { background: var(--accent-bg); border: 1px solid var(--accent-bd); color: var(--accent); }
  .pill-red    { background: var(--red-bg); border: 1px solid var(--red-bd); color: var(--red); }

  /* STAT PILLS */
  .stat-pills { display: flex; gap: 8px; flex-wrap: wrap; }
  .stat-pill {
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 8px; padding: 6px 12px;
    font-size: 11px; font-weight: 700; color: var(--muted);
    display: flex; align-items: center; gap: 5px;
  }
  .stat-pill span { color: var(--text); }

  /* SECTION LABEL */
  .sec-label {
    font-size: 10px; font-weight: 800; letter-spacing: 0.14em;
    text-transform: uppercase; color: var(--muted);
  }

  /* BUTTON */
  .btn {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    border: none; border-radius: 14px; padding: 16px;
    font-family: var(--display); font-size: 15px;
    cursor: pointer; user-select: none; -webkit-user-select: none;
    transition: transform 0.1s, filter 0.15s;
    text-decoration: none;
  }
  .btn:active { transform: scale(0.97); }
  .btn-accent { background: var(--accent); color: #fff; }
  .btn-accent:hover { filter: brightness(1.1); }
  .btn-green { background: var(--green); color: #fff; }
  .btn-surface {
    background: var(--surface); border: 1px solid var(--border); color: var(--text);
  }

  /* NOTE */
  .note {
    background: var(--surface); border: 1px solid var(--border);
    border-left: 3px solid var(--muted2);
    border-radius: var(--r); padding: 13px 16px;
    font-size: 12px; font-weight: 600; color: var(--muted);
    line-height: 1.6;
  }

  /* ANIMATIONS */
  @keyframes fadeUp   { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
  @keyframes fadeDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
  @keyframes fadeIn   { from { opacity:0; } to { opacity:1; } }

  .anim-up   { opacity: 0; animation: fadeUp 0.3s ease forwards; }
  .anim-down { opacity: 0; animation: fadeDown 0.3s ease forwards; }
  .anim-in   { opacity: 0; animation: fadeIn 0.3s ease forwards; }

  /* UTILITIES */
  .display { font-family: var(--display); }
  .text-muted { color: var(--muted); }
  .text-accent { color: var(--accent); }
  .flex-center { display: flex; align-items: center; justify-content: center; }

  body.teacher-shell {
    background:
      radial-gradient(circle at top left, rgba(79, 142, 247, 0.18), transparent 28%),
      radial-gradient(circle at top right, rgba(52, 208, 126, 0.14), transparent 24%),
      linear-gradient(180deg, #f8fbff 0%, #eef4fb 46%, #edf2f8 100%);
    color: #132033;
  }
  body.teacher-shell .page {
    gap: 16px;
    padding-bottom: calc(112px + var(--safe-bottom));
  }
  body.teacher-shell .card,
  body.teacher-shell .note,
  body.teacher-shell .btn-surface,
  body.teacher-shell .back-btn,
  body.teacher-shell .metric,
  body.teacher-shell .list-item,
  body.teacher-shell .student,
  body.teacher-shell .hw-card,
  body.teacher-shell .student-row {
    background: rgba(255,255,255,0.9);
    border-color: rgba(146, 166, 197, 0.25);
    box-shadow: 0 12px 34px rgba(45, 75, 125, 0.08);
  }
  body.teacher-shell .topbar-title,
  body.teacher-shell .list-title,
  body.teacher-shell .student-name,
  body.teacher-shell .hw-title,
  body.teacher-shell .metric-value,
  body.teacher-shell .stat b {
    color: #132033;
  }
  body.teacher-shell .text-muted,
  body.teacher-shell .student-email,
  body.teacher-shell .list-meta,
  body.teacher-shell .metric-label,
  body.teacher-shell .muted,
  body.teacher-shell .student-sub,
  body.teacher-shell .hw-meta,
  body.teacher-shell .sec-label {
    color: #627089;
  }
  body.teacher-shell .btn-surface {
    color: #20304a;
  }
  body.teacher-shell .btn-accent {
    background: linear-gradient(135deg, #2563eb 0%, #3f8cff 100%);
    box-shadow: 0 12px 22px rgba(37, 99, 235, 0.22);
  }
  .teacher-shell .mini-hero {
    position: relative;
    overflow: hidden;
    border-radius: 24px;
    padding: 20px;
    background:
      radial-gradient(circle at top left, rgba(255,255,255,0.9), transparent 40%),
      linear-gradient(135deg, #133d88 0%, #2563eb 48%, #52a0ff 100%);
    color: #fff;
    box-shadow: 0 18px 42px rgba(24, 64, 135, 0.28);
  }
  .teacher-shell .mini-hero::after {
    content: "";
    position: absolute;
    inset: auto -18px -18px auto;
    width: 110px;
    height: 110px;
    border-radius: 28px;
    background: rgba(255,255,255,0.12);
    transform: rotate(18deg);
  }
  .teacher-shell .hero-kicker {
    font-size: 11px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    opacity: 0.76;
    font-weight: 800;
  }
  .teacher-shell .hero-title {
    margin-top: 8px;
    font-family: var(--display);
    font-size: 28px;
    line-height: 1.05;
  }
  .teacher-shell .hero-subtitle {
    margin-top: 10px;
    max-width: 280px;
    font-size: 13px;
    line-height: 1.5;
    opacity: 0.88;
  }
  .teacher-shell .hero-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-top: 16px;
  }
  .teacher-shell .hero-stat {
    border-radius: 18px;
    padding: 12px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(10px);
  }
  .teacher-shell .hero-stat-label {
    font-size: 10px;
    font-weight: 700;
    opacity: 0.72;
  }
  .teacher-shell .hero-stat-value {
    margin-top: 6px;
    font-size: 22px;
    font-family: var(--display);
  }
  .teacher-shell .section-card {
    background: rgba(255,255,255,0.88);
    border: 1px solid rgba(146, 166, 197, 0.24);
    border-radius: 22px;
    padding: 16px;
    box-shadow: 0 12px 34px rgba(45, 75, 125, 0.08);
  }
  .teacher-shell .section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
  }
  .teacher-shell .section-title {
    font-size: 18px;
    font-weight: 900;
    color: #132033;
  }
  .teacher-shell .section-note {
    font-size: 12px;
    color: #627089;
  }
  .teacher-shell .chip-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  .teacher-shell .chip {
    padding: 8px 12px;
    border-radius: 999px;
    text-decoration: none;
    border: 1px solid rgba(146, 166, 197, 0.28);
    background: rgba(255,255,255,0.74);
    color: #42526d;
    font-size: 12px;
    font-weight: 800;
  }
  .teacher-shell .chip.active {
    background: #e7f0ff;
    color: #1d4ed8;
    border-color: rgba(37, 99, 235, 0.18);
  }
  .teacher-shell .action-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }
  .teacher-shell .mini-btn {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:38px;
    padding:0 14px;
    border-radius:999px;
    text-decoration:none;
    font-size:12px;
    font-weight:800;
    border:1px solid rgba(255,255,255,.18);
    background:rgba(255,255,255,.14);
    color:#fff;
  }
  .teacher-shell .mini-btn.light {
    background:rgba(255,255,255,.92);
    color:#17408b;
    border-color:transparent;
  }
  .teacher-shell .action-tile {
    display: block;
    padding: 16px;
    text-decoration: none;
    border-radius: 20px;
    background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(241,246,255,0.9));
    border: 1px solid rgba(146, 166, 197, 0.24);
    color: #132033;
    box-shadow: 0 10px 28px rgba(45, 75, 125, 0.08);
  }
  .teacher-shell .action-tile strong {
    display: block;
    font-size: 14px;
    line-height: 1.25;
  }
  .teacher-shell .action-tile span {
    display: block;
    margin-top: 6px;
    font-size: 11px;
    color: #627089;
    line-height: 1.45;
  }
  .teacher-shell .mini-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .teacher-shell .mini-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px;
    border-radius: 18px;
    background: rgba(245, 248, 252, 0.9);
    border: 1px solid rgba(146, 166, 197, 0.2);
  }
  .teacher-shell .mini-list-item strong {
    display: block;
    color: #132033;
    font-size: 14px;
  }
  .teacher-shell .mini-list-item span {
    display: block;
    margin-top: 4px;
    color: #627089;
    font-size: 12px;
  }
  .teacher-shell .ghost-link {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:36px;
    padding:0 12px;
    border-radius:12px;
    border:1px solid rgba(146,166,197,.24);
    color:#20304a;
    background:#fff;
    text-decoration:none;
    font-size:12px;
    font-weight:800;
  }
  .teacher-shell .status-tag {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    min-height:28px;
    padding:0 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    white-space:nowrap;
  }
  .teacher-shell .status-tag.red { color:#b42318; background:#fff0ef; }
  .teacher-shell .status-tag.yellow { color:#a15c00; background:#fff7e6; }
  .teacher-shell .status-tag.green { color:#0f7b45; background:#ebfff4; }
  .teacher-shell .status-tag.accent { color:#1d4ed8; background:#ebf3ff; }
  .teacher-shell .miniapp-bottom-nav {
    position: fixed;
    left: 50%;
    bottom: calc(14px + var(--safe-bottom));
    transform: translateX(-50%);
    width: min(448px, calc(100vw - 20px));
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    padding: 10px;
    border-radius: 24px;
    background: rgba(10, 19, 37, 0.82);
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow: 0 18px 50px rgba(9, 20, 38, 0.3);
    backdrop-filter: blur(22px);
    z-index: 120;
  }
  .teacher-shell .miniapp-bottom-nav a {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 8px 6px;
    border-radius: 16px;
    text-decoration: none;
    color: rgba(230, 239, 255, 0.72);
    font-size: 11px;
    font-weight: 800;
  }
  .teacher-shell .miniapp-bottom-nav a.active {
    color: #fff;
    background: rgba(82, 160, 255, 0.22);
  }
  .teacher-shell .miniapp-bottom-nav .nav-icon {
    font-size: 16px;
    line-height: 1;
  }

  @stack('styles')
</style>
</head>
<body class="{{ $isTeacherMiniApp ? 'teacher-shell' : '' }}">

@yield('body')

@if($isTeacherMiniApp)
  <nav class="miniapp-bottom-nav" aria-label="Teacher sections">
    @foreach($teacherNavItems as $item)
      <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'active' : '' }}">
        <span class="nav-icon">{{ $item['icon'] }}</span>
        <span>{{ $item['label'] }}</span>
      </a>
    @endforeach
  </nav>
@endif

<script>
  // Telegram Mini App integration
  const tg = window.Telegram?.WebApp;
  if (tg) {
    tg.expand();
    tg.setHeaderColor(getComputedStyle(document.documentElement).getPropertyValue('--bg').trim() || '#111318');
    tg.setBackgroundColor(getComputedStyle(document.documentElement).getPropertyValue('--bg').trim() || '#111318');

    // Theme detection
    const theme = tg.colorScheme || 'dark';
    document.documentElement.setAttribute('data-theme', theme);
    tg.onEvent('themeChanged', () => {
      const t = tg.colorScheme;
      document.documentElement.setAttribute('data-theme', t);
      const bg = getComputedStyle(document.documentElement).getPropertyValue('--bg').trim();
      tg.setHeaderColor(bg);
      tg.setBackgroundColor(bg);
    });
    // Update header after theme applied
    requestAnimationFrame(() => {
      const bg = getComputedStyle(document.documentElement).getPropertyValue('--bg').trim();
      tg.setHeaderColor(bg);
      tg.setBackgroundColor(bg);
    });
  }

  // CSRF for fetch requests
  window._csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  window.fetchPost = (url, data = {}) => {
    return fetch(url, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window._csrf, 'Accept': 'application/json' },
      body: JSON.stringify(data),
    });
  };
</script>

@stack('scripts')

</body>
</html>
