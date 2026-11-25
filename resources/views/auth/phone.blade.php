@extends('layouts.pwa')
@section('title', 'Телефон — Паломатика')
@section('focused-auth', '1')
@push('styles')
  .phone-screen { min-height:100svh; display:grid; align-content:center; justify-items:center; padding: max(24px,env(safe-area-inset-top)) 18px max(32px,env(safe-area-inset-bottom)); background:radial-gradient(ellipse at 50% 32%,var(--accent-bg),transparent 60%); }
  .phone-wrap { width:min(100%,390px); }
  .phone-wordmark { margin:0 0 22px; text-align:center; font:20px var(--display); letter-spacing:-.7px; color:var(--text); }
  .phone-wordmark span { color:var(--accent); }
  .phone-card { overflow:hidden; border:1px solid var(--border); border-radius:24px; background:var(--surface); box-shadow:0 18px 55px #0002; }
  .phone-art { display:grid; place-items:center; height:115px; color:#bbd8ff; background-color:#172a48; background-image:radial-gradient(circle at center,#30599b99,transparent 65%),linear-gradient(#99bfff12 1px,transparent 1px),linear-gradient(90deg,#99bfff12 1px,transparent 1px); background-size:auto,22px 22px,22px 22px; }
  .phone-glyph { width:58px; height:58px; display:grid; place-items:center; border-radius:18px; background:#233e67; border:1px solid #759bcd; box-shadow:0 0 0 9px #779fcc14,0 0 0 19px #779fcc0c; }
  .phone-content { padding:clamp(20px,6vw,30px); }
  .phone-content h1 { font:700 clamp(25px,7vw,30px)/1.15 var(--body); letter-spacing:-.8px; }
  .phone-copy { margin:9px 0 22px; color:var(--muted); font-size:15px; line-height:1.5; }
  .phone-field { display:grid; gap:7px; font-size:14px; font-weight:700; }
  .phone-field input { display:block; width:100%; height:52px; padding:0 14px; border:1px solid var(--border); border-radius:12px; background:var(--bg); color:var(--text); font:600 19px var(--body); }
  .phone-field input:focus { outline:2px solid var(--accent); outline-offset:1px; }
  .phone-field input[name=code] { letter-spacing:.25em; }
  .phone-submit { width:100%; min-height:50px; margin-top:15px; border:0; border-radius:12px; background:#346bd1; color:white; cursor:pointer; font:800 16px var(--body); }
  .phone-submit:active { background:#2859b5; }
  .phone-alt { text-align:center; margin-top:17px; color:var(--muted); }
  .phone-link,.phone-alt summary { color:var(--accent); font:700 14px var(--body); cursor:pointer; }
  .phone-link { display:inline-block; padding:8px; border:0; background:none; text-decoration:none; }
  .phone-alt form { text-align:left; margin-top:14px; }
  .phone-error { border-left:3px solid var(--red); padding:9px 12px; margin-bottom:14px; color:var(--text); background:var(--red-bg); font-size:14px; }
@endpush
@section('body')
<main class="phone-screen">
  <div class="phone-wrap">
    <div class="phone-wordmark">Пало<span>матика</span></div>
    <section class="phone-card" aria-labelledby="phone-title">
      <div class="phone-art" aria-hidden="true">
        <div class="phone-glyph">
          <svg width="31" height="31" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5.5 3.5 8 3l2 5-2.3 1.8a15 15 0 0 0 6.5 6.5L16 14l5 2-.5 2.5c-.3 1.6-1.5 2.6-3.1 2.3C10.3 19.4 4.6 13.7 3.2 6.6 2.9 5 3.9 3.8 5.5 3.5Z"/><path d="M15 4a7 7 0 0 1 5 5m-5-1.5a3.5 3.5 0 0 1 1.5 1.5"/>
          </svg>
        </div>
      </div>
      <div class="phone-content">
        <h1 id="phone-title">{{ $pending ? 'Подтвердите номер' : (auth()->check() ? 'Привязать телефон' : 'Войти по телефону') }}</h1>
        <p class="phone-copy">{{ $pending ? 'Введите последние четыре цифры номера, с которого вам позвонили. Брать трубку не нужно.' : (auth()->check() ? 'Подтвердите номер звонком, чтобы продолжить в своём кабинете.' : 'На привязанный номер поступит звонок для входа в кабинет.') }}</p>
        @if ($errors->any())
          <p role="alert" class="phone-error">{{ $errors->first() }}</p>
        @endif
        @if ($pending)
          <form method="post">
            @csrf
            <label class="phone-field">Последние 4 цифры входящего номера
              <input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{4}" maxlength="4" required autofocus>
            </label>
            <button class="phone-submit">Подтвердить</button>
          </form>
          <details class="phone-alt"><summary>Запросить звонок ещё раз</summary>
        @endif
        <form method="post">
          @csrf
          <input type="hidden" name="action" value="start">
          <label class="phone-field">Номер телефона
            <input name="phone" type="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" required>
          </label>
          <button class="phone-submit">Получить звонок</button>
        </form>
        @if ($pending)
          </details>
        @endif
      </div>
    </section>
    <div class="phone-alt">
      @auth
        <form method="post" action="/logout" style="text-align:center">@csrf<button class="phone-link">Выйти из аккаунта</button></form>
      @else
        <a class="phone-link" href="/login">Другой способ входа</a>
      @endauth
    </div>
  </div>
</main>
@endsection
