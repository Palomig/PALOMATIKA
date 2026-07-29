<?php

namespace Tests\Unit;

use App\Services\FipiTaskTaxonomy;
use Tests\TestCase;

class FipiTopic16TaxonomyTest extends TestCase
{
    public function test_manifest_has_the_approved_structure_and_complete_guid_map(): void
    {
        $taxonomy = FipiTaskTaxonomy::forTopic('16');

        $this->assertNotNull($taxonomy);
        $manifest = $taxonomy->manifest();
        $blocks = $manifest['blocks'];
        $groups = array_merge(...array_column($blocks, 'groups'));
        $guids = array_merge(...array_column($groups, 'guids'));

        $this->assertSame(322, $manifest['expected_tasks']);
        $this->assertCount(4, $blocks);
        $this->assertCount(23, $groups);
        $this->assertSame(range(1, 23), array_column($groups, 'number'));
        $this->assertSame([
            'Углы в окружности',
            'Вписанные четырёхугольники',
            'Вписанная окружность',
            'Описанная окружность',
        ], array_column($blocks, 'title'));
        $this->assertSame([
            10, 20, 10, 1, 10,
            20, 10, 20,
            10, 10, 30, 20, 10, 10, 30, 10,
            10, 20, 20, 10, 9, 10, 12,
        ], array_column($groups, 'expected_tasks'));
        $this->assertCount(322, $guids);
        $this->assertCount(322, array_unique($guids));
    }

    public function test_representative_guids_belong_to_the_expected_solution_methods(): void
    {
        $taxonomy = FipiTaskTaxonomy::forTopic('16');
        $this->assertNotNull($taxonomy);
        $manifest = $taxonomy->manifest();
        $groups = array_merge(...array_column($manifest['blocks'], 'groups'));
        $groupByGuid = [];
        foreach ($groups as $group) {
            foreach ($group['guids'] as $guid) {
                $groupByGuid[$guid] = $group['number'];
            }
        }

        $representatives = [
            '30BE3935230285E14593CCC90F6519A7' => 1,
            '18A7C3C26EF5A0AE4FAED10E585F739A' => 2,
            '1F13093FD68AAD904DB7766535ECE6DB' => 3,
            'DCE5A1D70370825E43204564A5383F19' => 4,
            '012EC79834179BFC4D6F6979452F41CC' => 5,
            '12564C9A358D8DC64B45F80E04ABC6DF' => 6,
            '077F8E596CCAAD01414C98786389A0F0' => 7,
            '049FC206D0C8806D46815DACEAFFDF11' => 8,
            '17095742C5B8B3404706F87C22A7894E' => 9,
            '139E3662D457A81B48EAD139AF8B51A3' => 10,
            '11CB301F802791F94A3384BE5F38D43C' => 11,
            '23DB227D40FFBF0F4A51F13E4FE84C4E' => 12,
            '25A7319428AD8B3A4B94D0A3FA7D7D6B' => 13,
            '165BECD4A74B8062475A04DE05E85603' => 14,
            '00975F104F1792504C505CDEFABB3563' => 15,
            '069B8FD28C17A74A4ACD223A1633ABF8' => 16,
            '2A429F78B6C3962545318619DB1B1DD6' => 17,
            '0B80B2C8C07FBBC94CB143D33681209E' => 18,
            '0499284DFEC7A18C4E4AE8A46FD70842' => 19,
            '236D467964EEA8004A16223166243D6C' => 20,
            '11056D64E8ADB75140C2DEB8B88946A1' => 21,
            '0B53D727BE019ADE44CA53D9E9CDA8B0' => 22,
            '0F248BA082EB81FE4C8FEAA39EE579AE' => 23,
        ];

        foreach ($representatives as $guid => $groupNumber) {
            $this->assertSame($groupNumber, $groupByGuid[$guid] ?? null, $guid);
        }
    }
}
