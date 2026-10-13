<?php

namespace Tests\Feature;

use App\Models\LessonSession;
use App\Models\LessonSessionTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Варианты ФИПИ «<p>$a-5<0$</p>» разбор разметки обрезал до «$a-5»: «<0$»
 * принимался за тег. Уже розданные снимки уроков чинит tasks:refresh-fipi-text.
 */
class RefreshFipiSnapshotTextTest extends TestCase
{
    use RefreshDatabase;

    private function lessonTask(array $payload): LessonSessionTask
    {
        $teacher = User::create(['name' => 't', 'email' => 't@t.t', 'password' => 'x', 'role' => 'teacher']);
        $session = LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'live', 'join_code' => '1234']);

        return LessonSessionTask::create([
            'lesson_session_id' => $session->id, 'position' => 1, 'bank' => 'oge',
            'topic_id' => '07', 'task_ref' => json_encode(['task_id' => 6]),
            'task_payload' => $payload, 'correct_answer' => '2',
        ]);
    }

    public function test_restores_truncated_option_labels_and_keeps_ids(): void
    {
        $raw = [
            'id' => 6, 'answer' => '2', 'fipi_guid' => str_repeat('b', 32),
            'html' => '<p>На координатной прямой отмечено число $a$. Какое из утверждений для этого числа является верным?</p>',
            'options' => [
                ['n' => 1, 'html' => '<p>$7-a>0$</p>'],
                ['n' => 2, 'html' => '<p>$a-5<0$</p>'],
            ],
        ];
        $task = $this->lessonTask([
            'type' => 'choice', 'answer' => '2', 'raw' => $raw,
            'expression' => 'На координатной прямой отмечено число $a$. Какое из утверждений для этого числа является верным?',
            'options' => [['id' => '1', 'label' => '$7-a>0$'], ['id' => '2', 'label' => '$a-5']],
        ]);

        $this->artisan('tasks:refresh-fipi-text', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame('$a-5', $task->fresh()->task_payload['options'][1]['label']);

        $this->artisan('tasks:refresh-fipi-text')->assertSuccessful();
        $this->assertSame(
            [['id' => '1', 'label' => '$7-a>0$'], ['id' => '2', 'label' => '$a-5<0$']],
            $task->fresh()->task_payload['options']
        );

        $this->artisan('tasks:refresh-fipi-text')
            ->expectsOutputToContain('Снимков с изменениями: 0')
            ->assertSuccessful();
    }
}
