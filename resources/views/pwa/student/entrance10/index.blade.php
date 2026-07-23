@extends('layouts.pwa')
@section('title', 'Контрольные для 10 класса — palomatika')

@include('pwa.student.entrance10._assets')

@section('body')
<script>
  window.E10 = {
    csrf: document.querySelector('meta[name="csrf-token"]')?.content,
    startUrl: @json(route('pwa.student.practice.entrance10.start')),
  };
</script>
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.student.practice.index') }}" class="back-btn">‹</a>
    <div class="topbar-title">Поступление в 10 класс</div>
  </div>

  <div class="e10-intro">
    <div class="e10-intro-title">{{ $meta['title'] ?? 'Вступительная работа в 10 класс' }}</div>
    @if(!empty($meta['subtitle']))<div class="e10-intro-sub">{{ $meta['subtitle'] }}</div>@endif
    <div class="e10-meta">
      @if(!empty($meta['duration_min']))<span class="e10-chip">⏱ {{ $meta['duration_min'] }} минут</span>@endif
      @if(!empty($meta['task_count']))<span class="e10-chip">📋 {{ $meta['task_count'] }} заданий</span>@endif
      @if(!empty($meta['max_score']))<span class="e10-chip">⭐ {{ $meta['max_score'] }} баллов</span>@endif
    </div>
  </div>

  <div class="e10-section-title">Полные варианты</div>
  <div class="e10-cards">
    @foreach($variantNumbers as $vn)
      <button type="button" class="e10-card e10-start" data-variant="{{ $vn }}">
        <div class="e10-card-icon">{{ $vn }}</div>
        <div style="text-align:left;">
          <div class="e10-card-title">Вариант {{ $vn }}</div>
          <div class="e10-card-desc">Аналог работы · задания а/б/в · проверка в конце</div>
        </div>
        <div class="e10-card-go">▶</div>
      </button>
    @endforeach
  </div>
  <div class="e10-hint" style="margin-top:8px;">Каждый запуск — свежий вариант: задания те же по типу, но с другими числами.</div>

  <div class="e10-section-title">База заданий по номерам</div>
  <div class="e10-num-grid">
    @foreach($numbers as $num)
      <a href="{{ route('pwa.student.practice.entrance10.bank', $num['number']) }}" class="e10-num-cell">
        <span class="e10-num-badge">{{ $num['number'] }}</span>
        <span class="e10-num-label">{{ $num['title'] }}</span>
        <span class="e10-num-tag {{ $num['generatable'] ? 'gen' : 'stat' }}">
          {{ $num['generatable'] ? '20 задач' : '2 задачи' }}
        </span>
      </a>
    @endforeach
  </div>
</div>

<script>
(function () {
  document.querySelectorAll('.e10-start').forEach(btn => {
    btn.addEventListener('click', function () {
      if (btn.dataset.busy) return;
      btn.dataset.busy = '1';
      const go = btn.querySelector('.e10-card-go'); const prev = go ? go.textContent : '';
      if (go) go.textContent = '…';
      fetch(window.E10.startUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.E10.csrf, 'Accept': 'application/json' },
        body: JSON.stringify({ variant: Number(btn.dataset.variant) }),
      })
      .then(r => r.json())
      .then(d => { if (d.redirect) location.href = d.redirect; else throw new Error(d.error || 'err'); })
      .catch(() => { btn.dataset.busy = ''; if (go) go.textContent = prev; alert('Не удалось начать вариант. Попробуйте ещё раз.'); });
    });
  });
})();
</script>
@endsection
