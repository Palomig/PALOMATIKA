@extends('layouts.pwa')

@section('title', $context === 'teacher' ? 'Вход — Репетитор' : 'Вход — Palomatika')

@section('body')
<div class="page" style="justify-content:center;min-height:100vh;">
  <div class="anim-up" style="text-align:center;margin-bottom:8px;">
    <div style="font-family:var(--display);font-size:28px;color:var(--accent);">palomatika</div>
    <div style="font-size:13px;color:var(--muted);margin-top:4px;">
      {{ $context === 'teacher' ? 'Кабинет репетитора' : 'Подготовка к ОГЭ' }}
    </div>
  </div>

  @if(session('error'))
  <div class="note" style="border-left-color:var(--red);color:var(--red);">{{ session('error') }}</div>
  @endif

  <div class="card anim-up" style="animation-delay:0.05s;">
    <div class="sec-label" style="margin-bottom:16px;">Войти через</div>
    <div style="display:flex;flex-direction:column;gap:10px;">

      {{-- Yandex --}}
      <a href="/auth/yandex" class="btn btn-surface btn-left">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#FC3F1D"><path d="M2.04 12c0-5.523 4.476-10 9.998-10C17.522 2 22 6.477 22 12s-4.478 10-9.962 10C6.516 22 2.04 17.523 2.04 12zm11.07 4.888V7.07h1.41c1.547 0 2.434.81 2.434 2.212 0 1.017-.51 1.742-1.412 2.04l1.951 5.566h-1.68l-1.74-5.13h-.644v5.13h-1.32z"/></svg>
        Яндекс
      </a>

      {{-- Google --}}
      <a href="/auth/google" class="btn btn-surface btn-left">
        <svg width="22" height="22" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
        Google
      </a>

      {{-- Telegram --}}
      @if(config('services.telegram.bot_username'))
      <div x-data="pwaTelegramAuth('{{ $context }}')" x-init="init()" style="display:flex;flex-direction:column;gap:6px;">
        <button @click="start()" :disabled="loading || waiting"
          class="btn btn-surface btn-left"
          :style="(loading || waiting) ? 'opacity:0.6' : 'opacity:1'">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="#26a5e4"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
          <span x-show="!loading && !waiting">Telegram</span>
          <span x-show="loading" style="display:none">Подготовка...</span>
          <span x-show="waiting" style="display:none">Ожидание в боте...</span>
        </button>
        <p x-show="waiting" x-cloak style="font-size:11px;color:var(--muted);text-align:center;margin:0;">
          Откройте Telegram и нажмите «Start» в боте
        </p>
        <p x-show="error" x-text="error" x-cloak style="font-size:11px;color:var(--red);text-align:center;margin:0;"></p>
      </div>
      @endif

    </div>
  </div>

  {{-- Android install button (hidden by default, shown by JS) --}}
  <button id="pwa-install-btn" onclick="installPwa()"
    class="btn btn-surface hidden anim-up"
    style="border-color:var(--accent-bd);color:var(--accent);animation-delay:0.15s;">
    📲 Установить приложение
  </button>
</div>

<script>
function pwaTelegramAuth(context) {
  return {
    loading: false,
    waiting: false,
    error: '',
    pollInterval: null,

    init() {
      // Restore pending token after mobile navigation (iOS kills poller when deep_link opens)
      try {
        const pendingToken = sessionStorage.getItem('tg_pending_token');
        if (pendingToken) {
          this.waiting = true;
          this.poll(pendingToken);
        }
      } catch (_) {}

      // Resume poller when user returns to tab after opening Telegram
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && this.waiting && !this.pollInterval) {
          try {
            const token = sessionStorage.getItem('tg_pending_token');
            if (token) this.poll(token);
          } catch (_) {}
        }
      });
    },

    async start() {
      this.loading = true;
      this.error = '';
      this.waiting = false;

      try {
        const res = await fetch('/api/telegram/generate-token', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({ pwa_redirect: context === 'teacher' ? 'teacher' : 'student' }),
        });

        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Ошибка');

        this.loading = false;
        this.waiting = true;

        // Store token so poller can be restored if page is reloaded on mobile
        try { sessionStorage.setItem('tg_pending_token', data.token); } catch (_) {}

        // Always open in new tab/app so this page stays alive with the poller
        window.open(data.deep_link, '_blank');

        this.poll(data.token);
      } catch (e) {
        this.loading = false;
        this.error = e.message;
      }
    },

    poll(token) {
      if (this.pollInterval) clearInterval(this.pollInterval);
      let attempts = 0;
      this.pollInterval = setInterval(async () => {
        if (++attempts > 150) {
          clearInterval(this.pollInterval);
          this.pollInterval = null;
          this.waiting = false;
          try { sessionStorage.removeItem('tg_pending_token'); } catch (_) {}
          this.error = 'Время ожидания истекло. Попробуйте снова.';
          return;
        }
        try {
          const res = await fetch('/api/telegram/check-token/' + token);
          const data = await res.json();
          if (data.status === 'authenticated') {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
            try { sessionStorage.removeItem('tg_pending_token'); } catch (_) {}
            window.location.href = data.login_url;
          } else if (data.status === 'expired' || data.status === 'not_found') {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
            try { sessionStorage.removeItem('tg_pending_token'); } catch (_) {}
            this.waiting = false;
            this.error = 'Сессия истекла. Попробуйте снова.';
          }
        } catch {}
      }, 2000);
    },
  };
}
</script>
@endsection
