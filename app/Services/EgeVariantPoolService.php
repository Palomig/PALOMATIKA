<?php
namespace App\Services;

use App\Models\OgeVariant;
use App\Models\User;
use App\Support\StudentSolvedTasks;
use Illuminate\Support\Str;

class EgeVariantPoolService
{
    public function __construct(
        private readonly EgeTaskDataService $taskData,
        private readonly EgeVariantBuilderService $builder,
    ) {}

    /**
     * Каждый старт — свежий рандомный вариант (пул выпилен).
     * Анти-повтор: решённые учеником задачи исключаются, пока банк темы не исчерпан.
     */
    public function getOrCreateVariant(User $user): OgeVariant
    {
        // Уровень берётся у сервиса данных: он же решает, из какого банка
        // собирать вариант и какие задачи считать уже решёнными.
        $level = $this->taskData->level();
        $exclude = StudentSolvedTasks::mapByTopic($user, OgeVariant::EXAM_EGE, $level);
        $built = $this->builder->build(Str::random(12), $exclude);

        if (empty($built['tasks'])) throw new \RuntimeException('No EGE production tasks');

        $hash = strtolower(Str::random(6));
        while (OgeVariant::where('hash', $hash)->exists()) {
            $hash = strtolower(Str::random(6));
        }

        $mark = $level === EgeTaskDataService::LEVEL_BASE ? 'Б' : 'П';

        return OgeVariant::create([
            'hash'        => $hash,
            'exam_type'   => OgeVariant::EXAM_EGE,
            'title'       => "Вариант ЕГЭ ({$mark})",
            'mode'        => OgeVariant::MODE_FULL,
            'source'      => OgeVariant::SOURCE_MINIAPP,
            // Уровень внутри варианта: по нему анти-повтор отличает банки,
            // у которых номера заданий совпадают.
            'config_json' => ['level' => $level, 'tasks' => $built['tasks']],
        ]);
    }
}
