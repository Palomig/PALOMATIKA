@extends('layouts.pwa')
@section('title', 'Позови друга — Palomatika')

@push('styles')
  .fr-hero { text-align: center; padding: 18px 0; border: 1px solid var(--purple-bd); border-radius: var(--r);
    background: radial-gradient(120% 90% at 50% 0%, var(--purple-bg), transparent 70%); }
  .fr-hero.gold { border-color: var(--yellow-bd); background: radial-gradient(120% 90% at 50% 0%, var(--yellow-bg), transparent 70%); }
  .fr-sum { font-family: var(--display); font-size: 46px; line-height: 1; color: var(--purple); }
  .fr-hero.gold .fr-sum { color: var(--yellow); }
  .fr-lbl { font-size: 12.5px; font-weight: 700; color: var(--muted); margin-top: 8px; padding: 0 24px; line-height: 1.5; }
  .fr-kick { display: inline-block; margin-top: 12px; padding: 7px 14px; border-radius: 999px; background: var(--yellow-bg);
    border: 1px solid var(--yellow-bd); color: var(--yellow); font-size: 11.5px; font-weight: 800; }
  .fr-ladder { padding: 14px 16px 0; text-align: left; }
  .fr-lr { display: flex; align-items: center; gap: 10px; padding: 6px 0; font-size: 12px; font-weight: 700; color: var(--muted); }
  .fr-ln { width: 20px; height: 20px; flex: 0 0 auto; border-radius: 999px; background: var(--surface2);
    font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
  .fr-lr b { margin-left: auto; font-family: var(--display); color: var(--text); font-size: 13px; font-weight: 400; }
  .fr-lr.gold, .fr-lr.gold b { color: var(--yellow); }
  .fr-bar-wrap { padding: 16px 16px 0; }
  .fr-bar { height: 9px; border-radius: 5px; background: var(--surface2); overflow: hidden; }
  .fr-bar i { display: block; height: 100%; border-radius: 5px; background: var(--yellow); }
  .fr-bar-lbl { display: flex; justify-content: space-between; margin-top: 7px; font-size: 10.5px; font-weight: 800; color: var(--muted); }
  .fr-bar-lbl b { color: var(--yellow); }
  .fr-fine { font-size: 10.5px; color: var(--muted); font-weight: 700; text-align: center; line-height: 1.5; margin-top: -4px; }
  .fr-btn { background: var(--purple); color: #15121f; width: 100%; }
  .fr-blk { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 16px; }
  .fr-h { font-family: var(--display); font-size: 14px; margin-bottom: 12px; color: var(--text); }
  .fr-sub { font-size: 12px; color: var(--muted); font-weight: 600; line-height: 1.5; margin: -6px 0 12px; }
  .fr-step { display: flex; gap: 11px; margin-bottom: 13px; }
  .fr-step:last-child { margin-bottom: 0; }
  .fr-sn { width: 23px; height: 23px; flex: 0 0 auto; border-radius: 999px; background: var(--purple-bg); border: 1px solid var(--purple-bd);
    color: var(--purple); font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
  .fr-st { font-size: 13px; font-weight: 700; line-height: 1.45; color: var(--text); }
  .fr-st span { display: block; color: var(--muted); font-weight: 600; font-size: 11.5px; margin-top: 2px; }
  .fr-say { background: var(--surface2); border-radius: 12px; padding: 13px; font-size: 13px; color: var(--text);
    font-weight: 600; line-height: 1.55; font-style: italic; margin-bottom: 9px; }
  .fr-say:last-child { margin-bottom: 0; }
  .fr-who { display: flex; gap: 9px; font-size: 12.5px; font-weight: 600; color: var(--muted); margin-bottom: 9px; }
  .fr-who:last-child { margin-bottom: 0; }
  .fr-who i { color: var(--purple); font-style: normal; }
  .fr-row { display: flex; align-items: center; gap: 11px; padding: 11px 0; border-bottom: 1px solid var(--border); }
  .fr-row:last-child { border-bottom: none; padding-bottom: 0; }
  .fr-row:first-of-type { padding-top: 0; }
  .fr-av { width: 34px; height: 34px; flex: 0 0 auto; border-radius: 999px; background: var(--surface2); color: var(--muted);
    display: flex; align-items: center; justify-content: center; font-family: var(--display); font-size: 14px; }
  .fr-av.ok { background: var(--green-bg); border: 1px solid var(--green-bd); color: var(--green); }
  .fr-av.go { background: var(--accent-bg); border: 1px solid var(--accent-bd); color: var(--accent); }
  .fr-m { flex: 1; min-width: 0; }
  .fr-n { font-size: 13px; font-weight: 800; color: var(--text); }
  .fr-s { font-size: 11.5px; color: var(--muted); font-weight: 600; margin-top: 1px; }
  .fr-b { font-size: 10px; font-weight: 800; padding: 4px 8px; border-radius: 7px; flex: 0 0 auto; text-transform: uppercase; letter-spacing: .04em; }
  .fr-b.ok { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-bd); }
  .fr-b.go { background: var(--accent-bg); color: var(--accent); border: 1px solid var(--accent-bd); }
  .fr-b.wait { background: var(--surface2); color: var(--muted); border: 1px solid var(--border); }
  .fr-x { background: none; border: none; color: var(--muted); font-size: 18px; cursor: pointer; padding: 4px 6px; }
  .fr-lb { display: flex; align-items: center; gap: 11px; padding: 10px 0; border-bottom: 1px solid var(--border); }
  .fr-lb:last-child { border-bottom: none; }
  .fr-lb.you { background: var(--purple-bg); border: 1px solid var(--purple-bd); border-radius: 11px; padding: 10px; margin: 2px -6px; }
  .fr-lp { width: 26px; flex: 0 0 auto; text-align: center; font-family: var(--display); font-size: 15px; color: var(--muted); }
  .fr-ln2 { flex: 1; font-size: 13px; font-weight: 800; color: var(--text); }
  .fr-ln2 small { display: block; font-size: 10.5px; color: var(--muted); font-weight: 700; }
  .fr-lc { font-size: 11px; font-weight: 800; color: var(--purple); }
  .fr-foot { margin-top: 12px; padding-top: 11px; border-top: 1px solid var(--border); font-size: 11.5px; color: var(--muted);
    font-weight: 700; text-align: center; line-height: 1.5; }
  .fr-empty { text-align: center; padding: 8px 10px; }
  .fr-empty div:first-child { font-size: 30px; margin-bottom: 8px; }
  .fr-toggle { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 11px 13px;
    background: var(--surface2); border-radius: 11px; margin-top: 12px; font-size: 12px; font-weight: 700; color: var(--muted); }
  .fr-toggle button { border: 1px solid var(--border); background: var(--surface); color: var(--text); border-radius: 999px;
    padding: 6px 12px; font-size: 11px; font-weight: 800; cursor: pointer; font-family: var(--body); }
  .fr-rules summary { font-size: 12.5px; font-weight: 800; color: var(--muted); cursor: pointer; list-style: none;
    display: flex; justify-content: space-between; }
  .fr-rules summary::-webkit-details-marker { display: none; }
  .fr-rules ul { margin-top: 12px; font-size: 11.5px; color: var(--muted); font-weight: 600; line-height: 1.65; }
  .fr-rules li { margin-bottom: 6px; list-style: none; padding-left: 13px; position: relative; }
  .fr-rules li::before { content: '·'; position: absolute; left: 3px; color: var(--purple); font-weight: 800; }
  .fr-rules b { color: var(--text); }
  .fr-err { font-size: 12px; font-weight: 700; color: var(--red); background: var(--red-bg); border: 1px solid var(--red-bd);
    border-radius: 12px; padding: 10px 13px; }
  .fr-overlay { position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,.55); backdrop-filter: blur(4px);
    display: flex; align-items: flex-end; justify-content: center; }
  .fr-sheet { background: var(--bg); border-radius: 20px 20px 0 0; width: 100%; max-width: 480px; padding: 22px 18px calc(28px + var(--safe-bottom)); }
  .fr-handle { width: 36px; height: 4px; background: var(--border); border-radius: 2px; margin: 0 auto 14px; }
  .fr-sheet-t { font-family: var(--display); font-size: 17px; text-align: center; color: var(--text); margin-bottom: 6px; }
  .fr-sheet-d { font-size: 12px; color: var(--muted); text-align: center; font-weight: 600; line-height: 1.5; margin-bottom: 14px; }
  .fr-input { display: block; width: 100%; padding: 13px 14px; background: var(--surface); color: var(--text); border: 1px solid var(--border);
    border-radius: 12px; font-size: 15px; font-family: var(--body); margin-bottom: 12px; }
  .fr-input:focus { outline: none; border-color: var(--purple-bd); }
  .fr-cancel { display: block; width: 100%; padding: 12px; background: none; border: none; color: var(--muted); font-size: 14px; font-weight: 700; cursor: pointer; }
@endpush

@section('body')
@php
  $s = $summary;
  $started = $s['earned'] > 0;
@endphp
<div class="page" x-data="{ noteOpen: false }">
  <div class="topbar">
    <a href="{{ route('pwa.student.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Позови друга</div>
  </div>

  @if(session('friends_error'))
    <div class="fr-err">{{ session('friends_error') }}</div>
  @endif

  @if($started)
    <div class="fr-hero gold">
      <div class="fr-sum">{{ $svc->rub($s['total']) }}</div>
      <div class="fr-lbl">заработано · до бонуса +{{ $svc->rub($svc::BONUS) }} осталось {{ $svc->rub($s['toBonus']) }}</div>
      <div class="fr-bar-wrap">
        <div class="fr-bar"><i style="width: {{ $s['progressPct'] }}%"></i></div>
        <div class="fr-bar-lbl"><span>{{ $svc->rub($s['earned'] % $svc::BONUS_STEP) }}</span><b>{{ $svc->rub($svc::BONUS_STEP) }} → +{{ $svc->rub($svc::BONUS) }}</b></div>
      </div>
    </div>
  @else
    <div class="fr-hero">
      <div class="fr-sum">{{ $svc->rub($svc::REWARD) }}</div>
      <div class="fr-lbl">наличными за каждого друга, который начнёт у нас заниматься</div>
      <div class="fr-kick">Соберёшь {{ $svc->rub($svc::BONUS_STEP) }} — ещё {{ $svc->rub($svc::BONUS) }} бонусом</div>
      <div class="fr-ladder">
        <div class="fr-lr"><span class="fr-ln">1</span><span>Первый друг</span><b>{{ $svc->rub($svc::REWARD) }}</b></div>
        <div class="fr-lr"><span class="fr-ln">2</span><span>Второй друг</span><b>{{ $svc->rub($svc::REWARD) }}</b></div>
        <div class="fr-lr"><span class="fr-ln">3</span><span>Третий друг</span><b>{{ $svc->rub($svc::REWARD) }}</b></div>
        <div class="fr-lr gold"><span class="fr-ln">🎁</span><span>Бонус за {{ $svc->rub($svc::BONUS_STEP) }}</span><b>+{{ $svc->rub($svc::BONUS) }}</b></div>
      </div>
    </div>
  @endif

  <button type="button" class="btn fr-btn" @click="noteOpen = true">Отметить, кого позвал</button>
  <div class="fr-fine">Деньги отдаём, когда друг оплатит занятия и придёт на второе</div>

  <div class="fr-blk">
    <div class="fr-h">Как это работает</div>
    <div class="fr-step"><div class="fr-sn">1</div><div class="fr-st">Предупреди преподавателя<span>Скажи, что придёшь с другом, — чтобы для него нашлось место</span></div></div>
    <div class="fr-step"><div class="fr-sn">2</div><div class="fr-st">Приведи друга на своё занятие<span>Первое — бесплатно: он просто посидит и позанимается с вами в группе</span></div></div>
    <div class="fr-step"><div class="fr-sn">3</div><div class="fr-st">Друг остался — забираешь {{ $svc->rub($svc::REWARD) }}<span>Когда он оплатит занятия и придёт на второе, преподаватель отдаст наличными</span></div></div>
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
              Был на первом занятии {{ $f['first_lesson_on']?->format('d.m') }} · ждём второе
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
          <div class="fr-n">Бонус за {{ $svc->rub($b->threshold) }}</div>
          <div class="fr-s">{{ $b->paid_at ? 'Получено ' . $b->paid_at->format('d.m') : 'Деньги у преподавателя' }}</div>
        </div>
        <div class="fr-b ok">+{{ $svc->rub($b->amount) }}</div>
      </div>
    @endforeach
  </div>
  @endif

  @if($s['notes']->isNotEmpty())
  <div class="fr-blk">
    <div class="fr-h">Мои отметки</div>
    <div class="fr-sub">Заметки для себя: кого ты звал и когда</div>
    @foreach($s['notes'] as $n)
      <div class="fr-row">
        <div class="fr-av">?</div>
        <div class="fr-m"><div class="fr-n">{{ $n->name }}</div><div class="fr-s">Отметил {{ $n->created_at?->format('d.m') }}</div></div>
        <form method="POST" action="{{ route('pwa.student.friends.notes.destroy', $n->id) }}">
          @csrf @method('DELETE')
          <button class="fr-x" type="submit" aria-label="Удалить отметку">×</button>
        </form>
      </div>
    @endforeach
  </div>
  @endif

  <div class="fr-blk">
    <div class="fr-h">Что сказать</div>
    <div class="fr-sub">Ничего продавать не надо — хватит одной фразы</div>
    <div class="fr-say">«Пошли со мной на математику — первое занятие бесплатное. Посидишь с нами в группе, посмотришь, как объясняют»</div>
    <div class="fr-say">«Приходи со мной на следующее занятие. Первое бесплатно, понравится — останешься»</div>
  </div>

  <div class="fr-blk">
    <div class="fr-h">Кому сказать</div>
    <div class="fr-who"><i>→</i><div>Одноклассникам, которые жалуются на математику</div></div>
    <div class="fr-who"><i>→</i><div>Ребятам с секции или с других курсов</div></div>
    <div class="fr-who"><i>→</i><div>Друзьям со двора и из старой школы</div></div>
    <div class="fr-who"><i>→</i><div>Брату или сестре друга — они тоже считаются</div></div>
  </div>

  <div class="fr-blk">
    <div class="fr-h">Доска зовущих · этот учебный год</div>
    @if($board['top']->isEmpty())
      <div class="fr-empty">
        <div>🏅</div>
        <div class="fr-n">Пока никто никого не позвал</div>
        <div class="fr-s" style="margin-top:4px">Позовёшь первым — займёшь первое место и будешь висеть тут весь год</div>
      </div>
    @else
      @foreach($board['top'] as $r)
        <div class="fr-lb {{ $r['you'] ? 'you' : '' }}">
          <div class="fr-lp">{{ [1 => '🥇', 2 => '🥈', 3 => '🥉'][$r['pos']] ?? $r['pos'] }}</div>
          <div class="fr-ln2">{{ $r['name'] }}@if($r['grade'])<small>{{ $r['grade'] }} класс</small>@endif</div>
          <div class="fr-lc">{{ $svc->friendsWord($r['friends']) }}</div>
        </div>
      @endforeach
      @if($board['me'])
        <div class="fr-lb you">
          <div class="fr-lp">{{ $board['me']['pos'] }}</div>
          <div class="fr-ln2">Ты</div>
          <div class="fr-lc">{{ $svc->friendsWord($board['me']['friends']) }}</div>
        </div>
      @endif
      <div class="fr-foot">Всего с сентября ребята привели {{ $svc->friendsWord($board['totalFriends']) }}<br>Общий друг считается каждому, кто его звал</div>
    @endif
    <form method="POST" action="{{ route('pwa.student.friends.board') }}" class="fr-toggle">
      @csrf
      <span>{{ $user->invite_board_hidden ? 'На доске ты без имени' : 'На доске видно твоё имя' }}</span>
      <input type="hidden" name="show" value="{{ $user->invite_board_hidden ? 1 : 0 }}">
      <button type="submit">{{ $user->invite_board_hidden ? 'Показать имя' : 'Скрыть имя' }}</button>
    </form>
  </div>

  <details class="fr-blk fr-rules">
    <summary><span>Условия и правила</span><span>+</span></summary>
    <ul>
      <li>Деньги начисляем, когда друг оплатит занятия и придёт на второе. Одного бесплатного занятия мало.</li>
      <li>Друг должен быть новым — если он уже занимался у нас раньше, не считается.</li>
      <li><b>Кто позвал, говорит сам новичок на первом занятии.</b> Задним числом это не меняется.</li>
      <li>Позвали вдвоём или втроём? Пусть скажет про всех — деньги разделятся поровну, и каждый продвинется к бонусу.</li>
      <li>Если двое спорят за одного друга — решает сам друг. Не договорились или он не помнит — <b>делим поровну</b>.</li>
      <li>Бонус {{ $svc->rub($svc::BONUS) }} — за каждые {{ $svc->rub($svc::BONUS_STEP) }} заработанного, то есть за каждого третьего друга.</li>
      <li>Деньги отдаёт преподаватель наличными на занятии.</li>
      <li>Заработанное не обнуляется. Доска зовущих — отдельная, она начинается заново каждое 1 сентября.</li>
    </ul>
  </details>

  <template x-if="noteOpen">
    <div class="fr-overlay" @click.self="noteOpen = false">
      <form class="fr-sheet" method="POST" action="{{ route('pwa.student.friends.notes.store') }}">
        @csrf
        <div class="fr-handle"></div>
        <div class="fr-sheet-t">Кого позвал?</div>
        <div class="fr-sheet-d">Просто заметка для себя — чтобы не забыть. На деньги не влияет: кто привёл, скажет сам друг, когда придёт</div>
        <input class="fr-input" type="text" name="name" maxlength="100" required placeholder="Имя и класс" autofocus>
        <button class="btn fr-btn" type="submit">Отметить</button>
        <button class="fr-cancel" type="button" @click="noteOpen = false">Отмена</button>
      </form>
    </div>
  </template>
</div>
@endsection
