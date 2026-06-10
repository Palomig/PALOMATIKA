# Telegram OIDC Auth Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Заменить старую Telegram-авторизацию (Login Widget + бот-диплинк логин) на официальный Telegram OAuth 2.0 / OpenID Connect, ключующий по тому же `oauth_id`, что и Mini App initData, чтобы веб и Mini App вели в один аккаунт.

**Architecture:** Тонкий `TelegramOidcService` реализует Authorization Code Flow + PKCE: redirect на `oauth.telegram.org/auth`, обмен `code` на `id_token` на `/token`, верификация JWT по JWKS (`firebase/php-jwt`, уже в зависимостях). Единый `TelegramIdentityResolver::resolve()` (вынесен из `TelegramMiniAppAuthService`) используется и OIDC, и Mini App → одна учётка по `(oauth_provider='telegram', oauth_id=sub)`. Один центральный callback на `palomatika.ru`; `SESSION_DOMAIN=.palomatika.ru` шарит сессию на все поддомены, `origin` в `state` определяет, куда вернуть.

**Tech Stack:** Laravel 10, PHP 8.2, `firebase/php-jwt` v6.11, PHPUnit Feature/Unit, Guzzle (`Http::` facade).

**Dev-окружение:** worktree `/home/dev/palomatika-tg-oidc`, ветка `claude/telegram-oidc-auth` (от origin/main). `vendor/` подключается симлинком из основного чекаута для тестов (см. ниже). Дизайн: [2026-06-09-telegram-oidc-auth-design.md](2026-06-09-telegram-oidc-auth-design.md).

> **Перед прогоном тестов:** в worktree нет полного `vendor/`. Один раз:
> `cd /home/dev/palomatika-tg-oidc && mv vendor vendor_committed 2>/dev/null; ln -sfn /home/dev/palomatika/vendor vendor`
> (composer.lock идентичен). Перед коммитом симлинк не коммитить — он вне индекса.

---

## Task 0: Конфиг, env, проверка зависимости

**Files:**
- Modify: `config/services.php` (блок `'telegram' => [...]`)
- Modify: `.env.example`

**Step 1: Добавить OIDC-конфиг в `config/services.php`**

В существующий массив `'telegram' => [ ... ]` добавить ключ `oidc`:

```php
'telegram' => [
    // ...existing keys (bot_username, bot_token, webhook_secret, ...)...
    'oidc' => [
        'client_id'     => env('TELEGRAM_OIDC_CLIENT_ID', '8047450650'),
        'client_secret' => env('TELEGRAM_OIDC_CLIENT_SECRET'),
        'redirect'      => env('TELEGRAM_OIDC_REDIRECT', 'https://palomatika.ru/auth/telegram/callback'),
        'authorize_url' => 'https://oauth.telegram.org/auth',
        'token_url'     => 'https://oauth.telegram.org/token',
        'jwks_url'      => 'https://oauth.telegram.org/.well-known/jwks.json',
        'issuer'        => 'https://oauth.telegram.org',
    ],
],
```

**Step 2: Добавить env-переменные в `.env.example`**

```
TELEGRAM_OIDC_CLIENT_ID=8047450650
TELEGRAM_OIDC_CLIENT_SECRET=
TELEGRAM_OIDC_REDIRECT=https://palomatika.ru/auth/telegram/callback
```

**Step 3: Подтвердить наличие `firebase/php-jwt`**

Run: `grep -A1 '"firebase/php-jwt"' composer.json || grep '"firebase/php-jwt"' composer.lock`
Expected: пакет присутствует (v6.11). Если в `composer.json` его нет (только транзитивно) — добавить в `require`: `composer require firebase/php-jwt` (в основном чекауте, т.к. vendor симлинкнут).

**Step 4: Commit**

```bash
git add config/services.php .env.example
git commit -m "feat(auth): config + env для Telegram OIDC"
```

---

## Task 1: Единый резолвер идентичности `TelegramIdentityResolver`

Выносим логику поиска/создания telegram-юзера из `TelegramMiniAppAuthService::findOrCreateUser` в отдельный сервис, чтобы OIDC и Mini App использовали ОДИН код. Попутно убираем запись дропнутого `trial_ends_at`.

**Files:**
- Create: `app/Services/TelegramIdentityResolver.php`
- Test: `tests/Unit/TelegramIdentityResolverTest.php`
- Modify (позже, Task 4 шаг интеграции): `app/Services/TelegramMiniAppAuthService.php`

**Step 1: Написать падающий тест**

```php
<?php
namespace Tests\Unit;

use App\Models\User;
use App\Services\TelegramIdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramIdentityResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolver(): TelegramIdentityResolver
    {
        return app(TelegramIdentityResolver::class);
    }

    public function test_creates_new_user_with_telegram_identity(): void
    {
        $user = $this->resolver()->resolve([
            'id' => 555, 'username' => 'vasya', 'name' => 'Вася Пупкин', 'photo' => 'https://t.me/p.jpg',
        ]);

        $this->assertSame('telegram', $user->oauth_provider);
        $this->assertSame('555', (string) $user->oauth_id);
        $this->assertSame('vasya', $user->tg_username);
    }

    public function test_returns_same_user_for_same_telegram_id(): void
    {
        $a = $this->resolver()->resolve(['id' => 777, 'name' => 'A']);
        $b = $this->resolver()->resolve(['id' => 777, 'name' => 'A']);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, User::where('oauth_id', '777')->count());
    }

    public function test_adopts_legacy_user_with_null_provider(): void
    {
        $legacy = User::create(['name' => 'Old', 'oauth_provider' => null, 'oauth_id' => '999']);
        $resolved = $this->resolver()->resolve(['id' => 999, 'name' => 'Old']);
        $this->assertSame($legacy->id, $resolved->id);
        $this->assertSame('telegram', $resolved->fresh()->oauth_provider);
    }

    public function test_does_not_write_dropped_trial_ends_at_column(): void
    {
        $user = $this->resolver()->resolve(['id' => 111, 'name' => 'X']);
        // trial_ends_at дропнут миграцией #44 — не должно быть в атрибутах
        $this->assertArrayNotHasKey('trial_ends_at', $user->getAttributes());
    }
}
```

**Step 2: Прогнать — убедиться что падает**

Run: `php vendor/bin/phpunit tests/Unit/TelegramIdentityResolverTest.php`
Expected: FAIL — `Class "App\Services\TelegramIdentityResolver" not found`.

**Step 3: Реализовать сервис**

`app/Services/TelegramIdentityResolver.php`:

```php
<?php

namespace App\Services;

use App\Models\User;

/**
 * Единый резолвер Telegram-идентичности для ВСЕХ путей входа
 * (OIDC web + Mini App initData). Ключ: (oauth_provider='telegram', oauth_id=tg id).
 *
 * @param array{id:int|string, username?:?string, name?:?string, photo?:?string} $claims
 */
class TelegramIdentityResolver
{
    public function resolve(array $claims): User
    {
        $telegramId = (string) ($claims['id'] ?? '');
        if ($telegramId === '') {
            throw new \InvalidArgumentException('Missing Telegram user id');
        }

        $username = $this->normalizeUsername($claims['username'] ?? null);
        $avatar   = $this->normalizeAvatar($claims['photo'] ?? null);

        $user = User::where('oauth_provider', 'telegram')->where('oauth_id', $telegramId)->first();
        if ($user) {
            $updates = [];
            if ($username !== null && $user->tg_username !== $username) {
                $updates['tg_username'] = $username;
            }
            if ($avatar !== null && $user->avatar !== $avatar) {
                $updates['avatar'] = $avatar;
            }
            if ($updates !== []) {
                $user->update($updates);
            }
            return $user;
        }

        // Легаси: запись с oauth_id, но без provider — присваиваем telegram.
        $legacy = User::whereNull('oauth_provider')->where('oauth_id', $telegramId)->orderBy('id')->first();
        if ($legacy) {
            $updates = ['oauth_provider' => 'telegram'];
            if ($username !== null && $legacy->tg_username !== $username) {
                $updates['tg_username'] = $username;
            }
            if ($avatar !== null && $legacy->avatar !== $avatar) {
                $updates['avatar'] = $avatar;
            }
            $legacy->update($updates);
            return $legacy;
        }

        return User::create([
            'name'           => $this->normalizeName($claims),
            'oauth_provider' => 'telegram',
            'oauth_id'       => $telegramId,
            'tg_username'    => $username,
            'avatar'         => $avatar,
        ]);
    }

    private function normalizeUsername(?string $u): ?string
    {
        $u = $u !== null ? ltrim(trim($u), '@') : '';
        return $u === '' ? null : $u;
    }

    private function normalizeAvatar(?string $url): ?string
    {
        $url = trim((string) $url);
        return ($url !== '' && str_starts_with($url, 'https://')) ? $url : null;
    }

    private function normalizeName(array $claims): string
    {
        $name = trim((string) ($claims['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }
        $username = $this->normalizeUsername($claims['username'] ?? null);
        return $username ?? 'Пользователь';
    }
}
```

**Step 4: Прогнать — убедиться что проходит**

Run: `php vendor/bin/phpunit tests/Unit/TelegramIdentityResolverTest.php`
Expected: PASS (4 теста).

**Step 5: Commit**

```bash
git add app/Services/TelegramIdentityResolver.php tests/Unit/TelegramIdentityResolverTest.php
git commit -m "feat(auth): единый TelegramIdentityResolver (OIDC + Mini App)"
```

---

## Task 2: `TelegramOidcService` — построение authorize URL + PKCE/state

**Files:**
- Create: `app/Services/TelegramOidcService.php`
- Test: `tests/Unit/TelegramOidcServiceAuthorizeTest.php`

**Step 1: Падающий тест**

```php
<?php
namespace Tests\Unit;

use App\Services\TelegramOidcService;
use Tests\TestCase;

class TelegramOidcServiceAuthorizeTest extends TestCase
{
    public function test_authorization_url_contains_required_params_and_stores_session(): void
    {
        config()->set('services.telegram.oidc.client_id', '8047450650');
        config()->set('services.telegram.oidc.redirect', 'https://palomatika.ru/auth/telegram/callback');

        $svc = app(TelegramOidcService::class);
        $url = $svc->buildAuthorizationUrl('student');

        $this->assertStringStartsWith('https://oauth.telegram.org/auth?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        $this->assertSame('8047450650', $q['client_id']);
        $this->assertSame('code', $q['response_type']);
        $this->assertSame('openid profile', $q['scope']);
        $this->assertSame('S256', $q['code_challenge_method']);
        $this->assertNotEmpty($q['state']);
        $this->assertNotEmpty($q['nonce']);
        $this->assertNotEmpty($q['code_challenge']);

        // verifier/state/nonce/origin сохранены в сессии
        $this->assertSame('student', session('tg_oidc.origin'));
        $this->assertSame($q['state'], session('tg_oidc.state'));
        $this->assertSame($q['nonce'], session('tg_oidc.nonce'));
        // challenge = base64url(sha256(verifier))
        $verifier = session('tg_oidc.verifier');
        $expected = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $this->assertSame($expected, $q['code_challenge']);
    }
}
```

**Step 2: Прогнать — FAIL** (`TelegramOidcService` not found).
Run: `php vendor/bin/phpunit tests/Unit/TelegramOidcServiceAuthorizeTest.php`

**Step 3: Реализовать `buildAuthorizationUrl` (остальные методы — Task 3)**

```php
<?php

namespace App\Services;

use Illuminate\Support\Str;

class TelegramOidcService
{
    public function buildAuthorizationUrl(string $origin): string
    {
        $cfg = config('services.telegram.oidc');

        $state    = Str::random(40);
        $nonce    = Str::random(40);
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        session([
            'tg_oidc.state'    => $state,
            'tg_oidc.nonce'    => $nonce,
            'tg_oidc.verifier' => $verifier,
            'tg_oidc.origin'   => $origin,
        ]);

        $params = http_build_query([
            'client_id'             => $cfg['client_id'],
            'redirect_uri'          => $cfg['redirect'],
            'response_type'         => 'code',
            'scope'                 => 'openid profile',
            'state'                 => $state,
            'nonce'                 => $nonce,
            'code_challenge'        => $challenge,
            'code_challenge_method' => 'S256',
        ]);

        return rtrim($cfg['authorize_url'], '?') . '?' . $params;
    }
}
```

**Step 4: Прогнать — PASS.**
Run: `php vendor/bin/phpunit tests/Unit/TelegramOidcServiceAuthorizeTest.php`

**Step 5: Commit**

```bash
git add app/Services/TelegramOidcService.php tests/Unit/TelegramOidcServiceAuthorizeTest.php
git commit -m "feat(auth): TelegramOidcService authorize URL + PKCE/state"
```

---

## Task 3: `TelegramOidcService` — обмен кода + верификация id_token

**Files:**
- Modify: `app/Services/TelegramOidcService.php`
- Test: `tests/Feature/TelegramOidcCallbackTest.php`

Telegram отдаёт `id_token` (RS256) из `/token`. Для детерминизма теста генерим **свою** RSA-пару, подменяем JWKS через `Http::fake`, и проверяем, что наш код верифицирует подпись/claims.

**Step 1: Падающий тест**

```php
<?php
namespace Tests\Feature;

use App\Services\TelegramOidcService;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramOidcCallbackTest extends TestCase
{
    private array $rsa; // [private, jwks]

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.oidc.client_id', 'BOT123');
        config()->set('services.telegram.oidc.client_secret', 'secret');
        config()->set('services.telegram.oidc.issuer', 'https://oauth.telegram.org');
        $this->rsa = $this->makeRsaKeyAndJwks('testkid');
    }

    private function makeRsaKeyAndJwks(string $kid): array
    {
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $privPem);
        $details = openssl_pkey_get_details($res);
        $n = rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '=');
        $e = rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '=');
        return [
            'priv' => $privPem,
            'jwks' => ['keys' => [['kty' => 'RSA', 'use' => 'sig', 'kid' => $kid, 'alg' => 'RS256', 'n' => $n, 'e' => $e]]],
        ];
    }

    private function makeIdToken(array $overrides = []): string
    {
        $payload = array_merge([
            'iss'   => 'https://oauth.telegram.org',
            'aud'   => 'BOT123',
            'sub'   => '424242',
            'name'  => 'Тест Тестов',
            'preferred_username' => 'testov',
            'picture' => 'https://t.me/x.jpg',
            'nonce' => 'NONCE',
            'iat'   => time(),
            'exp'   => time() + 300,
        ], $overrides);
        return JWT::encode($payload, $this->rsa['priv'], 'RS256', 'testkid');
    }

    public function test_valid_callback_resolves_claims(): void
    {
        $idToken = $this->makeIdToken();
        Http::fake([
            'oauth.telegram.org/token' => Http::response(['id_token' => $idToken], 200),
            'oauth.telegram.org/.well-known/jwks.json' => Http::response($this->rsa['jwks'], 200),
        ]);

        $claims = app(TelegramOidcService::class)->exchangeAndVerify('the-code', 'verifier-123', 'NONCE');

        $this->assertSame('424242', (string) $claims['sub']);
        $this->assertSame('Тест Тестов', $claims['name']);
        $this->assertSame('testov', $claims['preferred_username']);
    }

    public function test_rejects_wrong_nonce(): void
    {
        Http::fake([
            'oauth.telegram.org/token' => Http::response(['id_token' => $this->makeIdToken(['nonce' => 'OTHER'])], 200),
            'oauth.telegram.org/.well-known/jwks.json' => Http::response($this->rsa['jwks'], 200),
        ]);
        $this->expectException(\App\Services\TelegramOidcException::class);
        app(TelegramOidcService::class)->exchangeAndVerify('c', 'v', 'NONCE');
    }

    public function test_rejects_wrong_audience(): void
    {
        Http::fake([
            'oauth.telegram.org/token' => Http::response(['id_token' => $this->makeIdToken(['aud' => 'OTHERBOT'])], 200),
            'oauth.telegram.org/.well-known/jwks.json' => Http::response($this->rsa['jwks'], 200),
        ]);
        $this->expectException(\App\Services\TelegramOidcException::class);
        app(TelegramOidcService::class)->exchangeAndVerify('c', 'v', 'NONCE');
    }
}
```

**Step 2: Прогнать — FAIL** (`exchangeAndVerify` / `TelegramOidcException` отсутствуют).
Run: `php vendor/bin/phpunit tests/Feature/TelegramOidcCallbackTest.php`

**Step 3: Реализовать обмен + верификацию**

Создать `app/Services/TelegramOidcException.php`:

```php
<?php
namespace App\Services;
class TelegramOidcException extends \RuntimeException {}
```

Добавить в `TelegramOidcService` (импорты `use Firebase\JWT\JWT; use Firebase\JWT\JWK; use Firebase\JWT\Key; use Illuminate\Support\Facades\Http; use Illuminate\Support\Facades\Cache;`):

```php
/**
 * Обмен authorization code на id_token и его полная верификация.
 * @return array<string,mixed> проверенные claims
 * @throws TelegramOidcException
 */
public function exchangeAndVerify(string $code, string $verifier, string $expectedNonce): array
{
    $cfg = config('services.telegram.oidc');

    $resp = Http::asForm()
        ->withBasicAuth($cfg['client_id'], $cfg['client_secret'])
        ->post($cfg['token_url'], [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $cfg['redirect'],
            'code_verifier' => $verifier,
        ]);

    if (!$resp->ok() || !$resp->json('id_token')) {
        throw new TelegramOidcException('Token endpoint error: ' . $resp->status());
    }
    $idToken = (string) $resp->json('id_token');

    $keys = $this->jwks();
    try {
        $decoded = (array) JWT::decode($idToken, $keys);
    } catch (\Throwable $e) {
        throw new TelegramOidcException('id_token signature invalid: ' . $e->getMessage());
    }

    if (($decoded['iss'] ?? null) !== $cfg['issuer']) {
        throw new TelegramOidcException('Bad issuer');
    }
    $aud = $decoded['aud'] ?? null;
    $audOk = is_array($aud) ? in_array((string) $cfg['client_id'], array_map('strval', $aud), true)
                            : (string) $aud === (string) $cfg['client_id'];
    if (!$audOk) {
        throw new TelegramOidcException('Bad audience');
    }
    if (($decoded['nonce'] ?? null) !== $expectedNonce) {
        throw new TelegramOidcException('Nonce mismatch');
    }
    // exp проверяется внутри JWT::decode (с leeway). Возвращаем нормализованные claims.
    return [
        'sub'                => (string) ($decoded['sub'] ?? ''),
        'name'               => $decoded['name'] ?? null,
        'preferred_username' => $decoded['preferred_username'] ?? null,
        'picture'            => $decoded['picture'] ?? null,
        'phone_number'       => $decoded['phone_number'] ?? null,
    ];
}

/**
 * @return array<string, Key> kid => Key (кэш 1ч)
 */
private function jwks(): array
{
    $cfg = config('services.telegram.oidc');
    $raw = Cache::remember('tg_oidc_jwks', 3600, function () use ($cfg) {
        $r = Http::get($cfg['jwks_url']);
        if (!$r->ok()) {
            throw new TelegramOidcException('JWKS fetch failed');
        }
        return $r->json();
    });
    return JWK::parseKeySet($raw);
}
```

> Примечание: `JWT::$leeway` можно выставить (например `JWT::$leeway = 60;`) если будут расхождения часов. По умолчанию 0.

**Step 4: Прогнать — PASS** (3 теста).
Run: `php vendor/bin/phpunit tests/Feature/TelegramOidcCallbackTest.php`

**Step 5: Commit**

```bash
git add app/Services/TelegramOidcService.php app/Services/TelegramOidcException.php tests/Feature/TelegramOidcCallbackTest.php
git commit -m "feat(auth): OIDC token exchange + id_token JWKS-верификация"
```

---

## Task 4: Контроллер OIDC + роуты (главный домен) + интеграция Mini App с резолвером

**Files:**
- Create: `app/Http/Controllers/Auth/TelegramOidcController.php`
- Modify: `routes/web.php` (добавить redirect/callback, удалить старый telegram callback — удаление в Task 6)
- Modify: `app/Services/TelegramMiniAppAuthService.php` (делегировать в `TelegramIdentityResolver`)
- Test: `tests/Feature/TelegramOidcLoginFlowTest.php`

**Step 1: Падающий тест (полный флоу redirect→callback→логин)**

```php
<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\TelegramOidcService;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class TelegramOidcLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_route_sends_to_telegram(): void
    {
        $resp = $this->get('/auth/telegram/redirect?origin=student');
        $resp->assertredirect();
        $this->assertStringContainsString('oauth.telegram.org/auth', $resp->headers->get('Location'));
        $this->assertSame('student', session('tg_oidc.origin'));
    }

    public function test_callback_logs_in_and_resolves_user(): void
    {
        // подготовим сессию как после redirect
        session(['tg_oidc.state' => 'ST', 'tg_oidc.nonce' => 'NO', 'tg_oidc.verifier' => 'VE', 'tg_oidc.origin' => 'student']);

        // подменяем сервис: возвращаем проверенные claims
        $mock = Mockery::mock(TelegramOidcService::class);
        $mock->shouldReceive('exchangeAndVerify')->once()->with('CODE', 'VE', 'NO')
            ->andReturn(['sub' => '321', 'name' => 'Имя', 'preferred_username' => 'nick', 'picture' => null]);
        $this->app->instance(TelegramOidcService::class, $mock);

        $resp = $this->get('/auth/telegram/callback?code=CODE&state=ST');

        $this->assertAuthenticated();
        $user = User::where('oauth_provider', 'telegram')->where('oauth_id', '321')->firstOrFail();
        $this->assertSame($user->id, auth()->id());
    }

    public function test_callback_rejects_state_mismatch(): void
    {
        session(['tg_oidc.state' => 'GOOD', 'tg_oidc.verifier' => 'V', 'tg_oidc.nonce' => 'N']);
        $resp = $this->get('/auth/telegram/callback?code=C&state=BAD');
        $this->assertGuest();
        $resp->assertredirect();
    }
}
```

**Step 2: Прогнать — FAIL** (роутов нет).
Run: `php vendor/bin/phpunit tests/Feature/TelegramOidcLoginFlowTest.php`

**Step 3: Контроллер**

`app/Http/Controllers/Auth/TelegramOidcController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\TelegramIdentityResolver;
use App\Services\TelegramOidcException;
use App\Services\TelegramOidcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TelegramOidcController extends Controller
{
    public function __construct(
        private readonly TelegramOidcService $oidc,
        private readonly TelegramIdentityResolver $resolver,
        private readonly AuditLogger $audit,
    ) {}

    /** GET /auth/telegram/redirect?origin=student|teacher|parent|main */
    public function redirect(Request $request)
    {
        $origin = in_array($request->query('origin'), ['student', 'teacher', 'parent', 'main'], true)
            ? $request->query('origin') : 'main';
        return redirect()->away($this->oidc->buildAuthorizationUrl($origin));
    }

    /** GET /auth/telegram/callback */
    public function callback(Request $request)
    {
        if ($request->filled('error') || !$request->filled('code') || !$request->filled('state')
            || $request->query('state') !== session('tg_oidc.state')) {
            return $this->fail($request, 'state_or_error');
        }

        try {
            $claims = $this->oidc->exchangeAndVerify(
                (string) $request->query('code'),
                (string) session('tg_oidc.verifier'),
                (string) session('tg_oidc.nonce'),
            );
        } catch (TelegramOidcException $e) {
            return $this->fail($request, $e->getMessage());
        }

        $user = $this->resolver->resolve([
            'id'       => $claims['sub'],
            'username' => $claims['preferred_username'] ?? null,
            'name'     => $claims['name'] ?? null,
            'photo'    => $claims['picture'] ?? null,
        ]);

        $origin = session('tg_oidc.origin', 'main');
        $request->session()->forget(['tg_oidc.state', 'tg_oidc.nonce', 'tg_oidc.verifier', 'tg_oidc.origin']);

        Auth::login($user, true);
        $request->session()->regenerate();

        $this->audit->log([
            'event_type' => 'telegram_oidc_login_success', 'category' => 'auth', 'severity' => 'info',
            'actor_user_id' => $user->id, 'actor_role' => $user->role,
            'subject_type' => 'provider', 'subject_id' => 'telegram',
            'ip' => $request->ip(), 'user_agent' => $request->userAgent(),
        ]);

        return redirect()->to($this->originUrl($origin));
    }

    private function fail(Request $request, string $reason)
    {
        $this->audit->log([
            'event_type' => 'telegram_oidc_login_failed', 'category' => 'auth', 'severity' => 'warning',
            'subject_type' => 'provider', 'subject_id' => 'telegram',
            'ip' => $request->ip(), 'user_agent' => $request->userAgent(),
            'payload_json' => ['reason' => $reason],
        ]);
        $origin = session('tg_oidc.origin', 'main');
        return redirect()->to($this->originLoginUrl($origin))->with('error', 'Ошибка входа через Telegram.');
    }

    private function originUrl(string $origin): string
    {
        $base = config('app.base_domain');
        return match ($origin) {
            'student' => 'https://student.' . $base . '/',
            'teacher' => 'https://teacher.' . $base . '/dashboard',
            'parent'  => 'https://parent.' . $base . '/dashboard',
            default   => '/dashboard',
        };
    }

    private function originLoginUrl(string $origin): string
    {
        $base = config('app.base_domain');
        return match ($origin) {
            'student' => 'https://student.' . $base . '/login',
            'teacher' => 'https://teacher.' . $base . '/login',
            'parent'  => 'https://parent.' . $base . '/',
            default   => '/login',
        };
    }
}
```

**Step 4: Роуты** — в `routes/web.php` рядом с текущими telegram-роутами:

```php
use App\Http\Controllers\Auth\TelegramOidcController;

Route::get('/auth/telegram/redirect', [TelegramOidcController::class, 'redirect'])->name('auth.telegram.oidc.redirect');
Route::get('/auth/telegram/callback', [TelegramOidcController::class, 'callback'])->name('auth.telegram.oidc.callback');
```

> Старый `GET /auth/telegram/callback` (SocialAuthController::telegramCallback) удаляется в Task 6; в этом коммите новый callback регистрируем под тем же путём — убедиться, что старого определения уже нет (если конфликт — временно держать новый под именем, удалить старый в Task 6 и переключить). Рекомендуется выполнять Task 6 сразу после Task 4.

**Step 5: Mini App через единый резолвер** — в `TelegramMiniAppAuthService::findOrCreateUser` заменить тело на делегирование:

```php
public function findOrCreateUser(array $telegramUser): User
{
    return app(\App\Services\TelegramIdentityResolver::class)->resolve([
        'id'       => $telegramUser['id'] ?? '',
        'username' => $telegramUser['username'] ?? null,
        'name'     => $this->normalizeDisplayName($telegramUser),
        'photo'    => $telegramUser['photo_url'] ?? null,
    ]);
}
```

(существующие private-хелперы `normalizeDisplayName` оставить; `normalizeTelegramUsername`/`normalizeAvatarUrl` оставить если используются в других местах, иначе удалить.)

**Step 6: Прогнать — PASS.** Также прогнать существующий Mini App тест на регрессию:
Run: `php vendor/bin/phpunit tests/Feature/TelegramOidcLoginFlowTest.php tests/Feature/MiniAppAuthBridgeTest.php tests/Feature/TelegramWebAppAuthTest.php`
Expected: PASS (учесть предсуществующие падения окружения — сверять с базой origin/main при сомнении).

**Step 7: Commit**

```bash
git add app/Http/Controllers/Auth/TelegramOidcController.php routes/web.php app/Services/TelegramMiniAppAuthService.php tests/Feature/TelegramOidcLoginFlowTest.php
git commit -m "feat(auth): OIDC controller + роуты + Mini App через единый резолвер"
```

---

## Task 5: Кнопка «Войти через Telegram» на login-страницах

**Files:**
- Modify: `resources/views/pwa/shared/login.blade.php` (student/teacher PWA)
- Modify: `resources/views/auth/login.blade.php` (главный домен)
- Test: `tests/Feature/Pwa/PwaLoginTelegramButtonTest.php`

**Step 1: Падающий тест**

```php
<?php
namespace Tests\Feature\Pwa;

use Tests\TestCase;

class PwaLoginTelegramButtonTest extends TestCase
{
    public function test_student_login_shows_telegram_button(): void
    {
        $resp = $this->get('https://student.' . config('app.base_domain') . '/login');
        $resp->assertOk();
        $resp->assertSee('/auth/telegram/redirect?origin=student', false);
    }
}
```

**Step 2: FAIL.** Run: `php vendor/bin/phpunit tests/Feature/Pwa/PwaLoginTelegramButtonTest.php`

**Step 3: Добавить кнопку** в `resources/views/pwa/shared/login.blade.php` (основной, заметной; `$context` = student|teacher уже есть):

```blade
<a href="https://{{ config('app.base_domain') }}/auth/telegram/redirect?origin={{ $context }}"
   class="btn-telegram-login">
   <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248-2.04 9.613c-.154.683-.554.85-1.123.528l-3.1-2.285-1.496 1.44c-.165.165-.305.305-.625.305l.223-3.168 5.77-5.213c.25-.222-.055-.346-.39-.124L7.19 14.447l-3.04-.952c-.662-.207-.674-.662.138-.979l11.87-4.576c.552-.2 1.035.134.404.308z"/></svg>
   Войти через Telegram
</a>
```

(стиль `.btn-telegram-login` — синяя кнопка `#229ED9`, по аналогии с существующими login-кнопками в этом файле.)

В `resources/views/auth/login.blade.php` добавить аналогичную кнопку с `origin=main`.

**Step 4: PASS.** Run: `php vendor/bin/phpunit tests/Feature/Pwa/PwaLoginTelegramButtonTest.php`

**Step 5: Commit**

```bash
git add resources/views/pwa/shared/login.blade.php resources/views/auth/login.blade.php tests/Feature/Pwa/PwaLoginTelegramButtonTest.php
git commit -m "feat(auth): кнопка входа через Telegram на login-страницах"
```

---

## Task 6: Удалить старую Telegram-авторизацию (widget + бот-диплинк логин)

**Files:**
- Modify: `app/Http/Controllers/Auth/SocialAuthController.php` (удалить `telegramCallback`, `verifyTelegramAuth`, `findOrCreateTelegramUser`, `findOrCreateTelegramUser` private; в `redirect()` убрать telegram-ветку)
- Modify: `routes/web.php` (удалить старый `/auth/telegram/callback` → telegramCallback; `/auth/telegram/login/{token}`)
- Modify: `routes/api.php` (удалить `/telegram/generate-token`, `/telegram/check-token/{token}`)
- Modify: `app/Http/Controllers/Auth/TelegramBotAuthController.php` (удалить `generateToken`, `checkToken`, `login`; **оставить** `webAppLogin`, `webhook`, `sessionCheck`, `diag`)
- Test: `tests/Feature/LegacyTelegramAuthRemovedTest.php`

**Step 1: Падающий тест**

```php
<?php
namespace Tests\Feature;

use Tests\TestCase;

class LegacyTelegramAuthRemovedTest extends TestCase
{
    public function test_bot_deeplink_login_route_gone(): void
    {
        $this->get('/auth/telegram/login/sometoken123456')->assertNotFound();
    }

    public function test_generate_token_api_gone(): void
    {
        $this->postJson('/api/telegram/generate-token')->assertNotFound();
    }

    public function test_webapp_login_still_exists(): void
    {
        // маршрут есть (422/400 без валидного initData — НЕ 404)
        $resp = $this->postJson('/api/auth/telegram/webapp-login', []);
        $this->assertNotSame(404, $resp->getStatusCode());
    }
}
```

**Step 2: Прогнать** — `test_bot_deeplink_login_route_gone` и `generate_token` пока FAIL (роуты ещё есть), `webapp_login` PASS.
Run: `php vendor/bin/phpunit tests/Feature/LegacyTelegramAuthRemovedTest.php`

**Step 3: Удаления**

- В `routes/web.php`: удалить строки
  `Route::get('/auth/telegram/login/{token}', [TelegramBotAuthController::class, 'login'])...`
  и старый `Route::get('/auth/telegram/callback', [SocialAuthController::class, 'telegramCallback'])` (если есть).
- В `routes/api.php`: удалить
  `Route::post('/telegram/generate-token', ...)` и `Route::get('/telegram/check-token/{token}', ...)`.
  (Оставить `session-check`, `diag`, `webapp-login`, `webhook`.)
- В `SocialAuthController`: удалить методы `telegramCallback`, `verifyTelegramAuth`, `findOrCreateTelegramUser`; из `redirect()` убрать ветку `if ($provider === 'telegram')`.
- В `TelegramBotAuthController`: удалить методы `generateToken`, `checkToken`, `login` и неиспользуемые после этого private-хелперы (проверить ссылки grep'ом перед удалением). **Не трогать** `webAppLogin`, `webhook`, `sessionCheck`, `diag` и их зависимости.

**Step 4: Прогнать — PASS** (все 3).
Run: `php vendor/bin/phpunit tests/Feature/LegacyTelegramAuthRemovedTest.php`
Также: `php vendor/bin/phpunit tests/Feature/TelegramWebAppAuthTest.php` — Mini App не сломан.

**Step 5: Commit**

```bash
git add -A
git commit -m "refactor(auth): удалить старый Telegram widget + бот-диплинк логин"
```

---

## Task 7: Интеграционный тест на дубли (Mini App + web OIDC = один аккаунт)

**Files:**
- Test: `tests/Feature/TelegramUnifiedIdentityTest.php`

**Step 1: Тест**

```php
<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\TelegramIdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramUnifiedIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_miniapp_then_web_oidc_resolve_to_same_account(): void
    {
        $resolver = app(TelegramIdentityResolver::class);

        // Mini App (initData) — приходит как int id
        $fromMiniApp = $resolver->resolve(['id' => 50050, 'username' => 'student1', 'name' => 'Ученик']);

        // Web OIDC — sub приходит как строка
        $fromWeb = $resolver->resolve(['id' => '50050', 'username' => 'student1', 'name' => 'Ученик']);

        $this->assertSame($fromMiniApp->id, $fromWeb->id);
        $this->assertSame(1, User::where('oauth_id', '50050')->count());
    }
}
```

**Step 2: Прогнать — PASS** (резолвер уже готов; тест фиксирует контракт против регрессий).
Run: `php vendor/bin/phpunit tests/Feature/TelegramUnifiedIdentityTest.php`

**Step 3: Commit**

```bash
git add tests/Feature/TelegramUnifiedIdentityTest.php
git commit -m "test(auth): Mini App + web OIDC ведут в один аккаунт"
```

---

## Финал: полный прогон + ручная проверка

**Step 1:** Прогнать весь auth-блок:
Run: `php vendor/bin/phpunit tests/Feature/TelegramOidcCallbackTest.php tests/Feature/TelegramOidcLoginFlowTest.php tests/Unit/TelegramIdentityResolverTest.php tests/Unit/TelegramOidcServiceAuthorizeTest.php tests/Feature/LegacyTelegramAuthRemovedTest.php tests/Feature/TelegramUnifiedIdentityTest.php tests/Feature/TelegramWebAppAuthTest.php tests/Feature/MiniAppAuthBridgeTest.php`

**Step 2:** Ручные шаги перед прод-пушем (нельзя автоматизировать):
- В **BotFather → Bot Settings → Web Login** зарегистрировать Redirect URI `https://palomatika.ru/auth/telegram/callback` и Trusted Origins для поддоменов при необходимости.
- Положить `TELEGRAM_OIDC_CLIENT_SECRET` в прод `.env` (через deploy-механизм, не в репозиторий).
- Сверить реальный формат `aud` в id_token (bot id) — при необходимости поправить сравнение в `exchangeAndVerify`.

**Step 3:** Пуш ветки `claude/telegram-oidc-auth` → авто-merge в main → прод (**только после подтверждения пользователя** и проставленного секрета).

---

## Чеклист задач
- [ ] Task 0 — конфиг/env/зависимость
- [ ] Task 1 — TelegramIdentityResolver
- [ ] Task 2 — authorize URL + PKCE
- [ ] Task 3 — token exchange + JWKS-верификация
- [ ] Task 4 — контроллер + роуты + Mini App интеграция
- [ ] Task 5 — кнопка на login-страницах
- [ ] Task 6 — удаление старого widget + бот-диплинк логина
- [ ] Task 7 — интеграционный тест на дубли
- [ ] Финал — полный прогон + ручные шаги (BotFather/secret)
