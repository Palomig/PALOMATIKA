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
    position: relative; overflow: hidden; isolation: isolate;
    width: 100%; border: 1px solid rgba(255,255,255,.22); background: var(--surface2); color: var(--text);
    border-radius: 16px; padding: 15px 16px; text-align: left; cursor: pointer; font-size: 16px; font-weight: 800;
  }
  .game-option:disabled { opacity: .65; cursor: default; }
  .game-option-progress {
    position: absolute; inset: 0; z-index: -1; transform-origin: left center;
    transition: transform .1s linear, background .3s linear; pointer-events: none;
  }
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

  .lb-scope-hint { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-top: 4px; }
  .lb-table { display: flex; flex-direction: column; gap: 6px; margin-top: 12px; }
  .lb-row {
    display: grid; grid-template-columns: 36px 1fr auto; align-items: center; gap: 10px;
    background: var(--surface2); border: 1px solid var(--border); border-radius: 12px;
    padding: 10px 14px;
  }
  .lb-row.is-viewer { border-color: var(--accent); background: rgba(99,102,241,.08); }
  .lb-rank { font-family: var(--display); font-size: 18px; color: var(--muted); }
  .lb-rank.is-top1 { color: #facc15; }
  .lb-rank.is-top2 { color: #d1d5db; }
  .lb-rank.is-top3 { color: #f97316; }
  .lb-name { font-size: 14px; color: var(--text); font-weight: 700; }
  .lb-class { font-size: 11px; color: var(--muted); font-weight: 600; margin-top: 2px; }
  .lb-score { font-family: var(--display); font-size: 18px; color: var(--green); }
  .lb-empty { color: var(--muted); font-size: 13px; font-weight: 600; line-height: 1.5; margin-top: 10px; }
  .lb-divider { text-align: center; color: var(--muted); font-size: 11px; font-weight: 700; letter-spacing: .12em; margin: 8px 0 4px; }
@endpush

@section('body')
<div class="page" x-data="practiceGamePage({
  slug: @js($game['slug']),
  startEndpoint: @js(route('pwa.student.practice.mini-games.start', $game['slug'])),
  answerEndpoint: @js(route('pwa.student.practice.mini-games.answer', $game['slug'])),
  timeoutEndpoint: @js(route('pwa.student.practice.mini-games.timeout', $game['slug'])),
  leaderboardUrl: @js(route('pwa.student.practice.mini-games.leaderboard', $game['slug'])),
  theory: @js($game['theory']),
  turnSeconds: @js((int) ($game['turn_seconds'] ?? 10)),
})">
  <div class="topbar">
    <a href="{{ isset($game['category']) ? route('pwa.student.practice.category', $game['category']) : route('pwa.student.practice.index') }}" class="back-btn">‹</a>
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
        <div class="sec-label">🏆 Лидерборд</div>
        @php
          $scopeLabel = match ($boardScope) { 'class' => 'твой класс', 'school' => 'твоя школа', default => 'все ученики' };
        @endphp
        <div class="lb-scope-hint">{{ $scopeLabel }} · за всё время</div>

        @if(!$board['available'])
          <div class="lb-empty">Чтобы видеть лидерборд класса, укажи в профиле школу, номер класса и букву.</div>
        @elseif(empty($board['entries']))
          <div class="lb-empty">Здесь пока пусто. Сыграй раунд — твой результат появится первым.</div>
        @else
          <div class="lb-table">
            @foreach(array_slice($board['entries'], 0, 10) as $entry)
              @php
                $rankClass = match ($entry['rank']) { 1 => 'is-top1', 2 => 'is-top2', 3 => 'is-top3', default => '' };
              @endphp
              <div class="lb-row {{ $entry['is_viewer'] ? 'is-viewer' : '' }}">
                <div class="lb-rank {{ $rankClass }}">{{ $entry['rank'] }}</div>
                <div>
                  <div class="lb-name">{{ $entry['name'] }}{{ $entry['is_viewer'] ? ' (ты)' : '' }}</div>
                  @if(!empty($entry['class']))
                    <div class="lb-class">{{ $entry['class'] }}</div>
                  @endif
                </div>
                <div class="lb-score">{{ $entry['score'] }}</div>
              </div>
            @endforeach
          </div>

          @if(!empty($board['viewer_entry']))
            <div class="lb-divider">···</div>
            <div class="lb-table">
              <div class="lb-row is-viewer">
                <div class="lb-rank">{{ $board['viewer_entry']['rank'] }}</div>
                <div>
                  <div class="lb-name">{{ $board['viewer_entry']['name'] }} (ты)</div>
                  @if(!empty($board['viewer_entry']['class']))
                    <div class="lb-class">{{ $board['viewer_entry']['class'] }}</div>
                  @endif
                </div>
                <div class="lb-score">{{ $board['viewer_entry']['score'] }}</div>
              </div>
            </div>
          @endif
        @endif
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
            <span class="game-option-progress" :style="progressStyle"></span>
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
      <a :href="leaderboardUrl" class="btn btn-accent" style="margin-top:8px;display:block;text-align:center;text-decoration:none;">🏆 Открыть лидерборд</a>
    </div>
  </template>
</div>
@endsection

@push('scripts')
<script>
function practiceGamePage(config) {
  return {
    slug: config.slug,
    startEndpoint: config.startEndpoint,
    answerEndpoint: config.answerEndpoint,
    timeoutEndpoint: config.timeoutEndpoint,
    leaderboardUrl: config.leaderboardUrl,
    theory: config.theory,
    turnSeconds: config.turnSeconds,
    status: 'intro',
    runId: null,
    score: 0,
    timeLeft: config.turnSeconds,
    timeProgress: 1,
    timerId: null,
    loading: false,
    question: null,
    resultReason: 'wrong',

    get progressStyle() {
      const threshold = 3 / this.turnSeconds;
      const ratio = this.timeProgress <= threshold
        ? 0
        : (this.timeProgress - threshold) / (1 - threshold);
      const hue = Math.round(ratio * 140);
      const sat = 70;
      return `transform: scaleX(${this.timeProgress});`
        + ` background: linear-gradient(90deg,`
        + ` hsla(${hue}, ${sat}%, 50%, .36),`
        + ` hsla(${hue}, ${sat}%, 50%, .14));`;
    },

    async startGame() {
      this.score = 0;
      this.runId = null;
      this.resultReason = 'wrong';
      this.loading = true;
      try {
        const data = await this.postJson(this.startEndpoint, {});
        this.runId = data.run_id;
        this.score = data.score ?? 0;
        this.question = data.question;
        if (data.game?.theory) this.theory = data.game.theory;
        this.status = 'playing';
        this.timeLeft = this.turnSeconds;
        this.startTimer();
      } catch (error) {
        console.error(error);
        alert('Не удалось начать игру. Попробуй ещё раз.');
        this.status = 'intro';
      } finally {
        this.loading = false;
      }
    },

    async restart() {
      this.clearTimer();
      this.status = 'intro';
      this.question = null;
      this.runId = null;
      this.timeLeft = this.turnSeconds;
      this.timeProgress = 1;
    },

    async postJson(url, body) {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': window._csrf || '',
        },
        credentials: 'include',
        body: JSON.stringify(body),
      });
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      return response.json();
    },

    startTimer() {
      this.clearTimer();
      const startTime = Date.now();
      const duration = this.turnSeconds * 1000;
      this.timeLeft = this.turnSeconds;
      this.timeProgress = 1;
      this.timerId = setInterval(() => {
        const remaining = duration - (Date.now() - startTime);
        if (remaining <= 0) {
          this.timeLeft = 0;
          this.timeProgress = 0;
          this.handleTimeout();
          return;
        }
        this.timeLeft = Math.ceil(remaining / 1000);
        this.timeProgress = remaining / duration;
      }, 100);
    },

    clearTimer() {
      if (this.timerId) {
        clearInterval(this.timerId);
        this.timerId = null;
      }
    },

    async chooseOption(option) {
      if (this.loading || !this.runId) return;
      this.clearTimer();
      this.loading = true;
      try {
        const data = await this.postJson(this.answerEndpoint, {
          run_id: this.runId,
          option_id: option.id,
        });
        if (data.status === 'continue') {
          this.score = data.score ?? this.score;
          this.question = data.question;
          this.timeLeft = this.turnSeconds;
          this.startTimer();
        } else {
          this.score = data.score ?? this.score;
          this.finishGame(data.reason || 'wrong');
        }
      } catch (error) {
        console.error(error);
        this.finishGame('wrong');
      } finally {
        this.loading = false;
      }
    },

    async handleTimeout() {
      if (!this.runId) {
        this.finishGame('timeout');
        return;
      }
      this.loading = true;
      try {
        const data = await this.postJson(this.timeoutEndpoint, { run_id: this.runId });
        this.score = data.score ?? this.score;
        this.finishGame(data.reason || 'timeout');
      } catch (error) {
        console.error(error);
        this.finishGame('timeout');
      } finally {
        this.loading = false;
      }
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
