<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomGeneratorSubmitTest extends TestCase
{
    public function test_can_generate_test_with_topics_14_to_17(): void
    {
        $response = $this->post('/test/generator/generate', [
            'topics' => ['14', '15', '16', '17'],
            'tasks_per_topic' => 2,
        ]);

        $response->assertOk();
        $response->assertSee('Тест ОГЭ');
    }
}
