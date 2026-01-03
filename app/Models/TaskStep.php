<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStep extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'step_number',
        'instruction',
        'template',
        'correct_answers',
    ];

    protected $casts = [
        'correct_answers' => 'array',
        'created_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(StepBlock::class)->orderBy('sort_order');
    }

    public function attemptSteps(): HasMany
    {
        return $this->hasMany(AttemptStep::class);
    }
}
