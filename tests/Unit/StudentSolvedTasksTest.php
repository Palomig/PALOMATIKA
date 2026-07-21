<?php

namespace Tests\Unit;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Models\User;
use App\Support\StudentSolvedTasks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentSolvedTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_maps_tasks_from_attempted_variants_by_topic_and_exam_type(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $oge = OgeVariant::create([
            'hash' => 'solved1', 'exam_type' => 'oge', 'title' => 'v', 'source' => 'miniapp',
            'config_json' => ['tasks' => [
                ['topic_id' => '06', 'task' => ['id' => 5]],
                ['topic_id' => '6', 'task_id' => 7],          // без ведущего нуля + плоский id
                ['topic_id' => '15', 'id' => 3],              // формат билдеров ЕГЭ/ВПР
                ['topic_id' => '19', 'task_id' => 0],         // мусор — игнорируется
            ]],
        ]);
        $ege = OgeVariant::create([
            'hash' => 'solved2', 'exam_type' => 'ege', 'title' => 'v', 'source' => 'miniapp',
            'config_json' => ['tasks' => [['topic_id' => '01', 'id' => 9]]],
        ]);
        // Вариант без попытки — не считается решённым.
        OgeVariant::create([
            'hash' => 'solved3', 'exam_type' => 'oge', 'title' => 'v', 'source' => 'miniapp',
            'config_json' => ['tasks' => [['topic_id' => '07', 'task' => ['id' => 1]]]],
        ]);

        foreach ([$oge, $ege] as $v) {
            OgeAttempt::create([
                'variant_id' => $v->id, 'student_id' => $student->id,
                'status' => 'scored', 'started_at' => now(),
            ]);
        }

        $map = StudentSolvedTasks::mapByTopic($student, 'oge');

        $this->assertSame([5, 7], $map['06']);
        $this->assertSame([3], $map['15']);
        $this->assertArrayNotHasKey('19', $map);
        $this->assertArrayNotHasKey('07', $map);
        $this->assertArrayNotHasKey('01', $map); // ЕГЭ не попадает в карту ОГЭ

        $this->assertSame([9], StudentSolvedTasks::mapByTopic($student, 'ege')['01']);
    }
}
