<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TaskDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Проверка ответа в разделе «2я часть ОГЭ»: ученик вводит ответ, сервер
 * сверяет его через TaskAnswerResolver (и, для №20/23, MathAnswerParser).
 */
class Part2AnswerCheckTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://student.palomatika.ru';

    private function student(): User
    {
        return User::create([
            'name' => 'S',
            'email' => 's+' . uniqid() . '@t.t',
            'password' => 'x',
            'role' => 'student',
            'onboarding_completed_at' => now(),
            // без привязки pwa.telegram-link уводит на /link-telegram
            'telegram_chat_id' => random_int(100000000, 999999999),
        ]);
    }

    /**
     * Первая задача с корнем в ответе из указанной темы.
     *
     * @return array{0:int,1:int,2:string} номер задания, id задачи, эталон
     */
    private function radicalTask(string $topic): array
    {
        $data = app(TaskDataService::class)->getTopicData($topic);
        foreach (($data['blocks'] ?? []) as $block) {
            foreach (($block['zadaniya'] ?? []) as $zadanie) {
                foreach (($zadanie['tasks'] ?? []) as $task) {
                    $answer = (string) ($task['answer'] ?? '');
                    if ($answer !== '' && (str_contains($answer, 'sqrt') || str_contains($answer, '√'))) {
                        return [(int) $zadanie['number'], (int) $task['id'], $answer];
                    }
                }
            }
        }

        $this->fail("В теме {$topic} нет заданий с корнем в ответе");
    }

    public function test_accepts_equivalent_radical_notation(): void
    {
        [$zadanie, $taskId, $answer] = $this->radicalTask('23');
        // «12sqrt(6)» → ученик набирает «12√6».
        $typed = str_replace(['sqrt(', ')'], ['√', ''], $answer);

        $response = $this->actingAs($this->student())->postJson(self::BASE . '/part2/check', [
            'topic' => '23',
            'zadanie' => $zadanie,
            'task_id' => $taskId,
            'answer' => $typed,
        ]);

        $response->assertOk()->assertJson(['status' => 'checked', 'correct' => true]);
    }

    public function test_rejects_wrong_answer(): void
    {
        [$zadanie, $taskId] = $this->radicalTask('23');

        $response = $this->actingAs($this->student())->postJson(self::BASE . '/part2/check', [
            'topic' => '23',
            'zadanie' => $zadanie,
            'task_id' => $taskId,
            'answer' => '1',
        ]);

        $response->assertOk()->assertJson(['status' => 'checked', 'correct' => false]);
    }

    public function test_reveal_returns_reference_answer(): void
    {
        [$zadanie, $taskId, $answer] = $this->radicalTask('23');

        $response = $this->actingAs($this->student())->postJson(self::BASE . '/part2/check', [
            'topic' => '23',
            'zadanie' => $zadanie,
            'task_id' => $taskId,
            'reveal' => true,
        ]);

        $response->assertOk()->assertJson(['status' => 'revealed', 'answer' => $answer]);
    }

    public function test_unknown_task_is_not_found(): void
    {
        $response = $this->actingAs($this->student())->postJson(self::BASE . '/part2/check', [
            'topic' => '23',
            'zadanie' => 1,
            'task_id' => 999999,
            'answer' => '1',
        ]);

        $response->assertNotFound();
    }

    public function test_part1_topic_is_rejected(): void
    {
        $response = $this->actingAs($this->student())->postJson(self::BASE . '/part2/check', [
            'topic' => '06',
            'zadanie' => 1,
            'task_id' => 1,
            'answer' => '1',
        ]);

        $response->assertNotFound();
    }

    /** №23 — ответ с корнем: поле ввода получает панель символов. */
    public function test_topic_with_radicals_renders_input_with_pad(): void
    {
        $response = $this->actingAs($this->student())->get(self::BASE . '/part2?topic=23');

        $response->assertOk()
            ->assertSee('class="p2-input"', false)
            ->assertSee('data-mathpad="roots"', false)
            ->assertSee('p2-check', false);
    }

    /** №20 — множества и промежутки: расширенный набор символов. */
    public function test_inequality_topic_gets_full_pad(): void
    {
        $this->actingAs($this->student())
            ->get(self::BASE . '/part2?topic=20')
            ->assertOk()
            ->assertSee('data-mathpad="full"', false);
    }

    /** №21 — ответ всегда обычное число, панель не нужна. */
    public function test_plain_topic_has_input_without_pad(): void
    {
        $response = $this->actingAs($this->student())->get(self::BASE . '/part2?topic=21');

        $response->assertOk()
            ->assertSee('class="p2-input"', false)
            ->assertDontSee('data-mathpad="', false);
    }

    /** Ученику ответ сразу не показывается — только после «Показать ответ». */
    public function test_student_does_not_see_answer_upfront(): void
    {
        [, , $answer] = $this->radicalTask('23');

        $this->actingAs($this->student())
            ->get(self::BASE . '/part2?topic=23')
            ->assertOk()
            ->assertDontSee($answer, false)
            ->assertSee('p2-reveal', false);
    }

    public function test_guest_cannot_check(): void
    {
        $this->postJson(self::BASE . '/part2/check', [
            'topic' => '23',
            'zadanie' => 1,
            'task_id' => 1,
            'answer' => '1',
        ])->assertStatus(401);
    }
}
