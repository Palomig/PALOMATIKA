@extends('layouts.pwa')
@section('title', $game['title'] . ' — palomatika')

@push('styles')
  .game-shell { display: flex; flex-direction: column; gap: 14px; }
  .game-hero, .game-stage, .game-result {
    background:
      radial-gradient(circle at top right, rgba(52,208,126,.12), transparent 34%),
      var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 20px;
  }
  .game-hero-title { font-family: var(--display); font-size: 24px; line-height: 1.15; margin: 10px 0 8px; }
  .game-copy { font-size: 13px; font-weight: 600; color: var(--muted); line-height: 1.55; }
  .game-stage { opacity: 0; animation: fadeUp 0.3s ease 0.08s forwards; }
  .game-stage-top { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
  .game-equation {
    font-family: var(--display); font-size: 30px; line-height: 1.2;
    text-align: center; padding: 18px; border-radius: 18px; background: var(--surface2); margin: 12px 0 14px;
  }
  .game-graph { display: flex; justify-content: center; margin: 12px 0 14px; }
  .game-graph .practice-graph-svg { width: 100%; max-width: 260px; height: auto; display: block; }
  .game-prompt { text-align: center; font-size: 13px; font-weight: 700; color: var(--muted); }
  .game-options { display: flex; flex-direction: column; gap: 10px; }
  .game-option {
    width: 100%; border: 1px solid var(--border); background: var(--surface2); color: var(--text);
    border-radius: 16px; padding: 15px 16px; text-align: left; cursor: pointer; font-size: 16px; font-weight: 800;
  }
  .game-option:disabled { opacity: .65; cursor: default; }
  .opt-frac {
    display: inline-flex; flex-direction: column; vertical-align: middle;
    margin: 0 3px; line-height: 1; font-size: 0.92em;
  }
  .opt-frac > span { display: block; padding: 2px 8px; text-align: center; }
  .opt-frac > .num { border-bottom: 2px solid currentColor; }
  .game-result-score { font-family: var(--display); font-size: 42px; line-height: 1; margin: 10px 0 8px; }
  .theory-list { display: flex; flex-direction: column; gap: 8px; margin-top: 12px; }
  .theory-item {
    background: var(--surface2); border: 1px solid var(--border); border-radius: 14px;
    padding: 12px 14px; font-size: 12px; font-weight: 600; color: var(--muted); line-height: 1.5;
  }
@endpush

@section('body')
<div class="page" x-data="practiceGamePage({
  slug: @js($game['slug']),
  endpoint: @js(route('pwa.student.practice.mini-games.question', $game['slug'])),
  theory: @js($game['theory']),
  turnSeconds: @js((int) ($game['turn_seconds'] ?? 10)),
})">
  <div class="topbar">
    <a href="{{ route('pwa.student.practice.mini-games') }}" class="back-btn">‹</a>
    <div class="topbar-title">{{ $game['title'] }}</div>
  </div>

  <template x-if="status === 'intro'">
    <div class="game-shell">
      <div class="game-hero anim-up">
        <div class="pill pill-green">{{ $game['intro']['eyebrow'] ?? 'Мини-игра' }}</div>
        <div class="game-hero-title">{{ $game['intro']['title'] ?? $game['title'] }}</div>
        <div class="game-copy">{{ $game['intro']['description'] ?? $game['description'] }}</div>
        <div class="stat-pills" style="margin-top:16px;">
          @foreach(($game['intro']['rules'] ?? []) as $rule)
            <div class="stat-pill">• <span>{{ $rule }}</span></div>
          @endforeach
        </div>
      </div>

      <button class="btn btn-green anim-up" style="animation-delay:.1s;" @click="startGame()">Начать</button>

      <div class="game-result anim-up" style="animation-delay:.14s;">
        <div class="sec-label">Что будет после игры</div>
        <div class="game-copy" style="margin-top:10px;">
          Если ошибёшься или не успеешь за 10 секунд, увидишь экран `Игра окончена`, свой счёт, основные правила темы и кнопку `Сыграть ещё`.
        </div>
        <div class="theory-list">
          @foreach(($game['theory']['items'] ?? []) as $item)
            <div class="theory-item">{{ $item }}</div>
          @endforeach
        </div>
      </div>
    </div>
  </template>

  <template x-if="status === 'playing' && question">
    <div class="game-stage">
      <div class="game-stage-top">
        <div class="pill pill-green">Счёт: <span x-text="score"></span></div>
        <div class="pill pill-accent">Уровень <span x-text="question.level"></span></div>
        <div class="pill pill-red">⏱ <span x-text="timeLeft"></span> сек</div>
      </div>
      <div class="game-prompt" x-text="question.prompt"></div>
      <template x-if="question.graph">
        <div class="game-graph" x-html="question.graph"></div>
      </template>
      <template x-if="question.equation">
        <div class="game-equation" x-text="question.equation"></div>
      </template>
      <div class="game-options">
        <template x-for="option in question.options" :key="option.id">
          <button class="game-option" @click="chooseOption(option)" :disabled="loading">
            <template x-if="option.fraction">
              <span>
                <span x-text="option.fraction.prefix"></span>
                <span class="opt-frac">
                  <span class="num" x-text="option.fraction.numerator"></span>
                  <span class="den" x-text="option.fraction.denominator"></span>
                </span>
              </span>
            </template>
            <template x-if="!option.fraction">
              <span x-text="option.label"></span>
            </template>
          </button>
        </template>
      </div>
    </div>
  </template>

  <template x-if="status === 'result'">
    <div class="game-result anim-up">
      <div class="pill pill-red" x-text="resultReason === 'timeout' ? 'Время вышло' : 'Игра окончена'"></div>
      <div class="game-result-score" x-text="score"></div>
      <div class="game-copy">правильных ответов подряд</div>

      <div class="sec-label" style="margin-top:18px;" x-text="theory.title"></div>
      <div class="theory-list">
        <template x-for="(item, index) in theory.items" :key="index">
          <div class="theory-item" x-text="item"></div>
        </template>
      </div>

      <button class="btn btn-green" style="margin-top:18px;" @click="restart()">Сыграть ещё</button>
    </div>
  </template>
</div>
@endsection

@push('scripts')
<script>
function practiceGamePage(config) {
  return {
    slug: config.slug,
    endpoint: config.endpoint,
    theory: config.theory,
    turnSeconds: config.turnSeconds,
    status: 'intro',
    score: 0,
    timeLeft: config.turnSeconds,
    timerId: null,
    loading: false,
    question: null,
    resultReason: 'wrong',

    async startGame() {
      this.score = 0;
      this.resultReason = 'wrong';
      await this.loadQuestion();
    },

    async restart() {
      this.clearTimer();
      this.status = 'intro';
      this.question = null;
      this.timeLeft = this.turnSeconds;
    },

    async loadQuestion() {
      this.loading = true;

      try {
        const response = await fetch(`${this.endpoint}?score=${this.score}`, {
          headers: { 'Accept': 'application/json' },
          credentials: 'include',
        });

        if (!response.ok) {
          throw new Error('Не удалось загрузить вопрос');
        }

        const data = await response.json();
        this.question = data.question;
        this.theory = data.game.theory;
        this.status = 'playing';
        this.timeLeft = this.turnSeconds;
        this.startTimer();
      } catch (error) {
        console.error(error);
        alert('Не удалось загрузить задание. Попробуй ещё раз.');
        this.status = 'intro';
      } finally {
        this.loading = false;
      }
    },

    startTimer() {
      this.clearTimer();
      this.timerId = setInterval(() => {
        this.timeLeft -= 1;
        if (this.timeLeft <= 0) {
          this.finishGame('timeout');
        }
      }, 1000);
    },

    clearTimer() {
      if (this.timerId) {
        clearInterval(this.timerId);
        this.timerId = null;
      }
    },

    async chooseOption(option) {
      if (this.loading) return;

      this.clearTimer();

      if (!option.is_correct) {
        this.finishGame('wrong');
        return;
      }

      this.score += 1;
      await this.loadQuestion();
    },

    finishGame(reason) {
      this.clearTimer();
      this.resultReason = reason;
      this.status = 'result';
    },
  };
}
</script>
@endpush
