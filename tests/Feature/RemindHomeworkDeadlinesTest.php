<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RemindHomeworkDeadlinesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.bot_token' => 'TESTTOKEN']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
    }

    private function tgStudent(string $tgId): User
    {
        return User::factory()->create([
            'role' => 'student', 'oauth_provider' => 'telegram', 'oauth_id' => $tgId,
            'telegram_chat_id' => (int) $tgId,
        ]);
    }

    private function homeworkFor(User $student, ?string $deadline, string $status = 'assigned'): HomeworkAssignment
    {
        $hw = Homework::create([
            'teacher_id' => User::factory()->create(['role' => 'teacher'])->id,
            'homework_type' => 'topic_photo_practice', 'title' => 'ДЗ',
            'tasks_count' => 2, 'topic_number' => 6, 'assigned_at' => now(),
            'deadline_at' => $deadline,
        ]);
        return HomeworkAssignment::create([
            'homework_id' => $hw->id, 'student_id' => $student->id,
            'status' => $status, 'tasks_total' => 2,
        ]);
    }

    public function test_reminds_incomplete_homework_due_tomorrow(): void
    {
        $a = $this->homeworkFor($this->tgStudent('880001'), now()->addDay()->format('Y-m-d'));

        $this->artisan('homework:remind-deadlines')->assertExitCode(0);

        Http::assertSent(fn ($r) => $r['chat_id'] === '880001' && str_contains($r['text'], 'апомин'));
        $this->assertNotNull($a->fresh()->reminded_at);
    }

    public function test_does_not_remind_completed_or_no_deadline(): void
    {
        $this->homeworkFor($this->tgStudent('880002'), now()->addDay()->format('Y-m-d'), 'completed');
        $this->homeworkFor($this->tgStudent('880003'), null); // без срока

        $this->artisan('homework:remind-deadlines')->assertExitCode(0);
        Http::assertNothingSent();
    }

    public function test_does_not_remind_twice_same_day(): void
    {
        $a = $this->homeworkFor($this->tgStudent('880004'), now()->addDay()->format('Y-m-d'));
        $a->update(['reminded_at' => now()]);

        $this->artisan('homework:remind-deadlines')->assertExitCode(0);
        Http::assertNothingSent();
    }

    public function test_does_not_remind_far_deadline(): void
    {
        $this->homeworkFor($this->tgStudent('880005'), now()->addDays(10)->format('Y-m-d'));

        $this->artisan('homework:remind-deadlines')->assertExitCode(0);
        Http::assertNothingSent();
    }
}
