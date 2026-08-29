<?php

namespace Tests\Feature\Pwa;

use App\Models\OgeVariant;
use App\Models\OgeAttempt;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Models\User;
use App\Services\EgeTaskDataService;
use App\Services\EgeVariantBuilderService;
use App\Services\EgeVariantPoolService;
use App\Services\TaskBankRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EgeLevelsTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'student',
            'grade_num' => 11,
            'onboarding_completed_at' => now(),
        ], $attributes));
    }

    private function activeAttempt(User $user, ?string $level, string $title, string $hash): OgeAttempt
    {
        $variant = OgeVariant::create([
            'hash' => $hash,
            'exam_type' => OgeVariant::EXAM_EGE,
            'level' => $level,
            'title' => $title,
            'source' => OgeVariant::SOURCE_MINIAPP,
            'config_json' => ['level' => $level, 'tasks' => [['id' => 1]]],
            'mode' => OgeVariant::MODE_FULL,
        ]);

        return OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $user->id,
            'status' => 'active',
            'started_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    private function bankTask(string $bank, string $topic, int $id): void
    {
        TaskTopic::firstOrCreate(
            ['bank' => $bank, 'grade' => null, 'topic' => $topic],
            ['payload' => ['topic_id' => $topic, 'meta' => ['title' => "Задание {$topic}"]]]
        );
        $group = TaskGroup::create([
            'bank' => $bank, 'grade' => null, 'topic' => $topic,
            'block_number' => 1, 'block_title' => 'ФИПИ', 'zadanie_number' => 1,
            'position' => 0, 'instruction' => 'Решите', 'type' => 'fipi',
            'payload' => ['instruction' => 'Решите', 'type' => 'fipi', 'status' => 'production'],
            'status' => 'production', 'source' => 'fipi',
        ]);
        Task::create([
            'task_group_id' => $group->id, 'position' => 0, 'type' => 'fipi',
            'payload' => ['id' => $id, 'html' => '<p>Условие</p>', 'answer' => '1', 'status' => 'production'],
            'answer' => '1', 'answer_src' => 'test', 'status' => 'production',
            'source' => 'fipi', 'fipi_guid' => str_pad((string) $id, 32, 'A'),
        ]);
        Cache::flush();
        TaskBankRepository::forgetTableCheck();
    }

    public function test_level_columns_exist_and_are_mass_assignable(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'ege_level'));
        $this->assertTrue(Schema::hasColumn('oge_variants', 'level'));

        $user = User::factory()->create(['ege_level' => 'base']);
        $variant = OgeVariant::create([
            'hash' => 'egelvl',
            'exam_type' => OgeVariant::EXAM_EGE,
            'level' => 'base',
            'title' => 'Вариант ЕГЭ (Б)',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'config_json' => ['level' => 'base', 'tasks' => []],
            'mode' => OgeVariant::MODE_FULL,
        ]);

        $this->assertSame('base', $user->fresh()->ege_level);
        $this->assertSame('base', $variant->fresh()->level);
    }

    public function test_ege_home_defaults_to_profile(): void
    {
        $user = $this->student(['ege_level' => null]);

        $this->actingAs($user)
            ->get('http://student.palomatika.ru/ege-app')
            ->assertOk()
            ->assertSee('ЕГЭ (П) · 11 класс');
    }

    public function test_ege_home_uses_stored_level(): void
    {
        $user = $this->student(['ege_level' => 'base']);

        $this->actingAs($user)
            ->get('http://student.palomatika.ru/ege-app')
            ->assertOk()
            ->assertSee('ЕГЭ (Б) · 11 класс');
    }

    public function test_explicit_level_overrides_and_persists_student_choice(): void
    {
        $user = $this->student(['ege_level' => 'prof']);

        $this->actingAs($user)
            ->get('http://student.palomatika.ru/ege-app?level=base')
            ->assertOk()
            ->assertSee('ЕГЭ (Б) · 11 класс');

        $this->assertSame('base', $user->fresh()->ege_level);
    }

    public function test_invalid_query_does_not_replace_stored_level(): void
    {
        $user = $this->student(['ege_level' => 'base']);

        $this->actingAs($user)
            ->get('http://student.palomatika.ru/ege-app?level=unknown')
            ->assertOk()
            ->assertSee('ЕГЭ (Б) · 11 класс');

        $this->assertSame('base', $user->fresh()->ege_level);
    }

    public function test_student_view_level_does_not_change_the_viewers_profile(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'grade_num' => 9,
            'ege_level' => 'prof',
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->get('http://student.palomatika.ru/ege-app?level=base')
            ->assertOk()
            ->assertSee('ЕГЭ (Б) · 11 класс');

        $this->assertSame('prof', $teacher->fresh()->ege_level);
    }

    public function test_home_has_one_level_scoped_full_variant_and_switcher(): void
    {
        $user = $this->student(['ege_level' => 'base']);

        $content = $this->actingAs($user)
            ->get('http://student.palomatika.ru/ege-app')
            ->assertOk()
            ->assertSee('Профиль (П)')
            ->assertSee('База (Б)')
            ->assertSee('Задания 1–21, как на экзамене')
            ->getContent();

        $this->assertSame(1, substr_count($content, '<div class="tile-name">Полный вариант</div>'));
        $this->assertStringContainsString('/ege-app?level=prof', $content);
        $this->assertStringContainsString('/ege-app?level=base', $content);
    }

    public function test_home_only_shows_active_attempts_for_selected_level(): void
    {
        $user = $this->student(['ege_level' => 'base']);
        $this->activeAttempt($user, 'base', 'Продолжить базу', 'base01');
        $this->activeAttempt($user, 'prof', 'Не показывать профиль', 'prof01');
        $this->activeAttempt($user, null, 'Старый профиль', 'oldp01');

        $this->actingAs($user)
            ->get('http://student.palomatika.ru/ege-app')
            ->assertOk()
            ->assertSee('Продолжить базу')
            ->assertDontSee('Не показывать профиль')
            ->assertDontSee('Старый профиль');

        $this->actingAs($user)
            ->get('http://student.palomatika.ru/ege-app?level=prof')
            ->assertOk()
            ->assertDontSee('Продолжить базу')
            ->assertSee('Не показывать профиль')
            ->assertSee('Старый профиль');
    }

    public function test_pool_creates_a_level_scoped_mini_ege_variant(): void
    {
        $user = $this->student();
        $this->bankTask(EgeTaskDataService::BANK_PROF, '01', 101);
        $data = new EgeTaskDataService(EgeTaskDataService::LEVEL_PROF);
        $pool = new EgeVariantPoolService($data, new EgeVariantBuilderService($data));

        $variant = $pool->getOrCreateVariant($user, 'geometry');

        $this->assertSame('prof', $variant->level);
        $this->assertSame(OgeVariant::MODE_MINI_GEOMETRY, $variant->mode);
        $this->assertSame('Мини-ЕГЭ (П) — Геометрия', $variant->title);
        $this->assertSame('geometry', $variant->config_json['mini_mode']);
        $numbers = array_column($variant->config_json['tasks'], 'task_number');
        $this->assertNotEmpty($numbers);
        $this->assertCount(count($numbers), array_unique($numbers));
        $this->assertEmpty(array_diff($numbers, [1, 2, 3]));
    }
}
