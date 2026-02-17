<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomGeneratorSubmitTest extends TestCase
{
    public function test_can_generate_test_with_topics_14_to_17(): void
    {
        $response = $this->followingRedirects()->post('/test/generator/generate', [
            'topics' => ['14', '15', '16', '17'],
            'tasks_per_topic' => 2,
        ]);

        $response->assertOk();
        $response->assertSee('Кастомный тест ОГЭ');
    }

    public function test_can_generate_test_with_topics_15_to_19_and_render_each_topic(): void
    {
        $response = $this->followingRedirects()->post('/test/generator/generate', [
            'topics' => ['15', '16', '17', '18', '19'],
            'tasks_per_topic' => 1,
        ]);

        $response->assertOk();
        $response->assertSee('Кастомный тест ОГЭ');
        $response->assertSee('15.');
        $response->assertSee('16.');
        $response->assertSee('17.');
        $response->assertSee('18.');
        $response->assertSee('19.');
    }

    public function test_topic_19_generation_renders_three_numbered_statements(): void
    {
        $response = $this->followingRedirects()->post('/test/generator/generate', [
            'topics' => ['19'],
            'tasks_per_topic' => 1,
        ]);

        $response->assertOk();
        $response->assertSee('19.');
        $response->assertSee('1.');
        $response->assertSee('2.');
        $response->assertSee('3.');
        $response->assertSee('Введите номера верных утверждений');
        $response->assertDontSee('15. ', false);
    }
}
