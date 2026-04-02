{{-- resources/views/pwa/shared/ios-install-prompt.blade.php --}}
<div
  id="ios-install-prompt"
  x-data="iosInstallPrompt()"
  x-show="show"
  x-cloak
  style="position:fixed;bottom:0;left:0;right:0;z-index:9999;padding:16px;padding-bottom:calc(16px + env(safe-area-inset-bottom));">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:20px;max-width:480px;margin:0 auto;box-shadow:0 -4px 32px rgba(0,0,0,0.3);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
      <span style="font-family:var(--display);font-size:14px;">Установить приложение</span>
      <button @click="dismiss()" style="background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer;padding:0;line-height:1;">✕</button>
    </div>
    <p style="font-size:12px;color:var(--muted);line-height:1.6;margin-bottom:16px;">
      Нажмите
      <svg style="display:inline;width:16px;height:16px;vertical-align:middle;" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l-4 4h3v8h2V6h3L12 2zM5 18v2h14v-2H5z"/></svg>
      → <strong style="color:var(--text);">«На экран Домой»</strong> чтобы установить Palomatika как приложение
    </p>
    <div style="display:flex;align-items:center;gap:8px;background:var(--surface2);border-radius:12px;padding:12px;">
      <span style="font-size:24px;">⬆️</span>
      <span style="font-size:11px;color:var(--muted);">Кнопка «Поделиться» → Прокрутите вниз → «На экран Домой»</span>
    </div>
    <div style="text-align:center;margin-top:8px;color:var(--accent);font-size:11px;font-weight:700;">↓ Кнопка внизу экрана</div>
  </div>
</div>

<script>
function iosInstallPrompt() {
  const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
  const isInApp = window.navigator.standalone;
  const dismissed = localStorage.getItem('ios_install_dismissed');
  return {
    show: isIos && !isInApp && !dismissed,
    dismiss() {
      this.show = false;
      localStorage.setItem('ios_install_dismissed', '1');
    }
  };
}
</script>
