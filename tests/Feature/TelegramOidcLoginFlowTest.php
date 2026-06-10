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
        $resp->assertRedirect();
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
        $resp->assertRedirect();
    }
}
