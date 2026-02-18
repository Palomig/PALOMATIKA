<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginIntendedRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('role')->default('student');
            $table->timestamps();
        });
    }

    public function test_guest_opening_oge_variant_gets_redirected_back_after_login(): void
    {
        User::factory()->create([
            'email' => 'student.oge@example.com',
            'password' => 'StrongPass123',
            'role' => 'student',
        ]);

        $variantPath = '/oge/abc123';

        $guest = $this->get($variantPath);
        $guest->assertRedirect('/login');

        $login = $this->postJson('/login', [
            'email' => 'student.oge@example.com',
            'password' => 'StrongPass123',
            'remember' => true,
        ]);

        $login->assertOk();
        $login->assertJsonPath('success', true);
        $login->assertJsonPath('redirect_to', url($variantPath));
    }
}
