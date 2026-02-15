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
}
