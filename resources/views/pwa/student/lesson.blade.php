@extends('layouts.pwa')
@section('title', 'Урок — palomatika')

@push('katex')
{{-- SDK Телеграма нужен ТОЛЬКО внутри мини-аппа: там он даёт события
     activated/deactivated, без которых свёрнутый вебвью выглядит как «на уроке».
     В обычном браузере это мёртвый груз, поэтому грузим по признакам вебвью
     (сервер их не видит: Телеграм передаёт свои параметры во фрагменте URL, да и
     тот теряется при переходах внутри PWA). Глобалы вебвью живут на каждой
     странице, так что проверка работает и после навигации.
     document.write — намеренно: скрипт обязан выполниться до старта Alpine.
     Версия KaTeX 0.16.9, как на остальных страницах: иначе у урока свой кэш. --}}
<script>
  if (window.TelegramWebviewProxy || window.TelegramWebviewProxyProto
      || /Telegram/i.test(navigator.userAgent) || /tgWebApp/.test(location.hash)
      || window.parent !== window) {
    document.write('<scr' + 'ipt src="/js/telegram-web-app.js"></scr' + 'ipt>');
  }
</script>
<link rel="preload" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/fonts/KaTeX_Main-Regular.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/fonts/KaTeX_Math-Italic.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
        onload="renderMathInElement(document.body,{delimiters:[{left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false}],throwOnError:false}); window.lessonFitFormulas && window.lessonFitFormulas()"></script>
@endpush

@push('styles')
  /* Растры банка ЕГЭ: чертёж отдельным блоком, обозначения внутри
     предложения («SABCD», «AM = 2») — строкой. Оба чёрным по прозрачному,
     поэтому на тёмном фоне нужна подложка; display обязателен, иначе
     Tailwind-сброс делает их блочными и рвёт предложение. */
  .lesson-task-expr img.fipi-inline,
  .lesson-task-expr img.fipi-figure { background: #fff; border-radius: 4px; }
  .lesson-task-expr img.fipi-inline {
    display: inline-block; padding: 0 2px; height: 1.3em; width: auto; vertical-align: -0.26em;
  }
  .lesson-task-expr img.fipi-figure { display: block; max-width: 100%; padding: 6px; margin: 8px 0; }
  .lesson-task-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
  .lesson-task-card.is-answered { border-color: var(--accent); background: var(--accent-bg); }
  .lesson-task-num { font-family: var(--display); font-size: 18px; color: var(--accent); }
  .lesson-task-expr { font-size: 18px; color: var(--text); word-break: break-word; min-height: 24px; }
  /* Формула — неделимая коробка: у задач ЕГЭ строка рвалась посреди неё
     («Решите неравенство log₁₆(x +» / «5) + …»). Ученик на уроке видит
     те же карточки, что учитель на подготовке, — правило одно и то же. */
  .lesson-task-expr .katex {
    font-size: 1.08em;
    display: inline-block;
    max-width: 100%;
    /* Одной строкой: ширину под карточку подбирает кегль (fitFormulas). */
    white-space: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    vertical-align: middle;
    /* Полоса прокрутки скрыта: KaTeX вылезает за коробку на доли пикселя,
       и под короткими формулами рисовалась полоса со стрелками. */
    scrollbar-width: none;
    -ms-overflow-style: none;
  }
  .lesson-task-expr .katex::-webkit-scrollbar { width: 0; height: 0; }
  /* Подпункты «а) б) в)» второй части — каждый со своей строки, маркер
     выступает влево. Формула плюс прилипшая к ней точка — одним куском. */
  .cond-lead, .cond-sub { display: block; }
  .cond-sub { padding-left: 16px; text-indent: -16px; margin-top: 4px; }
  .lesson-task-expr .nb { white-space: nowrap; }
  .lesson-task-image { width: 100%; display: flex; justify-content: center; background: var(--surface2); border-radius: 10px; padding: 12px; overflow: hidden; }
  /* Растр ФИПИ чёрным по прозрачному — на тёмной подложке не читается. */
  .lesson-task-image.is-raster { background: #fff; }
  .lesson-task-image svg, .lesson-task-image img { max-width: 100%; height: auto; max-height: 320px; }
  .lesson-answer-row { display: flex; gap: 8px; align-items: center; }
  .lesson-answer-input { flex: 1; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 10px; padding: 12px 14px; font-size: 16px; font-family: ui-monospace, monospace; }
  .lesson-answer-input:focus { outline: 2px solid var(--accent); border-color: var(--accent); }
  .lesson-submit-btn { background: var(--accent); color: white; border: none; border-radius: 10px; padding: 12px 18px; font-weight: 800; cursor: pointer; font-size: 14px; }
  .lesson-submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }
  .lesson-status-line { font-size: 12px; color: var(--muted); }
  .lesson-status-line.is-sent { color: var(--accent); font-weight: 700; }
  .lesson-choice-options { display: grid; gap: 8px; }
  .lesson-choice-option { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; cursor: pointer; }
  .lesson-choice-option.is-selected { background: var(--accent-bg); border-color: var(--accent); }
  .lesson-choice-option input[type="radio"] { accent-color: var(--accent); }
  .lesson-end-banner { background: var(--red-bg); border: 1px solid var(--red-bd); border-radius: 14px; padding: 16px; color: var(--red); font-weight: 700; text-align: center; }
  .lesson-released-banner { background: var(--green-bg); border: 1px solid var(--green-bd); border-radius: 14px; padding: 16px; color: var(--green); font-weight: 700; text-align: center; }
  .lock-timer { margin-left: auto; font-family: ui-monospace, monospace; font-size: 13px; font-weight: 700; color: var(--muted); }
  .resume-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.72); display: flex; align-items: center; justify-content: center; padding: 24px; }
  .resume-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px; max-width: 320px; width: 100%; text-align: center; display: flex; flex-direction: column; gap: 14px; }
  .resume-title { font-family: var(--display); font-size: 18px; color: var(--text); }
  .resume-sub { font-size: 13px; color: var(--muted); }
  .resume-btn { background: var(--accent); color: white; border: none; border-radius: 10px; padding: 14px 18px; font-weight: 800; font-size: 15px; cursor: pointer; }
  .personal-badge { font-size: 10px; font-weight: 800; padding: 1px 8px; border-radius: 6px; background: var(--accent-bg); color: var(--accent); border: 1px solid var(--accent-bd); white-space: nowrap; }

  /* Разбор домашки: только чтение, поля ответа нет — это не задача урока */
  .review-block { border: 1px solid var(--purple-bd); background: var(--purple-bg); border-radius: var(--r); padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; }
  .review-block-head { font-family: var(--display); font-size: 15px; color: var(--text); }
  .review-card-s { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px 13px; }
  .review-card-num { font-size: 11px; font-weight: 800; color: var(--purple); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
  .review-visual-s { margin: 6px 0; display: flex; justify-content: center; }
  .review-visual-s :is(svg, img) { max-width: 100%; height: auto; }
  .review-text-s { font-size: 14px; line-height: 1.45; color: var(--text); word-break: break-word; }
  .review-answers-s { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
  .review-chip-s { font-size: 12px; font-weight: 700; padding: 4px 9px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); }
  .review-chip-s.is-mine { color: var(--yellow); border-color: var(--yellow-bd); background: var(--yellow-bg); }
  .review-chip-label-s { color: var(--muted); font-weight: 600; }
  .review-photos-s { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
  .review-photo-s { padding: 0; border: none; background: none; cursor: zoom-in; width: 92px; }
  .review-photo-s img { width: 100%; aspect-ratio: 3 / 4; object-fit: cover; border-radius: 8px; border: 1px solid var(--border); display: block; }
  .review-photo-s:active img { opacity: .8; }
@endpush

@section('body')
<div class="page" x-data="studentLesson({{ $session->id }}, '{{ $session->status }}')" x-init="init()"
     @keydown.escape.window="viewer && close()"
     @keydown.arrow-left.window="viewer && step(-1)"
     @keydown.arrow-right.window="viewer && step(1)">
  <div class="topbar">
    <a href="{{ route('pwa.student.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Урок</div>
    <span class="lock-timer" x-show="lockActive" x-cloak>🔒 <span x-text="lockLeft"></span></span>
  </div>

  <template x-if="status === 'ended'">
    <div class="lesson-end-banner">Урок завершён. Ответы больше не принимаются.</div>
  </template>

  {{-- Пауза после отлучки: страница закрыта оверлеем, пока ученик не нажмёт «Продолжить» --}}
  <div class="resume-overlay" x-show="resumeVisible" x-cloak>
    <div class="resume-card">
      <div class="resume-title">Ты отходил 👀</div>
      <div class="resume-sub" x-text="'Тебя не было ' + resumeAwayLabel + '. Вернись к задачам!'"></div>
      <button type="button" class="resume-btn" @click="confirmResume()">Продолжить урок</button>
    </div>
  </div>

  <template x-if="released">
    <div class="lesson-released-banner">Учитель отпустил тебя — можно выходить 👋</div>
  </template>

  {{--
    Разбор домашки: задачи, которые учитель отметил при проверке и взял на урок.
    Решать их заново не нужно — поля ответа здесь нет, это «смотрим вместе на то,
    что ты уже написал». Заметку учителя ученику не показываем.
  --}}
  <template x-if="review.length">
    <div class="review-block">
      <div class="review-block-head">🔍 Разбор с учителем</div>
      <template x-for="card in review" :key="'rev-' + card.id">
        <div class="review-card-s">
          <div class="review-card-num" x-text="'Задача ' + card.task_order"></div>
          <div class="review-visual-s" x-show="card.svg" x-html="card.svg"></div>
          <div class="review-text-s" x-html="card.text"></div>

          <div class="review-answers-s">
            <span class="review-chip-s"><span class="review-chip-label-s">верный ответ:</span> <span x-text="card.correct"></span></span>
            <template x-if="card.first_answer !== null">
              <span class="review-chip-s is-mine"><span class="review-chip-label-s">твой:</span> <span x-text="card.first_answer"></span></span>
            </template>
          </div>

          <div class="review-photos-s" x-show="card.photos.length">
            <template x-for="(p, pi) in card.photos" :key="'revp-' + card.id + '-' + pi">
              <button type="button" class="review-photo-s" @click="openReviewPhotos(card, pi)">
                <img :src="p.url" :alt="p.label" loading="lazy">
              </button>
            </template>
          </div>
        </div>
      </template>
    </div>
  </template>

  @include('pwa._shared.photo-viewer')

  <template x-for="task in tasks" :key="task.id">
    <div class="lesson-task-card" :class="task.my_answer ? 'is-answered' : ''" :data-task-id="task.id">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div class="lesson-task-num" x-text="task.position + ')'"></div>
        <div style="display:flex;align-items:center;gap:8px;">
          <span class="personal-badge" x-show="task.personal" x-cloak>персональная</span>
          <div class="lesson-status-line" :class="task.my_answer ? 'is-sent' : ''"
               x-text="task.my_answer ? '✓ отправлено' : 'жду ответ'"></div>
        </div>
      </div>

      <div class="lesson-task-image" x-show="task.payload.image_svg" x-html="task.payload.image_svg"></div>
      <template x-if="!task.payload.image_svg && task.payload.image_url">
        <div class="lesson-task-image is-raster"><img :src="task.payload.image_url" alt=""></div>
      </template>
      <div class="lesson-task-expr" x-html="renderMath(task.payload.expression)"></div>

      {{-- Choice type --}}
      <template x-if="task.payload.type === 'choice'">
        <div class="lesson-choice-options">
          <template x-for="opt in task.payload.options" :key="opt.id">
            <label class="lesson-choice-option" :class="task.my_answer === opt.id ? 'is-selected' : ''">
              <input type="radio" :name="'task_' + task.id" :value="opt.id"
                     :checked="task.my_answer === opt.id"
                     :disabled="status === 'ended'"
                     @change="submitAnswer(task.id, opt.id)">
              <span x-html="renderMath(opt.label)"></span>
            </label>
          </template>
        </div>
      </template>

      {{-- Expression type --}}
      <template x-if="task.payload.type !== 'choice'">
        <div class="lesson-answer-row">
          {{-- !!(…): Alpine 3.15 в клонах template при undefined СТАВИТ boolean-атрибут,
               а не снимает — выражение обязано возвращать строго boolean --}}
          <input type="text" inputmode="text" class="lesson-answer-input"
                 :value="task.my_answer || ''"
                 :disabled="!!(status === 'ended' || sending[task.id])"
                 :placeholder="task.my_answer ? '' : 'Твой ответ'"
                 @keydown.enter.prevent="submitAnswer(task.id, $event.target.value)"
                 @paste="onAnswerPaste(task.id, $event)"
                 @blur="if($event.target.value && $event.target.value !== (task.my_answer||'')) submitAnswer(task.id, $event.target.value)">
          <button class="lesson-submit-btn"
                  :disabled="!!(status === 'ended' || sending[task.id])"
                  @click="submitAnswer(task.id, $event.target.previousElementSibling.value)">
            <span x-show="!sending[task.id]" x-text="task.my_answer ? '↻' : '→'"></span>
            <span x-show="sending[task.id]" x-cloak>…</span>
          </button>
        </div>
      </template>
    </div>
  </template>

  {{-- До первого ответа сервера не показываем «задач нет» — это была ложная
       надпись в первые секунды загрузки. --}}
  <template x-if="!loaded">
    <div style="text-align:center;color:var(--muted);padding:30px 0;">Загружаем урок…</div>
  </template>

  <template x-if="loaded && tasks.length === 0">
    <div style="text-align:center;color:var(--muted);padding:30px 0;">
      Учитель ещё не добавил задачи. Подожди немного.
    </div>
  </template>
</div>

<script>
  /**
   * Довести формулы в карточках урока до ширины карточки.
   *
   * `renderMathInElement` кладёт `.katex` внутрь безымянного span, поэтому
   * текст после формулы ищем от этой обёртки, а не от самой формулы.
   * Пунктуацию, прилипшую к формуле, склеиваем с ней: рядом с неделимой
   * коробкой браузеру можно переносить строку, и точка уезжала одна.
   * Кегль уменьшаем ровно во столько раз, во сколько формула не влезла,
   * но не мельче 11px.
   */
  window.lessonFitFormulas = function () {
    const host = (k, root) => {
      let n = k;
      while (n.parentNode && n.parentNode !== root && !n.nextSibling) n = n.parentNode;
      return n;
    };
    document.querySelectorAll('.lesson-task-expr').forEach((el) => {
      const base = parseFloat(getComputedStyle(el).fontSize) || 18;
      const floor = Math.min(1, 11 / base);
      el.querySelectorAll('.katex').forEach((k) => {
        const h = host(k, el);
        if (!(h.parentNode && h.parentNode.classList && h.parentNode.classList.contains('nb'))) {
          const next = h.nextSibling;
          const m = next && next.nodeType === 3 ? next.textContent.match(/^[.,;:!?)]+/) : null;
          if (m) {
            const nb = document.createElement('span');
            nb.className = 'nb';
            h.parentNode.insertBefore(nb, h);
            nb.appendChild(h);
            nb.appendChild(document.createTextNode(m[0]));
            next.textContent = next.textContent.slice(m[0].length);
          }
        }
        k.style.fontSize = '';
        const after = host(k, el).nextSibling;
        const avail = el.clientWidth - (after && after.textContent.trim() ? 14 : 0);
        const need = k.scrollWidth;
        if (avail <= 0 || !need || need <= avail) return;
        k.style.fontSize = (Math.max(floor, avail / need) * 100).toFixed(1) + '%';
      });
    });
  };
  window.addEventListener('resize', () => window.lessonFitFormulas());

  function studentLesson(sessionId, initialStatus) {
    let tasksJson = ''; // вне reactive: снапшот последних серверных tasks
    let reviewJson = ''; // то же для карточек разбора

    return {
      sessionId,
      status: initialStatus,
      tasks: [],
      review: [],          // разбор домашки с учителем: только чтение
      // Просмотрщик тетради (контракт партиала pwa._shared.photo-viewer)
      photos: [],
      viewer: false,
      vi: 0,
      loaded: false,       // пришёл первый ответ /state
      sending: {},
      pollTimer: null,
      lock: null,          // {locked_until, released_at, active} из state
      nowTick: Date.now(), // обновляется раз в секунду для реактивности таймера
      hiddenAt: null,      // когда вкладка ушла в hidden (для оверлея «Продолжить»)
      resumeVisible: false,
      resumeAwaySec: 0,
      inTelegram: false,   // страница открыта в вебвью Telegram mini app
      tgActive: true,      // мини-апп не свёрнут (activated/deactivated)
      lastSentVisible: null, // дедуп: visibilitychange и tg-события могут дублироваться
      lastInteraction: Date.now(),
      wakeLock: null,      // Screen Wake Lock: экран не гаснет, пока идёт урок

      async init() {
        await this.refreshState();
        this.pollTimer = setInterval(() => {
          if (document.hidden) return;
          this.refreshState();
        }, 5000);
        setInterval(() => { this.nowTick = Date.now(); }, 1000);
        this.initActivityTracking();
        this.initBehaviorTracking();
        this.initWakeLock();
      },

      // Экран не блокируется, пока ученик на странице урока (как в видеоплеере).
      // Система сама снимает лок при уходе страницы в фон — поэтому берём заново
      // при возврате. Часть вебвью отдаёт лок только после жеста, отсюда повтор
      // по первому тачу (см. initActivityTracking).
      initWakeLock() {
        if (!('wakeLock' in navigator)) return;
        this.requestWakeLock();
        document.addEventListener('visibilitychange', () => {
          if (document.visibilityState === 'visible') this.requestWakeLock();
        });
      },

      async requestWakeLock() {
        if (!('wakeLock' in navigator)) return;
        if (this.wakeLock || this.status === 'ended') return;
        if (document.visibilityState !== 'visible') return;
        try {
          const lock = await navigator.wakeLock.request('screen');
          lock.addEventListener('release', () => { this.wakeLock = null; });
          this.wakeLock = lock;
        } catch (e) {
          this.wakeLock = null; // NotAllowedError: батарея на нуле, вебвью запретил
        }
      },

      releaseWakeLock() {
        const lock = this.wakeLock;
        this.wakeLock = null;
        if (lock) lock.release().catch(() => {});
      },

      // Отслеживание присутствия: сервер строит таймлайн present/away.
      // В вебвью Telegram mini app сворачивание НЕ переводит документ в hidden
      // и не даёт pagehide — страница живёт в фоне. Поэтому: события
      // activated/deactivated из Telegram WebApp API (Bot API 8.0+), а для
      // старых клиентов страховка — без взаимодействий heartbeat не продлевает
      // present, и сервер обрезает интервал по stale (25 сек).
      initActivityTracking() {
        const INACTIVITY_MAX_MS = 180000; // 3 мин без тача/клавиатуры в Телеграме = не на уроке
        const tg = window.Telegram && window.Telegram.WebApp;
        this.inTelegram = !!(window.TelegramWebviewProxy || (tg && (tg.initData || tg.platform !== 'unknown')));

        if (this.inTelegram && tg && tg.onEvent) {
          try {
            if (tg.isActive === false) this.tgActive = false;
            tg.onEvent('deactivated', () => { this.tgActive = false; this.onVisibilityChanged(); });
            tg.onEvent('activated', () => {
              this.tgActive = true;
              this.lastInteraction = Date.now();
              this.onVisibilityChanged();
            });
          } catch (e) { /* старый клиент без этих событий */ }
        }

        ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach((ev) =>
          document.addEventListener(ev, () => {
            this.lastInteraction = Date.now();
            if (!this.wakeLock) this.requestWakeLock();
          }, { passive: true, capture: true }));

        this.lastSentVisible = this.isOnPage();
        this.sendActivity(this.lastSentVisible);
        document.addEventListener('visibilitychange', () => this.onVisibilityChanged());
        // Heartbeat: пока страница «на уроке» — продлеваем present (детект молчаливого ухода).
        setInterval(() => {
          if (!this.isOnPage()) return;
          if (this.inTelegram && Date.now() - this.lastInteraction > INACTIVITY_MAX_MS) return;
          this.sendActivity(true);
        }, 10000);
        // Закрытие вкладки/сворачивание приложения — надёжно через sendBeacon.
        window.addEventListener('pagehide', () => this.beaconActivity(false));
      },

      isOnPage() {
        return document.visibilityState === 'visible' && this.tgActive;
      },

      // Единая точка смены видимости (DOM + Telegram): presence и оверлей «Продолжить».
      onVisibilityChanged() {
        const RESUME_MIN_AWAY = 10; // сек отлучки → по возврату оверлей
        const visible = this.isOnPage();
        if (visible === this.lastSentVisible) return;
        this.lastSentVisible = visible;
        this.sendActivity(visible);

        if (!visible) {
          if (!this.hiddenAt) this.hiddenAt = Date.now();
          return;
        }
        if (!this.hiddenAt) return;
        const away = Math.round((Date.now() - this.hiddenAt) / 1000);
        this.hiddenAt = null;
        if (this.status !== 'ended' && away >= RESUME_MIN_AWAY) {
          this.resumeAwaySec = away;
          this.resumeVisible = true;
        }
      },

      sendActivity(visible) {
        fetch(`/lessons/${this.sessionId}/activity`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
          credentials: 'include',
          body: JSON.stringify({ visible }),
          keepalive: true,
        }).catch(() => {});
      },

      // Поведенческие сигналы: копирование условия, вставка ответа.
      // Пауза «Продолжить» после отлучки — в onVisibilityChanged().
      initBehaviorTracking() {
        // Скопировал текст внутри карточки задачи (не свой ответ из инпута).
        document.addEventListener('copy', () => {
          if (this.status === 'ended') return;
          if (document.activeElement?.classList?.contains('lesson-answer-input')) return;
          const sel = window.getSelection();
          if (!sel || sel.isCollapsed) return;
          let node = sel.anchorNode;
          if (node && node.nodeType === Node.TEXT_NODE) node = node.parentElement;
          const card = node?.closest?.('.lesson-task-card');
          if (!card) return;
          this.sendEvent('copy_task', Number(card.dataset.taskId) || null,
            { length: String(sel).length });
        });
      },

      confirmResume() {
        this.resumeVisible = false;
        this.sendEvent('resume', null, { away_seconds: this.resumeAwaySec });
        this.resumeAwaySec = 0;
      },

      get resumeAwayLabel() {
        const s = this.resumeAwaySec;
        if (s < 60) return s + ' сек';
        const m = Math.floor(s / 60);
        return m + ' мин ' + (s % 60) + ' сек';
      },

      onAnswerPaste(taskId, e) {
        if (this.status === 'ended') return;
        const text = e.clipboardData?.getData('text') || '';
        if (!text.trim()) return;
        this.sendEvent('paste_answer', taskId, { length: text.length });
      },

      sendEvent(kind, taskId, meta) {
        fetch(`/lessons/${this.sessionId}/event`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
          credentials: 'include',
          body: JSON.stringify({ kind, task_id: taskId, meta: meta || {} }),
          keepalive: true,
        }).catch(() => {});
      },

      beaconActivity(visible) {
        try {
          const blob = new Blob([JSON.stringify({ visible, _token: document.querySelector('meta[name=csrf-token]').content })],
            { type: 'application/json' });
          navigator.sendBeacon(`/lessons/${this.sessionId}/activity`, blob);
        } catch (e) { /* ignore */ }
      },

      get lockActive() {
        if (!this.lock?.active || !this.lock.locked_until) return false;
        return new Date(this.lock.locked_until).getTime() > this.nowTick;
      },

      get released() {
        return !!this.lock?.released_at && this.status !== 'ended';
      },

      get lockLeft() {
        if (!this.lock?.locked_until) return '';
        const ms = new Date(this.lock.locked_until).getTime() - this.nowTick;
        if (ms <= 0) return '0:00';
        const totalSec = Math.floor(ms / 1000);
        const m = Math.floor(totalSec / 60);
        const s = totalSec % 60;
        return `${m}:${String(s).padStart(2, '0')}`;
      },

      async refreshState() {
        const r = await fetch(`/lessons/${this.sessionId}/state`, { headers: { 'Accept': 'application/json' }, credentials: 'include' });
        if (!r.ok) return;
        const d = await r.json();
        this.loaded = true;
        this.status = d.session.status;
        if (this.status === 'ended') this.releaseWakeLock(); // урок кончился — экран гасим как обычно
        this.lock = d.lock || null;
        // tasks заменяем только при реальном изменении и не во время ввода:
        // иначе :value каждые 5с переприменяется и стирает недопечатанный ответ.
        const typing = document.activeElement?.classList?.contains('lesson-answer-input');
        // Карточки разбора меняются редко (учитель добавляет их руками), но
        // сравниваем так же: x-html при каждом poll пересоздавал бы DOM и сбивал
        // уже дорендеренные формулы.
        const rj = JSON.stringify(d.review || []);
        if (rj !== reviewJson) {
          reviewJson = rj;
          this.review = d.review || [];
          this.$nextTick(() => {
            if (window.renderMathInElement) window.renderMathInElement(document.body, { delimiters: [{left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false}], throwOnError: false });
          });
        }
        const tj = JSON.stringify(d.tasks);
        if (tj !== tasksJson && !typing) {
          tasksJson = tj;
          this.tasks = d.tasks;
          // Re-render KaTeX только когда задачи реально изменились: обход всего
          // body каждые 5 секунд заметно тормозил слабые телефоны.
          this.$nextTick(() => {
            if (window.renderMathInElement) window.renderMathInElement(document.body, { delimiters: [{left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false}], throwOnError: false });
            if (window.lessonFitFormulas) window.lessonFitFormulas();
          });
        }
      },

      /** Тетрадь открывается поверх урока — как у учителя, тем же партиалом. */
      openReviewPhotos(card, index) {
        this.photos = (card.photos || []).map(p => ({ src: p.url, full: p.full, label: p.label }));
        if (!this.photos.length) return;
        this.vi = index;
        this.viewer = true;
        document.body.style.overflow = 'hidden';
      },
      close() { this.viewer = false; document.body.style.overflow = ''; },
      step(d) { this.vi = (this.vi + d + this.photos.length) % this.photos.length; },

      async submitAnswer(taskId, answer) {
        if (!answer || this.sending[taskId]) return;
        this.sending[taskId] = true;
        try {
          const r = await fetch(`/lessons/${this.sessionId}/answer`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
              'Accept': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({ task_id: taskId, answer: String(answer) }),
          });
          if (!r.ok) {
            const err = await r.json().catch(() => ({}));
            alert(err.error || 'Не удалось отправить');
            return;
          }
          // Update local copy так чтобы UI сразу обновился, без ожидания polling
          const task = this.tasks.find(t => t.id === taskId);
          if (task) task.my_answer = String(answer);
        } finally {
          this.sending[taskId] = false;
        }
      },

      renderMath(text) {
        // KaTeX renders on init; just escape and return — auto-render walks DOM
        const esc = (t) => { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; };
        const s = String(text || '');
        // Подпункты «а) б) в)» лежат в банке отдельными абзацами, и при
        // выпрямлении разметки перед каждым остаётся перевод строки. Прочие
        // переносы там случайные (в ОГЭ рвут предложение посреди фразы),
        // поэтому разбиваем только по подпунктам.
        const parts = s.split(/\n(?=[ \t]*[абвгд]\))/);
        if (parts.length < 2) return esc(s);
        return parts
          .map((part, i) => '<span class="' + (i === 0 ? 'cond-lead' : 'cond-sub') + '">'
            + esc(part.trim()) + '</span>')
          .join('');
      },
    };
  }
</script>
@endsection
