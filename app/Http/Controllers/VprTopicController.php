<?php

namespace App\Http\Controllers;

use App\Services\VprTaskDataService;
use Illuminate\Http\Request;

class VprTopicController extends Controller
{
    private const GRADES = [5, 6, 7, 8];

    private const GRADE_TOPIC_COUNT = [
        5 => 17,
        6 => 17,
        7 => 17,
        8 => 18,
    ];

    /**
     * Список всех тем по классам.
     */
    public function index()
    {
        $gradeData = [];

        foreach (self::GRADES as $grade) {
            $maxTopic = self::GRADE_TOPIC_COUNT[$grade];
            $service  = new VprTaskDataService($grade);
            $topics   = [];

            foreach ($service->getAllTopicsMeta() as $topicId => $meta) {
                if ((int) $topicId > $maxTopic) continue;

                $meta = $service->getTopicMeta($topicId);

                $topics[$topicId] = array_merge($meta, [
                    'exists' => $service->topicDataExists($topicId),
                    'stats'  => $service->topicDataExists($topicId)
                        ? $service->getTopicStats($topicId)
                        : null,
                ]);
            }

            $gradeData[$grade] = [
                'topics'     => $topics,
                'max_topic'  => $maxTopic,
            ];
        }

        return view('vpr-topics.index', compact('gradeData'));
    }

    /**
     * Детальная страница темы для класса.
     */
    public function show(int $grade, string $id)
    {
        if (!in_array($grade, self::GRADES)) {
            abort(404, "Класс {$grade} не поддерживается");
        }

        $topicId  = str_pad($id, 2, '0', STR_PAD_LEFT);
        $maxTopic = self::GRADE_TOPIC_COUNT[$grade];

        if ((int) $topicId < 1 || (int) $topicId > $maxTopic) {
            abort(404, "Задание {$topicId} не существует для {$grade} класса");
        }

        $service  = new VprTaskDataService($grade);
        $topicMeta = $service->getTopicMeta($topicId);
        $blocks    = $service->getBlocks($topicId);

        // Stats for display
        $total = 0;
        $blockCount = count($blocks);
        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $total += count($zadanie['tasks'] ?? []);
            }
        }
        $stats = ['tasks' => $total, 'blocks' => $blockCount];

        // All topic IDs for navigation
        $allTopicIds = array_map(
            fn($n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            range(1, $maxTopic)
        );

        return view('vpr-topics.show', compact(
            'grade', 'topicId', 'topicMeta', 'blocks', 'stats', 'allTopicIds'
        ));
    }

    /**
     * API: данные темы.
     */
    public function apiGetTopicData(int $grade, string $topicId)
    {
        if (!in_array($grade, self::GRADES)) {
            return response()->json(['success' => false, 'error' => 'Invalid grade'], 404);
        }

        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
        $service  = new VprTaskDataService($grade);

        if (!$service->topicDataExists($topicId)) {
            return response()->json(['success' => false, 'error' => 'Topic data not found'], 404);
        }

        return response()->json([
            'success'  => true,
            'grade'    => $grade,
            'topic_id' => $topicId,
            'meta'     => $service->getTopicMeta($topicId),
            'stats'    => $service->getTopicStats($topicId),
            'blocks'   => $service->getBlocks($topicId),
        ]);
    }
}
