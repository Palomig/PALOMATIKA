<?php

namespace Tests\Feature;

use App\Services\AlgTaskDataService;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AlgTopicDataServiceTest extends TestCase
{
    public function test_grade_7_algebra_topic_exposes_curriculum_fields(): void
    {
        Cache::flush();

        $service = new AlgTaskDataService(7);

        $data = $service->getTopicData('01');

        $this->assertSame('01', $data['topic_id']);
        $this->assertArrayHasKey('curriculum', $data);
        $this->assertArrayHasKey('micro_skills', $data);
        $this->assertArrayHasKey('homework_sets', $data);
        $this->assertNotEmpty($data['homework_sets'][0]['tasks']);
    }

    public function test_teacher_sees_curriculum_summary_on_grade_7_topic_page(): void
    {
        Cache::flush();

        $teacher = User::factory()->make(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->get('/alg-topics/7/1')
            ->assertOk()
            ->assertSee('Главная идея')
            ->assertSee('Скобки - это группа')
            ->assertSee('Микронавыки')
            ->assertSee('Скобки как группа')
            ->assertSee('Домашние работы')
            ->assertSee('Раскрытие скобок: смысл и базовая техника');
    }

    public function test_grade_7_algebra_has_separate_arithmetic_base_topic(): void
    {
        Cache::flush();

        $service = new AlgTaskDataService(7);

        $this->assertContains('00', $service->getExistingTopicIds());

        $data = $service->getTopicData('00');

        $this->assertSame('00', $data['topic_id']);
        $this->assertSame('Арифметическая база перед алгеброй', $data['meta']['title']);
        $this->assertSame('arithmetic_foundation', $data['meta']['strand']);
        $this->assertArrayHasKey('curriculum', $data);
        $this->assertCount(7, $data['micro_skills']);
        $this->assertNotEmpty($data['homework_sets'][0]['tasks']);
        $this->assertFileDoesNotExist(storage_path('app/tasks/topic_00.json'));
        $this->assertFileDoesNotExist(storage_path('app/tasks/vpr/grade_7/topic_00.json'));
    }

    public function test_teacher_can_open_grade_7_arithmetic_base_topic_page(): void
    {
        Cache::flush();

        $teacher = User::factory()->make(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->get('/alg-topics/7/0')
            ->assertOk()
            ->assertSee('Арифметическая база перед алгеброй')
            ->assertSee('Знаки и отрицательные числа')
            ->assertSee('-8 + 6')
            ->assertSee('Домашние работы');
    }
}
