<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MiniAppPart2PageTest extends TestCase
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
            $table->string('avatar')->nullable();
            $table->unsignedTinyInteger('grade_num')->nullable();
            $table->string('grade_letter', 5)->nullable();
            $table->string('school_number', 20)->nullable();
            $table->string('city', 80)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_part2_page_supports_topic_23_selection(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($student)
            ->get('/tg/part2?topic=23')
            ->assertOk()
            ->assertSee('Геометрия (вычисление)')
            ->assertSee('Биссектриса угла A параллелограмма');
    }
}
