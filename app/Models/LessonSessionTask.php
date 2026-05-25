<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonSessionTask extends Model
{
    protected $fillable = [
        'lesson_session_id',
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
        'task_payload' => 'array',
        'position'     => 'integer',
        'grade'        => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(LessonSessionAttempt::class);
    }
}
