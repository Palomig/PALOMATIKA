<?php

namespace App\Support;

use App\Models\OgeAttempt;

class OgeResultLinkBuilder
{
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

        return [
            'variant' => [
                'web_url' => $variantWebUrl,
                'mini_app_url' => $variantMiniAppUrl,
                'button_url' => $variantButtonUrl,
                'preferred_url' => $variantMiniAppUrl ?? $variantWebUrl,
                'startapp_payload' => $variantPayload,
            ],
            'attempt' => [
                'web_url' => $attemptWebUrl,
                'mini_app_url' => $attemptMiniAppUrl,
                'button_url' => $attemptButtonUrl,
                'preferred_url' => $attemptMiniAppUrl ?? $attemptWebUrl,
                'startapp_payload' => $attemptPayload,
            ],
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
}
