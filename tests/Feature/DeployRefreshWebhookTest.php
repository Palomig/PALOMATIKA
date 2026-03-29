<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DeployRefreshWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.deploy.webhook_secret', 'test-secret');
    }

    public function test_deploy_refresh_webhook_runs_command_with_valid_secret(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('deploy:refresh', [])
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('ok');

        $response = $this->withHeaders([
            'X-Deploy-Secret' => 'test-secret',
        ])->postJson('/api/deploy/refresh');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'command' => 'deploy:refresh',
            ]);
    }

    public function test_deploy_refresh_webhook_rejects_concurrent_run(): void
    {
        Cache::put('deploy:refresh:running', true, now()->addMinutes(10));

        Artisan::shouldReceive('call')->never();

        $response = $this->withHeaders([
            'X-Deploy-Secret' => 'test-secret',
        ])->postJson('/api/deploy/refresh');

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'command' => 'deploy:refresh',
            ]);
    }
}
