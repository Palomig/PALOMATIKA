<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttemptStep extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attempt_id',
        'task_step_id',
        'step_number',
        'is_correct',
        'started_at',
        'finished_at',
        'time_spent_seconds',
        'attempts_count',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    public function taskStep(): BelongsTo
    {
        return $this->belongsTo(TaskStep::class);
    }

    public function blockSelections(): HasMany
    {
        return $this->hasMany(StepBlockSelection::class);
    }
}
