<?php
namespace Tests\Unit;

use App\Models\User;
use App\Services\TelegramIdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramIdentityResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolver(): TelegramIdentityResolver
    {
        return app(TelegramIdentityResolver::class);
    }

    public function test_creates_new_user_with_telegram_identity(): void
    {
        $user = $this->resolver()->resolve([
            'id' => 555, 'username' => 'vasya', 'name' => 'Вася Пупкин', 'photo' => 'https://t.me/p.jpg',
        ]);

        $this->assertSame('telegram', $user->oauth_provider);
        $this->assertSame('555', (string) $user->oauth_id);
        $this->assertSame('vasya', $user->tg_username);
    }

    public function test_returns_same_user_for_same_telegram_id(): void
    {
        $a = $this->resolver()->resolve(['id' => 777, 'name' => 'A']);
        $b = $this->resolver()->resolve(['id' => 777, 'name' => 'A']);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, User::where('oauth_id', '777')->count());
    }

    public function test_adopts_legacy_user_with_null_provider(): void
    {
        $legacy = User::create(['name' => 'Old', 'oauth_provider' => null, 'oauth_id' => '999']);
        $resolved = $this->resolver()->resolve(['id' => 999, 'name' => 'Old']);
        $this->assertSame($legacy->id, $resolved->id);
        $this->assertSame('telegram', $resolved->fresh()->oauth_provider);
    }

    public function test_does_not_write_dropped_trial_ends_at_column(): void
    {
        $user = $this->resolver()->resolve(['id' => 111, 'name' => 'X']);
        // trial_ends_at дропнут миграцией #44 — не должно быть в атрибутах
        $this->assertArrayNotHasKey('trial_ends_at', $user->getAttributes());
    }
}
