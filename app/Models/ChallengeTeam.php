<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChallengeTeam extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'challenge_id',
        'name',
        'total_xp',
        'rank_position',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'challenge_team_members', 'team_id')
            ->withPivot('xp_contributed', 'joined_at');
    }

    public function getMembersCountAttribute(): int
    {
        return $this->members()->count();
    }
}
