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

  .pulse-dot {
    display: inline-block; width: 7px; height: 7px;
    background: var(--red); border-radius: 50%; margin-right: 6px;
    animation: pulse 1.5s ease infinite; flex-shrink: 0;
  }

  .btn-loading { pointer-events: none; opacity: 0.7; }
  .spinner { width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }

  @keyframes tickAnim { 0% { transform: translateY(-4px); opacity: 0.5; } 100% { transform: translateY(0); opacity: 1; } }
  @keyframes shimmer { 0% { left: -100%; } 100% { left: 200%; } }
  @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.7); } }
  @keyframes spin { to { transform: rotate(360deg); } }
@endpush

@section('body')
<div class="home-container" x-data="homePage()">

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
      Каждый день без практики — это пропущенные баллы.<br>
      <span>Узнай свой реальный уровень прямо сейчас.</span>
    </div>
  </div>

  <div class="stats-row">
    <div class="stat-card">
      <span class="stat-num">14</span>
      <span class="stat-text">заданий</span>
    </div>
    <div class="stat-card">
      <span class="stat-num">~45</span>
      <span class="stat-text">минут</span>
    </div>
    <div class="stat-card">
      <span class="stat-num">0₽</span>
      <span class="stat-text">бесплатно</span>
    </div>
  </div>

  @auth
    <a href="/tg/dashboard" class="btn-primary" id="go-btn" style="text-decoration:none;">
      <span style="display:flex;align-items:center;gap:8px;">🚀 Начать подготовку</span>
    </a>
  @else
    <button class="btn-primary" id="start-btn" @click="handleLogin()">
      <span id="start-btn-text" style="display:flex;align-items:center;gap:8px;">🚀 Начать подготовку</span>
    </button>
  @endauth

  <button class="btn-secondary" @click="handleInvite()">
    👥 Пригласить одноклассника
  </button>

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

    pad(n) { return String(n).padStart(2, '0'); },

    init() {
      this.updateCountdown();
      setInterval(() => this.updateCountdown(), 1000);

      // After login reload: auto-redirect to dashboard/onboarding
      const pendingRedirect = sessionStorage.getItem('_tg_redirect');
      if (pendingRedirect) {
        sessionStorage.removeItem('_tg_redirect');
        // Small delay to ensure cookie is fully committed
        setTimeout(() => { window.location.href = pendingRedirect; }, 50);
      }
    },

    updateCountdown() {
      const diff = examDate - new Date();
      if (diff <= 0) return;
      this.days  = Math.floor(diff / 86400000);
      this.hours = Math.floor((diff % 86400000) / 3600000);
      this.mins  = Math.floor((diff % 3600000) / 60000);
      this.secs  = Math.floor((diff % 60000) / 1000);
    },

    async handleLogin() {
      if (this.loginInProgress) return;
      this.loginInProgress = true;

      // Show loading state
      const btn = document.getElementById('start-btn');
      const btnText = document.getElementById('start-btn-text');
      if (btn) btn.classList.add('btn-loading');
      if (btnText) btnText.innerHTML = '<span class="spinner"></span> Входим...';

      const tg = window.Telegram?.WebApp;
      const initData = tg && typeof tg.initData === 'string' ? tg.initData.trim() : '';

      if (!initData) {
        // No Telegram data — try form POST fallback
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/tg/auth';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'initData';
        input.value = '';
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
        return;
      }

      try {
        const response = await fetch('/api/auth/telegram/webapp-login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
          },
          body: JSON.stringify({
            initData: initData,
            initDataUnsafe: tg.initDataUnsafe || null,
            startParam: tg.initDataUnsafe?.start_param || null,
          })
        });

        const data = await response.json();

        if (response.ok && data.success) {
          // Save redirect target, then reload same page (mobile WebView
          // loses cookies on href navigation, but reload() preserves them)
          sessionStorage.setItem('_tg_redirect', data.redirect_to || '/tg/dashboard');
          window.location.reload();
          return;
        }

        // Login failed
        if (btnText) btnText.innerHTML = '🚀 Начать подготовку';
        if (btn) btn.classList.remove('btn-loading');
        this.loginInProgress = false;

        const msg = data.message || data.error || 'Ошибка входа';
        if (tg && tg.showAlert) { tg.showAlert(msg); }
        else { alert(msg); }
      } catch (err) {
        if (btnText) btnText.innerHTML = '🚀 Начать подготовку';
        if (btn) btn.classList.remove('btn-loading');
        this.loginInProgress = false;

        if (tg && tg.showAlert) { tg.showAlert('Ошибка соединения'); }
        else { alert('Ошибка соединения'); }
      }
    },

    handleInvite() {
      const tg = window.Telegram?.WebApp;
      const botUsername = '{{ config("services.telegram.bot_username", "palomatika_auth_bot") }}';
      const link = `https://t.me/${botUsername}/app`;
      const text = 'Готовься к ОГЭ по математике бесплатно! ' + link;
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
          if (tg && tg.showAlert) { tg.showAlert('Ссылка скопирована! Отправь другу в чат'); }
          else { alert('Ссылка скопирована!'); }
        });
      } else if (navigator.share) {
        navigator.share({ title: 'palomatika — ОГЭ', text: text });
      }
    },
  };
}
</script>
@endpush
