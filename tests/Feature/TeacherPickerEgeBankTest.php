<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Services\LessonTaskPickerService;
use App\Services\TaskBankRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Выбор задач учителем: переключение между банками и содержимое ЕГЭ.
 *
 * Инструмент один на все классы, банк выбирается вкладкой класса. После
 * переезда ЕГЭ в базу список тем нельзя строить по файлам: у ФИПИ 19
 * номеров заданий, а файла topic_03 в прежнем банке не было вовсе — тема
 * «Стереометрия» у учителя просто отсутствовала.
 */
class TeacherPickerEgeBankTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        foreach ([['01', 'Планиметрия'], ['03', 'Стереометрия'], ['15', 'Неравенство']] as [$topic, $title]) {
            TaskTopic::create([
                'bank' => 'ege', 'grade' => null, 'topic' => $topic,
                'payload' => ['topic_id' => $topic, 'meta' => ['title' => $title]],
            ]);
            $group = TaskGroup::create([
                'bank' => 'ege', 'grade' => null, 'topic' => $topic,
                'block_number' => 1, 'block_title' => 'ФИПИ', 'zadanie_number' => 1,
                'position' => 0, 'instruction' => $title, 'type' => 'fipi',
                'payload' => ['instruction' => $title, 'type' => 'fipi', 'status' => 'production'],
                'status' => 'production', 'source' => 'fipi',
            ]);
            Task::create([
                'task_group_id' => $group->id, 'position' => 0, 'type' => 'fipi',
                'payload' => ['id' => 1, 'status' => 'production', 'answer' => '7',
                    'html' => "<p>Условие темы {$topic}: угол \$ABC\$ равен \$60^\\circ\$.</p>"],
                'answer' => '7', 'answer_src' => 'codex', 'status' => 'production',
                'source' => 'fipi', 'fipi_guid' => str_pad($topic, 32, 'E'),
            ]);
        }
    }

    public function test_ege_appears_among_teacher_classes(): void
    {
        $classes = app(LessonTaskPickerService::class)->availableClasses();

        $banks = array_column($classes, 'bank');
        $this->assertContains('ege', $banks, 'вкладка ЕГЭ обязана быть, раз задачи есть');
        $this->assertContains('oge', $banks);

        $ege = collect($classes)->firstWhere('bank', 'ege');
        $this->assertSame('10–11 ЕГЭ', $ege['label']);
    }

    public function test_topics_come_from_the_database_after_the_move(): void
    {
        $topics = app(LessonTaskPickerService::class)->topics('ege');

        $this->assertSame(['01', '03', '15'], array_column($topics, 'id'),
            'по файлам темы 03 не было вовсе — она есть только в базе');
        $this->assertSame('Неравенство', collect($topics)->firstWhere('id', '15')['title']);
    }

    public function test_ege_is_split_into_exam_parts(): void
    {
        $picker = app(LessonTaskPickerService::class);

        $this->assertSame(['1я часть', '2я часть'],
            array_column($picker->sections('ege'), 'title'),
            'у ЕГЭ 19 номеров заданий: листать их подряд неудобно, как и в ОГЭ');

        $part1 = array_column($picker->topics('ege', null, 'part1'), 'id');
        $part2 = array_column($picker->topics('ege', null, 'part2'), 'id');

        $this->assertSame(['01', '03'], $part1);
        $this->assertSame(['15'], $part2, 'краткий и развёрнутый ответ разведены по частям');
    }

    public function test_vpr_has_no_sections(): void
    {
        $this->assertSame([], app(LessonTaskPickerService::class)->sections('vpr'));
    }

    public function test_task_card_carries_the_drawing(): void
    {
        // Чертёж банка ЕГЭ — растр внутри разметки условия; карточка выбора
        // оставалась без рисунка, потому что искали только инлайновый SVG.
        $group = TaskGroup::where('bank', 'ege')->where('topic', '01')->first();
        $group->tasks()->update(['payload' => [
            'id' => 1, 'status' => 'production', 'answer' => '7',
            'html' => '<p>Условие.<img class="fipi-figure" src="/ege-bank/img/A/pic.png" alt="рисунок"></p>',
        ]]);
        Cache::flush();

        $tasks = app(LessonTaskPickerService::class)->tasks('ege', ['topic_id' => '01']);

        $this->assertSame('/ege-bank/img/A/pic.png', $tasks[0]['image']);
    }

    public function test_inline_labels_survive_in_the_card(): void
    {
        $group = TaskGroup::where('bank', 'ege')->where('topic', '15')->first();
        $group->tasks()->update(['payload' => [
            'id' => 1, 'status' => 'production', 'answer' => '7',
            'html' => '<p>В пирамиде <img class="fipi-inline" src="/ege-bank/img/A/s.png" alt="рисунок">'
                . ' сторона равна 8.</p>',
        ]]);
        Cache::flush();

        $tasks = app(LessonTaskPickerService::class)->tasks('ege', ['topic_id' => '15']);

        $this->assertStringContainsString('fipi-inline', $tasks[0]['expression'],
            'без обозначений условие в карточке рассыпается');
    }

    public function test_tasks_of_a_topic_are_offered_to_the_teacher(): void
    {
        $tasks = app(LessonTaskPickerService::class)->tasks('ege', ['topic_id' => '01']);

        $this->assertCount(1, $tasks);
        $this->assertStringContainsString('Условие темы 01', $tasks[0]['expression'],
            'условие банка ФИПИ лежит в html — его нужно развернуть в текст');
        $this->assertSame('7', $tasks[0]['answer']);
    }
}
