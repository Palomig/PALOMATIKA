<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Поведенческое событие ученика на странице урока — сигналы списывания:
 * copy_task (скопировал условие), paste_answer (вставил в поле ответа),
 * resume (нажал «Продолжить» после отлучки).
 */
class LessonBehaviorEvent extends Model
{
    public const KIND_COPY_TASK    = 'copy_task';
    public const KIND_PASTE_ANSWER = 'paste_answer';
    public const KIND_RESUME       = 'resume';

    public const KINDS = [
        self::KIND_COPY_TASK,
        self::KIND_PASTE_ANSWER,
        self::KIND_RESUME,
    ];

    public $timestamps = false;

    protected $fillable = [
        'lesson_session_id',
        'student_id',
        'lesson_session_task_id',
        'kind',
        'meta',
        'occurred_at',
    ];

    protected $casts = [
        'meta'        => 'array',
        'occurred_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(LessonSessionTask::class, 'lesson_session_task_id');
    }
}
