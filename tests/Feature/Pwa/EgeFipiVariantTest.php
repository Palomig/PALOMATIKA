<?php

namespace Tests\Feature\Pwa;

use App\Models\OgeVariant;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Models\User;
use App\Services\EgeVariantBuilderService;
use App\Services\TaskBankRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use App\Services\EgeTaskDataService;
use Tests\TestCase;

/**
 * Вариант ЕГЭ, собранный из банка ФИПИ.
 *
 * У этих задач нет ни `text`, ни `expression`, ни `svg` — всё условие лежит
 * в `html`. Экран решения строился вокруг прежних полей, и без отдельной
 * ветки ученик увидел бы только заголовок задания и пустое место вместо
 * условия.
 */
class EgeFipiVariantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        TaskTopic::create([
            'bank' => 'ege', 'grade' => null, 'topic' => '01',
            'payload' => ['topic_id' => '01', 'meta' => ['title' => 'Планиметрия']],
        ]);
        $group = TaskGroup::create([
            'bank' => 'ege', 'grade' => null, 'topic' => '01',
            'block_number' => 1, 'block_title' => 'ФИПИ', 'zadanie_number' => 1,
            'position' => 0, 'instruction' => 'Треугольник', 'type' => 'fipi',
            'payload' => ['instruction' => 'Треугольник', 'type' => 'fipi', 'status' => 'production'],
            'status' => 'production', 'source' => 'fipi',
        ]);
        Task::create([
            'task_group_id' => $group->id, 'position' => 0, 'type' => 'fipi',
            'payload' => [
                'id' => 1,
                'html' => '<p>Угол $ABC$ равен $103^\circ$. '
                    . '<img class="fipi-figure" src="/ege-bank/img/x/pic.png" alt="рисунок"></p>',
                'answer' => '61',
                'status' => 'production',
            ],
            'answer' => '61', 'answer_src' => 'codex',
            'status' => 'production', 'source' => 'fipi',
            'fipi_guid' => 'BBBB0000000000000000000000000001',
        ]);
    }

    public function test_variant_carries_the_condition_markup(): void
    {
        $built = app(EgeVariantBuilderService::class)->build('testhash01');

        $task = collect($built['tasks'])->firstWhere('topic_id', '01');
        $this->assertNotNull($task, 'задача ФИПИ должна попасть в вариант');
        $this->assertStringContainsString('Угол $ABC$', $task['html'],
            'условие целиком лежит в html: без него ученик увидит пустое место');
        $this->assertSame('61', $task['correct_answer']);
    }

    /**
     * Плитка «Полный вариант» отправляет fetch с `Accept: application/json`
     * и ждёт `{redirect: …}` — как ВПР. Контроллер отвечал только редиректом:
     * fetch шёл по нему сам, получал HTML страницы теста, и `res.json()`
     * падал на «Unexpected token '<'». Ученик видел «Ошибка соединения», и
     * вариант не запускался вовсе. Обычный POST (без Accept) при этом
     * работал, поэтому тест обязан ходить именно как приложение.
     */
    public function test_start_answers_the_app_with_json(): void
    {
        $user = User::factory()->create([
            'role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('pwa.student.ege.start'));

        $response->assertOk();
        $response->assertJsonStructure(['redirect']);

        $redirect = $response->json('redirect');
        $this->assertNotEmpty($redirect);
        $this->actingAs($user)->get($redirect)->assertOk();
    }

    public function test_part_two_gets_the_symbol_pad(): void
    {
        // Ответ части 2 — корни с π или множество промежутков; таких знаков
        // на клавиатуре телефона нет, и без панели ученик их не наберёт.
        $group = TaskGroup::where('bank', 'ege')->first();
        $group->update(['topic' => '13']);
        TaskTopic::where('bank', 'ege')->update(['topic' => '13']);

        $user = User::factory()->create([
            'role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now(),
        ]);
        $page = $this->followRedirects(
            $this->actingAs($user)->post(route('pwa.student.ege.start'))
        );

        $page->assertSee('data-mathpad="ege2"', false)
            ->assertSee('hasMathAnswer', false);
    }

    public function test_home_screen_matches_the_other_banks(): void
    {
        $user = User::factory()->create([
            'role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now(),
        ]);

        $page = $this->actingAs($user)->get(route('pwa.student.ege.home'));

        // Те же блоки, что на домашних экранах ОГЭ и ВПР: приветствие,
        // полоса Premium, плитки разделов. Раньше здесь были заголовок и две
        // кнопки — ученик попадал будто в другой продукт.
        $page->assertOk()
            ->assertSee('greeting-badge', false)
            ->assertSee('premium-strip', false)
            ->assertSee('tiles-grid', false)
            ->assertSee('База заданий')
            ->assertSee('ЕГЭ (П) · 11 класс');

        // Класс и число заданий берутся из данных, а не из констант ОГЭ:
        // на экране стояло «9 класс · 20 заданий».
        $page->assertDontSee('9 класс')
            ->assertDontSee('20 заданий');
    }

    public function test_task_database_is_a_screen_inside_the_app(): void
    {
        // Плитка «База заданий» раньше уводила на сайт (/ege), то есть из
        // приложения наружу. У ОГЭ и ВПР это экран внутри PWA.
        $user = User::factory()->create([
            'role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($user)->get(route('pwa.student.ege.home'))
            ->assertSee('ege-app/tasks', false);

        $this->actingAs($user)->get(route('pwa.student.ege.tasks'))
            ->assertOk()
            ->assertSee('База заданий ЕГЭ')
            ->assertSee('Выбери задание');
    }

    public function test_task_database_is_split_into_exam_parts(): void
    {
        $user = User::factory()->create([
            'role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now(),
        ]);

        // Вход в базу предлагает части, как в ОГЭ.
        $home = $this->actingAs($user)
            ->get(route('pwa.student.ege.home'))
            ->getContent();

        $this->assertStringContainsString('ege-app/tasks?level=prof&amp;part=1', $home);
        $this->assertStringContainsString('ege-app/tasks?level=prof&amp;part=2', $home);

        // В первой части номера 1–12, во второй 13–19: краткий ответ и
        // развёрнутый смешивать нельзя, у них разный формат ответа.
        // Ссылки сравниваем по фрагменту: в разметке «&» экранируется.
        $first = $this->actingAs($user)->get(route('pwa.student.ege.tasks', ['part' => 1]))->getContent();
        $this->assertStringContainsString('Задания 1–12 · краткий ответ', $first);
        $this->assertStringContainsString('topic=12', $first);
        $this->assertStringNotContainsString('topic=13', $first);

        $second = $this->actingAs($user)->get(route('pwa.student.ege.tasks', ['part' => 2]))->getContent();
        $this->assertStringContainsString('Задания 13–19 · развёрнутый ответ', $second);
        $this->assertStringContainsString('topic=13', $second);
        $this->assertStringNotContainsString('topic=12', $second);
    }

    public function test_task_database_separates_primary_formulas_only_for_topics_13_and_15(): void
    {
        $user = User::factory()->create([
            'role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now(),
        ]);
        $task = Task::query()->firstOrFail();

        $renderTopic = function (int $topic, string $html) use ($user, $task): string {
            $topicId = str_pad((string) $topic, 2, '0', STR_PAD_LEFT);
            TaskTopic::query()->update(['topic' => $topicId]);
            TaskGroup::query()->update(['topic' => $topicId]);
            $task->update(['payload' => array_merge($task->payload, ['html' => $html])]);
            Cache::flush();
            TaskBankRepository::forgetTableCheck();

            return $this->actingAs($user)
                ->get(route('pwa.student.ege.tasks', ['level' => 'prof', 'part' => 2, 'topic' => $topic]))
                ->assertOk()
                ->getContent();
        };

        $topic13 = $renderTopic(
            13,
            '<p>а) Решите уравнение $x^2-1=0$.</p><p>б) Найдите корни на отрезке $[0;5]$.</p>'
        );
        $this->assertStringContainsString('Решите уравнение<br class="fipi-primary-formula-break">$x^2-1=0$.', $topic13);
        $this->assertStringContainsString('отрезке $[0;5]$.', $topic13);

        $topic15 = $renderTopic(15, '<p>Решите неравенство $\log_3(x)\leq 2$.</p>');
        $this->assertStringContainsString('Решите неравенство<br class="fipi-primary-formula-break">', $topic15);

        $topic14 = $renderTopic(14, '<p>Найдите объём $V=12$.</p>');
        $this->assertStringNotContainsString('fipi-primary-formula-break', $topic14);
    }

    public function test_task_database_keeps_financial_data_as_a_table_on_mobile(): void
    {
        $user = User::factory()->create([
            'role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now(),
        ]);
        TaskTopic::query()->update(['topic' => '16']);
        TaskGroup::query()->update(['topic' => '16']);
        $task = Task::query()->firstOrFail();
        $task->update(['payload' => array_merge($task->payload, [
            'html' => '<p>Условия возврата:</p><table><tbody><tr>'
                . '<td>Месяц и год</td><td>Июль 2016</td><td>Июль 2017</td>'
                . '</tr><tr><td>Долг</td><td>$S$</td><td>$0{,}6S$</td>'
                . '</tr></tbody></table>',
        ])]);
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        $content = $this->actingAs($user)
            ->get(route('pwa.student.ege.tasks', ['level' => 'prof', 'part' => 2, 'topic' => 16]))
            ->assertOk()
            ->assertSee('<table>', false)
            ->getContent();

        // Таблица остаётся таблицей, а узкая карточка прокручивается по
        // горизонтали. Общий display:block склеивал все ячейки в строку.
        $this->assertStringContainsString('.fipi-table-scroll', $content);
        $this->assertStringNotContainsString(
            '.fipi-condition table, .fipi-condition tbody,',
            $content
        );
        $this->assertSame(1, substr_count($content, '<style>'));
    }

    public function test_base_level_uses_its_own_task_database_entry(): void
    {
        // Уровень выбирается на всём экране ЕГЭ. После выбора базы её плитка
        // ведёт прямо в отдельный банк ФИПИ с нумерацией 1–21.
        $user = User::factory()->create([
            'role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now(),
        ]);

        $topic = TaskTopic::create([
            'bank' => EgeTaskDataService::BANK_BASE, 'grade' => null, 'topic' => '21',
            'payload' => ['topic_id' => '21', 'level' => 'base',
                          'meta' => ['title' => 'Текстовая задача повышенной сложности']],
        ]);
        $group = TaskGroup::create([
            'bank' => EgeTaskDataService::BANK_BASE, 'grade' => null, 'topic' => '21',
            'block_number' => 1, 'block_title' => 'ФИПИ', 'zadanie_number' => 1,
            'position' => 0, 'instruction' => 'Перебор', 'type' => 'fipi',
            'payload' => ['instruction' => 'Перебор', 'type' => 'fipi',
                          'status' => 'production'],
            'status' => 'production', 'source' => 'fipi',
        ]);
        Task::create([
            'task_group_id' => $group->id, 'position' => 0, 'type' => 'fipi',
            'payload' => ['id' => 1, 'status' => 'production', 'answer' => '7',
                          'html' => '<p>Найдите наименьшее число.</p>'],
            'answer' => '7', 'answer_src' => 'claude', 'status' => 'production',
            'source' => 'fipi', 'fipi_guid' => str_pad('B21', 32, 'C'),
        ]);
        $this->assertNotNull($topic->id);

        $this->actingAs($user)->get(route('pwa.student.ege.home', ['level' => 'base']))
            ->assertSee(route('pwa.student.ege.tasks', ['level' => 'base']), false);

        $page = $this->actingAs($user)
            ->get(route('pwa.student.ege.tasks', ['level' => 'base']))
            ->assertOk()
            ->assertSee('База заданий ЕГЭ (Б)')
            ->assertSee('Задания 1–21 · краткий ответ')
            ->getContent();

        // Номер 21 есть только у базы: у профиля номера кончаются на 19.
        $this->assertStringContainsString('topic=21', $page);
    }

    public function test_base_variant_is_built_from_the_base_bank(): void
    {
        // Полный вариант базы — свой банк и своя нумерация. Уровень пишется
        // внутрь варианта: по нему анти-повтор отличает банки, у которых
        // номера заданий совпадают.
        $user = User::factory()->create([
            'role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now(),
        ]);

        TaskTopic::create([
            'bank' => EgeTaskDataService::BANK_BASE, 'grade' => null, 'topic' => '21',
            'payload' => ['topic_id' => '21', 'level' => 'base',
                          'meta' => ['title' => 'Текстовая задача повышенной сложности']],
        ]);
        $group = TaskGroup::create([
            'bank' => EgeTaskDataService::BANK_BASE, 'grade' => null, 'topic' => '21',
            'block_number' => 1, 'block_title' => 'ФИПИ', 'zadanie_number' => 1,
            'position' => 0, 'instruction' => 'Перебор', 'type' => 'fipi',
            'payload' => ['instruction' => 'Перебор', 'type' => 'fipi',
                          'status' => 'production'],
            'status' => 'production', 'source' => 'fipi',
        ]);
        Task::create([
            'task_group_id' => $group->id, 'position' => 0, 'type' => 'fipi',
            'payload' => ['id' => 1, 'status' => 'production', 'answer' => '7',
                          'html' => '<p>Найдите наименьшее число.</p>'],
            'answer' => '7', 'answer_src' => 'claude', 'status' => 'production',
            'source' => 'fipi', 'fipi_guid' => str_pad('V21', 32, 'E'),
        ]);
        Cache::flush();

        $this->actingAs($user)
            ->postJson(route('pwa.student.ege.start'), ['level' => 'base'])
            ->assertOk()
            ->assertJsonStructure(['redirect']);

        $variant = OgeVariant::query()->latest('id')->first();
        $this->assertSame('Вариант ЕГЭ (Б)', $variant->title);
        $this->assertSame('base', $variant->level,
            'индексируемая колонка нужна фильтру незавершённых попыток');
        $this->assertSame('base', $variant->config_json['level']);
        $numbers = array_column($variant->config_json['tasks'], 'task_number');
        $this->assertSame([21], $numbers,
            'в варианте базы только её задания — профильных номеров там быть не может');
    }

    public function test_test_screen_renders_the_condition(): void
    {
        $user = User::factory()->create([
            'role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('pwa.student.ege.start'));
        $response->assertRedirect();

        $this->followRedirects($response)
            ->assertSee('q-fipi', false)
            ->assertSee('currentTask.html', false);
    }
}
