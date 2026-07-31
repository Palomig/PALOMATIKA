@extends('layouts.pwa')
@section('title', 'Домашка — palomatika')

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
<div class="page" x-data="hwTopicPractice()">
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
        <form class="task-form" method="POST" action="{{ route('pwa.student.homework.topic.submit', [$assignment, $task]) }}" enctype="multipart/form-data"
              x-data="taskPhotos('{{ route('pwa.student.homework.topic.photo-ticket', [$assignment, $task]) }}')"
              @submit="onTaskFormSubmit($event, $data)">
          @csrf
          {{-- Ответ возвращаем только той задаче, из которой пришла ошибка. --}}
          <input class="task-input" type="text" name="answer" placeholder="Ответ" required
                 value="{{ (int) session('answer_task_id') === (int) $task->id ? old('answer') : '' }}">
          {{-- Сюда JS складывает id уже загруженных страниц; тогда файлы на сервер не идут. --}}
          <div x-ref="photoIds" hidden></div>

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

          <button class="submit-btn" type="submit" :disabled="busy || preparing"
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

/**
 * Страницы решения одной задачи. Каждая страница — либо уже загруженная в
 * хранилище (есть remoteId), либо файл, который уйдёт на сервер обычной
 * отправкой. Смешивать нельзя: если хоть одна страница не загрузилась,
 * отправляем файлами всё — так учитель точно увидит решение целиком.
 */
function taskPhotos(ticketUrl) {
  return {
    ticketUrl,
    pages: [],
    preparing: false,
    busy: false,
    nextKey: 1,

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
      const left = HW_PHOTO_MAX_PAGES - this.pages.length;
      const base = 'Страниц: ' + this.pages.length + (left > 0 ? ', можно добавить ещё ' + left : ', это максимум');
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
        try {
          this.syncForm();
        } catch (e) {
          // Форма важнее красоты: пусть уйдёт как есть, чем ученик застрянет.
        }
      }
    },

    removePage(index) {
      this.pages.splice(index, 1);
      this.syncForm();
    },

    canRefillInput() {
      return typeof DataTransfer !== 'undefined';
    },

    /**
     * Приводит форму в соответствие набранным страницам: либо скрытые photo_ids
     * (всё уже в хранилище), либо файлы в инпуте (фолбэк).
     *
     * Элементы берём через $refs, а НЕ через $el: внутри обработчика @change
     * Alpine подставляет в $el сам инпут, и поиск по форме возвращает null.
     */
    syncForm() {
      const holder = this.$refs.photoIds;
      const input = this.$refs.photoInput;
      if (!holder || !input) return;

      holder.replaceChildren();

      if (this.allUploaded) {
        for (const page of this.pages) {
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = 'photo_ids[]';
          hidden.value = page.remoteId;
          holder.appendChild(hidden);
        }
        if (this.canRefillInput()) {
          input.files = new DataTransfer().files;   // не гнать те же байты второй раз
        }
        return;
      }

      if (this.canRefillInput()) {
        const dt = new DataTransfer();
        for (const page of this.pages) {
          if (page.file) dt.items.add(page.file);
        }
        input.files = dt.files;
      }
    },

    /** @returns {Promise<string|null>} photo_id или null, если нужно уйти на фолбэк */
    async uploadToStore(file) {
      try {
        const ticketResponse = await window.fetchPost(this.ticketUrl);
        if (!ticketResponse.ok) return null;

        const ticket = await ticketResponse.json();
        if (!ticket.enabled || !ticket.upload_url || !ticket.token) return null;

        const form = new FormData();
        form.append('photo', file, file.name || 'solution.jpg');

        const uploaded = await fetch(ticket.upload_url, {
          method: 'POST',
          headers: { 'Authorization': 'Bearer ' + ticket.token },
          body: form,
        });
        if (!uploaded.ok) return null;

        const body = await uploaded.json();
        return body.photo_id || null;
      } catch (e) {
        return null;
      }
    },

    async compressPhoto(file) {
      if (!file.type.startsWith('image/') || file.size <= HW_PHOTO_SKIP_BELOW) return file;

      const source = await this.decodeImage(file);
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

    onTaskFormSubmit(event, scope) {
      if (scope.preparing) {
        event.preventDefault();
        return;
      }
      if (!scope.pages.length) {
        event.preventDefault();
        this.showPhotoModal = true;
        return;
      }
      // Отправка занимает секунды — блокируем повторные тапы.
      scope.busy = true;
    },
  };
}
</script>
@endpush
