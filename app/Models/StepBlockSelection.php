<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StepBlockSelection extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attempt_step_id',
        'step_block_id',
        'position',
        'is_correct',
        'selected_at',
        'skill_id',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'selected_at' => 'datetime',
    ];

    public function attemptStep(): BelongsTo
    {
        return $this->belongsTo(AttemptStep::class);
    }

    public function stepBlock(): BelongsTo
    {
        return $this->belongsTo(StepBlock::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
