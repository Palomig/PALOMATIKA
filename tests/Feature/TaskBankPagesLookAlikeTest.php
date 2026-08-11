<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Models\User;
use App\Services\TaskBankRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * База заданий ОГЭ и ЕГЭ рендерится одними шаблонами.
 *
 * У ЕГЭ был свой layout и своя копия партиалов: правки в ОГЭ до него не
 * доезжали, и разделы жили каждый своей жизнью. Теперь разметка общая, а
 * различаются только подписи, список номеров и маршруты.
 */
class TaskBankPagesLookAlikeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        foreach ([['ege', '03', 'Стереометрия'], ['ege', '13', 'Уравнение (часть 2)']] as [$bank, $topic, $title]) {
            TaskTopic::create([
                'bank' => $bank, 'grade' => null, 'topic' => $topic,
                'payload' => ['topic_id' => $topic, 'meta' => ['title' => $title, 'description' => 'Описание']],
            ]);
            $group = TaskGroup::create([
                'bank' => $bank, 'grade' => null, 'topic' => $topic,
                'block_number' => 1, 'block_title' => 'ФИПИ', 'zadanie_number' => 1,
                'position' => 0, 'instruction' => $title, 'type' => 'fipi',
                'payload' => ['instruction' => $title, 'type' => 'fipi', 'status' => 'production'],
                'status' => 'production', 'source' => 'fipi',
            ]);
            Task::create([
                'task_group_id' => $group->id, 'position' => 0, 'type' => 'fipi',
                'payload' => ['id' => 1, 'status' => 'production', 'answer' => '7',
                    'html' => '<p>Условие с формулой $x^2$.</p>'],
                'answer' => '7', 'answer_src' => 'codex', 'status' => 'production',
                'source' => 'fipi', 'fipi_guid' => str_pad($topic, 32, 'F'),
            ]);
        }
    }

    public function test_ege_topic_page_uses_the_shared_layout(): void
    {
        $page = $this->get(route('ege.show', ['id' => 13]));

        $page->assertOk()
            ->assertSee('Задание 13. Уравнение (часть 2)')
            ->assertSee('Назад к заданиям ЕГЭ')
            ->assertSee('Задачник ЕГЭ профиль 2026 (тренажер)');
    }

    public function test_ege_index_uses_the_shared_showcase(): void
    {
        $page = $this->get(route('ege.index'));

        $page->assertOk()
            ->assertSee('База заданий ЕГЭ')
            ->assertSee('Стереометрия')
            ->assertSee('→ ОГЭ');
    }

    public function test_oge_pages_are_intact(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher', 'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($teacher)->get(route('topics.index'))
            ->assertOk()
            ->assertSee('База заданий ОГЭ')
            ->assertSee('→ ЕГЭ', false);

        $this->actingAs($teacher)->get(route('topics.show', ['id' => 6]))
            ->assertOk()
            ->assertSee('Назад к темам');
    }

    public function test_styles_do_not_leak_into_the_page_body(): void
    {
        // Стек `styles` выводится после закрывающего </style>, поэтому push
        // обязан нести свой тег. Иначе правила печатались текстом поверх
        // вёрстки — и на ЕГЭ, и на ОГЭ.
        $html = $this->get(route('ege.show', ['id' => 13]))->getContent();
        $body = substr($html, strpos($html, '<body'));
        $afterStyles = substr($body, strrpos($body, '</style>') ?: 0);

        $this->assertStringNotContainsString('fipi-condition img', $afterStyles);
    }
}
