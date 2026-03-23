<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'telegram/webhook',
        'api/auth/telegram/webapp-login',
        'tg/auth',
        'tg/auth/bridge-ping',
        'tg/onboarding',
        'parent/auth',
    ];
}
