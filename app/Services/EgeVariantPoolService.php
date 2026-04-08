<?php
namespace App\Services;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Models\OgeVariantPoolEntry;
use App\Models\OgeVariantPoolTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EgeVariantPoolService
{
    public function __construct(
        private readonly EgeTaskDataService $taskData,
        private readonly EgeVariantBuilderService $builder,
    ) {}

    public function getOrCreateVariant(User $user): OgeVariant
    {
        $attempted = OgeAttempt::where('student_id', $user->id)->pluck('variant_id');

        $poolEntry = OgeVariantPoolEntry::active()
            ->whereHas('variant', fn($q) => $q->where('exam_type', OgeVariant::EXAM_EGE))
            ->whereNotIn('variant_id', $attempted)
            ->inRandomOrder()
            ->first();

        if ($poolEntry) return $poolEntry->variant;

        return $this->generateNewVariant();
    }

    protected function generateNewVariant(int $maxRetries = 8): OgeVariant
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            $built = $this->builder->build(Str::random(12));
            if (empty($built['tasks'])) throw new \RuntimeException('No EGE production tasks');

            $fingerprint = md5(json_encode(
                array_map(fn($t) => $t['topic_id'] . '_' . ($t['id'] ?? ''), $built['tasks'])
            ));

            if (OgeVariantPoolEntry::where('task_fingerprint', $fingerprint)->exists()) continue;

            return DB::transaction(function () use ($built, $fingerprint) {
                $hash = strtolower(Str::random(6));
                while (OgeVariant::where('hash', $hash)->exists()) {
                    $hash = strtolower(Str::random(6));
                }

                $variant = OgeVariant::create([
                    'hash'        => $hash,
                    'exam_type'   => OgeVariant::EXAM_EGE,
                    'title'       => 'Вариант ЕГЭ',
                    'mode'        => OgeVariant::MODE_FULL,
                    'source'      => OgeVariant::SOURCE_MINIAPP,
                    'config_json' => ['tasks' => $built['tasks']],
                ]);

                $poolEntry = OgeVariantPoolEntry::create([
                    'variant_id'       => $variant->id,
                    'type'             => 'full',
                    'status'           => 'active',
                    'task_fingerprint' => $fingerprint,
                    'created_at'       => now(),
                ]);

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
        throw new \RuntimeException('Could not generate unique EGE variant');
    }
}
