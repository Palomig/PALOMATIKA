<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Задача — то, на что ученик даёт ответ.
 *
 * `legacy_task_key` — прежний адрес вида `topic_15_block_1_zadanie_3_task_5`.
 * Он кодировал позицию в JSON-файле, и по нему до сих пор ссылается история
 * попыток. Новые ссылки идут по `id`, который от перестановок не зависит.
 */
class Task extends Model
{
    protected $fillable = [
        'task_group_id', 'position', 'type', 'payload', 'answer', 'answer_src',
        'status', 'source', 'fipi_guid', 'intro_guid', 'legacy_task_key', 'svg_model',
    ];

    protected $casts = [
        'payload' => 'array',
        'svg_model' => 'array',
        'position' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(TaskGroup::class, 'task_group_id');
    }

    /** Тип задачи, иначе тип задания — как и в JSON, где task наследует zadanie. */
    public function resolvedType(): ?string
    {
        return $this->type ?: $this->group?->type;
    }
}
