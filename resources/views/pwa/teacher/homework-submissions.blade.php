@extends('layouts.pwa')
@section('title', 'Проверка домашки — palomatika')

@push('katex')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/contrib/auto-render.min.js"></script>
@endpush

@push('styles')
  .topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
  }
  .back { color: var(--text); text-decoration: none; font-size: 18px; padding: 6px 8px; border: 1px solid var(--border); border-radius: 10px; }
  .topbar-title { font-family: var(--display); font-size: 18px; color: var(--text); }

  .notice { margin-bottom: 10px; padding: 10px 12px; border-radius: 10px; font-size: 13px; font-weight: 700; }
  .notice-ok { color: #86efac; background: rgba(34,197,94,.14); border: 1px solid rgba(34,197,94,.24); }
  .notice-error { color: #fecaca; background: rgba(239,68,68,.14); border: 1px solid rgba(239,68,68,.24); }

  .sub-summary {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 14px 16px; margin-bottom: 12px;
  }
  .sub-summary-title { font-family: var(--display); font-size: 16px; color: var(--text); }
  .sub-summary-meta { margin-top: 4px; font-size: 12px; color: var(--muted); font-weight: 700; }
  .summary-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
  .badge {
    font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;
    padding: 3px 8px; border-radius: 6px; white-space: nowrap;
  }
  .badge-reviewed { color: #86efac; background: rgba(34,197,94,.2); }
  .badge-debt { color: #fcd34d; background: rgba(234,179,8,.2); }
  .badge-progress { color: var(--muted); background: var(--surface2); }

  .summary-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
  .act-btn {
    border: 1px solid var(--border); background: var(--surface2); color: var(--text);
    border-radius: 10px; padding: 9px 13px; font-size: 12px; font-weight: 800; cursor: pointer;
  }
  .act-btn:active { opacity: .75; }
  .act-primary { background: var(--accent); border-color: transparent; color: #fff; }

  .sub-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 14px 16px; margin-bottom: 10px;
  }
  .sub-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
  .sub-head-right { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
  .sub-head-right form { margin: 0; }

  /* Вторая стадия проверки: что из этой домашки идёт в повестку урока. */
  .review-counter {
    margin-top: 10px; padding: 8px 11px; border-radius: 10px;
    font-size: 12px; font-weight: 800;
    color: var(--purple); background: var(--purple-bg); border: 1px solid var(--purple-bd);
  }
  .review-toggle {
    border: 1px solid var(--border); background: var(--surface2); color: var(--muted);
    border-radius: 8px; padding: 4px 9px; font-size: 11px; font-weight: 800;
    cursor: pointer; white-space: nowrap; font-family: inherit;
  }
  .review-toggle:active { opacity: .75; }
  .review-toggle.is-on { background: var(--purple); border-color: transparent; color: #fff; }
  .review-toggle:disabled { opacity: .5; cursor: default; }
  .review-resolved { font-size: 10px; font-weight: 800; color: var(--muted); white-space: nowrap; }
  /* Кандидат на разбор: ученик ошибся или не приступил. Полоса нужна, чтобы
     при прокрутке дюжины задач было видно, куда смотреть. Только подсказка —
     отметку всё равно ставит учитель. */
  .sub-card.is-candidate { border-left: 3px solid var(--yellow); }
  .sub-num { font-family: var(--display); font-size: 14px; color: var(--text); }
  .sub-state { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; padding: 3px 8px; border-radius: 6px; white-space: nowrap; }
  .state-wait { color: var(--muted); background: var(--surface2); }
  .state-correct { color: #86efac; background: rgba(34,197,94,.2); }
  .state-wrong { color: #fecaca; background: rgba(239,68,68,.2); }

  .sub-text { color: var(--text); font-size: 13px; line-height: 1.45; overflow-wrap: anywhere; }
  .sub-text .katex { font-size: 1.05em; }
  .sub-visual { margin: 6px 0; display: flex; justify-content: center; }
  .sub-visual svg { max-width: 100%; width: auto; height: auto; display: block; }

  .answer-row { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; font-size: 12px; font-weight: 700; }
  .answer-chip {
    padding: 4px 9px; border-radius: 8px;
    background: var(--surface2); border: 1px solid var(--border); color: var(--text);
  }
  .answer-chip.correct { color: #86efac; border-color: rgba(34,197,94,.3); background: rgba(34,197,94,.1); }
  .answer-chip.wrong { color: #fecaca; border-color: rgba(239,68,68,.3); background: rgba(239,68,68,.1); }
  .answer-chip-label { color: var(--muted); font-weight: 600; }

  .attempt-label { margin: 12px 0 6px; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
  .pages-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 8px; }
  .page-tile {
    position: relative; display: block; width: 100%;
    padding: 0; border: none; background: none; cursor: zoom-in;
  }
  .page-tile img {
    width: 100%; aspect-ratio: 3 / 4; object-fit: cover;
    border-radius: 10px; border: 1px solid var(--border); display: block;
  }
  .page-tile:active img { opacity: .8; }
  .page-tile-num {
    position: absolute; left: 6px; bottom: 6px;
    background: rgba(0,0,0,.65); color: #fff;
    font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 5px;
  }

  {{-- Стили просмотрщика тетради живут в pwa._shared.photo-viewer --}}
  .no-photo { margin-top: 10px; font-size: 12px; color: var(--muted); font-weight: 700; }

  .note-form { margin-top: 10px; display: grid; gap: 8px; }
  .note-input, .note-select {
    width: 100%; padding: 10px 12px; border-radius: 10px;
    border: 1px solid var(--border); background: var(--surface2);
    color: var(--text); font-size: 13px; font-family: inherit;
  }
  .note-input { min-height: 64px; resize: vertical; }
  .note-add {
    border: none; border-radius: 10px; padding: 10px 14px;
    background: var(--accent); color: #fff; font-weight: 900; font-size: 12px; cursor: pointer;
  }
  .note-toggle {
    margin-top: 10px; border: 1px dashed var(--border); background: none;
    color: var(--muted); border-radius: 10px; padding: 8px 12px;
    font-size: 12px; font-weight: 800; cursor: pointer; width: 100%;
  }
  .notes-list { display: grid; gap: 8px; margin-top: 10px; }
  .note-item {
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 10px; padding: 10px 12px;
  }
  .note-body { font-size: 13px; color: var(--text); line-height: 1.4; overflow-wrap: anywhere; }
  .note-meta { margin-top: 4px; font-size: 11px; color: var(--muted); font-weight: 700; display: flex; flex-wrap: wrap; gap: 6px; }
  .note-kind { padding: 2px 6px; border-radius: 5px; background: var(--surface); }
  .kind-weakness { color: #fcd34d; }
  .kind-todo { color: #93bbfd; }
  .kind-strength { color: #86efac; }
  .sec-label { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin: 16px 0 8px; }
@endpush

@section('body')
@php
  $isDebt = $assignment->isDebt();
  $isReviewed = $assignment->reviewed_at !== null;

  // Плоский список всех страниц домашки: просмотрщик листает их подряд,
  // поэтому индекс страницы известен уже при отрисовке плитки.
  $viewerPhotos = [];
  $viewerIndex = [];
  foreach ($homework->topicTasks as $viewerTask) {
      $viewerSubmission = $submissions->get($viewerTask->id);
      if (!$viewerSubmission) {
          continue;
      }
      foreach ($viewerSubmission->photos->groupBy('attempt_no') as $viewerAttemptNo => $viewerPages) {
          foreach ($viewerPages->values() as $viewerPageNo => $viewerPhoto) {
              $viewerIndex[$viewerPhoto->id] = count($viewerPhotos);
              $viewerPhotos[] = [
                  'src' => route('pwa.teacher.homework.solution-photo', [$viewerPhoto, 'w' => 1600]),
                  'full' => route('pwa.teacher.homework.solution-photo', $viewerPhoto),
                  'label' => 'Задача ' . $viewerTask->task_order
                      . ' · ' . ((int) $viewerAttemptNo === 2 ? 'вторая попытка' : 'первая попытка')
                      . ' · стр. ' . ($viewerPageNo + 1),
              ];
          }
      }
  }

  // Отметки «разобрать» держим в Alpine: перезагрузка страницы после каждой
  // галочки сбрасывала скролл в начало, а отмечают их подряд по всему списку.
  $reviewFlags = [];
  foreach ($homework->topicTasks as $flagTask) {
      $flagItem = $reviewItems->get($flagTask->id);
      $reviewFlags[$flagTask->id] = $flagItem !== null
          && in_array($flagItem->status, \App\Models\HomeworkReviewItem::ACTIVE_STATUSES, true);
  }
  $pendingReview = count(array_filter($reviewFlags));
@endphp
<div class="page"
     x-data="{
       noteFor: null,
       photos: @js($viewerPhotos),
       viewer: false,
       vi: 0,
       open(i) { this.vi = i; this.viewer = true; document.body.style.overflow = 'hidden'; },
       close() { this.viewer = false; document.body.style.overflow = ''; },
       step(d) { this.vi = (this.vi + d + this.photos.length) % this.photos.length; },

       flags: @js((object) $reviewFlags),
       busy: {},
       get reviewCount() { return Object.values(this.flags).filter(Boolean).length; },

       /**
        * Переключение отметки без перезагрузки. Состояние меняем сразу, а при
        * ошибке откатываем: учитель отмечает задачи подряд, ждать ответ сервера
        * на каждой галочке — терять темп.
        */
       async toggleReview(taskId) {
         if (this.busy[taskId]) return;
         const next = !this.flags[taskId];
         this.busy[taskId] = true;
         this.flags[taskId] = next;
         try {
           // Путь относительный: маршрут живёт на teacher-поддомене, а url()
           // построил бы его от APP_URL, то есть от главного домена.
           const r = await fetch(`/homework/assignment/{{ $assignment->id }}/tasks/${taskId}/review`, {
             method: 'POST',
             headers: {
               'Content-Type': 'application/json',
               'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
               Accept: 'application/json',
             },
             credentials: 'include',
             body: JSON.stringify({ on: next }),
           });
           if (!r.ok) throw new Error('http ' + r.status);
         } catch (e) {
           this.flags[taskId] = !next;
           alert('Не удалось изменить отметку. Проверь связь и попробуй ещё раз.');
         } finally {
           this.busy[taskId] = false;
         }
       }
     }"
     @keydown.escape.window="viewer && close()"
     @keydown.arrow-left.window="viewer && step(-1)"
     @keydown.arrow-right.window="viewer && step(1)">
  <div class="topbar">
    <a href="{{ route('pwa.teacher.homework') }}" class="back">←</a>
    <div class="topbar-title">Проверка</div>
    <div style="width:34px;"></div>
  </div>

  @if(session('success'))
    <div class="notice notice-ok">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="notice notice-error">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="notice notice-error">{{ $errors->first() }}</div>
  @endif

  <div class="sub-summary">
    <div class="sub-summary-title">{{ $assignment->student?->name ?? 'Ученик' }}</div>
    <div class="sub-summary-meta">
      {{ $homework->title ?: 'Домашнее задание' }} · {{ $assignment->tasks_completed }} из {{ $assignment->tasks_total }} принято
    </div>
    <div class="summary-badges">
      @if($isReviewed)
        <span class="badge badge-reviewed">Проверено {{ $assignment->reviewed_at->format('d.m.Y') }}</span>
      @endif
      @if($isDebt)
        <span class="badge badge-debt">Долг с {{ $assignment->debt_since->format('d.m.Y') }}</span>
      @endif
      <span class="badge badge-progress">{{ $assignment->status }}</span>
    </div>

    <div class="review-counter" x-show="reviewCount > 0" @if($pendingReview === 0) style="display:none" @endif>
      К разбору: <span x-text="reviewCount">{{ $pendingReview }}</span>
    </div>

    <div class="summary-actions">
      <form method="POST" action="{{ route('pwa.teacher.homework.reviewed', $assignment) }}">
        @csrf
        <button class="act-btn {{ $isReviewed ? '' : 'act-primary' }}" type="submit">
          {{ $isReviewed ? 'Снять «проверено»' : 'Отметить проверенным' }}
        </button>
      </form>
      <form method="POST" action="{{ route('pwa.teacher.homework.debt', $assignment) }}">
        @csrf
        <button class="act-btn" type="submit">{{ $assignment->debt_since ? 'Снять долг' : 'Пометить долгом' }}</button>
      </form>
    </div>
  </div>

  @foreach($homework->topicTasks as $task)
    @php
      $payload = $task->task_payload ?? [];
      $submission = $submissions->get($task->id);
      $text = $payload['text_html'] ?? $payload['text'] ?? $payload['html'] ?? $payload['question'] ?? $payload['expression'] ?? 'Задача';
      $svg = $payload['svg'] ?? null;
      $hasInlineSvg = is_string($svg) && str_contains($svg, '<svg');
      $photosByAttempt = $submission ? $submission->photos->groupBy('attempt_no') : collect();

      if (!$submission) {
          $stateClass = 'state-wait';
          $stateLabel = 'Не сдано';
      } elseif ($submission->is_correct) {
          $stateClass = 'state-correct';
          $stateLabel = 'Верно';
      } else {
          $stateClass = 'state-wrong';
          $stateLabel = $submission->accepted_at ? 'Неверно' : 'Ещё пробует';
      }

      $reviewItem = $reviewItems->get($task->id);
      $isFlagged = $reviewFlags[$task->id] ?? false;
      $isResolved = $reviewItem !== null && $reviewItem->status === \App\Models\HomeworkReviewItem::STATUS_DONE;
      // Кандидат — задача, где ученик ошибся или не приступил. Подсвечиваем,
      // но не отмечаем: что разбирать, решает учитель. Отмеченную не подсвечиваем —
      // условие реактивное, полоса гаснет сразу при нажатии.
      $isCandidate = !$isResolved && ($submission === null || !$submission->is_correct);
    @endphp

    <div class="sub-card {{ $isCandidate && !$isFlagged ? 'is-candidate' : '' }}"
         @if($isCandidate) :class="flags[{{ $task->id }}] ? '' : 'is-candidate'" @endif>
      <div class="sub-head">
        <div class="sub-num">Задача {{ $task->task_order }}</div>
        <div class="sub-head-right">
          @if($isResolved)
            <span class="review-resolved">разобрано {{ $reviewItem->resolved_at?->format('d.m') }}</span>
          @endif
          <button type="button" class="review-toggle {{ $isFlagged ? 'is-on' : '' }}"
                  :class="flags[{{ $task->id }}] ? 'is-on' : ''"
                  :disabled="!!busy[{{ $task->id }}]"
                  @click="toggleReview({{ $task->id }})"
                  x-text="flags[{{ $task->id }}] ? '✓ Разобрать' : '+ Разобрать'">{{ $isFlagged ? '✓ Разобрать' : '+ Разобрать' }}</button>
          <div class="sub-state {{ $stateClass }}">{{ $stateLabel }}</div>
        </div>
      </div>

      @if($hasInlineSvg)
        <div class="sub-visual">{!! $svg !!}</div>
      @endif

      <div class="sub-text math-scope">{!! $text !!}</div>

      <div class="answer-row math-scope">
        <span class="answer-chip"><span class="answer-chip-label">эталон:</span> {{ $task->correct_answer }}</span>
        @if($submission?->first_answer !== null)
          <span class="answer-chip {{ $submission->attempts_count === 1 && $submission->is_correct ? 'correct' : ($submission->second_answer !== null || !$submission->is_correct ? 'wrong' : '') }}">
            <span class="answer-chip-label">1-я попытка:</span> {{ $submission->first_answer }}
          </span>
        @endif
        @if($submission?->second_answer !== null)
          <span class="answer-chip {{ $submission->is_correct ? 'correct' : 'wrong' }}">
            <span class="answer-chip-label">2-я попытка:</span> {{ $submission->second_answer }}
          </span>
        @endif
      </div>

      @forelse($photosByAttempt as $attemptNo => $pages)
        @php
          // app.locale = 'en', поэтому русское склонение считаем руками.
          $n = $pages->count();
          $mod100 = $n % 100;
          $mod10 = $n % 10;
          $pageWord = ($mod100 >= 11 && $mod100 <= 14) ? 'страниц'
              : ($mod10 === 1 ? 'страница' : ($mod10 >= 2 && $mod10 <= 4 ? 'страницы' : 'страниц'));
        @endphp
        <div class="attempt-label">
          {{ $attemptNo == 2 ? 'Вторая попытка' : 'Первая попытка' }} · {{ $n }} {{ $pageWord }}
        </div>
        <div class="pages-grid">
          @foreach($pages as $i => $photo)
            <button type="button" class="page-tile" @click="open({{ $viewerIndex[$photo->id] }})"
                    aria-label="Открыть страницу {{ $i + 1 }}">
              <img src="{{ route('pwa.teacher.homework.solution-photo', [$photo, 'w' => 400]) }}" alt="Страница решения {{ $i + 1 }}" loading="lazy">
              <span class="page-tile-num">{{ $i + 1 }}</span>
            </button>
          @endforeach
        </div>
      @empty
        <div class="no-photo">
          {{ $submission ? 'Фото решения не сохранилось.' : 'Ученик ещё не отправлял решение.' }}
        </div>
      @endforelse

      {{-- Заметка по конкретной задаче: то, что ученик не понял, видно именно здесь. --}}
      <button type="button" class="note-toggle" @click="noteFor = noteFor === {{ $task->id }} ? null : {{ $task->id }}"
              x-text="noteFor === {{ $task->id }} ? 'Свернуть заметку' : '+ заметка по задаче {{ $task->task_order }}'"></button>
      <template x-if="noteFor === {{ $task->id }}">
        <form class="note-form" method="POST" action="{{ route('pwa.teacher.homework.note', $assignment) }}">
          @csrf
          <input type="hidden" name="task_ref" value="Задача {{ $task->task_order }}">
          <textarea class="note-input" name="body" required
                    placeholder="Что не понимает: например «не видит подобие, путает высоту и медиану»"></textarea>
          <select class="note-select" name="kind">
            <option value="weakness">Не понимает</option>
            <option value="todo">Разобрать на уроке</option>
            <option value="strength">Получается хорошо</option>
            <option value="general">Просто заметка</option>
          </select>
          <button class="note-add" type="submit">Сохранить заметку</button>
        </form>
      </template>
    </div>
  @endforeach

  <div class="sec-label">Заметки по ученику</div>

  <div class="sub-card">
    <form class="note-form" method="POST" action="{{ route('pwa.teacher.homework.note', $assignment) }}">
      @csrf
      <textarea class="note-input" name="body" required
                placeholder="Общая заметка по этой домашке"></textarea>
      <select class="note-select" name="kind">
        <option value="weakness">Не понимает</option>
        <option value="todo">Разобрать на уроке</option>
        <option value="strength">Получается хорошо</option>
        <option value="general">Просто заметка</option>
      </select>
      <button class="note-add" type="submit">Сохранить заметку</button>
    </form>

    @if($notes->isNotEmpty())
      <div class="notes-list">
        @foreach($notes as $note)
          <div class="note-item">
            <div class="note-body">{{ $note->body }}</div>
            <div class="note-meta">
              <span class="note-kind kind-{{ $note->kind }}">{{ ['weakness' => 'не понимает', 'todo' => 'разобрать', 'strength' => 'хорошо', 'general' => 'заметка'][$note->kind] ?? $note->kind }}</span>
              @if($note->task_ref)<span>{{ $note->task_ref }}</span>@endif
              @if((int) $note->homework_assignment_id === (int) $assignment->id)<span>эта домашка</span>@endif
              <span>{{ $note->created_at?->format('d.m.Y') }}</span>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="no-photo">Заметок пока нет. Всё записанное здесь появится и в карточке ученика.</div>
    @endif
  </div>

  @include('pwa._shared.photo-viewer')
</div>
@endsection

@push('scripts')
<script>
  // Условия задач приходят с LaTeX внутри $…$ — без этого учитель видит сырой код.
  // Рендерим точечно по .math-scope, а не по всему body: в заметках учителя
  // доллар — это доллар, ломать его KaTeX-ом нельзя.
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof renderMathInElement !== 'function') return;
    document.querySelectorAll('.math-scope').forEach(function (el) {
      renderMathInElement(el, {
        delimiters: [
          { left: '$$', right: '$$', display: true },
          { left: '\\[', right: '\\]', display: true },
          { left: '\\(', right: '\\)', display: false },
          { left: '$', right: '$', display: false }
        ],
        throwOnError: false
      });
    });
  });
</script>
@endpush
