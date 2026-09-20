<?php

namespace Tests\Feature;

use App\Models\LessonSession;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use App\Services\LessonSessionService;
use App\Services\LessonTaskPickerService;
use App\Services\SkillsTaskDataService;
use App\Services\TaskBankRepository;
use App\Services\TaskBankResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Банк «Скиллы»: файл репозитория → база → вкладка пикера → задача в
 * домашке и на уроке. Первая тема — «Десятичные дроби», сто примеров по
 * три действия в каждом.
 */
class SkillsBankTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();
    }

    private function import(): void
    {
        $this->artisan('tasks:import-skills')->assertSuccessful();
        Cache::flush();
    }

    public function test_decimals_file_imports_one_hundred_examples_with_three_operations_each(): void
    {
        $this->import();

        $this->assertSame(4, TaskGroup::where('bank', 'skills')->where('topic', '01')->count());
        $this->assertSame(100, Task::whereHas('group', fn ($q) => $q->where('bank', 'skills'))->count());

        $tasks = Task::whereHas('group', fn ($q) => $q->where('bank', 'skills'))->get();
        foreach ($tasks as $task) {
            $expr = (string) $task->payload['expression'];
            $ops = preg_match_all('/(?<![\\\\{])[+\-:]|\\\\cdot/u', $expr);
            $this->assertSame(3, $ops, "в примере не три действия: {$expr}");
            $this->assertMatchesRegularExpression('/^-?\d+(,\d+)?$/', (string) $task->answer,
                "ответ не десятичная дробь: {$task->answer}");
            $this->assertSame('production', $task->payload['status'], 'статус обязан лежать в payload');
        }
    }

    public function test_import_is_idempotent(): void
    {
        $this->import();
        $this->import();

        $this->assertSame(100, Task::whereHas('group', fn ($q) => $q->where('bank', 'skills'))->count());
    }

    public function test_skills_tab_appears_after_the_exams(): void
    {
        $this->assertNotContains('skills',
            array_column(app(LessonTaskPickerService::class)->availableClasses(), 'bank'),
            'без задач вкладка не показывается');

        $this->import();

        $classes = app(LessonTaskPickerService::class)->availableClasses();
        $tab = collect($classes)->firstWhere('bank', 'skills');
        $this->assertNotNull($tab);
        $this->assertSame('Скиллы', $tab['label']);
        $this->assertNull($tab['grade'], 'навыки сквозные, класса у вкладки нет');
        $this->assertSame('skills', end($classes)['bank'], 'вкладка стоит последней, после экзаменов');
    }

    public function test_picker_lists_decimals_topic_and_its_tasks(): void
    {
        $this->import();
        $picker = app(LessonTaskPickerService::class);

        $topics = $picker->topics('skills');
        $this->assertSame([['01', 'Десятичные дроби']],
            array_map(fn ($t) => [$t['id'], $t['title']], $topics));
        $this->assertStringContainsString('$', $topics[0]['preview'], 'превью темы — первый пример');

        $tasks = $picker->tasks('skills', ['topic_id' => '01']);
        $this->assertCount(100, $tasks);
        $this->assertSame(4, count(array_unique(array_column($tasks, 'group_key'))),
            'четыре задания по набору действий');
        $this->assertStringContainsString('Сложение, вычитание и умножение', $tasks[0]['group_label']);
        $this->assertNotSame('', $tasks[0]['answer']);
    }

    public function test_picker_refs_resolve_for_homework_and_lesson(): void
    {
        $this->import();
        $first = app(LessonTaskPickerService::class)->tasks('skills', ['topic_id' => '01'])[0];

        // refs ровно как их собирает фронтовый taskRefs() в task-picker.blade.php
        $refs = ['topic_id' => '01', 'zadanie_number' => $first['zadanie_number'], 'task_id' => $first['id']];
        $resolved = app(TaskBankResolver::class)->resolve('skills', $refs);

        $this->assertSame('expression', $resolved['type']);
        $this->assertSame($first['expression'], $resolved['expression']);
        $this->assertSame($first['answer'], $resolved['answer']);
        $this->assertStringStartsWith('Скиллы · Десятичные дроби · Задание 1.', $resolved['source_label']);

        // На урок задача садится без «Data truncated for column 'bank'».
        $teacher = User::create(['name' => 'T', 'email' => 'skills@example.test', 'password' => 'x', 'role' => 'teacher']);
        $session = LessonSession::create(['teacher_id' => $teacher->id, 'status' => LessonSession::STATUS_DRAFT, 'join_code' => '4816']);
        $task = app(LessonSessionService::class)->addTask($session, 'skills', $refs);

        $this->assertSame('skills', $task->fresh()->bank);
        $this->assertSame($first['answer'], $task->correct_answer);
    }

    public function test_topic_meta_comes_from_the_file(): void
    {
        $this->import();

        $svc = new SkillsTaskDataService();
        $this->assertSame('Десятичные дроби', $svc->getTopicMeta('01')['title']);
        $this->assertSame('01', $svc->getTopicData('01')['topic_id']);
    }
}
