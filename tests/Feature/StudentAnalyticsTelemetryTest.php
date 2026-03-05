<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptAnswer;
use App\Models\OgeAttemptScoring;
use App\Models\OgeAttemptTaskDetail;
use App\Models\OgeAttemptTaskTiming;
use App\Models\OgeVariant;
use App\Models\StudentTopicMastery;
use App\Models\User;
use App\Services\OgeAttemptService;
use App\Services\OgeVariantBuilderService;
use App\Services\StudentAnalyticsService;
use App\Services\TaskAnswerResolver;
use App\Services\TaskDataService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentAnalyticsTelemetryTest extends TestCase
{
    private User $student;
    private OgeVariant $variant;
    private OgeAttempt $attempt;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        $this->createTables();
        $this->seedData();
    }

    private function createTables(): void
    {
        Schema::dropIfExists('student_topic_mastery');
        Schema::dropIfExists('oge_attempt_task_details');
        Schema::dropIfExists('oge_attempt_scorings');
        Schema::dropIfExists('oge_attempt_task_timings');
        Schema::dropIfExists('oge_attempt_answers');
        Schema::dropIfExists('oge_attempt_events');
        Schema::dropIfExists('oge_attempts');
        Schema::dropIfExists('oge_variants');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('role')->default('student');
            $table->timestamps();
        });

        Schema::create('oge_variants', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 16)->unique();
            $table->foreignId('owner_teacher_id')->nullable();
            $table->string('title')->nullable();
            $table->string('source', 32)->nullable();
            $table->string('external_ref', 255)->nullable();
            $table->string('created_via', 64)->nullable();
            $table->json('config_json')->nullable();
            $table->string('mode', 32)->nullable();
            $table->boolean('is_curated')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('oge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id');
            $table->foreignId('student_id');
            $table->string('status')->default('active');
            $table->json('device_meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('oge_attempt_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id');
            $table->unsignedInteger('seq');
            $table->string('event_type', 64);
            $table->unsignedTinyInteger('task_number')->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamp('client_ts')->nullable();
            $table->timestamp('server_ts')->nullable();
            $table->unique(['attempt_id', 'seq']);
        });

        Schema::create('oge_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id');
            $table->unsignedTinyInteger('task_number');
            $table->string('current_answer', 255)->nullable();
            $table->unsignedInteger('commits_count')->default(0);
            $table->timestamp('first_committed_at')->nullable();
            $table->timestamp('last_committed_at')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['attempt_id', 'task_number']);
        });

        Schema::create('oge_attempt_task_timings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id');
            $table->unsignedTinyInteger('task_number');
            $table->unsignedBigInteger('active_ms')->default(0);
            $table->unsignedInteger('focus_count')->default(0);
            $table->unsignedInteger('heartbeat_count')->default(0);
            $table->unsignedInteger('blur_count')->default(0);
            $table->timestamp('first_focused_at')->nullable();
            $table->timestamp('last_focus_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['attempt_id', 'task_number']);
        });

        Schema::create('oge_attempt_scorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id');
            $table->unsignedTinyInteger('task_number');
            $table->boolean('is_correct')->nullable();
            $table->string('correct_answer', 255)->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['attempt_id', 'task_number']);
        });

        Schema::create('oge_attempt_task_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attempt_id');
            $table->unsignedTinyInteger('task_number');
            $table->string('topic_id', 4);
            $table->unsignedInteger('block_number');
            $table->unsignedInteger('zadanie_number');
            $table->unsignedInteger('task_index')->nullable();
            $table->string('task_type', 64);
            $table->string('svg_type', 64)->nullable();
            $table->string('subtype', 96)->nullable();
            $table->string('section', 128)->nullable();
            $table->string('source', 64)->nullable();
            $table->string('task_key', 160)->nullable();
            $table->string('task_fingerprint', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['attempt_id', 'task_number']);
        });

        Schema::create('student_topic_mastery', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('topic_id', 4);
            $table->string('task_type', 64);
            $table->string('svg_type', 64)->nullable();
            $table->string('subtype', 96)->nullable();
            $table->string('section', 128)->nullable();
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedBigInteger('total_active_ms')->default(0);
            $table->unsignedBigInteger('avg_active_ms')->default(0);
            $table->decimal('accuracy', 5, 4)->default(0);
            $table->decimal('mastery_score', 5, 4)->default(0.5);
            $table->json('recent_outcomes')->nullable();
            $table->unsignedInteger('current_correct_streak')->default(0);
            $table->unsignedInteger('current_incorrect_streak')->default(0);
            $table->boolean('last_outcome')->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'topic_id', 'task_type', 'svg_type', 'subtype', 'section'], 'stm_unique');
        });
    }

    private function seedData(): void
    {
        $this->student = User::create([
            'name' => 'Test Student',
            'email' => 'student@test.com',
            'role' => 'student',
        ]);

        // Create a variant with config_json tasks that contain rich metadata
        $this->variant = OgeVariant::create([
            'hash' => 'test01',
            'title' => 'Test Variant',
            'source' => 'miniapp',
            'config_json' => [
                'source' => 'miniapp',
                'tasks' => [
                    [
                        'task_number' => 6,
                        'topic_id' => '06',
                        'block_number' => 1,
                        'zadanie_number' => 2,
                        'type' => 'expression',
                        'task' => ['id' => 5, 'expression' => '2+3', 'answer' => '5'],
                    ],
                    [
                        'task_number' => 15,
                        'topic_id' => '15',
                        'block_number' => 1,
                        'zadanie_number' => 3,
                        'type' => 'geometry',
                        'svg_type' => 'bisector',
                        'subtype' => 'bisector_angle',
                        'section' => 'Биссектриса',
                        'source' => 'miniapp_pool',
                        'task' => ['id' => 10, 'text' => 'Найдите угол', 'answer' => '45'],
                    ],
                    [
                        'task_number' => 7,
                        'topic_id' => '07',
                        'block_number' => 2,
                        'zadanie_number' => 1,
                        'type' => 'choice',
                        'task' => ['id' => 3, 'answer' => '3'],
                    ],
                ],
            ],
        ]);

        $this->attempt = OgeAttempt::create([
            'variant_id' => $this->variant->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'started_at' => now()->subMinutes(10),
            'last_seen_at' => now(),
        ]);
    }

    // ----- Task Detail Persistence Tests -----

    public function test_task_details_are_persisted_on_submit(): void
    {
        // Commit answers
        $service = $this->makeService();
        $service->commitAnswer($this->attempt, 6, '5');
        $service->commitAnswer($this->attempt, 15, '45');
        $service->commitAnswer($this->attempt, 7, '3');

        // Add timings
        OgeAttemptTaskTiming::create([
            'attempt_id' => $this->attempt->id,
            'task_number' => 6,
            'active_ms' => 12000,
            'focus_count' => 1,
        ]);
        OgeAttemptTaskTiming::create([
            'attempt_id' => $this->attempt->id,
            'task_number' => 15,
            'active_ms' => 25000,
            'focus_count' => 3,
        ]);

        // Submit
        $service->submitAttempt($this->attempt->fresh());

        // Verify task details were written
        $details = OgeAttemptTaskDetail::where('attempt_id', $this->attempt->id)
            ->orderBy('task_number')
            ->get();

        $this->assertGreaterThanOrEqual(3, $details->count());

        // Check task 6 detail
        $detail6 = $details->firstWhere('task_number', 6);
        $this->assertNotNull($detail6);
        $this->assertEquals('06', $detail6->topic_id);
        $this->assertEquals('expression', $detail6->task_type);
        $this->assertEquals(1, $detail6->block_number);
        $this->assertEquals(2, $detail6->zadanie_number);
        $this->assertEquals(5, $detail6->task_index);
        $this->assertStringContainsString('topic_06', $detail6->task_key);

        // Check task 15 detail (geometry with svg_type)
        $detail15 = $details->firstWhere('task_number', 15);
        $this->assertNotNull($detail15);
        $this->assertEquals('15', $detail15->topic_id);
        $this->assertEquals('geometry', $detail15->task_type);
        $this->assertEquals('bisector', $detail15->svg_type);
        $this->assertEquals('bisector_angle', $detail15->subtype);
        $this->assertEquals('Биссектриса', $detail15->section);
        $this->assertEquals('miniapp_pool', $detail15->source);
        $this->assertSame(64, strlen((string) $detail15->task_fingerprint));
    }

    public function test_mastery_is_updated_on_submit(): void
    {
        $service = $this->makeService();

        // Answer task 6 correctly, task 15 incorrectly
        $service->commitAnswer($this->attempt, 6, '5');     // correct
        $service->commitAnswer($this->attempt, 15, '99');   // incorrect (correct=45)

        $service->submitAttempt($this->attempt->fresh());

        // Check mastery rows were created
        $mastery6 = StudentTopicMastery::where('student_id', $this->student->id)
            ->where('topic_id', '06')
            ->where('task_type', 'expression')
            ->first();

        $this->assertNotNull($mastery6);
        $this->assertEquals(1, $mastery6->attempts_count);
        $this->assertEquals(1, $mastery6->correct_count);
        $this->assertGreaterThan(0.5, $mastery6->mastery_score); // Should increase from 0.5

        $mastery15 = StudentTopicMastery::where('student_id', $this->student->id)
            ->where('topic_id', '15')
            ->where('task_type', 'geometry')
            ->first();

        $this->assertNotNull($mastery15);
        $this->assertEquals(1, $mastery15->attempts_count);
        $this->assertEquals(0, $mastery15->correct_count);
        $this->assertLessThan(0.5, $mastery15->mastery_score); // Should decrease from 0.5
    }

    public function test_mastery_accumulates_across_attempts(): void
    {
        $service = $this->makeService();

        // First attempt: answer task 6 correctly
        $service->commitAnswer($this->attempt, 6, '5');
        $service->submitAttempt($this->attempt->fresh());

        $mastery1 = StudentTopicMastery::where('student_id', $this->student->id)
            ->where('topic_id', '06')
            ->first();
        $score1 = $mastery1->mastery_score;

        // Second attempt with a new variant
        $variant2 = OgeVariant::create([
            'hash' => 'test02',
            'title' => 'Test Variant 2',
            'source' => 'miniapp',
            'config_json' => [
                'source' => 'miniapp',
                'tasks' => [
                    [
                        'task_number' => 6,
                        'topic_id' => '06',
                        'block_number' => 1,
                        'zadanie_number' => 2,
                        'type' => 'expression',
                        'task' => ['id' => 5, 'expression' => '2+3', 'answer' => '5'],
                    ],
                ],
            ],
        ]);

        $attempt2 = OgeAttempt::create([
            'variant_id' => $variant2->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'started_at' => now()->subMinutes(5),
            'last_seen_at' => now(),
        ]);

        // Use a fresh service instance to clear caches
        $service2 = $this->makeService();
        $service2->commitAnswer($attempt2, 6, '5');
        $service2->submitAttempt($attempt2->fresh());

        $mastery2 = StudentTopicMastery::where('student_id', $this->student->id)
            ->where('topic_id', '06')
            ->first();

        $this->assertEquals(2, $mastery2->attempts_count);
        $this->assertEquals(2, $mastery2->correct_count);
        $this->assertGreaterThan($score1, $mastery2->mastery_score); // Mastery should increase
    }

    public function test_timing_enrichment_tracks_focus_heartbeat_and_blur_counters(): void
    {
        $service = $this->makeService();

        $service->touchTiming($this->attempt, 6, 'task_focused');
        $service->touchTiming($this->attempt, 6, 'heartbeat');
        $service->touchTiming($this->attempt, 6, 'heartbeat');
        $service->touchTiming($this->attempt, 6, 'task_blurred');

        $timing = OgeAttemptTaskTiming::where('attempt_id', $this->attempt->id)
            ->where('task_number', 6)
            ->firstOrFail();

        $this->assertEquals(1, $timing->focus_count);
        $this->assertEquals(2, $timing->heartbeat_count);
        $this->assertEquals(1, $timing->blur_count);
        $this->assertNotNull($timing->first_focused_at);
    }

    public function test_mastery_tracks_recent_correctness_history_and_streaks(): void
    {
        $service = $this->makeService();

        $service->commitAnswer($this->attempt, 6, '5');
        $service->submitAttempt($this->attempt->fresh());

        $variant2 = OgeVariant::create([
            'hash' => 'test03',
            'title' => 'Test Variant 3',
            'source' => 'miniapp',
            'config_json' => [
                'source' => 'miniapp',
                'tasks' => [[
                    'task_number' => 6,
                    'topic_id' => '06',
                    'block_number' => 1,
                    'zadanie_number' => 2,
                    'type' => 'expression',
                    'task' => ['id' => 5, 'expression' => '2+3', 'answer' => '5'],
                ]],
            ],
        ]);

        $attempt2 = OgeAttempt::create([
            'variant_id' => $variant2->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'started_at' => now()->subMinutes(4),
            'last_seen_at' => now(),
        ]);

        $service2 = $this->makeService();
        $service2->commitAnswer($attempt2, 6, '0');
        $service2->submitAttempt($attempt2->fresh());

        $mastery = StudentTopicMastery::where('student_id', $this->student->id)
            ->where('topic_id', '06')
            ->where('task_type', 'expression')
            ->firstOrFail();

        $this->assertEquals([true, false], $mastery->recent_outcomes);
        $this->assertEquals(0, $mastery->current_correct_streak);
        $this->assertEquals(1, $mastery->current_incorrect_streak);
        $this->assertFalse((bool) $mastery->last_outcome);
    }

    public function test_task_key_format(): void
    {
        $key = OgeAttemptTaskDetail::buildTaskKey('15', 1, 3, 10);
        $this->assertEquals('topic_15_block_1_zadanie_3_task_10', $key);

        $keyNoIndex = OgeAttemptTaskDetail::buildTaskKey('06', 2, 1, null);
        $this->assertEquals('topic_06_block_2_zadanie_1', $keyNoIndex);
    }

    // ----- StudentAnalyticsService Tests -----

    public function test_analytics_weak_and_strong_zones(): void
    {
        // Seed mastery data manually
        StudentTopicMastery::create([
            'student_id' => $this->student->id,
            'topic_id' => '06',
            'task_type' => 'expression',
            'attempts_count' => 10,
            'correct_count' => 9,
            'accuracy' => 0.9,
            'mastery_score' => 0.85,
            'last_attempted_at' => now(),
        ]);

        StudentTopicMastery::create([
            'student_id' => $this->student->id,
            'topic_id' => '15',
            'task_type' => 'geometry',
            'svg_type' => 'bisector',
            'attempts_count' => 8,
            'correct_count' => 2,
            'accuracy' => 0.25,
            'mastery_score' => 0.2,
            'last_attempted_at' => now(),
        ]);

        StudentTopicMastery::create([
            'student_id' => $this->student->id,
            'topic_id' => '15',
            'task_type' => 'geometry',
            'svg_type' => 'right_triangle',
            'attempts_count' => 5,
            'correct_count' => 1,
            'accuracy' => 0.2,
            'mastery_score' => 0.15,
            'last_attempted_at' => now(),
        ]);

        $service = app(StudentAnalyticsService::class);

        // Weak zones
        $weak = $service->getWeakZones($this->student->id);
        $this->assertCount(2, $weak);
        $this->assertEquals(0.15, $weak->first()->mastery_score); // weakest first

        // Strong zones
        $strong = $service->getStrongZones($this->student->id);
        $this->assertCount(1, $strong);
        $this->assertEquals('06', $strong->first()->topic_id);

        // Profile
        $profile = $service->getStudentProfile($this->student->id);
        $this->assertEquals(2, $profile['weak_count']);
        $this->assertEquals(1, $profile['strong_count']);
        $this->assertEquals(23, $profile['total_attempts']);
        $this->assertArrayHasKey('06', $profile['topics']);
        $this->assertArrayHasKey('15', $profile['topics']);
        $this->assertArrayHasKey('subtypes', $profile['topics']['15']);
        $this->assertCount(2, $profile['topics']['15']['subtypes']);
    }

    public function test_analytics_empty_for_new_student(): void
    {
        $service = app(StudentAnalyticsService::class);
        $profile = $service->getStudentProfile($this->student->id);

        $this->assertEmpty($profile['topics']);
        $this->assertEquals(0, $profile['total_attempts']);
        $this->assertEquals(0, $profile['weak_count']);
    }

    public function test_topic_weights_inversely_proportional_to_mastery(): void
    {
        StudentTopicMastery::create([
            'student_id' => $this->student->id,
            'topic_id' => '06',
            'task_type' => 'expression',
            'attempts_count' => 10,
            'correct_count' => 9,
            'mastery_score' => 0.9,
            'last_attempted_at' => now(),
        ]);

        StudentTopicMastery::create([
            'student_id' => $this->student->id,
            'topic_id' => '15',
            'task_type' => 'geometry',
            'attempts_count' => 8,
            'correct_count' => 2,
            'mastery_score' => 0.2,
            'last_attempted_at' => now(),
        ]);

        $service = app(StudentAnalyticsService::class);
        $weights = $service->getTopicWeights($this->student->id);

        $this->assertArrayHasKey('06', $weights);
        $this->assertArrayHasKey('15', $weights);
        $this->assertGreaterThan($weights['06'], $weights['15']); // Weak topic has higher weight
    }

    public function test_topic_weights_account_for_weak_subtypes_within_topic(): void
    {
        StudentTopicMastery::create([
            'student_id' => $this->student->id,
            'topic_id' => '16',
            'task_type' => 'geometry',
            'svg_type' => 'circle',
            'subtype' => 'inscribed_angle',
            'attempts_count' => 10,
            'correct_count' => 9,
            'mastery_score' => 0.95,
            'last_attempted_at' => now(),
        ]);
        StudentTopicMastery::create([
            'student_id' => $this->student->id,
            'topic_id' => '16',
            'task_type' => 'geometry',
            'svg_type' => 'circle',
            'subtype' => 'tangent_length',
            'attempts_count' => 10,
            'correct_count' => 4,
            'mastery_score' => 0.55,
            'last_attempted_at' => now(),
        ]);
        StudentTopicMastery::create([
            'student_id' => $this->student->id,
            'topic_id' => '17',
            'task_type' => 'geometry',
            'svg_type' => 'quadrilateral',
            'subtype' => 'parallel_lines',
            'attempts_count' => 20,
            'correct_count' => 15,
            'mastery_score' => 0.75,
            'last_attempted_at' => now(),
        ]);

        $service = app(StudentAnalyticsService::class);
        $weights = $service->getTopicWeights($this->student->id);

        $this->assertArrayHasKey('16', $weights);
        $this->assertArrayHasKey('17', $weights);
        $this->assertGreaterThan($weights['17'], $weights['16']);
    }

    // ----- Backward Compatibility -----

    public function test_submit_still_produces_scoring_rows(): void
    {
        $service = $this->makeService();

        $service->commitAnswer($this->attempt, 6, '5');
        $service->commitAnswer($this->attempt, 15, '45');

        $service->submitAttempt($this->attempt->fresh());

        // Verify scorings still work as before
        $scorings = OgeAttemptScoring::where('attempt_id', $this->attempt->id)->get();
        $this->assertGreaterThanOrEqual(2, $scorings->count());

        $scoring6 = $scorings->firstWhere('task_number', 6);
        $this->assertTrue($scoring6->is_correct);

        $scoring15 = $scorings->firstWhere('task_number', 15);
        $this->assertTrue($scoring15->is_correct);
    }

    public function test_submit_marks_answers_as_final(): void
    {
        $service = $this->makeService();

        $service->commitAnswer($this->attempt, 6, '5');
        $service->submitAttempt($this->attempt->fresh());

        $answer = OgeAttemptAnswer::where('attempt_id', $this->attempt->id)
            ->where('task_number', 6)
            ->first();
        $this->assertTrue($answer->is_final);
    }

    public function test_attempt_status_becomes_scored(): void
    {
        $service = $this->makeService();

        $service->commitAnswer($this->attempt, 6, '5');
        $result = $service->submitAttempt($this->attempt->fresh());

        $this->assertEquals('scored', $result->status);
    }

    private function makeService(): OgeAttemptService
    {
        return new OgeAttemptService(
            app(OgeVariantBuilderService::class),
            app(TaskAnswerResolver::class),
        );
    }
}
