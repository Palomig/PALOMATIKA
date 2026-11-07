<?php

namespace Tests\Feature;

use App\Services\LessonTaskPickerService;
use App\Services\TaskBankRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Варианты ответа в карточках выбора заданий.
 *
 * «Какому из данных промежутков принадлежит число…» в карточке пикера
 * показывало только условие и «Ответ: 3» — самих промежутков не было,
 * выбрать задачу вслепую нельзя (заметка Джарвиса #103). Карточка обязана
 * нести варианты: их номера совпадают с эталоном и с тем, что отправит
 * ученик на уроке.
 */
class PickerShowsChoiceOptionsTest extends TestCase
{
    use RefreshDatabase;

    private const TOPIC = '98';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        File::ensureDirectoryExists(storage_path('app/tasks'));
        File::put(storage_path('app/tasks/topic_' . self::TOPIC . '.json'), json_encode([
            'topic_id' => self::TOPIC,
            'blocks'   => [[
                'number'    => 1,
                'title'     => 'ФИПИ',
                'zadaniya'  => [[
                    'number'      => 6,
                    'instruction' => 'Определить принадлежность числовому промежутку',
                    'type'        => 'fipi',
                    'tasks'       => [
                        [
                            'id'     => 1,
                            'answer' => '3',
                            'html'   => '<p>Какому из данных промежутков принадлежит число $\\sqrt{7}$?</p>',
                            'options' => [
                                ['n' => 1, 'html' => '<p>$[1;2]$</p>'],
                                ['n' => 2, 'html' => '<p>$[2;3]$</p>'],
                                ['n' => 3, 'html' => '<p>$[3;4]$</p>'],
                                ['n' => 4, 'html' => '<p>$[4;5]$</p>'],
                            ],
                        ],
                    ],
                ]],
            ]],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function tearDown(): void
    {
        File::delete(storage_path('app/tasks/topic_' . self::TOPIC . '.json'));
        Cache::flush();
        TaskBankRepository::forgetTableCheck();
        parent::tearDown();
    }

    public function test_choice_task_card_carries_options(): void
    {
        $tasks = app(LessonTaskPickerService::class)->tasks('oge', ['topic_id' => self::TOPIC]);

        $this->assertCount(1, $tasks, 'задача с вариантами не попала в пикер');
        $card = $tasks[0];

        $this->assertCount(4, $card['options'], 'в карточке нет вариантов ответа');
        $this->assertSame('1', $card['options'][0]['id']);
        $this->assertSame('4', $card['options'][3]['id']);
    }

    public function test_option_labels_are_plain_latex_not_html(): void
    {
        $tasks = app(LessonTaskPickerService::class)->tasks('oge', ['topic_id' => self::TOPIC]);
        $card = $tasks[0];

        foreach ($card['options'] as $opt) {
            $this->assertStringNotContainsString('<p>', $opt['label'], 'разметка просочилась в подпись варианта');
            $this->assertStringContainsString('[', $opt['label'], 'подпись варианта потеряла содержимое');
        }
        $this->assertSame('$[3;4]$', $card['options'][2]['label']);
    }

    public function test_option_numbers_match_the_answer(): void
    {
        // Эталон — номер варианта: «Ответ: 3» обязан указывать на реальный
        // вариант, иначе учитель выбирает задачу по неверному содержанию.
        $tasks = app(LessonTaskPickerService::class)->tasks('oge', ['topic_id' => self::TOPIC]);
        $card = $tasks[0];

        $matching = array_filter(
            $card['options'],
            static fn (array $opt): bool => (string) $opt['id'] === (string) $card['answer'],
        );
        $this->assertCount(1, $matching, 'эталон не указывает ни на один вариант из карточки');
    }

    public function test_multi_select_statements_do_not_get_options(): void
    {
        // У утверждений перечень уже вшит в условие — дублировать его
        // вариантами нельзя.
        $topic = json_decode(File::get(storage_path('app/tasks/topic_' . self::TOPIC . '.json')), true);
        $topic['blocks'][0]['zadaniya'][0]['tasks'][0]['multi_select'] = true;
        File::put(storage_path('app/tasks/topic_' . self::TOPIC . '.json'), json_encode($topic, JSON_UNESCAPED_UNICODE));
        Cache::flush();

        $tasks = app(LessonTaskPickerService::class)->tasks('oge', ['topic_id' => self::TOPIC]);

        $this->assertSame([], $tasks[0]['options']);
    }
}
