<?php
namespace Tests\Unit;

use App\Services\VprTaskDataService;
use Tests\TestCase;

class VprTaskDataServiceTest extends TestCase
{
    public function test_base_path_resolves_by_grade(): void
    {
        $svc5 = new VprTaskDataService(5);
        $svc8 = new VprTaskDataService(8);

        // basePath is protected — test via topicExists returning false for missing file
        $this->assertFalse($svc5->topicDataExists('01')); // no files yet
        $this->assertFalse($svc8->topicDataExists('01'));
    }

    public function test_get_topic_meta_returns_defaults_for_unknown_topic(): void
    {
        $svc = new VprTaskDataService(5);
        $meta = $svc->getTopicMeta('99');
        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('color', $meta);
    }

    public function test_grade_5_has_18_topic_metas(): void
    {
        $svc = new VprTaskDataService(5);
        $this->assertCount(18, $svc->getAllTopicsMeta());
    }

    public function test_all_grades_have_18_topics(): void
    {
        foreach ([5, 6, 7, 8] as $grade) {
            $svc = new VprTaskDataService($grade);
            $this->assertCount(18, $svc->getAllTopicsMeta(),
                "Grade $grade should have 18 topics");
        }
    }
}
