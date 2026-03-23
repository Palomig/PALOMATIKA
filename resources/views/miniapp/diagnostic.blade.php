@extends('layouts.miniapp')
@section('title', 'Диагностика — palomatika')

@push('styles')
  .diag-header {
    text-align: center;
    padding: 24px 0 16px;
    opacity: 0; animation: fadeDown 0.3s ease 0s forwards;
  }
  .diag-title { font-family: var(--display); font-size: 22px; color: var(--text); margin-bottom: 6px; }
  .diag-subtitle { font-size: 13px; color: var(--muted); line-height: 1.5; }

  .progress-wrap { margin-bottom: 20px; opacity: 0; animation: fadeUp 0.3s ease 0.1s forwards; }
  .progress-label { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
  .progress-bar { height: 4px; background: var(--border); border-radius: 2px; }
  .progress-fill { height: 4px; background: var(--accent); border-radius: 2px; transition: width 0.3s ease; }

  .question-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 20px;
    margin-bottom: 12px;
  }
  .q-category { font-size: 10px; font-weight: 800; color: var(--accent); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; }
  .q-text { font-size: 15px; color: var(--text); line-height: 1.6; margin-bottom: 16px; font-weight: 600; }

  /* MC choices */
  .choices { display: flex; flex-direction: column; gap: 8px; }
  .choice-btn {
    display: flex; align-items: center; gap: 12px;
    width: 100%; padding: 12px 14px;
    background: var(--bg); border: 1.5px solid var(--border);
    border-radius: 10px; cursor: pointer; text-align: left;
    font-size: 14px; color: var(--text);
    transition: border-color 0.15s, background 0.15s;
  }
  .choice-btn:hover { border-color: var(--accent); }
  .choice-btn.selected { border-color: var(--accent); background: rgba(79,142,247,0.1); color: var(--accent); font-weight: 700; }
  .choice-letter {
    width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0;
    background: var(--surface2); color: var(--muted); font-weight: 800; font-size: 12px;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s, color 0.15s;
  }
  .choice-btn.selected .choice-letter { background: var(--accent); color: #fff; }

  .nav-btns { display: flex; gap: 8px; margin-top: 8px; }
  .btn-prev, .btn-next, .btn-submit {
    flex: 1; padding: 13px; border-radius: 12px; font-size: 15px; font-weight: 700;
    border: none; cursor: pointer; transition: opacity 0.15s;
  }
  .btn-prev { background: var(--surface); border: 1px solid var(--border); color: var(--text); }
  .btn-next, .btn-submit { background: var(--accent); color: #fff; }
  .btn-prev:disabled { opacity: 0.3; }
  .btn-next:disabled { opacity: 0.4; cursor: default; }

  .dots { display: flex; gap: 4px; justify-content: center; flex-wrap: wrap; margin: 12px 0; }
  .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--border); transition: background 0.2s; }
  .dot.answered { background: var(--accent); }
  .dot.active { background: var(--text); }
@endpush

@section('body')
<div x-data="diagApp()" x-init="init()">

  <div class="diag-header">
    <div class="diag-title">Диагностика</div>
    <div class="diag-subtitle">Выберите правильный ответ — мы подберём<br>задания по вашему уровню</div>
  </div>

  <div class="progress-wrap">
    <div class="progress-label">
      <span>Вопрос <span x-text="current + 1"></span> из {{ $totalQuestions }}</span>
      <span x-text="Math.round(((current + 1) / {{ $totalQuestions }}) * 100) + '%'"></span>
    </div>
    <div class="progress-bar">
      <div class="progress-fill" :style="'width:' + Math.round(((current + 1) / {{ $totalQuestions }}) * 100) + '%'"></div>
    </div>
  </div>

  <div class="dots">
    @foreach($questions as $i => $q)
    <div class="dot" :class="{ answered: answers[{{ $i }}] !== '', active: current === {{ $i }} }"></div>
    @endforeach
  </div>

  {{-- Вопросы --}}
  @foreach($questions as $i => $q)
  <div class="question-card" x-show="current === {{ $i }}" x-transition.opacity>
    <div class="q-category">{{ $q['category'] }} · {{ $q['skill_name'] }}</div>
    <div class="q-text">{{ $q['question']['question'] ?? '' }}</div>

    @if(($q['type'] ?? '') === 'mc')
    <div class="choices">
      @foreach($q['question']['choices'] as $ci => $choice)
      <button
        type="button"
        class="choice-btn"
        :class="{ selected: answers[{{ $i }}] === '{{ $ci }}' }"
        @click="selectChoice({{ $i }}, '{{ $ci }}')"
      >
        <span class="choice-letter">{{ ['А','Б','В','Г'][$ci] }}</span>
        <span>{{ $choice }}</span>
      </button>
      @endforeach
    </div>
    @endif
  </div>
  @endforeach

  <div class="nav-btns">
    <button class="btn-prev" @click="prev()" :disabled="current === 0">← Назад</button>
    <button
      class="btn-next"
      x-show="current < {{ $totalQuestions - 1 }}"
      @click="next()"
      :disabled="answers[current] === ''"
    >Вперёд →</button>
    <button
      class="btn-submit"
      x-show="current === {{ $totalQuestions - 1 }}"
      @click="submit()"
      :disabled="answers[current] === '' || submitting"
      x-text="submitting ? 'Сохраняем...' : 'Завершить'"
    ></button>
  </div>

  {{-- Скрытая форма --}}
  <form id="diag-form" method="POST" action="{{ route('miniapp.diagnostic.submit') }}" style="display:none">
    @csrf
    <template x-for="(ans, i) in answers" :key="i">
      <input type="text" :name="'answers[' + i + ']'" :value="ans">
    </template>
  </form>

</div>

<script>
function diagApp() {
  return {
    current: 0,
    answers: Array({{ $totalQuestions }}).fill(''),
    submitting: false,
    init() {},
    prev() { if (this.current > 0) this.current--; },
    next() {
      if (this.current < {{ $totalQuestions - 1 }}) this.current++;
    },
    selectChoice(questionIndex, choiceIndex) {
      this.answers[questionIndex] = choiceIndex;
      // Автоматически переходим к следующему через 400ms
      if (questionIndex < {{ $totalQuestions - 1 }}) {
        setTimeout(() => { this.current = questionIndex + 1; }, 400);
      }
    },
    submit() {
      if (this.submitting) return;
      this.submitting = true;
      document.getElementById('diag-form').submit();
    }
  }
}
</script>
@endsection
