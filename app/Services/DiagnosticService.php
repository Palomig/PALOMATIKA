<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\UserSkill;

class DiagnosticService
{
    /** Количество вопросов на каждый листовой навык */
    private const QUESTIONS_PER_SKILL = 2;

    /** ELO-подобный коэффициент обучения для диагностики */
    private const K = 0.25;

    public function __construct(
        private readonly TaskDataService $taskDataService,
    ) {}

    /**
     * Сгенерировать диагностические вопросы, покрывающие все листовые навыки.
     * Каждый вопрос — задание из JSON банка, привязанное к skill_id.
     *
     * @return array<int, array{skill_id: int, skill_name: string, category: string, question: array, correct_answer: string}>
     */
    public function generateQuestions(): array
    {
        $leafSkills = Skill::active()
            ->whereNotNull('parent_id')
            ->whereDoesntHave('children')
            ->with('parent')
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get();

        $questions = [];

        foreach ($leafSkills as $skill) {
            $ogeNumbers = $skill->oge_numbers ?? [];
            if (empty($ogeNumbers)) {
                continue;
            }

            // Выбрать задания из связанных тем ОГЭ
            $picked = $this->pickTasksForSkill($skill, self::QUESTIONS_PER_SKILL);

            foreach ($picked as $task) {
                $questions[] = [
                    'skill_id' => $skill->id,
                    'skill_name' => $skill->name,
                    'category' => $skill->category ?? $skill->parent?->name ?? '',
                    'question' => $task,
                    'correct_answer' => $task['answer'] ?? '',
                ];
            }
        }

        // Перемешать, чтобы вопросы не шли кластерами по категориям
        shuffle($questions);

        return $questions;
    }

    /**
     * Выбрать N случайных production-заданий из тем ОГЭ, связанных с навыком.
     */
    private function pickTasksForSkill(Skill $skill, int $count): array
    {
        $ogeNumbers = $skill->oge_numbers ?? [];
        $allTasks = [];

        foreach ($ogeNumbers as $ogeNum) {
            $topicId = str_pad($ogeNum, 2, '0', STR_PAD_LEFT);
            $blocks = $this->taskDataService->getBlocks($topicId, 'production');

            foreach ($blocks as $block) {
                foreach ($block['zadaniya'] ?? [] as $zadanie) {
                    foreach ($zadanie['tasks'] ?? [] as $task) {
                        $allTasks[] = array_merge($task, [
                            'topic_id' => $topicId,
                            'block_number' => $block['number'] ?? null,
                            'zadanie_number' => $zadanie['number'] ?? null,
                        ]);
                    }
                }
            }
        }

        if (empty($allTasks)) {
            return [];
        }

        shuffle($allTasks);
        return array_slice($allTasks, 0, $count);
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
