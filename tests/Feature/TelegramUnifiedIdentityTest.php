<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TelegramIdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramUnifiedIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_miniapp_then_web_oidc_resolve_to_same_account(): void
    {
        $resolver = app(TelegramIdentityResolver::class);

        // Mini App (initData) — приходит как int id
        $fromMiniApp = $resolver->resolve(['id' => 50050, 'username' => 'student1', 'name' => 'Ученик']);

        // Web OIDC — sub приходит как строка
        $fromWeb = $resolver->resolve(['id' => '50050', 'username' => 'student1', 'name' => 'Ученик']);

        $this->assertSame($fromMiniApp->id, $fromWeb->id);
        $this->assertSame(1, User::where('oauth_id', '50050')->count());
    }
}
