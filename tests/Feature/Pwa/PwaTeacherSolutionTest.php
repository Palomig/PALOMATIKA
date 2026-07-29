<?php

namespace Tests\Feature\Pwa;

use App\Models\TaskGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Кнопка «Подробнее · для учителя» во второй части.
 *
 * Разбор задания писался руками и лежал в прежнем банке; банк ФИПИ собран из
 * открытого банка и его не несёт. После замены банка кнопка пропала — разборы
 * переносятся на соответствующие задания командой `tasks:attach-legacy-solutions`.
 */
class PwaTeacherSolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        if (!file_exists(storage_path('app/imports/bank_katex.json'))) {
            $this->markTestSkipped('нет выгрузки банка ФИПИ');
        }
        Artisan::call('tasks:import-json', ['--bank' => 'oge']);
        Artisan::call('tasks:import-fipi', ['--and-retire' => true]);
        Artisan::call('tasks:attach-legacy-solutions');
        Cache::flush();
    }

    public function test_solutions_are_carried_over_to_the_live_bank(): void
    {
        $withSolution = TaskGroup::query()
            ->where('bank', 'oge')->where('source', 'fipi')
            ->get()
            ->filter(fn (TaskGroup $g) => trim((string) ($g->payload['solution'] ?? '')) !== '');

        $this->assertGreaterThan(20, $withSolution->count(),
            'разборы не перенеслись на банк ФИПИ');
    }

    public function test_teacher_sees_the_button_and_student_does_not(): void
    {
        $group = TaskGroup::query()
            ->where('bank', 'oge')->where('source', 'fipi')->where('topic', '25')
            ->get()
            ->first(fn (TaskGroup $g) => trim((string) ($g->payload['solution'] ?? '')) !== '');

        $this->assertNotNull($group, 'в теме 25 не нашлось задания с разбором');

        $teacher = User::factory()->create(['role' => 'teacher', 'onboarding_completed_at' => now()]);
        $student = User::factory()->create(['role' => 'student', 'onboarding_completed_at' => now()]);

        $asTeacher = $this->actingAs($teacher)->get(route('pwa.student.part2', ['topic' => '25']));
        $asTeacher->assertOk();
        $asTeacher->assertSee('для учителя');

        $asStudent = $this->actingAs($student)->get(route('pwa.student.part2', ['topic' => '25']));
        $asStudent->assertOk();
        $asStudent->assertDontSee('для учителя');
    }

    public function test_solution_page_opens(): void
    {
        $group = TaskGroup::query()
            ->where('bank', 'oge')->where('source', 'fipi')->where('topic', '25')
            ->get()
            ->first(fn (TaskGroup $g) => trim((string) ($g->payload['solution'] ?? '')) !== '');

        $teacher = User::factory()->create(['role' => 'teacher', 'onboarding_completed_at' => now()]);

        $this->actingAs($teacher)
            ->get(route('pwa.student.part2.solution', [
                'topic' => '25',
                'number' => $group->zadanie_number,
            ]))
            ->assertOk();
    }
}
