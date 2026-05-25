<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonSessionAttempt extends Model
{
    protected $fillable = [
        'lesson_session_id',
        'lesson_session_task_id',
        'student_id',
        'answer_raw',
        'is_correct',
        'answered_at',
    ];

    protected $casts = [
        'is_correct'  => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(LessonSessionTask::class, 'lesson_session_task_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
