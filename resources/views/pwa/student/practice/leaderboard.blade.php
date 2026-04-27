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
    $scopeLabels = ['class' => 'Класс', 'school' => 'Школа', 'all' => 'Все'];
    $periodLabels = ['all_time' => 'За всё время', 'week' => 'На этой неделе'];
  @endphp

  <div class="lb-section-label">Скоп</div>
  <div class="lb-tabs">
    @foreach($scopeLabels as $key => $label)
      @php $enabled = $availableScopes[$key] ?? false; @endphp
      <a href="{{ route('pwa.student.practice.mini-games.leaderboard', ['slug' => $game['slug'], 'scope' => $key, 'period' => $period]) }}"
         class="lb-tab {{ $scope === $key ? 'is-active' : '' }} {{ !$enabled ? 'is-disabled' : '' }}">
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
      Чтобы видеть лидерборд класса, укажи в профиле школу, номер класса и букву.
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
