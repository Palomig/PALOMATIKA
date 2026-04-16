<!DOCTYPE html>
<html lang="ru" x-data="{ theme: localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') }" :data-theme="theme">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="route-name" content="{{ Route::currentRouteName() ?? '' }}">
<meta name="vapid-public-key" content="{{ config('services.vapid.public_key') }}">
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

  @stack('styles')
</style>
</head>
<body>

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

{{-- Bug report button + modal --}}
<div x-data="bugReport()" x-cloak>

  {{-- Floating trigger button --}}
  <button @click="open = true" aria-label="Сообщить об ошибке"
    style="position:fixed;bottom:calc(20px + var(--safe-bottom));right:16px;z-index:900;
           height:48px;padding:0 18px 0 14px;border-radius:24px;border:none;
           background:rgba(220,38,38,0.92);color:#fff;cursor:pointer;
           display:flex;align-items:center;gap:8px;
           box-shadow:0 4px 16px rgba(220,38,38,.45),0 2px 6px rgba(0,0,0,.3);
           font-family:var(--body);font-size:14px;font-weight:600;
           transition:opacity .2s,transform .15s;white-space:nowrap;"
    :style="open ? 'opacity:0;pointer-events:none' : 'opacity:1'"
    @mouseenter="$el.style.transform='scale(1.04)'" @mouseleave="$el.style.transform='scale(1)'">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
      <path d="M12 8v4m0 4h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/>
    </svg>
    <span>Ошибка?</span>
  </button>

  {{-- Modal backdrop --}}
  <div x-show="open" @click.self="open = false" x-transition:enter="anim-in"
    style="position:fixed;inset:0;z-index:950;background:rgba(0,0,0,.55);display:flex;align-items:flex-end;justify-content:center;">

    {{-- Sheet --}}
    <div x-show="open" x-transition:enter="anim-up"
      style="width:100%;max-width:480px;background:var(--surface);border-radius:20px 20px 0 0;
             padding:24px 20px calc(24px + var(--safe-bottom));display:flex;flex-direction:column;gap:14px;">

      <div style="display:flex;align-items:center;justify-content:space-between;">
        <span style="font-family:var(--display);font-size:16px;color:var(--text);">Сообщить об ошибке</span>
        <button @click="open = false"
          style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:22px;line-height:1;padding:4px;">×</button>
      </div>

      <template x-if="sent">
        <div style="text-align:center;padding:20px 0;color:var(--green);font-weight:700;font-size:15px;">
          Спасибо! Репорт отправлен.
        </div>
      </template>

      <template x-if="!sent">
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div style="font-size:12px;color:var(--muted);font-weight:600;line-height:1.5;">
            Опишите что произошло (необязательно). Мы автоматически соберём данные о странице, браузере и устройстве.
          </div>

          <textarea x-model="description" placeholder="Что пошло не так?" rows="4"
            style="width:100%;background:var(--surface2);border:1px solid var(--border);border-radius:12px;
                   padding:12px;color:var(--text);font-family:var(--body);font-size:14px;
                   resize:none;outline:none;"></textarea>

          <div x-show="error" x-text="error" style="font-size:12px;color:var(--red);font-weight:600;"></div>

          <button @click="submit()" :disabled="loading"
            style="width:100%;background:var(--accent);color:#fff;border:none;border-radius:12px;
                   padding:14px;font-family:var(--display);font-size:15px;cursor:pointer;"
            :style="loading ? 'opacity:.6' : ''">
            <span x-show="!loading">Отправить</span>
            <span x-show="loading">Отправка...</span>
          </button>
        </div>
      </template>

    </div>
  </div>
</div>

<script>
function bugReport() {
  // Collect JS errors in a ring buffer (max 10)
  const jsErrors = [];
  const _onerror = window.onerror;
  window.onerror = function(msg, src, line, col, err) {
    if (jsErrors.length < 10) jsErrors.push({ message: msg, source: src + ':' + line });
    if (_onerror) _onerror.apply(this, arguments);
  };
  window.addEventListener('unhandledrejection', function(e) {
    if (jsErrors.length < 10) jsErrors.push({ message: 'UnhandledRejection: ' + (e.reason?.message || e.reason), source: '' });
  });

  return {
    open: false,
    sent: false,
    loading: false,
    error: '',
    description: '',

    async submit() {
      this.loading = true;
      this.error   = '';

      const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
      const screenInfo = {
        screen_w:   screen.width,
        screen_h:   screen.height,
        window_w:   window.innerWidth,
        window_h:   window.innerHeight,
        dpr:        window.devicePixelRatio || 1,
        pwa:        window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true,
        online:     navigator.onLine,
        connection: conn ? (conn.effectiveType || conn.type || '?') : 'unknown',
        language:   navigator.language,
        timezone:   Intl.DateTimeFormat().resolvedOptions().timeZone,
      };

      try {
        const pageContext = (typeof window.__bugContext === 'function') ? window.__bugContext() : null;
        const res = await window.fetchPost('/bug-report', {
          url:          window.location.href,
          route_name:   document.querySelector('meta[name="route-name"]')?.content || null,
          description:  this.description.trim() || null,
          user_agent:   navigator.userAgent,
          screen_info:  screenInfo,
          js_errors:    jsErrors.length ? jsErrors : null,
          page_context: pageContext,
        });

        if (!res.ok) throw new Error('Ошибка сервера');
        this.sent = true;
        setTimeout(() => { this.open = false; this.sent = false; this.description = ''; }, 2500);
      } catch (e) {
        this.error = 'Не удалось отправить. Попробуйте ещё раз.';
      } finally {
        this.loading = false;
      }
    },
  };
}
</script>

</body>
</html>
