@extends('layouts.pwa')
@section('title', 'Практика — palomatika')

@push('styles')
  .practice-categories { display: flex; flex-direction: column; gap: 12px; }
  .practice-category {
    display: flex; align-items: center; gap: 14px; text-decoration: none; color: inherit;
    background: linear-gradient(180deg, rgba(79,142,247,.08), transparent), var(--surface);
    border: 1.5px solid var(--accent-bd); border-radius: 20px; padding: 18px;
    opacity: 0; animation: fadeUp 0.3s ease 0.08s forwards;
  }
  .practice-category-icon {
    width: 52px; height: 52px; border-radius: 16px; flex-shrink: 0;
    background: var(--accent-bg); display: flex; align-items: center; justify-content: center; font-size: 26px;
  }
  .practice-category-title { font-family: var(--display); font-size: 17px; margin-bottom: 4px; }
  .practice-category-desc { font-size: 12px; font-weight: 600; color: var(--muted); line-height: 1.45; }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.student.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Практика</div>
  </div>

  <div class="practice-categories">
    @foreach($categories as $category)
      <a href="{{ route('pwa.student.practice.category', $category['slug']) }}" class="practice-category">
        <div class="practice-category-icon">{{ $category['icon'] ?? '🎮' }}</div>
        <div>
          <div class="practice-category-title">{{ $category['title'] }}</div>
          <div class="practice-category-desc">{{ $category['description'] }}</div>
        </div>
      </a>
    @endforeach
  </div>
</div>
@endsection
