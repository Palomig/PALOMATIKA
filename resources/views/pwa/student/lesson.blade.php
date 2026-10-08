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
<link rel="preload" href="/vendor/katex/0.16.9/fonts/KaTeX_Main-Regular.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/vendor/katex/0.16.9/fonts/KaTeX_Math-Italic.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/vendor/katex/0.16.9/katex.min.css">
<script defer src="/vendor/katex/0.16.9/katex.min.js"></script>
<script defer src="/vendor/katex/0.16.9/contrib/auto-render.min.js"
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
  @include('pwa._shared.partials.fipi-condition-css')
  .lesson-task-expr.fipi-condition { font-size: 17px; }
  /* Внутри разметки ФИПИ формулы переносятся как обычный текст:
     правило «одной строкой» — для плоского условия. */
  .lesson-task-expr.fipi-condition .katex { white-space: normal; display: inline; overflow: visible; }
  .lesson-task-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
  /* Отправленный ответ — синим, как выбранный вариант в задаче с выбором.
     Ни зелёного, ни галочки: верен ли ответ, ученик на уроке не видит. */
  .lesson-task-card.is-answered { border-color: var(--accent-bd); }
  .lesson-task-card.is-new { border-color: var(--yellow-bd); }
  /* Номер — в начале текста условия, а не отдельной строкой. */
  .lesson-task-num { font-family: var(--display); font-size: 17px; color: var(--accent); float: left; margin-right: 6px; line-height: 1.35; }
  .lesson-task-tags { display: flex; gap: 6px; }
  .lesson-new-badge { font-size: 10px; font-weight: 800; padding: 1px 8px; border-radius: 6px; background: var(--yellow-bg); color: var(--yellow); border: 1px solid var(--yellow-bd); }
  .lesson-task-lead { font-size: 16px; color: var(--text); line-height: 1.45; }
  /* Пример — отдельной строкой под текстом задания, крупно. */
  .lesson-task-formula { font-size: 20px; min-height: 0; padding: 2px 0; }
  .lesson-sent-row { display: flex; align-items: center; gap: 8px; padding: 10px 12px; min-height: 48px; border-radius: 10px; background: var(--accent-bg); border: 1px solid var(--accent); font-size: 14px; color: var(--muted); }
  .lesson-sent-value { color: var(--text); font-family: ui-monospace, monospace; font-weight: 800; font-size: 15px; word-break: break-word; }
  .lesson-sent-value .katex { font-size: 1.1em; }
  .lesson-sent-edit { margin-left: auto; background: none; border: none; color: var(--accent); font-size: 13px; font-weight: 800; cursor: pointer; text-decoration: underline dotted; flex-shrink: 0; }
  .lesson-choice-note { font-size: 12px; color: var(--muted); }
  /* Дробный ответ: поле ответа само устроено как дробь — у серий, где ответ
     дробь (см. TaskBankResolver::answerField). Переключателя нет. */
  .lesson-frac { flex: 1; min-width: 0; display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface2); }
  .lesson-frac:focus-within { border-color: var(--accent); }
  .lesson-frac input { text-align: center; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font-family: ui-monospace, monospace; }
  .lesson-frac input:focus { outline: none; border-color: var(--accent); }
  .lesson-frac input::placeholder { font-family: var(--body); font-size: 11px; color: var(--muted); }
  .lesson-frac-whole { width: 56px; height: 64px; font-size: 22px; padding: 4px; }
  .lesson-frac-stack { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 0; max-width: 180px; }
  .lesson-frac-stack input { width: 100%; padding: 6px; font-size: 16px; }
  .lesson-frac-line { height: 2px; background: var(--text); opacity: .75; border-radius: 2px; }
  .lesson-frac-hint { font-size: 11px; color: var(--muted); width: 100%; }
  .lesson-answer-row .lesson-submit-btn { align-self: stretch; }
  .topbar-titles { display: flex; flex-direction: column; min-width: 0; }
  .topbar-sub { font-size: 12px; color: var(--muted); font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .lesson-live-pill { font-size: 11px; font-weight: 800; padding: 3px 9px; border-radius: 20px; text-transform: uppercase; letter-spacing: .03em; background: var(--accent-bg); color: var(--accent); flex-shrink: 0; }
  .lesson-new-toast { position: fixed; left: 12px; right: 12px; top: calc(10px + var(--safe-top, 0px)); z-index: 150; max-width: 520px; margin: 0 auto; display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 14px; background: var(--surface); border: 1px solid var(--yellow-bd); box-shadow: 0 10px 30px rgba(0,0,0,.35); color: var(--text); font-size: 14px; font-weight: 700; cursor: pointer; }
  .lesson-new-toast .go { margin-left: auto; color: var(--yellow); font-weight: 800; }
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
  /* flex-wrap: под полем ответа живёт виджет дробного ввода */
  .lesson-answer-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
  .lesson-answer-input { flex: 1; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 10px; padding: 12px 14px; font-size: 16px; font-family: ui-monospace, monospace; }
  .lesson-answer-input:focus { outline: 2px solid var(--accent); border-color: var(--accent); }
  .lesson-submit-btn { background: var(--accent); color: white; border: none; border-radius: 10px; padding: 12px 18px; font-weight: 800; cursor: pointer; font-size: 14px; }
  .lesson-submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }
  .lesson-choice-options { display: grid; gap: 8px; }
  .lesson-choice-option { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; cursor: pointer; }
  .lesson-choice-option.is-selected { background: var(--accent-bg); border-color: var(--accent); }
  .lesson-choice-option input[type="radio"] { accent-color: var(--accent); }
  .lesson-end-banner { background: var(--red-bg); border: 1px solid var(--red-bd); border-radius: 14px; padding: 16px; color: var(--red); font-weight: 700; text-align: center; }
  .lesson-released-banner { background: var(--green-bg); border: 1px solid var(--green-bd); border-radius: 14px; padding: 16px; color: var(--green); font-weight: 700; text-align: center; }
  .lock-timer { margin-left: auto; flex-shrink: 0; font-family: ui-monospace, monospace; font-size: 13px; font-weight: 700; color: var(--muted); }
  .resume-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.72); display: flex; align-items: center; justify-content: center; padding: 24px; }
  .resume-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px; max-width: 320px; width: 100%; text-align: center; display: flex; flex-direction: column; gap: 14px; }
  .resume-title { font-family: var(--display); font-size: 18px; color: var(--text); }
  .resume-sub { font-size: 13px; color: var(--muted); }
  .resume-btn { background: var(--accent); color: white; border: none; border-radius: 10px; padding: 14px 18px; font-weight: 800; font-size: 15px; cursor: pointer; }
  .personal-badge { font-size: 10px; font-weight: 800; padding: 1px 8px; border-radius: 6px; background: var(--accent-bg); color: var(--accent); border: 1px solid var(--accent-bd); white-space: nowrap; }

  /* Разбор домашки: только чтение, поля ответа нет — это не задача урока */
  .review-block { border: 1px solid var(--purple-bd); background: var(--purple-bg); border-radius: var(--r); padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; }
  .review-block-head { display: flex; align-items: center; gap: 8px; font-family: var(--display); font-size: 15px; color: var(--text); background: none; border: none; padding: 0; text-align: left; cursor: pointer; width: 100%; }
  .review-block-head .chev { margin-left: auto; font-family: var(--body); font-size: 13px; font-weight: 800; color: var(--muted); white-space: nowrap; }
  .review-card-s { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px 13px; }
  .review-card-num { font-size: 11px; font-weight: 800; color: var(--purple); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
  .review-visual-s { margin: 6px 0; display: flex; justify-content: center; }
  .review-visual-s :is(svg, img) { max-width: 100%; height: auto; }
  .review-text-s { font-size: 14px; line-height: 1.45; color: var(--text); word-break: break-word; }
  .review-answers-s { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
  .review-chip-s { font-size: 12px; font-weight: 700; padding: 4px 9px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); }
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
    <div class="topbar-titles">
      <div class="topbar-title">Урок</div>
      @if($session->teacher)<div class="topbar-sub">{{ $session->teacher->name }}</div>@endif
    </div>
    <span class="lock-timer" x-show="lockActive" x-cloak>🔒 <span x-text="lockLeft"></span></span>
    <span class="lesson-live-pill" x-show="status === 'live'" :style="lockActive ? '' : 'margin-left:auto'" x-cloak>идёт</span>
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

  {{-- Учитель добавил задачу по ходу урока: она встаёт в конец ленты, а
       ученик может быть где угодно — зовём к ней, а не молча. --}}
  <div class="lesson-new-toast" x-show="newToast" x-cloak x-transition.opacity @click="goToNew()">
    <span x-text="newIds.length > 1 ? 'Учитель добавил задачи' : 'Учитель добавил задачу'"></span>
    <span class="go">открыть ›</span>
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
      <button type="button" class="review-block-head" @click="reviewOpen = !reviewOpen; typesetSoon()">
        🔍 Разбор домашки с учителем
        <span class="chev" x-text="reviewOpen ? 'свернуть' : (review.length + ' ' + taskWord(review.length) + ' ›')"></span>
      </button>
      <template x-for="card in (reviewOpen ? review : [])" :key="'rev-' + card.id">
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
  @include('pwa._shared.partials.fipi-condition-js')

  <template x-for="task in tasks" :key="task.id">
    <div class="lesson-task-card" :class="{ 'is-answered': !!task.my_answer && !editing[task.id], 'is-new': isNew(task) }" :data-task-id="task.id">
      <template x-if="task.personal || isNew(task)">
        <div class="lesson-task-tags">
          <span class="personal-badge" x-show="task.personal">только тебе</span>
          <span class="lesson-new-badge" x-show="isNew(task)">новая</span>
        </div>
      </template>

      {{-- Банк ЕГЭ: условие целиком в разметке ФИПИ (таблицы соответствия,
           графики-варианты, обозначения-растры); формулы $…$ дорисует
           auto-render, который обходит DOM после загрузки задач. --}}
      <template x-if="task.payload.condition_html">
        <div>
          <span class="lesson-task-num" x-text="task.position + ')'"></span>
          <div class="lesson-task-expr fipi-condition"
               x-html="window.paloFipiHtml ? window.paloFipiHtml(task.payload.condition_html) : (task.payload.condition_html || '')"></div>
        </div>
      </template>
      {{-- Текст задания отдельно, пример — крупно строкой ниже (см. splitCondition). --}}
      <template x-if="!task.payload.condition_html && splitCondition(task)">
        <div style="display:flex;flex-direction:column;gap:8px;">
          <div class="lesson-task-lead"><span class="lesson-task-num" x-text="task.position + ')'"></span><span x-html="renderMath(splitCondition(task).lead)"></span></div>
          <div class="lesson-task-expr lesson-task-formula" x-html="formulaHtml(splitCondition(task).formula)"></div>
          <div class="lesson-task-lead" x-show="splitCondition(task).tail" x-html="renderMath(splitCondition(task).tail)"></div>
        </div>
      </template>
      <template x-if="!task.payload.condition_html && !splitCondition(task)">
        <div class="lesson-task-expr"><span class="lesson-task-num" x-text="task.position + ')'"></span><span x-html="renderMath(task.payload.expression)"></span></div>
      </template>

      <div class="lesson-task-image" x-show="task.payload.image_svg" x-html="task.payload.image_svg"></div>
      <template x-if="!task.payload.image_svg && task.payload.image_url && !task.payload.condition_html">
        <div class="lesson-task-image is-raster"><img :src="task.payload.image_url" alt=""></div>
      </template>

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
          <div class="lesson-choice-note" x-show="task.my_answer && status !== 'ended'">Отправлено учителю — до конца урока можно выбрать другой вариант</div>
        </div>
      </template>

      {{-- Ответ отправлен: синяя строка, поменять можно до конца урока --}}
      <template x-if="task.payload.type !== 'choice' && task.my_answer && !editing[task.id]">
        <div class="lesson-sent-row">
          <span>Отправлено учителю:</span>
          <span class="lesson-sent-value" x-html="answerHtml(task.my_answer)"></span>
          <button type="button" class="lesson-sent-edit" x-show="status !== 'ended'" @click="startEdit(task)">изменить</button>
        </div>
      </template>

      {{-- Дробный ответ: поле само устроено как дробь --}}
      <template x-if="task.payload.type !== 'choice' && isFracField(task) && (!task.my_answer || editing[task.id])">
        <div class="lesson-answer-row">
          <div class="lesson-frac">
            <template x-if="task.payload.answer_field === 'mixed'">
              <input type="text" class="lesson-answer-input lesson-frac-whole" placeholder="целая"
                     :inputmode="task.payload.answer_signed ? 'text' : 'numeric'"
                     x-model="frac(task.id).w" :disabled="!!(status === 'ended' || sending[task.id])"
                     @keydown.enter.prevent="submitFrac(task)">
            </template>
            <div class="lesson-frac-stack">
              <input type="text" class="lesson-answer-input" placeholder="числитель"
                     :inputmode="task.payload.answer_letters || task.payload.answer_signed ? 'text' : 'numeric'"
                     autocapitalize="off" autocorrect="off" spellcheck="false"
                     x-model="frac(task.id).n" :disabled="!!(status === 'ended' || sending[task.id])"
                     @paste="onAnswerPaste(task.id, $event)"
                     @keydown.enter.prevent="submitFrac(task)">
              <span class="lesson-frac-line"></span>
              <input type="text" class="lesson-answer-input" placeholder="знаменатель"
                     :inputmode="task.payload.answer_letters ? 'text' : 'numeric'"
                     autocapitalize="off" autocorrect="off" spellcheck="false"
                     x-model="frac(task.id).d" :disabled="!!(status === 'ended' || sending[task.id])"
                     @keydown.enter.prevent="submitFrac(task)">
            </div>
          </div>
          <button class="lesson-submit-btn" :disabled="!!(status === 'ended' || sending[task.id])" @click="submitFrac(task)">
            <span x-show="!sending[task.id]">→</span>
            <span x-show="sending[task.id]" x-cloak>…</span>
          </button>
          <div class="lesson-frac-hint" x-show="task.payload.answer_field === 'mixed'">Нет целой части — оставь «целую» пустой</div>
        </div>
      </template>

      {{-- Обычный ответ --}}
      <template x-if="task.payload.type !== 'choice' && !isFracField(task) && (!task.my_answer || editing[task.id])">
        <div class="lesson-answer-row">
          {{-- !!(…): Alpine 3.15 в клонах template при undefined СТАВИТ boolean-атрибут,
               а не снимает — выражение обязано возвращать строго boolean --}}
          <input type="text" inputmode="text" class="lesson-answer-input"
                 :value="task.my_answer || ''"
                 :disabled="!!(status === 'ended' || sending[task.id])"
                 placeholder="Твой ответ"
                 @keydown.enter.prevent="submitAnswer(task.id, $event.target.value)"
                 @paste="onAnswerPaste(task.id, $event)"
                 @blur="if($event.target.value && $event.target.value !== (task.my_answer||'')) submitAnswer(task.id, $event.target.value)">
          <button class="lesson-submit-btn"
                  :disabled="!!(status === 'ended' || sending[task.id])"
                  @click="submitAnswer(task.id, $el.closest('.lesson-answer-row').querySelector('.lesson-answer-input').value)">
            <span x-show="!sending[task.id]">→</span>
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
    let knownIds = null; // id задач, которые ученик уже видел (null — до первой загрузки)
    let toastTimer = null;

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
      reviewOpen: false,   // разбор домашки свёрнут в строку
      editing: {},         // task_id → ученик меняет отправленный ответ
      fracDraft: {},       // task_id → {w, n, d} дробного поля
      newIds: [],          // задачи, добавленные учителем после открытия страницы
      newToast: false,

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
          this.typesetSoon();
        }
        const tj = JSON.stringify(d.tasks);
        if (tj !== tasksJson && !typing) {
          tasksJson = tj;
          this.tasks = d.tasks;
          this.noticeNewTasks(d.tasks);
          // Re-render KaTeX только когда задачи реально изменились: обход всего
          // body каждые 5 секунд заметно тормозил слабые телефоны.
          this.$nextTick(() => {
            if (window.renderMathInElement) window.renderMathInElement(document.body, { delimiters: [{left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false}], throwOnError: false });
            if (window.lessonFitFormulas) window.lessonFitFormulas();
          });
        }
      },

      noticeNewTasks(tasks) {
        const ids = tasks.map(t => t.id);
        if (knownIds === null) { knownIds = new Set(ids); return; }
        const fresh = tasks.filter(t => !knownIds.has(t.id) && !t.my_answer).map(t => t.id);
        ids.forEach(id => knownIds.add(id));
        if (!fresh.length) return;
        this.newIds = [...this.newIds, ...fresh];
        this.newToast = true;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { this.newToast = false; }, 8000);
      },

      isNew(task) {
        return this.newIds.includes(task.id) && !task.my_answer;
      },

      goToNew() {
        this.newToast = false;
        const id = this.newIds.find(i => this.tasks.some(t => t.id === i && !t.my_answer));
        const el = id && document.querySelector(`.lesson-task-card[data-task-id="${id}"]`);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      },

      taskWord(n) {
        const m10 = n % 10, m100 = n % 100;
        if (m10 === 1 && m100 !== 11) return 'задача';
        if (m10 >= 2 && m10 <= 4 && (m100 < 10 || m100 >= 20)) return 'задачи';
        return 'задач';
      },

      /**
       * Текст задания отдельно от примера.
       *  — наши банки: текст серии над голой формулой (payload.instruction);
       *  — условие одной фразой «Найдите значение выражения $…$.»: фраза,
       *    формула строкой ниже, хвост («Если уравнение имеет…») — следом.
       * Делим, только когда формула одна, в ней есть действие, а после неё
       * конец условия или новое предложение с заглавной: «равно $112$ км»
       * так не разорвётся.
       */
      splitCondition(task) {
        const p = task.payload || {};
        const expr = String(p.expression || '').trim();
        if (p.instruction) {
          const bare = expr.replace(/^\$+|\$+$/g, '');
          if (bare && !bare.includes('$')) return { lead: p.instruction, formula: bare, tail: '' };
        }
        const m = expr.match(/^([^$]+?)\s*\$([^$]+)\$\s*([.,;:]?)\s*([^$]*)$/s);
        if (!m) return null;
        const [, lead, formula, , tail] = m;
        if (!/[=+\-<>]|\\(?:frac|dfrac|sqrt|cdot|log)|\^/.test(formula)) return null;
        if (tail && !/^[А-ЯЁA-Z]/.test(tail.trim())) return null;
        if (!/\p{L}/u.test(lead) || lead.length > 160) return null;
        return { lead: lead.trim(), formula, tail: tail.trim() };
      },

      formulaHtml(f) {
        const d = document.createElement('div');
        d.textContent = '$\\displaystyle ' + f + '$';
        return d.innerHTML;
      },

      isFracField(task) {
        const f = task.payload && task.payload.answer_field;
        return f === 'fraction' || f === 'mixed';
      },

      frac(id) {
        if (!this.fracDraft[id]) this.fracDraft[id] = { w: '', n: '', d: '' };
        return this.fracDraft[id];
      },

      // «a+5» над «a-5» — это (a+5)/(a-5): сумму и разность в скобки.
      composeFrac(task) {
        const f = this.frac(task.id);
        const wrap = (x) => /^-?[^\s+\-*/()]+$/.test(x) ? x : `(${x})`;
        const w = String(f.w || '').trim(), n = String(f.n || '').trim(), d = String(f.d || '').trim();
        const fr = n && d ? `${wrap(n)}/${wrap(d)}` : n;
        return [w, fr].filter(Boolean).join(' ');
      },

      submitFrac(task) {
        const answer = this.composeFrac(task);
        if (answer) this.submitAnswer(task.id, answer);
      },

      startEdit(task) {
        if (this.isFracField(task)) {
          const s = String(task.my_answer || '').trim();
          const mx = s.match(/^(-?\d+)\s+(\d+)\/(\d+)$/);
          const m = s.match(/^\(?([^()/]+)\)?\/\(?([^()/]+)\)?$/);
          this.fracDraft[task.id] = mx ? { w: mx[1], n: mx[2], d: mx[3] }
            : m ? { w: '', n: m[1], d: m[2] } : { w: '', n: s, d: '' };
        }
        this.editing[task.id] = true;
      },

      // Отправленный дробный ответ показываем дробью.
      answerHtml(raw) {
        const s = String(raw || '').trim();
        const esc = (t) => { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; };
        const unp = (x) => x.replace(/^\((.*)\)$/, '$1');
        let m = s.match(/^(-?\d+)\s+(\d+)\/(\d+)$/);
        if (m) return esc(`$${m[1]}\\dfrac{${m[2]}}{${m[3]}}$`);
        m = s.match(/^(\([^()$]+\)|[^\s/()$]+)\/(\([^()$]+\)|[^\s/()$]+)$/);
        if (m) return esc(`$\\dfrac{${unp(m[1])}}{${unp(m[2])}}$`);
        return esc(s);
      },

      typesetSoon() {
        this.$nextTick(() => {
          if (window.renderMathInElement) window.renderMathInElement(document.body, { delimiters: [{left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false}], throwOnError: false });
          if (window.lessonFitFormulas) window.lessonFitFormulas();
        });
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
          this.editing[taskId] = false;
          this.newIds = this.newIds.filter(i => i !== taskId);
          this.typesetSoon();
        } finally {
          this.sending[taskId] = false;
        }
      },

      renderMath(text) {
        // KaTeX renders on init; just escape and return — auto-render walks DOM
        // Растры-обозначения ФИПИ внутри плоского условия остаются картинками.
        const esc = (t) => {
          if (window.paloEscapeKeepingFipiImages) return window.paloEscapeKeepingFipiImages(t || '');
          const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML;
        };
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
