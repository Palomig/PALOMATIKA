@extends('layouts.pwa')
@section('title', 'Позови друга — Palomatika')

@push('styles')
@include('pwa.student.partials.friends-styles')
  .fr-l3-row { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--border);
    font-size: 13px; font-weight: 700; color: var(--text); }
  .fr-l3-row b { margin-left: auto; font-family: var(--display); font-weight: 400; font-size: 15px; }
  .fr-l3-n { width: 24px; height: 24px; flex: 0 0 auto; border-radius: 999px; background: var(--surface2); color: var(--muted);
    font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
  .fr-l3-third { margin-top: 12px; padding: 14px; border-radius: 14px; border: 1px solid var(--yellow-bd);
    background: linear-gradient(180deg, var(--yellow-bg), transparent); }
  .fr-l3-top { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 800; color: var(--text); }
  .fr-l3-top .fr-l3-n { background: var(--yellow); color: #221806; }
  .fr-l3-badge { margin-left: auto; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;
    color: var(--yellow); background: var(--yellow-bg); border: 1px solid var(--yellow-bd); border-radius: 999px; padding: 3px 9px; }
  .fr-l3-calc { font-family: var(--display); font-size: 22px; color: var(--text); margin-top: 10px; line-height: 1.25; }
  .fr-l3-calc em { font-style: normal; color: var(--yellow); }
  .fr-l3-eq { font-size: 12px; font-weight: 800; color: var(--yellow); margin-top: 4px; }
  .fr-l3-total { display: flex; justify-content: space-between; margin-top: 12px; font-size: 12.5px; font-weight: 800; color: var(--muted); }
  .fr-l3-total b { font-family: var(--display); font-weight: 400; font-size: 17px; color: var(--green); }
  .fr-slots { display: flex; align-items: flex-start; justify-content: center; gap: 6px; margin-top: 16px; }
  .fr-slot { flex: 1; max-width: 92px; text-align: center; }
  .fr-slot-c { width: 50px; height: 50px; margin: 0 auto 7px; border-radius: 999px; border: 2px solid var(--muted2);
    display: flex; align-items: center; justify-content: center; font-family: var(--display); font-size: 17px; color: var(--muted); }
  .fr-slot.done .fr-slot-c { border-color: var(--green); color: var(--green); }
  .fr-slot.gift .fr-slot-c { border-style: dashed; border-color: var(--yellow); color: var(--yellow); }
  .fr-slot.gift.done .fr-slot-c { border-style: solid; }
  .fr-slot-l { font-size: 10.5px; font-weight: 800; color: var(--muted); line-height: 1.35; }
  .fr-slot.gift .fr-slot-l { color: var(--yellow); }
  .fr-arm { flex: 0 0 12px; height: 2px; border-radius: 2px; background: var(--muted2); margin-top: 25px; }
@endpush

@section('body')
@php
  $s = $summary;
  $started = $s['earned'] > 0 || $s['friends']->isNotEmpty();
  $cycleBase = intdiv($s['earned'], $svc::BONUS_STEP) * $svc::BONUS_EVERY;
@endphp
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.student.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Позови друга</div>
  </div>

  @if($started)
    <div class="fr-hero {{ $s['earned'] > 0 ? 'gold' : '' }}">
      <div class="fr-sum">{{ $svc->rub($s['total']) }}</div>
      <div class="fr-lbl">заработано</div>
      <div class="fr-slots">
        @foreach(range(1, $svc::BONUS_EVERY) as $i)
          @php
            $fill = max(0, min(1, $s['friendsInCycle'] - ($i - 1)));
            $gift = $i === $svc::BONUS_EVERY;
            $pct = (int) round($fill * 100);
          @endphp
          @if($i > 1)<div class="fr-arm"></div>@endif
          <div class="fr-slot {{ $gift ? 'gift' : '' }} {{ $fill >= 1 ? 'done' : '' }}">
            <div class="fr-slot-c" style="background: linear-gradient(to top, {{ $gift ? 'var(--yellow-bg)' : 'var(--green-bg)' }} {{ $pct }}%, transparent {{ $pct }}%)">
              {{ $fill >= 1 ? '✓' : ($gift ? '🎁' : $cycleBase + $i) }}
            </div>
            <div class="fr-slot-l">{{ $cycleBase + $i }}-й друг<br>{{ $gift ? '+' . $svc->rub($svc::BONUS) : $svc->rub($svc::REWARD) }}</div>
          </div>
        @endforeach
      </div>
      <div class="fr-lbl" style="margin-top:12px">
        Ещё {{ $svc->friendsWord($s['friendsToBonus']) }} — и получишь <b>{{ $svc->rub($svc::REWARD) }} + {{ $svc->rub($svc::BONUS) }} сверху</b>
      </div>
    </div>
  @else
    <div class="fr-hero">
      <div class="fr-sum">{{ $svc->rub($svc::REWARD) }}</div>
      <div class="fr-lbl">наличными за каждого друга, который начнёт у нас заниматься</div>
    </div>
  @endif

  <div class="fr-blk">
    <div class="fr-h">Сколько получишь</div>
    <div class="fr-l3-row"><span class="fr-l3-n">1</span><span>Первый друг</span><b>{{ $svc->rub($svc::REWARD) }}</b></div>
    <div class="fr-l3-row" style="border-bottom:none"><span class="fr-l3-n">2</span><span>Второй друг</span><b>{{ $svc->rub($svc::REWARD) }}</b></div>
    <div class="fr-l3-third">
      <div class="fr-l3-top"><span class="fr-l3-n">3</span><span>Третий друг</span><span class="fr-l3-badge">🎁 Бонус</span></div>
      <div class="fr-l3-calc">{{ $svc->rub($svc::REWARD) }} <em>+ {{ $svc->rub($svc::BONUS) }} сверху</em></div>
      <div class="fr-l3-eq">= {{ $svc->rub($svc::REWARD + $svc::BONUS) }} за одного друга</div>
    </div>
    <div class="fr-l3-total"><span>За трёх друзей</span><b>{{ $svc->rub($svc::REWARD * $svc::BONUS_EVERY + $svc::BONUS) }}</b></div>
  </div>
  <div class="fr-fine">Деньги отдаём, когда друг оплатит месяц занятий</div>

  <div class="fr-blk">
    <div class="fr-h">Как это работает</div>
    <div class="fr-step"><div class="fr-sn">1</div><div class="fr-st">Предупреди преподавателя<span>Скажи, что придёшь с другом, — чтобы для него нашлось место</span></div></div>
    <div class="fr-step"><div class="fr-sn">2</div><div class="fr-st">Приведи друга на своё занятие<span>Первое — бесплатно: он просто посидит и позанимается с вами в группе</span></div></div>
    <div class="fr-step"><div class="fr-sn">3</div><div class="fr-st">Если друг решит заниматься с нами — забираешь {{ $svc->rub($svc::REWARD) }}<span>Получишь деньги, когда он оплатит месяц занятий</span></div></div>
  </div>

  @if($s['friends']->isNotEmpty())
  <div class="fr-blk">
    <div class="fr-h">Мои друзья</div>
    @foreach($s['friends'] as $f)
      <div class="fr-row">
        <div class="fr-av {{ $f['qualified'] ? 'ok' : 'go' }}">{{ ['', '½', '⅓'][count($f['others'])] ?: mb_substr($f['name'], 0, 1) }}</div>
        <div class="fr-m">
          <div class="fr-n">{{ $f['name'] }}</div>
          <div class="fr-s">
            @if(!$f['qualified'])
              Был на первом занятии {{ $f['first_lesson_on']?->format('d.m') }} · ждём оплату месяца
            @elseif($f['paid_at'])
              Занимается · получено {{ $f['paid_at']->format('d.m') }}
            @else
              Занимается · деньги у преподавателя
            @endif
            @if(count($f['others'])) · {{ $svc->splitText($f['others']) }} @endif
          </div>
        </div>
        <div class="fr-b {{ $f['qualified'] ? 'ok' : 'go' }}">{{ $f['qualified'] ? $svc->rub($f['amount']) : 'В пути' }}</div>
      </div>
    @endforeach
    @foreach($s['bonuses'] as $b)
      <div class="fr-row">
        <div class="fr-av ok">🎁</div>
        <div class="fr-m">
          <div class="fr-n">Бонус за {{ intdiv($b->threshold, $svc::REWARD) }}-го друга</div>
          <div class="fr-s">{{ $b->paid_at ? 'Получено ' . $b->paid_at->format('d.m') : 'Деньги у преподавателя' }}</div>
        </div>
        <div class="fr-b ok">+{{ $svc->rub($b->amount) }}</div>
      </div>
    @endforeach
  </div>
  @endif

  @include('pwa.student.partials.friends-board')

  <details class="fr-blk fr-rules">
    <summary><span>Условия и правила</span><span>+</span></summary>
    <ul>
      <li>Деньги отдаём, когда друг оплатит месяц занятий. Одного бесплатного занятия мало.</li>
      <li>За каждого третьего друга — ещё <b>{{ $svc->rub($svc::BONUS) }} сверху</b>: третий друг приносит {{ $svc->rub($svc::REWARD + $svc::BONUS) }}.</li>
      <li>Друг должен быть новым — если он уже занимался у нас раньше, не считается.</li>
      <li><b>Кто позвал, говорит сам новичок на первом занятии.</b> Задним числом это не меняется.</li>
      <li>Позвали вдвоём или втроём? Пусть скажет про всех — {{ $svc->rub($svc::REWARD) }} разделятся поровну. Такой друг и к бонусу засчитывается каждому наполовину (или на треть).</li>
      <li>Если двое спорят за одного друга — решает сам друг. Не договорились или он не помнит — <b>делим поровну</b>.</li>
      <li>Деньги отдаёт преподаватель наличными на занятии.</li>
      <li>Заработанное не обнуляется.</li>
    </ul>
  </details>
</div>
@endsection
