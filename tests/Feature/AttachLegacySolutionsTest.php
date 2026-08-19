<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Models\TaskGroup;
use App\Services\TaskBankRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Перенос учительских разборов из отключённого банка на задания ФИПИ.
 * Автоматика ловит пары «один к одному», ручная карта — случаи, где
 * педагогическая перегруппировка слила несколько прежних серий в одну.
 */
class AttachLegacySolutionsTest extends TestCase
{
    use RefreshDatabase;

    private function group(string $source, string $topic, int $number, array $payload, string $text): TaskGroup
    {
        $group = TaskGroup::create([
            'bank' => 'oge',
            'topic' => $topic,
            'block_number' => 1,
            'block_title' => 'ФИПИ',
            'zadanie_number' => $number,
            'position' => $number,
            'type' => 'word_problem',
            'payload' => array_merge(['number' => $number], $payload),
            'source' => $source,
        ]);

        Task::create([
            'task_group_id' => $group->id,
            'position' => 0,
            'type' => 'word_problem',
            'payload' => ['id' => 1, 'text' => $text],
            'source' => $source,
        ]);

        return $group;
    }

    private function payloadOf(TaskGroup $group): array
    {
        return TaskGroup::query()->whereKey($group->id)->first()->payload ?? [];
    }

    public function test_merges_several_legacy_series_into_one_fipi_group(): void
    {
        // Тема 21: прежние серии 1 и 2 вошли в группу ФИПИ №1.
        $this->group(TaskBankRepository::RETIRED, '21', 1, [
            'section' => 'I) Движение по прямой',
            'solution' => '<p>Разбор туда-обратно</p>',
        ], 'Велосипедист выехал из А в В.');
        $this->group(TaskBankRepository::RETIRED, '21', 2, [
            'section' => 'II) Движение по прямой (навстречу)',
            'solution' => '<p>Разбор навстречу</p>',
        ], 'Из двух городов навстречу друг другу.');

        $target = $this->group('fipi', '21', 1, [
            'instruction' => 'Скорость и разность времени в пути',
            'taxonomy_key' => 'motion_line',
        ], 'Совсем другой текст условия.');

        $this->artisan('tasks:attach-legacy-solutions')->assertSuccessful();

        $solution = $this->payloadOf($target)['solution'] ?? '';
        $this->assertStringContainsString('Разбор туда-обратно', $solution);
        $this->assertStringContainsString('Разбор навстречу', $solution);
        // Подзаголовки подтипов — без кураторской нумерации прежнего банка.
        $this->assertStringContainsString('<h3 class="sol-part">Движение по прямой</h3>', $solution);
        $this->assertStringNotContainsString('I)', $solution);
    }

    public function test_carries_illustration_by_manual_map(): void
    {
        // Тема 23: чертёж серии 6 нужен группе ФИПИ №5, а текст условия
        // автоматика не сопоставила.
        $this->group(TaskBankRepository::RETIRED, '23', 6, [
            'section' => 'Биссектрисы углов A и B трапеции',
            'illustration' => '<svg id="trapezoid"></svg>',
        ], 'Биссектрисы углов A и B при боковой стороне AB.');

        $target = $this->group('fipi', '23', 5, [
            'instruction' => 'Биссектрисы углов трапеции',
            'taxonomy_key' => 'trapezoid_bisectors',
        ], 'Биссектрисы углов $A$ и $B$ при боковой стороне $AB$.');

        $this->artisan('tasks:attach-legacy-solutions')->assertSuccessful();

        $this->assertSame('<svg id="trapezoid"></svg>', $this->payloadOf($target)['illustration'] ?? null);
    }

    public function test_keeps_one_to_one_match_and_leaves_others_untouched(): void
    {
        $this->group(TaskBankRepository::RETIRED, '25', 1, [
            'section' => 'Трапеция и биссектриса',
            'solution' => '<p>Разбор 25</p>',
        ], 'В трапеции ABCD биссектриса угла A.');

        $matched = $this->group('fipi', '25', 1, [
            'instruction' => 'Биссектриса делит высоту',
        ], 'В трапеции ABCD биссектриса угла A.');   // тот же текст с другими числами

        $untouched = $this->group('fipi', '25', 2, [
            'instruction' => 'Совсем другой подтип',
        ], 'Окружность вписана в угол.');

        $this->artisan('tasks:attach-legacy-solutions')->assertSuccessful();

        $this->assertSame('<p>Разбор 25</p>', $this->payloadOf($matched)['solution'] ?? null);
        $this->assertArrayNotHasKey('solution', $this->payloadOf($untouched));
    }

    public function test_solution_page_titles_curated_group_by_its_own_instruction(): void
    {
        // Прежние короткие заголовки описывают серии старого банка: у группы
        // ФИПИ с тем же номером они врут, и в шапке их быть не должно.
        $this->group('fipi', '21', 3, [
            'instruction' => 'Средняя скорость: весь путь разделить на всё время',
            'taxonomy_key' => 'average_speed',
            'solution' => '<p>Разбор средней скорости</p>',
        ], 'Первые 450 км автомобиль ехал со скоростью 90 км/ч.');

        $teacher = User::create([
            'name' => 'T',
            'email' => 't+' . uniqid() . '@t.t',
            'password' => 'x',
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => random_int(100000000, 999999999),
        ]);

        $response = $this->actingAs($teacher)
            ->get('http://student.palomatika.ru/part2/solution/21/3');

        $response->assertOk();
        $response->assertSee('Средняя скорость: весь путь разделить на всё время');
        $response->assertSee('Разбор средней скорости', false);
        $response->assertDontSee('Движение вдогонку');
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->group(TaskBankRepository::RETIRED, '24', 11, [
            'section' => 'Две высоты, тупой угол',
            'solution' => '<p>Разбор высот</p>',
        ], 'В треугольнике ABC с тупым углом проведены высоты.');

        $target = $this->group('fipi', '24', 8, [
            'instruction' => 'Подобие и равные углы при высотах треугольника',
            'taxonomy_key' => 'altitudes',
        ], 'В треугольнике $ABC$ с тупым углом $ABC$ проведены высоты $AA_1$ и $CC_1$.');

        $this->artisan('tasks:attach-legacy-solutions', ['--dry-run' => true])->assertSuccessful();

        $this->assertArrayNotHasKey('solution', $this->payloadOf($target));
    }
}
