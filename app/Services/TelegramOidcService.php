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
