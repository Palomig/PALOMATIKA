<?php

namespace Tests\Feature;

use App\Services\TaskDataService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OptionRenderModalityTest extends TestCase
{
    public function test_interval_like_plain_text_options_are_not_auto_converted_without_explicit_visual_mode(): void
    {
        $zadanie = [
            'number' => 1,
            'type' => 'choice',
            'instruction' => 'Тест модальности опций',
            'tasks' => [[
                'id' => 1,
                'expression' => 'x > 1',
                'options' => [
                    '(-∞; 1]',
                    '(1; +∞)',
                    'нет решений',
                    '(-∞; +∞)',
                ],
            ]],
        ];

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '07',
            'isVariant' => true,
        ]);

        $view->assertSee('1) (-∞; 1]');
        $view->assertDontSee('pattern id="hatch_', false);
    }

    public function test_interval_options_render_as_svg_when_explicit_visual_mode_is_set(): void
    {
        $zadanie = [
            'number' => 1,
            'type' => 'choice',
            'instruction' => 'Тест визуального режима опций',
            'tasks' => [[
                'id' => 1,
                'expression' => 'x > 1',
                'options_render_mode' => 'visual_options',
                'options' => [
                    '(-∞; 1]',
                    '(1; +∞)',
                    'нет решений',
                    '(-∞; +∞)',
                ],
            ]],
        ];

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '07',
            'isVariant' => true,
        ]);

        $view->assertSee('pattern id="hatch_', false);
    }

    public function test_topic_07_data_is_normalized_with_explicit_text_option_render_mode(): void
    {
        Cache::forget('topic_data_07');

        $blocks = app(TaskDataService::class)->getBlocks('07');
        $firstZadanie = $blocks[0]['zadaniya'][0] ?? null;
        $firstTask = $firstZadanie['tasks'][0] ?? null;

        $this->assertIsArray($firstZadanie);
        $this->assertIsArray($firstTask);
        $this->assertSame('text_options', $firstZadanie['options_render_mode'] ?? null);
        $this->assertSame('text_options', $firstTask['options_render_mode'] ?? null);
    }
}
