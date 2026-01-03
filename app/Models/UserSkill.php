<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkill extends Model
{
    protected $fillable = [
        'user_id',
        'skill_id',
        'weight',
        'attempts_count',
        'correct_count',
        'last_practiced_at',
    ];

    protected $casts = [
        'weight' => 'decimal:3',
        'last_practiced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function getAccuracyAttribute(): ?float
    {
        if ($this->attempts_count === 0) {
            return null;
        }
        return round($this->correct_count / $this->attempts_count * 100, 1);
    }

    public function getMasteryLevelAttribute(): string
    {
        return match (true) {
            $this->weight >= 0.9 => 'master',
            $this->weight >= 0.7 => 'advanced',
            $this->weight >= 0.5 => 'intermediate',
            $this->weight >= 0.3 => 'beginner',
            default => 'novice',
        };
    }
}
