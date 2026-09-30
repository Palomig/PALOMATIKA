@extends('layouts.pwa')
@section('title', 'Позови друга — Palomatika')

@push('styles')
@include('pwa.student.partials.friends-styles')
  .fr-duo { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 16px; }
  .fr-duo-c { background: var(--surface); border: 1px solid var(--green-bd); border-radius: 13px; padding: 12px 10px; }
  .fr-duo-v { font-family: var(--display); font-size: 20px; color: var(--green); }
  .fr-duo-l { font-size: 11px; font-weight: 800; color: var(--muted); margin-top: 3px; line-height: 1.35; }
  .fr-parents { border-color: var(--accent-bd); }
  .fr-parents-k { font-size: 10px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--accent); margin-bottom: 8px; }
  .fr-parents p { font-size: 13px; line-height: 1.6; color: var(--text); font-weight: 600; margin-bottom: 8px; }
  .fr-parents p:last-child { margin-bottom: 0; }
@endpush

@section('body')
@php
  $s = $summary;
  $pct = $svc::DISCOUNT_PERCENT;
@endphp
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.student.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Позови друга</div>
  </div>

  <div class="fr-hero green">
    <div class="fr-sum">−{{ $pct }}%</div>
    <div class="fr-lbl">на следующий месяц занятий — <b>и тебе, и другу</b></div>
    <div class="fr-duo">
      <div class="fr-duo-c"><div class="fr-duo-v">−{{ $pct }}%</div><div class="fr-duo-l">тебе на следующий месяц</div></div>
      <div class="fr-duo-c"><div class="fr-duo-v">−{{ $pct }}%</div><div class="fr-duo-l">другу на следующий месяц</div></div>
    </div>
  </div>
  <div class="fr-fine">Скидка появится, когда друг оплатит месяц занятий</div>

  <div class="fr-blk">
    <div class="fr-h">Как это работает</div>
    <div class="fr-step"><div class="fr-sn">1</div><div class="fr-st">Расскажи родителям<span>Покажи им эту страницу — ниже всё написано для них</span></div></div>
    <div class="fr-step"><div class="fr-sn">2</div><div class="fr-st">Приведи друга на своё занятие<span>Первое — бесплатно: он просто посидит и позанимается с вами в группе</span></div></div>
    <div class="fr-step"><div class="fr-sn">3</div><div class="fr-st">Если друг решит заниматься с нами — вам обоим скидка {{ $pct }}%<span>На следующий месяц, когда он оплатит месяц занятий</span></div></div>
  </div>

  <div class="fr-blk fr-parents">
    <div class="fr-parents-k">Для родителей</div>
    <p>Если друг вашего ребёнка начнёт у нас заниматься, <b>обе семьи получат скидку {{ $pct }}% на следующий месяц занятий</b>.</p>
    <p>Первое занятие для друга бесплатное — он просто придёт вместе с вашим ребёнком и позанимается в группе.</p>
    <p>За каждого приведённого друга — ещё один месяц со скидкой. Оформлять ничего не нужно: скидку применит преподаватель.</p>
  </div>

  @if($s['friends']->isNotEmpty())
  <div class="fr-blk">
    <div class="fr-h">Мои друзья</div>
    @foreach($s['friends'] as $f)
      <div class="fr-row">
        <div class="fr-av {{ $f['qualified'] ? 'ok' : 'go' }}">{{ mb_substr($f['name'], 0, 1) }}</div>
        <div class="fr-m">
          <div class="fr-n">{{ $f['name'] }}</div>
          <div class="fr-s">
            @if(!$f['qualified'])
              Был на первом занятии {{ $f['first_lesson_on']?->format('d.m') }} · ждём оплату месяца
            @elseif($f['paid_at'])
              Занимается · твоя скидка применена
            @else
              Занимается · скидка на твой следующий месяц
            @endif
          </div>
        </div>
        <div class="fr-b {{ $f['qualified'] ? 'ok' : 'go' }}">{{ $f['qualified'] ? '−' . $pct . '%' : 'В пути' }}</div>
      </div>
    @endforeach
  </div>
  @endif

  @include('pwa.student.partials.friends-board')

  <details class="fr-blk fr-rules">
    <summary><span>Условия и правила</span><span>+</span></summary>
    <ul>
      <li>Скидку {{ $pct }}% получаете оба — ты и друг, — когда он оплатит месяц занятий. Одного бесплатного занятия мало.</li>
      <li>Скидка — на следующий месяц. За каждого приведённого друга — ещё один месяц со скидкой.</li>
      <li>Друг должен быть новым — если он уже занимался у нас раньше, не считается.</li>
      <li><b>Кто позвал, говорит сам новичок на первом занятии.</b> Задним числом это не меняется.</li>
      <li>Позвали вдвоём? Пусть скажет про обоих — скидку получит каждый.</li>
      <li>Скидку применяет преподаватель, оформлять ничего не нужно.</li>
    </ul>
  </details>
</div>
@endsection
