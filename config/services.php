<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Providers
    |--------------------------------------------------------------------------
    */

    'vkontakte' => [
        'client_id' => env('VK_CLIENT_ID'),
        'client_secret' => env('VK_CLIENT_SECRET'),
        'redirect' => env('VK_REDIRECT_URI'),
    ],

    'telegram' => [
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'palomatika_auth_bot'),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        // BotFather /setdomain value for Mini App web_app buttons (host/domain only).
        'webapp_domain' => env('TELEGRAM_WEBAPP_DOMAIN'),
        // Absolute Mini App URL used inside Telegram web_app buttons, e.g. https://app.example.com/oge.
        'webapp_base_url' => env('TELEGRAM_WEBAPP_BASE_URL'),
        'mini_app_link_scheme' => env('TELEGRAM_MINI_APP_LINK_SCHEME', 'https'),
        // BotFather Mini App short name (used in https://t.me/<bot>/<short_name>?startapp=...)
        'mini_app_short_name' => env('TELEGRAM_MINI_APP_SHORT_NAME'),
        // Max age for Telegram WebApp auth_date validation.
        'webapp_auth_max_age' => (int) env('TELEGRAM_WEBAPP_AUTH_MAX_AGE', 86400),
        // Отдельный токен для родительского бота (@palomatika_parent_bot)
        'parent_bot_token' => env('TELEGRAM_PARENT_BOT_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Robokassa Payment
    |--------------------------------------------------------------------------
    */

    'robokassa' => [
        'merchant_login' => env('ROBOKASSA_MERCHANT_LOGIN'),
        'password_1' => env('ROBOKASSA_PASSWORD_1'),
        'password_2' => env('ROBOKASSA_PASSWORD_2'),
        'test_mode' => env('ROBOKASSA_TEST_MODE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deploy Webhook
    |--------------------------------------------------------------------------
    |
    | Secret for authenticating deploy refresh webhook calls from CI/CD.
    | Set DEPLOY_WEBHOOK_SECRET in .env and as GitHub Actions secret.
    |
    */

    'deploy' => [
        'webhook_secret' => env('DEPLOY_WEBHOOK_SECRET'),
    ],

];
