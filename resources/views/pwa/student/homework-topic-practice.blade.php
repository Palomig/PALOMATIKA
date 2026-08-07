@extends('layouts.pwa')
@section('title', 'Домашка — palomatika')

{{-- Условия приходят из банков с формулами в $…$ — без KaTeX ученик видит голый LaTeX. --}}
@push('katex')
@include('partials.head-katex')
@endpush

@push('styles')
  .topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
  }
  .back { color: var(--text); text-decoration: none; font-size: 18px; padding: 6px 8px; border: 1px solid var(--border); border-radius: 10px; }
  .topbar-title { font-family: var(--display); font-size: 18px; color: var(--text); }
  .hw-summary {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 14px 16px; margin-bottom: 12px;
  }
  .hw-summary-title { font-family: var(--display); font-size: 16px; color: var(--text); }
  .hw-summary-meta { margin-top: 4px; font-size: 12px; color: var(--muted); font-weight: 700; }
  .task-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 14px 16px; margin-bottom: 10px;
  }
  .task-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
  .task-num { font-family: var(--display); font-size: 14px; color: var(--text); }
  .task-state { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; padding: 3px 8px; border-radius: 6px; white-space: nowrap; }
  .state-open { color: #93bbfd; background: rgba(59,130,246,.2); }
  .state-retry { color: #fcd34d; background: rgba(234,179,8,.2); }
  .state-done { color: #86efac; background: rgba(34,197,94,.2); }
  .task-text { color: var(--text); font-size: 14px; line-height: 1.45; overflow-wrap: anywhere; }
  /* Формулы в условии мельче окружающего текста читаются плохо — как в тесте. */
  .task-text .katex, .task-instruction .katex { font-size: 1.15em; }
  .task-instruction { color: var(--muted); font-size: 12px; font-weight: 700; margin-bottom: 6px; }
  .task-visual { margin: 4px 0 10px; display: flex; justify-content: center; }
  .task-visual svg { max-width: 100%; width: auto; height: auto; display: block; }
  .task-visual img { max-width: 100%; height: auto; display: block; border-radius: 8px; }
  .task-visual > .task-visual-frame { width: 100%; max-width: 320px; }
  .task-form { margin-top: 12px; display: grid; gap: 8px; }
  .task-input {
    width: 100%; padding: 11px 12px; border-radius: 10px;
    border: 1px solid var(--border); background: var(--surface2);
    color: var(--text); font-size: 14px;
  }
  .file-input { color: var(--muted); font-size: 12px; font-weight: 700; }
  .submit-btn {
    width: 100%; border: none; border-radius: 10px; padding: 11px 14px;
    background: var(--accent); color: #fff; font-weight: 900; font-size: 13px;
  }
  .submit-btn:active { opacity: .75; }
  .notice {
    margin-bottom: 10px; padding: 10px 12px; border-radius: 10px;
    font-size: 13px; font-weight: 700;
  }
  .notice-ok { color: #86efac; background: rgba(34,197,94,.14); border: 1px solid rgba(34,197,94,.24); }
  .notice-error { color: #fecaca; background: rgba(239,68,68,.14); border: 1px solid rgba(239,68,68,.24); }

  .photo-slot { display: flex; align-items: center; gap: 10px; }
  .photo-label {
    flex: 1; display: flex; align-items: center; gap: 8px;
    padding: 10px 12px; border-radius: 10px; cursor: pointer;
    background: var(--surface2); border: 1px dashed var(--border);
    color: var(--muted); font-size: 12px; font-weight: 700;
  }
  .photo-label.has-file { color: var(--green); border-color: rgba(34,197,94,.4); border-style: solid; background: rgba(34,197,94,.08); }
  .photo-label input { display: none; }
  .photo-label-icon { font-size: 18px; }

  .page-list { display: grid; gap: 6px; }
  .page-row {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 10px; border-radius: 9px;
    background: var(--surface2); border: 1px solid var(--border);
    font-size: 12px; font-weight: 700; color: var(--text);
  }
  .page-num { color: var(--muted); white-space: nowrap; }
  .page-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .page-state { color: var(--muted); font-size: 11px; white-space: nowrap; }
  .page-drop {
    border: none; background: none; color: var(--muted);
    font-size: 14px; cursor: pointer; padding: 0 2px;
  }
  .page-drop:active { opacity: .6; }
  .photo-hint { font-size: 11px; color: var(--muted); font-weight: 700; }
  .task-error {
    display: grid; gap: 8px;
    padding: 9px 11px; border-radius: 10px;
    font-size: 12px; font-weight: 700; line-height: 1.4;
    color: #fecaca; background: rgba(239,68,68,.14); border: 1px solid rgba(239,68,68,.24);
  }
  .task-error-btn {
    border: 1px solid rgba(239,68,68,.4); background: none; color: #fecaca;
    border-radius: 8px; padding: 7px 10px; font-weight: 800; font-size: 12px; cursor: pointer;
  }
  .task-error-btn:active { opacity: .65; }

  .hw-modal-overlay {
    position: fixed; inset: 0; z-index: 250;
    background: rgba(0,0,0,.6); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
  }
  .hw-modal {
    width: 100%; max-width: 360px;
    background: var(--bg); border: 1px solid var(--border);
    border-radius: var(--r); padding: 22px 22px 18px;
    box-shadow: 0 20px 50px rgba(0,0,0,.45);
    text-align: center;
    animation: fadeUp 0.25s ease;
  }
  .hw-modal-icon {
    font-size: 34px; margin-bottom: 6px;
  }
  .hw-modal-title {
    font-family: var(--display); font-size: 18px; color: var(--text);
    margin-bottom: 6px;
  }
  .hw-modal-body {
    color: var(--muted); font-size: 13px; font-weight: 600; line-height: 1.45;
    margin-bottom: 16px;
  }
  .hw-modal-btn {
    width: 100%; border: none; border-radius: 10px; padding: 11px 14px;
    background: var(--accent); color: #fff; font-weight: 900; font-size: 13px;
    cursor: pointer;
  }
  .hw-modal-btn:active { opacity: .75; }
@endpush

@section('body')
<div class="page" x-data="hwTopicPractice()" @hw-need-photo="showPhotoModal = true">
  <div class="topbar">
    <a href="{{ route('pwa.student.homework') }}" class="back">←</a>
    <div class="topbar-title">Домашка</div>
    <div style="width:34px;"></div>
  </div>

  @if(session('success'))
    <div class="notice notice-ok">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="notice notice-error">{{ session('error') }}</div>
  @endif
  {{-- Ошибки отправки приходят готовой плашкой в session('error') — здесь только остальные. --}}
  @if($errors->any() && !session('error'))
    <div class="notice notice-error">{{ $errors->first() }}</div>
  @endif

  <div class="hw-summary">
    <div class="hw-summary-title">{{ $homework->title ?: 'Домашнее задание' }}</div>
    <div class="hw-summary-meta">{{ $assignment->tasks_completed }} из {{ $assignment->tasks_total }} принято</div>
  </div>

  @foreach($homework->topicTasks as $task)
    @php
      $payload = $task->task_payload ?? [];
      $submission = $submissions->get($task->id);
      $accepted = $submission && $submission->accepted_at;
      $needsRetry = $submission && !$submission->accepted_at && (int) $submission->attempts_count === 1;
      $stateClass = $accepted ? 'state-done' : ($needsRetry ? 'state-retry' : 'state-open');
      $stateLabel = $accepted ? 'Принято' : ($needsRetry ? 'Повторить' : 'Открыто');
      // `html` — условие из банка ФИПИ (задачи, выбранные по теме); без него у ученика
      // вместо задачи оставалось только слово «Задача».
      $text = $payload['text_html'] ?? $payload['text'] ?? $payload['html'] ?? $payload['question'] ?? $payload['expression'] ?? 'Задача';
      $svg = $payload['svg'] ?? null;
      $image = $payload['image'] ?? null;
      $hasInlineSvg = is_string($svg) && str_contains($svg, '<svg');
      $hasLegacyInlineSvg = !$hasInlineSvg && is_string($image) && str_starts_with(ltrim($image), '<svg');
      $hasImageFile = !$hasInlineSvg && !$hasLegacyInlineSvg && is_string($image) && $image !== '';
      $payloadTopicId = (string) ($payload['topic_id'] ?? '');
    @endphp

    <div class="task-card">
      <div class="task-head">
        <div class="task-num">Задача {{ $task->task_order }}</div>
        <div class="task-state {{ $stateClass }}">{{ $stateLabel }}</div>
      </div>

      @if(!empty($payload['instruction']))
        <div class="task-instruction">{{ $payload['instruction'] }}</div>
      @endif

      @if($hasInlineSvg)
        <div class="task-visual"><div class="task-visual-frame">{!! $svg !!}</div></div>
      @elseif($hasLegacyInlineSvg)
        <div class="task-visual"><div class="task-visual-frame">{!! $image !!}</div></div>
      @elseif($hasImageFile && $payloadTopicId !== '')
        <div class="task-visual">
          <img src="{{ asset('images/tasks/' . $payloadTopicId . '/' . $image) }}" alt="Иллюстрация к задаче">
        </div>
      @endif

      <div class="task-text">{!! $text !!}</div>

      @if(!$accepted)
        {{-- action/method оставлены рабочими: без JS и в фолбэке с файлами
             форма отправляется нативно, как раньше. --}}
        <form class="task-form" method="POST" action="{{ route('pwa.student.homework.topic.submit', [$assignment, $task]) }}" enctype="multipart/form-data"
              x-ref="form"
              x-data="taskPhotos({
                submitUrl: '{{ route('pwa.student.homework.topic.submit', [$assignment, $task]) }}',
                ticketUrl: '{{ route('pwa.student.homework.topic.photo-ticket', [$assignment, $task]) }}',
                logUrl: '{{ route('pwa.student.homework.topic.photo-log', [$assignment, $task]) }}',
                draftKey: 'hw-pages:{{ $assignment->id }}:{{ $task->id }}',
              })"
              x-init="restoreDraft()"
              @submit.prevent="send()">
          @csrf
          {{-- Ответ возвращаем только той задаче, из которой пришла ошибка. --}}
          <input class="task-input" type="text" name="answer" placeholder="Ответ" required x-ref="answer"
                 value="{{ (int) session('answer_task_id') === (int) $task->id ? old('answer') : '' }}">

          <template x-if="pages.length">
            <div class="page-list">
              <template x-for="(page, index) in pages" :key="page.key">
                <div class="page-row">
                  <span class="page-num" x-text="'Стр. ' + (index + 1)"></span>
                  <span class="page-name" x-text="page.name"></span>
                  <span class="page-state" x-text="pageState(page)"></span>
                  <button type="button" class="page-drop" @click="removePage(index)" aria-label="Убрать страницу">✕</button>
                </div>
              </template>
            </div>
          </template>

          <div class="photo-slot">
            <label class="photo-label" :class="pages.length && 'has-file'">
              <span class="photo-label-icon">📷</span>
              <span x-text="photoLabel()"></span>
              <input type="file" name="solution_photos[]" accept="image/*" multiple
                     x-ref="photoInput" @change="pickPhotos($event)">
            </label>
          </div>
          <div class="photo-hint" x-text="hintText()"></div>

          {{-- Ошибка отправки должна быть видна у своей задачи и без перезагрузки:
               в WebView ни редиректа, ни страницы-заглушки ученик не увидит. --}}
          <template x-if="error">
            <div class="task-error">
              <div x-text="error"></div>
              <template x-if="stale">
                <button type="button" class="task-error-btn" @click="window.location.reload()">Обновить страницу</button>
              </template>
            </div>
          </template>

          {{-- Кнопку не гасим на время загрузки фото: с телефона снимок едет
               десятки секунд, и мёртвая кнопка читается как «сдать нельзя».
               Нажатие в этот момент само дождётся догрузки внутри send(). --}}
          <button class="submit-btn" type="submit" :disabled="busy"
                  x-text="busy ? 'Отправляем…' : '{{ $needsRetry ? 'Отправить вторую попытку' : 'Отправить' }}'"></button>
        </form>
      @endif
    </div>
  @endforeach

  <template x-if="showPhotoModal">
    <div class="hw-modal-overlay" @click.self="showPhotoModal = false">
      <div class="hw-modal">
        <div class="hw-modal-icon">📷</div>
        <div class="hw-modal-title">Нужно фото решения</div>
        <div class="hw-modal-body">Без фото решения домашняя работа не принимается. Сфотографируй тетрадь с решением и прикрепи фото к ответу.</div>
        <button type="button" class="hw-modal-btn" @click="showPhotoModal = false">Понятно</button>
      </div>
    </div>
  </template>
</div>
@endsection

@push('scripts')
<script>
// Фото тетради с телефона весит 3–20 МБ: на мобильном интернете такая отправка
// долгая и часто рвётся. Поэтому перед отправкой ужимаем снимок в браузере до
// ~1600px/JPEG и сразу увозим в хранилище. Если сжатие невозможно (например,
// HEIC, который браузер не умеет декодировать) — отправляем оригинал,
// сервер его тоже принимает.
const HW_PHOTO_MAX_SIDE = 1600;
const HW_PHOTO_SKIP_BELOW = 900 * 1024;
const HW_PHOTO_MAX_PAGES = {{ \App\Models\HomeworkSolutionPhoto::MAX_PER_ATTEMPT }};
// Ни одно ожидание не должно быть бесконечным: в мобильном вебвью декодирование
// 12-мегапиксельного снимка и загрузка по слабой сети иногда не завершаются
// никогда. По таймауту просто идём дальше — фото уедет с формой как есть.
const HW_PHOTO_DECODE_TIMEOUT = 20000;
const HW_PHOTO_TICKET_TIMEOUT = 15000;
const HW_PHOTO_UPLOAD_TIMEOUT = 90000;

/** Ждёт промис не дольше ms; по таймауту отдаёт fallback, не ломая цепочку. */
function hwWithTimeout(promise, ms, fallback = null) {
  return new Promise(resolve => {
    let done = false;
    const finish = value => { if (!done) { done = true; resolve(value); } };
    const timer = setTimeout(() => finish(fallback), ms);
    Promise.resolve(promise)
      .then(value => { clearTimeout(timer); finish(value); })
      .catch(() => { clearTimeout(timer); finish(fallback); });
  });
}

const HW_PHOTO_SUBMIT_TIMEOUT = 30000;
// Черновик страниц: photo_id живут в hw-photos, поэтому после сбоя, случайной
// перезагрузки или протухшей сессии ученику не надо переснимать тетрадь.
const HW_PHOTO_DRAFT_TTL = 24 * 60 * 60 * 1000;

/**
 * Страницы решения одной задачи. Каждая страница — либо уже загруженная в
 * хранилище (есть remoteId), либо файл, который уйдёт на сервер обычной
 * отправкой. Смешивать нельзя: если хоть одна страница не загрузилась,
 * отправляем файлами всё — так учитель точно увидит решение целиком.
 *
 * Отправка — один явный сценарий: жмём кнопку → дожидаемся (и при нужде
 * повторяем) загрузку страниц → одним fetch шлём ответ и photo_id → показываем
 * результат. Нативной отправки формы в основном пути нет: в Telegram WebView
 * редирект и ответ сервера ученику не видны, и любой сбой выглядит как тишина.
 */
function taskPhotos(config) {
  return {
    submitUrl: config.submitUrl,
    ticketUrl: config.ticketUrl,
    logUrl: config.logUrl,
    draftKey: config.draftKey,
    pages: [],
    preparing: false,
    busy: false,
    nextKey: 1,
    error: '',
    // Сессия/токен протухли: помогает только перезагрузка, фото при этом целы.
    stale: false,
    // След того, что происходило на телефоне, — уедет на сервер вместе с итогом.
    trail: [],
    // Страница уходит на перезагрузку: кнопку обратно включать не надо.
    leaving: false,

    get allUploaded() {
      return this.pages.length > 0 && this.pages.every(p => !!p.remoteId);
    },

    photoLabel() {
      if (this.preparing) return 'Загружаем страницы…';
      if (!this.pages.length) return 'Прикрепить фото решения';
      if (this.pages.length >= HW_PHOTO_MAX_PAGES) return 'Больше страниц не влезет';
      return 'Добавить ещё страницу';
    },

    hintText() {
      if (!this.pages.length) {
        return 'Если решение на нескольких страницах — прикрепи их все, до ' + HW_PHOTO_MAX_PAGES + '.';
      }
      if (this.busy) {
        return 'Отправляем — не закрывай страницу.';
      }
      const left = HW_PHOTO_MAX_PAGES - this.pages.length;
      const base = 'Страниц: ' + this.pages.length + (left > 0 ? ', можно добавить ещё ' + left : ', это максимум');
      if (this.preparing) {
        return base + '. Фото ещё грузятся — можно жать «Отправить», дождёмся сами.';
      }
      return this.allUploaded ? base + '. Все загружены.' : base + '.';
    },

    pageState(page) {
      if (page.uploading) return 'загружаем…';
      return page.remoteId ? 'загружено' : 'уйдёт с формой';
    },

    async pickPhotos(event) {
      const input = event.target;
      const picked = Array.from(input.files || []);
      // Инпут — только способ выбрать: файлы держим у себя, иначе повторный
      // выбор затирает уже набранные страницы. Чистим только если сможем
      // положить их обратно — без DataTransfer фолбэку нечего будет отправлять.
      if (this.canRefillInput()) {
        input.value = '';
      }

      if (!picked.length) return;

      const room = HW_PHOTO_MAX_PAGES - this.pages.length;
      if (room <= 0) return;

      this.error = '';
      this.note('picked', Math.min(picked.length, room) + ' шт.');
      this.preparing = true;
      try {
        for (const file of picked.slice(0, room)) {
          this.pages.push({
            key: this.nextKey++,
            name: file.name || 'страница',
            file,
            remoteId: null,
            uploading: true,
          });

          // Дальше работаем с реактивной ссылкой из pages: правки исходного
          // объекта Alpine не видит, и строка залипает на «загружаем…».
          const page = this.pages[this.pages.length - 1];

          try {
            page.file = await this.compressPhoto(file);
          } catch (e) {
            page.file = file;
          }

          try {
            page.remoteId = await this.uploadToStore(page.file);
          } finally {
            // Строка страницы не должна залипнуть в «загружаем…», что бы ни случилось.
            page.uploading = false;
          }
        }
      } finally {
        this.preparing = false;
        this.saveDraft();
      }
    },

    removePage(index) {
      this.pages.splice(index, 1);
      this.error = '';
      this.saveDraft();
    },

    canRefillInput() {
      return typeof DataTransfer !== 'undefined';
    },

    /**
     * Отправка ответа.
     *
     * Один проход без скрытой магии: проверили ввод → догрузили недостающие
     * страницы → отправили JSON → показали ответ сервера. Кнопку разблокируем
     * в finally, что бы ни случилось: залипшая «Отправляем…» для ученика
     * неотличима от поломки.
     */
    async send() {
      if (this.busy) return;

      this.error = '';
      this.stale = false;
      this.leaving = false;

      const answer = (this.$refs.answer?.value || '').trim();
      if (!answer) {
        this.error = 'Впиши ответ.';
        this.$refs.answer?.focus();
        return;
      }
      if (!this.pages.length) {
        // Модалка живёт во внешнем компоненте страницы — до него событие всплывёт.
        this.$dispatch('hw-need-photo');
        return;
      }

      this.busy = true;
      this.note('submit_start', this.pages.length + ' стр.');

      try {
        await this.ensureUploaded();

        if (!this.allUploaded) {
          // Хранилище недоступно — уходим обычной отправкой формы с файлами.
          this.note('fallback_native');
          this.report('fallback');
          if (this.fillFallbackInput()) {
            this.leaving = true;
            this.$refs.form.submit();   // напрямую: обработчик @submit нам здесь не нужен
            return;
          }
          this.error = 'Не получилось загрузить фото. Проверь интернет и попробуй ещё раз.';
          return;
        }

        const response = await this.withDeadline(
          signal => fetch(this.submitUrl, {
            method: 'POST',
            credentials: 'include',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window._csrf,
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ answer, photo_ids: this.pages.map(p => p.remoteId) }),
            signal,
          }),
          HW_PHOTO_SUBMIT_TIMEOUT,
        );

        if (!response) {
          this.note('submit_timeout');
          this.error = 'Сервер не ответил. Фото сохранены — попробуй отправить ещё раз.';
          this.report('timeout');
          return;
        }

        this.note('submit_http', response.status);

        // 419/401 — протухшая сессия или CSRF-токен. Раньше это приезжало
        // страницей-заглушкой, которую ученик в WebView просто не видел.
        if (response.status === 419 || response.status === 401 || response.redirected) {
          this.stale = true;
          this.error = 'Страница устарела. Обнови её и отправь ещё раз — фото уже сохранены, переснимать не нужно.';
          this.report('stale');
          return;
        }

        const body = await response.json().catch(() => null);

        if (!body) {
          this.error = 'Сервер ответил непонятно (код ' + response.status + '). Попробуй ещё раз.';
          this.report('bad_body');
          return;
        }

        if (body.reload) {
          // Состояние задачи изменилось, сообщение сервер положил во флеш.
          this.dropDraft();
          this.leaving = true;
          this.report(body.ok ? 'ok' : 'retry');
          window.location.reload();
          return;
        }

        if (body.code === 'photo_rejected') {
          // Эти photo_id больше ничего не стоят — повторная отправка тех же
          // даст ту же ошибку, проще честно попросить фото заново.
          this.pages = [];
          this.dropDraft();
        }

        this.error = body.message || 'Не получилось отправить. Попробуй ещё раз.';
        this.report('rejected');
      } catch (e) {
        this.note('submit_error', (e && e.name) === 'AbortError' ? 'оборвано' : (e && e.message));
        this.error = 'Не получилось отправить ответ — похоже, пропал интернет. Фото сохранены, попробуй ещё раз.';
        this.report('network');
      } finally {
        if (!this.leaving) this.busy = false;
      }
    },

    /** Дотягивает страницы, которые не доехали в хранилище с первого раза. */
    async ensureUploaded() {
      const pending = this.pages.filter(p => !p.remoteId && p.file);
      if (!pending.length) return;

      this.preparing = true;
      try {
        for (const page of pending) {
          page.uploading = true;
          try {
            page.remoteId = await this.uploadToStore(page.file);
            this.note(page.remoteId ? 'reupload_ok' : 'reupload_failed', page.name);
          } finally {
            page.uploading = false;
          }
        }
      } finally {
        this.preparing = false;
        this.saveDraft();
      }
    },

    /**
     * Фолбэк: кладёт файлы обратно в инпут, чтобы форма ушла как обычный
     * multipart. Работает, только когда файлы есть у всех страниц.
     */
    fillFallbackInput() {
      const input = this.$refs.photoInput;
      if (!input) return false;

      const files = this.pages.map(p => p.file).filter(Boolean);
      if (files.length !== this.pages.length) return false;

      if (!this.canRefillInput()) {
        // Без DataTransfer инпут держит то, что выбрал ученик, — этого хватит.
        return !!(input.files && input.files.length);
      }

      const dt = new DataTransfer();
      for (const file of files) dt.items.add(file);
      input.files = dt.files;
      return dt.files.length > 0;
    },

    restoreDraft() {
      try {
        const raw = localStorage.getItem(this.draftKey);
        if (!raw) return;

        const draft = JSON.parse(raw);
        if (!draft || !Array.isArray(draft.pages) || Date.now() - (draft.at || 0) > HW_PHOTO_DRAFT_TTL) {
          this.dropDraft();
          return;
        }

        this.pages = draft.pages
          .filter(p => p && p.remoteId)
          .slice(0, HW_PHOTO_MAX_PAGES)
          .map(p => ({
            key: this.nextKey++,
            name: p.name || 'страница',
            file: null,          // байтов больше нет, но они уже в хранилище
            remoteId: p.remoteId,
            uploading: false,
          }));
      } catch (e) {
        // Черновик — удобство, а не условие работы.
      }
    },

    saveDraft() {
      try {
        const ready = this.pages
          .filter(p => p.remoteId)
          .map(p => ({ name: p.name, remoteId: p.remoteId }));

        if (!ready.length) {
          this.dropDraft();
          return;
        }
        localStorage.setItem(this.draftKey, JSON.stringify({ at: Date.now(), pages: ready }));
      } catch (e) {
        // Приватный режим/переполненное хранилище — не повод ломать отправку.
      }
    },

    dropDraft() {
      try {
        localStorage.removeItem(this.draftKey);
      } catch (e) {}
    },

    note(step, detail) {
      this.trail.push({
        at: new Date().toTimeString().slice(0, 8),
        step,
        detail: detail == null ? '' : String(detail).slice(0, 200),
      });
      if (this.trail.length > 40) this.trail.shift();
    },

    /**
     * Отдаёт накопленный след на сервер. Половина сценария выполняется на
     * телефоне, и без этого следа сбой у ученика не оставляет вообще никаких
     * следов — ни в базе, ни в логах.
     */
    report(outcome) {
      const trail = this.trail.splice(0, this.trail.length);
      if (!trail.length) return;

      try {
        window.fetchPost(this.logUrl, {
          outcome,
          trail,
          ua: (navigator.userAgent || '').slice(0, 200),
        }).catch(() => {});
      } catch (e) {}
    },

    /** @returns {Promise<string|null>} photo_id или null, если нужно уйти на фолбэк */
    async uploadToStore(file) {
      try {
        // Не через window.fetchPost: тому нельзя передать signal, а тикет тоже
        // должен уметь оборваться по сроку.
        const ticketResponse = await this.withDeadline(
          signal => fetch(this.ticketUrl, {
            method: 'POST',
            credentials: 'include',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window._csrf,
              'Accept': 'application/json',
            },
            body: '{}',
            signal,
          }),
          HW_PHOTO_TICKET_TIMEOUT,
        );
        if (!ticketResponse) {
          this.note('ticket_timeout');
          return null;
        }
        if (!ticketResponse.ok) {
          this.note('ticket_http', ticketResponse.status);
          return null;
        }

        const ticket = await ticketResponse.json();
        if (!ticket.enabled || !ticket.upload_url || !ticket.token) {
          this.note('ticket_off');
          return null;
        }

        const form = new FormData();
        form.append('photo', file, file.name || 'solution.jpg');

        const uploaded = await this.withDeadline(
          signal => fetch(ticket.upload_url, {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + ticket.token },
            body: form,
            signal,
          }),
          HW_PHOTO_UPLOAD_TIMEOUT,
        );
        if (!uploaded) {
          this.note('upload_timeout', file.size);
          return null;
        }
        if (!uploaded.ok) {
          this.note('upload_http', uploaded.status);
          return null;
        }

        const body = await uploaded.json();
        if (!body.photo_id) this.note('upload_no_id');
        return body.photo_id || null;
      } catch (e) {
        this.note('upload_error', (e && e.name) === 'AbortError' ? 'оборвано' : (e && e.message));
        return null;
      }
    },

    /**
     * Запрос с жёстким сроком: на слабой сети fetch может висеть до последнего,
     * а ученик всё это время смотрит на «загружаем…». По сроку рвём соединение
     * и уходим на фолбэк — файл уедет обычной отправкой формы.
     */
    async withDeadline(run, ms) {
      const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
      const timer = setTimeout(() => controller?.abort(), ms);
      try {
        return await hwWithTimeout(run(controller?.signal), ms + 1000);
      } finally {
        clearTimeout(timer);
      }
    },

    async compressPhoto(file) {
      if (!file.type.startsWith('image/') || file.size <= HW_PHOTO_SKIP_BELOW) return file;

      // Декодирование большого снимка в мобильном вебвью иногда не возвращается
      // вовсе — тогда шлём оригинал, лишь бы ученик не завис на «загружаем…».
      const source = await hwWithTimeout(this.decodeImage(file), HW_PHOTO_DECODE_TIMEOUT);
      if (!source) return file;

      const scale = Math.min(1, HW_PHOTO_MAX_SIDE / Math.max(source.width, source.height));
      const width = Math.round(source.width * scale);
      const height = Math.round(source.height * scale);

      const canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;
      canvas.getContext('2d').drawImage(source, 0, 0, width, height);

      const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.82));
      if (!blob || blob.size >= file.size) return file;

      const base = (file.name || 'solution').replace(/\.[^.]+$/, '') || 'solution';
      return new File([blob], base + '.jpg', { type: 'image/jpeg' });
    },

    async decodeImage(file) {
      if (window.createImageBitmap) {
        try {
          return await createImageBitmap(file, { imageOrientation: 'from-image' });
        } catch (e) {
          // Safari/старые движки — падаем на <img>
        }
      }

      const url = URL.createObjectURL(file);
      try {
        return await new Promise((resolve, reject) => {
          const img = new Image();
          img.onload = () => resolve(img);
          img.onerror = reject;
          img.src = url;
        });
      } catch (e) {
        return null;
      } finally {
        URL.revokeObjectURL(url);
      }
    },
  };
}

function hwTopicPractice() {
  return {
    showPhotoModal: false,
  };
}
</script>
@endpush
