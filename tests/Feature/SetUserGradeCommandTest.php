<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetUserGradeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_moves_only_listed_students_and_keeps_letter(): void
    {
        $a = User::factory()->create(['role' => 'student', 'grade_num' => 9, 'grade_letter' => 'А']);
        $b = User::factory()->create(['role' => 'student', 'grade_num' => 9, 'grade_letter' => 'Д']);
        $other = User::factory()->create(['role' => 'student', 'grade_num' => 9]);

        $this->artisan('user:set-grade', ['ids' => [$a->id, $b->id], '--grade' => 10])->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $a->id, 'grade_num' => 10, 'grade_letter' => 'А']);
        $this->assertDatabaseHas('users', ['id' => $b->id, 'grade_num' => 10, 'grade_letter' => 'Д']);
        $this->assertDatabaseHas('users', ['id' => $other->id, 'grade_num' => 9]);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $a = User::factory()->create(['role' => 'student', 'grade_num' => 9]);

        $this->artisan('user:set-grade', ['ids' => [$a->id], '--grade' => 10, '--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $a->id, 'grade_num' => 9]);
    }

    public function test_refuses_teachers_and_unknown_ids_without_partial_writes(): void
    {
        $student = User::factory()->create(['role' => 'student', 'grade_num' => 9]);
        $teacher = User::factory()->create(['role' => 'teacher', 'grade_num' => 9]);

        $this->artisan('user:set-grade', ['ids' => [$student->id, $teacher->id], '--grade' => 10])->assertExitCode(1);
        $this->artisan('user:set-grade', ['ids' => [$student->id, 999999], '--grade' => 10])->assertExitCode(1);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'grade_num' => 9]);
    }
}
