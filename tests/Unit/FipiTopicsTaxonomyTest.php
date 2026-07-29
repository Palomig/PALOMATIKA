<?php

namespace Tests\Unit;

use App\Services\FipiTaskTaxonomy;
use Tests\TestCase;

class FipiTopicsTaxonomyTest extends TestCase
{
    private const EXPECTED_TASKS = [
        '06' => 81,
        '07' => 171,
        '08' => 321,
        '09' => 111,
        '10' => 211,
        '11' => 101,
        '12' => 175,
        '13' => 141,
        '14' => 110,
        '15' => 252,
        '16' => 322,
        '17' => 316,
        '18' => 154,
        '19' => 150,
        '20' => 200,
        '21' => 190,
        '22' => 157,
        '23' => 172,
        '24' => 60,
        '25' => 130,
    ];

    public function test_every_topic_from_06_through_25_has_a_valid_manifest(): void
    {
        foreach (self::EXPECTED_TASKS as $topic => $expectedTasks) {
            $taxonomy = FipiTaskTaxonomy::forTopic($topic);

            $this->assertNotNull($taxonomy, "missing topic {$topic}");
            $taxonomy->validateManifest();

            $manifest = $taxonomy->manifest();
            $groups = array_merge(...array_column($manifest['blocks'], 'groups'));
            $guids = array_merge(...array_column($groups, 'guids'));

            $this->assertSame($expectedTasks, $manifest['expected_tasks'], $topic);
            $this->assertSame($manifest['expected_tasks'], count($guids), $topic);
            $this->assertCount(count($guids), array_unique($guids), $topic);
            $this->assertSame(range(1, count($groups)), array_column($groups, 'number'), $topic);
        }
    }
}
