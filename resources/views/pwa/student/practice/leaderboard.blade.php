@extends('layouts.pwa')
@section('title', 'Лидерборд: ' . $game['title'] . ' — palomatika')

@push('styles')
  .lb-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
  .lb-tab {
    flex: 1 1 0; min-width: max-content; text-align: center;
    padding: 9px 12px; border-radius: 12px; border: 1px solid var(--border);
    background: var(--surface); color: var(--text); font-size: 12px; font-weight: 700;
    text-decoration: none; cursor: pointer;
  }
  .lb-tab.is-active { background: var(--accent); border-color: var(--accent); color: #fff; }
  .lb-tab.is-disabled { opacity: .45; pointer-events: none; }
  .lb-section-label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
  .lb-table { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
  .lb-row {
    display: grid; grid-template-columns: 36px 1fr auto; align-items: center;
    background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
    padding: 10px 14px; gap: 10px;
  }
  .lb-row.is-viewer { border-color: var(--accent); background: rgba(99,102,241,.08); }
  .lb-rank { font-family: var(--display); font-size: 18px; color: var(--muted); }
  .lb-rank.is-top1 { color: #facc15; }
  .lb-rank.is-top2 { color: #d1d5db; }
  .lb-rank.is-top3 { color: #f97316; }
  .lb-name { font-size: 14px; color: var(--text); font-weight: 700; }
  .lb-class { font-size: 11px; color: var(--muted); font-weight: 600; margin-top: 2px; }
  .lb-score { font-family: var(--display); font-size: 18px; color: var(--green); }

  .lb-row.is-top1 {
    grid-template-columns: 48px 1fr auto;
    background:
      radial-gradient(ellipse 60% 100% at 0% 50%, rgba(244,196,48,0.07), transparent 60%),
      var(--surface);
    border: 1px solid rgba(244,196,48,0.22);
    border-left: 3px solid rgba(244,196,48,0.85);
    border-radius: 4px;
    padding: 14px 16px;
    position: relative; overflow: hidden;
  }
  .lb-row.is-top1 .lb-rank {
    font-size: 34px; line-height: 1;
    color: transparent;
    background: linear-gradient(180deg, #fff3a8 0%, #f4c418 55%, #c08a06 100%);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .lb-leader-pill {
    display: inline-flex; align-items: center; gap: 4px;
    background: linear-gradient(180deg, #fde047, #f4c418);
    color: #3a2e0a;
    font-size: 10px; font-weight: 800;
    padding: 3px 8px; border-radius: 4px;
    margin-bottom: 5px;
    letter-spacing: .04em;
    box-shadow: 0 1px 3px rgba(244,196,48,0.35);
  }
  .lb-leader-pill .star { font-size: 9px; }
  .lb-row.is-top1 .lb-name { font-size: 15px; font-weight: 800; }
  .lb-row.is-top1 .lb-score-block { text-align: right; }
  .lb-row.is-top1 .lb-score-value {
    font-family: var(--display); font-size: 26px; line-height: 1;
    color: transparent;
    background: linear-gradient(180deg, #fff3a8, #f4c418 55%, #c08a06);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .lb-row.is-top1 .lb-score-label {
    font-size: 9px; color: rgba(244,196,48,0.7);
    font-weight: 800; text-transform: uppercase; letter-spacing: .1em;
    margin-top: 3px;
  }
  .lb-row.is-top1::before {
    content: ""; position: absolute; inset: 0;
    background: linear-gradient(115deg,
      transparent 38%,
      rgba(255,236,160,0.18) 48%,
      rgba(255,255,255,0.55) 50%,
      rgba(255,236,160,0.18) 52%,
      transparent 62%);
    transform: translateX(-110%);
    animation: lb-shimmer 5s ease-in-out infinite;
    pointer-events: none;
  }
  @keyframes lb-shimmer {
    0%   { transform: translateX(-110%); }
    55%  { transform: translateX(110%); }
    100% { transform: translateX(110%); }
  }
  .lb-empty {
    background: var(--surface); border: 1px dashed var(--border); border-radius: 14px;
    padding: 22px; text-align: center; color: var(--muted); font-size: 13px; font-weight: 600;
  }
  .lb-viewer-divider {
    text-align: center; color: var(--muted); font-size: 11px; font-weight: 700;
    letter-spacing: .12em; margin: 10px 0 6px;
  }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.student.practice.mini-games.show', $game['slug']) }}" class="back-btn">‹</a>
    <div class="topbar-title">🏆 {{ $game['title'] }}</div>
  </div>

  @php
    $scopeLabels = ['all' => 'Все', 'school' => 'Школа', 'class' => 'Класс', 'group' => 'Группа'];
    $periodLabels = ['all_time' => 'За всё время', 'week' => 'На этой неделе'];
    $scopeDisabledHint = [
      'school' => 'Укажи в профиле номер школы',
      'class' => 'Укажи в профиле школу, класс и букву',
      'group' => 'Доступно, когда тебя добавит репетитор',
    ];
  @endphp

  <div class="lb-section-label">Скоп</div>
  <div class="lb-tabs">
    @foreach($scopeLabels as $key => $label)
      @php $enabled = $availableScopes[$key] ?? false; @endphp
      <a href="{{ route('pwa.student.practice.mini-games.leaderboard', ['slug' => $game['slug'], 'scope' => $key, 'period' => $period]) }}"
         class="lb-tab {{ $scope === $key ? 'is-active' : '' }} {{ !$enabled ? 'is-disabled' : '' }}"
         @if(!$enabled) title="{{ $scopeDisabledHint[$key] ?? '' }}" @endif>
        {{ $label }}
      </a>
    @endforeach
  </div>

  <div class="lb-section-label">Период</div>
  <div class="lb-tabs">
    @foreach($periodLabels as $key => $label)
      <a href="{{ route('pwa.student.practice.mini-games.leaderboard', ['slug' => $game['slug'], 'scope' => $scope, 'period' => $key]) }}"
         class="lb-tab {{ $period === $key ? 'is-active' : '' }}">
        {{ $label }}
      </a>
    @endforeach
  </div>

  @if(!$board['available'])
    <div class="lb-empty">
      {{ $scopeDisabledHint[$scope] ?? 'Лидерборд недоступен.' }}
    </div>
  @elseif(empty($board['entries']))
    <div class="lb-empty">
      Здесь пока пусто. Сыграй раунд — твой результат появится первым.
    </div>
  @else
    <div class="lb-table">
      @foreach($board['entries'] as $entry)
        @php
          $rankClass = match ($entry['rank']) { 1 => 'is-top1', 2 => 'is-top2', 3 => 'is-top3', default => '' };
          $isLeader = $entry['rank'] === 1;
        @endphp
        <div class="lb-row {{ $rankClass }} {{ $entry['is_viewer'] ? 'is-viewer' : '' }}">
          <div class="lb-rank {{ $rankClass }}">{{ $entry['rank'] }}</div>
          <div>
            @if($isLeader)
              <div class="lb-leader-pill"><span class="star">★</span> Лучший</div>
            @endif
            <div class="lb-name">{{ $entry['name'] }}{{ $entry['is_viewer'] ? ' (ты)' : '' }}</div>
            @if(!empty($entry['class']))
              <div class="lb-class">{{ $entry['class'] }}</div>
            @endif
          </div>
          @if($isLeader)
            <div class="lb-score-block">
              <div class="lb-score-value">{{ $entry['score'] }}</div>
              <div class="lb-score-label">очков</div>
            </div>
          @else
            <div class="lb-score">{{ $entry['score'] }}</div>
          @endif
        </div>
      @endforeach
    </div>

    @if(!empty($board['viewer_entry']))
      <div class="lb-viewer-divider">···</div>
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
@endsection
