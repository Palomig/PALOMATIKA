<?php

namespace Tests\Feature;

use App\Models\LessonSession;
use App\Models\Task;
use App\Models\User;
use App\Services\LessonTaskPickerService;
use App\Services\TaskBankRepository;
use App\Services\TaskBankResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FipiLessonTaskResolveTest extends TestCase
{
    private const TEACHER_BASE = 'http://teacher.palomatika.ru';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createMinimalSchema();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        if (!file_exists(storage_path('app/imports/bank_katex.json'))) {
            $this->markTestSkipped('нет выгрузки банка ФИПИ');
        }

        Artisan::call('tasks:import-fipi', ['--and-retire' => true]);
        Cache::flush();
    }

    /**
     * Схема восстанавливается после теста.
     *
     * `createMinimalSchema()` сносит базу целиком и поднимает свой урезанный
     * набор таблиц: в нём у `users` нет ни `grade_num`, ни телеграм-полей.
     * Без восстановления база оставалась такой для следующих тестов, и они
     * падали на вставке пользователя — по отдельности проходя. Это и делало
     * красным полный прогон.
     */
    protected function tearDown(): void
    {
        Artisan::call('migrate:fresh');
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        parent::tearDown();
    }

    private function createMinimalSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('student');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('task_topics', function (Blueprint $table): void {
            $table->id();
            $table->string('bank', 8);
            $table->unsignedTinyInteger('grade')->nullable();
            $table->string('topic', 4);
            $table->json('payload')->nullable();
            $table->timestamps();
        });
        Schema::create('task_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('topic_id', 4);
            $table->string('task_key', 120);
            $table->string('status', 16)->default('draft');
            $table->timestamps();
        });
        Schema::create('task_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('bank', 8);
            $table->unsignedTinyInteger('grade')->nullable();
            $table->string('topic', 4);
            $table->unsignedSmallInteger('block_number');
            $table->string('block_title')->nullable();
            $table->unsignedSmallInteger('zadanie_number');
            $table->unsignedSmallInteger('position')->default(0);
            $table->text('instruction')->nullable();
            $table->string('type', 40);
            $table->string('svg_type', 40)->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 16)->default('draft');
            $table->string('source', 24)->default('palomatika');
            $table->timestamps();
        });
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_group_id');
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('type', 40)->nullable();
            $table->json('payload')->nullable();
            $table->text('answer')->nullable();
            $table->string('answer_src', 16)->nullable();
            $table->string('status', 16)->default('draft');
            $table->string('source', 24)->default('palomatika');
            $table->char('fipi_guid', 32)->nullable()->unique();
            $table->string('legacy_task_key')->nullable();
            $table->json('svg_model')->nullable();
            $table->timestamps();
        });
        Schema::create('lesson_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id');
            $table->string('status')->default('draft');
            $table->string('join_code', 4)->nullable();
            $table->timestamps();
        });
        Schema::create('lesson_session_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_session_id');
            $table->foreignId('assigned_student_id')->nullable();
            $table->unsignedInteger('position');
            $table->string('bank');
            $table->unsignedTinyInteger('grade')->nullable();
            $table->string('topic_id', 16)->nullable();
            $table->string('skill_slug', 64)->nullable();
            $table->string('task_ref', 255);
            $table->json('task_payload');
            $table->text('correct_answer');
            $table->timestamps();
        });
    }

    public function test_teacher_can_add_fipi_geometry_task_from_picker_to_lesson(): void
    {
        $teacher = User::create([
            'name' => 'T',
            'email' => 'fipi-lesson@example.test',
            'password' => 'x',
            'role' => 'teacher',
        ]);
        $session = LessonSession::create([
            'teacher_id' => $teacher->id,
            'status' => LessonSession::STATUS_DRAFT,
            'join_code' => '4815',
        ]);

        $tasks = app(LessonTaskPickerService::class)
            ->tasks('oge', ['topic_id' => '16'], 'part1');
        $picked = collect($tasks)->first(
            static fn (array $task) => $task['image_svg'] !== ''
        );
        $this->assertNotNull($picked, 'В теме 16 должна быть задача с чертежом');

        $response = $this->actingAs($teacher)->postJson(
            self::TEACHER_BASE . "/lessons/{$session->id}/tasks",
            [
                'bank' => 'oge',
                'refs' => [
                    'topic_id' => '16',
                    'zadanie_number' => $picked['zadanie_number'],
                    'task_id' => $picked['id'],
                ],
            ]
        );

        $response->assertCreated();
        $response->assertJsonPath('task.task_payload.type', 'expression');
        $this->assertNotSame('', trim((string) $response->json('task.task_payload.expression')));
        $this->assertStringContainsString(
            '<svg',
            (string) $response->json('task.task_payload.image_svg')
        );
    }

    public function test_fipi_single_choice_uses_numeric_option_ids_and_html_labels(): void
    {
        $picker = app(LessonTaskPickerService::class);
        $picked = $picker->tasks('oge', ['topic_id' => '19'], 'part1')[0];

        $resolved = app(TaskBankResolver::class)->resolve('oge', [
            'topic_id' => '19',
            'zadanie_number' => $picked['zadanie_number'],
            'task_id' => $picked['id'],
        ]);

        $this->assertSame('choice', $resolved['type']);
        $this->assertSame('1', $resolved['options'][0]['id']);
        $this->assertNotSame('', trim($resolved['options'][0]['label']));
        $this->assertStringNotContainsString('<p>', $resolved['options'][0]['label']);
    }

    public function test_fipi_multi_select_uses_text_input_with_numbered_statements(): void
    {
        $task = Task::all()->first(
            static fn (Task $candidate) => !empty($candidate->payload['multi_select'])
        );
        $this->assertNotNull($task, 'В теме 19 должна быть задача с несколькими ответами');

        $resolved = app(TaskBankResolver::class)->resolve('oge', [
            'topic_id' => $task->group->topic,
            'zadanie_number' => $task->group->zadanie_number,
            'task_id' => $task->payload['id'],
        ]);

        $this->assertSame('expression', $resolved['type']);
        $this->assertStringContainsString('1)', $resolved['expression']);
        $this->assertStringContainsString('2)', $resolved['expression']);
    }

    public function test_teacher_can_add_tasks_from_split_and_merged_topic_16_groups(): void
    {
        $teacher = User::create([
            'name' => 'Taxonomy teacher',
            'email' => 'taxonomy-lesson@example.test',
            'password' => 'x',
            'role' => 'teacher',
        ]);
        $session = LessonSession::create([
            'teacher_id' => $teacher->id,
            'status' => LessonSession::STATUS_DRAFT,
            'join_code' => '1616',
        ]);

        $representativeGuids = [
            // Исходный subtype 11 разделён: описанный четырёхугольник → группа 12.
            '23DB227D40FFBF0F4A51F13E4FE84C4E',
            // Исходные subtype 12 и 18 объединены общей формулой r = a√3/6.
            '00975F104F1792504C505CDEFABB3563',
            '0369CB6A6A6BBEA44B84323F497738DD',
        ];

        foreach ($representativeGuids as $guid) {
            $source = Task::query()->with('group')->where('fipi_guid', $guid)->firstOrFail();

            $response = $this->actingAs($teacher)->postJson(
                self::TEACHER_BASE . "/lessons/{$session->id}/tasks",
                [
                    'bank' => 'oge',
                    'refs' => [
                        'topic_id' => '16',
                        'zadanie_number' => $source->group->zadanie_number,
                        'task_id' => $source->payload['id'],
                    ],
                ],
            );

            $response->assertCreated();
            $response->assertJsonPath('task.task_payload.type', 'expression');
            $this->assertNotSame('', trim((string) $response->json('task.task_payload.expression')));
            $this->assertNotSame('', trim((string) $response->json('task.correct_answer')));
            $this->assertStringContainsString(
                '<svg',
                (string) $response->json('task.task_payload.image_svg'),
            );
        }
    }
}
