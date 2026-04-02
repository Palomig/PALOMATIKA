@extends('layouts.pwa')
@section('title', 'Перенос аккаунта — Palomatika')
@section('body')
<div class="page" style="justify-content:center;min-height:100vh;">
  <div style="text-align:center;" class="anim-up">
    <div style="font-family:var(--display);font-size:24px;color:var(--accent);margin-bottom:8px;">Добро пожаловать!</div>
    <p style="font-size:13px;color:var(--muted);line-height:1.6;">
      @if($userId)
        Ваш аккаунт из Telegram будет сохранён.<br>Войдите через удобный сервис:
      @else
        Войдите, чтобы начать пользоваться Palomatika:
      @endif
    </p>
  </div>

  <div class="card anim-up" style="animation-delay:0.05s;">
    <div class="sec-label" style="margin-bottom:16px;">Выберите способ входа</div>
    <div style="display:flex;flex-direction:column;gap:10px;">
      @foreach(['vkontakte' => 'ВКонтакте', 'yandex' => 'Яндекс', 'google' => 'Google'] as $provider => $label)
      <a href="/auth/{{ $provider }}{{ $token ? '?migration_token='.$token : '' }}" class="btn btn-surface">
        {{ $label }}
      </a>
      @endforeach
    </div>
  </div>
</div>
@endsection
