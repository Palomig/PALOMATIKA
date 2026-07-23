<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Homework extends Model
{
    protected $table = 'homeworks';

    public $timestamps = false;

    protected $fillable = [
        'teacher_id',
        'lesson_session_id',
        'title',
        'homework_type',
        'tasks_count',
        'variant_hash',
        'topic_number',
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

    public function assignments(): HasMany
    {
        return $this->hasMany(HomeworkAssignment::class);
    }

    public function topicTasks(): HasMany
    {
        return $this->hasMany(HomeworkTopicTask::class)->orderBy('task_order');
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
