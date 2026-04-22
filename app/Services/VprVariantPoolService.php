<?php
namespace App\Services;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Models\OgeVariantPoolEntry;
use App\Models\OgeVariantPoolTask;
use App\Models\User;
use App\Support\VariantPoolSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VprVariantPoolService
{
    public function __construct(
        private readonly VprTaskDataService $taskData,
        private readonly VprVariantBuilderService $builder,
    ) {}

    public function getOrCreateVariant(User $user, string $type = 'full'): OgeVariant
    {
        $examType = 'vpr_' . $this->taskData->getGrade();
        $poolEntry = $this->findUnsolvedVariant($user, $examType, $type);

        if ($poolEntry) {
            return $poolEntry->variant;
        }

        return $this->generateNewVariant($examType, $type);
    }

    protected function findUnsolvedVariant(User $user, string $examType, string $type): ?OgeVariantPoolEntry
    {
        $attempted = OgeAttempt::where('student_id', $user->id)->pluck('variant_id');

        $query = OgeVariantPoolEntry::active()
            ->whereHas('variant', fn($q) => $q
                ->where('exam_type', $examType)
                ->whereRaw('CAST(config_json AS CHAR) NOT LIKE ?', ['%base64,%']))
            ->where('type', $type)
            ->whereNotIn('variant_id', $attempted)
            ->inRandomOrder();

        if (VariantPoolSchema::hasExamTypeColumn()) {
            $query->forExamType($examType);
        }

        return $query->first();
    }

    protected function generateNewVariant(string $examType, string $type, int $maxRetries = 8): OgeVariant
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            $built = $type === 'mixed'
                ? $this->builder->buildMini(Str::random(12))
                : $this->builder->build(Str::random(12));

            if (empty($built['tasks'])) {
                throw new \RuntimeException("No production tasks for $examType");
            }

            $fingerprint = md5(json_encode(
                array_map(fn($t) => $t['topic_id'] . '_' . ($t['id'] ?? ''), $built['tasks'])
            ));

            $duplicateQuery = OgeVariantPoolEntry::query()->where('task_fingerprint', $fingerprint);
            if (VariantPoolSchema::hasExamTypeColumn()) {
                $duplicateQuery->where('exam_type', $examType);
            } else {
                $duplicateQuery->whereHas('variant', fn ($q) => $q->where('exam_type', $examType));
            }

            if ($duplicateQuery->exists()) {
                continue;
            }

            return DB::transaction(function () use ($examType, $built, $fingerprint, $type) {
                $hash = strtolower(Str::random(6));
                while (OgeVariant::where('hash', $hash)->exists()) {
                    $hash = strtolower(Str::random(6));
                }

                $grade = (int) explode('_', $examType)[1];
                $isMini = $type === 'mixed';

                $variant = OgeVariant::create([
                    'hash'        => $hash,
                    'exam_type'   => $examType,
                    'title'       => $isMini ? "Мини-ВПР {$grade} класс" : "Вариант ВПР {$grade} класс",
                    'mode'        => $isMini ? OgeVariant::MODE_MINI_MIXED : OgeVariant::MODE_FULL,
                    'source'      => OgeVariant::SOURCE_MINIAPP,
                    'config_json' => ['tasks' => $built['tasks']],
                ]);

                $poolEntryPayload = [
                    'variant_id'       => $variant->id,
                    'type'             => $isMini ? 'mixed' : 'full',
                    'status'           => 'active',
                    'task_fingerprint' => $fingerprint,
                    'created_at'       => now(),
                ];

                if (VariantPoolSchema::hasExamTypeColumn()) {
                    $poolEntryPayload['exam_type'] = $examType;
                }

                $poolEntry = OgeVariantPoolEntry::create($poolEntryPayload);

                foreach ($built['tasks'] as $idx => $task) {
                    OgeVariantPoolTask::create([
                        'pool_id'        => $poolEntry->id,
                        'topic_id'       => $task['topic_id'],
                        'block_number'   => $task['block_number'] ?? 1,
                        'zadanie_number' => $task['zadanie_number'] ?? 1,
                        'task_id'        => $task['id'] ?? 0,
                        'sort_order'     => $idx + 1,
                    ]);
                }

                return $variant;
            });
        }

        throw new \RuntimeException("Could not generate unique VPR variant after {$maxRetries} retries");
    }
}
