@extends('layouts.miniapp')
@section('title', 'Варианты учителя — palomatika')

@push('styles')
  .variant-list { display: flex; flex-direction: column; gap: 10px; }
  .variant-item { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 12px; }
  .variant-title { font-size: 14px; font-weight: 800; color: var(--text); }
  .variant-meta { margin-top: 4px; font-size: 11px; color: var(--muted); }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="/tg/teacher/dashboard" class="back-btn">‹</a>
    <div class="topbar-title">Варианты</div>
  </div>

  <a href="/tg/admin/variants" class="btn btn-accent">Создать новый вариант</a>

  <div class="variant-list">
    @forelse($variants as $variant)
      <div class="variant-item">
        <div class="variant-title">{{ $variant->title ?: ('Вариант ' . $variant->hash) }}</div>
        <div class="variant-meta">
          {{ $variant->is_curated ? 'Кураторский' : 'Генератор' }} ·
          Попыток: {{ $variant->attempts_count }} ·
          {{ $variant->created_at?->format('d.m.Y H:i') }}
        </div>
      </div>
    @empty
      <div class="note">Вариантов пока нет.</div>
    @endforelse
  </div>

  @if($variants->hasPages())
    <div>{{ $variants->links() }}</div>
  @endif
</div>
@endsection
