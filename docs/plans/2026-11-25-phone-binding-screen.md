# Экран привязки телефона в два шага — план реализации

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Экран `/phone-login` становится мастером из двух шагов — «Номер» и «Звонок»: пользователь понимает, зачем просят телефон, вводит номер с маской, видит, на какой номер звонят, и вводит код в четыре ячейки.

**Architecture:** Состояние экрана остаётся серверным (`pending` из сессии). Контроллер дополнительно кладёт в сессию введённый номер и время звонка — для показа на втором шаге и паузы перед повтором. Маска номера, ячейки кода и отсчёт — прогрессивное улучшение на vanilla JS внутри Blade: без JavaScript экран работает как раньше.

**Tech Stack:** Laravel 10, Blade, vanilla JS без сборщика, токены `layouts.pwa`, PHPUnit на MySQL `palomatika_test`, Playwright для поведения и картинок.

**Дизайн:** [2026-11-25-phone-binding-screen-design.md](2026-11-25-phone-binding-screen-design.md) — читать перед началом.

---

## Контекст исполнителю

Рабочая директория: `/home/dev/palomatika-phone-screen` — клон от `origin/main` (`a7a5bca`), ветка `claude/phone-screen`, не запушена.

**Почему отдельное дерево.** В `/home/dev/palomatika-ucaller` параллельно работает Codex по той же фиче. Его дерево не трогать: скрипт деплоя заливает файлы из рабочего дерева.

**Тесты.** База поднята: MySQL `127.0.0.1`, база `palomatika_test`, пользователь `palomatika_test` / `palomatika_test_pw` (как в `phpunit.xml`). Первый прогон после пересоздания базы идёт около минуты (миграции), дальше — секунды.

```bash
cd /home/dev/palomatika-phone-screen && php artisan test --filter=PhoneScreenTest
```

**Не запускать весь `php artisan test`** — полный прогон красный и на `main`. Строки «deprecated» про `PDO::MYSQL_ATTR_SSL_CA` в `config/database.php` — это PHP 8.5, к задаче отношения не имеет.

**`vendor/` уже на месте.** В репозитории отслеживаются семь файлов `vendor/composer/*` и `vendor/socialiteproviders/*`: перед коммитом `git status` должен показывать только файлы задачи.

**Локальный стенд** — живое приложение на SQLite, звонок Zvonok подменён. Пользователь `artem@example.test` / `secret12` (ученик «Артём»). Запуск:

```bash
cd /home/dev/palomatika-phone-screen && php -S 127.0.0.1:8099 -t public /tmp/palomatika-phone-snap/router.php
```

Заглушка в `router.php` всегда отвечает кодом `4567`; номер, оканчивающийся на `0000`, имитирует сбой API. Лимит стенда — 3 звонка на номер в час, поэтому перед каждым прогоном очищай счётчики:

```bash
cd /home/dev/palomatika-phone-screen && php artisan tinker --execute='DB::table("phone_login_limits")->delete();'
```

**Не коммитить:** `.env` (в `.gitignore`), `storage/stand.sqlite` (в `.git/info/exclude`), скрипты стенда из `/tmp/palomatika-phone-snap/`.

**Деплой.** Пуш ветки `claude/*` ничего не выкладывает. Деплой — `scripts/promote.sh claude/phone-screen` после решения Стаса. В рамках плана не делаем.

---

## Task 1: Контроллер хранит номер и время звонка

**Files:**
- Create: `tests/Feature/PhoneScreenTest.php`
- Modify: `app/Http/Controllers/Auth/PhoneLoginController.php`

Тесты этого шага смотрят только в сессию: старый шаблон номера ещё не показывает. Проверки сессии цепляются к ответу — у тест-класса в этой версии Laravel их нет. Во втором и третьем тесте сначала проверяется, что ключ появился: иначе проверка исчезновения проходит ещё до написания фичи.

**Step 1: Написать падающие тесты**

Создай `tests/Feature/PhoneScreenTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_details_are_kept_for_the_second_step(): void
    {
        $this->fakeCall();
        $this->post('/phone-login', ['action' => 'start', 'phone' => '8 (900) 123-45-67'])
            ->assertRedirect('/phone-login')
            ->assertSessionHas('phone_call', function ($call) {
                return is_array($call) && $call['phone'] === '+7 900 123-45-67' && abs($call['at'] - time()) < 5;
            });
    }

    public function test_call_details_are_forgotten_once_the_code_is_accepted(): void
    {
        $this->fakeCall();
        $user = User::factory()->create();
        DB::table('phone_login_numbers')->insert(['phone' => '79001234567', 'user_id' => $user->id]);

        $this->post('/phone-login', ['action' => 'start', 'phone' => '+7 900 123-45-67'])
            ->assertRedirect('/phone-login')
            ->assertSessionHas('phone_call');
        $this->post('/phone-login', ['code' => '4567'])
            ->assertRedirect('https://student.'.config('app.base_domain').'/')
            ->assertSessionMissing('phone_call');
    }

    public function test_call_details_are_dropped_when_a_new_call_is_requested(): void
    {
        $this->fakeCall();
        $this->post('/phone-login', ['action' => 'start', 'phone' => '+7 900 123-45-67'])
            ->assertRedirect('/phone-login')
            ->assertSessionHas('phone_call');

        $this->post('/phone-login', ['action' => 'start', 'phone' => '123'])
            ->assertSessionHasErrors()
            ->assertSessionMissing('phone_call');
    }

    public function test_wrong_code_error_tells_how_to_fix_it(): void
    {
        $this->fakeCall();
        $this->post('/phone-login', ['action' => 'start', 'phone' => '+7 900 123-45-67'])->assertRedirect('/phone-login');

        $this->post('/phone-login', ['code' => '0000'])->assertSessionHasErrors();
        $this->get('/phone-login')->assertOk()
            ->assertSee('Код не принят. Проверьте цифры или закажите звонок ещё раз.');
    }

    private function fakeCall(): void
    {
        config(['services.zvonok.id' => 'test-id', 'services.zvonok.key' => 'test-key']);
        Http::fake([
            'zvonok.com/manager/cabapi_external/api/v1/phones/flashcall/' => Http::response([
                'status' => 'ok', 'data' => ['pincode' => '4567', 'call_id' => 1],
            ]),
        ]);
    }
}
```

**Step 2: Убедиться, что тесты падают**

```bash
cd /home/dev/palomatika-phone-screen && php artisan test --filter=PhoneScreenTest
```

Ожидание: 4 failed — в сессии нет `phone_call`, текст ошибки кода прежний.

**Step 3: Контроллер**

В `PhoneLoginController` замени `form()`:

```php
    public function form(Request $request)
    {
        abort_unless(config('services.zvonok.key') && config('services.zvonok.id'), 404);
        return response()->view('auth.phone', [
            'pending' => $request->session()->has('phone_challenge'),
            'call' => $request->session()->get('phone_call'),
        ])->header('Cache-Control', 'no-store');
    }
```

В `submit()` в ветке `start` замени забывание и запись сессии:

```php
            $request->session()->forget(['phone_challenge', 'phone_call']);
            try {
                $challenge = $service->start($data['phone'], $request->ip(), $actor);
            } catch (\DomainException $exception) {
                return back()->withErrors($exception->getMessage());
            }
            $request->session()->put('phone_challenge', $challenge);
            $request->session()->put('phone_call', ['phone' => self::format($data['phone']), 'at' => time()]);
            return redirect('/phone-login');
```

Там же поменяй текст ошибки кода и забывание после успеха:

```php
        if (!$user) {
            return back()->withErrors('Код не принят. Проверьте цифры или закажите звонок ещё раз.');
        }
        $request->session()->forget(['phone_challenge', 'phone_call']);
```

И добавь в конец класса приватный метод:

```php
    private static function format(string $phone): string
    {
        $digits = substr(preg_replace('/\D/', '', $phone), -10);
        return '+7 '.substr($digits, 0, 3).' '.substr($digits, 3, 3).'-'.substr($digits, 6, 2).'-'.substr($digits, 8);
    }
```

**Step 4: Прогнать тесты**

```bash
cd /home/dev/palomatika-phone-screen && php artisan test --filter=PhoneScreenTest && php artisan test --filter=PhoneLoginTest
```

Ожидание: `PhoneScreenTest` — 4 passed, `PhoneLoginTest` — 2 passed.

**Step 5: Коммит**

```bash
cd /home/dev/palomatika-phone-screen && git add app/Http/Controllers/Auth/PhoneLoginController.php tests/Feature/PhoneScreenTest.php
git commit -m "$(cat <<'EOF'
feat(телефон): хранить номер и время звонка для второго шага

Номер берётся из того, что ввёл пользователь, поэтому «тихая» попытка
без звонка внешне не отличается от обычной.

Co-Authored-By: Claude Code <noreply@anthropic.com>
EOF
)"
```

---

## Task 2: Разметка и стили экрана

**Files:**
- Modify: `resources/views/auth/phone.blade.php` (заменяется целиком)
- Modify: `tests/Feature/PhoneScreenTest.php`

JavaScript в этом шаге нет: ячейки и кнопка повтора появляются в разметке, но оживают в задаче 3.

**Step 1: Написать падающие тесты на рендер**

Добавь в `PhoneScreenTest` перед `fakeCall()` три теста:

```php
    public function test_second_step_shows_the_typed_number_even_without_a_call(): void
    {
        $this->fakeCall();
        $this->post('/phone-login', ['action' => 'start', 'phone' => '8 (900) 123-45-67'])->assertRedirect('/phone-login');
        $this->get('/phone-login')->assertOk()
            ->assertSee('+7 900 123-45-67', false)
            ->assertSee('name="phone" value="+7 900 123-45-67"', false)
            ->assertSee('data-wait="', false)
            ->assertSee('class="pv-cells"', false)
            ->assertSee('Изменить номер')
            ->assertSee('Нет звонка?');
    }

    public function test_guest_sees_both_steps_and_a_way_back(): void
    {
        $this->fakeCall();
        $this->get('/phone-login')->assertOk()
            ->assertSee('Войти по телефону')
            ->assertSee('Номер ещё не привязан?')
            ->assertSee('href="/login"', false)
            ->assertSee('data-phone', false);
    }

    public function test_signed_in_user_sees_which_account_gets_the_number(): void
    {
        $this->fakeCall();
        $this->actingAs(User::factory()->create(['name' => 'Артём', 'role' => 'student']))
            ->get('/phone-login')->assertOk()
            ->assertSee('Подтвердите телефон')
            ->assertSee('Артём')
            ->assertSee('ученик')
            ->assertSee('action="/logout"', false)
            ->assertDontSee('Другой способ входа');
    }
```

Первый тест — главный для приватности: номер, которого нет в `phone_login_numbers`, показывается так же, как привязанный. Иначе «тихую» попытку можно узнать по пустому месту.

**Step 2: Убедиться, что падает**

```bash
cd /home/dev/palomatika-phone-screen && php artisan test --filter=PhoneScreenTest
```

Ожидание: 3 failed, 5 passed — старый шаблон не показывает номер, подсказку гостя и строку пользователя.

**Step 3: Заменить шаблон**

Содержимое `resources/views/auth/phone.blade.php` целиком:

```blade
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
```

**Step 4: Прогнать тесты**

```bash
cd /home/dev/palomatika-phone-screen && php artisan test --filter=PhoneScreenTest && php artisan test --filter=PhoneLoginTest
```

Ожидание: `PhoneScreenTest` — 7 passed, `PhoneLoginTest` — 2 passed.

**Step 5: Коммит**

```bash
cd /home/dev/palomatika-phone-screen && git add resources/views/auth/phone.blade.php tests/Feature/PhoneScreenTest.php
git commit -m "$(cat <<'EOF'
feat(телефон): экран привязки в два шага

Шаг «Номер» объясняет, зачем просят телефон, шаг «Звонок» показывает
номер и принимает код в четыре ячейки.

Co-Authored-By: Claude Code <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: Поведение в браузере, картинки, превью

**Files:**
- Create (вне репозитория): `/tmp/palomatika-phone-snap/behaviors.mjs`
- Modify: `resources/views/auth/phone.blade.php` (добавить `@push('scripts')` перед `@section('body')`)

Проверки поведения — это тесты задачи: они пишутся первыми и падают, пока в шаблоне нет JavaScript.

**Step 1: Поднять стенд и написать проверки**

Стенд запускается в отдельном терминале и живёт до конца задачи:

```bash
cd /home/dev/palomatika-phone-screen && php artisan tinker --execute='DB::table("phone_login_limits")->delete();'
php -S 127.0.0.1:8099 -t public /tmp/palomatika-phone-snap/router.php
```

Создай `/tmp/palomatika-phone-snap/behaviors.mjs`:

```js
// Поведение экрана /phone-login на локальном стенде. Код заглушки — 4567.
import { chromium } from '/home/dev/palomig-portfolio/node_modules/playwright/index.mjs';

const STAND = 'http://127.0.0.1:8099';
const results = [];
const check = (name, ok, detail = '') => results.push(`${ok ? 'PASS' : 'FAIL'}  ${name}${ok ? '' : `  → ${detail}`}`);
const section = async (name, run) => {
  try { await run(); } catch (error) { check(name, false, String(error.message).split('\n')[0]); }
};
const mobile = { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, serviceWorkers: 'block', locale: 'ru-RU' };
const submit = (page, selector) => Promise.all([page.waitForNavigation(), page.click(selector)]);
const cabinetRoute = (route) => route.fulfill({ status: 200, body: 'cabinet' });

const browser = await chromium.launch();

// 1. Маска номера
{
  const page = await (await browser.newContext(mobile)).newPage();
  page.setDefaultTimeout(5000);
  await page.goto(`${STAND}/phone-login`);
  const phone = page.locator('[data-phone] input[name=phone]');
  const expect = async (name, wanted) => check(name, (await phone.inputValue()) === wanted, await phone.inputValue());

  await section('маска', async () => {
    await phone.pressSequentially('9001234567');
    await expect('маска добавляет +7 и форматирует', '+7 (900) 123-45-67');
    await phone.fill('');
    await phone.pressSequentially('89001234567');
    await expect('маска отбрасывает восьмёрку, набранную по цифре', '+7 (900) 123-45-67');
    await phone.fill('');
    await phone.fill('+7 900 123-45-67');
    await expect('маска сохраняет вставленный номер с +7', '+7 (900) 123-45-67');
    await phone.fill('');
    await phone.fill('8314123322');
    await expect('маска не трогает код города на 8', '+7 (831) 412-33-22');
    await phone.press('Backspace');
    await phone.press('Backspace');
    await expect('backspace убирает по одной цифре', '+7 (831) 412-33');
    await phone.fill('');
    await phone.pressSequentially('900123');
    check('незаконченный номер не отправляется', await phone.evaluate((el) => !el.checkValidity()));
  });
  await page.context().close();
}

// 2. Второй шаг: ячейки кода, повторный звонок, весь путь до кабинета
{
  const context = await browser.newContext(mobile);
  await context.route('https://student.palomatika.ru/**', cabinetRoute);
  const page = await context.newPage();
  page.setDefaultTimeout(5000);
  await page.goto(`${STAND}/phone-login`);
  await page.fill('[data-phone] input[name=phone]', '+7 900 123-45-67');
  await submit(page, '[data-phone] .pv-btn');

  const cell = (index) => page.locator('.pv-cells input').nth(index);
  const code = () => page.locator('[data-code] input[name=code]').inputValue();
  const text = async (selector) => (await page.locator(selector).count()) ? (await page.textContent(selector)).trim() : '';

  check('второй шаг показывает номер', await text('.pv-number') === '+7 900 123-45-67');
  await section('ячейки кода', async () => {
    check('четыре ячейки заменяют обычное поле', await page.locator('.pv-cells input').count() === 4
      && await page.isVisible('.pv-cells') && !(await page.isVisible('.pv-otp-source')));
    check('подтверждение недоступно, пока не четыре цифры', await page.locator('[data-code] .pv-btn').isDisabled());
    await cell(0).pressSequentially('45');
    check('цифры сами переходят в следующую ячейку', (await cell(1).inputValue()) === '5'
      && (await cell(2).evaluate((el) => el === document.activeElement)) && (await code()) === '45');
    await cell(2).press('Backspace');
    check('backspace в пустой ячейке чистит предыдущую', (await cell(1).inputValue()) === '' && (await code()) === '4');
    await cell(0).evaluate((el) => {
      const data = new DataTransfer();
      data.setData('text', 'Код: 4567');
      el.dispatchEvent(new ClipboardEvent('paste', { clipboardData: data, bubbles: true, cancelable: true }));
    });
    check('вставленный код заполняет все ячейки', (await code()) === '4567' && !(await page.locator('[data-code] .pv-btn').isDisabled()));
  });
  await section('повторный звонок', async () => {
    const recall = page.locator('[data-wait]');
    check('сразу после звонка повтор недоступен', await recall.isDisabled() && /0:(29|30)/.test(await recall.textContent()));
    await page.waitForFunction(() => !document.querySelector('[data-wait]').disabled, null, { timeout: 45000 });
    check('через 30 секунд повтор доступен', (await recall.textContent()).trim() === 'Позвонить ещё раз');
    await page.click('.pv-more summary:has-text("Изменить номер")');
    check('«Изменить номер» раскрывает форму с текущим номером', (await page.locator('[data-phone] input[name=phone]').inputValue()) === '+7 (900) 123-45-67');
    await submit(page, '[data-wait]');
    check('повтор возвращает на второй шаг', await page.isVisible('.pv-cells'));
    await cell(0).pressSequentially('0000');
    await submit(page, '[data-code] .pv-btn');
    check('неверный код даёт понятную ошибку', (await text('.pv-alert')).includes('Код не принят. Проверьте цифры или закажите звонок ещё раз.'));
    await cell(0).pressSequentially('4567');
    await submit(page, '[data-code] .pv-btn');
    check('принятый код уводит с экрана', page.url() === 'https://student.palomatika.ru/');
  });
  await context.close();
}

// 3. Строка пользователя у вошедшего
{
  const context = await browser.newContext(mobile);
  await context.route('https://student.palomatika.ru/**', cabinetRoute);
  const page = await context.newPage();
  page.setDefaultTimeout(5000);
  await page.goto(`${STAND}/login`);
  await page.fill('input[name=email]', 'artem@example.test');
  await page.fill('input[name=password]', 'secret12');
  await Promise.all([page.waitForURL((url) => url.hostname === 'student.palomatika.ru'), page.click('button[type=submit]')]);
  await page.goto(`${STAND}/phone-login`);
  const person = (await page.textContent('.pv-person')).replace(/\s+/g, ' ');
  check('вошедший видит, к какому кабинету привяжется номер', person.includes('Артём') && person.includes('ученик')
    && (await page.locator('.pv-head form[action="/logout"]').count()) === 1);
  await context.close();
}

// 4. Без JavaScript
{
  const context = await browser.newContext({ ...mobile, javaScriptEnabled: false });
  const page = await context.newPage();
  page.setDefaultTimeout(5000);
  await page.goto(`${STAND}/phone-login`);
  await page.fill('[data-phone] input[name=phone]', '+7 900 123-45-68');
  await submit(page, '[data-phone] .pv-btn');
  check('без JavaScript экран остаётся рабочим', await page.isVisible('.pv-otp-source')
    && !(await page.isVisible('.pv-cells')) && !(await page.locator('[data-wait]').isDisabled()));
  await context.close();
}

await browser.close();
console.log(results.join('\n'));
process.exit(results.some((line) => line.startsWith('FAIL')) ? 1 : 0);
```

**Step 2: Убедиться, что проверки падают**

```bash
cd /home/dev/palomatika-phone-screen && php artisan tinker --execute='DB::table("phone_login_limits")->delete();' && node /tmp/palomatika-phone-snap/behaviors.mjs
```

Ожидание: код выхода 1 и не меньше десяти `FAIL` — все проверки маски, ячейки кода, пауза повтора и «повтор возвращает на второй шаг». Зелёными остаются те, что проверяют разметку без JavaScript.

**Step 3: Добавить скрипт в шаблон**

В `resources/views/auth/phone.blade.php` между `@endpush` и `@section('body')` вставь приведённое ниже. Селектор поля — строго `input[name=phone]`: внутри той же формы лежат скрытые `_token` и `action`, и маска не должна их касаться (в токене есть цифры — форма умерла бы с «страница устарела»).

```blade
@push('scripts')
  <script id="pv-js">
    (() => {
      const digits = (value) => value.replace(/\D/g, '');

      // The field always holds "+7 (900) 123-45-67", so the server gets a complete number.
      document.querySelectorAll('[data-phone] input[name=phone]').forEach((input) => {
        const apply = (raw) => {
          if (raw.startsWith('7')) raw = raw.slice(1);
          if (raw.length === 11 && raw.startsWith('8')) raw = raw.slice(1);
          const value = raw.slice(0, 10);
          input.value = value ? '+7 (' + value.slice(0, 3) + (value.length > 3 ? ') ' + value.slice(3, 6) + (value.length > 6 ? '-' + value.slice(6, 8) + (value.length > 8 ? '-' + value.slice(8) : '') : '') : '') : '';
          input.setSelectionRange(input.value.length, input.value.length);
          input.setCustomValidity(value.length > 0 && value.length < 10 ? 'Введите номер полностью' : '');
        };
        input.addEventListener('input', () => apply(digits(input.value)));
        apply(digits(input.value));
      })();

      document.querySelectorAll('[data-code]').forEach((form) => {
        const source = form.querySelector('.pv-otp-source');
        const cells = [...form.querySelectorAll('.pv-cells input')];
        const submit = form.querySelector('.pv-btn');
        const sync = () => {
          source.value = cells.map((cell) => cell.value).join('');
          submit.disabled = source.value.length !== 4;
        };
        form.querySelector('.pv-cells').hidden = false;
        form.classList.add('is-split');
        cells.forEach((cell, index) => {
          cell.addEventListener('input', () => {
            cell.value = digits(cell.value).slice(-1);
            if (cell.value) cells[index + 1]?.focus();
            sync();
          });
          cell.addEventListener('keydown', (event) => {
            if (event.key !== 'Backspace' || cell.value || !index) return;
            event.preventDefault();
            cells[index - 1].value = '';
            cells[index - 1].focus();
            sync();
          });
          cell.addEventListener('paste', (event) => {
            const text = digits(event.clipboardData.getData('text')).slice(0, 4);
            if (!text) return;
            event.preventDefault();
            cells.forEach((target, i) => { target.value = text[i] || ''; });
            cells[Math.min(text.length, 3)].focus();
            sync();
          });
          cell.addEventListener('focus', () => cell.select());
        });
        sync();
        if (document.activeElement === source) cells[0].focus();
      });

      document.querySelectorAll('[data-wait]').forEach((button) => {
        let left = Number(button.dataset.wait);
        const tick = () => {
          button.disabled = left > 0;
          button.querySelector('.pv-wait').textContent = left > 0 ? ' · 0:' + String(left).padStart(2, '0') : '';
          if (left-- > 0) setTimeout(tick, 1000);
        };
        tick();
      });
    })();
  </script>
@endpush
```

**Step 4: Прогнать проверки**

```bash
cd /home/dev/palomatika-phone-screen && php artisan tinker --execute='DB::table("phone_login_limits")->delete();' && node /tmp/palomatika-phone-snap/behaviors.mjs
```

Ожидание: 20 `PASS`, код выхода 0. Затем тесты шаблона: `php artisan test --filter=PhoneScreenTest` — 7 passed.

**Step 5: Картинки**

Скриптом стенда `shot.mjs` снять каждое состояние в обеих темах на `390×844`, плюс `360×640` и `1280×800`:

| Состояние | Как получить на стенде |
|---|---|
| Шаг 1, гость | `/phone-login` |
| Шаг 1, гость, ошибка | ввести `+7 900 000-00-00`, отправить |
| Шаг 1, вошедший | войти как `artem@example.test`, открыть `/phone-login` |
| Шаг 2, ждём звонок | номер `+7 900 123-45-67`, «Позвонить мне» |
| Шаг 2, код не принят | ввести `0000`, «Подтвердить» |
| Шаг 2, «Нет звонка?» раскрыт | раскрыть блок |

Что смотреть глазами: шаг 1 целиком помещается на `390×844` без прокрутки; на `360×640` ничего не обрезано и кнопки не уже поля; заголовок не ломается в два слова на `360`; на `1280×800` карточка по центру и не шире 420 px; ячейки кода одного размера; в светлой теме текст второстепенных подписей читается; «Позвонить ещё раз · 0:30» не переносится. Найденное править в `@push('styles')`, после правок повторить шаги 4 и 5.

Отдельно: без JavaScript (`javaScriptEnabled: false`) шаг 1 и шаг 2 выглядят опрятно — обычное поле кода вместо ячеек.

**Step 6: Опубликовать превью**

Скриптом стенда снять с живого стенда HTML каждого состояния (обе темы), вычистить из снимков скрипты макета, токены и манифест, оставить только `#pv-js`, и собрать `/var/www/html/preview/palomatika-phone/index.html`: самодостаточная страница с `<meta name="robots" content="noindex, nofollow">`, переключатель темы и рамки телефонов с состояниями. Формы в снимках не отправляют (submit отменяется), маска и ячейки в рамках работают. Проверить: `https://palomig.ru/preview/palomatika-phone/` открывается, во всех рамках есть контент, в консоли нет ошибок.

**Step 7: Коммит**

```bash
cd /home/dev/palomatika-phone-screen && git add resources/views/auth/phone.blade.php
git commit -m "$(cat <<'EOF'
feat(телефон): маска номера, ячейки кода и пауза перед повтором

Прогрессивное улучшение: без JavaScript остаются обычные поля.

Co-Authored-By: Claude Code <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: Документация

**Files:**
- Create: `.claude/product/modules/auth.md`
- Modify: `docs/plans/2026-11-25-phone-binding-screen-design.md` (строка статуса)

**Step 1:** Создай `.claude/product/modules/auth.md` — коротко: способы входа; обязательная привязка телефона (с 2026-11-25): `RequireVerifiedPhone`, `phone_login_numbers`, экран `/phone-login` в два шага, ключи сессии `phone_challenge` и `phone_call`, лимиты; где код; что проверять при изменениях. Сверить факты по `app/Http/Kernel.php`, `app/Services/PhoneLogin.php`, `app/Http/Middleware/RequireVerifiedPhone.php`.

**Step 2:** В дизайн-доке поменяй строку статуса на «реализовано в ветке `claude/phone-screen`, ждёт решения Стаса о выкатке».

**Step 3: Коммит**

```bash
cd /home/dev/palomatika-phone-screen && git add .claude/product/modules/auth.md docs/plans/2026-11-25-phone-binding-screen-design.md
git commit -m "$(cat <<'EOF'
docs(телефон): модуль авторизации и статус дизайн-дока

Co-Authored-By: Claude Code <noreply@anthropic.com>
EOF
)"
```

**Step 4: Финальная проверка**

```bash
cd /home/dev/palomatika-phone-screen && php artisan test --filter=PhoneScreenTest && php artisan test --filter=PhoneLoginTest && git status -sb
```

Ожидание: 7 и 2 теста зелёные; `git status` показывает только строку ветки. Ветка не запушена, прод не тронут.
