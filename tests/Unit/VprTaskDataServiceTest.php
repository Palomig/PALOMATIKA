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

    public function test_grade_5_topic_03_uses_unknown_component_training_structure(): void
    {
        $svc = new VprTaskDataService(5);

        $meta = $svc->getTopicMeta('03');
        $data = $svc->getTopicData('03');

        $this->assertSame('Неизвестный компонент', $meta['title'] ?? null);
        $this->assertStringContainsString('равен', mb_strtolower((string) ($meta['description'] ?? '')));

        $zadaniya = $data['blocks'][0]['zadaniya'] ?? [];
        $this->assertCount(3, $zadaniya);
        $this->assertSame('expression', $zadaniya[0]['type'] ?? null);
        $this->assertSame('expression', $zadaniya[1]['type'] ?? null);
        $this->assertSame('expression', $zadaniya[2]['type'] ?? null);
        $this->assertGreaterThanOrEqual(8, count($zadaniya[0]['tasks'] ?? []));
        $this->assertGreaterThanOrEqual(8, count($zadaniya[1]['tasks'] ?? []));
        $this->assertGreaterThanOrEqual(8, count($zadaniya[2]['tasks'] ?? []));
    }

    public function test_grade_5_topic_04_uses_baked_semantic_svg_images(): void
    {
        $svc = new VprTaskDataService(5);
        $data = $svc->getTopicData('04');

        $tasks = $data['blocks'][0]['zadaniya'][0]['tasks'] ?? [];
        $this->assertNotEmpty($tasks);

        $firstImage = (string) ($tasks[0]['image'] ?? '');

        $this->assertStringContainsString('<svg', $firstImage);
        $this->assertStringContainsString('<rect', $firstImage);
        $this->assertStringContainsString('#0a1628', $firstImage);
        $this->assertStringNotContainsString('data:image/png;base64', $firstImage);
    }

    public function test_grade_5_topic_04_uses_chart_training_categories_and_vpr_wording(): void
    {
        $svc = new VprTaskDataService(5);

        $meta = $svc->getTopicMeta('04');
        $data = $svc->getTopicData('04');

        $this->assertSame('Диаграммы', $meta['title'] ?? null);
        $this->assertStringContainsString('диаграм', mb_strtolower((string) ($meta['description'] ?? '')));

        $zadaniya = $data['blocks'][0]['zadaniya'] ?? [];
        $this->assertCount(3, $zadaniya);
        $this->assertSame('word_problem', $zadaniya[0]['type'] ?? null);
        $this->assertSame('word_problem', $zadaniya[1]['type'] ?? null);
        $this->assertSame('word_problem', $zadaniya[2]['type'] ?? null);

        $firstTaskText = (string) ($zadaniya[0]['tasks'][0]['text'] ?? '');
        $this->assertStringNotContainsString('Вариант', $firstTaskText);
        $this->assertStringContainsString('Пользуясь этими данными', $firstTaskText);
        $this->assertStringContainsString('1)', $firstTaskText);
    }

    public function test_grade_5_topic_05_uses_baked_semantic_svg_images(): void
    {
        $svc = new VprTaskDataService(5);
        $data = $svc->getTopicData('05');

        $tasks = $data['blocks'][0]['zadaniya'][0]['tasks'] ?? [];
        $this->assertNotEmpty($tasks);

        $firstImage = (string) ($tasks[0]['image'] ?? '');

        $this->assertStringContainsString('<svg', $firstImage);
        $this->assertStringContainsString('<polygon', $firstImage);
        $this->assertStringContainsString('#0a1628', $firstImage);
        $this->assertStringNotContainsString('data:image/png;base64', $firstImage);
    }

    public function test_grade_5_topic_05_uses_area_categories_and_clean_text(): void
    {
        $svc = new VprTaskDataService(5);

        $meta = $svc->getTopicMeta('05');
        $data = $svc->getTopicData('05');

        $this->assertSame('Площадь фигур на клетчатой бумаге', $meta['title'] ?? null);
        $this->assertStringContainsString('площад', mb_strtolower((string) ($meta['description'] ?? '')));

        $zadaniya = $data['blocks'][0]['zadaniya'] ?? [];
        $this->assertCount(3, $zadaniya);
        $this->assertSame('word_problem', $zadaniya[0]['type'] ?? null);
        $this->assertSame('word_problem', $zadaniya[1]['type'] ?? null);
        $this->assertSame('word_problem', $zadaniya[2]['type'] ?? null);

        $firstTaskText = (string) ($zadaniya[0]['tasks'][0]['text'] ?? '');
        $this->assertStringNotContainsString('Вариант', $firstTaskText);
        $this->assertStringNotContainsString('Бумага расчерчена', $firstTaskText);
    }

    public function test_grade_5_topic_06_uses_baked_number_line_svg_images(): void
    {
        $svc = new VprTaskDataService(5);
        $data = $svc->getTopicData('06');

        $tasks = $data['blocks'][0]['zadaniya'][0]['tasks'] ?? [];
        $this->assertNotEmpty($tasks);

        $firstImage = (string) ($tasks[0]['image'] ?? '');

        $this->assertStringContainsString('<svg', $firstImage);
        $this->assertStringContainsString('<circle', $firstImage);
        $this->assertStringContainsString('#0a1628', $firstImage);
        $this->assertStringNotContainsString('data:image/png;base64', $firstImage);
    }

    public function test_grade_5_topic_06_uses_number_line_categories_and_clean_text(): void
    {
        $svc = new VprTaskDataService(5);

        $meta = $svc->getTopicMeta('06');
        $data = $svc->getTopicData('06');

        $this->assertSame('Числовой луч', $meta['title'] ?? null);
        $this->assertStringContainsString('луч', mb_strtolower((string) ($meta['description'] ?? '')));

        $zadaniya = $data['blocks'][0]['zadaniya'] ?? [];
        $this->assertCount(3, $zadaniya);
        $this->assertSame('word_problem', $zadaniya[0]['type'] ?? null);
        $this->assertSame('word_problem', $zadaniya[1]['type'] ?? null);
        $this->assertSame('word_problem', $zadaniya[2]['type'] ?? null);

        $firstTaskText = (string) ($zadaniya[0]['tasks'][0]['text'] ?? '');
        $this->assertStringNotContainsString('Вариант', $firstTaskText);
        $this->assertStringNotContainsString('На числовом луче', $firstTaskText);
    }

    public function test_canonicalize_legacy_task_replaces_embedded_png_table_with_structured_table(): void
    {
        $svc = new VprTaskDataService(5);

        $legacyTask = [
            'id' => 1,
            'topic_id' => '14',
            'task_number' => 14,
            'source_pdf' => '1_trenirovochny_variant_VPR_2025_po_matematike_5_klass.pdf',
            'image' => '<svg><image href="data:image/png;base64,AAAA" /></svg>',
            'text' => 'legacy',
        ];

        $normalized = $svc->canonicalizeLegacyTask('14', $legacyTask);

        $this->assertArrayHasKey('table', $normalized);
        $this->assertNotEmpty($normalized['table']['headers'] ?? []);
        $this->assertNotEmpty($normalized['table']['rows'] ?? []);
        $this->assertArrayNotHasKey('image', $normalized);
        $this->assertSame('6450', $normalized['answer'] ?? null);
    }
}
