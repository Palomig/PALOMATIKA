<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonSessionParticipant extends Model
{
    public const SOURCE_SCHEDULE = 'schedule';
    public const SOURCE_INVITE   = 'invite';

    public $timestamps = false;

    protected $fillable = [
        'lesson_session_id',
        'student_id',
        'source',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
