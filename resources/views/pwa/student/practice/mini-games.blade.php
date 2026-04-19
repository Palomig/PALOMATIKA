@extends('layouts.pwa')
@section('title', 'Мини-игры — palomatika')

@push('styles')
  .games-list { display: flex; flex-direction: column; gap: 12px; }
  .game-card {
    display: flex; align-items: flex-start; gap: 14px; text-decoration: none; color: inherit;
    background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 18px;
    opacity: 0; animation: fadeUp 0.3s ease 0.08s forwards;
  }
  .game-card-icon {
    width: 52px; height: 52px; border-radius: 16px; flex-shrink: 0;
    background: var(--green-bg); display: flex; align-items: center; justify-content: center; font-size: 26px;
  }
  .game-card-title { font-family: var(--display); font-size: 17px; margin-bottom: 5px; }
  .game-card-desc { font-size: 12px; font-weight: 600; color: var(--muted); line-height: 1.5; }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.student.practice.index') }}" class="back-btn">‹</a>
    <div class="topbar-title">Мини-игры</div>
  </div>

  <div class="note anim-up" style="animation-delay:.04s;">
    Короткие игровые тренировки, где мы отрабатываем один навык до автоматизма. Сейчас доступна первая тема.
  </div>

  <div class="games-list">
    @foreach($games as $game)
      <a href="{{ route('pwa.student.practice.mini-games.show', $game['slug']) }}" class="game-card">
        <div class="game-card-icon">{{ $game['icon'] ?? '🎮' }}</div>
        <div>
          <div class="pill pill-green" style="width:max-content;margin-bottom:10px;">Активно</div>
          <div class="game-card-title">{{ $game['title'] }}</div>
          <div class="game-card-desc">{{ $game['description'] }}</div>
        </div>
      </a>
    @endforeach
  </div>
</div>
@endsection
