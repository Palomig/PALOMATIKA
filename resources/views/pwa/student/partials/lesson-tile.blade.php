{{-- Lesson tile: показывается ТОЛЬКО когда у ученика идёт live-сессия.
     Использует /lessons/active endpoint, polling каждые 30 секунд.
     Управляется отдельным Alpine-компонентом, не зависит от родительского. --}}
<div x-data="lessonTile()" x-init="init()" x-show="session" x-cloak>
  <a :href="`/lessons/${session?.id}`"
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
</div>

@once
<style>
  @keyframes pulse {
    0%,100% { opacity: 0.4; transform: scale(1); }
    50%     { opacity: 1;   transform: scale(1.4); }
  }
</style>
<script>
  function lessonTile() {
    return {
      session: null,
      pollTimer: null,
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
    };
  }
</script>
@endonce
