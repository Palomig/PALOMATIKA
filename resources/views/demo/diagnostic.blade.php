<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Демо: Диагностический тест — palomatika</title>
<script src="https://palomatika.ru/js/alpine.min.js" defer></script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0e1117; color: #e8eaf0; min-height: 100vh; }

  .demo-banner {
    background: linear-gradient(90deg, #f59e0b, #f97316);
    color: #fff; text-align: center; padding: 8px 16px;
    font-size: 13px; font-weight: 700; letter-spacing: 0.05em;
  }

  .wrap { max-width: 480px; margin: 0 auto; padding: 16px; }

  .diag-header { text-align: center; padding: 20px 0 14px; }
  .diag-title { font-size: 22px; font-weight: 900; color: #e8eaf0; margin-bottom: 6px; }
  .diag-subtitle { font-size: 13px; color: #8b92a5; line-height: 1.5; }

  .progress-wrap { margin-bottom: 18px; }
  .progress-label { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #8b92a5; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
  .progress-bar { height: 4px; background: #2a2d3a; border-radius: 2px; }
  .progress-fill { height: 4px; background: #4f8ef7; border-radius: 2px; transition: width 0.3s ease; }

  .dots { display: flex; gap: 4px; justify-content: center; flex-wrap: wrap; margin: 10px 0 14px; }
  .dot { width: 8px; height: 8px; border-radius: 50%; background: #2a2d3a; transition: background 0.2s; }
  .dot.answered { background: #4f8ef7; }
  .dot.active { background: #e8eaf0; }

  .question-card { background: #1a1d27; border: 1px solid #2a2d3a; border-radius: 14px; padding: 20px; margin-bottom: 12px; }
  .q-category { font-size: 10px; font-weight: 800; color: #4f8ef7; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; }
  .q-text { font-size: 15px; color: #e8eaf0; line-height: 1.6; margin-bottom: 16px; font-weight: 600; }

  .choices { display: flex; flex-direction: column; gap: 8px; }
  .choice-btn {
    display: flex; align-items: center; gap: 12px;
    width: 100%; padding: 12px 14px;
    background: #0e1117; border: 1.5px solid #2a2d3a;
    border-radius: 10px; cursor: pointer; text-align: left;
    font-size: 14px; color: #e8eaf0;
    transition: border-color 0.15s, background 0.15s;
  }
  .choice-btn:hover { border-color: #4f8ef7; }
  .choice-btn.selected { border-color: #4f8ef7; background: rgba(79,142,247,0.12); color: #4f8ef7; font-weight: 700; }
  .choice-letter {
    width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
    background: #2a2d3a; color: #8b92a5; font-weight: 800; font-size: 12px;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s, color 0.15s;
  }
  .choice-btn.selected .choice-letter { background: #4f8ef7; color: #fff; }

  .nav-btns { display: flex; gap: 8px; margin-top: 8px; }
  .btn-prev, .btn-next, .btn-submit {
    flex: 1; padding: 14px; border-radius: 12px; font-size: 15px; font-weight: 700;
    border: none; cursor: pointer; transition: opacity 0.15s;
  }
  .btn-prev { background: #1a1d27; border: 1px solid #2a2d3a; color: #e8eaf0; }
  .btn-next, .btn-submit { background: #4f8ef7; color: #fff; }
  .btn-prev:disabled, .btn-next:disabled { opacity: 0.3; cursor: default; }

  .counter { text-align: center; font-size: 12px; color: #8b92a5; margin-top: 12px; }
  .counter span { color: #4f8ef7; font-weight: 700; }
</style>
</head>
<body>

<div class="demo-banner">🔍 ДЕМО-РЕЖИМ — результаты не сохраняются</div>

<div class="wrap">
<div x-data="diagApp()" x-init="init()">

  <div class="diag-header">
    <div class="diag-title">Диагностика</div>
    <div class="diag-subtitle">Выберите правильный ответ — всего {{ $totalQuestions }} вопросов</div>
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

  @foreach($questions as $i => $q)
  <div class="question-card" x-show="current === {{ $i }}" x-transition.opacity>
    <div class="q-category">{{ $q['category'] }} · {{ $q['skill_name'] }}</div>
    <div class="q-text">{{ $q['question']['question'] }}</div>
    <div class="choices">
      @foreach($q['question']['choices'] as $ci => $choice)
      <button type="button" class="choice-btn"
        :class="{ selected: answers[{{ $i }}] === '{{ $ci }}' }"
        @click="selectChoice({{ $i }}, '{{ $ci }}')">
        <span class="choice-letter">{{ ['А','Б','В','Г'][$ci] }}</span>
        <span>{{ $choice }}</span>
      </button>
      @endforeach
    </div>
  </div>
  @endforeach

  <div class="nav-btns">
    <button class="btn-prev" @click="prev()" :disabled="current === 0">← Назад</button>
    <button class="btn-next" x-show="current < {{ $totalQuestions - 1 }}"
      @click="next()" :disabled="answers[current] === ''">Вперёд →</button>
    <button class="btn-submit" x-show="current === {{ $totalQuestions - 1 }}"
      @click="submit()" :disabled="answers[current] === '' || submitting"
      x-text="submitting ? 'Проверяем...' : 'Завершить и проверить'"></button>
  </div>

  <div class="counter">Отвечено: <span x-text="answers.filter(a => a !== '').length"></span> из {{ $totalQuestions }}</div>

  <form id="diag-form" method="POST" action="{{ route('demo.diagnostic.check') }}" style="display:none">
    @csrf
    <template x-for="(ans, i) in answers" :key="i">
      <input type="text" :name="'answers[' + i + ']'" :value="ans">
    </template>
  </form>

</div>
</div>

<script>
function diagApp() {
  return {
    current: 0,
    answers: Array({{ $totalQuestions }}).fill(''),
    submitting: false,
    init() {},
    prev() { if (this.current > 0) this.current--; },
    next() { if (this.current < {{ $totalQuestions - 1 }}) this.current++; },
    selectChoice(qi, ci) {
      this.answers[qi] = ci;
      if (qi < {{ $totalQuestions - 1 }}) {
        setTimeout(() => { this.current = qi + 1; }, 380);
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
</body>
</html>
