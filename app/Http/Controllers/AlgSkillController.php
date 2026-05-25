<?php

namespace App\Http\Controllers;

use App\Services\AlgTaskDataService;

class AlgSkillController extends Controller
{
    /**
     * Банк навыков класса: список всех skills, сгруппированных по группам.
     */
    public function index(int $grade)
    {
        if (!in_array($grade, AlgTaskDataService::GRADES, true)) {
            abort(404, "Класс {$grade} не поддерживается для алгебры");
        }

        $bundle = (new AlgTaskDataService($grade))->getSkillsBundle();
        if (empty($bundle['skills'] ?? null)) {
            abort(404, "Банк навыков для {$grade} класса ещё не загружен");
        }

        $groups = collect($bundle['groups'] ?? [])
            ->map(fn(array $group) => $group + [
                'skills' => array_values(array_filter(
                    $bundle['skills'],
                    fn(array $skill) => ($skill['group'] ?? null) === $group['id'],
                )),
            ])
            ->filter(fn(array $group) => !empty($group['skills']))
            ->values()
            ->all();

        $totalTasks = array_sum(array_map(
            fn(array $skill) => (int) ($skill['tasks_count'] ?? 0),
            $bundle['skills'],
        ));

        return view('alg-skills.index', [
            'grade'      => $grade,
            'groups'     => $groups,
            'skillsCount'=> count($bundle['skills']),
            'totalTasks' => $totalTasks,
        ]);
    }

    /**
     * Страница одного навыка: типы примеров + полные списки + домашки.
     */
    public function show(int $grade, string $slug)
    {
        if (!in_array($grade, AlgTaskDataService::GRADES, true)) {
            abort(404, "Класс {$grade} не поддерживается для алгебры");
        }

        $service = new AlgTaskDataService($grade);
        $skill   = $service->getSkillBySlug($slug);
        if (!$skill) {
            abort(404, "Навык «{$slug}» не найден для {$grade} класса");
        }

        $levels = array_map(function (array $level) {
            $level['representative_tasks'] = AlgTaskDataService::representativeTasks($level['tasks'] ?? []);
            return $level;
        }, $skill['levels'] ?? []);

        return view('alg-skills.show', [
            'grade'         => $grade,
            'skill'         => $skill,
            'levels'        => $levels,
            'homeworkSets'  => $skill['homework_sets'] ?? [],
        ]);
    }
}
