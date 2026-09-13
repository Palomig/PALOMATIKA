<?php

namespace Tests\Feature;

use App\Models\LessonSession;
use App\Models\LessonSessionTask;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * `4^{\dfrac{1}{5}}` KaTeX рисовал дробью в полный рост рядом с четвёркой —
 * на уроке не было видно, что это степень. В показателях и индексах дробь
 * должна быть `\frac`; дроби в основной строке остаются `\dfrac`.
 */
class FixScriptDfracCommandTest extends TestCase
{
    use RefreshDatabase;

    private const HTML = '<p>Найдите значение выражения $4^{\dfrac{1}{5}}\cdot{16}^{\dfrac{9}{10}}$ и $\dfrac{1}{2^{x+\dfrac{1}{2}}}$.</p>';
    private const FIXED = '<p>Найдите значение выражения $4^{\frac{1}{5}}\cdot{16}^{\frac{9}{10}}$ и $\dfrac{1}{2^{x+\frac{1}{2}}}$.</p>';

    private function bankTask(string $html): Task
    {
        $group = TaskGroup::create([
            'bank' => 'ege', 'grade' => null, 'topic' => '08',
            'block_number' => 1, 'block_title' => 'ФИПИ', 'zadanie_number' => 4,
            'position' => 0, 'instruction' => '', 'type' => 'fipi',
            'payload' => ['type' => 'fipi', 'status' => 'production'],
            'status' => 'production', 'source' => 'fipi',
        ]);

        return Task::create([
            'task_group_id' => $group->id, 'position' => 0, 'type' => 'fipi',
            'payload' => ['id' => 1, 'html' => $html, 'answer' => '16', 'status' => 'production'],
            'answer' => '16', 'answer_src' => 'claude', 'status' => 'production',
            'source' => 'fipi', 'fipi_guid' => str_repeat('a', 32),
        ]);
    }

    public function test_fractions_in_exponents_become_frac_in_bank_and_lesson_snapshot(): void
    {
        $task = $this->bankTask(self::HTML);
        $teacher = User::create(['name' => 't', 'email' => 't@t.t', 'password' => 'x', 'role' => 'teacher']);
        $session = LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'draft', 'join_code' => '1234']);
        $lessonTask = LessonSessionTask::create([
            'lesson_session_id' => $session->id, 'position' => 1, 'bank' => 'ege',
            'topic_id' => '08', 'task_ref' => json_encode(['task_id' => 1]),
            'task_payload' => [
                'expression' => 'Найдите значение выражения $4^{\dfrac{1}{5}}$.',
                'condition_html' => self::HTML,
                'raw' => ['html' => self::HTML],
            ],
            'correct_answer' => '16',
        ]);
        Cache::put('ege_topic_data_08', ['stale' => true], 3600);

        $this->artisan('tasks:fix-script-dfrac')->assertSuccessful();

        $this->assertSame(self::FIXED, $task->fresh()->payload['html']);
        $snapshot = $lessonTask->fresh()->task_payload;
        $this->assertSame('Найдите значение выражения $4^{\frac{1}{5}}$.', $snapshot['expression']);
        $this->assertSame(self::FIXED, $snapshot['condition_html']);
        $this->assertSame(self::FIXED, $snapshot['raw']['html']);
        $this->assertNull(Cache::get('ege_topic_data_08'), 'кэш темы должен сброситься');
    }

    public function test_dry_run_changes_nothing_and_second_run_is_idempotent(): void
    {
        $task = $this->bankTask(self::HTML);

        $this->artisan('tasks:fix-script-dfrac', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(self::HTML, $task->fresh()->payload['html']);

        $this->artisan('tasks:fix-script-dfrac')->assertSuccessful();
        $this->artisan('tasks:fix-script-dfrac')
            ->expectsOutputToContain('банк: 0, задачи уроков: 0, задачи домашки: 0')
            ->assertSuccessful();
        $this->assertSame(self::FIXED, $task->fresh()->payload['html']);
    }

    public function test_plain_fractions_are_left_alone(): void
    {
        $task = $this->bankTask('<p>Вычислите $\dfrac{3}{4}+\dfrac{1}{4}$.</p>');

        $this->artisan('tasks:fix-script-dfrac')->assertSuccessful();

        $this->assertSame('<p>Вычислите $\dfrac{3}{4}+\dfrac{1}{4}$.</p>', $task->fresh()->payload['html']);
    }
}
