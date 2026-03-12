@extends('layouts.miniapp')
@section('title', 'Дашборд — palomatika')

@push('styles')
  .greeting {
    opacity: 0; animation: fadeDown 0.3s ease 0s forwards;
    display: flex; align-items: center; justify-content: space-between;
  }
  .greeting-name { font-family: var(--display); font-size: 20px; color: var(--text); }
  .greeting-countdown {
    display: flex; align-items: center; gap: 5px;
    font-size: 12px; font-weight: 700; color: var(--muted);
    white-space: nowrap;
  }
  .greeting-countdown-val { font-family: var(--display); font-size: 13px; color: var(--text); }

  /* INFO CARD (used by FIPI banner) */
  .last-result {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 16px;
    opacity: 0; animation: fadeUp 0.3s ease 0.09s forwards;
  }
  .lr-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
  .lr-title { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; }
  .lr-date { font-size: 10px; font-weight: 700; color: var(--muted); }
  .lr-body { display: flex; align-items: center; gap: 16px; }
  .lr-score { font-family: var(--display); font-size: 32px; color: var(--green); line-height: 1; }
  .lr-score small { font-size: 18px; color: var(--muted); }
  .lr-details { flex: 1; }
  .lr-label { font-size: 12px; font-weight: 700; color: var(--muted); }
  .lr-time { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 2px; }

  /* ACTION TILES */
  .tile-row { display: flex; gap: 10px; }

  .tile-big {
    flex: 1;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--r); padding: 18px 16px;
    cursor: pointer; transition: border-color 0.15s, background 0.15s;
    user-select: none; text-decoration: none; color: inherit;
    opacity: 0; animation: fadeUp 0.3s ease 0.12s forwards;
  }
  .tile-big:active { background: var(--surface2); }

  .tile-purple { border-color: var(--purple-bd); }
  .tile-purple:active { background: var(--purple-bg); }
  .tile-blue { border-color: var(--accent-bd); }
  .tile-blue:active { background: var(--accent-bg); }

  .tile-icon { font-size: 28px; margin-bottom: 10px; }
  .tile-name { font-family: var(--display); font-size: 15px; color: var(--text); margin-bottom: 3px; }
  .tile-desc { font-size: 11px; font-weight: 600; color: var(--muted); line-height: 1.4; }
  .tile-badge {
    display: inline-block; margin-top: 8px;
    font-size: 9px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.08em; padding: 3px 8px; border-radius: 6px;
  }
  .badge-purple { background: var(--purple-bg); color: var(--purple); }
  .badge-blue { background: var(--accent-bg); color: var(--accent); }
  .badge-red { background: rgba(239, 68, 68, .2); color: #ff8a8a; }

  /* SMALL TILES 2x2 */
  .tiles-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
    opacity: 0; animation: fadeUp 0.3s ease 0.16s forwards;
  }
  .tile-sm {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 14px;
    cursor: pointer; transition: background 0.15s;
    user-select: none; text-decoration: none; color: inherit;
    position: relative;
  }
  .tile-sm:active { background: var(--surface2); }
  .tile-sm-icon { font-size: 22px; margin-bottom: 6px; }
  .tile-sm-name { font-size: 13px; font-weight: 800; color: var(--text); margin-bottom: 2px; }
  .tile-sm-desc { font-size: 10px; font-weight: 600; color: var(--muted); line-height: 1.3; }
  .tile-badge-top-right { position: absolute; top: 8px; right: 8px; margin-top: 0; }

  /* WEAK TOPICS */
  .weak-section {
    opacity: 0; animation: fadeUp 0.3s ease 0.2s forwards;
  }
  .weak-row {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px;
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 8px;
  }
  .weak-info { display: flex; align-items: center; gap: 10px; }
  .weak-num { font-family: var(--display); font-size: 14px; color: var(--muted); width: 24px; }
  .weak-name { font-size: 13px; font-weight: 700; color: var(--text); }
  .weak-pct { font-family: var(--display); font-size: 14px; }
  .weak-pct.low { color: var(--red); }
  .weak-pct.mid { color: var(--yellow); }
  .weak-pct.high { color: var(--green); }

  .pulse-dot-sm {
    display: inline-block; width: 6px; height: 6px;
    background: var(--red); border-radius: 50%; margin-right: 4px;
    animation: pulse 1.5s ease infinite;
  }
  @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.7); } }

  /* RESUME BANNER */
  .resume-banner {
    display: flex; align-items: center; justify-content: space-between;
    background: linear-gradient(135deg, rgba(124,58,237,.18), rgba(59,130,246,.12));
    border: 1.5px solid rgba(124,58,237,.4);
    border-radius: var(--r); padding: 14px 16px;
    text-decoration: none; color: inherit;
    opacity: 0; animation: fadeUp 0.3s ease 0.07s forwards;
  }
  .resume-banner:active { opacity: 0.85; }
  .resume-left { display: flex; align-items: center; gap: 12px; }
  .resume-pulse {
    width: 10px; height: 10px; background: var(--green);
    border-radius: 50%; flex-shrink: 0;
    animation: pulse 1.5s ease infinite;
  }
  .resume-title { font-family: var(--display); font-size: 14px; color: var(--text); }
  .resume-sub { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 2px; }
  .resume-btn {
    font-size: 12px; font-weight: 800; color: var(--purple);
    white-space: nowrap;
  }

  /* FULL VARIANT CHOICE MODAL */
  .fv-overlay {
    position: fixed; inset: 0; z-index: 100;
    background: rgba(0,0,0,.55); backdrop-filter: blur(4px);
    display: flex; align-items: flex-end; justify-content: center;
  }
  .fv-sheet {
    background: var(--bg); border-radius: 20px 20px 0 0;
    width: 100%; max-width: 420px; padding: 20px 20px 32px;
  }
  .fv-handle {
    width: 36px; height: 4px; background: var(--border);
    border-radius: 2px; margin: 0 auto 16px;
  }
  .fv-title {
    font-family: var(--display); font-size: 18px; color: var(--text);
    text-align: center; margin-bottom: 16px;
  }
  .fv-option {
    display: flex; align-items: center; gap: 14px;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--r); padding: 16px;
    margin-bottom: 10px; cursor: pointer; transition: border-color 0.15s;
    text-decoration: none; color: inherit;
  }
  .fv-option:active { background: var(--surface2); }
  .fv-opt-icon { font-size: 28px; flex-shrink: 0; }
  .fv-opt-name { font-family: var(--display); font-size: 15px; color: var(--text); }
  .fv-opt-desc { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 2px; line-height: 1.3; }
  .fv-opt-badge {
    display: inline-block; margin-top: 6px;
    font-size: 9px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.08em; padding: 3px 8px; border-radius: 6px;
  }
  .fv-cancel {
    display: block; width: 100%; padding: 14px;
    background: none; border: none; color: var(--muted);
    font-size: 14px; font-weight: 700; cursor: pointer; margin-top: 4px;
  }
@endpush

@section('body')
<div class="page" x-data="dashboardPage()">

  {{-- GREETING + COUNTDOWN --}}
  <div class="greeting">
    <div class="greeting-name">Привет, {{ $user->name ?? 'ученик' }}!</div>
    <div class="greeting-countdown">
      <span class="pulse-dot-sm"></span>
      <span>До ОГЭ</span>
      <span class="greeting-countdown-val" x-text="daysLeft + ' дн'">— дн</span>
    </div>
  </div>

  {{-- RESUME BANNER --}}
  @if(count($activeAttemptsList) === 1)
  <a href="/tg/test/{{ $activeAttemptsList[0]['id'] }}" class="resume-banner">
    <div class="resume-left">
      <div class="resume-pulse"></div>
      <div class="resume-info">
        <div class="resume-title">{{ $activeAttemptsList[0]['type'] }}</div>
        <div class="resume-sub">
          Отвечено {{ $activeAttemptsList[0]['answeredCount'] }} из {{ $activeAttemptsList[0]['totalCount'] }}
          · начат {{ $activeAttemptsList[0]['startedAt']?->diffForHumans() }}
        </div>
      </div>
    </div>
    <div class="resume-btn">Продолжить →</div>
  </a>
  @elseif(count($activeAttemptsList) > 1)
  <div class="resume-banner" style="cursor:pointer" @click="showUnfinished = true">
    <div class="resume-left">
      <div class="resume-pulse"></div>
      <div class="resume-info">
        <div class="resume-title">У вас {{ count($activeAttemptsList) }} нерешённых {{ count($activeAttemptsList) <= 4 ? 'варианта' : 'вариантов' }}</div>
        <div class="resume-sub">Нажмите, чтобы выбрать</div>
      </div>
    </div>
    <div class="resume-btn">Продолжить →</div>
  </div>
  @endif

@if(($newFipiCount ?? 0) > 0)
  <div class="last-result" style="border-color: rgba(124, 58, 237, 0.35); background: linear-gradient(180deg, rgba(124,58,237,.16), rgba(124,58,237,.07));">
    <div class="lr-header">
      <div class="lr-title" style="color:#d8c1ff">Новые задания</div>
      <div class="lr-date" style="color:#c9a7ff">ФИПИ</div>
    </div>
    <div class="lr-body">
      <div class="lr-score" style="color:#fff">{{ $newFipiCount }}</div>
      <div class="lr-details">
        <div class="lr-label">новых заданий в банке ФИПИ</div>
        <div class="lr-time" style="color:#c9a7ff">темы: 9, 10, 15, 16, 17</div>
      </div>
    </div>
  </div>
  @endif

  {{-- ACTION TILES --}}
  <div class="tile-row">
    <a href="/tg/mini" class="tile-big tile-purple">
      <div class="tile-icon">⚡</div>
      <div class="tile-name">Мини-ОГЭ</div>
      <div class="tile-desc">Короткая тренировка по одной теме</div>
      <div class="tile-badge badge-purple">~10 мин</div>
    </a>
    <a href="#" class="tile-big tile-blue" @click.prevent="showFullChoice = true">
      <div class="tile-icon">📝</div>
      <div class="tile-name">Полный вариант</div>
      <div class="tile-desc">Как на экзамене</div>
      <div class="tile-badge badge-blue">~45 мин</div>
    </a>
  </div>

  {{-- SMALL TILES --}}
  <div class="tiles-grid">
    <a href="#" class="tile-sm" @click.prevent="showTaskBase = true">
      <div class="tile-sm-icon">📚</div>
      <div class="tile-sm-name">База заданий</div>
      <div class="tile-sm-desc">ФИПИ 1 и 2 части</div>
      @if(($newFipiCount ?? 0) > 0)
      <div class="tile-badge badge-red tile-badge-top-right">Новое</div>
      @endif
    </a>
    <div class="tile-sm" style="opacity:0.5;cursor:default;">
      <div class="tile-sm-icon">🔍</div>
      <div class="tile-sm-name">Разбор ошибок</div>
      <div class="tile-sm-desc">Скоро</div>
      <div class="tile-badge badge-blue tile-badge-top-right" style="font-size:8px;">Soon</div>
    </div>
    <a href="/tg/history" class="tile-sm">
      <div class="tile-sm-icon">📊</div>
      <div class="tile-sm-name">История</div>
      <div class="tile-sm-desc">Все попытки</div>
    </a>
    <a href="#" class="tile-sm" @click.prevent="handleInvite()">
      <div class="tile-sm-icon">👥</div>
      <div class="tile-sm-name">Позвать друга</div>
      <div class="tile-sm-desc">Пусть тоже готовится</div>
    </a>
    <a href="/tg/tutor" class="tile-sm">
      <div class="tile-sm-icon">👨‍🏫</div>
      <div class="tile-sm-name">Репетитор</div>
      <div class="tile-sm-desc">Бесплатный урок</div>
    </a>
  </div>

  {{-- FULL VARIANT CHOICE MODAL --}}
  <template x-if="showFullChoice">
    <div class="fv-overlay" @click.self="showFullChoice = false">
      <div class="fv-sheet">
        <div class="fv-handle"></div>
        <div class="fv-title">Выбери формат</div>

        <div class="fv-option" @click="startFull(false)">
          <div class="fv-opt-icon">📝</div>
          <div>
            <div class="fv-opt-name">Только 1 часть</div>
            <div class="fv-opt-desc">14 заданий · задачи с кратким ответом</div>
            <div class="fv-opt-badge badge-blue">~45 мин</div>
          </div>
        </div>

        <div class="fv-option" @click="startFull(true)">
          <div class="fv-opt-icon">📝✍️</div>
          <div>
            <div class="fv-opt-name">1 + 2 часть</div>
            <div class="fv-opt-desc">16 заданий · включая уравнения и текстовые задачи</div>
            <div class="fv-opt-badge badge-purple">~60 мин</div>
          </div>
        </div>

        <button class="fv-cancel" @click="showFullChoice = false">Отмена</button>
      </div>
    </div>
  </template>

  {{-- UNFINISHED ATTEMPTS MODAL --}}
  @if(count($activeAttemptsList) > 1)
  <template x-if="showUnfinished">
    <div class="fv-overlay" @click.self="showUnfinished = false">
      <div class="fv-sheet">
        <div class="fv-handle"></div>
        <div class="fv-title">Нерешённые варианты</div>

        @foreach($activeAttemptsList as $att)
        <a href="/tg/test/{{ $att['id'] }}" class="fv-option">
          <div class="fv-opt-icon">{{ $att['type'] === 'Мини-ОГЭ' ? '⚡' : '📝' }}</div>
          <div>
            <div class="fv-opt-name">{{ $att['type'] }}</div>
            <div class="fv-opt-desc">
              Отвечено {{ $att['answeredCount'] }} из {{ $att['totalCount'] }}
              · начат {{ $att['startedAt']?->diffForHumans() }}
            </div>
          </div>
        </a>
        @endforeach

        <button class="fv-cancel" @click="showUnfinished = false">Отмена</button>
      </div>
    </div>
  </template>
  @endif

  {{-- TASK BASE CHOICE MODAL --}}
  <template x-if="showTaskBase">
    <div class="fv-overlay" @click.self="showTaskBase = false">
      <div class="fv-sheet">
        <div class="fv-handle"></div>
        <div class="fv-title">База заданий</div>

        <a href="/tg/new-tasks" class="fv-option">
          <div class="fv-opt-icon">🆕</div>
          <div>
            <div class="fv-opt-name">Новые задания</div>
            <div class="fv-opt-desc">Задания из банка ФИПИ</div>
            @if(($newFipiCount ?? 0) > 0)
            <div class="fv-opt-badge badge-red">{{ $newFipiCount }} новых</div>
            @endif
          </div>
        </a>

        <a href="/tg/part2" class="fv-option">
          <div class="fv-opt-icon">✍️</div>
          <div>
            <div class="fv-opt-name">2я часть ОГЭ</div>
            <div class="fv-opt-desc">Задания 20–25 с развёрнутым ответом</div>
          </div>
        </a>

        <button class="fv-cancel" @click="showTaskBase = false">Отмена</button>
      </div>
    </div>
  </template>

  {{-- WEAK TOPICS --}}
  @if(count($weakTopics) > 0)
  <div class="weak-section">
    <div class="sec-label">Слабые темы</div>
    @foreach($weakTopics as $wt)
    <div class="weak-row">
      <div class="weak-info">
        <div class="weak-num">{{ $wt['task_number'] }}</div>
        <div class="weak-name">{{ $wt['name'] }}</div>
      </div>
      <div class="weak-pct {{ $wt['pct'] < 40 ? 'low' : ($wt['pct'] < 70 ? 'mid' : 'high') }}">{{ $wt['pct'] }}%</div>
    </div>
    @endforeach
  </div>
  @endif

</div>
@endsection

@push('scripts')
<script>
function dashboardPage() {
  const examDate = new Date('2026-06-02T10:00:00+03:00');
  return {
    daysLeft: Math.max(0, Math.floor((examDate - new Date()) / 86400000)),
    showFullChoice: false,
    showTaskBase: false,
    showUnfinished: false,
    startingFull: false,

    async startFull(withPart2 = false) {
      if (this.startingFull) return;
      this.startingFull = true;
      this.showFullChoice = false;

      try {
        const body = withPart2 ? { part2: true } : {};
        const res = await window.fetchPost('/tg/full/start', body);
        if (!res.ok && res.status === 419) {
          alert('Сессия истекла. Перезайдите в приложение.');
          this.startingFull = false;
          return;
        }
        const data = await res.json();
        if (data.redirect) {
          window.location.href = data.redirect;
        } else {
          alert(data.error || data.message || 'Ошибка запуска');
          this.startingFull = false;
        }
      } catch (e) {
        console.error('startFull error:', e);
        alert('Ошибка соединения: ' + e.message);
        this.startingFull = false;
      }
    },

    handleInvite() {
      const tg = window.Telegram?.WebApp;
      const botUsername = '{{ config("services.telegram.bot_username", "palomatika_auth_bot") }}';
      const link = `https://t.me/${botUsername}?startapp=ref_{{ $user->id }}`;
      const text = 'Готовься к ОГЭ по математике бесплатно!';
      const shareUrl = `https://t.me/share/url?url=${encodeURIComponent(link)}&text=${encodeURIComponent(text)}`;

      if (tg && tg.openTelegramLink) {
        tg.openTelegramLink(shareUrl);
      } else {
        window.open(shareUrl, '_blank');
      }
    },
  };
}
</script>
@endpush
