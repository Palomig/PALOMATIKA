# Palomatika PWA Migration Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the Telegram mini app (`/tg/*`) with two installable PWAs at `student.palomatika.ru` and `teacher.palomatika.ru`, authenticated via VK / Yandex / Google OAuth.

**Architecture:** One Laravel codebase serves all subdomains via subdomain route groups. New `App\Http\Controllers\Pwa\` controllers and `resources/views/pwa/` views replace Telegram-specific code. All existing task services (`TaskDataService`, `OgeAttemptService`, etc.) are reused unchanged. The `/tg/*` routes stay active throughout migration.

**Tech Stack:** Laravel 10, PHP 8.2, Apache 2.4, Tailwind CSS CDN, Alpine.js CDN, Laravel Socialite (VK already installed via `socialiteproviders/vkontakte`, add Yandex + Google).

---

## Sacred — NEVER modify these

| Path | Why |
|------|-----|
| `storage/app/tasks/` | OGE/EGE JSON task data |
| `app/Services/TaskDataService.php` | Central task data access |
| `app/Services/GeometrySvgRenderer.php` | SVG baking |
| `app/Services/OgeVariantBuilderService.php` | Deterministic variant generation |
| `app/Services/OgeAttemptService.php` | Attempt lifecycle + scoring |
| `app/Services/TaskAnswerResolver.php` | Answer normalization |
| `routes/web.php` lines for `/topics/*` | Task database pages |
| `routes/web.php` lines for `/materials/*` | Learning materials pages |
| `app/Http/Controllers/JarvisMaterialPageController.php` | Materials controller |
| `app/Http/Controllers/TopicController.php` | Topics controller |
| `database/migrations/` | All migrations |

---

## Phase 1 — Subdomain Infrastructure

### Task 1: Apache VirtualHosts for subdomains (dev environment)

**Files:**
- Create: `/etc/apache2/sites-available/student-palomatika.conf`
- Create: `/etc/apache2/sites-available/teacher-palomatika.conf`

**Step 1: Create student VirtualHost**

```apache
# /etc/apache2/sites-available/student-palomatika.conf
<VirtualHost *:80>
    ServerName student.palomatika.ru
    DocumentRoot /home/dev/palomatika/public

    <Directory /home/dev/palomatika/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/student-palomatika-error.log
    CustomLog ${APACHE_LOG_DIR}/student-palomatika-access.log combined
</VirtualHost>
```

**Step 2: Create teacher VirtualHost**

```apache
# /etc/apache2/sites-available/teacher-palomatika.conf
<VirtualHost *:80>
    ServerName teacher.palomatika.ru
    DocumentRoot /home/dev/palomatika/public

    <Directory /home/dev/palomatika/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/teacher-palomatika-error.log
    CustomLog ${APACHE_LOG_DIR}/teacher-palomatika-access.log combined
</VirtualHost>
```

**Step 3: Enable sites and reload**

```bash
sudo a2ensite student-palomatika.conf teacher-palomatika.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

**Step 4: Add to /etc/hosts (dev only)**

```bash
echo "127.0.0.1 student.palomatika.ru" | sudo tee -a /etc/hosts
echo "127.0.0.1 teacher.palomatika.ru" | sudo tee -a /etc/hosts
```

> **Production note (Timeweb):** Add subdomains via Timeweb control panel → "Поддомены". Point each to the same document root as `palomatika.ru`. SSL is handled by Timeweb's Let's Encrypt integration. No manual Apache config needed on production.

**Step 5: Commit**

```bash
git add -A
git commit -m "infra: add Apache VirtualHost configs for student/teacher subdomains"
```

---

### Task 2: Session cookie spans all subdomains

**Files:**
- Modify: `.env` and `.env.example`

**Step 1: Set session domain**

In `.env`:
```
SESSION_DOMAIN=.palomatika.ru
```

In `.env.example`:
```
SESSION_DOMAIN=.palomatika.ru
```

This makes the session cookie available on `palomatika.ru`, `student.palomatika.ru`, and `teacher.palomatika.ru`.

**Step 2: Clear config cache**

```bash
cd /home/dev/palomatika && php artisan config:clear
```

**Step 3: Verify (manual)**

Log in on `palomatika.ru`, then navigate to `student.palomatika.ru` — the `laravel_session` cookie should be present.

**Step 4: Commit**

```bash
git commit -m "config: set SESSION_DOMAIN to .palomatika.ru for subdomain auth sharing"
```

---

### Task 3: Laravel subdomain route groups

**Files:**
- Create: `routes/pwa.php`
- Modify: `app/Providers/RouteServiceProvider.php`

**Step 1: Create routes/pwa.php (scaffold — controllers filled in Phase 4+)**

```php
<?php

use App\Http\Controllers\Pwa\AuthController;
use App\Http\Controllers\Pwa\StudentController;
use App\Http\Controllers\Pwa\TeacherController;
use App\Http\Controllers\Pwa\ManifestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| student.palomatika.ru
|--------------------------------------------------------------------------
*/
Route::domain('student.' . config('app.base_domain'))->group(function () {

    // PWA assets
    Route::get('/manifest.json', [ManifestController::class, 'student'])->name('pwa.student.manifest');
    Route::get('/sw.js', [ManifestController::class, 'serviceWorker'])->name('pwa.sw');

    // Auth
    Route::get('/login', [AuthController::class, 'showLogin'])->name('pwa.student.login');
    Route::get('/auth/{provider}', [AuthController::class, 'redirect'])->name('pwa.student.auth.redirect');
    Route::get('/auth/{provider}/callback', [AuthController::class, 'callback'])->name('pwa.student.auth.callback');
    Route::post('/logout', [AuthController::class, 'logout'])->name('pwa.student.logout');

    // Onboarding (no auth required — new users land here)
    Route::get('/onboarding', [StudentController::class, 'onboarding'])->name('pwa.student.onboarding');
    Route::post('/onboarding', [StudentController::class, 'saveOnboarding'])->name('pwa.student.onboarding.save');

    // Migration from Telegram
    Route::get('/migrate', [AuthController::class, 'migrateFromTelegram'])->name('pwa.student.migrate');

    // Protected student routes
    Route::middleware(['auth', 'pwa.onboarding'])->group(function () {
        Route::get('/', [StudentController::class, 'dashboard'])->name('pwa.student.dashboard');
        Route::get('/mini', [StudentController::class, 'mini'])->name('pwa.student.mini');
        Route::get('/part2', [StudentController::class, 'part2'])->name('pwa.student.part2');
        Route::get('/tasks-part1', [StudentController::class, 'tasksPart1'])->name('pwa.student.tasks-part1');
        Route::post('/mini/start', [StudentController::class, 'startMini'])->name('pwa.student.mini.start');
        Route::post('/full/start', [StudentController::class, 'startFull'])->name('pwa.student.full.start');
        Route::get('/test/{attemptId}', [StudentController::class, 'test'])->name('pwa.student.test');
        Route::get('/results/{attemptId}', [StudentController::class, 'results'])->name('pwa.student.results');
        Route::get('/history', [StudentController::class, 'history'])->name('pwa.student.history');
        Route::get('/history/{attemptId}', [StudentController::class, 'historyDetail'])->name('pwa.student.history.detail');
        Route::get('/profile', [StudentController::class, 'profile'])->name('pwa.student.profile');
        Route::get('/homework', [StudentController::class, 'studentHomework'])->name('pwa.student.homework');
        Route::get('/tutor', [StudentController::class, 'tutor'])->name('pwa.student.tutor');
    });
});

/*
|--------------------------------------------------------------------------
| teacher.palomatika.ru
|--------------------------------------------------------------------------
*/
Route::domain('teacher.' . config('app.base_domain'))->group(function () {

    // PWA assets
    Route::get('/manifest.json', [ManifestController::class, 'teacher'])->name('pwa.teacher.manifest');
    Route::get('/sw.js', [ManifestController::class, 'serviceWorker'])->name('pwa.sw.teacher');

    // Auth
    Route::get('/login', [AuthController::class, 'showTeacherLogin'])->name('pwa.teacher.login');
    Route::get('/auth/{provider}', [AuthController::class, 'redirectTeacher'])->name('pwa.teacher.auth.redirect');
    Route::get('/auth/{provider}/callback', [AuthController::class, 'callbackTeacher'])->name('pwa.teacher.auth.callback');
    Route::post('/logout', [AuthController::class, 'logout'])->name('pwa.teacher.logout');

    // Protected teacher routes
    Route::middleware(['auth', 'role:teacher,admin'])->group(function () {
        Route::get('/', fn() => redirect()->route('pwa.teacher.dashboard'));
        Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('pwa.teacher.dashboard');
        Route::get('/students', [TeacherController::class, 'students'])->name('pwa.teacher.students');
        Route::get('/students/{studentId}', [TeacherController::class, 'studentProfile'])->name('pwa.teacher.student.profile');
        Route::get('/students/{studentId}/attempt/{attemptId}', [TeacherController::class, 'studentAttemptDetail'])->name('pwa.teacher.student.attempt');
        Route::post('/students/{studentId}/ownership', [TeacherController::class, 'toggleOwnership'])->name('pwa.teacher.student.ownership');
        Route::patch('/students/{studentId}/alias', [TeacherController::class, 'updateAlias'])->name('pwa.teacher.student.alias');
        Route::patch('/students/{studentId}/link', [TeacherController::class, 'updateStudentLink'])->name('pwa.teacher.student.link');
        Route::get('/lessons', [TeacherController::class, 'lessons'])->name('pwa.teacher.lessons');
        Route::get('/homework', [TeacherController::class, 'homework'])->name('pwa.teacher.homework');
        Route::post('/homework/assign', [TeacherController::class, 'assignHomework'])->name('pwa.teacher.homework.assign');
        Route::get('/variants', [TeacherController::class, 'variants'])->name('pwa.teacher.variants');
        Route::get('/referrals', [TeacherController::class, 'referrals'])->name('pwa.teacher.referrals');
    });
});
```

**Step 2: Add `base_domain` to config/app.php**

```php
// In config/app.php, add:
'base_domain' => env('APP_BASE_DOMAIN', 'palomatika.ru'),
```

**Step 3: Add to .env and .env.example**

```
APP_BASE_DOMAIN=palomatika.ru
```

**Step 4: Register routes/pwa.php in RouteServiceProvider**

In `app/Providers/RouteServiceProvider.php`, inside the `boot()` method, add after the existing `web` route registration:

```php
Route::middleware('web')
    ->withoutMiddleware([\App\Http\Middleware\CaptureTelegramStartParam::class])
    ->group(base_path('routes/pwa.php'));
```

**Step 5: Register `pwa.onboarding` middleware alias in Kernel.php**

In `app/Http/Kernel.php`, `$middlewareAliases` array, add:

```php
'pwa.onboarding' => \App\Http\Middleware\EnsurePwaOnboardingComplete::class,
```

**Step 6: Write test**

Create `tests/Feature/Pwa/PwaRoutesTest.php`:

```php
<?php

namespace Tests\Feature\Pwa;

use Tests\TestCase;

class PwaRoutesTest extends TestCase
{
    public function test_student_login_page_is_accessible(): void
    {
        $response = $this->get('http://student.palomatika.ru/login');
        $response->assertStatus(200);
    }

    public function test_teacher_login_page_is_accessible(): void
    {
        $response = $this->get('http://teacher.palomatika.ru/login');
        $response->assertStatus(200);
    }

    public function test_student_manifest_is_accessible(): void
    {
        $response = $this->get('http://student.palomatika.ru/manifest.json');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_student_dashboard_requires_auth(): void
    {
        $response = $this->get('http://student.palomatika.ru/');
        $response->assertRedirect();
    }
}
```

**Step 7: Run test (expect fail — controllers don't exist yet)**

```bash
cd /home/dev/palomatika && php artisan test tests/Feature/Pwa/PwaRoutesTest.php
```
Expected: FAIL (class not found errors)

**Step 8: Commit scaffold**

```bash
git add routes/pwa.php config/app.php app/Providers/RouteServiceProvider.php app/Http/Kernel.php tests/Feature/Pwa/
git commit -m "feat: scaffold PWA subdomain route groups for student and teacher"
```

---

## Phase 2 — OAuth Authentication

### Task 4: Install Yandex and Google Socialite providers

**Step 1: Install packages**

```bash
cd /home/dev/palomatika
composer require socialiteproviders/yandex
```

Google is built into `laravel/socialite` (already installed) — no extra package needed.

**Step 2: Register Yandex event listener in EventServiceProvider**

In `app/Providers/EventServiceProvider.php`, add to `$listen`:

```php
\SocialiteProviders\Manager\SocialiteWasCalled::class => [
    \SocialiteProviders\Yandex\YandexExtendSocialite::class . '@handle',
],
```

**Step 3: Add to config/services.php**

```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI'),
],

'yandex' => [
    'client_id'     => env('YANDEX_CLIENT_ID'),
    'client_secret' => env('YANDEX_CLIENT_SECRET'),
    'redirect'      => env('YANDEX_REDIRECT_URI'),
],
```

**Step 4: Add to .env.example**

```
VK_CLIENT_ID=
VK_CLIENT_SECRET=
VK_REDIRECT_URI="${APP_URL}/auth/vk/callback"

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://student.palomatika.ru/auth/google/callback

YANDEX_CLIENT_ID=
YANDEX_CLIENT_SECRET=
YANDEX_REDIRECT_URI=https://student.palomatika.ru/auth/yandex/callback
```

> **Note on redirect URIs:** Each OAuth provider (VK App, Google Console, Yandex OAuth) must have BOTH `student.palomatika.ru` and `teacher.palomatika.ru` callback URLs registered as allowed redirect URIs. The `redirect` config value will be overridden dynamically in the controller.

**Step 5: Clear config**

```bash
php artisan config:clear
```

**Step 6: Commit**

```bash
git add composer.json composer.lock config/services.php app/Providers/EventServiceProvider.php .env.example
git commit -m "feat: add Yandex and Google Socialite providers"
```

---

### Task 5: EnsurePwaOnboardingComplete middleware

**Files:**
- Create: `app/Http/Middleware/EnsurePwaOnboardingComplete.php`

**Step 1: Create middleware**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePwaOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->onboarding_completed_at) {
            $hasProfile = !empty($user->name)
                && !empty($user->grade_num)
                && !empty($user->grade_letter)
                && !empty($user->school_number)
                && !empty($user->city);

            if ($hasProfile) {
                $user->forceFill(['onboarding_completed_at' => now()])->save();
            } else {
                // Redirect to onboarding on same subdomain
                $host = $request->getHost();
                return redirect('http://' . $host . '/onboarding');
            }
        }

        return $next($request);
    }
}
```

**Step 2: Update Authenticate middleware to handle PWA subdomains**

In `app/Http/Middleware/Authenticate.php`, add PWA redirect logic:

```php
protected function redirectTo(Request $request): ?string
{
    if ($request->expectsJson()) {
        return null;
    }

    if ($request->isMethod('GET') && $request->hasSession()) {
        $request->session()->put('url.intended', $request->fullUrl());
    }

    // Mini App routes
    if ($request->is('tg/*') || $request->is('tg')) {
        return url('/tg');
    }

    // PWA subdomain routes — redirect to /login on same subdomain
    $host = $request->getHost();
    $baseDomain = config('app.base_domain', 'palomatika.ru');
    if (str_ends_with($host, '.' . $baseDomain)) {
        return 'http://' . $host . '/login';
    }

    return route('login');
}
```

**Step 3: Commit**

```bash
git add app/Http/Middleware/EnsurePwaOnboardingComplete.php app/Http/Middleware/Authenticate.php
git commit -m "feat: add PWA-aware onboarding and auth redirect middleware"
```

---

### Task 6: PwaAuthController

**Files:**
- Create: `app/Http/Controllers/Pwa/AuthController.php`
- Create: `resources/views/pwa/shared/login.blade.php`

**Step 1: Create controller**

```php
<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    private const ALLOWED_PROVIDERS = ['vkontakte', 'google', 'yandex'];

    /**
     * Determine which subdomain we're on (student|teacher).
     */
    private function appContext(Request $request): string
    {
        $host = $request->getHost();
        return str_starts_with($host, 'teacher.') ? 'teacher' : 'student';
    }

    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect('http://student.' . config('app.base_domain') . '/');
        }
        return view('pwa.shared.login', ['context' => 'student']);
    }

    public function showTeacherLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect('http://teacher.' . config('app.base_domain') . '/dashboard');
        }
        return view('pwa.shared.login', ['context' => 'teacher']);
    }

    /**
     * Redirect to OAuth provider (student subdomain).
     */
    public function redirect(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);
        $callbackUrl = 'https://student.' . config('app.base_domain') . '/auth/' . $provider . '/callback';
        return Socialite::driver($provider)->redirectUrl($callbackUrl)->redirect();
    }

    /**
     * Redirect to OAuth provider (teacher subdomain).
     */
    public function redirectTeacher(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);
        $callbackUrl = 'https://teacher.' . config('app.base_domain') . '/auth/' . $provider . '/callback';
        return Socialite::driver($provider)->redirectUrl($callbackUrl)->redirect();
    }

    /**
     * Handle OAuth callback (student subdomain).
     */
    public function callback(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);

        try {
            $callbackUrl = 'https://student.' . config('app.base_domain') . '/auth/' . $provider . '/callback';
            $socialUser = Socialite::driver($provider)->redirectUrl($callbackUrl)->user();
        } catch (\Throwable $e) {
            return redirect('http://student.' . config('app.base_domain') . '/login')
                ->with('error', 'Ошибка авторизации. Попробуйте ещё раз.');
        }

        $user = $this->findOrCreateUser($socialUser, $provider);
        Auth::login($user, true);
        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        if ($intended && str_contains($intended, 'student.' . config('app.base_domain'))) {
            return redirect($intended);
        }

        $base = 'http://student.' . config('app.base_domain');
        return $user->onboarding_completed_at
            ? redirect($base . '/')
            : redirect($base . '/onboarding');
    }

    /**
     * Handle OAuth callback (teacher subdomain).
     */
    public function callbackTeacher(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);

        try {
            $callbackUrl = 'https://teacher.' . config('app.base_domain') . '/auth/' . $provider . '/callback';
            $socialUser = Socialite::driver($provider)->redirectUrl($callbackUrl)->user();
        } catch (\Throwable $e) {
            return redirect('http://teacher.' . config('app.base_domain') . '/login')
                ->with('error', 'Ошибка авторизации. Попробуйте ещё раз.');
        }

        $user = $this->findOrCreateUser($socialUser, $provider);

        // For teacher app: only allow teacher/admin roles (or new users who'll be assigned)
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect('http://teacher.' . config('app.base_domain') . '/dashboard');
    }

    public function logout(Request $request)
    {
        $host = $request->getHost();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('http://' . $host . '/login');
    }

    /**
     * Restore account from Telegram migration token.
     */
    public function migrateFromTelegram(Request $request)
    {
        $token = trim((string) $request->query('token', ''));
        $payload = $token !== '' ? Cache::get('pwa_migration:' . $token) : null;

        return view('pwa.student.migrate', [
            'userId' => $payload['user_id'] ?? null,
            'token' => $token,
        ]);
    }

    /**
     * After OAuth during migration: link existing Telegram account to new OAuth provider.
     */
    public function migrationCallback(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);

        $token = $request->session()->get('migration_token', '');
        $payload = $token !== '' ? Cache::pull('pwa_migration:' . $token) : null;

        $callbackUrl = 'https://student.' . config('app.base_domain') . '/auth/' . $provider . '/callback';

        try {
            $socialUser = Socialite::driver($provider)->redirectUrl($callbackUrl)->user();
        } catch (\Throwable $e) {
            return redirect('http://student.' . config('app.base_domain') . '/login')
                ->with('error', 'Ошибка авторизации.');
        }

        if ($payload && isset($payload['user_id'])) {
            // Link new OAuth to existing Telegram account
            $user = User::find($payload['user_id']);
            if ($user) {
                $user->update([
                    'oauth_provider' => $provider,
                    'oauth_id'       => $socialUser->getId(),
                ]);
                Auth::login($user, true);
                $request->session()->regenerate();
                return redirect('http://student.' . config('app.base_domain') . '/')
                    ->with('success', 'Аккаунт успешно перенесён!');
            }
        }

        // Fallback: create new account
        $user = $this->findOrCreateUser($socialUser, $provider);
        Auth::login($user, true);
        $request->session()->regenerate();
        return redirect('http://student.' . config('app.base_domain') . '/');
    }

    private function findOrCreateUser($socialUser, string $provider): User
    {
        // Normalise provider name: Socialite uses 'vkontakte', store as 'vk'
        $storedProvider = match($provider) {
            'vkontakte' => 'vk',
            default => $provider,
        };

        $user = User::where('oauth_provider', $storedProvider)
            ->where('oauth_id', (string) $socialUser->getId())
            ->first();

        if ($user) {
            // Update avatar/name if changed
            $user->update([
                'avatar' => $socialUser->getAvatar() ?? $user->avatar,
            ]);
            return $user;
        }

        return User::create([
            'name'           => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Пользователь',
            'email'          => $socialUser->getEmail(),
            'oauth_provider' => $storedProvider,
            'oauth_id'       => (string) $socialUser->getId(),
            'avatar'         => $socialUser->getAvatar(),
            'role'           => 'student',
        ]);
    }
}
```

**Step 2: Write auth tests**

Create `tests/Feature/Pwa/PwaAuthTest.php`:

```php
<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PwaAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_login_page_shows_oauth_buttons(): void
    {
        $response = $this->get('http://student.palomatika.ru/login');
        $response->assertStatus(200);
        $response->assertSee('vkontakte');
    }

    public function test_logout_redirects_to_login(): void
    {
        $user = User::factory()->create(['oauth_provider' => 'vk', 'oauth_id' => '123']);
        $response = $this->actingAs($user)
            ->post('http://student.palomatika.ru/logout');
        $response->assertRedirect();
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $user = User::factory()->create([
            'oauth_provider' => 'vk',
            'oauth_id' => '456',
            'onboarding_completed_at' => now(),
        ]);
        $response = $this->actingAs($user)->get('http://student.palomatika.ru/login');
        $response->assertRedirect();
    }
}
```

**Step 3: Commit**

```bash
git add app/Http/Controllers/Pwa/ tests/Feature/Pwa/PwaAuthTest.php
git commit -m "feat: add PwaAuthController with VK/Yandex/Google OAuth and migration flow"
```

---

## Phase 3 — PWA Shell

### Task 7: PWA Layout (pwa.blade.php)

**Files:**
- Create: `resources/views/layouts/pwa.blade.php`

This is the `miniapp.blade.php` with Telegram SDK removed and PWA features added (theme via OS preference, no Telegram account mismatch guard).

```html
<!DOCTYPE html>
<html lang="ru" x-data="{ theme: localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') }" :data-theme="theme">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#111318" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#f7f8fc" media="(prefers-color-scheme: light)">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="@yield('app-name', 'Palomatika')">
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<title>@yield('title', 'Palomatika')</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Russo+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

{{-- Alpine.js --}}
<script defer src="/js/alpine.min.js"></script>

@stack('katex')
@stack('head')

<style>
  /* ===== identical CSS variables and base styles from miniapp.blade.php ===== */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
  [x-cloak] { display: none !important; }

  :root {
    --bg: #111318; --surface: #1c1f27; --surface2: #23272f; --border: #2a2e3a;
    --accent: #4f8ef7; --text: #eef0f6; --muted: #6b7280; --muted2: #3e4352;
    --green: #34d07e; --green-bg: rgba(52,208,126,0.1); --green-bd: rgba(52,208,126,0.22);
    --red: #f06060; --red-bg: rgba(240,96,96,0.1); --red-bd: rgba(240,96,96,0.22);
    --yellow: #f0b440; --yellow-bg: rgba(240,180,64,0.1); --yellow-bd: rgba(240,180,64,0.22);
    --purple: #a78bfa; --purple-bg: rgba(167,139,250,0.1); --purple-bd: rgba(167,139,250,0.22);
    --accent-bg: rgba(79,142,247,0.1); --accent-bd: rgba(79,142,247,0.25);
    --display: 'Russo One', sans-serif; --body: 'Nunito', sans-serif;
    --safe-bottom: env(safe-area-inset-bottom, 0px); --safe-top: env(safe-area-inset-top, 0px);
    --r: 16px;
  }
  :root[data-theme="light"] {
    --bg: #f7f8fc; --surface: #ffffff; --surface2: #f0f1f5; --border: #e4e7f0;
    --text: #12182b; --muted: #8892a4; --muted2: #c5c9d4;
    --green: #22b468; --green-bg: rgba(34,180,104,0.08); --green-bd: rgba(34,180,104,0.18);
    --red: #e04848; --red-bg: rgba(224,72,72,0.08); --red-bd: rgba(224,72,72,0.18);
    --yellow: #d49a20; --yellow-bg: rgba(212,154,32,0.08); --yellow-bd: rgba(212,154,32,0.18);
    --purple: #8b6be0; --purple-bg: rgba(139,107,224,0.08); --purple-bd: rgba(139,107,224,0.18);
    --accent-bg: rgba(79,142,247,0.08); --accent-bd: rgba(79,142,247,0.18);
  }
  html, body { height: 100%; background: var(--bg); color: var(--text); font-family: var(--body); -webkit-font-smoothing: antialiased; overflow-x: hidden; }
  .page { max-width: 480px; margin: 0 auto; padding: calc(16px + var(--safe-top)) 16px calc(32px + var(--safe-bottom)); display: flex; flex-direction: column; gap: 14px; min-height: 100vh; }
  .topbar { display: flex; align-items: center; gap: 12px; opacity: 0; animation: fadeDown 0.3s ease 0s forwards; }
  .back-btn { width: 36px; height: 36px; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--muted); cursor: pointer; flex-shrink: 0; transition: background 0.15s; text-decoration: none; }
  .back-btn:active { background: var(--surface2); }
  .topbar-title { font-family: var(--display); font-size: 16px; color: var(--text); }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 20px; }
  .pill { padding: 3px 9px; border-radius: 6px; font-size: 9px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; flex-shrink: 0; }
  .pill-purple { background: var(--purple-bg); border: 1px solid var(--purple-bd); color: var(--purple); }
  .pill-green  { background: var(--green-bg);  border: 1px solid var(--green-bd);  color: var(--green); }
  .pill-yellow { background: var(--yellow-bg); border: 1px solid var(--yellow-bd); color: var(--yellow); }
  .pill-accent { background: var(--accent-bg); border: 1px solid var(--accent-bd); color: var(--accent); }
  .pill-red    { background: var(--red-bg);    border: 1px solid var(--red-bd);    color: var(--red); }
  .stat-pills { display: flex; gap: 8px; flex-wrap: wrap; }
  .stat-pill { background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; padding: 6px 12px; font-size: 11px; font-weight: 700; color: var(--muted); display: flex; align-items: center; gap: 5px; }
  .stat-pill span { color: var(--text); }
  .sec-label { font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: var(--muted); }
  .btn { display: flex; align-items: center; justify-content: center; gap: 10px; border: none; border-radius: 14px; padding: 16px; font-family: var(--display); font-size: 15px; cursor: pointer; user-select: none; -webkit-user-select: none; transition: transform 0.1s, filter 0.15s; text-decoration: none; }
  .btn:active { transform: scale(0.97); }
  .btn-accent { background: var(--accent); color: #fff; }
  .btn-accent:hover { filter: brightness(1.1); }
  .btn-green { background: var(--green); color: #fff; }
  .btn-surface { background: var(--surface); border: 1px solid var(--border); color: var(--text); }
  .note { background: var(--surface); border: 1px solid var(--border); border-left: 3px solid var(--muted2); border-radius: var(--r); padding: 13px 16px; font-size: 12px; font-weight: 600; color: var(--muted); line-height: 1.6; }
  @keyframes fadeUp   { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
  @keyframes fadeDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
  @keyframes fadeIn   { from { opacity:0; } to { opacity:1; } }
  .anim-up   { opacity: 0; animation: fadeUp 0.3s ease forwards; }
  .anim-down { opacity: 0; animation: fadeDown 0.3s ease forwards; }
  .anim-in   { opacity: 0; animation: fadeIn 0.3s ease forwards; }
  .display { font-family: var(--display); }
  .text-muted { color: var(--muted); }
  .text-accent { color: var(--accent); }
  .flex-center { display: flex; align-items: center; justify-content: center; }

  @stack('styles')
</style>
</head>
<body>

@yield('body')

{{-- iOS PWA install prompt --}}
@include('pwa.shared.ios-install-prompt')

<script>
  window._csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  window.fetchPost = (url, data = {}) => fetch(url, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window._csrf, 'Accept': 'application/json' },
    body: JSON.stringify(data),
  });

  // Register service worker
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  }

  // Android install prompt
  let deferredPrompt;
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    document.getElementById('pwa-install-btn')?.classList.remove('hidden');
  });

  window.installPwa = async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    await deferredPrompt.userChoice;
    deferredPrompt = null;
    document.getElementById('pwa-install-btn')?.classList.add('hidden');
  };
</script>

@stack('scripts')

</body>
</html>
```

**Step 2: Commit**

```bash
git add resources/views/layouts/pwa.blade.php
git commit -m "feat: add PWA layout without Telegram SDK, with SW registration and install prompt"
```

---

### Task 8: Manifest controller + JSON

**Files:**
- Create: `app/Http/Controllers/Pwa/ManifestController.php`
- Create: `resources/views/pwa/student/manifest.json.php`
- Create: `resources/views/pwa/teacher/manifest.json.php`
- Create: `public/icons/` (placeholder — real icons to be added separately)

**Step 1: Create ManifestController**

```php
<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ManifestController extends Controller
{
    public function student()
    {
        $manifest = [
            'name'             => 'Palomatika — Ученик',
            'short_name'       => 'Palomatika',
            'description'      => 'Подготовка к ОГЭ по математике',
            'start_url'        => '/',
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#111318',
            'theme_color'      => '#111318',
            'lang'             => 'ru',
            'icons'            => [
                ['src' => '/icons/student-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icons/student-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
            'screenshots'      => [],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }

    public function teacher()
    {
        $manifest = [
            'name'             => 'Palomatika — Репетитор',
            'short_name'       => 'Palomatika Pro',
            'description'      => 'Управление учениками и уроками',
            'start_url'        => '/dashboard',
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#111318',
            'theme_color'      => '#4f8ef7',
            'lang'             => 'ru',
            'icons'            => [
                ['src' => '/icons/teacher-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icons/teacher-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }

    public function serviceWorker()
    {
        $content = file_get_contents(public_path('sw.js'));
        return response($content, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Service-Worker-Allowed', '/');
    }
}
```

**Step 2: Create placeholder icons directory**

```bash
mkdir -p /home/dev/palomatika/public/icons
# Create 1x1 placeholder PNGs (replace with real icons before launch)
php -r "
\$img = imagecreatetruecolor(192, 192);
\$blue = imagecolorallocate(\$img, 79, 142, 247);
imagefill(\$img, 0, 0, \$blue);
imagepng(\$img, 'public/icons/student-192.png');
imagepng(\$img, 'public/icons/teacher-192.png');
\$img512 = imagecreatetruecolor(512, 512);
imagefill(\$img512, 0, 0, \$blue);
imagepng(\$img512, 'public/icons/student-512.png');
imagepng(\$img512, 'public/icons/teacher-512.png');
"
```

**Step 3: Commit**

```bash
git add app/Http/Controllers/Pwa/ManifestController.php public/icons/
git commit -m "feat: add PWA manifest controller and placeholder icons"
```

---

### Task 9: Service Worker

**Files:**
- Create: `public/sw.js`

```javascript
// public/sw.js
const CACHE_NAME = 'palomatika-v1';

// App shell — cached on install
const SHELL_URLS = [
  '/js/alpine.min.js',
  '/offline.html',
];

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(SHELL_URLS))
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  // Only handle GET requests
  if (request.method !== 'GET') return;

  // Never cache API calls or auth routes
  const url = new URL(request.url);
  if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/auth/')) return;

  event.respondWith(
    fetch(request)
      .then(response => {
        // Cache JS/CSS/fonts
        if (response.ok && (
          url.pathname.endsWith('.js') ||
          url.pathname.endsWith('.css') ||
          url.pathname.includes('/fonts/')
        )) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
        }
        return response;
      })
      .catch(() => {
        // Offline fallback for HTML navigation
        if (request.headers.get('accept')?.includes('text/html')) {
          return caches.match('/offline.html');
        }
      })
  );
});
```

**Step 2: Create offline page**

Create `public/offline.html`:

```html
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Нет соединения — Palomatika</title>
<style>
  body { background: #111318; color: #eef0f6; font-family: 'Nunito', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; text-align: center; padding: 24px; }
  h1 { font-size: 24px; margin-bottom: 12px; }
  p { color: #6b7280; font-size: 14px; }
</style>
</head>
<body>
  <div>
    <h1>Нет соединения</h1>
    <p>Проверьте интернет и попробуйте снова</p>
  </div>
</body>
</html>
```

**Step 3: Commit**

```bash
git add public/sw.js public/offline.html
git commit -m "feat: add service worker with app shell caching and offline fallback"
```

---

### Task 10: iOS install prompt + Login page

**Files:**
- Create: `resources/views/pwa/shared/ios-install-prompt.blade.php`
- Create: `resources/views/pwa/shared/login.blade.php`

**Step 1: iOS install prompt partial**

```html
{{-- resources/views/pwa/shared/ios-install-prompt.blade.php --}}
<div
  id="ios-install-prompt"
  x-data="iosInstallPrompt()"
  x-show="show"
  x-cloak
  style="position:fixed;bottom:0;left:0;right:0;z-index:9999;padding:16px;padding-bottom:calc(16px + env(safe-area-inset-bottom));">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:20px;max-width:480px;margin:0 auto;box-shadow:0 -4px 32px rgba(0,0,0,0.3);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
      <span style="font-family:var(--display);font-size:14px;">Установить приложение</span>
      <button @click="dismiss()" style="background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer;padding:0;line-height:1;">✕</button>
    </div>
    <p style="font-size:12px;color:var(--muted);line-height:1.6;margin-bottom:16px;">
      Нажмите
      <svg style="display:inline;width:16px;height:16px;vertical-align:middle;" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l-4 4h3v8h2V6h3L12 2zM5 18v2h14v-2H5z"/></svg>
      → <strong style="color:var(--text);">«На экран Домой»</strong> чтобы установить Palomatika как приложение
    </p>
    <div style="display:flex;align-items:center;gap:8px;background:var(--surface2);border-radius:12px;padding:12px;">
      <span style="font-size:24px;">⬆️</span>
      <span style="font-size:11px;color:var(--muted);">Кнопка «Поделиться» → Прокрутите вниз → «На экран Домой»</span>
    </div>
    {{-- Arrow pointing to share button --}}
    <div style="text-align:center;margin-top:8px;color:var(--accent);font-size:11px;font-weight:700;">↓ Кнопка внизу экрана</div>
  </div>
</div>

<script>
function iosInstallPrompt() {
  const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
  const isInApp = window.navigator.standalone;
  const dismissed = localStorage.getItem('ios_install_dismissed');
  return {
    show: isIos && !isInApp && !dismissed,
    dismiss() {
      this.show = false;
      localStorage.setItem('ios_install_dismissed', '1');
    }
  };
}
</script>
```

**Step 2: Login page**

```html
{{-- resources/views/pwa/shared/login.blade.php --}}
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

      {{-- VK --}}
      <a href="/auth/vkontakte" class="btn btn-surface" style="justify-content:flex-start;gap:14px;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#4a76a8"><path d="M20.5 0h-17C1.567 0 0 1.567 0 3.5v17C0 22.433 1.567 24 3.5 24h17c1.933 0 3.5-1.567 3.5-3.5v-17C24 1.567 22.433 0 20.5 0zm.94 16.7h-2.18c-.83 0-1.08-.66-2.56-2.15-.78-.78-1.12-.88-1.32-.88-.27 0-.35.08-.35.46v1.96c0 .33-.1.53-1 .53-1.47 0-3.1-.89-4.24-2.55-1.72-2.42-2.19-4.23-2.19-4.6 0-.2.08-.38.46-.38h2.18c.34 0 .47.16.6.53.66 1.9 1.76 3.57 2.21 3.57.17 0 .25-.08.25-.52V9.5c-.05-.94-.54-1.02-.54-1.35 0-.16.13-.33.34-.33h3.43c.28 0 .38.15.38.47v3.18c0 .28.13.38.2.38.17 0 .32-.1.64-.42 1-1.07 1.71-2.71 1.71-2.71.09-.2.28-.38.62-.38h2.18c.65 0 .8.33.65.65-.27 1.25-2.9 4.97-2.9 4.97-.14.22-.19.33 0 .57.14.19.6.6.9.97.56.64 1 1.17 1.12 1.54.12.37-.07.56-.45.56z"/></svg>
        ВКонтакте
      </a>

      {{-- Yandex --}}
      <a href="/auth/yandex" class="btn btn-surface" style="justify-content:flex-start;gap:14px;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#FC3F1D"><path d="M2.04 12c0-5.523 4.476-10 9.998-10C17.522 2 22 6.477 22 12s-4.478 10-9.962 10C6.516 22 2.04 17.523 2.04 12zm11.07 4.888V7.07h1.41c1.547 0 2.434.81 2.434 2.212 0 1.017-.51 1.742-1.412 2.04l1.951 5.566h-1.68l-1.74-5.13h-.644v5.13h-1.32z"/></svg>
        Яндекс
      </a>

      {{-- Google --}}
      <a href="/auth/google" class="btn btn-surface" style="justify-content:flex-start;gap:14px;">
        <svg width="22" height="22" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
        Google
      </a>

    </div>
  </div>

  <p class="anim-up" style="text-align:center;font-size:11px;color:var(--muted);animation-delay:0.1s;">
    Регистрация не нужна — войдите через любой аккаунт
  </p>

  {{-- Android install button (hidden by default, shown by JS) --}}
  <button id="pwa-install-btn" onclick="installPwa()"
    class="btn btn-surface hidden anim-up"
    style="border-color:var(--accent-bd);color:var(--accent);animation-delay:0.15s;">
    📲 Установить приложение
  </button>
</div>
@endsection
```

**Step 3: Commit**

```bash
git add resources/views/pwa/shared/
git commit -m "feat: add PWA login page with VK/Yandex/Google buttons and iOS install prompt"
```

---

## Phase 4 — Student PWA App

### Task 11: Pwa\StudentController scaffold + onboarding

**Files:**
- Create: `app/Http/Controllers/Pwa/StudentController.php`

The `Pwa\StudentController` uses the same services as `MiniAppStudentController` but renders `pwa.student.*` views. Copy the full constructor and all method bodies from `MiniAppStudentController`, then:
1. Replace every `view('miniapp.X')` with `view('pwa.student.X')`
2. Replace every `redirect('/tg/X')` with `redirect('http://student.' . config('app.base_domain') . '/X')`
3. Remove any Telegram Stars billing methods (`activateTrial`, `buyPremium`, `requestPayout`) — premium is removed
4. Remove the `diagnostic` and `diagnostic-editor` methods (teacher-only feature)
5. Keep `MiniAppHelpers` trait if it has reusable non-Telegram methods, otherwise inline what's needed

```php
<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Services\MiniVariantService;
use App\Services\OgeAttemptService;
use App\Services\OgeVariantBuilderService;
use App\Services\OgeVariantPoolService;
use App\Services\TaskDataService;
use App\Services\MiniAppTaskCanonicalizer;
use App\Services\MiniAppTaskSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function __construct(
        private readonly MiniVariantService $miniVariant,
        private readonly OgeAttemptService $attemptService,
        private readonly OgeVariantBuilderService $variantBuilder,
        private readonly OgeVariantPoolService $poolService,
        private readonly TaskDataService $taskData,
        private readonly MiniAppTaskCanonicalizer $taskCanonicalizer,
        private readonly MiniAppTaskSanitizer $taskSanitizer,
    ) {}

    // Copy each method from MiniAppStudentController, changing:
    // view('miniapp.X')  →  view('pwa.student.X')
    // redirect('/tg/X')  →  redirect($this->base() . '/X')
    //
    // Add this helper:
    private function base(): string
    {
        return 'http://student.' . config('app.base_domain');
    }

    // Onboarding (GET)
    public function onboarding(Request $request)
    {
        if (Auth::check() && Auth::user()->onboarding_completed_at) {
            return redirect($this->base() . '/');
        }
        return view('pwa.student.onboarding');
    }

    // Onboarding (POST) — same validation as MiniAppAuthController::saveOnboarding
    public function saveOnboarding(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|min:2|max:100',
            'grade_num'     => 'required|integer|in:9',
            'grade_letter'  => 'required|string|in:А,Б,В,Г,Д,К,М',
            'school_number' => 'required|string|max:20',
            'city'          => 'nullable|string|max:80',
        ]);

        $user = $request->user();
        abort_unless($user, 401);

        $user->update([
            'name'                    => $data['name'],
            'grade_num'               => $data['grade_num'],
            'grade_letter'            => $data['grade_letter'],
            'school_number'           => $data['school_number'],
            'city'                    => $data['city'] ?: 'Чехов',
            'onboarding_completed_at' => now(),
        ]);

        return redirect($this->base() . '/');
    }

    // All other methods (dashboard, mini, part2, test, results, history, profile, etc.)
    // are copied verbatim from MiniAppStudentController with the two substitutions above.
}
```

> **Implementation note:** The actual method bodies are in `app/Http/Controllers/MiniAppStudentController.php`. Copy each method wholesale — do not rewrite the logic. The only changes are view names and redirect URLs.

**Step 2: Write test**

```php
// tests/Feature/Pwa/PwaStudentRoutesTest.php
public function test_dashboard_redirects_unauthenticated(): void
{
    $response = $this->get('http://student.palomatika.ru/');
    $response->assertRedirect();
}

public function test_onboarding_page_accessible_when_authenticated(): void
{
    $user = User::factory()->create(['oauth_provider' => 'vk', 'oauth_id' => '1']);
    $response = $this->actingAs($user)->get('http://student.palomatika.ru/onboarding');
    $response->assertStatus(200);
}
```

**Step 3: Commit**

```bash
git add app/Http/Controllers/Pwa/StudentController.php tests/Feature/Pwa/PwaStudentRoutesTest.php
git commit -m "feat: add Pwa\\StudentController porting MiniApp student logic to PWA routes"
```

---

### Task 12: Student views — layout port

**Files to create** (each extends `layouts.pwa` instead of `layouts.miniapp`):

For each file in `resources/views/miniapp/`, create the equivalent in `resources/views/pwa/student/`:

| Old | New |
|-----|-----|
| `miniapp/dashboard.blade.php` | `pwa/student/dashboard.blade.php` |
| `miniapp/onboarding.blade.php` | `pwa/student/onboarding.blade.php` |
| `miniapp/mini.blade.php` | `pwa/student/mini.blade.php` |
| `miniapp/new-tasks.blade.php` | `pwa/student/new-tasks.blade.php` |
| `miniapp/part2.blade.php` | `pwa/student/part2.blade.php` |
| `miniapp/tasks-part1.blade.php` | `pwa/student/tasks-part1.blade.php` |
| `miniapp/test.blade.php` | `pwa/student/test.blade.php` |
| `miniapp/results.blade.php` | `pwa/student/results.blade.php` |
| `miniapp/history.blade.php` | `pwa/student/history.blade.php` |
| `miniapp/history-detail.blade.php` | `pwa/student/history-detail.blade.php` |
| `miniapp/profile.blade.php` | `pwa/student/profile.blade.php` |
| `miniapp/student-homework.blade.php` | `pwa/student/student-homework.blade.php` |
| `miniapp/tutor.blade.php` | `pwa/student/tutor.blade.php` |

**Process for each file:**

1. Copy the file content
2. Change `@extends('layouts.miniapp')` → `@extends('layouts.pwa')`
3. Remove any blocks that reference Telegram (`tg.share`, Telegram bot links, Stars balance, etc.)
4. Update internal links: `/tg/X` → `/X` (since we're on the student subdomain, relative paths work)

**Step 2: Commit**

```bash
git add resources/views/pwa/student/
git commit -m "feat: add PWA student views ported from miniapp views"
```

---

## Phase 5 — Teacher PWA App

### Task 13: Pwa\TeacherController

**Files:**
- Create: `app/Http/Controllers/Pwa/TeacherController.php`

Same pattern as `StudentController` — copy from `MiniAppTeacherController` (939 lines), change:
- `view('miniapp.teacher-X')` → `view('pwa.teacher.X')`
- `redirect('/tg/teacher/X')` → `redirect($this->base() . '/X')`
- Add `private function base(): string { return 'http://teacher.' . config('app.base_domain'); }`

**Step 2: Create teacher views**

| Old | New |
|-----|-----|
| `miniapp/teacher-dashboard.blade.php` | `pwa/teacher/dashboard.blade.php` |
| `miniapp/teacher-students.blade.php` | `pwa/teacher/students.blade.php` |
| `miniapp/teacher-student-profile.blade.php` | `pwa/teacher/student-profile.blade.php` |
| `miniapp/teacher-lessons.blade.php` | `pwa/teacher/lessons.blade.php` |
| `miniapp/teacher-homework.blade.php` | `pwa/teacher/homework.blade.php` |
| `miniapp/teacher-variants.blade.php` | `pwa/teacher/variants.blade.php` |
| `miniapp/teacher-referrals.blade.php` | `pwa/teacher/referrals.blade.php` |

Same process: copy, change extends, update links.

**Step 3: Write test**

```php
// tests/Feature/Pwa/PwaTeacherRoutesTest.php
public function test_teacher_dashboard_requires_auth(): void
{
    $response = $this->get('http://teacher.palomatika.ru/dashboard');
    $response->assertRedirect();
}

public function test_teacher_dashboard_accessible_for_teacher_role(): void
{
    $user = User::factory()->create([
        'oauth_provider' => 'vk',
        'oauth_id' => '789',
        'role' => 'teacher',
        'onboarding_completed_at' => now(),
    ]);
    $response = $this->actingAs($user)->get('http://teacher.palomatika.ru/dashboard');
    $response->assertStatus(200);
}
```

**Step 4: Commit**

```bash
git add app/Http/Controllers/Pwa/TeacherController.php resources/views/pwa/teacher/ tests/Feature/Pwa/PwaTeacherRoutesTest.php
git commit -m "feat: add Pwa\\TeacherController and teacher PWA views"
```

---

## Phase 6 — Migration

### Task 14: Migration banner in /tg/*

**Files:**
- Create: `resources/views/miniapp/partials/migration-banner.blade.php`
- Modify: `resources/views/miniapp/dashboard.blade.php`
- Modify: `resources/views/miniapp/home.blade.php`

**Step 1: Create migration banner partial**

```html
{{-- resources/views/miniapp/partials/migration-banner.blade.php --}}
<div style="background:var(--accent-bg);border:1px solid var(--accent-bd);border-radius:14px;padding:14px 16px;display:flex;gap:12px;align-items:flex-start;">
  <span style="font-size:20px;flex-shrink:0;">🚀</span>
  <div>
    <div style="font-family:var(--display);font-size:13px;color:var(--accent);margin-bottom:4px;">Palomatika переезжает!</div>
    <div style="font-size:11px;color:var(--muted);line-height:1.5;margin-bottom:10px;">
      Мы запустили отдельное приложение. Перейдите и установите его на телефон.
    </div>
    <a href="https://student.palomatika.ru/migrate?token={{ $migrationToken ?? '' }}"
       style="display:inline-block;background:var(--accent);color:#fff;padding:8px 16px;border-radius:10px;font-family:var(--display);font-size:12px;text-decoration:none;">
      Перейти в новое приложение →
    </a>
  </div>
</div>
```

**Step 2: Add `$migrationToken` to dashboard in MiniAppStudentController**

In `MiniAppStudentController::dashboard()`, add before the return:

```php
// Generate one-time migration token for this user
$migrationToken = \Illuminate\Support\Str::random(32);
\Illuminate\Support\Facades\Cache::put(
    'pwa_migration:' . $migrationToken,
    ['user_id' => $user->id],
    now()->addMinutes(10)
);
```

Pass `$migrationToken` to the view.

**Step 3: Include banner in miniapp/dashboard.blade.php**

Add at the top of the dashboard body (after topbar):

```html
@include('miniapp.partials.migration-banner', ['migrationToken' => $migrationToken])
```

**Step 4: Commit**

```bash
git add resources/views/miniapp/partials/migration-banner.blade.php
git commit -m "feat: add migration banner to Telegram mini app pointing to PWA"
```

---

### Task 15: Migration landing page on student subdomain

**Files:**
- Create: `resources/views/pwa/student/migrate.blade.php`

```html
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
```

**Step 2: Commit**

```bash
git add resources/views/pwa/student/migrate.blade.php
git commit -m "feat: add Telegram migration landing page on student subdomain"
```

---

## Phase 7 — Landing + Cleanup

### Task 16: Update palomatika.ru landing page

**Files:**
- Identify current landing controller from routes/web.php (line ~1: `Route::get('/')`)
- Create or update the landing view

The new landing has two cards: "Я ученик" → `student.palomatika.ru` and "Я репетитор" → `teacher.palomatika.ru`.

```html
{{-- resources/views/landing.blade.php (new simple landing) --}}
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Palomatika — Подготовка к ОГЭ</title>
  {{-- reuse pwa layout CSS variables inline --}}
</head>
<body style="background:#111318;color:#eef0f6;font-family:'Nunito',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;">
  <div style="max-width:400px;width:100%;text-align:center;">
    <div style="font-family:'Russo One',sans-serif;font-size:32px;color:#4f8ef7;margin-bottom:8px;">palomatika</div>
    <p style="color:#6b7280;font-size:14px;margin-bottom:32px;">Подготовка к ОГЭ по математике</p>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <a href="https://student.palomatika.ru"
         style="display:block;background:#4f8ef7;color:#fff;padding:18px;border-radius:16px;font-family:'Russo One',sans-serif;font-size:16px;text-decoration:none;">
        Я ученик
      </a>
      <a href="https://teacher.palomatika.ru"
         style="display:block;background:#1c1f27;border:1px solid #2a2e3a;color:#eef0f6;padding:18px;border-radius:16px;font-family:'Russo One',sans-serif;font-size:16px;text-decoration:none;">
        Я репетитор
      </a>
    </div>
    <p style="margin-top:24px;font-size:11px;color:#3e4352;">
      <a href="/topics" style="color:#6b7280;">База заданий</a>
      &nbsp;·&nbsp;
      <a href="/materials" style="color:#6b7280;">Материалы</a>
    </p>
  </div>
</body>
</html>
```

**Step 2: Update the root route in routes/web.php**

Find the `Route::get('/')` line and point it to the new landing view:

```php
Route::get('/', fn() => view('landing'));
```

**Step 3: Commit**

```bash
git add resources/views/landing.blade.php routes/web.php
git commit -m "feat: update palomatika.ru landing page with links to student/teacher PWA"
```

---

### Task 17: Remove old website pages/controllers

**What to remove** (verify each exists before deleting):

Controllers (check they are not referenced by `/topics/`, `/materials/`, `/tg/`, or `/api/`):
- Any controller only referenced by old non-functional routes

Views (in `resources/views/`):
- Old marketing/landing views not related to miniapp, topics, or materials

Routes in `routes/web.php`:
- Remove route groups for old pages not in: `/tg/*`, `/topics/*`, `/materials/*`, `/api/*`

**Process for each file:**

```bash
# Before deleting any controller, verify it's unused:
grep -r "ClassName" routes/ app/ --include="*.php"
# Only delete if result is empty
```

**Do NOT delete:**
- Everything in `resources/views/miniapp/` (still needed for `/tg/*`)
- Everything in `resources/views/tasks/` (used by topics pages)
- `layouts/miniapp.blade.php`
- `layouts/topic.blade.php`
- Any controller referenced from `/topics/`, `/materials/`, or `/tg/` routes

**Step 2: Run tests after cleanup**

```bash
php artisan test
```

All tests should pass. If any fail, a deleted file was still in use — restore it.

**Step 3: Commit**

```bash
git add -A
git commit -m "chore: remove unused legacy website pages and controllers"
```

---

### Task 18: Run full test suite + final checks

**Step 1: Run all tests**

```bash
cd /home/dev/palomatika && php artisan test --parallel
```

Expected: All tests pass except the pre-existing `LocalWebLoginTest` (MySQL driver issue, not our code).

**Step 2: Verify manifest.json**

```bash
curl -s http://student.palomatika.ru/manifest.json | python3 -m json.tool
curl -s http://teacher.palomatika.ru/manifest.json | python3 -m json.tool
```

**Step 3: Verify sw.js**

```bash
curl -I http://student.palomatika.ru/sw.js
# Expect: Content-Type: application/javascript, Service-Worker-Allowed: /
```

**Step 4: Verify /tg/ still works**

```bash
curl -I http://palomatika.ru/tg/
# Expect: 200 OK or redirect to login
```

**Step 5: Verify /topics/ and /materials/ still work**

```bash
curl -I http://palomatika.ru/topics/6
curl -I http://palomatika.ru/materials
# Both should return 200
```

**Step 6: Final commit**

```bash
git add -A
git commit -m "feat: complete PWA migration — student and teacher subdomains live"
git push
```

---

## Summary

| Phase | Tasks | Key Deliverable |
|-------|-------|-----------------|
| 1. Infrastructure | 1-3 | Apache vhosts, session domain, route scaffold |
| 2. Auth | 4-6 | VK/Yandex/Google OAuth, PWA middleware |
| 3. PWA Shell | 7-10 | Layout, manifest, SW, install prompts |
| 4. Student App | 11-12 | All student routes + views |
| 5. Teacher App | 13 | All teacher routes + views |
| 6. Migration | 14-15 | Banner in /tg/, migration page |
| 7. Cleanup | 16-18 | Landing, cleanup, test suite |

**After all users have migrated** (separate PR):
- Remove `/tg/*` routes and all miniapp views/controllers
- Remove Telegram config from services.php
- Remove `CaptureTelegramStartParam` middleware

**Bugs in OGE variant generation** — tracked separately, not in this plan.
