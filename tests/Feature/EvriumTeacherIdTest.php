<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvriumTeacherIdTest extends TestCase
{
    use RefreshDatabase;

    private function student(string $email): User
    {
        return User::create(['name' => 'U', 'email' => $email, 'password' => 'x', 'role' => 'student']);
    }

    public function test_promote_assigns_sequential_evrium_teacher_id(): void
    {
        // Относительно текущего max — в свежей БД миграции могут создавать служебный
        // teacher-аккаунт (system-teacher@palomatika.local), который тоже занимает номер.
        $base = (int) (User::max('evrium_teacher_id') ?? 0);

        $first  = $this->student('a@t.t');
        $second = $this->student('b@t.t');

        $this->artisan('user:promote-teacher', ['identifier' => 'a@t.t'])->assertSuccessful();
        $this->artisan('user:promote-teacher', ['identifier' => 'b@t.t'])->assertSuccessful();

        $this->assertSame($base + 1, $first->fresh()->evrium_teacher_id);
        $this->assertSame($base + 2, $second->fresh()->evrium_teacher_id);
    }

    public function test_promote_does_not_overwrite_existing_evrium_id(): void
    {
        $user = $this->student('c@t.t');
        $user->update(['evrium_teacher_id' => 99]);

        $this->artisan('user:promote-teacher', ['identifier' => 'c@t.t'])->assertSuccessful();

        $this->assertSame(99, $user->fresh()->evrium_teacher_id);
        $this->assertSame('teacher', $user->fresh()->role);
    }

    public function test_next_evrium_teacher_id_is_max_plus_one(): void
    {
        $base = (int) (User::max('evrium_teacher_id') ?? 0);
        $this->assertSame($base + 1, User::nextEvriumTeacherId());

        $this->student('d@t.t')->update(['evrium_teacher_id' => $base + 5]);
        $this->assertSame($base + 6, User::nextEvriumTeacherId());
    }
}
