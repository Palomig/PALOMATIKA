<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use App\Services\DiagnosticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_diagnostic_questions(): void
    {
        // Засеиваем навыки
        $root = Skill::create(['name' => 'Алгебра', 'slug' => 'algebra', 'category' => 'Алгебра']);
        $child1 = Skill::create(['name' => 'Сложение дробей', 'slug' => 'slozenie-drobei', 'category' => 'Алгебра', 'parent_id' => $root->id, 'oge_numbers' => ['06']]);
        $child2 = Skill::create(['name' => 'Умножение дробей', 'slug' => 'umnozenie-drobei', 'category' => 'Алгебра', 'parent_id' => $root->id, 'oge_numbers' => ['06']]);

        $service = app(DiagnosticService::class);
        $questions = $service->generateQuestions();

        $this->assertNotEmpty($questions);
        $this->assertArrayHasKey('skill_id', $questions[0]);
        $this->assertArrayHasKey('question', $questions[0]);
        $this->assertArrayHasKey('correct_answer', $questions[0]);
    }

    public function test_processes_results_and_fills_user_skills(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $root = Skill::create(['name' => 'Алгебра', 'slug' => 'algebra', 'category' => 'Алгебра']);
        $skill = Skill::create(['name' => 'Сложение дробей', 'slug' => 'slozenie-drobei', 'category' => 'Алгебра', 'parent_id' => $root->id, 'oge_numbers' => ['06']]);

        $service = app(DiagnosticService::class);
        $service->processResults($user->id, [
            ['skill_id' => $skill->id, 'is_correct' => true],
            ['skill_id' => $skill->id, 'is_correct' => false],
        ]);

        $userSkill = UserSkill::where('user_id', $user->id)->where('skill_id', $skill->id)->first();
        $this->assertNotNull($userSkill);
        $this->assertEquals(2, $userSkill->attempts_count);
        $this->assertEquals(1, $userSkill->correct_count);
        $this->assertGreaterThan(0, (float) $userSkill->weight);
    }
}
