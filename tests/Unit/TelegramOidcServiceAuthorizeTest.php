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
