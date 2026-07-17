<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonSession extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_LIVE  = 'live';
    public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'teacher_id',
        'schedule_id',
        'status',
        'starts_at',
        'ends_at',
        'invite_token',
        'join_code',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(LessonSchedule::class, 'schedule_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(LessonSessionTask::class)->orderBy('position');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(LessonSessionParticipant::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(LessonSessionAttempt::class);
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
