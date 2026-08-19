@extends('layouts.pwa')
@section('title', $title . ' — решение — palomatika')

@push('katex')
@include('partials.head-katex')
@endpush

@push('styles')
@include('pwa.student.partials.part2-solution-styles')
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

  @include('pwa.student.partials.part2-solution-body')
</div>
@endsection
