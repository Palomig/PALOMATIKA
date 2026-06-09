<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyTelegramAuthRemovedTest extends TestCase
{
    public function test_bot_deeplink_login_route_gone(): void
    {
        $this->get('/auth/telegram/login/sometoken123456')->assertNotFound();
    }

    public function test_generate_token_api_gone(): void
    {
        $this->postJson('/api/telegram/generate-token')->assertNotFound();
    }

    public function test_webapp_login_still_exists(): void
    {
        // маршрут есть (422/400 без валидного initData — НЕ 404)
        $resp = $this->postJson('/api/auth/telegram/webapp-login', []);
        $this->assertNotSame(404, $resp->getStatusCode());
    }
}
