<?php

namespace App\Http\Controllers;

use App\Services\AlgTaskDataService;

class AlgTopicController extends Controller
{
    /**
     * Список всех тем по классам (5–8) для алгебры.
     */
    public function index()
    {
        $gradeData = [];

        foreach (AlgTaskDataService::GRADES as $grade) {
            $service = new AlgTaskDataService($grade);
            $topics  = [];

            foreach ($service->getExistingTopicIds() as $topicId) {
                $topics[$topicId] = array_merge(
                    $service->getTopicMeta($topicId),
                    [
                        'exists' => true,
                        'stats'  => $service->getTopicStats($topicId),
                    ]
                );
            }

            $gradeData[$grade] = ['topics' => $topics];
        }

        return view('alg-topics.index', compact('gradeData'));
    }

    /**
     * Детальная страница темы для класса.
     */
    public function show(int $grade, string $id)
    {
        if (!in_array($grade, AlgTaskDataService::GRADES, true)) {
            abort(404, "Класс {$grade} не поддерживается для алгебры");
        }

        $topicId = str_pad($id, 2, '0', STR_PAD_LEFT);
        $service = new AlgTaskDataService($grade);

        if (!$service->topicDataExists($topicId)) {
            abort(404, "Тема {$topicId} не найдена для {$grade} класса");
        }

        $topicMeta   = $service->getTopicMeta($topicId);
        $topicData   = $service->getTopicData($topicId);
        $blocks      = $service->getBlocks($topicId);
        $stats       = $service->getTopicStats($topicId);
        $allTopicIds = $service->getExistingTopicIds();
        $curriculum   = $topicData['curriculum'] ?? [];
        $microSkills  = $topicData['micro_skills'] ?? [];
        $homeworkSets = $topicData['homework_sets'] ?? [];

        return view('alg-topics.show', compact(
            'grade',
            'topicId',
            'topicMeta',
            'blocks',
            'stats',
            'allTopicIds',
            'curriculum',
            'microSkills',
            'homeworkSets'
        ));
    }
}
