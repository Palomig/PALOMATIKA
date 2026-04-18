<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MiniAppAuthBridgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        Config::set('services.telegram.bot_token', '123456:TEST_BOT_TOKEN');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('role', 32)->default('student');
            $table->string('oauth_provider')->nullable();
            $table->string('oauth_id')->nullable();
            $table->string('tg_username', 100)->nullable();
            $table->string('avatar')->nullable();
            $table->unsignedTinyInteger('grade_num')->nullable();
            $table->string('grade_letter', 5)->nullable();
            $table->string('school_number', 20)->nullable();
            $table->string('city', 80)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->unsignedBigInteger('referred_by_user_id')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('tg_trial_used')->default(false);
            $table->timestamp('tg_premium_until')->nullable();
            $table->integer('star_balance')->default(0);
            $table->timestamps();
        });
    }

    // --- Auth Bridge: Happy Path ---

    public function test_auth_continue_restores_session_from_handoff_token(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
        ]);

        $token = 'test_handoff_token_abc';
        Cache::put('tg_auth_handoff:' . $token, [
            'user_id' => $user->id,
            'redirect_to' => 'https://student.palomatika.ru/',
        ], now()->addMinutes(2));

        $response = $this->get('/tg/auth/continue?token=' . $token);

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    // --- Auth Bridge: Session Loss ---

    public function test_auth_continue_without_token_and_no_session_redirects_to_home(): void
    {
        $response = $this->get('/tg/auth/continue');

        $response->assertRedirect('/tg');
    }

    public function test_auth_continue_with_expired_token_and_no_session_redirects_to_home(): void
    {
        $response = $this->get('/tg/auth/continue?token=expired_token_xyz');

        $response->assertRedirect('/tg');
    }

    public function test_auth_continue_with_valid_session_works_without_token(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/tg/auth/continue');

        $response->assertOk();
    }

    // --- Onboarding Token Fallback ---

    public function test_auth_continue_forwards_onboarding_target_to_pwa_bridge(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $pwaTarget = 'https://student.palomatika.ru/';
        $token = 'test_handoff_onboarding';
        Cache::put('tg_auth_handoff:' . $token, [
            'user_id' => $user->id,
            'redirect_to' => $pwaTarget,
        ], now()->addMinutes(2));

        $response = $this->get('/tg/auth/continue?token=' . $token);

        $response->assertOk();
        $response->assertViewIs('miniapp.auth-bridge-final');
        $response->assertViewHas('target', $pwaTarget);
        $this->assertAuthenticatedAs($user);
    }

    public function test_save_onboarding_with_token_fallback_when_session_lost(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $onbToken = 'test_onb_token_123';
        Cache::put('tg_onb_token:' . $onbToken, [
            'user_id' => $user->id,
        ], now()->addMinutes(20));

        $response = $this->postJson('/tg/onboarding', [
            'name' => 'Тест Ученик',
            'grade_num' => 9,
            'grade_letter' => 'А',
            'school_number' => '42',
            'city' => 'Москва',
            'onboarding_token' => $onbToken,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $user->refresh();
        $this->assertSame('Тест Ученик', $user->name);
        $this->assertNotNull($user->onboarding_completed_at);
    }

    public function test_save_onboarding_without_session_and_without_token_returns_401(): void
    {
        $response = $this->postJson('/tg/onboarding', [
            'name' => 'Тест',
            'grade_num' => 9,
            'grade_letter' => 'А',
            'school_number' => '42',
        ]);

        $response->assertStatus(401);
    }

    // --- Home Redirect Invariants ---

    public function test_home_redirects_authed_student_to_pwa_student_subdomain(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/tg');

        $response->assertRedirect('https://student.palomatika.ru/');
    }

    public function test_home_redirects_authed_teacher_to_pwa_teacher_subdomain(): void
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/tg');

        $response->assertRedirect('https://teacher.palomatika.ru/dashboard');
    }

    public function test_home_redirects_authed_user_without_onboarding_to_pwa_landing(): void
    {
        // PWA subdomain's own onboarding middleware redirects non-onboarded users to /onboarding.
        $user = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/tg');

        $response->assertRedirect('https://student.palomatika.ru/');
    }

    public function test_home_forwards_variant_hash_startapp_to_pwa(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/tg?startapp=oge_variant_hash_abc12345');

        $response->assertRedirect('https://student.palomatika.ru/?startapp=oge_variant_hash_abc12345');
    }

    // --- Bridge Ping ---

    public function test_auth_bridge_ping_returns_ok(): void
    {
        $response = $this->postJson('/tg/auth/bridge-ping');

        $response->assertOk()->assertJsonPath('ok', true);
    }
}
