<?php

namespace Tests\Feature;

use App\Models\StudentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentNoteControllerTest extends TestCase
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

    private function note(User $teacher, User $student, array $attrs = []): StudentNote
    {
        return StudentNote::create(array_merge([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'kind'       => 'general',
            'source'     => 'chat',
            'body'       => 'исходный текст',
        ], $attrs));
    }

    public function test_update_changes_own_note(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $note = $this->note($teacher, $student);

        $this->actingAs($teacher)
            ->patchJson(self::BASE . "/student-notes/{$note->id}", [
                'body'      => 'новый текст',
                'kind'      => 'weakness',
                'topic_tag' => 'геометрия: подобие',
            ])
            ->assertOk()
            ->assertJsonPath('note.body', 'новый текст')
            ->assertJsonPath('note.kind', 'weakness')
            ->assertJsonPath('note.topic_tag', 'геометрия: подобие');

        $this->assertDatabaseHas('student_notes', [
            'id'        => $note->id,
            'body'      => 'новый текст',
            'kind'      => 'weakness',
            'topic_tag' => 'геометрия: подобие',
        ]);
    }

    public function test_destroy_deletes_own_note(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $note = $this->note($teacher, $student);

        $this->actingAs($teacher)
            ->deleteJson(self::BASE . "/student-notes/{$note->id}")
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('student_notes', ['id' => $note->id]);
    }

    public function test_update_foreign_note_404(): void
    {
        $owner = $this->teacher();
        $intruder = $this->teacher();
        $student = $this->student();
        $note = $this->note($owner, $student);

        $this->actingAs($intruder)
            ->patchJson(self::BASE . "/student-notes/{$note->id}", ['body' => 'взлом'])
            ->assertNotFound();

        $this->assertDatabaseHas('student_notes', ['id' => $note->id, 'body' => 'исходный текст']);
    }

    public function test_destroy_foreign_note_404(): void
    {
        $owner = $this->teacher();
        $intruder = $this->teacher();
        $student = $this->student();
        $note = $this->note($owner, $student);

        $this->actingAs($intruder)
            ->deleteJson(self::BASE . "/student-notes/{$note->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('student_notes', ['id' => $note->id]);
    }
}
