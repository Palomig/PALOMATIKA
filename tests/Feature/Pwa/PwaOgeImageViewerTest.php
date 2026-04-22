<?php

namespace Tests\Feature\Pwa;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaOgeImageViewerTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(int $grade = 9): User
    {
        return User::factory()->create([
            'role' => 'student',
            'grade_num' => $grade,
            'grade_letter' => 'А',
            'school_number' => '1',
            'city' => 'Чехов',
            'onboarding_completed_at' => now(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractSerializedTasks(string $html): array
    {
        $matched = preg_match('/tasks:\s*(\[[\s\S]*?\])\s*,\s*attemptId:/', $html, $matches);
        $this->assertSame(1, $matched, 'Could not extract serialized tasks payload from student test page.');

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

        $this->fail("Could not find serialized task #{$taskNumber} in student payload.");
    }

    public function test_oge_topic_11_graph_task_disables_image_viewer_in_payload(): void
    {
        $student = $this->makeStudent();
        $variant = OgeVariant::create([
            'hash' => 'ogegraphviewer11',
            'exam_type' => OgeVariant::EXAM_OGE,
            'title' => 'ОГЭ графики',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => [
                'tasks' => [[
                    'task_number' => 11,
                    'topic_id' => '11',
                    'type' => 'matching',
                    'image' => 'img-001.png',
                    'text' => 'График функции',
                ]],
            ],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'started_at' => now()->subMinutes(2),
            'last_seen_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($student)
            ->get("http://student.palomatika.ru/test/{$attempt->id}");

        $response->assertOk();
        $tasks = $this->extractSerializedTasks($response->getContent());
        $task = $this->findSerializedTaskByNumber($tasks, 11);

        $this->assertTrue($task['viewer_disabled'] ?? false);
        $this->assertSame('default', $task['viewer_orientation'] ?? null);
    }

    public function test_oge_topic_07_number_line_task_uses_landscape_image_viewer(): void
    {
        $student = $this->makeStudent();
        $variant = OgeVariant::create([
            'hash' => 'ogenumberline07',
            'exam_type' => OgeVariant::EXAM_OGE,
            'title' => 'ОГЭ числовой луч',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => [
                'tasks' => [[
                    'task_number' => 7,
                    'topic_id' => '07',
                    'type' => 'choice',
                    'image' => 'img-010.png',
                    'text' => 'Какому промежутку принадлежит значение?',
                ]],
            ],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'started_at' => now()->subMinutes(2),
            'last_seen_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($student)
            ->get("http://student.palomatika.ru/test/{$attempt->id}");

        $response->assertOk();
        $tasks = $this->extractSerializedTasks($response->getContent());
        $task = $this->findSerializedTaskByNumber($tasks, 7);

        $this->assertFalse($task['viewer_disabled'] ?? true);
        $this->assertSame('landscape', $task['viewer_orientation'] ?? null);
    }
}
