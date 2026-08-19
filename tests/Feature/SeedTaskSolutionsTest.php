<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Разборы второй части пишутся файлами в storage/app/tasks/solutions,
 * а живут в payload группы: команда переносит одно в другое.
 */
class SeedTaskSolutionsTest extends TestCase
{
    use RefreshDatabase;

    private function group(string $topic, int $number, string $source = 'fipi'): TaskGroup
    {
        $group = TaskGroup::create([
            'bank' => 'oge',
            'topic' => $topic,
            'block_number' => 1,
            'zadanie_number' => $number,
            'position' => $number,
            'type' => 'word_problem',
            'payload' => ['number' => $number, 'instruction' => 'Тест'],
            'source' => $source,
        ]);

        Task::create([
            'task_group_id' => $group->id,
            'position' => 0,
            'type' => 'word_problem',
            'payload' => ['id' => 1, 'text' => 'Условие'],
            'source' => $source,
        ]);

        return $group;
    }

    private function solutionOf(TaskGroup $group): string
    {
        return (string) ((TaskGroup::query()->whereKey($group->id)->first()->payload ?? [])['solution'] ?? '');
    }

    public function test_writes_authored_solution_into_group_payload(): void
    {
        $group = $this->group('23', 3);
        $expected = trim(File::get(storage_path('app/tasks/solutions/oge/topic_23/3.html')));

        $this->artisan('tasks:seed-solutions', ['--topic' => '23'])->assertSuccessful();

        $this->assertSame($expected, $this->solutionOf($group));
    }

    public function test_dry_run_and_repeat_change_nothing(): void
    {
        $group = $this->group('23', 5);

        $this->artisan('tasks:seed-solutions', ['--topic' => '23', '--dry-run' => true])->assertSuccessful();
        $this->assertSame('', $this->solutionOf($group));

        $this->artisan('tasks:seed-solutions', ['--topic' => '23'])->assertSuccessful();
        $first = $this->solutionOf($group);

        // Повторный прогон идемпотентен: та же запись, ничего не дублируется.
        $this->artisan('tasks:seed-solutions', ['--topic' => '23'])->assertSuccessful();
        $this->assertSame($first, $this->solutionOf($group));
        $this->assertNotSame('', $first);
    }

    public function test_does_not_touch_retired_bank(): void
    {
        $retired = $this->group('23', 7, 'palomatika_legacy');

        $this->artisan('tasks:seed-solutions', ['--topic' => '23'])->assertSuccessful();

        $this->assertSame('', $this->solutionOf($retired));
    }

    /** Каждый написанный разбор — с чертежом и блоком ответа. */
    public function test_every_authored_file_has_a_figure_and_an_answer(): void
    {
        $files = File::files(storage_path('app/tasks/solutions/oge/topic_23'));
        $this->assertCount(12, $files);

        foreach ($files as $file) {
            $html = File::get($file->getPathname());
            $name = $file->getFilename();
            $this->assertStringContainsString('<div class="answer">', $html, $name);
            $this->assertStringContainsString('<div class="sol-figure">', $html, $name);
            // Чертёж — в стилистике заданий 24 и 25: тёмная подложка и та же палитра.
            $this->assertStringContainsString('fill="#0a1628"', $html, $name);
            $this->assertMatchesRegularExpression('/<svg[^>]+viewBox="0 0 340 \\d+"/', $html, $name);
            // Столько же чертежей, сколько разобранных задач.
            $this->assertSame(
                substr_count($html, '<p><i>'),
                substr_count($html, '<div class="sol-figure">'),
                $name
            );
        }
    }
}
