<?php
namespace Tests\Unit;

use App\Http\Controllers\VprTopicController;
use App\Services\VprTaskDataService;
use Tests\TestCase;

class VprTaskDataServiceTest extends TestCase
{
    public function test_base_path_resolves_by_grade(): void
    {
        $svc5 = new VprTaskDataService(5);
        $svc8 = new VprTaskDataService(8);

        // Grade-specific paths resolve correctly — non-existent topic returns false
        $this->assertFalse($svc5->topicDataExists('99'));
        $this->assertFalse($svc8->topicDataExists('99'));
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

    public function test_grade_5_topic_01_uses_fraction_training_meta_and_expression_tasks(): void
    {
        $svc = new VprTaskDataService(5);

        $meta = $svc->getTopicMeta('01');
        $data = $svc->getTopicData('01');

        $this->assertSame('Обыкновенные дроби', $meta['title'] ?? null);
        $this->assertStringContainsString('дроб', mb_strtolower((string) ($meta['description'] ?? '')));

        $zadaniya = $data['blocks'][0]['zadaniya'] ?? [];
        $this->assertNotEmpty($zadaniya);
        $this->assertSame('expression', $zadaniya[0]['type'] ?? null);
        $this->assertGreaterThanOrEqual(20, count($zadaniya[0]['tasks'] ?? []));
        $this->assertArrayHasKey('expression', $zadaniya[0]['tasks'][0] ?? []);
    }

    public function test_grade_5_topic_01_renders_number_wording_for_denominator_tasks(): void
    {
        $controller = $this->app->make(VprTopicController::class);
        $response = $controller->show(5, '1');
        $html = $response->render();

        $this->assertStringContainsString('Представьте число $4$ в виде дроби со знаменателем 6.', $html);
        $this->assertStringNotContainsString('Представьте выражение $4$ в виде дроби со знаменателем 6.', $html);
    }

    public function test_grade_5_topic_02_uses_parts_and_whole_training_structure(): void
    {
        $svc = new VprTaskDataService(5);

        $meta = $svc->getTopicMeta('02');
        $data = $svc->getTopicData('02');

        $this->assertSame('Часть и целое', $meta['title'] ?? null);
        $this->assertStringContainsString('част', mb_strtolower((string) ($meta['description'] ?? '')));

        $zadaniya = $data['blocks'][0]['zadaniya'] ?? [];
        $this->assertCount(3, $zadaniya);
        $this->assertSame('word_problem', $zadaniya[0]['type'] ?? null);
        $this->assertSame('word_problem', $zadaniya[1]['type'] ?? null);
        $this->assertSame('word_problem', $zadaniya[2]['type'] ?? null);
        $this->assertGreaterThanOrEqual(8, count($zadaniya[0]['tasks'] ?? []));
        $this->assertGreaterThanOrEqual(8, count($zadaniya[1]['tasks'] ?? []));
        $this->assertGreaterThanOrEqual(8, count($zadaniya[2]['tasks'] ?? []));
    }
}
