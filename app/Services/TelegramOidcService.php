<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

    /**
     * Обмен authorization code на id_token и его полная верификация.
     * @return array<string,mixed> проверенные claims
     * @throws TelegramOidcException
     */
    public function exchangeAndVerify(string $code, string $verifier, string $expectedNonce): array
    {
        JWT::$leeway = 60;

        $cfg = config('services.telegram.oidc');

        $clientId     = (string) ($cfg['client_id'] ?? '');
        $clientSecret = (string) ($cfg['client_secret'] ?? '');
        if ($clientSecret === '') {
            // Защита от TypeError и понятный диагноз: секрет не прокинут в конфиг на сервере.
            throw new TelegramOidcException('OIDC client_secret missing on server (проверь .env + config:clear)');
        }

        $resp = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post($cfg['token_url'], [
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $cfg['redirect'],
                'code_verifier' => $verifier,
            ]);

        if (!$resp->ok() || !$resp->json('id_token')) {
            // В reason кладём статус + тело ответа Telegram (invalid_client / invalid_grant и т.п.).
            throw new TelegramOidcException('Token endpoint error: ' . $resp->status() . ' ' . mb_substr((string) $resp->body(), 0, 160));
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

        // JWKS Telegram содержит ключи разных алгоритмов (RS256, ES256, EdDSA, ES256K).
        // Парсим КАЖДЫЙ ключ отдельно и пропускаем те, что среда не поддерживает
        // (например EdDSA/secp256k1 без ext-sodium), иначе один неподдерживаемый ключ
        // ронял бы весь набор и давал 500. id_token подписан RS256-ключом (oidc-1).
        $keys = [];
        foreach (($raw['keys'] ?? []) as $jwk) {
            $kid = $jwk['kid'] ?? null;
            if ($kid === null) {
                continue;
            }
            try {
                if (method_exists(JWK::class, 'parseKey')) {
                    $key = JWK::parseKey($jwk, $jwk['alg'] ?? null);
                    if ($key !== null) {
                        $keys[$kid] = $key;
                    }
                } elseif (($jwk['kty'] ?? null) === 'RSA') {
                    // Старые версии firebase/php-jwt без parseKey() — только RSA.
                    $keys += JWK::parseKeySet(['keys' => [$jwk]]);
                }
            } catch (\Throwable $e) {
                // Неподдерживаемый/битый ключ — пропускаем, остальные используем.
                continue;
            }
        }

        if ($keys === []) {
            throw new TelegramOidcException('No usable JWKS keys');
        }

        return $keys;
    }
}
