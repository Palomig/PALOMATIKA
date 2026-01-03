<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'task_id',
        'session_id',
        'source',
        'homework_id',
        'duel_id',
        'is_completed',
        'is_correct',
        'started_at',
        'finished_at',
        'time_spent_seconds',
        'xp_earned',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'is_correct' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class);
    }

    public function duel(): BelongsTo
    {
        return $this->belongsTo(Duel::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(AttemptStep::class)->orderBy('step_number');
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
