@extends('layouts.pwa')
@section('title', 'Рефералы — palomatika')

@push('styles')
  .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .metric { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 14px; }
  .metric-label { font-size: 11px; color: var(--muted); margin-bottom: 4px; }
  .metric-value { font-family: var(--display); font-size: 24px; color: var(--text); }
  .ref-list { display: flex; flex-direction: column; gap: 8px; }
  .ref-item { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px; }
  .ref-name { font-size: 14px; font-weight: 800; color: var(--text); }
  .ref-meta { font-size: 11px; color: var(--muted); margin-top: 2px; }
  .ref-count { font-family: var(--display); font-size: 18px; color: var(--accent); }
  .ref-head { display: flex; align-items: center; justify-content: space-between; }
  .role-badge { font-size: 10px; padding: 2px 6px; border-radius: 6px; background: var(--surface2); color: var(--muted); }
  .role-badge.student { background: #1e3a5f; color: #60a5fa; }
  .role-badge.teacher { background: #1a3d2e; color: #4ade80; }
  .arrow { font-size: 11px; color: var(--muted); margin: 0 4px; }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.teacher.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Рефералы</div>
  </div>

  <div class="grid">
    <div class="metric">
      <div class="metric-label">Всего пользователей</div>
      <div class="metric-value">{{ $totalUsers }}</div>
    </div>
    <div class="metric">
      <div class="metric-label">Пришли по рефералу</div>
      <div class="metric-value">{{ $totalReferred }}</div>
    </div>
  </div>

  <div class="sec-label">Топ приглашающих</div>
  <div class="ref-list">
    @forelse($referrers as $ref)
      <div class="ref-item">
        <div class="ref-head">
          <div>
            <div class="ref-name">{{ $ref->name }}</div>
            <div class="ref-meta">
              <span class="role-badge {{ $ref->role }}">{{ $ref->role }}</span>
              · ID {{ $ref->id }}
              · рег. {{ $ref->created_at?->format('d.m.Y') }}
            </div>
          </div>
          <div class="ref-count">{{ $ref->referrals_count }}</div>
        </div>
      </div>
    @empty
      <div class="note">Пока нет рефералов.</div>
    @endforelse
  </div>

  <div class="sec-label">Последние приглашения</div>
  <div class="ref-list">
    @forelse($recentReferrals as $user)
      <div class="ref-item">
        <div class="ref-name">
          {{ $user->referrer?->name ?? '?' }}
          <span class="arrow">→</span>
          {{ $user->name }}
        </div>
        <div class="ref-meta">
          <span class="role-badge {{ $user->referrer?->role }}">{{ $user->referrer?->role }}</span>
          <span class="arrow">→</span>
          <span class="role-badge {{ $user->role }}">{{ $user->role ?: 'new' }}</span>
          · {{ $user->created_at?->format('d.m.Y H:i') }}
        </div>
      </div>
    @empty
      <div class="note">Пока нет приглашённых.</div>
    @endforelse
  </div>
</div>
@endsection
