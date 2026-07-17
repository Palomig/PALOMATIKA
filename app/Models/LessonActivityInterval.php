<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonActivityInterval extends Model
{
    public const KIND_PRESENT = 'present';
    public const KIND_AWAY    = 'away';

    // updated_at ведём вручную (heartbeat), created_at не нужен
    public $timestamps = false;

    protected $fillable = [
        'lesson_session_id',
        'student_id',
        'kind',
        'started_at',
        'ended_at',
        'updated_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }
}
