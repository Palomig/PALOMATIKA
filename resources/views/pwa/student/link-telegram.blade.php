@extends('layouts.pwa')

@section('title', 'Подключить уведомления')

@section('body')
<div class="page" style="justify-content:center;min-height:100vh;" x-data="telegramLink()">
  <div class="anim-up" style="text-align:center;margin-bottom:8px;">
    <div style="font-family:var(--display);font-size:28px;color:var(--accent);">palomatika</div>
  </div>

  <div class="card anim-up" style="animation-delay:0.05s;">
    <div class="sec-label" style="margin-bottom:12px;">Последний шаг</div>

    <p style="font-size:14px;line-height:1.5;margin:0 0 8px;">
      Подключи Telegram — туда придёт уведомление, когда учитель задаст домашку,
      и напоминание, если подходит срок.
    </p>
    <p style="font-size:12px;color:var(--muted);margin:0 0 16px;">
      Нажми кнопку, в открывшемся чате с ботом нажми «Start» и возвращайся — страница обновится сама.
    </p>

    <a :href="deepLink || '#'" @click="onOpen($event)" target="_blank" rel="noopener"
      class="btn btn-left"
      style="background:#229ED9;border-color:#229ED9;color:#fff;font-weight:600;">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248-2.04 9.613c-.154.683-.554.85-1.123.528l-3.1-2.285-1.496 1.44c-.165.165-.305.305-.625.305l.223-3.168 5.77-5.213c.25-.222-.055-.346-.39-.124L7.19 14.447l-3.04-.952c-.662-.207-.674-.662.138-.979l11.87-4.576c.552-.2 1.035.134.404.308z"/></svg>
      <span x-text="loading ? 'Готовим ссылку…' : 'Подключить уведомления'"></span>
    </a>

    <p x-show="waiting" x-cloak style="font-size:12px;color:var(--muted);text-align:center;margin:12px 0 0;">
      Ждём «Start» в боте @{{ $botUsername }}…
    </p>
    <p x-show="error" x-text="error" x-cloak style="font-size:12px;color:var(--red);text-align:center;margin:12px 0 0;"></p>
  </div>

  <form method="POST" action="/logout" style="margin-top:16px;text-align:center;">
    @csrf
    <button type="submit" class="btn btn-surface" style="font-size:12px;">Выйти</button>
  </form>
</div>

<script>
function telegramLink() {
  return {
    code: '',
    deepLink: '',
    loading: false,
    waiting: false,
    error: '',
    poller: null,

    async onOpen(event) {
      if (this.deepLink) {
        this.startPolling();
        return;
      }

      // Ссылку выдаём по клику: код живёт 15 минут, незачем жечь его на показ страницы.
      event.preventDefault();
      this.loading = true;
      this.error = '';

      try {
        const response = await fetch('/link-telegram/start', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.deep_link) {
          throw new Error('Не удалось подготовить ссылку');
        }

        this.code = data.code;
        this.deepLink = data.deep_link;
        this.startPolling();
        window.open(data.deep_link, '_blank', 'noopener');
      } catch (e) {
        this.error = e.message || 'Что-то пошло не так, попробуй ещё раз';
      } finally {
        this.loading = false;
      }
    },

    startPolling() {
      if (this.poller) return;
      this.waiting = true;

      this.poller = setInterval(async () => {
        try {
          const response = await fetch('/link-telegram/status?code=' + encodeURIComponent(this.code), {
            headers: { 'Accept': 'application/json' },
          });
          const data = await response.json().catch(() => ({}));
          if (data.linked) {
            clearInterval(this.poller);
            this.poller = null;
            window.location.href = '/';
          }
        } catch (e) {
          // Сеть моргнула — просто ждём следующего тика.
        }
      }, 2000);

      // Через 10 минут перестаём долбить сервер: код всё равно протухнет.
      setTimeout(() => {
        if (this.poller) {
          clearInterval(this.poller);
          this.poller = null;
          this.waiting = false;
        }
      }, 600000);
    },
  };
}
</script>
@endsection
