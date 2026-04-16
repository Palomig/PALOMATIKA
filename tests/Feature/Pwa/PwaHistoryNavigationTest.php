<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PwaHistoryNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('oge_attempts');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('role')->default('student');
            $table->unsignedTinyInteger('grade_num')->nullable();
            $table->string('grade_letter')->nullable();
            $table->string('school_number')->nullable();
            $table->string('city')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('oge_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_grade_5_history_back_button_uses_vpr_home(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'grade_num' => 5,
            'grade_letter' => 'А',
            'school_number' => '1',
            'city' => 'Чехов',
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('http://student.palomatika.ru/history');

        $response->assertOk();
        $response->assertSee('href="' . route('pwa.student.vpr.home') . '"', false);
        $response->assertDontSee('href="' . route('pwa.student.dashboard') . '" class="back"', false);
    }
}
