<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStreak extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'streak_protected_until',
    ];

    protected $casts = [
        'last_activity_date' => 'date',
        'streak_protected_until' => 'date',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isProtected(): bool
    {
        return $this->streak_protected_until && $this->streak_protected_until->isFuture();
    }

    public function shouldResetStreak(): bool
    {
        if (!$this->last_activity_date) {
            return false;
        }

        if ($this->isProtected()) {
            return false;
        }

        return $this->last_activity_date->diffInDays(today()) > 1;
    }
}
