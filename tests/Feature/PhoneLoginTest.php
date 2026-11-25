<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhoneLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_session_requires_a_call_before_cabinet_access(): void
    {
        config(['services.plusofon.id' => 'test-id', 'services.plusofon.key' => 'test-key']);
        \Illuminate\Support\Facades\Http::fake([
            'restapi.plusofon.ru/api/v1/flash-call/send' => \Illuminate\Support\Facades\Http::response([
                'success' => true, 'data' => ['pin' => '0012', 'key' => 'sample'],
            ]),
        ]);
        $user = User::factory()->create(['role' => 'parent']);
        $this->actingAs($user)->get('/parent/dashboard')->assertRedirect('/phone-login');
        $this->getJson('/parent/dashboard')->assertForbidden()->assertJsonPath('error', 'phone_verification_required');
        $this->get('/phone-login')->assertOk();
        $this->post('/phone-login', ['action' => 'start', 'phone' => '89000000001'])->assertRedirect('/phone-login');
        $this->post('/phone-login', ['code' => '0012'])->assertRedirect('https://'.config('app.base_domain').'/parent/dashboard');
        $this->assertDatabaseHas('phone_login_numbers', ['user_id' => $user->id, 'phone' => '79000000001']);
        $this->get('/parent/dashboard')->assertOk();
    }

    public function test_flash_call_opens_existing_account_and_consumes_challenge(): void
    {
        config(['services.plusofon.id' => 'example', 'services.plusofon.key' => 'example-key']);
        $user = User::factory()->create(['role' => 'student']);
        $challenge = bin2hex(random_bytes(24));
        $phone = '79000000001';
        DB::table('phone_login_numbers')->insert(['phone' => $phone, 'user_id' => $user->id]);
        DB::table('phone_login_codes')->insert([
            'id' => $challenge, 'phone' => $phone, 'actor' => 0, 'owner' => $user->id,
            'digest' => hash_hmac('sha256', $challenge.'0012', 'example-key'),
            'attempts' => 5, 'expires_at' => time() + 300,
        ]);
        $this->withSession(['phone_challenge' => $challenge])->get('/phone-login')
            ->assertOk()->assertSeeText('Последние 4 цифры');
        $this->post('/phone-login', ['code' => '1111'])->assertSessionHasErrors();
        $this->assertGuest();
        $this->post('/phone-login', ['code' => '0012'])->assertRedirect('https://student.'.config('app.base_domain').'/');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('phone_login_codes', ['id' => $challenge]);
    }
}
