# MiniAppController Refactoring Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Разбить god-контроллер MiniAppController (2313 строк, 40 публичных методов) на 5 специализированных контроллеров, попутно исправив баги и добавив тесты.

**Architecture:** Выделяем 5 bounded contexts: Auth, Student, Teacher, Billing, Admin. Shared-хелперы (`resolveMiniAppRole`, `issueOnboardingToken`, `variantModeLabel`) выносим в trait `MiniAppHelpers`. Маршруты в `routes/web.php` обновляем, указывая на новые контроллеры. Все существующие URL-пути (`/tg/*`) остаются прежними.

**Tech Stack:** PHP 8.2, Laravel 10, SQLite in-memory для тестов.

---

## Фаза 1 — Быстрые фиксы

### Task 1: Исправить баг редиректа в register.blade.php

**Files:**
- Modify: `resources/views/auth/register.blade.php:266`
- Modify: `resources/views/auth/register.blade.php:424`

**Step 1: Исправить fallback-редирект**

В строке 266 заменить:
```js
window.location.href = data.redirect_to || '/dashboard';
```
на:
```js
window.location.href = data.redirect_to || '/tg/dashboard';
```

В строке 424 — аналогичная замена.

**Step 2: Проверить login.blade.php для сравнения**

Убедиться, что `login.blade.php:253` и `login.blade.php:402` уже используют `/tg/dashboard`. Не менять.

**Step 3: Коммит**

```bash
git add resources/views/auth/register.blade.php
git commit -m "fix: redirect to /tg/dashboard after Telegram registration (not /dashboard)"
```

---

### Task 2: Обновлять tg_username в findOrCreateUser

**Files:**
- Modify: `app/Services/TelegramMiniAppAuthService.php:93-94`
- Test: `tests/Feature/TelegramWebAppAuthTest.php` (добавить тест)

**Step 1: Написать тест на обновление username**

В файле `tests/Feature/TelegramWebAppAuthTest.php` добавить метод:

```php
public function test_existing_user_gets_tg_username_updated_on_login(): void
{
    // Добавляем колонку tg_username в тестовую схему
    if (!Schema::hasColumn('users', 'tg_username')) {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tg_username', 100)->nullable();
        });
    }

    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => null,
        'oauth_provider' => 'telegram',
        'oauth_id' => '555666777',
        'tg_username' => 'old_username',
    ]);

    $initData = $this->makeSignedInitData([
        'auth_date' => (string) now()->timestamp,
        'query_id' => 'AAHdF6IQAAAAAN0XohDhrOrf',
        'user' => [
            'id' => 555666777,
            'first_name' => 'New',
            'last_name' => 'Name',
            'username' => 'new_username',
        ],
    ]);

    $response = $this->postJson('/api/auth/telegram/webapp-login', [
        'initData' => $initData,
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $user->refresh();
    $this->assertSame('new_username', $user->tg_username);
}
```

**Step 2: Запустить тест — убедиться что FAIL**

```bash
php artisan test --filter=test_existing_user_gets_tg_username_updated_on_login
```
Expected: FAIL — `assertSame('new_username', ...)` не пройдёт, т.к. обновления ещё нет.

**Step 3: Реализовать обновление username**

В `app/Services/TelegramMiniAppAuthService.php`, строки 93-94, заменить:

```php
if ($user) {
    return $user;
}
```

на:

```php
if ($user) {
    $updates = [];
    $newUsername = trim((string) ($telegramUser['username'] ?? ''));
    if ($newUsername !== '' && $user->tg_username !== $newUsername) {
        $updates['tg_username'] = $newUsername;
    }
    if ($updates !== []) {
        $user->update($updates);
    }
    return $user;
}
```

Также при создании нового пользователя (строка 102) добавить `tg_username`:

```php
return User::create([
    'name' => $name,
    'oauth_provider' => 'telegram',
    'oauth_id' => $telegramId,
    'tg_username' => trim((string) ($telegramUser['username'] ?? '')) ?: null,
    'avatar' => $telegramUser['photo_url'] ?? null,
    'trial_ends_at' => now()->addDays(7),
]);
```

**Step 4: Запустить тест — убедиться что PASS**

```bash
php artisan test --filter=test_existing_user_gets_tg_username_updated_on_login
```
Expected: PASS

**Step 5: Коммит**

```bash
git add app/Services/TelegramMiniAppAuthService.php tests/Feature/TelegramWebAppAuthTest.php
git commit -m "fix: sync tg_username on every Telegram login, not just on registration"
```

---

## Фаза 2 — Тесты на auth bridge

### Task 3: Feature-тесты на MiniApp auth bridge

**Files:**
- Create: `tests/Feature/MiniAppAuthBridgeTest.php`

**Step 1: Создать тест-файл с setup**

Используем ту же SQLite-in-memory схему, что и в `MiniAppTeacherRoutesTest`.

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MiniAppAuthBridgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        Config::set('services.telegram.bot_token', '123456:TEST_BOT_TOKEN');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('role', 32)->default('student');
            $table->string('oauth_provider')->nullable();
            $table->string('oauth_id')->nullable();
            $table->string('tg_username', 100)->nullable();
            $table->string('avatar')->nullable();
            $table->unsignedTinyInteger('grade_num')->nullable();
            $table->string('grade_letter', 5)->nullable();
            $table->string('school_number', 20)->nullable();
            $table->string('city', 80)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->unsignedBigInteger('referred_by_user_id')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('tg_trial_used')->default(false);
            $table->timestamp('tg_premium_until')->nullable();
            $table->integer('star_balance')->default(0);
            $table->timestamps();
        });
    }

    // --- Auth Bridge: Happy Path ---

    public function test_auth_continue_restores_session_from_handoff_token(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
        ]);

        $token = 'test_handoff_token_abc';
        Cache::put('tg_auth_handoff:' . $token, [
            'user_id' => $user->id,
            'redirect_to' => '/tg/dashboard',
        ], now()->addMinutes(2));

        $response = $this->get('/tg/auth/continue?token=' . $token);

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    // --- Auth Bridge: Session Loss ---

    public function test_auth_continue_without_token_and_no_session_redirects_to_home(): void
    {
        $response = $this->get('/tg/auth/continue');

        $response->assertRedirect('/tg');
    }

    public function test_auth_continue_with_expired_token_and_no_session_redirects_to_home(): void
    {
        $response = $this->get('/tg/auth/continue?token=expired_token_xyz');

        $response->assertRedirect('/tg');
    }

    public function test_auth_continue_with_valid_session_works_without_token(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/tg/auth/continue');

        $response->assertOk();
    }

    // --- Onboarding Token Fallback ---

    public function test_auth_continue_renders_onboarding_for_user_without_onboarding(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $token = 'test_handoff_onboarding';
        Cache::put('tg_auth_handoff:' . $token, [
            'user_id' => $user->id,
            'redirect_to' => '/tg/onboarding',
        ], now()->addMinutes(2));

        $response = $this->get('/tg/auth/continue?token=' . $token);

        $response->assertOk();
        $response->assertViewIs('miniapp.onboarding');
        $this->assertAuthenticatedAs($user);
    }

    public function test_save_onboarding_with_token_fallback_when_session_lost(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $onbToken = 'test_onb_token_123';
        Cache::put('tg_onb_token:' . $onbToken, [
            'user_id' => $user->id,
        ], now()->addMinutes(20));

        // POST without session (not logged in), but with valid onboarding token
        $response = $this->postJson('/tg/onboarding', [
            'name' => 'Тест Ученик',
            'grade_num' => 9,
            'grade_letter' => 'А',
            'school_number' => '42',
            'city' => 'Москва',
            'onboarding_token' => $onbToken,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $user->refresh();
        $this->assertSame('Тест Ученик', $user->name);
        $this->assertNotNull($user->onboarding_completed_at);
    }

    public function test_save_onboarding_without_session_and_without_token_returns_401(): void
    {
        $response = $this->postJson('/tg/onboarding', [
            'name' => 'Тест',
            'grade_num' => 9,
            'grade_letter' => 'А',
            'school_number' => '42',
        ]);

        $response->assertStatus(401);
    }

    // --- Home Redirect Invariants ---

    public function test_home_redirects_authed_user_to_dashboard(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/tg');

        $response->assertRedirect('/tg/dashboard');
    }

    public function test_home_redirects_authed_user_without_onboarding_to_onboarding(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/tg');

        $response->assertRedirect('/tg/onboarding');
    }

    public function test_home_preserves_startapp_param(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/tg?startapp=ref_42');

        $response->assertRedirect('/tg/dashboard?startapp=ref_42');
    }

    // --- Bridge Ping ---

    public function test_auth_bridge_ping_returns_ok(): void
    {
        $response = $this->postJson('/tg/auth/bridge-ping');

        $response->assertOk()->assertJsonPath('ok', true);
    }
}
```

**Step 2: Запустить тесты**

```bash
php artisan test --filter=MiniAppAuthBridgeTest
```
Expected: все PASS (тесты проверяют существующее поведение, не новый код).

**Step 3: Коммит**

```bash
git add tests/Feature/MiniAppAuthBridgeTest.php
git commit -m "test: add auth bridge feature tests (happy path, session loss, onboarding token)"
```

---

## Фаза 3 — Разбиение контроллера

### Task 4: Создать trait MiniAppHelpers

Shared-методы, которые используются из нескольких контекстов.

**Files:**
- Create: `app/Http/Controllers/Traits/MiniAppHelpers.php`

**Step 1: Создать trait**

```php
<?php

namespace App\Http\Controllers\Traits;

use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait MiniAppHelpers
{
    protected function issueOnboardingToken(int $userId): string
    {
        $token = Str::random(48);
        Cache::put('tg_onb_token:' . $token, ['user_id' => $userId], now()->addMinutes(20));
        return $token;
    }

    private function resolveMiniAppRole(Request $request, ?User $user): string
    {
        if (!$user) {
            return 'student';
        }

        if ($user->role === 'admin') {
            $viewAs = $request->session()->get('view_as_role');
            if (in_array($viewAs, ['student', 'teacher'], true)) {
                return $viewAs;
            }
            return 'teacher';
        }

        return $user->role;
    }

    private function variantModeLabel(?OgeVariant $variant): string
    {
        if (!$variant) {
            return 'Вариант';
        }

        $mode = $variant->mode ?? 'full';
        return match (true) {
            str_starts_with($mode, 'mini_') => 'Мини-вариант',
            $mode === 'full' => 'Полный вариант',
            $mode === 'part2' => '2-я часть',
            $mode === 'tasks_part1' => 'Задания 1-й части',
            default => $mode,
        };
    }

    protected function modeName(string $mode): string
    {
        return match ($mode) {
            'full' => 'Полный вариант',
            'part2' => '2-я часть',
            'tasks_part1' => '1-я часть (задания)',
            default => $mode,
        };
    }
}
```

**Step 2: Запустить существующие тесты — убедиться что ничего не сломано**

```bash
php artisan test --filter=MiniApp
```

**Step 3: Коммит**

```bash
git add app/Http/Controllers/Traits/MiniAppHelpers.php
git commit -m "refactor: extract MiniAppHelpers trait for shared controller methods"
```

---

### Task 5: Выделить MiniAppTeacherController

Самый изолированный контекст: 11 методов, свой route-prefix `/tg/teacher/*`.

**Files:**
- Create: `app/Http/Controllers/MiniAppTeacherController.php`
- Modify: `app/Http/Controllers/MiniAppController.php` — удалить teacher-методы
- Modify: `routes/web.php:447-461` — указать на новый контроллер

**Step 1: Создать контроллер**

Перенести методы: `teacherDashboard`, `teacherLessons`, `teacherStudents`, `teacherStudentProfile`, `teacherStudentAttemptDetail`, `toggleTeacherStudentOwnership`, `updateTeacherStudentAlias`, `teacherVariants`, `teacherHomework`, `assignHomework`, `updateStudentLink`, `teacherReferrals`.

Также перенести private-хелперы, используемые ТОЛЬКО в teacher-контексте: `fetchEvriumSchedule`, `resolveEvriumSlots`, `buildTodayLessonSlots`, `determineLessonStatus`, `collectTeacherScheduleData`.

Конструктор получит только нужные зависимости: `TaskDataService`, `OgeAttemptService`, `OgeVariantBuilderService`, `MiniAppTaskCanonicalizer`.

Используется `MiniAppHelpers` trait.

**Step 2: Обновить routes/web.php**

В секции `/tg/teacher/*` (строки 447-461) заменить `MiniAppController::class` на `MiniAppTeacherController::class`.

Добавить `use App\Http\Controllers\MiniAppTeacherController;` в начало routes.

**Step 3: Удалить перенесённые методы из MiniAppController**

Удалить все teacher-методы и teacher-only хелперы. Не удалять `resolveMiniAppRole` и `variantModeLabel` — они используются и в student контексте.

**Step 4: Добавить trait MiniAppHelpers в оба контроллера**

```php
use App\Http\Controllers\Traits\MiniAppHelpers;

class MiniAppTeacherController extends Controller
{
    use MiniAppHelpers;
    // ...
}
```

И в MiniAppController:
```php
use App\Http\Controllers\Traits\MiniAppHelpers;

class MiniAppController extends Controller
{
    use MiniAppHelpers;
    // ... (удалить дублирующие методы, которые теперь в trait)
}
```

**Step 5: Запустить тесты**

```bash
php artisan test --filter=MiniApp
```
Expected: все тесты PASS, включая MiniAppTeacherRoutesTest.

**Step 6: Коммит**

```bash
git add app/Http/Controllers/MiniAppTeacherController.php app/Http/Controllers/MiniAppController.php routes/web.php
git commit -m "refactor: extract MiniAppTeacherController (11 methods + schedule helpers)"
```

---

### Task 6: Выделить MiniAppBillingController

**Files:**
- Create: `app/Http/Controllers/MiniAppBillingController.php`
- Modify: `app/Http/Controllers/MiniAppController.php`
- Modify: `routes/web.php:442-445`

**Step 1: Создать контроллер**

Перенести методы: `activateTrial`, `buyPremium`, `requestPayout`, `giftSeen`.

Конструктор: без зависимостей (все методы используют только Eloquent и Auth напрямую).

**Step 2: Обновить маршруты**

```php
Route::post('/premium/trial', [MiniAppBillingController::class, 'activateTrial'])->name('miniapp.premium.trial');
Route::post('/premium/buy', [MiniAppBillingController::class, 'buyPremium'])->name('miniapp.premium.buy');
Route::post('/premium/payout', [MiniAppBillingController::class, 'requestPayout'])->name('miniapp.premium.payout');
Route::post('/gift/seen', [MiniAppBillingController::class, 'giftSeen'])->name('miniapp.gift.seen');
```

**Step 3: Удалить методы из MiniAppController**

**Step 4: Запустить тесты**

```bash
php artisan test --filter=MiniApp
```

**Step 5: Коммит**

```bash
git add app/Http/Controllers/MiniAppBillingController.php app/Http/Controllers/MiniAppController.php routes/web.php
git commit -m "refactor: extract MiniAppBillingController (trial, premium, payout, gifts)"
```

---

### Task 7: Выделить MiniAppAdminController

**Files:**
- Create: `app/Http/Controllers/MiniAppAdminController.php`
- Modify: `app/Http/Controllers/MiniAppController.php`
- Modify: `routes/web.php:465-468`

**Step 1: Создать контроллер**

Перенести: `adminVariants`, `createCuratedVariant`.

Конструктор: `TaskDataService`, `OgeVariantBuilderService`, `MiniAppTaskCanonicalizer`, `MiniAppTaskSanitizer`.

**Step 2: Обновить маршруты**

```php
Route::middleware(['role:teacher,admin'])->prefix('admin')->group(function () {
    Route::get('/variants', [MiniAppAdminController::class, 'adminVariants'])->name('miniapp.admin.variants');
    Route::post('/variants/create', [MiniAppAdminController::class, 'createCuratedVariant'])->name('miniapp.admin.variants.create');
});
```

**Step 3: Удалить методы из MiniAppController**

**Step 4: Тесты + коммит**

```bash
php artisan test --filter=MiniApp
git add app/Http/Controllers/MiniAppAdminController.php app/Http/Controllers/MiniAppController.php routes/web.php
git commit -m "refactor: extract MiniAppAdminController (curated variants)"
```

---

### Task 8: Выделить MiniAppAuthController

**Files:**
- Create: `app/Http/Controllers/MiniAppAuthController.php`
- Modify: `app/Http/Controllers/MiniAppController.php`
- Modify: `routes/web.php:404-414,418-420,423`

**Step 1: Создать контроллер**

Перенести: `home`, `authenticate`, `authBridgePing`, `authContinue`, `onboarding`, `saveOnboarding`, `switchMode`.

Конструктор: `TelegramMiniAppAuthService`.

Использует `MiniAppHelpers` trait для `issueOnboardingToken` и `resolveMiniAppRole`.

**Step 2: Обновить маршруты**

```php
Route::get('/', [MiniAppAuthController::class, 'home'])->name('miniapp.home');
Route::post('/auth', [MiniAppAuthController::class, 'authenticate'])->name('miniapp.auth');
Route::post('/auth/bridge-ping', [MiniAppAuthController::class, 'authBridgePing'])->name('miniapp.auth.bridge_ping');
Route::get('/auth/continue', [MiniAppAuthController::class, 'authContinue'])->name('miniapp.auth.continue');
Route::post('/onboarding', [MiniAppAuthController::class, 'saveOnboarding'])->name('miniapp.onboarding.save');

Route::middleware(['auth'])->group(function () {
    Route::post('/mode/{role}', [MiniAppAuthController::class, 'switchMode'])->where('role', 'student|teacher')->name('miniapp.mode.switch');
    Route::get('/onboarding', [MiniAppAuthController::class, 'onboarding'])->name('miniapp.onboarding');
    // ... student routes remain on MiniAppController (now StudentController)
});
```

**Step 3: Тесты**

```bash
php artisan test --filter=MiniApp
```

**Step 4: Коммит**

```bash
git add app/Http/Controllers/MiniAppAuthController.php app/Http/Controllers/MiniAppController.php routes/web.php
git commit -m "refactor: extract MiniAppAuthController (auth bridge, onboarding, mode switch)"
```

---

### Task 9: Переименовать MiniAppController → MiniAppStudentController

**Files:**
- Rename: `app/Http/Controllers/MiniAppController.php` → `app/Http/Controllers/MiniAppStudentController.php`
- Modify: `routes/web.php` — все оставшиеся `/tg/*` student-маршруты

**Step 1: Переименовать файл и класс**

Оставшиеся методы (14): `dashboard`, `newTasks`, `part2`, `tasksPart1`, `mini`, `startMini`, `startFull`, `test`, `results`, `history`, `historyDetail`, `tutor`, `profile`, `studentHomework`.

Конструктор: `MiniVariantService`, `OgeAttemptService`, `OgeVariantBuilderService`, `OgeVariantPoolService`, `TaskDataService`, `MiniAppTaskCanonicalizer`, `MiniAppTaskSanitizer`.

**Step 2: Обновить все маршруты**

Заменить `MiniAppController::class` → `MiniAppStudentController::class`.

**Step 3: Тесты**

```bash
php artisan test --filter=MiniApp
```

**Step 4: Коммит**

```bash
git add app/Http/Controllers/MiniAppStudentController.php app/Http/Controllers/MiniAppController.php routes/web.php
git commit -m "refactor: rename MiniAppController to MiniAppStudentController (final split)"
```

---

## Итоговая структура

| Контроллер | Методы | Строк (~) |
|---|---|---|
| `MiniAppAuthController` | 7 | ~250 |
| `MiniAppStudentController` | 14 | ~900 |
| `MiniAppTeacherController` | 12 | ~700 |
| `MiniAppBillingController` | 4 | ~120 |
| `MiniAppAdminController` | 2 | ~350 |
| `MiniAppHelpers` trait | 4 shared | ~60 |
| **Итого** | 39+4 | ~2380 |

URL-пути не меняются. Все тесты проходят.
