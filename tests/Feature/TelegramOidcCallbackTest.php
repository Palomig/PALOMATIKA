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

    public function test_skips_unsupported_jwks_keys_and_still_verifies_rsa(): void
    {
        // JWKS Telegram содержит ключи разных алгоритмов. Неподдерживаемый/битый ключ
        // не должен ронять весь набор (на проде это давало 500) — он пропускается,
        // а RS256-ключ всё равно используется для проверки подписи.
        $jwks = $this->rsa['jwks'];
        array_unshift($jwks['keys'], ['kty' => 'OKP', 'crv' => 'Ed25519', 'kid' => 'bad-eddsa', 'alg' => 'EdDSA', 'x' => '!!!not-valid-base64!!!']);
        $jwks['keys'][] = ['kty' => 'WEIRD', 'kid' => 'unknown-kty', 'alg' => 'XX256'];

        Http::fake([
            'oauth.telegram.org/token' => Http::response(['id_token' => $this->makeIdToken()], 200),
            'oauth.telegram.org/.well-known/jwks.json' => Http::response($jwks, 200),
        ]);

        $claims = app(TelegramOidcService::class)->exchangeAndVerify('the-code', 'v', 'NONCE');
        $this->assertSame('424242', (string) $claims['sub']);
    }

    public function test_missing_client_secret_throws_clear_error(): void
    {
        config()->set('services.telegram.oidc.client_secret', '');
        $this->expectException(\App\Services\TelegramOidcException::class);
        $this->expectExceptionMessageMatches('/client_secret missing/');
        app(TelegramOidcService::class)->exchangeAndVerify('c', 'v', 'NONCE');
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
