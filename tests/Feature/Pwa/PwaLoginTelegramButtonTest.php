<?php
namespace Tests\Feature\Pwa;

use Tests\TestCase;

class PwaLoginTelegramButtonTest extends TestCase
{
    public function test_student_login_shows_telegram_button(): void
    {
        $resp = $this->get('https://student.' . config('app.base_domain') . '/login');
        $resp->assertOk();
        $resp->assertSee('/auth/telegram/redirect?origin=student', false);
    }
}
