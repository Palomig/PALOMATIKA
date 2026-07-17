<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonSessionTask extends Model
{
    protected $fillable = [
        'lesson_session_id',
        'assigned_student_id',
        'position',
        'bank',
        'grade',
        'topic_id',
        'skill_slug',
        'task_ref',
        'task_payload',
        'correct_answer',
    ];

    protected $casts = [
        'task_payload'        => 'array',
        'position'            => 'integer',
        'grade'               => 'integer',
        'assigned_student_id' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }

    public function assignedStudent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_student_id');
    }

    /** Задача видима ученику: общая (null) или персонально его. */
    public function visibleTo(int $studentId): bool
    {
        return $this->assigned_student_id === null
            || (int) $this->assigned_student_id === $studentId;
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(LessonSessionAttempt::class);
    }
}
