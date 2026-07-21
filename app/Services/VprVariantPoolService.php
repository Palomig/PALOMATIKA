<?php
namespace App\Services;

use App\Models\OgeVariant;
use App\Models\User;
use App\Support\StudentSolvedTasks;
use Illuminate\Support\Str;

class VprVariantPoolService
{
    public function __construct(
        private readonly VprTaskDataService $taskData,
        private readonly VprVariantBuilderService $builder,
    ) {}

    /**
     * Каждый старт — свежий рандомный вариант (пул выпилен).
     * Анти-повтор: решённые учеником задачи исключаются, пока банк темы не исчерпан.
     */
    public function getOrCreateVariant(User $user, string $type = 'full'): OgeVariant
    {
        $examType = 'vpr_' . $this->taskData->getGrade();
        $isMini = $type === 'mixed';
        $exclude = StudentSolvedTasks::mapByTopic($user, $examType);

        $built = $isMini
            ? $this->builder->buildMini(Str::random(12), 5, $exclude)
            : $this->builder->build(Str::random(12), $exclude);

        if (empty($built['tasks'])) {
            throw new \RuntimeException("No production tasks for $examType");
        }

        $hash = strtolower(Str::random(6));
        while (OgeVariant::where('hash', $hash)->exists()) {
            $hash = strtolower(Str::random(6));
        }

        $grade = $this->taskData->getGrade();

        return OgeVariant::create([
            'hash'        => $hash,
            'exam_type'   => $examType,
            'title'       => $isMini ? "Мини-ВПР {$grade} класс" : "Вариант ВПР {$grade} класс",
            'mode'        => $isMini ? OgeVariant::MODE_MINI_MIXED : OgeVariant::MODE_FULL,
            'source'      => OgeVariant::SOURCE_MINIAPP,
            'config_json' => ['tasks' => $built['tasks']],
        ]);
    }
}
