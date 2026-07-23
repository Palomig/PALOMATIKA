@extends('layouts.pwa')
@section('title', 'Задание ' . $info['number'] . ' — база — palomatika')

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
    <div class="topbar-title">База · Задание {{ $info['number'] }}</div>
  </div>

  <div class="e10-intro">
    <div class="e10-intro-title">№{{ $info['number'] }}. {{ $info['title'] }}</div>
    <div class="e10-intro-sub">{{ count($tasks) }} задач для отработки. Ответ и решение — по кнопке.</div>
  </div>

  {{-- Переключатель номеров --}}
  <div class="e10-num-grid" style="grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:14px;">
    @foreach($numbers as $num)
      <a href="{{ route('pwa.student.practice.entrance10.bank', $num['number']) }}"
         class="e10-num-cell {{ $num['number'] === $info['number'] ? 'is-active' : '' }}"
         style="min-height:auto;align-items:center;padding:10px 6px;">
        <span class="e10-num-badge">{{ $num['number'] }}</span>
      </a>
    @endforeach
  </div>

  @foreach($tasks as $i => $task)
    <div class="e10-task-index">{{ $i + 1 }}</div>
    @include('pwa.student.entrance10._task', ['task' => $task])
  @endforeach
</div>
@endsection
