<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhoneLoginTest extends TestCase
{
    use DatabaseTruncation;

    public function test_flash_call_opens_existing_account_and_consumes_challenge(): void
    {
        config(['services.ucaller.id' => 'example', 'services.ucaller.key' => 'example-key']);
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
