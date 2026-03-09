@extends('layouts.miniapp')
@section('title', 'Мини-ОГЭ — palomatika')

@push('styles')
  /* MODE CARDS */
  .modes { display: flex; flex-direction: column; gap: 10px; opacity: 0; animation: fadeUp 0.3s ease 0.13s forwards; }
  .mode-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--r); overflow: hidden;
    cursor: pointer; transition: border-color 0.15s, background 0.15s;
    user-select: none; -webkit-user-select: none;
  }
  .mode-card:active { background: var(--surface2); }
  .mode-card-inner { padding: 16px 18px; display: flex; align-items: center; gap: 14px; }
  .mode-icon-wrap {
    width: 48px; height: 48px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; flex-shrink: 0;
  }
  .mode-body { flex: 1; min-width: 0; }
  .mode-name { font-family: var(--display); font-size: 15px; color: var(--text); margin-bottom: 3px; }
  .mode-desc { font-size: 12px; font-weight: 600; color: var(--muted); line-height: 1.45; }
  .mode-right { flex-shrink: 0; text-align: right; }
  .mode-count { font-family: var(--display); font-size: 22px; color: var(--text); line-height: 1; }
  .mode-count-label { font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; }

  .mode-tags {
    padding: 8px 18px 12px; display: flex; gap: 6px; flex-wrap: wrap;
    border-top: 1px solid var(--border);
  }
  .tag { padding: 3px 9px; border-radius: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.04em; }

  /* COLOR VARIANTS */
  .card-geo  { border-color: var(--purple-bd); }
  .card-geo:active { background: var(--purple-bg); }
  .icon-geo  { background: var(--purple-bg); }
  .tag-geo   { background: var(--purple-bg); color: var(--purple); }

  .card-alg  { border-color: var(--green-bd); }
  .card-alg:active { background: var(--green-bg); }
  .icon-alg  { background: var(--green-bg); }
  .tag-alg   { background: var(--green-bg); color: var(--green); }

  .card-mix  { border-color: var(--yellow-bd); }
  .card-mix:active { background: var(--yellow-bg); }
  .icon-mix  { background: var(--yellow-bg); }
  .tag-mix   { background: var(--yellow-bg); color: var(--yellow); }

  .hero-mini {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 20px;
    opacity: 0; animation: fadeUp 0.3s ease 0.06s forwards;
  }
  .hero-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
  .hero-icon { font-size: 36px; line-height: 1; }
  .hero-title { font-family: var(--display); font-size: 22px; color: var(--text); line-height: 1.2; margin-bottom: 8px; }
  .hero-sub { font-size: 13px; font-weight: 600; color: var(--muted); line-height: 1.55; }

  .sec-label-anim { opacity: 0; animation: fadeUp 0.3s ease 0.1s forwards; }
  .note-anim { opacity: 0; animation: fadeUp 0.3s ease 0.2s forwards; }
@endpush

@section('body')
<div class="page" x-data="miniPage()">

  <div class="topbar">
    <a href="/tg/dashboard" class="back-btn">‹</a>
    <div class="topbar-title">Мини-ОГЭ</div>
  </div>

  <div class="hero-mini">
    <div class="hero-top">
      <div class="hero-icon">⚡</div>
      <div class="pill pill-purple">Новое</div>
    </div>
    <div class="hero-title">Тренируйся<br>по темам</div>
    <div class="hero-sub">
      Не всегда есть время на полный вариант. Мини-ОГЭ — это короткая тренировка по одной теме. Выбери что хочешь прокачать сегодня.
    </div>
    <div class="stat-pills" style="margin-top:14px;">
      <div class="stat-pill">⏱ <span>10–15 мин</span></div>
      <div class="stat-pill">📝 <span>5–7 заданий</span></div>
      <div class="stat-pill">🎯 <span>по теме</span></div>
    </div>
  </div>

  <div class="sec-label sec-label-anim">Выбери тему</div>

  <div class="modes">

    {{-- ГЕОМЕТРИЯ --}}
    <div class="mode-card card-geo" @click="startMode('geometry')">
      <div class="mode-card-inner">
        <div class="mode-icon-wrap icon-geo">📐</div>
        <div class="mode-body">
          <div class="mode-name">Геометрия</div>
          <div class="mode-desc">Углы, треугольники, площади, окружности и координатная плоскость</div>
        </div>
        <div class="mode-right">
          <div class="mode-count">5</div>
          <div class="mode-count-label">заданий</div>
        </div>
      </div>
      <div class="mode-tags">
        <span class="tag tag-geo">~10 мин</span>
        <span class="tag tag-geo">Задания 15, 16, 17, 18, 19</span>
      </div>
    </div>

    {{-- АЛГЕБРА --}}
    <div class="mode-card card-alg" @click="startMode('algebra')">
      <div class="mode-card-inner">
        <div class="mode-icon-wrap icon-alg">🔢</div>
        <div class="mode-body">
          <div class="mode-name">Алгебра</div>
          <div class="mode-desc">Уравнения, функции, неравенства, графики и выражения</div>
        </div>
        <div class="mode-right">
          <div class="mode-count">5</div>
          <div class="mode-count-label">заданий</div>
        </div>
      </div>
      <div class="mode-tags">
        <span class="tag tag-alg">~10 мин</span>
        <span class="tag tag-alg">5 из заданий 6–14</span>
      </div>
    </div>

    {{-- СМЕШАННОЕ --}}
    <div class="mode-card card-mix" @click="startMode('mixed')">
      <div class="mode-card-inner">
        <div class="mode-icon-wrap icon-mix">🔀</div>
        <div class="mode-body">
          <div class="mode-name">Смешанное</div>
          <div class="mode-desc">4 задания алгебры + 3 геометрии</div>
        </div>
        <div class="mode-right">
          <div class="mode-count">7</div>
          <div class="mode-count-label">заданий</div>
        </div>
      </div>
      <div class="mode-tags">
        <span class="tag tag-mix">~15 мин</span>
        <span class="tag tag-mix">4 алгебра + 3 геометрия</span>
      </div>
    </div>

  </div>

  <div class="note note-anim">
    💡 Результаты мини-теста учитываются в статистике и блоке «Слабые темы» так же, как полный вариант.
  </div>

</div>
@endsection

@push('scripts')
<script>
function miniPage() {
  return {
    async startMode(mode) {
      try {
        const res = await window.fetchPost('/tg/mini/start', { mode });
        if (!res.ok && res.status === 419) {
          alert('Сессия истекла. Перезайдите в приложение.');
          return;
        }
        const data = await res.json();
        if (data.redirect) {
          window.location.href = data.redirect;
        } else {
          alert(data.error || data.message || 'Ошибка запуска');
        }
      } catch (e) {
        console.error('startMode error:', e);
        alert('Ошибка соединения: ' + e.message);
      }
    },
  };
}
</script>
@endpush
