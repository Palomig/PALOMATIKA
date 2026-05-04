<?php

namespace App\Http\Controllers;

use App\Services\GeomTaskDataService;

class GeomTopicController extends Controller
{
    /**
     * Список всех тем по классам (7–9) для геометрии.
     */
    public function index()
    {
        $gradeData = [];

        foreach (GeomTaskDataService::GRADES as $grade) {
            $service = new GeomTaskDataService($grade);
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

        return view('geom-topics.index', compact('gradeData'));
    }

    /**
     * Детальная страница темы для класса.
     */
    public function show(int $grade, string $id)
    {
        if (!in_array($grade, GeomTaskDataService::GRADES, true)) {
            abort(404, "Класс {$grade} не поддерживается для геометрии");
        }

        $topicId = str_pad($id, 2, '0', STR_PAD_LEFT);
        $service = new GeomTaskDataService($grade);

        if (!$service->topicDataExists($topicId)) {
            abort(404, "Тема {$topicId} не найдена для {$grade} класса");
        }

        $topicMeta   = $service->getTopicMeta($topicId);
        $blocks      = $service->getBlocks($topicId);
        $stats       = $service->getTopicStats($topicId);
        $allTopicIds = $service->getExistingTopicIds();

        return view('geom-topics.show', compact(
            'grade', 'topicId', 'topicMeta', 'blocks', 'stats', 'allTopicIds'
        ));
    }
}
