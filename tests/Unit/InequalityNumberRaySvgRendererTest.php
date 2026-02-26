<?php

namespace Tests\Unit;

use App\Services\InequalityNumberRaySvgRenderer;
use Tests\TestCase;

class InequalityNumberRaySvgRendererTest extends TestCase
{
    public function test_endpoint_semantics_match_config_type(): void
    {
        $renderer = app(InequalityNumberRaySvgRenderer::class);

        $openSvg = $renderer->render([
            'type' => 'lt',
            'b' => 4,
            'axisMin' => -2,
            'axisMax' => 6,
        ]);
        $this->assertStringContainsString('fill="#0d1b2a" stroke="#4d9fdc"', $openSvg);

        $closedSvg = $renderer->render([
            'type' => 'lte',
            'b' => 4,
            'axisMin' => -2,
            'axisMax' => 6,
        ]);
        $this->assertStringContainsString('fill="#4d9fdc"', $closedSvg);
    }

    public function test_hatch_direction_is_left_for_left_ray_and_right_for_right_ray(): void
    {
        $renderer = app(InequalityNumberRaySvgRenderer::class);

        $left = $renderer->render([
            'type' => 'lt',
            'b' => 3,
            'axisMin' => -5,
            'axisMax' => 6,
        ]);
        preg_match('/<line x1="([0-9.]+)" y1="18" x2="([0-9.]+)" y2="30" stroke="#e8a838"/', $left, $leftMatch);
        $this->assertNotEmpty($leftMatch);
        $this->assertGreaterThan((float) $leftMatch[2], (float) $leftMatch[1]);

        $right = $renderer->render([
            'type' => 'gt',
            'a' => -1,
            'axisMin' => -5,
            'axisMax' => 6,
        ]);
        preg_match('/<line x1="([0-9.]+)" y1="18" x2="([0-9.]+)" y2="30" stroke="#e8a838"/', $right, $rightMatch);
        $this->assertNotEmpty($rightMatch);
        $this->assertLessThan((float) $rightMatch[2], (float) $rightMatch[1]);
    }

    public function test_label_format_uses_decimal_comma_and_unicode_minus(): void
    {
        $renderer = app(InequalityNumberRaySvgRenderer::class);

        $svg = $renderer->render([
            'type' => 'open',
            'a' => -3.7,
            'b' => 2.5,
            'axisMin' => -10,
            'axisMax' => 10,
        ]);

        $this->assertStringContainsString('>−3,7</text>', $svg);
        $this->assertStringContainsString('>2,5</text>', $svg);
        $this->assertStringNotContainsString('-3.7', $svg);
    }

    public function test_only_boundary_labels_are_rendered(): void
    {
        $renderer = app(InequalityNumberRaySvgRenderer::class);

        $svg = $renderer->render([
            'type' => 'outerClosed',
            'a' => -2,
            'b' => 6,
            'axisMin' => -10,
            'axisMax' => 10,
        ]);

        $this->assertSame(2, substr_count($svg, '<text '));
    }

    public function test_boundary_labels_use_increased_global_font_size(): void
    {
        $renderer = app(InequalityNumberRaySvgRenderer::class);

        $svg = $renderer->render([
            'type' => 'open',
            'a' => -2,
            'b' => 6,
            'axisMin' => -10,
            'axisMax' => 10,
        ]);

        $this->assertSame(2, substr_count($svg, 'font-size="17"'));
    }
}
