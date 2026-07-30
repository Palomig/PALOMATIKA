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
              x-data="{ hasFile: false, preparing: false, busy: false }" @submit="onTaskFormSubmit($event, $data)">
          @csrf
          {{-- Ответ возвращаем только той задаче, из которой пришла ошибка. --}}
          <input class="task-input" type="text" name="answer" placeholder="Ответ" required
                 value="{{ (int) session('answer_task_id') === (int) $task->id ? old('answer') : '' }}">
          <div class="photo-slot">
            <label class="photo-label" :class="hasFile && 'has-file'">
              <span class="photo-label-icon">📷</span>
              <span x-text="preparing ? 'Готовим фото…' : (hasFile ? 'Фото решения прикреплено' : 'Прикрепить фото решения')"></span>
              <input type="file" name="solution_photo" accept="image/*"
                     @change="pickPhoto($event, $data)">
            </label>
          </div>
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
// ~1600px/JPEG. Если сжатие невозможно (например, HEIC, который браузер не умеет
// декодировать) — отправляем оригинал, сервер его тоже принимает.
const HW_PHOTO_MAX_SIDE = 1600;
const HW_PHOTO_SKIP_BELOW = 900 * 1024;

function hwTopicPractice() {
  return {
    showPhotoModal: false,

    async pickPhoto(event, scope) {
      const input = event.target;
      const file = input.files && input.files[0];
      scope.hasFile = !!file;

      if (!file) return;

      scope.preparing = true;
      try {
        const compressed = await this.compressPhoto(file);
        if (compressed !== file && typeof DataTransfer !== 'undefined') {
          const dt = new DataTransfer();
          dt.items.add(compressed);
          input.files = dt.files;
        }
      } catch (e) {
        // остаётся оригинальный файл
      } finally {
        scope.preparing = false;
        scope.hasFile = !!(input.files && input.files.length);
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

      const base = file.name.replace(/\.[^.]+$/, '') || 'solution';
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

    onTaskFormSubmit(event, scope) {
      if (scope.preparing) {
        event.preventDefault();
        return;
      }
      if (!scope.hasFile) {
        event.preventDefault();
        this.showPhotoModal = true;
        return;
      }
      // Отправка большого фото занимает секунды — блокируем повторные тапы.
      scope.busy = true;
    },
  };
}
</script>
@endpush
