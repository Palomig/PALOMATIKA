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
  .q-text { font-size: 15px; color: var(--text); line-height: 1.6; margin-bottom: 16px; }
  .q-input {
    width: 100%; padding: 12px 14px;
    background: var(--bg); border: 1px solid var(--border); border-radius: 10px;
    color: var(--text); font-size: 16px; outline: none;
    box-sizing: border-box;
  }
  .q-input:focus { border-color: var(--accent); }

  .nav-btns { display: flex; gap: 8px; margin-top: 8px; }
  .btn-prev, .btn-next, .btn-submit {
    flex: 1; padding: 13px; border-radius: 12px; font-size: 15px; font-weight: 700;
    border: none; cursor: pointer; transition: opacity 0.15s;
  }
  .btn-prev { background: var(--surface); border: 1px solid var(--border); color: var(--text); }
  .btn-next, .btn-submit { background: var(--accent); color: #fff; }
  .btn-prev:disabled { opacity: 0.3; }

  .dots { display: flex; gap: 4px; justify-content: center; flex-wrap: wrap; margin: 12px 0; }
  .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--border); transition: background 0.2s; }
  .dot.answered { background: var(--accent); }
  .dot.active { background: var(--text); }
@endpush

@section('content')
<div x-data="diagApp()" x-init="init()">

  <div class="diag-header">
    <div class="diag-title">Диагностика</div>
    <div class="diag-subtitle">Ответьте на вопросы, чтобы мы<br>подобрали задания по вашему уровню</div>
  </div>

  <div class="progress-wrap">
    <div class="progress-label">
      <span>Вопрос <span x-text="current + 1"></span> из {{ $totalQuestions }}</span>
      <span x-text="Math.round((current / {{ $totalQuestions }}) * 100) + '%'"></span>
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
    <div class="q-text">{{ $q['question']['question'] ?? $q['question']['text'] ?? '' }}</div>
    <input
      class="q-input"
      type="text"
      inputmode="decimal"
      placeholder="Введите ответ"
      x-model="answers[{{ $i }}]"
      @keydown.enter="next()"
    >
  </div>
  @endforeach

  <div class="nav-btns">
    <button class="btn-prev" @click="prev()" :disabled="current === 0">← Назад</button>
    <button
      class="btn-next"
      x-show="current < {{ $totalQuestions - 1 }}"
      @click="next()"
    >Вперёд →</button>
    <button
      class="btn-submit"
      x-show="current === {{ $totalQuestions - 1 }}"
      @click="submit()"
    >Завершить</button>
  </div>

  {{-- Скрытая форма для сабмита --}}
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
    init() {},
    prev() { if (this.current > 0) this.current--; },
    next() { if (this.current < {{ $totalQuestions - 1 }}) this.current++; },
    submit() {
      document.getElementById('diag-form').submit();
    }
  }
}
</script>
@endsection
