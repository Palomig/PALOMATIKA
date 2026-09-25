<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RememberDevicesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'student']);

        Route::middleware('web')->group(function () {
            Route::get('/_remember/login/{id}', function ($id) {
                Auth::login(User::findOrFail($id), true);

                return 'ok';
            });
            Route::get('/_remember/me', fn () => (string) (Auth::id() ?? 'guest'));
            Route::get('/_remember/logout', function () {
                Auth::logout();

                return 'bye';
            });
        });
    }

    private function recallerName(): string
    {
        return Auth::guard('web')->getRecallerName();
    }

    /** Новый «чистый» запрос: без сессии, только с remember-кукой (или вообще без кук). */
    private function fresh(?string $recaller = null): static
    {
        Auth::forgetGuards();
        $this->flushSession();
        $this->defaultCookies = [];
        $this->unencryptedCookies = [];

        return $recaller === null ? $this : $this->withCookie($this->recallerName(), $recaller);
    }

    private function loginDevice(): string
    {
        $response = $this->fresh()->get('/_remember/login/'.$this->user->id);
        $response->assertOk();

        return $response->getCookie($this->recallerName())->getValue();
    }

    public function test_each_login_gets_its_own_device_token(): void
    {
        $shared = $this->user->remember_token;
        $phone = $this->loginDevice();
        $laptop = $this->loginDevice();

        $this->assertNotSame(explode('|', $phone)[1], explode('|', $laptop)[1]);
        $this->assertSame(2, DB::table('remember_devices')->where('user_id', $this->user->id)->count());
        $this->assertSame($shared, $this->user->fresh()->remember_token);
    }

    public function test_logout_on_one_device_keeps_the_others_signed_in(): void
    {
        $phone = $this->loginDevice();
        $laptop = $this->loginDevice();

        $this->fresh($phone)->get('/_remember/logout')->assertOk();

        $this->fresh($phone)->get('/_remember/me')->assertSeeText('guest');
        $this->fresh($laptop)->get('/_remember/me')->assertSeeText((string) $this->user->id);
        $this->assertSame(1, DB::table('remember_devices')->where('user_id', $this->user->id)->count());
    }

    public function test_restoring_from_cookie_extends_it(): void
    {
        $phone = $this->loginDevice();

        $response = $this->fresh($phone)->get('/_remember/me');

        $response->assertSeeText((string) $this->user->id);
        $this->assertSame($phone, $response->getCookie($this->recallerName())->getValue());
    }

    public function test_relogin_on_same_device_reuses_its_token(): void
    {
        $phone = $this->loginDevice();

        $response = $this->fresh($phone)->get('/_remember/login/'.$this->user->id);

        $this->assertSame($phone, $response->getCookie($this->recallerName())->getValue());
        $this->assertSame(1, DB::table('remember_devices')->count());
    }

    public function test_legacy_shared_token_still_works_and_is_adopted(): void
    {
        $this->user->forceFill(['remember_token' => 'legacy-token-123'])->save();
        $legacy = $this->user->id.'|legacy-token-123|'.$this->user->password;

        $this->fresh($legacy)->get('/_remember/me')->assertSeeText((string) $this->user->id);
        $this->assertSame(1, DB::table('remember_devices')->where('user_id', $this->user->id)->count());

        // Даже если общий токен потом сменится, перенесённая кука живёт.
        $this->user->forceFill(['remember_token' => 'rotated'])->save();
        $this->fresh($legacy)->get('/_remember/me')->assertSeeText((string) $this->user->id);
    }

    public function test_unknown_token_is_rejected(): void
    {
        $this->loginDevice();

        $this->fresh($this->user->id.'|forged-token|'.$this->user->password)
            ->get('/_remember/me')
            ->assertSeeText('guest');
    }

    public function test_stale_device_is_rejected(): void
    {
        $phone = $this->loginDevice();
        DB::table('remember_devices')->update(['last_used_at' => now()->subDays(401)]);

        $this->fresh($phone)->get('/_remember/me')->assertSeeText('guest');
    }
}
