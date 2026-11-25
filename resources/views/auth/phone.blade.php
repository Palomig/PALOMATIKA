@extends('layouts.pwa')

@section('title', $pending ? 'Код из звонка — Паломатика' : 'Телефон — Паломатика')
@section('focused-auth', '1')

@push('styles')
  .pv { --ink2:color-mix(in srgb,var(--text) 78%,var(--bg)); --ink3:color-mix(in srgb,var(--text) 62%,var(--bg)); min-height:100svh; display:grid; place-items:center; padding:calc(24px + var(--safe-top)) 16px calc(24px + var(--safe-bottom)); background:radial-gradient(90% 42% at 50% 0%,var(--accent-bg),transparent 70%); }
  .pv-wrap { width:min(100%,420px); display:grid; gap:14px; animation:pv-rise .45s cubic-bezier(.2,.7,.2,1) both; }
  .pv-brand { text-align:center; font:28px var(--display); color:var(--accent); }
  .pv-steps { list-style:none; display:flex; align-items:center; width:min(72%,250px); margin:0 auto; font-size:13px; font-weight:800; color:var(--ink3); }
  .pv-steps li { display:flex; align-items:center; gap:8px; white-space:nowrap; }
  .pv-steps li:first-child { flex:1; }
  .pv-steps li:first-child::after { content:''; flex:1; height:2px; margin:0 10px; border-radius:2px; background:var(--border); }
  .pv-steps i { width:12px; height:12px; flex:none; border-radius:50%; background:var(--surface2); border:2px solid var(--ink3); }
  .pv-steps .is-on { color:var(--text); }
  .pv-steps .is-on i { background:var(--accent); border-color:var(--accent); box-shadow:0 0 0 4px var(--accent-bg); }
  .pv-steps .is-done::after { background:var(--green); }
  .pv-steps .is-done i { border-color:var(--green); background:var(--green) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 6 9 17l-5-5'/%3E%3C/svg%3E") center/8px no-repeat; }
  .pv-card { background:var(--surface); border:1px solid var(--border); border-radius:26px; padding:20px; box-shadow:0 24px 60px rgba(0,0,0,.35); }
  :root[data-theme="light"] .pv-card { box-shadow:0 20px 50px rgba(18,24,43,.12); }
  .pv-head { display:flex; align-items:center; gap:12px; min-height:40px; }
  .pv-avatar { width:40px; height:40px; flex:none; display:grid; place-items:center; border-radius:50%; background:var(--accent-bg); color:var(--accent); font-size:17px; font-weight:800; }
  .pv-person { flex:1; min-width:0; line-height:1.25; }
  .pv-person b { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:15px; }
  .pv-person small { font-size:12px; font-weight:700; color:var(--ink3); }
  .pv-link,.pv-back { border:0; background:none; padding:8px; color:var(--accent); font:700 14px var(--body); cursor:pointer; text-decoration:none; white-space:nowrap; }
  .pv-link { margin-right:-8px; }
  .pv-back { display:flex; align-items:center; gap:4px; margin-left:-8px; }
  .pv-back svg { width:18px; height:18px; }
  .pv-title { margin-top:18px; font:800 26px/1.18 var(--body); letter-spacing:-.6px; }
  .pv-lead { margin-top:8px; font-size:15px; line-height:1.5; color:var(--ink2); }
  .pv-how { list-style:none; margin-top:16px; display:grid; gap:10px; }
  .pv-how li { display:flex; gap:10px; font-size:14px; line-height:1.45; color:var(--ink2); }
  .pv-how b { flex:none; width:22px; height:22px; margin-top:-1px; display:grid; place-items:center; border-radius:50%; background:var(--surface2); color:var(--accent); font-size:12px; }
  .pv-form { margin-top:20px; }
  .pv-form .pv-alert { margin:0 0 14px; }
  .pv-label { display:block; margin-bottom:8px; font-size:11px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:var(--ink3); }
  .pv-input { display:block; width:100%; height:58px; padding:0 16px; border:1.5px solid var(--border); border-radius:16px; background:var(--bg); color:var(--text); font:700 18px var(--body); font-variant-numeric:tabular-nums; }
  .pv-input::placeholder { color:var(--ink3); opacity:1; font-weight:600; }
  .pv-input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 4px var(--accent-bg); }
  .pv-btn { display:block; width:100%; min-height:56px; margin-top:14px; border:0; border-radius:16px; background:var(--accent); color:#fff; font:16px var(--display); cursor:pointer; transition:transform .12s, opacity .15s; }
  .pv-btn:active { transform:scale(.98); }
  .pv-btn:disabled { opacity:.45; cursor:default; }
  .pv-btn2 { display:block; width:100%; min-height:50px; margin-top:10px; border:0; border-radius:16px; background:var(--surface2); color:var(--text); font:800 15px var(--body); cursor:pointer; transition:transform .12s, opacity .15s; }
  .pv-btn2:active { transform:scale(.98); }
  .pv-btn2:disabled { opacity:.55; cursor:default; font-variant-numeric:tabular-nums; }
  .pv-note { margin-top:12px; font-size:13px; line-height:1.5; color:var(--ink3); }
  .pv-alert { display:flex; align-items:flex-start; gap:9px; margin-top:14px; padding:11px 12px; border:1px solid var(--red-bd); border-radius:14px; background:var(--red-bg); font-size:14px; font-weight:700; line-height:1.4; }
  .pv-alert::before { content:''; flex:none; width:18px; height:18px; margin-top:1px; border-radius:50%; background:var(--red) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round'%3E%3Cpath d='M12 7v6'/%3E%3Cpath d='M12 16.5h.01'/%3E%3C/svg%3E") center/12px no-repeat; }
  .pv-call { margin-top:14px; text-align:center; }
  .pv-orb { position:relative; width:96px; height:96px; margin:0 auto 14px; display:grid; place-items:center; border-radius:50%; background:var(--accent-bg); border:1px solid var(--accent-bd); color:var(--accent); }
  .pv-orb::before,.pv-orb::after { content:''; position:absolute; inset:0; border-radius:50%; border:2px solid var(--accent); animation:pv-ripple 2.4s ease-out infinite; pointer-events:none; }
  .pv-orb::after { animation-delay:1.2s; }
  .pv-number { font-size:24px; font-weight:800; font-variant-numeric:tabular-nums; }
  .pv-status { margin-top:4px; font-size:14px; font-weight:700; color:var(--ink2); }
  .pv-dots b { animation:pv-blink 1.2s infinite; }
  .pv-dots b:nth-child(2) { animation-delay:.2s; }
  .pv-dots b:nth-child(3) { animation-delay:.4s; }
  .pv-center { text-align:center; }
  .pv-otp { margin-top:20px; }
  .pv-otp-source { text-align:center; letter-spacing:.4em; }
  .pv-cells { display:flex; justify-content:center; gap:10px; }
  .pv-cells input { flex:0 1 72px; min-width:0; height:66px; border:1.5px solid var(--border); border-radius:16px; background:var(--bg); color:var(--text); text-align:center; font:800 28px var(--body); caret-color:var(--accent); }
  .pv-cells input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 4px var(--accent-bg); }
  .is-split .pv-otp-source { display:none; }
  .pv-more { margin-top:10px; text-align:center; }
  .pv-more summary { display:inline-block; padding:8px 12px; color:var(--accent); font:700 14px var(--body); cursor:pointer; list-style:none; }
  .pv-more summary::-webkit-details-marker { display:none; }
  .pv-more-body { margin-top:12px; padding-top:14px; border-top:1px solid var(--border); text-align:left; }
  .pv-more-body .pv-form { margin-top:0; }
  .pv-help { list-style:none; display:grid; gap:8px; font-size:13px; line-height:1.5; color:var(--ink2); }
  .pv-inline { display:none; }
  @keyframes pv-rise { from { opacity:0; transform:translateY(14px); } }
  @keyframes pv-ripple { from { transform:scale(1); opacity:.5; } to { transform:scale(1.8); opacity:0; } }
  @keyframes pv-blink { 0%,60%,100% { opacity:.2; } 30% { opacity:1; } }
  @media (prefers-reduced-motion: reduce) { .pv-wrap,.pv-orb::before,.pv-orb::after,.pv-dots b { animation:none; } }
@endpush

@section('body')
@php
  $phone = $call['phone'] ?? null;
  $wait = max(0, 30 - (time() - ($call['at'] ?? time())));
  $roles = ['student' => 'ученик', 'teacher' => 'репетитор', 'parent' => 'родитель', 'admin' => 'администратор'];
@endphp
<main class="pv">
  <div class="pv-wrap">
    <div class="pv-brand">palomatika</div>
    <ol class="pv-steps" aria-label="Этапы">
      <li class="{{ $pending ? 'is-done' : 'is-on' }}" @if (!$pending) aria-current="step" @endif><i></i>Номер</li>
      <li class="{{ $pending ? 'is-on' : '' }}" @if ($pending) aria-current="step" @endif><i></i>Звонок</li>
    </ol>

    <section class="pv-card">
      <div class="pv-head">
        @auth
          @php $me = auth()->user(); $name = trim((string) $me->name) ?: 'Профиль'; @endphp
          <span class="pv-avatar" aria-hidden="true">{{ mb_substr($name, 0, 1) }}</span>
          <span class="pv-person"><b>{{ $name }}</b><small>{{ $roles[$me->role] ?? '' }}</small></span>
          <form method="post" action="/logout">@csrf<button class="pv-link" type="submit">Выйти</button></form>
        @else
          <a class="pv-back" href="/login">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Другой способ входа
          </a>
        @endauth
      </div>

      @if ($pending)
        <div class="pv-call">
          <div class="pv-orb" aria-hidden="true">
            <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 2 16 8 22 8"/><line x1="23" x2="17" y1="1" y2="7"/><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          </div>
          @if ($phone)<div class="pv-number">{{ $phone }}</div>@endif
          <div class="pv-status">Звоним<span class="pv-dots" aria-hidden="true"><b>.</b><b>.</b><b>.</b></span></div>
        </div>
        <h1 class="pv-title pv-center">Последние 4 цифры номера</h1>
        <p class="pv-lead pv-center">Возьмите их из входящего звонка. Трубку не берите — звонок сбросится сам.</p>

        <form method="post" data-code>
          @csrf
          @if ($errors->any())<p class="pv-alert" role="alert">{{ $errors->first() }}</p>@endif
          <div class="pv-otp">
            <input class="pv-input pv-otp-source" name="code" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="off" placeholder="0000" aria-label="Последние 4 цифры номера" required autofocus>
            <div class="pv-cells" hidden>
              <input inputmode="numeric" maxlength="1" autocomplete="off" aria-label="Первая цифра">
              <input inputmode="numeric" maxlength="1" autocomplete="off" aria-label="Вторая цифра">
              <input inputmode="numeric" maxlength="1" autocomplete="off" aria-label="Третья цифра">
              <input inputmode="numeric" maxlength="1" autocomplete="off" aria-label="Четвёртая цифра" enterkeyhint="done">
            </div>
          </div>
          <button class="pv-btn" type="submit">Подтвердить</button>
        </form>
        <p class="pv-note pv-center">Код действует 5 минут.</p>

        @if ($phone)
          <form method="post" id="pv-recall" class="pv-inline">@csrf<input type="hidden" name="action" value="start"><input type="hidden" name="phone" value="{{ $phone }}"></form>
          <button class="pv-btn2" type="submit" form="pv-recall" data-wait="{{ $wait }}">Позвонить ещё раз<span class="pv-wait" aria-hidden="true"></span></button>
        @endif
      @else
        <h1 class="pv-title">{{ auth()->check() ? 'Подтвердите телефон' : 'Войти по телефону' }}</h1>
        <p class="pv-lead">{{ auth()->check() ? 'Теперь в Паломатике входят по номеру телефона. Профиль и прогресс на месте — привязка займёт около минуты.' : 'Вход по номеру, который привязан к кабинету.' }}</p>
        <ol class="pv-how">
          <li><b>1</b><span>Введите номер телефона</span></li>
          <li><b>2</b><span>Вам позвонят. Трубку не берите — звонок сбросится сам</span></li>
          <li><b>3</b><span>Введите последние 4 цифры номера звонящего</span></li>
        </ol>
      @endif

      {{-- Форма номера одна на оба состояния: на втором шаге она спрятана за «Изменить номер». --}}
      @if ($pending)<details class="pv-more"><summary>Изменить номер</summary><div class="pv-more-body">@endif
      <form method="post" class="pv-form" data-phone>
        @csrf
        <input type="hidden" name="action" value="start">
        @unless ($pending)
          @if ($errors->any())<p class="pv-alert" role="alert">{{ $errors->first() }}</p>@endif
        @endunless
        <label class="pv-label" for="pv-phone">Номер телефона</label>
        <input class="pv-input" id="pv-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" placeholder="+7 900 123-45-67" value="{{ old('phone', $phone ?? '') }}" required>
        <button class="pv-btn" type="submit">Позвонить мне</button>
      </form>
      @if ($pending)</div></details>@endif

      @if ($pending)
        <details class="pv-more">
          <summary>Нет звонка?</summary>
          <div class="pv-more-body">
            <ul class="pv-help">
              <li>Обычно звонок приходит в течение 30 секунд.</li>
              <li>Если линия занята или нет сети, звонок не пройдёт.</li>
              <li>Определитель или блокировщик может скрыть незнакомый номер — проверьте их.</li>
              <li>Пропустили звонок? Номер останется в журнале вызовов, цифры оттуда тоже подойдут.</li>
              @guest <li>Номер ещё не привязан к кабинету — тогда звонок не поступит. Войдите другим способом.</li>@endguest
            </ul>
          </div>
        </details>
      @endif

      @unless ($pending)
        <p class="pv-note">{{ auth()->check() ? 'Звонок бесплатный. Телефон с этим номером должен быть рядом — подойдёт и номер родителя. Один номер — один кабинет.' : 'Звонок бесплатный, трубку брать не нужно. Номер ещё не привязан? Войдите другим способом — привязать его предложим сразу после входа.' }}</p>
      @endunless
    </section>
  </div>
</main>
@endsection
