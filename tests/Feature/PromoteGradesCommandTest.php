<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoteGradesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotes_grades_5_to_10_by_one(): void
    {
        foreach ([5, 6, 7, 8, 9, 10] as $grade) {
            User::factory()->create(['role' => 'student', 'grade_num' => $grade]);
        }

        $this->artisan('grades:promote')->assertExitCode(0);

        foreach ([6, 7, 8, 9, 10, 11] as $expected) {
            $this->assertDatabaseHas('users', ['grade_num' => $expected]);
        }
    }

    public function test_promotes_grade_11_to_12(): void
    {
        User::factory()->create(['role' => 'student', 'grade_num' => 11]);

        $this->artisan('grades:promote')->assertExitCode(0);

        $this->assertDatabaseHas('users', ['grade_num' => 12]);
    }

    public function test_does_not_change_teachers(): void
    {
        User::factory()->create(['role' => 'teacher', 'grade_num' => 9]);

        $this->artisan('grades:promote')->assertExitCode(0);

        $this->assertDatabaseHas('users', ['role' => 'teacher', 'grade_num' => 9]);
    }

    public function test_does_not_change_grade_12(): void
    {
        User::factory()->create(['role' => 'student', 'grade_num' => 12]);

        $this->artisan('grades:promote')->assertExitCode(0);

        $this->assertDatabaseHas('users', ['grade_num' => 12]);
        $this->assertDatabaseMissing('users', ['grade_num' => 13]);
    }
}
