@extends('layouts.pwa')
@section('title', $title . ' — решение — palomatika')

@push('katex')
@include('partials.head-katex')
@endpush

@push('styles')
  .sol-wrap { padding-bottom: 32px; }
  .sol-badge {
    display: inline-flex; align-items: center; gap: 6px;
    margin-bottom: 12px; padding: 4px 10px; border-radius: 999px;
    background: rgba(58,96,152,.18); border: 1px solid #3a6098;
    color: #9cc2f0; font-size: 12px; font-weight: 600;
  }
  .sol-title { font-family: var(--display); font-size: 20px; color: var(--text); margin: 4px 0 2px; }
  .sol-subtitle { color: var(--text-muted); font-size: 14px; margin-bottom: 12px; }
  .sol-instruction {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px; margin-bottom: 16px;
    color: var(--text); font-size: 15px; line-height: 1.5;
  }
  .sol-body { color: var(--text); font-size: 15px; line-height: 1.6; }
  .sol-body p { margin: 0 0 12px; }
  .sol-body ul { margin: 0 0 12px; padding-left: 20px; }
  .sol-body li { margin-bottom: 6px; }
  .sol-body .formula { display: block; text-align: center; margin: 12px 0; overflow-x: auto; }
  .sol-body .sol-figure {
    display: block; margin: 16px auto; max-width: 360px;
    background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 10px;
  }
  .sol-body .sol-figure svg { display: block; width: 100%; height: auto; }
  .sol-body .answer {
    margin-top: 16px; padding: 12px 14px; border-radius: 12px;
    background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.4);
    color: var(--text); font-weight: 600; font-size: 16px;
  }
  .sol-body .step-note { color: var(--text-muted); }
@endpush

@section('body')
<div class="page sol-wrap task-render-scope">
  <div class="page-header">
    <a href="{{ url('/part2?topic=' . $topic) }}" class="back-btn">‹</a>
    <div class="header-title">
      <div class="header-name">Подробное решение</div>
      <div class="header-desc">Задание {{ $topic }} · только для учителя</div>
    </div>
  </div>

  <span class="sol-badge">📖 материал для учителя</span>
  <div class="sol-title">{{ $title }}</div>
  @if($subtitle)
    <div class="sol-subtitle">{{ $subtitle }}</div>
  @endif

  @if($instruction !== '')
    <div class="sol-instruction">{{ $instruction }}</div>
  @endif

  <div class="sol-body">
    {!! $solution !!}
  </div>
</div>
@endsection
