@extends('layouts.pwa')
@section('title', 'Решения ученика — palomatika')

@push('styles')
  .topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
  }
  .back { color: var(--text); text-decoration: none; font-size: 18px; padding: 6px 8px; border: 1px solid var(--border); border-radius: 10px; }
  .topbar-title { font-family: var(--display); font-size: 18px; color: var(--text); }

  .sub-summary {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 14px 16px; margin-bottom: 12px;
  }
  .sub-summary-title { font-family: var(--display); font-size: 16px; color: var(--text); }
  .sub-summary-meta { margin-top: 4px; font-size: 12px; color: var(--muted); font-weight: 700; }

  .sub-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 14px 16px; margin-bottom: 10px;
  }
  .sub-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
  .sub-num { font-family: var(--display); font-size: 14px; color: var(--text); }
  .sub-state { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; padding: 3px 8px; border-radius: 6px; white-space: nowrap; }
  .state-wait { color: var(--muted); background: var(--surface2); }
  .state-correct { color: #86efac; background: rgba(34,197,94,.2); }
  .state-wrong { color: #fecaca; background: rgba(239,68,68,.2); }

  .sub-text { color: var(--text); font-size: 13px; line-height: 1.45; overflow-wrap: anywhere; }
  .sub-visual { margin: 6px 0; display: flex; justify-content: center; }
  .sub-visual svg { max-width: 100%; width: auto; height: auto; display: block; }

  .answer-row {
    display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px;
    font-size: 12px; font-weight: 700;
  }
  .answer-chip {
    padding: 4px 9px; border-radius: 8px;
    background: var(--surface2); border: 1px solid var(--border); color: var(--text);
  }
  .answer-chip.correct { color: #86efac; border-color: rgba(34,197,94,.3); background: rgba(34,197,94,.1); }
  .answer-chip.wrong { color: #fecaca; border-color: rgba(239,68,68,.3); background: rgba(239,68,68,.1); }
  .answer-chip-label { color: var(--muted); font-weight: 600; }

  .photo-link { display: block; margin-top: 10px; }
  .photo-link img {
    width: 100%; max-height: 320px; object-fit: cover;
    border-radius: 10px; border: 1px solid var(--border); display: block;
  }
  .photo-hint { margin-top: 5px; font-size: 11px; color: var(--muted); font-weight: 700; }
  .no-photo { margin-top: 10px; font-size: 12px; color: var(--muted); font-weight: 700; }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.teacher.homework') }}" class="back">←</a>
    <div class="topbar-title">Решения</div>
    <div style="width:34px;"></div>
  </div>

  <div class="sub-summary">
    <div class="sub-summary-title">{{ $assignment->student?->name ?? 'Ученик' }}</div>
    <div class="sub-summary-meta">
      {{ $homework->title ?: 'Домашнее задание' }} · {{ $assignment->tasks_completed }} из {{ $assignment->tasks_total }} принято
    </div>
  </div>

  @foreach($homework->topicTasks as $task)
    @php
      $payload = $task->task_payload ?? [];
      $submission = $submissions->get($task->id);
      $text = $payload['text_html'] ?? $payload['text'] ?? $payload['html'] ?? $payload['question'] ?? $payload['expression'] ?? 'Задача';
      $svg = $payload['svg'] ?? null;
      $hasInlineSvg = is_string($svg) && str_contains($svg, '<svg');

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
    @endphp

    <div class="sub-card">
      <div class="sub-head">
        <div class="sub-num">Задача {{ $task->task_order }}</div>
        <div class="sub-state {{ $stateClass }}">{{ $stateLabel }}</div>
      </div>

      @if($hasInlineSvg)
        <div class="sub-visual">{!! $svg !!}</div>
      @endif

      <div class="sub-text">{!! $text !!}</div>

      <div class="answer-row">
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

      @if($submission && $submission->solution_photo_path)
        <a class="photo-link" href="{{ route('pwa.teacher.homework.submission-photo', $submission) }}" target="_blank" rel="noopener">
          <img src="{{ route('pwa.teacher.homework.submission-photo', $submission) }}" alt="Фото решения" loading="lazy">
        </a>
        <div class="photo-hint">Фото последней попытки · {{ $submission->updated_at?->format('d.m.Y H:i') }} · нажми, чтобы открыть целиком</div>
      @elseif($submission)
        <div class="no-photo">Фото решения не сохранилось.</div>
      @else
        <div class="no-photo">Ученик ещё не отправлял решение.</div>
      @endif
    </div>
  @endforeach
</div>
@endsection
