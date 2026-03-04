@extends('layouts.miniapp')
@section('title', 'Дашборд — palomatika')

@push('styles')
  .greeting { opacity: 0; animation: fadeDown 0.3s ease 0s forwards; }
  .greeting-name { font-family: var(--display); font-size: 20px; color: var(--text); }
  .greeting-sub { font-size: 12px; font-weight: 600; color: var(--muted); margin-top: 2px; }

  /* COUNTDOWN STRIP */
  .cd-strip {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 12px 16px;
    display: flex; align-items: center; justify-content: space-between;
    opacity: 0; animation: fadeUp 0.3s ease 0.06s forwards;
  }
  .cd-strip-left { display: flex; align-items: center; gap: 8px; }
  .cd-strip-label { font-size: 11px; font-weight: 700; color: var(--muted); }
  .cd-strip-val { font-family: var(--display); font-size: 14px; color: var(--text); }
  .cd-strip-date { font-size: 10px; font-weight: 700; color: var(--muted); }

  /* LAST RESULT */
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
  }
  .tile-sm:active { background: var(--surface2); }
  .tile-sm-icon { font-size: 22px; margin-bottom: 6px; }
  .tile-sm-name { font-size: 13px; font-weight: 800; color: var(--text); margin-bottom: 2px; }
  .tile-sm-desc { font-size: 10px; font-weight: 600; color: var(--muted); line-height: 1.3; }

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
@endpush

@section('body')
<div class="page" x-data="dashboardPage()">

  {{-- GREETING --}}
  <div class="greeting">
    <div class="greeting-name">Привет, {{ $user->name ?? 'ученик' }}!</div>
    <div class="greeting-sub">Математика · ОГЭ 2026</div>
  </div>

  {{-- COUNTDOWN STRIP --}}
  <div class="cd-strip">
    <div class="cd-strip-left">
      <span class="pulse-dot-sm"></span>
      <span class="cd-strip-label">До ОГЭ</span>
      <span class="cd-strip-val" x-text="daysLeft + ' дн'">— дн</span>
    </div>
    <div class="cd-strip-date">2 июня 2026</div>
  </div>

  {{-- LAST RESULT --}}
  @if($lastAttempt)
  <div class="last-result">
    <div class="lr-header">
      <div class="lr-title">Последний результат</div>
      <div class="lr-date">{{ $lastAttempt->submitted_at?->format('d.m.Y') ?? '' }}</div>
    </div>
    <div class="lr-body">
      <div class="lr-score">{{ $lastCorrect }}<small>/{{ $lastTotal }}</small></div>
      <div class="lr-details">
        <div class="lr-label">правильных ответов</div>
        @if($lastTime)
        <div class="lr-time">⏱ {{ floor($lastTime / 60) }}:{{ str_pad($lastTime % 60, 2, '0', STR_PAD_LEFT) }}</div>
        @endif
      </div>
    </div>
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
      <div class="tile-badge badge-purple">Новое</div>
    </a>
    <a href="#" class="tile-big tile-blue" @click.prevent="startFull()">
      <div class="tile-icon">📝</div>
      <div class="tile-name">Полный вариант</div>
      <div class="tile-desc">14 заданий, как на экзамене</div>
      <div class="tile-badge badge-blue">~45 мин</div>
    </a>
  </div>

  {{-- SMALL TILES --}}
  <div class="tiles-grid">
    <a href="/tg/mini" class="tile-sm">
      <div class="tile-sm-icon">🆕</div>
      <div class="tile-sm-name">Новые задания</div>
      <div class="tile-sm-desc">из банка ФИПИ</div>
    </a>
    @if($lastAttempt)
    <a href="/tg/results/{{ $lastAttempt->id }}" class="tile-sm">
      <div class="tile-sm-icon">🔍</div>
      <div class="tile-sm-name">Разбор ошибок</div>
      <div class="tile-sm-desc">Посмотри, где ошибся</div>
    </a>
    @else
    <div class="tile-sm" style="opacity:0.5;">
      <div class="tile-sm-icon">🔍</div>
      <div class="tile-sm-name">Разбор ошибок</div>
      <div class="tile-sm-desc">Пройди тест</div>
    </div>
    @endif
    <div class="tile-sm" style="opacity:0.5;">
      <div class="tile-sm-icon">📊</div>
      <div class="tile-sm-name">История</div>
      <div class="tile-sm-desc">Все попытки</div>
    </div>
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

    async startFull() {
      try {
        const res = await window.fetchPost('/tg/full/start');
        const data = await res.json();
        if (data.redirect) {
          window.location.href = data.redirect;
        } else {
          alert(data.error || 'Ошибка запуска');
        }
      } catch (e) {
        alert('Ошибка соединения');
      }
    },

    handleInvite() {
      const tg = window.Telegram?.WebApp;
      const botUsername = '{{ config("services.telegram.bot_username", "palomatika_auth_bot") }}';
      const link = `https://t.me/${botUsername}/app`;
      const text = 'Готовься к ОГЭ по математике бесплатно! ' + link;
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
          if (tg && tg.showAlert) { tg.showAlert('Ссылка скопирована! Отправь другу в чат'); }
          else { alert('Ссылка скопирована!'); }
        });
      } else if (navigator.share) {
        navigator.share({ title: 'palomatika — ОГЭ', text: text });
      }
    },
  };
}
</script>
@endpush
