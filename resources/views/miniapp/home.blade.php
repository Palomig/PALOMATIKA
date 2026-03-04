@extends('layouts.miniapp')
@section('title', 'ОГЭ по математике — palomatika')

@push('styles')
  /* ANIMATED BACKGROUND */
  body::before {
    content: '';
    position: fixed; inset: 0;
    background:
      radial-gradient(ellipse 80% 60% at 50% -10%, rgba(79,142,247,0.18) 0%, transparent 70%),
      radial-gradient(ellipse 50% 40% at 90% 80%, rgba(247,195,79,0.1) 0%, transparent 60%),
      radial-gradient(ellipse 40% 30% at 10% 70%, rgba(79,218,159,0.08) 0%, transparent 60%);
    pointer-events: none; z-index: 0;
  }
  body::after {
    content: '';
    position: fixed; inset: 0;
    background-image:
      linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none; z-index: 0;
  }

  .home-container {
    position: relative; z-index: 1;
    min-height: 100dvh;
    display: flex; flex-direction: column;
    padding: 0 20px;
    padding-bottom: calc(24px + var(--safe-bottom));
    max-width: 480px; margin: 0 auto;
  }

  .home-topbar {
    display: flex; justify-content: space-between; align-items: center;
    padding: 20px 0 0;
    opacity: 0; animation: fadeDown 0.5s ease 0.1s forwards;
  }
  .home-logo { font-family: var(--display); font-size: 15px; color: var(--accent); letter-spacing: 0.05em; }
  .exam-badge {
    background: var(--red-bg); border: 1px solid var(--red-bd);
    color: var(--red); font-size: 10px; font-weight: 800;
    padding: 4px 10px; border-radius: 20px;
    letter-spacing: 0.08em; text-transform: uppercase;
  }

  .hero {
    flex: 1; display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    text-align: center; padding: 32px 0 24px;
  }
  .hero-eyebrow {
    font-size: 11px; font-weight: 700; letter-spacing: 0.2em;
    text-transform: uppercase; color: var(--muted); margin-bottom: 16px;
    opacity: 0; animation: fadeUp 0.5s ease 0.2s forwards;
  }
  .hero-title {
    font-family: var(--display); font-size: clamp(28px, 8vw, 42px);
    line-height: 1.1; color: var(--text); margin-bottom: 8px;
    opacity: 0; animation: fadeUp 0.5s ease 0.3s forwards;
  }
  .hero-title em { color: var(--accent); font-style: normal; }
  .hero-sub {
    font-size: 14px; color: var(--muted); font-weight: 600; margin-bottom: 40px;
    opacity: 0; animation: fadeUp 0.5s ease 0.4s forwards;
  }

  /* COUNTDOWN */
  .countdown-wrap { width: 100%; opacity: 0; animation: fadeUp 0.6s ease 0.5s forwards; }
  .countdown-label {
    font-size: 10px; font-weight: 700; letter-spacing: 0.15em;
    text-transform: uppercase; color: var(--muted); text-align: center; margin-bottom: 16px;
  }
  .countdown { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 8px; }
  .cd-unit {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 16px 8px 12px; text-align: center;
    position: relative; overflow: hidden;
  }
  .cd-unit::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(79,142,247,0.5), transparent);
  }
  .cd-num {
    font-family: var(--display); font-size: clamp(26px, 7vw, 36px);
    color: var(--text); line-height: 1; display: block;
  }
  .cd-num.tick { animation: tickAnim 0.15s ease; }
  .cd-label {
    font-size: 9px; font-weight: 700; letter-spacing: 0.1em;
    text-transform: uppercase; color: var(--muted); margin-top: 6px; display: block;
  }
  .countdown-date { text-align: center; font-size: 11px; color: var(--muted); font-weight: 600; }
  .countdown-date strong { color: var(--yellow); }

  /* URGENCY */
  .urgency {
    margin: 24px 0;
    background: var(--red-bg); border: 1px solid var(--red-bd);
    border-radius: 12px; padding: 12px 16px;
    display: flex; align-items: center; gap: 10px;
    opacity: 0; animation: fadeUp 0.5s ease 0.7s forwards;
  }
  .urgency-icon { font-size: 18px; flex-shrink: 0; }
  .urgency-text { font-size: 12px; line-height: 1.5; color: var(--text); font-weight: 600; }
  .urgency-text span { color: var(--red); }

  /* STATS */
  .stats-row {
    display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 28px;
    opacity: 0; animation: fadeUp 0.5s ease 0.8s forwards;
  }
  .stat-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 8px; text-align: center;
  }
  .stat-num { font-family: var(--display); font-size: 20px; color: var(--green); display: block; }
  .stat-text {
    font-size: 9px; font-weight: 700; color: var(--muted);
    letter-spacing: 0.05em; text-transform: uppercase; margin-top: 2px;
  }

  /* BUTTONS */
  .btn-primary {
    width: 100%; background: var(--accent); color: #fff;
    border: none; border-radius: 16px; padding: 18px;
    font-family: var(--body); font-size: 16px; font-weight: 800;
    cursor: pointer; position: relative; overflow: hidden;
    transition: transform 0.15s, box-shadow 0.15s;
    box-shadow: 0 0 30px rgba(79,142,247,0.35);
    display: flex; align-items: center; justify-content: center; gap: 8px;
    opacity: 0; animation: fadeUp 0.5s ease 0.9s forwards;
  }
  .btn-primary::before {
    content: ''; position: absolute; top: 0; left: -100%;
    width: 60%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    animation: shimmer 2.5s ease 2s infinite;
  }
  .btn-primary:active { transform: scale(0.97); box-shadow: 0 0 15px rgba(79,142,247,0.35); }

  .btn-secondary {
    width: 100%; background: transparent; color: var(--muted);
    border: 1px solid var(--border); border-radius: 16px; padding: 15px;
    font-family: var(--body); font-size: 14px; font-weight: 700;
    cursor: pointer; margin-top: 10px;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: border-color 0.2s, color 0.2s;
    opacity: 0; animation: fadeUp 0.5s ease 1s forwards;
  }
  .btn-secondary:active { border-color: var(--accent); color: var(--accent); }

  .bottom-note {
    text-align: center; font-size: 11px; color: var(--muted); margin-top: 14px;
    opacity: 0; animation: fadeUp 0.5s ease 1.1s forwards;
  }
  .bottom-note strong { color: var(--green); }

  .auth-code {
    margin-top: 10px;
    background: rgba(239,68,68,.12);
    border: 1px solid rgba(239,68,68,.35);
    color: #ffb4b4;
    border-radius: 10px;
    padding: 8px 10px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .04em;
    text-align: center;
  }

  .pulse-dot {
    display: inline-block; width: 7px; height: 7px;
    background: var(--red); border-radius: 50%; margin-right: 6px;
    animation: pulse 1.5s ease infinite; flex-shrink: 0;
  }

  .btn-loading { pointer-events: none; opacity: 0.7; }
  .spinner { width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }

  .preloader {
    position: fixed; inset: 0; z-index: 9999;
    background:
      radial-gradient(900px 420px at 10% -10%, rgba(108,182,238,.22), transparent 55%),
      radial-gradient(800px 420px at 110% 110%, rgba(79,142,247,.18), transparent 50%),
      #0b1626;
    display:flex; align-items:center; justify-content:center;
    transition: opacity .25s ease;
  }
  .preloader-card { width:min(320px,calc(100vw - 32px)); background:rgba(18,31,51,.92); border:1px solid #263a56; border-radius:16px; padding:18px; text-align:center; }
  .preloader-logo { font-size:28px; margin-bottom:8px; }
  .preloader-title { color:#fff; font-weight:800; margin-bottom:8px; }
  .preloader-sub { color:#9fb3cf; font-size:12px; }
  .preloader-bar { height:6px; border-radius:999px; background:#1f3048; margin-top:12px; overflow:hidden; }
  .preloader-bar > span { display:block; width:40%; height:100%; background:linear-gradient(90deg,#4f8ef7,#6cb6ee); animation:pload 1.15s ease-in-out infinite; }

  @keyframes tickAnim { 0% { transform: translateY(-4px); opacity: 0.5; } 100% { transform: translateY(0); opacity: 1; } }
  @keyframes shimmer { 0% { left: -100%; } 100% { left: 200%; } }
  @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.7); } }
  @keyframes spin { to { transform: rotate(360deg); } }
  @keyframes pload { 0% { transform: translateX(-110%); } 100% { transform: translateX(280%); } }
@endpush

@section('body')
<div class="home-container" x-data="homePage()">
  <div class="preloader" x-show="!appReady" x-transition.opacity>
    <div class="preloader-card">
      <div class="preloader-logo">🤖</div>
      <div class="preloader-title">Загружаем mini app</div>
      <div class="preloader-sub">Подготавливаем безопасный вход через Telegram…</div>
      <div class="preloader-bar"><span></span></div>
    </div>
  </div>

  <div class="home-topbar">
    <div class="home-logo">palomatika</div>
    <div class="exam-badge">ОГЭ 2026</div>
  </div>

  <div class="hero">
    <div class="hero-eyebrow">математика · 9 класс</div>
    <h1 class="hero-title">Готов к<br><em>ОГЭ?</em></h1>
    <p class="hero-sub">Проверь себя на реальных заданиях ОГЭ — бесплатно</p>

    <div class="countdown-wrap">
      <div class="countdown-label">
        <span class="pulse-dot"></span>до экзамена осталось
      </div>
      <div class="countdown">
        <div class="cd-unit">
          <span class="cd-num" id="cd-days" x-text="pad(days)">00</span>
          <span class="cd-label">дней</span>
        </div>
        <div class="cd-unit">
          <span class="cd-num" id="cd-hours" x-text="pad(hours)">00</span>
          <span class="cd-label">часов</span>
        </div>
        <div class="cd-unit">
          <span class="cd-num" id="cd-mins" x-text="pad(mins)">00</span>
          <span class="cd-label">минут</span>
        </div>
        <div class="cd-unit">
          <span class="cd-num" id="cd-secs" x-text="pad(secs)">00</span>
          <span class="cd-label">секунд</span>
        </div>
      </div>
      <div class="countdown-date">Экзамен: <strong>2 июня 2026, 10:00 МСК</strong></div>
    </div>
  </div>

  <div class="urgency">
    <span class="urgency-icon">⚡</span>
    <div class="urgency-text">
      Попробуй новый формат — Мини-ОГЭ ~10 минут.<br>
      <span>Для тех у кого мало времени.</span>
    </div>
  </div>


  @auth
    <a href="/tg/dashboard" class="btn-primary" id="go-btn" style="text-decoration:none;" :style="!appReady ? 'pointer-events:none;opacity:.6' : ''">
      <span style="display:flex;align-items:center;gap:8px;">🚀 Начать подготовку</span>
    </a>
  @else
    <button class="btn-primary" id="start-btn" @click="handleLogin()" :disabled="!appReady">
      <span id="start-btn-text" style="display:flex;align-items:center;gap:8px;" x-text="appReady ? '🚀 Начать подготовку' : '⏳ Инициализация…'"></span>
    </button>
  @endauth

  <button class="btn-secondary" @click="handleInvite()">
    👥 Пригласить одноклассника
  </button>

  <div class="auth-code" x-show="authErrorCode" x-cloak>
    Код ошибки: <span x-text="authErrorCode"></span>
  </div>

  <div class="bottom-note">
    <strong>Бесплатно</strong> · задания из банка ФИПИ · результат сразу
  </div>


</div>
@endsection

@push('scripts')
<script>
function homePage() {
  const examDate = new Date('2026-06-02T10:00:00+03:00');
  return {
    days: 0, hours: 0, mins: 0, secs: 0,
    loginInProgress: false,
    appReady: false,
    authErrorCode: '',
    traceId: '',

    pad(n) { return String(n).padStart(2, '0'); },

    async init() {
      this.updateCountdown();
      setInterval(() => this.updateCountdown(), 1000);

      await this.waitTelegramReady();
      this.appReady = true;

      // If mini-app was opened with startapp=oge_variant_hash_..., forward into dashboard flow.
      const tg = window.Telegram?.WebApp;
      const startParamFromTg = tg?.initDataUnsafe?.start_param || '';
      const urlParams = new URLSearchParams(window.location.search);
      const startParamFromUrl = urlParams.get('startapp') || urlParams.get('tgWebAppStartParam') || '';
      const startParam = (startParamFromTg || startParamFromUrl || '').trim();
      if (startParam && /^oge_variant_hash_[a-z0-9]{8,32}$/i.test(startParam)) {
        @auth
          window.location.replace('/tg/dashboard?startapp=' + encodeURIComponent(startParam));
          return;
        @else
          // For unauthenticated users opened via battle deep link,
          // auto-run Telegram auth without requiring manual "Начать подготовку" click.
          this.handleLogin();
          return;
        @endauth
      }

      @if(session('error'))
      {
        const tg = window.Telegram?.WebApp;
        const errMsg = @json(session('error'));
        if (tg && tg.showAlert) { tg.showAlert(errMsg); }
        else { alert(errMsg); }
      }
      @endif
    },

    async waitTelegramReady() {
      const tg = window.Telegram?.WebApp;
      if (!tg) {
        // Outside Telegram — no initData will ever appear, but allow page interaction.
        await new Promise(r => setTimeout(r, 350));
        return;
      }

      try { tg.ready?.(); } catch (_) {}

      const started = Date.now();
      const timeout = 4000; // 4s — enough for slow VPN/proxy connections
      while (Date.now() - started < timeout) {
        const initData = typeof tg.initData === 'string' ? tg.initData.trim() : '';
        if (initData.length > 20) return; // initData is ready
        await new Promise(r => setTimeout(r, 100));
      }

      // Timeout reached — initData never arrived.
      // appReady will still be set to true so the UI is interactive,
      // but handleLogin() will show an error if initData is empty.
      console.warn('[palomatika] Telegram initData not available after ' + timeout + 'ms');
    },

    updateCountdown() {
      const diff = examDate - new Date();
      if (diff <= 0) return;
      this.days  = Math.floor(diff / 86400000);
      this.hours = Math.floor((diff % 86400000) / 3600000);
      this.mins  = Math.floor((diff % 3600000) / 60000);
      this.secs  = Math.floor((diff % 60000) / 1000);
    },

    handleLogin() {
      if (this.loginInProgress) return;
      this.loginInProgress = true;
      this.authErrorCode = '';
      this.traceId = this.generateTraceId();
      this.sendDiag('auth_start', 'A_START');

      const btn = document.getElementById('start-btn');
      const btnText = document.getElementById('start-btn-text');
      if (btn) btn.classList.add('btn-loading');
      if (btnText) btnText.innerHTML = '<span class="spinner"></span> Входим...';

      const tg = window.Telegram?.WebApp;
      const initData = tg && typeof tg.initData === 'string' ? tg.initData.trim() : '';
      const startParam = (tg?.initDataUnsafe?.start_param || new URLSearchParams(window.location.search).get('startapp') || '').trim();

      if (!initData) {
        this.authErrorCode = 'E_INITDATA_EMPTY';
        this.sendDiag('auth_precheck', this.authErrorCode);
        if (tg && tg.showAlert) { tg.showAlert('Не удалось получить данные Telegram. Попробуйте перезапустить мини-приложение.'); }
        else { alert('Откройте приложение через Telegram'); }
        if (btnText) btnText.innerHTML = '🚀 Начать подготовку';
        if (btn) btn.classList.remove('btn-loading');
        this.loginInProgress = false;
        return;
      }

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 12000);

      fetch('/api/auth/telegram/webapp-login', {
        method: 'POST',
        credentials: 'include',
        signal: controller.signal,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': window._csrf,
        },
        body: JSON.stringify({ initData, startParam, trace_id: this.traceId }),
      })
      .then(async (res) => {
        clearTimeout(timeoutId);
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
          this.authErrorCode = `E_WEBAPP_${res.status || 'FAIL'}`;
          this.sendDiag('webapp_login_response', this.authErrorCode, { http_status: res.status || null });
          throw new Error(data.message || 'Ошибка авторизации');
        }

        // Verify session really exists in WebView before redirect.
        const meRes = await fetch('/api/telegram/session-check', { credentials: 'include' });
        const me = await meRes.json().catch(() => ({}));
        if (!meRes.ok || !me.authenticated) {
          this.authErrorCode = 'E_COOKIE_SESSION';
          this.sendDiag('auth_post_login_check', this.authErrorCode, { me_status: meRes.status || null });
          throw new Error('Сессия не сохранилась');
        }

        this.sendDiag('auth_success', 'A_OK');
        const target = data.redirect_to || data.redirect || '/tg/dashboard';
        window.location.replace(target);
      })
      .catch((e) => {
        clearTimeout(timeoutId);
        if (!this.authErrorCode) {
          this.authErrorCode = (e && e.name === 'AbortError') ? 'E_WEBAPP_TIMEOUT' : 'E_WEBAPP_NETWORK';
        }
        this.sendDiag('webapp_login_catch', this.authErrorCode, { err: (e && e.message) ? e.message : null });

        if (btnText) btnText.innerHTML = '🚀 Начать подготовку';
        if (btn) btn.classList.remove('btn-loading');
        this.loginInProgress = false;
        const msg = (e && e.message) ? `${e.message} (${this.authErrorCode})` : `Ошибка авторизации (${this.authErrorCode})`;
        if (tg && tg.showAlert) tg.showAlert(msg);
        else alert(msg);
      });
    },

    handleInvite() {
      const tg = window.Telegram?.WebApp;
      const botUsername = '{{ config("services.telegram.bot_username", "palomatika_auth_bot") }}';
      const appLink = `https://t.me/${botUsername}?start=invite`;
      const text = 'Решай реальные задания ОГЭ по математике — бесплатно! Проверь свой уровень 🎯';

      if (tg && tg.openTelegramLink) {
        const shareUrl = `https://t.me/share/url?url=${encodeURIComponent(appLink)}&text=${encodeURIComponent(text)}`;
        tg.openTelegramLink(shareUrl);
      } else {
        const fullText = text + '\n' + appLink;
        if (navigator.share) {
          navigator.share({ title: 'palomatika — ОГЭ', text: fullText });
        } else if (navigator.clipboard) {
          navigator.clipboard.writeText(fullText).then(() => alert('Ссылка скопирована!'));
        }
      }
    },

    generateTraceId() {
      return `hm-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;
    },

    async sendDiag(stage, code, extra = {}) {
      try {
        const tg = window.Telegram?.WebApp;
        const initData = typeof tg?.initData === 'string' ? tg.initData.trim() : '';
        const hasHash = initData.includes('hash=');
        const hasSignature = initData.includes('signature=');

        await fetch('/api/telegram/diag', {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window._csrf,
          },
          body: JSON.stringify({
            trace_id: this.traceId || null,
            stage,
            code,
            platform: tg?.platform || null,
            version: tg?.version || null,
            init_len: initData.length,
            has_hash: hasHash,
            has_signature: hasSignature,
            path: 'miniapp_home',
            extra,
          }),
        });
      } catch (_) {}
    },
  };
}
</script>
@endpush
