<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StepBlock extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_step_id',
        'content',
        'content_html',
        'is_correct',
        'is_trap',
        'skill_id',
        'trap_explanation',
        'sort_order',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'is_trap' => 'boolean',
    ];

    public function taskStep(): BelongsTo
    {
        return $this->belongsTo(TaskStep::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
