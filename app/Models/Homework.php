<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Homework extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'teacher_id',
        'title',
        'homework_type',
        'topic_id',
        'tasks_count',
        'assigned_at',
        'deadline_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'deadline_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'homework_tasks')
            ->withPivot('task_order')
            ->orderByPivot('task_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(HomeworkAssignment::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function isOverdue(): bool
    {
        return $this->deadline_at && $this->deadline_at->isPast();
    }

    public function getCompletionRateAttribute(): float
    {
        $total = $this->assignments()->count();
        if ($total === 0) {
            return 0;
        }
        $completed = $this->assignments()->where('status', 'completed')->count();
        return round($completed / $total * 100, 1);
    }
}
