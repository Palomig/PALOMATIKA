<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeGameRun extends Model
{
    public const REASON_WRONG = 'wrong';
    public const REASON_TIMEOUT = 'timeout';
    public const REASON_ABANDONED = 'abandoned';

    protected $fillable = [
        'user_id',
        'slug',
        'score',
        'end_reason',
        'current_question',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'current_question' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'score' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }
}
