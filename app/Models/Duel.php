<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Duel extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'challenger_id',
        'opponent_id',
        'topic_id',
        'tasks_count',
        'status',
        'challenger_correct',
        'challenger_time_seconds',
        'opponent_correct',
        'opponent_time_seconds',
        'winner_id',
        'accepted_at',
        'started_at',
        'finished_at',
        'expires_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function challenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'challenger_id');
    }

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opponent_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'duel_tasks')
            ->withPivot('task_order')
            ->orderByPivot('task_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
