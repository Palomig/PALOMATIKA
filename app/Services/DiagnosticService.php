<?php

namespace App\Services;

use App\Data\DiagnosticQuestionBank;
use App\Models\Skill;
use App\Models\UserSkill;
use Illuminate\Support\Facades\Storage;

class DiagnosticService
{
    private const STORAGE_PATH = 'diagnostic_test.json';

    public function __construct() {}

    /**
     * Вернуть вопросы для диагностического теста.
     * Использует банк MC-вопросов (DiagnosticQuestionBank).
     *
     * @return array<int, array{skill_id: int, skill_name: string, category: string, type: string, question: array, correct_answer: string}>
     */
    public function generateQuestions(): array
    {
        $bank = DiagnosticQuestionBank::all();
        $questions = [];

        // Загружаем все нужные навыки одним запросом
        $skillIds = array_keys($bank);
        $skills = Skill::whereIn('id', $skillIds)->where('is_active', true)->get()->keyBy('id');

        foreach ($bank as $skillId => $mc) {
            $skill = $skills->get($skillId);
            if (!$skill) continue;

            $questions[] = [
                'skill_id'       => $skillId,
                'skill_name'     => $skill->name,
                'category'       => $skill->category ?? '',
                'type'           => 'mc',
                'question'       => [
                    'question' => $mc['question'],
                    'choices'  => $mc['choices'],
                ],
                'correct_answer' => (string) $mc['correct'],
            ];
        }

        return $questions;
    }

    /**
     * Диагностический тест всегда доступен (банк вопросов встроен).
     */
    public function isTestConfigured(): bool
    {
        return !empty(DiagnosticQuestionBank::all());
    }

    /**
     * Обработать результаты диагностики и заполнить user_skills.
     *
     * @param array<int, array{skill_id: int, is_correct: bool}> $results
     */
    public function processResults(int $userId, array $results): void
    {
        // Группировка по навыку
        $bySkill = [];
        foreach ($results as $r) {
            $bySkill[$r['skill_id']][] = $r['is_correct'];
        }

        foreach ($bySkill as $skillId => $answers) {
            $correct = count(array_filter($answers));
            $total = count($answers);
            $accuracy = $total > 0 ? $correct / $total : 0;

            // Начальный вес на основе точности диагностики
            $weight = round($accuracy, 3);

            UserSkill::updateOrCreate(
                ['user_id' => $userId, 'skill_id' => $skillId],
                [
                    'weight' => $weight,
                    'attempts_count' => $total,
                    'correct_count' => $correct,
                    'last_practiced_at' => now(),
                ]
            );
        }

        // Создать записи с нулевым весом для навыков, которые не были протестированы
        $testedSkillIds = array_keys($bySkill);
        $untestedSkills = Skill::active()
            ->whereNotNull('parent_id')
            ->whereDoesntHave('children')
            ->whereNotIn('id', $testedSkillIds)
            ->pluck('id');

        foreach ($untestedSkills as $skillId) {
            UserSkill::firstOrCreate(
                ['user_id' => $userId, 'skill_id' => $skillId],
                ['weight' => 0, 'attempts_count' => 0, 'correct_count' => 0]
            );
        }
    }

    /**
     * Проверить, прошёл ли пользователь диагностику.
     */
    public function hasCompletedDiagnostic(int $userId): bool
    {
        return UserSkill::where('user_id', $userId)->exists();
    }
}
