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
    public function getOrCreateVariant(User $user, ?string $miniMode = null): OgeVariant
    {
        // Уровень берётся у сервиса данных: он же решает, из какого банка
        // собирать вариант и какие задачи считать уже решёнными.
        $level = $this->taskData->level();
        $exclude = StudentSolvedTasks::mapByTopic($user, OgeVariant::EXAM_EGE, $level);
        $modeConfig = null;
        if ($miniMode !== null) {
            $modeConfig = EgeVariantBuilderService::miniModes($level)[$miniMode] ?? null;
            if ($modeConfig === null) {
                throw new \InvalidArgumentException("Unknown EGE mini mode: {$miniMode}");
            }
        }

        $seed = Str::random(12);
        $built = $modeConfig === null
            ? $this->builder->build($seed, $exclude)
            : $this->builder->buildMini($seed, $modeConfig['topics'], $modeConfig['count'], $exclude);

        if (empty($built['tasks'])) throw new \RuntimeException('No EGE production tasks');

        $hash = strtolower(Str::random(6));
        while (OgeVariant::where('hash', $hash)->exists()) {
            $hash = strtolower(Str::random(6));
        }

        $mark = $level === EgeTaskDataService::LEVEL_BASE ? 'Б' : 'П';
        $variantMode = $modeConfig['variant_mode'] ?? OgeVariant::MODE_FULL;
        $title = $modeConfig === null
            ? "Вариант ЕГЭ ({$mark})"
            : "Мини-ЕГЭ ({$mark}) — {$modeConfig['title']}";
        $config = ['level' => $level, 'tasks' => $built['tasks']];
        if ($miniMode !== null) {
            $config['mini_mode'] = $miniMode;
        }

        return OgeVariant::create([
            'hash'        => $hash,
            'exam_type'   => OgeVariant::EXAM_EGE,
            'level'       => $level,
            'title'       => $title,
            'mode'        => $variantMode,
            'source'      => OgeVariant::SOURCE_MINIAPP,
            // Уровень внутри варианта: по нему анти-повтор отличает банки,
            // у которых номера заданий совпадают.
            'config_json' => $config,
        ]);
    }
}
