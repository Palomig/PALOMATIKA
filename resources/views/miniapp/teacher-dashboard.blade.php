@extends('layouts.miniapp')
@section('title', 'Кабинет учителя — palomatika')

@push('styles')
  .top-actions { display: flex; gap: 8px; flex-wrap: wrap; }
  .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .metric { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 14px; }
  .metric-label { font-size: 11px; color: var(--muted); margin-bottom: 4px; }
  .metric-value { font-family: var(--display); font-size: 24px; color: var(--text); }
  .list { display: flex; flex-direction: column; gap: 8px; }
  .list-item { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px; }
  .list-title { font-size: 13px; font-weight: 700; color: var(--text); }
  .list-meta { font-size: 11px; color: var(--muted); margin-top: 2px; }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="/tg" class="back-btn">‹</a>
    <div class="topbar-title">Кабинет учителя</div>
  </div>

  @if($canSwitchMode)
    <div class="top-actions">
      <form method="POST" action="/tg/mode/student">@csrf<button class="btn btn-surface" type="submit">Режим ученика</button></form>
      <form method="POST" action="/tg/mode/teacher">@csrf<button class="btn btn-accent" type="submit">Режим учителя</button></form>
    </div>
  @endif

  <div class="grid">
    <div class="metric">
      <div class="metric-label">Ученики</div>
      <div class="metric-value">{{ $studentCount }}</div>
    </div>
    <div class="metric">
      <div class="metric-label">С алиасом</div>
      <div class="metric-value">{{ $aliasedCount }}</div>
    </div>
    <div class="metric">
      <div class="metric-label">Варианты</div>
      <div class="metric-value">{{ $variantsCount }}</div>
    </div>
    <div class="metric">
      <div class="metric-label">Кураторские</div>
      <div class="metric-value">{{ $curatedCount }}</div>
    </div>
  </div>

  <a class="btn btn-accent" href="/tg/teacher/students">Ученики и алиасы</a>
  <a class="btn btn-surface" href="/tg/teacher/variants">Мои варианты</a>
  <a class="btn btn-surface" href="/tg/admin/variants">Создать вариант</a>
  @if($user->isAdmin())
    <a class="btn btn-surface" href="/tg/teacher/referrals">📊 Рефералы</a>
  @endif

  <div class="sec-label">Последние варианты</div>
  <div class="list">
    @forelse($recentVariants as $variant)
      <div class="list-item">
        <div class="list-title">{{ $variant->title ?: ('Вариант ' . $variant->hash) }}</div>
        <div class="list-meta">{{ $variant->created_at?->format('d.m.Y H:i') }} · {{ $variant->is_curated ? 'Кураторский' : 'Генератор' }}</div>
      </div>
    @empty
      <div class="note">Пока нет вариантов.</div>
    @endforelse
  </div>
</div>
@endsection
