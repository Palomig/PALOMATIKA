<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonSessionParticipant extends Model
{
    public const SOURCE_SCHEDULE = 'schedule';
    public const SOURCE_INVITE   = 'invite';
    public const SOURCE_CODE     = 'code';

    public $timestamps = false;

    protected $fillable = [
        'lesson_session_id',
        'student_id',
        'source',
        'joined_at',
        'locked_until',
        'released_at',
        'released_by',
    ];

    protected $casts = [
        'joined_at'    => 'datetime',
        'locked_until' => 'datetime',
        'released_at'  => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Лок активен: учитель не отпустил, время не вышло, урок не завершён.
     */
    public function hasActiveLock(): bool
    {
        return $this->released_at === null
            && $this->locked_until !== null
            && $this->locked_until->greaterThan(now())
            && in_array($this->session->status, [LessonSession::STATUS_DRAFT, LessonSession::STATUS_LIVE], true);
    }
}
