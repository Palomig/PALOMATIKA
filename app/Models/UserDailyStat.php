<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDailyStat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'date',
        'online_seconds',
        'active_seconds',
        'tasks_started',
        'tasks_completed',
        'tasks_correct',
        'xp_earned',
        'sessions_count',
        'first_activity_at',
        'last_activity_at',
    ];

    protected $casts = [
        'date' => 'date',
        'first_activity_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getOnlineMinutesAttribute(): int
    {
        return (int) round($this->online_seconds / 60);
    }

    public function getActiveMinutesAttribute(): int
    {
        return (int) round($this->active_seconds / 60);
    }

    public function getAccuracyAttribute(): ?float
    {
        if ($this->tasks_completed === 0) {
            return null;
        }
        return round($this->tasks_correct / $this->tasks_completed * 100, 1);
    }

    public static function getOrCreateForDate(int $userId, $date = null): self
    {
        $date = $date ?? today();

        return static::firstOrCreate([
            'user_id' => $userId,
            'date' => $date,
        ]);
    }
}
