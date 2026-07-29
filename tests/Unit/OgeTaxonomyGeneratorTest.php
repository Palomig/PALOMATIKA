<?php

namespace Tests\Unit;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class OgeTaxonomyGeneratorTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputDir = sys_get_temp_dir() . '/oge-taxonomy-' . bin2hex(random_bytes(5));
        mkdir($this->outputDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->outputDir . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->outputDir);
        parent::tearDown();
    }

    public function test_it_generates_curriculum_order_and_stable_source_order(): void
    {
        $process = $this->runGenerator(
            base_path('tests/fixtures/fipi-taxonomy-small.json'),
            base_path('tests/fixtures/fipi-taxonomy-small-curriculum.php'),
            '06-07',
        );

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $topic06 = require $this->outputDir . '/oge-topic-06.php';
        $groups = array_merge(...array_column($topic06['blocks'], 'groups'));

        $this->assertSame(['common-fractions', 'decimal-fractions'], array_column($groups, 'key'));
        $this->assertSame(['A-1', 'A-2'], $groups[0]['guids']);
        $this->assertSame(3, $topic06['expected_tasks']);
        $this->assertStringContainsString('ИТОГО: тем 2, задач 4', $process->getOutput());
        $this->assertDoesNotMatchRegularExpression(
            '/[ \t]+$/m',
            (string) file_get_contents($this->outputDir . '/oge-topic-06.php'),
        );
    }

    public function test_it_rejects_a_source_subtype_missing_from_curriculum(): void
    {
        $curriculum = require base_path('tests/fixtures/fipi-taxonomy-small-curriculum.php');
        array_pop($curriculum['06']['sections']);
        $path = $this->outputDir . '/incomplete.php';
        file_put_contents($path, '<?php return ' . var_export($curriculum, true) . ';');

        $process = $this->runGenerator(
            base_path('tests/fixtures/fipi-taxonomy-small.json'),
            $path,
            '06',
        );

        $this->assertNotSame(0, $process->getExitCode());
        $this->assertStringContainsString('подтип 1 отсутствует', $process->getErrorOutput());
    }

    public function test_it_rejects_an_unknown_curriculum_subtype(): void
    {
        $curriculum = require base_path('tests/fixtures/fipi-taxonomy-small-curriculum.php');
        $curriculum['06']['sections'][0]['groups'][0]['subtypes'][] = 99;
        $path = $this->outputDir . '/unknown.php';
        file_put_contents($path, '<?php return ' . var_export($curriculum, true) . ';');

        $process = $this->runGenerator(
            base_path('tests/fixtures/fipi-taxonomy-small.json'),
            $path,
            '06',
        );

        $this->assertNotSame(0, $process->getExitCode());
        $this->assertStringContainsString('подтип 99 отсутствует в банке', $process->getErrorOutput());
    }

    public function test_it_can_split_one_source_subtype_by_condition_pattern(): void
    {
        $curriculum = [
            '06' => [
                'sections' => [[
                    'title' => 'Раздел',
                    'groups' => [
                        [
                            'key' => 'other-fraction',
                            'title' => 'Другая дробь',
                            'rules' => [[
                                'subtype' => 2,
                                'pattern' => '/другую/u',
                            ]],
                        ],
                        [
                            'key' => 'first-fraction',
                            'title' => 'Первая дробь',
                            'rules' => [[
                                'subtype' => 2,
                                'pattern' => '/^(?!.*другую).*дробь/us',
                            ]],
                        ],
                        [
                            'key' => 'decimals',
                            'title' => 'Десятичные дроби',
                            'subtypes' => [1],
                        ],
                    ],
                ]],
            ],
        ];
        $path = $this->outputDir . '/split.php';
        file_put_contents($path, '<?php return ' . var_export($curriculum, true) . ';');

        $process = $this->runGenerator(
            base_path('tests/fixtures/fipi-taxonomy-small.json'),
            $path,
            '06',
        );

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $manifest = require $this->outputDir . '/oge-topic-06.php';
        $groups = $manifest['blocks'][0]['groups'];
        $this->assertSame(['A-1'], $groups[0]['guids']);
        $this->assertSame(['A-2'], $groups[1]['guids']);
    }

    private function runGenerator(string $bank, string $curriculum, string $topics): Process
    {
        $process = new Process([
            PHP_BINARY,
            base_path('scripts/build-oge-taxonomies.php'),
            $bank,
            $curriculum,
            "--topics={$topics}",
            "--output-dir={$this->outputDir}",
        ], base_path());
        $process->run();

        return $process;
    }
}
