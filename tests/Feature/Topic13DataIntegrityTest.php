<?php

namespace Tests\Feature;

use App\Services\TaskDataService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Topic13DataIntegrityTest extends TestCase
{
    public function test_topic_13_matches_expected_pdf_structure_and_key_content(): void
    {
        $path = storage_path('app/tasks/topic_13.json');
        $this->assertFileExists($path);

        $data = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($data, 'Invalid JSON in topic_13.json');

        $this->assertSame('13', $data['topic_id'] ?? null);
        $this->assertCount(2, $data['blocks'] ?? []);

        $blocks = $data['blocks'];
        $this->assertSame(1, $blocks[0]['number'] ?? null);
        $this->assertSame(2, $blocks[1]['number'] ?? null);
        $this->assertCount(13, $blocks[0]['zadaniya'] ?? []);
        $this->assertCount(8, $blocks[1]['zadaniya'] ?? []);

        $totalTasks = 0;
        $imageTasks = 0;
        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $tasks = $zadanie['tasks'] ?? [];
                $totalTasks += count($tasks);
                foreach ($tasks as $task) {
                    if (!empty($task['image'])) {
                        $imageTasks++;
                    }
                }
            }
        }

        $this->assertSame(166, $totalTasks, 'Unexpected topic 13 total task count');
        $this->assertSame(87, $imageTasks, 'Unexpected topic 13 image task count');

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $this->assertNotSame('graphic', $zadanie['type'] ?? null, 'Topic 13 should not use synthetic graphic renderer');
            }
        }

        // Block 1, task set 1: sign correction from PDF (<=, not >=)
        $b1z1t3 = $blocks[0]['zadaniya'][0]['tasks'][2] ?? null;
        $this->assertSame('6 - 7x \\leq 3x - 7', $b1z1t3['expression'] ?? null);

        // Block 1, task set 2: systems signs correction
        $b1z2t1 = $blocks[0]['zadaniya'][1]['tasks'][0] ?? null;
        $this->assertStringContainsString('x + 3,6 \\leq 0', $b1z2t1['expression'] ?? '');
        $this->assertStringContainsString('x + 2 \\leq -1', $b1z2t1['expression'] ?? '');

        // Block 1, image task sections should use raster assets, not synthetic SVG-only behavior
        $b1z7 = $blocks[0]['zadaniya'][6] ?? [];
        $this->assertSame(7, $b1z7['number'] ?? null);
        $this->assertCount(9, $b1z7['tasks'] ?? []);
        $this->assertSame('img-018.png', $b1z7['tasks'][0]['image'] ?? null);

        // Block 2 image mapping continuity check (final task set)
        $b2z8 = $blocks[1]['zadaniya'][7] ?? [];
        $this->assertSame(8, $b2z8['number'] ?? null);
        $this->assertCount(6, $b2z8['tasks'] ?? []);
        $this->assertSame('img-081.png', $b2z8['tasks'][0]['image'] ?? null);
        $this->assertSame('img-086.png', $b2z8['tasks'][5]['image'] ?? null);
    }

    public function test_topic_13_zadanie_1_options_render_as_plain_text_not_interval_svg(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $zadanie = $blocks[0]['zadaniya'][0] ?? null;

        $this->assertIsArray($zadanie);

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);

        $view->assertSee('1) [-0,2; +∞)');
        $view->assertDontSee('pattern id="hatch_', false);
    }

    public function test_topic_13_block1_boundary_between_zadanie_10_and_11_uses_correct_images(): void
    {
        $path = storage_path('app/tasks/topic_13.json');
        $data = json_decode((string) file_get_contents($path), true);

        $this->assertIsArray($data);

        $z10 = $data['blocks'][0]['zadaniya'][9] ?? null;
        $z11 = $data['blocks'][0]['zadaniya'][10] ?? null;
        $z12 = $data['blocks'][0]['zadaniya'][11] ?? null;

        $this->assertSame(10, $z10['number'] ?? null);
        $this->assertSame(11, $z11['number'] ?? null);
        $this->assertSame(12, $z12['number'] ?? null);

        // PDF page boundary: Zadanie 10 item 9 is the final 4-option graph panel (0 and 3), then Zadanie 11 starts.
        $this->assertSame('img-026.png', $z10['tasks'][0]['image'] ?? null);
        $this->assertSame('img-034.png', $z10['tasks'][8]['image'] ?? null);
        $this->assertSame('3x - x^2 \\leq 0', $z10['tasks'][8]['expression'] ?? null);

        // Zadanie 11 item 1 options are for x^2 - 49 and must use the closed segment [-7; 7] graph.
        $this->assertSame('img-035.png', $z11['tasks'][0]['image'] ?? null);
        $this->assertSame('img-042.png', $z11['tasks'][7]['image'] ?? null);

        // Zadanie 12 starts on the next image after Zadanie 11 item 8.
        $this->assertSame('img-043.png', $z12['tasks'][0]['image'] ?? null);
    }
}
