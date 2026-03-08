<?php

namespace Tests\Feature;

use App\Services\TaskDataService;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class ValidateProductionTasksCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_matching_signs_answer_is_not_validated_as_choice_index_range(): void
    {
        $data = [
            'blocks' => [[
                'zadaniya' => [[
                    'number' => 1,
                    'type' => 'matching_signs',
                    'tasks' => [[
                        'id' => 1,
                        'status' => 'production',
                        'answer' => '213',
                        'options' => ['A', 'B', 'C'],
                    ]],
                ]],
            ]],
        ];

        $mock = Mockery::mock(TaskDataService::class);
        $mock->shouldReceive('getAllTopicsMeta')->andReturn(['11' => ['id' => '11']]);
        $mock->shouldReceive('topicDataExists')->with('11')->andReturnTrue();
        $mock->shouldReceive('getTopicData')->with('11')->andReturn($data);
        $mock->shouldReceive('saveTopicData')->never();

        $this->app->instance(TaskDataService::class, $mock);

        $code = Artisan::call('tasks:validate', [
            '--topic' => '11',
            '--production-only' => true,
        ]);

        $this->assertSame(0, $code);
    }

    public function test_fix_types_converts_int_answer_to_string_for_choice_task(): void
    {
        $data = [
            'blocks' => [[
                'zadaniya' => [[
                    'number' => 1,
                    'type' => 'choice',
                    'tasks' => [[
                        'id' => 1,
                        'status' => 'production',
                        'answer' => 2,
                        'options' => ['a', 'b', 'c'],
                    ]],
                ]],
            ]],
        ];

        $saved = null;

        $mock = Mockery::mock(TaskDataService::class);
        $mock->shouldReceive('getAllTopicsMeta')->andReturn(['07' => ['id' => '07']]);
        $mock->shouldReceive('topicDataExists')->with('07')->andReturnTrue();
        $mock->shouldReceive('getTopicData')->with('07')->andReturnUsing(fn () => $data);
        $mock->shouldReceive('saveTopicData')->with('07', Mockery::on(function ($arg) use (&$saved) {
            $saved = $arg;
            return true;
        }))->once()->andReturnTrue();

        $this->app->instance(TaskDataService::class, $mock);

        $code = Artisan::call('tasks:validate', [
            '--topic' => '07',
            '--production-only' => true,
            '--fix-types' => true,
        ]);

        $this->assertSame(0, $code);
        $this->assertNotNull($saved);
        $this->assertSame('2', $saved['blocks'][0]['zadaniya'][0]['tasks'][0]['answer']);
    }
}
