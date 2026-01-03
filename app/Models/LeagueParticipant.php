<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueParticipant extends Model
{
    protected $fillable = [
        'user_id',
        'league_id',
        'week_start',
        'xp_earned',
        'rank_position',
        'result',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function scopeCurrentWeek($query)
    {
        return $query->where('week_start', now()->startOfWeek());
    }

    public function scopeByWeek($query, $weekStart)
    {
        return $query->where('week_start', $weekStart);
    }
}
