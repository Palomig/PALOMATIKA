<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\UserSkill;
use Illuminate\Support\Facades\Storage;

class DiagnosticService
{
    private const STORAGE_PATH = 'diagnostic_test.json';

    public function __construct() {}

    /**
     * Вернуть вопросы из статичного теста, составленного учителем.
     * Читает storage/app/diagnostic_test.json.
     *
     * @return array<int, array{skill_id: int, skill_name: string, category: string, question: array, correct_answer: string}>
     */
    public function generateQuestions(): array
    {
        if (!Storage::exists(self::STORAGE_PATH)) {
            return [];
        }

        $saved = json_decode(Storage::get(self::STORAGE_PATH), true) ?? [];
        $questions = [];

        foreach ($saved as $entry) {
            $skillId = (int) ($entry['skill_id'] ?? 0);
            $skillName = $entry['skill_name'] ?? '';

            // Получить категорию из БД для красивого отображения
            $skill = Skill::find($skillId);
            $category = $skill?->category ?? $skill?->parent?->name ?? '';

            foreach ($entry['tasks'] ?? [] as $task) {
                $questions[] = [
                    'skill_id'     => $skillId,
                    'skill_name'   => $skillName,
                    'category'     => $category,
                    'question'     => $task,
                    'correct_answer' => $task['answer'] ?? '',
                ];
            }
        }

        return $questions;
    }

    /**
     * Проверить, составлен ли диагностический тест учителем.
     */
    public function isTestConfigured(): bool
    {
        if (!Storage::exists(self::STORAGE_PATH)) {
            return false;
        }
        $saved = json_decode(Storage::get(self::STORAGE_PATH), true) ?? [];
        return !empty($saved);
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
