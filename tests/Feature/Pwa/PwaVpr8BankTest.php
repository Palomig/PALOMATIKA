<?php

namespace Tests\Feature\Pwa;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\User;
use App\Services\VprTaskDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ВПР 8 класса: вариант 4 2026 года и демоверсия 2025. Задания с развёрнутым
 * решением (№13–18) и отметкой на прямой (№4, №6) проверяются вручную.
 */
class PwaVpr8BankTest extends TestCase
{
    use RefreshDatabase;

    private const MANUAL_TOPICS = ['04', '06', '13', '14', '15', '16', '17', '18'];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_bank_has_both_variants_for_all_18_numbers(): void
    {
        $data = new VprTaskDataService(8);

        for ($n = 1; $n <= 18; $n++) {
            $topic = str_pad((string) $n, 2, '0', STR_PAD_LEFT);
            $sources = [];
            foreach ($data->getBlocks($topic) as $block) {
                foreach ($block['zadaniya'] as $zadanie) {
                    foreach ($zadanie['tasks'] as $task) {
                        $sources[] = $task['source'];
                        $manual = in_array($topic, self::MANUAL_TOPICS, true);
                        $this->assertSame($manual, !empty($task['manual']), "№{$n}");
                        if ($manual) {
                            $this->assertArrayNotHasKey('answer', $task, "№{$n}: эталон у ручного задания");
                            $this->assertNotEmpty($task['answer_display'], "№{$n}");
                            $this->assertNotEmpty($task['solution'], "№{$n}");
                        } else {
                            $this->assertNotSame('', (string) ($task['answer'] ?? ''), "№{$n}");
                        }
                    }
                }
            }
            $this->assertContains('ВПР 2026, вариант 4', $sources, "№{$n}");
            $this->assertContains('Демоверсия ВПР 2025', $sources, "№{$n}");
        }
    }

    public function test_full_variant_scores_only_auto_checked_tasks(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'grade_num' => 8,
            'onboarding_completed_at' => now(),
        ]);

        $start = $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/vpr/full/start');
        $start->assertOk();
        preg_match('~/vpr/test/(\d+)~', (string) $start->json('redirect'), $m);
        $attempt = OgeAttempt::with('variant')->findOrFail((int) $m[1]);

        $this->assertSame('vpr_8', $attempt->variant->exam_type);
        $tasks = $attempt->variant->config_json['tasks'];
        $this->assertCount(18, $tasks);

        // Ответы: №11 — кириллическая «Н»/«А», остальное — эталон или пусто.
        $answers = [];
        foreach ($tasks as $i => $task) {
            $slot = $i + 1;
            if (!empty($task['manual'])) {
                $this->assertNull($task['correct_answer'], 'ручное задание без эталона');
                continue;
            }
            $correct = (string) $task['correct_answer'];
            $answers[$slot] = match ($correct) {
                'H или L' => 'Н',
                'A' => 'А',
                '-1012 или -1210' => '-1210',
                default => $correct,
            };
        }

        $this->actingAs($student)
            ->postJson("/api/oge/attempts/{$attempt->id}/submit", ['answers' => $answers, 'elapsed' => 60])
            ->assertOk();

        $scorings = OgeAttemptScoring::where('attempt_id', $attempt->id)->get()->keyBy('task_number');
        foreach ($tasks as $i => $task) {
            $scoring = $scorings->get($i + 1);
            if (!empty($task['manual'])) {
                $this->assertTrue($scoring === null || $scoring->is_correct === null, "№{$task['task_number']} не проверяется");
            } else {
                $this->assertTrue((bool) $scoring?->is_correct, "№{$task['task_number']}: ответ «{$answers[$i + 1]}»");
            }
        }

        $results = $this->actingAs($student)
            ->get("http://student.palomatika.ru/vpr/results/{$attempt->id}");
        $results->assertOk();
        $results->assertSee('вручную');
        $results->assertSee('>10<', false); // все 10 автопроверяемых — верно
    }

    public function test_manual_task_shows_paper_note_instead_of_input(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'grade_num' => 8,
            'onboarding_completed_at' => now(),
        ]);

        $start = $this->actingAs($student)->postJson('http://student.palomatika.ru/vpr/full/start');
        preg_match('~/vpr/test/(\d+)~', (string) $start->json('redirect'), $m);

        $page = $this->actingAs($student)->get("http://student.palomatika.ru/vpr/test/{$m[1]}");
        $page->assertOk();
        $page->assertSee('Реши в тетради');
        $page->assertSee('"manual":true', false);
    }
}
