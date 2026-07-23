@extends('layouts.pwa')
@section('title', ($variant['title'] ?? 'Вариант') . ' — palomatika')

@include('pwa.student.entrance10._assets')

@section('body')
<script>
  window.E10 = {
    csrf: document.querySelector('meta[name="csrf-token"]')?.content,
    checkUrl: @json(route('pwa.student.practice.entrance10.check')),
  };
</script>
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.student.practice.entrance10.index') }}" class="back-btn">‹</a>
    <div class="topbar-title">{{ $variant['title'] }}</div>
  </div>

  <div class="e10-intro">
    <div class="e10-intro-title">{{ $meta['title'] ?? 'Вступительная работа' }}</div>
    @if(!empty($meta['subtitle']))<div class="e10-intro-sub">{{ $meta['subtitle'] }}</div>@endif
    <div class="e10-meta">
      @if(!empty($meta['duration_min']))<span class="e10-chip">⏱ {{ $meta['duration_min'] }} минут</span>@endif
      @if(!empty($meta['max_score']))<span class="e10-chip">⭐ {{ $meta['max_score'] }} баллов</span>@endif
    </div>
    <div class="e10-rules" style="list-style:none;padding-left:0;">Все задания требуют развёрнутого решения. Здесь можно проверить итоговый ответ и посмотреть разбор.</div>
  </div>

  @foreach($variant['tasks'] as $task)
    @include('pwa.student.entrance10._task', ['task' => $task])
  @endforeach
</div>
@endsection
