<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Правка ответа обязана попасть и в колонку, и в payload: интерфейс
 * собирает задачу из payload, и правка одной колонки была бы невидимой.
 */
class FixTaskAnswerCommandTest extends TestCase
{
    use RefreshDatabase;

    private function task(string $guid, string $answer): Task
    {
        $group = TaskGroup::create([
            'bank' => 'ege', 'grade' => null, 'topic' => '01',
            'block_number' => 1, 'block_title' => 'ФИПИ', 'zadanie_number' => 2,
            'position' => 0, 'instruction' => '', 'type' => 'fipi',
            'payload' => ['type' => 'fipi', 'status' => 'production'],
            'status' => 'production', 'source' => 'fipi',
        ]);

        return Task::create([
            'task_group_id' => $group->id, 'position' => 0, 'type' => 'fipi',
            'payload' => ['id' => 7, 'html' => '<p>Условие</p>', 'answer' => $answer, 'answer_src' => 'codex', 'status' => 'production'],
            'answer' => $answer, 'answer_src' => 'codex', 'status' => 'production',
            'source' => 'fipi', 'fipi_guid' => $guid,
        ]);
    }

    public function test_fixes_answer_in_column_and_payload(): void
    {
        $task = $this->task(str_pad('a1', 32, 'b'), '84');

        $this->artisan('tasks:fix-answer', ['--guid' => $task->fipi_guid, '--answer' => '67', '--src' => 'claude'])
            ->assertExitCode(0);

        $task->refresh();
        $this->assertSame('67', $task->answer);
        $this->assertSame('67', $task->payload['answer']);
        $this->assertSame('claude', $task->answer_src);
        $this->assertSame('claude', $task->payload['answer_src']);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $task = $this->task(str_pad('c3', 32, 'd'), '84');

        $this->artisan('tasks:fix-answer', ['--guid' => $task->fipi_guid, '--answer' => '67', '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame('84', $task->fresh()->answer);
    }

    public function test_unknown_guid_fails(): void
    {
        $this->artisan('tasks:fix-answer', ['--guid' => str_pad('e5', 32, 'f'), '--answer' => '1'])
            ->assertExitCode(1);
    }
}
