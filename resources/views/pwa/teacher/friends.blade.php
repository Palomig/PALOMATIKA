@extends('layouts.pwa')
@section('title', 'Позови друга — palomatika')

@push('styles')
  .tf-blk { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 16px; }
  .tf-h { font-family: var(--display); font-size: 15px; color: var(--text); margin-bottom: 4px; }
  .tf-sub { font-size: 12px; color: var(--muted); font-weight: 600; line-height: 1.5; margin-bottom: 12px; }
  .tf-lbl { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin: 12px 0 6px; }
  .tf-input { display: block; width: 100%; padding: 11px 12px; background: var(--surface2); color: var(--text);
    border: 1px solid var(--border); border-radius: 10px; font-size: 14px; font-family: var(--body); }
  .tf-input:focus { outline: none; border-color: var(--accent-bd); }
  .tf-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
  .tf-pick { max-height: 320px; overflow-y: auto; border: 1px solid var(--border); border-radius: 10px; margin-top: 8px; }
  .tf-opt { display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; border-bottom: 1px solid var(--border); cursor: pointer; }
  .tf-opt:last-child { border-bottom: none; }
  .tf-opt input { margin-top: 3px; width: 18px; height: 18px; accent-color: var(--accent); flex: 0 0 auto; }
  .tf-opt-n { font-size: 13px; font-weight: 800; color: var(--text); }
  .tf-opt-s { font-size: 11px; color: var(--muted); font-weight: 600; margin-top: 1px; }
  .tf-opt-note { font-size: 11px; color: var(--yellow); font-weight: 700; margin-top: 2px; }
  .tf-split { margin-top: 12px; padding: 10px 12px; border-radius: 10px; background: var(--green-bg); border: 1px solid var(--green-bd);
    font-size: 12.5px; font-weight: 800; color: var(--green); text-align: center; }
  .tf-fine { font-size: 11px; color: var(--muted); font-weight: 600; line-height: 1.5; margin-top: 10px; text-align: center; }
  .tf-row { display: flex; align-items: center; gap: 10px; padding: 11px 0; border-bottom: 1px solid var(--border); }
  .tf-row:last-child { border-bottom: none; padding-bottom: 0; }
  .tf-m { flex: 1; min-width: 0; }
  .tf-n { font-size: 13px; font-weight: 800; color: var(--text); }
  .tf-s { font-size: 11.5px; color: var(--muted); font-weight: 600; margin-top: 1px; line-height: 1.45; }
  .tf-acts { display: flex; gap: 6px; margin-top: 9px; flex-wrap: wrap; }
  .tf-btn { border: none; border-radius: 9px; padding: 8px 12px; font-size: 12px; font-weight: 800; cursor: pointer; font-family: var(--body); }
  .tf-btn.ok { background: var(--green); color: #0b1f14; }
  .tf-btn.ghost { background: var(--surface2); color: var(--muted); border: 1px solid var(--border); }
  .tf-btn.pay { background: var(--accent); color: #fff; }
  .tf-amt { font-family: var(--display); font-size: 15px; color: var(--text); white-space: nowrap; }
  .tf-flash { font-size: 12.5px; font-weight: 700; border-radius: 12px; padding: 10px 13px; }
  .tf-flash.ok { color: var(--green); background: var(--green-bg); border: 1px solid var(--green-bd); }
  .tf-flash.err { color: var(--red); background: var(--red-bg); border: 1px solid var(--red-bd); }
  .tf-empty { font-size: 12.5px; color: var(--muted); font-weight: 600; }
  .tf-tag { display: inline-block; font-size: 9.5px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
    border-radius: 6px; padding: 2px 6px; margin-left: 6px; vertical-align: 1px; }
  .tf-tag.cash { color: var(--green); background: var(--green-bg); }
  .tf-tag.discount { color: var(--accent); background: var(--accent-bg); }
  .tf-tabs { display: flex; gap: 6px; margin-bottom: 12px; }
  .tf-tab { flex: 1; padding: 8px; border-radius: 9px; border: 1px solid var(--border); background: var(--surface2); color: var(--muted);
    font-size: 12px; font-weight: 800; cursor: pointer; font-family: var(--body); }
  .tf-tab.on { background: var(--accent); border-color: var(--accent); color: #fff; }
  .tf-num { width: 64px; padding: 8px; background: var(--surface2); color: var(--text); border: 1px solid var(--border);
    border-radius: 9px; font-size: 14px; text-align: center; font-family: var(--body); }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.teacher.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Позови друга</div>
  </div>

  @if(session('friends_ok'))<div class="tf-flash ok">{{ session('friends_ok') }}</div>@endif
  @if(session('friends_error'))<div class="tf-flash err">{{ session('friends_error') }}</div>@endif
  @if($errors->any())<div class="tf-flash err">{{ $errors->first() }}</div>@endif

  {{-- Новый друг --}}
  <form class="tf-blk" method="POST" action="{{ route('pwa.teacher.friends.store') }}"
        x-data="{ q: '', selected: @js(array_map('strval', (array) old('referrers', []))),
                  programs: @js($referrers->mapWithKeys(fn ($r) => [(string) $r->id => $svc->programForGrade((int) $r->grade_num)])),
                  get program() { return this.selected.length ? this.programs[this.selected[0]] : null } }">
    @csrf
    <div class="tf-h">Новичок на первом занятии — кто привёл?</div>
    <div class="tf-sub">Спроси у него самого и запиши сразу. Задним числом не меняем — иначе потом придут двое с разными версиями. У 8–11 класса акция — 2000 ₽ наличными, у 6–7 — скидка 50% на месяц обоим.</div>

    <div class="tf-lbl">Новичок</div>
    <input class="tf-input" name="invitee_name" required maxlength="100" placeholder="Имя и фамилия" value="{{ old('invitee_name') }}">
    <div class="tf-2" style="margin-top:8px">
      <select class="tf-input" name="invitee_grade">
        <option value="">Класс</option>
        @foreach(range(5, 11) as $g)
          <option value="{{ $g }}" @selected((string) old('invitee_grade') === (string) $g)>{{ $g }} класс</option>
        @endforeach
      </select>
      <input class="tf-input" type="date" name="first_lesson_on" required value="{{ old('first_lesson_on', now()->toDateString()) }}">
    </div>

    <div class="tf-lbl">Кого он назвал · до трёх</div>
    <input class="tf-input" type="search" x-model="q" placeholder="Поиск по имени">
    <div class="tf-pick">
      @forelse($referrers as $r)
        @php $rp = $svc->programForGrade((int) $r->grade_num); @endphp
        <label class="tf-opt" x-show="q === '' || @js(mb_strtolower($r->name)).includes(q.toLowerCase())">
          <input type="checkbox" name="referrers[]" value="{{ $r->id }}" x-model="selected"
                 :disabled="!!(!selected.includes('{{ $r->id }}') && (selected.length >= {{ $svc::MAX_REFERRERS }} || (program && program !== '{{ $rp }}')))">
          <span>
            <span class="tf-opt-n">{{ $r->name }}</span>
            <span class="tf-opt-s">{{ $r->grade_num }} класс</span>
            <span class="tf-tag {{ $rp }}">{{ $rp === $svc::DISCOUNT ? '−50%' : '2000 ₽' }}</span>
          </span>
        </label>
      @empty
        <div class="tf-opt tf-empty">Нет прикреплённых учеников 8–11 классов</div>
      @endforelse
    </div>

    <div class="tf-split" x-show="selected.length > 0" x-cloak
         x-text="program === '{{ $svc::DISCOUNT }}'
           ? 'Скидка 50% на следующий месяц — ' + (selected.length === 1 ? 'пригласившему' : 'каждому пригласившему') + ' и новичку'
           : (selected.length === 1 ? 'Выплата: {{ $svc::REWARD }} ₽ одному' : 'Выплата разделится: по ' + Math.floor({{ $svc::REWARD }} / selected.length) + ' ₽ каждому')"></div>

    <button class="btn btn-accent" type="submit" style="width:100%;margin-top:12px" :disabled="!!(selected.length === 0)">Записать</button>
    <div class="tf-fine">Пришёл сам, никого не назвал — записывать не нужно</div>
  </form>

  {{-- Ждут второго занятия --}}
  <div class="tf-blk">
    <div class="tf-h">Ждут оплаты месяца · {{ $pending->count() }}</div>
    <div class="tf-sub">Когда новичок оплатит месяц занятий — нажми «Остался»: деньги или скидки встанут в очередь.</div>
    @forelse($pending as $inv)
      <div class="tf-row" style="display:block">
        <div class="tf-n">{{ $inv->invitee_name }}@if($inv->invitee_grade), {{ $inv->invitee_grade }} класс @endif<span class="tf-tag {{ $inv->program }}">{{ $inv->program === $svc::DISCOUNT ? '−50%' : '2000 ₽' }}</span></div>
        <div class="tf-s">
          Первое занятие {{ $inv->first_lesson_on->format('d.m') }} ·
          {{ $inv->credits->map(fn ($c) => $svc->shortName($c->referrer?->name) . ($inv->program === $svc::DISCOUNT ? '' : ' ' . $svc->rub($c->amount)))->implode(', ') }}
        </div>
        <div class="tf-acts">
          <form method="POST" action="{{ route('pwa.teacher.friends.qualify', $inv) }}">@csrf
            <button class="tf-btn ok" type="submit">Остался: оплатил месяц</button>
          </form>
          <form method="POST" action="{{ route('pwa.teacher.friends.cancel', $inv) }}"
                onsubmit="return confirm(@js($inv->invitee_name . ' не остался? Выплаты и скидки по нему не будет.'))">@csrf
            <button class="tf-btn ghost" type="submit">Не остался</button>
          </form>
        </div>
      </div>
    @empty
      <div class="tf-empty">Пока никого</div>
    @endforelse
  </div>

  {{-- Очередь выплат --}}
  <div class="tf-blk">
    <div class="tf-h">К выдаче · {{ $svc->rub($queue['total']) }}</div>
    <div class="tf-sub">Отдай наличными на занятии и отметь — иначе через полгода не восстановить, кому и сколько.</div>
    @if($queue['credits']->isEmpty() && $queue['bonuses']->isEmpty())
      <div class="tf-empty">Выдавать нечего</div>
    @endif
    @foreach($queue['credits'] as $c)
      @php $others = $c->invite->credits->where('referrer_id', '!=', $c->referrer_id)->map(fn ($o) => $svc->shortName($o->referrer?->name))->filter()->values()->all(); @endphp
      <div class="tf-row">
        <div class="tf-m">
          <div class="tf-n">{{ $c->referrer?->name }}</div>
          <div class="tf-s">Друг: {{ $c->invite->invitee_name }}@if($others) · {{ $svc->splitText($others) }}@endif</div>
        </div>
        <div class="tf-amt">{{ $svc->rub($c->amount) }}</div>
        <form method="POST" action="{{ route('pwa.teacher.friends.credit.paid', $c) }}">@csrf
          <button class="tf-btn pay" type="submit">Выдал</button>
        </form>
      </div>
    @endforeach
    @foreach($queue['bonuses'] as $b)
      <div class="tf-row">
        <div class="tf-m">
          <div class="tf-n">{{ $b->referrer?->name }}</div>
          <div class="tf-s">Бонус за {{ intdiv($b->threshold, $svc::REWARD) }}-го друга</div>
        </div>
        <div class="tf-amt">+{{ $svc->rub($b->amount) }}</div>
        <form method="POST" action="{{ route('pwa.teacher.friends.bonus.paid', $b) }}">@csrf
          <button class="tf-btn pay" type="submit">Выдал</button>
        </form>
      </div>
    @endforeach
  </div>

  {{-- Скидки 6–7 класса --}}
  <div class="tf-blk">
    <div class="tf-h">Скидки 50% к применению · {{ $queue['discounts']->count() + $queue['inviteeDiscounts']->count() }}</div>
    <div class="tf-sub">6–7 класс: примени скидку к следующему месяцу и отметь.</div>
    @if($queue['discounts']->isEmpty() && $queue['inviteeDiscounts']->isEmpty())
      <div class="tf-empty">Применять нечего</div>
    @endif
    @foreach($queue['discounts'] as $c)
      <div class="tf-row">
        <div class="tf-m">
          <div class="tf-n">{{ $c->referrer?->name }}</div>
          <div class="tf-s">Пригласил: {{ $c->invite->invitee_name }}</div>
        </div>
        <div class="tf-amt">−{{ $c->amount }}%</div>
        <form method="POST" action="{{ route('pwa.teacher.friends.credit.paid', $c) }}">@csrf
          <button class="tf-btn pay" type="submit">Применил</button>
        </form>
      </div>
    @endforeach
    @foreach($queue['inviteeDiscounts'] as $inv)
      <div class="tf-row">
        <div class="tf-m">
          <div class="tf-n">{{ $inv->invitee_name }}</div>
          <div class="tf-s">Новичок · скидка на второй месяц</div>
        </div>
        <div class="tf-amt">−{{ $svc::DISCOUNT_PERCENT }}%</div>
        <form method="POST" action="{{ route('pwa.teacher.friends.invitee-discount', $inv) }}">@csrf
          <button class="tf-btn pay" type="submit">Применил</button>
        </form>
      </div>
    @endforeach
  </div>

  @if($superAdmin)
  {{-- Доски зовущих — вручную, только супер-админ --}}
  <div class="tf-blk" id="board" x-data="{ tab: @js(session('board_program', $svc::CASH)) }">
    <div class="tf-h">Доски зовущих</div>
    <div class="tf-sub">Ученики видят ровно то, что здесь записано. Поставь 0 или удали, чтобы убрать с доски.</div>
    @if(session('board_ok'))<div class="tf-flash ok" style="margin-bottom:12px">✓ {{ session('board_ok') }}</div>@endif
    @if(session('board_error'))<div class="tf-flash err" style="margin-bottom:12px">{{ session('board_error') }}</div>@endif
    <div class="tf-tabs">
      <button type="button" class="tf-tab" :class="tab === '{{ $svc::CASH }}' && 'on'" @click="tab = '{{ $svc::CASH }}'">8–11 класс</button>
      <button type="button" class="tf-tab" :class="tab === '{{ $svc::DISCOUNT }}' && 'on'" @click="tab = '{{ $svc::DISCOUNT }}'">6–7 класс</button>
    </div>
    @foreach([$svc::CASH, $svc::DISCOUNT] as $prog)
      <div x-show="tab === '{{ $prog }}'" @if($prog !== $svc::CASH) x-cloak @endif>
        @forelse($boards[$prog] as $e)
          <div class="tf-row">
            <div class="tf-m">
              <div class="tf-n">{{ $e->user?->name }}</div>
              <div class="tf-s">{{ $e->user?->grade_num }} класс</div>
            </div>
            <form method="POST" action="{{ route('pwa.teacher.friends.board.store') }}" style="display:flex;gap:6px;align-items:center">@csrf
              <input type="hidden" name="program" value="{{ $prog }}">
              <input type="hidden" name="user_id" value="{{ $e->user_id }}">
              <input class="tf-num" type="number" name="friends" min="0" max="99" value="{{ $e->friends }}" aria-label="Друзей">
              <button class="tf-btn pay" type="submit">OK</button>
            </form>
            <form method="POST" action="{{ route('pwa.teacher.friends.board.destroy', $e) }}">@csrf @method('DELETE')
              <button class="tf-btn ghost" type="submit" aria-label="Убрать с доски">×</button>
            </form>
          </div>
        @empty
          <div class="tf-empty" style="margin-bottom:10px">Доска пустая</div>
        @endforelse
        <form method="POST" action="{{ route('pwa.teacher.friends.board.store') }}" style="display:flex;gap:6px;margin-top:12px">@csrf
          <input type="hidden" name="program" value="{{ $prog }}">
          <select class="tf-input" name="user_id" required style="flex:1">
            <option value="">Добавить ученика</option>
            @foreach($referrers->filter(fn ($r) => $svc->programForGrade((int) $r->grade_num) === $prog) as $r)
              <option value="{{ $r->id }}">{{ $r->name }}, {{ $r->grade_num }} кл.</option>
            @endforeach
          </select>
          <input class="tf-num" type="number" name="friends" min="0" max="99" value="1" aria-label="Друзей">
          <button class="tf-btn pay" type="submit">+</button>
        </form>
      </div>
    @endforeach
  </div>
  @endif

  @if($paidCredits->isNotEmpty())
  <div class="tf-blk">
    <div class="tf-h">Выдано недавно</div>
    @foreach($paidCredits as $c)
      <div class="tf-row">
        <div class="tf-m">
          <div class="tf-n">{{ $c->referrer?->name }}</div>
          <div class="tf-s">Друг: {{ $c->invite?->invitee_name }} · {{ $c->paid_at->format('d.m.Y') }}</div>
        </div>
        <div class="tf-amt">{{ $svc->rub($c->amount) }}</div>
      </div>
    @endforeach
  </div>
  @endif
</div>
@endsection
