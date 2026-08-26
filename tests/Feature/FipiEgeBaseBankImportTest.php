<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Services\EgeTaskDataService;
use App\Services\LessonTaskPickerService;
use App\Services\TaskBankRepository;
use App\Services\TaskBankResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Импорт БАЗЫ ЕГЭ — второго уровня рядом с профилем.
 *
 * База живёт в собственном банке `ege_b`: нумерация заданий у уровней
 * разная (профиль 1–19, база 1–21), и задание 12 базы не имеет ничего
 * общего с заданием 12 профиля. Здесь проверяется именно разделение —
 * раскладка внутри темы уже проверена на профиле
 * ({@see FipiEgeBankImportTest}).
 */
class FipiEgeBaseBankImportTest extends TestCase
{
    use RefreshDatabase;

    private string $basePath;
    private string $profPath;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        $this->basePath = storage_path('app/imports/ege_base_katex_test.json');
        $this->profPath = storage_path('app/imports/ege_prof_katex_test.json');
        File::ensureDirectoryExists(dirname($this->basePath));
        File::put($this->basePath, json_encode($this->baseBank(), JSON_UNESCAPED_UNICODE));
        File::put($this->profPath, json_encode($this->profBank(), JSON_UNESCAPED_UNICODE));
    }

    protected function tearDown(): void
    {
        File::delete([$this->basePath, $this->profPath]);
        parent::tearDown();
    }

    /** @return array<string, mixed> */
    private function baseBank(): array
    {
        return [
            'source' => 'fipi-ege',
            'level' => 'base',
            'count' => 4,
            'tasks' => [
                [
                    'guid' => 'BBBB0000000000000000000000000001',
                    'task_no' => 14, 'task_title' => 'Вычисления',
                    'subtype_id' => 'b1', 'subtype_title' => 'Найдите значение выражения',
                    'html' => '<p>Найдите значение выражения $8\cdot 10{}^{-1}$.</p>',
                    'answer' => '0,8', 'answer_src' => 'calc',
                ],
                [
                    'guid' => 'BBBB0000000000000000000000000002',
                    'task_no' => 12, 'task_title' => 'Планиметрия',
                    'subtype_id' => 'b2', 'subtype_title' => 'Площадь по рисунку',
                    'html' => '<p>Рисунок: <img class="fipi-figure" src="img/BBBB02/pic.png" alt="рисунок"></p>',
                    'images' => ['img/BBBB02/pic.png'],
                    'answer' => '24', 'answer_src' => 'codex',
                ],
                [
                    // Ответов у базы пока нет вовсе — такие задачи обязаны
                    // уходить в черновики, иначе ученик получит задачу,
                    // которую нечем проверить.
                    'guid' => 'BBBB0000000000000000000000000003',
                    'task_no' => 21, 'task_title' => 'Текстовая задача повышенной сложности',
                    'subtype_id' => 'b3', 'subtype_title' => 'Перебор',
                    'html' => '<p>Найдите наименьшее число.</p>',
                ],
                [
                    // У профиля 19 номеров, у базы 21 — двадцать первый
                    // обязан импортироваться, а не отсечься по чужой границе.
                    'guid' => 'BBBB0000000000000000000000000004',
                    'task_no' => 22, 'task_title' => 'Такого номера нет',
                    'subtype_id' => 'b4', 'subtype_title' => 'Мимо',
                    'html' => '<p>Номера 22 у базы не бывает.</p>',
                    'answer' => '1',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function profBank(): array
    {
        return [
            'source' => 'fipi-ege',
            'level' => 'prof',
            'count' => 1,
            'tasks' => [[
                'guid' => 'AAAA0000000000000000000000000001',
                'task_no' => 12, 'task_title' => 'Наибольшее и наименьшее значение',
                'subtype_id' => 'p1', 'subtype_title' => 'Точка минимума',
                'html' => '<p>Найдите точку минимума функции.</p>',
                'answer' => '7', 'answer_src' => 'calc',
            ]],
        ];
    }

    private function importBase(array $options = []): int
    {
        return Artisan::call('tasks:import-fipi-ege', array_merge(
            ['--level' => 'base', '--file' => $this->basePath], $options));
    }

    public function test_base_lands_in_its_own_bank(): void
    {
        $this->assertSame(0, $this->importBase());

        $this->assertSame(['12', '14', '21'], TaskTopic::query()
            ->where('bank', EgeTaskDataService::BANK_BASE)->orderBy('topic')->pluck('topic')->all());
        $this->assertSame(0, TaskTopic::query()->where('bank', 'ege')->count(),
            'база не имеет права попасть в банк профиля');

        $topic = TaskTopic::query()->where('bank', EgeTaskDataService::BANK_BASE)
            ->where('topic', '14')->first();
        $this->assertSame('Вычисления', $topic->payload['meta']['title']);
        $this->assertSame('base', $topic->payload['level']);
    }

    public function test_task_number_21_is_imported_and_22_is_not(): void
    {
        $this->importBase();

        $this->assertNotNull(Task::query()
            ->where('fipi_guid', 'BBBB0000000000000000000000000003')->first(),
            'у базы 21 номер заданий — граница профиля здесь не годится');
        $this->assertNull(Task::query()
            ->where('fipi_guid', 'BBBB0000000000000000000000000004')->first());
    }

    public function test_task_without_answer_goes_to_draft(): void
    {
        $this->importBase();

        $this->assertSame('draft', Task::query()
            ->where('fipi_guid', 'BBBB0000000000000000000000000003')->first()->status);
        $this->assertSame('production', Task::query()
            ->where('fipi_guid', 'BBBB0000000000000000000000000001')->first()->status);
    }

    public function test_task_with_many_valid_answers_stays_draft(): void
    {
        File::put($this->basePath, json_encode([
            'source' => 'fipi-ege', 'level' => 'base', 'count' => 1,
            'tasks' => [[
                'guid' => 'BBBB0000000000000000000000000009',
                'task_no' => 19, 'task_title' => 'Свойства чисел',
                'subtype_id' => 'b9', 'subtype_title' => 'Вычеркните цифры',
                'html' => '<p>Вычеркните в числе 85417627 три цифры…</p>',
                // Ответ есть, но верных чисел много: автопроверка отвергла бы
                // другое подходящее, а ученик не понял бы, за что.
                'answer' => '8172', 'answer_kind' => 'any_valid',
            ]],
        ], JSON_UNESCAPED_UNICODE));

        $this->importBase();

        $this->assertSame('draft', Task::query()
            ->where('fipi_guid', 'BBBB0000000000000000000000000009')->first()->status);
    }

    public function test_images_go_to_their_own_public_folder(): void
    {
        $this->importBase();

        $this->assertStringContainsString('src="/ege-bank/img-base/BBBB02/pic.png"',
            Task::query()->where('fipi_guid', 'BBBB0000000000000000000000000002')
                ->first()->payload['html'],
            'каталог рисунков свой: переимпорт уровня чистит его папку');
    }

    public function test_levels_do_not_overwrite_each_other(): void
    {
        $this->importBase();
        Artisan::call('tasks:import-fipi-ege', ['--file' => $this->profPath]);

        $this->assertSame(3, TaskGroup::query()
            ->where('bank', EgeTaskDataService::BANK_BASE)->count(),
            'импорт профиля не должен трогать задания базы');

        // Номер 12 есть у обоих уровней и означает разные задания.
        $svcBase = new EgeTaskDataService(EgeTaskDataService::LEVEL_BASE);
        $svcProf = new EgeTaskDataService();
        $this->assertSame('Планиметрия',
            $svcBase->getTopicData('12')['meta']['title']);
        $this->assertSame('Наибольшее и наименьшее значение',
            $svcProf->getTopicData('12')['meta']['title']);
    }

    public function test_teacher_picker_offers_the_base_bank(): void
    {
        $this->importBase();
        Cache::flush();

        $picker = app(LessonTaskPickerService::class);
        $labels = array_column($picker->availableClasses(), 'label');
        $this->assertContains('10–11 ЕГЭ (Б)', $labels);

        $topics = $picker->topics(EgeTaskDataService::BANK_BASE);
        $this->assertSame(['12', '14', '21'], array_column($topics, 'id'));
        $this->assertSame([], $picker->sections(EgeTaskDataService::BANK_BASE),
            'база не делится на части: развёрнутых ответов у неё нет');

        $tasks = $picker->tasks(EgeTaskDataService::BANK_BASE, ['topic_id' => '14']);
        $this->assertCount(1, $tasks);
        $this->assertSame('№1 · Найдите значение выражения', $tasks[0]['group_label']);
    }

    public function test_resolved_task_is_labelled_as_base_level(): void
    {
        $this->importBase();
        Cache::flush();

        $resolved = app(TaskBankResolver::class)->resolve(EgeTaskDataService::BANK_BASE, [
            'topic_id' => '14', 'zadanie_number' => 1, 'task_id' => 1,
        ]);

        $this->assertStringStartsWith('ЕГЭ (Б) · Тема 14', $resolved['source_label'],
            'учитель обязан видеть уровень: задание 14 базы и профиля — разные задания');
    }
}
