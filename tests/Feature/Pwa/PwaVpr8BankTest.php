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
 * ВПР 8 класса: вариант 4 2026 года и демоверсия 2025. Задания, которые на
 * ВПР проверяются вручную (№4, №6, №13–18), у нас — выбор из четырёх.
 */
class PwaVpr8BankTest extends TestCase
{
    use RefreshDatabase;

    private const CHOICE_TOPICS = ['04', '06', '13', '14', '15', '16', '17', '18'];

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
                    $choice = in_array($topic, self::CHOICE_TOPICS, true);
                    $this->assertSame($choice ? 'choice' : 'word_problem', $zadanie['type'], "№{$n}");
                    foreach ($zadanie['tasks'] as $task) {
                        $sources[] = $task['source'];
                        $this->assertArrayNotHasKey('manual', $task, "№{$n}");
                        $this->assertNotSame('', (string) ($task['answer'] ?? ''), "№{$n}");
                        if ($choice) {
                            $this->assertCount(4, $task['options'], "№{$n}");
                            $this->assertContains($task['answer'], ['1', '2', '3', '4'], "№{$n}");
                        }
                    }
                }
            }
            $this->assertContains('ВПР 2026, вариант 4', $sources, "№{$n}");
            $this->assertContains('Демоверсия ВПР 2025', $sources, "№{$n}");
        }
    }

    /** Ответ так, как его отправит экран: id варианта или его номер. */
    private function uiAnswer(array $servedTask, string $bankAnswer): string
    {
        if (empty($servedTask['options'])) {
            return $bankAnswer;
        }
        $index = (int) $bankAnswer - 1;
        $option = $servedTask['options'][$index];
        $id = is_array($option) ? trim((string) ($option['id'] ?? '')) : '';

        return $id !== '' ? $id : (string) ($index + 1);
    }

    public function test_full_variant_scores_every_task_including_choices(): void
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
        $bankTasks = $attempt->variant->config_json['tasks'];
        $this->assertCount(18, $bankTasks);

        $page = $this->actingAs($student)->get("http://student.palomatika.ru/vpr/test/{$attempt->id}");
        $page->assertOk();
        preg_match('/tasks:\s*(\[[\s\S]*?\])\s*,\s*attemptId:/', $page->getContent(), $tm);
        $served = json_decode($tm[1], true);
        $this->assertCount(18, $served);

        // №11 — кириллическая «Н»/«А», выбор — как нажмёт ученик.
        $answers = [];
        foreach ($bankTasks as $i => $task) {
            $raw = (string) $task['answer'];
            $answers[$i + 1] = match ($raw) {
                'H или L' => 'Н',
                'A' => 'А',
                '-1012 или -1210' => '-1210',
                default => $this->uiAnswer($served[$i], $raw),
            };
        }

        $this->actingAs($student)
            ->postJson("/api/oge/attempts/{$attempt->id}/submit", ['answers' => $answers, 'elapsed' => 60])
            ->assertOk();

        $scorings = OgeAttemptScoring::where('attempt_id', $attempt->id)->get()->keyBy('task_number');
        foreach ($bankTasks as $i => $task) {
            $this->assertTrue((bool) $scorings->get($i + 1)?->is_correct, "№{$task['task_number']}: ответ «{$answers[$i + 1]}»");
        }

        $results = $this->actingAs($student)
            ->get("http://student.palomatika.ru/vpr/results/{$attempt->id}");
        $results->assertOk();
        $results->assertDontSee('"manual":true', false);
        $results->assertSee('>18<', false); // все 18 — верно
    }

    public function test_graph_task_fits_the_screen(): void
    {
        foreach ((new VprTaskDataService(8))->getBlocks('11') as $block) {
            foreach ($block['zadaniya'][0]['tasks'] as $task) {
                $this->assertTrue($task['svg_fit'] ?? false, 'граф №11 без svg_fit растянется на 880px');
            }
        }
    }
}
