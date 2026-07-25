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
        $user = $this->resolver()->resolveByChatId([
            'id' => 555, 'username' => 'vasya', 'name' => 'Вася Пупкин', 'photo' => 'https://t.me/p.jpg',
        ]);

        $this->assertSame('telegram', $user->oauth_provider);
        $this->assertSame(555, $user->telegram_chat_id);
        $this->assertSame('vasya', $user->tg_username);
        $this->assertNotNull($user->telegram_linked_at);
    }

    public function test_returns_same_user_for_same_telegram_id(): void
    {
        $a = $this->resolver()->resolveByChatId(['id' => 777, 'name' => 'A']);
        $b = $this->resolver()->resolveByChatId(['id' => '777', 'name' => 'A']);

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, User::where('telegram_chat_id', 777)->count());
    }

    public function test_adopts_legacy_user_with_null_provider(): void
    {
        $legacy = User::create(['name' => 'Old', 'oauth_provider' => null, 'oauth_id' => '999']);

        $resolved = $this->resolver()->resolveByChatId(['id' => 999, 'name' => 'Old']);

        $this->assertSame($legacy->id, $resolved->id);
        $this->assertSame('telegram', $resolved->fresh()->oauth_provider);
        // Легаси-запись получает chat_id, значит бот наконец может ей писать.
        $this->assertSame(999, $resolved->fresh()->telegram_chat_id);
    }

    public function test_oidc_sub_does_not_become_chat_id(): void
    {
        // Настоящая причина, по которой уведомления молча терялись: OIDC отдаёт
        // 20-значный псевдоним, а он попадал в поле, откуда бот брал chat_id.
        $user = $this->resolver()->resolveBySub([
            'sub' => '16549735672622918414', 'username' => 'palomig', 'name' => 'Стас',
        ]);

        $this->assertNull($user->telegram_chat_id);
        $this->assertSame('16549735672622918414', $user->telegram_oidc_sub);
    }

    public function test_same_sub_returns_same_user(): void
    {
        $a = $this->resolver()->resolveBySub(['sub' => '123456789012345678', 'name' => 'X']);
        $b = $this->resolver()->resolveBySub(['sub' => '123456789012345678', 'name' => 'X']);

        $this->assertSame($a->id, $b->id);
    }

    public function test_migrates_legacy_sub_stored_in_oauth_id(): void
    {
        $legacy = User::create([
            'name' => 'Old OIDC', 'oauth_provider' => 'telegram', 'oauth_id' => '8232516374096784273',
        ]);

        $resolved = $this->resolver()->resolveBySub(['sub' => '8232516374096784273', 'name' => 'Old OIDC']);

        $this->assertSame($legacy->id, $resolved->id);
        $this->assertSame('8232516374096784273', $resolved->fresh()->telegram_oidc_sub);
        $this->assertNull($resolved->fresh()->telegram_chat_id);
    }

    public function test_returning_from_telegram_clears_blocked_mark(): void
    {
        $user = $this->resolver()->resolveByChatId(['id' => 321, 'name' => 'B']);
        $user->update(['telegram_blocked_at' => now()]);

        $this->resolver()->resolveByChatId(['id' => 321, 'name' => 'B']);

        $this->assertNull($user->fresh()->telegram_blocked_at);
    }

    public function test_does_not_write_dropped_trial_ends_at_column(): void
    {
        $user = $this->resolver()->resolveByChatId(['id' => 111, 'name' => 'X']);
        // trial_ends_at дропнут миграцией #44 — не должно быть в атрибутах
        $this->assertArrayNotHasKey('trial_ends_at', $user->getAttributes());
    }
}
