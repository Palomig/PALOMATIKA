{{-- Кнопка установки PWA. Подключение: @include('pwa.shared.install-app')
     в любом месте страницы на layouts.pwa — компонент самодостаточный.

     Один клик работает там, где браузер дал beforeinstallprompt (Chrome,
     Android, десктоп). Где не дал (iOS, Firefox, вебвью Телеграма) —
     показываем инструкцию под конкретную платформу: другого способа нет,
     установку нельзя вызвать из кода. --}}
@push('styles')
  /* Анимация живёт на самой карточке, а не на обёртке: transform у предка
     сделал бы его containing block для position:fixed и прибил шторку. */
  .install-app { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 16px; display: flex; flex-direction: column; gap: 14px; opacity: 0; animation: fadeUp 0.3s ease 0.13s forwards; }
  .install-app__head { display: flex; align-items: flex-start; gap: 12px; }
  .install-app__icon { width: 42px; height: 42px; flex-shrink: 0; border-radius: 12px; background: var(--accent-bg); border: 1px solid var(--accent-bd); display: flex; align-items: center; justify-content: center; font-size: 20px; }
  .install-app__title { font-family: var(--display); font-size: 15px; color: var(--text); }
  .install-app__sub { font-size: 12px; color: var(--muted); line-height: 1.5; margin-top: 4px; }
  .install-app__btn { width: 100%; padding: 14px; font-size: 14px; }
  .install-app__done { display: flex; align-items: center; justify-content: center; gap: 8px; background: var(--green-bg); border: 1px solid var(--green-bd); color: var(--green); border-radius: 12px; padding: 12px; font-size: 13px; font-weight: 700; }

  .install-sheet-overlay { position: fixed; inset: 0; z-index: 300; background: rgba(0,0,0,.55); backdrop-filter: blur(4px); display: flex; align-items: flex-end; justify-content: center; }
  .install-sheet { background: var(--bg); border-radius: 20px 20px 0 0; width: 100%; max-width: 480px; padding: 16px 20px calc(24px + var(--safe-bottom)); }
  .install-sheet__handle { width: 36px; height: 4px; background: var(--border); border-radius: 2px; margin: 0 auto 16px; }
  .install-sheet__title { font-family: var(--display); font-size: 17px; color: var(--text); text-align: center; margin-bottom: 16px; }
  .install-sheet__steps { display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px; }
  .install-sheet__step { display: flex; align-items: flex-start; gap: 12px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px 14px; }
  .install-sheet__num { width: 22px; height: 22px; flex-shrink: 0; border-radius: 50%; background: var(--accent); color: #fff; font-size: 12px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
  .install-sheet__text { font-size: 13px; color: var(--text); line-height: 1.5; }
  .install-sheet__cancel { display: block; width: 100%; padding: 14px; background: none; border: none; color: var(--muted); font-size: 14px; font-weight: 700; cursor: pointer; }
@endpush

<div x-data="installApp()" x-init="init()">
  <div class="install-app">
    <div class="install-app__head">
      <div class="install-app__icon">📲</div>
      <div>
        <div class="install-app__title">Приложение на телефон</div>
        <div class="install-app__sub">Иконка на экране, запуск без браузера и адресной строки — как обычное приложение.</div>
      </div>
    </div>

    <template x-if="installed">
      <div class="install-app__done">✓ Приложение установлено</div>
    </template>

    <template x-if="!installed">
      <button type="button" class="btn btn-accent install-app__btn" @click="install()">
        Скачать приложение
      </button>
    </template>
  </div>

  {{-- Инструкция для браузеров без установки в один клик --}}
  <template x-if="sheetOpen">
    <div class="install-sheet-overlay" @click.self="sheetOpen = false">
      <div class="install-sheet">
        <div class="install-sheet__handle"></div>
        <div class="install-sheet__title" x-text="guide.title"></div>

        <div class="install-sheet__steps">
          <template x-for="(step, i) in guide.steps" :key="i">
            <div class="install-sheet__step">
              <div class="install-sheet__num" x-text="i + 1"></div>
              <div class="install-sheet__text" x-html="step"></div>
            </div>
          </template>
        </div>

        <template x-if="guide.openInBrowser">
          <button type="button" class="btn btn-accent install-app__btn" @click="openInBrowser()">
            Открыть в браузере
          </button>
        </template>

        <button type="button" class="install-sheet__cancel" @click="sheetOpen = false">Закрыть</button>
      </div>
    </div>
  </template>
</div>

<script>
function installApp() {
  const ua = navigator.userAgent;
  // iPadOS 13+ представляется Macintosh — ловим по тач-поинтам.
  const isIos = /iphone|ipad|ipod/i.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
  const isTelegram = !!window.Telegram?.WebApp?.initData || /Telegram/i.test(ua);
  const isAndroid = /android/i.test(ua);

  return {
    installed: false,
    sheetOpen: false,
    guide: { title: '', steps: [], openInBrowser: false },

    init() {
      this.installed = this.isStandalone();
      window.addEventListener('pwa-installed', () => { this.installed = true; this.sheetOpen = false; });
    },

    // Установленное приложение открывается в своём окне, без браузерного UI.
    isStandalone() {
      return window.matchMedia('(display-mode: standalone)').matches
        || window.matchMedia('(display-mode: fullscreen)').matches
        || window.navigator.standalone === true;
    },

    async install() {
      const outcome = await window.installPwa();
      if (outcome === 'accepted') {
        this.installed = true;
        return;
      }
      // 'dismissed' — человек сам отказался в системном окне, не мешаем.
      if (outcome === null) {
        this.guide = this.guideForPlatform();
        this.sheetOpen = true;
      }
    },

    guideForPlatform() {
      if (isTelegram) {
        return {
          title: 'Открой сайт в браузере',
          steps: [
            'Телеграм не умеет ставить приложения — нужен браузер.',
            'Нажми кнопку ниже: сайт откроется в Chrome или Safari.',
            'Там снова зайди в профиль и нажми «Скачать приложение».',
          ],
          openInBrowser: true,
        };
      }

      if (isIos) {
        return {
          title: 'Установка на iPhone',
          steps: [
            'Нажми кнопку «Поделиться» <strong>⬆️</strong> внизу экрана Safari.',
            'Пролистай список и выбери <strong>«На экран “Домой”»</strong>.',
            'Нажми <strong>«Добавить»</strong> — иконка появится на экране.',
          ],
          openInBrowser: false,
        };
      }

      if (isAndroid) {
        return {
          title: 'Установка на Android',
          steps: [
            'Открой меню браузера <strong>⋮</strong> справа сверху.',
            'Выбери <strong>«Установить приложение»</strong> или <strong>«Добавить на главный экран»</strong>.',
            'Подтверди — иконка появится на экране.',
          ],
          openInBrowser: false,
        };
      }

      return {
        title: 'Установка на компьютер',
        steps: [
          'Нажми значок установки <strong>⊕</strong> справа в адресной строке.',
          'Если значка нет — меню браузера <strong>⋮</strong> → <strong>«Установить Palomatika»</strong>.',
          'В Safari: <strong>«Файл»</strong> → <strong>«Добавить в Dock»</strong>.',
        ],
        openInBrowser: false,
      };
    },

    openInBrowser() {
      const url = window.location.origin + window.location.pathname;
      const tg = window.Telegram?.WebApp;
      if (tg?.openLink) tg.openLink(url);
      else window.open(url, '_blank');
    },
  };
}
</script>
