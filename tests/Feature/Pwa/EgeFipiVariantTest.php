<?php

namespace Tests\Feature\Pwa;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Models\User;
use App\Services\EgeVariantBuilderService;
use App\Services\TaskBankRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
