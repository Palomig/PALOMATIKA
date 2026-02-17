<?php

namespace App\Services;

use App\Models\TaskAnswerOverride;
use Illuminate\Support\Facades\Schema;

class TaskAnswerProvenanceService
{
    public function getOverridesForTopic(string $topicId): array
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
        try {
            if (!Schema::hasTable('task_answer_overrides')) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        return TaskAnswerOverride::query()
            ->with('updatedBy:id,name')
            ->where('task_key', 'like', "topic_{$topicId}_%")
            ->get()
            ->mapWithKeys(function (TaskAnswerOverride $override) {
                $name = $override->updatedBy?->name;

                return [
                    $override->task_key => [
                        'answer' => $override->answer,
                        'source' => $override->source,
                        'updated_by_name' => $name,
                        'source_label' => $this->sourceLabel($override->source, $name),
                    ],
                ];
            })
            ->all();
    }

    public function sourceLabel(string $source = 'ai', ?string $updatedByName = null): string
    {
        if ($source === 'manual') {
            return $updatedByName ? "Ручной by {$updatedByName}" : 'Ручной';
        }

        return 'AI';
    }
}
