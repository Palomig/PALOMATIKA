@extends('layouts.pwa')
@section('title', 'Задание ' . $info['number'] . ' — palomatika')

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
    <div class="topbar-title">Задание {{ $info['number'] }}</div>
  </div>

  <div class="e10-intro">
    <div class="e10-intro-title">№{{ $info['number'] }}. {{ $info['title'] }}</div>
    <div class="e10-intro-sub">
      {{ $generatable
          ? 'Задачи из вариантов и бесконечная генерация похожих. Вводите ответ и проверяйте.'
          : 'Задачи из вариантов. Введите ответ и проверьте, при затруднении — смотрите разбор.' }}
    </div>
  </div>

  {{-- Переключатель номеров --}}
  <div class="e10-num-grid" style="grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:8px;">
    @foreach($numbers as $num)
      <a href="{{ route('pwa.student.practice.entrance10.number', $num['number']) }}"
         class="e10-num-cell {{ $num['number'] === $info['number'] ? 'is-active' : '' }}"
         style="min-height:auto;align-items:center;padding:10px 6px;">
        <span class="e10-num-badge">{{ $num['number'] }}</span>
      </a>
    @endforeach
  </div>

  @if($generatable)
    <button type="button" id="e10-gen" class="e10-gen-btn"
            data-url="{{ route('pwa.student.practice.entrance10.generate', $info['number']) }}">
      ✨ Сгенерировать похожую задачу
    </button>
  @endif
  <div id="e10-gen-slot"></div>

  <div class="e10-section-title" style="margin-top:6px;">Из вариантов</div>
  @forelse($staticTasks as $task)
    @include('pwa.student.entrance10._task', ['task' => $task])
  @empty
    <p class="e10-card-desc">Для этого номера пока нет задач.</p>
  @endforelse
</div>
@endsection
