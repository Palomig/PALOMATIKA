<?php

namespace Tests\Feature\Pwa;

use App\Models\StudentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentNotesProfileTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://teacher.palomatika.ru';

    private function teacher(): User
    {
        return User::create([
            'name' => 'T', 'email' => 't+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher',
        ]);
    }

    private function student(): User
    {
        return User::create([
            'name' => 'S', 'email' => 's+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'student',
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_profile_shows_own_notes_and_hides_foreign(): void
    {
        $teacher = $this->teacher();
        $other = $this->teacher();
        $student = $this->student();

        StudentNote::create([
            'student_id' => $student->id, 'teacher_id' => $teacher->id,
            'kind' => 'weakness', 'source' => 'chat',
            'topic_tag' => 'геометрия: подобие',
            'body' => 'МОЯ_ЗАПИСЬ путает признаки подобия',
        ]);

        StudentNote::create([
            'student_id' => $student->id, 'teacher_id' => $other->id,
            'kind' => 'general', 'source' => 'chat',
            'body' => 'ЧУЖАЯ_ЗАПИСЬ невидимая',
        ]);

        $this->actingAs($teacher)
            ->get(self::BASE . "/students/{$student->id}")
            ->assertOk()
            ->assertSee('МОЯ_ЗАПИСЬ путает признаки подобия')
            ->assertDontSee('ЧУЖАЯ_ЗАПИСЬ невидимая');
    }
}
