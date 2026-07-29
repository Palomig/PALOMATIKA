<?php

namespace Tests\Unit;

use App\Services\FipiTaskTaxonomy;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FipiTaskTaxonomyTest extends TestCase
{
    public function test_it_groups_tasks_in_manifest_and_source_order(): void
    {
        $taxonomy = new FipiTaskTaxonomy($this->manifest());

        $groups = $taxonomy->group([
            ['guid' => 'BBB', 'order' => [1]],
            ['guid' => 'AAA', 'order' => [0]],
        ]);

        $this->assertSame('Углы в окружности', $groups[0]['block_title']);
        $this->assertSame('central-angle', $groups[0]['key']);
        $this->assertSame(['AAA', 'BBB'], array_column($groups[0]['items'], 'guid'));
    }

    public function test_it_rejects_a_guid_repeated_in_the_manifest(): void
    {
        $manifest = $this->manifest();
        $manifest['blocks'][0]['groups'][] = [
            'key' => 'duplicate',
            'number' => 2,
            'title' => 'Дубликат',
            'expected_tasks' => 1,
            'guids' => ['AAA'],
        ];
        $manifest['expected_tasks'] = 3;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AAA');

        (new FipiTaskTaxonomy($manifest))->group([
            ['guid' => 'AAA'],
            ['guid' => 'BBB'],
        ]);
    }

    public function test_it_rejects_an_unclassified_source_guid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CCC');

        (new FipiTaskTaxonomy($this->manifest()))->group([
            ['guid' => 'AAA'],
            ['guid' => 'BBB'],
            ['guid' => 'CCC'],
        ]);
    }

    public function test_it_rejects_a_manifest_guid_missing_from_source(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('BBB');

        (new FipiTaskTaxonomy($this->manifest()))->group([
            ['guid' => 'AAA'],
        ]);
    }

    public function test_it_rejects_a_wrong_group_count(): void
    {
        $manifest = $this->manifest();
        $manifest['blocks'][0]['groups'][0]['expected_tasks'] = 3;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('central-angle');

        (new FipiTaskTaxonomy($manifest))->group([
            ['guid' => 'AAA'],
            ['guid' => 'BBB'],
        ]);
    }

    public function test_it_rejects_a_wrong_total(): void
    {
        $manifest = $this->manifest();
        $manifest['expected_tasks'] = 3;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('16');

        (new FipiTaskTaxonomy($manifest))->group([
            ['guid' => 'AAA'],
            ['guid' => 'BBB'],
        ]);
    }

    /** @return array<string, mixed> */
    private function manifest(): array
    {
        return [
            'topic' => '16',
            'expected_tasks' => 2,
            'blocks' => [[
                'number' => 1,
                'title' => 'Углы в окружности',
                'groups' => [[
                    'key' => 'central-angle',
                    'number' => 1,
                    'title' => 'Центральный и вписанный углы',
                    'expected_tasks' => 2,
                    'guids' => ['AAA', 'BBB'],
                ]],
            ]],
        ];
    }
}
