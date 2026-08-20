<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneratePrintVariantsCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $out;

    protected function setUp(): void
    {
        parent::setUp();

        $this->out = sys_get_temp_dir() . '/oge-print-test-' . getmypid();

        $group = TaskGroup::create([
            'bank' => 'oge', 'topic' => '06', 'block_number' => 1,
            'zadanie_number' => 1, 'type' => 'fipi', 'status' => 'production',
        ]);

        foreach (range(1, 4) as $i) {
            Task::create([
                'task_group_id' => $group->id,
                // Два абзаца: на них проверяется сшивка задания запретом разрыва.
                'payload' => ['html' => "<p>Найдите значение выражения \$\\dfrac{{$i}}{2}\$.</p>"
                    . '<p>Ответ округлите до десятых.</p>'],
                'answer' => (string) $i,
                'status' => 'production',
            ]);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->out . '/*') ?: [] as $path) {
            is_dir($path) ? array_map('unlink', glob($path . '/*') ?: []) && rmdir($path) : unlink($path);
        }
        @rmdir($this->out);

        parent::tearDown();
    }

    /** Сборку .tex проверяем без pdflatex: в CI его нет и не должно быть. */
    public function test_builds_tex_and_answer_key(): void
    {
        $this->artisan('oge:print', [
            '--count' => 2,
            '--seed' => 42,
            '--topics' => '06',
            '--out' => $this->out,
            '--no-pdf' => true,
            '--keep-tex' => true,
        ])->assertSuccessful();

        $tex = $this->out . '/build-1/variant.tex';
        $this->assertFileExists($tex);

        $body = file_get_contents($tex);
        $this->assertStringContainsString('\begin{document}', $body);
        $this->assertStringContainsString('\begin{zadanie}{6}', $body);
        $this->assertStringContainsString('\dfrac', $body);

        // Абзацы задания сшиты запретом разрыва: иначе условие расползается
        // по двум листам, а чертёж и строка ответа уезжают от своего номера.
        $this->assertStringContainsString('\nopagebreak', $body);

        // Преамбула и метрики кладутся рядом — сборка не должна зависеть от cwd.
        $this->assertFileExists($this->out . '/build-1/preamble.tex');
        $this->assertFileExists($this->out . '/build-1/params.tex');

        $key = json_decode((string) file_get_contents($this->out . '/answers.json'), true);
        $this->assertCount(2, $key);
        $this->assertArrayHasKey(6, $key[1]['answers']);
        $this->assertSame(42, $key[1]['seed']);
        $this->assertSame(43, $key[2]['seed']);
    }

    public function test_unknown_topic_is_reported_instead_of_empty_pdf(): void
    {
        $this->artisan('oge:print', [
            '--topics' => '99',
            '--out' => $this->out,
            '--no-pdf' => true,
        ])->assertFailed();
    }
}
