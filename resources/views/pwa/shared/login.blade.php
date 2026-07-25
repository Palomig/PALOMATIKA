@extends('layouts.pwa')

@section('title', $context === 'teacher' ? 'Вход — Репетитор' : 'Вход — Palomatika')

@section('body')
<div class="page" style="justify-content:center;min-height:100vh;">
  <div class="anim-up" style="text-align:center;margin-bottom:8px;">
    <div style="font-family:var(--display);font-size:28px;color:var(--accent);">palomatika</div>
    <div style="font-size:13px;color:var(--muted);margin-top:4px;">
      {{ $context === 'teacher' ? 'Кабинет репетитора' : 'Подготовка к ОГЭ' }}
    </div>
  </div>

  @if(session('error'))
  <div class="note" style="border-left-color:var(--red);color:var(--red);">{{ session('error') }}</div>
  @endif

  <div class="card anim-up" style="animation-delay:0.05s;">
    <div class="sec-label" style="margin-bottom:16px;">Войти через</div>
    <div style="display:flex;flex-direction:column;gap:10px;">

      {{-- Telegram (основной способ, OIDC redirect) --}}
      <a href="https://{{ config('app.base_domain') }}/auth/telegram/redirect?origin={{ $context }}"
        class="btn btn-left"
        style="background:#229ED9;border-color:#229ED9;color:#fff;font-weight:600;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248-2.04 9.613c-.154.683-.554.85-1.123.528l-3.1-2.285-1.496 1.44c-.165.165-.305.305-.625.305l.223-3.168 5.77-5.213c.25-.222-.055-.346-.39-.124L7.19 14.447l-3.04-.952c-.662-.207-.674-.662.138-.979l11.87-4.576c.552-.2 1.035.134.404.308z"/></svg>
        Войти через Telegram
      </a>

      {{-- Yandex --}}
      <a href="/auth/yandex" class="btn btn-surface btn-left">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#FC3F1D"><path d="M2.04 12c0-5.523 4.476-10 9.998-10C17.522 2 22 6.477 22 12s-4.478 10-9.962 10C6.516 22 2.04 17.523 2.04 12zm11.07 4.888V7.07h1.41c1.547 0 2.434.81 2.434 2.212 0 1.017-.51 1.742-1.412 2.04l1.951 5.566h-1.68l-1.74-5.13h-.644v5.13h-1.32z"/></svg>
        Яндекс
      </a>

      {{-- Google --}}
      <a href="/auth/google" class="btn btn-surface btn-left">
        <svg width="22" height="22" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
        Google
      </a>

    </div>
  </div>

  {{-- Android install button (hidden by default, shown by JS) --}}
  <button id="pwa-install-btn" onclick="installPwa()"
    class="btn btn-surface hidden anim-up"
    style="border-color:var(--accent-bd);color:var(--accent);animation-delay:0.15s;">
    📲 Установить приложение
  </button>
</div>

{{-- Бот-логин со страницы входа убран: вход через Telegram идёт OIDC-редиректом,
     а привязка чата для уведомлений живёт на /link-telegram. --}}
@endsection
