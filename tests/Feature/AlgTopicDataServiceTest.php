<?php

namespace Tests\Feature;

use App\Services\AlgTaskDataService;
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
}
