@extends('layouts.pwa')
@section('title', 'Телефон — Паломатика')
@section('body')
<main class="page">
    <h1>{{ auth()->check() ? 'Привязать телефон' : 'Войти по телефону' }}</h1>
    @if (session('phone_linked'))
        <p role="status">Телефон привязан. В следующий раз можно войти по звонку.</p>
        <a href="{{ auth()->user()->isParent() ? '/parent/dashboard' : '/' }}">Вернуться в кабинет</a>
    @endif
    @if ($errors->any())
        <p role="alert">{{ $errors->first() }}</p>
    @endif
    @if ($pending)
        <form method="post">
            @csrf
            <label>Последние 4 цифры входящего номера
                <input name="code" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required autofocus>
            </label>
            <button class="btn" type="submit">Подтвердить</button>
        </form>
    @endif
    <form method="post">
        @csrf
        <input type="hidden" name="action" value="start">
        <label>Номер телефона <input name="phone" type="tel" autocomplete="tel" placeholder="+7" required></label>
        <button class="btn" type="submit">Получить звонок</button>
    </form>
    @guest
        <p>При первом входе войдите привычным способом и привяжите номер в кабинете.</p>
    @endguest
</main>
@endsection
