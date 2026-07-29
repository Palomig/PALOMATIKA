<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Всё, что в файле темы лежит рядом с `blocks`: `topic_id`, `meta`,
 * `exam_type`, `svg_baked`, а у алгебры ещё `curriculum` и `micro_skills`.
 */
class TaskTopic extends Model
{
    protected $fillable = ['bank', 'grade', 'topic', 'payload'];

    protected $casts = [
        'payload' => 'array',
        'grade' => 'integer',
    ];
}
