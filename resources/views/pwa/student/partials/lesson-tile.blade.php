{{-- Lesson tile: всегда видна. Если есть live-сессия ученика — зелёная «УРОК идёт»,
     иначе — вход по 4-значному коду (модал). Polling /lessons/active каждые 30 сек. --}}
<div x-data="lessonTile()" x-init="init()">
  {{-- Идёт урок — открыть --}}
  <a x-show="session" x-cloak :href="`/lessons/${session?.id}`"
     style="display:flex;flex-direction:column;align-items:center;gap:6px;
            padding:20px;border-radius:16px;text-decoration:none;font-family:var(--display);
            color:white;background:var(--green);border:1px solid var(--green);
            box-shadow: 0 4px 12px rgba(52,208,126,0.3);
            position:relative;overflow:hidden;">
    <div style="font-size:11px;letter-spacing:0.1em;opacity:0.85;text-transform:uppercase;">Сейчас идёт</div>
    <div style="font-size:28px;font-weight:800;letter-spacing:0.04em;">УРОК</div>
    <div style="font-size:12px;opacity:0.9;">нажми, чтобы открыть →</div>
    <span style="position:absolute;top:10px;right:12px;width:8px;height:8px;background:white;border-radius:999px;animation: pulse 1.4s ease-in-out infinite;"></span>
  </a>

  {{-- Урока нет — вход по коду --}}
  <button x-show="!session" type="button" @click="codeOpen = true; joinError = ''; code = '';"
     style="display:flex;flex-direction:column;align-items:center;gap:6px;width:100%;cursor:pointer;
            padding:20px;border-radius:16px;font-family:var(--display);
            color:var(--text);background:var(--surface);border:1px dashed var(--border);">
    <div style="font-size:11px;letter-spacing:0.1em;color:var(--muted);text-transform:uppercase;">Учитель дал код?</div>
    <div style="font-size:28px;font-weight:800;letter-spacing:0.04em;">УРОК</div>
    <div style="font-size:12px;color:var(--muted);">войти по коду →</div>
  </button>

  {{-- Модал ввода кода --}}
  <template x-if="codeOpen">
    <div class="lt-overlay" @click.self="codeOpen = false">
      <div class="lt-sheet">
        <div class="lt-handle"></div>
        <div class="lt-title">Код урока</div>
        <div class="lt-desc">Введи 4 цифры, которые продиктовал учитель</div>
        <input class="lt-input" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4"
               x-model="code" @input="code = code.replace(/\D/g,''); joinError = '';"
               @keyup.enter="joinByCode()" placeholder="••••" autofocus>
        <div class="lt-error" x-show="joinError" x-text="joinError"></div>
        <button class="lt-btn" @click="joinByCode()" :disabled="code.length !== 4 || joining"
                x-text="joining ? 'Входим…' : 'Войти в урок'"></button>
        <button class="lt-cancel" type="button" @click="codeOpen = false">Отмена</button>
      </div>
    </div>
  </template>
</div>

@once
<style>
  @keyframes pulse {
    0%,100% { opacity: 0.4; transform: scale(1); }
    50%     { opacity: 1;   transform: scale(1.4); }
  }
  .lt-overlay { position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,.55); backdrop-filter: blur(4px); display: flex; align-items: flex-end; justify-content: center; }
  .lt-sheet { background: var(--bg); border-radius: 20px 20px 0 0; width: 100%; max-width: 420px; padding: 24px 20px 32px; }
  .lt-handle { width: 36px; height: 4px; background: var(--border); border-radius: 2px; margin: 0 auto 16px; }
  .lt-title { font-family: var(--display); font-size: 20px; color: var(--text); text-align: center; margin-bottom: 8px; }
  .lt-desc { font-size: 13px; color: var(--muted); text-align: center; line-height: 1.5; margin-bottom: 16px; }
  .lt-input { display: block; width: 160px; margin: 0 auto 12px; padding: 12px; text-align: center;
              font-family: ui-monospace, monospace; font-size: 32px; letter-spacing: 10px;
              background: var(--surface); color: var(--text); border: 1px solid var(--border); border-radius: 12px; }
  .lt-input:focus { outline: none; border-color: var(--purple-bd); }
  .lt-error { color: var(--red); font-size: 12px; text-align: center; margin-bottom: 10px; }
  .lt-btn { display: block; width: 100%; padding: 16px; border: none; border-radius: 14px;
            font-family: var(--display); font-size: 15px; cursor: pointer; text-align: center;
            margin-bottom: 10px; background: var(--purple); color: #fff; }
  .lt-btn:disabled { opacity: .5; cursor: default; }
  .lt-cancel { display: block; width: 100%; padding: 14px; background: none; border: none; color: var(--muted); font-size: 14px; font-weight: 700; cursor: pointer; }
</style>
<script>
  function lessonTile() {
    return {
      session: null,
      pollTimer: null,
      codeOpen: false,
      code: '',
      joinError: '',
      joining: false,
      async init() {
        await this.fetchActive();
        this.pollTimer = setInterval(() => {
          if (!document.hidden) this.fetchActive();
        }, 30000);
      },
      async fetchActive() {
        try {
          const r = await fetch('/lessons/active', { headers: { 'Accept': 'application/json' }, credentials: 'include' });
          if (!r.ok) return;
          const d = await r.json();
          this.session = d.session;
        } catch (e) { /* silent */ }
      },
      async joinByCode() {
        if (this.code.length !== 4 || this.joining) return;
        this.joining = true;
        this.joinError = '';
        try {
          const r = await fetch('/lessons/join', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            credentials: 'include',
            body: JSON.stringify({ code: this.code }),
          });
          const d = await r.json();
          if (r.ok && d.lesson_id) {
            window.location.href = `/lessons/${d.lesson_id}`;
            return;
          }
          this.joinError = d.error || 'Не получилось войти';
        } catch (e) {
          this.joinError = 'Ошибка соединения';
        } finally {
          this.joining = false;
        }
      },
    };
  }
</script>
@endonce
