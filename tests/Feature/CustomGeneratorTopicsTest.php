<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomGeneratorTopicsTest extends TestCase
{
    public function test_custom_generator_shows_topics_14_to_17(): void
    {
        $response = $this->get('/test/generator');

        $response->assertOk();
        $response->assertSee('14');
        $response->assertSee('Прогрессии');
        $response->assertSee('15');
        $response->assertSee('Треугольники');
        $response->assertSee('16');
        $response->assertSee('Окружность');
        $response->assertSee('17');
        $response->assertSee('Четырёхугольники');
    }
}
