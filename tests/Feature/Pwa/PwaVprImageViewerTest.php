<?php

namespace Tests\Feature\Pwa;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaVprImageViewerTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(int $grade = 5): User
    {
        return User::factory()->create([
            'role' => 'student',
            'grade_num' => $grade,
            'onboarding_completed_at' => now(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractSerializedTasks(string $html): array
    {
        $matched = preg_match('/tasks:\s*(\[[\s\S]*?\])\s*,\s*attemptId:/', $html, $matches);
        $this->assertSame(1, $matched, 'Could not extract serialized tasks payload from VPR test page.');

        $tasks = json_decode($matches[1], true);
        $this->assertIsArray($tasks, 'Serialized tasks payload must decode to an array.');

        return $tasks;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tasks
     * @return array<string, mixed>
     */
    private function findSerializedTaskByNumber(array $tasks, int $taskNumber): array
    {
        foreach ($tasks as $task) {
            if ((int) ($task['task_number'] ?? 0) === $taskNumber) {
                return $task;
            }
        }

        $this->fail("Could not find serialized task #{$taskNumber} in VPR payload.");
    }

    public function test_vpr_topic_06_number_line_task_uses_landscape_image_viewer(): void
    {
        $user = $this->makeStudent();

        $variant = OgeVariant::create([
            'hash' => 'vpr5numberline06',
            'exam_type' => OgeVariant::EXAM_VPR5,
            'title' => 'Вариант ВПР 5 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => ['tasks' => [[
                'task_number' => 6,
                'topic_id' => '06',
                'type' => 'word_problem',
                'image' => 'img-001.png',
                'text' => 'Посмотрите на числовой луч.',
            ]]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $user->id,
            'status' => 'active',
            'started_at' => now()->subMinute(),
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get("http://student.palomatika.ru/vpr/test/{$attempt->id}");

        $response->assertOk();
        $tasks = $this->extractSerializedTasks($response->getContent());
        $taskSix = $this->findSerializedTaskByNumber($tasks, 6);

        $this->assertFalse($taskSix['viewer_disabled'] ?? true);
        $this->assertSame('landscape', $taskSix['viewer_orientation'] ?? null);
    }
}
