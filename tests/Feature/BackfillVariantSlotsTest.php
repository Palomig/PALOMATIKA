<?php

namespace Tests\Feature;

use App\Models\OgeVariant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillVariantSlotsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('oge_variants');

        Schema::create('oge_variants', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 16)->unique();
            $table->foreignId('owner_teacher_id')->nullable();
            $table->string('title')->nullable();
            $table->string('source')->nullable();
            $table->string('mode')->nullable();
            $table->json('config_json')->nullable();
            $table->boolean('is_curated')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function test_backfill_adds_slot_and_exam_number_to_miniapp_variant(): void
    {
        OgeVariant::create([
            'hash' => 'bftest1',
            'source' => 'miniapp',
            'mode' => 'mini_mixed',
            'config_json' => [
                'tasks' => [
                    ['task_number' => 8, 'topic_id' => '08', 'task' => ['id' => 1, 'answer' => '5']],
                    ['task_number' => 14, 'topic_id' => '14', 'task' => ['id' => 2, 'answer' => '10']],
                ],
            ],
        ]);

        $this->artisan('variants:backfill-slots')
            ->assertExitCode(0);

        $variant = OgeVariant::where('hash', 'bftest1')->first();
        $tasks = $variant->config_json['tasks'];

        $this->assertSame(1, $tasks[0]['slot']);
        $this->assertSame(8, $tasks[0]['exam_number']);
        $this->assertSame(2, $tasks[1]['slot']);
        $this->assertSame(14, $tasks[1]['exam_number']);
    }

    public function test_backfill_skips_already_backfilled_variant(): void
    {
        OgeVariant::create([
            'hash' => 'bftest2',
            'source' => 'miniapp',
            'mode' => 'mini_mixed',
            'config_json' => [
                'tasks' => [
                    ['slot' => 1, 'exam_number' => 8, 'task_number' => 8, 'task' => ['id' => 1]],
                ],
            ],
        ]);

        $this->artisan('variants:backfill-slots')
            ->expectsOutputToContain('Skipped: 1')
            ->assertExitCode(0);
    }

    public function test_dry_run_does_not_modify_database(): void
    {
        OgeVariant::create([
            'hash' => 'bftest3',
            'source' => 'custom_random',
            'mode' => null,
            'config_json' => [
                'source' => 'custom_random',
                'tasks' => [
                    ['task_number' => 6, 'attempt_task_number' => 1, 'task' => ['id' => 1]],
                ],
            ],
        ]);

        $this->artisan('variants:backfill-slots', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY RUN]')
            ->assertExitCode(0);

        $variant = OgeVariant::where('hash', 'bftest3')->first();
        $tasks = $variant->config_json['tasks'];
        $this->assertArrayNotHasKey('slot', $tasks[0]);
    }

    public function test_backfill_handles_custom_random_variant(): void
    {
        OgeVariant::create([
            'hash' => 'bftest4',
            'source' => 'custom_random',
            'mode' => null,
            'config_json' => [
                'source' => 'custom_random',
                'tasks' => [
                    ['task_number' => 6, 'attempt_task_number' => 1, 'test_number' => 1, 'task' => ['id' => 1]],
                    ['task_number' => 8, 'attempt_task_number' => 2, 'test_number' => 2, 'task' => ['id' => 2]],
                ],
            ],
        ]);

        $this->artisan('variants:backfill-slots')
            ->assertExitCode(0);

        $variant = OgeVariant::where('hash', 'bftest4')->first();
        $tasks = $variant->config_json['tasks'];

        $this->assertSame(1, $tasks[0]['slot']);
        $this->assertSame(6, $tasks[0]['exam_number']);
        $this->assertSame(2, $tasks[1]['slot']);
        $this->assertSame(8, $tasks[1]['exam_number']);
    }

    public function test_backfill_gives_distinct_slots_to_duplicate_tasks(): void
    {
        // Two structurally identical tasks — must get different slots
        $duplicateTask = ['task_number' => 8, 'topic_id' => '08', 'task' => ['id' => 1, 'answer' => '5']];

        OgeVariant::create([
            'hash' => 'bfdup01',
            'source' => 'miniapp',
            'mode' => 'mini_mixed',
            'config_json' => [
                'tasks' => [$duplicateTask, $duplicateTask, $duplicateTask],
            ],
        ]);

        $this->artisan('variants:backfill-slots')
            ->assertExitCode(0);

        $variant = OgeVariant::where('hash', 'bfdup01')->first();
        $tasks = $variant->config_json['tasks'];

        $this->assertCount(3, $tasks);
        $slots = array_column($tasks, 'slot');
        $this->assertSame([1, 2, 3], $slots, 'Duplicate tasks must get distinct sequential slots');
        // All have same exam_number since they are the same topic
        $this->assertSame(8, $tasks[0]['exam_number']);
        $this->assertSame(8, $tasks[1]['exam_number']);
        $this->assertSame(8, $tasks[2]['exam_number']);
    }

    public function test_backfill_processes_partially_backfilled_variant(): void
    {
        // First task has canonical fields, second doesn't — command should NOT skip
        OgeVariant::create([
            'hash' => 'bfpart1',
            'source' => 'miniapp',
            'mode' => 'mini_mixed',
            'config_json' => [
                'tasks' => [
                    ['slot' => 1, 'exam_number' => 8, 'task_number' => 8, 'task' => ['id' => 1]],
                    ['task_number' => 14, 'topic_id' => '14', 'task' => ['id' => 2]],
                ],
            ],
        ]);

        $this->artisan('variants:backfill-slots')
            ->expectsOutputToContain('Updated: 1')
            ->assertExitCode(0);

        $variant = OgeVariant::where('hash', 'bfpart1')->first();
        $tasks = $variant->config_json['tasks'];

        // Both tasks now have canonical fields
        $this->assertSame(1, $tasks[0]['slot']);
        $this->assertSame(8, $tasks[0]['exam_number']);
        $this->assertSame(2, $tasks[1]['slot']);
        $this->assertSame(14, $tasks[1]['exam_number']);
    }
}
