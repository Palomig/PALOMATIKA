<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'homework_id',
        'student_id',
        'status',
        'tasks_total',
        'tasks_completed',
        'tasks_correct',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->tasks_total === 0) {
            return 0;
        }
        return round($this->tasks_completed / $this->tasks_total * 100, 1);
    }

    public function getAccuracyAttribute(): ?float
    {
        if ($this->tasks_completed === 0) {
            return null;
        }
        return round($this->tasks_correct / $this->tasks_completed * 100, 1);
    }
}
