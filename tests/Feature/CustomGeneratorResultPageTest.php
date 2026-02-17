<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CustomGeneratorResultPageTest extends TestCase
{
    public function test_cached_custom_test_result_page_is_accessible_by_hash(): void
    {
        $hash = 'abcd1234';

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '15',
            'topic_title' => 'Треугольники',
            'block_number' => 1,
            'zadanie_number' => 1,
            'instruction' => 'Проверка',
            'task' => [
                'svg' => '<svg viewBox="0 0 10 10"><circle cx="5" cy="5" r="3"/></svg>',
                'options' => ['1', '2'],
            ],
        ]], now()->addMinutes(5));

        $response = $this->get("/test/generator/result/{$hash}");

        $response->assertOk();
        $response->assertSee('Кастомный тест ОГЭ');
        $response->assertSee('<svg', false);
    }

    public function test_result_page_renders_inline_svg_from_image_field(): void
    {
        $hash = 'efgh5678';

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '15',
            'topic_title' => 'Треугольники',
            'block_number' => 1,
            'zadanie_number' => 1,
            'instruction' => 'Проверка inline SVG',
            'task' => [
                'image' => '<svg viewBox="0 0 12 12"><rect x="1" y="1" width="10" height="10"/></svg>',
                'options' => ['1', '2'],
            ],
        ]], now()->addMinutes(5));

        $response = $this->get("/test/generator/result/{$hash}");

        $response->assertOk();
        $response->assertSee('<svg viewBox="0 0 12 12">', false);
    }

    public function test_result_page_renders_svg_from_svg_type_payload(): void
    {
        $hash = 'ijkl9012';

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '07',
            'topic_title' => 'Числа, координатная прямая',
            'block_number' => 1,
            'zadanie_number' => 1,
            'instruction' => 'Проверка svg_type',
            'svg_type' => 'single_point',
            'task' => [
                'point_value' => 2.5,
                'point_label' => 'a',
                'options' => ['1', '2'],
            ],
        ]], now()->addMinutes(5));

        $response = $this->get("/test/generator/result/{$hash}");

        $response->assertOk();
        $response->assertSee('number-line', false);
    }
}
