<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TelegramWebAppAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('services.telegram.bot_token', '123456:TEST_BOT_TOKEN');
        config()->set('services.telegram.bot_username', 'palomatika_test_bot');

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('oauth_provider')->nullable();
            $table->string('oauth_id')->nullable();
            $table->string('avatar')->nullable();
            $table->string('role')->default('student');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('telegram_auth_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('telegram_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->text('photo_url')->nullable();
            $table->enum('status', ['pending', 'authenticated', 'used'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function test_valid_init_data_logs_in_existing_telegram_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Existing TG User',
            'email' => null,
            'oauth_provider' => 'telegram',
            'oauth_id' => '987654321',
        ]);

        $initData = $this->makeSignedInitData([
            'auth_date' => (string) now()->timestamp,
            'query_id' => 'AAHdF6IQAAAAAN0XohDhrOrc',
            'user' => [
                'id' => 987654321,
                'first_name' => 'Ivan',
                'last_name' => 'Petrov',
                'username' => 'ivanpetrov',
            ],
        ]);

        $response = $this->postJson('/api/auth/telegram/webapp-login', [
            'initData' => $initData,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect_to', url('/dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::where('oauth_provider', 'telegram')->where('oauth_id', '987654321')->count());
    }

    public function test_valid_init_data_creates_user_when_missing(): void
    {
        $initData = $this->makeSignedInitData([
            'auth_date' => (string) now()->timestamp,
            'query_id' => 'AAHdF6IQAAAAAN0XohDhrOrd',
            'user' => [
                'id' => 1122334455,
                'first_name' => 'Anna',
                'last_name' => 'Smirnova',
                'username' => 'anna_s',
                'photo_url' => 'https://t.me/i/userpic/320/anna.jpg',
            ],
        ]);

        $response = $this->postJson('/api/auth/telegram/webapp-login', [
            'initData' => $initData,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertAuthenticated();

        $createdUser = User::where('oauth_provider', 'telegram')
            ->where('oauth_id', '1122334455')
            ->first();

        $this->assertNotNull($createdUser);
        $this->assertSame('Anna Smirnova', $createdUser->name);
        $this->assertSame('https://t.me/i/userpic/320/anna.jpg', $createdUser->avatar);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $initData = $this->makeSignedInitData([
            'auth_date' => (string) now()->timestamp,
            'query_id' => 'AAHdF6IQAAAAAN0XohDhrOre',
            'user' => [
                'id' => 123123123,
                'first_name' => 'Bad',
            ],
        ]);

        $tampered = preg_replace('/hash=[^&]+$/', 'hash=invalidhash', $initData) ?? ($initData . '&hash=invalidhash');

        $response = $this->postJson('/api/auth/telegram/webapp-login', [
            'initData' => $tampered,
        ]);

        $response
            ->assertStatus(401)
            ->assertJsonPath('success', false);

        $this->assertGuest();
    }

    public function test_generate_token_fallback_flow_is_unaffected(): void
    {
        $response = $this->postJson('/api/telegram/generate-token');

        $response
            ->assertOk()
            ->assertJsonStructure(['token', 'deep_link', 'expires_in'])
            ->assertJsonPath('expires_in', 300);

        $this->assertStringStartsWith('https://t.me/palomatika_test_bot?start=', (string) $response->json('deep_link'));
    }

    private function makeSignedInitData(array $payload): string
    {
        $normalized = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                continue;
            }

            $normalized[$key] = (string) $value;
        }

        ksort($normalized);

        $dataCheckString = collect($normalized)
            ->map(fn (string $value, string $key) => "{$key}={$value}")
            ->implode("\n");

        $secretKey = hash_hmac('sha256', (string) config('services.telegram.bot_token'), 'WebAppData', true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        return http_build_query([...$normalized, 'hash' => $hash], '', '&', PHP_QUERY_RFC3986);
    }
}
