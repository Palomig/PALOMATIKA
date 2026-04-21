<div x-data="bugReport()" x-cloak>

  <button type="button"
    class="bug-report-trigger"
    :class="{ 'is-hidden': open }"
    x-show="!open"
    @click="open = true"
    aria-label="Сообщить об ошибке"
    data-bug-report-trigger>
    <svg class="bug-report-trigger__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M12 8v4m0 4h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/>
    </svg>
    <span class="bug-report-trigger__label">Баг-репорт</span>
  </button>

  <div x-show="open" @click.self="close()" x-transition:enter="anim-in"
    class="bug-report-backdrop"
    data-bug-report-modal>

    <div x-show="open" x-transition:enter="anim-up"
      class="bug-report-card">

      <div class="bug-report-head">
        <div class="bug-report-lead">
          <div class="bug-report-lead__badge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M12 8v4m0 4h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/>
            </svg>
          </div>
          <div>
            <div class="bug-report-lead__title">Сообщить об ошибке</div>
            <div class="bug-report-lead__text">Опишите, что произошло — мы приложим данные о странице, браузере и устройстве.</div>
          </div>
        </div>
        <button type="button"
          class="bug-report-close"
          @click="close()"
          aria-label="Закрыть"
          data-bug-report-close>&times;</button>
      </div>

      <template x-if="sent">
        <div class="bug-report-success">
          Спасибо! Репорт отправлен.
        </div>
      </template>

      <template x-if="!sent">
        <form class="bug-report-form" @submit.prevent="submit()">
          <textarea x-model="description"
            class="bug-report-textarea"
            placeholder="Коротко: что сломано, где именно и что ожидалось?"
            rows="5"></textarea>

          <div x-show="error" x-text="error" class="bug-report-error"></div>

          <button type="submit"
            :disabled="loading"
            class="bug-report-submit"
            :class="{ 'is-loading': loading }"
            data-bug-report-submit>
            <span x-show="!loading">Отправить репорт</span>
            <span x-show="loading">Отправка…</span>
          </button>
        </form>
      </template>

    </div>
  </div>
</div>

<script>
function bugReport() {
  const jsErrors = [];
  const _onerror = window.onerror;
  window.onerror = function(msg, src, line, col, err) {
    if (jsErrors.length < 10) jsErrors.push({ message: String(msg), source: String(src || '') + ':' + String(line || '') });
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

    init() {
      this.$watch('open', (isOpen) => {
        document.body.style.overflow = isOpen ? 'hidden' : '';
      });
    },

    close() {
      this.open = false;
      this.error = '';
    },

    // Each field is wrapped in safe() because a single throwing property
    // (window.matchMedia in WebView, navigator.* in sandboxed iframes)
    // used to bubble up and freeze submit with loading=true forever.
    collectScreenInfo() {
      const safe = (fn, fallback) => { try { return fn(); } catch (_) { return fallback; } };
      const conn = safe(() => navigator.connection || navigator.mozConnection || navigator.webkitConnection, null);
      return {
        screen_w:   safe(() => screen.width, 0),
        screen_h:   safe(() => screen.height, 0),
        window_w:   safe(() => window.innerWidth, 0),
        window_h:   safe(() => window.innerHeight, 0),
        dpr:        safe(() => window.devicePixelRatio, 1) || 1,
        pwa:        safe(() => window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true, false),
        online:     safe(() => navigator.onLine, true),
        connection: conn ? (conn.effectiveType || conn.type || '?') : 'unknown',
        language:   safe(() => navigator.language, 'unknown'),
        timezone:   safe(() => Intl.DateTimeFormat().resolvedOptions().timeZone, 'unknown'),
      };
    },

    async submit() {
      if (this.loading) return;
      this.loading = true;
      this.error   = '';

      try {
        if (typeof window.fetchPost !== 'function') {
          throw new Error('fetchPost is not available');
        }

        const pageContext = (typeof window.__bugContext === 'function') ? window.__bugContext() : null;
        const url = String(window.location.href || '').slice(0, 500);
        const routeName = document.querySelector('meta[name="route-name"]')?.content || null;

        const res = await window.fetchPost('/bug-report', {
          url,
          route_name:   routeName ? String(routeName).slice(0, 100) : null,
          description:  this.description.trim() || null,
          user_agent:   String(navigator.userAgent || '').slice(0, 500),
          screen_info:  this.collectScreenInfo(),
          js_errors:    jsErrors.length ? jsErrors : null,
          page_context: pageContext,
        });

        if (!res.ok) throw new Error('Ошибка сервера (' + res.status + ')');
        this.sent = true;
        setTimeout(() => {
          this.close();
          this.sent = false;
          this.description = '';
        }, 2500);
      } catch (e) {
        console.warn('bug-report submit failed', e);
        this.error = 'Не удалось отправить. Попробуйте ещё раз.';
      } finally {
        this.loading = false;
      }
    },
  };
}
</script>
