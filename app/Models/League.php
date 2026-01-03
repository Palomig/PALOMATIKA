<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class League extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'slug',
        'name',
        'level',
        'icon',
        'color',
        'promote_top',
        'demote_bottom',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(LeagueParticipant::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('level');
    }

    public static function getByLevel(int $level): ?self
    {
        return static::where('level', $level)->first();
    }
}
