<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OgeAttemptScoring extends Model
{
    protected $fillable = [
        'attempt_id',
        'task_number',
        'is_correct',
        'correct_answer',
        'checked_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'checked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OgeAttempt::class, 'attempt_id');
    }
}

