<?php

namespace App\Support;

use App\Models\OgeAttempt;

class OgeResultLinkBuilder
{
    private const TELEGRAM_OPEN_BUTTON_TEXT = 'Открыть в Telegram';

    public function buildTelegramLinks(OgeAttempt $attempt): array
    {
        $variantWebUrl = $this->buildVariantResultsUrl($attempt);
        $attemptWebUrl = $this->buildAttemptResultsUrl($attempt);

        $variantPayload = 'oge_variant_' . (int) $attempt->variant_id;
        $attemptPayload = 'oge_attempt_' . (int) $attempt->id;

        $variantMiniAppUrl = $this->buildTelegramMiniAppDeepLink($variantPayload);
        $attemptMiniAppUrl = $this->buildTelegramMiniAppDeepLink($attemptPayload);

        $variantButtonUrl = $this->buildTelegramWebAppButtonUrl($variantPayload) ?? $variantWebUrl;
        $attemptButtonUrl = $this->buildTelegramWebAppButtonUrl($attemptPayload) ?? $attemptWebUrl;
        $variantButton = $this->buildTelegramButtonPayload(
            $variantButtonUrl,
            $variantWebUrl,
        );
        $attemptButton = $this->buildTelegramButtonPayload(
            $attemptButtonUrl,
            $attemptWebUrl,
        );

        return [
            'variant' => [
                'web_url' => $variantWebUrl,
                'mini_app_url' => $variantMiniAppUrl,
                'button_url' => $variantButtonUrl,
                'preferred_url' => $variantMiniAppUrl ?? $variantWebUrl,
                'startapp_payload' => $variantPayload,
                'button_type' => $variantButton['type'],
                'button_payload' => $variantButton['button'],
                'reply_markup' => $variantButton['reply_markup'],
            ],
            'attempt' => [
                'web_url' => $attemptWebUrl,
                'mini_app_url' => $attemptMiniAppUrl,
                'button_url' => $attemptButtonUrl,
                'preferred_url' => $attemptMiniAppUrl ?? $attemptWebUrl,
                'startapp_payload' => $attemptPayload,
                'button_type' => $attemptButton['type'],
                'button_payload' => $attemptButton['button'],
                'reply_markup' => $attemptButton['reply_markup'],
            ],
        ];
    }

    public function validateTelegramWebAppConfig(): array
    {
        $botUsername = trim((string) config('services.telegram.bot_username', ''));
        $webAppBaseUrl = trim((string) config('services.telegram.webapp_base_url', ''));
        $webAppDomain = trim((string) config('services.telegram.webapp_domain', ''));
        $issues = [];
        $parsedHost = null;

        if ($webAppBaseUrl !== '') {
            $parsedHost = parse_url($webAppBaseUrl, PHP_URL_HOST);
            $parsedScheme = strtolower((string) parse_url($webAppBaseUrl, PHP_URL_SCHEME));

            if (!is_string($parsedHost) || $parsedHost === '' || !in_array($parsedScheme, ['http', 'https'], true)) {
                $issues[] = 'TELEGRAM_WEBAPP_BASE_URL must be an absolute http(s) URL';
            }

            if ($botUsername === '') {
                $issues[] = 'TELEGRAM_BOT_USERNAME is required for Telegram Mini App deep links';
            }

            if ($webAppDomain === '') {
                $issues[] = 'TELEGRAM_WEBAPP_DOMAIN (BotFather /setdomain) is required for web_app buttons';
            } elseif (is_string($parsedHost) && $parsedHost !== '' && !str_contains($parsedHost, $webAppDomain) && !str_contains($webAppDomain, $parsedHost)) {
                $issues[] = 'TELEGRAM_WEBAPP_DOMAIN must match TELEGRAM_WEBAPP_BASE_URL host';
            }
        }

        return [
            'web_app_button_enabled' => $webAppBaseUrl !== '',
            'is_valid' => count($issues) === 0,
            'bot_username_configured' => $botUsername !== '',
            'webapp_base_url' => $webAppBaseUrl !== '' ? $webAppBaseUrl : null,
            'webapp_base_host' => is_string($parsedHost) && $parsedHost !== '' ? $parsedHost : null,
            'webapp_domain' => $webAppDomain !== '' ? $webAppDomain : null,
            'issues' => $issues,
        ];
    }

    public function buildVariantResultsUrl(OgeAttempt $attempt): string
    {
        return route('teacher.oge.results', ['variantId' => $attempt->variant_id]);
    }

    public function buildAttemptResultsUrl(OgeAttempt $attempt): string
    {
        $base = $this->buildVariantResultsUrl($attempt);
        $attemptId = (int) $attempt->id;

        return "{$base}?attempt={$attemptId}#attempt-{$attemptId}";
    }

    private function buildTelegramMiniAppDeepLink(string $startappPayload): ?string
    {
        $botUsername = trim((string) config('services.telegram.bot_username', ''));
        if ($botUsername === '') {
            return null;
        }

        $encodedPayload = rawurlencode($startappPayload);
        $scheme = strtolower((string) config('services.telegram.mini_app_link_scheme', 'https'));

        if ($scheme === 'tg') {
            return "tg://resolve?domain={$botUsername}&startapp={$encodedPayload}";
        }

        return "https://t.me/{$botUsername}?startapp={$encodedPayload}";
    }

    private function buildTelegramWebAppButtonUrl(string $startappPayload): ?string
    {
        $baseUrl = trim((string) config('services.telegram.webapp_base_url', ''));
        if ($baseUrl === '') {
            return null;
        }

        return $this->appendQueryParam($baseUrl, 'startapp', $startappPayload);
    }

    private function appendQueryParam(string $url, string $key, string $value): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . rawurlencode($key) . '=' . rawurlencode($value);
    }

    private function buildTelegramButtonPayload(string $buttonUrl, string $fallbackWebUrl): array
    {
        $isWebAppButton = $buttonUrl !== $fallbackWebUrl;

        $button = [
            'text' => self::TELEGRAM_OPEN_BUTTON_TEXT,
        ];

        if ($isWebAppButton) {
            $button['web_app'] = ['url' => $buttonUrl];
        } else {
            $button['url'] = $fallbackWebUrl;
        }

        return [
            'type' => $isWebAppButton ? 'web_app' : 'url',
            'button' => $button,
            'reply_markup' => [
                'inline_keyboard' => [[$button]],
            ],
        ];
    }
}
