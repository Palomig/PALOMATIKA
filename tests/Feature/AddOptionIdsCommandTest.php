<?php

namespace Tests\Feature;

use App\Services\TaskDataService;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class AddOptionIdsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dry_run_does_not_persist_changes(): void
    {
        $data = [
            'blocks' => [[
                'zadaniya' => [[
                    'type' => 'choice',
                    'tasks' => [[
                        'id' => 1,
                        'status' => 'production',
                        'answer' => '2',
                        'options' => ['A', 'B', 'C'],
                    ]],
                ]],
            ]],
        ];

        $mock = Mockery::mock(TaskDataService::class);
        $mock->shouldReceive('getAllTopicsMeta')->andReturn(['07' => ['id' => '07']]);
        $mock->shouldReceive('topicDataExists')->with('07')->andReturnTrue();
        $mock->shouldReceive('getTopicData')->with('07')->andReturn($data);
        $mock->shouldReceive('saveTopicData')->never();

        $this->app->instance(TaskDataService::class, $mock);

        $code = Artisan::call('tasks:add-option-ids', [
            '--topic' => '07',
            '--production-only' => true,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $code);
    }

    public function test_applied_mode_persists_option_objects_and_remapped_answer(): void
    {
        $data = [
            'blocks' => [[
                'zadaniya' => [[
                    'type' => 'choice',
                    'tasks' => [[
                        'id' => 1,
                        'status' => 'production',
                        'answer' => '2',
                        'options' => ['A', 'B', 'C'],
                    ]],
                ]],
            ]],
        ];

        $saved = null;

        $mock = Mockery::mock(TaskDataService::class);
        $mock->shouldReceive('getAllTopicsMeta')->andReturn(['07' => ['id' => '07']]);
        $mock->shouldReceive('topicDataExists')->with('07')->andReturnTrue();
        $mock->shouldReceive('getTopicData')->with('07')->andReturn($data);
        $mock->shouldReceive('saveTopicData')->with('07', Mockery::on(function ($arg) use (&$saved) {
            $saved = $arg;
            return true;
        }))->once()->andReturnTrue();

        $this->app->instance(TaskDataService::class, $mock);

        $code = Artisan::call('tasks:add-option-ids', [
            '--topic' => '07',
            '--production-only' => true,
        ]);

        $this->assertSame(0, $code);
        $this->assertNotNull($saved);

        $task = $saved['blocks'][0]['zadaniya'][0]['tasks'][0];
        $this->assertSame('b', $task['answer']);
        $this->assertSame('a', $task['options'][0]['id']);
        $this->assertSame('b', $task['options'][1]['id']);
        $this->assertSame('B', $task['options'][1]['label']);
    }
}
