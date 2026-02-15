<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OgeAttemptAnswer extends Model
{
    protected $fillable = [
        'attempt_id',
        'task_number',
        'current_answer',
        'commits_count',
        'first_committed_at',
        'last_committed_at',
        'is_final',
    ];

    protected $casts = [
        'first_committed_at' => 'datetime',
        'last_committed_at' => 'datetime',
        'is_final' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OgeAttempt::class, 'attempt_id');
    }
}

