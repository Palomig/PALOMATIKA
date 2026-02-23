<?php

namespace Tests\Unit;

use App\Models\OgeAttempt;
use App\Support\OgeResultLinkBuilder;
use Tests\TestCase;

class OgeResultLinkBuilderTest extends TestCase
{
    public function test_builds_mini_app_links_and_button_urls_when_telegram_configured(): void
    {
        config()->set('services.telegram.bot_username', 'palomatika_bot');
        config()->set('services.telegram.webapp_base_url', 'https://mini.example.com/oge');
        config()->set('services.telegram.mini_app_link_scheme', 'tg');

        $attempt = new OgeAttempt();
        $attempt->id = 321;
        $attempt->variant_id = 99;

        $links = app(OgeResultLinkBuilder::class)->buildTelegramLinks($attempt);

        $this->assertSame('oge_variant_99', $links['variant']['startapp_payload']);
        $this->assertSame('oge_attempt_321', $links['attempt']['startapp_payload']);
        $this->assertSame('tg://resolve?domain=palomatika_bot&startapp=oge_variant_99', $links['variant']['mini_app_url']);
        $this->assertSame('tg://resolve?domain=palomatika_bot&startapp=oge_attempt_321', $links['attempt']['mini_app_url']);
        $this->assertSame('https://mini.example.com/oge?startapp=oge_variant_99', $links['variant']['button_url']);
        $this->assertSame('https://mini.example.com/oge?startapp=oge_attempt_321', $links['attempt']['button_url']);
        $this->assertSame('web_app', $links['variant']['button_type']);
        $this->assertSame('Открыть в Telegram', $links['variant']['button_payload']['text'] ?? null);
        $this->assertSame(
            'https://mini.example.com/oge?startapp=oge_variant_99',
            $links['variant']['button_payload']['web_app']['url'] ?? null
        );
        $this->assertSame(
            'https://mini.example.com/oge?startapp=oge_variant_99',
            $links['variant']['reply_markup']['inline_keyboard'][0][0]['web_app']['url'] ?? null
        );
    }

    public function test_falls_back_to_web_links_when_telegram_mini_app_config_missing(): void
    {
        config()->set('services.telegram.bot_username', null);
        config()->set('services.telegram.webapp_base_url', null);

        $attempt = new OgeAttempt();
        $attempt->id = 77;
        $attempt->variant_id = 55;

        $links = app(OgeResultLinkBuilder::class)->buildTelegramLinks($attempt);

        $this->assertNull($links['variant']['mini_app_url']);
        $this->assertNull($links['attempt']['mini_app_url']);
        $this->assertSame($links['variant']['web_url'], $links['variant']['button_url']);
        $this->assertSame($links['attempt']['web_url'], $links['attempt']['button_url']);
        $this->assertSame($links['variant']['web_url'], $links['variant']['preferred_url']);
        $this->assertSame($links['attempt']['web_url'], $links['attempt']['preferred_url']);
        $this->assertSame('url', $links['variant']['button_type']);
        $this->assertSame('Открыть в Telegram', $links['variant']['button_payload']['text'] ?? null);
        $this->assertSame($links['variant']['web_url'], $links['variant']['button_payload']['url'] ?? null);
        $this->assertSame($links['variant']['web_url'], $links['variant']['reply_markup']['inline_keyboard'][0][0]['url'] ?? null);
    }
}
