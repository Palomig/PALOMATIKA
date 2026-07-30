<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'notified_at',
        'reminded_at',
        'reviewed_at',
        'reviewed_by',
        'debt_since',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'notified_at' => 'datetime',
        'reminded_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'debt_since' => 'datetime',
    ];

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function topicTaskSubmissions(): HasMany
    {
        return $this->hasMany(HomeworkTopicTaskSubmission::class);
    }

    /** Заметки учителя, написанные по этой домашке (та же копилка, что и с урока). */
    public function notes(): HasMany
    {
        return $this->hasMany(StudentNote::class, 'homework_assignment_id');
    }

    /** Долг — незавершённая работа, которую не закрыли к моменту выдачи новой. */
    public function isDebt(): bool
    {
        return $this->debt_since !== null && $this->status !== 'completed';
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
