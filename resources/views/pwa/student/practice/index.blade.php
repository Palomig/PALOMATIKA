@extends('layouts.pwa')
@section('title', 'Практика — palomatika')

@push('styles')
  .hero-practice {
    background:
      radial-gradient(circle at top right, rgba(79,142,247,.18), transparent 36%),
      radial-gradient(circle at bottom left, rgba(52,208,126,.12), transparent 38%),
      var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 22px;
    opacity: 0; animation: fadeUp 0.3s ease 0.06s forwards;
  }
  .hero-practice-title { font-family: var(--display); font-size: 24px; line-height: 1.15; margin: 10px 0 8px; }
  .hero-practice-sub { font-size: 13px; font-weight: 600; color: var(--muted); line-height: 1.55; }
  .practice-link {
    display: block; text-decoration: none; color: inherit;
    background: linear-gradient(180deg, rgba(79,142,247,.08), transparent), var(--surface);
    border: 1.5px solid var(--accent-bd); border-radius: 20px; padding: 18px;
    opacity: 0; animation: fadeUp 0.3s ease 0.12s forwards;
  }
  .practice-link-title { font-family: var(--display); font-size: 17px; margin-bottom: 4px; }
  .practice-link-desc { font-size: 12px; font-weight: 600; color: var(--muted); line-height: 1.45; }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.student.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Практика</div>
  </div>

  <div class="hero-practice">
    <div class="pill pill-accent">Новый раздел</div>
    <div class="hero-practice-title">Практика навыков<br>в коротком формате</div>
    <div class="hero-practice-sub">
      Здесь будут быстрые тренажёры и мини-игры по конкретным темам. Начни с уравнений и закрепи перенос через равно.
    </div>
    <div class="stat-pills" style="margin-top:16px;">
      <div class="stat-pill">⚡ <span>короткие сессии</span></div>
      <div class="stat-pill">🎯 <span>по одной теме</span></div>
      <div class="stat-pill">🧠 <span>на типичные ошибки</span></div>
    </div>
  </div>

  <a href="{{ route('pwa.student.practice.mini-games') }}" class="practice-link">
    <div class="pill pill-green" style="width:max-content;margin-bottom:12px;">Мини-игры</div>
    <div class="practice-link-title">Тренажёры на скорость</div>
    <div class="practice-link-desc">Один ход, два варианта ответа, таймер и игра до первой ошибки.</div>
  </a>
</div>
@endsection
