<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomGeneratorHashUrlTest extends TestCase
{
    public function test_custom_generator_post_redirects_to_hashed_url(): void
    {
        $response = $this->post('/test/generator/generate', [
            'topics' => ['18'],
            'tasks_per_topic' => 1,
        ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertNotNull($location);
        $this->assertMatchesRegularExpression('#/test/generator/result/[a-z0-9]{8}$#', $location);
    }
}
